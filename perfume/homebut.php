<?php
// You can add PHP logic here if needed in the future
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Home-Log</title>

    <style>
        /* ---------- YOUR CSS STARTS HERE ---------- */

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Roboto', sans-serif;
            color: #333;
        }

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
            padding: 8px 25px;
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

        .nav-btn-login {
            color: #fff;
            background-color: #99130491;
        }

        .nav-btn-login:hover {
            background-color: #444444;
        }

        .nav-btn-signup {
            color: #ffffff;
            background-color: transparent;
            border: 2px solid #99130491;
        }

        .nav-btn-signup:hover {
            background-color: #99130491;
            color: #fff;
        }

        .hamburger {
            display: none;
            flex-direction: column;
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
        }

        @media screen and (max-width: 768px) {
            nav ul { display: none; }
            .hamburger { display: flex; }
            #navLinks { display: none; flex-direction: column; background: #000; width: 100%; }
            #navLinks.active { display: flex; }
        }

        .hero-section {
            position: relative;
            height: 90vh;
            background-image: url('../HOMEPAGE/images/perfume.jpg');
            background-size: cover;
            background-position: center 40%;
            display: flex;
            align-items: flex-start;
        }

        .hero-section::after {
            content: "";
            position: absolute;
            width: 100%;
            height: 100%;
            background-color: rgba(0,0,0,0.5);
        }

        .hero-content {
            position: absolute;
            top: 20%;
            left: 5%;
            color: white;
            width: 40%;
        }

        .hero-actions { display: flex; gap: 12px; margin-top: 20px; }

        .cta-btn {
            padding: 12px 25px;
            border-radius: 5px;
            color: #fff;
            text-decoration: none;
        }

        .cta-login { background-color: #670c02ed; }
        .cta-login:hover { background-color: #6c6c6c91; }

        .cta-signup {
            background-color: transparent;
            border: 2px solid #99130491;
        }
        .cta-signup:hover { background-color: #99130491; }

        .hero-note { margin-top: 10px; }

        .features-container {
            display: flex;
            justify-content: space-around;
            padding: 20px;
            flex-wrap: wrap;
        }

        .feature { text-align: center; max-width: 250px; }

        .feature-icon img { width: 50px; height: 50px; }

        .new-offers-title {
            text-align: center;
            font-size: 1.5rem;
            text-transform: uppercase;
            margin-top: 30px;
        }

        .newcomers-offers-container { padding-bottom: 40px; background: #fff; }

        .offers-section {
            display: flex;
            gap: 15px;
            flex-wrap: wrap;
            max-width: 900px;
            margin: auto;
            padding: 20px;
        }

        .offers-left, .offers-right {
            width: 48%;
            height: 250px;
            border-radius: 8px;
            position: relative;
            background-size: cover;
            background-position: center;
            overflow: hidden;
        }

        .offers-left {
            background-image: url('../HOMEPAGE/images/offer1.jpg');
        }

        .offers-right {
            background-image: url('../HOMEPAGE/images/offer 2.jpg');
        }

        .offers-left .content, .offers-right .content {
            position: absolute;
            top: 30%;
            left: 5%;
            color: #fff;
        }

        footer {
            background-color: #000; 
            color: #fff;
            text-align: center;
            padding: 20px;
        }

        /* ---------- END OF CSS ---------- */
    </style>
</head>

<body>

<!-- Navigation Bar -->
<nav>
    <div class="logo">
        <img src="../WEBSITE-50%/images/kai5.png" alt="Logo">
        <h1>Parfum</h1>
    </div>

    <div class="hamburger" onclick="toggleMenu()">
        <div></div><div></div><div></div>
    </div>

    <ul id="navLinks">
        <li><a href="contact.php">Contact</a></li>
        <li><a href="about-us.php">About</a></li>
        <li><a href="login.php" class="nav-btn-login">Log In</a></li>
        <li><a href="signup.php" class="nav-btn-signup">Sign Up</a></li>
    </ul>
</nav>

<!-- Hero -->
<div class="hero-section">
    <div class="hero-content">
        <h2>CHOOSE YOUR FAVORITE PERFUME</h2>
        <p>Discover our exclusive collection...</p>

        <div class="hero-actions">
            <a href="login.php" class="cta-btn cta-login">Log In to Shop</a>
            <a href="signup.php" class="cta-btn cta-signup">Create Account</a>
        </div>

        <div class="hero-note">
            <strong>Note:</strong> Preview scents below. Full shopping requires login.
        </div>
    </div>
</div>

<!-- Features -->
<div class="features-container">
    <div class="feature">
        <div class="feature-icon"><img src="../HOMEPAGE/images/shipped.png"></div>
        <div class="feature-title">Free Shipping</div>
    </div>

    <div class="feature">
        <div class="feature-icon"><img src="../HOMEPAGE/images/medal (1).png"></div>
        <div class="feature-title">100% Authentic</div>
    </div>

    <div class="feature">
        <div class="feature-icon"><img src="../HOMEPAGE/images/lock-lines.png"></div>
        <div class="feature-title">Secure Payment</div>
    </div>
</div>

<!-- Special Offers -->
<div class="newcomers-offers-container">
    <div class="new-offers-title">Special Offers for Newcomers</div>

    <div class="offers-section">
        <a href="signup.php" class="offers-left">
            <div class="content">
                <h2>Exclusive Welcome Discount</h2>
                <p>20% off your first purchase. Code: WELCOME20</p>
            </div>
        </a>

        <a href="login.php" class="offers-right">
            <div class="content">
                <h2>Free Shipping on First Order</h2>
                <p>No minimum purchase.</p>
            </div>
        </a>
    </div>
</div>

<!-- Footer -->
<footer>
    <p>&copy; 2024 RRJJ Scents. All rights reserved.</p>
    <ul class="footer-links">
        <li><a href="privacy-policy.php">Privacy Policy</a></li>
        <li><a href="terms-of-service.php">Terms of Service</a></li>
        <li><a href="contact.php">Contact</a></li>
    </ul>
</footer>

<script>
    function toggleMenu() {
        document.getElementById("navLinks").classList.toggle("active");
    }
</script>

</body>
</html>
