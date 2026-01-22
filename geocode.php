<?php
// Simple server-side geocoding using Nominatim (OpenStreetMap)
// NOTE: For production use a paid geocoding API (Google Maps, Mapbox) or a dedicated Nominatim instance.
function geocode_address_nominatim($address) {
    $address = trim($address);
    if ($address === '') return null;

    $url = 'https://nominatim.openstreetmap.org/search?format=json&limit=1&q=' . urlencode($address);

    // Nominatim requires a valid User-Agent identifying the application. Change this to your app/contact.
    $opts = [
        'http' => [
            'method' => 'GET',
            'header' => "User-Agent: TestStore/1.0 (contact@example.com)\r\nAccept-Language: en\r\n"
        ]
    ];

    $context = stream_context_create($opts);
    $response = @file_get_contents($url, false, $context);
    if ($response === false) return null;

    $data = json_decode($response, true);
    if (!is_array($data) || count($data) === 0) return null;

    return [
        'lat' => isset($data[0]['lat']) ? (float)$data[0]['lat'] : null,
        'lng' => isset($data[0]['lon']) ? (float)$data[0]['lon'] : null,
    ];
}

?>
