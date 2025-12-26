-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Dec 15, 2025 at 09:07 AM
-- Server version: 10.4.32-MariaDB
-- PHP Version: 8.0.30

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `perfume`
--

-- --------------------------------------------------------

--
-- Table structure for table `notifications`
--

CREATE TABLE `notifications` (
  `id` int(11) NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `title` varchar(100) NOT NULL,
  `message` text NOT NULL,
  `is_read` tinyint(1) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `notifications`
--

INSERT INTO `notifications` (`id`, `user_id`, `title`, `message`, `is_read`, `created_at`) VALUES
(1, NULL, 'New Perfume Collection', 'We have just launched a new perfume collection! Don\'t miss out on the luxurious new fragrances.', 0, '2025-12-15 05:15:35'),
(2, 1, 'Order Shipped', 'Your order #ORD-2024-001 has been shipped and is on its way to you.', 0, '2025-12-15 05:15:35'),
(3, NULL, 'Special Offer', 'Enjoy 20% off on all premium fragrances this weekend only! Use code: SCENT20 at checkout.', 0, '2025-12-15 05:15:35'),
(4, 1, 'Welcome to RRJJ Scents', 'Thank you for joining RRJJ Scents! Enjoy exclusive offers and new arrivals.', 0, '2025-12-15 05:15:35');

-- --------------------------------------------------------

--
-- Table structure for table `orders`
--

CREATE TABLE `orders` (
  `order_id` int(11) NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `total_amount` decimal(10,2) NOT NULL,
  `shipping_address` text NOT NULL,
  `customer_name` varchar(255) NOT NULL,
  `customer_phone` varchar(20) DEFAULT NULL,
  `status` enum('pending','approved','shipped','delivered','cancelled') DEFAULT 'pending',
  `admin_notes` text DEFAULT NULL,
  `order_date` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `orders`
--

INSERT INTO `orders` (`order_id`, `user_id`, `total_amount`, `shipping_address`, `customer_name`, `customer_phone`, `status`, `admin_notes`, `order_date`, `updated_at`) VALUES
(1, 2, 2914.99, '456 Customer St, Manila, Philippines', 'John Doe', '+639234567890', 'approved', NULL, '2025-12-10 01:01:35', '2025-12-15 01:01:35'),
(2, 3, 7790.00, '789 Sample Ave, Cebu, Philippines', 'Maria Santos', '+639345678901', 'shipped', NULL, '2025-12-12 01:01:35', '2025-12-15 01:01:35'),
(3, 4, 1895.00, '321 Example Rd, Davao, Philippines', 'Juan Dela Cruz', '+639456789012', 'delivered', NULL, '2025-12-13 01:01:35', '2025-12-15 01:01:35'),
(4, 2, 999.00, '456 Customer St, Manila, Philippines', 'John Doe', '+639234567890', 'pending', NULL, '2025-12-14 01:01:35', '2025-12-15 01:01:35'),
(5, 3, 6500.00, 'Tandag-Lanuza Road', 'jimcarlo', '09456456', 'approved', '', '2025-12-15 04:34:33', '2025-12-15 04:35:16');

-- --------------------------------------------------------

--
-- Table structure for table `order_items`
--

CREATE TABLE `order_items` (
  `item_id` int(11) NOT NULL,
  `order_id` int(11) NOT NULL,
  `product_id` int(11) NOT NULL,
  `quantity` int(11) NOT NULL,
  `price` decimal(10,2) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `order_items`
--

INSERT INTO `order_items` (`item_id`, `order_id`, `product_id`, `quantity`, `price`) VALUES
(1, 1, 1, 1, 2914.99),
(2, 2, 2, 1, 7790.00),
(3, 3, 3, 1, 1895.00),
(4, 4, 4, 1, 999.00),
(5, 5, 6, 1, 6500.00);

-- --------------------------------------------------------

--
-- Table structure for table `products`
--

CREATE TABLE `products` (
  `product_id` int(11) NOT NULL,
  `product_name` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `price` decimal(10,2) NOT NULL,
  `category` varchar(50) DEFAULT NULL,
  `image` varchar(255) DEFAULT NULL,
  `stock` int(11) DEFAULT 0,
  `status` enum('active','inactive') DEFAULT 'active',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `products`
--

INSERT INTO `products` (`product_id`, `product_name`, `description`, `price`, `category`, `image`, `stock`, `status`, `created_at`) VALUES
(1, 'Yves Saint Laurent Libre Eau De Toilette for Women', 'An elegant fragrance for confident women. A blend of lavender, orange blossom, and vanilla.', 2914.99, 'Women', 'images/products/693f9be16b6fc_1765776353.jpg', 50, 'active', '2025-12-15 01:01:35'),
(2, 'Yves Saint Laurent Black Opium for Women', 'Addictive oriental coffee scent with notes of black coffee, white flowers, and vanilla.', 7790.00, 'Women', 'images/ysl-black-opium.jpg', 50, 'active', '2025-12-15 01:01:35'),
(3, 'Elizabeth Arden Green Tea', 'Fresh and revitalizing green tea fragrance with citrus notes.', 1895.00, 'Women', 'images/elizabeth-arden-green-tea.jpg', 50, 'active', '2025-12-15 01:01:35'),
(4, 'Jovan White Musk Men', 'Clean, fresh musk fragrance for men.', 999.00, 'Men', 'images/jovan-white-musk-men.jpg', 50, 'active', '2025-12-15 01:01:35'),
(5, 'Dior Sauvage', 'Fresh and spicy fragrance with notes of bergamot, pepper, and ambroxan.', 4500.00, 'Men', 'images/dior-sauvage.jpg', 50, 'active', '2025-12-15 01:01:35'),
(6, 'Chanel No. 5', 'Timeless floral aldehyde fragrance for women.', 6500.00, 'Women', 'images/chanel-no5.jpg', 50, 'active', '2025-12-15 01:01:35'),
(7, 'Versace Eros for Men', 'A vibrant, intense, and luminous fragrance for men.', 4200.00, 'Men', 'images/versace-eros.jpg', 50, 'active', '2025-12-15 01:01:35'),
(8, 'Gucci Bloom for Women', 'A rich floral scent with notes of tuberose and jasmine.', 5800.00, 'Women', 'images/gucci-bloom.jpg', 50, 'active', '2025-12-15 01:01:35'),
(9, 'Calvin Klein One', 'A unisex fragrance that captures the individualistic spirit.', 2200.00, 'Unisex', 'images/ck-one.jpg', 50, 'active', '2025-12-15 01:01:35'),
(10, 'Tom Ford Tobacco Vanille', 'A rich, opulent fragrance with tobacco and vanilla notes.', 8500.00, 'Unisex', 'images/tf-tobacco-vanille.jpg', 50, 'active', '2025-12-15 01:01:35');

-- --------------------------------------------------------

--
-- Table structure for table `purchases`
--

CREATE TABLE `purchases` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `product_id` int(11) NOT NULL,
  `quantity` int(11) DEFAULT 1,
  `price` decimal(10,2) NOT NULL,
  `status` enum('pending','shipped','delivered','cancelled') DEFAULT 'pending',
  `purchase_date` timestamp NOT NULL DEFAULT current_timestamp(),
  `cancelled_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `purchases`
--

INSERT INTO `purchases` (`id`, `user_id`, `product_id`, `quantity`, `price`, `status`, `purchase_date`, `cancelled_at`) VALUES
(1, 1, 1, 1, 2914.99, 'pending', '2025-12-15 05:15:45', NULL),
(2, 1, 2, 2, 3500.00, 'shipped', '2025-12-15 05:15:45', NULL),
(3, 1, 3, 1, 2800.00, 'delivered', '2025-12-15 05:15:45', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `signup_db`
--

CREATE TABLE `signup_db` (
  `user_id` int(11) NOT NULL,
  `first_name` varchar(50) NOT NULL,
  `last_name` varchar(50) NOT NULL,
  `email` varchar(100) NOT NULL,
  `password_hash` varchar(255) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `signup_db`
--

INSERT INTO `signup_db` (`user_id`, `first_name`, `last_name`, `email`, `password_hash`, `created_at`) VALUES
(1, 'jimcarlo', 'pajenado', 'jimjimsabsal285@gmail.com', '$2y$10$5jDilf/ArLsWC6Nfpnk.2.3XOBg2zmePz5JEfO3ULnwHo.oV6pzYm', '2025-12-15 01:08:49'),
(2, 'jemalyn', 'hilos', 'jemalyn@gmail.com', '$2y$10$zN92YoMFYkfKHgGqBKE39ukwzvYW3E9MuGgvXXiCuNDodVNXc9qG.', '2025-12-15 01:10:34'),
(3, 'jimcarlo', 'pajenado', 'jimjimpajenado285@gmail.com', '$2y$10$9SyF0LyzVVBhPerwhRku8.lKU/Y3F49jtEzpTMhYaU3XFF0oWC3Di', '2025-12-15 04:34:12');

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `user_id` int(11) NOT NULL,
  `first_name` varchar(50) NOT NULL,
  `last_name` varchar(50) NOT NULL,
  `email` varchar(100) NOT NULL,
  `password` varchar(255) NOT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `address` text DEFAULT NULL,
  `user_role` enum('customer','admin') DEFAULT 'customer',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`user_id`, `first_name`, `last_name`, `email`, `password`, `phone`, `address`, `user_role`, `created_at`) VALUES
(1, 'Admin', 'User', 'admin@gmail.com', '$2y$10$HdasctDqpYxeJc0SoFUX7u.Z4M00GfMwidoHYMjIAk3qmD5pp2I8a', '+639123456789', '123 Admin Street, City, Country', 'admin', '2025-12-15 01:01:35'),
(2, 'John', 'Doe', 'john.doe@example.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '+639234567890', '456 Customer St, Manila, Philippines', 'customer', '2025-12-15 01:01:35'),
(3, 'Maria', 'Santos', 'maria.santos@example.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '+639345678901', '789 Sample Ave, Cebu, Philippines', 'customer', '2025-12-15 01:01:35'),
(4, 'Juan', 'Dela Cruz', 'juan.delacruz@example.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '+639456789012', '321 Example Rd, Davao, Philippines', 'customer', '2025-12-15 01:01:35'),
(5, 'jimcarlo', 'pajenado', 'jimjimsabsal285@gmail.com', '$2y$10$5jDilf/ArLsWC6Nfpnk.2.3XOBg2zmePz5JEfO3ULnwHo.oV6pzYm', NULL, NULL, 'customer', '2025-12-15 01:08:49'),
(6, 'jemalyn', 'hilos', 'jemalyn@gmail.com', '$2y$10$zN92YoMFYkfKHgGqBKE39ukwzvYW3E9MuGgvXXiCuNDodVNXc9qG.', NULL, NULL, 'customer', '2025-12-15 01:10:34'),
(7, 'jimcarlo', 'pajenado', 'jimjimpajenado285@gmail.com', '$2y$10$9SyF0LyzVVBhPerwhRku8.lKU/Y3F49jtEzpTMhYaU3XFF0oWC3Di', NULL, NULL, 'customer', '2025-12-15 04:34:12'),
(8, 'Juan', 'Dela Cruz', 'juan.delacruz@gmail.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', NULL, NULL, 'customer', '2025-12-15 05:15:25');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `notifications`
--
ALTER TABLE `notifications`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `orders`
--
ALTER TABLE `orders`
  ADD PRIMARY KEY (`order_id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `order_items`
--
ALTER TABLE `order_items`
  ADD PRIMARY KEY (`item_id`),
  ADD KEY `order_id` (`order_id`),
  ADD KEY `product_id` (`product_id`);

--
-- Indexes for table `products`
--
ALTER TABLE `products`
  ADD PRIMARY KEY (`product_id`);

--
-- Indexes for table `purchases`
--
ALTER TABLE `purchases`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `signup_db`
--
ALTER TABLE `signup_db`
  ADD PRIMARY KEY (`user_id`),
  ADD UNIQUE KEY `email` (`email`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`user_id`),
  ADD UNIQUE KEY `email` (`email`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `notifications`
--
ALTER TABLE `notifications`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `orders`
--
ALTER TABLE `orders`
  MODIFY `order_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `order_items`
--
ALTER TABLE `order_items`
  MODIFY `item_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `products`
--
ALTER TABLE `products`
  MODIFY `product_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- AUTO_INCREMENT for table `purchases`
--
ALTER TABLE `purchases`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `signup_db`
--
ALTER TABLE `signup_db`
  MODIFY `user_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `user_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `orders`
--
ALTER TABLE `orders`
  ADD CONSTRAINT `orders_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE SET NULL;

--
-- Constraints for table `order_items`
--
ALTER TABLE `order_items`
  ADD CONSTRAINT `order_items_ibfk_1` FOREIGN KEY (`order_id`) REFERENCES `orders` (`order_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `order_items_ibfk_2` FOREIGN KEY (`product_id`) REFERENCES `products` (`product_id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
