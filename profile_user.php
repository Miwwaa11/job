<?php
session_start();
include 'koneksi.php';

// 1. INISIASI DAN OTENTIKASI
if (!isset($_SESSION['status_login']) || $_SESSION['status_login'] !== TRUE || $_SESSION['role'] !== 'user') {
    header("Location: user.php");
    exit;
}

$id_user = $_SESSION['id_user'];
$error_message = '';
$success_message = '';
$user_data = null;
$pendidikan_options = ['SD', 'SMP', 'SMA/SMK', 'D1', 'D3', 'S1', 'S2', 'S3'];

// Tentukan mode tampilan (view atau edit)
$mode = (isset($_GET['mode']) && $_GET['mode'] === 'edit') ? 'edit' : 'view';


// 2. PROSES UPDATE DATA (Jika formulir dikirimkan)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $mode === 'edit') {
    
    // Pastikan ID pengguna yang disubmit cocok dengan ID sesi (walaupun form ada di satu file)
    $id_pengguna_post = $_POST['id_pengguna'] ?? null;
    if ($id_pengguna_post != $id_user) {
        $_SESSION['error_message'] = "Akses ditolak. ID pengguna tidak cocok.";
        header("Location: profile_user.php");
        exit;
    }

    // Sanitasi dan validasi input
    $nama_pengguna = trim($_POST['nama_pengguna'] ?? '');
    $tanggal_lahir = $_POST['tanggal_lahir'] ?? null;
    $no_hp = trim($_POST['no_hp'] ?? '');
    $pendidikan_terakhir = $_POST['pendidikan_terakhir'] ?? null;
    $alamat = trim($_POST['alamat'] ?? '');
    $pengalaman_kerja = trim($_POST['pengalaman_kerja'] ?? '');

    // Validasi dasar
    if (empty($nama_pengguna)) {
        $error_message = "Nama lengkap harus diisi.";
        // Mode tetap edit
    } else {
        // Lakukan UPDATE ke database
        $query_update = "UPDATE tb_pengguna SET
            nama_pengguna = ?,
            no_hp = ?,
            alamat = ?,
            tanggal_lahir = ?,
            pendidikan_terakhir = ?,
            pengalaman_kerja = ?
        WHERE id_pengguna = ?";

        if ($stmt = mysqli_prepare($koneksi, $query_update)) {
            mysqli_stmt_bind_param($stmt, "ssssssi", 
                $nama_pengguna, 
                $no_hp, 
                $alamat, 
                $tanggal_lahir, 
                $pendidikan_terakhir, 
                $pengalaman_kerja,
                $id_user
            );

            if (mysqli_stmt_execute($stmt)) {
                // Update data sesi dan redirect ke mode view
                $_SESSION['nama'] = $nama_pengguna; 
                $_SESSION['success_message'] = "Profil berhasil diperbarui!";
                header("Location: profile_user.php");
                exit;
            } else {
                $error_message = "Gagal memperbarui profil: " . mysqli_error($koneksi);
            }
            mysqli_stmt_close($stmt);

        } else {
            $error_message = "Gagal mempersiapkan statement SQL untuk update: " . mysqli_error($koneksi);
        }
    }
}


// 3. AMBIL DATA PENGGUNA TERBARU (untuk ditampilkan, baik di mode view atau edit)
// Cek apakah ada pesan dari sesi (setelah redirect dari proses update yang gagal/success)
if (isset($_SESSION['success_message'])) {
    $success_message = $_SESSION['success_message'];
    unset($_SESSION['success_message']);
}
if (isset($_SESSION['error_message'])) {
    $error_message = $_SESSION['error_message'];
    unset($_SESSION['error_message']);
    // Jika ada error dari sesi (misalnya ID tidak cocok), mode dikembalikan ke view, jadi kita tidak perlu mengembalikan ke edit di sini.
}


$query_ambil_data = "SELECT 
    nama_pengguna, 
    email_pengguna, 
    no_hp, 
    alamat, 
    tanggal_lahir, 
    pendidikan_terakhir, 
    pengalaman_kerja 
FROM 
    tb_pengguna 
WHERE 
    id_pengguna = ?";

if ($stmt = mysqli_prepare($koneksi, $query_ambil_data)) {
    mysqli_stmt_bind_param($stmt, "i", $id_user);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    
    if (mysqli_num_rows($result) === 0) {
        header("Location: dashboard_pengguna.php");
        exit;
    }
    $user_data = mysqli_fetch_assoc($result);
    mysqli_stmt_close($stmt);

} else {
    // Jika gagal ambil data awal, ini sangat fatal
    $error_message = "Gagal memuat data awal: " . mysqli_error($koneksi);
    $user_data = null; 
}

// Tutup koneksi di akhir skrip
// mysqli_close($koneksi); // Kita pindahkan ke akhir HTML

?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo ($mode === 'edit' ? 'Edit' : 'Lihat'); ?> Profil | <?php echo htmlspecialchars($user_data['nama_pengguna'] ?? 'Profil'); ?></title>
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
            margin: 0;
            padding: 0;
        }

        .container { 
            max-width: 900px; 
            margin: 40px auto; 
            padding: 0 20px;
        }

        .header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 30px;
        }

        .header h1 {
            color: var(--primary-color);
            border-bottom: 3px solid var(--secondary-color);
            padding-bottom: 10px;
            font-size: 2em;
            font-weight: 700;
        }

        /* ACTIONS */
        .actions {
            display: flex;
            gap: 10px;
        }
        
        .action-link {
            text-decoration: none;
            font-weight: 600;
            padding: 8px 15px;
            border-radius: 6px;
            transition: all 0.3s;
        }
        .back-link {
            color: var(--primary-color);
            border: 2px solid var(--primary-color);
        }
        .back-link:hover {
            background-color: var(--primary-color);
            color: white;
        }
        .edit-link {
            background-color: var(--secondary-color);
            color: white;
            border: 2px solid var(--secondary-color);
        }
        .edit-link:hover {
            background-color: #0088b3;
            border-color: #0088b3;
        }


        .profile-card {
            background-color: var(--bg-card);
            padding: 30px 40px;
            border-radius: 12px;
            box-shadow: var(--shadow-subtle);
        }

        /* VIEW MODE STYLES */
        .view-data {
            display: grid;
            grid-template-columns: 1fr 2fr; 
            gap: 15px 25px;
            padding-top: 15px;
        }
        .view-data .label {
            font-weight: 600;
            color: var(--text-light);
            text-transform: uppercase;
            font-size: 0.9em;
        }
        .view-data .value {
            font-weight: 500;
            color: var(--text-dark);
            border-bottom: 1px dashed #eee;
            padding-bottom: 5px;
        }
        .view-data div:nth-child(even) {
            border-bottom: 1px dashed #eee;
        }
        .view-data .full-width {
            grid-column: 1 / -1;
            padding-top: 10px;
        }

        /* FORM STYLES */
        .form-group {
            margin-bottom: 20px;
        }

        .form-group label {
            display: block;
            margin-bottom: 8px;
            font-weight: 600;
            color: var(--text-dark);
        }

        .form-group input[type="text"],
        .form-group input[type="email"],
        .form-group input[type="date"],
        .form-group select,
        .form-group textarea {
            width: 100%;
            padding: 12px;
            border: 1px solid #ced4da;
            border-radius: 6px;
            box-sizing: border-box;
            font-size: 1em;
            font-family: 'Poppins', sans-serif;
            transition: border-color 0.3s, box-shadow 0.3s;
        }

        .form-group input:focus,
        .form-group select:focus,
        .form-group textarea:focus {
            border-color: var(--primary-color);
            box-shadow: 0 0 0 0.2rem rgba(0, 77, 153, 0.25);
            outline: none;
        }

        .form-group textarea {
            resize: vertical;
            min-height: 100px;
        }

        .btn-submit {
            display: block;
            width: 100%;
            padding: 14px;
            background-color: var(--primary-color);
            color: white;
            border: none;
            border-radius: 8px;
            font-size: 1.1em;
            font-weight: 600;
            cursor: pointer;
            transition: background-color 0.3s;
        }

        .btn-submit:hover {
            background-color: #003366;
        }

        /* MESSAGES */
        .message-box {
            padding: 15px;
            border-radius: 6px;
            margin-bottom: 20px;
            font-weight: 500;
            display: flex;
            align-items: center;
        }
        .message-box i {
            margin-right: 10px;
        }

        .success {
            background-color: #d4edda; 
            color: #155724; 
            border: 1px solid #c3e6cb;
        }

        .error {
            background-color: #f8d7da; 
            color: #721c24; 
            border: 1px solid #f5c6cb;
        }

        /* Responsiveness for view-data */
        @media (max-width: 600px) {
            .view-data {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>
<div class="container">

    <header class="header">
        <h1>
            <i class="fas fa-user-circle"></i> 
            <?php echo ($mode === 'edit' ? 'Edit Data Profil' : 'Profil Pengguna'); ?>
        </h1>
        <div class="actions">
            <a href="dashboard_pengguna.php" class="action-link back-link">
                <i class="fas fa-arrow-left"></i> Dashboard
            </a>
            
            <?php if ($mode === 'view'): ?>
                <a href="profile_user.php?mode=edit" class="action-link edit-link">
                    <i class="fas fa-edit"></i> Edit Profil
                </a>
            <?php else: ?>
                 <a href="profile_user.php" class="action-link back-link">
                    <i class="fas fa-times"></i> Batal Edit
                </a>
            <?php endif; ?>
        </div>
    </header>

    <?php if ($success_message): ?>
        <div class="message-box success">
            <i class="fas fa-check-circle"></i> <?php echo $success_message; ?>
        </div>
    <?php endif; ?>

    <?php if ($error_message): ?>
        <div class="message-box error">
            <i class="fas fa-times-circle"></i> <?php echo $error_message; ?>
        </div>
    <?php endif; ?>
    
    <?php if ($user_data): ?>
    <div class="profile-card">
        
        <?php if ($mode === 'view'): ?>
            <div class="view-data">
                <div class="label">Nama Lengkap</div>
                <div class="value"><?php echo htmlspecialchars($user_data['nama_pengguna']); ?></div>

                <div class="label">Email</div>
                <div class="value"><?php echo htmlspecialchars($user_data['email_pengguna']); ?></div>

                <div class="label">Tanggal Lahir</div>
                <div class="value"><?php echo htmlspecialchars($user_data['tanggal_lahir'] ?: 'Belum diisi'); ?></div>

                <div class="label">Nomor HP</div>
                <div class="value"><?php echo htmlspecialchars($user_data['no_hp'] ?: 'Belum diisi'); ?></div>

                <div class="label">Pendidikan Terakhir</div>
                <div class="value"><?php echo htmlspecialchars($user_data['pendidikan_terakhir'] ?: 'Belum diisi'); ?></div>

                <div class="label full-width" style="border-bottom:none; margin-top: 10px;">Alamat Tinggal</div>
                <div class="value full-width" style="word-wrap: break-word;"><?php echo nl2br(htmlspecialchars($user_data['alamat'] ?: 'Belum diisi')); ?></div>

                <div class="label full-width" style="border-bottom:none; margin-top: 10px;">Pengalaman Kerja</div>
                <div class="value full-width" style="word-wrap: break-word;"><?php echo nl2br(htmlspecialchars($user_data['pengalaman_kerja'] ?: 'Belum diisi')); ?></div>
            </div>

        <?php else: ?>
            <form action="profile_user.php?mode=edit" method="POST">
                <input type="hidden" name="id_pengguna" value="<?php echo $id_user; ?>">

                <div class="form-group">
                    <label for="nama_pengguna"><i class="fas fa-user"></i> Nama Lengkap</label>
                    <input type="text" id="nama_pengguna" name="nama_pengguna" value="<?php echo htmlspecialchars($user_data['nama_pengguna']); ?>" required>
                </div>

                <div class="form-group">
                    <label for="email_pengguna"><i class="fas fa-envelope"></i> Email</label>
                    <input type="email" id="email_pengguna" name="email_pengguna" value="<?php echo htmlspecialchars($user_data['email_pengguna']); ?>" readonly style="background-color: #e9ecef;">
                    <small style="color: #6c757d; display: block; margin-top: 5px;">*Email tidak dapat diubah di halaman ini.</small>
                </div>
                
                <div class="form-group">
                    <label for="tanggal_lahir"><i class="fas fa-calendar-alt"></i> Tanggal Lahir</label>
                    <input type="date" id="tanggal_lahir" name="tanggal_lahir" value="<?php echo htmlspecialchars($user_data['tanggal_lahir'] ?? ''); ?>">
                </div>

                <div class="form-group">
                    <label for="no_hp"><i class="fas fa-phone"></i> Nomor HP</label>
                    <input type="text" id="no_hp" name="no_hp" value="<?php echo htmlspecialchars($user_data['no_hp'] ?? ''); ?>">
                </div>

                <div class="form-group">
                    <label for="pendidikan_terakhir"><i class="fas fa-graduation-cap"></i> Pendidikan Terakhir</label>
                    <select id="pendidikan_terakhir" name="pendidikan_terakhir">
                        <option value="">-- Pilih Pendidikan --</option>
                        <?php 
                        $current_pendidikan = $user_data['pendidikan_terakhir'] ?? '';
                        foreach ($pendidikan_options as $option): 
                        ?>
                            <option value="<?php echo $option; ?>" 
                                <?php echo ($option == $current_pendidikan) ? 'selected' : ''; ?>>
                                <?php echo $option; ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div class="form-group">
                    <label for="alamat"><i class="fas fa-map-marker-alt"></i> Alamat Tinggal</label>
                    <textarea id="alamat" name="alamat"><?php echo htmlspecialchars($user_data['alamat'] ?? ''); ?></textarea>
                </div>

                <div class="form-group">
                    <label for="pengalaman_kerja"><i class="fas fa-briefcase"></i> Pengalaman Kerja (Deskripsi Singkat)</label>
                    <textarea id="pengalaman_kerja" name="pengalaman_kerja" placeholder="Jelaskan secara singkat pengalaman kerja Anda..."><?php echo htmlspecialchars($user_data['pengalaman_kerja'] ?? ''); ?></textarea>
                </div>

                <button type="submit" class="btn-submit">
                    <i class="fas fa-save"></i> Simpan Perubahan Profil
                </button>
            </form>
        <?php endif; ?>
    </div>
    <?php endif; ?>

</div>
<?php mysqli_close($koneksi); ?>
</body>
</html>