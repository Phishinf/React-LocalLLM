from flask import Flask, request, jsonify, session
import json
import os
import requests
from dotenv import load_dotenv
import base64
from PIL import Image
from io import BytesIO
import uuid
import threading
import time
import re

# Load environment variables from .env file
load_dotenv()

app = Flask(__name__)

conversations = {}

app.secret_key = "printngift_secret_key"  # for session management

# Config
OPENAI_API_KEY = os.getenv("OPENAI_API_KEY", "your-api-key")
PRODUCTS_JSON = "products.json"

# Load data from JSON files
def load_json(file_path):
    data = []
    try:
        with open(file_path, 'r', encoding='utf-8') as f:
            json_data = json.load(f)
            # Check if the file contains an object with a 'products' key
            if isinstance(json_data, dict) and 'products' in json_data:
                data = json_data['products']
            # Or if it's a direct array of products
            elif isinstance(json_data, list):
                data = json_data
            else:
                print(f"Unexpected JSON structure in {file_path}")
        print(f"Loaded {len(data)} items from {file_path}")
    except Exception as e:
        print(f"Error loading {file_path}: {e}")
    return data

# Load data on startup
products = load_json(PRODUCTS_JSON)

def extract_quotation_data(conversation_history):
    """Extract quotation data from conversation history"""
    quotation_data = {
        "customer_email": None,
        "company_name": None,
        "budget_per_pack": None,
        "number_of_packs": None,
        "occasion": None,
        "special_requests": None
    }
    
    # Analyze conversation for data points
    conversation_text = " ".join([msg["content"].lower() for msg in conversation_history if msg["role"] == "user"])
    
    # Email pattern
    email_match = re.search(r'\b[A-Za-z0-9._%+-]+@[A-Za-z0-9.-]+\.[A-Z|a-z]{2,}\b', conversation_text)
    if email_match:
        quotation_data["customer_email"] = email_match.group()
    
    # Budget pattern (looking for dollar amounts)
    budget_patterns = [
        r'\$(\d+(?:\.\d{2})?)',
        r'(\d+)\s*dollars?',
        r'budget.*?(\d+)',
        r'(\d+).*?per\s+(?:pack|person|pax)'
    ]
    for pattern in budget_patterns:
        budget_match = re.search(pattern, conversation_text)
        if budget_match:
            quotation_data["budget_per_pack"] = budget_match.group(1)
            break
    
    # Quantity pattern
    quantity_patterns = [
        r'(\d+)\s*(?:packs?|pieces?|units?|recipients?|people|pax)',
        r'need\s*(\d+)',
        r'(\d+)\s*employees?',
        r'(\d+)\s*clients?'
    ]
    for pattern in quantity_patterns:
        qty_match = re.search(pattern, conversation_text)
        if qty_match:
            quotation_data["number_of_packs"] = qty_match.group(1)
            break
    
    # Company name patterns (after keywords)
    company_patterns = [
        r'company\s+(?:is\s+)?([A-Za-z0-9\s&.-]+)',
        r'from\s+([A-Za-z0-9\s&.-]+)\s+(?:company|corp|ltd|pte)',
        r'work\s+at\s+([A-Za-z0-9\s&.-]+)',
        r'represent\s+([A-Za-z0-9\s&.-]+)'
    ]
    for pattern in company_patterns:
        company_match = re.search(pattern, conversation_text)
        if company_match:
            quotation_data["company_name"] = company_match.group(1).strip()
            break
    
    # Occasion patterns
    occasion_keywords = {
        "client appreciation": ["client", "appreciation", "thank"],
        "employee recognition": ["employee", "staff", "recognition", "achievement"],
        "chinese new year": ["cny", "chinese new year", "lunar new year"],
        "conference": ["conference", "seminar", "event", "workshop"],
        "birthday": ["birthday", "celebration"],
        "wedding": ["wedding", "marriage"],
        "graduation": ["graduation", "graduate"],
        "farewell": ["farewell", "goodbye", "leaving"]
    }
    
    for occasion, keywords in occasion_keywords.items():
        if any(keyword in conversation_text for keyword in keywords):
            quotation_data["occasion"] = occasion
            break
    
    return quotation_data

def generate_data_collection_prompt(quotation_data, conversation_history):
    """Generate targeted prompt based on missing data"""
    missing_data = [k for k, v in quotation_data.items() if not v]
    collected_data = {k: v for k, v in quotation_data.items() if v}
    
    if len(missing_data) == 0:
        # All data collected
        return f"""Perfect! I have all the information needed for your quotation:
✓ Company: {quotation_data['company_name']}
✓ Email: {quotation_data['customer_email']}
✓ Occasion: {quotation_data['occasion']}
✓ Quantity: {quotation_data['number_of_packs']} packs
✓ Budget: ${quotation_data['budget_per_pack']} per pack
✓ Requirements: {quotation_data['special_requests'] or 'Standard options'}

I'll prepare your customized quotation and send it to {quotation_data['customer_email']} within 2 working days. Thank you!"""
    
    # Acknowledge collected data and ask for missing pieces
    if collected_data:
        acknowledgment = "Great! I have: "
        acknowledgment += ", ".join([f"{k.replace('_', ' ')}: {v}" for k, v in collected_data.items()])
        acknowledgment += ". "
    else:
        acknowledgment = ""
    
    # Ask for most critical missing data first
    priority_order = ["occasion", "number_of_packs", "budget_per_pack", "company_name", "customer_email", "special_requests"]
    
    for data_point in priority_order:
        if data_point in missing_data:
            if data_point == "occasion":
                return acknowledgment + "What's the occasion for these gifts?"
            elif data_point == "number_of_packs":
                return acknowledgment + "How many recipients/packs do you need?"
            elif data_point == "budget_per_pack":
                return acknowledgment + "What's your ideal budget per pack/person?"
            elif data_point == "company_name":
                return acknowledgment + "Which company should I prepare this quotation for?"
            elif data_point == "customer_email":
                return acknowledgment + "What email should I send the quotation to?"
            elif data_point == "special_requests":
                return acknowledgment + "Any specific items you'd like included or special requirements?"
    
    return acknowledgment + "Let me gather a bit more information to prepare your perfect quotation."

def process_product_image(product):
    """Process product image URL - handle different possible formats"""
    try:
        # Debug: Print image information
        print(f"Processing image for product: {product.get('name', 'Unknown')}")
        print(f"  Has 'images' key: {'Yes' if 'images' in product else 'No'}")
        print(f"  Has 'photo' key: {'Yes' if 'photo' in product else 'No'}")
        
        image_url = None
        
        # First, check for 'photo' field (direct image URL)
        if 'photo' in product and product['photo']:
            image_url = product['photo']
            print(f"  Found photo field: {image_url}")
        
        # Then check for 'images' field with different formats
        elif 'images' in product and product['images']:
            images_data = product['images']
            print(f"  Images value type: {type(images_data)}")
            print(f"  Images value: {str(images_data)[:100]}")  # Show first 100 chars
            
            if isinstance(images_data, str):
                # Direct URL string
                image_url = images_data
                print(f"  Direct URL string: {image_url}")
            elif isinstance(images_data, list) and len(images_data) > 0:
                # List of URLs - take the first one
                image_url = images_data[0]
                print(f"  From list: {image_url}")
            elif isinstance(images_data, dict) and len(images_data) > 0:
                # Dictionary of URLs - take the first value
                image_url = list(images_data.values())[0]
                print(f"  From dict: {image_url}")
        
        # If it's a relative URL, convert to absolute
        if image_url and image_url.startswith('/'):
            # Update with your actual domain
            base_domain = "https://printngift.com"  # Replace with actual domain
            image_url = f"{base_domain}{image_url}"
            print(f"  Converted relative URL: {image_url}")
        
        # Fallback to placeholder if no valid image found
        if not image_url:
            image_url = "https://via.placeholder.com/300x200?text=No+Image"
            print(f"  Using placeholder: {image_url}")
        
        print(f"  Final processed URL: {image_url}")
        return image_url
        
    except Exception as e:
        print(f"Error processing image for {product.get('name', 'Unknown')}: {str(e)}")
        return "https://via.placeholder.com/300x200?text=Image+Error"

def process_product_for_response(product):
    """Process product for JSON response with proper image handling"""
    processed_product = product.copy()
    processed_product["photo"] = process_product_image(product)
    
    # Add discount badge information if available
    if product.get('has_bulk_discount', False):
        processed_product["discount_badge"] = "BULK DISCOUNT"
    
    # Ensure price formatting
    original_price = product.get('original_price', '')
    sale_price = product.get('sale_price', '')
    
    if sale_price and sale_price != original_price:
        processed_product["price_display"] = f"{sale_price} (was {original_price})"
        processed_product["has_discount"] = True
    else:
        processed_product["price_display"] = original_price or "Price on request"
        processed_product["has_discount"] = False
    
    return processed_product

@app.route('/process-text', methods=['POST'])
def process_text():
    """Process text requests from the chatbot"""
    # Get request data
    data = request.json
    if not data or 'message' not in data:
        return jsonify({"error": "No message provided"}), 400
    
    user_message = data['message']
    print(f"Received message: {user_message}")
    
    # Get or create a conversation ID
    conversation_id = data.get('conversation_id')
    
    if not conversation_id:
        conversation_id = str(uuid.uuid4())
    
    # Get or initialize conversation history
    if conversation_id not in conversations:
        conversations[conversation_id] = []
    
    # Add the user message to history
    conversations[conversation_id].append({"role": "user", "content": user_message})
    
    # Extract quotation data from conversation
    quotation_data = extract_quotation_data(conversations[conversation_id])
    
    # Check if we have all required data
    missing_data = [k for k, v in quotation_data.items() if not v]
    
    # If we have most data, prioritize data collection over product recommendations
    if len(missing_data) <= 2:
        focused_response = generate_data_collection_prompt(quotation_data, conversations[conversation_id])
        # Add assistant response to history
        conversations[conversation_id].append({"role": "assistant", "content": focused_response})
        
        # Get minimal relevant products
        relevant_products = search_products(user_message, 2)
        
        return jsonify({
            "response": focused_response,
            "products": [process_product_for_response(p) for p in relevant_products],
            "conversation_id": conversation_id,
            "quotation_data": quotation_data,
            "data_completion": f"{6 - len(missing_data)}/6 complete"
        })
    
    # Get relevant products
    relevant_products = search_products(user_message, 5)
    
    # Format product context based on our specific JSON structure with image processing
    product_context = []
    for p in relevant_products:
        # Process image URL - handle different possible formats
        image_url = process_product_image(p)
        
        product_info = {
            "name": p.get("name", ""),
            "original_price": p.get("original_price", ""),
            "sale_price": p.get("sale_price", ""),
            "description": p.get("description", "")[:200] + "..." if p.get("description") else "",
            "category": p.get("category", ""),
            "material": p.get("material", ""),
            "dimensions": p.get("dimensions", ""),
            "color": p.get("color", ""),
            "brand": p.get("brand", ""),
            "photo": image_url,  # Processed image URL
            "link": p.get("link", ""),
            "has_bulk_discount": p.get("has_bulk_discount", False),
            "formatted_discount": p.get("formatted_discount", "")
        }
        product_context.append(product_info)
    
    # Call LLM with conversation history
    response = call_openai_api_with_history(conversations[conversation_id], product_context)
    
    # Add assistant response to history
    conversations[conversation_id].append({"role": "assistant", "content": response})
    
    # Return response with quotation tracking
    return jsonify({
        "response": response,
        "products": [process_product_for_response(p) for p in relevant_products[:3]],
        "conversation_id": conversation_id,
        "quotation_data": quotation_data,
        "data_completion": f"{6 - len(missing_data)}/6 complete"
    })

@app.route('/process-image', methods=['POST'])
def process_image():
    """Process image uploads from the chatbot"""
    # Check if an image was uploaded
    if 'image' not in request.files:
        return jsonify({"error": "No image provided"}), 400
    
    # Get the image
    image_file = request.files['image']
    
    try:
        # Open the image using PIL
        img = Image.open(image_file)
        
        # Convert to RGB if needed (in case of RGBA, etc.)
        if img.mode != 'RGB':
            img = img.convert('RGB')
        
        # Resize if too large
        max_size = 1024
        if img.width > max_size or img.height > max_size:
            img.thumbnail((max_size, max_size))
        
        # Convert to base64 for OpenAI API
        buffered = BytesIO()
        img.save(buffered, format="JPEG")
        img_str = base64.b64encode(buffered.getvalue()).decode('utf-8')
        
        # Call OpenAI Vision API if available
        if OPENAI_API_KEY and OPENAI_API_KEY != "your-api-key":
            try:
                # Call Vision API
                description = call_vision_api(img_str)
            except Exception as e:
                print(f"Error calling Vision API: {e}")
                description = "I can see your image! Let me suggest some relevant gift options from our collection."
        else:
            description = "I can see your image! Let me suggest some relevant gift options from our collection."
        
        # Get diverse product categories
        gift_categories = ["electronics", "bags", "drinkware", "clothing", "premium", "home appliances"]
        
        # Get sample products from various categories with image processing
        suggested_products = []
        for category in gift_categories:
            # Look for products that might match the category
            category_products = [p for p in products if p.get("category", "").lower().find(category.lower()) != -1 or 
                               p.get("name", "").lower().find(category.lower()) != -1]
            if category_products and len(category_products) > 0:
                product = category_products[0].copy()
                product["photo"] = process_product_image(product)  # Process image
                suggested_products.append(product)
                if len(suggested_products) >= 3:
                    break
        
        # If we still need more products, add random ones
        while len(suggested_products) < 3 and products:
            import random
            random_product = random.choice(products).copy()
            random_product["photo"] = process_product_image(random_product)  # Process image
            if random_product not in suggested_products:
                suggested_products.append(random_product)
        
        return jsonify({
            "response": description,
            "products": [process_product_for_response(p) for p in suggested_products]
        })
        
    except Exception as e:
        print(f"Error processing image: {e}")
        return jsonify({
            "response": "I couldn't process this image. Please try another or describe what you're looking for.",
            "products": [process_product_for_response(p) for p in products[:3]] if products else []
        })

@app.route('/quotation-status/<conversation_id>', methods=['GET'])
def get_quotation_status(conversation_id):
    """Get the current quotation data collection status"""
    if conversation_id not in conversations:
        return jsonify({"error": "Conversation not found"}), 404
    
    quotation_data = extract_quotation_data(conversations[conversation_id])
    missing_data = [k for k, v in quotation_data.items() if not v]
    
    return jsonify({
        "conversation_id": conversation_id,
        "quotation_data": quotation_data,
        "completion_status": f"{6 - len(missing_data)}/6",
        "missing_fields": missing_data,
        "is_complete": len(missing_data) == 0
    })

@app.route('/export-quotation/<conversation_id>', methods=['GET'])
def export_quotation_data(conversation_id):
    """Export quotation data for team processing"""
    if conversation_id not in conversations:
        return jsonify({"error": "Conversation not found"}), 404
    
    quotation_data = extract_quotation_data(conversations[conversation_id])
    missing_data = [k for k, v in quotation_data.items() if not v]
    
    # Include conversation history for context
    conversation_summary = []
    for msg in conversations[conversation_id]:
        if msg["role"] == "user":
            conversation_summary.append(f"Customer: {msg['content']}")
        else:
            conversation_summary.append(f"Mary: {msg['content']}")
    
    export_data = {
        "timestamp": time.strftime("%Y-%m-%d %H:%M:%S"),
        "conversation_id": conversation_id,
        "quotation_ready": len(missing_data) == 0,
        "customer_details": {
            "email": quotation_data["customer_email"] or "NOT COLLECTED",
            "company": quotation_data["company_name"] or "NOT COLLECTED",
            "budget_per_pack": quotation_data["budget_per_pack"] or "NOT COLLECTED",
            "number_of_packs": quotation_data["number_of_packs"] or "NOT COLLECTED",
            "occasion": quotation_data["occasion"] or "NOT COLLECTED",
            "special_requests": quotation_data["special_requests"] or "NOT COLLECTED"
        },
        "missing_information": missing_data,
        "conversation_summary": conversation_summary,
        "next_steps": "Generate quotation and send to customer" if len(missing_data) == 0 else f"Collect remaining information: {', '.join(missing_data)}"
    }
    
    return jsonify(export_data)

@app.route('/all-quotations', methods=['GET'])
def get_all_quotations():
    """Get all quotation data for admin dashboard"""
    all_quotations = []
    
    for conv_id, messages in conversations.items():
        if not messages:
            continue
            
        quotation_data = extract_quotation_data(messages)
        missing_data = [k for k, v in quotation_data.items() if not v]
        
        # Only include conversations with some quotation data
        if len(missing_data) < 6:  # At least one field collected
            all_quotations.append({
                "conversation_id": conv_id,
                "completion_status": f"{6 - len(missing_data)}/6",
                "is_complete": len(missing_data) == 0,
                "customer_email": quotation_data["customer_email"],
                "company_name": quotation_data["company_name"],
                "occasion": quotation_data["occasion"],
                "last_activity": time.strftime("%Y-%m-%d %H:%M:%S")  # This could be enhanced with actual timestamps
            })
    
    return jsonify({
        "total_conversations": len(all_quotations),
        "complete_quotations": len([q for q in all_quotations if q["is_complete"]]),
        "incomplete_quotations": len([q for q in all_quotations if not q["is_complete"]]),
        "quotations": all_quotations
    })

def cleanup_old_conversations():
    """Remove conversations that are older than 30 minutes"""
    while True:
        time.sleep(1800)  # Run every 30 minutes
        current_time = time.time()
        to_remove = []
        
        for conv_id, messages in conversations.items():
            if not messages:
                continue
                
            # Check if conversation has been inactive for more than 30 minutes
            if 'timestamp' in conversations[conv_id]:
                last_activity = conversations[conv_id]['timestamp']
                if current_time - last_activity > 1800:  # 30 minutes
                    to_remove.append(conv_id)
        
        # Remove old conversations
        for conv_id in to_remove:
            del conversations[conv_id]
        
        print(f"Cleaned up {len(to_remove)} inactive conversations")

def call_openai_api_with_history(conversation_history, product_context):
    """Call the OpenAI API with conversation history"""
    # Build system prompt for corporate gifts retailer focused on quotation data collection
    system_prompt = """
    🎁 PRINTNGIFT.com Sales Assistant Mary – Quotation-Focused System Prompt
    
    Keep responses under 60 words. Focus on collecting quotation information efficiently.

    You are Mary, PrintnGift's experienced sales assistant specializing in corporate gifts and promotional items. Your primary mission is to gather the MANDATORY information needed to generate accurate quotations while providing helpful product guidance.

    🎯 CRITICAL MISSION: Collect These 6 MANDATORY Data Points:
    1. 📧 Customer Email (for quotation delivery)
    2. 🏢 Company Name (for personalized service)
    3. 💰 Budget Per Pack/Pax (pricing expectations)
    4. 📊 Number of Packs (quantity requirements)
    5. 🎉 Occasion (purpose of gifting)
    6. 📝 Special Requests (preferred items, pack contents, special requirements)

    🔄 CONVERSATION FLOW STRATEGY:
    - Start with warm greeting and occasion inquiry
    - Naturally gather information through helpful questions
    - Ask for missing data points conversationally
    - Once you have 4+ data points, guide toward completing the set
    - When you have all 6 points, offer to prepare quotation

    💡 SMART QUESTIONING TECHNIQUES:
    - "What's the occasion we're celebrating?" (Occasion)
    - "How many recipients are we gifting to?" (Number of Packs)
    - "What's your ideal budget per person/pack?" (Budget Per Pack)
    - "Which company should I prepare this quotation for?" (Company Name)
    - "What email should I send the quotation to?" (Customer Email)
    - "Any specific items you'd like included or special requirements?" (Special Requests)

    🎁 PRODUCT CATEGORIES (for recommendations):
    - Electronics & Tech Accessories
    - Premium Executive Gifts
    - Bags & Pouches
    - Drinkware & Food Containers
    - Corporate Apparel
    - Festive & Seasonal Gifts

    📋 COMMON OCCASIONS & APPROPRIATE RESPONSES:
    - **Client Appreciation**: Premium items, executive gifts, branded drinkware
    - **Employee Recognition**: Tech accessories, quality bags, branded apparel
    - **CNY/Festive**: Traditional hampers, premium food containers, festive packaging
    - **Conference/Events**: Practical giveaways, branded merchandise, bulk items
    - **Personal Celebrations**: Elegant gifts, personalized items, gift sets

    💰 BUDGET GUIDANCE:
    - Under $15: Promotional items (mugs, pens, basic tech)
    - $15-$50: Quality gifts (power banks, notebooks, bags)
    - $50-$100: Premium items (executive sets, quality drinkware)
    - $100+: Luxury gifts (high-end electronics, premium sets)

    🎯 RESPONSE PATTERNS:
    When customer provides information, acknowledge it and ask for the next missing piece:
    - "Great! For [occasion] gifts, I'd recommend [brief suggestion]. What's your budget per pack?"
    - "Perfect! [Number] packs for [occasion]. Which company should I prepare the quotation for?"
    - "Excellent! I have [list collected info]. May I get your email to send the detailed quotation?"

    ⚡ EFFICIENCY RULES:
    - Keep responses under 60 words
    - Ask for only 1-2 missing data points per response
    - Provide brief product suggestions to maintain engagement
    - Use customer's provided information to show you're listening
    - Guide conversation toward quotation completion

    🏁 QUOTATION COMPLETION:
    When you have all 6 data points, respond:
    "Perfect! I have everything needed:
    ✓ Company: [Company Name]
    ✓ Email: [Email]
    ✓ Occasion: [Occasion] 
    ✓ Quantity: [Number] packs
    ✓ Budget: $[Budget] per pack
    ✓ Requirements: [Special Requests]

    I'll prepare your customized quotation with suitable options and send it to [email] within 2 hours. Thank you!"

    📞 ESCALATION TRIGGERS:
    If customer asks about:
    - Urgent timeline (same day/next day)
    - Complex customization requirements
    - Large orders (500+ pieces)
    - International shipping
    → Offer to connect them with our specialist team

    Remember: Every conversation should progress toward collecting all 6 mandatory data points while maintaining a helpful, professional tone. You're not just selling products—you're gathering information to create perfect quotations.
    """
    
    # Add product context
    if product_context:
        system_prompt += "\n\n🛍️ Available Products:\n"
        for i, product in enumerate(product_context, 1):
            price_info = product['sale_price'] if product['sale_price'] else product['original_price']
            details = []
            if product['category']:
                details.append(f"Category: {product['category']}")
            if product['material']:
                details.append(f"Material: {product['material']}")
            if product['brand']:
                details.append(f"Brand: {product['brand']}")
            details_str = " | ".join(details) if details else ""
            
            system_prompt += f"{i}. {product['name']} - {price_info}\n"
            if details_str:
                system_prompt += f"   Details: {details_str}\n"
            if product['description']:
                system_prompt += f"   Description: {product['description']}\n"
    
    # Create messages array with system prompt and conversation history
    messages = [{"role": "system", "content": system_prompt}]
    
    # Only include the last several messages to prevent context limit issues
    max_history = 10  # Adjust based on your needs
    recent_history = conversation_history[-max_history:] if len(conversation_history) > max_history else conversation_history
    
    # Add conversation history
    messages.extend(recent_history)
    
    # Call OpenAI API
    response = requests.post(
        "https://api.openai.com/v1/chat/completions",
        headers={
            "Authorization": f"Bearer {OPENAI_API_KEY}",
            "Content-Type": "application/json"
        },
        json={
            "model": "gpt-4o",
            "messages": messages,
            "max_tokens": 600
        }
    )
    
    result = response.json()
    if 'choices' in result and len(result['choices']) > 0:
        return result['choices'][0]['message']['content']
    else:
        print(f"Unexpected OpenAI response: {result}")
        return fallback_response(conversation_history[-1]["content"], product_context)

def call_vision_api(image_base64):
    """Call the OpenAI Vision API for image analysis"""
    response = requests.post(
        "https://api.openai.com/v1/chat/completions",
        headers={
            "Authorization": f"Bearer {OPENAI_API_KEY}",
            "Content-Type": "application/json"
        },
        json={
            "model": "gpt-4-vision-preview",  # Vision model
            "messages": [
                {
                    "role": "user",
                    "content": [
                        {
                            "type": "text", 
                            "text": "Analyze this image and suggest what type of corporate gift or promotional item would be suitable based on what you see. Consider the context, setting, or items visible in the image."
                        },
                        {
                            "type": "image_url",
                            "image_url": {
                                "url": f"data:image/jpeg;base64,{image_base64}"
                            }
                        }
                    ]
                }
            ],
            "max_tokens": 300
        }
    )
    
    result = response.json()
    if 'choices' in result and len(result['choices']) > 0:
        return result['choices'][0]['message']['content']
    else:
        print(f"Unexpected OpenAI Vision response: {result}")
        return "Based on your image, I can suggest some relevant gift options from our collection. Let me show you some suitable corporate gifts!"

def fallback_response(message, product_context):
    """Generate a fallback response focused on quotation data collection"""
    message_lower = message.lower()
    
    # Check for quotation data patterns and respond accordingly
    if any(word in message_lower for word in ["email", "@", "send", "quotation", "quote"]):
        return "Perfect! What email address should I send your customized quotation to? I'll include several options based on your requirements."
    
    elif any(word in message_lower for word in ["company", "business", "organization", "firm"]):
        return "Great! Which company should I prepare this quotation for? This helps me customize the recommendations for your needs."
    
    elif any(word in message_lower for word in ["budget", "price", "cost", "spend", "$"]):
        return "Excellent! What's your ideal budget per pack or per person? This helps me suggest the most suitable options within your range."
    
    elif any(word in message_lower for word in ["quantity", "number", "how many", "people", "recipients", "packs"]):
        return "Perfect! How many gift packs or recipients are we preparing for? This helps determine the best bulk pricing options."
    
    elif any(word in message_lower for word in ["occasion", "event", "purpose", "reason", "celebration"]):
        return "Wonderful! What's the occasion for these gifts? This helps me recommend the most appropriate items and presentation."
    
    elif any(word in message_lower for word in ["special", "request", "prefer", "include", "want", "need"]):
        return "Great! Any specific items you'd like included in the packs or special requirements? This ensures we create exactly what you envision."
    
    # Occasion-specific responses that also gather data
    elif any(word in message_lower for word in ["client", "customer", "appreciation"]):
        return "Client appreciation gifts! How many clients are you gifting to, and what's your budget per pack? I'll suggest premium options that leave lasting impressions."
    
    elif any(word in message_lower for word in ["employee", "staff", "team", "recognition"]):
        return "Employee recognition is so important! How many team members, and what's your budget per person? I'll recommend items that boost morale and show appreciation."
    
    elif any(word in message_lower for word in ["chinese new year", "cny", "festive", "holiday"]):
        return "Perfect timing for CNY gifts! How many recipients and what's your budget per pack? I'll suggest traditional options with festive packaging."
    
    elif any(word in message_lower for word in ["conference", "event", "seminar", "workshop"]):
        return "Conference giveaways are great for engagement! How many attendees and what's your budget per pack? I'll suggest practical items with excellent branding potential."
    
    # Featured products query with data collection
    elif any(word in message_lower for word in ["featured", "popular", "recommend", "suggest", "show"]):
        return "I'd love to show you our featured items! First, what's the occasion and how many gift packs do you need? This helps me recommend the perfect options."
    
    # Default response that starts data collection
    return "Hi! I'm Mary from PrintnGift. I'd love to help you find perfect corporate gifts! What's the occasion, and how many gift packs do you need? This helps me suggest the best options for you."

def search_products(query, limit=5):
    """Enhanced search for corporate gifts products"""
    query_terms = query.lower().split()
    scored_products = []
    
    # Define category mappings for better search results
    category_keywords = {
        "electronics": ["power bank", "speaker", "charger", "tech", "gadget", "electronics", "usb", "wireless"],
        "bags": ["bag", "briefcase", "laptop", "tote", "pouch", "backpack", "organizer", "travel"],
        "drinkware": ["mug", "tumbler", "bottle", "cup", "coffee", "tea", "water", "drink", "thermos"],
        "clothing": ["shirt", "polo", "jacket", "cap", "hat", "apparel", "clothing", "uniform"],
        "premium": ["executive", "luxury", "premium", "high-end", "elegant", "sophisticated"],
        "home": ["home", "kitchen", "appliance", "household", "domestic"],
        "festive": ["festive", "holiday", "seasonal", "christmas", "chinese new year", "celebration"]
    }
    
    # Occasion-based keywords
    occasion_keywords = {
        "corporate": ["client", "business", "corporate", "professional", "executive", "office"],
        "employee": ["employee", "staff", "team", "recognition", "appreciation", "achievement"],
        "event": ["conference", "seminar", "event", "meeting", "workshop", "trade show"],
        "personal": ["birthday", "wedding", "anniversary", "graduation", "farewell", "personal"]
    }
    
    for product in products:
        score = 0
        
        # Ensure we're handling both string and None values safely
        name = product.get('name', '') or ''
        description = product.get('description', '') or ''
        category = product.get('category', '') or ''
        
        product_text = f"{name} {description} {category}".lower()
        
        # Basic keyword matching
        for term in query_terms:
            if term in product_text:
                score += 1
        
        # Category-specific bonus scoring
        for category_name, keywords in category_keywords.items():
            for keyword in keywords:
                if any(keyword in term for term in query_terms):
                    for product_keyword in keywords:
                        if product_keyword in product_text:
                            score += 3  # Higher score for category matches
                            break
        
        # Occasion-specific bonus scoring
        for occasion_name, keywords in occasion_keywords.items():
            for keyword in keywords:
                if any(keyword in term for term in query_terms):
                    # All products are suitable for corporate occasions, but some are more specific
                    score += 1
        
        # Exact name matches get highest priority
        if any(term in name.lower() for term in query_terms):
            score += 5
        
        if score > 0:
            scored_products.append((score, product))
    
    # Sort by score and return top matches
    scored_products.sort(reverse=True, key=lambda x: x[0])
    return [product for score, product in scored_products[:limit]]

if __name__ == '__main__':
    print(f"Starting PrintnGift Quotation-Focused Sales Service...")
    print(f"Sales Assistant: Mary")
    print(f"Mission: Collect 6 mandatory quotation data points")
    print(f"OpenAI API Key configured: {'Yes' if OPENAI_API_KEY and OPENAI_API_KEY != 'your-api-key' else 'No'}")
    print(f"Loaded {len(products)} products")
    
    print(f"\n🎯 MANDATORY QUOTATION DATA POINTS:")
    print(f"  1. Customer Email")
    print(f"  2. Company Name") 
    print(f"  3. Budget Per Pack/Pax")
    print(f"  4. Number of Packs")
    print(f"  5. Occasion")
    print(f"  6. Special Requests")
    
    print(f"\nProduct categories available:")
    
    # Show available categories
    categories = set()
    for product in products:
        if product.get('category'):
            categories.add(product.get('category'))
    
    for category in sorted(categories):
        count = len([p for p in products if p.get('category') == category])
        print(f"  - {category}: {count} products")
    
    # Show image field statistics
    print(f"\nImage field statistics:")
    photo_count = len([p for p in products if p.get('photo')])
    images_count = len([p for p in products if p.get('images')])
    print(f"  - Products with 'photo' field: {photo_count}")
    print(f"  - Products with 'images' field: {images_count}")
    print(f"  - Products with either image field: {len([p for p in products if p.get('photo') or p.get('images')])}")
    
    print(f"\nAPI endpoints:")
    print(f"  - POST /process-text (chat interface)")
    print(f"  - POST /process-image (image uploads)")
    print(f"  - GET /quotation-status/<conversation_id> (check data collection progress)")
    print(f"  - GET /export-quotation/<conversation_id> (export quotation data)")
    print(f"  - GET /all-quotations (admin dashboard)")
    
    host = os.getenv("HOST", "0.0.0.0")
    port = int(os.getenv("PORT", 5100))
    
    # Start the cleanup thread
    cleanup_thread = threading.Thread(target=cleanup_old_conversations, daemon=True)
    cleanup_thread.start()
    
    app.run(host=host, port=port, debug=True)