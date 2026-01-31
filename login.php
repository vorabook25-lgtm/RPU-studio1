<?php 
    session_start();
    include('db.php');
    $msg = "";

    // --- ส่วนจัดการ Cookie (จำรหัสผ่าน) ---
    $cookie_email = isset($_COOKIE['rpu_email']) ? $_COOKIE['rpu_email'] : '';
    $cookie_pass = isset($_COOKIE['rpu_pass']) ? base64_decode($_COOKIE['rpu_pass']) : ''; 
    $is_remember = ($cookie_email != "") ? "checked" : "";

    // --- ส่วนกดปุ่ม Login ---
    if (isset($_POST['login'])) {
        $email = mysqli_real_escape_string($conn, $_POST['email']);
        $password = mysqli_real_escape_string($conn, $_POST['password']);
        $remember = isset($_POST['remember']); 

        // เช็คอีเมลในระบบ
        $query = mysqli_query($conn, "SELECT * FROM users WHERE email = '$email'");
        
        if (mysqli_num_rows($query) > 0) {
            $row = mysqli_fetch_assoc($query);
            // เช็ครหัสผ่าน
            if (password_verify($password, $row['password'])) {
                // 1. ตั้งค่า Session
                $_SESSION['user_id'] = $row['id'];
                $_SESSION['username'] = $row['username'];
                $_SESSION['role'] = $row['role'];

                // 2. จัดการ Cookie
                if ($remember) {
                    setcookie("rpu_email", $email, time() + (86400 * 30), "/");
                    setcookie("rpu_pass", base64_encode($password), time() + (86400 * 30), "/");
                } else {
                    setcookie("rpu_email", "", time() - 3600, "/");
                    setcookie("rpu_pass", "", time() - 3600, "/");
                }

                // 🔥 3. LOGIC แก้ไขใหม่: เช็คสถานะการยอมรับนโยบายตรงนี้ครับ 🔥
                
                // ตรวจสอบว่าในฐานข้อมูลมีค่า policy_accepted เป็น 1 หรือไม่?
                if (isset($row['policy_accepted']) && $row['policy_accepted'] == 1) {
                    // ✅ กรณีคนเก่า (ยอมรับแล้ว): ส่งไปหน้า Dashboard ตามตำแหน่งเลย
                    if ($row['role'] == 'photographer') {
                        header('location: photographer_dashboard.php');
                    } else {
                        // หน้าหลักของลูกค้า
                        header('location: marketplace.php'); 
                    }
                } else {
                    // ⚠️ กรณีคนใหม่ (ยังไม่ยอมรับ): ส่งไปหน้า Policy ก่อน
                    header('location: policy.php');
                }
                exit;

            } else {
                $msg = "❌ รหัสผ่านไม่ถูกต้อง";
            }
        } else {
            $msg = "❌ ไม่พบอีเมลนี้ในระบบ";
        }
    }
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <title>เข้าสู่ระบบ | RPU System</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href="https://fonts.googleapis.com/css2?family=Sarabun:wght@300;400;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        body { background: #1a1a2e; font-family: 'Sarabun'; display: flex; justify-content: center; align-items: center; min-height: 100vh; margin: 0; }
        
        .login-box { 
            background: white; 
            padding: 40px; 
            border-radius: 20px; 
            width: 100%; 
            max-width: 400px; 
            text-align: center; 
            box-shadow: 0 15px 35px rgba(0,0,0,0.5); 
            border-top: 5px solid #00d2d3; 
        }

        .logo { 
            width: 80px; height: 80px; 
            background: #1a1a2e; color: #00d2d3; 
            border-radius: 50%; 
            display: flex; align-items: center; justify-content: center; 
            font-size: 35px; margin: 0 auto 20px; 
            box-shadow: 0 5px 15px rgba(0,0,0,0.2);
        }

        h2 { color: #1a1a2e; margin: 10px 0 30px; letter-spacing: 1px; }

        input[type="email"], input[type="password"] { 
            width: 100%; padding: 12px; margin-bottom: 15px; 
            border: 1px solid #ddd; border-radius: 30px; 
            box-sizing: border-box; font-family: 'Sarabun'; padding-left: 20px; 
            transition: 0.3s;
        }
        
        input:focus { border-color: #00d2d3; outline: none; box-shadow: 0 0 5px rgba(0, 210, 211, 0.3); }

        .remember-box {
            display: flex;
            align-items: center;
            justify-content: flex-start;
            margin-bottom: 20px;
            font-size: 14px;
            color: #666;
            cursor: pointer;
            padding-left: 10px;
        }
        .remember-box input { margin-right: 8px; cursor: pointer; accent-color: #00d2d3; width: 16px; height: 16px; }

        .btn-login { 
            width: 100%; padding: 12px; 
            background: #1a1a2e; color: #00d2d3; 
            border: 1px solid #1a1a2e; border-radius: 30px; 
            font-weight: bold; cursor: pointer; transition: 0.3s; 
            font-size: 16px; margin-top: 5px; 
        }
        
        .btn-login:hover { background: #00d2d3; color: #1a1a2e; transform: translateY(-2px); }

        .links { margin-top: 25px; font-size: 14px; border-top: 1px solid #eee; padding-top: 20px; }
        .links a { color: #666; text-decoration: none; margin: 0 10px; transition: 0.2s; }
        .links a:hover { color: #00d2d3; font-weight: bold; }

        .alert { color: #e74c3c; background: #fadbd8; padding: 12px; border-radius: 10px; margin-bottom: 20px; font-size: 14px; border: 1px solid #e74c3c; }
    </style>
</head>
<body>
    <div class="login-box">
        <div class="logo"><i class="fas fa-camera-retro"></i></div>
        <h2>RPU USER</h2>
        
        <?php if($msg != "") echo "<div class='alert'><i class='fas fa-exclamation-triangle'></i> $msg</div>"; ?>
        
        <form method="post">
            <input type="email" name="email" placeholder="อีเมล (Email)" value="<?php echo $cookie_email; ?>" required>
            <input type="password" name="password" placeholder="รหัสผ่าน (Password)" value="<?php echo $cookie_pass; ?>" required>
            
            <label class="remember-box">
                <input type="checkbox" name="remember" <?php echo $is_remember; ?>> จำรหัสผ่านไว้
            </label>

            <button type="submit" name="login" class="btn-login">เข้าสู่ระบบ</button>
        </form>

        <div class="links">
            <a href="register.php"><i class="fas fa-user-plus"></i> สมัครสมาชิก</a> | 
            <a href="forgot_password.php"><i class="fas fa-key"></i> ลืมรหัสผ่าน?</a>
        </div>
    </div>
</body>
</html>