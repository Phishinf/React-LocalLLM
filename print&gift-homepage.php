<?php
// Singapore Print&Gift Homepage with Multiple Communication Channels
// This file serves the main homepage and includes chatbot and WhatsApp
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>PrintnGift - Corporate Gifts Singapore</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            line-height: 1.6;
            color: #333;
        }

        /* Header */
        .top-bar {
            background: linear-gradient(135deg, #4a90e2, #357abd);
            color: white;
            padding: 10px 0;
            font-size: 14px;
        }

        .top-bar .container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 0 20px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .top-bar .left {
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .top-bar .right {
            display: flex;
            gap: 20px;
        }

        .contact-info {
            display: flex;
            gap: 20px;
        }

        .contact-info span {
            display: flex;
            align-items: center;
            gap: 5px;
        }

        /* Navigation */
        .navbar {
            background: white;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            padding: 15px 0;
        }

        .navbar .container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 0 20px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .logo {
            display: flex;
            align-items: center;
            gap: 10px;
            font-size: 24px;
            font-weight: bold;
            color: #4a90e2;
        }

        .logo::before {
            content: "🎁";
            font-size: 30px;
        }

        .search-bar {
            flex: 1;
            max-width: 600px;
            margin: 0 30px;
            position: relative;
        }

        .search-bar input {
            width: 100%;
            padding: 12px 50px 12px 20px;
            border: 2px solid #e0e0e0;
            border-radius: 25px;
            font-size: 16px;
            outline: none;
            transition: border-color 0.3s;
        }

        .search-bar input:focus {
            border-color: #4a90e2;
        }

        .search-btn {
            position: absolute;
            right: 5px;
            top: 50%;
            transform: translateY(-50%);
            background: #4a90e2;
            color: white;
            border: none;
            padding: 8px 15px;
            border-radius: 20px;
            cursor: pointer;
        }

        .nav-right {
            display: flex;
            align-items: center;
            gap: 20px;
        }

        .contact-btn {
            background: #25d366;
            color: white;
            padding: 10px 20px;
            border-radius: 25px;
            text-decoration: none;
            font-weight: 500;
            transition: background 0.3s;
        }

        .contact-btn:hover {
            background: #1ea952;
        }

        .cart {
            position: relative;
            background: #f8f9fa;
            padding: 10px 20px;
            border-radius: 25px;
            font-weight: bold;
        }

        .cart-count {
            position: absolute;
            top: -5px;
            right: -5px;
            background: #e74c3c;
            color: white;
            border-radius: 50%;
            width: 20px;
            height: 20px;
            font-size: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        /* Main Navigation Menu */
        .main-nav {
            background: #f8f9fa;
            padding: 15px 0;
        }

        .main-nav .container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 0 20px;
        }

        .nav-menu {
            display: flex;
            gap: 30px;
            list-style: none;
        }

        .nav-menu li a {
            text-decoration: none;
            color: #333;
            font-weight: 500;
            padding: 10px 0;
            transition: color 0.3s;
        }

        .nav-menu li a:hover {
            color: #4a90e2;
        }

        /* Hero Section */
        .hero {
            background: linear-gradient(135deg, #4a90e2, #357abd);
            color: white;
            text-align: center;
            padding: 80px 0;
        }

        .hero h1 {
            font-size: 48px;
            margin-bottom: 20px;
        }

        .hero h2 {
            font-size: 64px;
            font-weight: bold;
            margin-bottom: 40px;
        }

        /* Content Section */
        .content {
            background: url('data:image/svg+xml,<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 1200 600"><rect fill="%23f0f0f0" width="1200" height="600"/><rect fill="%23d0d0d0" x="100" y="100" width="200" height="150" rx="10"/><rect fill="%23c0c0c0" x="350" y="120" width="180" height="120" rx="8"/><rect fill="%23b0b0b0" x="580" y="80" width="220" height="180" rx="12"/><rect fill="%23a0a0a0" x="850" y="110" width="200" height="140" rx="10"/></svg>') center/cover;
            padding: 100px 0;
            position: relative;
        }

        .content::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(0, 0, 0, 0.7);
        }

        .content-inner {
            position: relative;
            z-index: 2;
            max-width: 1200px;
            margin: 0 auto;
            padding: 0 20px;
            color: white;
        }

        .content h3 {
            font-size: 48px;
            margin-bottom: 30px;
            line-height: 1.2;
        }

        .content p {
            font-size: 18px;
            line-height: 1.6;
            max-width: 800px;
        }

        .content a {
            color: #4a90e2;
            text-decoration: underline;
        }

        /* Chat Icons */
        .chat-icons {
            position: fixed;
            bottom: 30px;
            right: 30px;
            z-index: 1000;
            display: flex;
            flex-direction: column;
            gap: 15px;
        }

        .chat-icon {
            width: 120px;
            height: 120px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            text-decoration: none;
            font-size: 24px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.3);
            transition: transform 0.3s, box-shadow 0.3s;
        }

        .chat-icon:hover {
            transform: scale(1.1);
            box-shadow: 0 6px 20px rgba(0,0,0,0.4);
        }

        .whatsapp-icon {
            background: #25d366;
        }

        .chat-support-icon {
            background: #4a90e2;
        }

        /* Responsive */
        @media (max-width: 768px) {
            .top-bar .container {
                flex-direction: column;
                gap: 10px;
            }

            .navbar .container {
                flex-direction: column;
                gap: 20px;
            }

            .search-bar {
                max-width: 100%;
                margin: 0;
            }

            .nav-menu {
                flex-wrap: wrap;
                gap: 15px;
                justify-content: center;
            }

            .hero h1 {
                font-size: 32px;
            }

            .hero h2 {
                font-size: 42px;
            }

            .content h3 {
                font-size: 32px;
            }

            .chat-icons {
                bottom: 20px;
                right: 20px;
            }

            .chat-icon {
                width: 50px;
                height: 50px;
                font-size: 20px;
            }
        }
    </style>
</head>
<body>
    <!-- Top Bar -->
    <div class="top-bar">
        <div class="container">
            <div class="left">
                <span>🎁 Need Business Gifts? Reach out to us now >>></span>
            </div>
            <div class="right">
                <div class="contact-info">
                    <span>📞 (65) 8224 2122</span>
                    <span>⏰ (65) 9748 4695</span>
                    <span>✉️ enquiries@printngift.com</span>
                </div>
                <div>
                    <span>HRnGift</span>
                    <span>|</span>
                    <span>Care Pack</span>
                </div>
            </div>
        </div>
    </div>

    <!-- Navigation -->
    <nav class="navbar">
        <div class="container">
            <div class="logo">
                print<span style="color: #25d366;">gift</span>
            </div>
            
            <div class="search-bar">
                <input type="text" placeholder="Search...">
                <button class="search-btn">🔍</button>
            </div>

            <div class="nav-right">
                <div class="cart">
                    $0.00 🛒
                    <span class="cart-count">0</span>
                </div>
            </div>
        </div>
    </nav>

    <!-- Main Navigation Menu -->
    <div class="main-nav">
        <div class="container">
            <ul class="nav-menu">
                <li><a href="#">New Arrivals</a></li>
                <li><a href="#">Premium Collections</a></li>
                <li><a href="#">Clothing ▼</a></li>
                <li><a href="#">Bags & Pouches</a></li>
                <li><a href="#">Drinkware & Food Containers ▼</a></li>
                <li><a href="#">See All ▼</a></li>
                <li><a href="#">Festival Gifts</a></li>
                <li><a href="#">Print ▼</a></li>
                <li><a href="#">Deals</a></li>
            </ul>
        </div>
    </div>

    <!-- Hero Section -->
    <section class="hero">
        <div class="container">
            <h1>PRINT&GIFT</h1>
            <h2>Corporate Gifts Singapore</h2>
        </div>
    </section>

    <!-- Content Section -->
    <section class="content">
        <div class="content-inner">
            <h3>Build Lasting Connections With Our Premium Gifts</h3>
            <p>
                Infuse personalised touch into each meaningful relationship you cherish. 
                <a href="#">Discover our wide collection of custom and quality gifts</a> 
                to WOW your valued clients!
            </p>
        </div>
    </section>

    <!-- Chat Icons -->
    <div class="chat-icons">
        <a href="https://wa.me/6582242122" class="chat-icon whatsapp-icon" title="WhatsApp">
            <img src="chaticon_2.jpg" alt="WhatsApp" style="width: 100%; height: 100%; border-radius: 100%; object-fit: cover;">
        </a>
        <a href="#" class="chat-icon chat-support-icon" title="Chat Support">
            <img src="chaticon_1.jpg" alt="Chat Support" style="width: 100%; height: 100%; border-radius: 100%; object-fit: cover;">
        </a>
    </div>
    <!-- Chatbot Container -->
    <div id="chatbot-container" class="chatbot-container">
        <iframe src="http://localhost:8100/chat" width="800" height="600" frameborder="0"></iframe>
    </div>
</body>
</html>
<?php
// End of file
?>