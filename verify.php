<?php
@include 'config.php';

if(isset($_GET['email']) && isset($_GET['code'])){
    $email = filter_var($_GET['email'], FILTER_SANITIZE_STRING);
    $code = filter_var($_GET['code'], FILTER_SANITIZE_STRING);

    $stmt = $conn->prepare("SELECT * FROM `users` WHERE email = ? AND verification_code = ?");
    $stmt->execute([$email, $code]);

    if($stmt->rowCount() > 0){
        $update = $conn->prepare("UPDATE `users` SET is_verified = 1, verification_code = '' WHERE email = ?");
        $update->execute([$email]);
        
        echo "<script>alert('Account verified! You can now login.'); window.location.href='login.php';</script>";
    } else {
        echo "<script>alert('Invalid or expired verification link.'); window.location.href='login.php';</script>";
    }
} else {
    header('location:login.php');
}
?>