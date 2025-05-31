<?php
// Enhanced API handler with multimodal LLM support

// Set content type to JSON
header('Content-Type: application/json');

// Get the request path
$request_path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$api_endpoint = trim(str_replace('/api/', '', $request_path), '/');

// Process API requests based on endpoint
switch ($api_endpoint) {
    case 'chat':
        handleChatRequest();
        break;
    case 'process-image':
        handleImageRequest();
        break;
    case 'refresh':
        handleRefreshRequest();
        break;
    case 'search':
        handleSearchRequest();
        break;
    case 'faq':
        handleFaqRequest();
        break;
    default:
        // Invalid API endpoint
        http_response_code(404);
        echo json_encode(['error' => 'Invalid API endpoint']);
        break;
}

// Function to handle chat requests
function handleChatRequest() {
    // Get POST data
    $data = json_decode(file_get_contents('php://input'), true);
    
    if (!isset($data['message'])) {
        http_response_code(400);
        echo json_encode(['error' => 'Missing message parameter']);
        return;
    }
    
    $message = $data['message'];
    $imageData = isset($data['image']) ? $data['image'] : null;
    
    // Process the message with LLM integration
    $response = processUserMessage($message, $imageData);
    
    // Return the response
    echo json_encode($response);
}

// Function to handle image processing requests
function handleImageRequest() {
    // Check if an image was uploaded
    if (!isset($_FILES['image']) || $_FILES['image']['error'] !== UPLOAD_ERR_OK) {
        http_response_code(400);
        echo json_encode(['error' => 'No image uploaded or upload error']);
        return;
    }
    
    // Get the uploaded image
    $image_path = $_FILES['image']['tmp_name'];
    
    // Process the image with LLM integration
    $response = processUserImage($image_path);
    
    // Return the response
    echo json_encode($response);
}

// Function to handle refresh requests
function handleRefreshRequest() {
    // Include the product fetcher
    include_once 'product_fetcher_enhanced.php';
    
    // Refresh the product cache
    $success = refreshProductCache();
    
    // Return the response
    echo json_encode(['success' => $success]);
}

// Function to handle search requests
function handleSearchRequest() {
    // Get query parameters
    $query = isset($_GET['q']) ? $_GET['q'] : '';
    $category = isset($_GET['category']) ? $_GET['category'] : '';
    $limit = isset($_GET['limit']) ? intval($_GET['limit']) : 4;
    
    if (empty($query) && empty($category)) {
        http_response_code(400);
        echo json_encode(['error' => 'Missing search parameters']);
        return;
    }
    
    // Include the product fetcher
    include_once 'product_fetcher_enhanced.php';
    
    // Get search results
    if (!empty($query)) {
        $products = searchProducts($query, $limit);
    } else {
        $products = getProductsByCategory($category, $limit);
    }
    
    // Return the response
    echo json_encode([
        'query' => $query,
        'category' => $category,
        'count' => count($products),
        'products' => $products
    ]);
}

// Function to handle FAQ requests
function handleFaqRequest() {
    // Get query parameter
    $query = isset($_GET['q']) ? $_GET['q'] : '';
    
    // Include the product fetcher
    include_once 'product_fetcher_enhanced.php';
    
    // Get FAQ results
    $faqs = getFAQs($query);
    
    // Return the response
    echo json_encode([
        'query' => $query,
        'count' => count($faqs),
        'faqs' => $faqs
    ]);
}

// Function to process user messages and generate appropriate responses using LLM
function processUserMessage($message, $imageData = null) {
    // Initialize session if not already
    if (session_status() == PHP_SESSION_NONE) {
        session_start();
    }
    
    // Get conversation ID from session
    $conversation_id = isset($_SESSION['conversation_id']) ? $_SESSION['conversation_id'] : null;
    
    // Initialize response
    $response = [
        'message' => '',
        'products' => [],
        'faqs' => []
    ];
    
    // Prepare data for the LLM service
    $requestData = [
        'message' => $message,
        'conversation_id' => $conversation_id
    ];
    
    // Add image data if provided
    if ($imageData) {
        $requestData['image'] = $imageData;
    }
    
    // Call the LLM service
    try {
        //$config = include 'config.php';
        //$llmServiceUrl = $config['llm_service_url'];
        $llmServiceUrl = getenv('LLM_SERVICE_URL') ?: 'http://localhost:5100';
        
        // Initialize cURL session
        $ch = curl_init("{$llmServiceUrl}/process-text");
        
        // Set cURL options
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($requestData));
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Content-Type: application/json'
        ]);
        
        // Execute cURL session and get the response
        $result = curl_exec($ch);
        
        // Close cURL session
        curl_close($ch);
        
        // Parse the response JSON
        $llmResponse = json_decode($result, true);
        
        if (isset($llmResponse['response'])) {
            $response['message'] = $llmResponse['response'];
        }
        
        if (isset($llmResponse['products']) && is_array($llmResponse['products'])) {
            $response['products'] = $llmResponse['products'];
        }
        
        if (isset($llmResponse['conversation_id'])) {
            // Store conversation ID in session
            $_SESSION['conversation_id'] = $llmResponse['conversation_id'];
        }
        
        if (isset($llmResponse['faqs']) && is_array($llmResponse['faqs'])) {
            $response['faqs'] = $llmResponse['faqs'];
        }
    } catch (Exception $e) {
        // Log the error
        error_log("Error calling LLM service: " . $e->getMessage());
        
        // Set a fallback response
        $response['message'] = "I'm sorry, I'm having trouble processing your request. Please try again later.";
        
        // Include the product fetcher for fallback product recommendations
        include_once 'product_fetcher_enhanced.php';
        $response['products'] = getFeaturedProducts(2);
    }
    
    return $response;
}

// Function to process user images
function processUserImage($imagePath) {
    // Initialize response
    $response = [
        'message' => '',
        'products' => []
    ];
    
    // Call the LLM service for image processing
    try {
        $llmServiceUrl = getenv('LLM_SERVICE_URL') ?: 'http://localhost:5100';
        
        // Initialize cURL session
        $ch = curl_init("{$llmServiceUrl}/process-image");
        
        // Prepare form data with image file
        $postData = [
            'image' => new CURLFile($imagePath)
        ];
        
        // Set cURL options
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $postData);
        
        // Execute cURL session and get the response
        $result = curl_exec($ch);
        
        // Close cURL session
        curl_close($ch);
        
        // Parse the response JSON
        $llmResponse = json_decode($result, true);
        
        if (isset($llmResponse['response'])) {
            $response['message'] = $llmResponse['response'];
        }
        
        if (isset($llmResponse['products']) && is_array($llmResponse['products'])) {
            $response['products'] = $llmResponse['products'];
        }
    } catch (Exception $e) {
        // Log the error
        error_log("Error calling LLM image service: " . $e->getMessage());
        
        // Set a fallback response
        $response['message'] = "I'm having trouble analyzing this image. Could you tell me what you're looking for?";
        
        // Include the product fetcher for fallback product recommendations
        include_once 'product_fetcher_enhanced.php';
        $response['products'] = getFeaturedProducts(3);
    }
    
    return $response;
}

// Function to check if text contains any profanity or inappropriate content
function containsInappropriateContent($text) {
    // A very simple profanity filter - in a real implementation, you'd want something more robust
    $profanityList = ['badword1', 'badword2', 'offensive', 'inappropriate'];
    
    foreach ($profanityList as $word) {
        if (stripos($text, $word) !== false) {
            return true;
        }
    }
    
    return false;
}

// Function to sanitize user input
function sanitizeInput($input) {
    // Remove any HTML tags
    $sanitized = strip_tags($input);
    
    // Remove any potential SQL injection
    $sanitized = str_replace(['\'', '"', ';', '--'], '', $sanitized);
    
    return $sanitized;
}
