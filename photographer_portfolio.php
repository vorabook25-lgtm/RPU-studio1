<?php 
    session_start();
    include('db.php');
    if (!isset($_SESSION['user_id'])) { header('location: login.php'); exit; }
    $user_id = $_SESSION['user_id'];
    $msg = "";

    // --- Notification Logic ---
    $noti_job_count = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as count FROM bookings WHERE photographer_id = '$user_id' AND status = 'pending'"))['count'];
    $job_badge = ($noti_job_count > 0) ? "<span class='badge'>$noti_job_count</span>" : "";
    $noti_chat_count = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as count FROM bookings WHERE photographer_id = '$user_id' AND status IN ('confirmed', 'paid')"))['count'];
    $chat_badge = ($noti_chat_count > 0) ? "<span class='badge'>$noti_chat_count</span>" : "";

    $upload_dir = 'uploads/portfolio/';
    if(!file_exists($upload_dir)) { mkdir($upload_dir, 0777, true); }

    // --- Logic 1: อัปโหลด Top 3 (ทีละรูปตาม Slot) ---
    if (isset($_POST['upload_top'])) {
        $slot = $_POST['slot_number']; // 1, 2, หรือ 3
        if (!empty($_FILES['top_file']['name'])) {
            $ext = pathinfo($_FILES['top_file']['name'], PATHINFO_EXTENSION);
            $new_name = "top" . $slot . "_" . $user_id . "_" . time() . "." . $ext;
            
            if(move_uploaded_file($_FILES['top_file']['tmp_name'], $upload_dir . $new_name)) {
                // เช็คก่อนว่ามีรูปใน Slot นี้หรือยัง
                $check = mysqli_query($conn, "SELECT id, image_file FROM portfolio WHERE user_id='$user_id' AND img_order='$slot'");
                if($row = mysqli_fetch_assoc($check)) {
                    // ถ้ามีแล้ว ให้ลบรูปเก่าทิ้ง แล้วอัปเดตชื่อรูปใหม่
                    @unlink($upload_dir . $row['image_file']);
                    mysqli_query($conn, "UPDATE portfolio SET image_file='$new_name' WHERE id='".$row['id']."'");
                } else {
                    // ถ้ายังไม่มี ให้ Insert ใหม่
                    mysqli_query($conn, "INSERT INTO portfolio (user_id, image_file, img_order) VALUES ('$user_id', '$new_name', '$slot')");
                }
                $msg = "<div class='alert success'>✅ อัปเดต Top $slot เรียบร้อยครับ</div>";
            }
        }
    }

    // --- Logic 2: อัปโหลดรูปทั่วไป (ไม่จำกัด) ---
    if (isset($_POST['upload_general'])) {
        $count = 0;
        if (!empty($_FILES['general_files']['name'][0])) {
            foreach ($_FILES['general_files']['name'] as $key => $val) {
                $ext = pathinfo($_FILES['general_files']['name'][$key], PATHINFO_EXTENSION);
                $new_name = "port_" . $user_id . "_" . uniqid() . "." . $ext;
                if(move_uploaded_file($_FILES['general_files']['tmp_name'][$key], $upload_dir . $new_name)) {
                    mysqli_query($conn, "INSERT INTO portfolio (user_id, image_file, img_order) VALUES ('$user_id', '$new_name', 0)");
                    $count++;
                }
            }
        }
        if($count > 0) $msg = "<div class='alert success'>✅ เพิ่มรูปผลงานทั่วไปสำเร็จ $count รูป</div>";
    }

    // --- Logic 3: ลบรูป ---
    if (isset($_GET['delete'])) {
        $del_id = mysqli_real_escape_string($conn, $_GET['delete']);
        $check = mysqli_query($conn, "SELECT * FROM portfolio WHERE id='$del_id' AND user_id='$user_id'");
        if ($row = mysqli_fetch_assoc($check)) {
            @unlink($upload_dir . $row['image_file']);
            mysqli_query($conn, "DELETE FROM portfolio WHERE id='$del_id'");
            header('location: photographer_portfolio.php'); exit;
        }
    }

    // ดึงข้อมูลรูปภาพแยกประเภท
    // Top 3
    $top_works = array(1 => null, 2 => null, 3 => null);
    $q_top = mysqli_query($conn, "SELECT * FROM portfolio WHERE user_id='$user_id' AND img_order > 0 ORDER BY img_order ASC");
    while($row = mysqli_fetch_assoc($q_top)) { $top_works[$row['img_order']] = $row; }

    // รูปทั่วไป
    $q_general = mysqli_query($conn, "SELECT * FROM portfolio WHERE user_id='$user_id' AND img_order = 0 ORDER BY id DESC");
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <title>แฟ้มผลงาน (Portfolio) | RPU AM</title>
    <link href="https://fonts.googleapis.com/css2?family=Sarabun:wght@300;400;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        body { font-family: 'Sarabun', sans-serif; margin: 0; background: #f0f2f5; display: flex; height: 100vh; overflow: hidden; }
        .sidebar { width: 260px; background: #1a1a2e; color: white; padding: 25px; display: flex; flex-direction: column; flex-shrink: 0; }
        .sidebar h2 { color: #00d2d3; text-align: center; margin: 0 0 40px 0; font-size: 24px; letter-spacing: 1px; text-transform: uppercase; }
        .menu-item { position: relative; padding: 12px 20px; color: #bdc3c7; text-decoration: none; display: flex; align-items: center; border-radius: 10px; margin-bottom: 12px; cursor: pointer; transition: all 0.3s ease; font-size: 16px; }
        .menu-item:hover, .menu-item.active { background: #16213e; color: #00d2d3; transform: translateX(5px); }
        .menu-item i { margin-right: 15px; width: 25px; text-align: center; font-size: 18px; }
        .badge { background: #ff4757; color: white; border-radius: 50%; padding: 2px 8px; font-size: 12px; font-weight: bold; position: absolute; right: 15px; top: 50%; transform: translateY(-50%); }
        .logout-box { margin-top: auto; } .menu-item.logout { color: #ff6b6b; } .menu-item.logout:hover { background: rgba(255, 107, 107, 0.1); color: #ff4757; }

        .main-content { flex: 1; padding: 40px; overflow-y: auto; }
        
        .alert { padding: 15px; border-radius: 10px; text-align: center; margin-bottom: 20px; background: #d4edda; color: #155724; }

        /* --- Style Top 3 --- */
        .section-title { font-size: 1.2rem; font-weight: bold; margin-bottom: 15px; color: #1a1a2e; border-left: 5px solid #00d2d3; padding-left: 10px; }
        
        .top3-grid { display: grid; grid-template-columns: repeat(3, 1fr); gap: 20px; margin-bottom: 40px; }
        .top-card { 
            background: white; height: 250px; border-radius: 15px; border: 2px dashed #00d2d3; 
            position: relative; overflow: hidden; transition: 0.3s; 
            display: flex; align-items: center; justify-content: center; flex-direction: column;
        }
        .top-card:hover { border-color: #1a1a2e; transform: translateY(-5px); box-shadow: 0 10px 20px rgba(0,0,0,0.1); }
        .top-card img { width: 100%; height: 100%; object-fit: cover; position: absolute; top: 0; left: 0; }
        .top-label { 
            position: absolute; top: 10px; left: 10px; background: #00d2d3; color: #1a1a2e; 
            padding: 5px 15px; border-radius: 20px; font-weight: bold; z-index: 2; font-size: 14px;
        }
        .top-upload-btn { 
            position: absolute; bottom: 10px; right: 10px; background: rgba(0,0,0,0.7); color: white; 
            padding: 8px 15px; border-radius: 20px; cursor: pointer; z-index: 2; font-size: 12px;
        }
        .top-upload-btn:hover { background: #1a1a2e; }

        /* --- Style General --- */
        .upload-general-zone { 
            background: white; padding: 20px; border-radius: 15px; box-shadow: 0 5px 15px rgba(0,0,0,0.05); 
            text-align: center; border: 2px dashed #ccc; cursor: pointer; margin-bottom: 20px; 
        }
        .upload-general-zone:hover { background: #f9f9f9; border-color: #00d2d3; }

        .gallery-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(180px, 1fr)); gap: 15px; }
        .gallery-item { position: relative; border-radius: 10px; overflow: hidden; height: 180px; box-shadow: 0 2px 8px rgba(0,0,0,0.1); }
        .gallery-item img { width: 100%; height: 100%; object-fit: cover; transition: 0.3s; }
        .gallery-item:hover img { transform: scale(1.05); }
        
        .btn-delete { 
            position: absolute; top: 5px; right: 5px; background: rgba(255,0,0,0.8); color: white; 
            width: 25px; height: 25px; border-radius: 50%; display: flex; align-items: center; justify-content: center; 
            text-decoration: none; opacity: 0; transition: 0.2s; 
        }
        .gallery-item:hover .btn-delete, .top-card:hover .btn-delete { opacity: 1; }
    </style>
</head>
<body>

    <div class="sidebar">
        <h2>RPU AM</h2>
        <a class="menu-item" href="photographer_dashboard.php"><i class="fas fa-home"></i> หน้าหลัก <?php echo $job_badge; ?></a>
        <a class="menu-item active" href="photographer_portfolio.php"><i class="fas fa-images"></i> แฟ้มผลงาน</a>
        <a class="menu-item" href="photographer_upload.php"><i class="fas fa-cloud-upload-alt"></i> ส่งมอบงาน</a>
        <a class="menu-item" href="job_history.php"><i class="fas fa-history"></i> ประวัติการรับงาน</a>
        <a class="menu-item" href="photographer_chat.php"><i class="fas fa-comments"></i> แชทลูกค้า <?php echo $chat_badge; ?></a>
        <a class="menu-item" href="settings.php"><i class="fas fa-cog"></i> ตั้งค่าระบบ</a>
        <div class="logout-box"><a class="menu-item logout" href="logout.php"><i class="fas fa-sign-out-alt"></i> ออกจากระบบ</a></div>
    </div>

    <div class="main-content">
        <h1 style="color:#333; margin-top:0;">📸 จัดการแฟ้มผลงาน</h1>
        <?php echo $msg; ?>

        <div class="section-title">🏆 Top 3 ผลงานที่ภูมิใจที่สุด (Highlight)</div>
        <div class="top3-grid">
            <?php for($i=1; $i<=3; $i++) { ?>
                <div class="top-card">
                    <div class="top-label">อันดับ #<?php echo $i; ?></div>
                    
                    <?php if(isset($top_works[$i])) { ?>
                        <img src="uploads/portfolio/<?php echo $top_works[$i]['image_file']; ?>">
                        <a href="?delete=<?php echo $top_works[$i]['id']; ?>" class="btn-delete" onclick="return confirm('ลบ Top <?php echo $i; ?>?')"><i class="fas fa-trash"></i></a>
                        <label for="top_file_<?php echo $i; ?>" class="top-upload-btn"><i class="fas fa-pen"></i> เปลี่ยนรูป</label>
                    <?php } else { ?>
                        <i class="fas fa-plus-circle fa-2x" style="color:#ddd; margin-bottom:10px;"></i>
                        <span style="color:#aaa;">ว่าง</span>
                        <label for="top_file_<?php echo $i; ?>" class="top-upload-btn" style="background:#00d2d3; color:#1a1a2e;"><i class="fas fa-upload"></i> เพิ่มรูป</label>
                    <?php } ?>

                    <form method="post" enctype="multipart/form-data" style="display:none;">
                        <input type="hidden" name="slot_number" value="<?php echo $i; ?>">
                        <input type="file" name="top_file" id="top_file_<?php echo $i; ?>" accept="image/*" onchange="this.form.submit()">
                        <input type="hidden" name="upload_top" value="1">
                    </form>
                </div>
            <?php } ?>
        </div>

        <div class="section-title">🖼️ ผลงานอื่นๆ (ไม่จำกัดจำนวน)</div>
        <form method="post" enctype="multipart/form-data">
            <div class="upload-general-zone" onclick="document.getElementById('genFiles').click()">
                <i class="fas fa-images fa-2x" style="color:#00d2d3;"></i>
                <span style="color:#666; font-weight:bold; margin-left:10px;">คลิกเพื่อเพิ่มรูปผลงานทั่วไป (เลือกได้หลายรูป)</span>
                <input type="file" id="genFiles" name="general_files[]" multiple hidden onchange="this.form.submit()">
                <input type="hidden" name="upload_general" value="1">
            </div>
        </form>

        <div class="gallery-grid">
            <?php if (mysqli_num_rows($q_general) > 0) { while($row = mysqli_fetch_assoc($q_general)) { ?>
                <div class="gallery-item">
                    <img src="uploads/portfolio/<?php echo $row['image_file']; ?>">
                    <a href="?delete=<?php echo $row['id']; ?>" class="btn-delete" onclick="return confirm('ลบรูปนี้?')"><i class="fas fa-trash"></i></a>
                </div>
            <?php } } else { echo "<div style='grid-column:1/-1; text-align:center; color:#999;'>ยังไม่มีรูปผลงานทั่วไป</div>"; } ?>
        </div>
    </div>

</body>
</html>