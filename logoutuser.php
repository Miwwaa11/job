<?php
session_start();
include 'koneksi.php'; 

if (isset($_SESSION['id_user']) && isset($_SESSION['role']) && $_SESSION['status_login'] === TRUE) {
    
    $id_user = $_SESSION['id_user'];
    $role = $_SESSION['role'];

    if ($role == 'user') {
        $update_status_sql = "UPDATE tb_pengguna SET status_login = 'offline' WHERE id_pengguna = ?";
        
        if ($stmt = mysqli_prepare($koneksi, $update_status_sql)) {
            mysqli_stmt_bind_param($stmt, 's', $id_user);
            mysqli_stmt_execute($stmt);
            mysqli_stmt_close($stmt);
        }
    }
    
    session_unset();
    session_destroy();
}

if (isset($koneksi)) {
    mysqli_close($koneksi);
}

header("Location: user.php");
exit;
?>