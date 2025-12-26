<?php
session_start();

// Check if user is logged in
if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    header("Location: index.php");
    exit();
}

// Database connection (adjust credentials as needed)
$host = 'localhost';
$username = 'root';
$password = '';
$database = 'perfume';

$conn = mysqli_connect($host, $username, $password, $database);

if (!$conn) {
    die("Connection failed: " . mysqli_connect_error());
}

// Get user information
$user_id = $_SESSION['user_id'] ?? null;
$user_data = [];
$purchases = [];
$notifications = [];

if ($user_id) {
    // Get user data - CHANGED 'id' to 'user_id'
    $user_sql = "SELECT * FROM users WHERE user_id = ?";
    $user_stmt = mysqli_prepare($conn, $user_sql);
    if ($user_stmt === false) {
        die("Error preparing user query: " . mysqli_error($conn));
    }
    mysqli_stmt_bind_param($user_stmt, "i", $user_id);
    mysqli_stmt_execute($user_stmt);
    $user_result = mysqli_stmt_get_result($user_stmt);
    $user_data = mysqli_fetch_assoc($user_result);
    mysqli_stmt_close($user_stmt);
    
    // Get user purchases - FIXED: Changed 'image_path' to 'image'
    $purchase_sql = "SELECT p.*, pr.product_name, pr.price, pr.image 
                     FROM purchases p 
                     JOIN products pr ON p.product_id = pr.product_id 
                     WHERE p.user_id = ? 
                     ORDER BY p.purchase_date DESC";
    $purchase_stmt = mysqli_prepare($conn, $purchase_sql);
    if ($purchase_stmt === false) {
        die("Error preparing purchase query: " . mysqli_error($conn));
    }
    mysqli_stmt_bind_param($purchase_stmt, "i", $user_id);
    mysqli_stmt_execute($purchase_stmt);
    $purchase_result = mysqli_stmt_get_result($purchase_stmt);
    
    while ($row = mysqli_fetch_assoc($purchase_result)) {
        $purchases[] = $row;
    }
    mysqli_stmt_close($purchase_stmt);
    
    // Get notifications
    $notification_sql = "SELECT * FROM notifications 
                         WHERE user_id = ? OR user_id IS NULL 
                         ORDER BY created_at DESC LIMIT 10";
    $notification_stmt = mysqli_prepare($conn, $notification_sql);
    if ($notification_stmt === false) {
        die("Error preparing notification query: " . mysqli_error($conn));
    }
    mysqli_stmt_bind_param($notification_stmt, "i", $user_id);
    mysqli_stmt_execute($notification_stmt);
    $notification_result = mysqli_stmt_get_result($notification_stmt);
    
    while ($row = mysqli_fetch_assoc($notification_result)) {
        $notifications[] = $row;
    }
    mysqli_stmt_close($notification_stmt);
}

mysqli_close($conn);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Profile - RRJJ Scents</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Roboto', sans-serif;
            color: #333;
            display: flex;
            flex-direction: column;
            min-height: 100vh;
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

        /* Active state for current page */
        nav ul li a.active {
            background-color: #991304;
            color: white;
        }

        /* Profile link in nav */
        .profile-nav-link {
            display: inline-flex;
            align-items: center;
            font-size: 0.8rem; 
            padding: 8px 15px; 
        }  

        .profile-nav-link img {
            width: 20px; 
            height: 20px; 
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
            
            .profile-nav-link {
                justify-content: center;
            }
        }

        /* Welcome Message */
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

        .profile-container {
            background-color: white;
            display: flex;
            flex-wrap: wrap;
            width: 80%;
            max-width: 1200px;
            border-radius: 8px;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
            margin: 0 auto;
            margin-top: 30px;
            margin-bottom: 50px;
            flex: 1;
        }

        .profile-left {
            width: 300px;
            padding: 30px;
            text-align: left;
            border-right: 2px solid #ccc;
            align-items: center;
            box-sizing: border-box;
        }

        .profile-icon {
            width: 100px;
            height: 100px;
            background-color: #ddd;
            border-radius: 50%;
            margin: 0 auto 20px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 40px;
            color: #666;
            background: linear-gradient(135deg, #f5f5f5, #e0e0e0);
            overflow: hidden;
        }

        .profile-icon img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }

        .profile-info {
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            text-align: center;
        }

        .profile-name {
            font-size: 26px;
            font-weight: bold;
            margin-bottom: 10px;
            color: #333;
        }

        .email-item {
            font-size: 18px;
            color: #555;
            margin-bottom: 15px;
        }

        .member-since {
            font-size: 14px;
            color: #777;
            margin-bottom: 20px;
        }

        .logout-link {
            font-size: 16px;
            color: #d43535;
            cursor: pointer;
            display: flex;
            align-items: center;
            transition: color 0.3s;
            margin-top: 10px;
        }

        .logout-link a {
            text-decoration: none; 
            color: #d43535; 
        }

        .logout-link i {
            margin-right: 10px;
            font-size: 20px;
        }

        .logout-link:hover {
            color: #991304;
        }

        .section-container {
            display: flex;
            flex-direction: column;
            margin-top: 30px;
        }

        .section-item {
            background-color: #f0f0f0;
            padding: 15px;
            border-radius: 8px;
            text-align: left;
            cursor: pointer;
            transition: background-color 0.3s;
            font-size: 16px;
            margin-bottom: 10px;
            display: flex;
            align-items: center;
            width: auto;
            max-width: 260px;
        }

        .section-item:hover {
            background-color: #ddd;
        }

        .section-item.active {
            background-color: #991304;
            color: white;
        }

        .section-item i {
            font-size: 24px;
            margin-right: 10px;
        }

        .section-item span {
            background-color: transparent;
            color: inherit;
        }

        .profile-right {
            flex: 1;
            padding: 30px;
            display: flex;
            flex-direction: column;
            transition: all 0.3s ease-in-out;
            box-sizing: border-box;
        }

        .details-container {
            display: flex;
            flex-direction: column;
            width: 100%;
        }

        .details {
            width: 100%;
            padding: 15px;
            border-radius: 8px;
            text-align: left;
            display: none;
            box-sizing: border-box;
        }

        .details.active {
            display: block;
        }

        .details h3 {
            margin-bottom: 20px;
            text-align: left;
            color: #333;
            border-bottom: 2px solid #991304;
            padding-bottom: 10px;
        }

        .details h4 {
            color: #333;
            margin-bottom: 10px;
        }

        .purchase-wrapper {
            max-height: 500px;
            overflow-y: auto;
        }

        .no-purchases {
            text-align: center;
            padding: 40px;
            color: #777;
            font-size: 18px;
        }

        .no-purchases i {
            font-size: 50px;
            margin-bottom: 20px;
            color: #ddd;
        }

        .purchase-item {
            display: flex;
            margin-bottom: 20px;
            align-items: flex-start;
            justify-content: flex-start;
            flex-direction: row; 
            text-align: left; 
            width: 100%;
            padding: 20px;
            background-color: #f9f9f9;
            border-radius: 8px;
            border: 1px solid #eee;
            transition: transform 0.3s, box-shadow 0.3s;
        }

        .purchase-item:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
        }

        .purchase-item img {
            width: 120px; 
            height: 120px; 
            margin-right: 20px; 
            object-fit: cover;
            border-radius: 8px;
        }

        .purchase-details {
            flex-grow: 1; 
            font-size: 14px;
            color: #333;
        }

        .purchase-details .product-name {
            font-size: 18px;
            font-weight: bold;
            margin-bottom: 8px;
            color: #222;
        }

        .purchase-details .product-description {
            color: #666;
            margin-bottom: 10px;
            line-height: 1.5;
        }

        .purchase-meta {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-top: 15px;
            padding-top: 15px;
            border-top: 1px solid #eee;
        }

        .price {
            color: #991304;
            font-size: 18px;
            font-weight: bold;
        }

        .purchase-date {
            color: #777;
            font-size: 14px;
        }

        .order-status {
            display: inline-block;
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: bold;
            margin-right: 10px;
        }

        .status-delivered {
            background-color: #d4edda;
            color: #155724;
        }

        .status-pending {
            background-color: #fff3cd;
            color: #856404;
        }

        .status-cancelled {
            background-color: #f8d7da;
            color: #721c24;
        }

        .status-shipped {
            background-color: #cce5ff;
            color: #004085;
        }

        .cancel-order {
            background-color: #e63946;
            color: white;
            font-size: 14px;
            padding: 8px 12px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            transition: background-color 0.3s ease;
        }

        .cancel-order:hover {
            background-color: #d43535;
        }

        .cancel-order:disabled {
            background-color: #ccc;
            cursor: not-allowed;
        }

        .notification-item {
            margin-bottom: 15px;
            padding: 15px;
            background-color: #f0f0f0;
            border-radius: 8px;
            border-left: 4px solid #991304;
            transition: transform 0.3s;
        }

        .notification-item:hover {
            transform: translateX(5px);
        }

        .notification-item strong {
            color: #333;
            font-size: 16px;
            display: block;
            margin-bottom: 5px;
        }

        .notification-item p {
            color: #555;
            font-size: 14px;
            line-height: 1.5;
        }

        .notification-date {
            color: #777;
            font-size: 12px;
            margin-top: 8px;
            text-align: right;
        }

        .popup-overlay {
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background-color: rgba(0, 0, 0, 0.5);
            display: none;
            justify-content: center;
            align-items: center;
            z-index: 2000;
        }

        .popup {
            background-color: white;
            padding: 30px;
            border-radius: 8px;
            text-align: center;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
            max-width: 400px;
            font-family: 'Arial', sans-serif;
            animation: popupIn 0.3s ease;
        }

        @keyframes popupIn {
            from { transform: scale(0.8); opacity: 0; }
            to { transform: scale(1); opacity: 1; }
        }

        .popup h2 {
            font-size: 20px;
            font-weight: bold;
            color: #333;
            margin-bottom: 20px;
        }

        .popup-buttons {
            display: flex;
            justify-content: center;
            gap: 15px;
            margin-top: 20px;
        }

        .popup-btn {
            padding: 10px 25px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-weight: bold;
            transition: background-color 0.3s;
        }

        .popup-btn.confirm {
            background-color: #e63946;
            color: white;
        }

        .popup-btn.confirm:hover {
            background-color: #d43535;
        }

        .popup-btn.cancel {
            background-color: #6c757d;
            color: white;
        }

        .popup-btn.cancel:hover {
            background-color: #5a6268;
        }

        @media screen and (max-width: 1024px) {
            .profile-container {
                flex-direction: column;
                align-items: center;
                justify-content: center;
            }

            .profile-left {
                width: 100%;
                border-right: none;
                padding: 20px;
                box-sizing: border-box;
                border-bottom: 2px solid #ccc;
            }

            .profile-right {
                width: 100%;
                padding: 30px;
                box-sizing: border-box;
            }
            
            .section-container {
                display: flex;
                flex-direction: row;
                justify-content: center;
                flex-wrap: wrap;
                gap: 10px;
                margin-top: 20px;
            }
            
            .section-item {
                width: calc(50% - 10px);
                text-align: center;
                justify-content: center;
                align-items: center;
                max-width: none;
            }

            .purchase-item {
                flex-direction: column;
                text-align: center;
            }

            .purchase-item img {
                margin-bottom: 15px;
                margin-right: 0;
                width: 150px;
                height: 150px;
            }

            .purchase-meta {
                flex-direction: column;
                gap: 10px;
                align-items: flex-start;
            }

            .profile-name {
                font-size: 22px;
            }

            .email-item {
                font-size: 16px;
            }

            .logout-link {
                font-size: 14px;
            }
        }

        @media screen and (max-width: 768px) {
            .profile-container {
                flex-direction: column;
                align-items: center;
                width: 95%;
                margin-top: 20px;
            }

            .profile-left,
            .profile-right {
                width: 100%;
                padding: 15px;
            }

            .section-container {
                flex-direction: column;
            }

            .section-item {
                width: 100%;
                padding: 12px;
                text-align: center;
                justify-content: center;
                max-width: none;
            }

            .purchase-item {
                flex-direction: column;
                text-align: center;
                padding: 15px;
            }

            .purchase-item img {
                margin-bottom: 10px;
                margin-right: 0;
                width: 100%;
                max-width: 200px;
                height: auto;
                aspect-ratio: 1/1;
            }

            .purchase-details {
                text-align: center;
            }

            .purchase-meta {
                align-items: center;
                text-align: center;
            }

            .profile-name {
                font-size: 20px;
            }

            .email-item {
                font-size: 14px;
            }

            .logout-link {
                font-size: 12px;
            }
        }

        /* Footer */
        footer {
            background-color: #000; 
            color: #fff;
            padding: 20px;
            font-size: 0.9rem; 
            text-align: center;
            margin-top: auto;
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
    <!-- Welcome Message -->
    <div class="welcome-message">
        Welcome, <?php echo htmlspecialchars($_SESSION['first_name'] ?? 'User'); ?>! 
        <a href="logout.php">Logout</a>
    </div>

    <!-- Navigation Bar -->
    <nav>
        <div class="logo">
            <img src="images/kai5.png" alt="RRJJ Scents Logo">
            <h1>RRJJ Scents</h1>
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
            <li><a href="about.php">About</a></li>
            <li><a href="myprofile.php" class="profile-nav-link active">
                <img src="images/profile.png" alt="Profile Icon">
                <?php echo htmlspecialchars($_SESSION['first_name'] ?? 'Profile'); ?>
            </a></li>
            <li><a href="cart.php" class="profile-nav-link">
                <img src="images/cart.png" alt="Cart Icon">
                Cart
            </a></li>
        </ul>
    </nav>

    <div class="profile-container">
        <!-- Left Side Profile Section -->
        <div class="profile-left">
            <div class="profile-icon">
                <?php if (!empty($user_data['profile_picture'])): ?>
                    <img src="<?php echo htmlspecialchars($user_data['profile_picture']); ?>" alt="Profile Picture">
                <?php else: ?>
                    <i class="fas fa-user"></i>
                <?php endif; ?>
            </div>
            <div class="profile-info">
                <div class="profile-name">
                    <?php echo htmlspecialchars($user_data['first_name'] ?? 'User') . ' ' . htmlspecialchars($user_data['last_name'] ?? ''); ?>
                </div>
                <div class="email-item">
                    <?php echo htmlspecialchars($user_data['email'] ?? 'No email set'); ?>
                </div>
                <?php if (!empty($user_data['created_at'])): ?>
                    <div class="member-since">
                        Member since: <?php echo date('F Y', strtotime($user_data['created_at'])); ?>
                    </div>
                <?php endif; ?>
                <div class="logout-link">
                    <i class="fas fa-sign-out-alt"></i>
                    <a href="logout.php">Log Out</a>
                </div>
            </div>
            <div class="section-container">
                <div class="section-item active" onclick="showSection('purchases')" id="purchases-btn">
                    <i class="fas fa-shopping-cart"></i>
                    <span>My Purchases</span>
                </div>
                <div class="section-item" onclick="showSection('notifications')" id="notifications-btn">
                    <i class="fas fa-bell"></i>
                    <span>Notifications</span>
                </div>
                <div class="section-item" onclick="showSection('account')" id="account-btn">
                    <i class="fas fa-user-cog"></i>
                    <span>Account Settings</span>
                </div>
            </div>
        </div>
    
        <!-- Right Side Content Section -->
        <div class="profile-right">
            <div class="details-container">
                <!-- Purchases Section -->
                <div class="details active" id="purchases-details">
                    <h3>My Purchases</h3>
                    <div class="purchase-wrapper">
                        <?php if (empty($purchases)): ?>
                            <div class="no-purchases">
                                <i class="fas fa-shopping-cart"></i>
                                <h4>No purchases yet</h4>
                                <p>Start shopping to see your purchases here!</p>
                                <a href="collections.php" style="display: inline-block; margin-top: 15px; padding: 10px 20px; background-color: #991304; color: white; text-decoration: none; border-radius: 5px;">Browse Collections</a>
                            </div>
                        <?php else: ?>
                            <?php foreach ($purchases as $purchase): ?>
                                <div class="purchase-item">
                                    <?php 
                                    // Use the correct image field from products table
                                    $product_image = !empty($purchase['image']) ? $purchase['image'] : 'images/perfume-sample.jpg';
                                    ?>
                                    <img src="<?php echo htmlspecialchars($product_image); ?>" alt="<?php echo htmlspecialchars($purchase['product_name']); ?>">
                                    <div class="purchase-details">
                                        <div class="product-name"><?php echo htmlspecialchars($purchase['product_name']); ?></div>
                                        <div class="order-status <?php echo 'status-' . htmlspecialchars($purchase['status'] ?? 'pending'); ?>">
                                            <?php echo ucfirst(htmlspecialchars($purchase['status'] ?? 'pending')); ?>
                                        </div>
                                        <div class="product-description">
                                            Purchase Date: <?php echo date('F j, Y', strtotime($purchase['purchase_date'])); ?><br>
                                            Quantity: <?php echo htmlspecialchars($purchase['quantity'] ?? 1); ?><br>
                                            Order #<?php echo htmlspecialchars($purchase['id'] ?? $purchase['purchase_id']); ?>
                                        </div>
                                        <div class="purchase-meta">
                                            <div class="price">₱<?php echo number_format($purchase['price'], 2); ?></div>
                                            <div class="purchase-date">
                                                <?php echo date('M d, Y', strtotime($purchase['purchase_date'])); ?>
                                            </div>
                                            <?php if (($purchase['status'] ?? 'pending') == 'pending'): ?>
                                                <button class="cancel-order" onclick="confirmCancel(<?php echo $purchase['id'] ?? $purchase['purchase_id']; ?>, '<?php echo htmlspecialchars($purchase['product_name']); ?>')">
                                                    Cancel Order
                                                </button>
                                            <?php else: ?>
                                                <button class="cancel-order" disabled>
                                                    Cannot Cancel
                                                </button>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                </div>
    
                <!-- Notifications Section -->
                <div class="details" id="notifications-details">
                    <h3>Notifications</h3>
                    <?php if (empty($notifications)): ?>
                        <div class="notification-item">
                            <strong>No notifications</strong>
                            <p>You're all caught up! Check back later for updates.</p>
                        </div>
                    <?php else: ?>
                        <?php foreach ($notifications as $notification): ?>
                            <div class="notification-item">
                                <strong><?php echo htmlspecialchars($notification['title']); ?></strong>
                                <p><?php echo htmlspecialchars($notification['message']); ?></p>
                                <div class="notification-date">
                                    <?php echo date('M d, Y - g:i A', strtotime($notification['created_at'])); ?>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
                
                <!-- Account Settings Section -->
                <div class="details" id="account-details">
                    <h3>Account Settings</h3>
                    <div style="background-color: #f0f0f0; padding: 20px; border-radius: 8px; margin-bottom: 20px;">
                        <h4>Personal Information</h4>
                        <p><strong>Name:</strong> <?php echo htmlspecialchars($user_data['first_name'] ?? '') . ' ' . htmlspecialchars($user_data['last_name'] ?? ''); ?></p>
                        <p><strong>Email:</strong> <?php echo htmlspecialchars($user_data['email'] ?? ''); ?></p>
                        <p><strong>Phone:</strong> <?php echo htmlspecialchars($user_data['phone'] ?? 'Not set'); ?></p>
                        <p><strong>Address:</strong> <?php echo htmlspecialchars($user_data['address'] ?? 'Not set'); ?></p>
                        <a href="edit_profile.php" style="display: inline-block; margin-top: 15px; padding: 10px 20px; background-color: #991304; color: white; text-decoration: none; border-radius: 5px;">Edit Profile</a>
                    </div>
                    
                    <div style="background-color: #f0f0f0; padding: 20px; border-radius: 8px;">
                        <h4>Security</h4>
                        <p><strong>Account Created:</strong> <?php echo date('F j, Y', strtotime($user_data['created_at'] ?? 'now')); ?></p>
                        <p><strong>Last Login:</strong> <?php echo date('F j, Y - g:i A', strtotime($_SESSION['last_login'] ?? 'now')); ?></p>
                        <a href="change_password.php" style="display: inline-block; margin-top: 15px; padding: 10px 20px; background-color: #333; color: white; text-decoration: none; border-radius: 5px;">Change Password</a>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Popup Overlay for Cancellation -->
    <div class="popup-overlay" id="popup-overlay">
        <div class="popup">
            <h2 id="popup-title">Cancel Order</h2>
            <p id="popup-message">Are you sure you want to cancel this order?</p>
            <div class="popup-buttons">
                <button class="popup-btn confirm" onclick="cancelOrder()">Yes, Cancel</button>
                <button class="popup-btn cancel" onclick="closePopup()">No, Keep It</button>
            </div>
        </div>
    </div>

    <!-- Success Popup -->
    <div class="popup-overlay" id="success-popup">
        <div class="popup">
            <h2>Success!</h2>
            <p id="success-message">Your order has been cancelled.</p>
            <div class="popup-buttons">
                <button class="popup-btn cancel" onclick="closeSuccessPopup()">OK</button>
            </div>
        </div>
    </div>

    <!-- Footer -->
    <footer>
        <p>&copy; <?php echo date('Y'); ?> RRJJ Scents. All rights reserved.</p>
        <ul class="footer-links">
            <li><a href="privacy-policy.php">Privacy Policy</a></li>
            <li><a href="terms.php">Terms of Service</a></li>
            <li><a href="contact.php">Contact</a></li>
        </ul>
    </footer>
      
    <script>
        let currentOrderId = null;
        
        // Function to toggle menu
        function toggleMenu() {
            const navLinks = document.getElementById("navLinks");
            navLinks.classList.toggle("active");
        }

        // Function to show selected section
        function showSection(section) {
            // Hide all details sections
            document.getElementById("purchases-details").classList.remove('active');
            document.getElementById("notifications-details").classList.remove('active');
            document.getElementById("account-details").classList.remove('active');
            
            // Remove active class from all buttons
            document.getElementById("purchases-btn").classList.remove('active');
            document.getElementById("notifications-btn").classList.remove('active');
            document.getElementById("account-btn").classList.remove('active');
            
            // Show selected section and activate its button
            if (section === 'purchases') {
                document.getElementById("purchases-details").classList.add('active');
                document.getElementById("purchases-btn").classList.add('active');
            } else if (section === 'notifications') {
                document.getElementById("notifications-details").classList.add('active');
                document.getElementById("notifications-btn").classList.add('active');
            } else if (section === 'account') {
                document.getElementById("account-details").classList.add('active');
                document.getElementById("account-btn").classList.add('active');
            }
        }

        // Function to confirm cancellation
        function confirmCancel(orderId, productName) {
            currentOrderId = orderId;
            document.getElementById('popup-title').textContent = 'Cancel Order';
            document.getElementById('popup-message').textContent = `Are you sure you want to cancel your order for "${productName}"?`;
            document.getElementById('popup-overlay').style.display = 'flex';
        }

        // Function to close popup
        function closePopup() {
            document.getElementById('popup-overlay').style.display = 'none';
            currentOrderId = null;
        }

        // Function to cancel order (AJAX call)
        function cancelOrder() {
            if (!currentOrderId) return;
            
            // Send AJAX request to cancel order
            fetch('cancel_order.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({ order_id: currentOrderId })
            })
            .then(response => response.json())
            .then(data => {
                closePopup();
                if (data.success) {
                    document.getElementById('success-message').textContent = data.message;
                    document.getElementById('success-popup').style.display = 'flex';
                    
                    // Refresh the page after 2 seconds to show updated status
                    setTimeout(() => {
                        location.reload();
                    }, 2000);
                } else {
                    alert('Error: ' + data.message);
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('An error occurred. Please try again.');
            });
        }

        // Function to close success popup
        function closeSuccessPopup() {
            document.getElementById('success-popup').style.display = 'none';
        }

        // Close menu when clicking outside on mobile
        document.addEventListener('click', function(event) {
            const navLinks = document.getElementById("navLinks");
            const hamburger = document.querySelector('.hamburger');
            
            if (window.innerWidth <= 768) {
                if (!hamburger.contains(event.target) && !navLinks.contains(event.target)) {
                    navLinks.classList.remove('active');
                }
            }
        });
        
        // Close menu when window is resized above mobile breakpoint
        window.addEventListener('resize', function() {
            const navLinks = document.getElementById("navLinks");
            if (window.innerWidth > 768) {
                navLinks.classList.remove('active');
            }
        });
    </script>
</body>
</html>