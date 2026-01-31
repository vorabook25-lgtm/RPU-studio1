<?php
    session_start();
    
    // ล้างข้อมูล Session ทั้งหมด (ออกจากระบบ)
    session_unset();
    session_destroy();

    // ดีดกลับไปหน้า Login
    header('location: login.php');
    exit;
?>