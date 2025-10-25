-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Oct 20, 2025 at 11:54 AM
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
-- Database: `meditrack`
--

-- --------------------------------------------------------

--
-- Table structure for table `admin`
--

CREATE TABLE `admin` (
  `username` varchar(20) NOT NULL,
  `password` varchar(255) NOT NULL,
  `role` varchar(20) NOT NULL,
  `adminId` int(10) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `admin`
--

INSERT INTO `admin` (`username`, `password`, `role`, `adminId`) VALUES
('admin', 'admin', 'admin', 1);

-- --------------------------------------------------------

--
-- Table structure for table `billing`
--

CREATE TABLE `billing` (
  `Bill_id` int(20) NOT NULL,
  `Patient_id` int(20) NOT NULL,
  `Doctor_id` int(20) DEFAULT NULL,
  `Service_type` varchar(50) DEFAULT NULL,
  `Amount` decimal(10,2) NOT NULL,
  `Bill_date` date NOT NULL,
  `Due_date` date DEFAULT NULL,
  `Payment_status` varchar(20) NOT NULL DEFAULT 'Unpaid',
  `Service_description` text NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `billing`
--

INSERT INTO `billing` (`Bill_id`, `Patient_id`, `Doctor_id`, `Service_type`, `Amount`, `Bill_date`, `Due_date`, `Payment_status`, `Service_description`) VALUES
(1, 1, 1, 'Consultation', 150.00, '2025-10-20', '2025-11-19', 'Pending', 'General consultation visit'),
(2, 16, 4, 'Consultation', 600.00, '2025-10-20', '2025-10-22', 'Paid', '7 days Treatment'),
(3, 16, 4, 'Consultation', 600.00, '2025-10-20', '2025-10-22', 'Paid', '7 days Treatment');

-- --------------------------------------------------------

--
-- Table structure for table `booking`
--

CREATE TABLE `booking` (
  `Booking_id` int(20) NOT NULL,
  `Patient_id` int(20) NOT NULL,
  `Doctor_id` int(20) NOT NULL,
  `Name` varchar(20) NOT NULL,
  `Email` varchar(30) NOT NULL,
  `Age` int(20) NOT NULL,
  `Gender` varchar(20) NOT NULL,
  `Phone_no` int(20) NOT NULL,
  `Select_date` date NOT NULL,
  `Select_department` varchar(20) NOT NULL,
  `Additional_message` text NOT NULL,
  `Status` varchar(20) NOT NULL DEFAULT 'Pending'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `booking`
--

INSERT INTO `booking` (`Booking_id`, `Patient_id`, `Doctor_id`, `Name`, `Email`, `Age`, `Gender`, `Phone_no`, `Select_date`, `Select_department`, `Additional_message`, `Status`) VALUES
(1, 15, 1, 'sreelekshmi', 'sree@gmail.com', 29, 'Female', 2147483647, '2025-08-31', 'Cardiologist', 'Alergy', 'Pending'),
(2, 15, 3, 'sreelekshmi', 'sree@gmail.com', 29, 'Female', 2147483647, '2025-08-31', 'Psychiatrist', 'Qwertty', 'Pending'),
(3, 15, 3, 'sreelekshmi', 'sree@gmail.com', 29, 'Female', 2147483647, '2025-09-07', 'Psychiatrist', 'qwe', 'Approved'),
(4, 16, 4, 'Nikhil', 'nikhil@gmail.com', 29, 'Male', 2147483647, '2025-10-21', 'Dermatology', '10:30pm', 'Approved');

-- --------------------------------------------------------

--
-- Table structure for table `doctorreg`
--

CREATE TABLE `doctorreg` (
  `Doctor_id` int(20) NOT NULL,
  `Name` varchar(30) NOT NULL,
  `Email` varchar(30) NOT NULL,
  `Age` int(20) NOT NULL,
  `Gender` varchar(20) NOT NULL,
  `Phone_no` int(20) NOT NULL,
  `Department` varchar(30) NOT NULL,
  `Qualification` varchar(100) NOT NULL DEFAULT '',
  `Experience` varchar(50) NOT NULL DEFAULT '',
  `Profile_photo` varchar(255) NOT NULL DEFAULT '',
  `Bio` text NOT NULL DEFAULT '',
  `Password` varchar(255) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `doctorreg`
--

INSERT INTO `doctorreg` (`Doctor_id`, `Name`, `Email`, `Age`, `Gender`, `Phone_no`, `Department`, `Qualification`, `Experience`, `Profile_photo`, `Bio`, `Password`) VALUES
(1, 'sneha', 'sneha789@gmail.com', 22, 'Female', 369852014, 'Cardiologist', '', '', '', '', '$2y$10$wkR.ZcmepHKLfKWtdXNKOOluJFnTETbRvqUQ8O2vllU38IpFolSLS'),
(2, 'anu', 'anu1234@gmail.com', 23, 'Female', 234567891, 'Neurologist', '', '', '', '', '$2y$10$8UdbnMjy2.neDYZwJ4oRKOGftCz9VLLKiuBpQnjSan6HiLf8HRqHi'),
(3, 'Jeswin P J', 'jes10@gmail.com', 27, 'Male', 2147483647, 'Psychiatrist', '', '', '', '', '$2y$10$juc6tb.KbXj48k2vR3QI.O4547Q7oplbqupwQ4nOO1OR32VKHrwq2'),
(4, 'JESWIN CNK', 'jes0@gmail.com', 50, 'Male', 2147483647, 'Dermatologist', 'MBBS', '10', 'uploads/doctors/doctor_4_1756542635.png', '', '$2y$10$7TNPk6deYaYoYtXZgymV.O9nMkYNh3vdRsvFH1KFF7cXrhhrodfyG');

-- --------------------------------------------------------

--
-- Table structure for table `medical_records`
--

CREATE TABLE `medical_records` (
  `Record_id` int(20) NOT NULL,
  `Patient_id` int(20) NOT NULL,
  `Doctor_id` int(20) NOT NULL,
  `Record_date` date NOT NULL,
  `Diagnosis` text NOT NULL,
  `Treatment` text NOT NULL,
  `Notes` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `medical_records`
--

INSERT INTO `medical_records` (`Record_id`, `Patient_id`, `Doctor_id`, `Record_date`, `Diagnosis`, `Treatment`, `Notes`) VALUES
(1, 16, 4, '2025-10-20', 'Loss of Vitamin D', 'Taking the Omega 3 For 7 days', 'Visit after 7 days');

-- --------------------------------------------------------

--
-- Table structure for table `patient_documents`
--

CREATE TABLE `patient_documents` (
  `id` int(20) NOT NULL,
  `Patient_id` int(20) NOT NULL,
  `document_type` varchar(50) NOT NULL,
  `original_filename` varchar(255) NOT NULL,
  `file_path` varchar(500) NOT NULL,
  `file_size` int(20) NOT NULL,
  `upload_date` datetime NOT NULL DEFAULT current_timestamp(),
  `approval_status` enum('pending','approved','rejected') NOT NULL DEFAULT 'pending',
  `approval_comments` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `prescription`
--

CREATE TABLE `prescription` (
  `Prescription_id` int(20) NOT NULL,
  `Patient_id` int(20) NOT NULL,
  `Doctor_id` int(20) NOT NULL,
  `Date_issued` date NOT NULL,
  `Medicine_name` varchar(255) NOT NULL,
  `Dosage` varchar(30) NOT NULL,
  `Duration` varchar(30) NOT NULL,
  `Instructions` text NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `prescription`
--

INSERT INTO `prescription` (`Prescription_id`, `Patient_id`, `Doctor_id`, `Date_issued`, `Medicine_name`, `Dosage`, `Duration`, `Instructions`) VALUES
(1, 16, 4, '2025-10-20', 'Omega 3', 'Daily 1', '7 days', 'Visit After 7 days');

-- --------------------------------------------------------

--
-- Table structure for table `register`
--

CREATE TABLE `register` (
  `Patient_id` int(20) NOT NULL,
  `Name` varchar(20) NOT NULL,
  `Email` varchar(50) NOT NULL,
  `Age` int(20) NOT NULL,
  `Gender` varchar(20) NOT NULL,
  `Phone_no` int(20) NOT NULL,
  `Blood_group` varchar(5) NOT NULL,
  `Password` varchar(225) NOT NULL,
  `Photo` blob NOT NULL,
  `Documents` varchar(25) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `register`
--

INSERT INTO `register` (`Patient_id`, `Name`, `Email`, `Age`, `Gender`, `Phone_no`, `Blood_group`, `Password`, `Photo`, `Documents`) VALUES
(1, 'Jeswin', 'jes@gmail.com', 99, 'Male', 999999999, 'A+', '$2y$10$K7OZxCpkvuxYpBs0.0L90ewgGuwWdMmL/bN0EU0dgd3BxPbggS.ye', '', ''),
(2, 'sneha', 'sneha123@gmail.com', 20, 'Female', 23456789, 'A+', '$2y$10$d3nK4zJaCzr7AsWTGGZ57OzBhQC4MpJst0nPKBmVVI19oD4/W/jvS', '', ''),
(14, 'Manu', 'manu@gmail.com', 12, 'Male', 2147483647, 'A+', '$2y$10$SBeRMOSIeouSSj4rLGtwOuSLLjieHST1LbGQhaywbcNI7a3Hmh8q.', '', ''),
(15, 'sreelekshmi', 'sree@gmail.com', 29, 'Female', 2147483647, 'AB+', '$2y$10$Q1WWZ/wYi5yq3/ZE8OYmeeamswpmDE0WEe/jUOdiMdmHWKes5aQZG', '', ''),
(16, 'Nikhil', 'nikhil@gmail.com', 29, 'Male', 2147483647, 'A-', '$2y$10$Aj63ctRp46aWsSEQANmI9.gEr.pyvojFjK60YyXijpK/z06b/uSQu', '', '');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `admin`
--
ALTER TABLE `admin`
  ADD PRIMARY KEY (`adminId`);

--
-- Indexes for table `billing`
--
ALTER TABLE `billing`
  ADD PRIMARY KEY (`Bill_id`),
  ADD KEY `fk_billing_patient` (`Patient_id`),
  ADD KEY `fk_billing_doctor` (`Doctor_id`);

--
-- Indexes for table `booking`
--
ALTER TABLE `booking`
  ADD PRIMARY KEY (`Booking_id`),
  ADD KEY `fk_booking_patient` (`Patient_id`),
  ADD KEY `fk_booking_doctor` (`Doctor_id`);

--
-- Indexes for table `doctorreg`
--
ALTER TABLE `doctorreg`
  ADD PRIMARY KEY (`Doctor_id`);

--
-- Indexes for table `medical_records`
--
ALTER TABLE `medical_records`
  ADD PRIMARY KEY (`Record_id`),
  ADD KEY `fk_records_patient` (`Patient_id`),
  ADD KEY `fk_records_doctor` (`Doctor_id`);

--
-- Indexes for table `patient_documents`
--
ALTER TABLE `patient_documents`
  ADD PRIMARY KEY (`id`),
  ADD KEY `Patient_id` (`Patient_id`),
  ADD KEY `approval_status` (`approval_status`);

--
-- Indexes for table `prescription`
--
ALTER TABLE `prescription`
  ADD PRIMARY KEY (`Prescription_id`),
  ADD KEY `fk_prescription_patient` (`Patient_id`),
  ADD KEY `fk_prescription_doctor` (`Doctor_id`);

--
-- Indexes for table `register`
--
ALTER TABLE `register`
  ADD PRIMARY KEY (`Patient_id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `admin`
--
ALTER TABLE `admin`
  MODIFY `adminId` int(10) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `billing`
--
ALTER TABLE `billing`
  MODIFY `Bill_id` int(20) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `booking`
--
ALTER TABLE `booking`
  MODIFY `Booking_id` int(20) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `doctorreg`
--
ALTER TABLE `doctorreg`
  MODIFY `Doctor_id` int(20) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `medical_records`
--
ALTER TABLE `medical_records`
  MODIFY `Record_id` int(20) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `patient_documents`
--
ALTER TABLE `patient_documents`
  MODIFY `id` int(20) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `prescription`
--
ALTER TABLE `prescription`
  MODIFY `Prescription_id` int(20) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `register`
--
ALTER TABLE `register`
  MODIFY `Patient_id` int(20) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=17;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `billing`
--
ALTER TABLE `billing`
  ADD CONSTRAINT `fk_billing_doctor` FOREIGN KEY (`Doctor_id`) REFERENCES `doctorreg` (`Doctor_id`) ON DELETE SET NULL,
  ADD CONSTRAINT `fk_billing_patient` FOREIGN KEY (`Patient_id`) REFERENCES `register` (`Patient_id`);

--
-- Constraints for table `booking`
--
ALTER TABLE `booking`
  ADD CONSTRAINT `fk_booking_doctor` FOREIGN KEY (`Doctor_id`) REFERENCES `doctorreg` (`Doctor_id`),
  ADD CONSTRAINT `fk_booking_patient` FOREIGN KEY (`Patient_id`) REFERENCES `register` (`Patient_id`);

--
-- Constraints for table `medical_records`
--
ALTER TABLE `medical_records`
  ADD CONSTRAINT `fk_records_doctor` FOREIGN KEY (`Doctor_id`) REFERENCES `doctorreg` (`Doctor_id`),
  ADD CONSTRAINT `fk_records_patient` FOREIGN KEY (`Patient_id`) REFERENCES `register` (`Patient_id`);

--
-- Constraints for table `patient_documents`
--
ALTER TABLE `patient_documents`
  ADD CONSTRAINT `fk_patient_documents_patient` FOREIGN KEY (`Patient_id`) REFERENCES `register` (`Patient_id`) ON DELETE CASCADE;

--
-- Constraints for table `prescription`
--
ALTER TABLE `prescription`
  ADD CONSTRAINT `fk_prescription_doctor` FOREIGN KEY (`Doctor_id`) REFERENCES `doctorreg` (`Doctor_id`),
  ADD CONSTRAINT `fk_prescription_patient` FOREIGN KEY (`Patient_id`) REFERENCES `register` (`Patient_id`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
