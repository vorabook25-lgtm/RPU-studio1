<?php 
    session_start();
    include('db.php'); // ต้องเชื่อมต่อฐานข้อมูล

    // ถ้ายังไม่ Login ให้กลับไป Login
    if (!isset($_SESSION['user_id'])) { header('location: login.php'); exit; }
    
    // Logic: เมื่อกดปุ่ม "ยอมรับ"
    if (isset($_POST['agree'])) {
        $user_id = $_SESSION['user_id'];
        $role = isset($_SESSION['role']) ? $_SESSION['role'] : 'photographer'; 

        // ✅ 1. อัปเดตฐานข้อมูล: เปลี่ยนสถานะเป็น 1 (ยอมรับแล้ว)
        $sql = "UPDATE users SET policy_accepted = 1 WHERE id = '$user_id'";
        mysqli_query($conn, $sql);
        
        // ✅ 2. พาไปหน้า Dashboard ตามบทบาท
        if ($role == 'photographer') {
            header('location: photographer_dashboard.php');
        } else {
            // ถ้าเป็นลูกค้า ให้ไปหน้าลูกค้า
            header('location: marketplace.php'); 
        }
        exit;
    }
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <title>นโยบายและข้อตกลง | RPU System</title>
    <link href="https://fonts.googleapis.com/css2?family=Sarabun:wght@300;400;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        body { background: #1a1a2e; color: white; font-family: 'Sarabun'; display: flex; justify-content: center; align-items: center; min-height: 100vh; margin: 0; padding: 20px; box-sizing: border-box; }
        
        .box { 
            background: white; 
            color: #333; 
            padding: 30px; 
            border-radius: 15px; 
            width: 100%;
            max-width: 800px; /* ขยายให้กว้างขึ้น */
            box-shadow: 0 15px 40px rgba(0,0,0,0.5); 
            border-top: 5px solid #00d2d3;
            display: flex;
            flex-direction: column;
            height: 80vh; /* ให้สูงเกือบเต็มจอ */
        }

        h2 { margin-top: 0; color: #1a1a2e; border-bottom: 2px solid #eee; padding-bottom: 15px; }
        
        /* กล่องเนื้อหา */
        .content {
            flex: 1; /* ยืดให้เต็มพื้นที่ */
            background: #f8f9fa;
            border: 1px solid #ddd;
            border-radius: 10px;
            padding: 20px;
            overflow-y: auto; /* Scroll ได้ */
            text-align: left;
            font-size: 14px;
            margin-bottom: 20px;
            color: #444;
            line-height: 1.8;
        }

        /* หัวข้อในนโยบาย */
        .policy-section { margin-bottom: 20px; }
        .policy-title { font-weight: bold; color: #1a1a2e; font-size: 16px; margin-bottom: 8px; display: flex; align-items: center; gap: 8px; }
        .policy-title i { color: #00d2d3; }
        ul { margin: 0; padding-left: 25px; }
        li { margin-bottom: 5px; }

        /* Scrollbar สวยๆ */
        .content::-webkit-scrollbar { width: 8px; }
        .content::-webkit-scrollbar-track { background: #f1f1f1; border-radius: 4px; }
        .content::-webkit-scrollbar-thumb { background: #ccc; border-radius: 4px; }
        .content::-webkit-scrollbar-thumb:hover { background: #00d2d3; }

        /* Checkbox & Button */
        .footer-action { background: white; padding-top: 10px; }
        .checkbox-container {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
            margin-bottom: 15px;
            cursor: pointer;
            font-weight: bold;
            color: #1a1a2e;
            user-select: none;
        }
        input[type="checkbox"] { width: 20px; height: 20px; cursor: pointer; accent-color: #00d2d3; }

        .btn { 
            display: block; width: 100%; padding: 15px; background: #1a1a2e; color: #00d2d3; border: 1px solid #1a1a2e; 
            border-radius: 30px; font-weight: bold; font-size: 16px; transition: 0.3s;
            opacity: 0.5; cursor: not-allowed; pointer-events: none;
        }
        .btn.active { opacity: 1; cursor: pointer; pointer-events: auto; background: #00d2d3; color: #1a1a2e; box-shadow: 0 5px 15px rgba(0, 210, 211, 0.4); }
    </style>
</head>
<body>
    <div class="box">
        <h2><i class="fas fa-balance-scale"></i> ข้อตกลงและนโยบายการให้บริการ (Terms of Service)</h2>
        
        <div class="content">
            <p style="text-align:center; font-weight:bold; color:#e74c3c;">** กรุณาอ่านทำความเข้าใจก่อนกดยอมรับ **</p>

            <div class="policy-section">
                <div class="policy-title"><i class="fas fa-money-bill-wave"></i> 1. การจองและชำระเงิน (Booking & Payment)</div>
                <ul>
                    <li>การจองคิวจะสมบูรณ์เมื่อลูกค้าชำระค่ามัดจำ (Deposit) ตามยอดที่ระบบแจ้งไว้เท่านั้น</li>
                    <li>ลำดับคิวจะยึดตามเวลาที่โอนเงินมัดจำ (First come, first served)</li>
                    <li>ค่าบริการส่วนที่เหลือ (Final Payment) ต้องชำระทันทีเมื่อเสร็จสิ้นงานถ่ายภาพ หรือส่งมอบงาน</li>
                    <li>หากลูกค้าต้องการใบกำกับภาษี กรุณาแจ้งช่างภาพล่วงหน้าก่อนวันงาน</li>
                </ul>
            </div>

            <div class="policy-section">
                <div class="policy-title"><i class="fas fa-calendar-times"></i> 2. การเลื่อนและการยกเลิก (Reschedule & Cancellation)</div>
                <ul>
                    <li><strong>แจ้งยกเลิกก่อนวันงาน 7 วันขึ้นไป:</strong> คืนเงินมัดจำเต็มจำนวน</li>
                    <li><strong>แจ้งยกเลิกก่อนวันงาน 3-6 วัน:</strong> คืนเงินมัดจำ 50%</li>
                    <li><strong>แจ้งยกเลิกน้อยกว่า 3 วัน:</strong> ขอสงวนสิทธิ์ไม่คืนเงินมัดจำทุกกรณี</li>
                    <li>สามารถขอเลื่อนวันถ่ายได้ 1 ครั้ง (ต้องแจ้งล่วงหน้าอย่างน้อย 5 วัน) โดยขึ้นอยู่กับตารางงานว่างของช่างภาพ</li>
                    <li>กรณีเหตุสุดวิสัย (เช่น ภัยธรรมชาติ, โรคระบาดร้ายแรง) ให้เจรจาตกลงเป็นรายกรณี</li>
                </ul>
            </div>

            <div class="policy-section">
                <div class="policy-title"><i class="fas fa-clock"></i> 3. ขอบเขตการทำงาน (Scope of Work)</div>
                <ul>
                    <li>เวลาทำงานเริ่มนับตามเวลาที่นัดหมาย หากลูกค้ามาสาย เวลาทำงานจะสิ้นสุดตามกำหนดเดิม (ไม่มีการทดเวลา)</li>
                    <li>หากต้องการเพิ่มเวลาทำงาน (Overtime) คิดค่าบริการเพิ่มเป็นรายชั่วโมง ตามเรทของช่างภาพ</li>
                    <li>ช่างภาพรับผิดชอบเฉพาะการถ่ายภาพ ไม่รวมค่าเดินทาง, ค่าที่พัก, หรือค่าธรรมเนียมสถานที่ (ถ้ามี) ซึ่งลูกค้าเป็นผู้รับผิดชอบ</li>
                </ul>
            </div>

            <div class="policy-section">
                <div class="policy-title"><i class="fas fa-cloud-download-alt"></i> 4. การส่งมอบงาน (Deliverables)</div>
                <ul>
                    <li>กำหนดส่งงานภายใน 7-14 วัน หลังวันถ่ายภาพ (หรือตามตกลง)</li>
                    <li>ส่งงานผ่านช่องทาง Google Drive / Cloud Storage โดยจะเก็บไฟล์ไว้ให้ดาวน์โหลดเป็นเวลา 30 วัน</li>
                    <li>ไฟล์ที่ได้รับจะเป็นไฟล์ .JPG ความละเอียดสูง (High Resolution) ที่ผ่านการปรับแสงสีแล้ว</li>
                    <li><strong>ไม่แจกไฟล์ดิบ (RAW File)</strong> ในทุกกรณี เว้นแต่จะมีการตกลงซื้อขายสิทธิ์เพิ่มเติม</li>
                    <li>การรีทัช (Retouch) จะครอบคลุมเฉพาะการปรับแสงสี (Tone/Color) ไม่รวมการปรับสัดส่วนโครงสร้างร่างกายที่เกินจริง</li>
                </ul>
            </div>

            <div class="policy-section">
                <div class="policy-title"><i class="fas fa-copyright"></i> 5. ลิขสิทธิ์และสิทธิการใช้งาน (Copyright)</div>
                <ul>
                    <li>ลิขสิทธิ์ภาพถ่ายยังคงเป็นของช่างภาพโดยสมบูรณ์ ตามกฎหมายลิขสิทธิ์</li>
                    <li>ลูกค้าได้รับสิทธิ์ในการนำภาพไปใช้เพื่อ <strong>"วัตถุประสงค์ส่วนตัว" (Personal Use)</strong> เท่านั้น เช่น ลง Social Media, อัดกรอบรูป</li>
                    <li>ห้ามนำภาพไปใช้ในเชิงพาณิชย์ (Commercial Use) เช่น โฆษณา, ขายสินค้า, หรือขายต่อให้บุคคลที่สาม โดยไม่ได้รับอนุญาต</li>
                </ul>
            </div>

            <div class="policy-section">
                <div class="policy-title"><i class="fas fa-user-shield"></i> 6. นโยบายความเป็นส่วนตัว (Privacy Policy)</div>
                <ul>
                    <li>ข้อมูลส่วนตัวของท่าน (ชื่อ, เบอร์โทร) จะถูกใช้เพื่อการติดต่อประสานงานภายในระบบ RPU System เท่านั้น</li>
                    <li>ช่างภาพขอสงวนสิทธิ์ในการนำภาพผลงานบางส่วนไปใช้ในการประชาสัมพันธ์ (Portfolio) หากลูกค้าไม่สะดวกใจ กรุณาแจ้งเป็นลายลักษณ์อักษร</li>
                </ul>
            </div>
        </div>

        <div class="footer-action">
            <form method="post">
                <label class="checkbox-container">
                    <input type="checkbox" id="agreeCheck" onchange="toggleButton()">
                    ข้าพเจ้าได้อ่าน เข้าใจ และยอมรับเงื่อนไขทั้งหมดข้างต้น
                </label>
                
                <button type="submit" name="agree" id="submitBtn" class="btn">
                    เข้าสู่ระบบ RPU System <i class="fas fa-arrow-right"></i>
                </button>
            </form>
        </div>
    </div>

    <script>
        function toggleButton() {
            const checkbox = document.getElementById('agreeCheck');
            const btn = document.getElementById('submitBtn');

            if (checkbox.checked) {
                btn.classList.add('active');
            } else {
                btn.classList.remove('active');
            }
        }
    </script>
</body>
</html>