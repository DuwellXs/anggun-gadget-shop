<?php
@include 'config.php';
session_start();

$redirect_url = isset($_GET['redirect']) ? $_GET['redirect'] : 'home.php';

if(isset($_POST['submit'])){

   $email = filter_var($_POST['email'], FILTER_SANITIZE_STRING);
   $pass = md5($_POST['pass']); 
   
   // Default to 'user' if not specified
   $role = isset($_POST['role']) ? $_POST['role'] : 'user'; 

   // Map 'rider' selection to database value 'delivery'
   $db_role = ($role == 'rider') ? 'delivery' : $role;

   $sql = "SELECT * FROM `users` WHERE email = ? AND password = ? AND user_type = ?";
   $stmt = $conn->prepare($sql);
   $stmt->execute([$email, $pass, $db_role]);
   
   if($stmt->rowCount() > 0){
      $row = $stmt->fetch(PDO::FETCH_ASSOC);

      if($row['is_verified'] == 0 && $role == 'user'){
          $message[] = 'Please verify your email before logging in!';
      } else {
          if($role == 'admin'){
             $_SESSION['admin_id'] = $row['id'];
             header('location:admin_page.php');
          } elseif($role == 'rider'){
             $_SESSION['delivery_id'] = $row['id']; 
             header('location:du_page.php');
          } else {
             $_SESSION['user_id'] = $row['id'];
             $target = $_POST['redirect_target'] ?: 'home.php';
             header('location:' . $target);
          }
          exit();
      }
   } else {
      $message[] = 'Incorrect email, password, or role!';
   }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
   <meta charset="UTF-8">
   <meta name="viewport" content="width=device-width, initial-scale=1.0">
   <title>Anggun Gadget | Sign In</title>
   <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.1.1/css/all.min.css">
   <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
   
   <style>
      * { margin: 0; padding: 0; box-sizing: border-box; font-family: 'Poppins', sans-serif; }
      body { min-height: 100vh; display: flex; align-items: center; justify-content: center; overflow: hidden; position: relative; }
      #myVideo { position: fixed; right: 0; bottom: 0; min-width: 100%; min-height: 100%; width: auto; height: auto; z-index: -1; object-fit: cover; }
      .login-container { background: rgba(255, 255, 255, 0.85); backdrop-filter: blur(15px); -webkit-backdrop-filter: blur(15px); padding: 50px 40px; border-radius: 20px; width: 100%; max-width: 420px; box-shadow: 0 15px 35px rgba(0,0,0,0.1); text-align: center; border: 1px solid rgba(255,255,255,0.6); }
      .logo-text { font-size: 1.8rem; font-weight: 800; color: #1D1D1F; margin-bottom: 10px; text-transform: uppercase; letter-spacing: -1px; }
      
      /* Hidden Toggle Header */
      .header-area { display: flex; align-items: center; justify-content: center; gap: 8px; margin-bottom: 25px; }
      .login-container h3 { font-size: 1.2rem; color: #666; font-weight: 500; margin: 0; }
      .staff-trigger { font-size: 0.8rem; color: #ccc; cursor: pointer; transition: 0.3s; }
      .staff-trigger:hover { color: #1D1D1F; }

      /* The Hidden Role Selector */
      .role-selector { display: none; background: #f0f0f0; border-radius: 12px; padding: 4px; margin-bottom: 25px; animation: fadeIn 0.3s ease; }
      .role-option { flex: 1; padding: 10px; cursor: pointer; border-radius: 10px; font-size: 0.85rem; font-weight: 600; color: #666; transition: 0.3s; }
      .role-option.active { background: #fff; color: #1D1D1F; box-shadow: 0 2px 10px rgba(0,0,0,0.08); }
      
      .input-group { margin-bottom: 15px; text-align: left; }
      .box { width: 100%; padding: 16px; border: 1px solid #e0e0e0; border-radius: 12px; background: #fff; font-size: 0.95rem; color: #333; outline: none; transition: 0.3s; }
      .box:focus { border-color: #1A4DFF; box-shadow: 0 0 0 4px rgba(26, 77, 255, 0.1); }
      .btn-login { width: 100%; background: #1D1D1F; color: #fff; padding: 16px; border-radius: 12px; font-size: 1rem; font-weight: 600; border: none; cursor: pointer; transition: 0.3s; margin-top: 10px; }
      .btn-login:hover { background: #1A4DFF; transform: translateY(-2px); }
      .links { margin-top: 25px; font-size: 0.9rem; color: #666; }
      .links a { color: #1A4DFF; font-weight: 600; text-decoration: none; }
      .links a:hover { text-decoration: underline; }
      .message { position: fixed; top: 20px; right: 20px; background: #ff4444; color: white; padding: 12px 20px; border-radius: 8px; z-index: 100; box-shadow: 0 5px 15px rgba(0,0,0,0.2); }
      
      @keyframes fadeIn { from { opacity: 0; transform: translateY(-5px); } to { opacity: 1; transform: translateY(0); } }
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
      <div class="logo-text">Anggun.</div>
      
      <div class="header-area">
         <h3 id="login-sub">Welcome Back</h3>
         <i class="fas fa-lock staff-trigger" onclick="toggleStaffMode()" title="Staff Login"></i>
      </div>

      <div class="role-selector" id="roleBox">
         <div class="role-option active" onclick="setRole('user', this)">Customer</div>
         <div class="role-option" onclick="setRole('admin', this)">Seller</div>
         <div class="role-option" onclick="setRole('rider', this)">Rider</div>
      </div>

      <form action="" method="POST">
         <input type="hidden" name="role" id="role_input" value="user">
         <input type="hidden" name="redirect_target" value="<?= htmlspecialchars($redirect_url) ?>">

         <div class="input-group">
            <input type="email" name="email" class="box" placeholder="Email address" required>
         </div>
         
         <div class="input-group">
            <input type="password" name="pass" class="box" placeholder="Password" required>
         </div>

         <div style="text-align: right; margin-bottom: 10px; margin-top: -10px;">
            <a href="forgot_password.php" style="color: #444; font-size: 0.85rem; font-weight: 500; text-decoration: none; transition: 0.3s;" onmouseover="this.style.color='#1A4DFF'" onmouseout="this.style.color='#444'">
               Forgot Password?
            </a>
         </div>

         <button type="submit" name="submit" class="btn-login">Sign In</button>

         <div class="links">
            New here? <a href="register.php">Create an account</a>
         </div>
      </form>
   </div>

   <script>
      function toggleStaffMode() {
         const roleBox = document.getElementById('roleBox');
         if (roleBox.style.display === 'flex') {
            roleBox.style.display = 'none';
            // Reset to user if closed
            setRole('user', document.querySelector('.role-option')); 
         } else {
            roleBox.style.display = 'flex';
         }
      }

      function setRole(role, element) {
         document.getElementById('role_input').value = role;
         document.querySelectorAll('.role-option').forEach(el => el.classList.remove('active'));
         if(element) element.classList.add('active');
         
         let sub = "Welcome Back";
         if(role === 'admin') sub = "Seller Dashboard";
         if(role === 'rider') sub = "Rider Portal";
         document.getElementById('login-sub').innerText = sub;
      }
   </script>

</body>
</html>