<?php
@include 'config.php';
session_start();

if(!isset($_SESSION['admin_id'])) {
    header('HTTP/1.1 403 Forbidden');
    exit('Unauthorized access');
}

if(!isset($_GET['id'])) {
    header('HTTP/1.1 400 Bad Request');
    exit('Order ID not provided');
}

try {
    $stmt = $conn->prepare("
        SELECT o.*, 
               c.name AS customer_name,
               c.email AS customer_email
        FROM `orders` o
        LEFT JOIN `users` c ON o.user_id = c.id
        WHERE o.id = ?
    ");
    
    $stmt->execute([$_GET['id']]);
    $order = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if(!$order) {
        header('HTTP/1.1 404 Not Found');
        exit('Order not found');
    }
    
    header('Content-Type: application/json');
    echo json_encode($order);
    
} catch(PDOException $e) {
    header('HTTP/1.1 500 Internal Server Error');
    exit('Database error occurred');
}
?>