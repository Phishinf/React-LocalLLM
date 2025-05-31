<?php
// chat_interface.php - Full featured chat interface
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>VINOHK Assistant - Mary </title>
    <style>
        :root {
            --primary-color: #7f54b3;
            --primary-dark: #533b78;
            --secondary-color: #a46497;
            --light-color: #f7f7f7;
            --dark-color: #333;
            --success-color: #2ecc71;
            --warning-color: #f39c12;
            --danger-color: #e74c3c;
        }
        
        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }
        
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            line-height: 1.6;
            color: var(--dark-color);
            background-color: #f5f5f5;
        }
        
        .chat-container {
            max-width: 1200px;
            margin: 20px auto;
            display: grid;
            grid-template-columns: 1fr 300px;
            grid-gap: 20px;
            height: calc(100vh - 40px);
        }

        /* Improved responsive grid for mobile */
        @media (max-width: 768px) {
            .chat-container {
                grid-template-columns: 1fr;
                margin: 0;
                height: 100vh;
                max-width: 100%;
                grid-gap: 0;
            }
            
            .chat-box {
                border-radius: 0;
                height: 100vh;
            }
            
            .chat-header {
                padding: 10px;
                position: fixed;
                top: 0;
                left: 0;
                right: 0;
                z-index: 100;
            }
            
            .chat-messages {
                padding: 10px;
                padding-top: 60px; /* Space for fixed header */
                padding-bottom: 70px; /* Space for fixed input */
            }
            
            .message {
                max-width: 85%;
                font-size: 0.95rem;
            }
            
            .chat-input {
                position: fixed;
                bottom: 0;
                left: 0;
                right: 0;
                padding: 10px;
                background-color: white;
                box-shadow: 0 -2px 5px rgba(0,0,0,0.1);
                z-index: 100;
            }
            
            .chat-input button {
                padding: 0 12px;
                height: 40px;
            }
            
            .chat-input input {
                padding: 10px 12px;
                margin-right: 8px;
            }
            
            /* Mobile toggle for sidebar */
            .sidebar-toggle {
                display: block;
                position: fixed;
                bottom: 70px;
                right: 15px;
                background-color: var(--primary-color);
                color: white;
                border: none;
                border-radius: 50%;
                width: 50px;
                height: 50px;
                box-shadow: 0 2px 5px rgba(0,0,0,0.2);
                z-index: 101;
                display: flex;
                align-items: center;
                justify-content: center;
            }
            
            /* Mobile product sidebar */
            .product-sidebar {
                position: fixed;
                top: 0;
                left: 0;
                right: 0;
                bottom: 0;
                z-index: 200;
                transform: translateX(100%);
                transition: transform 0.3s ease;
                border-radius: 0;
            }
            
            .product-sidebar.active {
                transform: translateX(0);
            }
            
            .sidebar-close {
                position: absolute;
                top: 10px;
                right: 10px;
                background: none;
                border: none;
                color: white;
                font-size: 1.5rem;
                cursor: pointer;
            }
            
            /* Optimize buttons for touch */
            .chat-input button.mic-btn,
            .chat-input button.speaker-btn,
            .chat-input button.images-btn {
                width: 40px;
                height: 40px;
            }
        }

        /* Additional mobile touch optimizations */
        @media (max-width: 480px) {
            .message {
                max-width: 90%;
                padding: 8px 12px;
            }
            
            .product-card {
                max-width: 100%;
            }
            
            .user-images, .images-preview {
                max-width: 100%;
                height: auto;
            }
            
            /* Stack send button under input on very small screens */
            @media (max-width: 360px) {
                .chat-input {
                    flex-wrap: wrap;
                }
                
                .chat-input input[type="text"] {
                    flex: 1 0 100%;
                    order: 2;
                    margin-top: 8px;
                }
                
                .chat-input button#send-btn {
                    order: 3;
                    margin-top: 8px;
                    width: 100%;
                }
                
                .chat-input button.mic-btn,
                .chat-input button.speaker-btn,
                .chat-input button.images-btn {
                    order: 1;
                }
            }
        }        
        
        .chat-box {
            background: white;
            border-radius: 10px;
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
            display: flex;
            flex-direction: column;
            overflow: hidden;
        }
        
        .chat-header {
            padding: 15px;
            background-color: var(--primary-color);
            color: white;
            font-weight: bold;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .chat-header .refresh-btn {
        display: flex;
        align-items: center;
        background-color: rgba(255, 255, 255, 0.2);
        border-radius: 5px;
        padding: 5px 10px;
        transition: background-color 0.2s;
    }
        
        .chat-header .refresh-btn:hover {
            background-color: rgba(255, 255, 255, 0.3);
        }
        
        .chat-header .refresh-btn svg {
            margin-right: 5px;
        }
        
        .chat-header .refresh-btn .refresh-text {
            font-size: 0.8rem;
            font-weight: normal;
        }

        .chat-header .logo {
            display: flex;
            align-items: center;
        }
        
        .chat-header .logo img {
            height: 30px;
            margin-right: 10px;
        }
        
        .chat-messages {
            flex: 1;
            padding: 15px;
            overflow-y: auto;
            display: flex;
            flex-direction: column;
        }
        
        .message {
            margin-bottom: 15px;
            padding: 10px 15px;
            border-radius: 18px;
            max-width: 80%;
            position: relative;
            animation: fadeIn 0.3s ease-in-out;
        }
        
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(10px); }
            to { opacity: 1; transform: translateY(0); }
        }
        
        .user-message {
            background-color: #e6f2ff;
            align-self: flex-end;
            border-bottom-right-radius: 5px;
        }
        
        .bot-message {
            background-color: var(--light-color);
            align-self: flex-start;
            border-bottom-left-radius: 5px;
        }
        
        .message-time {
            font-size: 0.7rem;
            color: #999;
            margin-top: 5px;
            text-align: right;
        }
        
        .product-card {
            margin-top: 10px;
            margin-bottom: 15px;
            background-color: white;
            border-radius: 8px;
            overflow: hidden;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            max-width: 350px;
        }
        
        .product-card img {
            width: 100%;
            height: 180px;
            object-fit: cover;
        }
        
        .product-info {
            padding: 12px;
        }
        
        .product-title {
            font-weight: bold;
            margin-bottom: 5px;
        }
        
        .product-price {
            color: var(--secondary-color);
            font-weight: bold;
        }
        
        .product-description {
            font-size: 0.9rem;
            margin-top: 8px;
            color: #666;
        }
        
        .product-link {
            display: inline-block;
            margin-top: 10px;
            color: var(--primary-color);
            text-decoration: none;
            font-weight: bold;
            font-size: 0.9rem;
        }
        
        .product-link:hover {
            text-decoration: underline;
        }
        
        .chat-input {
            display: flex;
            padding: 15px;
            background-color: white;
            border-top: 1px solid #eee;
        }
        
        .chat-input input {
            flex: 1;
            padding: 12px 15px;
            border: 1px solid #ddd;
            border-radius: 25px;
            margin-right: 10px;
            font-size: 1rem;
        }
        
        .chat-input input:focus {
            outline: none;
            border-color: var(--primary-color);
        }
        
        .chat-input button {
            background-color: var(--primary-color);
            color: white;
            border: none;
            padding: 0 10px;
            border-radius: 20px;
            font-size: 1rem;
            cursor: pointer;
            transition: background-color 0.2s;
        }
        
        .chat-input button:hover {
            background-color: var(--primary-dark);
        }
        
        .chat-input button:disabled {
            background-color: #ccc;
            cursor: not-allowed;
        }
        
        .chat-input button.images-btn {
            background-color: var(--secondary-color);
            margin-right: 5px;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        
        .chat-input button.images-btn svg {
            height: 20px;
            width: 20px;
        }
        
        .product-sidebar {
            background: white;
            border-radius: 10px;
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
            overflow: hidden;
            display: flex;
            flex-direction: column;
        }
        
        .sidebar-header {
            padding: 15px;
            background-color: var(--primary-color);
            color: white;
            font-weight: bold;
        }
        
        .sidebar-content {
            flex: 1;
            padding: 15px;
            overflow-y: auto;
        }
        
        .sidebar-product {
            margin-bottom: 20px;
            padding-bottom: 20px;
            border-bottom: 1px solid #eee;
        }
        
        .sidebar-product:last-child {
            border-bottom: none;
        }
        
        .sidebar-product img {
            width: 100%;
            height: 150px;
            object-fit: cover;
            border-radius: 8px;
            margin-bottom: 10px;
        }
        
        .sidebar-product-title {
            font-weight: bold;
            margin-bottom: 5px;
        }
        
        .sidebar-product-price {
            color: var(--secondary-color);
            font-weight: bold;
            margin-bottom: 8px;
        }
        
        .sidebar-product-link {
            display: inline-block;
            color: var(--primary-color);
            text-decoration: none;
            font-weight: bold;
            font-size: 0.9rem;
        }
        
        .sidebar-product-link:hover {
            text-decoration: underline;
        }
        
        .typing-indicator {
            display: inline-block;
            padding: 10px 15px;
            background-color: var(--light-color);
            border-radius: 18px;
            margin-bottom: 15px;
            align-self: flex-start;
            animation: fadeIn 0.3s ease-in-out;
        }
        
        .typing-indicator span {
            height: 8px;
            width: 8px;
            background-color: #999;
            display: inline-block;
            border-radius: 50%;
            margin-right: 3px;
            animation: typing 1.3s infinite;
        }
        
        .typing-indicator span:nth-child(2) {
            animation-delay: 0.2s;
        }
        
        .typing-indicator span:nth-child(3) {
            animation-delay: 0.4s;
            margin-right: 0;
        }
        
        @keyframes typing {
            0% { transform: translateY(0); }
            50% { transform: translateY(-5px); }
            100% { transform: translateY(0); }
        }
        
        .hidden {
            display: none;
        }
        
        #file-upload {
            display: none;
        }
        
        .images-preview {
            max-width: 200px;
            max-height: 200px;
            margin: 10px;
            border-radius: 8px;
            border: 2px solid var(--primary-color);
        }
        
        .user-images {
            max-width: 200px;
            max-height: 200px;
            border-radius: 8px;
            margin-top: 10px;
        }
        .chat-input button.mic-btn,
        .chat-input button.speaker-btn {
            background-color: var(--secondary-color);
            margin-right: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        
        .chat-input button.mic-btn svg,
        .chat-input button.speaker-btn svg {
            height: 20px;
            width: 20px;
        }
        
        /* Make the input box slightly smaller to accommodate new buttons */
        .chat-input input[type="text"] {
            flex: 1;
        }
    </style>
</head>
<body>
    <div class="chat-container">
        <div class="chat-box">
            <div class="chat-header">
                <div class="logo">
                    <span>Print&Gift Sale Assistant - Mary</span>
                </div>
                <button id="refresh-btn" title="Refresh product catalog" class="refresh-btn">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                        <path d="M17.65 6.35C16.2 4.9 14.21 4 12 4C7.58 4 4.01 7.58 4.01 12C4.01 16.42 7.58 20 12 20C15.73 20 18.84 17.45 19.73 14H17.65C16.83 16.33 14.61 18 12 18C8.69 18 6 15.31 6 12C6 8.69 8.69 6 12 6C13.66 6 15.14 6.69 16.22 7.78L13 11H20V4L17.65 6.35Z" fill="white"/>
                    </svg>
                    <span class="refresh-text">Refresh Products</span>
                </button>
            </div>
            <div class="chat-messages" id="chat-messages">
                <div class="message bot-message">
                    Hello! I'm your Print&Gift Sale Assistant. My name is Mary. How can I help?
                    <div class="message-time">Just now</div>
                </div>
            </div>
            
            <div class="chat-input">
                <button class="mic-btn" id="mic-btn" title="Use microphone">
                    <svg width="20" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                        <path d="M12 14C13.66 14 15 12.66 15 11V5C15 3.34 13.66 2 12 2C10.34 2 9 3.34 9 5V11C9 12.66 10.34 14 12 14ZM11 5C11 4.45 11.45 4 12 4C12.55 4 13 4.45 13 5V11C13 11.55 12.55 12 12 12C11.45 12 11 11.55 11 11V5Z" fill="white"/>
                        <path d="M17 11C17 13.76 14.76 16 12 16C9.24 16 7 13.76 7 11H5C5 14.53 7.61 17.43 11 17.92V21H13V17.92C16.39 17.43 19 14.53 19 11H17Z" fill="white"/>
                    </svg>
                </button>
                <button class="speaker-btn" id="speaker-btn" title="Text to speech">
                    <svg width="20" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                        <path d="M3 9V15H7L12 20V4L7 9H3ZM16.5 12C16.5 10.23 15.48 8.71 14 7.97V16.02C15.48 15.29 16.5 13.77 16.5 12ZM14 3.23V5.29C16.89 6.15 19 8.83 19 12C19 15.17 16.89 17.85 14 18.71V20.77C18.01 19.86 21 16.28 21 12C21 7.72 18.01 4.14 14 3.23Z" fill="white"/>
                    </svg>
                </button>
                <button class="imagess-btn" id="imagess-btn" title="Upload an images">
                    <svg width="20" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                        <path d="M21 19V5C21 3.9 20.1 3 19 3H5C3.9 3 3 3.9 3 5V19C3 20.1 3.9 21 5 21H19C20.1 21 21 20.1 21 19ZM8.5 13.5L11 16.51L14.5 12L19 18H5L8.5 13.5Z" fill="white"/>
                    </svg>
                </button>
                <input type="file" id="file-upload" accept="images/*">
                <input type="text" id="user-input" placeholder="Type your message here...">
                <button id="send-btn">Send</button>
            </div>
        </div><!-- This closing div was missing in the right spot -->
        
        <div class="product-sidebar">
            <div class="sidebar-header">Featured Products</div>
            <div class="sidebar-content" id="sidebar-content">
                <!-- Featured products will be loaded here -->
                <div class="sidebar-product">
                    <img src="https://via.placeholder.com/300x200?text=Loading+Products" alt="Loading">
                    <div class="sidebar-product-title">Loading products...</div>
                    <div class="sidebar-product-price">Please wait</div>
                </div>
            </div>
        </div>


        <!-- images preview with cancel button for mobile -->
        <div class="images-preview-container" id="images-preview-container" style="display: none;">
            <img class="images-preview" id="images-preview" src="" alt="Preview">
            <div class="images-cancel" id="images-cancel">&times;</div>
        </div>

        <!-- Add these inputs for direct mobile camera and gallery access -->
        <input type="file" id="camera-input" accept="images/*" capture="camera" style="display: none;">
        <input type="file" id="gallery-input" accept="images/*" style="display: none;">
    </div>

    <script>
       document.addEventListener('DOMContentLoaded', function() {
            const chatMessages = document.getElementById('chat-messages');
            const userInput = document.getElementById('user-input');
            const sendBtn = document.getElementById('send-btn');
            const imagesBtn = document.getElementById('images-btn');
            const fileUpload = document.getElementById('file-upload');
            const refreshBtn = document.getElementById('refresh-btn');
            const sidebarContent = document.getElementById('sidebar-content');
            
            let imagesData = null;
            let imagesPreview = null;
            
            // Load featured products on page load
            fetchFeaturedProducts();
            
            // Send button click event
            sendBtn.addEventListener('click', function() {
                sendMessage();
            });
            
            // Enter key press event
            userInput.addEventListener('keypress', function(e) {
                if (e.key === 'Enter') {
                    sendMessage();
                }
            });
            
            // images button click event
            imagesBtn.addEventListener('click', function() {
                fileUpload.click();
            });
            
                    
            // Add these lines inside the DOMContentLoaded function
            const micBtn = document.getElementById('mic-btn');
            const speakerBtn = document.getElementById('speaker-btn');
            
            // Microphone button click event
            micBtn.addEventListener('click', function() {
                // Microphone functionality to be implemented
                console.log('Microphone button clicked');
                // Add your speech-to-text implementation here
            });
    
            // Speaker button click event
            speakerBtn.addEventListener('click', function() {
                // Text-to-speech functionality to be implemented
                console.log('Speaker button clicked');
                // Add your text-to-speech implementation here
            });

            // File upload change event
            fileUpload.addEventListener('change', function(e) {
                if (e.target.files.length > 0) {
                    const file = e.target.files[0];
                    const reader = new FileReader();
                    
                    reader.onload = function(event) {
                        // Store the base64 images data
                        imagesData = event.target.result.split(',')[1];
                        
                        // Add images preview if not already added
                        if (!imagesPreview) {
                            imagesPreview = document.createElement('img');
                            imagesPreview.className = 'images-preview';
                            userInput.parentNode.insertBefore(imagesPreview, userInput);
                        }
                        
                        // Update images preview
                        imagesPreview.src = event.target.result;
                    };
                    
                    reader.readAsDataURL(file);
                }
            });
  
            // Refresh button click event with improved error handling
            // Temporary workaround that skips the problematic API call
            refreshBtn.addEventListener('click', function() {
                refreshBtn.disabled = true;
                
                // Show loading message
                addBotMessage("Refreshing product catalog...");
                
                // Skip the problematic API call and directly fetch products
                setTimeout(() => {
                    try {
                        // Clear sidebar first
                        sidebarContent.innerHTML = '';
                        
                        // Add loading indicator to sidebar
                        const loadingElem = document.createElement('div');
                        loadingElem.className = 'sidebar-product';
                        loadingElem.innerHTML = `
                            <img src="https://via.placeholder.com/300x200?text=Loading+Products" alt="Loading">
                            <div class="sidebar-product-title">Loading products...</div>
                            <div class="sidebar-product-price">Please wait</div>
                        `;
                        sidebarContent.appendChild(loadingElem);
                        
                        // Fetch products
                        fetchFeaturedProducts();
                        
                        // Show success message
                        addBotMessage("Product catalog has been refreshed successfully.");
                    } catch (error) {
                        console.error('Error:', error);
                        addBotMessage("There was an error loading the products: " + error.message);
                    } finally {
                        refreshBtn.disabled = false;
                    }
                }, 500);
            });

            function sendMessage() {
                const message = userInput.value.trim();
                if (!message && !imagesData) return;
                
                // Add user message to chat
                if (message) {
                    addUserMessage(message);
                }
                
                // Add user images to chat if present
                if (imagesData) {
                    addUserimages();
                }
                
                // Show typing indicator
                showTypingIndicator();
                
                // Send message to server
                fetch('/api/chat', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify({
                        message: message,
                        images: imagesData
                    })
                })
                .then(response => response.json())
                .then(data => {
                    // Hide typing indicator
                    hideTypingIndicator();
                    
                    // Process the response
                    processResponse(data);
                })
                .catch(error => {
                    console.error('Error:', error);
                    hideTypingIndicator();
                    addBotMessage("I'm having trouble connecting to the server. Please try again later.");
                })
                .finally(() => {
                    // Clear user input and images
                    userInput.value = '';
                    clearimages();
                });
            }
            
            function addUserMessage(message) {
                const messageElem = document.createElement('div');
                messageElem.className = 'message user-message';
                messageElem.textContent = message;
                
                const timeElem = document.createElement('div');
                timeElem.className = 'message-time';
                timeElem.textContent = getCurrentTime();
                
                messageElem.appendChild(timeElem);
                chatMessages.appendChild(messageElem);
                
                // Scroll to bottom
                chatMessages.scrollTop = chatMessages.scrollHeight;
            }
            
            function addUserimages() {
                const imgElem = document.createElement('img');
                imgElem.className = 'user-images';
                imgElem.src = `data:images/jpeg;base64,${imagesData}`;
                
                const messageElem = document.createElement('div');
                messageElem.className = 'message user-message';
                messageElem.appendChild(imgElem);
                
                const timeElem = document.createElement('div');
                timeElem.className = 'message-time';
                timeElem.textContent = getCurrentTime();
                
                messageElem.appendChild(timeElem);
                chatMessages.appendChild(messageElem);
                
                // Scroll to bottom
                chatMessages.scrollTop = chatMessages.scrollHeight;
            }
            
            function addBotMessage(message) {
                const messageElem = document.createElement('div');
                messageElem.className = 'message bot-message';
                messageElem.textContent = message;
                
                const timeElem = document.createElement('div');
                timeElem.className = 'message-time';
                timeElem.textContent = getCurrentTime();
                
                messageElem.appendChild(timeElem);
                chatMessages.appendChild(messageElem);
                
                // Scroll to bottom
                chatMessages.scrollTop = chatMessages.scrollHeight;
            }
            
            function safeString(value) {
                if (value === null || value === undefined) {
                    return '';
                }
                if (typeof value === 'object') {
                    // For objects, return a meaningful representation if possible
                    return JSON.stringify(value).substring(0, 30) + '...';
                }
                return String(value);
            }

            function addProductCard(product, inSidebar = false) {
                if (inSidebar) {
                    // Add to sidebar
                    const productElem = document.createElement('div');
                    productElem.className = 'sidebar-product';
                    
                    // Use photo field from product data instead of imagess array
                    const imgSrc = product.photo 
                        ? product.photo 
                        : 'https://via.placeholder.com/300x200?text=No+images';
                    
                    productElem.innerHTML = `
                        <img src="${imgSrc}" alt="${safeString(product.name)}" onerror="this.src='https://via.placeholder.com/300x200?text=images+Error'">
                        <div class="sidebar-product-title">${safeString(product.name)}</div>
                        <div class="sidebar-product-price">${safeString(product.original_price)}</div>
                        <a href="${product.link || '#'}" target="_blank" class="sidebar-product-link">View Details</a>
                    `;
                    
                    sidebarContent.appendChild(productElem);
                } else {
                    // Add to chat
                    const productElem = document.createElement('div');
                    productElem.className = 'product-card';
                    
                    // Use photo field from product data
                    const imgSrc = product.photo 
                        ? product.photo 
                        : 'https://via.placeholder.com/300x200?text=No+images';
                    
                    // Use original_price from product data
                    const priceDisplay = product.original_price || product.sale_price || 'Price unavailable';
                    
                    // Get description and truncate if needed
                    const desc = product.description || '';
                    const shortDesc = desc.length > 100 ? desc.substring(0, 100) + '...' : desc;
                    
                    productElem.innerHTML = `
                        <img src="${imgSrc}" alt="${safeString(product.name)}" onerror="this.src='https://via.placeholder.com/300x200?text=images+Error'">
                        <div class="product-info">
                            <div class="product-title">${safeString(product.name)}</div>
                            <div class="product-price">${safeString(priceDisplay)}</div>
                            <div class="product-description">${safeString(shortDesc)}</div>
                            <a href="${product.link || '#'}" target="_blank" class="product-link">View Details</a>
                        </div>
                    `;
                    
                    const messageElem = document.createElement('div');
                    messageElem.className = 'message bot-message';
                    messageElem.appendChild(productElem);
                    
                    const timeElem = document.createElement('div');
                    timeElem.className = 'message-time';
                    timeElem.textContent = getCurrentTime();
                    
                    messageElem.appendChild(timeElem);
                    chatMessages.appendChild(messageElem);
                    
                    // Scroll to bottom
                    chatMessages.scrollTop = chatMessages.scrollHeight;
                }
            }
            
            function processResponse(data) {
                // Add only the text response to the chat
                if (data.message) {
                    addBotMessage(data.message);
                }
                
                // Update sidebar with products instead of adding them to the chat
                if (data.products && data.products.length > 0) {
                    updateSidebarWithProducts(data.products);
                }
            }

            function updateSidebarWithProducts(products) {
                // Clear sidebar content
                sidebarContent.innerHTML = '';
                
                // Update sidebar header to reflect the context
                const sidebarHeader = document.querySelector('.sidebar-header');
                if (sidebarHeader) {
                    sidebarHeader.textContent = 'Suggested Products';
                }
                
                // Add each product to the sidebar
                products.forEach(product => {
                    // Only add products to the sidebar, not to the chat
                    addProductCard(product, true);
                });
            }

            function showTypingIndicator() {
                const typingElem = document.createElement('div');
                typingElem.className = 'typing-indicator';
                typingElem.id = 'typing-indicator';
                typingElem.innerHTML = '<span></span><span></span><span></span>';
                chatMessages.appendChild(typingElem);
                
                // Scroll to bottom
                chatMessages.scrollTop = chatMessages.scrollHeight;
            }
            
            function hideTypingIndicator() {
                const typingElem = document.getElementById('typing-indicator');
                if (typingElem) {
                    typingElem.remove();
                }
            }
            
            function clearimages() {
                imagesData = null;
                if (imagesPreview) {
                    imagesPreview.remove();
                    imagesPreview = null;
                }
                fileUpload.value = '';
            }
            
            function getCurrentTime() {
                const now = new Date();
                let hours = now.getHours();
                const minutes = now.getMinutes().toString().padStart(2, '0');
                const ampm = hours >= 12 ? 'PM' : 'AM';
                
                hours = hours % 12;
                hours = hours ? hours : 12; // Convert 0 to 12
                
                return `${hours}:${minutes} ${ampm}`;
            }
            
            function fetchFeaturedProducts() {
                // Clear sidebar content
                sidebarContent.innerHTML = '';
                
                // Show loading placeholder
                const loadingElem = document.createElement('div');
                loadingElem.className = 'sidebar-product';
                loadingElem.innerHTML = `
                    <img src="https://via.placeholder.com/300x200?text=Loading+Products" alt="Loading">
                    <div class="sidebar-product-title">Loading products...</div>
                    <div class="sidebar-product-price">Please wait</div>
                `;
                sidebarContent.appendChild(loadingElem);
                
                // Fetch featured products
                fetch('/api/chat', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify({
                        message: "Show me featured products"
                    })
                })
                .then(response => response.json())
                .then(data => {
                    // Clear loading placeholder
                    sidebarContent.innerHTML = '';
                    
                    // Add products to sidebar
                    if (data.products && data.products.length > 0) {
                        data.products.forEach(product => {
                            addProductCard(product, true);
                        });
                    } else {
                        // No products found
                        const noProductsElem = document.createElement('div');
                        noProductsElem.textContent = "No featured products found.";
                        sidebarContent.appendChild(noProductsElem);
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    sidebarContent.innerHTML = '<div>Error loading products. Please try again later.</div>';
                });
            }
        });
    </script>
</body>
</html>
<?php
// End of file
?>