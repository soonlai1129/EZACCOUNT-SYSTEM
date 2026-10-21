-- phpMyAdmin SQL Dump
-- version 5.2.0
-- https://www.phpmyadmin.net/
--
-- Host: localhost
-- Generation Time: Nov 05, 2025 at 07:08 AM
-- Server version: 10.6.11-MariaDB
-- PHP Version: 7.4.33

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `student_ezaccount`
--

-- --------------------------------------------------------

--
-- Table structure for table `advertisement_expenses`
--

CREATE TABLE `advertisement_expenses` (
  `advertisement_id` int(11) NOT NULL,
  `record_id` int(11) NOT NULL,
  `expense_name` varchar(255) NOT NULL,
  `amount` decimal(12,2) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `advertisement_expenses`
--

INSERT INTO `advertisement_expenses` (`advertisement_id`, `record_id`, `expense_name`, `amount`) VALUES
(3, 2, 'kaa', '12.10'),
(4, 2, 'taa', '2.20'),
(5, 3, 'nb', '23.00'),
(6, 3, 'as', '11.00'),
(8, 5, 'jj', '5.00'),
(9, 5, 'dd', '2.00'),
(10, 6, 'a', '2.00'),
(13, 11, 'd', '3.00'),
(14, 12, 'f', '3.00'),
(15, 13, 'hh', '33.00'),
(16, 13, 'd', '4.00'),
(18, 16, 'sdf', '213.00'),
(19, 17, 'asd', '32.00'),
(20, 21, 'ff', '34.00'),
(26, 23, 'Banner', '20.00'),
(35, 25, 'banner', '3.00'),
(36, 25, 'logo', '2.00'),
(38, 30, 'Ad', '1100.00'),
(43, 33, 'banner', '3.00'),
(44, 33, 'poster', '4.00'),
(45, 34, 'ad', '5.00'),
(46, 35, 'ad', '5.00'),
(47, 36, 'ad', '5.00'),
(48, 37, 'ad', '5.00'),
(49, 38, 'ad', '5.00'),
(51, 39, 'ad', '5.00'),
(52, 40, 'ad', '5.00'),
(53, 41, 'banner', '5.00'),
(55, 42, 'banner', '5.00'),
(57, 43, 'banner', '2.00'),
(63, 45, 'ad', '25.00');

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

--
-- Dumping data for table `business_owners`
--

INSERT INTO `business_owners` (`owner_id`, `owner_name`, `company_name`, `email`, `password`, `phone_number`) VALUES
(1, 'HUA SOON LAI', 'BIJI KOPI', 'soonlai1129@gmail.com', 'Abcd1234@', '011-12477991'),
(2, 'CHUI JUEN ONG', 'Biji Kupi', 'ochuijuen@gmail.com', '1234Abcd@', '011-53306598'),
(3, 'RAMBO HERO', 'Kkmt', 'ramzi016@gmail.com', 'Malaysia123#', '001-95105159'),
(5, 'ONG CHUI JUEN', 'Biji Kupi', 'chuijuenong99@gmail.com', '1234Abcd@', '011-53306598'),
(6, 'AHMAD KHAIRI', 'BIJI KUPI', 'khy4045.ky@gmail.com', 'Bayu4045@', '010-5524254'),
(7, 'HARUMI', 'Car Repair', 'icescheng2640@gmail.com', 'Aa@1234567', '011-9087253'),
(8, 'LIM JUN HWA', 'H Tech Solution Sdn Bhd', 'ljhwa2004@gmail.com', 'Junhwa@2004', '011-23080722');

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

--
-- Dumping data for table `closing_reports`
--

INSERT INTO `closing_reports` (`report_id`, `outlet_id`, `staff_id`, `report_date`, `shift`, `cash_float`, `cash_sale`, `qr_sale`, `total_cash`, `total_payment`, `expected_cash_in_hand`, `actual_cash_in_hand`, `difference`) VALUES
(1, 1, NULL, '2025-01-01', 'Evening', '200.00', '120.00', '20.00', '320.00', '15.00', '305.00', '100.00', '-205.00'),
(3, 3, NULL, '2025-02-01', 'Evening', '200.00', '200.00', '100.00', '400.00', '68.00', '332.00', '60.00', '-272.00'),
(5, 1, 1, '2025-03-01', 'Morning', '100.00', '200.00', '100.00', '300.00', '0.00', '300.00', '300.00', '0.00'),
(6, 1, 1, '2025-03-31', 'Evening', '100.00', '200.00', '10.00', '300.00', '0.00', '300.00', '300.00', '0.00'),
(7, 1, 1, '2025-04-01', 'Evening', '300.00', '100.00', '400.00', '400.00', '30.00', '370.00', '370.00', '0.00'),
(8, 1, 1, '2025-04-30', 'Evening', '300.00', '100.00', '200.00', '400.00', '0.00', '400.00', '400.00', '0.00'),
(9, 1, NULL, '2025-05-01', 'Morning', '300.00', '100.00', '100.00', '400.00', '0.00', '400.00', '400.00', '0.00'),
(10, 1, NULL, '2025-05-31', 'Evening', '20.00', '100.00', '40.00', '120.00', '40.00', '80.00', '80.00', '0.00'),
(18, 3, NULL, '2025-09-01', 'Evening', '200.00', '100.00', '100.00', '300.00', '0.00', '300.00', '300.00', '0.00'),
(20, 3, NULL, '2025-10-01', 'Evening', '200.00', '120.40', '130.12', '320.40', '0.00', '320.40', '320.40', '0.00'),
(21, 3, NULL, '2025-10-08', 'Evening', '200.00', '42.20', '123.00', '242.20', '22.00', '220.20', '220.20', '0.00'),
(22, 1, NULL, '2025-10-12', 'Morning', '120.00', '90.00', '120.00', '210.00', '10.00', '200.00', '200.00', '0.00'),
(23, 7, NULL, '2025-10-22', 'Morning', '300.00', '600.00', '200.00', '900.00', '3.00', '897.00', '907.00', '10.00'),
(24, 1, NULL, '2025-10-22', 'Morning', '300.00', '100.00', '100.00', '400.00', '0.00', '400.00', '400.00', '0.00'),
(25, 1, NULL, '2025-10-24', 'Morning', '56.00', '23.00', '56.00', '79.00', '0.00', '79.00', '79.00', '0.00'),
(27, 3, NULL, '2025-10-27', 'Evening', '586.60', '218.50', '308.50', '805.10', '55.00', '750.10', '750.10', '0.00'),
(30, 1, NULL, '2025-10-29', 'Morning', '200.00', '200.00', '0.00', '400.00', '0.00', '400.00', '400.00', '0.00'),
(31, 6, NULL, '2025-10-31', 'Morning', '123.00', '567.00', '330.00', '690.00', '300.00', '390.00', '750.00', '360.00'),
(32, 6, 8, '2025-10-31', 'Morning', '300.00', '700.00', '888.00', '1000.00', '300.00', '700.00', '720.00', '20.00'),
(33, 3, NULL, '2025-11-01', 'Morning', '450.00', '300.00', '330.00', '750.00', '5.00', '745.00', '745.00', '0.00'),
(36, 1, 1, '2025-10-01', 'Evening', '400.00', '200.00', '100.00', '600.00', '4.00', '596.00', '596.00', '0.00'),
(37, 1, 1, '2025-10-31', 'Evening', '100.00', '200.00', '200.00', '300.00', '0.00', '300.00', '300.00', '0.00'),
(38, 3, NULL, '2025-10-01', 'Morning', '200.00', '300.00', '100.00', '500.00', '5.00', '495.00', '495.00', '0.00'),
(40, 3, NULL, '2025-11-02', 'Morning', '200.00', '300.00', '200.00', '500.00', '10.00', '490.00', '490.00', '0.00'),
(46, 3, NULL, '2025-11-03', 'Morning', '205.00', '205.00', '205.00', '410.00', '10.50', '399.50', '799.20', '399.70'),
(47, 3, 5, '2025-11-02', 'Evening', '205.00', '205.00', '205.00', '410.00', '10.00', '400.00', '400.00', '0.00'),
(48, 3, NULL, '2025-11-03', 'Evening', '205.00', '205.00', '205.00', '410.00', '10.00', '400.00', '400.00', '0.00'),
(49, 1, NULL, '2025-11-03', 'Morning', '100.00', '120.00', '100.00', '220.00', '5.00', '215.00', '215.00', '0.00'),
(50, 3, NULL, '2025-11-03', 'Morning', '200.00', '100.00', '200.00', '300.00', '5.00', '295.00', '295.00', '0.00'),
(52, 3, NULL, '2025-11-03', 'Morning', '200.00', '100.00', '100.00', '300.00', '5.00', '295.00', '250.00', '-45.00'),
(53, 3, NULL, '2025-11-03', 'Evening', '205.00', '205.00', '300.00', '410.00', '10.00', '400.00', '400.00', '0.00'),
(54, 3, NULL, '2025-11-03', 'Evening', '205.00', '205.00', '205.00', '410.00', '5.00', '405.00', '400.00', '-5.00');

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

--
-- Dumping data for table `closing_report_cash_breakdown`
--

INSERT INTO `closing_report_cash_breakdown` (`breakdown_id`, `report_id`, `denomination`, `quantity`, `total_amount`) VALUES
(1, 1, '50.00', 2, '100.00'),
(3, 3, '5.00', 12, '60.00'),
(40, 23, '100.00', 4, '400.00'),
(41, 23, '50.00', 3, '150.00'),
(42, 23, '20.00', 4, '80.00'),
(43, 23, '10.00', 26, '260.00'),
(44, 23, '5.00', 3, '15.00'),
(45, 23, '1.00', 2, '2.00'),
(73, 31, '100.00', 5, '500.00'),
(74, 31, '50.00', 5, '250.00'),
(78, 32, '100.00', 4, '400.00'),
(79, 32, '50.00', 4, '200.00'),
(80, 32, '20.00', 6, '120.00'),
(89, 33, '100.00', 7, '700.00'),
(90, 33, '20.00', 2, '40.00'),
(91, 33, '5.00', 1, '5.00'),
(92, 30, '100.00', 4, '400.00'),
(93, 27, '50.00', 1, '50.00'),
(94, 27, '20.00', 5, '100.00'),
(95, 27, '10.00', 37, '370.00'),
(96, 27, '1.00', 176, '176.00'),
(97, 27, '0.20', 266, '53.20'),
(98, 27, '0.10', 9, '0.90'),
(99, 24, '100.00', 1, '100.00'),
(100, 24, '50.00', 1, '50.00'),
(101, 24, '20.00', 12, '240.00'),
(102, 24, '10.00', 1, '10.00'),
(103, 21, '100.00', 2, '200.00'),
(104, 21, '1.00', 9, '9.00'),
(105, 21, '0.50', 22, '11.00'),
(106, 21, '0.20', 1, '0.20'),
(107, 25, '20.00', 3, '60.00'),
(108, 25, '10.00', 1, '10.00'),
(109, 25, '5.00', 1, '5.00'),
(110, 25, '1.00', 4, '4.00'),
(111, 22, '100.00', 2, '200.00'),
(112, 20, '100.00', 1, '100.00'),
(113, 20, '50.00', 4, '200.00'),
(114, 20, '20.00', 1, '20.00'),
(115, 20, '0.20', 2, '0.40'),
(116, 18, '100.00', 1, '100.00'),
(117, 18, '10.00', 20, '200.00'),
(118, 10, '10.00', 3, '30.00'),
(119, 10, '1.00', 50, '50.00'),
(121, 9, '100.00', 4, '400.00'),
(122, 8, '50.00', 6, '300.00'),
(123, 8, '5.00', 20, '100.00'),
(124, 7, '100.00', 3, '300.00'),
(125, 7, '5.00', 14, '70.00'),
(126, 6, '100.00', 2, '200.00'),
(127, 6, '5.00', 20, '100.00'),
(128, 5, '100.00', 3, '300.00'),
(129, 36, '50.00', 10, '500.00'),
(130, 36, '20.00', 4, '80.00'),
(131, 36, '10.00', 1, '10.00'),
(132, 36, '5.00', 1, '5.00'),
(133, 36, '1.00', 1, '1.00'),
(134, 37, '100.00', 3, '300.00'),
(142, 38, '50.00', 3, '150.00'),
(143, 38, '20.00', 4, '80.00'),
(144, 38, '10.00', 20, '200.00'),
(145, 38, '5.00', 10, '50.00'),
(146, 38, '1.00', 15, '15.00'),
(150, 40, '100.00', 4, '400.00'),
(151, 40, '50.00', 1, '50.00'),
(152, 40, '20.00', 2, '40.00'),
(164, 46, '100.00', 5, '500.00'),
(165, 46, '50.00', 5, '250.00'),
(166, 46, '20.00', 1, '20.00'),
(167, 46, '5.00', 5, '25.00'),
(168, 46, '0.50', 8, '4.00'),
(169, 46, '0.20', 1, '0.20'),
(172, 47, '100.00', 4, '400.00'),
(174, 48, '100.00', 4, '400.00'),
(175, 49, '100.00', 2, '200.00'),
(176, 49, '10.00', 1, '10.00'),
(177, 49, '5.00', 1, '5.00'),
(178, 50, '100.00', 2, '200.00'),
(179, 50, '50.00', 1, '50.00'),
(180, 50, '20.00', 2, '40.00'),
(181, 50, '5.00', 1, '5.00'),
(183, 52, '100.00', 1, '100.00'),
(184, 52, '50.00', 3, '150.00'),
(186, 53, '100.00', 4, '400.00'),
(187, 54, '100.00', 4, '400.00');

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

--
-- Dumping data for table `closing_report_payments`
--

INSERT INTO `closing_report_payments` (`payment_id`, `report_id`, `payment_type`, `amount`) VALUES
(1, 1, 'Gaji', '4.00'),
(2, 1, 'Ais', '2.00'),
(3, 1, 'Serahan', '4.00'),
(4, 1, 'gg', '5.00'),
(5, 3, 'Gaji', '22.00'),
(6, 3, 'Ais', '45.00'),
(7, 3, 'Serahan', '1.00'),
(30, 23, 'ais batu', '3.00'),
(33, 31, 'Gaji', '300.00'),
(35, 32, 'Gaji', '300.00'),
(39, 33, 'ais batu', '5.00'),
(40, 27, 'Gaji', '55.00'),
(41, 21, '12', '22.00'),
(42, 22, 'ais batu', '10.00'),
(43, 10, 'Gaji', '20.00'),
(44, 10, 'Ais', '20.00'),
(45, 7, 'Gaji', '10.00'),
(46, 7, 'Ais', '20.00'),
(47, 36, 'ais batu', '4.00'),
(49, 38, 'ais batu', '5.00'),
(51, 40, 'Ice', '10.00'),
(58, 46, 'Ice', '10.50'),
(60, 47, 'Ice', '10.00'),
(62, 48, 'Ice', '10.00'),
(63, 49, 'ice', '5.00'),
(64, 50, 'ice', '5.00'),
(66, 52, 'ice', '5.00'),
(68, 53, 'Ice', '10.00'),
(69, 54, 'Ice', '5.00');

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

--
-- Dumping data for table `operating_expense_records`
--

INSERT INTO `operating_expense_records` (`record_id`, `outlet_id`, `record_date`, `total_operating_expense`) VALUES
(2, 1, '2025-01-31', '169.90'),
(3, 3, '2025-02-01', '88.00'),
(5, 3, '2025-03-01', '48.00'),
(6, 1, '2025-03-31', '116.00'),
(10, 3, '2025-05-31', '35.00'),
(11, 1, '2025-06-01', '136.00'),
(12, 3, '2025-06-30', '216.00'),
(13, 3, '2025-07-01', '119.00'),
(16, 3, '2025-08-31', '217.00'),
(17, 3, '2025-09-01', '34.00'),
(18, 1, '2025-09-01', '61.00'),
(21, 3, '2025-10-08', '66.00'),
(23, 1, '2025-10-12', '628.00'),
(25, 7, '2025-10-22', '558.70'),
(26, 1, '2025-10-24', '345.00'),
(30, 6, '2025-10-31', '3.00'),
(33, 3, '2025-11-02', '1969.00'),
(34, 3, '2025-11-02', '175.00'),
(35, 3, '2025-11-03', '1045.00'),
(36, 3, '2025-11-03', '620.00'),
(37, 3, '2025-11-03', '1020.00'),
(38, 3, '2025-11-03', '572.00'),
(39, 3, '2025-11-03', '770.00'),
(40, 1, '2025-11-03', '320.00'),
(41, 3, '2025-11-03', '417.00'),
(42, 3, '2025-11-03', '768.00'),
(43, 3, '2025-11-03', '962.00'),
(45, 3, '2025-11-03', '2.00'),
(46, 12, '2025-11-04', '2500.00');

-- --------------------------------------------------------

--
-- Table structure for table `others_expenses`
--

CREATE TABLE `others_expenses` (
  `others_id` int(11) NOT NULL,
  `record_id` int(11) NOT NULL,
  `expense_name` varchar(255) NOT NULL,
  `amount` decimal(12,2) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `others_expenses`
--

INSERT INTO `others_expenses` (`others_id`, `record_id`, `expense_name`, `amount`) VALUES
(3, 2, 'haha', '34.00'),
(4, 2, 'hello', '2.00'),
(5, 5, 'dfg', '4.00'),
(6, 5, 'sd', '1.00'),
(9, 10, 'gh', '5.00'),
(10, 13, 'a', '23.00'),
(11, 13, 'f', '2.00'),
(12, 16, 'ddf', '2.00'),
(13, 16, 'g', '2.00'),
(14, 17, 'f', '2.00'),
(15, 18, 'vv', '2.00'),
(16, 18, 'zz', '3.00'),
(24, 23, 'cup', '120.00'),
(30, 25, 'kayu', '2.00'),
(32, 30, 'Ais', '300.00'),
(35, 33, 'insurance', '5.00'),
(36, 34, 'maintenance', '10.00'),
(37, 35, 'insurance', '10.00'),
(38, 36, 'maintenance', '5.00'),
(39, 37, 'maintenance', '5.00'),
(40, 38, 'maintenance', '5.00'),
(42, 39, 'maintenance', '5.00'),
(43, 40, 'maintenance', '5.00'),
(44, 41, 'maintenace', '2.00'),
(46, 42, 'maintenance', '3.00'),
(48, 43, 'maintenance', '30.00'),
(54, 45, 'maintenance', '200.00');

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

--
-- Dumping data for table `outlets`
--

INSERT INTO `outlets` (`outlet_id`, `owner_id`, `outlet_name`, `outlet_address`) VALUES
(1, 1, 'ALOR SETAR', 'LOT 1081, JALAN SULTANAH, LEBUHRAYA SULTANAH BAHIYAH, SAMBUNGAN, 05350 ALOR SETAR, KEDAH'),
(3, 1, 'JITRA', 'JALAN DARULAMAN JAYA 1, BANDAR DARULAMAN JAYA, 06000 JITRA, KEDAH'),
(6, 5, 'JITRA BIJI KUPI', 'JALAN DARULAMAN JAYA 1, BANDAR DARULAMAN JAYA'),
(7, 6, 'JITRA', 'KIOSK BIJI KUPI\r\n06000 JITRA'),
(8, 2, 'BIJI KUPI JITRA', 'TAMAN SERI PAGI'),
(10, 7, 'FS ENTERPRISE', 'KULIM.PERDANA\nKULIM HITECH PARK\n09000 KULIM\nKEDAH'),
(11, 5, 'PENDANG', 'PERSIARAN PENDANG SQUARE 1, PENDANG SQUARE, 06700 PENDANG, KEDAH'),
(12, 8, 'HQ', '78, JALAN BERSATU, TAMAN PERINDUSTRIAN PERMATA, 05000 A/S KEDAH');

-- --------------------------------------------------------

--
-- Table structure for table `rental_expenses`
--

CREATE TABLE `rental_expenses` (
  `rental_id` int(11) NOT NULL,
  `record_id` int(11) NOT NULL,
  `expense_name` varchar(255) NOT NULL,
  `amount` decimal(12,2) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `rental_expenses`
--

INSERT INTO `rental_expenses` (`rental_id`, `record_id`, `expense_name`, `amount`) VALUES
(3, 2, 'oppo', '10.20'),
(4, 2, 'kayu', '2.20'),
(5, 3, 'qwe', '22.00'),
(7, 5, 'asd', '12.00'),
(8, 6, 'asd', '12.00'),
(9, 6, 'ff', '23.00'),
(14, 10, 'fg', '22.00'),
(15, 10, '2', '4.00'),
(16, 11, 'sd', '34.00'),
(17, 11, 'f', '1.00'),
(18, 13, 'sd', '22.00'),
(23, 21, 'sdf', '32.00'),
(30, 23, 'kedai', '250.00'),
(31, 23, 'kerusi', '60.00'),
(40, 25, 'kedai', '450.00'),
(41, 25, 'car', '100.00'),
(43, 30, 'Shop', '430.00'),
(48, 33, 'car', '50.00'),
(49, 34, 'shop', '50.00'),
(50, 35, 'shop', '20.00'),
(51, 36, 'shop', '100.00'),
(52, 37, 'shop', '500.00'),
(53, 38, 'shop', '50.00'),
(55, 39, 'shop', '50.00'),
(56, 40, 'shop', '200.00'),
(57, 41, 'shop', '200.00'),
(59, 42, 'shop', '50.00'),
(61, 43, 'shop', '220.00'),
(67, 46, 'Rental expenses', '2000.00'),
(68, 45, 'shop', '500.00');

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

--
-- Dumping data for table `salary_expenses`
--

INSERT INTO `salary_expenses` (`salary_id`, `record_id`, `staff_id`, `amount`) VALUES
(2, 2, 1, '100.00'),
(4, 6, 1, '12.00'),
(5, 6, 1, '44.00'),
(9, 11, 1, '33.00'),
(10, 11, 1, '33.00'),
(13, 18, 1, '23.00'),
(19, 23, 1, '100.00'),
(21, 26, 5, '345.00'),
(25, 30, 4, '1800.00'),
(28, 33, 9, '1800.00'),
(29, 33, 5, '100.00'),
(30, 34, 5, '100.00'),
(31, 35, 5, '1000.00'),
(32, 36, 5, '500.00'),
(33, 37, 5, '500.00'),
(34, 38, 5, '500.00'),
(36, 39, 5, '700.00'),
(37, 40, 1, '100.00'),
(38, 41, 5, '200.00'),
(40, 42, 5, '700.00'),
(42, 43, 5, '700.00'),
(49, 45, 5, '1700.00');

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

--
-- Dumping data for table `staff`
--

INSERT INTO `staff` (`staff_id`, `outlet_id`, `staff_name`, `ic_number`, `age`, `phone_number`, `address`, `email`, `password`) VALUES
(1, 1, 'SOON LEE', '051129-02-0257', 22, '011-12477990', '485 TAMAN BERSATU KUALA KEDAH', 'soonlaihelp@gmail.com', 'Abcd1234@'),
(4, 6, 'ICES', '050101-02-0101', 30, '012-3456789', 'TAMAN ANGGERIK', 'chuijuen.ong@gmail.com', '1234Abcd@'),
(5, 3, 'DANIEL', '070809-02-0222', 20, '011-12477991', '485,TAMAN BERSATU FASA 3\r\nJALAN BATAS PAIP\r\nMALAYSIA\r\nKEDAH ALOR SETAR', 'm-6498833@moe-dl.edu.my', 'Abcd1234@'),
(8, 6, 'IVY OOI', '040522-02-9000', 21, '001-8990222', 'TAMAN SISWA 1', 'yingyinbing@gmail.com', '12345678Aa@'),
(9, 3, 'AKILAH', '981201-02-0202', 30, '012-3456789', 'TAMAN SISWA 2, LORONG 1, 06000, JITRA, KEDAH', 'akilahstaff1201@gmail.com', 'Abcd1234@');

-- --------------------------------------------------------

--
-- Table structure for table `stock_category`
--

CREATE TABLE `stock_category` (
  `category_id` int(11) NOT NULL,
  `category_name` varchar(255) NOT NULL,
  `outlet_id` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `stock_category`
--

INSERT INTO `stock_category` (`category_id`, `category_name`, `outlet_id`) VALUES
(1, 'POWDER', 1),
(5, 'SYRUP', 1),
(6, 'POWDER', 7),
(7, 'SYRUP', 7),
(13, 'FRUITMIX', 7),
(14, 'SAUCE', 7),
(15, 'MILK', 7),
(16, 'BEANS', 7),
(17, 'PASTRY', 7),
(18, 'SUGAR', 7),
(19, 'CUP & OTHERS', 7),
(21, 'Powder', 8),
(22, 'Powder', 11),
(23, 'Powder', 6),
(25, 'FRUITMIX', 1),
(26, 'FRUITMIX', 3),
(28, 'POWDER', 3),
(41, 'sugar', 1),
(44, 'Sauce', 3),
(45, 'sugar', 3),
(46, 'PC/Laptops', 12);

-- --------------------------------------------------------

--
-- Table structure for table `stock_items`
--

CREATE TABLE `stock_items` (
  `item_id` int(11) NOT NULL,
  `category_id` int(11) NOT NULL,
  `item_name` varchar(255) NOT NULL,
  `base_price` decimal(10,2) NOT NULL,
  `remaining_quantity` int(11) NOT NULL,
  `total_stock_value` decimal(10,2) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `stock_items`
--

INSERT INTO `stock_items` (`item_id`, `category_id`, `item_name`, `base_price`, `remaining_quantity`, `total_stock_value`) VALUES
(1, 1, 'Peach Tea Powder', '33.00', 1, '33.00'),
(2, 1, 'Lemongrass Tea Powder', '33.00', 2, '66.00'),
(3, 1, 'Matcha Powder', '195.00', 1, '195.00'),
(10, 5, 'Salted Caramel  Syrup', '52.00', 4, '208.00'),
(12, 7, 'Vanilla 1 liter', '52.00', 8, '416.00'),
(15, 7, 'Salted Caramel  Syrup', '52.00', 4, '208.00'),
(16, 7, 'Roasted Hazelnut 1 liter', '52.00', 2, '104.00'),
(17, 7, 'Frosty Mint', '44.00', 2, '88.00'),
(18, 7, 'Ginger Syrup', '44.00', 0, '0.00'),
(19, 7, 'Macadamia Syrup', '44.00', 1, '44.00'),
(20, 7, 'Lemonade Syrup', '30.00', 2, '60.00'),
(21, 7, 'Pandan', '44.00', 1, '44.00'),
(22, 7, 'Strawberry Syrup', '50.00', 3, '150.00'),
(23, 7, 'Mango Syrup', '44.00', 0, '0.00'),
(24, 7, 'Melon', '44.00', 1, '44.00'),
(25, 7, 'Rose Syrup', '44.00', 1, '44.00'),
(26, 7, 'Pomegranate', '44.00', 0, '0.00'),
(27, 7, 'BLUE LAGOON', '44.00', 0, '0.00'),
(28, 6, 'Matcha Powder', '195.00', 1, '195.00'),
(29, 6, 'Peach Tea Powder', '33.00', 2, '66.00'),
(30, 6, 'Lemongrass Tea Powder', '33.00', 2, '66.00'),
(31, 6, 'Davinci Frappe Powder', '60.00', 2, '120.00'),
(32, 6, 'Chocolate Powder 1 kg', '42.90', 2, '85.80'),
(33, 6, 'Chocolate Coin 1 kg', '22.00', 1, '22.00'),
(34, 6, 'Coin 1.5kg', '29.90', 0, '0.00'),
(35, 6, 'Avocada Milkshake', '24.60', 1, '24.60'),
(36, 6, 'Strawberry milkshake', '19.40', 1, '19.40'),
(37, 6, 'Mango Milkshake', '19.40', 2, '38.80'),
(38, 6, 'Honey Dew Milkshake', '19.40', 2, '38.80'),
(39, 6, 'Avocado Latte Powder', '75.00', 0, '0.00'),
(40, 6, 'pistachio', '35.00', 9, '315.00'),
(41, 6, 'milktea', '25.00', 3, '75.00'),
(42, 6, 'Boh Tea Tea leaf 500gm', '11.50', 0, '0.00'),
(43, 16, 'Beans BK', '126.00', 35, '4410.00'),
(44, 16, 'Beans Embun', '75.00', 14, '1050.00'),
(45, 16, 'Vietnam', '20.00', 4, '80.00'),
(46, 17, 'Azyta Pastry', '388.00', 1, '388.00'),
(47, 18, 'Gula Merah', '4.50', 3, '13.50'),
(48, 18, 'Gula Putih', '3.00', 0, '0.00'),
(49, 19, 'Cold Cup', '15.00', 2800, '42000.00'),
(50, 19, 'Hot Cup 50\'s', '18.00', 200, '3600.00'),
(51, 19, 'Dome lid', '12.00', 0, '0.00'),
(52, 19, 'Straw small', '2.85', 14, '39.90'),
(53, 19, 'Straw Big', '3.00', 3, '9.00'),
(54, 19, 'Straw Panas', '2.80', 0, '0.00'),
(55, 19, 'Garfu black rm', '25.00', 8, '200.00'),
(56, 19, 'Spoon Black', '13.30', 0, '0.00'),
(57, 19, 'Sticker', '0.07', 40, '2.60'),
(58, 19, 'Plastic Beg (RM)', '5.20', 0, '0.00'),
(59, 19, 'Cup Tray 4 cup', '4.50', 0, '0.00'),
(60, 19, 'Cup Tray 2 cup', '19.50', 0, '0.00'),
(61, 19, 'Brown Beg 6', '0.00', 1, '0.00'),
(62, 19, 'Brown Beg 8', '0.00', 2, '0.00'),
(63, 19, 'Lunch Box', '0.00', 1, '0.00'),
(64, 18, 'gula paket', '0.00', 0, '0.00'),
(65, 6, 'Powder 500g', '0.00', 0, '0.00'),
(66, 7, 'coconut', '0.00', 0, '0.00'),
(67, 13, 'Strawberry Fruitmix', '60.00', 5, '300.00'),
(68, 13, 'Peach Fruitmix', '55.00', 2, '110.00'),
(69, 13, 'Mango Fruitmix', '55.00', 0, '0.00'),
(70, 14, 'Dark Chocolate SAUCE', '88.00', 7, '616.00'),
(71, 14, 'Caramel  SAUCE', '92.00', 7, '644.00'),
(72, 15, 'Susu Cair Ideal', '5.40', 24, '129.60'),
(73, 15, 'Susu Pekat Cap Junjung', '4.80', 33, '158.40'),
(74, 15, 'Susu Kotak', '6.50', 300, '1950.00'),
(75, 15, 'Oatside', '11.20', 27, '302.40'),
(76, 15, 'Debic WC', '32.90', 4, '131.60'),
(77, 15, 'Whipped Cream LL', '42.90', 0, '0.00'),
(80, 21, 'Matcha', '195.00', 27, '5265.00'),
(81, 22, 'Matcha', '195.00', 23, '4485.00'),
(82, 23, 'Matcha', '195.00', 18, '3510.00'),
(84, 5, 'Vanilla 1 liter', '52.00', 8, '416.00'),
(85, 5, 'Roasted Hazelnut 1 liter', '52.00', 2, '104.00'),
(86, 5, 'Frosty Mint', '44.00', 2, '88.00'),
(87, 5, 'Ginger Syrup', '44.00', 0, '0.00'),
(88, 5, 'Macadamia Syrup', '44.00', 1, '44.00'),
(89, 5, 'Lemonade Syrup', '30.00', 2, '60.00'),
(90, 5, 'Pandan', '44.00', 1, '44.00'),
(91, 5, 'Strawberry Syrup', '50.00', 3, '150.00'),
(92, 5, 'Mango Syrup', '44.00', 0, '0.00'),
(93, 5, 'Melon', '44.00', 1, '44.00'),
(94, 5, 'Rose Syrup', '44.00', 1, '44.00'),
(95, 5, 'Pomegranate', '44.00', 0, '0.00'),
(96, 5, 'BLUE LAGOON', '44.00', 0, '0.00'),
(97, 5, 'coconut', '0.00', 0, '0.00'),
(98, 1, 'Davinci Frappe Powder', '60.00', 2, '120.00'),
(99, 1, 'Chocolate Powder 1 kg', '42.90', 2, '85.80'),
(100, 1, 'Chocolate Coin 1 kg', '22.00', 3, '66.00'),
(101, 1, 'Coin 1.5kg', '29.90', 0, '0.00'),
(102, 1, 'Powder 500g', '0.00', 0, '0.00'),
(103, 1, 'Avocada Milkshake', '24.60', 1, '24.60'),
(104, 1, 'Strawberry milkshake', '19.40', 1, '19.40'),
(105, 1, 'Mango Milkshake', '19.40', 2, '38.80'),
(106, 1, 'Honey Dew Milkshake', '19.40', 2, '38.80'),
(107, 1, 'Avocado Latte Powder', '75.00', 0, '0.00'),
(108, 1, 'pistachio', '35.00', 9, '315.00'),
(109, 1, 'milktea', '25.00', 4, '100.00'),
(110, 1, 'Boh Tea Tea leaf 500gm', '11.50', 0, '0.00'),
(111, 25, 'Strawberry Fruitmix', '60.00', 5, '300.00'),
(112, 25, 'Peach Fruitmix', '55.00', 2, '110.00'),
(113, 25, 'Mango Fruitmix', '55.00', 0, '0.00'),
(114, 26, 'Strawberry Fruitmix', '60.00', 2, '120.00'),
(115, 26, 'Peach Fruitmix', '55.00', 12, '660.00'),
(116, 26, 'Mango Fruitmix', '55.00', 67, '3685.00'),
(140, 28, 'Avocada Milkshake', '24.60', 2, '49.20'),
(147, 28, 'Boh Tea Tea leaf 500gm', '11.50', 27, '310.50'),
(160, 41, 'brown sugar', '5.00', 4, '20.00'),
(163, 44, 'Caramel Sauce', '92.52', 25, '2313.00'),
(164, 45, 'brown sugar', '5.00', 2, '10.00');

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

--
-- Dumping data for table `stock_movements`
--

INSERT INTO `stock_movements` (`movement_id`, `item_id`, `staff_id`, `movement_date`, `movement_type`, `movement_quantity`) VALUES
(2, 2, NULL, '2025-01-01', 'OUT', 2),
(3, 1, NULL, '2025-01-31', 'OUT', 1),
(5, 2, NULL, '2025-01-01', 'IN', 4),
(8, 2, 1, '2025-03-01', 'OUT', 2),
(9, 3, 1, '2025-03-12', 'IN', 1),
(10, 2, 1, '2025-03-31', 'OUT', 2),
(11, 1, 1, '2025-03-31', 'OUT', 1),
(12, 2, 1, '2025-04-01', 'OUT', 1),
(13, 2, 1, '2025-04-30', 'OUT', 2),
(24, 3, NULL, '2025-09-30', 'OUT', 1),
(34, 10, NULL, '2025-10-12', 'IN', 5),
(39, 28, NULL, '2025-10-22', 'IN', 2),
(40, 28, NULL, '2025-10-25', 'OUT', 3),
(43, 80, NULL, '2025-11-01', 'IN', 4),
(44, 82, 8, '2025-11-01', 'OUT', 5),
(49, 1, NULL, '2025-05-31', 'OUT', 1),
(50, 100, NULL, '2025-06-01', 'IN', 2),
(55, 109, NULL, '2025-09-01', 'IN', 1),
(63, 147, NULL, '2025-11-03', 'IN', 25),
(64, 116, NULL, '2025-11-03', 'IN', 12),
(66, 116, NULL, '2025-11-03', 'IN', 2),
(69, 116, NULL, '2025-11-03', 'IN', 3),
(70, 115, 5, '2025-11-03', 'OUT', 15),
(71, 115, NULL, '2025-11-03', 'IN', 23),
(73, 116, 5, '2025-11-03', 'IN', 23),
(74, 160, NULL, '2025-11-03', 'OUT', 1),
(76, 116, NULL, '2025-11-03', 'IN', 12),
(77, 116, NULL, '2025-11-03', 'IN', 13),
(78, 164, NULL, '2025-11-03', 'OUT', 2);

-- --------------------------------------------------------

--
-- Table structure for table `utilities_expenses`
--

CREATE TABLE `utilities_expenses` (
  `utilities_id` int(11) NOT NULL,
  `record_id` int(11) NOT NULL,
  `expense_name` varchar(255) NOT NULL,
  `amount` decimal(12,2) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `utilities_expenses`
--

INSERT INTO `utilities_expenses` (`utilities_id`, `record_id`, `expense_name`, `amount`) VALUES
(3, 2, 'electirc', '3.10'),
(4, 2, 'air', '4.10'),
(5, 3, 'ss', '21.00'),
(6, 3, 'ssa', '11.00'),
(8, 5, 'tt', '12.00'),
(9, 5, 'ggh', '12.00'),
(10, 6, 's', '23.00'),
(14, 10, 'cv', '4.00'),
(15, 11, 'fg', '32.00'),
(16, 12, 'sdf', '213.00'),
(17, 13, 'dd', '23.00'),
(18, 13, 'dd', '12.00'),
(20, 18, 'cc', '22.00'),
(21, 18, 'xx', '11.00'),
(28, 23, 'Air', '78.00'),
(37, 25, 'electric', '0.50'),
(38, 25, 'water', '1.20'),
(40, 30, 'water', '123.00'),
(45, 33, 'water', '2.00'),
(46, 33, 'electricity', '5.00'),
(47, 34, 'water', '5.00'),
(48, 34, 'electric', '5.00'),
(49, 35, 'water', '5.00'),
(50, 35, 'electric', '5.00'),
(51, 36, 'water', '5.00'),
(52, 36, 'electric', '5.00'),
(53, 37, 'water', '5.00'),
(54, 37, 'electric', '5.00'),
(55, 38, 'water', '6.00'),
(56, 38, 'electric', '6.00'),
(59, 39, 'water', '5.00'),
(60, 39, 'electric', '5.00'),
(61, 40, 'water', '5.00'),
(62, 40, 'electric', '5.00'),
(63, 41, 'water', '5.00'),
(64, 41, 'electric', '5.00'),
(67, 42, 'water', '5.00'),
(68, 42, 'electric', '5.00'),
(71, 43, 'water', '5.00'),
(72, 43, 'electric', '5.00'),
(78, 46, 'Water/Electricity', '500.00'),
(79, 45, 'water', '50.00');

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
  MODIFY `advertisement_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=64;

--
-- AUTO_INCREMENT for table `business_owners`
--
ALTER TABLE `business_owners`
  MODIFY `owner_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT for table `closing_reports`
--
ALTER TABLE `closing_reports`
  MODIFY `report_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=55;

--
-- AUTO_INCREMENT for table `closing_report_cash_breakdown`
--
ALTER TABLE `closing_report_cash_breakdown`
  MODIFY `breakdown_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=188;

--
-- AUTO_INCREMENT for table `closing_report_payments`
--
ALTER TABLE `closing_report_payments`
  MODIFY `payment_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=70;

--
-- AUTO_INCREMENT for table `operating_expense_records`
--
ALTER TABLE `operating_expense_records`
  MODIFY `record_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=47;

--
-- AUTO_INCREMENT for table `others_expenses`
--
ALTER TABLE `others_expenses`
  MODIFY `others_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=55;

--
-- AUTO_INCREMENT for table `outlets`
--
ALTER TABLE `outlets`
  MODIFY `outlet_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=13;

--
-- AUTO_INCREMENT for table `rental_expenses`
--
ALTER TABLE `rental_expenses`
  MODIFY `rental_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=69;

--
-- AUTO_INCREMENT for table `salary_expenses`
--
ALTER TABLE `salary_expenses`
  MODIFY `salary_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=50;

--
-- AUTO_INCREMENT for table `staff`
--
ALTER TABLE `staff`
  MODIFY `staff_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;

--
-- AUTO_INCREMENT for table `stock_category`
--
ALTER TABLE `stock_category`
  MODIFY `category_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=47;

--
-- AUTO_INCREMENT for table `stock_items`
--
ALTER TABLE `stock_items`
  MODIFY `item_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=167;

--
-- AUTO_INCREMENT for table `stock_movements`
--
ALTER TABLE `stock_movements`
  MODIFY `movement_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=81;

--
-- AUTO_INCREMENT for table `utilities_expenses`
--
ALTER TABLE `utilities_expenses`
  MODIFY `utilities_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=80;

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
