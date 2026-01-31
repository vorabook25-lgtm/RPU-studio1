<?php 
    session_start();
    include('db.php');
    if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'photographer') { header('location: login.php'); exit; }
    
    $pg_id = $_SESSION['user_id'];
    $msg = "";

    // --- Logic Badge ---
    $sql_pending = "SELECT COUNT(*) as count FROM bookings WHERE photographer_id = '$pg_id' AND status = 'pending'";
    $noti_job_count = mysqli_fetch_assoc(mysqli_query($conn, $sql_pending))['count'];
    $job_badge = ($noti_job_count > 0) ? "<span class='badge'>$noti_job_count</span>" : "";

    $noti_chat_query = mysqli_query($conn, "SELECT COUNT(*) as count FROM bookings WHERE photographer_id = '$pg_id' AND status IN ('confirmed', 'paid')");
    $noti_chat_count = mysqli_fetch_assoc($noti_chat_query)['count'];
    $chat_badge = ($noti_chat_count > 0) ? "<span class='badge'>$noti_chat_count</span>" : "";

    // --- 1. Logic: ยืนยันการจ่ายเงิน (Approve Payment) ---
    if (isset($_POST['approve_payment'])) {
        $booking_id = mysqli_real_escape_string($conn, $_POST['booking_id']);
        // อัปเดตสถานะการเงินเป็น 'paid'
        $sql = "UPDATE bookings SET payment_status = 'paid' WHERE id = '$booking_id' AND photographer_id = '$pg_id'";
        if (mysqli_query($conn, $sql)) {
            $msg = "<div class='alert success'>💰 ยืนยันยอดเงินเรียบร้อยแล้วครับ!</div>";
        }
    }

    // --- 2. Logic: อัปเดตสถานะงาน (รับงาน) ---
    if (isset($_POST['update_status'])) {
        $booking_id = mysqli_real_escape_string($conn, $_POST['booking_id']);
        $status = mysqli_real_escape_string($conn, $_POST['status']);
        $sql = "UPDATE bookings SET status='$status' WHERE id='$booking_id' AND photographer_id='$pg_id'";
        if (mysqli_query($conn, $sql)) { echo "<script>window.location='photographer_dashboard.php';</script>"; }
    }

    // --- 3. Logic: ยกเลิกงาน ---
    if (isset($_POST['cancel_job'])) {
        $booking_id = mysqli_real_escape_string($conn, $_POST['booking_id']);
        $reason = ($_POST['cancel_reason_select'] == 'other') ? $_POST['cancel_reason_text'] : $_POST['cancel_reason_select'];
        mysqli_query($conn, "UPDATE bookings SET status = 'cancelled', cancel_reason = '$reason' WHERE id = '$booking_id'");
    }

    // --- ดึงข้อมูลงาน ---
    $sql = "SELECT b.*, u.username as customer_name, u.phone as customer_phone 
            FROM bookings b 
            LEFT JOIN users u ON b.user_id = u.id 
            WHERE b.photographer_id = '$pg_id' 
            ORDER BY b.id DESC";
    $query = mysqli_query($conn, $sql);
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <title>หน้าหลัก | RPU AM</title>
    <link href="https://fonts.googleapis.com/css2?family=Sarabun:wght@300;400;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        body { font-family: 'Sarabun', sans-serif; margin: 0; background: #f0f2f5; display: flex; height: 100vh; overflow: hidden; }
        .sidebar { width: 260px; background: #1a1a2e; color: white; padding: 25px; display: flex; flex-direction: column; flex-shrink: 0; }
        .sidebar h2 { color: #00d2d3; text-align: center; margin: 0 0 40px 0; font-size: 24px; letter-spacing: 1px; }
        .menu-item { position: relative; padding: 12px 20px; color: #bdc3c7; text-decoration: none; display: flex; align-items: center; border-radius: 10px; margin-bottom: 12px; cursor: pointer; transition: 0.3s; }
        .menu-item:hover, .menu-item.active { background: #16213e; color: #00d2d3; }
        .menu-item i { margin-right: 15px; width: 25px; text-align: center; font-size: 18px; }
        .badge { background: #ff4757; color: white; border-radius: 50%; padding: 2px 8px; font-size: 12px; position: absolute; right: 15px; top: 50%; transform: translateY(-50%); }
        .logout-box { margin-top: auto; } .menu-item.logout { color: #ff6b6b; } 

        .main-content { flex: 1; padding: 40px; overflow-y: auto; }
        .job-card { background: white; border-radius: 15px; padding: 25px; margin-bottom: 20px; box-shadow: 0 4px 10px rgba(0,0,0,0.05); border-left: 5px solid #ccc; position: relative; }
        .status-pending { border-left-color: #ffc107; } .status-confirmed { border-left-color: #17a2b8; } .status-completed { border-left-color: #28a745; }
        
        .job-header { display: flex; justify-content: space-between; border-bottom: 1px solid #eee; padding-bottom: 15px; margin-bottom: 15px; }
        .detail-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 15px; margin-bottom: 15px; font-size: 15px; }
        
        .btn-action { padding: 8px 15px; border: none; border-radius: 20px; cursor: pointer; color: white; font-weight: bold; font-family: 'Sarabun'; display: inline-block; text-decoration: none; font-size: 14px; }
        .btn-accept { background: #17a2b8; } 
        .btn-go-upload { background: #6c5ce7; }
        .btn-cancel { background: #ff6b6b; float: right; border-radius: 20px; color:white; padding: 8px 15px; border:none; cursor: pointer; } 
        
        /* สไตล์สำหรับส่วนการเงิน */
        .payment-box { background: #f8f9fa; padding: 10px; border-radius: 10px; margin-bottom: 15px; display: flex; align-items: center; justify-content: space-between; border: 1px solid #e9ecef; }
        .pay-status { font-weight: bold; padding: 4px 10px; border-radius: 15px; font-size: 13px; }
        .pay-unpaid { background: #e2e6ea; color: #6c757d; }
        .pay-pending { background: #fff3cd; color: #856404; }
        .pay-paid { background: #d4edda; color: #155724; }
        .btn-slip { background: #1a1a2e; color: #00d2d3; border: 1px solid #1a1a2e; padding: 5px 15px; border-radius: 15px; cursor: pointer; font-size: 13px; margin-right: 5px; }
        .btn-approve { background: #28a745; color: white; border: none; padding: 5px 15px; border-radius: 15px; cursor: pointer; font-size: 13px; }

        /* Modal */
        .modal { display: none; position: fixed; z-index: 999; left: 0; top: 0; width: 100%; height: 100%; background-color: rgba(0,0,0,0.8); }
        .modal-content { background-color: white; margin: 5% auto; padding: 20px; border-radius: 15px; width: 90%; max-width: 500px; position:relative; text-align: center; }
        .slip-preview { max-width: 100%; max-height: 70vh; border-radius: 10px; box-shadow: 0 5px 15px rgba(0,0,0,0.2); }
        .close { position:absolute; right:15px; top:10px; font-size: 30px; cursor: pointer; color:#333; } 
        .alert { padding: 15px; border-radius: 10px; margin-bottom: 20px; text-align: center; }
        .success { background: #d4edda; color: #155724; } .error { background: #f8d7da; color: #721c24; }
    </style>
</head>
<body>
    <div class="sidebar">
        <h2>RPU AM</h2>
        <a class="menu-item active" href="photographer_dashboard.php"><i class="fas fa-home"></i> หน้าหลัก <?php echo $job_badge; ?></a>
        <a class="menu-item" href="photographer_portfolio.php"><i class="fas fa-images"></i> แฟ้มผลงาน</a>
        <a class="menu-item" href="photographer_upload.php"><i class="fas fa-cloud-upload-alt"></i> ส่งมอบงาน</a>
        <a class="menu-item" href="job_history.php"><i class="fas fa-history"></i> ประวัติการรับงาน</a>
        <a class="menu-item" href="photographer_chat.php"><i class="fas fa-comments"></i> แชทลูกค้า <?php echo $chat_badge; ?></a>
        <a class="menu-item" href="settings.php"><i class="fas fa-cog"></i> ตั้งค่าระบบ</a>
        <div class="logout-box"><a class="menu-item logout" href="logout.php"><i class="fas fa-sign-out-alt"></i> ออกจากระบบ</a></div>
    </div>

    <div class="main-content">
        <h1>📋 รายการงาน </h1>
        <?php echo $msg; ?>
        
        <?php if (mysqli_num_rows($query) > 0) { while($row = mysqli_fetch_assoc($query)) { ?>
            <div class="job-card status-<?php echo $row['status']; ?>">
                <div class="job-header">
                    <span style="font-weight:bold; font-size:1.1rem;">#<?php echo $row['id']; ?> : <?php echo $row['job_type']; ?></span>
                    <span style="background:#e3f2fd; color:#007bff; padding:5px 12px; border-radius:15px; font-size:0.9rem;">
                        <?php echo strtoupper($row['status']); ?>
                    </span>
                </div>
                
                <div class="detail-grid">
                    <div>📅 วันที่: <b><?php echo $row['booking_date']; ?></b></div>
                    <div>👤 ลูกค้า: <b><?php echo $row['customer_name']; ?></b></div>
                    <div>📞 โทร: <b><?php echo $row['customer_phone']; ?></b></div>
                </div>

                <div class="payment-box">
                    <div>
                        <i class="fas fa-money-bill-wave"></i> สถานะการเงิน: 
                        <span class="pay-status pay-<?php echo $row['payment_status']; ?>">
                            <?php 
                                if($row['payment_status']=='paid') echo '✅ จ่ายแล้ว';
                                elseif($row['payment_status']=='pending') echo '⏳ รอตรวจสอบสลิป';
                                else echo '❌ ยังไม่จ่าย';
                            ?>
                        </span>
                    </div>
                    <div>
                        <?php if(!empty($row['slip_image'])) { ?>
                            <button type="button" class="btn-slip" onclick="viewSlip('../uploads/slips/<?php echo $row['slip_image']; ?>')">
                                <i class="fas fa-file-invoice"></i> ดูสลิป
                            </button>
                        <?php } ?>

                        <?php if($row['payment_status'] != 'paid' && $row['status'] != 'cancelled') { ?>
                            <form method="post" style="display:inline;">
                                <input type="hidden" name="booking_id" value="<?php echo $row['id']; ?>">
                                <button type="submit" name="approve_payment" class="btn-approve" onclick="return confirm('ยืนยันว่าได้รับเงินครบถ้วนแล้ว?')">
                                    <i class="fas fa-check"></i> ยืนยันยอด
                                </button>
                            </form>
                        <?php } ?>
                    </div>
                </div>

                <div style="border-top:1px solid #eee; padding-top:15px;">
                    <?php if($row['status'] == 'pending') { ?>
                        <form method="post" style="display:inline;">
                            <input type="hidden" name="booking_id" value="<?php echo $row['id']; ?>">
                            <input type="hidden" name="status" value="confirmed">
                            <button type="submit" name="update_status" class="btn-action btn-accept">ยืนยันรับงาน</button>
                        </form>
                        <button class="btn-cancel" onclick="openCancelModal('<?php echo $row['id']; ?>')">ปฏิเสธ</button>
                    
                    <?php } elseif($row['status'] == 'confirmed') { ?>
                        <a href="photographer_upload.php" class="btn-action btn-go-upload"><i class="fas fa-cloud-upload-alt"></i> ไปหน้าส่งงาน</a>
                        <button class="btn-cancel" onclick="openCancelModal('<?php echo $row['id']; ?>')">ยกเลิก</button>

                    <?php } elseif($row['status'] == 'completed') { ?>
                        <div style="color:#28a745;">✅ <strong>ส่งมอบงานเรียบร้อยแล้ว</strong></div>
                    <?php } else { echo "<span style='color:red;'>งานถูกยกเลิก</span>"; } ?>
                </div>
            </div>
        <?php } } else { echo "<div style='text-align:center; color:#999; margin-top:50px;'>ไม่มีงานใหม่</div>"; } ?>
    </div>

    <div id="slipModal" class="modal">
        <div class="modal-content">
            <span class="close" onclick="closeSlipModal()">&times;</span>
            <h3>📄 หลักฐานการโอนเงิน</h3>
            <img id="slipImg" class="slip-preview" src="" alt="Slip">
            <br><br>
            <button onclick="closeSlipModal()" style="padding:10px 20px; background:#333; color:white; border:none; border-radius:20px; cursor:pointer;">ปิดหน้าต่าง</button>
        </div>
    </div>

    <div id="cancelModal" class="modal">
        <div class="modal-content">
            <span class="close" onclick="closeCancelModal()">&times;</span>
            <h3 style="color:#ff4757;">⚠️ ยกเลิกงาน</h3>
            <form method="post">
                <input type="hidden" id="modalBookingId" name="booking_id">
                <label>เหตุผล:</label>
                <select name="cancel_reason_select" id="reasonSelect" onchange="checkReason(this.value)" required style="width:100%; padding:10px; margin:10px 0;">
                    <option value="">-- เลือกเหตุผล --</option>
                    <option value="ติดภารกิจด่วน">ติดภารกิจด่วน</option>
                    <option value="other">อื่นๆ</option>
                </select>
                <div id="otherReasonBox" style="display:none;"><input type="text" name="cancel_reason_text" placeholder="ระบุเหตุผล..." style="width:100%; padding:10px;"></div>
                <button type="submit" name="cancel_job" style="width:100%; padding:10px; background:#ff4757; color:white; border:none; border-radius:5px; margin-top:10px; cursor:pointer;">ยืนยัน</button>
            </form>
        </div>
    </div>
    
    <script>
        // Slip Modal Logic
        function viewSlip(imgSrc) {
            document.getElementById('slipImg').src = imgSrc;
            document.getElementById('slipModal').style.display = 'block';
        }
        function closeSlipModal() { document.getElementById('slipModal').style.display = 'none'; }

        // Cancel Modal Logic
        function openCancelModal(id) { document.getElementById('modalBookingId').value = id; document.getElementById('cancelModal').style.display = 'block'; }
        function closeCancelModal() { document.getElementById('cancelModal').style.display = 'none'; }
        function checkReason(val) { document.getElementById('otherReasonBox').style.display = (val === 'other') ? 'block' : 'none'; }
        
        window.onclick = function(e) { 
            if(e.target == document.getElementById('cancelModal')) closeCancelModal(); 
            if(e.target == document.getElementById('slipModal')) closeSlipModal();
        }
    </script>
</body>
</html>