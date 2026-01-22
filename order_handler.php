<?php
// order_handler.php
include 'config.php';
session_start();

$user_id = $_SESSION['user_id'] ?? null;
$response = ['success' => false, 'message' => ''];

// 1. Check if user is logged in
if (!$user_id) {
    echo json_encode(['success' => false, 'message' => 'Please login first.']);
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    
    // 2. Get the Action and Order ID
    $action = $_POST['action'] ?? '';
    $order_id = $_POST['order_id'] ?? 0;

    if ($action === 'cancel_order' && $order_id) {
        
        // 3. Verify the order belongs to this user AND is still "Processing"
        // We don't want them cancelling orders that are already "On the way"
        $check = $conn->prepare("SELECT * FROM `orders` WHERE id = ? AND user_id = ?");
        $check->execute([$order_id, $user_id]);
        
        if ($check->rowCount() > 0) {
            $order = $check->fetch(PDO::FETCH_ASSOC);
            $current_status = strtolower($order['delivery_status']);

            // Only allow cancellation if status is 'pending' or 'preparing order'
            if (in_array($current_status, ['pending', 'preparing order'])) {
                
                // 4. UPDATE STATUS TO CANCELLED
                $update = $conn->prepare("UPDATE `orders` SET delivery_status = 'Cancelled' WHERE id = ?");
                
                if ($update->execute([$order_id])) {
                    
                    // (Optional) Restore Stock?
                    // If you want to put items back in stock, you would write that logic here.
                    
                    $response['success'] = true;
                    $response['message'] = 'Order cancelled. Refund request sent to Admin.';
                } else {
                    $response['message'] = 'Database error. Could not cancel.';
                }

            } else {
                $response['message'] = 'Order cannot be cancelled at this stage (it might be on the way).';
            }
        } else {
            $response['message'] = 'Order not found.';
        }
    } else {
        $response['message'] = 'Invalid request.';
    }
}

// 5. Return JSON response to the JavaScript
echo json_encode($response);
?>