-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Waktu pembuatan: 28 Des 2025 pada 05.57
-- Versi server: 10.4.32-MariaDB
-- Versi PHP: 8.2.12

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `rumahsakit`
--

-- --------------------------------------------------------

--
-- Struktur dari tabel `t_bedstatus`
--

CREATE TABLE `t_bedstatus` (
  `f_idbedsts` int(11) NOT NULL,
  `f_idpetugas` int(11) NOT NULL,
  `f_idbed` int(11) NOT NULL,
  `f_sts` enum('Kosong','Terisi','Siap','Pembersihan','Maintenance') NOT NULL,
  `f_waktumulai` datetime NOT NULL,
  `f_waktuselesai` datetime DEFAULT NULL,
  `f_keterangan` varchar(150) NOT NULL,
  `f_created` datetime NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data untuk tabel `t_bedstatus`
--

INSERT INTO `t_bedstatus` (`f_idbedsts`, `f_idpetugas`, `f_idbed`, `f_sts`, `f_waktumulai`, `f_waktuselesai`, `f_keterangan`, `f_created`) VALUES
(1, 1, 2, 'Terisi', '2025-12-13 20:12:49', '2025-12-13 21:16:14', 'Pasien Cimongmong masuk rawat inap. (Ditutup Otomatis oleh Bed Status Update)', '2025-12-13 20:12:49'),
(2, 1, 2, '', '2025-12-13 20:48:19', '2025-12-13 21:16:14', 'Pasien keluar (Pulang) - Bed No.C01 perlu dibersihkan. Catatan: Dipulangkan karena semuanya sudah membaik', '2025-12-13 20:48:19'),
(3, 1, 2, 'Pembersihan', '2025-12-13 21:16:14', '2025-12-13 21:17:06', 'Sedang dibersihkan', '2025-12-13 21:16:14'),
(4, 1, 2, 'Siap', '2025-12-13 21:17:06', '2025-12-13 21:19:41', 'Otomatis: Selesai pembersihan dan siap digunakan.', '2025-12-13 21:17:06'),
(5, 1, 1, 'Terisi', '2025-12-13 21:21:29', '2025-12-25 17:46:28', 'Pasien Kalya Mahar masuk rawat inap.', '2025-12-13 21:21:29'),
(7, 1, 4, 'Siap', '2025-12-24 09:23:18', '2025-12-25 19:02:39', 'Bed baru ditambahkan dengan status Aktif. Otomatis Siap digunakan.', '2025-12-24 09:23:18'),
(8, 1, 5, 'Siap', '2025-12-24 09:23:34', '2025-12-27 14:34:53', 'Bed baru ditambahkan dengan status Aktif. Otomatis Siap digunakan.', '2025-12-24 09:23:34'),
(9, 1, 2, 'Siap', '2025-12-24 09:25:14', '2025-12-24 12:29:42', '', '2025-12-24 09:25:14'),
(10, 4, 2, 'Terisi', '2025-12-24 12:29:42', '2025-12-25 17:46:21', 'Pasien Vino Bastian masuk rawat inap.', '2025-12-24 12:29:42'),
(11, 1, 6, 'Siap', '2025-12-24 12:32:15', '2025-12-25 19:01:01', 'Bed baru ditambahkan dengan status Aktif. Otomatis Siap digunakan.', '2025-12-24 12:32:15'),
(12, 1, 7, 'Siap', '2025-12-25 16:28:51', '2025-12-25 19:38:55', 'Bed baru ditambahkan dengan status Aktif. Otomatis Siap digunakan. (Ditutup Otomatis oleh Update Master Data)', '2025-12-25 16:28:51'),
(13, 1, 8, 'Siap', '2025-12-25 16:48:25', '2025-12-27 14:35:36', 'Bed baru ditambahkan dengan status Aktif. Otomatis Siap digunakan.', '2025-12-25 16:48:25'),
(14, 1, 2, 'Pembersihan', '2025-12-25 17:46:21', '2025-12-25 19:39:50', 'Pasien keluar (Pulang) - Bed No.C01 perlu dibersihkan (Ditutup Otomatis oleh Update Master Data)', '2025-12-25 17:46:21'),
(15, 1, 1, 'Pembersihan', '2025-12-25 17:46:28', '2025-12-27 13:49:23', 'Pasien keluar (Pulang) - Bed No.D01 perlu dibersihkan', '2025-12-25 17:46:28'),
(16, 1, 6, 'Terisi', '2025-12-25 19:01:01', '2025-12-27 13:48:24', 'Pasien masuk rawat inap (Request: Kelas 1, Ditempatkan: Kelas 2)', '2025-12-25 19:01:01'),
(17, 1, 4, 'Terisi', '2025-12-25 19:02:39', '2025-12-27 13:59:30', 'Pasien masuk rawat inap (Request: VIP, Ditempatkan: VIP)', '2025-12-25 19:02:39'),
(18, 1, 2, 'Maintenance', '2025-12-25 19:39:50', '2025-12-25 19:41:54', 'Master Data diubah menjadi Maintenance. Bed dalam perbaikan fisik berat. (Ditutup Otomatis oleh Update Master Data)', '2025-12-25 19:39:50'),
(19, 1, 2, 'Siap', '2025-12-25 19:41:54', '2025-12-27 14:34:57', 'Master Data diubah menjadi Aktif dari Maintenance. Bed siap digunakan.', '2025-12-25 19:41:54'),
(20, 1, 9, 'Siap', '2025-12-25 19:47:22', '2025-12-27 14:35:14', 'Bed baru ditambahkan dengan status Aktif. Otomatis Siap digunakan.', '2025-12-25 19:47:22'),
(21, 1, 10, 'Siap', '2025-12-26 11:18:50', '2025-12-27 14:35:10', 'Bed baru ditambahkan dengan status Aktif. Otomatis Siap digunakan.', '2025-12-26 11:18:50'),
(22, 1, 11, 'Siap', '2025-12-27 10:50:58', '2025-12-27 14:35:08', 'Bed baru ditambahkan dengan status Aktif. Otomatis Siap digunakan.', '2025-12-27 10:50:58'),
(23, 1, 12, 'Siap', '2025-12-27 10:52:27', '2025-12-27 14:35:05', 'Bed baru ditambahkan dengan status Aktif. Otomatis Siap digunakan.', '2025-12-27 10:52:27'),
(24, 1, 13, 'Siap', '2025-12-27 11:01:28', '2025-12-27 14:35:02', 'Bed baru ditambahkan dengan status Aktif. Otomatis Siap digunakan.', '2025-12-27 11:01:28'),
(25, 1, 6, 'Pembersihan', '2025-12-27 13:48:24', '2025-12-27 14:30:03', 'Pasien keluar (Pindah) - Bed No.D02 perlu dibersihkan', '2025-12-27 13:48:24'),
(26, 5, 1, 'Siap', '2025-12-27 13:49:23', '2025-12-27 14:34:43', 'Pembersihan selesai dilakukan oleh petugas kebersihan', '2025-12-27 13:49:23'),
(27, 1, 4, 'Pembersihan', '2025-12-27 13:59:30', '2025-12-27 14:38:30', 'Pasien keluar (Pulang) - Bed No.B01 perlu dibersihkan', '2025-12-27 13:59:30'),
(28, 5, 6, 'Siap', '2025-12-27 14:30:03', '2025-12-27 14:33:52', 'Pembersihan selesai dilakukan oleh petugas kebersihan', '2025-12-27 14:30:03'),
(29, 5, 4, 'Siap', '2025-12-27 14:38:30', '2025-12-27 14:39:12', 'Pembersihan selesai dilakukan oleh petugas kebersihan', '2025-12-27 14:38:30'),
(30, 1, 9, 'Terisi', '2025-12-27 14:49:39', '2025-12-27 14:51:22', 'Pasien masuk rawat inap (Request: Kelas 3, Ditempatkan: Kelas 3)', '2025-12-27 14:49:39'),
(31, 1, 10, 'Terisi', '2025-12-27 14:49:57', '2025-12-27 15:18:53', 'Pasien masuk rawat inap (Request: Kelas 3, Ditempatkan: Kelas 3)', '2025-12-27 14:49:57'),
(32, 1, 9, 'Pembersihan', '2025-12-27 14:51:22', '2025-12-27 14:52:12', 'Pasien keluar (Pulang) - Bed No.E01 perlu dibersihkan', '2025-12-27 14:51:22'),
(33, 5, 9, 'Siap', '2025-12-27 14:52:12', '2025-12-27 16:25:15', 'Pembersihan selesai dilakukan oleh petugas kebersihan', '2025-12-27 14:52:12'),
(34, 1, 5, 'Terisi', '2025-12-27 15:18:41', '2025-12-28 07:49:36', 'Pasien masuk rawat inap (Request: VVIP, Ditempatkan: VVIP)', '2025-12-27 15:18:41'),
(35, 1, 10, 'Pembersihan', '2025-12-27 15:18:53', NULL, 'Pasien keluar (Pulang) - Bed No.E02 perlu dibersihkan', '2025-12-27 15:18:53'),
(36, 1, 9, 'Maintenance', '2025-12-27 16:25:15', NULL, 'Sedang diperbaiki', '2025-12-27 16:25:15'),
(37, 5, 5, 'Pembersihan', '2025-12-28 07:49:36', '2025-12-28 08:00:22', 'Pasien keluar (Pulang) - Bed No.A01 perlu dibersihkan', '2025-12-28 07:49:36'),
(38, 5, 5, 'Siap', '2025-12-28 08:00:22', NULL, 'Pembersihan selesai dilakukan oleh Joni Dash pada 28/12/2025 08:00:22', '2025-12-28 08:00:22');

-- --------------------------------------------------------

--
-- Struktur dari tabel `t_pasien`
--

CREATE TABLE `t_pasien` (
  `f_idpasien` int(11) NOT NULL,
  `f_norekmed` varchar(150) NOT NULL,
  `f_nama` varchar(150) NOT NULL,
  `f_tgllahir` date NOT NULL,
  `f_jnskelamin` enum('Laki-laki','Perempuan') NOT NULL,
  `f_notlp` varchar(11) NOT NULL,
  `f_alamat` varchar(150) NOT NULL,
  `f_created` datetime NOT NULL,
  `f_updated` datetime NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data untuk tabel `t_pasien`
--

INSERT INTO `t_pasien` (`f_idpasien`, `f_norekmed`, `f_nama`, `f_tgllahir`, `f_jnskelamin`, `f_notlp`, `f_alamat`, `f_created`, `f_updated`) VALUES
(1, 'RM-2025-001', 'Cimongmong', '2019-05-13', 'Perempuan', '2147483647', 'Jl. Turagangga Balik No 24B', '2025-12-13 19:54:12', '2025-12-13 19:54:12'),
(2, 'RM-2025-002', 'Kalya Mahar', '2005-01-01', 'Perempuan', '2147483647', 'JL. Baru Lintang No 29A', '2025-12-13 19:55:31', '2025-12-13 19:55:31'),
(3, 'RM-2025-003', 'Vino Bastian', '2005-08-04', 'Laki-laki', '2147483647', 'JL. PangkalKaya Baru Delima No 44', '2025-12-13 20:09:15', '2025-12-27 12:13:54'),
(4, 'RM-2025-004', 'Janeh Tum', '1978-01-28', 'Perempuan', '2147483647', 'Jl. Bebek Goreng', '2025-12-24 09:29:05', '2025-12-24 09:29:05'),
(5, 'RM-2025-005', 'Dinda Dindoy', '2005-02-21', 'Perempuan', '2147483647', 'Jl, Gaya Baru Pisangan', '2025-12-24 12:29:08', '2025-12-27 12:43:40'),
(7, 'RM-2025-006', 'Alvaro Brajendra', '2004-12-13', 'Laki-laki', '08887865123', 'Jl. Sayonara Naro', '2025-12-27 13:13:52', '2025-12-27 13:15:23');

-- --------------------------------------------------------

--
-- Struktur dari tabel `t_petugas`
--

CREATE TABLE `t_petugas` (
  `f_idpetugas` int(11) NOT NULL,
  `f_nama` varchar(150) NOT NULL,
  `f_username` varchar(150) NOT NULL,
  `f_password` varchar(150) NOT NULL,
  `f_role` enum('Admin','Admisi','Perawat','Petugas Kebersihan') NOT NULL,
  `f_unitkerja` varchar(150) NOT NULL,
  `f_created` datetime NOT NULL,
  `f_updated` datetime NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data untuk tabel `t_petugas`
--

INSERT INTO `t_petugas` (`f_idpetugas`, `f_nama`, `f_username`, `f_password`, `f_role`, `f_unitkerja`, `f_created`, `f_updated`) VALUES
(1, 'Nazwa Putri', 'Nazwa', '12345', 'Admin', '', '0000-00-00 00:00:00', '0000-00-00 00:00:00'),
(2, 'Aisyah Septiani', 'Aisyah', '12345', 'Admin', '', '0000-00-00 00:00:00', '0000-00-00 00:00:00'),
(3, 'Rafael William', 'Rafafa', '$2y$10$YTvOwrZZBSEonp0Qxspl9uuaTBOS9K8GTJ2bh.tFo5bfLPBPi9bMi', 'Perawat', 'Lantai 5', '2025-12-13 19:50:41', '2025-12-24 09:14:36'),
(4, 'Ayara Asheka', 'Ayaraya', '$2y$10$6aGrnvtPT4Y3kb2XMt21DOoXJr/RXJ5MwhkOJVyL.otqDVUnyiyA.', 'Admisi', 'Lantai 5', '2025-12-24 09:17:21', '2025-12-24 09:17:21'),
(5, 'Joni Dash', 'JonDash', '$2y$10$ZyIxNGWRsIvcKbtRbu.nVuNUtC00kkDtOwRly7HR4FODUu31qBuqq', 'Petugas Kebersihan', 'Lantai 5', '2025-12-24 09:18:26', '2025-12-26 10:47:46'),
(6, 'Kalyaa', 'kalyaa', '$2y$10$ZW0AnnExHNXsefOpRg9C1OcDxZP7aTVeGZZ52Le5tcKFFGv6E0Oga', 'Admisi', 'Lantai 3', '2025-12-26 20:19:59', '2025-12-26 20:20:17'),
(7, 'Zosep', 'Zoysep', '$2y$10$tLPWLJmBSOPtPy47axmoFOURxoc.olSk38FDZMl4fQrrCbqbxaUZm', 'Petugas Kebersihan', 'Lantai 3', '2025-12-27 15:48:36', '2025-12-27 15:48:36'),
(8, 'Meyra', 'Meymey', '$2y$10$oFBFO9O9QLvLZ5W862BsFuRDSPWGSqBXg0qHAQnKl1gr.dx91KEm.', 'Perawat', 'Lantai 3', '2025-12-27 15:49:11', '2025-12-27 15:49:11'),
(9, 'Nazira', 'Zirauhuyy', '$2y$10$sSAdp7jgaaIQO2AvJ8y3WeRnDIJnVqeGLmCVEh5O9q.yVlLdRvSpC', 'Perawat', 'Lantai 4', '2025-12-27 15:49:51', '2025-12-27 15:49:51'),
(10, 'Avril', 'Avril Brototot', '$2y$10$5NJ7qIoU3TN4Di0uKYIkFuxH8Joz2Oogx/6FdYtTnKuc5LUSTobDG', 'Admisi', 'Lantai 4', '2025-12-27 15:50:21', '2025-12-27 15:50:21'),
(11, 'Byron', 'Biyorn', '$2y$10$tDFwXRbavN3p4raYJfY.ueKylOcIEy5xMAIbGhCCs2uQXfRAf27Y2', 'Petugas Kebersihan', 'Lantai 4', '2025-12-27 15:50:56', '2025-12-27 15:50:56'),
(12, 'Dinda', 'dindin', '$2y$10$mVZaV2ZhApcVdApS/5NAueYZP7ti5djjEtd42NcFIxKqUA2J7w.hu', 'Perawat', 'Lantai 2', '2025-12-27 15:54:06', '2025-12-27 15:54:06'),
(13, 'Aisyohh', 'Aiaisyah', '$2y$10$jghFIkAEFlTRd7LOrZFPDutF4ceqFWNVnIosrOq78MR5NzQ2KiDEa', 'Admisi', 'Lantai 2', '2025-12-27 15:54:39', '2025-12-27 15:54:39'),
(14, 'bir', 'bir', '$2y$10$SUQ0zmUdFkWYIE3Ilwpa3.t7y943qtnuRK5qSywmnY.X..uCpyXAa', 'Petugas Kebersihan', 'Lantai 2', '2025-12-27 15:55:13', '2025-12-27 15:55:13'),
(15, 'Alea', 'lealea', '$2y$10$Is2D0rC1qj7MFljtn3sZq.VQ2P6Ln1UW4q44RiNy2/sBjGnZE1zR6', 'Perawat', 'Lantai 1', '2025-12-27 15:55:59', '2025-12-27 15:55:59'),
(16, 'Aylaa', 'ayyla', '$2y$10$ZGF3rAWAIDwcU3mVw2hlqu/musBRz5EH3tEfOh5dtlIHmUBvt8WNG', 'Admisi', 'Lantai 1', '2025-12-27 15:56:33', '2025-12-27 15:56:33'),
(17, 'kiri', 'kiri', '$2y$10$A10S/ryNocNQl3ORFn8pReoNrQwver5.IaTOBNA6lI6SbOMyQj9SW', 'Petugas Kebersihan', 'Lantai 1', '2025-12-27 15:56:57', '2025-12-27 15:57:28');

-- --------------------------------------------------------

--
-- Struktur dari tabel `t_rawatinap`
--

CREATE TABLE `t_rawatinap` (
  `f_idrawatinap` int(11) NOT NULL,
  `f_idpasien` int(11) NOT NULL,
  `f_idbed` int(11) NOT NULL,
  `f_idpetugas` int(11) NOT NULL,
  `f_kelas_diminta` enum('VVIP','VIP','Kelas 1','Kelas 2','Kelas 3') NOT NULL,
  `f_kelas_ditempatkan` enum('VVIP','VIP','Kelas 1','Kelas 2','Kelas 3') DEFAULT NULL,
  `f_status_penempatan` enum('langsung','upgrade','downgrade','menunggu') NOT NULL,
  `f_waktumasuk` datetime NOT NULL,
  `f_waktukeluar` datetime DEFAULT NULL,
  `f_stsbersih` enum('Siap','Kotor') NOT NULL,
  `f_alasan` enum('Pulang','Pindah','Meninggal') DEFAULT NULL,
  `f_created` datetime NOT NULL,
  `f_updated` datetime NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data untuk tabel `t_rawatinap`
--

INSERT INTO `t_rawatinap` (`f_idrawatinap`, `f_idpasien`, `f_idbed`, `f_idpetugas`, `f_kelas_diminta`, `f_kelas_ditempatkan`, `f_status_penempatan`, `f_waktumasuk`, `f_waktukeluar`, `f_stsbersih`, `f_alasan`, `f_created`, `f_updated`) VALUES
(1, 1, 2, 1, 'Kelas 1', 'Kelas 1', 'langsung', '2025-12-13 20:12:49', '2025-12-13 20:48:19', 'Siap', 'Pulang', '2025-12-13 20:12:49', '2025-12-13 20:48:19'),
(2, 2, 1, 1, 'Kelas 2', 'Kelas 2', 'langsung', '2025-12-13 21:21:29', '2025-12-25 17:46:28', 'Siap', 'Pulang', '2025-12-13 21:21:29', '2025-12-27 13:49:23'),
(3, 3, 2, 4, 'Kelas 1', 'Kelas 1', 'langsung', '2025-12-24 12:29:42', '2025-12-25 17:46:21', 'Siap', 'Pulang', '2025-12-24 12:29:42', '2025-12-25 17:46:21'),
(4, 4, 6, 1, 'Kelas 1', 'Kelas 2', 'downgrade', '2025-12-25 19:01:01', '2025-12-27 13:48:24', 'Siap', 'Pindah', '2025-12-25 19:01:01', '2025-12-27 14:30:03'),
(5, 5, 4, 1, 'VIP', 'VIP', 'langsung', '2025-12-25 19:02:39', '2025-12-27 13:59:30', 'Siap', 'Pulang', '2025-12-25 19:02:39', '2025-12-27 14:38:30'),
(6, 5, 9, 1, 'Kelas 3', 'Kelas 3', 'langsung', '2025-12-27 14:49:39', '2025-12-27 14:51:22', 'Siap', 'Pulang', '2025-12-27 14:49:39', '2025-12-27 14:52:12'),
(7, 4, 10, 1, 'Kelas 3', 'Kelas 3', 'langsung', '2025-12-27 14:49:57', '2025-12-27 15:18:53', 'Kotor', 'Pulang', '2025-12-27 14:49:57', '2025-12-27 15:18:53'),
(8, 5, 5, 1, 'VVIP', 'VVIP', 'langsung', '2025-12-27 15:18:41', '2025-12-28 07:49:36', 'Siap', 'Pulang', '2025-12-27 15:18:41', '2025-12-28 07:49:36');

-- --------------------------------------------------------

--
-- Struktur dari tabel `t_ruangan`
--

CREATE TABLE `t_ruangan` (
  `f_idruangan` int(11) NOT NULL,
  `f_nama` varchar(150) NOT NULL,
  `f_kelas` enum('VVIP','VIP','Kelas 1','Kelas 2','Kelas 3') NOT NULL,
  `f_lantai` int(11) NOT NULL,
  `f_kapasitas` int(11) NOT NULL,
  `f_created` datetime NOT NULL,
  `f_updated` datetime NOT NULL,
  `f_luasruangan` decimal(6,2) DEFAULT NULL,
  `f_luasperbed` decimal(5,2) DEFAULT NULL,
  `f_faktorefisiensi` decimal(3,2) NOT NULL,
  `f_kapasitasmaks` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data untuk tabel `t_ruangan`
--

INSERT INTO `t_ruangan` (`f_idruangan`, `f_nama`, `f_kelas`, `f_lantai`, `f_kapasitas`, `f_created`, `f_updated`, `f_luasruangan`, `f_luasperbed`, `f_faktorefisiensi`, `f_kapasitasmaks`) VALUES
(1, 'Ruang Lily of The Valley', 'VVIP', 5, 1, '2025-12-13 19:45:40', '2025-12-25 18:11:03', 50.00, 18.00, 0.70, 1),
(2, 'Ruang Hydrangea', 'VIP', 4, 1, '2025-12-13 19:46:37', '2025-12-25 18:09:34', 30.00, 12.00, 0.70, 1),
(3, 'Ruang Edelweiss', 'Kelas 1', 3, 2, '2025-12-13 19:48:02', '2025-12-25 17:57:05', 40.00, 10.00, 0.70, 2),
(4, 'Ruang Tulip', 'Kelas 2', 2, 4, '2025-12-13 19:48:56', '2025-12-25 17:56:02', 60.00, 8.00, 0.65, 4),
(5, 'Melati', 'Kelas 3', 1, 6, '2025-12-25 15:12:24', '2025-12-25 15:12:24', 56.00, NULL, 0.65, 6),
(6, 'Anggrek', 'Kelas 3', 1, 6, '2025-12-25 15:53:41', '2025-12-25 15:53:41', 56.00, 6.00, 0.65, 6);

-- --------------------------------------------------------

--
-- Struktur dari tabel `t_standar_luasbed`
--

CREATE TABLE `t_standar_luasbed` (
  `f_idstandar` int(11) NOT NULL,
  `f_kelas` varchar(20) NOT NULL,
  `f_luasmin` decimal(5,2) NOT NULL COMMENT 'Luas minimum per bed (m²)',
  `f_luasmaks` decimal(5,2) NOT NULL COMMENT 'Luas maksimum per bed (m²)',
  `f_faktorefisiensi` decimal(3,2) DEFAULT 0.65 COMMENT 'Faktor efisiensi default',
  `f_keterangan` text DEFAULT NULL,
  `f_created` datetime NOT NULL,
  `f_updated` datetime NOT NULL,
  `f_jarakminbed` decimal(4,2) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data untuk tabel `t_standar_luasbed`
--

INSERT INTO `t_standar_luasbed` (`f_idstandar`, `f_kelas`, `f_luasmin`, `f_luasmaks`, `f_faktorefisiensi`, `f_keterangan`, `f_created`, `f_updated`, `f_jarakminbed`) VALUES
(1, 'Kelas 3', 6.00, 8.00, 0.65, 'Multi-bed (4-6 bed per ruang) - Standar Kemenkes & KRIS minimal', '2025-12-25 14:37:23', '2025-12-25 14:37:23', 1.50),
(2, 'Kelas 2', 8.00, 10.00, 0.65, 'Semi-private (2-4 bed per ruang) - Standar Kemenkes & Semi-private untuk jarak', '2025-12-25 14:37:23', '2025-12-25 14:37:23', 1.80),
(3, 'Kelas 1', 10.00, 12.00, 0.70, 'Semi-private (1-2 bed per ruang) - Standar Kemenkes, untuk jarak privasi tinggi', '2025-12-25 14:37:23', '2025-12-25 14:37:23', 2.00),
(4, 'VIP', 12.00, 14.00, 0.70, 'Private room (1 bed) - Standar RS', '2025-12-25 14:37:23', '2025-12-25 14:37:23', 2.50),
(5, 'VVIP', 18.00, 20.00, 0.70, 'Suite premium (1 bed) - Standar RS', '2025-12-25 14:37:23', '2025-12-25 14:37:23', 3.00);

-- --------------------------------------------------------

--
-- Struktur dari tabel `t_tempattidur`
--

CREATE TABLE `t_tempattidur` (
  `f_idbed` int(11) NOT NULL,
  `f_idruangan` int(11) NOT NULL,
  `f_nomorbed` varchar(150) NOT NULL,
  `f_stsfisik` enum('Aktif','Nonaktif','Maintenance') NOT NULL,
  `f_created` datetime NOT NULL,
  `f_updated` datetime NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data untuk tabel `t_tempattidur`
--

INSERT INTO `t_tempattidur` (`f_idbed`, `f_idruangan`, `f_nomorbed`, `f_stsfisik`, `f_created`, `f_updated`) VALUES
(1, 4, 'D01', 'Aktif', '2025-12-13 20:11:44', '2025-12-27 14:34:43'),
(2, 3, 'C01', 'Aktif', '2025-12-13 20:12:00', '2025-12-27 14:34:57'),
(4, 2, 'B01', 'Aktif', '2025-12-24 09:23:18', '2025-12-27 14:39:12'),
(5, 1, 'A01', 'Aktif', '2025-12-24 09:23:34', '2025-12-27 14:34:53'),
(6, 4, 'D02', 'Aktif', '2025-12-24 12:32:15', '2025-12-27 14:33:52'),
(7, 4, 'D03', 'Aktif', '2025-12-25 16:28:51', '2025-12-25 19:38:55'),
(8, 4, 'D04', 'Aktif', '2025-12-25 16:48:25', '2025-12-27 14:35:36'),
(9, 6, 'E01', 'Maintenance', '2025-12-25 19:47:22', '2025-12-27 16:25:15'),
(10, 6, 'E02', 'Aktif', '2025-12-26 11:18:50', '2025-12-27 14:35:10'),
(11, 6, 'E03', 'Aktif', '2025-12-27 10:50:58', '2025-12-27 14:35:08'),
(12, 6, 'E04', 'Aktif', '2025-12-27 10:52:27', '2025-12-27 14:35:05'),
(13, 6, 'E05', 'Aktif', '2025-12-27 11:01:28', '2025-12-27 14:35:02');

--
-- Indexes for dumped tables
--

--
-- Indeks untuk tabel `t_bedstatus`
--
ALTER TABLE `t_bedstatus`
  ADD PRIMARY KEY (`f_idbedsts`),
  ADD KEY `f_idpetugas` (`f_idpetugas`,`f_idbed`),
  ADD KEY `f_idbed` (`f_idbed`);

--
-- Indeks untuk tabel `t_pasien`
--
ALTER TABLE `t_pasien`
  ADD PRIMARY KEY (`f_idpasien`);

--
-- Indeks untuk tabel `t_petugas`
--
ALTER TABLE `t_petugas`
  ADD PRIMARY KEY (`f_idpetugas`);

--
-- Indeks untuk tabel `t_rawatinap`
--
ALTER TABLE `t_rawatinap`
  ADD PRIMARY KEY (`f_idrawatinap`),
  ADD KEY `f_idpasien` (`f_idpasien`,`f_idbed`,`f_idpetugas`),
  ADD KEY `f_idpetugas` (`f_idpetugas`),
  ADD KEY `f_idbed` (`f_idbed`);

--
-- Indeks untuk tabel `t_ruangan`
--
ALTER TABLE `t_ruangan`
  ADD PRIMARY KEY (`f_idruangan`);

--
-- Indeks untuk tabel `t_standar_luasbed`
--
ALTER TABLE `t_standar_luasbed`
  ADD PRIMARY KEY (`f_idstandar`),
  ADD UNIQUE KEY `f_kelas` (`f_kelas`);

--
-- Indeks untuk tabel `t_tempattidur`
--
ALTER TABLE `t_tempattidur`
  ADD PRIMARY KEY (`f_idbed`),
  ADD KEY `f_idruangan` (`f_idruangan`);

--
-- AUTO_INCREMENT untuk tabel yang dibuang
--

--
-- AUTO_INCREMENT untuk tabel `t_bedstatus`
--
ALTER TABLE `t_bedstatus`
  MODIFY `f_idbedsts` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=39;

--
-- AUTO_INCREMENT untuk tabel `t_pasien`
--
ALTER TABLE `t_pasien`
  MODIFY `f_idpasien` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT untuk tabel `t_petugas`
--
ALTER TABLE `t_petugas`
  MODIFY `f_idpetugas` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=18;

--
-- AUTO_INCREMENT untuk tabel `t_rawatinap`
--
ALTER TABLE `t_rawatinap`
  MODIFY `f_idrawatinap` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT untuk tabel `t_ruangan`
--
ALTER TABLE `t_ruangan`
  MODIFY `f_idruangan` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT untuk tabel `t_standar_luasbed`
--
ALTER TABLE `t_standar_luasbed`
  MODIFY `f_idstandar` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT untuk tabel `t_tempattidur`
--
ALTER TABLE `t_tempattidur`
  MODIFY `f_idbed` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=14;

--
-- Ketidakleluasaan untuk tabel pelimpahan (Dumped Tables)
--

--
-- Ketidakleluasaan untuk tabel `t_bedstatus`
--
ALTER TABLE `t_bedstatus`
  ADD CONSTRAINT `t_bedstatus_ibfk_1` FOREIGN KEY (`f_idbed`) REFERENCES `t_tempattidur` (`f_idbed`) ON UPDATE CASCADE,
  ADD CONSTRAINT `t_bedstatus_ibfk_2` FOREIGN KEY (`f_idpetugas`) REFERENCES `t_petugas` (`f_idpetugas`) ON UPDATE CASCADE;

--
-- Ketidakleluasaan untuk tabel `t_rawatinap`
--
ALTER TABLE `t_rawatinap`
  ADD CONSTRAINT `t_rawatinap_ibfk_1` FOREIGN KEY (`f_idpetugas`) REFERENCES `t_petugas` (`f_idpetugas`) ON UPDATE CASCADE,
  ADD CONSTRAINT `t_rawatinap_ibfk_2` FOREIGN KEY (`f_idpasien`) REFERENCES `t_pasien` (`f_idpasien`) ON UPDATE CASCADE,
  ADD CONSTRAINT `t_rawatinap_ibfk_3` FOREIGN KEY (`f_idbed`) REFERENCES `t_tempattidur` (`f_idbed`) ON UPDATE CASCADE;

--
-- Ketidakleluasaan untuk tabel `t_tempattidur`
--
ALTER TABLE `t_tempattidur`
  ADD CONSTRAINT `t_tempattidur_ibfk_1` FOREIGN KEY (`f_idruangan`) REFERENCES `t_ruangan` (`f_idruangan`) ON UPDATE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
