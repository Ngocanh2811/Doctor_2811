-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1:3307
-- Generation Time: May 27, 2025 at 06:01 AM
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
-- Database: `hospitaldb`
--

-- --------------------------------------------------------

--
-- Table structure for table `appointment`
--

CREATE TABLE `appointment` (
  `AppointmentID` int(11) NOT NULL,
  `PatientID` int(11) DEFAULT NULL,
  `DoctorID` int(11) DEFAULT NULL,
  `AppointmentDate` date DEFAULT NULL,
  `AppointmentTime` time DEFAULT NULL,
  `Reason` varchar(255) DEFAULT NULL,
  `Status` varchar(50) DEFAULT NULL,
  `CreateAt` datetime DEFAULT current_timestamp(),
  `ModifyAt` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `appointment`
--

INSERT INTO `appointment` (`AppointmentID`, `PatientID`, `DoctorID`, `AppointmentDate`, `AppointmentTime`, `Reason`, `Status`, `CreateAt`, `ModifyAt`) VALUES
(3, 1, 1, '2025-04-15', '10:00:00', 'Regular Checkup', 'cancelled', '2025-05-06 13:23:19', '2025-05-06 13:23:19'),
(4, 2, 2, '2025-04-16', '14:00:00', 'Neurological Exam', 'Scheduled', '2025-05-06 13:23:19', '2025-05-06 13:23:19');

-- --------------------------------------------------------

--
-- Table structure for table `audit_log`
--

CREATE TABLE `audit_log` (
  `LogID` int(11) NOT NULL,
  `UserID` int(11) DEFAULT NULL,
  `Timestamp` datetime DEFAULT current_timestamp(),
  `Action` varchar(100) DEFAULT NULL,
  `TableAffected` varchar(50) DEFAULT NULL,
  `RecordID` int(11) DEFAULT NULL,
  `IPAddress` varchar(45) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `audit_log`
--

INSERT INTO `audit_log` (`LogID`, `UserID`, `Timestamp`, `Action`, `TableAffected`, `RecordID`, `IPAddress`) VALUES
(1, 1, '2025-05-07 00:08:32', 'Logged in', 'USER', 1, '192.168.1.1'),
(2, 2, '2025-05-07 00:08:32', 'Updated appointment', 'APPOINTMENT', 1, '192.168.1.2'),
(3, 2, '2025-05-07 00:08:37', 'Login', 'USER', NULL, '::1'),
(4, 2, '2025-05-22 17:45:41', 'Login', 'USER', NULL, '::1'),
(5, 2, '2025-05-23 09:52:43', 'Login', 'USER', NULL, '::1'),
(6, 2, '2025-05-27 10:41:14', 'Login', 'USER', NULL, '::1');

-- --------------------------------------------------------

--
-- Table structure for table `doctor`
--

CREATE TABLE `doctor` (
  `DoctorID` int(11) NOT NULL,
  `FirstName` varchar(50) DEFAULT NULL,
  `LastName` varchar(50) DEFAULT NULL,
  `Specialization` varchar(100) DEFAULT NULL,
  `Department` varchar(100) DEFAULT NULL,
  `PhoneNumber` varchar(20) DEFAULT NULL,
  `Email` varchar(100) DEFAULT NULL,
  `HireDate` datetime DEFAULT NULL,
  `avatar` varchar(255) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `doctor`
--

INSERT INTO `doctor` (`DoctorID`, `FirstName`, `LastName`, `Specialization`, `Department`, `PhoneNumber`, `Email`, `HireDate`, `avatar`) VALUES
(1, 'John', 'Doe', 'Cardiologist', 'Cardiology', '123-456-7890', 'john@hospital.co', '2025-05-06 13:22:49', ''),
(2, 'Jane', 'Smith', 'Neurologist', 'Neurology', '123-456-7891', 'jane.smith@hospital.com', '2025-05-06 13:22:49', '');

-- --------------------------------------------------------

--
-- Table structure for table `medical_record`
--

CREATE TABLE `medical_record` (
  `RecordID` int(11) NOT NULL,
  `PatientID` int(11) DEFAULT NULL,
  `Diagnosis` varchar(255) DEFAULT NULL,
  `Treatment` text DEFAULT NULL,
  `Classification` varchar(50) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `medical_record`
--

INSERT INTO `medical_record` (`RecordID`, `PatientID`, `Diagnosis`, `Treatment`, `Classification`) VALUES
(1, 1, 'Hypertension', 'Lifestyle changes and medication', 'Chronic'),
(2, 2, 'Migraine', 'Pain management and monitoring', 'Chronic');

-- --------------------------------------------------------

--
-- Table structure for table `medication`
--

CREATE TABLE `medication` (
  `MedicationID` int(11) NOT NULL,
  `PatientID` int(11) DEFAULT NULL,
  `MedicationName` varchar(100) NOT NULL,
  `Dosage` varchar(100) DEFAULT NULL,
  `StartDate` date DEFAULT NULL,
  `EndDate` date DEFAULT NULL,
  `Instructions` text DEFAULT NULL,
  `PrescribedByID` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `medication`
--

INSERT INTO `medication` (`MedicationID`, `PatientID`, `MedicationName`, `Dosage`, `StartDate`, `EndDate`, `Instructions`, `PrescribedByID`) VALUES
(1, 1, 'Lisinopril', '10 mg daily', '2025-04-10', '2025-10-10', 'Take with foo', 1),
(2, 2, 'Ibuprofen', '200 mg every 6 hours', '2025-04-12', '2025-04-15', 'Take as needed for pain', 2);

-- --------------------------------------------------------

--
-- Table structure for table `patient`
--

CREATE TABLE `patient` (
  `PatientID` int(11) NOT NULL,
  `FirstName` varchar(50) DEFAULT NULL,
  `LastName` varchar(50) DEFAULT NULL,
  `DateOfBirth` date DEFAULT NULL,
  `Gender` enum('Male','Female','Other') DEFAULT NULL,
  `Address` varchar(255) DEFAULT NULL,
  `PhoneNumber` varchar(20) DEFAULT NULL,
  `RegistrationDate` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `patient`
--

INSERT INTO `patient` (`PatientID`, `FirstName`, `LastName`, `DateOfBirth`, `Gender`, `Address`, `PhoneNumber`, `RegistrationDate`) VALUES
(1, 'David', 'Brown', '1985-07-15', 'Male', '123 Main St', '123-456-7892', '2025-05-06 13:18:00'),
(2, 'Emily', 'Davis', '1992-05-30', 'Female', '456 Oak St', '123-456-7893', '2025-05-06 13:18:00'),
(3, 'Tuan', 'Tran', '1993-05-25', 'Female', NULL, '0953141363', NULL),
(4, 'Linh', 'Do', '1967-08-11', NULL, NULL, '0979671182', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `question`
--

CREATE TABLE `question` (
  `QuestionID` int(11) NOT NULL,
  `DoctorID` int(11) DEFAULT NULL,
  `PatientID` int(11) DEFAULT NULL,
  `Question` text NOT NULL,
  `QuestionDate` datetime DEFAULT current_timestamp(),
  `Answer` text DEFAULT NULL,
  `AnswerDate` datetime DEFAULT NULL,
  `IsAnswered` tinyint(1) DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `question`
--

INSERT INTO `question` (`QuestionID`, `DoctorID`, `PatientID`, `Question`, `QuestionDate`, `Answer`, `AnswerDate`, `IsAnswered`) VALUES
(1, 1, 1, 'Xin bác sĩ cho em biết cách dùng thuốc 3 kháng sinh ?', '2025-05-16 09:30:00', 'www', '2025-05-17 11:23:37', 1),
(2, 2, 2, 'Em có thể ăn gì sau khi mổ vùng ổ bụng?', '2025-05-15 14:15:00', NULL, NULL, 0);

-- --------------------------------------------------------

--
-- Table structure for table `user`
--

CREATE TABLE `user` (
  `UserID` int(11) NOT NULL,
  `Username` varchar(50) NOT NULL,
  `PasswordHash` varchar(255) NOT NULL,
  `Role` enum('admin','doctor','nurse') NOT NULL,
  `Email` varchar(100) DEFAULT NULL,
  `LinkedID` int(11) DEFAULT NULL,
  `CreatedAt` datetime DEFAULT current_timestamp(),
  `LastLogin` datetime DEFAULT NULL,
  `IsActive` tinyint(1) DEFAULT 1,
  `avatar` varchar(255) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `user`
--

INSERT INTO `user` (`UserID`, `Username`, `PasswordHash`, `Role`, `Email`, `LinkedID`, `CreatedAt`, `LastLogin`, `IsActive`, `avatar`) VALUES
(1, 'admin', 'hashedpassword123', 'admin', NULL, NULL, '2025-05-06 13:26:02', '2025-05-06 13:26:02', 1, ''),
(2, 'doctor1', '1234', 'doctor', 'nguyentna2811@gmail.com', 1, '2025-05-06 13:26:02', '2025-05-27 10:41:14', 1, ''),
(3, 'nurse1', 'hashedpassword345', 'nurse', NULL, 2, '2025-05-06 13:26:02', '2025-05-06 13:26:02', 1, '');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `appointment`
--
ALTER TABLE `appointment`
  ADD PRIMARY KEY (`AppointmentID`),
  ADD KEY `PatientID` (`PatientID`),
  ADD KEY `DoctorID` (`DoctorID`);

--
-- Indexes for table `audit_log`
--
ALTER TABLE `audit_log`
  ADD PRIMARY KEY (`LogID`),
  ADD KEY `UserID` (`UserID`);

--
-- Indexes for table `doctor`
--
ALTER TABLE `doctor`
  ADD PRIMARY KEY (`DoctorID`);

--
-- Indexes for table `medical_record`
--
ALTER TABLE `medical_record`
  ADD PRIMARY KEY (`RecordID`),
  ADD KEY `PatientID` (`PatientID`);

--
-- Indexes for table `medication`
--
ALTER TABLE `medication`
  ADD PRIMARY KEY (`MedicationID`),
  ADD KEY `PatientID` (`PatientID`),
  ADD KEY `PrescribedByID` (`PrescribedByID`);

--
-- Indexes for table `patient`
--
ALTER TABLE `patient`
  ADD PRIMARY KEY (`PatientID`);

--
-- Indexes for table `question`
--
ALTER TABLE `question`
  ADD PRIMARY KEY (`QuestionID`),
  ADD KEY `DoctorID` (`DoctorID`),
  ADD KEY `PatientID` (`PatientID`);

--
-- Indexes for table `user`
--
ALTER TABLE `user`
  ADD PRIMARY KEY (`UserID`),
  ADD UNIQUE KEY `Username` (`Username`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `appointment`
--
ALTER TABLE `appointment`
  MODIFY `AppointmentID` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `audit_log`
--
ALTER TABLE `audit_log`
  MODIFY `LogID` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `doctor`
--
ALTER TABLE `doctor`
  MODIFY `DoctorID` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `medical_record`
--
ALTER TABLE `medical_record`
  MODIFY `RecordID` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `medication`
--
ALTER TABLE `medication`
  MODIFY `MedicationID` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `patient`
--
ALTER TABLE `patient`
  MODIFY `PatientID` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `question`
--
ALTER TABLE `question`
  MODIFY `QuestionID` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `user`
--
ALTER TABLE `user`
  MODIFY `UserID` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `appointment`
--
ALTER TABLE `appointment`
  ADD CONSTRAINT `appointment_ibfk_1` FOREIGN KEY (`PatientID`) REFERENCES `patient` (`PatientID`) ON DELETE CASCADE,
  ADD CONSTRAINT `appointment_ibfk_2` FOREIGN KEY (`DoctorID`) REFERENCES `doctor` (`DoctorID`) ON DELETE SET NULL;

--
-- Constraints for table `audit_log`
--
ALTER TABLE `audit_log`
  ADD CONSTRAINT `audit_log_ibfk_1` FOREIGN KEY (`UserID`) REFERENCES `user` (`UserID`) ON DELETE SET NULL;

--
-- Constraints for table `medical_record`
--
ALTER TABLE `medical_record`
  ADD CONSTRAINT `medical_record_ibfk_1` FOREIGN KEY (`PatientID`) REFERENCES `patient` (`PatientID`) ON DELETE CASCADE;

--
-- Constraints for table `medication`
--
ALTER TABLE `medication`
  ADD CONSTRAINT `medication_ibfk_1` FOREIGN KEY (`PatientID`) REFERENCES `patient` (`PatientID`) ON DELETE CASCADE,
  ADD CONSTRAINT `medication_ibfk_2` FOREIGN KEY (`PrescribedByID`) REFERENCES `doctor` (`DoctorID`) ON DELETE SET NULL;

--
-- Constraints for table `question`
--
ALTER TABLE `question`
  ADD CONSTRAINT `question_ibfk_1` FOREIGN KEY (`DoctorID`) REFERENCES `doctor` (`DoctorID`) ON DELETE SET NULL,
  ADD CONSTRAINT `question_ibfk_2` FOREIGN KEY (`PatientID`) REFERENCES `patient` (`PatientID`) ON DELETE SET NULL;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
