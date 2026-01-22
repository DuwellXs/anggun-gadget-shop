<?php

@include 'config.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = $_POST['email'];
    $email = filter_var($email, FILTER_SANITIZE_EMAIL);

    try {
        $sql = "SELECT user_type FROM users WHERE email = ?";
        $stmt = $conn->prepare($sql);
        $stmt->execute([$email]);

        if ($stmt->rowCount() > 0) {
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            echo $row['user_type']; // Output the user type
        } else {
            echo ''; // No user found
        }
    } catch (PDOException $e) {
        error_log("Database Error: " . $e->getMessage());
        echo ''; // Silence any errors
    }
}
?>
