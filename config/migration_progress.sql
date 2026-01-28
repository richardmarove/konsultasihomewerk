-- Migration: Add numerical score support for Mental Health Progress Tracking
-- Run this in phpMyAdmin or your MySQL client

USE bk;

ALTER TABLE hasil_asesmen 
ADD COLUMN skor_numerik INT NULL AFTER skor,
ADD COLUMN catatan TEXT NULL AFTER skor_numerik;

ALTER TABLE hasil_asesmen 
ADD INDEX idx_mental_health_history (id_siswa, kategori, terakhir_diperbarui);
