<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ผลงานช่างภาพ | Rajapruk Photo</title>
    
    <style>
        /* ตั้งค่าพื้นฐาน */
        body {
            font-family: 'Sarabun', sans-serif; /* แนะนำให้หา Google Font มาใส่เพิ่มถ้าต้องการ */
            margin: 0;
            padding: 0;
            background-color: #f4f4f4;
            
            /* เริ่มต้น: ซ่อนหน้าเว็บไว้ก่อน (เพื่อทำ Effect) */
            opacity: 0;
            transition: opacity 0.5s ease-in-out;
        }

        /* เมื่อโหลดเสร็จ จะเปลี่ยนเป็น Class นี้เพื่อโชว์หน้าเว็บ */
        body.loaded {
            opacity: 1;
        }

        /* เมื่อกำลังจะเปลี่ยนหน้า จะใช้ Class นี้เพื่อค่อยๆ จางหาย */
        body.fade-out {
            opacity: 0;
        }

        /* กล่องหลักสำหรับใส่เนื้อหา */
        .container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 40px 20px;
        }

        /* หัวข้อหน้า */
        .page-header {
            text-align: center;
            margin-bottom: 40px;
        }

        /* ตารางรูปภาพ (Grid) */
        .gallery-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
            gap: 20px;
        }

        /* การ์ดแต่ละรูป */
        .gallery-item {
            position: relative;
            background: white;
            border-radius: 15px;
            overflow: hidden;
            box-shadow: 0 4px 10px rgba(0,0,0,0.1);
            cursor: pointer;
            transition: transform 0.3s ease;
        }

        .gallery-item:hover {
            transform: translateY(-10px); /* ลอยขึ้นเมื่อเอาเมาส์ชี้ */
        }

        .gallery-item img {
            width: 100%;
            height: 250px;
            object-fit: cover;
            display: block;
        }

        /* ข้อมูลที่จะโชว์ตอนเอาเมาส์ชี้ */
        .info-overlay {
            padding: 15px;
            background: white;
        }

        .info-title {
            font-size: 1.1rem;
            font-weight: bold;
            color: #333;
            margin: 0;
        }

        .info-desc {
            font-size: 0.9rem;
            color: #777;
            margin-top: 5px;
        }
        
        /* ปุ่มกลับหน้าหลัก (Navbar) */
        .navbar {
            background: #333;
            color: white;
            padding: 15px;
            text-align: center;
        }
        
        .navbar a {
            color: white;
            text-decoration: none;
            margin: 0 10px;
        }
    </style>
</head>
<body>

    <div class="navbar">
        <a href="index.php" onclick="goToPage(event, 'index.php')">🏠 กลับหน้าหลัก</a> | 
        <span>ผลงานของเรา</span>
    </div>

    <div class="container">
        <div class="page-header">
            <h1>ผลงานที่ผ่านมา</h1>
            <p>รวมภาพประทับใจจากลูกค้ามหาวิทยาลัยราชพฤกษ์</p>
        </div>

        <div class="gallery-grid">
            <?php
            // ส่วนจำลองข้อมูล (ในอนาคตนายท่านแก้ตรงนี้ให้ดึงจาก Database)
            $galleryData = [
                ['id' => 1, 'image' => 'https://picsum.photos/id/101/400/300', 'title' => 'รับปริญญานอกรอบ', 'desc' => 'คณะบริหารธุรกิจ'],
                ['id' => 2, 'image' => 'https://picsum.photos/id/102/400/300', 'title' => 'งานเฟรชชี่ไนท์', 'desc' => 'กิจกรรมนักศึกษา'],
                ['id' => 3, 'image' => 'https://picsum.photos/id/103/400/300', 'title' => 'Portrait เดี่ยว', 'desc' => 'สวนสาธารณะใน ม.'],
                ['id' => 4, 'image' => 'https://picsum.photos/id/104/400/300', 'title' => 'ถ่ายภาพกลุ่มเพื่อน', 'desc' => 'ธีมชุดนักศึกษา'],
                ['id' => 5, 'image' => 'https://picsum.photos/id/106/400/300', 'title' => 'งานสัมมนา', 'desc' => 'หอประชุมใหญ่'],
                ['id' => 6, 'image' => 'https://picsum.photos/id/108/400/300', 'title' => 'Landscape', 'desc' => 'บรรยากาศตึกเรียน'],
            ];

            // วนลูปแสดงรูปภาพ
            foreach ($galleryData as $item) {
            ?>
                <div class="gallery-item" onclick="directToPage('booking.php?id=<?php echo $item['id']; ?>')">
                    <img src="<?php echo $item['image']; ?>" alt="<?php echo $item['title']; ?>">
                    <div class="info-overlay">
                        <h3 class="info-title"><?php echo $item['title']; ?></h3>
                        <p class="info-desc"><?php echo $item['desc']; ?></p>
                    </div>
                </div>
            <?php } ?>
        </div>
    </div>

    <script>
        // 1. ทันทีที่โหลดหน้าเสร็จ ให้ค่อยๆ ปรากฏขึ้นมา (Fade In)
        window.addEventListener('load', () => {
            document.body.classList.add('loaded');
        });

        // 2. ฟังก์ชันสำหรับเปลี่ยนหน้า (Fade Out -> ย้ายหน้า)
        function directToPage(url) {
            // เริ่มทำให้หน้าจางหาย
            document.body.classList.remove('loaded');
            document.body.classList.add('fade-out');

            // รอ 0.5 วินาที (500ms) ให้เอฟเฟกต์จบ แล้วค่อยเปลี่ยนหน้าจริง
            setTimeout(() => {
                window.location.href = url;
            }, 500);
        }

        // 3. ฟังก์ชันสำหรับ Link ธรรมดา (เช่น ปุ่มกลับหน้าหลัก)
        function goToPage(event, url) {
            event.preventDefault(); // ห้ามเปลี่ยนหน้าทันที
            directToPage(url); // เรียกใช้ฟังก์ชันเปลี่ยนหน้าแบบนุ่มนวล
        }
    </script>

</body>
</html>