<?php
session_start();
include '../config/database.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $nama       = $_POST['nama_lengkap'];
    $nis        = $_POST['nis'];
    $email      = $_POST['email'];
    $password   = $_POST['password'];
    $kelas      = $_POST['tingkat_kelas'];
    $jurusan    = $_POST['jurusan'];
    $gender     = $_POST['jenis_kelamin'];

    // Basic Validation
    if (empty($nama) || empty($nis) || empty($email) || empty($password)) {
        header("Location: ../register.php?error=" . urlencode("Semua kolom wajib diisi!"));
        exit;
    }

    if (strlen($password) < 6) {
        header("Location: ../register.php?error=" . urlencode("Password harus memiliki minimal 6 karakter!"));
        exit;
    }

    // Check for Duplicates (Securely)
    // Checking Email
    $stmt_check_email = $conn->prepare("SELECT id FROM user WHERE email = ?");
    $stmt_check_email->bind_param("s", $email);
    $stmt_check_email->execute();
    $result_email = $stmt_check_email->get_result();

    // Checking NIS
    $stmt_check_nis = $conn->prepare("SELECT id FROM siswa WHERE nis = ?");
    $stmt_check_nis->bind_param("s", $nis);
    $stmt_check_nis->execute();
    $result_nis = $stmt_check_nis->get_result();

    if ($result_email->num_rows > 0) {
        header("Location: ../register.php?error=" . urlencode("Email sudah terdaftar!"));
        exit;
    }
    
    if ($result_nis->num_rows > 0) {
        header("Location: ../register.php?error=" . urlencode("NIS sudah terdaftar!"));
        exit;
    }

    // Proceed to Insert
    $conn->begin_transaction();

    try {
        // HASH PASSWORD
        $hashed_password = password_hash($password, PASSWORD_DEFAULT);

        // Insert into 'user' table
        $stmt_user = $conn->prepare("INSERT INTO user (email, kata_sandi, peran) VALUES (?, ?, 'siswa')");
        $stmt_user->bind_param("ss", $email, $hashed_password);
        $stmt_user->execute();
        
        $new_user_id = $conn->insert_id;

        // Insert into 'siswa' table
        $stmt_siswa = $conn->prepare("INSERT INTO siswa (id_pengguna, nis, nama_lengkap, tingkat_kelas, jurusan, jenis_kelamin) VALUES (?, ?, ?, ?, ?, ?)");
        $stmt_siswa->bind_param("ississ", $new_user_id, $nis, $nama, $kelas, $jurusan, $gender);
        $stmt_siswa->execute();

        $conn->commit();
        header("Location: ../register.php?success=" . urlencode("Pendaftaran berhasil! Silakan login."));
        exit;

    } catch (Exception $e) {
        $conn->rollback();
        // Log detailed error for admin, show generic for user
        error_log($e->getMessage()); 
        header("Location: ../register.php?error=" . urlencode("Terjadi kesalahan sistem. Silakan coba lagi."));
        exit;
    }
} else {
    // If not POST, redirect back
    header("Location: ../register.php");
    exit;
}
?>
