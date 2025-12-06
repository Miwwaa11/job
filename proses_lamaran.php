<?php
session_start();
include 'koneksi.php'; 

if (!isset($_SESSION['status_login']) || $_SESSION['status_login'] !== TRUE || $_SESSION['role'] !== 'user' || $_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: user.php");
    exit;
}

if (!isset($_GET['id']) || empty($_GET['id'])) {
    $_SESSION['pesan_error'] = "Lowongan tidak valid.";
    header("Location: dashboard_pengguna.php");
    exit;
}

$id_lowongan = $_GET['id'];
$id_pengguna = $_SESSION['id_user'];
$tanggal_lamaran = date('Y-m-d');
$status_awal = 'Pending'; 
$catatan = $_POST['catatan'] ?? '';
$nama_file_cv = NULL; 
$target_path = NULL;

$query_loker_sql = "SELECT nama_pekerjaan FROM tb_lowongan WHERE id_lowongan = ?";
if ($stmt_loker = mysqli_prepare($koneksi, $query_loker_sql)) {
    mysqli_stmt_bind_param($stmt_loker, 's', $id_lowongan);
    mysqli_stmt_execute($stmt_loker);
    $result_loker = mysqli_stmt_get_result($stmt_loker);
    $data_loker = mysqli_fetch_assoc($result_loker);
    $nama_pekerjaan = $data_loker ? $data_loker['nama_pekerjaan'] : 'Lowongan ID ' . $id_lowongan;
    mysqli_stmt_close($stmt_loker);
} else {
    $nama_pekerjaan = 'Lowongan ID ' . $id_lowongan;
}


$query_cek_sql = "SELECT id_lamaran FROM tb_lamaran WHERE id_pengguna = ? AND id_lowongan = ?";
if ($stmt_cek = mysqli_prepare($koneksi, $query_cek_sql)) {
    mysqli_stmt_bind_param($stmt_cek, 'ss', $id_pengguna, $id_lowongan);
    mysqli_stmt_execute($stmt_cek);
    $result_cek = mysqli_stmt_get_result($stmt_cek);

    if (mysqli_num_rows($result_cek) > 0) {
        $_SESSION['pesan_error'] = "Anda sudah pernah melamar lowongan **" . htmlspecialchars($nama_pekerjaan) . "** sebelumnya.";
        mysqli_stmt_close($stmt_cek);
        mysqli_close($koneksi);
        header("Location: dashboard_pengguna.php");
        exit;
    }
    mysqli_stmt_close($stmt_cek);
}


if (isset($_FILES['cv_file']) && $_FILES['cv_file']['error'] == 0) {
    $file_cv = $_FILES['cv_file'];
    $allowed_types = ['application/pdf'];
    $max_size = 2 * 1024 * 1024;

    if (!in_array($file_cv['type'], $allowed_types)) {
        $_SESSION['pesan_error'] = "Gagal: Format file CV harus PDF.";
        mysqli_close($koneksi);
        header("Location: lamaran_form.php?id=" . urlencode($id_lowongan));
        exit;
    }
    
    if ($file_cv['size'] > $max_size) {
        $_SESSION['pesan_error'] = "Gagal: Ukuran file CV maksimal 2MB.";
        mysqli_close($koneksi);
        header("Location: lamaran_form.php?id=" . urlencode($id_lowongan));
        exit;
    }
    
    $upload_dir = 'uploads/cv/'; 
    if (!is_dir($upload_dir)) {
        mkdir($upload_dir, 0777, true);
    }

    $extension = pathinfo($file_cv['name'], PATHINFO_EXTENSION);
    $nama_file_cv_baru = $id_pengguna . '_' . $id_lowongan . '_' . time() . '.' . $extension;
    $target_path = $upload_dir . $nama_file_cv_baru;

    if (move_uploaded_file($file_cv['tmp_name'], $target_path)) {
        $nama_file_cv = $nama_file_cv_baru;
    } else {
        $_SESSION['pesan_error'] = "Gagal mengunggah file CV. Coba lagi.";
        mysqli_close($koneksi);
        header("Location: lamaran_form.php?id=" . urlencode($id_lowongan));
        exit;
    }
} else {
    $_SESSION['pesan_error'] = "File CV/Resume wajib diunggah.";
    mysqli_close($koneksi);
    header("Location: lamaran_form.php?id=" . urlencode($id_lowongan));
    exit;
}


$query_insert_sql = "INSERT INTO tb_lamaran (id_pengguna, id_lowongan, tanggal_lamaran, status, catatan, file_cv)
                     VALUES (?, ?, ?, ?, ?, ?)";

if ($stmt_insert = mysqli_prepare($koneksi, $query_insert_sql)) {
    mysqli_stmt_bind_param($stmt_insert, 'ssssss', $id_pengguna, $id_lowongan, $tanggal_lamaran, $status_awal, $catatan, $nama_file_cv);

    if (mysqli_stmt_execute($stmt_insert)) {
        $_SESSION['pesan_sukses'] = "Lamaran Lengkap Anda untuk lowongan **" . htmlspecialchars($nama_pekerjaan) . "** berhasil dikirim!";
        mysqli_stmt_close($stmt_insert);
        mysqli_close($koneksi);
        header("Location: dashboard_pengguna.php");
        exit;
    } else {
        $_SESSION['pesan_error'] = "Gagal menyimpan lamaran. Error database: " . mysqli_error($koneksi);
        if ($target_path && file_exists($target_path)) {
            unlink($target_path);
        }
        mysqli_stmt_close($stmt_insert);
        mysqli_close($koneksi);
        header("Location: lamaran_form.php?id=" . urlencode($id_lowongan));
        exit;
    }
} else {
    $_SESSION['pesan_error'] = "Gagal mempersiapkan query simpan lamaran.";
    if ($target_path && file_exists($target_path)) {
        unlink($target_path);
    }
    mysqli_close($koneksi);
    header("Location: lamaran_form.php?id=" . urlencode($id_lowongan));
    exit;
}

mysqli_close($koneksi);
?>