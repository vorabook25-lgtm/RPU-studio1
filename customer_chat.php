<?php 
    session_start();
    include('db.php');
    if (!isset($_SESSION['user_id'])) { header('location: login.php'); exit; }
    $user_id = $_SESSION['user_id'];

    // ดึงรายชื่อช่างภาพที่เคยติดต่อ
    $sql = "SELECT u.id as pg_id, u.username as pg_name, u.profile_img as pg_img 
            FROM bookings b 
            JOIN users u ON b.photographer_id = u.id 
            WHERE b.user_id = '$user_id' 
            GROUP BY u.id
            ORDER BY MAX(b.id) DESC";
    $chat_list = mysqli_query($conn, $sql);

    // รับรหัสคู่สนทนา (ช่างภาพ)
    $partner_id = isset($_GET['pg_id']) ? $_GET['pg_id'] : 0;
    $chat_title = "เลือกช่างภาพเพื่อเริ่มแชท";
    
    if($partner_id > 0) {
        $check = mysqli_fetch_assoc(mysqli_query($conn, "SELECT username FROM users WHERE id='$partner_id'"));
        if($check) { $chat_title = "สนทนากับ: " . $check['username']; }
    }
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <title>แชทลูกค้า | RPU UE</title>
    <link href="https://fonts.googleapis.com/css2?family=Sarabun:wght@300;400;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <style>
        /* สไตล์เดิม */
        body { font-family: 'Sarabun'; margin: 0; display: flex; height: 100vh; background: #f0f2f5; overflow: hidden; }
        .sidebar { width: 260px; background: #1a1a2e; color: white; padding: 25px; display: flex; flex-direction: column; flex-shrink: 0; }
        .sidebar h2 { color: #ff9f43; text-align: center; margin: 0 0 40px 0; font-size: 24px; letter-spacing: 1px; }
        .menu-item { padding: 12px 20px; color: #bdc3c7; text-decoration: none; display: flex; align-items: center; border-radius: 10px; margin-bottom: 12px; cursor: pointer; transition: 0.3s; }
        .menu-item:hover, .menu-item.active { background: #16213e; color: #ff9f43; transform: translateX(5px); }
        .menu-item i { margin-right: 15px; width: 25px; text-align: center; }
        .logout-box { margin-top: auto; } .menu-item.logout { color: #ff6b6b; } 

        .chat-container { flex: 1; display: flex; background: #e9ecef; }
        .chat-list { width: 300px; background: white; border-right: 1px solid #ddd; overflow-y: auto; }
        .chat-item { padding: 15px; border-bottom: 1px solid #f0f0f0; cursor: pointer; display: flex; align-items: center; gap: 10px; color: #333; text-decoration: none; }
        .chat-item.active-chat { background: #fff3e0; border-left: 4px solid #ff9f43; }
        .pg-avatar { width: 45px; height: 45px; border-radius: 50%; object-fit: cover; border: 1px solid #ddd; }
        
        .chat-area { flex: 1; display: flex; flex-direction: column; }
        .chat-header { padding: 15px; background: white; border-bottom: 1px solid #ddd; font-weight: bold; font-size: 1.1rem; }
        .chat-messages { flex: 1; padding: 20px; overflow-y: auto; display: flex; flex-direction: column; gap: 10px; }
        
        .msg { max-width: 70%; padding: 10px 15px; border-radius: 15px; font-size: 14px; word-wrap: break-word; }
        .msg-me { align-self: flex-end; background: #ff9f43; color: white; border-bottom-right-radius: 2px; }
        .msg-other { align-self: flex-start; background: white; border: 1px solid #ddd; border-bottom-left-radius: 2px; }
        
        .chat-input { padding: 20px; background: white; display: flex; gap: 10px; border-top: 1px solid #ddd; }
        input { flex: 1; padding: 12px; border-radius: 20px; border: 1px solid #ddd; outline: none; }
        button { border: none; background: none; color: #ff9f43; cursor: pointer; font-size: 18px; }
    </style>
</head>
<body>
    <div class="sidebar">
        <h2>RPU UE</h2>
        <a class="menu-item" href="marketplace.php"><i class="fas fa-search"></i> หาช่างภาพ</a>
        <a class="menu-item" href="customer_bookings.php"><i class="fas fa-calendar-check"></i> ประวัติการจอง</a>
        <a class="menu-item active" href="customer_chat.php"><i class="fas fa-comments"></i> แชทกับช่างภาพ</a>
        <a class="menu-item" href="customer_settings.php"><i class="fas fa-cog"></i> ตั้งค่าบัญชี</a>
        <div class="logout-box"><a class="menu-item logout" href="logout.php"><i class="fas fa-sign-out-alt"></i> ออกจากระบบ</a></div>
    </div>

    <div class="chat-container">
        <div class="chat-list">
            <div style="padding: 20px; font-weight: bold; color:#555;">💬 รายชื่อช่างภาพ</div>
            <?php while($row = mysqli_fetch_assoc($chat_list)) { 
                $isActive = ($row['pg_id'] == $partner_id) ? 'active-chat' : '';
                $img = !empty($row['pg_img']) ? "../RPU AM/uploads/".$row['pg_img'] : "https://cdn-icons-png.flaticon.com/512/3135/3135715.png";
            ?>
                <a href="?pg_id=<?php echo $row['pg_id']; ?>" class="chat-item <?php echo $isActive; ?>">
                    <img src="<?php echo $img; ?>" class="pg-avatar">
                    <div><?php echo $row['pg_name']; ?></div>
                </a>
            <?php } ?>
        </div>

        <div class="chat-area">
            <div class="chat-header"><?php echo $chat_title; ?></div>
            <div class="chat-messages" id="chatBox"></div>
            <?php if($partner_id > 0) { ?>
            <div class="chat-input">
                <input type="text" id="msgIn" placeholder="พิมพ์ข้อความ..." autocomplete="off">
                <button onclick="sendMessage()"><i class="fas fa-paper-plane"></i></button>
            </div>
            <?php } ?>
        </div>
    </div>

    <script>
        const partnerId = <?php echo $partner_id; ?>;
        const chatBox = document.getElementById('chatBox');

        function fetchMessages() {
            if(partnerId == 0) return;
            // ส่งตัวแปรชื่อ partner_id เสมอ
            $.post('api_chat.php', { action: 'fetch', partner_id: partnerId }, function(data) {
                let msgs = (typeof data === 'string') ? JSON.parse(data) : data;
                let html = '';
                msgs.forEach(m => { html += `<div class="msg ${m.class}">${m.msg}</div>`; });
                if(chatBox.innerHTML !== html) { 
                    chatBox.innerHTML = html; 
                    chatBox.scrollTop = chatBox.scrollHeight; 
                }
            });
        }

        function sendMessage() {
            let msg = $('#msgIn').val().trim();
            if(msg && partnerId > 0) {
                // ส่งตัวแปรชื่อ partner_id เสมอ
                $.post('api_chat.php', { action: 'send', partner_id: partnerId, message: msg }, function() {
                    $('#msgIn').val(''); 
                    fetchMessages(); 
                });
            }
        }

        $('#msgIn').keypress(function(e) { if(e.which == 13) sendMessage(); });
        if(partnerId > 0) { fetchMessages(); setInterval(fetchMessages, 2000); }
    </script>
</body>
</html>