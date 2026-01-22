<?php
// Returns latest rider location from rider_locations table
header('Content-Type: application/json');
@include 'config.php';

$rider_id = $_GET['rider_id'] ?? null;
if (!$rider_id) {
    echo json_encode(['success' => false, 'message' => 'rider_id required']);
    exit;
}

try {
    $stmt = $conn->prepare("SELECT lat, lng, updated_at FROM rider_locations WHERE rider_id = ? LIMIT 1");
    $stmt->execute([$rider_id]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    if ($row) {
        echo json_encode(['success' => true, 'lat' => $row['lat'], 'lng' => $row['lng'], 'updated_at' => $row['updated_at']]);
    } else {
        echo json_encode(['success' => false, 'message' => 'no location']);
    }
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}

?>
