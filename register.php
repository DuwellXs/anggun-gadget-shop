<?php
include 'config.php';
include 'mail_config.php'; 

if(isset($_POST['submit'])){

   $name = filter_var($_POST['name'], FILTER_SANITIZE_STRING);
   $email = filter_var($_POST['email'], FILTER_SANITIZE_STRING);

   $add_1 = $_POST['add_1'];
   $add_2 = $_POST['add_2'];
   $city = $_POST['city'];
   $state = $_POST['state'];
   $code = $_POST['code'];
   
   $address = filter_var($add_1 . "," . $add_2 . "," . $city . "," . $state . "," . $code, FILTER_SANITIZE_STRING);

   $pass = md5($_POST['pass']);
   $cpass = md5($_POST['cpass']);
   $phoneNum = filter_var($_POST['num'], FILTER_SANITIZE_STRING);

   $image = filter_var($_FILES['image']['name'], FILTER_SANITIZE_STRING);
   // $image_size = $_FILES['image']['size']; // [MODIFIED] Removed size check variable
   $image_tmp_name = $_FILES['image']['tmp_name'];
   $image_folder = 'uploaded_img/'.$image;

   $select = $conn->prepare("SELECT * FROM `users` WHERE email = ?");
   $select->execute([$email]);

   if($select->rowCount() > 0){
      $message[] = 'User email already exists!';
   } else {
      if($pass != $cpass){
         $message[] = 'Confirm password does not match!';
      } else {
         // [MODIFIED] Removed the "if($image_size > 2000000)" check completely.
         
         // Generate Code
         $verification_code = md5(rand());
         
         $insert = $conn->prepare("INSERT INTO `users`(name, email, address, p_num, password, image, verification_code, is_verified) VALUES(?,?,?,?,?,?,?,0)");
         
         if($insert->execute([$name, $email, $address, $phoneNum, $pass, $image, $verification_code])){
            move_uploaded_file($image_tmp_name, $image_folder);
            
            if(sendVerificationEmail($email, $name, $verification_code)){
                $message[] = 'Registered! Please check your email to verify your account.';
            } else {
                $message[] = 'Registered, but failed to send email. Please contact support.';
            }
         }
      }
   }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
   <meta charset="UTF-8">
   <meta http-equiv="X-UA-Compatible" content="IE=edge">
   <meta name="viewport" content="width=device-width, initial-scale=1.0">
   <title>Register | Anggun Gadget</title>
   
   <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.1.1/css/all.min.css">
   <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">

   <style>
      /* === CONSISTENT DESIGN === */
      * { margin: 0; padding: 0; box-sizing: border-box; font-family: 'Poppins', sans-serif; }
      
      body { 
         min-height: 100vh; 
         display: flex; 
         align-items: center; 
         justify-content: center; 
         padding: 20px; 
         position: relative; 
      }

      #myVideo { 
         position: fixed; right: 0; bottom: 0; 
         min-width: 100%; min-height: 100%; 
         width: auto; height: auto; 
         z-index: -1; object-fit: cover; 
      }

      .register-container { 
         background: rgba(255, 255, 255, 0.85); 
         backdrop-filter: blur(15px); 
         -webkit-backdrop-filter: blur(15px); 
         padding: 40px 40px; 
         border-radius: 20px; 
         width: 100%; 
         max-width: 500px; 
         box-shadow: 0 15px 35px rgba(0,0,0,0.1); 
         text-align: center; 
         border: 1px solid rgba(255,255,255,0.6); 
         margin-top: 50px; margin-bottom: 50px;
      }

      .logo-text { font-size: 1.8rem; font-weight: 800; color: #1D1D1F; margin-bottom: 5px; text-transform: uppercase; letter-spacing: -1px; }
      h3 { font-size: 1.1rem; margin-bottom: 20px; color: #666; font-weight: 500; text-transform: uppercase; letter-spacing: 1px; }

      .input-group { margin-bottom: 12px; text-align: left; }
      
      .box { 
         width: 100%; padding: 14px; 
         border: 1px solid #e0e0e0; 
         border-radius: 12px; 
         background: #fff; 
         font-size: 0.9rem; color: #333; 
         outline: none; transition: 0.3s; 
      }
      .box:focus { border-color: #1A4DFF; box-shadow: 0 0 0 4px rgba(26, 77, 255, 0.1); }
      
      .file-box { padding: 10px; font-size: 0.85rem; }

      .btn-register { 
         width: 100%; background: #1D1D1F; color: #fff; 
         padding: 16px; border-radius: 12px; 
         font-size: 1rem; font-weight: 600; 
         border: none; cursor: pointer; transition: 0.3s; margin-top: 15px; 
         text-transform: uppercase; letter-spacing: 1px;
      }
      .btn-register:hover { background: #abb5daff; transform: translateY(-2px); }

      .links { margin-top: 20px; font-size: 0.9rem; color: #666; }
      .links a { color: #1A4DFF; font-weight: 600; text-decoration: none; }
      .links a:hover { text-decoration: underline; }

      .message { 
         position: fixed; top: 20px; right: 20px; 
         background: #ff4444; color: white; padding: 12px 20px; 
         border-radius: 8px; z-index: 100; 
         box-shadow: 0 5px 15px rgba(0,0,0,0.2); 
         animation: slideIn 0.5s ease;
      }
      @keyframes slideIn { from { transform: translateX(100%); opacity: 0; } to { transform: translateX(0); opacity: 1; } }

      /* [ADDED] Preview Image Styles */
      .img-preview-container {
         display: none; /* Hidden by default */
         margin: 10px auto;
         width: 100px;
         height: 100px;
         border-radius: 50%;
         overflow: hidden;
         border: 3px solid #1A4DFF;
         box-shadow: 0 4px 10px rgba(0,0,0,0.1);
      }
      .img-preview-container img {
         width: 100%;
         height: 100%;
         object-fit: cover;
      }

      ::-webkit-scrollbar { width: 8px; }
      ::-webkit-scrollbar-track { background: transparent; }
      ::-webkit-scrollbar-thumb { background: #ccc; border-radius: 10px; }
      ::-webkit-scrollbar-thumb:hover { background: #999; }
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
   
   <div class="register-container">
      <div class="logo-text">Anggun.</div>
      <h3>Create Account</h3>

      <form action="" enctype="multipart/form-data" method="POST">
         
         <div class="img-preview-container" id="previewBox">
            <img src="" id="imgPreview" alt="Profile Preview">
         </div>

         <div class="input-group">
            <input type="file" name="image" class="box file-box" required accept="image/jpg, image/jpeg, image/png" onchange="previewImage(this)">
         </div>

         <div class="input-group">
            <input type="text" name="name" class="box" placeholder="Full Name" required>
         </div>
         <div class="input-group">
            <input type="email" name="email" class="box" placeholder="Email Address" required>
         </div>
         
         <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 10px;">
             <div class="input-group">
                <input type="text" name="state" class="box" placeholder="State" required>
             </div>
             <div class="input-group">
                <input type="text" name="city" class="box" placeholder="City" required>
             </div>
         </div>
         
         <div class="input-group">
            <input type="text" name="add_1" class="box" placeholder="Address Line 1" required>
         </div>
         <div class="input-group">
            <input type="text" name="add_2" class="box" placeholder="Address Line 2 (Optional)">
         </div>
         
         <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 10px;">
             <div class="input-group">
                <input type="number" min="0" name="code" class="box" placeholder="Postcode" required>
             </div>
             <div class="input-group">
                <input type="text" name="num" class="box" placeholder="Phone Number" required>
             </div>
         </div>

         <div class="input-group">
            <input type="password" name="pass" class="box" placeholder="Password" required>
         </div>
         <div class="input-group">
            <input type="password" name="cpass" class="box" placeholder="Confirm Password" required>
         </div>

         <button type="submit" name="submit" class="btn-register">Register Now</button>

         <div class="links">
            Already have an account? <a href="login.php">Login Now</a>
         </div>
      </form>
   </div>

   <script>
      function previewImage(input) {
         if (input.files && input.files[0]) {
            var reader = new FileReader();
            reader.onload = function (e) {
               document.getElementById('imgPreview').src = e.target.result;
               document.getElementById('previewBox').style.display = 'block';
            };
            reader.readAsDataURL(input.files[0]);
         }
      }
   </script>

</body>
</html>