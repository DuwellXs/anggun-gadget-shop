<?php
@include 'config.php';
@include 'mail_config.php';
session_start();

if(isset($_POST['submit'])){

   $email = filter_var($_POST['email'], FILTER_SANITIZE_STRING);

   $select = $conn->prepare("SELECT * FROM `users` WHERE email = ?");
   $select->execute([$email]);

   if($select->rowCount() > 0){
       // Generate Token
       $token = md5(rand());
       
       // Set Expiry (1 Hour from now)
       $update = $conn->prepare("UPDATE `users` SET reset_token = ?, reset_expiry = DATE_ADD(NOW(), INTERVAL 1 HOUR) WHERE email = ?");
       $update->execute([$token, $email]);

       if(sendResetEmail($email, $token)){
           $message[] = 'Reset link sent! Check your email.';
       } else {
           $message[] = 'Failed to send email. Try again later.';
       }
   } else {
       $message[] = 'Email not found!';
   }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
   <meta charset="UTF-8">
   <meta name="viewport" content="width=device-width, initial-scale=1.0">
   <title>Forgot Password</title>
   <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.1.1/css/all.min.css">
   <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
   
   <style>
      /* Same Design as Login */
      * { margin: 0; padding: 0; box-sizing: border-box; font-family: 'Poppins', sans-serif; }
      body { min-height: 100vh; display: flex; align-items: center; justify-content: center; position: relative; }
      #myVideo { position: fixed; right: 0; bottom: 0; min-width: 100%; min-height: 100%; width: auto; height: auto; z-index: -1; object-fit: cover; }
      
      .login-container { 
         background: rgba(255, 255, 255, 0.85); backdrop-filter: blur(15px); padding: 50px 40px; 
         border-radius: 20px; width: 100%; max-width: 420px; text-align: center; 
         box-shadow: 0 15px 35px rgba(0,0,0,0.1); border: 1px solid rgba(255,255,255,0.6); 
      }
      .box { width: 100%; padding: 16px; border: 1px solid #e0e0e0; border-radius: 12px; margin-bottom: 15px; outline: none; }
      .box:focus { border-color: #1A4DFF; box-shadow: 0 0 0 4px rgba(26, 77, 255, 0.1); }
      .btn-login { width: 100%; background: #1D1D1F; color: #fff; padding: 16px; border-radius: 12px; font-weight: 600; cursor: pointer; border: none; }
      .message { position: fixed; top: 20px; right: 20px; background: #333; color: white; padding: 10px 20px; border-radius: 5px; z-index: 100; }
   </style>
</head>
<body>

   <video autoplay muted loop id="myVideo">
      <source src="images/Introducing iPhone 15 .mp4" type="video/mp4">
   </video>

   <?php
   if(isset($message)){
      foreach($message as $msg){
         echo '<div class="message"><span>'.$msg.'</span> <i class="fas fa-times" onclick="this.parentElement.remove();"></i></div>';
      }
   }
   ?>

   <div class="login-container">
      <h2 style="margin-bottom: 10px; font-weight: 800; text-transform: uppercase;">Recovery</h2>
      <p style="margin-bottom: 30px; color: #666; font-size: 0.9rem;">Enter your email to receive a reset link.</p>

      <form action="" method="POST">
         <input type="email" name="email" class="box" placeholder="Enter your email" required>
         <button type="submit" name="submit" class="btn-login">Send Link</button>
         
         <div style="margin-top: 20px; font-size: 0.9rem;">
            <a href="login.php" style="color: #1A4DFF; text-decoration: none; font-weight: 600;">Back to Login</a>
         </div>
      </form>
   </div>

</body>
</html>