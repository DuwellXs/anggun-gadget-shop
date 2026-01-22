<?php
@include 'config.php';
session_start();

if(isset($_GET['email']) && isset($_GET['token'])){
    $email = filter_var($_GET['email'], FILTER_SANITIZE_STRING);
    $token = filter_var($_GET['token'], FILTER_SANITIZE_STRING);

    // Validate Token and Expiry
    $check = $conn->prepare("SELECT * FROM `users` WHERE email = ? AND reset_token = ? AND reset_expiry > NOW()");
    $check->execute([$email, $token]);

    if($check->rowCount() > 0){
        // Token Valid, Handle Submit
        if(isset($_POST['submit'])){
            $pass = md5($_POST['pass']);
            $cpass = md5($_POST['cpass']);

            if($pass != $cpass){
                $message[] = 'Passwords do not match!';
            } else {
                // Update Password & Clear Token
                $update = $conn->prepare("UPDATE `users` SET password = ?, reset_token = NULL, reset_expiry = NULL WHERE email = ?");
                $update->execute([$pass, $email]);
                
                echo "<script>alert('Password updated successfully!'); window.location.href='login.php';</script>";
            }
        }
    } else {
        echo "<script>alert('Invalid or expired link.'); window.location.href='login.php';</script>";
        exit();
    }
} else {
    header('location:login.php');
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
   <meta charset="UTF-8">
   <meta name="viewport" content="width=device-width, initial-scale=1.0">
   <title>Reset Password</title>
   <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
   
   <style>
      /* Reuse Login Styles */
      * { margin: 0; padding: 0; box-sizing: border-box; font-family: 'Poppins', sans-serif; }
      body { min-height: 100vh; display: flex; align-items: center; justify-content: center; position: relative; }
      #myVideo { position: fixed; right: 0; bottom: 0; min-width: 100%; min-height: 100%; width: auto; height: auto; z-index: -1; object-fit: cover; }
      .login-container { background: rgba(255, 255, 255, 0.85); backdrop-filter: blur(15px); padding: 50px 40px; border-radius: 20px; width: 100%; max-width: 420px; text-align: center; border: 1px solid rgba(255,255,255,0.6); }
      .box { width: 100%; padding: 16px; border: 1px solid #e0e0e0; border-radius: 12px; margin-bottom: 15px; outline: none; }
      .btn-login { width: 100%; background: #1D1D1F; color: #fff; padding: 16px; border-radius: 12px; font-weight: 600; cursor: pointer; border: none; }
      .message { position: fixed; top: 20px; right: 20px; background: #ff4444; color: white; padding: 10px 20px; border-radius: 5px; }
   </style>
</head>
<body>

   <video autoplay muted loop id="myVideo">
      <source src="images/Introducing iPhone 15 .mp4" type="video/mp4">
   </video>
   
   <?php if(isset($message)){ foreach($message as $msg){ echo '<div class="message">'.$msg.'</div>'; } } ?>

   <div class="login-container">
      <h2 style="margin-bottom: 20px; font-weight: 800; text-transform: uppercase;">New Password</h2>
      
      <form action="" method="POST">
         <input type="password" name="pass" class="box" placeholder="New Password" required>
         <input type="password" name="cpass" class="box" placeholder="Confirm Password" required>
         <button type="submit" name="submit" class="btn-login">Update Password</button>
      </form>
   </div>

</body>
</html>