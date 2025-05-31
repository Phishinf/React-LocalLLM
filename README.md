# Print&Gift Chatbot Integration

This project provides a complete solution for integrating a smart chatbot into the Print&Gift website. The chatbot can answer product questions, provide FAQs, and handle customer inquiries by scraping actual data from the Print&Gift website.

## Features

- **Responsive Web Interface**: Beautiful Print&Gift homepage with integrated chatbot
- **Intelligent Chatbot**: Natural language processing to understand user intentions
- **Product Search**: Find gift products by category, type, or keywords
- **FAQ Integration**: Access to common questions and answers
- **Data Scraper**: Extract real data from the Print&Gift website
- **Image Processing**: Basic image recognition for similar product suggestions

## File Structure

```
├── index.php                     # Main entry point and router
├── api_handler_enhanced.php      # API endpoint handler with NLP capabilities
├── product_fetcher_enhanced.php  # Product data manager with scraped data support
├── p&gscraper.php                # Web scraper for Print&Gift website
├── p&ggift products-homepage.php     # Main Print&Gift homepage with chatbot
├── chat_interface.php            # Full chat interface with product sidebar
├── chat-widget.php               # Simple chat widget for embedding
├── debug.php                     # Diagnostic tool for troubleshooting
├── p&ggift products_data.json        # Scraped data storage (created by scraper)
└── products_cache.json           # Product cache (created automatically)
```

## Installation

1. Make sure you have PHP 7.4+ installed
2. Install dependencies:
   ```
   composer require symfony/dom-crawler
   composer require symfony/css-selector
   composer require guzzlehttp/guzzle
   ```
3. Copy all files to your web server directory
4. Set appropriate permissions:
   ```
   chmod 755 *.php
   chmod 644 *.json
   ```
5. Start the PHP server:
   ```
   php -S localhost:8100
   ```

## Usage

### Accessing the Website

- Main Homepage: `http://localhost:8100/`
- Full Chat Interface: `http://localhost:8100/chat`
- Simple Chat Widget: `http://localhost:8100/widget`
- Debug Tool: `http://localhost:8100/debug.php`

### Running the Scraper

To fetch the latest products and FAQs from the Print&Gift website:

```
php p&gscraper.php
```

This will create or update the `p&ggift products_data.json` file with the latest information.

### Integration Into the Homepage

The chatbot appears as a chat bubble icon (💬) in the bottom right corner of the homepage. Clicking this icon toggles the chatbot interface.

### API Endpoints

- `/api/chat` - Process chat messages
- `/api/process-image` - Handle image uploads
- `/api/search` - Search for products
- `/api/faq` - Access FAQ data
- `/api/refresh` - Refresh the product cache

## Customizing the Chatbot

### Changing the Appearance

You can modify the chat-widget.php and chat_interface.php files to change the colors, styles, and layout of the chatbot. The primary styling variables are defined at the top of each CSS section.

### Enhancing the Intelligence

The chatbot's intelligence is primarily controlled by the `analyzeUserIntent()` and `extractEntities()` functions in `api_handler_enhanced.php`. You can improve these functions by:

1. Adding more intent patterns and keywords
2. Implementing more sophisticated entity extraction
3. Integrating with a third-party NLP service

### Adding More Products

You can either:

1. Modify the scraper to fetch more products from additional pages
2. Add products manually to the `p&ggift products_data.json` file
3. Update the `generateSampleProducts()` function in `product_fetcher_enhanced.php`



## Troubleshooting

If you encounter issues:

1. Check `debug.php` to verify all components are working
2. Ensure proper file permissions (especially for .json files)
3. Verify PHP and dependencies are correctly installed
4. Check browser console for JavaScript errors
5. Enable error reporting in PHP for more details:
   ```php
   error_reporting(E_ALL);
   ini_set('display_errors', 1);
   ```
## Start the FASTAPI server

uvicorn main:app --host 0.0.0.0 --port 80

## License

This project is for demonstration purposes. All Print&Gift branding, imagery, and product information belongs to Print&Gift Group Limited.
