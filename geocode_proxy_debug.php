<?php
@include 'config.php';
header('Content-Type: application/json');

$address = $_GET['address'] ?? ($_POST['address'] ?? '');
$address = trim($address);
if ($address === '') {
    echo json_encode(['success' => false, 'message' => 'No address provided']);
    exit();
}

$result = [
    'success' => false,
    'address' => $address,
    'php_version' => phpversion(),
    'curl_available' => function_exists('curl_version'),
    'attempts' => []
];

// First: try cURL and capture verbose info
if (function_exists('curl_version')) {
    $endpoint = 'https://nominatim.openstreetmap.org/search?format=json&limit=1&q=' . urlencode($address);
    $ch = curl_init($endpoint);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 10);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'User-Agent: TestStore/1.0 (contact@example.com)',
        'Accept-Language: en'
    ]);
    $response = curl_exec($ch);
    $errno = curl_errno($ch);
    $error = curl_error($ch);
    $httpcode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    $result['attempts'][] = [
        'method' => 'curl',
        'errno' => $errno,
        'error' => $error,
        'http_code' => $httpcode,
        'response_snippet' => $response ? substr($response, 0, 1000) : null
    ];

    if ($response !== false && $httpcode < 400) {
        $data = json_decode($response, true);
        if (is_array($data) && count($data) > 0) {
            $result['success'] = true;
            $result['lat'] = $data[0]['lat'] ?? null;
            $result['lng'] = $data[0]['lon'] ?? null;
            echo json_encode($result);
            exit();
        } else {
            $result['attempts'][] = ['method' => 'curl', 'status' => 'no-results'];
        }
    }
} else {
    $result['attempts'][] = ['method' => 'curl', 'status' => 'unavailable'];
}

// Fallback: file_get_contents with stream context
$opts = [
    'http' => [
        'method' => 'GET',
        'header' => "User-Agent: TestStore/1.0 (contact@example.com)\r\nAccept-Language: en\r\n",
        'timeout' => 10
    ]
];
$context = stream_context_create($opts);
$url = 'https://nominatim.openstreetmap.org/search?format=json&limit=1&q=' . urlencode($address);
$response2 = @file_get_contents($url, false, $context);
if ($response2 !== false) {
    $data2 = json_decode($response2, true);
    $result['attempts'][] = [
        'method' => 'file_get_contents',
        'response_snippet' => substr($response2, 0, 1000),
        'http_code' => null
    ];
    if (is_array($data2) && count($data2) > 0) {
        $result['success'] = true;
        $result['lat'] = $data2[0]['lat'] ?? null;
        $result['lng'] = $data2[0]['lon'] ?? null;
        echo json_encode($result);
        exit();
    } else {
        $result['attempts'][] = ['method' => 'file_get_contents', 'status' => 'no-results'];
    }
} else {
    $result['attempts'][] = ['method' => 'file_get_contents', 'status' => 'failed'];
}

// If still not successful, include last PHP error log entry if readable
$logPath = ini_get('error_log') ?: 'C:\xampp\apache\logs\error.log';
if (file_exists($logPath) && is_readable($logPath)) {
    $lines = file($logPath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    $last = array_slice($lines, -20);
    $result['error_log_tail'] = $last;
}

echo json_encode($result);
