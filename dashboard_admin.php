<?php
session_start();
include 'koneksi.php'; 

if (!isset($_SESSION['status_login']) || $_SESSION['status_login'] !== TRUE || $_SESSION['role'] !== 'admin') {
    header("Location: admin.php");
    exit;
}

if (isset($_GET['action'])) {
    $action = $_GET['action'];
    $id = $_GET['id'] ?? null; 
    
    $id_safe = (isset($koneksi, $id) && $id !== null) ? mysqli_real_escape_string($koneksi, $id) : null;
    $redirect_tab = '';
    $success_message = '';
    $error_message = '';

    if (isset($koneksi)) {
        switch ($action) {
            
            case 'logout':
                $_SESSION = array();
                if (ini_get("session.use_cookies")) {
                    $params = session_get_cookie_params();
                    setcookie(session_name(), '', time() - 42000, $params["path"], $params["domain"], $params["secure"], $params["httponly"]);
                }
                session_destroy();
                header("Location: admin.php");
                exit;

            case 'delete_pengguna':
                if ($id_safe) {
                    $query_hapus = "DELETE FROM tb_pengguna WHERE id_pengguna = '$id_safe'";
                    $redirect_tab = 'manajemen_pengguna';
                    if (mysqli_query($koneksi, $query_hapus)) {
                        $success_message = "✅ Pengguna ID $id_safe **berhasil** dihapus.";
                    } else {
                        $error_message = "❌ Gagal menghapus pengguna. Error: " . mysqli_error($koneksi);
                    }
                } else { $error_message = "❌ ID Pengguna tidak valid."; }
                break;
                
            case 'delete_lowongan':
                if ($id_safe) {
                    $query_hapus = "DELETE FROM tb_lowongan WHERE id_lowongan = '$id_safe'";
                    $redirect_tab = 'manajemen_lowongan';
                    if (mysqli_query($koneksi, $query_hapus)) {
                        $success_message = "✅ Lowongan ID $id_safe **berhasil** dihapus.";
                    } else {
                        $error_message = "❌ Gagal menghapus lowongan. Error: " . mysqli_error($koneksi);
                    }
                } else { $error_message = "❌ ID Lowongan tidak valid."; }
                break;
                
            case 'update_lamaran_status':
                if ($id_safe) {
                    $new_status = isset($_GET['status']) ? mysqli_real_escape_string($koneksi, $_GET['status']) : 'pending';
                    $query_update = "UPDATE tb_lamaran SET status = '$new_status' WHERE id_lamaran = '$id_safe'";
                    $redirect_tab = 'tinjau_lamaran';
                    if (mysqli_query($koneksi, $query_update)) {
                        $display_status = strtoupper($new_status == 'sukses' ? 'DITERIMA' : ($new_status == 'pending' ? 'MENUNGGU' : $new_status));
                        $success_message = "✅ Status Lamaran ID $id_safe **berhasil** diubah menjadi **$display_status**.";
                    } else {
                        $error_message = "❌ Gagal mengubah status lamaran. Error: " . mysqli_error($koneksi);
                    }
                } else { $error_message = "❌ ID Lamaran tidak valid."; }
                break;
            
            case 'toggle_lowongan_status':
                if ($id_safe) {
                    $query_current = "SELECT status_lowongan FROM tb_lowongan WHERE id_lowongan = '$id_safe'";
                    $result_current = mysqli_query($koneksi, $query_current);
                    
                    if ($result_current && $row = mysqli_fetch_assoc($result_current)) {
                        $current_status = $row['status_lowongan'];
                        $new_status = (strtolower($current_status) == 'aktif') ? 'Nonaktif' : 'Aktif';
                        
                        $query_update = "UPDATE tb_lowongan SET status_lowongan = '$new_status' WHERE id_lowongan = '$id_safe'";
                        $redirect_tab = 'manajemen_lowongan';
                        
                        if (mysqli_query($koneksi, $query_update)) {
                            $success_message = "✅ Status Lowongan ID $id_safe **berhasil** diubah menjadi **$new_status**.";
                        } else {
                            $error_message = "❌ Gagal mengubah status lowongan. Error: " . mysqli_error($koneksi);
                        }
                    } else {
                        $error_message = "❌ Lowongan tidak ditemukan atau gagal mengambil data status.";
                    }
                } else { $error_message = "❌ ID Lowongan tidak valid."; }
                break;
        }
        
        if ($redirect_tab) {
            $_SESSION['pesan'] = $success_message ?: $error_message;
            header("Location: dashboard_admin.php?tab=$redirect_tab");
            exit;
        }
    } else {
        $_SESSION['pesan'] = "❌ Gagal memproses aksi. Koneksi database tidak tersedia.";
        $default_tab = 'ringkasan';
        header("Location: dashboard_admin.php?tab=$default_tab");
        exit;
    }
}


$nama_admin = $_SESSION['nama'] ?? 'Admin KarirID';
$content = $_GET['tab'] ?? 'ringkasan'; 

$stats = [
    'total_pengguna' => 0,
    'lowongan_aktif' => 0,
    'total_lamaran' => 0,
];

$list_pengguna = [];
$list_lamaran = [];
$list_lowongan = []; 

if (isset($koneksi)) {
    $stats['total_pengguna'] = mysqli_fetch_assoc(mysqli_query($koneksi, "SELECT COUNT(id_pengguna) AS total FROM tb_pengguna"))['total'] ?? 0;
    $stats['lowongan_aktif'] = mysqli_fetch_assoc(mysqli_query($koneksi, "SELECT COUNT(id_lowongan) AS total FROM tb_lowongan WHERE status_lowongan = 'Aktif'"))['total'] ?? 0;
    $stats['total_lamaran'] = mysqli_fetch_assoc(mysqli_query($koneksi, "SELECT COUNT(id_lamaran) AS total FROM tb_lamaran"))['total'] ?? 0;
    
    $result_pengguna = mysqli_query($koneksi, "SELECT id_pengguna, nama_pengguna, email_pengguna, tanggal_daftar, status_login FROM tb_pengguna ORDER BY tanggal_daftar DESC");
    if ($result_pengguna) { while ($row = mysqli_fetch_assoc($result_pengguna)) { $list_pengguna[] = $row; } }
    
    $query_lamaran = "
        SELECT tla.id_lamaran, tpe.nama_pengguna, tlo.nama_pekerjaan AS judul_lowongan, tla.tanggal_lamaran, tla.status, tla.catatan, tla.file_cv
        FROM tb_lamaran tla
        JOIN tb_pengguna tpe ON tla.id_pengguna = tpe.id_pengguna
        JOIN tb_lowongan tlo ON tla.id_lowongan = tlo.id_lowongan
        ORDER BY tla.tanggal_lamaran DESC
    ";
    $result_lamaran = mysqli_query($koneksi, $query_lamaran);
    if ($result_lamaran) { while ($row = mysqli_fetch_assoc($result_lamaran)) { $list_lamaran[] = $row; } }

    $query_lowongan = "
        SELECT id_lowongan, perusahaan, nama_pekerjaan, deskripsi, gaji, tipe_pekerjaan, status_lowongan, tanggal_publish
        FROM tb_lowongan
        ORDER BY tanggal_publish DESC
    ";
    $result_lowongan = mysqli_query($koneksi, $query_lowongan);
    if ($result_lowongan) { while ($row = mysqli_fetch_assoc($result_lowongan)) { $list_lowongan[] = $row; } }
} else {
    $stats['total_pengguna'] = 'N/A (Koneksi Gagal)';
}

$pesan_status = $_SESSION['pesan'] ?? '';
unset($_SESSION['pesan']);

?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard Admin | <?php echo htmlspecialchars($nama_admin); ?></title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700;800&display=swap');
        :root {
            --primary-color: #004d99; --secondary-color: #00b4d8; --bg-light: #f4f7f9; --bg-card: #ffffff;
            --text-dark: #333333; --text-light: #777777; --shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
            --border-radius: 8px; --success-color: #28a745; --danger-color: #dc3545; 
            --warning-color: #ffc107; --info-color: #17a2b8; --dropdown-bg: #f9f9f9;
        }
        body {font-family: 'Inter', sans-serif;background-color: var(--bg-light);color: var(--text-dark);margin: 0;padding: 0;}
        .header {background-color: var(--primary-color);color: white;padding: 15px 40px;display: flex;justify-content: space-between;align-items: center;box-shadow: 0 2px 8px rgba(0, 0, 0, 0.2);}
        .header .logo {font-size: 1.5em;font-weight: 800;}
        .header .admin-info {display: flex;align-items: center;}
        .header .admin-info a {color: white;text-decoration: none;padding: 8px 15px;border: 1px solid white;border-radius: var(--border-radius);margin-left: 20px;transition: background-color 0.3s;}
        .container {max-width: 1200px;margin: 30px auto;padding: 0 20px;}
        .tabs {display: flex;overflow-x: auto;margin-bottom: 25px;border-bottom: 2px solid #ddd;white-space: nowrap;}
        .tab-button {background: none;border: none;padding: 15px 25px;cursor: pointer;font-size: 1em;font-weight: 600;color: var(--text-light);border-radius: var(--border-radius) var(--border-radius) 0 0;transition: color 0.3s, background-color 0.3s;}
        .tab-button.active {color: var(--primary-color);border-bottom: 3px solid var(--primary-color);background-color: var(--bg-card);}
        .card {background-color: var(--bg-card);border-radius: var(--border-radius);box-shadow: var(--shadow);padding: 30px;margin-bottom: 20px;}
        .stats-grid {display: grid;grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));gap: 20px;margin-bottom: 30px;}
        .stat-card {padding: 25px;border-radius: var(--border-radius);box-shadow: var(--shadow);background-color: white;display: flex;justify-content: space-between;align-items: center;transition: transform 0.2s; cursor: pointer;}
        .stat-card:hover {transform: translateY(-5px);}
        .stat-card .icon {font-size: 2.5em;color: var(--secondary-color);}
        .stat-card .details {text-align: right;}
        .stat-card .details .label {font-size: 0.9em;color: var(--text-light);font-weight: 600;}
        .stat-card .details a {text-decoration: none !important;} 
        .stat-card .details .number {font-size: 2em;font-weight: 800;color: var(--primary-color);}
        .status-message {padding: 15px;margin-bottom: 20px;border-radius: var(--border-radius);font-weight: 600;}
        .status-message.sukses {background-color: #d4edda;color: var(--success-color);border: 1px solid #c3e6cb;}
        .status-message.gagal {background-color: #f8d7da;color: var(--danger-color);border: 1px solid #f5c6cb;}
        .table-responsive {overflow-x: auto;}
        .data-table {width: 100%;border-collapse: collapse;margin-top: 20px;}
        .data-table th, .data-table td {padding: 12px 15px;text-align: left;border-bottom: 1px solid #eeeeee;}
        .data-table th {background-color: var(--primary-color);color: white;font-weight: 600;text-transform: uppercase;font-size: 0.85em;}
        .data-table tbody tr:hover {background-color: #f0f0f0;}
        .status-badge {display: inline-block;padding: 4px 10px;border-radius: 50px;font-size: 0.75em;font-weight: 700;color: white;text-transform: uppercase;}
        .status-badge.aktif {background-color: var(--info-color);}
        .status-badge.nonaktif {background-color: var(--danger-color);}
        .status-badge.pending {background-color: var(--warning-color);color: var(--text-dark);}
        .status-badge.sukses {background-color: var(--success-color);}
        .status-badge.ditolak {background-color: var(--danger-color);}
        .status-badge.online {background-color: var(--success-color);}
        .status-badge.offline {background-color: var(--danger-color);}
        .action-dropdown {position: relative;display: inline-block;}
        .action-dropdown-content {display: none;position: absolute;background-color: var(--dropdown-bg);min-width: 140px;box-shadow: 0px 8px 16px 0px rgba(0,0,0,0.2);z-index: 1;border-radius: 5px;right: 0;}
        .action-dropdown-content a {color: var(--text-dark);padding: 8px 12px;text-decoration: none;display: block;font-size: 0.9em;}
        .action-dropdown:hover .action-dropdown-content {display: block;}
        .btn-action {background: none;border: none;color: var(--primary-color);cursor: pointer;font-size: 1em;padding: 5px;transition: color 0.2s;}
        .btn-action.delete {color: var(--danger-color);margin: 0;}
        .btn-tambah {
            background-color: var(--success-color);
            color: white;
            padding: 10px 20px;
            border: none;
            border-radius: var(--border-radius);
            font-weight: 600;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 5px;
            margin-bottom: 20px;
            transition: background-color 0.3s;
        }
        .btn-tambah:hover {
            background-color: #1e7e34;
        }
    </style>
</head>
<body>

    <div class="header">
        <div class="logo">KarirID - Admin Panel</div>
        <div class="admin-info">
            <span>Selamat Datang, **<?php echo htmlspecialchars($nama_admin); ?>**</span>
            <a href="dashboard_admin.php?action=logout"><i class="fas fa-sign-out-alt"></i> Logout</a>
        </div>
    </div>

    <div class="container">
        
        <h1><i class="fas fa-user-shield"></i> Dashboard Administrator</h1>

        <div class="tabs">
            <button class="tab-button <?php echo ($content == 'ringkasan' ? 'active' : ''); ?>" onclick="changeTab('ringkasan')"><i class="fas fa-chart-line"></i> Ringkasan</button>
            <button class="tab-button <?php echo ($content == 'manajemen_lowongan' ? 'active' : ''); ?>" onclick="changeTab('manajemen_lowongan')"><i class="fas fa-briefcase"></i> Manajemen Lowongan</button>
            <button class="tab-button <?php echo ($content == 'tinjau_lamaran' ? 'active' : ''); ?>" onclick="changeTab('tinjau_lamaran')"><i class="fas fa-file-invoice"></i> Tinjau Lamaran</button>
            <button class="tab-button <?php echo ($content == 'manajemen_pengguna' ? 'active' : ''); ?>" onclick="changeTab('manajemen_pengguna')"><i class="fas fa-users"></i> Manajemen Pengguna</button>
        </div>

        <?php if ($pesan_status): 
            $status_class = (strpos(strtolower($pesan_status), 'berhasil') !== false || strpos(strtolower($pesan_status), 'sukses') !== false) ? 'sukses' : 'gagal';
        ?>
            <div class="status-message <?php echo $status_class; ?>">
                <?php echo $pesan_status; ?>
            </div>
        <?php endif; ?>

        <div id="ringkasan" class="tab-content" style="display: <?php echo ($content == 'ringkasan' ? 'block' : 'none'); ?>;">
            <h2>Statistik Cepat</h2>
            <div class="stats-grid">
                
                <div class="stat-card" onclick="changeTab('manajemen_pengguna')">
                    <div class="icon"><i class="fas fa-users"></i></div>
                    <div class="details">
                        <span class="label">Total Pengguna</span>
                        <span class="number"><?php echo htmlspecialchars($stats['total_pengguna']); ?></span>
                    </div>
                </div>
                
                <div class="stat-card" onclick="changeTab('manajemen_lowongan')">
                    <div class="icon"><i class="fas fa-briefcase"></i></div>
                    <div class="details">
                        <span class="label">Lowongan Aktif</span>
                        <span class="number"><?php echo htmlspecialchars($stats['lowongan_aktif']); ?></span>
                    </div>
                </div>
                
                <div class="stat-card" onclick="changeTab('tinjau_lamaran')">
                    <div class="icon"><i class="fas fa-paper-plane"></i></div>
                    <div class="details">
                        <span class="label">Total Lamaran</span>
                        <span class="number"><?php echo htmlspecialchars($stats['total_lamaran']); ?></span>
                    </div>
                </div>
                
            </div>
            <div class="card">
                <h3>Aktivitas Terbaru</h3>
                <div class="content-placeholder">
                    <p>Tempat untuk menampilkan lamaran atau lowongan terbaru. (Isi konten di sini)</p>
                </div>
            </div>
        </div>
        
        <div id="manajemen_lowongan" class="tab-content" style="display: <?php echo ($content == 'manajemen_lowongan' ? 'block' : 'none'); ?>;">
            <div class="card">
                <h2><i class="fas fa-briefcase"></i> Daftar Lowongan</h2>
                <a href="tambah_loker.php" class="btn-tambah">
                    <i class="fas fa-plus-circle"></i> Tambah Lowongan Baru
                </a>
                <div class="table-responsive">
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Perusahaan</th>
                                <th>Nama Pekerjaan</th>
                                <th>Gaji</th>
                                <th>Tipe</th>
                                <th>Status</th>
                                <th>Tgl. Publish</th>
                                <th>Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (!empty($list_lowongan)): ?>
                                <?php foreach ($list_lowongan as $lowongan): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($lowongan['id_lowongan']); ?></td>
                                    <td><?php echo htmlspecialchars($lowongan['perusahaan']); ?></td>
                                    <td><?php echo htmlspecialchars($lowongan['nama_pekerjaan']); ?></td>
                                    <td><?php echo htmlspecialchars($lowongan['gaji']); ?></td>
                                    <td><?php echo htmlspecialchars($lowongan['tipe_pekerjaan']); ?></td>
                                    <td>
                                        <?php 
                                        $status_loker = strtolower(htmlspecialchars($lowongan['status_lowongan']));
                                        $badge_loker_class = ($status_loker == 'aktif') ? 'aktif' : 'nonaktif';
                                        echo '<span class="status-badge ' . $badge_loker_class . '">' . htmlspecialchars($lowongan['status_lowongan']) . '</span>';
                                        ?>
                                    </td>
                                    <td><?php echo date('d M Y', strtotime($lowongan['tanggal_publish'])); ?></td>
                                    <td>
                                        <?php 
                                            $current_status_loker = strtolower(htmlspecialchars($lowongan['status_lowongan']));
                                            $btn_text = ($current_status_loker == 'aktif') ? 'Nonaktifkan' : 'Aktifkan';
                                            $btn_icon = ($current_status_loker == 'aktif') ? 'fa-lock' : 'fa-unlock';
                                        ?>
                                        <button 
                                            class="btn-action" 
                                            title="Ubah Status Lowongan: <?php echo $btn_text; ?>"
                                            onclick="confirmToggleLowonganStatus('<?php echo htmlspecialchars($lowongan['id_lowongan']); ?>', '<?php echo htmlspecialchars($lowongan['nama_pekerjaan']); ?>', '<?php echo $btn_text; ?>')">
                                            <i class="fas <?php echo $btn_icon; ?>"></i> Status
                                        </button>
                                        
                                        <button class="btn-action delete" title="Hapus Lowongan" onclick="confirmDeleteLowongan('<?php echo htmlspecialchars($lowongan['id_lowongan']); ?>', '<?php echo htmlspecialchars($lowongan['nama_pekerjaan']); ?>')">
                                            <i class="fas fa-trash-alt"></i> Hapus
                                        </button>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="8" style="text-align: center;">Tidak ada data lowongan yang ditemukan.</td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <div id="tinjau_lamaran" class="tab-content" style="display: <?php echo ($content == 'tinjau_lamaran' ? 'block' : 'none'); ?>;">
            <div class="card">
                <h2><i class="fas fa-file-invoice"></i> Daftar Lamaran Masuk</h2>
                <p>Total Lamaran: <strong><?php echo htmlspecialchars($stats['total_lamaran']); ?></strong></p>
                
                <div class="table-responsive">
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>ID Lamaran</th>
                                <th>Pelamar</th>
                                <th>Lowongan</th>
                                <th>Tgl. Lamaran</th>
                                <th>Status</th>
                                <th>Catatan</th>
                                <th>File CV</th>
                                <th>Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (!empty($list_lamaran)): ?>
                                <?php foreach ($list_lamaran as $lamaran): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($lamaran['id_lamaran']); ?></td>
                                    <td><?php echo htmlspecialchars($lamaran['nama_pengguna']); ?></td>
                                    <td><?php echo htmlspecialchars($lamaran['judul_lowongan']); ?></td>
                                    <td><?php echo date('d M Y', strtotime($lamaran['tanggal_lamaran'])); ?></td>
                                    <td>
                                        <?php 
                                        $status_l = strtolower(htmlspecialchars($lamaran['status']));
                                        $display_text = $status_l;
                                        $badge_l_class = '';
                                        
                                        if ($status_l == 'pending') {
                                            $badge_l_class = 'pending';
                                            $display_text = 'Menunggu';
                                        } elseif ($status_l == 'sukses') {
                                            $badge_l_class = 'sukses';
                                            $display_text = 'DITERIMA';
                                        } elseif ($status_l == 'ditolak') {
                                            $badge_l_class = 'ditolak';
                                            $display_text = 'DITOLAK';
                                        }
                                        
                                        echo '<span class="status-badge ' . $badge_l_class . '">' . htmlspecialchars($display_text) . '</span>';
                                        ?>
                                    </td>
                                    <td><?php echo htmlspecialchars($lamaran['catatan'] ?? '-'); ?></td>
                                    <td>
                                        <?php if ($lamaran['file_cv']): ?>
                                            <a href="uploads/cv/<?php echo htmlspecialchars($lamaran['file_cv']); ?>" target="_blank" title="Lihat CV">
                                                <i class="fas fa-file-pdf"></i> Lihat
                                            </a>
                                        <?php else: ?>
                                            -
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <div class="action-dropdown">
                                            <button class="btn-action" title="Ubah Status"><i class="fas fa-cog"></i> Kelola</button>
                                            <div class="action-dropdown-content">
                                                
                                                <?php if ($status_l !== 'sukses'): ?>
                                                    <a href="javascript:void(0)" onclick="confirmUpdateLamaran('<?php echo htmlspecialchars($lamaran['id_lamaran']); ?>', 'sukses')">✅ Terima (Diterima)</a>
                                                <?php endif; ?>

                                                <?php if ($status_l !== 'ditolak'): ?>
                                                    <a href="javascript:void(0)" onclick="confirmUpdateLamaran('<?php echo htmlspecialchars($lamaran['id_lamaran']); ?>', 'ditolak')">❌ Tolak</a>
                                                <?php endif; ?>
                                                
                                                <?php if ($status_l !== 'pending'): ?>
                                                    <a href="javascript:void(0)" onclick="confirmUpdateLamaran('<?php echo htmlspecialchars($lamaran['id_lamaran']); ?>', 'pending')">⏳ Tahan (Menunggu)</a>
                                                <?php endif; ?>
                                                
                                            </div>
                                        </div>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="8" style="text-align: center;">Tidak ada data lamaran yang ditemukan.</td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
                
            </div>
        </div>

        <div id="manajemen_pengguna" class="tab-content" style="display: <?php echo ($content == 'manajemen_pengguna' ? 'block' : 'none'); ?>;">
            <div class="card">
                <h2><i class="fas fa-users"></i> Daftar Semua Pengguna</h2>
                <p>Total Pengguna Terdaftar: <strong><?php echo htmlspecialchars($stats['total_pengguna']); ?></strong></p>
                <div class="table-responsive">
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Nama Pengguna</th>
                                <th>Email</th>
                                <th>Tgl. Daftar</th>
                                <th>Status Login</th>
                                <th>Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (!empty($list_pengguna)): ?>
                                <?php foreach ($list_pengguna as $pengguna): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($pengguna['id_pengguna']); ?></td>
                                    <td><?php echo htmlspecialchars($pengguna['nama_pengguna']); ?></td>
                                    <td><?php echo htmlspecialchars($pengguna['email_pengguna']); ?></td>
                                    <td><?php echo date('d M Y H:i', strtotime($pengguna['tanggal_daftar'])); ?></td>
                                    <td>
                                        <?php 
                                        $status = strtolower(htmlspecialchars($pengguna['status_login']));
                                        $badge_class = ($status == 'online') ? 'online' : 'offline';
                                        echo '<span class="status-badge ' . $badge_class . '">' . htmlspecialchars($status) . '</span>';
                                        ?>
                                    </td>
                                    <td>
                                        <button 
                                            class="btn-action delete" 
                                            title="Hapus Pengguna"
                                            onclick="confirmDeletePengguna('<?php echo htmlspecialchars($pengguna['id_pengguna']); ?>', '<?php echo htmlspecialchars($pengguna['nama_pengguna']); ?>')">
                                            <i class="fas fa-trash-alt"></i> Hapus
                                        </button>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="6" style="text-align: center;">Tidak ada data pengguna yang ditemukan.</td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
        
    </div>
    
    <script>
        function changeTab(tabName) {
            window.location.href = 'dashboard_admin.php?tab=' + tabName;
        }

        function confirmDeletePengguna(id, nama) {
            if (confirm('Apakah Anda yakin ingin menghapus pengguna "' + nama + '" (ID: ' + id + ')? Semua data terkait pengguna ini akan hilang.')) {
                window.location.href = 'dashboard_admin.php?action=delete_pengguna&id=' + id;
            }
        }
        
        function confirmDeleteLowongan(id, nama) {
            if (confirm('Apakah Anda yakin ingin menghapus lowongan "' + nama + '" (ID: ' + id + ')? Semua lamaran terkait lowongan ini juga akan hilang.')) {
                window.location.href = 'dashboard_admin.php?action=delete_lowongan&id=' + id;
            }
        }
        
        function confirmToggleLowonganStatus(id, nama, actionText) {
            if (confirm('Apakah Anda yakin ingin **' + actionText + '** lowongan "' + nama + '" (ID: ' + id + ')?')) {
                window.location.href = 'dashboard_admin.php?action=toggle_lowongan_status&id=' + id;
            }
        }

        function confirmUpdateLamaran(id, status) {
            var displayStatus = '';
            var actionText = '';
            
            if (status === 'sukses') {
                displayStatus = 'DITERIMA';
                actionText = 'menerima';
            } else if (status === 'ditolak') {
                displayStatus = 'DITOLAK';
                actionText = 'menolak';
            } else if (status === 'pending') {
                displayStatus = 'MENUNGGU';
                actionText = 'menahan';
            }

            if (confirm('Yakin ingin ' + actionText + ' lamaran ID ' + id + ' dan mengubah status menjadi ' + displayStatus + '?')) {
                window.location.href = 'dashboard_admin.php?action=update_lamaran_status&id=' + id + '&status=' + status;
            }
        }
    </script>
</body>
</html>