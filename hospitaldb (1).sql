-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1:3307
-- Generation Time: May 29, 2025 at 06:32 PM
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

DELIMITER $$
--
-- Procedures
--
CREATE DEFINER=`root`@`localhost` PROCEDURE `AddAppointment` (IN `pPatientID` INT, IN `pDoctorID` INT, IN `pDate` DATE, IN `pTime` TIME, IN `pReason` VARCHAR(255))   BEGIN
    INSERT INTO appointment (PatientID, DoctorID, AppointmentDate, AppointmentTime, Reason, Status)
    VALUES (pPatientID, pDoctorID, pDate, pTime, pReason, 'Scheduled');
    
    INSERT INTO audit_log (UserID, Action, TableAffected, RecordID, IPAddress)
    VALUES (pPatientID, 'Add appointment', 'appointment', LAST_INSERT_ID(), '0.0.0.0');
END$$

CREATE DEFINER=`root`@`localhost` PROCEDURE `ShowAllDoctors` ()   BEGIN
    SELECT DoctorID, FirstName, LastName, Specialization FROM doctor;
END$$

DELIMITER ;

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
(8, 10, 1, '2025-05-29', '09:00:00', 'Check blood pressure', 'confirmed', '2025-05-29 23:09:57', NULL),
(9, 11, 1, '2025-06-12', '09:30:00', 'Chest pain', 'cancelled', '2025-05-29 23:09:57', NULL),
(10, 12, 1, '2025-06-14', '10:00:00', 'Annual check-up', 'pending', '2025-05-29 23:09:57', NULL),
(11, 13, 1, '2025-06-16', '10:30:00', 'Shortness of breath', 'Scheduled', '2025-05-29 23:09:57', NULL),
(12, 14, 1, '2025-06-05', '11:00:00', 'Dizziness', 'Scheduled', '2025-05-29 23:09:57', NULL),
(13, 15, 1, '2025-06-06', '11:30:00', 'High cholesterol', 'Scheduled', '2025-05-29 23:09:57', NULL),
(14, 16, 1, '2025-06-07', '14:00:00', 'Follow-up', 'Scheduled', '2025-05-29 23:09:57', NULL);

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
(1, 'John', 'Doe', 'Internal Medicine', 'General', '0909999999', 'doctor1@hospital.com', '2023-01-01 00:00:00', '');

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
(1, 10, 'Hypertension', 'Lisinopril 10mg daily', 'Chronic'),
(2, 11, 'Arrhythmia', 'Monitor ECG, beta blockers', 'Chronic'),
(3, 12, 'Normal check-up', 'No issues', 'Normal'),
(4, 13, 'COPD', 'Inhalers, pulmonary rehab', 'Chronic'),
(5, 14, 'Vertigo', 'Physical therapy, meds', 'Acute'),
(6, 15, 'High cholesterol', 'Statins, diet changes', 'Chronic'),
(7, 16, 'Post-stent review', 'Review medication and lifestyle', 'Follow-up');

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
(1, 10, 'Lisinopril', '10 mg daily', '2025-06-01', '2025-12-01', 'Take after breakfast', 1),
(2, 11, 'Metoprolol', '50 mg twice daily', '2025-06-02', '2025-12-02', 'Before meals', 1),
(3, 13, 'Salbutamol Inhaler', '2 puffs every 4 hours', '2025-06-04', '2025-09-04', 'Inhale deeply', 1),
(4, 14, 'Meclizine', '25 mg as needed', '2025-06-05', '2025-07-05', 'Avoid driving', 1),
(5, 15, 'Atorvastatin', '20 mg at night', '2025-06-06', '2025-12-06', 'Take with water', 1);

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
(10, 'Alice', 'Nguyen', '1990-01-10', 'Female', 'HCM', '0901000001', '2025-05-29 23:07:19'),
(11, 'Binh', 'Tran', '1988-04-22', 'Male', 'HCM', '0901000002', '2025-05-29 23:07:19'),
(12, 'Chi', 'Le', '1975-06-15', 'Female', 'HCM', '0901000003', '2025-05-29 23:07:19'),
(13, 'Dung', 'Pham', '1983-09-30', 'Male', 'HCM', '0901000004', '2025-05-29 23:07:19'),
(14, 'Emi', 'Vu', '1995-11-01', 'Female', 'HCM', '0901000005', '2025-05-29 23:07:19'),
(15, 'Phong', 'Ho', '1980-07-07', 'Male', 'HCM', '0901000006', '2025-05-29 23:07:19'),
(16, 'Nga', 'Dang', '1992-02-25', 'Female', 'HCM', '0901000007', '2025-05-29 23:07:19');

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
(11, 1, 12, 'How often should I get a blood test?', '2025-05-29 23:28:59', 'Every 6-12 months depending on your condition.', '2025-05-29 23:30:00', 1),
(12, 1, 13, 'Is dizziness a sign of something serious?', '2025-05-29 23:28:59', 'Sometimes, especially if persistent. Get checked.', '2025-05-29 23:35:00', 1),
(13, 1, 10, 'Should I avoid caffeine with high blood pressure?', '2025-05-29 23:28:59', 'Yes, moderate your intake.', '2025-05-29 23:38:00', 1),
(14, 1, 14, 'Can I stop taking cholesterol meds if I feel fine?', '2025-05-29 23:28:59', NULL, NULL, 0),
(15, 1, 11, 'What causes sudden chest tightness at night?', '2025-05-29 23:28:59', NULL, NULL, 0),
(16, 1, 12, 'Do I need to fast before a glucose test?', '2025-05-29 23:28:59', NULL, NULL, 0),
(17, 1, 13, 'Can stress affect my blood pressure readings?', '2025-05-29 23:28:59', NULL, NULL, 0);

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
  `IsActive` tinyint(1) DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `user`
--

INSERT INTO `user` (`UserID`, `Username`, `PasswordHash`, `Role`, `Email`, `LinkedID`, `CreatedAt`, `LastLogin`, `IsActive`) VALUES
(8, 'admin', 'hashedpassword123', 'admin', NULL, NULL, '2025-05-29 23:14:40', '2025-05-29 23:14:40', 1),
(9, 'doctor1', '123', 'doctor', NULL, 1, '2025-05-29 23:14:40', '2025-05-29 23:14:40', 1),
(10, 'nurse1', 'hashedpassword345', 'nurse', NULL, 2, '2025-05-29 23:14:40', '2025-05-29 23:14:40', 1);

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
  MODIFY `AppointmentID` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=15;

--
-- AUTO_INCREMENT for table `audit_log`
--
ALTER TABLE `audit_log`
  MODIFY `LogID` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `doctor`
--
ALTER TABLE `doctor`
  MODIFY `DoctorID` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT for table `medical_record`
--
ALTER TABLE `medical_record`
  MODIFY `RecordID` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT for table `medication`
--
ALTER TABLE `medication`
  MODIFY `MedicationID` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `patient`
--
ALTER TABLE `patient`
  MODIFY `PatientID` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=17;

--
-- AUTO_INCREMENT for table `question`
--
ALTER TABLE `question`
  MODIFY `QuestionID` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=18;

--
-- AUTO_INCREMENT for table `user`
--
ALTER TABLE `user`
  MODIFY `UserID` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

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
