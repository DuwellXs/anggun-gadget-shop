<?php
@include 'config.php';
session_start();

// FIX: Sync Timezone
date_default_timezone_set('Asia/Kuala_Lumpur');

$delivery_id = $_SESSION['delivery_id'];
if (!isset($delivery_id)) { header('location:login.php'); exit(); }
?>

<!DOCTYPE html>
<html lang="en">
<head>
   <meta charset="UTF-8">
   <meta name="viewport" content="width=device-width, initial-scale=1.0">
   <title>History | Rider Panel</title>

   <script src="https://cdn.tailwindcss.com"></script>
   <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800;900&display=swap" rel="stylesheet">
   <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

   <style>
      /* === NUCLEAR DESIGN SYSTEM (Admin Standard) === */
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
         grid-template-columns: 280px 1fr; 
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

      /* CARD & TABLE */
      .card { 
         background: #fff; border-radius: 12px; 
         border: 1px solid #f3f4f6; 
         box-shadow: 0 10px 30px -10px rgba(0,0,0,0.05); 
         overflow: hidden; margin-bottom: 40px; 
      }

      .dispatch-table { width: 100%; border-collapse: separate; border-spacing: 0; }
      .dispatch-table th { 
         text-align: left; color: #9ca3af; font-size: 0.65rem; font-weight: 800; 
         text-transform: uppercase; padding: 18px 24px; border-bottom: 2px solid #f3f4f6; 
         background: #fff; 
      }
      .dispatch-table td { padding: 24px; border-bottom: 1px solid #f9fafb; vertical-align: top; }
      .dispatch-table tr:hover td { background: #fafafa; }

      /* BADGES */
      .hub-badge { 
         background: #fffbeb; color: #b45309; border: 1px solid #fcd34d; 
         padding: 6px 12px; border-radius: 50px; font-size: 0.65rem; font-weight: 800; 
         text-transform: uppercase; display: inline-flex; align-items: center; gap: 6px;
      }

      .proof-thumb { 
         width: 50px; height: 50px; border-radius: 8px; 
         border: 1px solid #e5e7eb; object-fit: cover; cursor: zoom-in; 
      }

      .img-modal { display: none; position: fixed; inset: 0; background: rgba(0,0,0,0.9); z-index: 2000; align-items: center; justify-content: center; }
      .img-modal img { max-height: 90vh; max-width: 90vw; border-radius: 8px; }
      .img-close { position: absolute; top: 20px; right: 20px; color: #fff; font-size: 2rem; cursor: pointer; }

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
         
         <div class="mb-12">
            <h1 class="text-4xl font-black text-black uppercase tracking-tighter">History</h1>
            <p class="text-sm font-bold text-gray-400 tracking-wide uppercase mt-1">Past Delivery Records</p>
         </div>

         <div class="card">
            <div class="overflow-x-auto">
               <table class="dispatch-table">
                  <thead>
                     <tr>
                        <th width="15%">Date & Time</th>
                        <th width="15%">Ref ID</th>
                        <th width="40%">Delivery Details</th>
                        <th width="15%">Proof</th>
                        <th width="15%">Status</th>
                     </tr>
                  </thead>
                  <tbody>
                     <?php
                        $select_orders = $conn->prepare("SELECT * FROM `orders` WHERE delivery_status LIKE ? AND delivery_rider = ? ORDER BY id DESC");
                        $select_orders->execute(['Delivered%', $delivery_id]);
                        
                        if ($select_orders->rowCount() > 0) {
                           while ($fetch_orders = $select_orders->fetch(PDO::FETCH_ASSOC)) {
                              $is_hub = (strpos($fetch_orders['delivery_status'], 'Hub') !== false);
                     ?>
                     <tr>
                        <td>
                           <div class="font-bold text-gray-900 text-sm"><?= date('d M Y', strtotime($fetch_orders['placed_on'])); ?></div>
                           <div class="text-xs text-gray-500 mt-1 font-bold"><?= date('h:i A', strtotime($fetch_orders['placed_on'])); ?></div>
                        </td>

                        <td>
                           <span class="bg-gray-100 text-gray-600 px-2 py-1 rounded text-xs font-mono font-bold border border-gray-200">
                               <?= htmlspecialchars($fetch_orders['payment_id']); ?>
                           </span>
                        </td>

                        <td>
                           <div class="font-bold text-gray-900 text-sm"><?= htmlspecialchars($fetch_orders['name']); ?></div>
                           
                           <?php if($is_hub): ?>
                              <div class="mt-1"><span class="hub-badge"><i class="fas fa-warehouse"></i> LOGISTICS HUB</span></div>
                           <?php else: ?>
                              <div class="text-xs text-gray-600 mt-1 leading-relaxed">
                                 <?= htmlspecialchars($fetch_orders['address']); ?>
                              </div>
                           <?php endif; ?>
                        </td>

                        <td>
                           <?php if(!empty($fetch_orders['delivery_image'])): ?>
                              <img src="uploaded_img/<?= $fetch_orders['delivery_image']; ?>" class="proof-thumb" onclick="openImage(this.src)">
                           <?php else: ?>
                              <span class="text-[10px] text-gray-300 font-bold uppercase">No Img</span>
                           <?php endif; ?>
                        </td>

                        <td>
                           <span class="text-xs font-black text-green-600 uppercase bg-green-50 px-2 py-1 rounded-full border border-green-100">
                              Delivered
                           </span>
                        </td>
                     </tr>
                     <?php
                           }
                        } else {
                           echo '<tr><td colspan="5" class="text-center py-20 text-gray-400 font-bold uppercase tracking-widest text-xs">No completed jobs yet</td></tr>';
                        }
                     ?>
                  </tbody>
               </table>
            </div>
         </div>

      </div>

   </div>
</div>

<div id="imgModal" class="img-modal" onclick="this.style.display='none'"><span class="img-close">&times;</span><img id="modalImg" src=""></div>
<script> 
   function openImage(src) { 
      document.getElementById('modalImg').src = src; 
      document.getElementById('imgModal').style.display = 'flex'; 
   } 
</script>

<script src="js/script.js"></script>

</body>
</html>