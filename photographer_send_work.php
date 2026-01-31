<?php 
    session_start();
    include('db.php');
    if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'photographer') { header('location: login.php'); exit; }
    
    $pg_id = $_SESSION['user_id'];
    $current_page = basename($_SERVER['PHP_SELF']);

    // Badge งานใหม่สำหรับ Sidebar
    $noti = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as c FROM bookings WHERE photographer_id = '$pg_id' AND status = 'pending'"))['c'];
    $job_badge = ($noti > 0) ? "<span class='badge'>$noti</span>" : "";

    // Logic: กดจบงาน (ส่งมอบงาน)
    if (isset($_POST['complete_job'])) {
        $booking_id = mysqli_real_escape_string($conn, $_POST['booking_id']);
        if (mysqli_query($conn, "UPDATE bookings SET status='completed' WHERE id='$booking_id' AND photographer_id='$pg_id'")) {
            echo "<script>alert('ส่งมอบงานเรียบร้อย'); window.location='photographer_send_work.php';</script>";
        }
    }

    // ดึงเฉพาะงานที่ Confirmed (รับงานแล้วแต่ยังไม่จบ)
    $sql = "SELECT b.*, u.username as customer_name 
            FROM bookings b 
            JOIN users u ON b.user_id = u.id 
            WHERE b.photographer_id = '$pg_id' AND b.status = 'confirmed'
            ORDER BY b.booking_date ASC";
    $query = mysqli_query($conn, $sql);
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
        .sidebar { width: 260px; background: #1a1a2e; color: white; padding: 25px; display: flex; flex-direction: column; flex-shrink: 0; }
        .sidebar h2 { color: #00d2d3; text-align: center; margin: 0 0 40px 0; font-size: 24px; }
        .menu-item { padding: 12px 20px; color: #bdc3c7; text-decoration: none; display: flex; align-items: center; border-radius: 10px; margin-bottom: 12px; transition: 0.3s; position:relative; }
        .menu-item:hover, .menu-item.active { background: #16213e; color: #00d2d3; }
        .menu-item i { margin-right: 15px; width: 25px; text-align: center; font-size: 18px; }
        .badge { background: #ff4757; color: white; border-radius: 50%; padding: 2px 8px; font-size: 12px; font-weight: bold; position: absolute; right: 15px; top: 50%; transform: translateY(-50%); }
        .logout-box { margin-top: auto; } .menu-item.logout { color: #ff6b6b; } 

        .main-content { flex: 1; padding: 40px; overflow-y: auto; }
        .card { background: white; padding: 25px; border-radius: 15px; margin-bottom: 20px; box-shadow: 0 4px 10px rgba(0,0,0,0.05); border-left: 5px solid #28a745; display: flex; justify-content: space-between; align-items: center; }
        .btn-finish { background: #28a745; color: white; border: none; padding: 10px 25px; border-radius: 30px; font-weight: bold; cursor: pointer; }
    </style>
</head>
<body>
    <div class="sidebar">
        <h2>RPU AM</h2>
        <a class="menu-item" href="photographer_dashboard.php"><i class="fas fa-home"></i> หน้าหลัก <?php echo $job_badge; ?></a>
        <a class="menu-item" href="photographer_portfolio.php"><i class="fas fa-images"></i> แฟ้มผลงาน</a>
        <a class="menu-item active" href="photographer_send_work.php"><i class="fas fa-cloud-upload-alt"></i> ส่งมอบงาน</a>
        <a class="menu-item" href="job_history.php"><i class="fas fa-history"></i> ประวัติการรับงาน</a>
        <a class="menu-item" href="photographer_chat.php"><i class="fas fa-comments"></i> แชทลูกค้า</a>
        <a class="menu-item" href="settings.php"><i class="fas fa-cog"></i> ตั้งค่าระบบ</a>
        <div class="logout-box"><a class="menu-item logout" href="logout.php"><i class="fas fa-sign-out-alt"></i> ออกจากระบบ</a></div>
    </div>

    <div class="main-content">
        <h1>📤 งานที่รอส่งมอบ (จบงาน)</h1>
        <?php if(mysqli_num_rows($query) > 0) { while($row = mysqli_fetch_assoc($query)) { ?>
            <div class="card">
                <div>
                    <h3 style="margin:0;">#<?php echo $row['id']; ?> : <?php echo $row['job_type']; ?></h3>
                    <p style="margin:5px 0; color:#666;">ลูกค้า: <strong><?php echo $row['customer_name']; ?></strong> | วันที่: <?php echo $row['booking_date']; ?></p>
                </div>
                <form method="post">
                    <input type="hidden" name="booking_id" value="<?php echo $row['id']; ?>">
                    <button type="submit" name="complete_job" class="btn-finish"><i class="fas fa-check"></i> กดเพื่อจบงาน</button>
                </form>
            </div>
        <?php } } else { echo "<p style='color:#999;'>ไม่มีงานที่รอส่งมอบในขณะนี้</p>"; } ?>
    </div>
</body>
</html>