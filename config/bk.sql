-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Jan 06, 2026 at 12:49 PM
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
-- Database: `bk`
--

-- --------------------------------------------------------

--
-- Table structure for table `detail_keluarga_siswa`
--

CREATE TABLE `detail_keluarga_siswa` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `id_siswa` bigint(20) UNSIGNED NOT NULL,
  `nama_ayah` varchar(100) DEFAULT NULL,
  `pekerjaan_ayah` varchar(100) DEFAULT NULL,
  `nama_ibu` varchar(100) DEFAULT NULL,
  `pekerjaan_ibu` varchar(100) DEFAULT NULL,
  `status_ekonomi` varchar(50) DEFAULT NULL,
  `jumlah_saudara` int(11) DEFAULT NULL,
  `alamat` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `detail_keluarga_siswa`
--

INSERT INTO `detail_keluarga_siswa` (`id`, `id_siswa`, `nama_ayah`, `pekerjaan_ayah`, `nama_ibu`, `pekerjaan_ibu`, `status_ekonomi`, `jumlah_saudara`, `alamat`) VALUES
(1, 1, 's', 's', 's', 's', 'Mampu', 2, 's'),
(2, 2, 's', 's', 's', 's', 'Mampu', 2, 's');

-- --------------------------------------------------------

--
-- Table structure for table `hasil_asesmen`
--

CREATE TABLE `hasil_asesmen` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `id_siswa` bigint(20) UNSIGNED NOT NULL,
  `kategori` enum('gaya_belajar','minat_karir','kepribadian','kesehatan_mental') NOT NULL,
  `ringkasan_hasil` text DEFAULT NULL,
  `skor` varchar(255) DEFAULT NULL,
  `terakhir_diperbarui` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `hasil_asesmen`
--

INSERT INTO `hasil_asesmen` (`id`, `id_siswa`, `kategori`, `ringkasan_hasil`, `skor`, `terakhir_diperbarui`) VALUES
(1, 1, 'kepribadian', '{\"q1_status_ortu\":\"Lengkap\",\"q2_status_ortu\":\"Ramai\",\"q3_status_ortu\":\"Ya\",\"q4_status_ortu\":\"Jalan Kaki\"}', '-', '2026-01-06 11:17:25'),
(2, 1, 'gaya_belajar', '{\"q1_gaya_belajar\":\"Auditori\",\"q2_gaya_belajar\":\"Visual\",\"q3_gaya_belajar\":\"Visual\",\"q4_gaya_belajar\":\"Kinestetik\"}', 'Visual Dominan', '2026-01-06 11:17:25'),
(3, 1, 'kesehatan_mental', '{\"q1_nyaman_teman\":\"Ya\",\"q2_cemas\":\"Tidak\",\"q3_cerita\":\"Ya\",\"q4_tekanan_akademik\":\"Ya\",\"q5_bullying\":\"Tidak\"}', 'Stabil', '2026-01-06 11:17:38'),
(4, 1, 'minat_karir', '{\"rencana_lulus\":\"Sekolah Kedinasan\",\"mapel_favorit\":[\"Matematika\",\"Olahraga\",\"Bahasa Indonesia\"],\"minat_pekerjaan\":\"Sosial & Hukum\"}', 'Sekolah Kedinasan', '2026-01-06 11:17:38'),
(5, 2, 'kepribadian', '{\"q1_status_ortu\":\"Lengkap\",\"q2_status_ortu\":\"Ramai\",\"q3_status_ortu\":\"Tidak\",\"q4_status_ortu\":\"Jalan Kaki\"}', '-', '2026-01-06 11:18:21'),
(6, 2, 'gaya_belajar', '{\"q1_gaya_belajar\":\"Visual\",\"q2_gaya_belajar\":\"Visual\",\"q3_gaya_belajar\":\"Visual\",\"q4_gaya_belajar\":\"Visual\"}', 'Visual Dominan', '2026-01-06 11:18:21'),
(7, 2, 'kesehatan_mental', '{\"q1_nyaman_teman\":\"Ya\",\"q2_cemas\":\"Ya\",\"q3_cerita\":\"Ya\",\"q4_tekanan_akademik\":\"Ya\",\"q5_bullying\":\"Ya\"}', 'PERLU PERHATIAN KHUSUS (Bullying)', '2026-01-06 11:18:30'),
(8, 2, 'minat_karir', '{\"rencana_lulus\":\"Kerja/Wirausaha\",\"mapel_favorit\":[\"Olahraga\",\"KK\",\"Agama\"],\"minat_pekerjaan\":\"Seni & Kreatif\"}', 'Kerja/Wirausaha', '2026-01-06 11:18:30');

-- --------------------------------------------------------

--
-- Table structure for table `konselor`
--

CREATE TABLE `konselor` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `id_pengguna` bigint(20) UNSIGNED NOT NULL,
  `nip` varchar(30) NOT NULL,
  `nama_lengkap` varchar(100) NOT NULL,
  `spesialisasi` varchar(100) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `konselor`
--

INSERT INTO `konselor` (`id`, `id_pengguna`, `nip`, `nama_lengkap`, `spesialisasi`) VALUES
(1, 2, '6001', 'counselortest', 'Konselor Umum');

-- --------------------------------------------------------

--
-- Table structure for table `konsultasi`
--

CREATE TABLE `konsultasi` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `id_siswa` bigint(20) UNSIGNED NOT NULL,
  `id_konselor` bigint(20) UNSIGNED NOT NULL,
  `kategori_topik` varchar(50) NOT NULL,
  `deskripsi_keluhan` text NOT NULL,
  `tanggal_konsultasi` datetime NOT NULL,
  `status` enum('menunggu','disetujui','ditolak','dijadwalkan_ulang','selesai') DEFAULT 'menunggu',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `konsultasi`
--

INSERT INTO `konsultasi` (`id`, `id_siswa`, `id_konselor`, `kategori_topik`, `deskripsi_keluhan`, `tanggal_konsultasi`, `status`, `created_at`) VALUES
(1, 2, 1, 'Pribadi', 'tes', '2026-01-09 16:44:00', 'disetujui', '2026-01-06 11:43:31'),
(2, 1, 1, 'Sosial', 'p', '2026-01-14 20:49:00', 'disetujui', '2026-01-06 11:45:40');

-- --------------------------------------------------------

--
-- Table structure for table `laporan_konsultasi`
--

CREATE TABLE `laporan_konsultasi` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `id_konsultasi` bigint(20) UNSIGNED NOT NULL,
  `inti_masalah` text NOT NULL,
  `solusi_diberikan` text NOT NULL,
  `perlu_tindak_lanjut` tinyint(1) DEFAULT 0,
  `catatan_rahasia` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `siswa`
--

CREATE TABLE `siswa` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `id_pengguna` bigint(20) UNSIGNED NOT NULL,
  `nis` varchar(20) NOT NULL,
  `nama_lengkap` varchar(100) NOT NULL,
  `tingkat_kelas` int(11) NOT NULL,
  `jurusan` varchar(50) NOT NULL,
  `jenis_kelamin` enum('L','P') NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `siswa`
--

INSERT INTO `siswa` (`id`, `id_pengguna`, `nis`, `nama_lengkap`, `tingkat_kelas`, `jurusan`, `jenis_kelamin`) VALUES
(1, 3, '1001', 'siswa 1', 11, 'RPL', 'L'),
(2, 4, '1002', 'siswa 2', 10, 'TKL', 'L');

-- --------------------------------------------------------

--
-- Table structure for table `user`
--

CREATE TABLE `user` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `email` varchar(255) NOT NULL,
  `kata_sandi` varchar(255) NOT NULL,
  `peran` enum('admin','siswa','konselor') NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `user`
--

INSERT INTO `user` (`id`, `email`, `kata_sandi`, `peran`, `created_at`) VALUES
(1, 'rickymarove@gmail.com', '$2a$12$Njj20/FcS0J7J66e.Nq/tuBaF56T.3EL/L2NtuMHvzjrHH3MA5.7e', 'admin', '2026-01-06 11:14:41'),
(2, 'counselortest@gmail.com', '$2y$10$bNFfH.CowmdmnFwDeB1iYeEFXtSKJOD0KaRRslMXwNLcFMDIIGoDW', 'konselor', '2026-01-06 11:15:15'),
(3, 'siswa@gmail.com', '$2y$10$9DAukUv6xb0.nHAHFUMC6eknAs5uT7yQMtfy7u5mKPJGsjfCu3/ai', 'siswa', '2026-01-06 11:15:38'),
(4, 'siswa2@gmail.com', '$2y$10$w1YC0Z6SSCCn/Y5JX/70CuvclpvfzJxW1XfZs/W9BktTqXI/iJuz.', 'siswa', '2026-01-06 11:17:58');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `detail_keluarga_siswa`
--
ALTER TABLE `detail_keluarga_siswa`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_detail_keluarga_siswa` (`id_siswa`);

--
-- Indexes for table `hasil_asesmen`
--
ALTER TABLE `hasil_asesmen`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_hasil_asesmen_siswa` (`id_siswa`);

--
-- Indexes for table `konselor`
--
ALTER TABLE `konselor`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `nip` (`nip`),
  ADD KEY `fk_konselor_user` (`id_pengguna`);

--
-- Indexes for table `konsultasi`
--
ALTER TABLE `konsultasi`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_konsultasi_siswa` (`id_siswa`),
  ADD KEY `fk_konsultasi_konselor` (`id_konselor`);

--
-- Indexes for table `laporan_konsultasi`
--
ALTER TABLE `laporan_konsultasi`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_laporan_konsultasi` (`id_konsultasi`);

--
-- Indexes for table `siswa`
--
ALTER TABLE `siswa`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `nis` (`nis`),
  ADD KEY `fk_siswa_user` (`id_pengguna`);

--
-- Indexes for table `user`
--
ALTER TABLE `user`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `email` (`email`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `detail_keluarga_siswa`
--
ALTER TABLE `detail_keluarga_siswa`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `hasil_asesmen`
--
ALTER TABLE `hasil_asesmen`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT for table `konselor`
--
ALTER TABLE `konselor`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `konsultasi`
--
ALTER TABLE `konsultasi`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `laporan_konsultasi`
--
ALTER TABLE `laporan_konsultasi`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `siswa`
--
ALTER TABLE `siswa`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `user`
--
ALTER TABLE `user`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `detail_keluarga_siswa`
--
ALTER TABLE `detail_keluarga_siswa`
  ADD CONSTRAINT `fk_detail_keluarga_siswa` FOREIGN KEY (`id_siswa`) REFERENCES `siswa` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `hasil_asesmen`
--
ALTER TABLE `hasil_asesmen`
  ADD CONSTRAINT `fk_hasil_asesmen_siswa` FOREIGN KEY (`id_siswa`) REFERENCES `siswa` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `konselor`
--
ALTER TABLE `konselor`
  ADD CONSTRAINT `fk_konselor_user` FOREIGN KEY (`id_pengguna`) REFERENCES `user` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `konsultasi`
--
ALTER TABLE `konsultasi`
  ADD CONSTRAINT `fk_konsultasi_konselor` FOREIGN KEY (`id_konselor`) REFERENCES `konselor` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_konsultasi_siswa` FOREIGN KEY (`id_siswa`) REFERENCES `siswa` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `laporan_konsultasi`
--
ALTER TABLE `laporan_konsultasi`
  ADD CONSTRAINT `fk_laporan_konsultasi` FOREIGN KEY (`id_konsultasi`) REFERENCES `konsultasi` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `siswa`
--
ALTER TABLE `siswa`
  ADD CONSTRAINT `fk_siswa_user` FOREIGN KEY (`id_pengguna`) REFERENCES `user` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
