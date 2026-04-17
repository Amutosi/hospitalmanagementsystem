-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Apr 17, 2026 at 09:41 PM
-- Server version: 10.4.32-MariaDB
-- PHP Version: 8.2.12

SET FOREIGN_KEY_CHECKS=0;
SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `hospitalmanagementsystem`
--

DELIMITER $$
--
-- Procedures
--
CREATE DEFINER=`root`@`localhost` PROCEDURE `sp_BookAppointment` (IN `p_PatientID` INT, IN `p_DoctorID` INT, IN `p_DateTime` DATETIME, IN `p_Duration` INT, IN `p_Purpose` TEXT)   BEGIN
    DECLARE v_Overlap INT;
    
    -- Check for overlapping appointments
    SELECT COUNT(*) INTO v_Overlap
    FROM Appointment
    WHERE DoctorID = p_DoctorID
    AND Status NOT IN ('Cancelled', 'NoShow')
    AND AppointmentDateTime < DATE_ADD(p_DateTime, INTERVAL p_Duration MINUTE)
    AND DATE_ADD(AppointmentDateTime, INTERVAL DurationMinutes MINUTE) > p_DateTime;
    
    IF v_Overlap = 0 THEN
        INSERT INTO Appointment (PatientID, DoctorID, AppointmentDateTime, DurationMinutes, Purpose, Status)
        VALUES (p_PatientID, p_DoctorID, p_DateTime, p_Duration, p_Purpose, 'Scheduled');
        
        SELECT 'Appointment booked successfully' AS Message, LAST_INSERT_ID() AS AppointmentID;
    ELSE
        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Doctor not available at this time';
    END IF;
END$$

CREATE DEFINER=`root`@`localhost` PROCEDURE `sp_GenerateBillForAppointment` (IN `p_AppointmentID` INT, IN `p_PatientID` INT)   BEGIN
    DECLARE v_ConsultationFee DECIMAL(10,2);
    DECLARE v_BillNumber VARCHAR(50);
    
    -- Get doctor's consultation fee
    SELECT d.ConsultationFee INTO v_ConsultationFee
    FROM Appointment a
    JOIN Doctor d ON a.DoctorID = d.DoctorID
    WHERE a.AppointmentID = p_AppointmentID;
    
    -- Generate unique bill number
    SET v_BillNumber = CONCAT('BILL', DATE_FORMAT(NOW(), '%Y%m%d'), LPAD(FLOOR(RAND() * 10000), 4, '0'));
    
    -- Create bill
    INSERT INTO Billing (PatientID, BillNumber, BillDate, DueDate, Subtotal, TaxAmount, DiscountAmount, TotalAmount, AmountPaid, PaymentStatus)
    VALUES (p_PatientID, v_BillNumber, CURDATE(), DATE_ADD(CURDATE(), INTERVAL 30 DAY), v_ConsultationFee, v_ConsultationFee * 0.10, 0, v_ConsultationFee * 1.10, 0, 'Pending');
    
    -- Add line item
    INSERT INTO BillLineItem (BillID, ServiceType, ServiceID, Description, Quantity, UnitPrice, LineTotal)
    VALUES (LAST_INSERT_ID(), 'Appointment', p_AppointmentID, 'Doctor Consultation', 1, v_ConsultationFee, v_ConsultationFee);
    
    SELECT 'Bill generated successfully' AS Message, v_BillNumber AS BillNumber;
END$$

DELIMITER ;

-- --------------------------------------------------------

--
-- Table structure for table `appointment`
--

CREATE TABLE `appointment` (
  `AppointmentID` int(11) NOT NULL,
  `PatientID` int(11) NOT NULL,
  `DoctorID` int(11) NOT NULL,
  `PatientTreatmentID` int(11) DEFAULT NULL,
  `AppointmentDateTime` datetime NOT NULL,
  `DurationMinutes` int(11) NOT NULL DEFAULT 30 CHECK (`DurationMinutes` between 15 and 240),
  `Status` enum('Scheduled','CheckedIn','InProgress','Completed','Cancelled','NoShow') DEFAULT 'Scheduled',
  `Purpose` text DEFAULT NULL,
  `Notes` text DEFAULT NULL,
  `CreatedAt` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `appointment`
--

INSERT INTO `appointment` (`AppointmentID`, `PatientID`, `DoctorID`, `PatientTreatmentID`, `AppointmentDateTime`, `DurationMinutes`, `Status`, `Purpose`, `Notes`, `CreatedAt`) VALUES
(3, 1, 3, NULL, '2026-04-17 18:25:00', 30, 'Completed', 'private', NULL, '2026-04-17 15:23:51');

--
-- Triggers `appointment`
--
DELIMITER $$
CREATE TRIGGER `trg_CheckAppointmentOverlap` BEFORE INSERT ON `appointment` FOR EACH ROW BEGIN
    DECLARE overlap_count INT;
    
    SELECT COUNT(*) INTO overlap_count
    FROM Appointment
    WHERE DoctorID = NEW.DoctorID
    AND Status NOT IN ('Cancelled', 'NoShow')
    AND AppointmentDateTime < DATE_ADD(NEW.AppointmentDateTime, INTERVAL NEW.DurationMinutes MINUTE)
    AND DATE_ADD(AppointmentDateTime, INTERVAL DurationMinutes MINUTE) > NEW.AppointmentDateTime;
    
    IF overlap_count > 0 THEN
        SIGNAL SQLSTATE '45000' 
        SET MESSAGE_TEXT = 'Doctor already has an appointment at this time';
    END IF;
END
$$
DELIMITER ;

-- --------------------------------------------------------

--
-- Table structure for table `billing`
--

CREATE TABLE `billing` (
  `BillID` int(11) NOT NULL,
  `PatientID` int(11) NOT NULL,
  `BillNumber` varchar(50) NOT NULL,
  `BillDate` date NOT NULL,
  `DueDate` date NOT NULL,
  `Subtotal` decimal(12,2) NOT NULL DEFAULT 0.00,
  `TaxAmount` decimal(12,2) NOT NULL DEFAULT 0.00,
  `DiscountAmount` decimal(12,2) NOT NULL DEFAULT 0.00,
  `TotalAmount` decimal(12,2) NOT NULL DEFAULT 0.00,
  `AmountPaid` decimal(12,2) NOT NULL DEFAULT 0.00,
  `PaymentStatus` enum('Pending','Partial','Paid','Overdue','Refunded') DEFAULT 'Pending',
  `PaymentMethod` varchar(50) DEFAULT NULL,
  `PaymentDate` date DEFAULT NULL,
  `Notes` text DEFAULT NULL,
  `CreatedAt` timestamp NOT NULL DEFAULT current_timestamp()
) ;

-- --------------------------------------------------------

--
-- Table structure for table `billlineitem`
--

CREATE TABLE `billlineitem` (
  `BillLineItemID` int(11) NOT NULL,
  `BillID` int(11) NOT NULL,
  `ServiceType` enum('Appointment','TreatmentStage','LabTest','Medication','RoomCharge') NOT NULL,
  `ServiceID` int(11) NOT NULL,
  `Description` varchar(255) DEFAULT NULL,
  `Quantity` int(11) NOT NULL CHECK (`Quantity` > 0),
  `UnitPrice` decimal(10,2) NOT NULL CHECK (`UnitPrice` >= 0),
  `LineTotal` decimal(12,2) NOT NULL,
  `CreatedAt` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Triggers `billlineitem`
--
DELIMITER $$
CREATE TRIGGER `trg_CalculateLineTotal` BEFORE INSERT ON `billlineitem` FOR EACH ROW BEGIN
    SET NEW.LineTotal = NEW.Quantity * NEW.UnitPrice;
END
$$
DELIMITER ;
DELIMITER $$
CREATE TRIGGER `trg_UpdateBillingTotal` AFTER INSERT ON `billlineitem` FOR EACH ROW BEGIN
    UPDATE Billing 
    SET Subtotal = (
        SELECT SUM(LineTotal) 
        FROM BillLineItem 
        WHERE BillID = NEW.BillID
    ),
    TotalAmount = Subtotal + TaxAmount - DiscountAmount
    WHERE BillID = NEW.BillID;
END
$$
DELIMITER ;

-- --------------------------------------------------------

--
-- Table structure for table `department`
--

CREATE TABLE `department` (
  `DepartmentID` int(11) NOT NULL,
  `DeptName` varchar(100) NOT NULL,
  `HeadDoctorID` int(11) DEFAULT NULL,
  `Description` text DEFAULT NULL,
  `CreatedAt` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `department`
--

INSERT INTO `department` (`DepartmentID`, `DeptName`, `HeadDoctorID`, `Description`, `CreatedAt`) VALUES
(1, 'Cardiology', 1, 'Heart and cardiovascular diseases', '2026-04-17 12:59:09'),
(2, 'Neurology', 2, 'Brain and nervous system disorders', '2026-04-17 12:59:09'),
(3, 'Orthopedics', 3, 'Bone and joint treatments', '2026-04-17 12:59:09'),
(4, 'Pediatrics', 4, 'Child healthcare', '2026-04-17 12:59:09'),
(5, 'Radiology', NULL, 'Medical imaging and diagnostics', '2026-04-17 12:59:09'),
(6, 'Oncology', NULL, 'Cancer treatment', '2026-04-17 12:59:09'),
(7, 'Emergency', NULL, 'Emergency medical services', '2026-04-17 12:59:09');

-- --------------------------------------------------------

--
-- Table structure for table `departmentcollaboration`
--

CREATE TABLE `departmentcollaboration` (
  `CollaborationID` int(11) NOT NULL,
  `PatientTreatmentID` int(11) NOT NULL,
  `LeadDepartmentID` int(11) NOT NULL,
  `SupportingDepartmentID` int(11) NOT NULL,
  `CollaborationStartDate` date NOT NULL,
  `CollaborationEndDate` date DEFAULT NULL,
  `RoleDescription` text DEFAULT NULL,
  `CreatedAt` timestamp NOT NULL DEFAULT current_timestamp()
) ;

-- --------------------------------------------------------

--
-- Table structure for table `doctor`
--

CREATE TABLE `doctor` (
  `DoctorID` int(11) NOT NULL,
  `FirstName` varchar(50) NOT NULL,
  `LastName` varchar(50) NOT NULL,
  `Specialization` varchar(100) NOT NULL,
  `ContactNo` varchar(20) NOT NULL,
  `Email` varchar(100) NOT NULL,
  `ProfileImage` varchar(255) DEFAULT NULL,
  `Username` varchar(50) DEFAULT NULL,
  `Password` varchar(255) DEFAULT NULL,
  `DepartmentID` int(11) DEFAULT NULL,
  `Qualification` varchar(200) DEFAULT NULL,
  `YearsOfExperience` int(11) DEFAULT 0,
  `ConsultationFee` decimal(10,2) DEFAULT 0.00,
  `IsActive` tinyint(1) DEFAULT 1,
  `CreatedAt` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `doctor`
--

INSERT INTO `doctor` (`DoctorID`, `FirstName`, `LastName`, `Specialization`, `ContactNo`, `Email`, `ProfileImage`, `Username`, `Password`, `DepartmentID`, `Qualification`, `YearsOfExperience`, `ConsultationFee`, `IsActive`, `CreatedAt`) VALUES
(1, 'Ruth', 'Acheng', 'Cardiology', '0700006872', 'ruth@hospital.com', 'assets/images/doctors/doctor1.jpg', 'ruth', 'ruth123', 1, 'MD, Cardiology Specialist', 0, 50000.00, 1, '2026-04-17 14:46:31'),
(2, 'Ella', 'Namwanje', 'General Medicine', '0756006898', 'ella@hospital.com', 'assets/images/default/default-doctor.png', 'ella', 'ella123', 4, 'Medical Degree', 0, 40000.00, 1, '2026-04-17 14:46:31'),
(3, 'Okello', 'Okecho', 'Cardiology', '0705984948', 'okecho@hospital.com', 'assets/images/default/default-doctor.png', 'okecho', 'okecho123', 1, 'Cardiology Specialist', 0, 50000.00, 1, '2026-04-17 14:46:31'),
(4, 'James', 'Mwangi', 'Pediatrics', '0712345678', 'james@hospital.com', 'assets/images/default/default-doctor.png', 'james', 'james123', 4, 'MBChB, Pediatrics', 0, 45000.00, 1, '2026-04-17 14:46:31'),
(5, 'Sarah', 'Nakato', 'Neurology', '0723456789', 'sarah@hospital.com', 'assets/images/default/default-doctor.png', 'sarah', 'sarah123', 2, 'MD, Neurology', 0, 55000.00, 1, '2026-04-17 14:46:31'),
(6, 'David', 'Okello', 'Orthopedics', '0734567890', 'david@hospital.com', 'assets/images/default/default-doctor.png', 'david', 'david123', 3, 'MBBS, Orthopedics', 0, 48000.00, 1, '2026-04-17 14:46:31'),
(7, 'Grace', 'Mukasa', 'Oncology', '0745678901', 'grace@hospital.com', 'assets/images/doctors/dr_smith.jpg', 'grace', 'grace123', 6, 'MD, Oncology', 0, 60000.00, 1, '2026-04-17 14:46:31');

-- --------------------------------------------------------

--
-- Table structure for table `doctorschedule`
--

CREATE TABLE `doctorschedule` (
  `ScheduleID` int(11) NOT NULL,
  `DoctorID` int(11) NOT NULL,
  `ScheduleDate` date NOT NULL,
  `StartTime` time NOT NULL,
  `EndTime` time NOT NULL,
  `Status` enum('Available','Booked','Completed') DEFAULT 'Available',
  `Notes` text DEFAULT NULL,
  `CreatedAt` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `labtest`
--

CREATE TABLE `labtest` (
  `LabTestID` int(11) NOT NULL,
  `TestName` varchar(100) NOT NULL,
  `StandardCost` decimal(10,2) NOT NULL DEFAULT 0.00,
  `NormalRange` varchar(100) DEFAULT NULL,
  `Description` text DEFAULT NULL,
  `PreparationInstructions` text DEFAULT NULL,
  `IsActive` tinyint(1) DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `labtest`
--

INSERT INTO `labtest` (`LabTestID`, `TestName`, `StandardCost`, `NormalRange`, `Description`, `PreparationInstructions`, `IsActive`) VALUES
(1, 'Complete Blood Count', 500.00, '4.5-11.0 K/uL', 'Basic blood cell analysis', NULL, 1),
(2, 'Blood Glucose', 300.00, '70-140 mg/dL', 'Blood sugar test', NULL, 1),
(3, 'Lipid Profile', 800.00, 'Cholesterol <200 mg/dL', 'Cholesterol and triglycerides', NULL, 1),
(4, 'Liver Function Test', 1200.00, 'ALT 10-40 U/L', 'Liver enzyme analysis', NULL, 1),
(5, 'Kidney Function Test', 1000.00, 'Creatinine 0.6-1.2 mg/dL', 'Kidney health assessment', NULL, 1),
(6, 'COVID-19 PCR', 4000.00, 'Negative', 'Coronavirus detection', NULL, 1);

-- --------------------------------------------------------

--
-- Table structure for table `nurse`
--

CREATE TABLE `nurse` (
  `NurseID` int(11) NOT NULL,
  `FirstName` varchar(50) NOT NULL,
  `LastName` varchar(50) NOT NULL,
  `Qualification` varchar(200) NOT NULL,
  `ContactNo` varchar(20) NOT NULL,
  `Email` varchar(100) NOT NULL,
  `Username` varchar(50) DEFAULT NULL,
  `Password` varchar(255) DEFAULT NULL,
  `DepartmentID` int(11) DEFAULT NULL,
  `ShiftPreference` varchar(20) DEFAULT NULL CHECK (`ShiftPreference` in ('Morning','Evening','Night','Rotating')),
  `IsActive` tinyint(1) DEFAULT 1,
  `CreatedAt` timestamp NOT NULL DEFAULT current_timestamp(),
  `ProfileImage` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `nurse`
--

INSERT INTO `nurse` (`NurseID`, `FirstName`, `LastName`, `Qualification`, `ContactNo`, `Email`, `Username`, `Password`, `DepartmentID`, `ShiftPreference`, `IsActive`, `CreatedAt`, `ProfileImage`) VALUES
(1, 'Parvin', 'Nakato', 'Pharmacy Degree', '0787810608', 'parvin@hospital.com', 'parvin', 'parvin123', 4, 'Morning', 1, '2026-04-17 14:47:03', NULL),
(2, 'Quin', 'Bee', 'Medical Degree', '0702104546', 'quin@hospital.com', 'quin', 'quin123', 1, 'Rotating', 1, '2026-04-17 14:47:03', NULL),
(3, 'Masaka', 'Mane', 'BSN', '0704026744', 'mane@hospital.com', 'mane', 'mane123', 5, 'Rotating', 1, '2026-04-17 14:47:03', NULL),
(4, 'Alice', 'Nambi', 'RN', '0711111111', 'alice@hospital.com', 'alice', 'alice123', 1, 'Morning', 1, '2026-04-17 14:47:03', NULL),
(5, 'Betty', 'Kato', 'LPN', '0722222222', 'betty@hospital.com', 'betty', 'betty123', 2, 'Evening', 1, '2026-04-17 14:47:03', NULL),
(6, 'Carol', 'Nakibuuka', 'BSN', '0733333333', 'carol@hospital.com', 'carol', 'carol123', 3, 'Night', 1, '2026-04-17 14:47:03', NULL),
(7, 'Diana', 'Mukasa', 'RN', '0744444444', 'diana@hospital.com', 'diana', 'diana123', 4, 'Rotating', 1, '2026-04-17 14:47:03', NULL),
(8, 'Mary', 'Nakamya', 'BSN', '0755000001', 'mary@hospital.com', 'mary', 'mary123', 1, 'Morning', 1, '2026-04-17 14:49:11', NULL),
(9, 'John', 'Mukasa', 'RN', '0755000002', 'john@hospital.com', 'john', 'john123', 2, 'Evening', 1, '2026-04-17 14:49:11', NULL),
(10, 'Peter', 'Okoth', 'LPN', '0755000003', 'peter@hospital.com', 'peter', 'peter123', 3, 'Night', 1, '2026-04-17 14:49:11', NULL),
(11, 'Jane', 'Atim', 'BSN', '0755000004', 'jane@hospital.com', 'jane', 'jane123', 4, 'Rotating', 1, '2026-04-17 14:49:11', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `nurseschedule`
--

CREATE TABLE `nurseschedule` (
  `ScheduleID` int(11) NOT NULL,
  `NurseID` int(11) NOT NULL,
  `ShiftDate` date NOT NULL,
  `StartTime` time NOT NULL,
  `EndTime` time NOT NULL,
  `AssignedDepartmentID` int(11) DEFAULT NULL,
  `PatientTreatmentID` int(11) DEFAULT NULL,
  `ShiftType` enum('Morning','Evening','Night') NOT NULL,
  `Notes` text DEFAULT NULL,
  `CreatedAt` timestamp NOT NULL DEFAULT current_timestamp()
) ;

-- --------------------------------------------------------

--
-- Table structure for table `patient`
--

CREATE TABLE `patient` (
  `PatientID` int(11) NOT NULL,
  `FirstName` varchar(50) NOT NULL,
  `LastName` varchar(50) NOT NULL,
  `DateOfBirth` date NOT NULL,
  `Gender` enum('Male','Female','Other') NOT NULL,
  `ContactNo` varchar(20) NOT NULL,
  `Email` varchar(100) DEFAULT NULL,
  `Username` varchar(50) DEFAULT NULL,
  `Password` varchar(255) DEFAULT NULL,
  `LastLogin` datetime DEFAULT NULL,
  `Address` text DEFAULT NULL,
  `BloodGroup` varchar(5) DEFAULT NULL,
  `EmergencyContactName` varchar(100) DEFAULT NULL,
  `EmergencyContactNo` varchar(20) DEFAULT NULL,
  `RegistrationDate` date NOT NULL,
  `IsActive` tinyint(1) DEFAULT 1,
  `CreatedAt` timestamp NOT NULL DEFAULT current_timestamp(),
  `PrescriptionLockedBy` varchar(20) DEFAULT NULL,
  `LastPrescriptionDate` datetime DEFAULT NULL,
  `LastPrescribedBy` enum('Doctor','Nurse') DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `patient`
--

INSERT INTO `patient` (`PatientID`, `FirstName`, `LastName`, `DateOfBirth`, `Gender`, `ContactNo`, `Email`, `Username`, `Password`, `LastLogin`, `Address`, `BloodGroup`, `EmergencyContactName`, `EmergencyContactNo`, `RegistrationDate`, `IsActive`, `CreatedAt`, `PrescriptionLockedBy`, `LastPrescriptionDate`, `LastPrescribedBy`) VALUES
(1, 'joel', 'Evaline', '2002-07-20', 'Male', '0756000899', 'joel@gmail.com', 'joel', 'joel123', '2026-04-17 18:24:11', 'Masindi', 'O-', 'Amutosi pauline', '0774670550', '2026-04-17', 1, '2026-04-17 15:23:01', NULL, NULL, NULL);

-- --------------------------------------------------------

--
-- Table structure for table `patientlabtest`
--

CREATE TABLE `patientlabtest` (
  `PatientLabTestID` int(11) NOT NULL,
  `PatientID` int(11) NOT NULL,
  `LabTestID` int(11) NOT NULL,
  `AppointmentID` int(11) DEFAULT NULL,
  `PatientTreatmentID` int(11) DEFAULT NULL,
  `OrderDate` datetime NOT NULL,
  `ResultDate` datetime DEFAULT NULL,
  `ResultValue` text DEFAULT NULL,
  `Status` enum('Ordered','SampleCollected','Processing','Completed','Cancelled') DEFAULT 'Ordered',
  `OrderedByDoctorID` int(11) NOT NULL,
  `LabTechnicianID` int(11) DEFAULT NULL,
  `Notes` text DEFAULT NULL,
  `CreatedAt` timestamp NOT NULL DEFAULT current_timestamp(),
  `ReviewedByDoctor` tinyint(4) DEFAULT 0,
  `ReviewDate` datetime DEFAULT NULL,
  `ReviewedByNurse` tinyint(4) DEFAULT 0
) ;

-- --------------------------------------------------------

--
-- Table structure for table `patienttreatment`
--

CREATE TABLE `patienttreatment` (
  `PatientTreatmentID` int(11) NOT NULL,
  `PatientID` int(11) NOT NULL,
  `TreatmentID` int(11) NOT NULL,
  `StartDate` date NOT NULL,
  `EndDate` date DEFAULT NULL,
  `Status` enum('Scheduled','Ongoing','Completed','Cancelled','OnHold') DEFAULT 'Scheduled',
  `SequenceOrder` int(11) NOT NULL CHECK (`SequenceOrder` > 0),
  `Notes` text DEFAULT NULL,
  `CreatedAt` timestamp NOT NULL DEFAULT current_timestamp(),
  `PrescribedByDoctor` int(11) DEFAULT NULL,
  `LabTestID` int(11) DEFAULT NULL
) ;

-- --------------------------------------------------------

--
-- Table structure for table `prescription`
--

CREATE TABLE `prescription` (
  `PrescriptionID` int(11) NOT NULL,
  `PatientID` int(11) NOT NULL,
  `DoctorID` int(11) DEFAULT NULL,
  `NurseID` int(11) DEFAULT NULL,
  `AppointmentID` int(11) DEFAULT NULL,
  `PatientTreatmentID` int(11) DEFAULT NULL,
  `PrescriptionDate` datetime NOT NULL,
  `MedicationName` varchar(100) NOT NULL,
  `Dosage` varchar(100) DEFAULT NULL,
  `Frequency` varchar(100) DEFAULT NULL,
  `Duration` varchar(100) DEFAULT NULL,
  `Instructions` text DEFAULT NULL,
  `CreatedAt` timestamp NOT NULL DEFAULT current_timestamp(),
  `LabTestID` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `treatment`
--

CREATE TABLE `treatment` (
  `TreatmentID` int(11) NOT NULL,
  `TreatmentName` varchar(100) NOT NULL,
  `StandardDurationDays` int(11) DEFAULT 1,
  `Description` text DEFAULT NULL,
  `BaseCost` decimal(10,2) DEFAULT 0.00,
  `IsActive` tinyint(1) DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `treatment`
--

INSERT INTO `treatment` (`TreatmentID`, `TreatmentName`, `StandardDurationDays`, `Description`, `BaseCost`, `IsActive`) VALUES
(1, 'Chemotherapy - Stage 1', 30, 'Initial chemotherapy session', 5000.00, 1),
(2, 'Chemotherapy - Stage 2', 30, 'Second stage chemotherapy', 4500.00, 1),
(3, 'Chemotherapy - Stage 3', 30, 'Final stage chemotherapy', 4000.00, 1),
(4, 'Knee Replacement', 45, 'Total knee replacement surgery', 25000.00, 1),
(5, 'Physical Therapy', 60, 'Post-surgery rehabilitation', 2000.00, 1),
(6, 'MRI Scan', 1, 'Magnetic resonance imaging', 3500.00, 1),
(7, 'CT Scan', 1, 'Computed tomography scan', 3000.00, 1);

-- --------------------------------------------------------

--
-- Stand-in structure for view `vw_dailyappointments`
-- (See below for the actual view)
--
CREATE TABLE `vw_dailyappointments` (
`AppointmentID` int(11)
,`PatientName` varchar(101)
,`DoctorName` varchar(101)
,`Specialization` varchar(100)
,`AppointmentDateTime` datetime
,`DurationMinutes` int(11)
,`Status` enum('Scheduled','CheckedIn','InProgress','Completed','Cancelled','NoShow')
,`Purpose` text
);

-- --------------------------------------------------------

--
-- Stand-in structure for view `vw_patienttreatments`
-- (See below for the actual view)
--
CREATE TABLE `vw_patienttreatments` (
`PatientID` int(11)
,`PatientName` varchar(101)
,`TreatmentName` varchar(100)
,`SequenceOrder` int(11)
,`StartDate` date
,`EndDate` date
,`Status` enum('Scheduled','Ongoing','Completed','Cancelled','OnHold')
,`Department` varchar(100)
);

-- --------------------------------------------------------

--
-- Stand-in structure for view `vw_pendingbills`
-- (See below for the actual view)
--
CREATE TABLE `vw_pendingbills` (
`BillID` int(11)
,`BillNumber` varchar(50)
,`PatientName` varchar(101)
,`BillDate` date
,`DueDate` date
,`TotalAmount` decimal(12,2)
,`AmountPaid` decimal(12,2)
,`BalanceDue` decimal(13,2)
,`PaymentStatus` enum('Pending','Partial','Paid','Overdue','Refunded')
);

-- --------------------------------------------------------

--
-- Structure for view `vw_dailyappointments`
--
DROP TABLE IF EXISTS `vw_dailyappointments`;

CREATE ALGORITHM=UNDEFINED DEFINER=`root`@`localhost` SQL SECURITY DEFINER VIEW `vw_dailyappointments`  AS SELECT `a`.`AppointmentID` AS `AppointmentID`, concat(`pat`.`FirstName`,' ',`pat`.`LastName`) AS `PatientName`, concat(`doc`.`FirstName`,' ',`doc`.`LastName`) AS `DoctorName`, `doc`.`Specialization` AS `Specialization`, `a`.`AppointmentDateTime` AS `AppointmentDateTime`, `a`.`DurationMinutes` AS `DurationMinutes`, `a`.`Status` AS `Status`, `a`.`Purpose` AS `Purpose` FROM ((`appointment` `a` join `patient` `pat` on(`a`.`PatientID` = `pat`.`PatientID`)) join `doctor` `doc` on(`a`.`DoctorID` = `doc`.`DoctorID`)) WHERE `a`.`AppointmentDateTime` >= curdate() ORDER BY `a`.`AppointmentDateTime` ASC ;

-- --------------------------------------------------------

--
-- Structure for view `vw_patienttreatments`
--
DROP TABLE IF EXISTS `vw_patienttreatments`;

CREATE ALGORITHM=UNDEFINED DEFINER=`root`@`localhost` SQL SECURITY DEFINER VIEW `vw_patienttreatments`  AS SELECT `p`.`PatientID` AS `PatientID`, concat(`p`.`FirstName`,' ',`p`.`LastName`) AS `PatientName`, `t`.`TreatmentName` AS `TreatmentName`, `pt`.`SequenceOrder` AS `SequenceOrder`, `pt`.`StartDate` AS `StartDate`, `pt`.`EndDate` AS `EndDate`, `pt`.`Status` AS `Status`, `d`.`DeptName` AS `Department` FROM ((((`patienttreatment` `pt` join `patient` `p` on(`pt`.`PatientID` = `p`.`PatientID`)) join `treatment` `t` on(`pt`.`TreatmentID` = `t`.`TreatmentID`)) left join `departmentcollaboration` `dc` on(`pt`.`PatientTreatmentID` = `dc`.`PatientTreatmentID`)) left join `department` `d` on(`dc`.`LeadDepartmentID` = `d`.`DepartmentID`)) ;

-- --------------------------------------------------------

--
-- Structure for view `vw_pendingbills`
--
DROP TABLE IF EXISTS `vw_pendingbills`;

CREATE ALGORITHM=UNDEFINED DEFINER=`root`@`localhost` SQL SECURITY DEFINER VIEW `vw_pendingbills`  AS SELECT `b`.`BillID` AS `BillID`, `b`.`BillNumber` AS `BillNumber`, concat(`p`.`FirstName`,' ',`p`.`LastName`) AS `PatientName`, `b`.`BillDate` AS `BillDate`, `b`.`DueDate` AS `DueDate`, `b`.`TotalAmount` AS `TotalAmount`, `b`.`AmountPaid` AS `AmountPaid`, `b`.`TotalAmount`- `b`.`AmountPaid` AS `BalanceDue`, `b`.`PaymentStatus` AS `PaymentStatus` FROM (`billing` `b` join `patient` `p` on(`b`.`PatientID` = `p`.`PatientID`)) WHERE `b`.`PaymentStatus` in ('Pending','Partial') ORDER BY `b`.`DueDate` ASC ;

--
-- Indexes for dumped tables
--

--
-- Indexes for table `appointment`
--
ALTER TABLE `appointment`
  ADD PRIMARY KEY (`AppointmentID`),
  ADD KEY `PatientTreatmentID` (`PatientTreatmentID`),
  ADD KEY `idx_DoctorDateTime` (`DoctorID`,`AppointmentDateTime`),
  ADD KEY `idx_Appointment_Patient` (`PatientID`,`AppointmentDateTime`),
  ADD KEY `idx_Appointment_Status` (`Status`,`AppointmentDateTime`),
  ADD KEY `idx_Appointment_Date` (`AppointmentDateTime`),
  ADD KEY `idx_appointment_datetime` (`AppointmentDateTime`,`Status`);

--
-- Indexes for table `billing`
--
ALTER TABLE `billing`
  ADD PRIMARY KEY (`BillID`),
  ADD UNIQUE KEY `BillNumber` (`BillNumber`),
  ADD KEY `idx_Billing_Patient` (`PatientID`,`PaymentStatus`),
  ADD KEY `idx_Billing_Date` (`BillDate`),
  ADD KEY `idx_BillNumber` (`BillNumber`);

--
-- Indexes for table `billlineitem`
--
ALTER TABLE `billlineitem`
  ADD PRIMARY KEY (`BillLineItemID`),
  ADD KEY `BillID` (`BillID`),
  ADD KEY `idx_Service` (`ServiceType`,`ServiceID`);

--
-- Indexes for table `department`
--
ALTER TABLE `department`
  ADD PRIMARY KEY (`DepartmentID`),
  ADD UNIQUE KEY `DeptName` (`DeptName`),
  ADD KEY `FK_Department_HeadDoctor` (`HeadDoctorID`);

--
-- Indexes for table `departmentcollaboration`
--
ALTER TABLE `departmentcollaboration`
  ADD PRIMARY KEY (`CollaborationID`),
  ADD KEY `PatientTreatmentID` (`PatientTreatmentID`),
  ADD KEY `LeadDepartmentID` (`LeadDepartmentID`),
  ADD KEY `SupportingDepartmentID` (`SupportingDepartmentID`);

--
-- Indexes for table `doctor`
--
ALTER TABLE `doctor`
  ADD PRIMARY KEY (`DoctorID`),
  ADD UNIQUE KEY `ContactNo` (`ContactNo`),
  ADD UNIQUE KEY `Email` (`Email`),
  ADD UNIQUE KEY `Username` (`Username`),
  ADD KEY `DepartmentID` (`DepartmentID`),
  ADD KEY `idx_doctor_active` (`IsActive`);

--
-- Indexes for table `doctorschedule`
--
ALTER TABLE `doctorschedule`
  ADD PRIMARY KEY (`ScheduleID`),
  ADD KEY `idx_doctor_date` (`DoctorID`,`ScheduleDate`),
  ADD KEY `idx_status` (`Status`);

--
-- Indexes for table `labtest`
--
ALTER TABLE `labtest`
  ADD PRIMARY KEY (`LabTestID`),
  ADD UNIQUE KEY `TestName` (`TestName`);

--
-- Indexes for table `nurse`
--
ALTER TABLE `nurse`
  ADD PRIMARY KEY (`NurseID`),
  ADD UNIQUE KEY `ContactNo` (`ContactNo`),
  ADD UNIQUE KEY `Email` (`Email`),
  ADD UNIQUE KEY `Username` (`Username`),
  ADD KEY `DepartmentID` (`DepartmentID`);

--
-- Indexes for table `nurseschedule`
--
ALTER TABLE `nurseschedule`
  ADD PRIMARY KEY (`ScheduleID`),
  ADD KEY `AssignedDepartmentID` (`AssignedDepartmentID`),
  ADD KEY `PatientTreatmentID` (`PatientTreatmentID`),
  ADD KEY `idx_NurseSchedule_Nurse` (`NurseID`,`ShiftDate`),
  ADD KEY `idx_NurseSchedule_Date` (`ShiftDate`);

--
-- Indexes for table `patient`
--
ALTER TABLE `patient`
  ADD PRIMARY KEY (`PatientID`),
  ADD UNIQUE KEY `ContactNo` (`ContactNo`),
  ADD UNIQUE KEY `Email` (`Email`),
  ADD UNIQUE KEY `Username` (`Username`),
  ADD KEY `idx_Patient_Name` (`LastName`,`FirstName`),
  ADD KEY `idx_Patient_Contact` (`ContactNo`),
  ADD KEY `idx_Patient_RegDate` (`RegistrationDate`),
  ADD KEY `idx_patient_active` (`IsActive`),
  ADD KEY `idx_username` (`Username`);

--
-- Indexes for table `patientlabtest`
--
ALTER TABLE `patientlabtest`
  ADD PRIMARY KEY (`PatientLabTestID`),
  ADD KEY `LabTestID` (`LabTestID`),
  ADD KEY `AppointmentID` (`AppointmentID`),
  ADD KEY `PatientTreatmentID` (`PatientTreatmentID`),
  ADD KEY `OrderedByDoctorID` (`OrderedByDoctorID`),
  ADD KEY `idx_LabTest_Patient` (`PatientID`,`Status`),
  ADD KEY `idx_LabTest_OrderDate` (`OrderDate`);

--
-- Indexes for table `patienttreatment`
--
ALTER TABLE `patienttreatment`
  ADD PRIMARY KEY (`PatientTreatmentID`),
  ADD UNIQUE KEY `UQ_PatientTreatment_Seq` (`PatientID`,`SequenceOrder`),
  ADD KEY `TreatmentID` (`TreatmentID`),
  ADD KEY `idx_PatientTreatment_Patient` (`PatientID`,`Status`),
  ADD KEY `idx_PatientTreatment_Dates` (`StartDate`,`EndDate`),
  ADD KEY `LabTestID` (`LabTestID`),
  ADD KEY `idx_patient_treatment` (`PatientID`);

--
-- Indexes for table `prescription`
--
ALTER TABLE `prescription`
  ADD PRIMARY KEY (`PrescriptionID`),
  ADD KEY `AppointmentID` (`AppointmentID`),
  ADD KEY `PatientTreatmentID` (`PatientTreatmentID`),
  ADD KEY `idx_doctor` (`DoctorID`),
  ADD KEY `idx_nurse` (`NurseID`),
  ADD KEY `idx_patient_role` (`PatientID`,`DoctorID`,`NurseID`),
  ADD KEY `idx_prescription_date` (`PrescriptionDate`),
  ADD KEY `LabTestID` (`LabTestID`);

--
-- Indexes for table `treatment`
--
ALTER TABLE `treatment`
  ADD PRIMARY KEY (`TreatmentID`),
  ADD UNIQUE KEY `TreatmentName` (`TreatmentName`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `appointment`
--
ALTER TABLE `appointment`
  MODIFY `AppointmentID` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `billing`
--
ALTER TABLE `billing`
  MODIFY `BillID` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `billlineitem`
--
ALTER TABLE `billlineitem`
  MODIFY `BillLineItemID` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `department`
--
ALTER TABLE `department`
  MODIFY `DepartmentID` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT for table `departmentcollaboration`
--
ALTER TABLE `departmentcollaboration`
  MODIFY `CollaborationID` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `doctor`
--
ALTER TABLE `doctor`
  MODIFY `DoctorID` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT for table `doctorschedule`
--
ALTER TABLE `doctorschedule`
  MODIFY `ScheduleID` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `labtest`
--
ALTER TABLE `labtest`
  MODIFY `LabTestID` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `nurse`
--
ALTER TABLE `nurse`
  MODIFY `NurseID` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=12;

--
-- AUTO_INCREMENT for table `nurseschedule`
--
ALTER TABLE `nurseschedule`
  MODIFY `ScheduleID` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `patient`
--
ALTER TABLE `patient`
  MODIFY `PatientID` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `patientlabtest`
--
ALTER TABLE `patientlabtest`
  MODIFY `PatientLabTestID` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `patienttreatment`
--
ALTER TABLE `patienttreatment`
  MODIFY `PatientTreatmentID` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `prescription`
--
ALTER TABLE `prescription`
  MODIFY `PrescriptionID` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `treatment`
--
ALTER TABLE `treatment`
  MODIFY `TreatmentID` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `appointment`
--
ALTER TABLE `appointment`
  ADD CONSTRAINT `appointment_ibfk_1` FOREIGN KEY (`PatientID`) REFERENCES `patient` (`PatientID`),
  ADD CONSTRAINT `appointment_ibfk_2` FOREIGN KEY (`DoctorID`) REFERENCES `doctor` (`DoctorID`),
  ADD CONSTRAINT `appointment_ibfk_3` FOREIGN KEY (`PatientTreatmentID`) REFERENCES `patienttreatment` (`PatientTreatmentID`) ON DELETE SET NULL;

--
-- Constraints for table `billing`
--
ALTER TABLE `billing`
  ADD CONSTRAINT `billing_ibfk_1` FOREIGN KEY (`PatientID`) REFERENCES `patient` (`PatientID`);

--
-- Constraints for table `billlineitem`
--
ALTER TABLE `billlineitem`
  ADD CONSTRAINT `billlineitem_ibfk_1` FOREIGN KEY (`BillID`) REFERENCES `billing` (`BillID`) ON DELETE CASCADE;

--
-- Constraints for table `department`
--
ALTER TABLE `department`
  ADD CONSTRAINT `FK_Department_HeadDoctor` FOREIGN KEY (`HeadDoctorID`) REFERENCES `doctor` (`DoctorID`) ON DELETE SET NULL;

--
-- Constraints for table `departmentcollaboration`
--
ALTER TABLE `departmentcollaboration`
  ADD CONSTRAINT `departmentcollaboration_ibfk_1` FOREIGN KEY (`PatientTreatmentID`) REFERENCES `patienttreatment` (`PatientTreatmentID`) ON DELETE CASCADE,
  ADD CONSTRAINT `departmentcollaboration_ibfk_2` FOREIGN KEY (`LeadDepartmentID`) REFERENCES `department` (`DepartmentID`),
  ADD CONSTRAINT `departmentcollaboration_ibfk_3` FOREIGN KEY (`SupportingDepartmentID`) REFERENCES `department` (`DepartmentID`);

--
-- Constraints for table `doctor`
--
ALTER TABLE `doctor`
  ADD CONSTRAINT `doctor_ibfk_1` FOREIGN KEY (`DepartmentID`) REFERENCES `department` (`DepartmentID`);

--
-- Constraints for table `doctorschedule`
--
ALTER TABLE `doctorschedule`
  ADD CONSTRAINT `doctorschedule_ibfk_1` FOREIGN KEY (`DoctorID`) REFERENCES `doctor` (`DoctorID`);

--
-- Constraints for table `nurse`
--
ALTER TABLE `nurse`
  ADD CONSTRAINT `nurse_ibfk_1` FOREIGN KEY (`DepartmentID`) REFERENCES `department` (`DepartmentID`) ON DELETE SET NULL;

--
-- Constraints for table `nurseschedule`
--
ALTER TABLE `nurseschedule`
  ADD CONSTRAINT `nurseschedule_ibfk_1` FOREIGN KEY (`NurseID`) REFERENCES `nurse` (`NurseID`) ON DELETE CASCADE,
  ADD CONSTRAINT `nurseschedule_ibfk_2` FOREIGN KEY (`AssignedDepartmentID`) REFERENCES `department` (`DepartmentID`) ON DELETE SET NULL,
  ADD CONSTRAINT `nurseschedule_ibfk_3` FOREIGN KEY (`PatientTreatmentID`) REFERENCES `patienttreatment` (`PatientTreatmentID`) ON DELETE SET NULL;

--
-- Constraints for table `patientlabtest`
--
ALTER TABLE `patientlabtest`
  ADD CONSTRAINT `patientlabtest_ibfk_1` FOREIGN KEY (`PatientID`) REFERENCES `patient` (`PatientID`),
  ADD CONSTRAINT `patientlabtest_ibfk_2` FOREIGN KEY (`LabTestID`) REFERENCES `labtest` (`LabTestID`),
  ADD CONSTRAINT `patientlabtest_ibfk_3` FOREIGN KEY (`AppointmentID`) REFERENCES `appointment` (`AppointmentID`) ON DELETE SET NULL,
  ADD CONSTRAINT `patientlabtest_ibfk_4` FOREIGN KEY (`PatientTreatmentID`) REFERENCES `patienttreatment` (`PatientTreatmentID`) ON DELETE SET NULL,
  ADD CONSTRAINT `patientlabtest_ibfk_5` FOREIGN KEY (`OrderedByDoctorID`) REFERENCES `doctor` (`DoctorID`);

--
-- Constraints for table `patienttreatment`
--
ALTER TABLE `patienttreatment`
  ADD CONSTRAINT `patienttreatment_ibfk_1` FOREIGN KEY (`PatientID`) REFERENCES `patient` (`PatientID`),
  ADD CONSTRAINT `patienttreatment_ibfk_2` FOREIGN KEY (`TreatmentID`) REFERENCES `treatment` (`TreatmentID`),
  ADD CONSTRAINT `patienttreatment_ibfk_3` FOREIGN KEY (`LabTestID`) REFERENCES `patientlabtest` (`PatientLabTestID`);

--
-- Constraints for table `prescription`
--
ALTER TABLE `prescription`
  ADD CONSTRAINT `prescription_ibfk_1` FOREIGN KEY (`PatientID`) REFERENCES `patient` (`PatientID`),
  ADD CONSTRAINT `prescription_ibfk_10` FOREIGN KEY (`DoctorID`) REFERENCES `doctor` (`DoctorID`),
  ADD CONSTRAINT `prescription_ibfk_11` FOREIGN KEY (`NurseID`) REFERENCES `nurse` (`NurseID`),
  ADD CONSTRAINT `prescription_ibfk_12` FOREIGN KEY (`NurseID`) REFERENCES `nurse` (`NurseID`),
  ADD CONSTRAINT `prescription_ibfk_13` FOREIGN KEY (`NurseID`) REFERENCES `nurse` (`NurseID`),
  ADD CONSTRAINT `prescription_ibfk_14` FOREIGN KEY (`DoctorID`) REFERENCES `doctor` (`DoctorID`),
  ADD CONSTRAINT `prescription_ibfk_15` FOREIGN KEY (`NurseID`) REFERENCES `nurse` (`NurseID`),
  ADD CONSTRAINT `prescription_ibfk_16` FOREIGN KEY (`NurseID`) REFERENCES `nurse` (`NurseID`),
  ADD CONSTRAINT `prescription_ibfk_17` FOREIGN KEY (`LabTestID`) REFERENCES `patientlabtest` (`PatientLabTestID`),
  ADD CONSTRAINT `prescription_ibfk_18` FOREIGN KEY (`LabTestID`) REFERENCES `patientlabtest` (`PatientLabTestID`),
  ADD CONSTRAINT `prescription_ibfk_2` FOREIGN KEY (`DoctorID`) REFERENCES `doctor` (`DoctorID`),
  ADD CONSTRAINT `prescription_ibfk_3` FOREIGN KEY (`NurseID`) REFERENCES `nurse` (`NurseID`),
  ADD CONSTRAINT `prescription_ibfk_4` FOREIGN KEY (`AppointmentID`) REFERENCES `appointment` (`AppointmentID`),
  ADD CONSTRAINT `prescription_ibfk_5` FOREIGN KEY (`PatientTreatmentID`) REFERENCES `patienttreatment` (`PatientTreatmentID`),
  ADD CONSTRAINT `prescription_ibfk_6` FOREIGN KEY (`NurseID`) REFERENCES `nurse` (`NurseID`),
  ADD CONSTRAINT `prescription_ibfk_7` FOREIGN KEY (`DoctorID`) REFERENCES `doctor` (`DoctorID`),
  ADD CONSTRAINT `prescription_ibfk_8` FOREIGN KEY (`DoctorID`) REFERENCES `doctor` (`DoctorID`),
  ADD CONSTRAINT `prescription_ibfk_9` FOREIGN KEY (`DoctorID`) REFERENCES `doctor` (`DoctorID`);
SET FOREIGN_KEY_CHECKS=1;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
