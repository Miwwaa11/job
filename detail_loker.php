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
$lowongan = null;

function formatRupiah($angka) {
    if (!is_numeric($angka) || $angka === 'Negosiasi' || empty($angka)) return htmlspecialchars($angka ?: 'Negosiasi'); 
    return "Rp " . number_format($angka, 0, ',', '.');
}

$query_detail_sql = "SELECT 
                        loker.*, 
                        lok.nama_lokasi, 
                        lok.negara
                     FROM 
                        tb_lowongan loker
                     LEFT JOIN 
                        id_lokasi lok ON loker.id_lokasi = lok.id_lokasi 
                     WHERE 
                        loker.id_lowongan = ?";

if ($stmt_detail = mysqli_prepare($koneksi, $query_detail_sql)) {
    mysqli_stmt_bind_param($stmt_detail, 's', $id_lowongan); 
    mysqli_stmt_execute($stmt_detail);
    $result_detail = mysqli_stmt_get_result($stmt_detail);

    if ($result_detail && mysqli_num_rows($result_detail) > 0) {
        $lowongan = mysqli_fetch_assoc($result_detail);
    } else {
        $_SESSION['pesan_error'] = "Lowongan tidak ditemukan atau ID tidak valid.";
        mysqli_stmt_close($stmt_detail);
        mysqli_close($koneksi);
        header("Location: dashboard_pengguna.php");
        exit;
    }
    mysqli_stmt_close($stmt_detail);
} else {
    $_SESSION['pesan_error'] = "Gagal mempersiapkan query lowongan.";
    mysqli_close($koneksi);
    header("Location: dashboard_pengguna.php");
    exit;
}

$id_pengguna = $_SESSION['id_user'];
$sudah_melamar = false;

$query_cek_lamaran_sql = "SELECT id_lamaran 
                          FROM tb_lamaran 
                          WHERE id_pengguna = ? AND id_lowongan = ?";

if ($stmt_cek = mysqli_prepare($koneksi, $query_cek_lamaran_sql)) {
    mysqli_stmt_bind_param($stmt_cek, 'ss', $id_pengguna, $id_lowongan);
    mysqli_stmt_execute($stmt_cek);
    $result_cek_lamaran = mysqli_stmt_get_result($stmt_cek);
    
    $sudah_melamar = mysqli_num_rows($result_cek_lamaran) > 0;
    mysqli_stmt_close($stmt_cek);
} 

?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Detail Lowongan | <?php echo htmlspecialchars($lowongan['nama_pekerjaan']); ?></title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Poppins:wght@400;600;700&display=swap');
        
        :root {
            --primary-color: #004d99; 
            --secondary-color: #00b4d8; 
            --text-dark: #333333;
            --text-light: #6c757d;
            --bg-light: #f8f9fa;
            --bg-card: #ffffff;
            --shadow-subtle: 0 4px 12px rgba(0, 0, 0, 0.08);
        }

        body { 
            font-family: 'Poppins', sans-serif; 
            background-color: var(--bg-light); 
            color: var(--text-dark);
            line-height: 1.6;
            padding-top: 20px;
        }

        .container { 
            max-width: 900px; 
            margin: 0 auto; 
            padding: 0 20px;
        }
        
        .breadcrumb {
            margin-bottom: 20px;
            font-size: 0.9em;
        }
        .breadcrumb a {
            color: var(--primary-color);
            text-decoration: none;
            font-weight: 600;
        }
        .breadcrumb i {
            margin: 0 5px;
            color: var(--text-light);
        }

        .detail-card {
            background: var(--bg-card);
            padding: 35px;
            border-radius: 12px;
            box-shadow: var(--shadow-subtle);
            margin-bottom: 30px;
        }

        .job-title {
            color: var(--primary-color);
            margin-top: 0;
            margin-bottom: 5px;
            font-size: 2em;
            font-weight: 700;
        }

        .company-info {
            font-size: 1.1em;
            color: var(--text-dark);
            font-weight: 600;
            margin-bottom: 20px;
            border-bottom: 1px solid #e0e0e0;
            padding-bottom: 15px;
        }
        .company-info i {
            margin-right: 8px;
            color: var(--secondary-color);
        }

        .job-meta {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
            padding: 15px 0;
            border-bottom: 1px solid #e0e0e0;
        }
        .meta-item {
            display: flex;
            align-items: center;
            font-size: 0.95em;
        }
        .meta-item i {
            color: var(--primary-color);
            margin-right: 10px;
            font-size: 1.2em;
        }
        .meta-label {
            font-weight: 600;
            color: var(--text-light);
            display: block;
        }
        .meta-value {
            font-weight: 700;
            color: var(--text-dark);
            display: block;
        }
        .meta-salary {
             color: #28a745; 
        }
        
        .job-section h3 {
            color: var(--primary-color);
            border-left: 4px solid var(--secondary-color);
            padding-left: 10px;
            margin-top: 30px;
            margin-bottom: 15px;
            font-size: 1.4em;
        }
        .job-section p, .job-section ul {
            margin-bottom: 15px;
            color: var(--text-dark);
        }
        .job-section ul {
            padding-left: 20px;
        }
        .job-section ul li {
            margin-bottom: 8px;
        }

        .btn-lamar {
            display: inline-block;
            padding: 15px 30px;
            background-color: var(--primary-color);
            color: white;
            text-align: center;
            border-radius: 8px;
            text-decoration: none;
            font-weight: 700;
            font-size: 1.1em;
            margin-top: 30px;
            transition: background-color 0.3s, transform 0.2s;
        }

        .btn-lamar:hover {
            background-color: #003366;
            transform: translateY(-1px);
        }

        .btn-disabled {
            background-color: #6c757d;
            cursor: not-allowed;
        }
        
        .back-link {
            display: inline-block;
            margin-top: 20px;
            color: var(--text-light);
            text-decoration: none;
            font-weight: 600;
        }
        .back-link:hover {
            color: var(--primary-color);
        }
    </style>
</head>
<body>
<div class="container">
    <div class="breadcrumb">
        <a href="dashboard_pengguna.php">Dashboard</a> <i class="fas fa-chevron-right"></i> Detail Lowongan
    </div>
    
    <div class="detail-card">
        <h1 class="job-title"><?php echo htmlspecialchars($lowongan['nama_pekerjaan']); ?></h1>
        <div class="company-info">
            <i class="fas fa-building"></i> <?php echo htmlspecialchars($lowongan['perusahaan']); ?>
        </div>

        <div class="job-meta">
            <div class="meta-item">
                <i class="fas fa-map-marker-alt"></i>
                <div>
                    <span class="meta-label">Lokasi:</span>
                    <span class="meta-value"><?php echo htmlspecialchars($lowongan['nama_lokasi'] ?? 'N/A') . ', ' . htmlspecialchars($lowongan['negara'] ?? 'Indonesia'); ?></span>
                </div>
            </div>
            <div class="meta-item">
                <i class="fas fa-clock"></i>
                <div>
                    <span class="meta-label">Tipe Pekerjaan:</span>
                    <span class="meta-value"><?php echo htmlspecialchars($lowongan['tipe_pekerjaan']); ?></span>
                </div>
            </div>
            <div class="meta-item">
                <i class="fas fa-graduation-cap"></i>
                <div>
                    <span class="meta-label">Pendidikan Min.:</span>
                    <span class="meta-value"><?php echo htmlspecialchars($lowongan['pendidikan_minimal'] ?? 'Tidak Ditetapkan'); ?></span>
                </div>
            </div>
            <div class="meta-item">
                <i class="fas fa-money-bill-wave"></i>
                <div>
                    <span class="meta-label">Gaji (Perkiraan):</span>
                    <span class="meta-value meta-salary"><?php echo formatRupiah($lowongan['gaji']); ?></span>
                </div>
            </div>
        </div>

        <div class="job-section">
            <h3><i class="fas fa-clipboard-list"></i> Deskripsi Pekerjaan</h3>
            <p><?php echo nl2br(htmlspecialchars($lowongan['deskripsi'])); ?></p>
        </div>

        <div class="job-section">
            <h3><i class="fas fa-calendar-alt"></i> Info Lowongan</h3>
            <p><strong>Status Lowongan:</strong> <?php echo htmlspecialchars($lowongan['status_lowongan']); ?></p>
            <p><strong>Dipublikasikan:</strong> <?php echo date('d M Y', strtotime($lowongan['tanggal_publish'])); ?></p>
            <p><strong>Batas Akhir:</strong> 
                <?php 
                if ($lowongan['tanggal_tutup'] && $lowongan['tanggal_tutup'] != '0000-00-00') {
                    echo date('d M Y', strtotime($lowongan['tanggal_tutup']));
                } else {
                    echo 'Tidak Dibatasi';
                }
                ?>
            </p>
        </div>

        <?php if ($sudah_melamar): ?>
            <a href="dashboard_pengguna.php" class="btn-lamar btn-disabled" onclick="return false;"><i class="fas fa-check-circle"></i> Sudah Anda Lamar</a>
            <p style="color:#721c24; margin-top: 10px; font-size: 0.9em;">Anda telah mengajukan lamaran untuk lowongan ini. Cek status di Riwayat Lamaran Anda.</p>
        <?php else: ?>
            <a href="lamaran_form.php?id=<?php echo $lowongan['id_lowongan']; ?>" class="btn-lamar"><i class="fas fa-paper-plane"></i> Lamar Sekarang!</a>
        <?php endif; ?>

    </div>

    <a href="dashboard_pengguna.php" class="back-link"><i class="fas fa-arrow-left"></i> Kembali ke Dashboard</a>

</div>

<?php 
if (isset($koneksi)) {
    mysqli_close($koneksi);
}
?>
</body>
</html>