<?php 
    session_start();
    include('db.php');

    // ตรวจสอบการ Login
    if (!isset($_SESSION['user_id'])) { header('location: login.php'); exit; }

    $user_id = $_SESSION['user_id'];
    $role = $_SESSION['role'];
    $msg = "";

    // --- 1. ระบบแจ้งเตือน (Notification Badges) ---
    $noti_job_query = mysqli_query($conn, "SELECT COUNT(*) as count FROM bookings WHERE photographer_id = '$user_id' AND status = 'pending'");
    $noti_job_count = mysqli_fetch_assoc($noti_job_query)['count'];
    $job_badge = ($noti_job_count > 0) ? "<span class='badge'>$noti_job_count</span>" : "";
    
    $noti_chat_query = mysqli_query($conn, "SELECT COUNT(*) as count FROM bookings WHERE photographer_id = '$user_id' AND status IN ('confirmed', 'paid')");
    $noti_chat_count = mysqli_fetch_assoc($noti_chat_query)['count'];
    $chat_badge = ($noti_chat_count > 0) ? "<span class='badge'>$noti_chat_count</span>" : "";

    // --- 2. ส่วนบันทึกข้อมูล (Save Logic) ---
    if (isset($_POST['save_settings'])) {
        // 🔥 ตัดการรับค่า username ออก (ปลอดภัย 100%)
        $phone = mysqli_real_escape_string($conn, $_POST['phone']);
        
        $bank_name = isset($_POST['bank_name']) ? mysqli_real_escape_string($conn, $_POST['bank_name']) : '';
        $bank_number = isset($_POST['bank_number']) ? mysqli_real_escape_string($conn, $_POST['bank_number']) : '';
        $price_half = isset($_POST['price_half']) ? $_POST['price_half'] : 0;
        $price_full = isset($_POST['price_full']) ? $_POST['price_full'] : 0;
        
        $s_h = $_POST['start_hour']; $s_m = $_POST['start_min'];
        $e_h = $_POST['end_hour']; $e_m = $_POST['end_min'];
        $work_start = "$s_h:$s_m:00";
        $work_end = "$e_h:$e_m:00";
        
        $on_digital = isset($_POST['on_digital']) ? 1 : 0;
        $on_mobile = isset($_POST['on_mobile']) ? 1 : 0;
        $on_client = isset($_POST['on_client']) ? 1 : 0;

        $profile_sql = "";
        if (!empty($_FILES['profile_img']['name'])) {
            $ext = pathinfo($_FILES['profile_img']['name'], PATHINFO_EXTENSION);
            $new_name = "profile_" . $user_id . "_" . time() . "." . $ext;
            if(!file_exists('uploads')) { mkdir('uploads', 0777, true); }
            if(move_uploaded_file($_FILES['profile_img']['tmp_name'], "uploads/$new_name")) {
                $profile_sql = ", profile_img='$new_name'";
            }
        }

        // อัปเดตข้อมูล (ยกเว้น username)
        $update_sql = "UPDATE users SET 
                phone='$phone', 
                bank_name='$bank_name', bank_number='$bank_number',
                price_half='$price_half', price_full='$price_full',
                work_start='$work_start', work_end='$work_end',
                on_digital='$on_digital', on_mobile='$on_mobile', on_client='$on_client'
                $profile_sql
                WHERE id='$user_id'";

        if (mysqli_query($conn, $update_sql)) {
            $msg = "<div class='alert success'>✅ บันทึกข้อมูลเรียบร้อยครับนายท่าน</div>";
        } else {
            $msg = "<div class='alert error'>❌ เกิดข้อผิดพลาด: ".mysqli_error($conn)."</div>";
        }
    }

    $user = mysqli_fetch_assoc(mysqli_query($conn, "SELECT * FROM users WHERE id = '$user_id'"));
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <title>ตั้งค่าระบบ | RPU AM</title>
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
        .box { background: white; padding: 30px; border-radius: 15px; box-shadow: 0 5px 15px rgba(0,0,0,0.05); margin-bottom: 25px; max-width: 800px; margin-left: auto; margin-right: auto; }
        
        h3 { margin-top: 0; color: #1a1a2e; border-bottom: 2px solid #00d2d3; display: inline-block; padding-bottom: 5px; margin-bottom: 20px; }
        label { display: block; margin-top: 15px; font-weight: 600; font-size: 14px; color: #555; }
        input[type="text"], input[type="number"], select { width: 100%; padding: 12px; margin-top: 5px; border: 1px solid #ddd; border-radius: 8px; box-sizing: border-box; font-family: 'Sarabun'; font-size: 16px; }
        
        .time-group { display: flex; gap: 5px; align-items: center; } .time-group select { flex: 1; text-align: center; }
        .img-preview { width: 120px; height: 120px; border-radius: 50%; object-fit: cover; border: 3px solid #00d2d3; background: #eee; margin-bottom: 10px; }
        .switch-row { display: flex; justify-content: space-between; align-items: center; background: #f8f9fa; padding: 12px 15px; border-radius: 10px; margin-top: 10px; border: 1px solid #eee; }
        .switch { position: relative; display: inline-block; width: 50px; height: 26px; }
        .switch input { opacity: 0; width: 0; height: 0; }
        .slider { position: absolute; cursor: pointer; top: 0; left: 0; right: 0; bottom: 0; background-color: #ccc; transition: .4s; border-radius: 34px; }
        .slider:before { position: absolute; content: ""; height: 20px; width: 20px; left: 3px; bottom: 3px; background-color: white; transition: .4s; border-radius: 50%; }
        input:checked + .slider { background-color: #28a745; } input:checked + .slider:before { transform: translateX(24px); }

        .btn-save { width: 100%; padding: 15px; background: #1a1a2e; color: #00d2d3; border: 1px solid #00d2d3; border-radius: 30px; font-weight: bold; cursor: pointer; margin-top: 20px; transition: 0.3s; font-size: 16px; }
        .btn-save:hover { background: #00d2d3; color: #1a1a2e; }
        
        .alert { padding: 15px; border-radius: 10px; text-align: center; margin-bottom: 20px; font-weight: bold; }
        .success { background: #d4edda; color: #155724; } .error { background: #f8d7da; color: #721c24; }
        
        .policy-read-box { background: #fafafa; border: 1px solid #eee; border-radius: 10px; padding: 20px; height: 250px; overflow-y: auto; font-size: 13px; color: #444; margin-top: 10px; line-height: 1.6; }
        .policy-read-box h4 { margin: 10px 0 5px; color: #1a1a2e; font-size: 14px; border-left: 3px solid #00d2d3; padding-left: 8px; }
        .policy-read-box ul { padding-left: 20px; margin: 0; } .policy-read-box li { margin-bottom: 5px; }
    </style>
</head>
<body>

    <div class="sidebar">
        <h2>RPU AM</h2>
        <a class="menu-item" href="photographer_dashboard.php"><i class="fas fa-home"></i> หน้าหลัก <?php echo $job_badge; ?></a>
        <a class="menu-item" href="photographer_portfolio.php"><i class="fas fa-images"></i> แฟ้มผลงาน</a>
        <a class="menu-item" href="photographer_upload.php"><i class="fas fa-cloud-upload-alt"></i> ส่งมอบงาน</a>
        <a class="menu-item" href="job_history.php"><i class="fas fa-history"></i> ประวัติการรับงาน</a>
        <a class="menu-item" href="photographer_chat.php"><i class="fas fa-comments"></i> แชทลูกค้า <?php echo $chat_badge; ?></a>
        <a class="menu-item active" href="settings.php"><i class="fas fa-cog"></i> ตั้งค่าระบบ</a>
        <div class="logout-box"><a class="menu-item logout" href="logout.php"><i class="fas fa-sign-out-alt"></i> ออกจากระบบ</a></div>
    </div>

    <div class="main-content">
        <h1 style="margin-top:0; margin-bottom:20px; color:#333;">⚙️ ตั้งค่าข้อมูลร้าน</h1>
        
        <?php echo $msg; ?>

        <form method="post" enctype="multipart/form-data">
            
            <div class="box">
                <div style="text-align: center;">
                    <?php $img_src = !empty($user['profile_img']) ? "uploads/".$user['profile_img'] : "https://via.placeholder.com/150"; ?>
                    <img src="<?php echo $img_src; ?>" class="img-preview" id="preview">
                    <br>
                    <label for="file-upload" style="color:#00d2d3; cursor:pointer; display:inline-block;"><i class="fas fa-camera"></i> เปลี่ยนรูปโปรไฟล์</label>
                    <input id="file-upload" type="file" name="profile_img" accept="image/*" style="display:none;" onchange="document.getElementById('preview').src = window.URL.createObjectURL(this.files[0])">
                </div>
                <div style="display:flex; gap:15px;">
                    <div style="flex:1;">
                        <label>ชื่อช่างภาพ / ชื่อร้าน</label>
                        <input type="text" value="<?php echo $user['username']; ?>" readonly style="background-color: white; color: #555; cursor: not-allowed;">
                    </div>
                    <div style="flex:1;"><label>เบอร์โทรศัพท์</label><input type="text" name="phone" value="<?php echo $user['phone']; ?>" required></div>
                </div>
            </div>

            <?php if($role == 'photographer'): ?>
            <div class="box">
                <h3>📢 สถานะรับงาน</h3>
                <div class="switch-row">
                    <span><i class="fas fa-camera"></i> กล้องดิจิตอล</span>
                    <label class="switch"><input type="checkbox" name="on_digital" <?php echo $user['on_digital']?'checked':''; ?>><span class="slider"></span></label>
                </div>
                <div class="switch-row">
                    <span><i class="fas fa-mobile-alt"></i> กล้องมือถือ</span>
                    <label class="switch"><input type="checkbox" name="on_mobile" <?php echo $user['on_mobile']?'checked':''; ?>><span class="slider"></span></label>
                </div>
                <div class="switch-row">
                    <span><i class="fas fa-users"></i> ผู้ช่วยช่างภาพ</span>
                    <label class="switch"><input type="checkbox" name="on_client" <?php echo $user['on_client']?'checked':''; ?>><span class="slider"></span></label>
                </div>
            </div>

            <div class="box">
                <h3>💰 ราคาและเวลาทำงาน</h3>
                <div style="display:flex; gap:15px;">
                    <div style="flex:1;"><label>ราคาครึ่งวัน (฿)</label><input type="number" name="price_half" value="<?php echo $user['price_half']; ?>"></div>
                    <div style="flex:1;"><label>ราคาเต็มวัน (฿)</label><input type="number" name="price_full" value="<?php echo $user['price_full']; ?>"></div>
                </div>

                <div style="display:flex; gap:15px; margin-top:10px;">
                    <div style="flex:1;">
                        <label>เวลาเริ่มงาน</label>
                        <div class="time-group">
                            <select name="start_hour">
                                <?php for($i=0;$i<=23;$i++){ $v=sprintf("%02d",$i); echo "<option value='$v' ".($v==date('H',strtotime($user['work_start']))?'selected':'').">$v</option>"; } ?>
                            </select> : 
                            <select name="start_min">
                                <?php for($i=0;$i<=55;$i+=5){ $v=sprintf("%02d",$i); echo "<option value='$v' ".($v==date('i',strtotime($user['work_start']))?'selected':'').">$v</option>"; } ?>
                            </select>
                        </div>
                    </div>
                    <div style="flex:1;">
                        <label>เวลาเลิกงาน</label>
                        <div class="time-group">
                            <select name="end_hour">
                                <?php for($i=0;$i<=23;$i++){ $v=sprintf("%02d",$i); echo "<option value='$v' ".($v==date('H',strtotime($user['work_end']))?'selected':'').">$v</option>"; } ?>
                            </select> : 
                            <select name="end_min">
                                <?php for($i=0;$i<=55;$i+=5){ $v=sprintf("%02d",$i); echo "<option value='$v' ".($v==date('i',strtotime($user['work_end']))?'selected':'').">$v</option>"; } ?>
                            </select>
                        </div>
                    </div>
                </div>

                <label>ธนาคาร</label>
                <select name="bank_name">
                    <?php 
                        $banks=["ธนาคารกสิกรไทย (KBANK)","ธนาคารไทยพาณิชย์ (SCB)","ธนาคารกรุงเทพ (BBL)","ธนาคารกรุงไทย (KTB)","พร้อมเพย์ (PromptPay)"]; 
                        foreach($banks as $b){ echo "<option value='$b' ".($user['bank_name']==$b?'selected':'').">$b</option>"; } 
                    ?>
                </select>
                <label>เลขบัญชี</label>
                <input type="text" name="bank_number" value="<?php echo $user['bank_number']; ?>" placeholder="xxx-x-xxxxx-x">
            </div>

            <div class="box">
                <h3><i class="fas fa-shield-alt"></i> นโยบายและข้อตกลงการให้บริการ (RPU AM Policy)</h3>
                <div class="policy-read-box">
                    <h4>1. มาตรฐานการให้บริการ (Service Standards)</h4>
                    <ul>
                        <li>ช่างภาพต้องเดินทางไปถึงสถานที่นัดหมายล่วงหน้าอย่างน้อย 30 นาที เพื่อเตรียมความพร้อม</li>
                        <li>การแต่งกายต้องสุภาพ เรียบร้อย และเหมาะสมกับสถานที่ (โดยเฉพาะงานรับปริญญาและสถานที่ราชการ)</li>
                        <li>อุปกรณ์ถ่ายภาพต้องมีความพร้อมใช้งานและอยู่ในสภาพสมบูรณ์</li>
                    </ul>

                    <h4>2. การชำระเงินและมัดจำ (Payment & Deposit)</h4>
                    <ul>
                        <li>ลูกค้าต้องชำระค่ามัดจำตามที่ตกลงเพื่อยืนยันคิวงาน</li>
                        <li>ส่วนที่เหลือสามารถชำระหลังเสร็จสิ้นงาน หรือตามตกลง (เงินสด/โอน)</li>
                        <li>กรณีช่างภาพยกเลิกงาน: ต้องคืนเงินมัดจำให้ลูกค้าเต็มจำนวนทันที</li>
                    </ul>

                    <h4>3. การส่งมอบงาน (Deliverables)</h4>
                    <ul>
                        <li>ระยะเวลาส่งงาน: ภายใน 7-14 วัน หลังจากวันถ่ายภาพ (หรือตามตกลง)</li>
                        <li>รูปแบบไฟล์: ไฟล์ .JPG ความละเอียดสูง (High Resolution) ผ่านช่องทาง RPU AM System หรือลิงก์ Cloud</li>
                        <li>การสำรองข้อมูล: ช่างภาพควรสำรองไฟล์งานไว้อย่างน้อย 30 วันหลังจากส่งมอบงาน</li>
                    </ul>

                    <h4>4. การยกเลิกและเลื่อนนัด (Cancellation Policy)</h4>
                    <ul>
                        <li>ลูกค้ายกเลิกน้อยกว่า 3 วันก่อนวันงาน: ขอสงวนสิทธิ์ในการไม่คืนเงินมัดจำ</li>
                        <li>สามารถเลื่อนวันถ่ายได้ 1 ครั้ง โดยต้องแจ้งล่วงหน้าอย่างน้อย 5 วัน และขึ้นอยู่กับคิวว่างของช่างภาพ</li>
                    </ul>

                    <h4>5. นโยบายความเป็นส่วนตัว (PDPA)</h4>
                    <ul>
                        <li>ระบบ RPU AM จะเปิดเผยเบอร์โทรศัพท์และข้อมูลติดต่อแก่คู่สัญญา (ลูกค้า-ช่างภาพ) เฉพาะเมื่อมีการยืนยันการจองแล้วเท่านั้น</li>
                        <li>ห้ามนำข้อมูลส่วนตัวของลูกค้าไปเผยแพร่หรือใช้ในทางมิชอบ</li>
                    </ul>
                </div>
            </div>
            <?php endif; ?>

            <button type="submit" name="save_settings" class="btn-save">บันทึกการเปลี่ยนแปลงทั้งหมด</button>
        </form>
    </div>

</body>
</html>