-- phpMyAdmin SQL Dump
-- version 5.2.2deb2
-- https://www.phpmyadmin.net/
--
-- Host: localhost:3306
-- Generation Time: Mar 07, 2026 at 11:38 PM
-- Server version: 11.8.3-MariaDB-1build1 from Ubuntu
-- PHP Version: 8.4.11

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
('admin', '$2y$10$mKl8gH8YRZLxFYDABAOdJ.v5UO0ZIi9PY2gGkF.9DPoAif5tQCyva', '5', 16, '[\"2\",\"1\"]'),
('A1', '$2y$12$UG91OfyblSogrAGe3i5WLOX4l3TWrLd9cWlp4E6cKo4ScvHjpa09a', '2', 17, '[\"3\",\"1\"]'),
('eic', '$2y$10$gcJ55oFuZc5xBj/tSvzk5uPnPfw8DSDEOVJQb1Vrip1GdqW/14AdW', '4', 21, '[]'),
('v1', '$2y$12$iHQP0z9bQCDBGIlvw0S8weynQYVb3cWB1B8RlYzAHdqcdtVaoAJC2', '2', 23, '[\"2\",\"3\"]');

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
(5, 'Blackmagic Router Panel', '/blackmagicrouterpanel/'),
(6, 'BMD Smart View', '/bmdSmartScope/'),
(7, 'AI Tally', '/aitally/');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `Admin Users`
--
ALTER TABLE `Admin Users`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `deviceplugin`
--
ALTER TABLE `deviceplugin`
  ADD PRIMARY KEY (`id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `Admin Users`
--
ALTER TABLE `Admin Users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=24;

--
-- AUTO_INCREMENT for table `deviceplugin`
--
ALTER TABLE `deviceplugin`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
