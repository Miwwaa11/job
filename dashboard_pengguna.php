<?php
session_start();
include 'koneksi.php';

if (!isset($_SESSION['status_login']) || $_SESSION['status_login'] !== TRUE || $_SESSION['role'] !== 'user') {
    header("Location: user.php");
    exit;
}

$id_user = $_SESSION['id_user'];
$nama_pengguna = htmlspecialchars($_SESSION['nama']);
$error_query = '';
$error_query_loker = '';

// Variabel waktu sesi dipertahankan untuk referensi di PHP, tetapi tidak ditampilkan di HTML.
$waktu_sesi = isset($_SESSION['login_time']) ? $_SESSION['login_time'] : date('Y-m-d H:i:s');
$waktu_display = date('H:i:s, d F Y', strtotime($waktu_sesi));

$query_lamaran_sql = "SELECT 
    lamaran.status, 
    lamaran.tanggal_lamaran,
    loker.nama_pekerjaan, 
    loker.perusahaan,
    loker.id_lowongan,
    loc.nama_lokasi 
FROM 
    tb_lamaran lamaran
JOIN 
    tb_lowongan loker ON lamaran.id_lowongan = loker.id_lowongan
LEFT JOIN
    id_lokasi loc ON loker.id_lokasi = loc.id_lokasi
WHERE 
    lamaran.id_pengguna = ? 
ORDER BY 
    lamaran.tanggal_lamaran DESC";

if ($stmt_lamaran = mysqli_prepare($koneksi, $query_lamaran_sql)) {
    mysqli_stmt_bind_param($stmt_lamaran, 's', $id_user); 
    mysqli_stmt_execute($stmt_lamaran);
    $result_lamaran = mysqli_stmt_get_result($stmt_lamaran);

    if (!$result_lamaran) {
        $error_query = "Gagal memuat hasil lamaran.";
    }
} else {
    $error_query = "Gagal mempersiapkan query riwayat lamaran: " . mysqli_error($koneksi);
    $result_lamaran = false; 
}

$query_loker_terbaru = "SELECT 
    loker.id_lowongan, 
    loker.nama_pekerjaan, 
    loker.perusahaan, 
    loker.tipe_pekerjaan, 
    loker.gaji, 
    loc.nama_lokasi 
FROM 
    tb_lowongan loker
LEFT JOIN 
    id_lokasi loc ON loker.id_lokasi = loc.id_lokasi
ORDER BY 
    loker.tanggal_publish DESC 
LIMIT 5";

$result_loker_terbaru = mysqli_query($koneksi, $query_loker_terbaru);

if (!$result_loker_terbaru) {
    $error_query_loker = "Gagal mengambil data lowongan: " . mysqli_error($koneksi);
    $data_loker = [];
} else {
    $data_loker = mysqli_fetch_all($result_loker_terbaru, MYSQLI_ASSOC);

    if (empty($data_loker)) {
           // Contoh data jika database kosong
           $data_loker = [
               ['id_lowongan' => '901', 'nama_pekerjaan' => 'Full Stack Developer', 'perusahaan' => 'TechNova Digital', 'nama_lokasi' => 'Jakarta Selatan', 'tipe_pekerjaan' => 'Full-Time', 'gaji' => 15000000],
               ['id_lowongan' => '902', 'nama_pekerjaan' => 'Content Writer Senior', 'perusahaan' => 'MediaKreatif', 'nama_lokasi' => 'Yogyakarta', 'tipe_pekerjaan' => 'Remote', 'gaji' => 7000000],
               ['id_lowongan' => '903', 'nama_pekerjaan' => 'Staff Administrasi', 'perusahaan' => 'Sinergi Logistik', 'nama_lokasi' => 'Surabaya', 'tipe_pekerjaan' => 'Kontrak', 'gaji' => 4500000],
           ];
    }
}

function formatRupiah($angka) {
    if (!is_numeric($angka) || $angka === 'Negosiasi' || empty($angka)) return htmlspecialchars($angka ?: 'Negosiasi'); 
    return "Rp " . number_format($angka, 0, ',', '.');
}

?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard Pengguna | <?php echo $nama_pengguna; ?></title>
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
            --shadow-hover: 0 8px 25px rgba(0, 77, 153, 0.15);
        }

        body { 
            font-family: 'Poppins', sans-serif; 
            background-color: var(--bg-light); 
            color: var(--text-dark);
            line-height: 1.6;
            margin: 0;
            padding: 0;
        }

        .container { 
            max-width: 1300px; 
            margin: 30px auto; 
            padding: 0 25px;
        }

        /* HEADER */
        .header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            background-image: linear-gradient(to right, var(--primary-color), #1a71c4);
            color: white;
            padding: 25px 35px;
            border-radius: 12px;
            margin-bottom: 40px;
            box-shadow: var(--shadow-subtle);
            min-height: 70px; 
        }

        .header h1 {
            margin: 0;
            font-size: 1.9em;
            font-weight: 700;
        }

        .header-logo {
            display: flex;
            align-items: center;
            flex-grow: 1; 
        }
        
        .header-logo img {
            height: 50px; 
            width: auto;
            object-fit: contain;
            filter: brightness(0) invert(1); 
        }

        /* Aksi Header Baru */
        .header-actions {
            display: flex;
            gap: 10px; /* Jarak antar tombol */
            align-items: center;
        }

        /* Gaya dasar tombol di header */
        .header-link {
            color: white;
            text-decoration: none;
            font-weight: 600;
            padding: 8px 18px;
            border-radius: 6px;
            transition: background-color 0.3s, transform 0.2s;
            border: 2px solid transparent; 
        }

        /* Tombol Profil */
        .profile-link {
            background-color: white; 
            color: var(--primary-color);
            border-color: white;
        }
        .profile-link:hover {
            background-color: #e0f2ff; /* Warna hover lebih lembut */
            transform: translateY(-1px);
        }

        /* Tombol Logout */
        .logout-link {
            background-color: var(--secondary-color);
        }

        .logout-link:hover {
            background-color: #0088b3;
            transform: translateY(-1px);
        }
        
        /* WELCOME BANNER */
        .welcome-banner {
            background-color: #eaf3ff; /* Light primary background */
            background-image: url('logo3.png'); 
            background-size: 250px; 
            background-position: 95% center; 
            background-repeat: no-repeat;
            padding: 35px 40px;
            border-radius: 12px;
            margin-bottom: 30px;
            box-shadow: var(--shadow-subtle);
            min-height: 150px;
            display: flex;
            align-items: center;
            justify-content: space-between; 
            overflow: hidden;
            border: 1px solid #c3d9ef;
        }

        .welcome-banner-content {
            max-width: 70%; 
        }

        .welcome-banner-content h1 {
            color: var(--primary-color);
            font-size: 2.2em;
            font-weight: 700;
            margin: 0 0 5px 0;
        }

        .welcome-banner-content p {
            color: var(--text-dark);
            font-size: 1.1em;
            margin: 0;
        }

        /* SECTION STYLES */
        section {
            background: var(--bg-card);
            padding: 30px;
            border-radius: 12px;
            box-shadow: var(--shadow-subtle);
            margin-bottom: 30px;
        }

        h2 {
            color: var(--primary-color);
            border-bottom: 3px solid var(--secondary-color);
            padding-bottom: 12px;
            margin-top: 0;
            margin-bottom: 25px;
            font-weight: 700;
            font-size: 1.6em;
        }

        /* RIWAYAT TABLE */
        .table-riwayat {
            width: 100%;
            border-collapse: collapse;
            font-size: 0.9em;
        }

        .table-riwayat th, .table-riwayat td {
            padding: 15px;
            text-align: left;
            border-bottom: 1px solid #e9ecef;
            vertical-align: middle;
        }

        .table-riwayat th {
            background-color: #eaf3ff; 
            color: var(--primary-color);
            font-weight: 700;
            text-transform: uppercase;
        }

        .table-riwayat tr:last-child td {
            border-bottom: none;
        }

        /* STATUS BADGE */
        .status-badge {
            display: inline-block;
            padding: 6px 14px;
            border-radius: 20px;
            font-weight: 600;
            font-size: 0.85em;
            text-transform: uppercase;
        }

        .status-pending { background-color: #ffedcc; color: #cc8400; } 
        .status-diterima { background-color: #d4edda; color: #155724; } 
        .status-ditolak { background-color: #f8d7da; color: #721c24; } 
        .status-lain { background-color: #e2e3e5; color: #495057; } 

        /* LOWONGAN CARD GRID */
        .loker-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(380px, 1fr));
            gap: 25px;
        }

        .loker-card {
            border: 1px solid #e0e0e0;
            padding: 25px;
            border-radius: 10px;
            transition: all 0.3s;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
            background: var(--bg-card);
        }
        
        .loker-card:hover {
            box-shadow: var(--shadow-hover); 
            border-color: var(--primary-color);
            transform: translateY(-3px);
        }

        .loker-card h3 {
            color: var(--primary-color);
            margin-top: 0;
            margin-bottom: 5px;
            font-size: 1.3em;
            font-weight: 700;
        }

        .loker-card p {
            margin: 3px 0;
            font-size: 0.9em;
            color: var(--text-light);
        }

        .loker-card .salary {
            font-weight: 700;
            color: #28a745; 
            font-size: 1.1em;
            margin-top: 15px;
            display: block;
        }

        .loker-card .meta {
            font-size: 0.85em;
            color: var(--text-light);
            margin-bottom: 15px;
            display: flex;
            gap: 15px;
            flex-wrap: wrap;
        }

        .loker-card .meta span i {
            margin-right: 5px;
            color: var(--secondary-color);
        }

        /* BUTTON LAMAR */
        .btn-lamar {
            display: block;
            width: 100%;
            padding: 12px;
            background-color: var(--primary-color);
            color: white;
            text-align: center;
            border-radius: 8px;
            text-decoration: none;
            margin-top: 20px;
            font-weight: 600;
            transition: background-color 0.3s;
        }

        .btn-lamar:hover {
            background-color: #003366;
        }

        /* MESSAGES */
        .message-box {
            padding: 20px;
            border-radius: 8px;
            margin-bottom: 20px;
            font-weight: 600;
            font-size: 1em;
            display: flex;
            align-items: center;
        }
        .message-box i {
            margin-right: 15px;
            font-size: 1.5em;
        }
        .message-error {
            background-color: #f8d7da; color: #721c24; border: 1px solid #f5c6cb;
        }
        .message-empty {
            background-color: #fff3cd; color: #856404; border: 1px solid #ffeeba;
        }
        
        /* Responsive adjustment */
        @media (max-width: 900px) {
            .welcome-banner {
                background-size: 180px; 
                background-position: right 20px bottom 20px; 
                min-height: 120px;
                padding: 30px 25px;
            }
            .welcome-banner-content {
                max-width: 100%; 
                padding-right: 0;
            }
            .welcome-banner-content h1 {
                font-size: 1.8em;
            }
            .welcome-banner-content p {
                font-size: 1em;
            }
            .loker-grid {
                grid-template-columns: 1fr;
            }
            .table-riwayat th, .table-riwayat td {
                padding: 10px;
                font-size: 0.8em;
            }
            .status-badge {
                padding: 4px 8px;
            }
        }
    </style>
</head>
<body>
<div class="container">
    <header class="header">
        <div class="header-logo">
            <img src="karirid_logo.png" alt="KarirID Logo" onerror="this.onerror=null;this.src='https://placehold.co/120x50/004d99/ffffff?text=KarirID';" style="height: 50px; width: auto; object-fit: contain; filter: brightness(0) invert(1);"> 
        </div>
        
        <div class="header-actions">
            <a href="profile_user.php" class="header-link profile-link">
                <i class="fas fa-user-circle"></i> Profil
            </a>
            <a href="logoutuser.php" class="header-link logout-link">
                <i class="fas fa-sign-out-alt"></i> Logout
            </a>
        </div>
    </header>

    <div class="welcome-banner">
        <div class="welcome-banner-content">
            <h1>Bangun Karier Impianmu Bersama Kami!</h1> 
            <p>Temukan ribuan peluang kerja dari berbagai industri dan perusahaan ternama di seluruh Indonesia. Kami memahami bahwa setiap orang memiliki perjalanan karier yang unik—karena itu, kami hadir untuk menghubungkanmu dengan kesempatan yang benar-benar relevan dengan kemampuanmu. Bangun masa depan kariermu hari ini. Mulai jelajahi, temukan peluang, dan wujudkan potensi terbaikmu.</p>
            
            </div>
    </div>
    
    <?php if (isset($_SESSION['pesan_sukses'])): ?>
        <div class="message-box" style="background-color: #d4edda; color: #155724; border: 1px solid #c3e6cb; margin-bottom: 20px;">
            <i class="fas fa-check-circle"></i> <?php echo $_SESSION['pesan_sukses']; ?>
        </div>
        <?php unset($_SESSION['pesan_sukses']); ?>
    <?php endif; ?>

    <?php if (isset($_SESSION['pesan_error'])): ?>
        <div class="message-box message-error" style="margin-bottom: 20px;">
            <i class="fas fa-times-circle"></i> <?php echo $_SESSION['pesan_error']; ?>
        </div>
        <?php unset($_SESSION['pesan_error']); ?>
    <?php endif; ?>

    <section id="riwayat-lamaran">
        <h2>Riwayat Lamaran Saya 💼</h2>

        <?php if ($error_query): ?>
            <p class="message-box message-error"><i class="fas fa-exclamation-triangle"></i> Gagal memuat riwayat lamaran: <?php echo $error_query; ?></p>
        <?php elseif ($result_lamaran && mysqli_num_rows($result_lamaran) > 0): ?>
            <div style="overflow-x: auto;">
                <table class="table-riwayat">
                    <thead>
                        <tr>
                            <th>Lowongan</th>
                            <th>Perusahaan</th>
                            <th>Lokasi</th> 
                            <th>Status</th>
                            <th>Tanggal Lamar</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while ($row = mysqli_fetch_assoc($result_lamaran)): 
                            $status_clean = strtolower($row['status']);
                            $status_class = 'status-lain';
                            if ($status_clean == 'pending') $status_class = 'status-pending';
                            if ($status_clean == 'diterima') $status_class = 'status-diterima';
                            if ($status_clean == 'ditolak') $status_class = 'status-ditolak';

                            $lokasi_display = htmlspecialchars($row['nama_lokasi'] ?? 'Lokasi Tidak Ditemukan'); 
                        ?>
                        <tr>
                            <td><?php echo htmlspecialchars($row['nama_pekerjaan']); ?></td>
                            <td><?php echo htmlspecialchars($row['perusahaan']); ?></td>
                            <td><?php echo $lokasi_display; ?></td>
                            <td>
                                <span class="status-badge <?php echo $status_class; ?>">
                                    <?php echo htmlspecialchars($row['status']); ?>
                                </span>
                            </td> 
                            <td><?php echo date('d M Y', strtotime($row['tanggal_lamaran'])); ?></td> 
                        </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
        <?php else: ?>
            <p class="message-box message-empty"><i class="fas fa-search"></i> Anda belum mengirim lamaran. Mari jelajahi lowongan di bawah ini!</p>
        <?php endif; ?>
    </section>

    <section id="loker-terbaru">
        <h2>Lowongan Pekerjaan Terbaru 💡</h2>
        
        <?php if ($error_query_loker): ?>
            <p class="message-box message-error"><i class="fas fa-exclamation-triangle"></i> Gagal memuat lowongan: <?php echo $error_query_loker; ?></p>
        <?php endif; ?>

        <div class="loker-grid">
            <?php 
            if (!empty($data_loker)): 
                foreach ($data_loker as $loker):
                    $lokasi_display = htmlspecialchars($loker['nama_lokasi'] ?? 'Lokasi Global');
                    $gaji_display = formatRupiah($loker['gaji'] ?? 0); 
                    $tipe_display = htmlspecialchars($loker['tipe_pekerjaan'] ?? 'N/A');
            ?>
                <div class="loker-card">
                    <div>
                        <h3><?php echo htmlspecialchars($loker['nama_pekerjaan']); ?></h3>
                        <p style="font-weight: 600; color: var(--text-dark); margin-bottom: 10px;"><i class="fas fa-building"></i> <?php echo htmlspecialchars($loker['perusahaan']); ?></p>
                        
                        <div class="meta">
                            <span><i class="fas fa-map-marker-alt"></i> <?php echo $lokasi_display; ?></span>
                            <span><i class="fas fa-clock"></i> <?php echo $tipe_display; ?></span>
                        </div>
                        
                        <span class="salary"><i class="fas fa-money-bill-wave"></i> <?php echo $gaji_display; ?></span>
                    </div>
                    <a href="detail_loker.php?id=<?php echo $loker['id_lowongan']; ?>" class="btn-lamar"><i class="fas fa-info-circle"></i> Lihat Detail & Lamar</a>
                </div>
            <?php endforeach; 
            else:
            ?>
            <p class="message-box message-empty">Tidak ada lowongan pekerjaan yang ditemukan saat ini.</p>
            <?php endif; ?>
        </div>
    </section>

</div>

<?php 
if (isset($stmt_lamaran)) {
    mysqli_stmt_close($stmt_lamaran);
}

if (isset($koneksi)) {
    mysqli_close($koneksi);
}
?>
</body>
</html>