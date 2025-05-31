<?php
// Add these lines at the very top of index.php
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Save as placeholder_handler.php
if (isset($_SERVER['REQUEST_URI']) && strpos($_SERVER['REQUEST_URI'], '/api/placeholder/') === 0) {
    $parts = explode('/', $_SERVER['REQUEST_URI']);
    $width = isset($parts[3]) ? intval($parts[3]) : 300;
    $height = isset($parts[4]) ? intval($parts[4]) : 300;
    
    header('Content-Type: image/svg+xml');
    echo '<svg xmlns="http://www.w3.org/2000/svg" width="'.$width.'" height="'.$height.'" viewBox="0 0 '.$width.' '.$height.'" fill="none">
        <rect width="'.$width.'" height="'.$height.'" fill="#EEEEEE"/>
        <text x="50%" y="50%" font-family="Arial" font-size="20" fill="#999999" dominant-baseline="middle" text-anchor="middle">'.$width.'x'.$height.'</text>
    </svg>';
    exit;
}

// Main server file that handles requests and routes them appropriately
session_start();

// Set headers to prevent caching
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Cache-Control: post-check=0, pre-check=0", false);
header("Pragma: no-cache");

// Check if this is an API request
if (isset($_SERVER['REQUEST_URI']) && strpos($_SERVER['REQUEST_URI'], '/api/') === 0) {
    // Handle API requests
    include 'api_handler.php';
    exit;
}

// Check for specific page requests
$request = $_SERVER['REQUEST_URI'];

switch ($request) {
    case '/':
    case '':
    case '/index.php':
        include 'print&gift-homepage.php';
        break;
    case '/chat':
    case '/chat.php':
        include 'chat_interface.php';
        break;
    case '/widget':
    case '/widget.php':
        include 'chat-widget.php';
        break;
    default:
        // Check if file exists
        $filePath = ltrim($request, '/');
        if (file_exists($filePath)) {
            // Determine MIME type based on file extension
            $extension = pathinfo($filePath, PATHINFO_EXTENSION);
            switch ($extension) {
                case 'css':
                    header('Content-Type: text/css');
                    break;
                case 'js':
                    header('Content-Type: application/javascript');
                    break;
                case 'jpg':
                case 'jpeg':
                    header('Content-Type: image/jpeg');
                    break;
                case 'png':
                    header('Content-Type: image/png');
                    break;
                case 'gif':
                    header('Content-Type: image/gif');
                    break;
                // Add more MIME types as needed
            }
            
            // Output the file contents
            readfile($filePath);
            exit;
        } else {
            // 404 Not Found
            http_response_code(404);
            echo '<h1>404 Not Found</h1>';
            echo '<p>The page you requested could not be found.</p>';
        }
        break;
    }
?>