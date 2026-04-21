-- phpMyAdmin SQL Dump
-- version 5.2.1deb3
-- https://www.phpmyadmin.net/
--
-- Host: localhost:3306
-- Generation Time: Apr 10, 2026 at 06:57 PM
-- Server version: 10.11.14-MariaDB-0ubuntu0.24.04.1
-- PHP Version: 8.3.6

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `iceburg`
--

-- --------------------------------------------------------

--
-- Table structure for table `Admin Users`
--

CREATE TABLE `Admin Users` (
  `UserEmail` text NOT NULL,
  `UserPassword` text NOT NULL,
  `UserPermissions` text NOT NULL,
  `id` int(11) NOT NULL,
  `allowedPlugins` text NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `Admin Users`
--

INSERT INTO `Admin Users` (`UserEmail`, `UserPassword`, `UserPermissions`, `id`, `allowedPlugins`) VALUES
('admin', '$2y$10$aYyi/ztJI41GZD7s90rYLOenHCXJLW3CIWuar4LHa3mPJDASQMQLK', '5', 16, '[\"2\",\"1\"]'),
('frontPanle', '$2y$10$WfbIdtZ8fBR8kFP13WJq0etVEQxsNo1.pRi89wIPZSYR9c6qGPNo.', '2', 24, '[\"7\",\"4\"]');

-- --------------------------------------------------------

--
-- Table structure for table `auditlogs`
--

CREATE TABLE `auditlogs` (
  `id` int(11) NOT NULL,
  `userid` int(11) DEFAULT NULL,
  `changetype` text NOT NULL,
  `changedetails` text NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `deviceplugin`
--

CREATE TABLE `deviceplugin` (
  `id` int(11) NOT NULL,
  `pluginName` text NOT NULL,
  `pluginFolder` text NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `deviceplugin`
--

INSERT INTO `deviceplugin` (`id`, `pluginName`, `pluginFolder`) VALUES
(1, 'X32/M32', '/x32/'),
(2, 'BMD Videohub', '/blackmagicvideohub/'),
(3, 'Cobalt 9905-MPX', '/9905-mpx/'),
(4, 'Iceburg Tally 8x8', '/tally/'),
(5, 'Blackmagic Router Panel 40 Button', '/blackmagicrouterpanel/'),
(6, 'BMD Smart View', '/bmdSmartScope/'),
(7, 'AI Tally', '/aitally/'),
(8, 'Blackmagic Router Panel 48 Button', '/48blackmagicrouterpanel/'),
(9, 'ZowieTek POV Camera', '/zowietekpov/'),
(10, 'AJA FS-2', '/ajafs2/'),
(11, 'AJA FS-4', '/ajafs4/');

-- --------------------------------------------------------

--
-- Table structure for table `devices`
--

CREATE TABLE `devices` (
  `id` int(11) NOT NULL,
  `name` text NOT NULL,
  `ip` text NOT NULL,
  `pluginID` int(11) NOT NULL,
  `madisorce` int(11) NOT NULL DEFAULT 0,
  `lastping` text NOT NULL DEFAULT '0'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `routerpanle`
--

CREATE TABLE `routerpanle` (
  `allowedusers` text NOT NULL,
  `panleName` text NOT NULL,
  `sorces` text NOT NULL,
  `destnations` text NOT NULL,
  `deviceID` int(11) NOT NULL,
  `id` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `tally_channels`
--

CREATE TABLE `tally_channels` (
  `id` int(11) NOT NULL,
  `device_id` int(11) NOT NULL,
  `channel` int(11) NOT NULL,
  `type` enum('input','output') NOT NULL,
  `state` tinyint(4) DEFAULT 0,
  `name` text DEFAULT NULL,
  `umd` text NOT NULL DEFAULT 'No UMD Listed'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `tally_mappings`
--

CREATE TABLE `tally_mappings` (
  `id` int(11) NOT NULL,
  `from_device` int(11) NOT NULL,
  `from_channel` int(11) NOT NULL,
  `to_device` int(11) NOT NULL,
  `to_channel` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Indexes for dumped tables
--

--
-- Indexes for table `Admin Users`
--
ALTER TABLE `Admin Users`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `auditlogs`
--
ALTER TABLE `auditlogs`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `deviceplugin`
--
ALTER TABLE `deviceplugin`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `devices`
--
ALTER TABLE `devices`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `routerpanle`
--
ALTER TABLE `routerpanle`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `tally_channels`
--
ALTER TABLE `tally_channels`
  ADD PRIMARY KEY (`id`),
  ADD KEY `device_id` (`device_id`);

--
-- Indexes for table `tally_mappings`
--
ALTER TABLE `tally_mappings`
  ADD PRIMARY KEY (`id`),
  ADD KEY `from_device` (`from_device`),
  ADD KEY `to_device` (`to_device`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `Admin Users`
--
ALTER TABLE `Admin Users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=28;

--
-- AUTO_INCREMENT for table `auditlogs`
--
ALTER TABLE `auditlogs`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `deviceplugin`
--
ALTER TABLE `deviceplugin`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- AUTO_INCREMENT for table `devices`
--
ALTER TABLE `devices`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `routerpanle`
--
ALTER TABLE `routerpanle`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `tally_channels`
--
ALTER TABLE `tally_channels`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `tally_mappings`
--
ALTER TABLE `tally_mappings`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `tally_channels`
--
ALTER TABLE `tally_channels`
  ADD CONSTRAINT `tally_channels_ibfk_1` FOREIGN KEY (`device_id`) REFERENCES `devices` (`id`);

--
-- Constraints for table `tally_mappings`
--
ALTER TABLE `tally_mappings`
  ADD CONSTRAINT `tally_mappings_ibfk_1` FOREIGN KEY (`from_device`) REFERENCES `devices` (`id`),
  ADD CONSTRAINT `tally_mappings_ibfk_2` FOREIGN KEY (`to_device`) REFERENCES `devices` (`id`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
