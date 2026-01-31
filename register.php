<?php 
    session_start();
    include('db.php');
    $msg = "";

    if (isset($_POST['register'])) {
        // --- ส่วนที่แก้ไข: กำหนดค่า Role เป็น 'customer' ทันที ---
        $role = 'customer'; 
        // ----------------------------------------------------

        $username = mysqli_real_escape_string($conn, $_POST['username']);
        $email = mysqli_real_escape_string($conn, $_POST['email']);
        $phone = mysqli_real_escape_string($conn, $_POST['phone']);
        $password = mysqli_real_escape_string($conn, $_POST['password']);
        $confirm_password = mysqli_real_escape_string($conn, $_POST['confirm_password']);

        // 1. เช็ครหัสผ่าน
        if ($password !== $confirm_password) {
            $msg = "❌ รหัสผ่านไม่ตรงกัน";
        } else {
            // 2. เช็คอีเมลซ้ำ
            $check_email = mysqli_query($conn, "SELECT * FROM users WHERE email = '$email'");
            if (mysqli_num_rows($check_email) > 0) {
                $msg = "❌ อีเมลนี้ถูกใช้งานแล้ว";
            } else {
                // 3. บันทึกข้อมูล
                $hashed_password = password_hash($password, PASSWORD_DEFAULT);
                
                $sql = "INSERT INTO users (username, email, phone, password, role) 
                        VALUES ('$username', '$email', '$phone', '$hashed_password', '$role')";
                
                if (mysqli_query($conn, $sql)) {
                    echo "<script>
                        alert('✅ สมัครสมาชิกสำเร็จ! ยินดีต้อนรับสู่ RPU System');
                        window.location='login.php';
                    </script>";
                } else {
                    $msg = "❌ เกิดข้อผิดพลาด: " . mysqli_error($conn);
                }
            }
        }
    }
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <title>สมัครสมาชิก (ลูกค้า) | RPU System</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href="https://fonts.googleapis.com/css2?family=Sarabun:wght@300;400;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        /* Theme เดียวกับ Login เป๊ะๆ */
        body { background: #1a1a2e; font-family: 'Sarabun'; display: flex; justify-content: center; align-items: center; min-height: 100vh; margin: 0; padding: 20px; box-sizing: border-box; }
        
        .login-box { 
            background: white; 
            padding: 40px; 
            border-radius: 20px; 
            width: 100%; 
            max-width: 450px; 
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

        h2 { color: #1a1a2e; margin: 10px 0 25px; letter-spacing: 1px; }

        .input-group { margin-bottom: 15px; position: relative; }
        .input-group i { position: absolute; left: 15px; top: 14px; color: #888; z-index: 1; }
        
        input { 
            width: 100%; padding: 12px 12px 12px 45px; 
            border: 1px solid #ddd; border-radius: 30px; 
            box-sizing: border-box; font-family: 'Sarabun'; 
            font-size: 14px; transition: 0.3s;
            background: #fff;
        }
        
        input:focus { border-color: #00d2d3; outline: none; box-shadow: 0 0 5px rgba(0, 210, 211, 0.3); }

        .btn-reg { 
            width: 100%; padding: 12px; 
            background: #1a1a2e; color: #00d2d3; 
            border: 1px solid #1a1a2e; border-radius: 30px; 
            font-weight: bold; cursor: pointer; transition: 0.3s; 
            font-size: 16px; margin-top: 10px; 
        }
        
        .btn-reg:hover { background: #00d2d3; color: #1a1a2e; transform: translateY(-2px); }

        .links { margin-top: 25px; font-size: 14px; border-top: 1px solid #eee; padding-top: 20px; }
        .links a { color: #666; text-decoration: none; transition: 0.2s; }
        .links a:hover { color: #00d2d3; font-weight: bold; }

        .alert { color: #e74c3c; background: #fadbd8; padding: 12px; border-radius: 10px; margin-bottom: 20px; font-size: 14px; border: 1px solid #e74c3c; }
    </style>
</head>
<body>
    <div class="login-box">
        <div class="logo"><i class="fas fa-user-plus"></i></div>
        <h2>ลงทะเบียนลูกค้าใหม่</h2>
        
        <?php if($msg != "") echo "<div class='alert'><i class='fas fa-exclamation-circle'></i> $msg</div>"; ?>
        
        <form method="post">
            <div class="input-group">
                <i class="fas fa-user"></i>
                <input type="text" name="username" placeholder="ชื่อ-นามสกุล" required>
            </div>

            <div class="input-group">
                <i class="fas fa-phone"></i>
                <input type="text" name="phone" placeholder="เบอร์โทรศัพท์" required>
            </div>

            <div class="input-group">
                <i class="fas fa-envelope"></i>
                <input type="email" name="email" placeholder="อีเมล (Email)" required>
            </div>

            <div class="input-group">
                <i class="fas fa-lock"></i>
                <input type="password" name="password" placeholder="รหัสผ่าน" required>
            </div>

            <div class="input-group">
                <i class="fas fa-key"></i>
                <input type="password" name="confirm_password" placeholder="ยืนยันรหัสผ่าน" required>
            </div>

            <button type="submit" name="register" class="btn-reg">สมัครสมาชิก</button>
        </form>

        <div class="links">
            มีบัญชีผู้ใช้แล้ว? <a href="login.php">เข้าสู่ระบบที่นี่</a>
        </div>
    </div>
</body>
</html>