<?php
    $servername = "localhost";
    $username = "root";
    $password = ""; // ถ้ามีรหัสผ่าน phpMyAdmin ให้ใส่ตรงนี้
    $dbname = "rpa_am"; // ชื่อฐานข้อมูลต้องตรงเป๊ะ

    // สร้างการเชื่อมต่อ
    $conn = mysqli_connect($servername, $username, $password, $dbname);

    // เช็คการเชื่อมต่อ
    if (!$conn) {
        die("Connection failed: " . mysqli_connect_error());
    }
    
    // ตั้งค่าภาษาไทย
    mysqli_set_charset($conn, "utf8");
?>