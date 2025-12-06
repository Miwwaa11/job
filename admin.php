<?php
session_start();
include 'koneksi.php'; 

$error_message = '';

if (isset($_SESSION['status_login']) && $_SESSION['status_login'] === TRUE && $_SESSION['role'] === 'admin') {
    header("Location: dashboard_admin.php");
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email_admin = mysqli_real_escape_string($koneksi, $_POST['email']);
    $password_input = $_POST['password']; 

    $query = "SELECT id_admin, nama, password FROM tb_admin WHERE email = '$email_admin'";
    
    $result = mysqli_query($koneksi, $query);

    if ($result && mysqli_num_rows($result) === 1) {
        $user = mysqli_fetch_assoc($result);
        
        if ($password_input === $user['password']) {
            
            $_SESSION['status_login'] = TRUE;
            $_SESSION['id_user'] = $user['id_admin']; 
            $_SESSION['nama'] = $user['nama'];
            $_SESSION['role'] = 'admin'; 
            
            $update_status = "UPDATE tb_admin SET status_login = NOW() WHERE id_admin = '{$user['id_admin']}'";
            mysqli_query($koneksi, $update_status);

            header("Location: dashboard_admin.php");
            exit;
        
        } else {
            $error_message = "Password salah. Silakan coba lagi.";
        }
    } else {
        $error_message = "Email tidak terdaftar sebagai Administrator.";
    }
}

if (isset($koneksi)) {
    @mysqli_close($koneksi); 
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login Administrator</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Poppins:wght@400;600;700&display=swap');
        
        :root {
            --primary-color: #004d99;
            --secondary-color: #00b4d8;
            --text-dark: #333333;
            --bg-light: #f4f7f9;
            --bg-card: #ffffff;
            --shadow: 0 10px 30px rgba(0, 77, 153, 0.1);
            --border-radius: 12px;
        }

        body { 
            font-family: 'Poppins', sans-serif; 
            background-color: var(--bg-light); 
            color: var(--text-dark);
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
            margin: 0;
        }

        .login-container {
            background: var(--bg-card);
            padding: 40px;
            border-radius: var(--border-radius);
            box-shadow: var(--shadow);
            width: 100%;
            max-width: 400px;
            text-align: center;
        }

        .login-container h2 {
            color: var(--primary-color);
            margin-bottom: 30px;
            font-weight: 700;
            border-bottom: 2px solid var(--secondary-color);
            padding-bottom: 10px;
        }

        .form-group {
            margin-bottom: 20px;
            text-align: left;
        }

        .form-group label {
            display: block;
            margin-bottom: 8px;
            font-weight: 600;
            color: var(--text-dark);
        }

        .form-group input {
            width: 100%;
            padding: 12px;
            border: 1px solid #ddd;
            border-radius: 6px;
            box-sizing: border-box;
            font-size: 1em;
        }

        .btn-login {
            width: 100%;
            padding: 15px;
            background-color: var(--primary-color);
            color: white;
            border: none;
            border-radius: 6px;
            cursor: pointer;
            font-size: 1.1em;
            font-weight: 700;
            transition: background-color 0.3s;
        }

        .btn-login:hover {
            background-color: #003366;
        }

        .alert-error {
            padding: 12px;
            margin-bottom: 20px;
            background-color: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
            border-radius: 6px;
            font-size: 0.95em;
            font-weight: 600;
        }
    </style>
</head>
<body>
    <div class="login-container">
        <h2><i class="fas fa-user-shield"></i> Login Administrator</h2>
        
        <?php if ($error_message): ?>
            <div class="alert-error"><?php echo $error_message; ?></div>
        <?php endif; ?>

        <form action="admin.php" method="POST">
            <div class="form-group">
                <label for="email"><i class="fas fa-envelope"></i> Email</label>
                <input type="email" id="email" name="email" required>
            </div>
            <div class="form-group">
                <label for="password"><i class="fas fa-lock"></i> Password</label>
                <input type="password" id="password" name="password" required>
            </div>
            <button type="submit" class="btn-login">Login</button>
        </form>
    </div>
</body>
</html>