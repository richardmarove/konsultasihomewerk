<?php
session_start();
include 'config/database.php';

$email = $_POST['email'];
$password = $_POST['password'];

$stmt = $conn->prepare("SELECT * FROM user WHERE email = ?");
$stmt->bind_param("s", $email);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows > 0) {
    $row = $result->fetch_assoc();
    
    if (password_verify($password, $row['kata_sandi'])) {
        
        $_SESSION['user_id'] = $row['id'];
        $_SESSION['peran'] = $row['peran'];
        $_SESSION['email'] = $row['email'];

        // Redirect berdasarkan peran
        if ($row['peran'] == 'siswa') {
            header("Location: index.php");
        } else if ($row['peran'] == 'konselor') {
            header("Location: dashboard_guru.php");
        } else if ($row['peran'] == 'admin') {
            header("Location: dashboard_admin.php");
        } else {
            echo "Peran tidak dikenali.";
        }
        exit;

    } else {

        header("Location: login.php?error=1");
        exit;
    }
} else {
    header("Location: login.php?error=1");
    exit;
}
?>