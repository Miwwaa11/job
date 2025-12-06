<?php
// MENGAKTIFKAN OUTPUT BUFFERING UNTUK MENCEGAH MASALAH HEADER
ob_start();
session_start();
// Pastikan file koneksi.php ada di direktori yang sama
include 'koneksi.php'; 

// 1. PROTEKSI HALAMAN ADMIN
if (!isset($_SESSION['status_login']) || $_SESSION['status_login'] !== TRUE || $_SESSION['role'] !== 'admin') {
    header('Location: admin.php'); 
    exit;
}

// Ambil ID admin yang sedang login
$tb_admin = $_SESSION['id_admin'] ?? 1; 
$success_message = '';
$error_message = '';
$id_lokasi_akhir = null; 

// Jika ada pesan sukses dari sesi, tampilkan dan hapus
if (isset($_SESSION['success_message'])) {
    $success_message = $_SESSION['success_message'];
    unset($_SESSION['success_message']);
}

// =======================================================
// 2. LOGIKA PROSES FORM SUBMIT
// =======================================================
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    
    // Ambil dan bersihkan data dari form LOWONGAN
    $perusahaan             = mysqli_real_escape_string($koneksi, $_POST['perusahaan']);
    $nama_pekerjaan         = mysqli_real_escape_string($koneksi, $_POST['nama_pekerjaan']);
    $deskripsi              = mysqli_real_escape_string($koneksi, $_POST['deskripsi']);
    $gaji                   = mysqli_real_escape_string($koneksi, $_POST['gaji']);
    $tipe_pekerjaan         = mysqli_real_escape_string($koneksi, $_POST['tipe_pekerjaan']);
    $status_lowongan        = mysqli_real_escape_string($koneksi, $_POST['status_lowongan']);
    $tanggal_publish        = date('Y-m-d'); 
    $tanggal_tutup          = mysqli_real_escape_string($koneksi, $_POST['tanggal_tutup']);
    $pendidikan_minimal     = mysqli_real_escape_string($koneksi, $_POST['pendidikan_minimal']);
    $pengalaman_minimal     = mysqli_real_escape_string($koneksi, $_POST['pengalaman_minimal']);
    
    // Ambil input Lokasi
    $id_lokasi_exist        = mysqli_real_escape_string($koneksi, $_POST['id_lokasi_exist']);
    $lokasi_baru_nama       = trim(mysqli_real_escape_string($koneksi, $_POST['lokasi_baru_nama']));
    $lokasi_baru_negara     = mysqli_real_escape_string($koneksi, $_POST['lokasi_baru_negara']);

    // -----------------------------------------------------------------
    // C. LOGIKA UPLOAD GAMBAR ILUSTRASI
    // -----------------------------------------------------------------
    $gambar_ilustrasi = ''; 
    
    if (isset($_FILES['gambar_ilustrasi']) && $_FILES['gambar_ilustrasi']['error'] === UPLOAD_ERR_OK) {
        $file_tmp = $_FILES['gambar_ilustrasi']['tmp_name'];
        $file_ext = strtolower(pathinfo($_FILES['gambar_ilustrasi']['name'], PATHINFO_EXTENSION));
        $allowed_ext = ['jpg', 'jpeg', 'png', 'gif'];
        $max_size = 2 * 1024 * 1024; // 2 MB

        if (!in_array($file_ext, $allowed_ext)) {
            $error_message = "Gagal: Format file gambar tidak didukung. Hanya JPG, JPEG, PNG, GIF.";
        } elseif ($_FILES['gambar_ilustrasi']['size'] > $max_size) {
            $error_message = "Gagal: Ukuran file gambar terlalu besar (Maks. 2MB).";
        } else {
            $upload_dir = 'uploads/lowongan/'; 
            
            if (!is_dir($upload_dir)) {
                mkdir($upload_dir, 0777, true); 
            }
            
            $file_name_new = 'job_' . $tb_admin . '_' . time() . '.' . $file_ext;
            $file_destination = $upload_dir . $file_name_new;

            if (move_uploaded_file($file_tmp, $file_destination)) {
                $gambar_ilustrasi = $file_destination; 
            } else {
                $error_message = "Gagal memindahkan file gambar ke direktori server.";
            }
        }
    }
    // -----------------------------------------------------------------


    // -----------------------------------------------------------------
    // A. LOGIKA PENGAMBILAN/PENAMBAHAN LOKASI
    // -----------------------------------------------------------------
    if (empty($error_message)) {
        
        if (!empty($id_lokasi_exist)) {
            $id_lokasi_akhir = $id_lokasi_exist;

        } elseif (!empty($lokasi_baru_nama)) {
            
            $check_query = "SELECT id_lokasi FROM id_lokasi WHERE nama_lokasi = '$lokasi_baru_nama'"; 
            $check_result = mysqli_query($koneksi, $check_query);
            
            if (mysqli_num_rows($check_result) > 0) {
                $row = mysqli_fetch_assoc($check_result);
                $id_lokasi_akhir = $row['id_lokasi'];
            } else {
                $insert_lokasi_query = "INSERT INTO id_lokasi (nama_lokasi, negara) VALUES ('$lokasi_baru_nama', '$lokasi_baru_negara')"; 
                if (mysqli_query($koneksi, $insert_lokasi_query)) {
                    $id_lokasi_akhir = mysqli_insert_id($koneksi); 
                } else {
                    $error_message = "Gagal menambah lokasi baru: " . mysqli_error($koneksi);
                }
            }
        } else {
            $error_message = "Harap pilih lokasi yang sudah ada atau masukkan lokasi baru.";
        }
    }
    // -----------------------------------------------------------------
    
    
    // -----------------------------------------------------------------
    // B. LOGIKA INSERT LOWONGAN
    // -----------------------------------------------------------------
    if (empty($error_message) && $id_lokasi_akhir) {
        
        // Query INSERT ke tb_lowongan
        $query = "INSERT INTO tb_lowongan (
                      perusahaan, nama_pekerjaan, deskripsi, gaji, tipe_pekerjaan, 
                      status_lowongan, tanggal_publish, tanggal_tutup, gambar_ilustrasi, 
                      pendidikan_minimal, pengalaman_minimal, tb_admin, id_lokasi
                    ) VALUES (
                      '$perusahaan', '$nama_pekerjaan', '$deskripsi', '$gaji', '$tipe_pekerjaan', 
                      '$status_lowongan', '$tanggal_publish', '$tanggal_tutup', '$gambar_ilustrasi', 
                      '$pendidikan_minimal', '$pengalaman_minimal', '$tb_admin', '$id_lokasi_akhir'
                    )";

        if (mysqli_query($koneksi, $query)) {
            $_SESSION['success_message'] = "Data lowongan **$nama_pekerjaan** berhasil ditambahkan!";
            
            // Redirect instan ke dashboard
            header("Location: dashboard_admin.php?tab=manajemen_lowongan");
            ob_end_flush(); 
            exit; 
        } else {
            $error_message = "Gagal menambahkan data lowongan: " . mysqli_error($koneksi);
        }
    }
}
// -----------------------------------------------------------------


// =======================================================
// 3. AMBIL DATA LOKASI UNTUK DROPDOWN
// =======================================================
$query_lokasi = "SELECT id_lokasi, nama_lokasi FROM id_lokasi ORDER BY nama_lokasi ASC"; 
$result_lokasi = mysqli_query($koneksi, $query_lokasi);
$lokasi_options = [];
if ($result_lokasi) {
    while ($row = mysqli_fetch_assoc($result_lokasi)) {
        $lokasi_options[] = $row;
    }
}

// KIRIMKAN BUFFER HTML SETELAH SEMUA LOGIKA PHP SELESAI
ob_end_flush();
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tambah Lowongan Baru | Admin Panel</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <style>
        /* CSS Styling Sederhana */
        body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; background-color: #f4f6f9; color: #495057; line-height: 1.6; }
        .container { max-width: 900px; margin: 30px auto; padding: 30px; background: #ffffff; border-radius: 12px; box-shadow: 0 0 20px rgba(0,0,0,0.05); }
        .page-header { display: flex; justify-content: space-between; align-items: center; border-bottom: 3px solid #e9ecef; margin-bottom: 25px; padding-bottom: 15px; }
        .page-header h1 { color: #007bff; font-size: 24px; font-weight: 600; margin: 0; }
        .back-link { color: #6c757d; text-decoration: none; font-weight: 500; padding: 8px 15px; border: 1px solid #ced4da; border-radius: 6px; transition: background-color 0.3s; }
        .back-link:hover { background-color: #e9ecef; color: #495057; }
        .form-row { display: grid; grid-template-columns: 1fr 1fr; gap: 20px; margin-bottom: 15px; }
        .form-group { margin-bottom: 20px; }
        .form-group.full-width { grid-column: 1 / -1; } 
        label { display: block; margin-bottom: 8px; font-weight: 600; color: #343a40; }
        input[type="text"], input[type="date"], input[type="number"], select, textarea {
            width: 100%; padding: 12px; border: 1px solid #ced4da; border-radius: 6px; box-sizing: border-box;
            font-size: 15px; transition: border-color 0.3s;
        }
        input[type="file"] {
            padding: 10px 0; 
            border: none;
        }
        input:focus, select:focus, textarea:focus { border-color: #007bff; outline: none; box-shadow: 0 0 0 0.2rem rgba(0, 123, 255, 0.25); }
        textarea { resize: vertical; height: 120px; }
        .btn-submit { 
            background-color: #28a745; color: white; padding: 12px 25px; border: none; border-radius: 6px; 
            cursor: pointer; font-size: 16px; font-weight: 600; margin-top: 10px; float: right;
            transition: background-color 0.3s;
        }
        .btn-submit:hover { background-color: #218838; }
        .alert { padding: 15px; margin-bottom: 20px; border-radius: 6px; font-weight: 500; }
        .alert-success { background-color: #d4edda; color: #155724; border: 1px solid #c3e6cb; }
        .alert-error { background-color: #f8d7da; color: #721c24; border: 1px solid #f5c6cb; }
        .lokasi-baru-input { 
            border-top: 1px solid #e9ecef; 
            padding-top: 15px; 
            margin-top: 15px; 
        }
        /* Styling untuk Box Gambar */
        .upload-box {
            border: 2px dashed #007bff;
            padding: 20px;
            border-radius: 8px;
            text-align: center;
            background-color: #f0f8ff;
        }
        .upload-box label {
            color: #007bff;
            font-size: 1.1em;
            margin-bottom: 0;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="page-header">
            <h1><i class="fas fa-plus-circle"></i> Tambah Lowongan Baru</h1>
            <a href="dashboard_admin.php?tab=manajemen_lowongan" class="back-link">
                <i class="fas fa-arrow-left"></i> Kembali
            </a>
        </div>

        <?php if ($success_message): ?>
            <div class="alert alert-success"><?php echo $success_message; ?></div>
        <?php endif; ?>

        <?php if ($error_message): ?>
            <div class="alert alert-error"><?php echo $error_message; ?></div>
        <?php endif; ?>

        <form action="tambah_loker.php" method="POST" enctype="multipart/form-data">
            
            <div class="form-row">
                <div class="form-group">
                    <label for="perusahaan">Nama Perusahaan <span style="color: red;">*</span></label>
                    <input type="text" id="perusahaan" name="perusahaan" required>
                </div>
                <div class="form-group">
                    <label for="nama_pekerjaan">Nama Posisi / Pekerjaan <span style="color: red;">*</span></label>
                    <input type="text" id="nama_pekerjaan" name="nama_pekerjaan" required>
                </div>
            </div>

            <div class="form-group full-width">
                <label for="deskripsi">Deskripsi Pekerjaan <span style="color: red;">*</span></label>
                <textarea id="deskripsi" name="deskripsi" required></textarea>
            </div>

            <div class="form-group full-width upload-box">
                <label for="gambar_ilustrasi"><i class="fas fa-image"></i> Gambar Ilustrasi / Logo Perusahaan (Opsional)</label>
                <input type="file" id="gambar_ilustrasi" name="gambar_ilustrasi" accept="image/*" style="display: block; margin: 5px auto;">
                <small style="display: block; color: #6c757d;">Maks. 2MB. Format: JPG, PNG, GIF. Jika diisi, akan tampil sebagai banner.</small>
            </div>
            <div class="form-row">
                <div class="form-group">
                    <label for="tipe_pekerjaan">Tipe Pekerjaan <span style="color: red;">*</span></label>
                    <select id="tipe_pekerjaan" name="tipe_pekerjaan" required>
                        <option value="">-- Pilih Tipe --</option>
                        <option value="Full-Time">Full-Time</option>
                        <option value="Part-Time">Part-Time</option>
                        <option value="Kontrak">Kontrak</option>
                        <option value="Freelance">Freelance</option>
                    </select>
                </div>
                <div class="form-group">
                    <label for="gaji">Gaji (Opsional)</label>
                    <input type="text" id="gaji" name="gaji" placeholder="Cth: Rp 5.000.000 - 8.000.000">
                </div>
            </div>

            <div class="form-row">
                <div class="form-group">
                    <label for="pendidikan_minimal">Pendidikan Minimal (Opsional)</label>
                    <input type="text" id="pendidikan_minimal" name="pendidikan_minimal" placeholder="Cth: S1 Teknik Informatika">
                </div>
                <div class="form-group">
                    <label for="pengalaman_minimal">Pengalaman Minimal (Opsional)</label>
                    <input type="text" id="pengalaman_minimal" name="pengalaman_minimal" placeholder="Cth: 1 tahun, Fresh Graduate OK">
                </div>
            </div>

            <div class="form-row">
                <div class="form-group">
                    <label for="tanggal_tutup">Batas Waktu Lamaran (Tanggal Tutup) <span style="color: red;">*</span></label>
                    <input type="date" id="tanggal_tutup" name="tanggal_tutup" required>
                </div>
                <div class="form-group">
                    <label for="status_lowongan">Status Lowongan <span style="color: red;">*</span></label>
                    <select id="status_lowongan" name="status_lowongan" required>
                        <option value="Aktif">Aktif</option>
                        <option value="Draft">Draft</option>
                        <option value="Tutup">Tutup</option>
                    </select>
                </div>
            </div>
            
            <div class="form-group full-width">
                <label for="id_lokasi_exist" style="font-size: 1.1em; border-bottom: 2px dotted #ccc;">1. Pilih Lokasi yang Sudah Ada <span style="color: red;">*</span></label>
                <select id="id_lokasi_exist" name="id_lokasi_exist">
                    <option value="">-- PILIH LOKASI TERSIMPAN --</option>
                    <?php
                    // Menampilkan opsi lokasi dari database
                    if (!empty($lokasi_options)) {
                        foreach ($lokasi_options as $lokasi) {
                            echo '<option value="' . htmlspecialchars($lokasi['id_lokasi']) . '">' . htmlspecialchars($lokasi['nama_lokasi']) . '</option>';
                        }
                    } else {
                        echo '<option value="" disabled>-- Belum ada Lokasi Tersimpan --</option>';
                    }
                    ?>
                </select>
                
                <div class="lokasi-baru-input">
                    <label style="font-size: 1.1em; border-bottom: 2px dotted #ccc;">2. ATAU Tambahkan Lokasi Baru</label>
                    <small style="display: block; margin-bottom: 10px; color: #6c757d;">(Isi ini jika lokasi di atas tidak ada)</small>
                    
                    <div class="form-row">
                        <div class="form-group" style="margin-bottom: 0;">
                            <label for="lokasi_baru_nama">Nama Kota/Daerah</label>
                            <input type="text" id="lokasi_baru_nama" name="lokasi_baru_nama" placeholder="Contoh: Yogyakarta, Makassar">
                        </div>
                        <div class="form-group" style="margin-bottom: 0;">
                            <label for="lokasi_baru_negara">Negara</label>
                            <input type="text" id="lokasi_baru_negara" name="lokasi_baru_negara" value="Indonesia" placeholder="Contoh: Indonesia">
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="form-group full-width">
                <button type="submit" class="btn-submit">
                    <i class="fas fa-paper-plane"></i> Publikasikan Lowongan
                </button>
                <div style="clear: both;"></div>
            </div>

        </form>

    </div>
</body>
</html>