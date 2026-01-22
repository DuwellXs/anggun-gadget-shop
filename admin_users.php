<?php

@include 'config.php';

session_start();

$admin_id = $_SESSION['admin_id'];

if(!isset($admin_id)){
   header('location:login.php');
   exit();
};

// 1. HANDLE USER DELETION
if(isset($_GET['delete'])){
   $delete_id = $_GET['delete'];
   
   if($delete_id == $admin_id){
       $message[] = 'Security Alert: You cannot delete your own account!';
   } else {
       $delete_users = $conn->prepare("DELETE FROM `users` WHERE id = ?");
       $delete_users->execute([$delete_id]);
       
       $conn->prepare("DELETE FROM `cart` WHERE user_id = ?")->execute([$delete_id]);
       $conn->prepare("DELETE FROM `wishlist` WHERE user_id = ?")->execute([$delete_id]);
       
       header('location:admin_users.php');
   }
}

// Fetch Users by Role
function getUsersByRole($conn, $role) {
    $stmt = $conn->prepare("SELECT * FROM `users` WHERE user_type = ? ORDER BY id DESC");
    $stmt->execute([$role]);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

$admins = getUsersByRole($conn, 'admin');
$riders = getUsersByRole($conn, 'delivery');
$customers = getUsersByRole($conn, 'user');

?>

<!DOCTYPE html>
<html lang="en">
<head>
   <meta charset="UTF-8">
   <meta name="viewport" content="width=device-width, initial-scale=1.0">
   <title>User Roles | Admin Panel</title>

   <script src="https://cdn.tailwindcss.com"></script>
   <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800;900&display=swap" rel="stylesheet">
   <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.1.1/css/all.min.css">

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
         padding-bottom: 40px;
         scrollbar-width: none; 
      }
      .sidebar-scroll::-webkit-scrollbar { display: none; }

      .content-scroll {
         height: 100%;
         overflow-y: auto;
         padding-top: 40px;
         padding-bottom: 100px;
         padding-right: 5px;
         scrollbar-width: none; 
      }
      .content-scroll::-webkit-scrollbar { display: none; }

      /* --- DISTINCT CARD STYLING --- */
      .user-grid {
         display: grid;
         grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
         gap: 24px;
      }

      .user-card {
         background: #fff;
         border: 1px solid #f3f4f6;
         border-radius: 12px;
         padding: 24px;
         box-shadow: 0 4px 6px -1px rgba(0,0,0,0.02);
         transition: all 0.2s ease;
         position: relative;
         display: flex;
         align-items: center;
         justify-content: space-between;
         overflow: hidden;
      }
      .user-card:hover {
         transform: translateY(-3px);
         box-shadow: 0 20px 40px -10px rgba(0,0,0,0.08);
      }

      /* ROLE SPECIFIC CARDS (The Distinction Logic) */
      .card-admin {
          background: #fff7ed; /* Orange Tint */
          border: 1px solid #ffedd5;
          border-left: 4px solid #f97316; /* Strong Left Border */
      }
      .card-rider {
          background: #eff6ff; /* Blue Tint */
          border: 1px solid #dbeafe;
          border-left: 4px solid #3b82f6; /* Strong Left Border */
      }
      .card-customer {
          background: #fff;
          border: 1px solid #f3f4f6;
          border-left: 4px solid #cbd5e1; /* Neutral Left Border */
      }

      /* INFO STYLING */
      .user-avatar { 
         width: 48px; height: 48px; border-radius: 10px; object-fit: cover; 
         border: 1px solid rgba(0,0,0,0.05); background: #fff;
      }
      
      /* SECTION HEADERS */
      .section-label {
          font-size: 0.85rem; font-weight: 900; letter-spacing: 0.5px; color: #111;
          text-transform: uppercase; margin-bottom: 20px; display: flex; align-items: center; gap: 12px;
          margin-top: 60px; padding-bottom: 10px; border-bottom: 2px solid #f3f4f6;
      }
      .section-label:first-of-type { margin-top: 0; }
      
      .badge-count {
          background: #000; color: #fff; font-size: 0.65rem; padding: 3px 8px; 
          border-radius: 50px; font-weight: 800;
      }

      /* ACTIONS */
      .btn-delete {
         width: 32px; height: 32px; display: flex; align-items: center; justify-content: center;
         background: #fff; border: 1px solid #fee2e2; color: #dc2626; border-radius: 8px;
         cursor: pointer; transition: 0.2s; font-size: 0.8rem;
         flex-shrink: 0;
      }
      .btn-delete:hover { background: #dc2626; color: #fff; border-color: #dc2626; }
      
      .btn-locked {
          width: 32px; height: 32px; display: flex; align-items: center; justify-content: center;
          background: rgba(0,0,0,0.05); border: 1px solid transparent; color: #9ca3af; border-radius: 8px;
          cursor: not-allowed; flex-shrink: 0;
      }

      /* CORNER BADGES */
      .role-tag {
          position: absolute; top: 0; right: 0;
          font-size: 0.55rem; font-weight: 800; text-transform: uppercase;
          padding: 4px 10px; border-bottom-left-radius: 8px; letter-spacing: 0.5px;
      }
      .tag-admin { background: #f97316; color: #fff; }
      .tag-rider { background: #3b82f6; color: #fff; }
      .tag-user { background: #f1f5f9; color: #64748b; }

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
         <?php include 'admin_header.php'; ?>
      </div>

      <div class="content-scroll">
         
         <div class="mb-12">
            <h1 class="text-4xl font-black text-black uppercase tracking-tighter">User Roles</h1>
            <div class="flex justify-between items-end mt-2">
               <p class="text-sm font-bold text-gray-400 tracking-wide uppercase">
                  Manage Account Permissions
               </p>
               <div class="flex items-center gap-2 bg-green-50 px-3 py-1.5 rounded-lg border border-green-100">
                  <span class="relative flex h-2 w-2">
                    <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-green-400 opacity-75"></span>
                    <span class="relative inline-flex rounded-full h-2 w-2 bg-green-500"></span>
                  </span>
                  <span class="text-[10px] font-black text-green-600 uppercase tracking-wider">
                     <?= count($admins) + count($riders) + count($customers) ?> Active Users
                  </span>
               </div>
            </div>
         </div>

         <div class="section-label border-orange-100 text-orange-950">
             <i class="fas fa-shield-alt text-orange-500"></i> Administrators 
             <span class="badge-count bg-orange-500 text-white"><?= count($admins) ?></span>
         </div>
         <div class="user-grid">
            <?php foreach($admins as $user): ?>
            <div class="user-card card-admin">
               <div class="role-tag tag-admin">Admin</div>
               <div class="flex items-center gap-4">
                  <img src="uploaded_img/<?= !empty($user['image']) ? $user['image'] : 'user-icon.png'; ?>" class="user-avatar shadow-sm ring-2 ring-orange-100" alt="">
                  <div>
                     <h3 class="text-sm font-black text-gray-900"><?= htmlspecialchars($user['name']); ?></h3>
                     <p class="text-xs font-bold text-orange-600/70 mt-0.5"><?= htmlspecialchars($user['email']); ?></p>
                     <p class="text-[10px] font-mono text-gray-400 mt-1">ID: #<?= $user['id'] ?></p>
                  </div>
               </div>
               <div>
                   <?php if($user['id'] == $admin_id): ?>
                       <div class="btn-locked" title="Protected Account"><i class="fas fa-lock"></i></div>
                   <?php else: ?>
                       <a href="admin_users.php?delete=<?= $user['id']; ?>" onclick="return confirm('Delete this admin?');" class="btn-delete"><i class="fas fa-trash"></i></a>
                   <?php endif; ?>
               </div>
            </div>
            <?php endforeach; ?>
         </div>

         <div class="section-label border-blue-100 text-blue-950">
             <i class="fas fa-motorcycle text-blue-500"></i> Delivery Fleet 
             <span class="badge-count bg-blue-500 text-white"><?= count($riders) ?></span>
         </div>
         <div class="user-grid">
            <?php if(empty($riders)) echo '<p class="text-xs text-gray-400 font-bold uppercase italic pl-1">No riders registered.</p>'; ?>
            <?php foreach($riders as $user): ?>
            <div class="user-card card-rider">
               <div class="role-tag tag-rider">Rider</div>
               <div class="flex items-center gap-4">
                  <img src="uploaded_img/<?= !empty($user['image']) ? $user['image'] : 'user-icon.png'; ?>" class="user-avatar shadow-sm ring-2 ring-blue-100" alt="">
                  <div>
                     <h3 class="text-sm font-black text-gray-900"><?= htmlspecialchars($user['name']); ?></h3>
                     <p class="text-xs font-bold text-blue-600/70 mt-0.5"><?= htmlspecialchars($user['email']); ?></p>
                     <p class="text-[10px] font-mono text-gray-400 mt-1">ID: #<?= $user['id'] ?></p>
                  </div>
               </div>
               <a href="admin_users.php?delete=<?= $user['id']; ?>" onclick="return confirm('Delete this rider?');" class="btn-delete"><i class="fas fa-trash"></i></a>
            </div>
            <?php endforeach; ?>
         </div>

         <div class="section-label border-gray-100 text-gray-900">
             <i class="fas fa-users text-gray-400"></i> Customers 
             <span class="badge-count bg-gray-900 text-white"><?= count($customers) ?></span>
         </div>
         <div class="user-grid pb-20">
            <?php if(empty($customers)) echo '<p class="text-xs text-gray-400 font-bold uppercase italic pl-1">No customers found.</p>'; ?>
            <?php foreach($customers as $user): ?>
            <div class="user-card card-customer">
               <div class="role-tag tag-user">User</div>
               <div class="flex items-center gap-4">
                  <img src="uploaded_img/<?= !empty($user['image']) ? $user['image'] : 'user-icon.png'; ?>" class="user-avatar" alt="">
                  <div>
                     <h3 class="text-sm font-black text-gray-900"><?= htmlspecialchars($user['name']); ?></h3>
                     <p class="text-xs font-bold text-gray-400 mt-0.5"><?= htmlspecialchars($user['email']); ?></p>
                     <p class="text-[10px] font-mono text-gray-300 mt-1">ID: #<?= $user['id'] ?></p>
                  </div>
               </div>
               <a href="admin_users.php?delete=<?= $user['id']; ?>" onclick="return confirm('Delete this customer?');" class="btn-delete"><i class="fas fa-trash"></i></a>
            </div>
            <?php endforeach; ?>
         </div>

      </div>

   </div>
</div>

<script src="js/script.js"></script>

</body>
</html>