<?php
session_start();

// Database connection (for user info if needed)
$host = "localhost";
$username = "root";
$password = "";
$database = "perfume";

$conn = new mysqli($host, $username, $password, $database);

if ($conn->connect_error) {
    // Don't die on terms page, just continue without database
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Terms of Service - Scentrix Parfum</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Roboto', sans-serif;
            color: #333;
            background-color: #f9f9f9;
        }

        /* Navigation Bar */
        nav {
            background-color: #000; 
            padding: 0.5rem 1rem;
            display: flex;
            justify-content: space-between;
            align-items: center;
            position: sticky; 
            top: 0; 
            z-index: 1000; 
        }

        .logo {
            display: flex;
            align-items: center;
            font-family: 'Roboto', sans-serif;
        }

        .logo img {
            height: 30px;
            margin-right: 10px;
        }

        .logo h1 {
            font-size: 1.2rem;
            margin: 0;
            font-weight: normal;
            color: #fff;
        }

        nav ul {
            list-style-type: none;
            display: flex;
            align-items: center;
        }

        nav ul li {
            margin-left: 12px;
        }

        nav ul li a {
            text-decoration: none;
            color: #f0f0f0;
            padding: 8px 20px;
            font-size: 0.8rem;
            font-weight: bold;
            text-transform: uppercase;
            border-radius: 5px;
            transition: background-color 0.3s, color 0.3s;
            letter-spacing: 1px;
        }

        nav ul li a:hover {
            background-color: #444;
        }

        /* User Welcome Message */
        .welcome-message {
            background-color: #f0f0f0;
            padding: 10px 20px;
            text-align: center;
            font-size: 14px;
            color: #333;
        }

        .welcome-message a {
            color: #991304;
            text-decoration: none;
            font-weight: bold;
            margin-left: 10px;
        }

        .welcome-message a:hover {
            text-decoration: underline;
        }

        /* Hamburger Menu */
        .hamburger {
            display: none; 
            flex-direction: column;
            justify-content: space-between;
            width: 25px;
            height: 20px;
            cursor: pointer;
            position: absolute;
            right: 20px;
            top: 18px;
        }

        .hamburger div {
            height: 3px;
            background-color: #fff;
        }

        #navLinks {
            display: flex; 
            flex-direction: row;
            position: relative;
            text-align: center;
        }

        #navLinks li {
            margin: 10px 0;
        }

        @media screen and (max-width: 768px) {
            nav ul {
                display: none;
            }

            .hamburger {
                display: flex;
            }

            #navLinks {
                display: none;
                position: absolute;
                top: 60px;
                left: 0;
                right: 0;
                background-color: #000;
                padding: 1rem;
                width: 100%;
                text-align: center;
                flex-direction: column;
            }

            #navLinks li {
                margin: 12px 0;
            }

            #navLinks.active {
                display: flex; 
            }
        }

        /* Terms Container */
        .terms-container {
            padding: 40px 20px;
            background-color: #fff;
            max-width: 1000px;
            margin: 0 auto;
            min-height: calc(100vh - 160px);
        }

        .terms-title {
            text-align: center;
            font-size: 1.5rem;
            font-weight: 600;
            text-transform: uppercase;
            margin-bottom: 30px;
            position: relative;
        }

        .terms-title::after {
            content: "";
            display: block;
            width: 15%;
            height: 2px;
            background-color: #991304;
            margin: 10px auto;
        }

        .terms-content {
            max-width: 800px;
            margin: 0 auto;
            padding: 20px;
            text-align: left;
            font-size: 1rem;
            line-height: 1.6;
            margin-bottom: 40px;
        }

        h3 {
            font-size: 1.2rem;
            margin: 25px 0 10px 0;
            color: #991304;
            font-weight: 600;
        }

        p {
            margin-bottom: 15px;
        }

        ul {
            list-style-type: disc;
            margin-left: 20px;
            padding-left: 20px;
            margin-bottom: 20px;
        }

        ul li {
            margin-bottom: 10px;
        }

        .highlight-box {
            background-color: #f9f9f9;
            border-left: 4px solid #991304;
            padding: 15px 20px;
            margin: 20px 0;
            border-radius: 0 5px 5px 0;
        }

        .last-updated {
            text-align: center;
            font-style: italic;
            color: #666;
            margin-top: 30px;
            padding-top: 20px;
            border-top: 1px solid #eee;
        }

        /* Footer */
        footer {
            background-color: #000;
            color: #fff;
            padding: 20px;
            font-size: 0.9rem;
            text-align: center;
            margin-top: 40px;
        }

        footer p {
            margin: 10px 0;
        }

        .footer-links {
            list-style: none;
            padding: 0;
        }

        .footer-links li {
            display: inline;
            margin: 0 10px;
        }

        .footer-links li a {
            color: #fff;
            text-decoration: none;
            text-transform: uppercase;
            font-weight: bold;
            transition: color 0.3s;
        }

        .footer-links li a:hover {
            color: #991304;
        }

        @media (max-width: 768px) {
            footer {
                font-size: 0.8rem;
            }
            
            .terms-container {
                padding: 20px 10px;
            }
            
            .terms-title::after {
                width: 30%;
            }
        }
        
        @media (max-width: 480px) {
            .terms-title {
                font-size: 1.3rem;
            }
            
            .terms-title::after {
                width: 50%;
            }
            
            h3 {
                font-size: 1.1rem;
            }
        }
    </style>
</head>
<body>
    <!-- User Welcome Message (if logged in) -->
    <?php if (isset($_SESSION['logged_in']) && $_SESSION['logged_in'] === true): ?>
    <div class="welcome-message">
        Welcome, <?php echo htmlspecialchars($_SESSION['first_name']); ?>! 
        <a href="logout.php">Logout</a>
    </div>
    <?php endif; ?>

    <!-- Navigation Bar -->
    <nav>
        <div class="logo">
            <img src="images/kai5.png" alt="Logo">
            <h1>Scentrix Parfum</h1>
        </div>

        <!-- Hamburger Menu Icon -->
        <div class="hamburger" onclick="toggleMenu()">
            <div></div>
            <div></div>
            <div></div>
        </div>

        <!-- Navigation Links -->
        <ul id="navLinks">
            <li><a href="homepage.php">Home</a></li>
            <li><a href="collections.php">Collections</a></li>
            <li><a href="contact.php">Contact</a></li>
            <li><a href="about.php">About</a></li>
            <?php if (isset($_SESSION['logged_in']) && $_SESSION['logged_in'] === true): ?>
                <li><a href="myprofile.php"><?php echo htmlspecialchars($_SESSION['first_name']); ?></a></li>
                <li><a href="cart.php">Cart</a></li>
            <?php else: ?>
                <li><a href="index.php">Login</a></li>
                <li><a href="signup.php">Sign Up</a></li>
            <?php endif; ?>
        </ul>
    </nav>

    <!-- Terms of Service Content -->
    <div class="terms-container">
        <div class="terms-title">
            Terms of Service
        </div>
        
        <div class="terms-content">
            <div class="highlight-box">
                <p><strong>Last Updated:</strong> December 15, 2024</p>
                <p>Welcome to Scentrix Parfum! These Terms of Service ("Terms") govern your use of our website, products, and services. By using our website or purchasing from us, you agree to these Terms.</p>
            </div>

            <h3>1. Acceptance of Terms</h3>
            <p>By accessing or using our website, you agree to comply with and be bound by these Terms. If you do not agree with these Terms, please refrain from using our website.</p>

            <h3>2. Changes to Terms</h3>
            <p>We reserve the right to modify or update these Terms at any time. Any changes will be effective when posted on this page, and we encourage you to review the Terms periodically. Your continued use of our website after any changes constitutes your acceptance of the modified Terms.</p>

            <h3>3. User Account</h3>
            <p>To access certain features of our website, you may need to create an account. You are responsible for:</p>
            <ul>
                <li>Maintaining the confidentiality of your account details</li>
                <li>All activities that occur under your account</li>
                <li>Notifying us immediately of any unauthorized use of your account</li>
                <li>Ensuring that your account information is accurate and up-to-date</li>
            </ul>

            <h3>4. Use of Website</h3>
            <p>You agree to use our website only for lawful purposes and in accordance with these Terms. You may not:</p>
            <ul>
                <li>Use the website in any way that could damage, disable, or impair the website</li>
                <li>Interfere with other users' access to the website</li>
                <li>Attempt to gain unauthorized access to any part of the website</li>
                <li>Use any automated systems or software to extract data from the website</li>
            </ul>

            <h3>5. Products and Orders</h3>
            <p>We make every effort to ensure that the products listed on our website are accurate, but we cannot guarantee that the descriptions, prices, or availability are always up to date. We reserve the right to:</p>
            <ul>
                <li>Refuse or cancel any orders at our discretion</li>
                <li>Limit the quantity of items purchased per person, per household, or per order</li>
                <li>Discontinue any product at any time</li>
                <li>Correct any errors, inaccuracies, or omissions in product information</li>
            </ul>

            <h3>6. Payment</h3>
            <p>All payments for products are processed through secure third-party payment providers. You agree to:</p>
            <ul>
                <li>Provide accurate payment information</li>
                <li>Authorize us to charge the total amount of your order, including applicable taxes and shipping fees</li>
                <li>Ensure that you have sufficient funds or credit available to complete the transaction</li>
            </ul>

            <h3>7. Shipping and Delivery</h3>
            <p>We strive to ship your orders in a timely manner. Please note:</p>
            <ul>
                <li>Delivery times may vary depending on your location</li>
                <li>Shipping costs are calculated at checkout</li>
                <li>We are not responsible for delays caused by shipping carriers or other third-party service providers</li>
                <li>You are responsible for providing accurate shipping information</li>
            </ul>

            <h3>8. Returns and Refunds</h3>
            <p>Our return and refund policy is available on our website. Please review the policy before making a purchase. Key points include:</p>
            <ul>
                <li>Products may be returned within 30 days of delivery</li>
                <li>Items must be unused and in original condition with packaging</li>
                <li>Refunds will be processed to the original payment method</li>
                <li>Shipping costs for returns are the customer's responsibility unless the item is defective</li>
            </ul>

            <h3>9. Intellectual Property</h3>
            <p>All content on the website, including images, logos, text, and designs, is owned by Scentrix Parfum and is protected by copyright laws. You may not:</p>
            <ul>
                <li>Use, reproduce, or distribute any content from the website without our permission</li>
                <li>Modify, adapt, or create derivative works from our content</li>
                <li>Use our trademarks or logos without express written permission</li>
            </ul>

            <h3>10. Limitation of Liability</h3>
            <p>Scentrix Parfum shall not be liable for any indirect, incidental, special, consequential, or punitive damages resulting from:</p>
            <ul>
                <li>Your use or inability to use the website</li>
                <li>Any unauthorized access to or use of our servers</li>
                <li>Any interruption or cessation of transmission to or from the website</li>
                <li>Any bugs, viruses, or other harmful code that may be transmitted through the website</li>
            </ul>
            <p>Our total liability shall not exceed the amount you paid for the products in question.</p>

            <h3>11. Governing Law</h3>
            <p>These Terms shall be governed by and construed in accordance with the laws of the Philippines. Any disputes arising from these Terms shall be subject to the exclusive jurisdiction of the courts in Makati City, Metro Manila.</p>

            <h3>12. Contact Us</h3>
            <p>If you have any questions regarding these Terms or any other inquiries, please contact us at:</p>
            <ul>
                <li><strong>Email:</strong> legal@scentrixparfum.com</li>
                <li><strong>Phone:</strong> (02) 8123-4567</li>
                <li><strong>Address:</strong> 123 Fragrance Avenue, Makati City, Metro Manila, Philippines</li>
            </ul>

            <div class="last-updated">
                <p>These Terms of Service were last updated on December 15, 2024.</p>
                <p>By using our website, you acknowledge that you have read, understood, and agree to be bound by these Terms of Service.</p>
            </div>
        </div>
    </div>

    <!-- Footer -->
    <footer>
        <p>&copy; 2024 Scentrix Parfum. All rights reserved.</p>
        <ul class="footer-links">
            <li><a href="privacy.php">Privacy Policy</a></li>
            <li><a href="terms.php">Terms of Service</a></li>
            <li><a href="contact.php">Contact</a></li>
        </ul>
    </footer>

    <script>
        // Function to toggle menu
        function toggleMenu() {
            const navLinks = document.getElementById('navLinks');
            navLinks.classList.toggle('active'); 
        }
        
        // Close menu when clicking outside on mobile
        document.addEventListener('click', function(event) {
            const navLinks = document.getElementById("navLinks");
            const hamburger = document.querySelector('.hamburger');
            
            if (window.innerWidth <= 768) {
                if (!navLinks.contains(event.target) && !hamburger.contains(event.target)) {
                    navLinks.classList.remove('active');
                }
            }
        });
    </script>
    
</body>
</html>

<?php
// Close database connection
if (isset($conn)) {
    $conn->close();
}
?>