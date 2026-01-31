<?php 
    session_start();
    include('db.php');
    if (!isset($_SESSION['user_id'])) { header('location: login.php'); exit; }
    $user_id = $_SESSION['user_id'];
    $msg = "";

    // บันทึกข้อมูล (ตัด Logic เปลี่ยนรหัสผ่านออกแล้ว)
    if (isset($_POST['save_profile'])) {
        $username = mysqli_real_escape_string($conn, $_POST['username']);
        $phone = mysqli_real_escape_string($conn, $_POST['phone']);
        
        $sql = "UPDATE users SET username='$username', phone='$phone' WHERE id='$user_id'";
        
        if(mysqli_query($conn, $sql)) {
            $msg = "<div class='alert success'>✅ บันทึกข้อมูลเรียบร้อยแล้วครับ</div>";
        } else {
            $msg = "<div class='alert error'>❌ เกิดข้อผิดพลาด: ".mysqli_error($conn)."</div>";
        }
    }

    $user = mysqli_fetch_assoc(mysqli_query($conn, "SELECT * FROM users WHERE id='$user_id'"));
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <title>ตั้งค่าบัญชี | RPU UE</title>
    <link href="https://fonts.googleapis.com/css2?family=Sarabun:wght@300;400;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        body { font-family: 'Sarabun'; margin: 0; background: #f0f2f5; display: flex; height: 100vh; overflow: hidden; }
        
        /* Sidebar ลูกค้า */
        .sidebar { width: 260px; background: #1a1a2e; color: white; padding: 25px; display: flex; flex-direction: column; flex-shrink: 0; }
        .sidebar h2 { color: #ff9f43; text-align: center; margin: 0 0 40px 0; font-size: 24px; letter-spacing: 1px; }
        .menu-item { padding: 12px 20px; color: #bdc3c7; text-decoration: none; display: flex; align-items: center; border-radius: 10px; margin-bottom: 12px; cursor: pointer; transition: 0.3s; }
        .menu-item:hover, .menu-item.active { background: #16213e; color: #ff9f43; transform: translateX(5px); }
        .menu-item i { margin-right: 15px; width: 25px; text-align: center; }
        .logout-box { margin-top: auto; } .menu-item.logout { color: #ff6b6b; } .menu-item.logout:hover { background: rgba(255, 107, 107, 0.1); color: #ff4757; }

        .main-content { flex: 1; padding: 40px; overflow-y: auto; }
        
        .box { background: white; padding: 40px; border-radius: 20px; box-shadow: 0 5px 15px rgba(0,0,0,0.05); max-width: 600px; margin: auto; }
        
        label { display: block; margin-top: 15px; font-weight: bold; color: #555; }
        input { width: 100%; padding: 12px; margin-top: 5px; border: 1px solid #ddd; border-radius: 8px; box-sizing: border-box; font-family: 'Sarabun'; }
        
        input[readonly] { background-color: #f9f9f9; color: #777; cursor: not-allowed; }

        .btn-save { width: 100%; padding: 15px; background: #ff9f43; color: white; border: none; border-radius: 30px; font-weight: bold; cursor: pointer; margin-top: 30px; font-size: 16px; transition: 0.3s; }
        .btn-save:hover { background: #e67e22; transform: translateY(-2px); }
        
        .alert { padding: 15px; border-radius: 10px; text-align: center; margin-bottom: 20px; }
        .success { background: #d4edda; color: #155724; } .error { background: #f8d7da; color: #721c24; }

        .policy-box { margin-top: 30px; border: 1px solid #ff9f43; border-radius: 10px; padding: 15px; background: #fff8e1; }
        .policy-content { height: 150px; overflow-y: auto; font-size: 13px; color: #555; background: white; padding: 10px; border-radius: 5px; border: 1px solid #eee; margin-top: 10px; }
        
        /* สไตล์ลิงก์ลืมรหัสผ่าน */
        .forgot-link { display: inline-block; margin-top: 10px; color: #e67e22; text-decoration: none; font-size: 14px; font-weight: bold; }
        .forgot-link:hover { text-decoration: underline; }
    </style>
</head>
<body>
    <div class="sidebar">
        <h2>RPU UE</h2>
        <a class="menu-item" href="marketplace.php"><i class="fas fa-search"></i> หาช่างภาพ</a>
        <a class="menu-item" href="customer_bookings.php"><i class="fas fa-calendar-check"></i> ประวัติการจอง</a>
        <a class="menu-item" href="customer_chat.php"><i class="fas fa-comments"></i> แชทกับช่างภาพ</a>
        <a class="menu-item active" href="customer_settings.php"><i class="fas fa-cog"></i> ตั้งค่าบัญชี</a>
        <div class="logout-box"><a class="menu-item logout" href="logout.php"><i class="fas fa-sign-out-alt"></i> ออกจากระบบ</a></div>
    </div>

    <div class="main-content">
        <h1 style="color:#333; margin-top:0;">⚙️ ตั้งค่าบัญชีผู้ใช้</h1>
        
        <div class="box">
            <?php echo $msg; ?>
            <form method="post">
                
                <label>ชื่อผู้ใช้งาน</label>
                <input type="text" name="username" value="<?php echo $user['username']; ?>" required>

                <label>อีเมล (แก้ไขไม่ได้)</label>
                <input type="email" value="<?php echo $user['email']; ?>" readonly>

                <label>เบอร์โทรศัพท์ติดต่อ</label>
                <input type="text" name="phone" value="<?php echo $user['phone']; ?>" required>
                
                <div style="text-align: right;">
                    <a href="forgot_password.php" class="forgot-link"><i class="fas fa-key"></i> เปลี่ยนรหัสผ่าน / ลืมรหัสผ่าน?</a>
                </div>

                <div class="policy-box">
                    <label style="margin-top:0; color:#e67e22;"><i class="fas fa-file-contract"></i> นโยบายการใช้งาน</label>
                    <div class="policy-content">
                        <strong>1. มาตรฐานการให้บริการ:</strong> ผู้ใช้งานต้องปฏิบัติตนด้วยความสุภาพ<br>
                        <strong>2. การจองและชำระเงิน:</strong> การโอนมัดจำถือเป็นการยืนยันสิทธิ์<br>
                        <strong>3. ข้อมูลส่วนบุคคล:</strong> ข้อมูลเบอร์โทรจะถูกเปิดเผยเฉพาะคู่สัญญาเท่านั้น<br>
                        <strong>4. การส่งงาน:</strong> หากพบปัญหาการส่งงานล่าช้า สามารถแจ้งแอดมินได้ทันที<br>
                        <strong>5. บทลงโทษ:</strong> หากทำผิดกฎซ้ำซ้อน ระบบขอสงวนสิทธิ์ในการระงับบัญชี<br>
                    </div>
                </div>

                <button type="submit" name="save_profile" class="btn-save">บันทึกข้อมูล</button>
            </form>
        </div>
    </div>
</body>
</html>