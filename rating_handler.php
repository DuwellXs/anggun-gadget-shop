<?php
include 'config.php';
session_start();

$response = ['success' => false, 'message' => 'An error occurred'];

if (!isset($_SESSION['user_id'])) {
    $response['message'] = 'Please login first.';
    echo json_encode($response);
    exit();
}

$user_id = $_SESSION['user_id'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    
    if (isset($_POST['action']) && $_POST['action'] === 'submit_rating') {

        $pid = $_POST['pid'] ?? '';
        $rating = $_POST['rating'] ?? '';
        $comment = $_POST['comment'] ?? '';
        $order_id = $_POST['order_id'] ?? '';

        if (empty($pid) || empty($rating) || empty($order_id)) {
            $response['message'] = 'Please fill in all required fields.';
            echo json_encode($response);
            exit();
        }

        try {
            // 1. INSERT OR UPDATE REVIEW
            // We use "ON DUPLICATE KEY UPDATE" to fix the error.
            // If the user already reviewed this product, it simply updates their old review 
            // with the new rating and comment instead of crashing.
            $sql = "INSERT INTO `review` (user_id, pid, rating, comment) 
                    VALUES (?, ?, ?, ?) 
                    ON DUPLICATE KEY UPDATE 
                    rating = VALUES(rating), 
                    comment = VALUES(comment), 
                    created_at = CURRENT_TIMESTAMP";
            
            $stmt = $conn->prepare($sql);
            $stmt->execute([$user_id, $pid, $rating, $comment]);

            // 2. ALWAYS UPDATE THE ORDER STATUS
            // Whether it was a new review or an update, we mark this specific order as rated.
            $update_order = $conn->prepare("UPDATE `orders` SET is_rated = 1 WHERE id = ?");
            $update_order->execute([$order_id]);

            $response['success'] = true;
            $response['message'] = 'Review submitted successfully!';

        } catch (PDOException $e) {
            $response['message'] = 'Database Error: ' . $e->getMessage();
        }
    } else {
        $response['message'] = 'Invalid action.';
    }
}

echo json_encode($response);
?>