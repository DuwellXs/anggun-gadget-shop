<?php
// Safe migration runner for adding latitude/longitude to orders and creating rider_locations table.
// Run this once from the browser or CLI. It will check existing schema before applying changes.

@include 'config.php';

if (!isset($conn)) {
    die('DB connection not available.');
}

try {
    // Check for columns and add them if missing
    $has_lat = false; $has_lng = false;
    $stmt = $conn->prepare("SELECT COLUMN_NAME FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'orders'");
    $stmt->execute();
    $cols = $stmt->fetchAll(PDO::FETCH_COLUMN);
    foreach ($cols as $c) {
        if ($c === 'latitude') $has_lat = true;
        if ($c === 'longitude') $has_lng = true;
    }

    if (!$has_lat) {
        echo "Adding column latitude to orders...<br>";
        $conn->exec("ALTER TABLE `orders` ADD COLUMN `latitude` DOUBLE NULL AFTER `address`");
    } else {
        echo "Column latitude already exists.<br>";
    }

    if (!$has_lng) {
        echo "Adding column longitude to orders...<br>";
        $conn->exec("ALTER TABLE `orders` ADD COLUMN `longitude` DOUBLE NULL AFTER `latitude`");
    } else {
        echo "Column longitude already exists.<br>";
    }

    // Create rider_locations table if missing
    $conn->exec("CREATE TABLE IF NOT EXISTS `rider_locations` (
        `rider_id` INT NOT NULL PRIMARY KEY,
        `lat` DOUBLE NOT NULL,
        `lng` DOUBLE NOT NULL,
        `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");

    echo "Migration finished. Verify schema in your DB.<br>";
} catch (Exception $e) {
    echo 'Migration error: ' . htmlspecialchars($e->getMessage()) . '<br>';
}

?>
