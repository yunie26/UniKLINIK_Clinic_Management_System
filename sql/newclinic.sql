-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Jun 06, 2026 at 10:28 AM
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
-- Database: `newclinic`
--

-- --------------------------------------------------------

--
-- Table structure for table `admin_credentials`
--

CREATE TABLE `admin_credentials` (
  `USERNAME` varchar(50) NOT NULL,
  `PASSWORD` varchar(50) NOT NULL,
  `IS_LOGGED_IN` tinyint(1) DEFAULT 0,
  `EMAIL` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf16 COLLATE=utf16_bin;

--
-- Dumping data for table `admin_credentials`
--

INSERT INTO `admin_credentials` (`USERNAME`, `PASSWORD`, `IS_LOGGED_IN`, `EMAIL`) VALUES
('admin', 'admin1234', 0, NULL);

-- --------------------------------------------------------

--
-- Table structure for table `customers`
--

CREATE TABLE `customers` (
  `ID` int(11) NOT NULL,
  `NAME` varchar(20) NOT NULL,
  `CONTACT_NUMBER` varchar(10) NOT NULL,
  `ADDRESS` varchar(100) NOT NULL,
  `DOCTOR_NAME` varchar(20) NOT NULL,
  `DOCTOR_ADDRESS` varchar(100) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf16 COLLATE=utf16_bin;

--
-- Dumping data for table `customers`
--

INSERT INTO `customers` (`ID`, `NAME`, `CONTACT_NUMBER`, `ADDRESS`, `DOCTOR_NAME`, `DOCTOR_ADDRESS`) VALUES
(3, 'Halimah', '0164551792', 'Kuala Lumpur', 'Hafiz', 'Kuala Lumpur'),
(4, 'Mustafa', '0111111323', 'Kuala Lumpur', 'Hafiz', 'Kuala Lumpur'),
(5, 'Naufal', '0124668911', 'Kuala Lumpur', 'Hafiz', 'Kuala Lumpur'),
(6, 'Syikin', '0112383459', 'Kuala Lumpur', 'DR Hafiz', 'Kuala Lumpur'),
(7, 'Anisa', '0129323921', 'Kuala Lumpur', 'DR Hafiz', 'Kuala Lumpur');

-- --------------------------------------------------------

--
-- Table structure for table `invoices`
--

CREATE TABLE `invoices` (
  `INVOICE_ID` int(11) NOT NULL,
  `NET_TOTAL` double NOT NULL DEFAULT 0,
  `INVOICE_DATE` date NOT NULL,
  `CUSTOMER_ID` int(11) NOT NULL,
  `TOTAL_AMOUNT` double NOT NULL,
  `TOTAL_DISCOUNT` double NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf16 COLLATE=utf16_bin;

-- --------------------------------------------------------

--
-- Table structure for table `invoice_lines`
--

CREATE TABLE `invoice_lines` (
  `ID` int(11) NOT NULL,
  `INVOICE_ID` int(11) NOT NULL,
  `LINE_ORDER` int(11) NOT NULL DEFAULT 0,
  `MEDICINE_NAME` varchar(255) NOT NULL,
  `BATCH_ID` varchar(100) NOT NULL DEFAULT '',
  `EXPIRY_DATE` date DEFAULT NULL,
  `QUANTITY` int(11) NOT NULL,
  `MRP` decimal(10,2) NOT NULL,
  `LINE_TOTAL` decimal(10,2) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `medicines`
--

CREATE TABLE `medicines` (
  `ID` int(11) NOT NULL,
  `NAME` varchar(100) NOT NULL,
  `PACKING` varchar(20) NOT NULL,
  `GENERIC_NAME` varchar(100) NOT NULL,
  `SUPPLIER_NAME` varchar(100) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf16 COLLATE=utf16_bin;

--
-- Dumping data for table `medicines`
--

INSERT INTO `medicines` (`ID`, `NAME`, `PACKING`, `GENERIC_NAME`, `SUPPLIER_NAME`) VALUES
(5, 'Penadol', '10 TAB', 'Paracetemol', 'Pharmacy Ceria'),
(6, 'Hurix', '1 BOTTLE', 'Cough', 'Pharmacy Anita');

-- --------------------------------------------------------

--
-- Table structure for table `medicines_stock`
--

CREATE TABLE `medicines_stock` (
  `ID` int(11) NOT NULL,
  `NAME` varchar(100) NOT NULL,
  `BATCH_ID` varchar(20) NOT NULL,
  `EXPIRY_DATE` varchar(10) NOT NULL,
  `QUANTITY` int(11) NOT NULL,
  `MRP` double NOT NULL,
  `RATE` double NOT NULL,
  `INVOICE_NUMBER` int(11) DEFAULT NULL COMMENT 'Supplier purchase invoice # (links to purchases.INVOICE_NUMBER)'
) ENGINE=InnoDB DEFAULT CHARSET=utf16 COLLATE=utf16_bin;

--
-- Dumping data for table `medicines_stock`
--

INSERT INTO `medicines_stock` (`ID`, `NAME`, `BATCH_ID`, `EXPIRY_DATE`, `QUANTITY`, `MRP`, `RATE`, `INVOICE_NUMBER`) VALUES
(5, 'Penadol', 'BATCH7176', '10/28', 122, 12.5, 10, 10),
(6, 'Hurix', 'BATCH4399', '10/29', 101, 16, 16, 1111);

-- --------------------------------------------------------

--
-- Table structure for table `notification_events`
--

CREATE TABLE `notification_events` (
  `ID` int(11) NOT NULL,
  `EVENT_KEY` varchar(190) NOT NULL,
  `ROLE_SCOPE` varchar(20) NOT NULL DEFAULT 'both',
  `TITLE` varchar(150) NOT NULL,
  `MESSAGE` varchar(255) NOT NULL,
  `URL` varchar(255) DEFAULT NULL,
  `EVENT_TS` datetime NOT NULL DEFAULT current_timestamp(),
  `LAST_SEEN_AT` datetime DEFAULT NULL,
  `SEEN_COUNT` int(11) NOT NULL DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `notification_events`
--

INSERT INTO `notification_events` (`ID`, `EVENT_KEY`, `ROLE_SCOPE`, `TITLE`, `MESSAGE`, `URL`, `EVENT_TS`, `LAST_SEEN_AT`, `SEEN_COUNT`) VALUES
(19, 'prescription_new_2', 'staff', 'New prescription', '#2 for Halimah — open counter queue.', 'counter_prescriptions.php', '2026-04-29 13:07:22', '2026-04-29 13:07:22', 1),
(34, 'doctor_dispensed_1', 'staff', 'Prescription Update', '1 prescription(s) marked as dispensed.', 'doctor_prescription_history.php', '2026-04-29 13:08:51', '2026-04-30 11:04:30', 33),
(54, 'prescription_new_3', 'staff', 'New prescription', '#3 for Mustafa — open counter queue.', 'counter_prescriptions.php', '2026-04-30 11:03:47', '2026-04-30 11:03:47', 1),
(68, 'doctor_dispensed_2', 'staff', 'Prescription Update', '2 prescription(s) marked as dispensed.', 'doctor_prescription_history.php', '2026-04-30 11:04:56', '2026-04-30 13:42:48', 88),
(153, 'prescription_new_4', 'staff', 'New prescription', '#4 for Halimah — open counter queue.', 'counter_prescriptions.php', '2026-04-30 13:42:30', '2026-04-30 13:42:30', 1),
(157, 'doctor_dispensed_3', 'staff', 'Prescription Update', '3 prescription(s) marked as dispensed.', 'doctor_prescription_history.php', '2026-04-30 13:44:40', '2026-04-30 13:47:27', 12),
(163, 'prescription_new_5', 'staff', 'New prescription', '#5 for Naufal — open counter queue.', 'counter_prescriptions.php', '2026-04-30 13:47:00', '2026-04-30 13:47:00', 1),
(170, 'doctor_dispensed_4', 'staff', 'Prescription Update', '4 prescription(s) marked as dispensed.', 'doctor_prescription_history.php', '2026-04-30 13:54:22', '2026-04-30 13:55:44', 8),
(176, 'prescription_new_6', 'staff', 'New prescription', '#6 for Naufal — open counter queue.', 'counter_prescriptions.php', '2026-04-30 13:55:40', '2026-04-30 13:55:40', 1),
(179, 'doctor_dispensed_5', 'staff', 'Prescription Update', '5 prescription(s) marked as dispensed.', 'doctor_prescription_history.php', '2026-04-30 13:56:08', '2026-06-06 16:22:47', 595);

-- --------------------------------------------------------

--
-- Table structure for table `notification_reads`
--

CREATE TABLE `notification_reads` (
  `ID` int(11) NOT NULL,
  `VIEWER_ROLE` varchar(20) NOT NULL,
  `VIEWER_ID` varchar(100) NOT NULL,
  `EVENT_KEY` varchar(190) NOT NULL,
  `READ_AT` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `notification_reads`
--

INSERT INTO `notification_reads` (`ID`, `VIEWER_ROLE`, `VIEWER_ID`, `EVENT_KEY`, `READ_AT`) VALUES
(1, 'staff', '7', 'prescription_new_2', '2026-04-29 13:08:29'),
(2, 'staff', '7', 'low_stock_1', '2026-04-29 13:17:16'),
(3, 'admin', 'admin', 'low_stock_1', '2026-04-30 10:59:27'),
(4, 'staff', '6', 'doctor_dispensed_1', '2026-04-30 11:01:01'),
(5, 'staff', '6', 'low_stock_1', '2026-04-30 11:01:35'),
(6, 'staff', '7', 'doctor_dispensed_1', '2026-04-30 11:01:52'),
(7, 'staff', '7', 'prescription_new_3', '2026-04-30 11:04:42'),
(8, 'staff', '6', 'doctor_dispensed_2', '2026-04-30 13:06:15'),
(9, 'staff', '11', 'doctor_dispensed_2', '2026-04-30 13:25:13'),
(10, 'staff', '7', 'doctor_dispensed_2', '2026-04-30 13:25:57'),
(11, 'staff', '7', 'prescription_new_4', '2026-04-30 13:45:57'),
(12, 'staff', '6', 'doctor_dispensed_3', '2026-04-30 13:47:22'),
(13, 'staff', '7', 'prescription_new_5', '2026-04-30 13:47:46'),
(14, 'staff', '7', 'doctor_dispensed_3', '2026-04-30 13:54:14'),
(15, 'staff', '7', 'prescription_new_6', '2026-04-30 13:56:05'),
(16, 'staff', '7', 'doctor_dispensed_4', '2026-04-30 13:56:10'),
(17, 'staff', '6', 'doctor_dispensed_5', '2026-04-30 13:57:36'),
(18, 'staff', '6', 'doctor_dispensed_4', '2026-04-30 13:57:38'),
(19, 'staff', '7', 'doctor_dispensed_5', '2026-05-07 20:22:23');

-- --------------------------------------------------------

--
-- Table structure for table `ot_requests`
--

CREATE TABLE `ot_requests` (
  `id` int(11) NOT NULL,
  `staff_id` int(11) NOT NULL,
  `ot_date` date DEFAULT NULL,
  `hours` decimal(4,2) DEFAULT NULL,
  `reason` text DEFAULT NULL,
  `status` enum('Pending','Approved','Rejected') DEFAULT 'Pending',
  `is_paid` tinyint(1) DEFAULT 0,
  `amount_paid` decimal(10,2) DEFAULT NULL,
  `payment_date` date DEFAULT NULL,
  `proof_attachment` varchar(255) DEFAULT NULL,
  `admin_remark` text DEFAULT NULL,
  `ot_type` enum('Normal','Weekend','Public Holiday') NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `prescriptions`
--

CREATE TABLE `prescriptions` (
  `ID` int(11) NOT NULL,
  `DOCTOR_STAFF_ID` int(11) NOT NULL,
  `PATIENT_NAME` varchar(100) NOT NULL,
  `PATIENT_CONTACT` varchar(20) DEFAULT NULL,
  `DIAGNOSIS` varchar(255) DEFAULT NULL,
  `PRESCRIPTION_DATE` date DEFAULT NULL,
  `MEDICINE_LIST` text NOT NULL,
  `NOTES` text DEFAULT NULL,
  `STATUS` varchar(20) NOT NULL DEFAULT 'SENT',
  `CREATED_AT` datetime NOT NULL DEFAULT current_timestamp(),
  `UPDATED_AT` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `COUNTER_READ` tinyint(1) NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `prescriptions`
--

INSERT INTO `prescriptions` (`ID`, `DOCTOR_STAFF_ID`, `PATIENT_NAME`, `PATIENT_CONTACT`, `DIAGNOSIS`, `PRESCRIPTION_DATE`, `MEDICINE_LIST`, `NOTES`, `STATUS`, `CREATED_AT`, `UPDATED_AT`, `COUNTER_READ`) VALUES
(2, 6, 'Halimah', '0164551792', 'demam', '2026-04-29', 'Penadol (10 TAB) — Dosage: 10 — Qty: 2 — After Meal', '', 'DISPENSED', '2026-04-29 13:07:22', '2026-04-29 13:08:51', 1),
(3, 6, 'Mustafa', '0111111323', 'Batuk', '2026-04-30', 'Hurix (1 BOTTLE) — Dosage: 1 — Qty: 1 — After Meal', '', 'DISPENSED', '2026-04-30 11:03:47', '2026-04-30 11:04:56', 1),
(4, 6, 'Halimah', '0164551792', 'Batuk', '2026-04-30', 'Hurix (1 BOTTLE) — Dosage: 1 — Qty: 1 — aa', '', 'DISPENSED', '2026-04-30 13:42:30', '2026-04-30 13:44:40', 1),
(5, 6, 'Naufal', '0124668911', 'Demam', '2026-04-30', 'Penadol (10 TAB) — Dosage: 2 — Qty: 2 — After Meal', '', 'DISPENSED', '2026-04-30 13:47:00', '2026-04-30 13:54:22', 1),
(6, 6, 'Naufal', '0124668911', 'Batuk', '2026-04-30', 'Hurix (1 BOTTLE) — Dosage: 2 — Qty: 1 — aa', '', 'DISPENSED', '2026-04-30 13:55:40', '2026-04-30 13:56:08', 1);

-- --------------------------------------------------------

--
-- Table structure for table `print_logs`
--

CREATE TABLE `print_logs` (
  `ID` int(11) NOT NULL,
  `DOC_TYPE` varchar(20) NOT NULL,
  `DOC_ID` int(11) NOT NULL,
  `USER_ROLE` varchar(20) NOT NULL,
  `USER_NAME` varchar(100) NOT NULL,
  `PRINTED_AT` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `print_logs`
--

INSERT INTO `print_logs` (`ID`, `DOC_TYPE`, `DOC_ID`, `USER_ROLE`, `USER_NAME`, `PRINTED_AT`) VALUES
(39, 'ORDER', 6, 'admin', 'admin', '2026-04-29 13:04:07'),
(40, 'RECEIPT', 6, 'admin', 'admin', '2026-04-29 13:09:29'),
(41, 'ORDER', 6, 'admin', 'admin', '2026-04-30 12:52:15'),
(42, 'ORDER', 7, 'admin', 'admin', '2026-04-30 12:52:23'),
(43, 'RECEIPT', 6, 'admin', 'admin', '2026-04-30 12:52:47'),
(44, 'RECEIPT', 6, 'admin', 'admin', '2026-04-30 12:56:39'),
(45, 'ORDER', 6, 'admin', 'admin', '2026-04-30 12:57:06'),
(46, 'RECEIPT', 6, 'admin', 'admin', '2026-04-30 12:58:05'),
(47, 'RECEIPT', 6, 'admin', 'admin', '2026-04-30 12:59:41'),
(48, 'RECEIPT', 7, 'staff', 'Anis', '2026-04-30 13:48:27'),
(49, 'ORDER', 8, 'admin', 'admin', '2026-05-08 00:28:35'),
(50, 'ORDER', 9, 'staff', 'Anis', '2026-05-22 16:56:06'),
(51, 'RECEIPT', 6, 'admin', 'admin', '2026-06-06 15:47:21'),
(52, 'RECEIPT', 7, 'admin', 'admin', '2026-06-06 15:47:32'),
(53, 'ORDER', 6, 'admin', 'admin', '2026-06-06 15:47:41'),
(54, 'ORDER', 10, 'admin', 'admin', '2026-06-06 15:52:45');

-- --------------------------------------------------------

--
-- Table structure for table `purchases`
--

CREATE TABLE `purchases` (
  `SUPPLIER_NAME` varchar(100) NOT NULL,
  `INVOICE_NUMBER` int(11) NOT NULL,
  `VOUCHER_NUMBER` int(11) NOT NULL,
  `PURCHASE_DATE` varchar(10) NOT NULL,
  `TOTAL_AMOUNT` double NOT NULL,
  `PAYMENT_STATUS` varchar(20) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf16 COLLATE=utf16_bin;

--
-- Dumping data for table `purchases`
--

INSERT INTO `purchases` (`SUPPLIER_NAME`, `INVOICE_NUMBER`, `VOUCHER_NUMBER`, `PURCHASE_DATE`, `TOTAL_AMOUNT`, `PAYMENT_STATUS`) VALUES
('Pharmacy Ceria', 10, 10, '2026-06-06', 37.5, 'PAID');

-- --------------------------------------------------------

--
-- Table structure for table `purchase_order_lines`
--

CREATE TABLE `purchase_order_lines` (
  `ID` int(11) NOT NULL,
  `VOUCHER_NUMBER` int(11) NOT NULL,
  `LINE_ORDER` int(11) NOT NULL DEFAULT 0,
  `MEDICINE_NAME` varchar(100) NOT NULL,
  `BATCH_ID` varchar(20) NOT NULL,
  `EXPIRY_DATE` varchar(10) NOT NULL,
  `QUANTITY` int(11) NOT NULL,
  `MRP` double NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `purchase_order_lines`
--

INSERT INTO `purchase_order_lines` (`ID`, `VOUCHER_NUMBER`, `LINE_ORDER`, `MEDICINE_NAME`, `BATCH_ID`, `EXPIRY_DATE`, `QUANTITY`, `MRP`) VALUES
(3, 6, 0, 'Penadol', 'BATCH7176', '10/28', 10, 12.5),
(4, 7, 0, 'Hurix', 'BATCH4399', '10/29', 20, 16),
(5, 8, 0, 'Penadol', 'BATCH7176', '10/28', 20, 12.5),
(6, 9, 0, 'Hurix', 'BATCH4399', '10/29', 1, 16),
(7, 10, 0, 'Penadol', 'BATCH7176', '10/28', 3, 12.5);

-- --------------------------------------------------------

--
-- Table structure for table `sales`
--

CREATE TABLE `sales` (
  `ID` int(11) NOT NULL,
  `INVOICE_ID` int(11) NOT NULL,
  `MEDICINE_NAME` varchar(255) NOT NULL,
  `EXPIRY_DATE` date NOT NULL,
  `QUANTITY` int(11) NOT NULL,
  `MRP` decimal(10,2) NOT NULL,
  `DISCOUNT` decimal(5,2) DEFAULT NULL,
  `TOTAL` decimal(10,2) GENERATED ALWAYS AS (`QUANTITY` * `MRP`) STORED,
  `CUSTOMER_ID` int(11) NOT NULL DEFAULT 0,
  `INVOICE_NUMBER` int(11) NOT NULL DEFAULT 0,
  `BATCH_ID` varchar(100) NOT NULL DEFAULT ''
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `staff`
--

CREATE TABLE `staff` (
  `ID` int(11) NOT NULL,
  `NAME` varchar(100) NOT NULL,
  `EMAIL` varchar(100) NOT NULL,
  `CONTACT_NUMBER` varchar(20) NOT NULL,
  `PASSWORD` varchar(255) NOT NULL,
  `CONFIRM_PASSWORD` varchar(255) NOT NULL,
  `GENDER` varchar(20) DEFAULT NULL,
  `DOB` date DEFAULT NULL,
  `ADDRESS` varchar(255) DEFAULT NULL,
  `ROLE` varchar(50) NOT NULL DEFAULT 'Clinic Assistant',
  `STATUS` varchar(20) NOT NULL DEFAULT 'Active',
  `SECRET_QUESTION` varchar(255) DEFAULT NULL,
  `SECRET_ANSWER` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `staff`
--

INSERT INTO `staff` (`ID`, `NAME`, `EMAIL`, `CONTACT_NUMBER`, `PASSWORD`, `CONFIRM_PASSWORD`, `GENDER`, `DOB`, `ADDRESS`, `ROLE`, `STATUS`, `SECRET_QUESTION`, `SECRET_ANSWER`) VALUES
(6, 'Hafiz', 'hafizzz1610@gmail.com', '0172343449', 'hafiz', 'hafiz', 'Male', '1980-10-16', 'kuala lumpur', 'Doctor', 'Active', 'What was the name of your first pet?', 'Cat'),
(7, 'Ayunie', 'nurayunie2803@gmail.com', '0165590127', 'ayunie', 'ayunie', '', NULL, '', 'Clinic Assistant', 'Active', 'What city were you born in?', 'kelantan'),
(8, 'Suvindran Ravindran', 'suvindran94@gmail.com', '01136096401', 'password', 'password', NULL, NULL, NULL, 'Clinic Assistant', 'Active', 'What city were you born in?', 'ipoh');

-- --------------------------------------------------------

--
-- Table structure for table `staff_leave`
--

CREATE TABLE `staff_leave` (
  `ID` int(11) NOT NULL,
  `STAFF_ID` int(11) NOT NULL,
  `LEAVE_TYPE` varchar(50) NOT NULL,
  `START_DATE` date NOT NULL,
  `END_DATE` date NOT NULL,
  `REASON` text NOT NULL,
  `STATUS` varchar(20) DEFAULT 'Pending',
  `ATTACHMENT` varchar(255) DEFAULT NULL,
  `ADMIN_REMARK` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `suppliers`
--

CREATE TABLE `suppliers` (
  `ID` int(11) NOT NULL,
  `NAME` varchar(100) NOT NULL,
  `EMAIL` varchar(100) NOT NULL,
  `CONTACT_NUMBER` varchar(10) NOT NULL,
  `ADDRESS` varchar(100) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf16 COLLATE=utf16_bin;

--
-- Dumping data for table `suppliers`
--

INSERT INTO `suppliers` (`ID`, `NAME`, `EMAIL`, `CONTACT_NUMBER`, `ADDRESS`) VALUES
(3, 'Pharmacy Anita', 'anitapharmacy@gmail.com', '0341234567', 'Kuala Lumpur'),
(4, 'Pharmacy Ceria', 'ceriapharmacy@gmail.com', '0311234768', 'Kuala Lumpur');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `admin_credentials`
--
ALTER TABLE `admin_credentials`
  ADD PRIMARY KEY (`USERNAME`);

--
-- Indexes for table `customers`
--
ALTER TABLE `customers`
  ADD PRIMARY KEY (`ID`);

--
-- Indexes for table `invoices`
--
ALTER TABLE `invoices`
  ADD PRIMARY KEY (`INVOICE_ID`);

--
-- Indexes for table `invoice_lines`
--
ALTER TABLE `invoice_lines`
  ADD PRIMARY KEY (`ID`),
  ADD KEY `INVOICE_ID` (`INVOICE_ID`);

--
-- Indexes for table `medicines`
--
ALTER TABLE `medicines`
  ADD PRIMARY KEY (`ID`);

--
-- Indexes for table `medicines_stock`
--
ALTER TABLE `medicines_stock`
  ADD PRIMARY KEY (`ID`),
  ADD UNIQUE KEY `BATCH_ID` (`BATCH_ID`),
  ADD KEY `INVOICE_NUMBER` (`INVOICE_NUMBER`);

--
-- Indexes for table `notification_events`
--
ALTER TABLE `notification_events`
  ADD PRIMARY KEY (`ID`),
  ADD UNIQUE KEY `uq_event_role` (`EVENT_KEY`,`ROLE_SCOPE`),
  ADD KEY `idx_event_ts` (`EVENT_TS`);

--
-- Indexes for table `notification_reads`
--
ALTER TABLE `notification_reads`
  ADD PRIMARY KEY (`ID`),
  ADD UNIQUE KEY `uq_viewer_event` (`VIEWER_ROLE`,`VIEWER_ID`,`EVENT_KEY`),
  ADD KEY `idx_viewer` (`VIEWER_ROLE`,`VIEWER_ID`),
  ADD KEY `idx_event_key` (`EVENT_KEY`);

--
-- Indexes for table `ot_requests`
--
ALTER TABLE `ot_requests`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `prescriptions`
--
ALTER TABLE `prescriptions`
  ADD PRIMARY KEY (`ID`),
  ADD KEY `STATUS` (`STATUS`),
  ADD KEY `COUNTER_READ` (`COUNTER_READ`),
  ADD KEY `DOCTOR_STAFF_ID` (`DOCTOR_STAFF_ID`);

--
-- Indexes for table `print_logs`
--
ALTER TABLE `print_logs`
  ADD PRIMARY KEY (`ID`);

--
-- Indexes for table `purchases`
--
ALTER TABLE `purchases`
  ADD PRIMARY KEY (`VOUCHER_NUMBER`);

--
-- Indexes for table `purchase_order_lines`
--
ALTER TABLE `purchase_order_lines`
  ADD PRIMARY KEY (`ID`),
  ADD KEY `VOUCHER_NUMBER` (`VOUCHER_NUMBER`);

--
-- Indexes for table `sales`
--
ALTER TABLE `sales`
  ADD PRIMARY KEY (`ID`),
  ADD KEY `INVOICE_ID` (`INVOICE_ID`);

--
-- Indexes for table `staff`
--
ALTER TABLE `staff`
  ADD PRIMARY KEY (`ID`),
  ADD UNIQUE KEY `EMAIL` (`EMAIL`),
  ADD UNIQUE KEY `CONTACT_NUMBER` (`CONTACT_NUMBER`);

--
-- Indexes for table `staff_leave`
--
ALTER TABLE `staff_leave`
  ADD PRIMARY KEY (`ID`),
  ADD KEY `STAFF_ID` (`STAFF_ID`);

--
-- Indexes for table `suppliers`
--
ALTER TABLE `suppliers`
  ADD PRIMARY KEY (`ID`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `customers`
--
ALTER TABLE `customers`
  MODIFY `ID` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT for table `invoices`
--
ALTER TABLE `invoices`
  MODIFY `INVOICE_ID` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT for table `invoice_lines`
--
ALTER TABLE `invoice_lines`
  MODIFY `ID` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT for table `medicines`
--
ALTER TABLE `medicines`
  MODIFY `ID` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `medicines_stock`
--
ALTER TABLE `medicines_stock`
  MODIFY `ID` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `notification_events`
--
ALTER TABLE `notification_events`
  MODIFY `ID` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=774;

--
-- AUTO_INCREMENT for table `notification_reads`
--
ALTER TABLE `notification_reads`
  MODIFY `ID` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=20;

--
-- AUTO_INCREMENT for table `ot_requests`
--
ALTER TABLE `ot_requests`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `prescriptions`
--
ALTER TABLE `prescriptions`
  MODIFY `ID` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `print_logs`
--
ALTER TABLE `print_logs`
  MODIFY `ID` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=55;

--
-- AUTO_INCREMENT for table `purchases`
--
ALTER TABLE `purchases`
  MODIFY `VOUCHER_NUMBER` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- AUTO_INCREMENT for table `purchase_order_lines`
--
ALTER TABLE `purchase_order_lines`
  MODIFY `ID` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT for table `sales`
--
ALTER TABLE `sales`
  MODIFY `ID` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `staff`
--
ALTER TABLE `staff`
  MODIFY `ID` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT for table `staff_leave`
--
ALTER TABLE `staff_leave`
  MODIFY `ID` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `suppliers`
--
ALTER TABLE `suppliers`
  MODIFY `ID` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `staff_leave`
--
ALTER TABLE `staff_leave`
  ADD CONSTRAINT `staff_leave_ibfk_1` FOREIGN KEY (`STAFF_ID`) REFERENCES `staff` (`ID`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
