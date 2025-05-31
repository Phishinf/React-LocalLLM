import os
import json
import glob
import argparse
import re
from datetime import datetime

def normalize_price(price_str):
    """
    Normalizes price strings to a standard format.
    
    Args:
        price_str: The price string to normalize
        
    Returns:
        Normalized price string or None if input is None
    """
    if price_str is None:
        return None
    
    # Convert to string if it's not already
    if not isinstance(price_str, str):
        return f"${float(price_str):.2f}"
        
    # Remove all non-numeric characters except for decimal point
    price_str = price_str.strip()
    
    # Extract numbers using regex
    match = re.search(r'(\d+(?:\.\d+)?)', price_str)
    if match:
        try:
            price_value = float(match.group(1))
            return f"${price_value:.2f}"
        except ValueError:
            return price_str
    
    return price_str

def validate_product(product, file_path):
    """
    Validates a product object and reports any issues.
    
    Args:
        product: The product object to validate
        file_path: The source file path for error reporting
        
    Returns:
        List of validation messages
    """
    messages = []
    
    # Check for required fields
    if not product.get("name"):
        messages.append(f"Warning: Missing 'name' in product from {file_path}")
    
    # Validate price formats
    for price_field in ["original_price", "sale_price"]:
        if price_field in product and product[price_field] is not None:
            if not isinstance(product[price_field], (str, int, float)):
                messages.append(f"Warning: Invalid {price_field} format in {file_path}")
    
    return messages

def merge_json_files(data_dir="data", output_file="products.json", verbose=True):
    """
    Merges all JSON files in the data_dir into a single output_file.
    Handles cases where some files may not have all expected keys.
    
    Args:
        data_dir (str): Directory containing JSON files to merge
        output_file (str): Output file path for merged data
        verbose (bool): Whether to print detailed progress messages
    
    Returns:
        int: Number of products processed
    """
    # Check if data directory exists
    if not os.path.isdir(data_dir):
        print(f"Error: Directory '{data_dir}' not found.")
        return 0
    
    # Expected keys
    expected_keys = ["name", "link", "price", "type", "short_description", 
                     "description", "labels", "labels urls", "images"]
    
    # List to store all product data
    all_products = []
    
    # Keep track of statistics
    stats = {
        "total_files": 0,
        "processed_files": 0,
        "skipped_files": 0,
        "total_products": 0,
        "validation_warnings": 0
    }
    
    # Find all JSON files in the data directory
    json_files = glob.glob(os.path.join(data_dir, "*.json"))
    stats["total_files"] = len(json_files)
    
    if not json_files:
        print(f"No JSON files found in '{data_dir}'.")
        return 0
    
    # Process each JSON file
    for json_file in json_files:
        try:
            with open(json_file, 'r', encoding='utf-8') as f:
                # Try to load the JSON data
                try:
                    data = json.load(f)
                    
                    # Handle both single object and array formats
                    if isinstance(data, dict):
                        products = [data]
                    elif isinstance(data, list):
                        products = data
                    else:
                        if verbose:
                            print(f"Warning: Unexpected data format in {json_file}. Skipping.")
                        stats["skipped_files"] += 1
                        continue
                    
                    # Process each product
                    for product in products:
                        # Validate the product
                        validation_messages = validate_product(product, json_file)
                        if validation_messages and verbose:
                            for msg in validation_messages:
                                print(msg)
                            stats["validation_warnings"] += len(validation_messages)
                        
                        # Create a new product dict with null values for missing keys
                        processed_product = {}
                        for key in expected_keys:
                            value = product.get(key, None)
                            
                            # Normalize price fields
                            if key in ["original_price", "sale_price"]:
                                value = normalize_price(value)
                                
                            processed_product[key] = value
                        
                        all_products.append(processed_product)
                        stats["total_products"] += 1
                    
                    if verbose:
                        print(f"Processed: {json_file}")
                    stats["processed_files"] += 1
                    
                except json.JSONDecodeError:
                    if verbose:
                        print(f"Error: Invalid JSON format in {json_file}. Skipping.")
                    stats["skipped_files"] += 1
                    
        except Exception as e:
            if verbose:
                print(f"Error processing {json_file}: {str(e)}")
            stats["skipped_files"] += 1
    
    # Add metadata to the output
    output_data = {
        "metadata": {
            "generated_at": datetime.now().isoformat(),
            "total_products": len(all_products),
            "source_directory": os.path.abspath(data_dir)
        },
        "products": all_products
    }
    
    # Write the merged data to the output file
    try:
        with open(output_file, 'w', encoding='utf-8') as f:
            json.dump(output_data, f, indent=2, ensure_ascii=False)
        
        print(f"\nSummary:")
        print(f"- Files processed: {stats['processed_files']}/{stats['total_files']}")
        print(f"- Files skipped: {stats['skipped_files']}")
        print(f"- Products merged: {stats['total_products']}")
        print(f"- Validation warnings: {stats['validation_warnings']}")
        print(f"- Output file: {os.path.abspath(output_file)}")
        
        return stats["total_products"]
    
    except Exception as e:
        print(f"Error writing to {output_file}: {str(e)}")
        return 0

if __name__ == "__main__":
    # Set up command line argument parsing
    parser = argparse.ArgumentParser(description='Merge JSON product files into a single file.')
    parser.add_argument('--data-dir', '-d', default='data', 
                        help='Directory containing JSON files (default: data)')
    parser.add_argument('--output', '-o', default='products.json',
                        help='Output file path (default: products.json)')
    parser.add_argument('--quiet', '-q', action='store_true',
                        help='Suppress detailed progress messages')
    
    args = parser.parse_args()
    
    # Run the merge process
    merge_json_files(data_dir=args.data_dir, output_file=args.output, verbose=not args.quiet)
