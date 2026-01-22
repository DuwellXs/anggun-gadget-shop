<?php
@include 'config.php';
session_start();

$rider_id = $_SESSION['rider_id'];

if(!isset($rider_id)){
   header('location:login.php');
   exit();
};

// --- UPDATE ORDER STATUS LOGIC ---
if(isset($_POST['update_order'])){
   $order_id = $_POST['order_id'];
   $update_status = $_POST['update_status'];

   $update_query = $conn->prepare("UPDATE `orders` SET delivery_status = ? WHERE id = ?");
   $update_query->execute([$update_status, $order_id]);
   $message[] = 'Order status updated to: ' . $update_status;
}

// --- FETCH DATA FOR STATS ---
$select_active = $conn->prepare("SELECT * FROM `orders` WHERE delivery_status = ? OR delivery_status = ?");
$select_active->execute(['Preparing Order', 'On the way']);
$number_of_active = $select_active->rowCount();

$select_completed = $conn->prepare("SELECT * FROM `orders` WHERE delivery_status = ?");
$select_completed->execute(['Completed']);
$number_of_completed = $select_completed->rowCount();

// --- FETCH PROFILE ---
$select_profile = $conn->prepare("SELECT * FROM `users` WHERE id = ?");
$select_profile->execute([$rider_id]);
$fetch_profile = $select_profile->fetch(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
   <meta charset="UTF-8">
   <meta name="viewport" content="width=device-width, initial-scale=1.0">
   <title>Rider Dashboard | Anggun Gadget</title>

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

      /* MASTER LAYOUT */
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

      /* SIDEBAR */
      .sidebar-scroll {
         height: 100%;
         overflow-y: auto;
         padding-top: 40px;
         scrollbar-width: none; 
         border-right: 1px solid #f1f5f9;
      }
      .sidebar-scroll::-webkit-scrollbar { display: none; }

      /* CONTENT */
      .content-scroll {
         height: 100%;
         overflow-y: auto; 
         padding-top: 40px;
         padding-bottom: 100px;
         scrollbar-width: none; 
      }
      .content-scroll::-webkit-scrollbar { display: none; }

      /* --- COMPONENTS --- */
      
      /* STAT CARDS */
      .stat-grid {
         display: grid;
         grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
         gap: 25px;
         margin-bottom: 50px;
      }
      .stat-card {
         background: #fff; border-radius: 20px; padding: 30px;
         border: 1px solid #f3f4f6;
         box-shadow: 0 10px 30px -10px rgba(0,0,0,0.05);
         display: flex; flex-direction: column; justify-content: space-between;
         transition: transform 0.2s;
      }
      .stat-card:hover { transform: translateY(-5px); border-color: #000; }
      .stat-value { font-size: 3rem; font-weight: 900; line-height: 1; letter-spacing: -2px; margin-bottom: 5px; }
      .stat-label { font-size: 0.75rem; font-weight: 800; text-transform: uppercase; color: #94a3b8; letter-spacing: 1px; }

      /* TASK GRID */
      .task-grid {
         display: grid; grid-template-columns: repeat(auto-fit, minmax(350px, 1fr)); gap: 30px;
      }
      .task-card {
         background: #fff; border-radius: 24px; padding: 30px;
         border: 1px solid #f1f5f9; box-shadow: 0 4px 6px -1px rgba(0,0,0,0.02);
         position: relative; overflow: hidden;
      }
      .task-header { display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 20px; }
      .order-id { font-size: 1.2rem; font-weight: 900; color: #000; }
      .order-date { font-size: 0.75rem; font-weight: 600; color: #94a3b8; }
      
      .info-row { display: flex; align-items: center; gap: 10px; margin-bottom: 12px; }
      .info-icon { width: 24px; text-align: center; color: #cbd5e1; }
      .info-text { font-size: 0.9rem; font-weight: 600; color: #334155; }
      
      /* BADGES */
      .status-badge {
         font-size: 0.7rem; font-weight: 800; text-transform: uppercase;
         padding: 6px 12px; border-radius: 50px; display: inline-block; margin-top: 15px;
      }
      .status-orange { background: #fff7ed; color: #c2410c; border: 1px solid #ffedd5; }
      .status-blue { background: #eff6ff; color: #1d4ed8; border: 1px solid #dbeafe; }

      /* BUTTONS */
      .btn-action {
         width: 100%; padding: 14px; border-radius: 12px; font-weight: 800;
         text-transform: uppercase; font-size: 0.8rem; letter-spacing: 1px;
         margin-top: 20px; transition: 0.2s; cursor: pointer; border: none;
      }
      .btn-pickup { background: #000; color: #fff; }
      .btn-pickup:hover { background: #333; }
      .btn-complete { background: #16a34a; color: #fff; }
      .btn-complete:hover { background: #15803d; }

      /* SIDEBAR NAV */
      .nav-item {
         display: flex; align-items: center; gap: 15px; padding: 14px 20px;
         border-radius: 16px; font-size: 0.8rem; font-weight: 700;
         color: #94a3b8; text-transform: uppercase; transition: 0.2s;
         text-decoration: none; margin-bottom: 5px;
      }
      .nav-item:hover, .nav-item.active { background: #f8fafc; color: #000; }
      .nav-item.active { background: #000; color: #fff; }

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
         <div class="px-6 mb-10">
            <span class="inline-block bg-blue-600 text-white text-[10px] font-black px-3 py-1 rounded-full uppercase tracking-widest mb-3">Rider Panel</span>
            <h1 class="text-2xl font-black uppercase tracking-tighter leading-none">Anggun<br><span class="text-gray-300">Logistics</span></h1>
         </div>

         <nav class="px-4">
            <a href="rider_page.php" class="nav-item active">
               <i class="fas fa-home w-5"></i> Dashboard
            </a>
            <div class="mt-8 pt-6 border-t border-gray-100">
               <div class="flex items-center gap-4 px-4 mb-6">
                  <img src="uploaded_img/<?= $fetch_profile['image']; ?>" class="w-10 h-10 rounded-full object-cover border border-gray-200">
                  <div>
                     <p class="text-xs font-black uppercase text-slate-900"><?= $fetch_profile['name']; ?></p>
                     <p class="text-[10px] font-bold text-slate-400">Rider ID: #<?= $rider_id ?></p>
                  </div>
               </div>
               <a href="logout.php" onclick="return confirm('Logout?');" class="nav-item text-red-400 hover:text-red-600 hover:bg-red-50">
                  <i class="fas fa-sign-out-alt w-5"></i> Logout
               </a>
            </div>
         </nav>
      </div>

      <div class="content-scroll">
         
         <div class="mb-12">
            <h1 class="text-4xl font-black text-black uppercase tracking-tighter">Dashboard</h1>
            <p class="text-sm font-bold text-gray-400 tracking-wide uppercase mt-1">
               Overview & Assignments
            </p>
         </div>

         <div class="stat-grid">
            <div class="stat-card">
               <div>
                  <div class="stat-value"><?= $number_of_active; ?></div>
                  <div class="stat-label text-blue-500">Active Tasks</div>
               </div>
               <div class="mt-4 h-1 w-full bg-blue-100 rounded-full"><div class="h-full bg-blue-500 w-1/2 rounded-full"></div></div>
            </div>
            <div class="stat-card">
               <div>
                  <div class="stat-value"><?= $number_of_completed; ?></div>
                  <div class="stat-label text-green-500">Completed Jobs</div>
               </div>
               <div class="mt-4 h-1 w-full bg-green-100 rounded-full"><div class="h-full bg-green-500 w-3/4 rounded-full"></div></div>
            </div>
         </div>

         <div class="mb-8 flex justify-between items-end">
             <h2 class="text-xl font-black text-slate-900 uppercase">Available Tasks</h2>
             <span class="text-xs font-bold bg-black text-white px-2 py-1 rounded">Live Feed</span>
         </div>

         <div class="task-grid">
            <?php
               $select_orders = $conn->prepare("SELECT * FROM `orders` WHERE delivery_status = ? OR delivery_status = ?");
               $select_orders->execute(['Preparing Order', 'On the way']);

               if($select_orders->rowCount() > 0){
                  while($fetch_orders = $select_orders->fetch(PDO::FETCH_ASSOC)){
            ?>
            <div class="task-card">
               
               <div class="task-header">
                  <div>
                      <div class="order-id">#<?= $fetch_orders['id']; ?></div>
                      <div class="order-date"><?= $fetch_orders['placed_on']; ?></div>
                  </div>
                  <div class="text-right">
                      <div class="text-lg font-black text-slate-900">RM<?= $fetch_orders['total_price']; ?></div>
                      <div class="text-[10px] font-bold text-slate-400 uppercase"><?= $fetch_orders['method']; ?></div>
                  </div>
               </div>

               <div class="info-row">
                   <i class="fas fa-user info-icon"></i>
                   <span class="info-text"><?= $fetch_orders['name']; ?></span>
               </div>
               <div class="info-row">
                   <i class="fas fa-map-marker-alt info-icon"></i>
                   <span class="info-text truncate"><?= $fetch_orders['address']; ?></span>
               </div>

               <div class="mt-4">
                   <?php if($fetch_orders['delivery_status'] == 'Preparing Order'){ ?>
                      <span class="status-badge status-orange">Ready for Pickup</span>
                   <?php } elseif($fetch_orders['delivery_status'] == 'On the way'){ ?>
                      <span class="status-badge status-blue">In Transit</span>
                   <?php } ?>
               </div>

               <form action="" method="POST">
                  <input type="hidden" name="order_id" value="<?= $fetch_orders['id']; ?>">
                  
                  <?php if($fetch_orders['delivery_status'] == 'Preparing Order'){ ?>
                     <input type="hidden" name="update_status" value="On the way">
                     <button type="submit" name="update_order" class="btn-action btn-pickup">
                        Accept & Pickup <i class="fas fa-box-open ml-2"></i>
                     </button>
                  <?php } elseif($fetch_orders['delivery_status'] == 'On the way'){ ?>
                     <input type="hidden" name="update_status" value="Completed">
                     <button type="submit" name="update_order" class="btn-action btn-complete">
                        Mark Delivered <i class="fas fa-check ml-2"></i>
                     </button>
                  <?php } ?>
               </form>

            </div>
            <?php
                  }
               }else{
                  echo '
                  <div class="col-span-full py-20 text-center border-2 border-dashed border-gray-200 rounded-2xl">
                      <i class="fas fa-box-open text-4xl text-gray-300 mb-4"></i>
                      <p class="text-xs font-bold text-gray-400 uppercase">No active deliveries found</p>
                  </div>';
               }
            ?>
         </div>

      </div>

   </div>
</div>

<?php if(isset($message)): ?>
<script>
   setTimeout(() => {
      document.querySelectorAll('.message').forEach(el => el.remove());
   }, 3000);
</script>
<?php endif; ?>

</body>
</html>