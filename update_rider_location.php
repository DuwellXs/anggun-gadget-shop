<?php
// Endpoint for riders to POST their current location (rider app/browser should call this)
// Expects POST: rider_id, lat, lng
@include 'config.php';

header('Content-Type: application/json');

$rider_id = $_POST['rider_id'] ?? null;
$lat = $_POST['lat'] ?? null;
$lng = $_POST['lng'] ?? null;

if (!$rider_id || !$lat || !$lng) {
    echo json_encode(['success' => false, 'message' => 'rider_id, lat and lng are required']);
    exit;
}

try {
    // Upsert into rider_locations
    $stmt = $conn->prepare("INSERT INTO rider_locations (rider_id, lat, lng, updated_at) VALUES (?, ?, ?, NOW()) ON DUPLICATE KEY UPDATE lat = VALUES(lat), lng = VALUES(lng), updated_at = NOW()");
    $stmt->execute([$rider_id, $lat, $lng]);
    echo json_encode(['success' => true]);
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}

?>
