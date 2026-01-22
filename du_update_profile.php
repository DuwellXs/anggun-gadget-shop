<?php
@include 'config.php';
session_start();

$delivery_id = $_SESSION['delivery_id'];
if (!isset($delivery_id)) { header('location:login.php'); exit(); }

// --- FETCH CURRENT PROFILE ---
$select_profile = $conn->prepare("SELECT * FROM `users` WHERE id = ?");
$select_profile->execute([$delivery_id]);
$fetch_profile = $select_profile->fetch(PDO::FETCH_ASSOC);

// --- HANDLE UPDATE ---
if (isset($_POST['update_profile'])) {

   $name = filter_var($_POST['name'], FILTER_SANITIZE_STRING);
   $email = filter_var($_POST['email'], FILTER_SANITIZE_STRING);

   $update_profile = $conn->prepare("UPDATE `users` SET name = ?, email = ? WHERE id = ?");
   $update_profile->execute([$name, $email, $delivery_id]);

   // IMAGE UPDATE
   $image = $_FILES['image']['name'];
   $image = filter_var($image, FILTER_SANITIZE_STRING);
   $image_size = $_FILES['image']['size'];
   $image_tmp_name = $_FILES['image']['tmp_name'];
   $image_folder = 'uploaded_img/' . $image;
   $old_image = $_POST['old_image'];

   if (!empty($image)) {
      if ($image_size > 2000000) {
         $message[] = 'Image size is too large!';
      } else {
         $update_image = $conn->prepare("UPDATE `users` SET image = ? WHERE id = ?");
         $update_image->execute([$image, $delivery_id]);
         if ($update_image) {
            move_uploaded_file($image_tmp_name, $image_folder);
            if ($old_image != 'user-icon.png' && file_exists('uploaded_img/' . $old_image)) {
               unlink('uploaded_img/' . $old_image);
            }
            $message[] = 'Profile picture updated!';
         }
      }
   }

   // PASSWORD UPDATE
   $old_pass = $_POST['old_pass'];
   $update_pass = md5($_POST['update_pass']);
   $new_pass = md5($_POST['new_pass']);
   $confirm_pass = md5($_POST['confirm_pass']);

   if (!empty($update_pass) || !empty($new_pass) || !empty($confirm_pass)) {
      if ($update_pass != $old_pass) {
         $message[] = 'Old password not matched!';
      } elseif ($new_pass != $confirm_pass) {
         $message[] = 'Confirm password not matched!';
      } else {
         $update_pass_query = $conn->prepare("UPDATE `users` SET password = ? WHERE id = ?");
         $update_pass_query->execute([$confirm_pass, $delivery_id]);
         $message[] = 'Password updated successfully!';
      }
   } else {
       if(empty($message)) $message[] = 'Profile updated successfully!';
   }
   
   // Refresh Data
   $select_profile->execute([$delivery_id]);
   $fetch_profile = $select_profile->fetch(PDO::FETCH_ASSOC);
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
   <meta charset="UTF-8">
   <meta name="viewport" content="width=device-width, initial-scale=1.0">
   <title>My Profile | Rider Panel</title>

   <script src="https://cdn.tailwindcss.com"></script>
   <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800;900&display=swap" rel="stylesheet">
   <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

   <style>
      /* === NUCLEAR DESIGN SYSTEM === */
      html, body {
         margin: 0; padding: 0;
         height: 100vh; 
         overflow: hidden !important;
         font-family: 'Inter', sans-serif;
         background-color: #ffffff !important;
         color: #000;
      }

      /* LAYOUT */
      .master-scroll-wrapper {
         height: 100vh;
         width: 100%;
         display: flex;
         justify-content: center;
         background-color: #fff;
      }

      .content-container {
         width: 100%;
         max-width: 1400px;
         height: 100%;
         display: grid;
         grid-template-columns: 280px minmax(0, 1fr); 
         gap: 60px;
         padding: 0 30px;
      }

      .sidebar-scroll {
         height: 100%;
         overflow-y: auto;
         padding-top: 40px;
         scrollbar-width: none; 
      }
      .sidebar-scroll::-webkit-scrollbar { display: none; }

      .content-scroll {
         height: 100%;
         overflow-y: auto; 
         padding-top: 40px;
         padding-bottom: 100px;
         scrollbar-width: none; 
      }
      .content-scroll::-webkit-scrollbar { display: none; }

      /* PROFILE CARD */
      .profile-card {
         background: #fff; border-radius: 24px; 
         border: 1px solid #f1f5f9;
         box-shadow: 0 10px 40px -10px rgba(0,0,0,0.05);
         padding: 40px; 
         max-width: 700px;
         margin: 0 auto;
      }

      /* AVATAR */
      .profile-wrapper { 
         position: relative; width: 140px; height: 140px; margin: 0 auto 30px;
         border-radius: 50%; overflow: hidden; 
         border: 4px solid #fff; 
         box-shadow: 0 10px 25px -5px rgba(0,0,0,0.1); 
         background: #f8fafc;
      }
      .profile-wrapper img { width: 100%; height: 100%; object-fit: cover; }
      .upload-overlay {
         position: absolute; inset: 0; background: rgba(0,0,0,0.5);
         display: flex; align-items: center; justify-content: center;
         opacity: 0; transition: 0.2s; cursor: pointer;
      }
      .profile-wrapper:hover .upload-overlay { opacity: 1; }

      /* FORM ELEMENTS */
      .ag-label {
         font-size: 0.7rem; font-weight: 800; color: #94a3b8; 
         text-transform: uppercase; letter-spacing: 1px; margin-bottom: 8px; display: block;
      }
      .ag-input {
         width: 100%; background: #f8fafc; border: 1px solid #e2e8f0;
         border-radius: 12px; padding: 14px 16px; font-size: 0.9rem; font-weight: 600; color: #0f172a;
         transition: 0.2s; outline: none;
      }
      .ag-input:focus { background: #fff; border-color: #000; }

      /* BUTTONS */
      .btn-black {
         background: #000; color: #fff; padding: 14px 30px; border-radius: 12px;
         font-weight: 700; text-transform: uppercase; letter-spacing: 1px; font-size: 0.8rem;
         cursor: pointer; border: none; transition: 0.2s; width: 100%;
      }
      .btn-black:hover { background: #333; transform: translateY(-2px); }

      .btn-cancel {
         display: block; text-align: center; width: 100%; padding: 14px 0;
         color: #94a3b8; font-weight: 700; text-transform: uppercase; letter-spacing: 1px; font-size: 0.75rem;
         transition: 0.2s;
      }
      .btn-cancel:hover { color: #000; }

      .section-divider {
         height: 1px; background: #f1f5f9; margin: 40px 0; position: relative;
      }
      .divider-label {
         position: absolute; top: 50%; left: 50%; transform: translate(-50%, -50%);
         background: #fff; padding: 0 20px; color: #cbd5e1; font-weight: 800; text-transform: uppercase; font-size: 0.7rem; letter-spacing: 1px;
      }

      @media (max-width: 1024px) {
         .content-container { grid-template-columns: 1fr; }
         .sidebar-scroll { display: none; }
      }
   </style>
</head>
<body>

<div class="master-scroll-wrapper">
   <div class="content-container">
      
      <div class="sidebar-scroll">
         <?php include 'du_header.php'; ?>
      </div>

      <div class="content-scroll">
         
         <div class="mb-12 text-center lg:text-left">
            <h1 class="text-4xl font-black text-black uppercase tracking-tighter">My Profile</h1>
            <p class="text-sm font-bold text-gray-400 tracking-wide uppercase mt-1">
               Account Settings & Security
            </p>
         </div>

         <div class="profile-card">
            <form action="" method="post" enctype="multipart/form-data" autocomplete="off">
               
               <div class="profile-wrapper group">
                  <img src="uploaded_img/<?= !empty($fetch_profile['image']) ? $fetch_profile['image'] : 'user-icon.png'; ?>" alt="Profile">
                  <div class="upload-overlay">
                     <i class="fas fa-camera text-white text-2xl"></i>
                  </div>
                  <input type="file" name="image" accept="image/jpg, image/jpeg, image/png" class="absolute inset-0 w-full h-full opacity-0 cursor-pointer" title="Change Profile Picture">
               </div>
               <input type="hidden" name="old_image" value="<?= $fetch_profile['image']; ?>">

               <div class="space-y-6">
                  <div>
                     <label class="ag-label">Rider Name</label>
                     <input type="text" name="name" value="<?= $fetch_profile['name']; ?>" class="ag-input" required>
                  </div>
                  <div>
                     <label class="ag-label">Email Address</label>
                     <input type="email" name="email" value="<?= $fetch_profile['email']; ?>" class="ag-input" required>
                  </div>
               </div>

               <div class="section-divider">
                  <span class="divider-label">Security Zone</span>
               </div>

               <div class="space-y-6 bg-red-50 p-6 rounded-2xl border border-red-100">
                  <input type="hidden" name="old_pass" value="<?= $fetch_profile['password']; ?>">
                  
                  <div>
                     <label class="ag-label text-red-400">Current Password</label>
                     <input type="password" name="update_pass" placeholder="Enter current to verify" class="ag-input" autocomplete="new-password">
                  </div>
                  <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                     <div>
                        <label class="ag-label text-red-400">New Password</label>
                        <input type="password" name="new_pass" placeholder="New Password" class="ag-input" autocomplete="new-password">
                     </div>
                     <div>
                        <label class="ag-label text-red-400">Confirm Password</label>
                        <input type="password" name="confirm_pass" placeholder="Confirm New" class="ag-input" autocomplete="new-password">
                     </div>
                  </div>
                  <p class="text-[10px] text-red-300 font-bold uppercase tracking-wider text-center">* Leave blank if you don't want to change password</p>
               </div>

               <div class="mt-8 pt-4">
                  <input type="submit" value="Save Changes" name="update_profile" class="btn-black">
                  <a href="du_page.php" class="btn-cancel">Cancel & Go Back</a>
               </div>

            </form>
         </div>

      </div>

   </div>
</div>

<script src="js/script.js"></script>

</body>
</html>