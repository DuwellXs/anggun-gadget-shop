<?php
$servername = "localhost";
$username = "root";
$password = ""; // if you set a password for MySQL, put it here
$database = "shop_db";

$conn = new mysqli($servername, $username, $password, $database);

if ($conn->connect_error) {
  die("Connection failed: " . $conn->connect_error);
}
?>
