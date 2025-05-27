-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1:3307
-- Generation Time: May 27, 2025 at 09:28 AM
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
(1, 1, 1, '2025-06-01', '09:00:00', 'Heart checkup', 'Scheduled', '2025-05-20 08:00:00', NULL),
(2, 2, 2, '2025-06-02', '10:00:00', 'Neurological exam', 'Scheduled', '2025-05-21 09:00:00', NULL),
(3, 3, 3, '2025-06-03', '11:00:00', 'Child fever', 'Completed', '2025-05-22 10:00:00', '2025-06-03 12:00:00'),
(4, 4, 4, '2025-06-04', '14:00:00', 'Skin rash', 'Scheduled', '2025-05-23 11:00:00', NULL),
(5, 5, 5, '2025-06-05', '13:00:00', 'Cancer consultation', 'Cancelled', '2025-05-24 12:00:00', '2025-05-25 09:00:00'),
(6, 6, 6, '2025-06-06', '15:00:00', 'Pregnancy check', 'Scheduled', '2025-05-25 13:00:00', NULL),
(7, 7, 7, '2025-06-07', '16:00:00', 'Mental health', 'Scheduled', '2025-05-26 14:00:00', NULL),
(8, 1, 2, '2025-06-08', '09:30:00', 'Follow-up visit', 'Completed', '2025-05-27 15:00:00', '2025-06-08 10:00:00');

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
(1, 'John', 'Doe', 'Cardiologist', 'Cardiology', '123-456-7890', 'john@hospital.co', '2020-01-15 00:00:00', ''),
(2, 'Jane', 'Smith', 'Neurologist', 'Neurology', '123-456-7891', 'jane.smith@hospital.com', '2019-05-20 00:00:00', ''),
(3, 'Robert', 'Johnson', 'Pediatrician', 'Pediatrics', '123-456-7892', 'robert.j@hospital.com', '2018-08-10 00:00:00', ''),
(4, 'Linda', 'Williams', 'Dermatologist', 'Dermatology', '123-456-7893', 'linda.w@hospital.com', '2021-03-05 00:00:00', ''),
(5, 'Michael', 'Brown', 'Oncologist', 'Oncology', '123-456-7894', 'michael.b@hospital.com', '2017-11-25 00:00:00', ''),
(6, 'Patricia', 'Jones', 'Gynecologist', 'Gynecology', '123-456-7895', 'patricia.j@hospital.com', '2022-02-28 00:00:00', ''),
(7, 'David', 'Garcia', 'Psychiatrist', 'Psychiatry', '123-456-7896', 'david.g@hospital.com', '2016-09-12 00:00:00', '');

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
(2, 2, 'Migraine', 'Pain management and monitoring', 'Chronic'),
(3, 3, 'Upper respiratory infection', 'Antibiotics and rest', 'Acute'),
(4, 4, 'Eczema', 'Topical steroids', 'Chronic'),
(5, 5, 'Breast cancer', 'Chemotherapy', 'Chronic'),
(6, 6, 'Pregnancy', 'Prenatal vitamins and checkups', 'Normal'),
(7, 7, 'Depression', 'Psychotherapy and medication', 'Chronic');

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
(1, 1, 'Lisinopril', '10 mg daily', '2025-05-01', '2025-11-01', 'Take with food', 1),
(2, 2, 'Ibuprofen', '200 mg every 6 hours', '2025-05-03', '2025-05-10', 'Take as needed for pain', 2),
(3, 3, 'Amoxicillin', '500 mg every 8 hours', '2025-05-05', '2025-05-15', 'Complete the course', 3),
(4, 4, 'Hydrocortisone cream', 'Apply twice daily', '2025-05-07', '2025-06-07', 'Apply to affected area', 4),
(5, 5, 'Tamoxifen', '20 mg daily', '2025-05-09', '2026-05-09', 'Take at the same time daily', 5),
(6, 6, 'Folic acid', '400 mcg daily', '2025-05-11', '2025-08-11', 'Take after meals', 6),
(7, 7, 'Sertraline', '50 mg daily', '2025-05-13', '2025-11-13', 'Take in the morning', 7);

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
(3, 'Michael', 'Wilson', '1978-11-20', 'Male', '789 Pine St', '123-456-7894', '2025-05-07 08:30:00'),
(4, 'Sarah', 'Miller', '1989-03-10', 'Female', '321 Maple Ave', '123-456-7895', '2025-05-07 09:00:00'),
(5, 'James', 'Moore', '1995-09-05', 'Male', '654 Elm St', '123-456-7896', '2025-05-08 10:15:00'),
(6, 'Jessica', 'Taylor', '1982-12-22', 'Female', '987 Cedar Rd', '123-456-7897', '2025-05-08 11:00:00'),
(7, 'William', 'Anderson', '1990-06-17', 'Male', '159 Spruce Ln', '123-456-7898', '2025-05-09 12:00:00');

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
(1, 1, 1, 'What is the dosage of Lisinopril?', '2025-06-01 10:00:00', '10 mg daily after meals', '2025-06-01 15:00:00', 1),
(2, 2, 2, 'Can I take Ibuprofen on an empty stomach?', '2025-06-02 11:00:00', 'It is better to take with food.', '2025-06-02 16:00:00', 1),
(3, 3, 3, 'When should I stop taking antibiotics?', '2025-06-03 12:00:00', NULL, NULL, 0),
(4, 4, 4, 'Is the cream safe during pregnancy?', '2025-06-04 13:00:00', 'Consult your obstetrician.', '2025-06-05 09:00:00', 1),
(5, 5, 5, 'What side effects does Tamoxifen have?', '2025-06-05 14:00:00', NULL, NULL, 0),
(6, 6, 6, 'How often should I take folic acid?', '2025-06-06 15:00:00', 'Daily, as prescribed.', '2025-06-06 17:00:00', 1),
(7, 7, 7, 'Can sertraline cause drowsiness?', '2025-06-07 16:00:00', 'Yes, especially in the first weeks.', '2025-06-08 10:00:00', 1);

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
(1, 'admin', 'hashedpassword123', 'admin', 'admin@hospital.com', NULL, '2025-01-01 08:00:00', '2025-06-01 09:00:00', 1, ''),
(2, 'doctor1', 'hashedpassword234', 'doctor', 'doctor1@hospital.com', 1, '2025-01-05 08:00:00', '2025-06-01 09:30:00', 1, ''),
(3, 'nurse1', 'hashedpassword345', 'nurse', 'nurse1@hospital.com', 2, '2025-01-10 08:00:00', '2025-06-01 10:00:00', 1, ''),
(4, 'doctor2', 'hashedpassword456', 'doctor', 'doctor2@hospital.com', 3, '2025-02-01 08:00:00', NULL, 1, ''),
(5, 'nurse2', 'hashedpassword567', 'nurse', 'nurse2@hospital.com', 4, '2025-02-15 08:00:00', NULL, 1, ''),
(6, 'doctor3', 'hashedpassword678', 'doctor', 'doctor3@hospital.com', 5, '2025-03-01 08:00:00', NULL, 1, ''),
(7, 'nurse3', 'hashedpassword789', 'nurse', 'nurse3@hospital.com', 6, '2025-03-15 08:00:00', NULL, 1, '');

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
  MODIFY `AppointmentID` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

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
  MODIFY `MedicationID` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT for table `patient`
--
ALTER TABLE `patient`
  MODIFY `PatientID` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT for table `question`
--
ALTER TABLE `question`
  MODIFY `QuestionID` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT for table `user`
--
ALTER TABLE `user`
  MODIFY `UserID` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

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
