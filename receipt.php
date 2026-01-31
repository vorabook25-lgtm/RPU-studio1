<?php
session_start();
include('db.php');

// 1. ตรวจสอบการ Login
if (!isset($_SESSION['user_id'])) {
    // ถ้าไม่ได้ Login ให้เด้งไป Login
    header('location: login.php');
    exit;
}

$user_id = $_SESSION['user_id'];
$role = $_SESSION['role']; // 'customer' หรือ 'photographer'

// 2. รับค่า Booking ID
if (!isset($_GET['booking_id'])) { die("ไม่พบรหัสการจอง"); }
$booking_id = mysqli_real_escape_string($conn, $_GET['booking_id']);

// 3. ดึงข้อมูลใบเสร็จ (จอยตาราง จอง + ลูกค้า + ช่างภาพ)
$sql = "SELECT b.*, 
        u_cus.username as cus_name, u_cus.email as cus_email, u_cus.phone as cus_phone,
        u_pg.username as pg_name, u_pg.phone as pg_phone, u_pg.profile_img
        FROM bookings b
        JOIN users u_cus ON b.user_id = u_cus.id
        JOIN users u_pg ON b.photographer_id = u_pg.id
        WHERE b.id = '$booking_id'";

$query = mysqli_query($conn, $sql);
$row = mysqli_fetch_assoc($query);

// 4. ความปลอดภัย: เช็คว่าคนที่ดู เป็นเจ้าของงาน หรือเป็นช่างภาพงานนี้ไหม?
if (!$row) { die("ไม่พบข้อมูลการจองนี้"); }

// ถ้าไม่ใช่ลูกค้าเจ้าของงาน และ ไม่ใช่ช่างภาพเจ้าของงาน -> ห้ามดู
if ($row['user_id'] != $user_id && $row['photographer_id'] != $user_id) {
    echo "<script>alert('คุณไม่มีสิทธิ์เข้าถึงใบเสร็จนี้'); window.close();</script>";
    exit;
}

// 5. เช็คสถานะการเงิน (ต้องจ่ายแล้วถึงจะดูได้)
if ($row['payment_status'] != 'paid') {
    echo "<div style='text-align:center; padding:50px; font-family:sans-serif;'>
            <h1>⚠️ ใบเสร็จยังไม่ออก</h1>
            <p>กรุณาชำระเงินและรอการอนุมัติยอดก่อนครับ</p>
          </div>";
    exit;
}
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <title>ใบเสร็จรับเงิน #<?php echo $booking_id; ?></title>
    <link href="https://fonts.googleapis.com/css2?family=Sarabun:wght@300;400;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        body { background: #555; font-family: 'Sarabun', sans-serif; padding: 20px; }
        .receipt-box {
            background: white; width: 210mm; min-height: 297mm; /* A4 Size */
            margin: 0 auto; padding: 40px; box-sizing: border-box;
            box-shadow: 0 0 20px rgba(0,0,0,0.5); position: relative;
        }
        
        .header { display: flex; justify-content: space-between; align-items: center; border-bottom: 2px solid #333; padding-bottom: 20px; margin-bottom: 20px; }
        .logo { font-size: 24px; font-weight: bold; color: #1a1a2e; }
        .logo i { color: #ff9f43; }
        .title { text-align: right; }
        .title h1 { margin: 0; font-size: 24px; text-transform: uppercase; color: #333; }
        .title p { margin: 0; font-size: 14px; color: #666; }

        .info-grid { display: flex; justify-content: space-between; margin-bottom: 30px; }
        .box-info h3 { margin: 0 0 10px; font-size: 16px; border-bottom: 1px solid #ddd; padding-bottom: 5px; }
        .box-info p { margin: 3px 0; font-size: 14px; color: #444; }

        table { width: 100%; border-collapse: collapse; margin-bottom: 30px; }
        th { background: #1a1a2e; color: white; padding: 10px; text-align: left; font-size: 14px; }
        td { border-bottom: 1px solid #eee; padding: 12px 10px; font-size: 14px; color: #333; }
        .total-row td { border-top: 2px solid #333; font-weight: bold; font-size: 16px; background: #f9f9f9; }

        .status-stamp {
            position: absolute; top: 150px; right: 40px;
            border: 3px solid #28a745; color: #28a745;
            padding: 10px 20px; font-size: 24px; font-weight: bold;
            transform: rotate(-15deg); border-radius: 10px; opacity: 0.8;
        }

        .footer { margin-top: 50px; text-align: center; font-size: 12px; color: #888; border-top: 1px solid #eee; padding-top: 20px; }
        
        /* ปุ่มพิมพ์ */
        .btn-print {
            position: fixed; bottom: 20px; right: 20px;
            background: #ff9f43; color: white; padding: 15px 30px;
            border: none; border-radius: 50px; font-size: 18px; cursor: pointer;
            box-shadow: 0 4px 10px rgba(0,0,0,0.2); transition: 0.3s;
        }
        .btn-print:hover { background: #e67e22; transform: translateY(-3px); }

        @media print {
            body { background: white; padding: 0; }
            .receipt-box { box-shadow: none; width: 100%; height: auto; }
            .btn-print { display: none; }
        }
    </style>
</head>
<body>

    <div class="receipt-box">
        <div class="status-stamp">PAID / จ่ายแล้ว</div>

        <div class="header">
            <div class="logo">
                <i class="fas fa-camera-retro"></i> RPU SYSTEM
                <div style="font-size:12px; font-weight:normal; margin-top:5px;">ระบบจองคิวช่างภาพออนไลน์</div>
            </div>
            <div class="title">
                <h1>ใบเสร็จรับเงิน</h1>
                <p>RECEIPT</p>
                <p>ต้นฉบับ (Original)</p>
            </div>
        </div>

        <div class="info-grid">
            <div class="box-info" style="width: 45%;">
                <h3>ผู้ชำระเงิน (Customer)</h3>
                <p><strong>ชื่อ:</strong> <?php echo $row['cus_name']; ?></p>
                <p><strong>อีเมล:</strong> <?php echo $row['cus_email']; ?></p>
                <p><strong>เบอร์โทร:</strong> <?php echo $row['cus_phone']; ?></p>
            </div>
            <div class="box-info" style="width: 45%; text-align: right;">
                <h3>ข้อมูลเอกสาร (Document Info)</h3>
                <p><strong>เลขที่ใบเสร็จ:</strong> INV-<?php echo str_pad($row['id'], 6, '0', STR_PAD_LEFT); ?></p>
                <p><strong>วันที่ออก:</strong> <?php echo date("d/m/Y", strtotime($row['booking_date'])); ?></p>
                <p><strong>ช่างภาพ:</strong> <?php echo $row['pg_name']; ?></p>
            </div>
        </div>

        <table>
            <thead>
                <tr>
                    <th style="width: 5%;">#</th>
                    <th style="width: 55%;">รายการ (Description)</th>
                    <th style="width: 20%; text-align:center;">ประเภท (Type)</th>
                    <th style="width: 20%; text-align:right;">จำนวนเงิน (Amount)</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td>1</td>
                    <td>
                        <strong>บริการถ่ายภาพนอกสถานที่</strong><br>
                        <small>สถานที่: <?php echo $row['location']; ?></small><br>
                        <small>วันที่: <?php echo $row['booking_date']; ?> | เวลา: <?php echo $row['booking_time']; ?> - <?php echo $row['booking_end_time']; ?></small>
                    </td>
                    <td style="text-align:center;"><?php echo $row['job_type']; ?></td>
                    <td style="text-align:right;"><?php echo number_format($row['price'], 2); ?> บาท</td>
                </tr>
                </tbody>
            <tfoot>
                <tr class="total-row">
                    <td colspan="3" style="text-align:right;">รวมทั้งสิ้น (Total)</td>
                    <td style="text-align:right; color:#28a745;"><?php echo number_format($row['price'], 2); ?> บาท</td>
                </tr>
            </tfoot>
        </table>

        <div style="margin-top: 50px; display: flex; justify-content: space-between;">
            <div style="text-align: center; width: 200px;">
                <p style="border-bottom: 1px solid #ccc; height: 50px;"></p>
                <p>( ผู้รับเงิน / Collector )</p>
                <p>RPU System Admin</p>
            </div>
            <div style="text-align: center; width: 200px;">
                <p style="border-bottom: 1px solid #ccc; height: 50px;"></p>
                <p>( ผู้จ่ายเงิน / Payer )</p>
                <p><?php echo $row['cus_name']; ?></p>
            </div>
        </div>

        <div class="footer">
            <p>ขอบคุณที่ใช้บริการ RPU Photographer Matching System</p>
            <p>เอกสารฉบับนี้ออกโดยระบบอัตโนมัติ</p>
        </div>
    </div>

    <button onclick="window.print()" class="btn-print"><i class="fas fa-print"></i> พิมพ์ใบเสร็จ</button>

</body>
</html>