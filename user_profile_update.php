<?php

@include 'config.php';

session_start();

$user_id = $_SESSION['user_id'];

if(!isset($user_id)){
   header('location:login.php');
   exit;
};

if(isset($_POST['update_profile'])){

   // 1. UPDATE BASIC INFO
   $name = filter_var($_POST['name'], FILTER_SANITIZE_STRING);
   $email = filter_var($_POST['email'], FILTER_SANITIZE_STRING);
   $pNum = filter_var($_POST['num'], FILTER_SANITIZE_STRING);

   // Construct Address
   $add_1 = $_POST['add_1'];
   $add_2 = $_POST['add_2'];
   $city = $_POST['city'];
   $state = $_POST['state'];
   $code = $_POST['code'];
   
   $address = filter_var("$add_1, $add_2, $city, $state, $code", FILTER_SANITIZE_STRING);

   $update_profile = $conn->prepare("UPDATE `users` SET `name` = ?, `email` = ?, `address` = ?, `p_num` = ? WHERE id = ?");
   $update_profile->execute([$name, $email, $address, $pNum ,$user_id]);

   // 2. UPDATE IMAGE
   $image = $_FILES['image']['name'];
   $old_image = $_POST['old_image'];
   
   if(!empty($image)){
      $image = filter_var($image, FILTER_SANITIZE_STRING);
      $image_size = $_FILES['image']['size'];
      $image_tmp_name = $_FILES['image']['tmp_name'];
      $image_folder = 'uploaded_img/'.$image;

      if($image_size > 2000000){
         $message[] = 'Image size is too large!';
      }else{
         $update_image = $conn->prepare("UPDATE `users` SET image = ? WHERE id = ?");
         $update_image->execute([$image, $user_id]);
         if($update_image){
            move_uploaded_file($image_tmp_name, $image_folder);
            if(!empty($old_image) && file_exists('uploaded_img/'.$old_image)){
                unlink('uploaded_img/'.$old_image);
            }
         };
      };
   }

   // 3. UPDATE PASSWORD
   if(!empty($_POST['new_pass'])){
      $old_pass = $_POST['old_pass_hash']; 
      $entered_old_pass = md5($_POST['update_pass']); 
      $new_pass = md5($_POST['new_pass']);
      $confirm_pass = md5($_POST['confirm_pass']);

      if($entered_old_pass != $old_pass){
         $message[] = 'Current password incorrect!';
      }elseif($new_pass != $confirm_pass){
         $message[] = 'New passwords do not match!';
      }else{
         $update_pass_query = $conn->prepare("UPDATE `users` SET password = ? WHERE id = ?");
         $update_pass_query->execute([$confirm_pass, $user_id]);
         $message[] = 'Password updated successfully!';
      }
   } else {
       if(empty($message)) { $message[] = 'Profile updated successfully!'; }
   }
}

?>

<!DOCTYPE html>
<html lang="en">
<head>
   <meta charset="UTF-8">
   <meta name="viewport" content="width=device-width, initial-scale=1.0">
   <title>Update Profile | Anggun Gadget</title>

   <script src="https://cdn.tailwindcss.com"></script>
   <link rel="preconnect" href="https://fonts.googleapis.com">
   <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800;900&display=swap" rel="stylesheet">
   <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
   <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.4.1/font/bootstrap-icons.css">

   <style>
      /* === 1. NUCLEAR WHITE & RESET === */
      html, body {
          margin: 0; padding: 0;
          height: 100vh; 
          overflow: hidden !important;
          font-family: 'Inter', sans-serif;
          background-color: #ffffff !important;
      }

      /* Master Scroll Wrapper (Hides Scrollbar) */
      .master-scroll-wrapper {
          height: 100vh;
          overflow-y: auto;
          scroll-behavior: smooth;
          padding-top: 100px;
          scrollbar-width: none; 
          -ms-overflow-style: none;
          background-color: #ffffff !important;
      }
      .master-scroll-wrapper::-webkit-scrollbar { display: none; }

      /* === 2. TYPOGRAPHY SYSTEM (FROM ABOUT PAGE) === */
      .text-shadow-pop { text-shadow: 2px 2px 0px #cbd5e1; }
      
      .hero-title {
          font-weight: 900; 
          text-transform: uppercase; 
          letter-spacing: -0.05em; 
          color: #000;
          line-height: 0.9;
      }

      .section-title { 
         font-weight: 900; 
         letter-spacing: -0.05em; 
         color: #000; 
         text-transform: uppercase; 
         font-size: 1.2rem;
      }

      .hero-badge {
         display: inline-block;
         background: #000; 
         color: #fff; 
         font-size: 10px; 
         font-weight: 700; 
         letter-spacing: 2px; 
         text-transform: uppercase;
         padding: 6px 14px; 
         border-radius: 99px; 
         margin-bottom: 20px;
      }
      
      .hero-desc {
          font-size: 0.85rem; line-height: 1.6; color: #666; font-weight: 500;
          margin-bottom: 30px;
          border-left: 3px solid #eee; padding-left: 20px; /* Matching Ratings/About style */
      }

      /* === 3. LAYOUT GRID === */
      .content-container {
          max-width: 1200px;
          margin: 0 auto;
          padding: 60px 20px 150px;
          display: grid;
          grid-template-columns: 35% 60%; 
          gap: 5%;
          align-items: start;
          min-height: 100vh;
      }

      .left-sticky-panel {
          position: sticky;
          top: 50px;
          padding-right: 20px;
          z-index: 50;
      }

      .right-card-stack {
          position: relative;
          padding-bottom: 100px;
      }

      /* === 4. COMPONENT STYLES === */
      
      /* Profile Circle */
      .profile-wrapper { 
         position: relative; width: 120px; height: 120px; 
         border-radius: 50%; overflow: hidden; 
         border: 4px solid #f8fafc; box-shadow: 0 10px 20px rgba(0,0,0,0.1); 
         margin-bottom: 25px;
      }
      .profile-wrapper img { width: 100%; height: 100%; object-fit: cover; }
      .upload-overlay {
         position: absolute; inset: 0; background: rgba(0,0,0,0.5);
         display: flex; align-items: center; justify-content: center;
         opacity: 0; transition: 0.2s; cursor: pointer;
      }
      .profile-wrapper:hover .upload-overlay { opacity: 1; }

      /* Cards */
      .stack-card {
          width: 100%;
          background: #fff;
          border-radius: 24px;
          border: 1px solid rgba(0,0,0,0.04);
          box-shadow: 0 20px 60px -10px rgba(0,0,0,0.05); /* Softer shadow like About page */
          margin-bottom: 40px;
          padding: 40px;
          position: relative;
          animation: dealIn 0.6s cubic-bezier(0.2, 0.8, 0.2, 1) forwards;
      }
      @keyframes dealIn { from { opacity: 0; transform: translateY(50px) scale(0.95); } to { opacity: 1; transform: translateY(0) scale(1); } }

      /* Inputs */
      .ag-label {
         font-size: 0.65rem; font-weight: 800;
         color: #94a3b8; text-transform: uppercase; letter-spacing: 1px;
         margin-bottom: 8px; display: block;
      }

      .ag-input {
         width: 100%;
         background: #f8fafc;
         border: 1px solid #e2e8f0;
         border-radius: 12px;
         padding: 16px;
         font-size: 0.9rem;
         font-weight: 600;
         color: #0f172a;
         transition: all 0.2s;
      }
      .ag-input:focus {
         background: #fff;
         border-color: #000;
         outline: none;
         box-shadow: 0 5px 15px rgba(0,0,0,0.05);
      }

      /* Headers inside cards */
      .card-header {
         display: flex; align-items: center; gap: 15px;
         margin-bottom: 30px; padding-bottom: 20px;
         border-bottom: 1px solid #f3f4f6;
      }
      .header-icon {
         width: 40px; height: 40px;
         background: #111; color: #fff;
         border-radius: 12px; display: flex; align-items: center; justify-content: center;
         font-size: 1.1rem;
      }

      /* Buttons (Matching About Page "Browse Shop") */
      .sticky-actions {
          margin-top: 30px;
          padding-top: 20px;
          border-top: 1px solid #eee;
      }
      .btn-save {
          background: #000; color: #fff;
          padding: 16px; border-radius: 12px; width: 100%;
          font-weight: 800; text-transform: uppercase; letter-spacing: 1px; font-size: 0.8rem;
          transition: transform 0.2s; cursor: pointer; border: none;
          box-shadow: 0 10px 30px rgba(0,0,0,0.15);
      }
      .btn-save:hover { transform: translateY(-3px); background: #222; }
      
      .btn-back {
          display: block; text-align: center; margin-top: 20px;
          color: #999; font-size: 0.75rem; font-weight: 800; text-transform: uppercase; letter-spacing: 1px;
          text-decoration: none; transition: color 0.2s;
      }
      .btn-back:hover { color: #000; }

      @media (max-width: 900px) {
         .content-container { display: block; padding: 40px 20px; }
         .left-sticky-panel { position: relative; top: 0; margin-bottom: 50px; padding-right: 0; border-bottom: 1px solid #eee; padding-bottom: 40px; }
         .stack-card { margin-bottom: 20px; padding: 30px 20px; }
      }
   </style>

</head>
<body>
   
<?php include 'header.php'; ?>

<?php 
   $addr_str = $fetch_profile['address'] ?? '';
   $address = explode(",", $addr_str);
   $address = array_pad($address, 5, '');
?>

<div class="master-scroll-wrapper">
   
   <form action="" method="POST" enctype="multipart/form-data" autocomplete="off">
       
       <div class="content-container">
           
           <div class="left-sticky-panel">
               <span class="hero-badge">Account Settings</span>
               
               <h1 class="text-5xl font-black text-black uppercase tracking-tighter mb-4 text-shadow-pop leading-[0.9]">
                   Edit<br>Profile
               </h1>
               
               <p class="hero-desc">Update your personal information, delivery details, and account security settings.</p>

               <div class="profile-wrapper group">
                  <img src="uploaded_img/<?= $fetch_profile['image']; ?>" alt="Profile">
                  <div class="upload-overlay">
                     <i class="fas fa-camera text-white text-2xl"></i>
                  </div>
                  <input type="file" name="image" accept="image/jpg, image/jpeg, image/png" class="absolute inset-0 w-full h-full opacity-0 cursor-pointer">
               </div>
               <input type="hidden" name="old_image" value="<?= $fetch_profile['image']; ?>">
               
               <div class="space-y-5 mb-8">
                  <div>
                     <label class="ag-label">Full Name</label>
                     <input type="text" name="name" value="<?= htmlspecialchars($fetch_profile['name']); ?>" class="ag-input" required>
                  </div>
                  <div>
                     <label class="ag-label">Email</label>
                     <input type="email" name="email" value="<?= htmlspecialchars($fetch_profile['email']); ?>" class="ag-input" required>
                  </div>
                  <div>
                     <label class="ag-label">Phone</label>
                     <input type="text" name="num" value="<?= htmlspecialchars($fetch_profile['p_num']); ?>" class="ag-input" required>
                  </div>
               </div>

               <div class="sticky-actions">
                  <input type="submit" value="Save Changes" name="update_profile" class="btn-save">
                  <a href="home.php" class="btn-back">Cancel & Go Back</a>
               </div>
           </div>

           <div class="right-card-stack">
               
               <div class="stack-card" style="animation-delay: 0.1s;">
                  <div class="card-header">
                     <div class="header-icon"><i class="fas fa-map-marker-alt"></i></div>
                     <div>
                        <h3 class="section-title text-[1rem] mb-0">Delivery Address</h3>
                        <p class="text-xs text-gray-400 font-bold mt-1 uppercase tracking-wider">Where we ship your gear</p>
                     </div>
                  </div>
                  
                  <div class="grid grid-cols-1 gap-6">
                     <div>
                        <label class="ag-label">Address Line 1</label>
                        <input type="text" name="add_1" value="<?= htmlspecialchars($address[0]); ?>" class="ag-input" placeholder="Unit / House Number" required>
                     </div>
                     <div>
                        <label class="ag-label">Address Line 2</label>
                        <input type="text" name="add_2" value="<?= htmlspecialchars($address[1]); ?>" class="ag-input" placeholder="Street Name / Area" required>
                     </div>
                     
                     <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                        <div>
                           <label class="ag-label">City</label>
                           <input type="text" name="city" value="<?= htmlspecialchars($address[2]); ?>" class="ag-input" required>
                        </div>
                        <div>
                           <label class="ag-label">State</label>
                           <input type="text" name="state" value="<?= htmlspecialchars($address[3]); ?>" class="ag-input" required>
                        </div>
                        <div>
                           <label class="ag-label">Postcode</label>
                           <input type="number" name="code" value="<?= htmlspecialchars(trim($address[4])); ?>" class="ag-input" required>
                        </div>
                     </div>
                  </div>
               </div>

               <div class="stack-card" style="animation-delay: 0.2s;">
                  <div class="card-header">
                     <div class="header-icon bg-red-50 text-red-500"><i class="fas fa-lock"></i></div>
                     <div>
                        <h3 class="section-title text-[1rem] mb-0 text-red-500">Security Zone</h3>
                        <p class="text-xs text-red-300 font-bold mt-1 uppercase tracking-wider">Password Management</p>
                     </div>
                  </div>
                  
                  <input type="hidden" name="old_pass_hash" value="<?= $fetch_profile['password']; ?>">
                  
                  <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                     <div class="md:col-span-2">
                        <label class="ag-label">Current Password</label>
                        <input type="password" name="update_pass" placeholder="Enter current to verify" class="ag-input" autocomplete="new-password">
                     </div>
                     <div>
                        <label class="ag-label">New Password</label>
                        <input type="password" name="new_pass" placeholder="New Password" class="ag-input" autocomplete="new-password">
                     </div>
                     <div>
                        <label class="ag-label">Confirm Password</label>
                        <input type="password" name="confirm_pass" placeholder="Confirm New" class="ag-input" autocomplete="new-password">
                     </div>
                  </div>
                  <p class="text-[10px] text-gray-400 mt-6 font-black uppercase tracking-widest">* Only fill these if you want to change your password</p>
               </div>

           </div>
       </div>
   </form>

   <?php include 'footer.php'; ?>

</div>

<script src="js/script.js"></script>

</body>
</html>