<?php
session_start();
include 'koneksi.php'; 

if (!isset($_SESSION['status_login']) || $_SESSION['status_login'] !== TRUE || $_SESSION['role'] !== 'user') {
    header("Location: user.php");
    exit;
}

if (!isset($_GET['id']) || empty($_GET['id'])) {
    $_SESSION['pesan_error'] = "ID Lowongan tidak ditemukan.";
    header("Location: dashboard_pengguna.php");
    exit;
}

$id_lowongan = $_GET['id'];
$id_pengguna = $_SESSION['id_user'];
$lowongan = null;

$query_loker_sql = "SELECT nama_pekerjaan, perusahaan FROM tb_lowongan WHERE id_lowongan = ?";
if ($stmt_loker = mysqli_prepare($koneksi, $query_loker_sql)) {
    mysqli_stmt_bind_param($stmt_loker, 's', $id_lowongan);
    mysqli_stmt_execute($stmt_loker);
    $result = mysqli_stmt_get_result($stmt_loker);

    if (!$result || mysqli_num_rows($result) == 0) {
        $_SESSION['pesan_error'] = "Lowongan tidak valid.";
        mysqli_stmt_close($stmt_loker);
        header("Location: dashboard_pengguna.php");
        exit;
    }
    $lowongan = mysqli_fetch_assoc($result);
    mysqli_stmt_close($stmt_loker);
} else {
    $_SESSION['pesan_error'] = "Gagal memuat detail lowongan.";
    header("Location: dashboard_pengguna.php");
    exit;
}

$query_cek_lamaran_sql = "SELECT id_lamaran FROM tb_lamaran WHERE id_pengguna = ? AND id_lowongan = ?";
if ($stmt_cek_lamaran = mysqli_prepare($koneksi, $query_cek_lamaran_sql)) {
    mysqli_stmt_bind_param($stmt_cek_lamaran, 'ss', $id_pengguna, $id_lowongan);
    mysqli_stmt_execute($stmt_cek_lamaran);
    $result_cek_lamaran = mysqli_stmt_get_result($stmt_cek_lamaran);
    
    if (mysqli_num_rows($result_cek_lamaran) > 0) {
        $_SESSION['pesan_error'] = "Anda sudah melamar lowongan ini sebelumnya.";
        mysqli_stmt_close($stmt_cek_lamaran);
        header("Location: dashboard_pengguna.php");
        exit;
    }
    mysqli_stmt_close($stmt_cek_lamaran);
} 
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Lengkapi Lamaran | <?php echo htmlspecialchars($lowongan['nama_pekerjaan']); ?></title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Poppins:wght@400;600;700&display=swap');
        
        :root {
            --primary-color: #004d99; 
            --secondary-color: #00b4d8; 
            --bg-light: #f8f9fa;
        }

        body { 
            font-family: 'Poppins', sans-serif; 
            background-color: var(--bg-light); 
            padding-top: 20px;
        }

        .container { 
            max-width: 700px; 
            margin: 0 auto; 
            padding: 0 20px;
        }
        
        .form-card {
            background: #ffffff;
            padding: 35px;
            border-radius: 12px;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.08);
            margin-bottom: 30px;
        }

        h1 {
            color: var(--primary-color);
            margin-top: 0;
            font-size: 1.8em;
            border-bottom: 2px solid var(--secondary-color);
            padding-bottom: 10px;
            margin-bottom: 20px;
        }

        .form-group {
            margin-bottom: 20px;
        }

        label {
            display: block;
            margin-bottom: 8px;
            font-weight: 600;
        }

        input[type="file"],
        textarea {
            width: 100%;
            padding: 12px;
            border: 1px solid #ced4da;
            border-radius: 8px;
            box-sizing: border-box;
            font-size: 1em;
            font-family: 'Poppins', sans-serif;
        }

        input[type="file"] {
            padding: 12px 0;
        }

        textarea {
            resize: vertical;
            min-height: 150px;
        }
        
        .btn-submit {
            display: block;
            width: 100%;
            padding: 15px;
            background-color: var(--primary-color);
            color: white;
            border: none;
            border-radius: 8px;
            font-weight: 700;
            font-size: 1.1em;
            cursor: pointer;
            transition: background-color 0.3s;
        }

        .btn-submit:hover {
            background-color: #003366;
        }
    </style>
</head>
<body>
<div class="container">
    <div class="form-card">
        <h1><i class="fas fa-file-alt"></i> Lamaran untuk <?php echo htmlspecialchars($lowongan['nama_pekerjaan']); ?></h1>
        <p style="margin-bottom: 25px;">**Perusahaan:** <?php echo htmlspecialchars($lowongan['perusahaan']); ?></p>

        <form action="proses_lamaran.php?id=<?php echo $id_lowongan; ?>" method="POST" enctype="multipart/form-data">
            
            <div class="form-group">
                <label for="cv_file"><i class="fas fa-upload"></i> Unggah CV/Resume Anda (Format PDF, Maks 2MB):</label>
                <input type="file" id="cv_file" name="cv_file" accept=".pdf" required>
            </div>
            
            <div class="form-group">
                <label for="catatan"><i class="fas fa-edit"></i> Surat Lamaran / Catatan Tambahan (Opsional):</label>
                <textarea id="catatan" name="catatan" placeholder="Tuliskan motivasi, pengalaman singkat, atau gaji harapan Anda."></textarea>
            </div>
            
            <button type="submit" class="btn-submit"><i class="fas fa-check-circle"></i> Kirim Lamaran Lengkap</button>
        </form>
    </div>
    
    <a href="detail_loker.php?id=<?php echo $id_lowongan; ?>" style="display: block; text-align: center; margin-top: 20px;"><i class="fas fa-arrow-left"></i> Kembali ke Detail Lowongan</a>

</div>

<?php 
if (isset($koneksi)) {
    mysqli_close($koneksi);
}
?>
</body>
</html>