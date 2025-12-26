<?php
session_start();

// Check if user is logged in
if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    header("Location: index.php");
    exit();
}
?>
<!doctype html>
<html lang="en">
  <head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>HOMEPAGE - Perfume Store</title>
    <style>
   * {
        margin: 0;
        padding: 0;
        box-sizing: border-box;
    }

    body {
        font-family: 'Roboto', sans-serif;
        color: #333;
    }

    /* Navigation Bar */
    nav {
        background-color: #000; 
        padding: 0.4rem 1rem;
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
        font-style: 'Roboto Condensed', sans-serif;
    }

    nav ul {
        list-style-type: none;
        display: flex;
        align-items: center;
    }

    nav ul li {
        margin-left: 20px; 
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
    padding: 8px 20px; 
    transform: none; 
    margin: 0;
}

    
    .about-link {
        padding-right: 0; 
        margin-right: 20px; 
    }

    .icon-link {
        display: inline-flex;
        align-items: center;
        font-size: 1rem; 
        padding: 8px 10px; 
    }  

    .icon-link img {
        width: 20px; 
        height: 20px; 
        margin-left: 8px; 
        margin-left: 8px; 
    margin-right: 8px; 
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
        top: 15px;
        z-index: 101; 
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

        /* Show the menu when 'active' class is added */
        #navLinks.active {
            display: flex;
        }
    }

      /* Hero Section */
      .hero-section {
        position: relative;
        height: 90vh;
        background-image: url('images/perfume.jpg'); /* Updated path */
        background-size: cover;
        background-position: center 40%;
        display: flex;
        align-items: flex-start;
        justify-content: flex-start;
        overflow: hidden;
        z-index: 0; 
      }

      .hero-section::after {
        content: "";
        position: absolute;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        background-color: rgba(0, 0, 0, 0.5);
        z-index: 0;
      }

      .hero-content {
        position: absolute;
        top: 20%;
        left: 5%;
        z-index: 1;
        color: white;
        width: 40%;
        max-width: 500px;
        font-size: 4vw;
        line-height: 1.5;
        word-wrap: break-word;
        padding: 0 10px;
      }

      @media (max-width: 768px) {
        .hero-content {
          top: 25%;
          left: 10%;
          width: 80%;
          font-size: 8vw;
          text-align: center;
        }
      }

      .hero-section h2 {
        font-size: 2.5rem;
        margin-bottom: 20px;
        word-wrap: break-word;
        white-space: normal;
        letter-spacing: 1px;
        text-transform: uppercase;
      }

      .hero-section p {
        font-size: 1rem;
        color: #ddd;
        margin-bottom: 20px;
        word-wrap: break-word;
        white-space: normal;
      }

      .shop-now-btn {
        padding: 12px 25px;
        font-size: 1rem;
        background-color: #670c02ed;
        color: white;
        text-decoration: none;
        border-radius: 5px;
        transition: background-color 0.3s;
        display: inline-block;
        margin-top: 20px;
        opacity: 0.9;
      }

      .shop-now-btn:hover {
        background-color: #6c6c6c91;
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

      /* Features Section */
      .features-container {
        display: flex;
        justify-content: space-around;
        align-items: flex-start;
        padding: 20px;
        background-color: #f9f9f9;
        flex-wrap: wrap;
      }

      .feature {
        text-align: center;
        max-width: 250px;
        margin: 15px;
      }

      .feature-icon img {
        width: 50px;  
        height: 50px; 
        object-fit: cover; 
        margin: 0 auto; 
      }

      .feature-title {
        font-weight: bold;
        margin: 10px 0 5px;
        font-size: 1.1rem;
        color: #000; 
      }

      .feature-description {
        color: #000; 
        font-size: 14px;
      }

       
    * {
      margin: 0;
      padding: 0;
      box-sizing: border-box;
    }

    body {
      font-family: 'Roboto', sans-serif;
      background-color: #f9f9f9;
      color: #333;
      line-height: 1.5;
    }

    /* Newcomers Title */
    .new-offers-title {
      text-align: center;
      font-size: 1.5rem;
      font-weight: 600;
      text-transform: uppercase;
      margin-top: 30px;
      margin-bottom: 20px;
      position: relative;
    }

    .new-offers-title::after {
      content: "";
      display: block;
      width: 15%;  
      height: 2px;
      background-color: #991304;
      margin: 10px auto;
    }

    /* Responsive for smaller screens */
@media (max-width: 768px) {
  .new-offers-title {
    font-size: 1.5rem; 
    margin-top: 20px; 
    margin-bottom: 15px; 
  }

  .new-offers-title::after {
    width: 30%; 
  }
}

@media (max-width: 480px) {
  .new-offers-title {
    font-size: 1.3rem; 
    margin-top: 15px; 
    margin-bottom: 10px; 
  }

  .new-offers-title::after {
    width: 50%; 
  }
}

/* Newcomers Section */
.newcomers-offers-container {
  background-color: #ffffff;
  padding-top: 5px;
  padding-bottom: 40px;
}

.offers-section {
  display: flex;
  justify-content: space-between;
  align-items: flex-start; 
  gap: 15px;
  flex-wrap: wrap; 
  margin: 0 auto;
  max-width: 900px;
  padding: 20px;
}

/* Left and Right Column Containers */
.offers-left, .offers-right {
  position: relative;
  border-radius: 8px;
  width: 48%;  
  height: 250px; 
  background-size: cover;
  background-position: center;
  transition: transform 0.3s ease;
  opacity: 1;
}

/* Left Column */
.offers-left {
  background-image: url('images/offer1.jpg');
}

/* Right Column */
.offers-right {
  background-image: url('images/offer2.jpg');
}

.offers-left::after,
.offers-right::after {
  content: "";
  position: absolute;
  top: 0;
  left: 0;
  width: 100%;
  height: 100%;
  background-color: rgba(0, 0, 0, 0.3);
  z-index: 0;
}

/* Content (Text) inside the Left and Right columns */
.offers-left .content, .offers-right .content {
  position: absolute;
  top: 30%;
  left: 5%;
  color: white;
  width: 90%;
  z-index: 1;
}

/* Content Titles */
.offers-left .content h2, .offers-right .content h2 {
  font-size: 1.4rem;
  margin-bottom: 5px;
  text-transform: uppercase;
}

/* Content Descriptions */
.offers-left .content p, .offers-right .content p {
  font-size: 0.9rem;
  margin-top: 10px;
  color: #ddd;
}

.offers-left:hover, .offers-right:hover {
  transform: scale(1.05); 
}

/* Media Query for Small Screens (Mobile) */
@media (max-width: 768px) {
  .offers-section {
    flex-direction: column; 
    align-items: center; 
  }

  .offers-left, .offers-right {
    width: 90%; 
    height: 200px; 
    margin-bottom: 15px; 
  }

  .offers-left .content h2, .offers-right .content h2 {
    font-size: 1.2rem;
  }

  .offers-left .content p, .offers-right .content p {
    font-size: 0.85rem; 
  }
}


      /* Footer */
      footer {
        background-color: #000; 
        color: #fff;
        padding: 20px;
        font-size: 0.9rem; 
        text-align: center;
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
      }
    </style>
  </head>
  <body>
    <!-- User Welcome Message -->
    <div class="welcome-message">
      Welcome, <?php echo htmlspecialchars($_SESSION['first_name']); ?>! 
      <a href="logout.php">Logout</a>
    </div>

    <!-- Navigation Bar -->
    <nav>
      <div class="logo">
          <img src="images/kai5.png" alt="Logo">
          <h1>Scentrix Parfum</h1>
      </div>
    
      <div class="hamburger" onclick="toggleMenu()">
          <div></div>
          <div></div>
          <div></div>
      </div>
    
      <ul id="navLinks">
          <li><a href="homepage.php">Home</a></li>
          <li><a href="collections.php">Collections</a></li>
          <li><a href="contact.php">Contact</a></li>
          <!-- About Link -->
          <li><a href="about.php" class="about-link">About</a></li>
          <!-- Profile Link for logged-in users -->
          <li><a href="myprofile.php" class="icon-link"><img src="images/profile.png" alt="Profile Icon"> <?php echo htmlspecialchars($_SESSION['first_name']); ?></a></li>
          <!-- Cart Link -->
          <li><a href="cart.php" class="icon-link"><img src="images/cart.png" alt="Cart Icon"> Cart</a></li>
      </ul>
    </nav>


   <!-- Hero Section -->
<div class="hero-section">
  <div class="hero-content">
    <h2>CHOOSE YOUR FAVORITE PERFUME</h2>
    <p>Discover our exclusive collection of fragrances, crafted for elegance and luxury. Each scent is designed to evoke sophistication and timeless beauty, making every moment unforgettable.</p>
    <a href="collections.php" class="shop-now-btn">Shop Now</a>
  </div>
</div>


    <!-- Features Section -->
    <div class="features-container">
      <div class="feature">
        <div class="feature-icon">
          <img src="images/shipped.png" alt="Free Shipping" />
        </div>
        <div class="feature-title">Free Shipping</div>
        <div class="feature-description">We offer free worldwide shipping on all orders.</div>
      </div>
      <div class="feature">
        <div class="feature-icon">
          <img src="images/medal (1).png" alt="100% Authentic" />
        </div>
        <div class="feature-title">100% Authentic</div>
        <div class="feature-description">Our perfumes are 100% authentic and sourced directly from the manufacturers.</div>
      </div>
      <div class="feature">
        <div class="feature-icon">
          <img src="images/lock-lines.png" alt="Secure Payment" />
        </div>
        <div class="feature-title">Secure Payment</div>
        <div class="feature-description">We ensure secure payment methods for a hassle-free experience.</div>
      </div>
    </div>


     <!-- Newcomers Section -->
  <div class="newcomers-offers-container">
    <!-- Newcomers Title -->
    <div class="new-offers-title">
      Special Offers for Newcomers
    </div>

    <!-- Newcomers Section -->
    <div class="offers-section">
      <!-- Left Column -->
      <a href="collections.php" class="offers-left">
        <div class="content">
          <h2>Exclusive Welcome Discount</h2>
          <p>Get 20% off your first purchase. Use code: WELCOME20 at checkout.</p>
        </div>
      </a>

      
      <!-- Right Column -->
      <a href="collections.php" class="offers-right">
        <div class="content">
          <h2>Free Shipping on First Order</h2>
          <p>Enjoy free shipping with no minimum purchase required.</p>
        </div>
      </a>
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
        const navLinks = document.getElementById("navLinks");
        navLinks.classList.toggle("active");
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