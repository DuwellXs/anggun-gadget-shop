<?php
@include 'config.php';

echo "<h1>System Repair: Earnings Data</h1>";

// 1. ADD COLUMN IF MISSING
try {
    $conn->query("SELECT 1 FROM `orders` LIMIT 1");
    $conn->query("ALTER TABLE `orders` ADD COLUMN delivery_fee DECIMAL(10,2) DEFAULT 0.00");
    echo "<p style='color:green'>&#10004; Checked/Added 'delivery_fee' column.</p>";
} catch (PDOException $e) {
    echo "<p style='color:green'>&#10004; Column 'delivery_fee' already exists.</p>";
}

// 2. FETCH ALL ORDERS
$stmt = $conn->query("SELECT * FROM `orders`");
$orders = $stmt->fetchAll(PDO::FETCH_ASSOC);

$count = 0;

foreach ($orders as $order) {
    // Only update if fee is 0 or NULL
    if ($order['delivery_fee'] <= 0 || $order['delivery_fee'] == NULL) {
        
        // --- LOGIC FROM DU_ORDER.PHP ---
        $postcode = '00000';
        if (preg_match('/(\d{5})/', $order['address'], $matches)) {
            $postcode = $matches[1];
        }

        $seed = intval(preg_replace('/\D/', '', $postcode));
        if($seed == 0) $seed = 500; 
        $dist = ($seed % 20) + 3; 

        // Default to 'Direct' pricing if mode is unknown
        $price = 5.00 + ($dist * 0.80);
        
        // Check if it was a Logistics order
        if (strpos($order['delivery_status'], 'Logistics') !== false || strpos($order['delivery_status'], 'Hub') !== false) {
            $price = 3.00;
        }

        // UPDATE DATABASE
        $update = $conn->prepare("UPDATE `orders` SET delivery_fee = ? WHERE id = ?");
        $update->execute([$price, $order['id']]);
        $count++;
    }
}

echo "<h2 style='color:blue'>Success! Updated $count past orders.</h2>";
echo "<a href='du_page.php' style='font-size:20px; font-weight:bold;'>&larr; Go Back to Dashboard</a>";
?>