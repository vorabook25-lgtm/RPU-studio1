<?php 
    session_start();
    include('db.php');
    if (!isset($_SESSION['user_id'])) { header('location: login.php'); exit; }
    
    // ถ้าเป็นช่างภาพ ให้ดีดไปหน้า Dashboard ตัวเอง (ไม่ให้มาจองเอง)
    if ($_SESSION['role'] == 'photographer') { header('location: ../RPU AM/photographer_dashboard.php'); exit; }

    $selected_type = isset($_GET['type']) ? $_GET['type'] : '';
    $search = isset($_GET['search']) ? mysqli_real_escape_string($conn, $_GET['search']) : '';

    // Logic Query: กรองตามประเภทที่เลือก
    $where_clause = "role = 'photographer'"; // ดึงเฉพาะคนที่เป็นช่างภาพ
    $type_title = "เลือกประเภทอุปกรณ์ที่ต้องการ";

    if ($selected_type == 'digital') { 
        $where_clause .= " AND on_digital = 1"; 
        $type_title = "📷 ช่างภาพกล้องดิจิตอล"; 
    } 
    elseif ($selected_type == 'mobile') { 
        $where_clause .= " AND on_mobile = 1"; 
        $type_title = "📱 ช่างภาพกล้องมือถือ"; 
    } 
    elseif ($selected_type == 'client') { 
        $where_clause .= " AND on_client = 1"; 
        $type_title = "🎥 ช่างภาพ (ใช้อุปกรณ์ลูกค้า)"; 
    } 

    // Logic Search: ค้นหาชื่อ
    if($search != "") { 
        $where_clause .= " AND username LIKE '%$search%'"; 
    }

    $sql = "SELECT * FROM users WHERE $where_clause ORDER BY id DESC";
    $query = mysqli_query($conn, $sql);
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <title>เลือกช่างภาพ | RPU UE</title>
    <link href="https://fonts.googleapis.com/css2?family=Sarabun:wght@300;400;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        body { font-family: 'Sarabun'; margin: 0; background: #f0f2f5; display: flex; height: 100vh; overflow: hidden; }
        
        /* Sidebar */
        .sidebar { width: 260px; background: #1a1a2e; color: white; padding: 25px; display: flex; flex-direction: column; flex-shrink: 0; }
        .sidebar h2 { color: #ff9f43; text-align: center; margin: 0 0 40px 0; font-size: 24px; letter-spacing: 1px; }
        .menu-item { padding: 12px 20px; color: #bdc3c7; text-decoration: none; display: flex; align-items: center; border-radius: 10px; margin-bottom: 12px; cursor: pointer; transition: 0.3s; }
        .menu-item:hover, .menu-item.active { background: #16213e; color: #ff9f43; transform: translateX(5px); }
        .menu-item i { margin-right: 15px; width: 25px; text-align: center; }
        .logout-box { margin-top: auto; } .menu-item.logout { color: #ff6b6b; } .menu-item.logout:hover { background: rgba(255, 107, 107, 0.1); color: #ff4757; }
        
        .main-content { flex: 1; padding: 40px; overflow-y: auto; }

        /* Search Box */
        .search-form { display: flex; gap: 10px; margin-bottom: 20px; }
        .search-form input { flex: 1; padding: 12px; border-radius: 8px; border: 1px solid #ddd; font-family: 'Sarabun'; }
        .search-form button { padding: 12px 25px; background: #1a1a2e; color: white; border: none; border-radius: 8px; cursor: pointer; }

        /* Type Grid */
        .type-grid { display: grid; grid-template-columns: repeat(3, 1fr); gap: 20px; margin-bottom: 30px; }
        .type-card { background: white; padding: 30px; border-radius: 15px; text-align: center; cursor: pointer; transition: 0.3s; border: 2px solid transparent; box-shadow: 0 5px 15px rgba(0,0,0,0.05); text-decoration: none; color: #333; }
        .type-card:hover { transform: translateY(-5px); box-shadow: 0 10px 25px rgba(0,0,0,0.1); }
        .type-card.active { border-color: #ff9f43; background: #fff8e1; }
        .type-icon { font-size: 40px; color: #1a1a2e; margin-bottom: 15px; }
        .type-card.active .type-icon { color: #ff9f43; }

        /* Photographer Grid */
        .photo-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(280px, 1fr)); gap: 25px; }
        .photo-card { background: white; border-radius: 15px; overflow: hidden; box-shadow: 0 5px 15px rgba(0,0,0,0.05); transition: 0.3s; border: 1px solid #eee; position: relative; }
        .photo-card:hover { transform: translateY(-5px); border-color: #ff9f43; }
        
        .profile-cover { height: 100px; background: linear-gradient(45deg, #1a1a2e, #ff9f43); position: relative; }
        
        .profile-img { 
            width: 90px; height: 90px; 
            border-radius: 50%; 
            border: 4px solid white; 
            object-fit: cover; 
            position: absolute; 
            bottom: -45px; left: 50%; 
            transform: translateX(-50%); 
            background: white;
            box-shadow: 0 3px 10px rgba(0,0,0,0.1);
        }
        
        .card-body { padding: 50px 20px 20px; text-align: center; }
        .btn-view { display: block; width: 100%; padding: 10px; background: #1a1a2e; color: white; text-decoration: none; border-radius: 30px; font-weight: bold; margin-top: 15px; transition:0.3s; }
        .btn-view:hover { background: #ff9f43; }

        .work-time { font-size: 13px; color: #666; margin-top: 5px; background: #f8f9fa; display: inline-block; padding: 4px 10px; border-radius: 15px; }
    </style>
</head>
<body>
    <div class="sidebar">
        <h2>RPU UE</h2>
        <a class="menu-item active" href="marketplace.php"><i class="fas fa-search"></i> หาช่างภาพ</a>
        <a class="menu-item" href="customer_bookings.php"><i class="fas fa-calendar-check"></i> ประวัติการจอง</a>
        <a class="menu-item" href="customer_chat.php"><i class="fas fa-comments"></i> แชทกับช่างภาพ</a>
        <a class="menu-item" href="customer_settings.php"><i class="fas fa-cog"></i> ตั้งค่าบัญชี</a>
        <div class="logout-box"><a class="menu-item logout" href="logout.php"><i class="fas fa-sign-out-alt"></i> ออกจากระบบ</a></div>
    </div>

    <div class="main-content">
        <form class="search-form">
            <input type="hidden" name="type" value="<?php echo $selected_type; ?>">
            <input type="text" name="search" placeholder="ค้นหาชื่อช่างภาพ..." value="<?php echo $search; ?>">
            <button type="submit">ค้นหา</button>
        </form>

        <h2 style="margin-top:0;">🛠️ ขั้นตอนที่ 1: เลือกประเภทอุปกรณ์</h2>
        <div class="type-grid">
            <a href="?type=digital" class="type-card <?php if($selected_type=='digital') echo 'active'; ?>">
                <i class="fas fa-camera type-icon"></i>
                <h3>กล้องดิจิตอล</h3>
                <p>Digital Camera</p>
            </a>
            <a href="?type=mobile" class="type-card <?php if($selected_type=='mobile') echo 'active'; ?>">
                <i class="fas fa-mobile-alt type-icon"></i>
                <h3>กล้องมือถือ</h3>
                <p>Mobile Camera</p>
            </a>
            <a href="?type=client" class="type-card <?php if($selected_type=='client') echo 'active'; ?>">
                <i class="fas fa-video type-icon"></i>
                <h3>อุปกรณ์ผู้ว่าจ้าง</h3>
                <p>Client's Gear</p>
            </a>
        </div>

        <?php if($selected_type != "") { ?>
            <h2 style="margin-top:0;">📸 ขั้นตอนที่ 2: เลือกช่างภาพ (<?php echo $type_title; ?>)</h2>
            <div class="photo-grid">
                <?php if(mysqli_num_rows($query) > 0) { 
                    while($row = mysqli_fetch_assoc($query)) { 
                        
                        // Path รูปโปรไฟล์ (RPU AM)
                        $folder_am = "../RPU AM/uploads/"; 
                        $img_filename = $row['profile_img'];
                        $check_file = $folder_am . $img_filename;

                        // ตรวจสอบไฟล์จริง
                        if (!empty($img_filename) && file_exists($check_file)) {
                            $show_img = $check_file;
                        } else {
                            $show_img = "https://cdn-icons-png.flaticon.com/512/3135/3135715.png";
                        }

                        // จัด format เวลา
                        $start_time = date('H:i', strtotime($row['work_start']));
                        $end_time = date('H:i', strtotime($row['work_end']));
                ?>
                    <div class="photo-card">
                        <div class="profile-cover">
                            <img src="<?php echo $show_img; ?>" class="profile-img" onerror="this.src='https://cdn-icons-png.flaticon.com/512/3135/3135715.png'">
                        </div>
                        <div class="card-body">
                            <h3 style="margin:0 0 5px;"><?php echo $row['username']; ?></h3>
                            
                            <div class="work-time"><i class="far fa-clock"></i> รับงาน: <?php echo $start_time; ?> - <?php echo $end_time; ?></div>
                            
                            <p style="color:#ff9f43; font-weight:bold; margin: 10px 0;">
                                ฿ <?php echo number_format($row['price_half']); ?> - <?php echo number_format($row['price_full']); ?>
                            </p>
                            
                            <a href="profile_view.php?id=<?php echo $row['id']; ?>&type=<?php echo $selected_type; ?>" class="btn-view">ดูผลงาน & จองคิว</a>
                        </div>
                    </div>
                <?php } } else { echo "<p style='color:#999;'>ไม่พบช่างภาพที่รับงานประเภทนี้ครับ</p>"; } ?>
            </div>
        <?php } ?>
    </div>
</body>
</html>