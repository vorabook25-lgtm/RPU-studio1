<?php 
    session_start();
    include('db.php');
    if (!isset($_SESSION['user_id'])) { header('location: login.php'); exit; }
    
    $photographer_id = isset($_GET['id']) ? mysqli_real_escape_string($conn, $_GET['id']) : 0;
    $selected_type = isset($_GET['type']) ? $_GET['type'] : 'digital';
    
    $sql = "SELECT * FROM users WHERE id = '$photographer_id' AND role = 'photographer'";
    $query = mysqli_query($conn, $sql);
    $pg = mysqli_fetch_assoc($query);

    if (!$pg) { echo "<script>alert('ไม่พบข้อมูลช่างภาพ'); window.location='marketplace.php';</script>"; exit; }

    // --- ดึงข้อมูลรีวิว & คะแนนเฉลี่ย ---
    $avg_query = mysqli_query($conn, "SELECT AVG(rating) as avg_score, COUNT(*) as total FROM reviews WHERE photographer_id = '$photographer_id'");
    $avg_data = mysqli_fetch_assoc($avg_query);
    $avg_score = number_format($avg_data['avg_score'], 1);
    $total_reviews = $avg_data['total'];

    $sql_reviews = "SELECT r.*, u.username, u.profile_img FROM reviews r 
                    JOIN users u ON r.user_id = u.id 
                    WHERE r.photographer_id = '$photographer_id' 
                    ORDER BY r.created_at DESC";
    $query_reviews = mysqli_query($conn, $sql_reviews);

    // --- Logic เวลา ---
    $work_start_str = $pg['work_start']; 
    $work_end_str   = $pg['work_end'];   
    $start_day = strtotime($work_start_str);
    $end_day   = strtotime($work_end_str);
    $time_options = "";
    for ($i = $start_day; $i <= $end_day; $i += 1800) { 
        $val = date("H:i", $i);
        $time_options .= "<option value='$val'>$val น.</option>";
    }

    $port_top = mysqli_query($conn, "SELECT * FROM portfolio WHERE user_id='$photographer_id' AND img_order > 0 ORDER BY img_order ASC");
    $port_gen = mysqli_query($conn, "SELECT * FROM portfolio WHERE user_id='$photographer_id' AND img_order = 0 ORDER BY id DESC LIMIT 12");
    $msg = "";

    // --- Logic จอง ---
    if (isset($_POST['submit_booking'])) {
        $customer_id = $_SESSION['user_id'];
        $date = mysqli_real_escape_string($conn, $_POST['date']);
        $location = mysqli_real_escape_string($conn, $_POST['location']);
        $job_type = mysqli_real_escape_string($conn, $_POST['job_type']);
        $camera_type = mysqli_real_escape_string($conn, $_POST['camera_type']);
        $time_start = mysqli_real_escape_string($conn, $_POST['time_start']);
        $time_end = mysqli_real_escape_string($conn, $_POST['time_end']);

        $ts1 = strtotime($time_start); $ts2 = strtotime($time_end);
        if ($ts2 <= $ts1) {
            $msg = "<div class='alert error'>❌ เวลาเลิกงานต้องมากกว่าเวลาเริ่มงานครับ</div>";
        } else {
            $duration = ($ts2 - $ts1) / 3600;
            $price = ($duration <= 4) ? $pg['price_half'] : $pg['price_full'];
            
            $slip_image = "";
            if (isset($_FILES['slip_file']) && $_FILES['slip_file']['error'] == 0) {
                $ext = pathinfo($_FILES['slip_file']['name'], PATHINFO_EXTENSION);
                $new_name = "slip_" . time() . "_" . $customer_id . "." . $ext;
                $upload_path = "../uploads/slips/" . $new_name;
                if (!file_exists("../uploads/slips/")) { mkdir("../uploads/slips/", 0777, true); }
                if (move_uploaded_file($_FILES['slip_file']['tmp_name'], $upload_path)) {
                    $slip_image = $new_name;
                }
            }

            $sql_book = "INSERT INTO bookings (user_id, photographer_id, booking_date, booking_time, booking_end_time, duration, location, job_type, camera_type, price, slip_image, status, payment_status) 
                         VALUES ('$customer_id', '$photographer_id', '$date', '$time_start', '$time_end', '$duration', '$location', '$job_type', '$camera_type', '$price', '$slip_image', 'pending', 'pending')";
            
            if (mysqli_query($conn, $sql_book)) {
                echo "<script>alert('✅ จองคิวเรียบร้อย!'); window.location='customer_bookings.php';</script>";
            } else {
                $msg = "<div class='alert error'>❌ เกิดข้อผิดพลาด</div>";
            }
        }
    }

    $folder_am = "../RPU AM/uploads/"; 
    $folder_portfolio = "../RPU AM/uploads/portfolio/"; 
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <title>จองคิวช่างภาพ | RPU UE</title>
    <link href="https://fonts.googleapis.com/css2?family=Sarabun:wght@300;400;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        body { font-family: 'Sarabun'; margin: 0; background: #f0f2f5; display: flex; height: 100vh; overflow: hidden; }
        .sidebar { width: 260px; background: #1a1a2e; color: white; padding: 25px; display: flex; flex-direction: column; flex-shrink: 0; }
        .sidebar h2 { color: #ff9f43; text-align: center; margin: 0 0 40px 0; font-size: 24px; letter-spacing: 1px; }
        .menu-item { padding: 12px 20px; color: #bdc3c7; text-decoration: none; display: flex; align-items: center; border-radius: 10px; margin-bottom: 12px; cursor: pointer; transition: 0.3s; }
        .menu-item:hover, .menu-item.active { background: #16213e; color: #ff9f43; transform: translateX(5px); }
        .menu-item i { margin-right: 15px; width: 25px; text-align: center; }
        .logout-box { margin-top: auto; } .menu-item.logout { color: #ff6b6b; } 

        .main-content { flex: 1; padding: 40px; overflow-y: auto; }
        .container { max-width: 900px; margin: auto; }
        
        .profile-header { background: white; border-radius: 15px; overflow: hidden; box-shadow: 0 5px 15px rgba(0,0,0,0.05); margin-bottom: 20px; display: flex; flex-wrap: wrap; }
        .cover-img { width: 100%; height: 150px; background: linear-gradient(45deg, #1a1a2e, #ff9f43); }
        .info-sec { padding: 20px; position: relative; width: 100%; margin-top: -50px; text-align: center; }
        .avatar { width: 120px; height: 120px; border-radius: 50%; border: 5px solid white; object-fit: cover; background: white; box-shadow: 0 5px 15px rgba(0,0,0,0.1); }
        .name-tag { font-size: 1.5rem; font-weight: bold; margin: 10px 0 5px; }
        .price-tag { background: #fff8e1; color: #e67e22; padding: 5px 15px; border-radius: 20px; display: inline-block; font-weight: bold; margin-bottom: 15px; }

        .content-grid { display: grid; grid-template-columns: 2fr 1fr; gap: 20px; }
        .card { background: white; padding: 25px; border-radius: 15px; box-shadow: 0 5px 15px rgba(0,0,0,0.05); margin-bottom: 20px; }
        h3 { margin-top: 0; border-left: 4px solid #ff9f43; padding-left: 10px; color: #1a1a2e; }
        
        .top3-grid { display: grid; grid-template-columns: repeat(3, 1fr); gap: 10px; margin-bottom: 20px; }
        .top-item img { width: 100%; height: 150px; object-fit: cover; border-radius: 10px; cursor: pointer; transition: 0.3s; }
        .top-item img:hover { transform: scale(1.05); }
        .gen-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(100px, 1fr)); gap: 10px; }
        .gen-item img { width: 100%; height: 100px; object-fit: cover; border-radius: 8px; }

        /* Booking & Payment */
        .booking-box { background: white; padding: 25px; border-radius: 15px; box-shadow: 0 5px 15px rgba(0,0,0,0.05); position: sticky; top: 20px; border-top: 5px solid #ff9f43; }
        label { display: block; margin-top: 15px; font-weight: bold; font-size: 14px; color:#555; }
        input, select { width: 100%; padding: 12px; margin-top: 5px; border: 1px solid #ddd; border-radius: 8px; box-sizing: border-box; font-family: 'Sarabun'; }
        .btn-book { width: 100%; padding: 15px; background: #ff9f43; color: white; border: none; border-radius: 30px; font-weight: bold; margin-top: 25px; cursor: pointer; transition: 0.3s; font-size: 16px; }
        .btn-book:hover { background: #e67e22; transform: translateY(-2px); }
        .btn-book:disabled { background: #ccc; cursor: not-allowed; }
        
        .alert { padding: 10px; border-radius: 8px; margin-bottom: 10px; text-align: center; } .error { background: #f8d7da; color: #721c24; }
        .equip-badge { background: #e8f5e9; padding: 12px; border-radius: 8px; margin-bottom: 15px; border: 1px solid #c3e6cb; color: #155724; font-size: 14px; display: flex; align-items: center; gap: 10px; }
        .btn-chat-profile { background: #28a745; color: white; padding: 8px 20px; border-radius: 30px; text-decoration: none; font-weight: bold; display: inline-block; margin-top: 10px; transition: 0.3s; border: 1px solid #28a745; }
        .btn-chat-profile:hover { background: #218838; transform: translateY(-2px); }
        
        .payment-section { margin-top: 20px; padding: 15px; background: #fafafa; border: 1px dashed #bbb; border-radius: 10px; text-align: center; }
        .qr-code { width: 120px; height: 120px; margin: 10px auto; display: block; }
        .price-display { font-size: 1.2rem; font-weight: bold; color: #e74c3c; }
        #slipPreview { width: 100%; border-radius: 8px; margin-top: 10px; border: 1px solid #eee; display: none; }
        #duration-result { margin-top: 10px; padding: 10px; background: #e3f2fd; color: #0d47a1; border-radius: 8px; font-size: 14px; text-align: center; display: none; border: 1px solid #bbdefb; }
        .time-row { display: flex; gap: 10px; } .time-col { flex: 1; }

        /* Review Styles */
        .review-list { margin-top: 15px; }
        .review-item { padding: 15px 0; border-bottom: 1px solid #eee; display: flex; gap: 15px; }
        .rev-avatar { width: 50px; height: 50px; border-radius: 50%; object-fit: cover; }
        .star-show { color: #f1c40f; font-size: 14px; }
    </style>
</head>
<body>

    <div class="sidebar">
        <h2>RPU UE</h2>
        <a class="menu-item active" href="marketplace.php"><i class="fas fa-search"></i> หาช่างภาพ</a> 
        <a class="menu-item" href="customer_bookings.php"><i class="fas fa-calendar-check"></i> ประวัติการจองของฉัน</a>
        <a class="menu-item" href="customer_chat.php"><i class="fas fa-comments"></i> แชทกับช่างภาพ</a>
        <a class="menu-item" href="customer_settings.php"><i class="fas fa-cog"></i> ตั้งค่าบัญชี</a>
        <div class="logout-box"><a class="menu-item logout" href="logout.php"><i class="fas fa-sign-out-alt"></i> ออกจากระบบ</a></div>
    </div>

    <div class="main-content">
        <div class="container">
            <div class="profile-header">
                <div class="cover-img"></div>
                <div class="info-sec">
                    <?php 
                        $img_filename = $pg['profile_img'];
                        $check_file = $folder_am . $img_filename;
                        $show_img = (!empty($img_filename) && file_exists($check_file)) ? $check_file : "https://cdn-icons-png.flaticon.com/512/3135/3135715.png";
                    ?>
                    <img src="<?php echo $show_img; ?>" class="avatar" onerror="this.src='https://cdn-icons-png.flaticon.com/512/3135/3135715.png'">
                    <div class="name-tag"><?php echo $pg['username']; ?> <i class="fas fa-check-circle" style="color:#28a745; font-size:1rem;"></i></div>
                    
                    <div style="color:#f1c40f; font-size:1.1rem; margin-bottom:5px;">
                        ★ <?php echo $avg_score; ?> / 5.0 (<?php echo $total_reviews; ?> รีวิว)
                    </div>

                    <div class="price-tag">ราคา: <?php echo number_format($pg['price_half']); ?> - <?php echo number_format($pg['price_full']); ?> บาท</div>
                    <div style="color:#666;"><i class="far fa-clock"></i> เวลาทำงานปกติ: <?php echo date("H:i", strtotime($pg['work_start'])); ?> - <?php echo date("H:i", strtotime($pg['work_end'])); ?></div>
                    <a href="customer_chat.php?pg_id=<?php echo $photographer_id; ?>" class="btn-chat-profile"><i class="fas fa-comment-dots"></i> แชท/สอบถาม</a>
                </div>
            </div>

            <div class="content-grid">
                <div class="left-col">
                    <div class="card">
                        <h3>🏆 ผลงานเด่น</h3>
                        <div class="top3-grid">
                            <?php if(mysqli_num_rows($port_top) > 0) {
                                while($p = mysqli_fetch_assoc($port_top)) { 
                                    $p_img = $folder_portfolio . $p['image_file'];
                                    echo "<div class='top-item'><img src='$p_img' onerror=\"this.src='https://via.placeholder.com/300?text=No+Image'\"></div>"; 
                                }
                            } else { echo "<p style='color:#999; text-align:center;'>ไม่มีรูปแนะนำ</p>"; } ?>
                        </div>
                    </div>
                    <div class="card">
                        <h3>🖼️ แกลเลอรี</h3>
                        <div class="gen-grid">
                            <?php if(mysqli_num_rows($port_gen) > 0) {
                                while($p = mysqli_fetch_assoc($port_gen)) { 
                                    $p_img = $folder_portfolio . $p['image_file'];
                                    echo "<div class='gen-item'><img src='$p_img' onerror=\"this.src='https://via.placeholder.com/150?text=No+Image'\"></div>"; 
                                }
                            } else { echo "<p style='color:#999; text-align:center; grid-column:1/-1;'>ไม่มีรูปเพิ่มเติม</p>"; } ?>
                        </div>
                    </div>

                    <div class="card">
                        <h3>💬 รีวิวจากลูกค้า</h3>
                        <div class="review-list">
                            <?php if(mysqli_num_rows($query_reviews) > 0) {
                                while($rev = mysqli_fetch_assoc($query_reviews)) {
                                    $u_img = !empty($rev['profile_img']) ? "../RPU UM/uploads/".$rev['profile_img'] : "https://cdn-icons-png.flaticon.com/512/3135/3135715.png";
                            ?>
                                <div class="review-item">
                                    <img src="<?php echo $u_img; ?>" class="rev-avatar">
                                    <div>
                                        <div style="font-weight:bold;"><?php echo $rev['username']; ?></div>
                                        <div class="star-show">
                                            <?php for($i=1; $i<=5; $i++) { echo ($i <= $rev['rating']) ? '★' : '☆'; } ?>
                                        </div>
                                        <p style="margin:5px 0; color:#555; font-size:14px;"><?php echo $rev['comment']; ?></p>
                                        <small style="color:#999;"><?php echo date("d/m/Y H:i", strtotime($rev['created_at'])); ?></small>
                                    </div>
                                </div>
                            <?php } } else { echo "<p style='text-align:center; color:#999; padding:20px;'>ยังไม่มีรีวิว</p>"; } ?>
                        </div>
                    </div>
                </div>

                <div class="right-col">
                    <div class="booking-box">
                        <h3 style="margin-bottom:20px;">📅 จองคิว & ชำระเงิน</h3>
                        <?php echo $msg; ?>
                        
                        <form method="post" id="bookingForm" enctype="multipart/form-data">
                            <div class="equip-badge">
                                <?php 
                                    if($selected_type == 'digital') { echo '<i class="fas fa-camera"></i> <span>อุปกรณ์: <strong>กล้องดิจิตอล</strong></span>'; }
                                    elseif($selected_type == 'mobile') { echo '<i class="fas fa-mobile-alt"></i> <span>อุปกรณ์: <strong>กล้องมือถือ</strong></span>'; }
                                    else { echo '<i class="fas fa-video"></i> <span>อุปกรณ์: <strong>ของผู้ว่าจ้าง</strong></span>'; }
                                ?>
                            </div>
                            <input type="hidden" name="camera_type" value="<?php echo $selected_type; ?>">

                            <label><i class="fas fa-briefcase"></i> ประเภทงาน</label>
                            <select name="job_type" required>
                                <option value="รับปริญญา">🎓 รับปริญญา</option>
                                <option value="งานแต่งงาน">💍 งานแต่งงาน</option>
                                <option value="ถ่ายแบบ/Profile">📸 ถ่ายแบบ / Profile</option>
                                <option value="Event/กิจกรรม">🎉 Event / กิจกรรม</option>
                                <option value="อื่นๆ">📌 อื่นๆ</option>
                            </select>

                            <label><i class="far fa-calendar-alt"></i> วันที่ต้องการจอง</label>
                            <input type="date" name="date" required min="<?php echo date('Y-m-d'); ?>">

                            <div class="time-row">
                                <div class="time-col">
                                    <label><i class="far fa-clock"></i> เวลาเริ่ม</label>
                                    <select name="time_start" id="time_start" required onchange="calculateTime()">
                                        <option value="" disabled selected>-- เลือก --</option>
                                        <?php echo $time_options; ?>
                                    </select>
                                </div>
                                <div class="time-col">
                                    <label><i class="fas fa-hourglass-end"></i> เวลาเลิก</label>
                                    <select name="time_end" id="time_end" required onchange="calculateTime()">
                                        <option value="" disabled selected>-- เลือก --</option>
                                        <?php echo $time_options; ?>
                                    </select>
                                </div>
                            </div>
                            
                            <div id="duration-result"></div>

                            <label><i class="fas fa-map-marker-alt"></i> สถานที่นัดหมาย</label>
                            <input type="text" name="location" placeholder="ระบุสถานที่ให้ชัดเจน..." required>

                            <div class="payment-section">
                                <h4 style="margin:0 0 10px; color:#333;">💳 โอนมัดจำ/ชำระเงิน</h4>
                                <div style="font-size:14px; color:#555;">ยอดที่ต้องชำระโดยประมาณ</div>
                                <div class="price-display" id="showPrice">0 บาท</div>

                                <img src="https://upload.wikimedia.org/wikipedia/commons/d/d0/QR_code_for_mobile_English_Wikipedia.svg" class="qr-code">
                                <p style="font-size:12px; margin:5px; color:#666;">
                                    กสิกรไทย: <strong>000-000-0000</strong><br>
                                    ชื่อบัญชี: RPU SYSTEM
                                </p>

                                <label style="margin-top:10px;"><i class="fas fa-file-upload"></i> แนบสลิปโอนเงิน (บังคับ)</label>
                                <input type="file" name="slip_file" accept="image/*" required onchange="previewSlip(this)">
                                <img id="slipPreview" src="#" alt="ตัวอย่างสลิป">
                            </div>

                            <button type="submit" name="submit_booking" id="btnSubmit" class="btn-book" disabled>ยืนยันการจองคิว</button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        function calculateTime() {
            var start = document.getElementById('time_start').value;
            var end = document.getElementById('time_end').value;
            var resultBox = document.getElementById('duration-result');
            var btnSubmit = document.getElementById('btnSubmit');
            var showPrice = document.getElementById('showPrice');

            var priceHalf = <?php echo $pg['price_half']; ?>;
            var priceFull = <?php echo $pg['price_full']; ?>;

            if (start && end) {
                var dateBase = "2000-01-01 ";
                var startDate = new Date(dateBase + start);
                var endDate = new Date(dateBase + end);

                var diffMs = endDate - startDate;
                var diffHrs = diffMs / (1000 * 60 * 60);

                if (diffHrs <= 0) {
                    resultBox.style.display = "block";
                    resultBox.style.background = "#f8d7da";
                    resultBox.style.color = "#721c24";
                    resultBox.innerHTML = "❌ เวลาเลิกต้องมากกว่าเวลาเริ่มครับ";
                    btnSubmit.disabled = true;
                    showPrice.innerHTML = "0 บาท";
                } else {
                    resultBox.style.display = "block";
                    resultBox.style.background = "#e3f2fd";
                    resultBox.style.color = "#0d47a1";
                    resultBox.innerHTML = "✅ รวมระยะเวลา: <strong>" + diffHrs + " ชั่วโมง</strong>";
                    btnSubmit.disabled = false;

                    var finalPrice = (diffHrs <= 4) ? priceHalf : priceFull;
                    showPrice.innerHTML = finalPrice.toLocaleString() + " บาท";
                }
            }
        }

        function previewSlip(input) {
            var preview = document.getElementById('slipPreview');
            if (input.files && input.files[0]) {
                var reader = new FileReader();
                reader.onload = function(e) {
                    preview.src = e.target.result;
                    preview.style.display = 'block';
                }
                reader.readAsDataURL(input.files[0]);
            }
        }
    </script>
</body>
</html>