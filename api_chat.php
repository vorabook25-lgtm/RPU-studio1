<?php
session_start();
include('db.php');
header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['status' => 'error', 'message' => 'Unauthorized']);
    exit;
}

$my_id = $_SESSION['user_id'];
$action = isset($_POST['action']) ? $_POST['action'] : '';

// --- 🔥 จุดสำคัญ: รองรับทั้ง cus_id และ partner_id ---
$other_id = 0;
if (isset($_POST['partner_id'])) { 
    $other_id = $_POST['partner_id']; // จากหน้าลูกค้า
} elseif (isset($_POST['cus_id'])) { 
    $other_id = $_POST['cus_id'];     // จากหน้าช่างภาพ
} elseif (isset($_POST['pg_id'])) { 
    $other_id = $_POST['pg_id'];      // เผื่อไว้
}

if ($other_id == 0) { 
    echo json_encode([]); 
    exit; 
}

// --- 1. ดึงข้อความ (FETCH) ---
if ($action == 'fetch') {
    $sql = "SELECT * FROM chats 
            WHERE (sender_id = '$my_id' AND receiver_id = '$other_id') 
            OR (sender_id = '$other_id' AND receiver_id = '$my_id') 
            ORDER BY timestamp ASC";
            
    $query = mysqli_query($conn, $sql);
    $messages = [];

    while ($row = mysqli_fetch_assoc($query)) {
        // กำหนด Class CSS (ต้องตรงกับในหน้า photographer_chat.php คือ msg-me / msg-other)
        $class = ($row['sender_id'] == $my_id) ? 'msg-me' : 'msg-other';
        
        $messages[] = [
            'msg' => $row['message'],
            'class' => $class
        ];
    }
    echo json_encode($messages);
}

// --- 2. ส่งข้อความ (SEND) ---
elseif ($action == 'send') {
    $message = isset($_POST['message']) ? mysqli_real_escape_string($conn, $_POST['message']) : '';

    if (!empty($message)) {
        $sql = "INSERT INTO chats (sender_id, receiver_id, message) VALUES ('$my_id', '$other_id', '$message')";
        if(mysqli_query($conn, $sql)) {
            echo json_encode(['status' => 'success']);
        } else {
            echo json_encode(['status' => 'error', 'message' => mysqli_error($conn)]);
        }
    }
}
?>