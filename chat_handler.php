<?php
@include 'config.php';
session_start();

// Make sure the user is logged in before doing anything
if (!isset($_SESSION['user_id'])) {
    // Send an error and stop the script
    header('Content-Type: application/json');
    echo json_encode(['status' => 'error', 'message' => 'Not authenticated.']);
    exit;
}

// Set the content type to JSON for all responses
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (empty($_POST['action'])) {
        echo json_encode(['status' => 'error', 'message' => 'No action specified.']);
        exit;
    }

    $action = $_POST['action'];

    // This block FETCHES existing messages to display them
    if ($action === 'get_messages') {
        $order_id = $_POST['order_id'];
        
        // This query now uses the correct table and column names
        $stmt = $conn->prepare("
            SELECT * FROM message 
            WHERE order_id = ? 
            ORDER BY created_at ASC
        ");
        $stmt->execute([$order_id]);
        $messages = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Add a 'sender_type' to each message so JavaScript knows if it's 'me' or 'them'
        foreach ($messages as &$msg) {
            if ($msg['user_id'] == $_SESSION['user_id']) {
                $msg['sender_type'] = 'me'; // This is a message from the current user
            } else {
                $msg['sender_type'] = 'them'; // This is a message from the other person
            }
        }

        echo json_encode($messages);

    // This block SAVES a new message
    } elseif ($action === 'send_message') {
        $order_id = $_POST['order_id'];
        $message_text = htmlspecialchars($_POST['message']);
        $sender_id = $_SESSION['user_id'];
        
        // This query now uses the correct table and column names
        $stmt = $conn->prepare("INSERT INTO message (order_id, user_id, message) VALUES (?, ?, ?)");
        $stmt->execute([$order_id, $sender_id, $message_text]);

        echo json_encode(['status' => 'success']);
    }
}
?>