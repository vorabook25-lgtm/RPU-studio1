<?php 
    session_start();
    include('db.php');
    if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'photographer') { header('location: login.php'); exit; }
    
    $pg_id = $_SESSION['user_id'];

    // --- 1. Badge Logic (นับจำนวนงานรออนุมัติ) ---
    $sql_count = "SELECT COUNT(*) as total FROM bookings WHERE photographer_id = '$pg_id' AND status = 'pending'";
    $res_count = mysqli_query($conn, $sql_count);
    $row_count = mysqli_fetch_assoc($res_count);
    $pending_count = $row_count['total'];
    $job_badge = ($pending_count > 0) ? "<span class='badge'>$pending_count</span>" : "";

    // --- 2. Logic: ดึงรายชื่อลูกค้า (ใช้ GROUP BY เพื่อไม่ให้ชื่อซ้ำ) ---
    $sql = "SELECT u.id as user_id, u.username as cus_name, u.profile_img as cus_img
            FROM bookings b 
            JOIN users u ON b.user_id = u.id 
            WHERE b.photographer_id = '$pg_id' 
            GROUP BY u.id 
            ORDER BY MAX(b.id) DESC";
    $chat_list = mysqli_query($conn, $sql);

    // --- 3. Logic: รับค่า cus_id (รหัสลูกค้าที่เลือกคุย) ---
    $current_cus_id = isset($_GET['cus_id']) ? $_GET['cus_id'] : 0;
    $chat_title = "เลือกรายชื่อเพื่อเริ่มแชท";
    
    if($current_cus_id > 0) {
        $user_sql = mysqli_query($conn, "SELECT username FROM users WHERE id = '$current_cus_id'");
        $user_row = mysqli_fetch_assoc($user_sql);
        if($user_row) $chat_title = "สนทนากับ: " . $user_row['username'];
    }
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <title>แชทลูกค้า | RPU AM</title>
    <link href="https://fonts.googleapis.com/css2?family=Sarabun:wght@300;400;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <style>
        body { font-family: 'Sarabun', sans-serif; margin: 0; background: #f0f2f5; display: flex; height: 100vh; overflow: hidden; }
        .sidebar { width: 260px; background: #1a1a2e; color: white; padding: 25px; display: flex; flex-direction: column; flex-shrink: 0; }
        .sidebar h2 { color: #00d2d3; text-align: center; margin: 0 0 40px 0; font-size: 24px; letter-spacing: 1px; }
        .menu-item { position: relative; padding: 12px 20px; color: #bdc3c7; text-decoration: none; display: flex; align-items: center; border-radius: 10px; margin-bottom: 12px; cursor: pointer; transition: 0.3s; }
        .menu-item:hover, .menu-item.active { background: #16213e; color: #00d2d3; }
        .menu-item i { margin-right: 15px; width: 25px; text-align: center; font-size: 18px; }
        .badge { background: #ff4757; color: white; border-radius: 50%; padding: 2px 8px; font-size: 12px; position: absolute; right: 15px; top: 50%; transform: translateY(-50%); }
        .logout-box { margin-top: auto; } .menu-item.logout { color: #ff6b6b; } 

        .chat-container { flex: 1; display: flex; background: #fff; }
        .chat-list { width: 300px; border-right: 1px solid #ddd; overflow-y: auto; background: #f8f9fa; }
        .chat-header-title { padding: 20px; font-weight: bold; border-bottom: 1px solid #eee; background: white; }
        .chat-item { padding: 15px; display: flex; align-items: center; gap: 10px; text-decoration: none; color: #333; border-bottom: 1px solid #eee; transition:0.2s; }
        .chat-item:hover { background: #eef; }
        .chat-item.active-chat { background: #e3f2fd; border-left: 4px solid #00d2d3; }
        .cus-avatar { width: 40px; height: 40px; border-radius: 50%; background: #ccc; object-fit: cover; }

        .chat-area { flex: 1; display: flex; flex-direction: column; }
        .chat-head { padding: 15px; border-bottom: 1px solid #ddd; font-weight: bold; font-size: 16px; background: white; }
        .chat-messages { flex: 1; padding: 20px; overflow-y: auto; background: #f0f2f5; display: flex; flex-direction: column; gap: 10px; }
        
        .msg { max-width: 70%; padding: 10px 15px; border-radius: 20px; font-size: 14px; word-wrap: break-word; }
        .msg-me { align-self: flex-end; background: #00d2d3; color: #1a1a2e; border-bottom-right-radius: 5px; }
        .msg-other { align-self: flex-start; background: white; border: 1px solid #ddd; border-bottom-left-radius: 5px; }
        
        .chat-input { padding: 15px; background: white; border-top: 1px solid #ddd; display: flex; gap: 10px; }
        .chat-input input { flex: 1; padding: 10px 15px; border-radius: 30px; border: 1px solid #ddd; outline: none; }
        .chat-input button { border: none; background: #1a1a2e; color: #00d2d3; width: 40px; height: 40px; border-radius: 50%; cursor: pointer; }
    </style>
</head>
<body>

    <div class="sidebar">
        <h2>RPU AM</h2>
        <a class="menu-item" href="photographer_dashboard.php"><i class="fas fa-home"></i> หน้าหลัก <?php echo $job_badge; ?></a>
        <a class="menu-item" href="photographer_portfolio.php"><i class="fas fa-images"></i> แฟ้มผลงาน</a>
        <a class="menu-item" href="photographer_upload.php"><i class="fas fa-cloud-upload-alt"></i> ส่งมอบงาน</a>
        <a class="menu-item" href="job_history.php"><i class="fas fa-history"></i> ประวัติการรับงาน</a>
        <a class="menu-item active" href="photographer_chat.php"><i class="fas fa-comments"></i> แชทลูกค้า</a>
        <a class="menu-item" href="settings.php"><i class="fas fa-cog"></i> ตั้งค่าระบบ</a>
        <div class="logout-box"><a class="menu-item logout" href="logout.php"><i class="fas fa-sign-out-alt"></i> ออกจากระบบ</a></div>
    </div>

    <div class="chat-container">
        <div class="chat-list">
            <div class="chat-header-title">💬 รายชื่อลูกค้า</div>
            <?php while($row = mysqli_fetch_assoc($chat_list)) { 
                $isActive = ($row['user_id'] == $current_cus_id) ? 'active-chat' : '';
                // Path รูปของลูกค้า
                $img = !empty($row['cus_img']) ? "../RPU UE/uploads/".$row['cus_img'] : "https://cdn-icons-png.flaticon.com/512/3135/3135715.png";
            ?>
                <a href="?cus_id=<?php echo $row['user_id']; ?>" class="chat-item <?php echo $isActive; ?>">
                    <img src="<?php echo $img; ?>" class="cus-avatar">
                    <div><?php echo $row['cus_name']; ?></div>
                </a>
            <?php } ?>
        </div>

        <div class="chat-area">
            <div class="chat-head"><?php echo $chat_title; ?></div>
            
            <div class="chat-messages" id="chatBox">
                <?php if($current_cus_id == 0) { echo "<p style='text-align:center; color:#aaa; margin-top:50px;'>เลือกรายชื่อเพื่อเริ่มสนทนา</p>"; } ?>
            </div>

            <?php if($current_cus_id > 0) { ?>
            <div class="chat-input">
                <input type="text" id="msgIn" placeholder="พิมพ์ข้อความ..." autocomplete="off">
                <button onclick="sendMessage()"><i class="fas fa-paper-plane"></i></button>
            </div>
            <?php } ?>
        </div>
    </div>

    <script>
        // ✅ แก้ไข: ใช้ตัวแปร $current_cus_id ให้ถูกต้อง (เดิมใช้ $current_pg_id ซึ่งผิด)
        const partnerId = <?php echo $current_cus_id; ?>; 
        const chatBox = document.getElementById('chatBox');

        function fetchMessages() {
            if(partnerId == 0) return;
            
            // ส่ง parameter ชื่อ 'partner_id' ตามมาตรฐานใหม่
            $.post('api_chat.php', { action: 'fetch', partner_id: partnerId }, function(data) {
                // เช็ค Error จาก API
                let msgs = (typeof data === 'string') ? JSON.parse(data) : data;
                if(msgs.status === 'error') { console.error(msgs.message); return; }

                let html = '';
                msgs.forEach(m => { 
                    html += `<div class="msg ${m.class}">${m.msg}</div>`; 
                });

                if(chatBox.innerHTML !== html) { 
                    chatBox.innerHTML = html; 
                    chatBox.scrollTop = chatBox.scrollHeight; 
                }
            });
        }

        function sendMessage() {
            let msg = $('#msgIn').val().trim();
            if(msg && partnerId > 0) {
                // ส่ง parameter ชื่อ 'partner_id'
                $.post('api_chat.php', { action: 'send', partner_id: partnerId, message: msg }, function(res) {
                    $('#msgIn').val(''); 
                    fetchMessages(); 
                });
            }
        }

        $('#msgIn').keypress(function(e) { if(e.which == 13) sendMessage(); });

        if(partnerId > 0) { 
            fetchMessages(); 
            setInterval(fetchMessages, 2000); 
        }
    </script>
</body>
</html>