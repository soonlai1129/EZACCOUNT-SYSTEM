-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Oct 05, 2025 at 04:10 PM
-- Server version: 10.4.32-MariaDB
-- PHP Version: 8.2.12

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `ezaccount`
--

-- --------------------------------------------------------

--
-- Table structure for table `advertisement_expenses`
--

CREATE TABLE `advertisement_expenses` (
  `advertisement_id` int(11) NOT NULL,
  `record_id` int(11) NOT NULL,
  `expense_name` varchar(150) NOT NULL,
  `amount` decimal(12,2) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `business_owners`
--

CREATE TABLE `business_owners` (
  `owner_id` int(11) NOT NULL,
  `owner_name` varchar(255) NOT NULL,
  `company_name` varchar(255) NOT NULL,
  `email` varchar(100) NOT NULL,
  `password` varchar(100) NOT NULL,
  `phone_number` varchar(12) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `closing_reports`
--

CREATE TABLE `closing_reports` (
  `report_id` int(11) NOT NULL,
  `outlet_id` int(11) NOT NULL,
  `staff_id` int(11) DEFAULT NULL,
  `report_date` date NOT NULL,
  `shift` enum('Morning','Evening') NOT NULL,
  `cash_float` decimal(10,2) NOT NULL,
  `cash_sale` decimal(10,2) NOT NULL,
  `qr_sale` decimal(10,2) DEFAULT NULL,
  `total_cash` decimal(10,2) NOT NULL,
  `total_payment` decimal(10,2) NOT NULL,
  `expected_cash_in_hand` decimal(10,2) NOT NULL,
  `actual_cash_in_hand` decimal(10,2) NOT NULL,
  `difference` decimal(10,2) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `closing_report_cash_breakdown`
--

CREATE TABLE `closing_report_cash_breakdown` (
  `breakdown_id` int(11) NOT NULL,
  `report_id` int(11) NOT NULL,
  `denomination` decimal(10,2) NOT NULL,
  `quantity` int(11) NOT NULL,
  `total_amount` decimal(10,2) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `closing_report_payments`
--

CREATE TABLE `closing_report_payments` (
  `payment_id` int(11) NOT NULL,
  `report_id` int(11) NOT NULL,
  `payment_type` varchar(100) NOT NULL,
  `amount` decimal(10,2) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `operating_expense_records`
--

CREATE TABLE `operating_expense_records` (
  `record_id` int(11) NOT NULL,
  `outlet_id` int(11) NOT NULL,
  `record_date` date NOT NULL,
  `total_operating_expense` decimal(12,2) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `others_expenses`
--

CREATE TABLE `others_expenses` (
  `others_id` int(11) NOT NULL,
  `record_id` int(11) NOT NULL,
  `expense_name` varchar(150) NOT NULL,
  `amount` decimal(12,2) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `outlets`
--

CREATE TABLE `outlets` (
  `outlet_id` int(11) NOT NULL,
  `owner_id` int(11) NOT NULL,
  `outlet_name` varchar(255) NOT NULL,
  `outlet_address` varchar(255) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `rental_expenses`
--

CREATE TABLE `rental_expenses` (
  `rental_id` int(11) NOT NULL,
  `record_id` int(11) NOT NULL,
  `expense_name` varchar(150) NOT NULL,
  `amount` decimal(12,2) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `salary_expenses`
--

CREATE TABLE `salary_expenses` (
  `salary_id` int(11) NOT NULL,
  `record_id` int(11) NOT NULL,
  `staff_id` int(11) DEFAULT NULL,
  `amount` decimal(12,2) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `staff`
--

CREATE TABLE `staff` (
  `staff_id` int(11) NOT NULL,
  `outlet_id` int(11) NOT NULL,
  `staff_name` varchar(255) NOT NULL,
  `ic_number` varchar(14) NOT NULL,
  `age` int(3) NOT NULL,
  `phone_number` varchar(12) NOT NULL,
  `address` varchar(255) NOT NULL,
  `email` varchar(100) NOT NULL,
  `password` varchar(100) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `stock_category`
--

CREATE TABLE `stock_category` (
  `category_id` int(11) NOT NULL,
  `category_name` varchar(255) NOT NULL,
  `outlet_id` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `stock_items`
--

CREATE TABLE `stock_items` (
  `item_id` int(11) NOT NULL,
  `category_id` int(11) NOT NULL,
  `item_name` varchar(255) NOT NULL,
  `base_price` decimal(10,2) NOT NULL,
  `remaining_quantity` int(11) DEFAULT NULL,
  `total_stock_value` decimal(10,2) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `stock_movements`
--

CREATE TABLE `stock_movements` (
  `movement_id` int(11) NOT NULL,
  `item_id` int(11) NOT NULL,
  `staff_id` int(11) DEFAULT NULL,
  `movement_date` date NOT NULL,
  `movement_type` enum('IN','OUT') NOT NULL,
  `movement_quantity` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `utilities_expenses`
--

CREATE TABLE `utilities_expenses` (
  `utilities_id` int(11) NOT NULL,
  `record_id` int(11) NOT NULL,
  `expense_name` varchar(150) NOT NULL,
  `amount` decimal(12,2) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Indexes for dumped tables
--

--
-- Indexes for table `advertisement_expenses`
--
ALTER TABLE `advertisement_expenses`
  ADD PRIMARY KEY (`advertisement_id`),
  ADD KEY `record_id` (`record_id`);

--
-- Indexes for table `business_owners`
--
ALTER TABLE `business_owners`
  ADD PRIMARY KEY (`owner_id`),
  ADD UNIQUE KEY `email` (`email`);

--
-- Indexes for table `closing_reports`
--
ALTER TABLE `closing_reports`
  ADD PRIMARY KEY (`report_id`),
  ADD KEY `outlet_id` (`outlet_id`),
  ADD KEY `staff_id` (`staff_id`);

--
-- Indexes for table `closing_report_cash_breakdown`
--
ALTER TABLE `closing_report_cash_breakdown`
  ADD PRIMARY KEY (`breakdown_id`),
  ADD KEY `report_id` (`report_id`);

--
-- Indexes for table `closing_report_payments`
--
ALTER TABLE `closing_report_payments`
  ADD PRIMARY KEY (`payment_id`),
  ADD KEY `report_id` (`report_id`);

--
-- Indexes for table `operating_expense_records`
--
ALTER TABLE `operating_expense_records`
  ADD PRIMARY KEY (`record_id`),
  ADD KEY `outlet_id` (`outlet_id`);

--
-- Indexes for table `others_expenses`
--
ALTER TABLE `others_expenses`
  ADD PRIMARY KEY (`others_id`),
  ADD KEY `record_id` (`record_id`);

--
-- Indexes for table `outlets`
--
ALTER TABLE `outlets`
  ADD PRIMARY KEY (`outlet_id`),
  ADD KEY `owner_id` (`owner_id`);

--
-- Indexes for table `rental_expenses`
--
ALTER TABLE `rental_expenses`
  ADD PRIMARY KEY (`rental_id`),
  ADD KEY `record_id` (`record_id`);

--
-- Indexes for table `salary_expenses`
--
ALTER TABLE `salary_expenses`
  ADD PRIMARY KEY (`salary_id`),
  ADD KEY `record_id` (`record_id`),
  ADD KEY `staff_id` (`staff_id`);

--
-- Indexes for table `staff`
--
ALTER TABLE `staff`
  ADD PRIMARY KEY (`staff_id`),
  ADD UNIQUE KEY `email` (`email`),
  ADD KEY `outlet_id` (`outlet_id`);

--
-- Indexes for table `stock_category`
--
ALTER TABLE `stock_category`
  ADD PRIMARY KEY (`category_id`),
  ADD KEY `outlet_id` (`outlet_id`);

--
-- Indexes for table `stock_items`
--
ALTER TABLE `stock_items`
  ADD PRIMARY KEY (`item_id`),
  ADD KEY `category_id` (`category_id`);

--
-- Indexes for table `stock_movements`
--
ALTER TABLE `stock_movements`
  ADD PRIMARY KEY (`movement_id`),
  ADD KEY `item_id` (`item_id`),
  ADD KEY `staff_id` (`staff_id`);

--
-- Indexes for table `utilities_expenses`
--
ALTER TABLE `utilities_expenses`
  ADD PRIMARY KEY (`utilities_id`),
  ADD KEY `record_id` (`record_id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `advertisement_expenses`
--
ALTER TABLE `advertisement_expenses`
  MODIFY `advertisement_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `business_owners`
--
ALTER TABLE `business_owners`
  MODIFY `owner_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `closing_reports`
--
ALTER TABLE `closing_reports`
  MODIFY `report_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `closing_report_cash_breakdown`
--
ALTER TABLE `closing_report_cash_breakdown`
  MODIFY `breakdown_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `closing_report_payments`
--
ALTER TABLE `closing_report_payments`
  MODIFY `payment_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `operating_expense_records`
--
ALTER TABLE `operating_expense_records`
  MODIFY `record_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `others_expenses`
--
ALTER TABLE `others_expenses`
  MODIFY `others_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `outlets`
--
ALTER TABLE `outlets`
  MODIFY `outlet_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `rental_expenses`
--
ALTER TABLE `rental_expenses`
  MODIFY `rental_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `salary_expenses`
--
ALTER TABLE `salary_expenses`
  MODIFY `salary_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `staff`
--
ALTER TABLE `staff`
  MODIFY `staff_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `stock_category`
--
ALTER TABLE `stock_category`
  MODIFY `category_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `stock_items`
--
ALTER TABLE `stock_items`
  MODIFY `item_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `stock_movements`
--
ALTER TABLE `stock_movements`
  MODIFY `movement_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `utilities_expenses`
--
ALTER TABLE `utilities_expenses`
  MODIFY `utilities_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `advertisement_expenses`
--
ALTER TABLE `advertisement_expenses`
  ADD CONSTRAINT `advertisement_expenses_ibfk_1` FOREIGN KEY (`record_id`) REFERENCES `operating_expense_records` (`record_id`);

--
-- Constraints for table `closing_reports`
--
ALTER TABLE `closing_reports`
  ADD CONSTRAINT `closing_reports_ibfk_1` FOREIGN KEY (`outlet_id`) REFERENCES `outlets` (`outlet_id`),
  ADD CONSTRAINT `closing_reports_ibfk_2` FOREIGN KEY (`staff_id`) REFERENCES `staff` (`staff_id`);

--
-- Constraints for table `closing_report_cash_breakdown`
--
ALTER TABLE `closing_report_cash_breakdown`
  ADD CONSTRAINT `closing_report_cash_breakdown_ibfk_1` FOREIGN KEY (`report_id`) REFERENCES `closing_reports` (`report_id`);

--
-- Constraints for table `closing_report_payments`
--
ALTER TABLE `closing_report_payments`
  ADD CONSTRAINT `closing_report_payments_ibfk_1` FOREIGN KEY (`report_id`) REFERENCES `closing_reports` (`report_id`);

--
-- Constraints for table `operating_expense_records`
--
ALTER TABLE `operating_expense_records`
  ADD CONSTRAINT `operating_expense_records_ibfk_1` FOREIGN KEY (`outlet_id`) REFERENCES `outlets` (`outlet_id`);

--
-- Constraints for table `others_expenses`
--
ALTER TABLE `others_expenses`
  ADD CONSTRAINT `others_expenses_ibfk_1` FOREIGN KEY (`record_id`) REFERENCES `operating_expense_records` (`record_id`);

--
-- Constraints for table `outlets`
--
ALTER TABLE `outlets`
  ADD CONSTRAINT `outlets_ibfk_1` FOREIGN KEY (`owner_id`) REFERENCES `business_owners` (`owner_id`);

--
-- Constraints for table `rental_expenses`
--
ALTER TABLE `rental_expenses`
  ADD CONSTRAINT `rental_expenses_ibfk_1` FOREIGN KEY (`record_id`) REFERENCES `operating_expense_records` (`record_id`);

--
-- Constraints for table `salary_expenses`
--
ALTER TABLE `salary_expenses`
  ADD CONSTRAINT `salary_expenses_ibfk_1` FOREIGN KEY (`record_id`) REFERENCES `operating_expense_records` (`record_id`),
  ADD CONSTRAINT `salary_expenses_ibfk_2` FOREIGN KEY (`staff_id`) REFERENCES `staff` (`staff_id`);

--
-- Constraints for table `staff`
--
ALTER TABLE `staff`
  ADD CONSTRAINT `staff_ibfk_1` FOREIGN KEY (`outlet_id`) REFERENCES `outlets` (`outlet_id`);

--
-- Constraints for table `stock_category`
--
ALTER TABLE `stock_category`
  ADD CONSTRAINT `stock_category_ibfk_1` FOREIGN KEY (`outlet_id`) REFERENCES `outlets` (`outlet_id`);

--
-- Constraints for table `stock_items`
--
ALTER TABLE `stock_items`
  ADD CONSTRAINT `stock_items_ibfk_1` FOREIGN KEY (`category_id`) REFERENCES `stock_category` (`category_id`);

--
-- Constraints for table `stock_movements`
--
ALTER TABLE `stock_movements`
  ADD CONSTRAINT `stock_movements_ibfk_1` FOREIGN KEY (`item_id`) REFERENCES `stock_items` (`item_id`),
  ADD CONSTRAINT `stock_movements_ibfk_2` FOREIGN KEY (`staff_id`) REFERENCES `staff` (`staff_id`);

--
-- Constraints for table `utilities_expenses`
--
ALTER TABLE `utilities_expenses`
  ADD CONSTRAINT `utilities_expenses_ibfk_1` FOREIGN KEY (`record_id`) REFERENCES `operating_expense_records` (`record_id`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
