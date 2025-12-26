<?php
session_start();

// Check if user is logged in
if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    header("Location: index.php");
    exit();
}

// Initialize session variables if they don't exist
if (!isset($_SESSION['last_name'])) {
    $_SESSION['last_name'] = '';
}
if (!isset($_SESSION['first_name'])) {
    $_SESSION['first_name'] = '';
}
if (!isset($_SESSION['user_id'])) {
    $_SESSION['user_id'] = '';
}
if (!isset($_SESSION['cart'])) {
    $_SESSION['cart'] = [];
}

// Database connection
$host = "localhost";
$username = "root";
$password = "";
$database = "perfume";

$conn = new mysqli($host, $username, $password, $database);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Handle Add to Cart
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['add_to_cart'])) {
    $product_id = isset($_POST['product_id']) ? intval($_POST['product_id']) : 0;
    $product_name = isset($_POST['product_name']) ? trim($conn->real_escape_string($_POST['product_name'])) : '';
    $product_price = isset($_POST['product_price']) ? floatval($_POST['product_price']) : 0;
    $quantity = isset($_POST['quantity']) ? intval($_POST['quantity']) : 1;
    
    if ($product_id > 0 && !empty($product_name) && $product_price > 0) {
        $cart_item = [
            'product_id' => $product_id,
            'product_name' => $product_name,
            'product_price' => $product_price,
            'quantity' => $quantity
        ];
        
        // Check if product already exists in cart
        $found = false;
        foreach ($_SESSION['cart'] as &$item) {
            if ($item['product_id'] == $product_id) {
                $item['quantity'] += $quantity;
                $found = true;
                break;
            }
        }
        
        if (!$found) {
            $_SESSION['cart'][] = $cart_item;
        }
        
        $_SESSION['cart_message'] = "{$product_name} added to cart!";
        $_SESSION['message_type'] = "success";
    }
    header("Location: collections.php");
    exit();
}

// Handle Update Cart Quantity
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['update_cart'])) {
    $product_id = isset($_POST['product_id']) ? intval($_POST['product_id']) : 0;
    $quantity = isset($_POST['quantity']) ? intval($_POST['quantity']) : 1;
    
    if ($product_id > 0 && $quantity >= 0) {
        foreach ($_SESSION['cart'] as $key => &$item) {
            if ($item['product_id'] == $product_id) {
                if ($quantity == 0) {
                    unset($_SESSION['cart'][$key]);
                } else {
                    $item['quantity'] = $quantity;
                }
                break;
            }
        }
        $_SESSION['cart'] = array_values($_SESSION['cart']); // Reindex array
    }
    header("Location: collections.php");
    exit();
}

// Handle Remove from Cart
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['remove_from_cart'])) {
    $product_id = isset($_POST['product_id']) ? intval($_POST['product_id']) : 0;
    
    if ($product_id > 0) {
        foreach ($_SESSION['cart'] as $key => $item) {
            if ($item['product_id'] == $product_id) {
                unset($_SESSION['cart'][$key]);
                break;
            }
        }
        $_SESSION['cart'] = array_values($_SESSION['cart']); // Reindex array
    }
    header("Location: collections.php");
    exit();
}

// Handle Checkout
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['checkout'])) {
    $user_id = isset($_SESSION['user_id']) ? intval($_SESSION['user_id']) : 0;
    $customer_name = isset($_POST['customer_name']) ? trim($conn->real_escape_string($_POST['customer_name'])) : '';
    $customer_address = isset($_POST['customer_address']) ? trim($conn->real_escape_string($_POST['customer_address'])) : '';
    $customer_phone = isset($_POST['customer_phone']) ? trim($conn->real_escape_string($_POST['customer_phone'])) : '';
    
    // Validate input
    if (empty($customer_name) || empty($customer_address) || empty($customer_phone)) {
        $_SESSION['order_error'] = "Please fill in all required fields.";
        header("Location: collections.php");
        exit();
    }
    
    if ($user_id <= 0) {
        $_SESSION['order_error'] = "Invalid user session. Please login again.";
        header("Location: collections.php");
        exit();
    }
    
    if (empty($_SESSION['cart'])) {
        $_SESSION['order_error'] = "Your cart is empty.";
        header("Location: collections.php");
        exit();
    }
    
    // Calculate total
    $total_amount = 0;
    foreach ($_SESSION['cart'] as $item) {
        $total_amount += $item['product_price'] * $item['quantity'];
    }
    
    // Insert order into database with transaction
    $conn->begin_transaction();
    
    try {
        // Insert into orders table
        $stmt = $conn->prepare("INSERT INTO orders (user_id, total_amount, shipping_address, customer_name, customer_phone, order_date, status) VALUES (?, ?, ?, ?, ?, NOW(), 'pending')");
        if (!$stmt) {
            throw new Exception("Prepare failed: " . $conn->error);
        }
        $stmt->bind_param("idsss", $user_id, $total_amount, $customer_address, $customer_name, $customer_phone);
        
        if (!$stmt->execute()) {
            throw new Exception("Execute failed: " . $stmt->error);
        }
        
        $order_id = $stmt->insert_id;
        $stmt->close();
        
        // Insert order items
        foreach ($_SESSION['cart'] as $item) {
            $item_stmt = $conn->prepare("INSERT INTO order_items (order_id, product_id, quantity, price) VALUES (?, ?, ?, ?)");
            if (!$item_stmt) {
                throw new Exception("Prepare failed: " . $conn->error);
            }
            $item_stmt->bind_param("iiid", $order_id, $item['product_id'], $item['quantity'], $item['product_price']);
            if (!$item_stmt->execute()) {
                throw new Exception("Execute failed: " . $item_stmt->error);
            }
            $item_stmt->close();
        }
        
        $conn->commit();
        
        // Clear cart after successful order
        $_SESSION['cart'] = [];
        $_SESSION['order_success'] = true;
        $_SESSION['order_message'] = "Your order has been placed successfully! Order ID: #{$order_id}";
        unset($_SESSION['order_error']);
        
    } catch (Exception $e) {
        $conn->rollback();
        $_SESSION['order_error'] = "There was an error placing your order. Please try again.";
        error_log("Order Error: " . $e->getMessage());
    }
    
    header("Location: collections.php");
    exit();
}

// Fetch products from database
$products = [];
$sql = "SELECT * FROM products WHERE status = 'active' ORDER BY category, product_name";
$result = $conn->query($sql);

if ($result && $result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $products[] = $row;
    }
}
?>
<!doctype html>
<html lang="en">
  <head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Collections - Perfume Store</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root {
            --primary-color: #991304;
            --primary-dark: #7a0f03;
            --secondary-color: #000;
            --text-color: #333;
            --light-gray: #f9f9f9;
            --white: #fff;
            --border-radius: 8px;
            --transition: all 0.3s ease;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Roboto', sans-serif;
            color: var(--text-color);
            background-color: var(--light-gray);
            line-height: 1.6;
        }

        /* Navigation Bar */
        nav {
            background-color: var(--secondary-color);
            padding: 0.5rem 1rem;
            display: flex;
            justify-content: space-between;
            align-items: center;
            position: sticky;
            top: 0;
            z-index: 1000;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
        }

        .logo {
            display: flex;
            align-items: center;
            text-decoration: none;
        }

        .logo img {
            height: 30px;
            width: auto;
            margin-right: 10px;
        }

        .logo h1 {
            font-size: 1.2rem;
            margin: 0;
            font-weight: 600;
            color: var(--white);
        }

        nav ul {
            list-style-type: none;
            display: flex;
            align-items: center;
            margin: 0;
            padding: 0;
        }

        nav ul li {
            margin-left: 12px;
            position: relative;
        }

        nav ul li a {
            text-decoration: none;
            color: #f0f0f0;
            padding: 8px 20px;
            font-size: 0.8rem;
            font-weight: 600;
            text-transform: uppercase;
            border-radius: var(--border-radius);
            transition: var(--transition);
            letter-spacing: 1px;
            display: block;
        }

        nav ul li a:hover,
        nav ul li a:focus {
            background-color: #444;
            color: var(--white);
            outline: none;
        }

        .cart-count {
            position: absolute;
            top: -8px;
            right: 2px;
            background-color: var(--primary-color);
            color: white;
            border-radius: 50%;
            width: 20px;
            height: 20px;
            font-size: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: bold;
        }

        /* User Welcome Message */
        .welcome-message {
            background-color: #f0f0f0;
            padding: 10px 20px;
            text-align: center;
            font-size: 14px;
            color: var(--text-color);
            border-bottom: 1px solid #ddd;
        }

        .welcome-message a {
            color: var(--primary-color);
            text-decoration: none;
            font-weight: 600;
            margin-left: 10px;
            transition: var(--transition);
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
            z-index: 1001;
            background: transparent;
            border: none;
            padding: 0;
        }

        .hamburger div {
            height: 3px;
            background-color: var(--white);
            transition: var(--transition);
            border-radius: 2px;
        }

        .hamburger.active div:nth-child(1) {
            transform: translateY(8.5px) rotate(45deg);
        }

        .hamburger.active div:nth-child(2) {
            opacity: 0;
        }

        .hamburger.active div:nth-child(3) {
            transform: translateY(-8.5px) rotate(-45deg);
        }

        #navLinks {
            display: flex;
            flex-direction: row;
        }

        @media screen and (max-width: 768px) {
            .hamburger {
                display: flex;
            }

            #navLinks {
                display: none;
                position: fixed;
                top: 60px;
                left: 0;
                right: 0;
                background-color: var(--secondary-color);
                padding: 1rem;
                width: 100%;
                text-align: center;
                flex-direction: column;
                box-shadow: 0 5px 10px rgba(0, 0, 0, 0.1);
            }

            #navLinks.active {
                display: flex;
            }

            #navLinks li {
                margin: 12px 0;
                width: 100%;
            }

            #navLinks li a {
                padding: 12px;
                width: 100%;
                border-radius: 5px;
            }
        }

        /* Collections Container */
        .main-container {
            display: flex;
            max-width: 1200px;
            margin: 20px auto;
            gap: 30px;
            padding: 0 20px;
        }

        .collections-container {
            flex: 1;
            padding: 30px;
            background-color: var(--white);
            border-radius: var(--border-radius);
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05);
        }

        .cart-container {
            width: 350px;
            padding: 30px;
            background-color: var(--white);
            border-radius: var(--border-radius);
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05);
            height: fit-content;
            position: sticky;
            top: 100px;
        }

        @media (max-width: 1024px) {
            .main-container {
                flex-direction: column;
            }
            
            .cart-container {
                width: 100%;
                position: static;
                order: -1;
            }
        }

        .new-offers-title {
            text-align: center;
            font-size: 1.8rem;
            font-weight: 700;
            text-transform: uppercase;
            margin-bottom: 40px;
            position: relative;
            color: var(--secondary-color);
        }

        .new-offers-title::after {
            content: "";
            display: block;
            width: 60px;
            height: 3px;
            background-color: var(--primary-color);
            margin: 15px auto 0;
        }

        .product-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(220px, 1fr));
            gap: 25px;
            margin-top: 20px;
        }

        @media (max-width: 768px) {
            .product-grid {
                grid-template-columns: repeat(auto-fill, minmax(160px, 1fr));
                gap: 15px;
            }
        }

        .product-card {
            background-color: var(--white);
            border-radius: var(--border-radius);
            overflow: hidden;
            box-shadow: 0 3px 10px rgba(0, 0, 0, 0.08);
            text-align: center;
            padding: 20px 15px;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
            transition: var(--transition);
            height: 100%;
            border: 1px solid #eee;
        }

        .product-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 5px 20px rgba(0, 0, 0, 0.15);
        }

        .product-card img {
            width: 100%;
            height: 180px;
            object-fit: contain;
            margin-bottom: 15px;
            border-radius: 5px;
            background-color: #f8f8f8;
            padding: 10px;
        }

        .product-title {
            font-size: 0.95rem;
            font-weight: 600;
            color: var(--text-color);
            margin-bottom: 10px;
            line-height: 1.4;
            height: 40px;
            overflow: hidden;
            display: -webkit-box;
            -webkit-line-clamp: 2;
            -webkit-box-orient: vertical;
        }

        .product-price {
            font-size: 1.1rem;
            color: var(--primary-color);
            font-weight: 700;
            margin-bottom: 20px;
        }

        .btn-group {
            display: flex;
            gap: 10px;
        }

        .buy-now-btn, .add-to-cart-btn {
            padding: 12px 20px;
            font-size: 0.95rem;
            border: none;
            border-radius: var(--border-radius);
            cursor: pointer;
            transition: var(--transition);
            width: 100%;
            font-weight: 600;
            letter-spacing: 0.5px;
            text-transform: uppercase;
        }

        .buy-now-btn {
            background-color: var(--primary-color);
            color: var(--white);
        }

        .add-to-cart-btn {
            background-color: #28a745;
            color: var(--white);
        }

        .buy-now-btn:hover,
        .buy-now-btn:focus {
            background-color: var(--primary-dark);
            outline: none;
        }

        .add-to-cart-btn:hover {
            background-color: #218838;
        }

        /* Cart Styles */
        .cart-title {
            font-size: 1.5rem;
            font-weight: 700;
            color: var(--secondary-color);
            margin-bottom: 25px;
            padding-bottom: 15px;
            border-bottom: 2px solid #eee;
        }

        .cart-items {
            max-height: 400px;
            overflow-y: auto;
            margin-bottom: 25px;
        }

        .cart-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 15px 0;
            border-bottom: 1px solid #eee;
        }

        .cart-item:last-child {
            border-bottom: none;
        }

        .cart-item-info h4 {
            font-size: 0.95rem;
            margin-bottom: 5px;
            color: var(--text-color);
        }

        .cart-item-price {
            color: var(--primary-color);
            font-weight: 600;
        }

        .cart-item-quantity {
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .quantity-btn {
            width: 30px;
            height: 30px;
            border: 1px solid #ddd;
            background: #f8f9fa;
            border-radius: 4px;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: bold;
        }

        .quantity-input {
            width: 50px;
            text-align: center;
            border: 1px solid #ddd;
            border-radius: 4px;
            padding: 5px;
        }

        .remove-item {
            background: none;
            border: none;
            color: #dc3545;
            cursor: pointer;
            font-size: 1.2rem;
            margin-left: 10px;
        }

        .cart-total {
            margin-top: 25px;
            padding-top: 20px;
            border-top: 2px solid #eee;
        }

        .total-row {
            display: flex;
            justify-content: space-between;
            margin-bottom: 15px;
            font-size: 1.1rem;
            font-weight: 600;
        }

        .total-amount {
            color: var(--primary-color);
            font-size: 1.3rem;
        }

        .checkout-btn {
            width: 100%;
            padding: 15px;
            background-color: var(--primary-color);
            color: white;
            border: none;
            border-radius: var(--border-radius);
            font-size: 1rem;
            font-weight: 600;
            cursor: pointer;
            transition: var(--transition);
            text-transform: uppercase;
            letter-spacing: 1px;
        }

        .checkout-btn:hover {
            background-color: var(--primary-dark);
        }

        .checkout-btn:disabled {
            background-color: #cccccc;
            cursor: not-allowed;
        }

        .empty-cart {
            text-align: center;
            color: #666;
            padding: 40px 0;
        }

        .empty-cart i {
            font-size: 3rem;
            color: #ddd;
            margin-bottom: 15px;
        }

        /* Footer */
        footer {
            background-color: var(--secondary-color);
            color: var(--white);
            padding: 30px 20px;
            font-size: 0.9rem;
            text-align: center;
            margin-top: 60px;
        }

        footer p {
            margin: 10px 0;
            color: #ccc;
        }

        .footer-links {
            list-style: none;
            padding: 0;
            margin: 20px 0;
        }

        .footer-links li {
            display: inline-block;
            margin: 0 15px;
        }

        .footer-links li a {
            color: var(--white);
            text-decoration: none;
            text-transform: uppercase;
            font-weight: 600;
            transition: var(--transition);
            font-size: 0.85rem;
        }

        .footer-links li a:hover {
            color: var(--primary-color);
        }

        @media (max-width: 768px) {
            .footer-links li {
                display: block;
                margin: 10px 0;
            }
        }

        /* Modal Styles */
        .modal {
            display: none;
            position: fixed;
            z-index: 2000;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0, 0, 0, 0.7);
            padding-top: 5%;
            overflow: auto;
            opacity: 0;
            transition: opacity 0.3s ease;
        }

        .modal.show {
            display: block;
            opacity: 1;
        }

        .modal-content {
            background-color: var(--white);
            margin: 5% auto;
            padding: 30px;
            width: 90%;
            max-width: 500px;
            border-radius: var(--border-radius);
            box-shadow: 0 5px 30px rgba(0, 0, 0, 0.3);
            text-align: center;
            position: relative;
            transform: translateY(-20px);
            transition: transform 0.3s ease;
        }

        .modal.show .modal-content {
            transform: translateY(0);
        }

        .modal-content h2 {
            font-size: 1.4rem;
            font-weight: 700;
            color: var(--text-color);
            margin-bottom: 15px;
            line-height: 1.4;
        }

        .modal-content img {
            width: 100%;
            max-height: 180px;
            object-fit: contain;
            margin: 15px 0;
            border-radius: var(--border-radius);
            background-color: #f8f8f8;
            padding: 15px;
        }

        .product-description {
            font-size: 0.95rem;
            color: #666;
            margin-bottom: 20px;
            line-height: 1.6;
        }

        .modal-content form {
            margin-top: 25px;
        }

        .form-group {
            margin-bottom: 20px;
            text-align: left;
        }

        .form-group label {
            display: block;
            margin-bottom: 8px;
            font-weight: 600;
            color: var(--text-color);
            font-size: 0.9rem;
        }

        .modal-content input[type="text"],
        .modal-content input[type="tel"] {
            width: 100%;
            padding: 14px;
            border: 1px solid #ddd;
            border-radius: var(--border-radius);
            background-color: #f7f7f7;
            color: var(--text-color);
            font-size: 15px;
            transition: var(--transition);
        }

        .modal-content input[type="text"]:focus,
        .modal-content input[type="tel"]:focus {
            outline: none;
            border-color: var(--primary-color);
            background-color: var(--white);
            box-shadow: 0 0 0 2px rgba(153, 19, 4, 0.1);
        }

        .modal-content input[type="hidden"] {
            display: none;
        }

        .order-btn {
            padding: 15px 30px;
            background-color: var(--primary-color);
            color: var(--white);
            border: none;
            border-radius: var(--border-radius);
            font-size: 1rem;
            font-weight: 600;
            width: 100%;
            cursor: pointer;
            transition: var(--transition);
            margin-top: 10px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .order-btn:hover,
        .order-btn:focus {
            background-color: var(--primary-dark);
            outline: none;
        }

        .close {
            color: #aaa;
            font-size: 28px;
            font-weight: bold;
            cursor: pointer;
            position: absolute;
            top: 15px;
            right: 20px;
            transition: var(--transition);
            line-height: 1;
            width: 30px;
            height: 30px;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 50%;
        }

        .close:hover,
        .close:focus {
            color: #333;
            background-color: #f0f0f0;
            text-decoration: none;
        }

        /* Success/Error Messages */
        .message-container {
            max-width: 1200px;
            margin: 20px auto;
            padding: 0 20px;
        }

        .success-message, .error-message, .info-message {
            padding: 15px 20px;
            border-radius: var(--border-radius);
            margin-bottom: 20px;
            text-align: center;
            font-size: 14px;
            font-weight: 500;
            animation: slideDown 0.3s ease;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
        }

        @keyframes slideDown {
            from {
                transform: translateY(-20px);
                opacity: 0;
            }
            to {
                transform: translateY(0);
                opacity: 1;
            }
        }

        .success-message {
            background-color: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }

        .error-message {
            background-color: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }

        .info-message {
            background-color: #d1ecf1;
            color: #0c5460;
            border: 1px solid #bee5eb;
        }

        /* Toast Notification */
        .toast {
            position: fixed;
            bottom: 20px;
            right: 20px;
            background-color: #28a745;
            color: white;
            padding: 15px 25px;
            border-radius: var(--border-radius);
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.2);
            z-index: 3000;
            display: flex;
            align-items: center;
            gap: 10px;
            transform: translateY(100px);
            opacity: 0;
            transition: all 0.3s ease;
        }

        .toast.show {
            transform: translateY(0);
            opacity: 1;
        }

        .toast.error {
            background-color: #dc3545;
        }

        /* Loading Spinner */
        .loading {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.5);
            z-index: 3000;
            justify-content: center;
            align-items: center;
        }

        .loading.show {
            display: flex;
        }

        .spinner {
            border: 5px solid #f3f3f3;
            border-top: 5px solid var(--primary-color);
            border-radius: 50%;
            width: 50px;
            height: 50px;
            animation: spin 1s linear infinite;
        }

        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }

        /* Fix for body scroll when modal is open */
        body.modal-open {
            overflow: hidden;
            height: 100%;
        }

        @media screen and (max-width: 768px) {
            .modal-content {
                width: 95%;
                margin-top: 10%;
                padding: 25px 20px;
            }
            
            .collections-container {
                padding: 30px 15px;
            }
            
            .cart-container {
                padding: 20px 15px;
            }
            
            .new-offers-title {
                font-size: 1.5rem;
                margin-bottom: 30px;
            }
            
            .main-container {
                padding: 0 15px;
            }
        }

        @media screen and (max-width: 480px) {
            .modal-content {
                padding: 20px 15px;
            }
            
            .product-card {
                padding: 15px 10px;
            }
            
            .product-title {
                font-size: 0.9rem;
            }
            
            .product-price {
                font-size: 1rem;
            }
            
            .btn-group {
                flex-direction: column;
            }
        }
    </style>
  </head>
  <body>
    <!-- User Welcome Message -->
    <div class="welcome-message">
      Welcome, <?php echo isset($_SESSION['first_name']) ? htmlspecialchars($_SESSION['first_name'], ENT_QUOTES, 'UTF-8') : 'User'; ?>! 
      <a href="logout.php">Logout</a>
    </div>

    <!-- Navigation Bar -->
    <nav>
      <a href="homepage.php" class="logo">
        <img src="images/kai5.png" alt="Scentrix Parfum Logo">
        <h1>Scentrix Parfum</h1>
      </a>

      <button class="hamburger" onclick="toggleMenu()" aria-label="Toggle navigation menu">
        <div></div>
        <div></div>
        <div></div>
      </button>

      <ul id="navLinks">
        <li><a href="homepage.php">Home</a></li>
        <li><a href="collections.php" aria-current="page">Collections</a></li>
        <li><a href="contact.php">Contact</a></li>
        <li><a href="about.php">About</a></li>
        <li><a href="myprofile.php"><?php echo isset($_SESSION['first_name']) ? htmlspecialchars($_SESSION['first_name'], ENT_QUOTES, 'UTF-8') : 'Profile'; ?></a></li>
        <li>
          <a href="#cart-section">
            <i class="fas fa-shopping-cart"></i> Cart
            <?php if (!empty($_SESSION['cart'])): ?>
              <span class="cart-count"><?php echo count($_SESSION['cart']); ?></span>
            <?php endif; ?>
          </a>
        </li>
      </ul>
    </nav>

    <!-- Success/Error Messages -->
    <div class="message-container">
        <?php if (isset($_SESSION['order_success']) && $_SESSION['order_success'] === true): ?>
            <div class="success-message">
                <i class="fas fa-check-circle"></i>
                <?php echo isset($_SESSION['order_message']) ? htmlspecialchars($_SESSION['order_message'], ENT_QUOTES, 'UTF-8') : 'Thank you for your order! Your order has been placed successfully.'; ?>
            </div>
            <?php unset($_SESSION['order_success'], $_SESSION['order_message']); ?>
        <?php endif; ?>
        
        <?php if (isset($_SESSION['order_error'])): ?>
            <div class="error-message">
                <i class="fas fa-exclamation-circle"></i>
                <?php echo htmlspecialchars($_SESSION['order_error'], ENT_QUOTES, 'UTF-8'); ?>
            </div>
            <?php unset($_SESSION['order_error']); ?>
        <?php endif; ?>
        
        <?php if (isset($_SESSION['cart_message'])): ?>
            <div class="info-message">
                <i class="fas fa-shopping-cart"></i>
                <?php echo htmlspecialchars($_SESSION['cart_message'], ENT_QUOTES, 'UTF-8'); ?>
            </div>
            <?php unset($_SESSION['cart_message'], $_SESSION['message_type']); ?>
        <?php endif; ?>
    </div>

    <!-- Main Container -->
    <div class="main-container">
        <!-- Cart Section -->
        <div class="cart-container" id="cart-section">
            <h2 class="cart-title">Your Shopping Cart</h2>
            
            <div class="cart-items" id="cartItems">
                <?php if (empty($_SESSION['cart'])): ?>
                    <div class="empty-cart">
                        <i class="fas fa-shopping-cart"></i>
                        <p>Your cart is empty</p>
                        <p>Add some products to get started!</p>
                    </div>
                <?php else: ?>
                    <?php 
                    $total_amount = 0;
                    foreach ($_SESSION['cart'] as $item): 
                        $item_total = $item['product_price'] * $item['quantity'];
                        $total_amount += $item_total;
                    ?>
                        <div class="cart-item" data-product-id="<?php echo $item['product_id']; ?>">
                            <div class="cart-item-info">
                                <h4><?php echo htmlspecialchars($item['product_name']); ?></h4>
                                <div class="cart-item-price">₱<?php echo number_format($item['product_price'], 2); ?></div>
                            </div>
                            <div class="cart-item-actions">
                                <form method="POST" action="" class="cart-quantity-form" style="display: inline;">
                                    <input type="hidden" name="product_id" value="<?php echo $item['product_id']; ?>">
                                    <div class="cart-item-quantity">
                                        <button type="button" class="quantity-btn minus" onclick="updateQuantity(<?php echo $item['product_id']; ?>, -1)">-</button>
                                        <input type="number" name="quantity" value="<?php echo $item['quantity']; ?>" 
                                               min="1" max="10" class="quantity-input" 
                                               onchange="updateQuantityInput(<?php echo $item['product_id']; ?>, this.value)">
                                        <button type="button" class="quantity-btn plus" onclick="updateQuantity(<?php echo $item['product_id']; ?>, 1)">+</button>
                                    </div>
                                </form>
                                <form method="POST" action="" style="display: inline;">
                                    <input type="hidden" name="product_id" value="<?php echo $item['product_id']; ?>">
                                    <button type="submit" name="remove_from_cart" class="remove-item" title="Remove item">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                </form>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
            
            <?php if (!empty($_SESSION['cart'])): ?>
                <div class="cart-total">
                    <div class="total-row">
                        <span>Total:</span>
                        <span class="total-amount">₱<?php echo number_format($total_amount, 2); ?></span>
                    </div>
                    <button type="button" class="checkout-btn" onclick="openCheckoutModal()">
                        <i class="fas fa-shopping-bag"></i> Proceed to Checkout
                    </button>
                </div>
            <?php endif; ?>
        </div>

        <!-- Products Section -->
        <div class="collections-container">
            <!-- Women's Collection -->
            <h2 class="new-offers-title">Women's Collection</h2>
            <div class="product-grid">
                <?php
                $women_products = array_filter($products, function($product) {
                    return strtolower($product['category']) === 'women' || strpos(strtolower($product['category']), 'women') !== false;
                });
                
                if (empty($women_products)): ?>
                    <p>No women's products available at the moment.</p>
                <?php else: ?>
                    <?php foreach ($women_products as $product): ?>
                        <div class="product-card">
                            <img src="<?php echo htmlspecialchars($product['image'] ?? 'images/placeholder.jpg'); ?>" 
                                 alt="<?php echo htmlspecialchars($product['product_name']); ?>" 
                                 loading="lazy" 
                                 onerror="this.src='images/placeholder.jpg'">
                            <div class="product-title"><?php echo htmlspecialchars($product['product_name']); ?></div>
                            <div class="product-price">₱<?php echo number_format($product['price'], 2); ?></div>
                            <p class="product-description"><?php echo htmlspecialchars(substr($product['description'] ?? '', 0, 100)); ?>...</p>
                            <div class="btn-group">
                                <form method="POST" action="" style="width: 100%;">
                                    <input type="hidden" name="product_id" value="<?php echo $product['product_id']; ?>">
                                    <input type="hidden" name="product_name" value="<?php echo htmlspecialchars($product['product_name']); ?>">
                                    <input type="hidden" name="product_price" value="<?php echo $product['price']; ?>">
                                    <input type="hidden" name="quantity" value="1">
                                    <button type="submit" name="add_to_cart" class="add-to-cart-btn">
                                        <i class="fas fa-cart-plus"></i> Add to Cart
                                    </button>
                                </form>
                                <button class="buy-now-btn" onclick="openModal(<?php echo htmlspecialchars(json_encode($product)); ?>)">
                                    <i class="fas fa-bolt"></i> Buy Now
                                </button>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>

            <!-- Men's Collection -->
            <h2 class="new-offers-title" style="margin-top: 60px;">Men's Collection</h2>
            <div class="product-grid">
                <?php
                $men_products = array_filter($products, function($product) {
                    return strtolower($product['category']) === 'men' || strpos(strtolower($product['category']), 'men') !== false;
                });
                
                if (empty($men_products)): ?>
                    <p>No men's products available at the moment.</p>
                <?php else: ?>
                    <?php foreach ($men_products as $product): ?>
                        <div class="product-card">
                            <img src="<?php echo htmlspecialchars($product['image'] ?? 'images/placeholder.jpg'); ?>" 
                                 alt="<?php echo htmlspecialchars($product['product_name']); ?>" 
                                 loading="lazy" 
                                 onerror="this.src='images/placeholder.jpg'">
                            <div class="product-title"><?php echo htmlspecialchars($product['product_name']); ?></div>
                            <div class="product-price">₱<?php echo number_format($product['price'], 2); ?></div>
                            <p class="product-description"><?php echo htmlspecialchars(substr($product['description'] ?? '', 0, 100)); ?>...</p>
                            <div class="btn-group">
                                <form method="POST" action="" style="width: 100%;">
                                    <input type="hidden" name="product_id" value="<?php echo $product['product_id']; ?>">
                                    <input type="hidden" name="product_name" value="<?php echo htmlspecialchars($product['product_name']); ?>">
                                    <input type="hidden" name="product_price" value="<?php echo $product['price']; ?>">
                                    <input type="hidden" name="quantity" value="1">
                                    <button type="submit" name="add_to_cart" class="add-to-cart-btn">
                                        <i class="fas fa-cart-plus"></i> Add to Cart
                                    </button>
                                </form>
                                <button class="buy-now-btn" onclick="openModal(<?php echo htmlspecialchars(json_encode($product)); ?>)">
                                    <i class="fas fa-bolt"></i> Buy Now
                                </button>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Loading Spinner -->
    <div class="loading" id="loadingSpinner">
        <div class="spinner"></div>
    </div>

    <!-- Toast Notification -->
    <div id="toast" class="toast">
        <i class="fas fa-check-circle"></i>
        <span id="toastMessage"></span>
    </div>

    <!-- Buy Now Modal -->
    <div id="orderModal" class="modal">
        <div class="modal-content">
            <span class="close" onclick="closeModal()" aria-label="Close modal">&times;</span>
            <h2 id="modalProductName"></h2>
            <p class="product-description" id="modalProductDescription"></p>
            <p class="product-price" id="modalProductPrice"></p>
            <img id="modalProductImage" src="" alt="Product Image" loading="lazy" onerror="this.src='images/placeholder.jpg'">
            <form id="orderForm" method="POST" action="">
                <input type="hidden" name="product_name" id="formProductName">
                <input type="hidden" name="product_price" id="formProductPrice">
                <input type="hidden" name="product_id" id="formProductId">
                
                <div class="form-group">
                    <label for="customer_name">Your Name</label>
                    <input type="text" name="customer_name" id="customer_name" placeholder="Enter your full name" 
                           value="<?php 
                               $full_name = '';
                               if (isset($_SESSION['first_name']) && !empty($_SESSION['first_name'])) {
                                   $full_name = htmlspecialchars($_SESSION['first_name'], ENT_QUOTES, 'UTF-8');
                               }
                               if (isset($_SESSION['last_name']) && !empty($_SESSION['last_name'])) {
                                   $full_name .= ' ' . htmlspecialchars($_SESSION['last_name'], ENT_QUOTES, 'UTF-8');
                               }
                               echo trim($full_name);
                           ?>" required>
                </div>
                
                <div class="form-group">
                    <label for="customer_phone">Phone Number</label>
                    <input type="tel" name="customer_phone" id="customer_phone" placeholder="Enter your phone number" required>
                </div>
                
                <div class="form-group">
                    <label for="customer_address">Shipping Address</label>
                    <input type="text" name="customer_address" id="customer_address" placeholder="Enter your complete shipping address" required>
                </div>
                
                <button type="submit" name="place_order" class="order-btn">Place Order</button>
            </form>
        </div>
    </div>

    <!-- Checkout Modal -->
    <div id="checkoutModal" class="modal">
        <div class="modal-content">
            <span class="close" onclick="closeCheckoutModal()" aria-label="Close modal">&times;</span>
            <h2>Checkout</h2>
            <form id="checkoutForm" method="POST" action="">
                <div class="form-group">
                    <label for="checkout_name">Your Name</label>
                    <input type="text" name="customer_name" id="checkout_name" placeholder="Enter your full name" 
                           value="<?php 
                               $full_name = '';
                               if (isset($_SESSION['first_name']) && !empty($_SESSION['first_name'])) {
                                   $full_name = htmlspecialchars($_SESSION['first_name'], ENT_QUOTES, 'UTF-8');
                               }
                               if (isset($_SESSION['last_name']) && !empty($_SESSION['last_name'])) {
                                   $full_name .= ' ' . htmlspecialchars($_SESSION['last_name'], ENT_QUOTES, 'UTF-8');
                               }
                               echo trim($full_name);
                           ?>" required>
                </div>
                
                <div class="form-group">
                    <label for="checkout_phone">Phone Number</label>
                    <input type="tel" name="customer_phone" id="checkout_phone" placeholder="Enter your phone number" required>
                </div>
                
                <div class="form-group">
                    <label for="checkout_address">Shipping Address</label>
                    <input type="text" name="customer_address" id="checkout_address" placeholder="Enter your complete shipping address" required>
                </div>
                
                <div class="order-summary">
                    <h3>Order Summary</h3>
                    <div id="checkoutItems">
                        <!-- Cart items will be populated here -->
                    </div>
                    <div class="total-row" style="margin-top: 20px;">
                        <span>Total:</span>
                        <span id="checkoutTotal">₱0.00</span>
                    </div>
                </div>
                
                <button type="submit" name="checkout" class="order-btn">
                    <i class="fas fa-shopping-bag"></i> Place Order
                </button>
            </form>
        </div>
    </div>

    <!-- Footer -->
    <footer>
      <p>&copy; <?php echo date('Y'); ?> Scentrix Parfum. All rights reserved.</p>
      <ul class="footer-links">
        <li><a href="privacy.php">Privacy Policy</a></li>
        <li><a href="terms.php">Terms of Service</a></li>
        <li><a href="contact.php">Contact</a></li>
      </ul>
    </footer>

    <script>
      // Function to toggle mobile menu
      function toggleMenu() {
        const navLinks = document.getElementById("navLinks");
        const hamburger = document.querySelector('.hamburger');
        navLinks.classList.toggle("active");
        hamburger.classList.toggle("active");
      }

      // Close menu when clicking outside on mobile
      document.addEventListener('click', function(event) {
        const navLinks = document.getElementById("navLinks");
        const hamburger = document.querySelector('.hamburger');
        
        if (window.innerWidth <= 768) {
          if (!navLinks.contains(event.target) && !hamburger.contains(event.target)) {
            navLinks.classList.remove('active');
            hamburger.classList.remove('active');
          }
        }
      });

      // Close menu when window is resized
      window.addEventListener('resize', function() {
        if (window.innerWidth > 768) {
          const navLinks = document.getElementById("navLinks");
          const hamburger = document.querySelector('.hamburger');
          navLinks.classList.remove('active');
          hamburger.classList.remove('active');
        }
      });

      // Toast notification function
      function showToast(message, type = 'success') {
        const toast = document.getElementById('toast');
        const toastMessage = document.getElementById('toastMessage');
        
        toastMessage.textContent = message;
        toast.className = 'toast ' + type;
        toast.classList.add('show');
        
        setTimeout(() => {
          toast.classList.remove('show');
        }, 3000);
      }

      // Function to open the Buy Now modal
      function openModal(product) {
        document.getElementById("modalProductName").textContent = product.product_name;
        document.getElementById("modalProductDescription").textContent = product.description || '';
        document.getElementById("modalProductPrice").textContent = '₱' + parseFloat(product.price).toLocaleString('en-US', {minimumFractionDigits: 2});
        document.getElementById("modalProductImage").src = product.image || 'images/placeholder.jpg';
        document.getElementById("modalProductImage").alt = product.product_name;
        
        // Set form values
        document.getElementById("formProductName").value = product.product_name;
        document.getElementById("formProductPrice").value = product.price;
        document.getElementById("formProductId").value = product.product_id;
        
        // Show modal with animation
        const modal = document.getElementById("orderModal");
        document.body.classList.add('modal-open');
        modal.classList.add('show');
        
        // Focus on first input field
        setTimeout(() => {
          document.getElementById('customer_name').focus();
        }, 300);
      }

      // Function to close the Buy Now modal
      function closeModal() {
        const modal = document.getElementById("orderModal");
        modal.classList.remove('show');
        document.body.classList.remove('modal-open');
        document.getElementById("orderForm").reset();
      }

      // Function to open checkout modal
      function openCheckoutModal() {
        const cart = <?php echo json_encode($_SESSION['cart']); ?>;
        
        if (cart.length === 0) {
          showToast('Your cart is empty!', 'error');
          return;
        }
        
        // Populate checkout items
        const checkoutItems = document.getElementById('checkoutItems');
        let html = '';
        let total = 0;
        
        cart.forEach(item => {
          const itemTotal = item.product_price * item.quantity;
          total += itemTotal;
          html += `
            <div class="cart-item">
              <div class="cart-item-info">
                <h4>${item.product_name} x${item.quantity}</h4>
                <div class="cart-item-price">₱${parseFloat(item.product_price).toLocaleString('en-US', {minimumFractionDigits: 2})}</div>
              </div>
              <div class="cart-item-price">₱${parseFloat(itemTotal).toLocaleString('en-US', {minimumFractionDigits: 2})}</div>
            </div>
          `;
        });
        
        checkoutItems.innerHTML = html;
        document.getElementById('checkoutTotal').textContent = '₱' + parseFloat(total).toLocaleString('en-US', {minimumFractionDigits: 2});
        
        // Show modal
        const modal = document.getElementById("checkoutModal");
        document.body.classList.add('modal-open');
        modal.classList.add('show');
        
        // Focus on first input field
        setTimeout(() => {
          document.getElementById('checkout_name').focus();
        }, 300);
      }

      // Function to close checkout modal
      function closeCheckoutModal() {
        const modal = document.getElementById("checkoutModal");
        modal.classList.remove('show');
        document.body.classList.remove('modal-open');
        document.getElementById("checkoutForm").reset();
      }

      // Update quantity functions
      function updateQuantity(productId, change) {
        const form = document.createElement('form');
        form.method = 'POST';
        form.action = '';
        
        const productIdInput = document.createElement('input');
        productIdInput.type = 'hidden';
        productIdInput.name = 'product_id';
        productIdInput.value = productId;
        
        // Find current quantity
        let currentQuantity = 1;
        const quantityInput = document.querySelector(`[data-product-id="${productId}"] .quantity-input`);
        if (quantityInput) {
          currentQuantity = parseInt(quantityInput.value);
        }
        
        const newQuantity = Math.max(1, currentQuantity + change);
        
        const quantityInputField = document.createElement('input');
        quantityInputField.type = 'hidden';
        quantityInputField.name = 'quantity';
        quantityInputField.value = newQuantity;
        
        const updateCartInput = document.createElement('input');
        updateCartInput.type = 'hidden';
        updateCartInput.name = 'update_cart';
        updateCartInput.value = '1';
        
        form.appendChild(productIdInput);
        form.appendChild(quantityInputField);
        form.appendChild(updateCartInput);
        document.body.appendChild(form);
        form.submit();
      }

      function updateQuantityInput(productId, value) {
        if (value >= 1 && value <= 10) {
          const form = document.createElement('form');
          form.method = 'POST';
          form.action = '';
          
          const productIdInput = document.createElement('input');
          productIdInput.type = 'hidden';
          productIdInput.name = 'product_id';
          productIdInput.value = productId;
          
          const quantityInputField = document.createElement('input');
          quantityInputField.type = 'hidden';
          quantityInputField.name = 'quantity';
          quantityInputField.value = value;
          
          const updateCartInput = document.createElement('input');
          updateCartInput.type = 'hidden';
          updateCartInput.name = 'update_cart';
          updateCartInput.value = '1';
          
          form.appendChild(productIdInput);
          form.appendChild(quantityInputField);
          form.appendChild(updateCartInput);
          document.body.appendChild(form);
          form.submit();
        }
      }

      // Close modal when clicking outside
      window.addEventListener('click', function(event) {
        const orderModal = document.getElementById("orderModal");
        const checkoutModal = document.getElementById("checkoutModal");
        
        if (event.target === orderModal) {
          closeModal();
        }
        if (event.target === checkoutModal) {
          closeCheckoutModal();
        }
      });

      // Close modal with Escape key
      document.addEventListener('keydown', function(event) {
        if (event.key === "Escape") {
          closeModal();
          closeCheckoutModal();
        }
      });

      // Handle form submissions
      document.getElementById("orderForm")?.addEventListener("submit", function(e) {
        const customerName = this.elements['customer_name'].value.trim();
        const customerPhone = this.elements['customer_phone'].value.trim();
        const customerAddress = this.elements['customer_address'].value.trim();
        
        if (!customerName || !customerPhone || !customerAddress) {
          e.preventDefault();
          showToast('Please fill in all required fields.', 'error');
          return false;
        }
        
        // Show loading spinner
        document.getElementById('loadingSpinner').classList.add('show');
        return true;
      });

      document.getElementById("checkoutForm")?.addEventListener("submit", function(e) {
        const customerName = this.elements['customer_name'].value.trim();
        const customerPhone = this.elements['customer_phone'].value.trim();
        const customerAddress = this.elements['customer_address'].value.trim();
        
        if (!customerName || !customerPhone || !customerAddress) {
          e.preventDefault();
          showToast('Please fill in all required fields.', 'error');
          return false;
        }
        
        // Show loading spinner
        document.getElementById('loadingSpinner').classList.add('show');
        return true;
      });

      // Hide loading spinner when page loads completely
      window.addEventListener('load', function() {
        document.getElementById('loadingSpinner').classList.remove('show');
        
        // Show toast if there's a cart message
        <?php if (isset($_SESSION['cart_message']) && isset($_SESSION['message_type'])): ?>
          showToast('<?php echo addslashes($_SESSION['cart_message']); ?>', '<?php echo $_SESSION['message_type']; ?>');
        <?php 
          unset($_SESSION['cart_message'], $_SESSION['message_type']);
        endif; ?>
      });

      // Handle page visibility to show/hide loading spinner
      document.addEventListener('visibilitychange', function() {
        if (document.visibilityState === 'visible') {
          document.getElementById('loadingSpinner').classList.remove('show');
        }
      });

      // Smooth scroll for anchor links
      document.querySelectorAll('a[href^="#"]').forEach(anchor => {
        anchor.addEventListener('click', function(e) {
          const href = this.getAttribute('href');
          if (href !== '#') {
            e.preventDefault();
            const target = document.querySelector(href);
            if (target) {
              target.scrollIntoView({
                behavior: 'smooth',
                block: 'start'
              });
            }
          }
        });
      });

      // Initialize tooltips
      document.querySelectorAll('[title]').forEach(element => {
        element.addEventListener('mouseenter', function() {
          const title = this.getAttribute('title');
          if (title) {
            const tooltip = document.createElement('div');
            tooltip.className = 'tooltip';
            tooltip.textContent = title;
            tooltip.style.position = 'absolute';
            tooltip.style.background = '#333';
            tooltip.style.color = '#fff';
            tooltip.style.padding = '5px 10px';
            tooltip.style.borderRadius = '4px';
            tooltip.style.fontSize = '12px';
            tooltip.style.zIndex = '10000';
            tooltip.style.whiteSpace = 'nowrap';
            
            const rect = this.getBoundingClientRect();
            tooltip.style.top = (rect.top - 30) + 'px';
            tooltip.style.left = (rect.left + rect.width/2) + 'px';
            tooltip.style.transform = 'translateX(-50%)';
            
            document.body.appendChild(tooltip);
            this._tooltip = tooltip;
          }
        });
        
        element.addEventListener('mouseleave', function() {
          if (this._tooltip) {
            this._tooltip.remove();
            delete this._tooltip;
          }
        });
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