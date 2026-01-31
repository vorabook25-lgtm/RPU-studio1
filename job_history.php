<?php 
    session_start();
    include('db.php');
    if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'photographer') { header('location: login.php'); exit; }
    
    $pg_id = $_SESSION['user_id'];
    $current_page = basename($_SERVER['PHP_SELF']);

    // 1. Badge Count สำหรับหน้าหลัก
    $sql_noti = "SELECT COUNT(*) as count FROM bookings WHERE photographer_id = '$pg_id' AND status = 'pending'";
    $noti_job_count = mysqli_fetch_assoc(mysqli_query($conn, $sql_noti))['count'];
    $job_badge = ($noti_job_count > 0) ? "<span class='badge'>$noti_job_count</span>" : "";

    // 2. ดึงประวัติงาน (Completed / Cancelled) + Join รีวิว
    $sql = "SELECT b.*, u.username as customer_name, u.phone as customer_phone, r.rating, r.comment as review_text
            FROM bookings b 
            LEFT JOIN users u ON b.user_id = u.id 
            LEFT JOIN reviews r ON b.id = r.booking_id
            WHERE b.photographer_id = '$pg_id' 
            AND b.status IN ('completed', 'cancelled')
            ORDER BY b.booking_date DESC, b.booking_time DESC";
    $query = mysqli_query($conn, $sql);
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <title>ประวัติการรับงาน | RPU AM</title>
    <link href="https://fonts.googleapis.com/css2?family=Sarabun:wght@300;400;500;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        body { font-family: 'Sarabun', sans-serif; margin: 0; background: #f0f2f5; display: flex; height: 100vh; overflow: hidden; color:#333; }
        
        /* Sidebar Styles */
        .sidebar { width: 260px; background: #1a1a2e; color: white; padding: 25px; display: flex; flex-direction: column; flex-shrink: 0; }
        .sidebar h2 { color: #00d2d3; text-align: center; margin: 0 0 40px 0; font-size: 24px; letter-spacing: 1px; }
        .menu-item { padding: 12px 20px; color: #bdc3c7; text-decoration: none; display: flex; align-items: center; border-radius: 10px; margin-bottom: 12px; cursor: pointer; transition: 0.3s; position: relative; }
        .menu-item:hover, .menu-item.active { background: #16213e; color: #00d2d3; transform: translateX(5px); }
        .menu-item i { margin-right: 15px; width: 25px; text-align: center; font-size: 18px; }
        .badge { background: #ff4757; color: white; border-radius: 50%; padding: 2px 8px; font-size: 12px; font-weight: bold; position: absolute; right: 15px; top: 50%; transform: translateY(-50%); }
        .logout-box { margin-top: auto; } .menu-item.logout { color: #ff6b6b; } 

        /* Main Content */
        .main-content { flex: 1; padding: 30px 40px; overflow-y: auto; }
        .page-header { margin-bottom: 30px; border-bottom: 2px solid #e0e0e0; padding-bottom: 15px; }
        .page-header h1 { margin: 0; color: #1a1a2e; font-size: 24px; }
        
        /* Card Layout */
        .history-card { 
            background: white; border-radius: 12px; padding: 25px; margin-bottom: 25px; 
            box-shadow: 0 4px 15px rgba(0,0,0,0.03); border: 1px solid #eee; position: relative; transition: 0.3s;
        }
        .history-card:hover { transform: translateY(-3px); box-shadow: 0 8px 25px rgba(0,0,0,0.08); }
        .history-card.completed { border-left: 5px solid #28a745; }
        .history-card.cancelled { border-left: 5px solid #dc3545; background: #fffbfb; }

        .card-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; }
        .job-id { font-size: 18px; font-weight: bold; color: #1a1a2e; }
        .job-type { background: #f0f2f5; padding: 4px 12px; border-radius: 15px; font-size: 13px; color: #555; margin-left: 10px; }
        
        .status-badge { padding: 6px 15px; border-radius: 20px; font-size: 13px; font-weight: bold; display: flex; align-items: center; gap: 5px; }
        .badge-success { background: #d4edda; color: #155724; }
        .badge-fail { background: #f8d7da; color: #721c24; }

        .info-grid { display: grid; grid-template-columns: repeat(4, 1fr); gap: 20px; margin-bottom: 20px; }
        .info-item { display: flex; flex-direction: column; }
        .label { font-size: 12px; color: #888; margin-bottom: 5px; text-transform: uppercase; letter-spacing: 0.5px; }
        .value { font-size: 15px; font-weight: 600; color: #333; }
        
        .cancel-box { background: #ffebeb; border: 1px dashed #ffcccc; padding: 15px; border-radius: 8px; color: #d63031; font-size: 14px; margin-top: 15px; }
        .review-section { margin-top: 20px; border-top: 1px solid #eee; padding-top: 20px; }
        .review-box { background: #fafafa; border-radius: 10px; padding: 15px; border: 1px solid #eee; }
        .stars { color: #ffc107; }

        /* Payment Tags */
        .pay-tag { font-size: 12px; padding: 3px 8px; border-radius: 4px; }
        .pay-cash { background: #fff3cd; color: #856404; }
        .pay-trans { background: #d1ecf1; color: #0c5460; }
    </style>
</head>
<body>

    <div class="sidebar">
        <h2>RPU AM</h2>
        <a class="menu-item" href="photographer_dashboard.php"><i class="fas fa-home"></i> หน้าหลัก <?php echo $job_badge; ?></a>
        <a class="menu-item" href="photographer_portfolio.php"><i class="fas fa-images"></i> แฟ้มผลงาน</a>
        
        <a class="menu-item" href="photographer_upload.php"><i class="fas fa-cloud-upload-alt"></i> ส่งมอบงาน</a>
        
        <a class="menu-item active" href="job_history.php"><i class="fas fa-history"></i> ประวัติการรับงาน</a>
        <a class="menu-item" href="photographer_chat.php"><i class="fas fa-comments"></i> แชทลูกค้า</a>
        <a class="menu-item" href="settings.php"><i class="fas fa-cog"></i> ตั้งค่าระบบ</a>
        <div class="logout-box"><a class="menu-item logout" href="logout.php"><i class="fas fa-sign-out-alt"></i> ออกจากระบบ</a></div>
    </div>

    <div class="main-content">
        <div class="page-header">
            <h1>📜 ประวัติการรับงาน</h1>
            <span style="color:#666; font-size:14px;">รายการงานที่เสร็จสิ้นและถูกยกเลิก (รวมรีวิวจากลูกค้า)</span>
        </div>
        
        <?php if(mysqli_num_rows($query) > 0) { 
            while($row = mysqli_fetch_assoc($query)) { 
                $is_completed = ($row['status'] == 'completed');
                $card_class = $is_completed ? 'completed' : 'cancelled';
                $date_show = date('d/m/Y', strtotime($row['booking_date']));
                
                $pay_method_txt = ($row['payment_method']=='cash') ? '<span class="pay-tag pay-cash">เงินสด</span>' : '<span class="pay-tag pay-trans">โอนจ่าย</span>';
                $pay_status_txt = ($row['payment_status']=='paid') ? '<span style="color:#28a745;">✅ จ่ายแล้ว</span>' : '<span style="color:#dc3545;">❌ ยังไม่จ่าย</span>';
                
                $stars_html = "";
                if(isset($row['rating'])) {
                    for($i=1; $i<=5; $i++) { $stars_html .= ($i <= $row['rating']) ? '<i class="fas fa-star"></i>' : '<i class="far fa-star"></i>'; }
                }
        ?>
            <div class="history-card <?php echo $card_class; ?>">
                <div class="card-header">
                    <div>
                        <span class="job-id">#<?php echo $row['id']; ?></span>
                        <span class="job-type"><?php echo $row['job_type']; ?></span>
                    </div>
                    <?php if($is_completed): ?>
                        <div class="status-badge badge-success"><i class="fas fa-check-circle"></i> งานสำเร็จ</div>
                    <?php else: ?>
                        <div class="status-badge badge-fail"><i class="fas fa-times-circle"></i> ยกเลิกแล้ว</div>
                    <?php endif; ?>
                </div>

                <div class="info-grid">
                    <div class="info-item"><span class="label"><i class="far fa-calendar-alt"></i> วันที่นัดหมาย</span><span class="value"><?php echo $date_show; ?></span></div>
                    <div class="info-item"><span class="label"><i class="far fa-clock"></i> เวลาปฏิบัติงาน (จริง)</span><span class="value"><?php echo $row['booking_time']; ?> - <?php echo $row['booking_end_time']; ?></span></div>
                    <div class="info-item"><span class="label"><i class="far fa-user"></i> ลูกค้า</span><span class="value"><?php echo $row['customer_name']; ?></span></div>
                    <div class="info-item"><span class="label"><i class="fas fa-coins"></i> การเงิน</span><span class="value"><?php echo $pay_method_txt; ?> <?php echo $pay_status_txt; ?></span></div>
                </div>

                <?php if(!$is_completed): ?>
                    <div class="cancel-box">
                        <strong>เหตุผลที่ยกเลิก:</strong> <?php echo $row['cancel_reason']; ?>
                    </div>
                <?php endif; ?>

                <?php if($is_completed): ?>
                    <div class="review-section">
                        <div style="font-weight:bold; margin-bottom:10px;">ความคิดเห็นจากลูกค้า:</div>
                        <?php if(isset($row['rating'])): ?>
                            <div class="review-box">
                                <div class="stars"><?php echo $stars_html; ?> <?php echo $row['rating']; ?>/5</div>
                                <div style="font-style:italic;">"<?php echo $row['review_text']; ?>"</div>
                            </div>
                        <?php else: ?>
                            <div style="color:#999; font-size:13px;">ลูกค้ายังไม่ได้รีวิว</div>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>
            </div>
        <?php } } else { echo "<div style='text-align:center; padding:50px; color:#999;'>ยังไม่มีประวัติการรับงาน</div>"; } ?>
    </div>
</body>
</html>