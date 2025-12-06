<?php
session_start();
include 'koneksi.php'; 

$error_message = '';

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['submit_registrasi'])) {
    
    $nama = $_POST['nama_pengguna']; 
    $email = $_POST['email_pengguna']; 
    $password_mentah = $_POST['password_pengguna'];
    
    $no_hp = $_POST['no_hp'];
    $alamat = $_POST['alamat'];
    $tanggal_lahir = $_POST['tanggal_lahir'];
    $pendidikan = $_POST['pendidikan_terakhir'];
    $pengalaman = $_POST['pengalaman_kerja'];

    
    $cek_email_sql = "SELECT id_pengguna FROM tb_pengguna WHERE email_pengguna = ?";
    if ($stmt_cek = mysqli_prepare($koneksi, $cek_email_sql)) {
        mysqli_stmt_bind_param($stmt_cek, 's', $email);
        mysqli_stmt_execute($stmt_cek);
        mysqli_stmt_store_result($stmt_cek);

        if (mysqli_stmt_num_rows($stmt_cek) > 0) {
            $error_message = "Email ini sudah terdaftar. Silakan gunakan email lain atau Login.";
        } else {
            $password_hash = password_hash($password_mentah, PASSWORD_DEFAULT);
            $tanggal_daftar = date('Y-m-d H:i:s');
            $role = 'user'; 
            $status_login = 'offline'; 
            
            $sql = "INSERT INTO tb_pengguna (
                        nama_pengguna, email_pengguna, password_pengguna, no_hp, alamat, tanggal_lahir, 
                        pendidikan_terakhir, pengalaman_kerja,
                        tanggal_daftar, role, status_login
                    ) VALUES (
                        ?, ?, ?, ?, ?, ?, 
                        ?, ?,
                        ?, ?, ?
                    )";

            if ($stmt_insert = mysqli_prepare($koneksi, $sql)) {
                mysqli_stmt_bind_param($stmt_insert, 'sssssssssss', 
                    $nama, $email, $password_hash, $no_hp, $alamat, $tanggal_lahir, 
                    $pendidikan, $pengalaman, $tanggal_daftar, $role, $status_login
                );

                if (mysqli_stmt_execute($stmt_insert)) {
                    $_SESSION['registration_success'] = "Pendaftaran berhasil! Akun Anda telah dibuat. Silakan login.";
                    mysqli_stmt_close($stmt_insert);
                    header('Location: user.php');
                    exit; 
                } else {
                    $error_message = "Gagal menyimpan data. Error: " . mysqli_error($koneksi);
                }
                mysqli_stmt_close($stmt_insert);
            } else {
                $error_message = "Gagal mempersiapkan query pendaftaran.";
            }
        }
        mysqli_stmt_close($stmt_cek);
    } else {
        $error_message = "Gagal mempersiapkan query pengecekan email.";
    }
}

if (isset($koneksi)) {
    mysqli_close($koneksi);
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <title>Daftar Akun Baru | KarirID</title> 
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Roboto:wght@400;700&display=swap');
        
        :root {
            --primary-color: #004d99; 
            --hover-color: #003366; 
            --background-color: #f4f7f6;
            --box-shadow-color: rgba(0, 0, 0, 0.15);
        }
        
        body {
            font-family: 'Roboto', sans-serif;
            background-color: var(--background-color);
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
            margin: 0;
            color: #333;
        }
        
        .regis-container {
            background-color: #ffffff; 
            padding: 45px;
            border-radius: 12px;
            box-shadow: 0 10px 40px var(--box-shadow-color);
            width: 100%;
            max-width: 500px;
            text-align: center;
            border: 1px solid #e0e0e0;
        }
        
        h2 {
            color: var(--primary-color);
            margin-bottom: 35px;
            margin-top: 0;
            padding-bottom: 15px;
            border-bottom: 2px solid #eee;
            font-weight: 700;
        }
        
        label {
            display: block;
            text-align: left;
            margin-bottom: 5px;
            font-weight: 700;
            color: #444; 
            font-size: 0.9em;
        }
        
        input[type="text"],
        input[type="email"], 
        input[type="password"],
        input[type="tel"],
        input[type="date"],
        textarea,
        select { 
            width: 100%;
            padding: 14px; 
            margin-bottom: 20px;
            border: 1px solid #ccc; 
            border-radius: 8px;
            box-sizing: border-box;
            font-size: 1em;
            transition: border-color 0.3s, box-shadow 0.3s;
        }
        
        input:focus, select:focus, textarea:focus {
            border-color: var(--primary-color); 
            outline: none;
            box-shadow: 0 0 0 2px rgba(0, 77, 153, 0.2); 
        }

        button[type="submit"] {
            width: 100%;
            background-image: linear-gradient(to top, var(--primary-color), #0056b3); 
            color: white;
            padding: 15px 20px;
            margin-top: 10px;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            font-size: 1.1em;
            font-weight: 700;
            letter-spacing: 0.5px;
            transition: background-image 0.3s;
        }
        
        button[type="submit"]:hover {
            background-image: linear-gradient(to bottom, var(--hover-color), #004d99); 
        }
        
        p.login-link {
            margin-top: 25px;
            font-size: 0.9em;
            color: #777;
        }
        
        p.login-link a {
            color: var(--primary-color); 
            text-decoration: none;
            font-weight: 700;
        }

        .message {
            padding: 15px;
            margin-bottom: 20px;
            border-radius: 6px;
            font-weight: 600;
            text-align: left;
            font-size: 0.95em;
        }
        .error-message {
            color: #dc3545; 
            background-color: #f8d7da;
            border: 1px solid #f5c6cb;
        }
        .success-message { 
            color: #155724; 
            background-color: #d4edda;
            border: 1px solid #c3e6cb;
        }
    </style>
</head>
<body>
    <div class="regis-container">
        <h2>Daftar Akun Pencari Kerja</h2>

        <?php 
        if ($error_message) {
            echo '<p class="message error-message">' . $error_message . '</p>';
        }
        ?>
        
        <form action="" method="POST"> 
            <label for="nama_pengguna">Nama Lengkap</label>
            <input type="text" id="nama_pengguna" name="nama_pengguna" placeholder="Masukkan nama Anda" required>
            
            <label for="email_pengguna">Email</label>
            <input type="email" id="email_pengguna" name="email_pengguna" placeholder="Masukkan email aktif" required>

            <label for="password_pengguna">Password</label>
            <input type="password" id="password_pengguna" name="password_pengguna" placeholder="Buat password" required minlength="6">

            <hr style="margin: 25px 0; border-top: 1px solid #eee;">
            <h4 style="margin-top: 0; color: #555; text-align: left;">Data Diri Lengkap (Wajib)</h4>

            <label for="no_hp">Nomor Telepon/HP</label>
            <input type="tel" id="no_hp" name="no_hp" placeholder="Contoh: 0812xxxxxxxx" pattern="[0-9]{9,15}" title="Hanya angka, 9-15 digit" required>
            
            <label for="tanggal_lahir">Tanggal Lahir</label>
            <input type="date" id="tanggal_lahir" name="tanggal_lahir" required>

            <label for="alamat">Alamat Lengkap</label>
            <textarea id="alamat" name="alamat" rows="3" placeholder="Masukkan alamat domisili Anda" required></textarea>

            <label for="pendidikan_terakhir">Pendidikan Terakhir</label>
            <select id="pendidikan_terakhir" name="pendidikan_terakhir" required>
                <option value="">Pilih Pendidikan Terakhir</option>
                <option value="SD">SD</option>
                <option value="SMP">SMP</option>
                <option value="SMA/SMK">SMA/SMK</option>
                <option value="D1">D1</option>
                <option value="D2">D2</option>
                <option value="D3">D3</option>
                <option value="S1/D4">S1/D4</option>
                <option value="S2">S2</option>
                <option value="S3">S3</option>
            </select>
            
            <label for="pengalaman_kerja">Pengalaman Kerja (Tahun)</label>
            <input type="text" id="pengalaman_kerja" name="pengalaman_kerja" placeholder="Contoh: 3 Tahun atau 0 Tahun (jika fresh graduate)" required>


            <button type="submit" name="submit_registrasi">Daftar Sekarang</button>
        </form>
        
        <p class="login-link">Sudah punya akun? <a href="user.php">Login di sini</a></p>
    </div>
</body>
</html>