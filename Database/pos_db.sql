-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: May 29, 2025 at 10:54 AM
-- Server version: 10.4.28-MariaDB
-- PHP Version: 8.2.4

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `pos_db`
--

-- --------------------------------------------------------

--
-- Table structure for table `admins`
--

CREATE TABLE `admins` (
  `id` int(11) NOT NULL,
  `username` varchar(50) NOT NULL,
  `password` varchar(255) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `admins`
--

INSERT INTO `admins` (`id`, `username`, `password`) VALUES
(1, 'admin', 'admin');

-- --------------------------------------------------------

--
-- Table structure for table `cart`
--

CREATE TABLE `cart` (
  `id` int(11) NOT NULL,
  `session_id` varchar(255) NOT NULL,
  `product_id` int(11) NOT NULL,
  `quantity` int(11) NOT NULL,
  `price` decimal(10,2) NOT NULL,
  `product_name` varchar(255) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `cashiers`
--

CREATE TABLE `cashiers` (
  `id` int(11) NOT NULL,
  `full_name` varchar(100) NOT NULL,
  `email` varchar(100) NOT NULL,
  `password` varchar(255) NOT NULL,
  `created_at` datetime NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `cashiers`
--

INSERT INTO `cashiers` (`id`, `full_name`, `email`, `password`, `created_at`) VALUES
(1, 'NASIR ABBAS', 'cashier@gmail.com', '$2y$10$Z9jK3kiSsIZ1xH3wBgYQk.2WQVaP7jWFsshf.6LKUzip6BNfr05NK', '2025-05-13 15:10:14'),
(2, ' Muhammad Kashif', 'nasiryt.827@gmail.com', '$2y$10$Sf.i9oTXg6dKaZeRq5KdV.4WBSK2jR8KCH7P3EjGJB4I7dXTUTBhu', '2025-05-29 09:19:49');

-- --------------------------------------------------------

--
-- Table structure for table `categories`
--

CREATE TABLE `categories` (
  `id` int(11) NOT NULL,
  `name` varchar(255) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `categories`
--

INSERT INTO `categories` (`id`, `name`) VALUES
(3, 'Men'),
(4, 'Women'),
(5, 'Kids'),
(6, 'Home'),
(7, 'Electronics'),
(8, 'Pets'),
(9, 'Handbag'),
(10, 'Shoes'),
(11, 'Jewelry & Accessories'),
(12, 'Makeup'),
(14, 'Cycle');

-- --------------------------------------------------------

--
-- Table structure for table `products`
--

CREATE TABLE `products` (
  `ProductID` int(11) NOT NULL,
  `ProductName` varchar(255) NOT NULL,
  `Description` text NOT NULL,
  `Price` decimal(10,2) NOT NULL,
  `StockQuantity` int(11) NOT NULL,
  `CategoryID` int(11) DEFAULT NULL,
  `ImageURL` varchar(255) DEFAULT NULL,
  `Timestamp` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `products`
--

INSERT INTO `products` (`ProductID`, `ProductName`, `Description`, `Price`, `StockQuantity`, `CategoryID`, `ImageURL`, `Timestamp`) VALUES
(1, 'product 1', 'daette', 300.00, 9, 11, '68380c6f9e182.png', '2024-04-13 10:34:57'),
(2, 'product 2', 'afeeg', 5000.00, 1872, 3, '68380c68ab818.jpeg', '2024-04-13 10:34:57'),
(7, 'fd', 'fd', 3000.00, 2984, 3, '68380c5c73798.png', '2025-05-29 04:27:24');

-- --------------------------------------------------------

--
-- Table structure for table `returns`
--

CREATE TABLE `returns` (
  `id` int(11) NOT NULL,
  `product_name` varchar(255) NOT NULL,
  `quantity` int(11) NOT NULL,
  `reason` text DEFAULT NULL,
  `return_date` timestamp NOT NULL DEFAULT current_timestamp(),
  `cashier_id` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `returns`
--

INSERT INTO `returns` (`id`, `product_name`, `quantity`, `reason`, `return_date`, `cashier_id`) VALUES
(1, 'product 1', 20, 'fdas', '2025-05-29 08:49:59', 2),
(2, 'product 10', 20, 'fdas', '2025-05-29 08:50:15', 2);

-- --------------------------------------------------------

--
-- Table structure for table `sales`
--

CREATE TABLE `sales` (
  `sale_id` int(11) NOT NULL,
  `customer_name` varchar(255) DEFAULT NULL,
  `customer_phone` varchar(20) DEFAULT NULL,
  `cashier_id` int(11) NOT NULL,
  `total_amount` decimal(10,2) NOT NULL,
  `sale_date` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `sales`
--

INSERT INTO `sales` (`sale_id`, `customer_name`, `customer_phone`, `cashier_id`, `total_amount`, `sale_date`) VALUES
(1, '', '', 2, 15900.00, '2025-05-29 08:01:05'),
(2, 'NASIR ABBAS', '3176526827', 2, 9900.00, '2025-05-29 08:03:07'),
(3, 'NASIR ABBAS', '3176526827', 2, 600.00, '2025-05-29 08:04:34'),
(4, 'NASIR ABBAS', '3176526827', 2, 600.00, '2025-05-29 08:04:52'),
(5, '', '', 2, 10000.00, '2025-05-27 08:05:42'),
(6, 'New', '3176526827', 2, 15000.00, '2025-05-29 08:06:36'),
(7, '', '', 2, 10000.00, '2025-05-29 08:07:14'),
(8, '', '', 2, 600.00, '2025-05-29 08:08:04'),
(9, 'Haider', '3176526827', 2, 300.00, '2025-05-29 08:11:38'),
(10, '', '', 2, 300.00, '2025-05-21 08:12:14'),
(11, '', '', 2, 600.00, '2025-05-29 08:12:47'),
(12, 'New NAme', '3176526827', 2, 18300.00, '2025-05-29 08:14:12'),
(13, '', '', 2, 5300.00, '2025-05-29 08:16:25'),
(14, '', '', 2, 600.00, '2025-05-29 08:16:35'),
(15, '', '', 2, 5000.00, '2025-05-26 08:17:19'),
(16, '', '', 2, 5000.00, '2025-05-29 08:17:51');

-- --------------------------------------------------------

--
-- Table structure for table `sale_items`
--

CREATE TABLE `sale_items` (
  `sale_item_id` int(11) NOT NULL,
  `sale_id` int(11) NOT NULL,
  `product_id` int(11) NOT NULL,
  `quantity` int(11) NOT NULL,
  `price` decimal(10,2) NOT NULL,
  `total_amount` decimal(10,2) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `sale_items`
--

INSERT INTO `sale_items` (`sale_item_id`, `sale_id`, `product_id`, `quantity`, `price`, `total_amount`) VALUES
(1, 1, 1, 3, 300.00, 900.00),
(2, 1, 2, 3, 5000.00, 15000.00),
(3, 2, 1, 3, 300.00, 900.00),
(4, 2, 7, 3, 3000.00, 9000.00),
(5, 3, 1, 2, 300.00, 600.00),
(6, 4, 1, 2, 300.00, 600.00),
(7, 5, 2, 2, 5000.00, 10000.00),
(8, 6, 2, 3, 5000.00, 15000.00),
(9, 7, 2, 2, 5000.00, 10000.00),
(10, 8, 1, 2, 300.00, 600.00),
(11, 9, 1, 1, 300.00, 300.00),
(12, 10, 1, 1, 300.00, 300.00),
(13, 11, 1, 2, 300.00, 600.00),
(14, 12, 1, 1, 300.00, 300.00),
(15, 12, 2, 3, 5000.00, 15000.00),
(16, 12, 7, 1, 3000.00, 3000.00),
(17, 13, 1, 1, 300.00, 300.00),
(18, 13, 2, 1, 5000.00, 5000.00),
(19, 14, 1, 2, 300.00, 600.00),
(20, 15, 2, 1, 5000.00, 5000.00),
(21, 16, 2, 1, 5000.00, 5000.00);

--
-- Indexes for dumped tables
--

--
-- Indexes for table `cart`
--
ALTER TABLE `cart`
  ADD PRIMARY KEY (`id`),
  ADD KEY `product_id` (`product_id`),
  ADD KEY `idx_session_id` (`session_id`);

--
-- Indexes for table `cashiers`
--
ALTER TABLE `cashiers`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `email` (`email`);

--
-- Indexes for table `categories`
--
ALTER TABLE `categories`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `products`
--
ALTER TABLE `products`
  ADD PRIMARY KEY (`ProductID`),
  ADD KEY `CategoryID` (`CategoryID`);

--
-- Indexes for table `returns`
--
ALTER TABLE `returns`
  ADD PRIMARY KEY (`id`),
  ADD KEY `cashier_id` (`cashier_id`);

--
-- Indexes for table `sales`
--
ALTER TABLE `sales`
  ADD PRIMARY KEY (`sale_id`),
  ADD KEY `cashier_id` (`cashier_id`);

--
-- Indexes for table `sale_items`
--
ALTER TABLE `sale_items`
  ADD PRIMARY KEY (`sale_item_id`),
  ADD KEY `sale_id` (`sale_id`),
  ADD KEY `product_id` (`product_id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `cart`
--
ALTER TABLE `cart`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=22;

--
-- AUTO_INCREMENT for table `cashiers`
--
ALTER TABLE `cashiers`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `categories`
--
ALTER TABLE `categories`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=15;

--
-- AUTO_INCREMENT for table `products`
--
ALTER TABLE `products`
  MODIFY `ProductID` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT for table `returns`
--
ALTER TABLE `returns`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `sales`
--
ALTER TABLE `sales`
  MODIFY `sale_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=17;

--
-- AUTO_INCREMENT for table `sale_items`
--
ALTER TABLE `sale_items`
  MODIFY `sale_item_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=22;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `cart`
--
ALTER TABLE `cart`
  ADD CONSTRAINT `cart_ibfk_1` FOREIGN KEY (`product_id`) REFERENCES `products` (`ProductID`) ON DELETE CASCADE;

--
-- Constraints for table `products`
--
ALTER TABLE `products`
  ADD CONSTRAINT `products_ibfk_1` FOREIGN KEY (`CategoryID`) REFERENCES `categories` (`id`);

--
-- Constraints for table `returns`
--
ALTER TABLE `returns`
  ADD CONSTRAINT `returns_ibfk_1` FOREIGN KEY (`cashier_id`) REFERENCES `cashiers` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `sales`
--
ALTER TABLE `sales`
  ADD CONSTRAINT `sales_ibfk_1` FOREIGN KEY (`cashier_id`) REFERENCES `cashiers` (`id`);

--
-- Constraints for table `sale_items`
--
ALTER TABLE `sale_items`
  ADD CONSTRAINT `sale_items_ibfk_1` FOREIGN KEY (`sale_id`) REFERENCES `sales` (`sale_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `sale_items_ibfk_2` FOREIGN KEY (`product_id`) REFERENCES `products` (`ProductID`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
