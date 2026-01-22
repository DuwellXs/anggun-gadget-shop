<?php
@include 'config.php';
require_once 'geocode.php';

header('Content-Type: application/json');
$address = $_REQUEST['address'] ?? '';
if (trim($address) === '') {
    echo json_encode(['success' => false, 'message' => 'No address provided']);
    exit();
}

$coords = geocode_address_nominatim($address);
if (!$coords || !isset($coords['lat']) || !$coords['lng']) {
    echo json_encode(['success' => false, 'message' => 'Geocoding failed']);
    exit();
}

echo json_encode(['success' => true, 'lat' => $coords['lat'], 'lng' => $coords['lng']]);
