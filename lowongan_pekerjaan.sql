-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Dec 06, 2025 at 08:41 AM
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
-- Database: `lowongan_pekerjaan`
--

-- --------------------------------------------------------

--
-- Table structure for table `id_lokasi`
--

CREATE TABLE `id_lokasi` (
  `id_lokasi` int(11) NOT NULL,
  `nama_lokasi` varchar(100) NOT NULL,
  `negara` varchar(50) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `id_lokasi`
--

INSERT INTO `id_lokasi` (`id_lokasi`, `nama_lokasi`, `negara`) VALUES
(1, 'Bali', NULL),
(2, 'Makasar', 'Indonesia'),
(3, 'Surabaya', 'Indonesia'),
(4, 'Malang', 'Indonesia');

-- --------------------------------------------------------

--
-- Table structure for table `tb_admin`
--

CREATE TABLE `tb_admin` (
  `id_admin` int(11) NOT NULL,
  `nama` varchar(100) NOT NULL,
  `password` varchar(255) NOT NULL,
  `tanggal_daftar` date DEFAULT NULL,
  `status_login` datetime DEFAULT NULL,
  `email` varchar(100) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `tb_admin`
--

INSERT INTO `tb_admin` (`id_admin`, `nama`, `password`, `tanggal_daftar`, `status_login`, `email`) VALUES
(0, 'Ryco Poetra Widjaya', 'admin123', '0000-00-00', '2025-10-22 13:40:28', 'admin@proyek.com');

-- --------------------------------------------------------

--
-- Table structure for table `tb_lamaran`
--

CREATE TABLE `tb_lamaran` (
  `id_lamaran` int(11) NOT NULL,
  `id_pengguna` int(11) NOT NULL,
  `id_lowongan` int(11) NOT NULL,
  `tanggal_lamaran` date DEFAULT curdate(),
  `status` enum('pending','diterima','ditolak') DEFAULT 'pending',
  `catatan` text DEFAULT NULL,
  `file_cv` varchar(255) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `tb_lamaran`
--

INSERT INTO `tb_lamaran` (`id_lamaran`, `id_pengguna`, `id_lowongan`, `tanggal_lamaran`, `status`, `catatan`, `file_cv`, `created_at`) VALUES
(5, 6, 7, '2025-10-15', 'pending', 'kaya kaya', '6_7_1760508614.pdf', '2025-10-15 06:10:14'),
(6, 10, 7, '2025-10-17', 'pending', 'cvv', '10_7_1760707518.pdf', '2025-10-17 13:25:18');

-- --------------------------------------------------------

--
-- Table structure for table `tb_lowongan`
--

CREATE TABLE `tb_lowongan` (
  `id_lowongan` int(11) NOT NULL,
  `perusahaan` varchar(100) NOT NULL,
  `nama_pekerjaan` varchar(200) NOT NULL,
  `deskripsi` text NOT NULL,
  `gaji` varchar(100) NOT NULL,
  `tipe_pekerjaan` varchar(50) NOT NULL,
  `status_lowongan` varchar(20) NOT NULL DEFAULT 'Aktif',
  `tanggal_publish` date NOT NULL,
  `gambar_ilustrasi` varchar(255) DEFAULT NULL,
  `tanggal_tutup` date NOT NULL,
  `pendidikan_minimal` varchar(100) DEFAULT NULL,
  `pengalaman_minimal` varchar(100) DEFAULT NULL,
  `tb_admin` int(11) NOT NULL,
  `id_lokasi` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `tb_lowongan`
--

INSERT INTO `tb_lowongan` (`id_lowongan`, `perusahaan`, `nama_pekerjaan`, `deskripsi`, `gaji`, `tipe_pekerjaan`, `status_lowongan`, `tanggal_publish`, `gambar_ilustrasi`, `tanggal_tutup`, `pendidikan_minimal`, `pengalaman_minimal`, `tb_admin`, `id_lokasi`) VALUES
(7, 'Pt mencari cinta sejati', 'CO', 'aa', '20jt', 'Part-Time', 'Aktif', '2025-10-04', NULL, '2025-10-20', 'S1 EKONOMI', '0', 0, 1),
(10, 'PT nusakambangan', 'CO', 'n', '20jt', 'Part-Time', 'Aktif', '2025-10-22', '', '2025-10-04', 'S1 EKONOMI', '5 thn', 0, 3),
(11, 'pt pertamirawr', 'BOS BESAR', 'AUTO KAYA RAYA', '20jt', 'Full-Time', 'Aktif', '2025-10-22', '', '2025-10-22', 'S1 PERMESINAN', '60', 0, 4);

-- --------------------------------------------------------

--
-- Table structure for table `tb_pengguna`
--

CREATE TABLE `tb_pengguna` (
  `id_pengguna` int(11) NOT NULL,
  `nama_pengguna` varchar(100) NOT NULL,
  `email_pengguna` varchar(100) NOT NULL,
  `password_pengguna` varchar(255) NOT NULL,
  `tanggal_daftar` datetime DEFAULT current_timestamp() COMMENT 'Waktu pengguna mendaftar',
  `no_hp` varchar(20) DEFAULT NULL,
  `alamat` text DEFAULT NULL,
  `tanggal_lahir` date DEFAULT NULL,
  `status_login` varchar(10) NOT NULL DEFAULT 'offline' COMMENT 'Nilai: online atau offline',
  `pendidikan_terakhir` varchar(100) DEFAULT NULL,
  `pengalaman_kerja` text DEFAULT NULL,
  `role` enum('user','admin') DEFAULT 'user'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `tb_pengguna`
--

INSERT INTO `tb_pengguna` (`id_pengguna`, `nama_pengguna`, `email_pengguna`, `password_pengguna`, `tanggal_daftar`, `no_hp`, `alamat`, `tanggal_lahir`, `status_login`, `pendidikan_terakhir`, `pengalaman_kerja`, `role`) VALUES
(2, 'Ryco Poetra Widjaya', 'rikoganteng@cruck.com', '$2y$10$lF4g4Dccm05L2A0ZkaptRuTBr0IClG1klqo.c1yR8StGy3saAbpRi', '2025-10-06 05:08:09', NULL, NULL, NULL, 'offline', NULL, NULL, 'user'),
(3, 'keisha jihan syafira', 'syafira@gmail.com', '$2y$10$Ft4SX.gDH2FL6XI/7fSCT.VtRo.DO9sOtPDFri7J3uHYfbr3sV1z6', '2025-10-06 05:13:09', NULL, NULL, NULL, 'offline', NULL, NULL, 'user'),
(6, 'Ryco Poetra Widjaya', 'rikodua@gmail.com', '$2y$10$36SQ2zPsp4oyjjTx46Rd3ebxANiRaSimLhX6WCFKrT19d3GMPQeIO', '2025-10-06 15:51:21', '087861683254', 'Tulungagung', '2008-11-22', 'online', 'SMP', '5', 'user'),
(9, 'Rijal mayo', 'rijal@test.com', '$2y$10$xLn0t/oTox7Li0twrEc5wuv2E2.7US.epmMIbdilStfDG6lzYLM52', '2025-10-07 02:22:29', '0816345578888', 'mars', '1903-03-03', 'offline', 'SD', '-0', 'user'),
(10, 'Bumi', 'Bumiganteng@lol.com', '$2y$10$0gKXIMVGE.zKyUYbzI45/u9PL1olXj7gITKM1VKm1LWQrYadDK4gG', '2025-10-09 03:08:40', '08976534362', 'Mbetak', '2025-10-17', 'offline', 'SMP', '9', 'user'),
(11, 'kiki', 'kiki@gmail.com', '$2y$10$BjfcK9h4D4TOnZ20U5GkzeEe04HW5lGeOsQNpS2QMUMpOW3p/1PS.', '2025-10-20 03:13:47', '081235676543', 'bago sepele ', '2025-10-20', 'online', 'SMA/SMK', '-0', 'user');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `id_lokasi`
--
ALTER TABLE `id_lokasi`
  ADD PRIMARY KEY (`id_lokasi`);

--
-- Indexes for table `tb_admin`
--
ALTER TABLE `tb_admin`
  ADD PRIMARY KEY (`id_admin`),
  ADD UNIQUE KEY `email` (`email`);

--
-- Indexes for table `tb_lamaran`
--
ALTER TABLE `tb_lamaran`
  ADD PRIMARY KEY (`id_lamaran`),
  ADD KEY `fk_lamaran_pengguna` (`id_pengguna`),
  ADD KEY `fk_lamaran_lowongan` (`id_lowongan`);

--
-- Indexes for table `tb_lowongan`
--
ALTER TABLE `tb_lowongan`
  ADD PRIMARY KEY (`id_lowongan`),
  ADD KEY `id_admin` (`tb_admin`),
  ADD KEY `id_lokasi` (`id_lokasi`);

--
-- Indexes for table `tb_pengguna`
--
ALTER TABLE `tb_pengguna`
  ADD PRIMARY KEY (`id_pengguna`),
  ADD UNIQUE KEY `email_pengguna` (`email_pengguna`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `id_lokasi`
--
ALTER TABLE `id_lokasi`
  MODIFY `id_lokasi` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `tb_admin`
--
ALTER TABLE `tb_admin`
  MODIFY `id_admin` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `tb_lamaran`
--
ALTER TABLE `tb_lamaran`
  MODIFY `id_lamaran` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `tb_lowongan`
--
ALTER TABLE `tb_lowongan`
  MODIFY `id_lowongan` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=12;

--
-- AUTO_INCREMENT for table `tb_pengguna`
--
ALTER TABLE `tb_pengguna`
  MODIFY `id_pengguna` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=12;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `tb_lamaran`
--
ALTER TABLE `tb_lamaran`
  ADD CONSTRAINT `fk_lamaran_lowongan` FOREIGN KEY (`id_lowongan`) REFERENCES `tb_lowongan` (`id_lowongan`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_lamaran_pengguna` FOREIGN KEY (`id_pengguna`) REFERENCES `tb_pengguna` (`id_pengguna`) ON DELETE CASCADE;

--
-- Constraints for table `tb_lowongan`
--
ALTER TABLE `tb_lowongan`
  ADD CONSTRAINT `tb_lowongan_ibfk_1` FOREIGN KEY (`tb_admin`) REFERENCES `tb_admin` (`id_admin`),
  ADD CONSTRAINT `tb_lowongan_ibfk_2` FOREIGN KEY (`id_lokasi`) REFERENCES `id_lokasi` (`id_lokasi`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
