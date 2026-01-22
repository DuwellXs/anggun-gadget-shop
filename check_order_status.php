<?php
@include 'config.php';
session_start();

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['order_id'])) {
    try {
        $stmt = $conn->prepare("SELECT delivery_status FROM orders WHERE id = ?");
        $stmt->execute([$_POST['order_id']]);
        
        if ($result = $stmt->fetch(PDO::FETCH_ASSOC)) {
            echo json_encode([
                'status' => $result['delivery_status'],
                'success' => true,
                'timestamp' => date('Y-m-d H:i:s')
            ]);
        } else {
            echo json_encode([
                'error' => 'Order not found',
                'success' => false
            ]);
        }
    } catch (PDOException $e) {
        echo json_encode([
            'error' => 'Database error',
            'success' => false,
            'message' => $e->getMessage()
        ]);
    }
} else {
    echo json_encode([
        'error' => 'Invalid request',
        'success' => false
    ]);
}
?>