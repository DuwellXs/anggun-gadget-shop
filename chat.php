<?php
session_start();
include 'config.php';

// Check if user is logged in
$user_id = $_SESSION['user_id'] ?? null;
$user_type = $_SESSION['user_type'] ?? null; // Add this to your session management

// Send message
if (isset($_POST['send_message'])) {
    $order_id = $_POST['order_id'];
    $message = $_POST['message'];
    
    // Validate order belongs to user
    $check_order = $conn->prepare("SELECT delivery_rider FROM orders WHERE id = ? AND user_id = ?");
    $check_order->execute([$order_id, $user_id]);
    $order = $check_order->fetch(PDO::FETCH_ASSOC);
    
    if ($order && $order['delivery_rider'] != '0') {
        $insert_message = $conn->prepare("INSERT INTO chat_messages 
            (order_id, sender_id, receiver_id, message, sender_type) 
            VALUES (?, ?, ?, ?, 'customer')");
        $insert_message->execute([
            $order_id, 
            $user_id, 
            $order['delivery_rider'], 
            $message
        ]);
    }
}

// Fetch messages for a specific order
function getOrderMessages($conn, $order_id, $user_id) {
    $messages = $conn->prepare("SELECT * FROM chat_messages 
        WHERE order_id = ? 
        AND (sender_id = ? OR receiver_id = ?)
        ORDER BY timestamp");
    $messages->execute([$order_id, $user_id, $user_id]);
    return $messages->fetchAll(PDO::FETCH_ASSOC);
}
?>