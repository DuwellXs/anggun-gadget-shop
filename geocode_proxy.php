<?php
@include 'config.php';

header('Content-Type: application/json');

$address = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $address = $_POST['address'] ?? '';
} else {
    $address = $_GET['address'] ?? '';
}

$address = trim($address);
if ($address === '') {
    echo json_encode(['success' => false, 'message' => 'No address provided']);
    exit();
}

// Build smarter variants: normalize, remove postal codes, expand KG->Kampung, dedupe repeated words
$variants = [];
$clean = preg_replace('/\\s+/', ' ', $address);
$variants[] = $clean;
$variants[] = preg_replace('/\\s-\\s/', ' ', $clean);
$variants[] = preg_replace('/-/', ' ', $clean);
$variants[] = preg_replace('/\\b\\d{4,6}\\b/', '', $clean); // remove postal codes

// Expand common local abbreviations
$norm = $variants[count($variants)-1];
$norm = preg_replace('/\\bKG\\b/i', 'Kampung', $norm);
$norm = preg_replace('/\\bKg\\b/i', 'Kampung', $norm);
$norm = preg_replace('/\\bKg\\.\\b/i', 'Kampung', $norm);
// remove duplicate consecutive words (e.g., 'Guruh Guruh' -> 'Guruh')
$norm = preg_replace('/\\b(\\w+)\\s+\\1\\b/i', '$1', $norm);
$norm = trim($norm);
$variants[] = $norm;
$variants[] = $norm . ', Malaysia';

$tried = [];
foreach ($variants as $q) {
    $q = trim($q);
    if ($q === '' || in_array($q, $tried)) continue;
    $tried[] = $q;

    $endpoint = 'https://nominatim.openstreetmap.org/search?format=json&limit=1&q=' . urlencode($q);
    $ch = curl_init($endpoint);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 8);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'User-Agent: TestStore/1.0 (contact@example.com)',
        'Accept-Language: en'
    ]);

    $response = curl_exec($ch);
    $httpcode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $curlErr = curl_error($ch);
    curl_close($ch);

    if ($response === false || $httpcode >= 400) {
        error_log("Geocode proxy try failed for [" . $q . "] http=$httpcode err={$curlErr}");
        continue;
    }

    $data = json_decode($response, true);
    if (is_array($data) && count($data) > 0) {
        $result = $data[0];
        $lat = $result['lat'] ?? null;
        $lng = $result['lon'] ?? null;
        if ($lat !== null && $lng !== null) {
            echo json_encode(['success' => true, 'lat' => $lat, 'lng' => $lng, 'query' => $q]);
            exit();
        }
    }
}

// As a last attempt, try progressively shorter locality queries (take last N words)
$parts = preg_split('/[\\s,]+/', $norm);
$n = count($parts);
for ($len = min(5, $n); $len >= 1; $len--) {
    $sub = implode(' ', array_slice($parts, -$len));
    $sub = trim($sub) . ', Malaysia';
    if (in_array($sub, $tried)) continue;
    $tried[] = $sub;

    $endpoint = 'https://nominatim.openstreetmap.org/search?format=json&limit=1&q=' . urlencode($sub);
    $ch = curl_init($endpoint);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 8);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'User-Agent: TestStore/1.0 (contact@example.com)',
        'Accept-Language: en'
    ]);
    $response = curl_exec($ch);
    $httpcode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $curlErr = curl_error($ch);
    curl_close($ch);
    if ($response === false || $httpcode >= 400) continue;
    $data = json_decode($response, true);
    if (is_array($data) && count($data) > 0) {
        $result = $data[0];
        $lat = $result['lat'] ?? null;
        $lng = $result['lon'] ?? null;
        if ($lat !== null && $lng !== null) {
            echo json_encode(['success' => true, 'lat' => $lat, 'lng' => $lng, 'query' => $sub]);
            exit();
        }
    }
}

// no result from any variant
echo json_encode(['success' => false, 'message' => 'No geocode results']);
