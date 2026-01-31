<?php 
    session_start();
    include('db.php');

    // เช็คล็อกอินและต้องเป็นช่างภาพเท่านั้น
    if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'photographer') { 
        header('location: login.php'); exit; 
    }

    $photographer_id = $_SESSION['user_id'];
    $msg = "";

    // --- 📤 ฟังก์ชันบันทึกการส่งงาน ---
    if (isset($_POST['submit_work'])) {
        $booking_id = mysqli_real_escape_string($conn, $_POST['booking_id']);
        $work_url = mysqli_real_escape_string($conn, $_POST['work_url']);

        $sql = "UPDATE bookings SET work_url='$work_url', status='completed' WHERE id='$booking_id' AND photographer_id='$photographer_id'";
        
        if (mysqli_query($conn, $sql)) {
            $msg = "<div class='alert success'>✅ ส่งงานเรียบร้อย! ลูกค้าสามารถดาวน์โหลดได้ทันที</div>";
        } else {
            $msg = "<div class='alert error'>❌ เกิดข้อผิดพลาด: ".mysqli_error($conn)."</div>";
        }
    }

    // ดึงงานของช่างภาพคนนี้ (เรียงจากงานใหม่ไปเก่า)
    // (JOIN ตาราง users เพื่อเอาชื่อลูกค้ามาแสดง)
    $sql = "SELECT bookings.*, users.username as customer_name, users.phone as customer_phone 
            FROM bookings 
            JOIN users ON bookings.customer_id = users.id 
            WHERE photographer_id = '$photographer_id' 
            ORDER BY booking_date DESC";
    $query = mysqli_query($conn, $sql);
?>

<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <title>จัดการงานและส่งงาน - RPU AM</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href="https://fonts.googleapis.com/css2?family=Sarabun:wght@300;400;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        /* 🎨 THEME: White Luxury */
        :root { --bg: #f4f4f4; --card: #ffffff; --gold: #D4AF37; --text: #333; --border: #e0e0e0; --green: #28a745; --blue: #007bff; }
        body { background: var(--bg); color: var(--text); font-family: 'Sarabun', sans-serif; margin: 0; padding: 20px; }
        
        .container { max-width: 800px; margin: 0 auto; padding-bottom: 50px; }
        .header { text-align: center; margin-bottom: 30px; }
        .header h2 { color: var(--gold); margin: 0; text-transform: uppercase; }

        /* การ์ดงาน */
        .job-card { 
            background: var(--card); padding: 20px; border-radius: 15px; 
            box-shadow: 0 5px 15px rgba(0,0,0,0.05); margin-bottom: 20px; 
            border: 1px solid white; display: flex; flex-wrap: wrap; justify-content: space-between; align-items: center;
            transition: 0.3s;
        }
        .job-card:hover { transform: translateY(-3px); box-shadow: 0 10px 25px rgba(0,0,0,0.1); border-color: var(--gold); }

        .job-info h3 { margin: 0 0 5px; color: #333; }
        .job-info p { margin: 3px 0; color: #666; font-size: 14px; }
        .job-info .badge { 
            display: inline-block; padding: 4px 10px; border-radius: 20px; 
            font-size: 12px; font-weight: bold; color: white; margin-top: 5px;
        }
        .badge.pending { background: #ffc107; color: #333; }
        .badge.confirmed { background: var(--blue); }
        .badge.completed { background: var(--green); }

        .job-action { text-align: right; min-width: 200px; }
        
        /* ปุ่ม */
        .btn-send { 
            background: var(--gold); color: white; border: none; padding: 10px 20px; 
            border-radius: 30px; cursor: pointer; font-weight: bold; transition: 0.2s;
        }
        .btn-send:hover { background: #b59021; }
        
        .btn-view {
            background: #eee; color: #555; text-decoration: none; padding: 10px 20px;
            border-radius: 30px; font-weight: bold; display: inline-block;
        }

        /* Modal Popup (กล่องเด้ง) */
        .modal {
            display: none; position: fixed; z-index: 999; left: 0; top: 0; width: 100%; height: 100%;
            background-color: rgba(0,0,0,0.5); backdrop-filter: blur(5px);
        }
        .modal-content {
            background-color: white; margin: 15% auto; padding: 30px; border-radius: 20px;
            width: 90%; max-width: 400px; text-align: center; position: relative;
        }
        .close { position: absolute; top: 15px; right: 20px; font-size: 24px; cursor: pointer; color: #aaa; }
        
        input[type="url"] { 
            width: 100%; padding: 12px; margin: 15px 0; border: 1px solid #ddd; 
            border-radius: 10px; box-sizing: border-box; font-family: 'Sarabun';
        }
        
        .alert { padding: 15px; border-radius: 10px; text-align: center; margin-bottom: 20px; }
        .success { background: #d4edda; color: #155724; border: 1px solid #c3e6cb; }
        .error { background: #f8d7da; color: #721c24; border: 1px solid #f5c6cb; }

        .nav-links { text-align: center; margin-bottom: 20px; }
        .nav-links a { margin: 0 10px; color: #666; text-decoration: none; }
        .nav-links a.active { color: var(--gold); font-weight: bold; }
    </style>
</head>
<body>

    <div class="container">
        <div class="header">
            <h2><i class="fas fa-briefcase"></i> รายการงานของฉัน</h2>
            <div class="nav-links">
                <a href="settings.php">ตั้งค่าร้าน</a> | 
                <a href="jobs.php" class="active">งานทั้งหมด</a> | 
                <a href="logout.php">ออกจากระบบ</a>
            </div>
        </div>

        <?php echo $msg; ?>

        <?php if(mysqli_num_rows($query) == 0): ?>
            <div style="text-align:center; color:#999; margin-top:50px;">
                <i class="fas fa-camera-retro" style="font-size:40px; margin-bottom:10px;"></i>
                <p>ยังไม่มีงานจองเข้ามา</p>
            </div>
        <?php else: ?>
            
            <?php while($row = mysqli_fetch_assoc($query)): ?>
                <div class="job-card">
                    <div class="job-info">
                        <h3>ลูกค้า: <?php echo $row['customer_name']; ?></h3>
                        <p><i class="far fa-calendar-alt"></i> วันที่: <?php echo date('d/m/Y', strtotime($row['booking_date'])); ?></p>
                        <p><i class="fas fa-clock"></i> รอบ: <?php echo ($row['time_slot']=='half')?'ครึ่งวัน':'เต็มวัน'; ?> (฿<?php echo number_format($row['price']); ?>)</p>
                        <p><i class="fas fa-phone"></i> โทร: <?php echo $row['customer_phone']; ?></p>
                        
                        <?php 
                            $status_class = $row['status'];
                            $status_text = ($row['status']=='pending')?'รอการยืนยัน':(($row['status']=='confirmed')?'รอส่งงาน':(($row['status']=='completed')?'ส่งงานแล้ว':'ยกเลิก'));
                        ?>
                        <span class="badge <?php echo $status_class; ?>"><?php echo $status_text; ?></span>
                    </div>

                    <div class="job-action">
                        <?php if($row['status'] == 'confirmed'): ?>
                            <button onclick="openSendModal('<?php echo $row['id']; ?>', '<?php echo $row['customer_name']; ?>')" class="btn-send">
                                <i class="fas fa-cloud-upload-alt"></i> ส่งงานลูกค้า
                            </button>
                        <?php elseif($row['status'] == 'completed'): ?>
                            <a href="<?php echo $row['work_url']; ?>" target="_blank" class="btn-view">
                                <i class="fas fa-check-circle"></i> ดูงานที่ส่งแล้ว
                            </a>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endwhile; ?>

        <?php endif; ?>
    </div>

    <div id="sendModal" class="modal">
        <div class="modal-content">
            <span class="close" onclick="closeModal()">&times;</span>
            <h3 style="color:var(--gold); margin-top:0;">ส่งงานให้ลูกค้า</h3>
            <p>ลูกค้า: <span id="modalCustomerName" style="font-weight:bold;"></span></p>
            <p style="font-size:12px; color:#666;">กรุณาวางลิงก์ Google Drive / OneDrive ที่นี่</p>
            
            <form method="post">
                <input type="hidden" name="booking_id" id="modalBookingId">
                <input type="url" name="work_url" required placeholder="https://drive.google.com/..." autofocus>
                <button type="submit" name="submit_work" class="btn-send" style="width:100%;">ยืนยันการส่งงาน</button>
            </form>
        </div>
    </div>

    <script>
        // ฟังก์ชันเปิด Modal
        function openSendModal(id, name) {
            document.getElementById('modalBookingId').value = id;
            document.getElementById('modalCustomerName').innerText = name;
            document.getElementById('sendModal').style.display = "block";
        }

        // ฟังก์ชันปิด Modal
        function closeModal() {
            document.getElementById('sendModal').style.display = "none";
        }

        // ปิดเมื่อกดข้างนอกกล่อง
        window.onclick = function(event) {
            if (event.target == document.getElementById('sendModal')) {
                closeModal();
            }
        }
    </script>

</body>
</html>