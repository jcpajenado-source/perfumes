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

// Handle Add to Cart (in case user adds from cart page)
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
    header("Location: cart.php");
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
    header("Location: cart.php");
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
    header("Location: cart.php");
    exit();
}

// Handle Clear Cart
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['clear_cart'])) {
    $_SESSION['cart'] = [];
    $_SESSION['cart_message'] = "Cart cleared successfully!";
    $_SESSION['message_type'] = "success";
    header("Location: cart.php");
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
        header("Location: cart.php");
        exit();
    }
    
    if ($user_id <= 0) {
        $_SESSION['order_error'] = "Invalid user session. Please login again.";
        header("Location: cart.php");
        exit();
    }
    
    if (empty($_SESSION['cart'])) {
        $_SESSION['order_error'] = "Your cart is empty.";
        header("Location: cart.php");
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
        $_SESSION['order_id'] = $order_id; // Store for notification
        unset($_SESSION['order_error']);
        
    } catch (Exception $e) {
        $conn->rollback();
        $_SESSION['order_error'] = "There was an error placing your order. Please try again.";
        error_log("Order Error: " . $e->getMessage());
    }
    
    header("Location: cart.php");
    exit();
}

// Fetch user's order history
$user_id = isset($_SESSION['user_id']) ? intval($_SESSION['user_id']) : 0;
$orders = [];
$order_notifications = [];

if ($user_id > 0) {
    $sql = "SELECT * FROM orders WHERE user_id = ? ORDER BY order_date DESC";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result && $result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            // Fetch order items for each order
            $order_id = $row['order_id'];
            $items_sql = "SELECT oi.*, p.product_name, p.image 
                         FROM order_items oi 
                         LEFT JOIN products p ON oi.product_id = p.product_id 
                         WHERE oi.order_id = ?";
            $item_stmt = $conn->prepare($items_sql);
            $item_stmt->bind_param("i", $order_id);
            $item_stmt->execute();
            $items_result = $item_stmt->get_result();
            $order_items = [];
            
            while ($item = $items_result->fetch_assoc()) {
                $order_items[] = $item;
            }
            $item_stmt->close();
            
            $row['items'] = $order_items;
            $orders[] = $row;
            
            // Check for notifications (approved orders that haven't been seen)
            if ($row['status'] == 'approved' && !isset($_SESSION['seen_orders'][$order_id])) {
                $order_notifications[] = [
                    'order_id' => $order_id,
                    'message' => "Order #{$order_id} has been approved by admin!"
                ];
            }
            
            // Check for shipped orders
            if ($row['status'] == 'shipped' && !isset($_SESSION['seen_shipped'][$order_id])) {
                $order_notifications[] = [
                    'order_id' => $order_id,
                    'message' => "Order #{$order_id} has been shipped!"
                ];
            }
            
            // Check for delivered orders
            if ($row['status'] == 'delivered' && !isset($_SESSION['seen_delivered'][$order_id])) {
                $order_notifications[] = [
                    'order_id' => $order_id,
                    'message' => "Order #{$order_id} has been delivered!"
                ];
            }
        }
    }
    $stmt->close();
}

// Mark notifications as seen when page loads
if (!empty($order_notifications)) {
    foreach ($order_notifications as $notification) {
        $order_id = $notification['order_id'];
        $order = array_filter($orders, function($o) use ($order_id) {
            return $o['order_id'] == $order_id;
        });
        
        if (!empty($order)) {
            $order = reset($order);
            switch ($order['status']) {
                case 'approved':
                    $_SESSION['seen_orders'][$order_id] = true;
                    break;
                case 'shipped':
                    $_SESSION['seen_shipped'][$order_id] = true;
                    break;
                case 'delivered':
                    $_SESSION['seen_delivered'][$order_id] = true;
                    break;
            }
        }
    }
}

// Fetch some products for "Continue Shopping" section
$products = [];
$sql = "SELECT * FROM products WHERE status = 'active' ORDER BY RAND() LIMIT 4";
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
    <title>Shopping Cart - Scentrix Parfum</title>
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

        /* Main Container */
        .main-container {
            max-width: 1200px;
            margin: 20px auto;
            padding: 0 20px;
        }

        /* Cart Container */
        .cart-container {
            background-color: var(--white);
            border-radius: var(--border-radius);
            padding: 30px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05);
            margin-bottom: 30px;
        }

        .section-title {
            font-size: 1.8rem;
            font-weight: 700;
            color: var(--secondary-color);
            margin-bottom: 25px;
            position: relative;
            padding-bottom: 15px;
        }

        .section-title::after {
            content: "";
            display: block;
            width: 60px;
            height: 3px;
            background-color: var(--primary-color);
            margin-top: 10px;
        }

        /* Cart Items */
        .cart-items {
            margin-bottom: 30px;
        }

        .cart-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 20px 0;
            border-bottom: 1px solid #eee;
        }

        .cart-item:last-child {
            border-bottom: none;
        }

        .cart-item-info {
            display: flex;
            align-items: center;
            gap: 20px;
            flex: 1;
        }

        .cart-item-image {
            width: 100px;
            height: 100px;
            object-fit: contain;
            border-radius: var(--border-radius);
            background-color: #f8f8f8;
            padding: 10px;
        }

        .cart-item-details h4 {
            font-size: 1rem;
            font-weight: 600;
            margin-bottom: 5px;
            color: var(--text-color);
        }

        .cart-item-price {
            color: var(--primary-color);
            font-weight: 700;
            font-size: 1.1rem;
        }

        .cart-item-actions {
            display: flex;
            align-items: center;
            gap: 15px;
        }

        .cart-item-quantity {
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .quantity-btn {
            width: 35px;
            height: 35px;
            border: 1px solid #ddd;
            background: #f8f9fa;
            border-radius: 50%;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: bold;
            font-size: 1.1rem;
            transition: var(--transition);
        }

        .quantity-btn:hover {
            background-color: #e9ecef;
        }

        .quantity-input {
            width: 60px;
            text-align: center;
            border: 1px solid #ddd;
            border-radius: var(--border-radius);
            padding: 8px;
            font-size: 1rem;
            font-weight: 600;
        }

        .remove-item {
            background: none;
            border: none;
            color: #dc3545;
            cursor: pointer;
            font-size: 1.3rem;
            transition: var(--transition);
            padding: 5px;
            border-radius: 50%;
            width: 40px;
            height: 40px;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .remove-item:hover {
            background-color: rgba(220, 53, 69, 0.1);
        }

        /* Cart Summary */
        .cart-summary {
            background-color: #f8f9fa;
            border-radius: var(--border-radius);
            padding: 25px;
            margin-top: 30px;
        }

        .summary-row {
            display: flex;
            justify-content: space-between;
            margin-bottom: 15px;
            font-size: 1rem;
        }

        .summary-row.total {
            font-size: 1.3rem;
            font-weight: 700;
            color: var(--primary-color);
            border-top: 2px solid #ddd;
            padding-top: 15px;
            margin-top: 15px;
        }

        .cart-actions {
            display: flex;
            gap: 15px;
            margin-top: 25px;
        }

        .btn {
            padding: 14px 30px;
            border: none;
            border-radius: var(--border-radius);
            font-size: 1rem;
            font-weight: 600;
            cursor: pointer;
            transition: var(--transition);
            text-transform: uppercase;
            letter-spacing: 1px;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            flex: 1;
        }

        .btn-primary {
            background-color: var(--primary-color);
            color: var(--white);
        }

        .btn-primary:hover {
            background-color: var(--primary-dark);
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(153, 19, 4, 0.3);
        }

        .btn-secondary {
            background-color: #6c757d;
            color: var(--white);
        }

        .btn-secondary:hover {
            background-color: #5a6268;
            transform: translateY(-2px);
        }

        .btn-success {
            background-color: #28a745;
            color: var(--white);
        }

        .btn-success:hover {
            background-color: #218838;
            transform: translateY(-2px);
        }

        /* Empty Cart */
        .empty-cart {
            text-align: center;
            padding: 50px 20px;
        }

        .empty-cart i {
            font-size: 4rem;
            color: #ddd;
            margin-bottom: 20px;
        }

        .empty-cart h3 {
            font-size: 1.5rem;
            margin-bottom: 10px;
            color: var(--text-color);
        }

        .empty-cart p {
            color: #666;
            margin-bottom: 25px;
        }

        /* Order History */
        .orders-container {
            background-color: var(--white);
            border-radius: var(--border-radius);
            padding: 30px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05);
            margin-bottom: 30px;
        }

        .orders-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }

        .orders-table th {
            background-color: #f8f9fa;
            padding: 15px;
            text-align: left;
            font-weight: 600;
            color: var(--secondary-color);
            border-bottom: 2px solid #eee;
        }

        .orders-table td {
            padding: 15px;
            border-bottom: 1px solid #eee;
            vertical-align: top;
        }

        .orders-table tr:hover {
            background-color: #f8f9fa;
        }

        /* Status Badges */
        .status-badge {
            display: inline-block;
            padding: 5px 12px;
            border-radius: 20px;
            font-size: 0.8rem;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .status-pending {
            background-color: #fff3cd;
            color: #856404;
        }

        .status-approved {
            background-color: #d1ecf1;
            color: #0c5460;
        }

        .status-shipped {
            background-color: #d4edda;
            color: #155724;
        }

        .status-delivered {
            background-color: #28a745;
            color: white;
        }

        .status-cancelled {
            background-color: #f8d7da;
            color: #721c24;
        }

        /* Notifications */
        .notifications-container {
            background-color: var(--white);
            border-radius: var(--border-radius);
            padding: 30px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05);
            margin-bottom: 30px;
            border-left: 4px solid var(--primary-color);
        }

        .notification-item {
            display: flex;
            align-items: center;
            padding: 15px;
            background-color: #f0f8ff;
            border-radius: var(--border-radius);
            margin-bottom: 10px;
            border-left: 4px solid #4a90e2;
        }

        .notification-item i {
            font-size: 1.5rem;
            color: #4a90e2;
            margin-right: 15px;
        }

        .notification-item.approved {
            background-color: #d4edda;
            border-left-color: #28a745;
        }

        .notification-item.approved i {
            color: #28a745;
        }

        .notification-item.shipped {
            background-color: #d1ecf1;
            border-left-color: #17a2b8;
        }

        .notification-item.shipped i {
            color: #17a2b8;
        }

        .notification-item.delivered {
            background-color: #d4edda;
            border-left-color: #155724;
        }

        .notification-item.delivered i {
            color: #155724;
        }

        /* Continue Shopping */
        .continue-shopping {
            background-color: var(--white);
            border-radius: var(--border-radius);
            padding: 30px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05);
        }

        .product-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(220px, 1fr));
            gap: 25px;
            margin-top: 20px;
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

        .add-to-cart-btn {
            padding: 12px 20px;
            font-size: 0.95rem;
            background-color: #28a745;
            color: var(--white);
            border: none;
            border-radius: var(--border-radius);
            cursor: pointer;
            transition: var(--transition);
            width: 100%;
            font-weight: 600;
            letter-spacing: 0.5px;
            text-transform: uppercase;
        }

        .add-to-cart-btn:hover {
            background-color: #218838;
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

        .toast.warning {
            background-color: #ffc107;
            color: #212529;
        }

        .toast.info {
            background-color: #17a2b8;
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

        /* Responsive Design */
        @media screen and (max-width: 768px) {
            .main-container {
                padding: 0 15px;
            }
            
            .cart-container,
            .orders-container,
            .notifications-container,
            .continue-shopping {
                padding: 20px 15px;
            }
            
            .section-title {
                font-size: 1.5rem;
            }
            
            .cart-item {
                flex-direction: column;
                align-items: flex-start;
                gap: 15px;
            }
            
            .cart-item-info {
                width: 100%;
            }
            
            .cart-item-actions {
                width: 100%;
                justify-content: space-between;
            }
            
            .cart-actions {
                flex-direction: column;
            }
            
            .orders-table {
                font-size: 0.9rem;
            }
            
            .orders-table td,
            .orders-table th {
                padding: 10px;
            }
            
            .product-grid {
                grid-template-columns: repeat(auto-fill, minmax(160px, 1fr));
                gap: 15px;
            }
            
            .modal-content {
                width: 95%;
                margin-top: 10%;
                padding: 25px 20px;
            }
        }

        @media screen and (max-width: 480px) {
            .cart-item-image {
                width: 80px;
                height: 80px;
            }
            
            .quantity-input {
                width: 50px;
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
        <li><a href="collections.php">Collections</a></li>
        <li><a href="contact.php">Contact</a></li>
        <li><a href="about.php">About</a></li>
        <li><a href="myprofile.php"><?php echo isset($_SESSION['first_name']) ? htmlspecialchars($_SESSION['first_name'], ENT_QUOTES, 'UTF-8') : 'Profile'; ?></a></li>
        <li>
          <a href="cart.php" aria-current="page">
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
            <?php 
            unset($_SESSION['order_success'], $_SESSION['order_message']);
            // Set a notification for the new order
            if (isset($_SESSION['order_id'])) {
                $order_notifications[] = [
                    'order_id' => $_SESSION['order_id'],
                    'message' => "Your order #{$_SESSION['order_id']} has been placed and is pending approval."
                ];
                unset($_SESSION['order_id']);
            }
            ?>
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
        <!-- Notifications Section -->
        <?php if (!empty($order_notifications)): ?>
        <div class="notifications-container">
            <h2 class="section-title">Notifications</h2>
            <?php foreach ($order_notifications as $notification): 
                $type = 'info';
                if (strpos($notification['message'], 'approved') !== false) $type = 'approved';
                if (strpos($notification['message'], 'shipped') !== false) $type = 'shipped';
                if (strpos($notification['message'], 'delivered') !== false) $type = 'delivered';
            ?>
                <div class="notification-item <?php echo $type; ?>">
                    <i class="fas fa-<?php 
                        if ($type == 'approved') echo 'check-circle';
                        elseif ($type == 'shipped') echo 'shipping-fast';
                        elseif ($type == 'delivered') echo 'box-open';
                        else echo 'bell';
                    ?>"></i>
                    <div>
                        <strong><?php echo htmlspecialchars($notification['message'], ENT_QUOTES, 'UTF-8'); ?></strong>
                        <p style="margin-top: 5px; font-size: 0.9rem; color: #666;">
                            <?php echo date('F j, Y \a\t g:i A'); ?>
                        </p>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>

        <!-- Shopping Cart Section -->
        <div class="cart-container">
            <h2 class="section-title">Your Shopping Cart</h2>
            
            <?php if (empty($_SESSION['cart'])): ?>
                <div class="empty-cart">
                    <i class="fas fa-shopping-cart"></i>
                    <h3>Your cart is empty</h3>
                    <p>Looks like you haven't added any items to your cart yet.</p>
                    <a href="collections.php" class="btn btn-primary" style="display: inline-flex; width: auto; padding: 12px 30px;">
                        <i class="fas fa-shopping-bag"></i> Continue Shopping
                    </a>
                </div>
            <?php else: ?>
                <div class="cart-items">
                    <?php 
                    $total_amount = 0;
                    $item_count = 0;
                    
                    foreach ($_SESSION['cart'] as $item): 
                        $item_total = $item['product_price'] * $item['quantity'];
                        $total_amount += $item_total;
                        $item_count += $item['quantity'];
                        
                        // Get product image from database
                        $product_sql = "SELECT image FROM products WHERE product_id = ?";
                        $product_stmt = $conn->prepare($product_sql);
                        $product_stmt->bind_param("i", $item['product_id']);
                        $product_stmt->execute();
                        $product_result = $product_stmt->get_result();
                        $product_image = 'images/placeholder.jpg';
                        if ($product_result->num_rows > 0) {
                            $product_data = $product_result->fetch_assoc();
                            $product_image = $product_data['image'];
                        }
                        $product_stmt->close();
                    ?>
                        <div class="cart-item" data-product-id="<?php echo $item['product_id']; ?>">
                            <div class="cart-item-info">
                                <img src="<?php echo htmlspecialchars($product_image); ?>" 
                                     alt="<?php echo htmlspecialchars($item['product_name']); ?>" 
                                     class="cart-item-image"
                                     onerror="this.src='images/placeholder.jpg'">
                                <div class="cart-item-details">
                                    <h4><?php echo htmlspecialchars($item['product_name']); ?></h4>
                                    <div class="cart-item-price">₱<?php echo number_format($item['product_price'], 2); ?></div>
                                </div>
                            </div>
                            <div class="cart-item-actions">
                                <form method="POST" action="" class="cart-quantity-form" style="display: inline;">
                                    <input type="hidden" name="product_id" value="<?php echo $item['product_id']; ?>">
                                    <div class="cart-item-quantity">
                                        <button type="button" class="quantity-btn minus" onclick="updateQuantity(<?php echo $item['product_id']; ?>, -1)">-</button>
                                        <input type="number" name="quantity" value="<?php echo $item['quantity']; ?>" 
                                               min="1" max="99" class="quantity-input" 
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
                </div>
                
                <div class="cart-summary">
                    <div class="summary-row">
                        <span>Items (<?php echo $item_count; ?>):</span>
                        <span>₱<?php echo number_format($total_amount, 2); ?></span>
                    </div>
                    <div class="summary-row">
                        <span>Shipping:</span>
                        <span>₱<?php echo number_format(($total_amount > 5000) ? 0 : 150, 2); ?></span>
                    </div>
                    <div class="summary-row">
                        <span>Tax:</span>
                        <span>₱<?php echo number_format($total_amount * 0.12, 2); ?></span>
                    </div>
                    <div class="summary-row total">
                        <span>Total:</span>
                        <?php 
                        $shipping = ($total_amount > 5000) ? 0 : 150;
                        $tax = $total_amount * 0.12;
                        $grand_total = $total_amount + $shipping + $tax;
                        ?>
                        <span>₱<?php echo number_format($grand_total, 2); ?></span>
                    </div>
                    
                    <div class="cart-actions">
                        <form method="POST" action="" style="flex: 1;">
                            <input type="hidden" name="clear_cart" value="1">
                            <button type="submit" class="btn btn-secondary" onclick="return confirm('Are you sure you want to clear your cart?')">
                                <i class="fas fa-trash-alt"></i> Clear Cart
                            </button>
                        </form>
                        <a href="collections.php" class="btn btn-success" style="flex: 1;">
                            <i class="fas fa-shopping-bag"></i> Continue Shopping
                        </a>
                        <button type="button" class="btn btn-primary" onclick="openCheckoutModal()" style="flex: 2;">
                            <i class="fas fa-credit-card"></i> Proceed to Checkout
                        </button>
                    </div>
                </div>
            <?php endif; ?>
        </div>

        <!-- Order History Section -->
        <?php if (!empty($orders)): ?>
        <div class="orders-container">
            <h2 class="section-title">Order History</h2>
            <div class="table-container">
                <table class="orders-table">
                    <thead>
                        <tr>
                            <th>Order ID</th>
                            <th>Date</th>
                            <th>Items</th>
                            <th>Total</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($orders as $order): ?>
                            <tr>
                                <td><strong>#<?php echo $order['order_id']; ?></strong></td>
                                <td><?php echo date('M d, Y', strtotime($order['order_date'])); ?></td>
                                <td>
                                    <?php 
                                    $item_count = 0;
                                    foreach ($order['items'] as $item) {
                                        $item_count += $item['quantity'];
                                    }
                                    echo $item_count . ' item' . ($item_count != 1 ? 's' : '');
                                    ?>
                                </td>
                                <td><strong>₱<?php echo number_format($order['total_amount'], 2); ?></strong></td>
                                <td>
                                    <span class="status-badge status-<?php echo $order['status']; ?>">
                                        <?php echo ucfirst($order['status']); ?>
                                    </span>
                                    <?php if (!empty($order['admin_notes'])): ?>
                                        <br><small style="font-size: 0.8rem; color: #666;"><?php echo htmlspecialchars($order['admin_notes']); ?></small>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <button class="btn btn-primary" style="padding: 5px 10px; font-size: 0.8rem;" onclick="viewOrderDetails(<?php echo $order['order_id']; ?>)">
                                        <i class="fas fa-eye"></i> View
                                    </button>
                                </td>
                            </tr>
                            <tr id="order-details-<?php echo $order['order_id']; ?>" style="display: none;">
                                <td colspan="6">
                                    <div class="order-details" style="background: #f8f9fa; padding: 20px; border-radius: var(--border-radius); margin-top: 10px;">
                                        <h4>Order Details - #<?php echo $order['order_id']; ?></h4>
                                        <p><strong>Shipping Address:</strong> <?php echo htmlspecialchars($order['shipping_address']); ?></p>
                                        <p><strong>Contact:</strong> <?php echo htmlspecialchars($order['customer_phone']); ?></p>
                                        
                                        <div style="margin-top: 15px;">
                                            <h5>Order Items:</h5>
                                            <?php foreach ($order['items'] as $item): ?>
                                                <div style="display: flex; align-items: center; padding: 10px 0; border-bottom: 1px solid #eee;">
                                                    <img src="<?php echo htmlspecialchars($item['image'] ?? 'images/placeholder.jpg'); ?>" 
                                                         alt="<?php echo htmlspecialchars($item['product_name']); ?>" 
                                                         style="width: 50px; height: 50px; object-fit: contain; margin-right: 10px; background: #fff; padding: 5px; border-radius: 5px;"
                                                         onerror="this.src='images/placeholder.jpg'">
                                                    <div style="flex: 1;">
                                                        <strong><?php echo htmlspecialchars($item['product_name']); ?></strong>
                                                        <div>Quantity: <?php echo $item['quantity']; ?></div>
                                                    </div>
                                                    <div style="font-weight: bold; color: var(--primary-color);">
                                                        ₱<?php echo number_format($item['price'] * $item['quantity'], 2); ?>
                                                    </div>
                                                </div>
                                            <?php endforeach; ?>
                                        </div>
                                        
                                        <div style="margin-top: 15px; padding-top: 15px; border-top: 2px solid #ddd;">
                                            <div style="display: flex; justify-content: space-between; font-weight: bold;">
                                                <span>Total Amount:</span>
                                                <span>₱<?php echo number_format($order['total_amount'], 2); ?></span>
                                            </div>
                                        </div>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
        <?php endif; ?>

        <!-- Continue Shopping Section -->
        <?php if (!empty($products)): ?>
        <div class="continue-shopping">
            <h2 class="section-title">You Might Also Like</h2>
            <div class="product-grid">
                <?php foreach ($products as $product): ?>
                    <div class="product-card">
                        <img src="<?php echo htmlspecialchars($product['image'] ?? 'images/placeholder.jpg'); ?>" 
                             alt="<?php echo htmlspecialchars($product['product_name']); ?>" 
                             loading="lazy" 
                             onerror="this.src='images/placeholder.jpg'">
                        <div class="product-title"><?php echo htmlspecialchars($product['product_name']); ?></div>
                        <div class="product-price">₱<?php echo number_format($product['price'], 2); ?></div>
                        <form method="POST" action="">
                            <input type="hidden" name="product_id" value="<?php echo $product['product_id']; ?>">
                            <input type="hidden" name="product_name" value="<?php echo htmlspecialchars($product['product_name']); ?>">
                            <input type="hidden" name="product_price" value="<?php echo $product['price']; ?>">
                            <input type="hidden" name="quantity" value="1">
                            <button type="submit" name="add_to_cart" class="add-to-cart-btn">
                                <i class="fas fa-cart-plus"></i> Add to Cart
                            </button>
                        </form>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
        <?php endif; ?>
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

    <!-- Checkout Modal -->
    <div id="checkoutModal" class="modal">
        <div class="modal-content">
            <span class="close" onclick="closeCheckoutModal()" aria-label="Close modal">&times;</span>
            <h2>Checkout</h2>
            <form id="checkoutForm" method="POST" action="">
                <div class="form-group">
                    <label for="checkout_name">Your Name *</label>
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
                    <label for="checkout_phone">Phone Number *</label>
                    <input type="tel" name="customer_phone" id="checkout_phone" placeholder="Enter your phone number" required>
                </div>
                
                <div class="form-group">
                    <label for="checkout_address">Shipping Address *</label>
                    <textarea name="customer_address" id="checkout_address" placeholder="Enter your complete shipping address" rows="3" required></textarea>
                </div>
                
                <div class="order-summary" style="margin-top: 20px; padding: 15px; background: #f8f9fa; border-radius: var(--border-radius);">
                    <h3 style="margin-bottom: 15px;">Order Summary</h3>
                    <div id="checkoutItems">
                        <!-- Cart items will be populated here -->
                    </div>
                    <div style="margin-top: 15px; padding-top: 15px; border-top: 2px solid #ddd;">
                        <div style="display: flex; justify-content: space-between; margin-bottom: 10px;">
                            <span>Subtotal:</span>
                            <span id="checkoutSubtotal">₱0.00</span>
                        </div>
                        <div style="display: flex; justify-content: space-between; margin-bottom: 10px;">
                            <span>Shipping:</span>
                            <span id="checkoutShipping">₱0.00</span>
                        </div>
                        <div style="display: flex; justify-content: space-between; margin-bottom: 10px;">
                            <span>Tax:</span>
                            <span id="checkoutTax">₱0.00</span>
                        </div>
                        <div style="display: flex; justify-content: space-between; font-weight: bold; font-size: 1.1rem;">
                            <span>Total:</span>
                            <span id="checkoutTotal">₱0.00</span>
                        </div>
                    </div>
                </div>
                
                <div style="margin-top: 25px; font-size: 0.9rem; color: #666;">
                    <p><i class="fas fa-info-circle"></i> Your order will be processed after admin approval.</p>
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

      // View order details
      function viewOrderDetails(orderId) {
        const detailsRow = document.getElementById(`order-details-${orderId}`);
        if (detailsRow.style.display === 'none' || detailsRow.style.display === '') {
          detailsRow.style.display = 'table-row';
        } else {
          detailsRow.style.display = 'none';
        }
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
        let subtotal = 0;
        
        cart.forEach(item => {
          const itemTotal = item.product_price * item.quantity;
          subtotal += itemTotal;
          html += `
            <div style="display: flex; justify-content: space-between; margin-bottom: 10px; padding-bottom: 10px; border-bottom: 1px solid #eee;">
              <div>
                <strong>${item.product_name}</strong>
                <div style="font-size: 0.9rem; color: #666;">${item.quantity} x ₱${parseFloat(item.product_price).toLocaleString('en-US', {minimumFractionDigits: 2})}</div>
              </div>
              <div style="font-weight: bold;">
                ₱${parseFloat(itemTotal).toLocaleString('en-US', {minimumFractionDigits: 2})}
              </div>
            </div>
          `;
        });
        
        checkoutItems.innerHTML = html;
        
        // Calculate totals
        const shipping = subtotal > 5000 ? 0 : 150;
        const tax = subtotal * 0.12;
        const total = subtotal + shipping + tax;
        
        document.getElementById('checkoutSubtotal').textContent = '₱' + parseFloat(subtotal).toLocaleString('en-US', {minimumFractionDigits: 2});
        document.getElementById('checkoutShipping').textContent = '₱' + parseFloat(shipping).toLocaleString('en-US', {minimumFractionDigits: 2});
        document.getElementById('checkoutTax').textContent = '₱' + parseFloat(tax).toLocaleString('en-US', {minimumFractionDigits: 2});
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
        if (value >= 1 && value <= 99) {
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
        const checkoutModal = document.getElementById("checkoutModal");
        
        if (event.target === checkoutModal) {
          closeCheckoutModal();
        }
      });

      // Close modal with Escape key
      document.addEventListener('keydown', function(event) {
        if (event.key === "Escape") {
          closeCheckoutModal();
        }
      });

      // Handle checkout form submission
      document.getElementById("checkoutForm")?.addEventListener("submit", function(e) {
        const customerName = this.elements['customer_name'].value.trim();
        const customerPhone = this.elements['customer_phone'].value.trim();
        const customerAddress = this.elements['customer_address'].value.trim();
        
        if (!customerName || !customerPhone || !customerAddress) {
          e.preventDefault();
          showToast('Please fill in all required fields.', 'error');
          return false;
        }
        
        // Phone number validation
        const phoneRegex = /^[0-9+\-\s()]{10,}$/;
        if (!phoneRegex.test(customerPhone)) {
          e.preventDefault();
          showToast('Please enter a valid phone number.', 'error');
          return false;
        }
        
        // Show loading spinner
        document.getElementById('loadingSpinner').classList.add('show');
        return true;
      });

      // Handle add to cart form submissions
      document.querySelectorAll('form[action=""]').forEach(form => {
        if (form.querySelector('input[name="add_to_cart"]')) {
          form.addEventListener('submit', function(e) {
            // Show loading spinner
            document.getElementById('loadingSpinner').classList.add('show');
            return true;
          });
        }
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
        
        // Check if there are new order notifications
        <?php if (!empty($order_notifications)): ?>
          <?php foreach ($order_notifications as $notification): ?>
            setTimeout(() => {
              showToast('<?php echo addslashes($notification['message']); ?>', 'info');
            }, 1000);
          <?php endforeach; ?>
        <?php endif; ?>
      });

      // Handle page visibility to show/hide loading spinner
      document.addEventListener('visibilitychange', function() {
        if (document.visibilityState === 'visible') {
          document.getElementById('loadingSpinner').classList.remove('show');
        }
      });

      // Auto-hide success/error messages after 5 seconds
      setTimeout(() => {
        const messages = document.querySelectorAll('.success-message, .error-message, .info-message');
        messages.forEach(msg => {
          msg.style.opacity = '0';
          msg.style.transition = 'opacity 0.5s ease';
          setTimeout(() => {
            if (msg.parentNode) msg.parentNode.removeChild(msg);
          }, 500);
        });
      }, 5000);

      // Auto-hide notifications after 10 seconds
      setTimeout(() => {
        const notifications = document.querySelectorAll('.notification-item');
        notifications.forEach(notification => {
          notification.style.opacity = '0';
          notification.style.transition = 'opacity 0.5s ease';
          setTimeout(() => {
            if (notification.parentNode) notification.parentNode.removeChild(notification);
          }, 500);
        });
      }, 10000);
    </script>
  </body>
</html>

<?php
// Close database connection
if (isset($conn)) {
    $conn->close();
}
?>