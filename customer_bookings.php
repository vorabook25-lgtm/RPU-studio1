<?php 
    session_start();
    include('db.php');
    if (!isset($_SESSION['user_id'])) { header('location: login.php'); exit; }
    $user_id = $_SESSION['user_id'];
    $msg = "";

    // --- 1. Logic: ส่งรีวิว ---
    if (isset($_POST['submit_review'])) {
        $booking_id = mysqli_real_escape_string($conn, $_POST['booking_id']);
        $pg_id = mysqli_real_escape_string($conn, $_POST['pg_id']);
        $rating = mysqli_real_escape_string($conn, $_POST['rating']);
        $comment = mysqli_real_escape_string($conn, $_POST['comment']);

        // เช็คว่าเคยรีวิวไปหรือยัง
        $check = mysqli_query($conn, "SELECT * FROM reviews WHERE booking_id = '$booking_id'");
        if(mysqli_num_rows($check) == 0) {
            $sql = "INSERT INTO reviews (booking_id, user_id, photographer_id, rating, comment) 
                    VALUES ('$booking_id', '$user_id', '$pg_id', '$rating', '$comment')";
            if (mysqli_query($conn, $sql)) {
                $msg = "<div class='alert success'>⭐ ขอบคุณสำหรับการรีวิวครับ!</div>";
            }
        }
    }

    // --- 2. Logic: อัปโหลดสลิป (คงเดิม) ---
    if (isset($_POST['upload_slip'])) {
        $booking_id = mysqli_real_escape_string($conn, $_POST['booking_id']);
        if (isset($_FILES['slip_file']) && $_FILES['slip_file']['error'] == 0) {
            $ext = pathinfo($_FILES['slip_file']['name'], PATHINFO_EXTENSION);
            $new_name = "slip_" . time() . "_" . $booking_id . "." . $ext;
            $upload_path = "../uploads/slips/" . $new_name; 
            if (!file_exists("../uploads/slips/")) { mkdir("../uploads/slips/", 0777, true); }
            if (move_uploaded_file($_FILES['slip_file']['tmp_name'], $upload_path)) {
                $sql = "UPDATE bookings SET slip_image = '$new_name', payment_status = 'pending' WHERE id = '$booking_id' AND user_id = '$user_id'";
                if (mysqli_query($conn, $sql)) { $msg = "<div class='alert success'>✅ ส่งหลักฐานเรียบร้อย</div>"; }
            }
        }
    }

    // --- ดึงข้อมูลการจอง + เช็คสถานะรีวิว ---
    $sql = "SELECT b.*, u.username as pg_name, u.profile_img as pg_img, u.phone as pg_phone, u.price_half, u.price_full,
            (SELECT COUNT(*) FROM reviews r WHERE r.booking_id = b.id) as is_reviewed
            FROM bookings b 
            JOIN users u ON b.photographer_id = u.id 
            WHERE b.user_id = '$user_id' 
            ORDER BY b.id DESC";
    $query = mysqli_query($conn, $sql);
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <title>ประวัติการจอง | RPU UE</title>
    <link href="https://fonts.googleapis.com/css2?family=Sarabun:wght@300;400;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        /* CSS เดิม */
        body { font-family: 'Sarabun'; margin: 0; background: #f0f2f5; display: flex; height: 100vh; overflow: hidden; }
        .sidebar { width: 260px; background: #1a1a2e; color: white; padding: 25px; display: flex; flex-direction: column; flex-shrink: 0; }
        .sidebar h2 { color: #ff9f43; text-align: center; margin: 0 0 40px 0; font-size: 24px; letter-spacing: 1px; }
        .menu-item { padding: 12px 20px; color: #bdc3c7; text-decoration: none; display: flex; align-items: center; border-radius: 10px; margin-bottom: 12px; cursor: pointer; transition: 0.3s; }
        .menu-item:hover, .menu-item.active { background: #16213e; color: #ff9f43; transform: translateX(5px); }
        .menu-item i { margin-right: 15px; width: 25px; text-align: center; }
        .logout-box { margin-top: auto; } .menu-item.logout { color: #ff6b6b; } 

        .main-content { flex: 1; padding: 40px; overflow-y: auto; }
        .alert { padding: 15px; border-radius: 10px; margin-bottom: 20px; text-align: center; }
        .success { background: #d4edda; color: #155724; } .error { background: #f8d7da; color: #721c24; }

        .job-card { background: white; border-radius: 15px; padding: 25px; margin-bottom: 20px; box-shadow: 0 5px 15px rgba(0,0,0,0.05); display: flex; gap: 20px; align-items: flex-start; border-left: 5px solid #ccc; position: relative; }
        .status-completed { border-left-color: #28a745; } .status-confirmed { border-left-color: #17a2b8; } .status-pending { border-left-color: #ffc107; }
        
        .pg-img { width: 70px; height: 70px; border-radius: 50%; object-fit: cover; border: 3px solid #ff9f43; }
        .job-details { flex: 1; }
        .job-meta-grid { display: grid; grid-template-columns: repeat(2, 1fr); gap: 10px; font-size: 14px; color: #555; margin-top:10px; }
        .job-meta-item i { color: #ff9f43; width: 20px; text-align: center; }
        
        .action-area { display: flex; flex-direction: column; gap: 8px; justify-content: center; min-width: 160px; text-align: right; }
        .btn { padding: 8px 15px; border-radius: 20px; text-decoration: none; font-size: 13px; cursor: pointer; border: none; display: inline-block; transition: 0.2s; color: white; width: 100%; box-sizing: border-box; text-align: center; }
        
        .btn-chat { background: #1a1a2e; }
        .btn-pay { background: #ff9f43; } .btn-pay:hover { background: #e67e22; }
        .btn-receipt { background: #6c5ce7; } .btn-receipt:hover { background: #5647c9; }
        .btn-gallery { background: #28a745; } .btn-gallery:hover { background: #218838; }
        /* 🔥 ปุ่มรีวิว */
        .btn-review { background: #f1c40f; color: #333; } .btn-review:hover { background: #f39c12; }

        .pay-badge { font-size: 12px; padding: 3px 8px; border-radius: 10px; font-weight: bold; }
        .pay-unpaid { background: #ffebee; color: #c62828; }
        .pay-pending { background: #fff8e1; color: #f57f17; }
        .pay-paid { background: #e8f5e9; color: #2e7d32; }

        /* Modals */
        .modal { display: none; position: fixed; z-index: 999; left: 0; top: 0; width: 100%; height: 100%; background-color: rgba(0,0,0,0.8); overflow-y: auto; }
        .modal-content { background-color: white; margin: 10% auto; padding: 25px; border-radius: 15px; width: 90%; max-width: 400px; text-align: center; position:relative; }
        
        .gallery-modal-content { background-color: #1a1a2e; margin: 5% auto; width: 90%; max-width: 1000px; padding: 20px; border-radius: 10px; min-height: 50vh; }
        .gallery-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(200px, 1fr)); gap: 15px; margin-top: 20px; }
        .gallery-item { position: relative; border-radius: 8px; overflow: hidden; background: #000; box-shadow: 0 4px 10px rgba(0,0,0,0.3); text-align: center; }
        .gallery-item img { width: 100%; height: 200px; object-fit: cover; cursor: pointer; transition: 0.3s; opacity: 0.9; display: block; }
        .gallery-item:hover img { opacity: 1; transform: scale(1.05); }
        .download-btn { position: absolute; bottom: 10px; right: 10px; background: rgba(255,255,255,0.9); color: #333; padding: 5px 12px; border-radius: 15px; font-size: 12px; font-weight: bold; text-decoration: none; display: none; z-index: 10; cursor: pointer; }
        .gallery-item:hover .download-btn { display: inline-block; }
        
        .close-modal { position:absolute; right:20px; top:15px; font-size: 30px; cursor: pointer; color: #aaa; }
        
        /* 🔥 Rating Stars Styles */
        .star-rating { direction: rtl; display: inline-block; font-size: 30px; }
        .star-rating input { display: none; }
        .star-rating label { color: #ddd; cursor: pointer; transition: 0.2s; }
        .star-rating input:checked ~ label, .star-rating label:hover, .star-rating label:hover ~ label { color: #f1c40f; }
    </style>
</head>
<body>
    <div class="sidebar">
        <h2>RPU UE</h2>
        <a class="menu-item" href="marketplace.php"><i class="fas fa-search"></i> หาช่างภาพ</a>
        <a class="menu-item active" href="customer_bookings.php"><i class="fas fa-calendar-check"></i> ประวัติการจอง</a>
        <a class="menu-item" href="customer_chat.php"><i class="fas fa-comments"></i> แชทกับช่างภาพ</a>
        <a class="menu-item" href="customer_settings.php"><i class="fas fa-cog"></i> ตั้งค่าบัญชี</a>
        <div class="logout-box"><a class="menu-item logout" href="logout.php"><i class="fas fa-sign-out-alt"></i> ออกจากระบบ</a></div>
    </div>

    <div class="main-content">
        <h1 style="color:#333;">📂 ประวัติการจอง & รีวิว</h1>
        <?php echo $msg; ?>
        
        <?php if(mysqli_num_rows($query) > 0) { while($row = mysqli_fetch_assoc($query)) { 
            $est_price = ($row['duration'] <= 4) ? $row['price_half'] : $row['price_full'];
            if($row['price'] > 0) { $est_price = $row['price']; } 
            $img_filename = $row['pg_img'];
            $pg_img = (!empty($img_filename)) ? "../RPU AM/uploads/".$img_filename : "https://cdn-icons-png.flaticon.com/512/3135/3135715.png";
        ?>
            <div class="job-card status-<?php echo $row['status']; ?>">
                <div class="pg-profile">
                    <img src="<?php echo $pg_img; ?>" class="pg-img" onerror="this.src='https://cdn-icons-png.flaticon.com/512/3135/3135715.png'">
                    <div style="font-size:12px; margin-top:5px; font-weight:bold;"><?php echo $row['pg_name']; ?></div>
                </div>
                
                <div class="job-details">
                    <h3>#<?php echo $row['id']; ?> : <?php echo $row['job_type']; ?></h3>
                    <div class="job-meta-grid">
                        <div class="job-meta-item"><i class="far fa-calendar-alt"></i> วันที่: <b><?php echo $row['booking_date']; ?></b></div>
                        <div class="job-meta-item"><i class="far fa-clock"></i> เวลา: <b><?php echo $row['booking_time']; ?></b></div>
                        <div class="job-meta-item"><i class="fas fa-map-marker-alt"></i> สถานที่: <b><?php echo $row['location']; ?></b></div>
                        <div class="job-meta-item"><i class="fas fa-coins"></i> ค่าบริการ: <b><?php echo number_format($est_price); ?> บาท</b></div>
                        <div class="job-meta-item">
                            <i class="fas fa-wallet"></i> สถานะ: 
                            <span class="pay-badge pay-<?php echo $row['payment_status']; ?>">
                                <?php echo ($row['payment_status']=='paid') ? '✅ จ่ายแล้ว' : '⏳ รอจ่าย/ตรวจ'; ?>
                            </span>
                        </div>
                    </div>
                </div>

                <div class="action-area">
                    <a href="customer_chat.php?pg_id=<?php echo $row['photographer_id']; ?>" class="btn btn-chat"><i class="fas fa-comment-dots"></i> แชท</a>

                    <?php if($row['status'] == 'confirmed' && $row['payment_status'] != 'paid') { ?>
                        <button onclick="openSlipModal('<?php echo $row['id']; ?>')" class="btn btn-pay"><i class="fas fa-file-invoice-dollar"></i> แจ้งโอน</button>
                    <?php } ?>

                    <?php if($row['payment_status'] == 'paid') { ?>
                        <a href="receipt.php?booking_id=<?php echo $row['id']; ?>" target="_blank" class="btn btn-receipt"><i class="fas fa-receipt"></i> ใบเสร็จ</a>
                    <?php } ?>

                    <?php if($row['status'] == 'completed') { ?>
                        <button onclick="openGallery('<?php echo $row['id']; ?>')" class="btn btn-gallery"><i class="fas fa-images"></i> ดูรูปงาน</button>
                        
                        <?php if($row['is_reviewed'] == 0) { ?>
                            <button onclick="openReviewModal('<?php echo $row['id']; ?>', '<?php echo $row['photographer_id']; ?>')" class="btn btn-review"><i class="fas fa-star"></i> ให้คะแนน</button>
                        <?php } else { ?>
                            <span style="font-size:12px; color:#f1c40f;">⭐ รีวิวแล้ว</span>
                        <?php } ?>

                        <div id="photos-<?php echo $row['id']; ?>" style="display:none;">
                            <?php 
                                $bid = $row['id'];
                                $res_photos = mysqli_query($conn, "SELECT * FROM job_photos WHERE booking_id = '$bid'");
                                if(mysqli_num_rows($res_photos) > 0) {
                                    while($photo = mysqli_fetch_assoc($res_photos)) {
                                        $safe_url = "../uploads/job_photos/" . rawurlencode($photo['file_name']);
                            ?>
                                    <div class="gallery-item">
                                        <img src="<?php echo $safe_url; ?>" onclick="window.open('<?php echo $safe_url; ?>')">
                                        <a href="<?php echo $safe_url; ?>" download target="_blank" class="download-btn"><i class="fas fa-download"></i> โหลด</a>
                                    </div>
                            <?php } } else { echo "<p style='color:white;'>ยังไม่มีรูปภาพ</p>"; } ?>
                        </div>
                    <?php } ?>
                </div>
            </div>
        <?php } } else { echo "<div style='text-align:center; color:#999; margin-top:50px;'>ไม่มีข้อมูล</div>"; } ?>
    </div>

    <div id="slipModal" class="modal">
        <div class="modal-content">
            <span class="close-modal" onclick="document.getElementById('slipModal').style.display='none'">&times;</span>
            <h3 style="color:#ff9f43;">แจ้งโอนเงิน</h3>
            <form method="post" enctype="multipart/form-data">
                <input type="hidden" id="modalBookingId" name="booking_id">
                <input type="file" name="slip_file" accept="image/*" required style="margin:10px 0;">
                <button type="submit" name="upload_slip" class="btn btn-pay" style="margin-top:10px;">ยืนยัน</button>
            </form>
        </div>
    </div>

    <div id="reviewModal" class="modal">
        <div class="modal-content">
            <span class="close-modal" onclick="document.getElementById('reviewModal').style.display='none'">&times;</span>
            <h3 style="color:#f1c40f;"><i class="fas fa-star"></i> ให้คะแนนความพึงพอใจ</h3>
            <form method="post">
                <input type="hidden" id="reviewBookingId" name="booking_id">
                <input type="hidden" id="reviewPgId" name="pg_id">
                
                <div class="star-rating">
                    <input type="radio" id="star5" name="rating" value="5" checked><label for="star5" title="5 stars">★</label>
                    <input type="radio" id="star4" name="rating" value="4"><label for="star4" title="4 stars">★</label>
                    <input type="radio" id="star3" name="rating" value="3"><label for="star3" title="3 stars">★</label>
                    <input type="radio" id="star2" name="rating" value="2"><label for="star2" title="2 stars">★</label>
                    <input type="radio" id="star1" name="rating" value="1"><label for="star1" title="1 star">★</label>
                </div>
                
                <textarea name="comment" rows="4" placeholder="เขียนรีวิวติชมผลงาน..." style="width:100%; padding:10px; margin-top:10px; border-radius:10px; border:1px solid #ddd; font-family:'Sarabun';" required></textarea>
                <button type="submit" name="submit_review" class="btn btn-review" style="width:100%; margin-top:15px; border:none; border-radius:20px; padding:10px; cursor:pointer;">ส่งรีวิว</button>
            </form>
        </div>
    </div>

    <div id="galleryModal" class="modal">
        <div class="gallery-modal-content">
            <div style="display:flex; justify-content:space-between; align-items:center; border-bottom:1px solid #444; padding-bottom:10px;">
                <h2 style="margin:0; color:white;">อัลบั้มรูปงาน</h2>
                <span class="close-modal" onclick="document.getElementById('galleryModal').style.display='none'">&times;</span>
            </div>
            <div id="galleryContainer" class="gallery-grid"></div>
        </div>
    </div>

    <script>
        function openSlipModal(id) {
            document.getElementById('modalBookingId').value = id;
            document.getElementById('slipModal').style.display = 'block';
        }
        function openReviewModal(bid, pgid) {
            document.getElementById('reviewBookingId').value = bid;
            document.getElementById('reviewPgId').value = pgid;
            document.getElementById('reviewModal').style.display = 'block';
        }
        function openGallery(id) {
            var content = document.getElementById('photos-' + id).innerHTML;
            if(!content.trim()) content = "<p style='color:white; text-align:center; width:100%; grid-column:1/-1;'>ยังไม่มีรูปภาพ</p>";
            document.getElementById('galleryContainer').innerHTML = content;
            document.getElementById('galleryModal').style.display = 'block';
        }
        window.onclick = function(e) {
            if (e.target.classList.contains('modal')) e.target.style.display = 'none';
        }
    </script>
</body>
</html>