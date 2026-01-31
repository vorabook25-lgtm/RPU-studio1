<?php 
    session_start();
    include('db.php');
    if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'photographer') { header('location: login.php'); exit; }
    $user_id = $_SESSION['user_id'];
    $msg = "";

    // 1. Logic: นับจำนวนงานรออนุมัติ (Badge)
    $sql_noti = "SELECT COUNT(*) as count FROM bookings WHERE photographer_id = '$user_id' AND status = 'pending'";
    $noti_job_count = mysqli_fetch_assoc(mysqli_query($conn, $sql_noti))['count'];
    $job_badge = ($noti_job_count > 0) ? "<span class='badge'>$noti_job_count</span>" : "";

    // 2. Logic: นับจำนวนแชท
    $noti_chat_count = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as count FROM bookings WHERE photographer_id = '$user_id' AND status IN ('confirmed', 'paid')"))['count'];
    $chat_badge = ($noti_chat_count > 0) ? "<span class='badge'>$noti_chat_count</span>" : "";

    // --- 3. Logic: การอัปโหลดงาน (แก้ไขใหม่) ---
    if (isset($_POST['submit_upload'])) {
        $booking_id = mysqli_real_escape_string($conn, $_POST['booking_id']);
        $uploaded_count = 0;
        
        // 🔥 แก้ Path ให้ตรงกับฝั่งลูกค้า (../uploads/job_photos/)
        $upload_dir = '../uploads/job_photos/';
        if(!file_exists($upload_dir)) { mkdir($upload_dir, 0777, true); }

        if (!empty($_FILES['work_files']['name'][0])) {
            foreach ($_FILES['work_files']['name'] as $key => $val) {
                if ($_FILES['work_files']['error'][$key] == 0) {
                    $ext = pathinfo($_FILES['work_files']['name'][$key], PATHINFO_EXTENSION);
                    // ตั้งชื่อไฟล์
                    $new_name = "job_" . $booking_id . "_" . uniqid() . "." . $ext;
                    
                    if(move_uploaded_file($_FILES['work_files']['tmp_name'][$key], $upload_dir . $new_name)) { 
                        
                        // 🔥 เพิ่มส่วนนี้: บันทึกชื่อไฟล์ลงฐานข้อมูล เพื่อให้ลูกค้าดึงไปแสดงได้
                        $sql_insert_img = "INSERT INTO job_photos (booking_id, file_name) VALUES ('$booking_id', '$new_name')";
                        mysqli_query($conn, $sql_insert_img);
                        
                        $uploaded_count++; 
                    }
                }
            }
        }
        
        if ($uploaded_count > 0) {
            // อัปเดตสถานะงานเป็น Completed
            mysqli_query($conn, "UPDATE bookings SET status = 'completed' WHERE id = '$booking_id'");
            $msg = "<div class='alert success'>✅ ส่งงานรหัส #$booking_id สำเร็จ! อัปโหลด $uploaded_count รูป</div>";
        } else {
            $msg = "<div class='alert error'>❌ ไม่มีการอัปโหลดไฟล์ หรือเกิดข้อผิดพลาด</div>";
        }
    }

    // ดึงงานที่ยังไม่เสร็จ (Confirmed)
    $booking_query = mysqli_query($conn, "SELECT id, booking_date FROM bookings WHERE photographer_id = '$user_id' AND status = 'confirmed'");
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <title>ส่งมอบงาน | RPU AM</title>
    <link href="https://fonts.googleapis.com/css2?family=Sarabun:wght@300;400;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        body { font-family: 'Sarabun'; margin: 0; background: #f0f2f5; display: flex; height: 100vh; overflow: hidden; }
        
        /* Sidebar */
        .sidebar { width: 260px; background: #1a1a2e; color: white; padding: 25px; display: flex; flex-direction: column; flex-shrink: 0; }
        .sidebar h2 { color: #00d2d3; text-align: center; margin: 0 0 40px 0; font-size: 24px; letter-spacing: 1px; text-transform: uppercase; }
        .menu-item { position: relative; padding: 12px 20px; color: #bdc3c7; text-decoration: none; display: flex; align-items: center; border-radius: 10px; margin-bottom: 12px; cursor: pointer; transition: all 0.3s ease; font-size: 16px; }
        .menu-item:hover, .menu-item.active { background: #16213e; color: #00d2d3; transform: translateX(5px); }
        .menu-item i { margin-right: 15px; width: 25px; text-align: center; font-size: 18px; }
        .badge { background: #ff4757; color: white; border-radius: 50%; padding: 2px 8px; font-size: 12px; font-weight: bold; position: absolute; right: 15px; top: 50%; transform: translateY(-50%); }
        .logout-box { margin-top: auto; } .menu-item.logout { color: #ff6b6b; } 

        .main-content { flex: 1; padding: 40px; overflow-y: auto; }
        .card { background: white; padding: 40px; border-radius: 20px; box-shadow: 0 5px 15px rgba(0,0,0,0.05); max-width: 600px; margin: auto; }
        select { width: 100%; padding: 15px; border-radius: 12px; border: 1px solid #ddd; margin-bottom: 20px; font-family: 'Sarabun'; font-size: 16px; }
        .upload-zone { border: 2px dashed #00d2d3; padding: 40px; text-align: center; border-radius: 15px; background: #f0fbfc; cursor: pointer; }
        .btn-send { width: 100%; padding: 15px; background: #28a745; color: white; border: none; border-radius: 35px; font-weight: bold; cursor: pointer; margin-top: 20px; font-size: 16px; }
        .alert { padding: 15px; border-radius: 10px; margin-bottom: 20px; text-align: center; }
        .success { background: #d4edda; color: #155724; }
        .error { background: #f8d7da; color: #721c24; }
    </style>
</head>
<body>
    <div class="sidebar">
        <h2>RPU AM</h2>
        <a class="menu-item" href="photographer_dashboard.php"><i class="fas fa-home"></i> หน้าหลัก <?php echo $job_badge; ?></a>
        <a class="menu-item" href="photographer_portfolio.php"><i class="fas fa-images"></i> แฟ้มผลงาน</a>
        <a class="menu-item active" href="photographer_upload.php"><i class="fas fa-cloud-upload-alt"></i> ส่งมอบงาน</a>
        <a class="menu-item" href="job_history.php"><i class="fas fa-history"></i> ประวัติการรับงาน</a>
        <a class="menu-item" href="photographer_chat.php"><i class="fas fa-comments"></i> แชทลูกค้า <?php echo $chat_badge; ?></a>
        <a class="menu-item" href="settings.php"><i class="fas fa-cog"></i> ตั้งค่าระบบ</a>
        <div class="logout-box"><a class="menu-item logout" href="logout.php"><i class="fas fa-sign-out-alt"></i> ออกจากระบบ</a></div>
    </div>

    <div class="main-content">
        <div class="card">
            <h1 style="margin-top:0;">📤 ส่งมอบงาน</h1>
            <?php echo $msg; ?>
            
            <form method="post" enctype="multipart/form-data">
                <label style="font-weight:bold;">เลือกรหัสงาน</label>
                <select name="booking_id" required>
                    <option value="">-- กรุณาเลือกงานที่ต้องการส่ง --</option>
                    <?php while($row = mysqli_fetch_assoc($booking_query)) { 
                        echo "<option value='".$row['id']."'>รหัส #".$row['id']." (วันที่: ".$row['booking_date'].")</option>"; 
                    } ?>
                </select>
                
                <label style="font-weight:bold; display:block; margin-bottom:10px;">อัปโหลดรูป (เลือกได้หลายรูป)</label>
                <div class="upload-zone" onclick="document.getElementById('files').click()">
                    <i class="fas fa-images fa-3x" style="color:#00d2d3; margin-bottom:10px;"></i>
                    <p id="file-text">คลิกเพื่อเลือกไฟล์ทั้งหมด (แนะนำ .JPG)</p>
                    <input type="file" id="files" name="work_files[]" multiple hidden onchange="document.getElementById('file-text').innerText = '✅ เลือกแล้ว ' + this.files.length + ' ไฟล์ (พร้อมส่ง)'">
                </div>

                <button type="submit" name="submit_upload" class="btn-send" onclick="return confirm('ยืนยันการส่งงาน? เมื่อส่งแล้วสถานะงานจะเปลี่ยนเป็น Completed ทันที')">ยืนยันการส่งงาน</button>
            </form>
        </div>
    </div>
</body>
</html>