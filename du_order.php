<?php
@include 'config.php';
session_start();

$delivery_id = $_SESSION['delivery_id'];
if (!isset($delivery_id)) { header('location:login.php'); exit(); }

// --- HANDLE BATCH ACCEPT ---
if (isset($_POST['accept_batch'])) {
    $order_ids = explode(',', $_POST['batch_ids']); 
    $mode = $_POST['delivery_mode']; // 'Direct' or 'Logistics'
    
    // [FIX] REVERTED TO 'On Route' WITH MODE
    // This ensures du_your_order.php can see it AND determines the proof logic (Hub vs Direct)
    $status_string = 'On Route - ' . $mode;

    foreach ($order_ids as $oid) {
        $update = $conn->prepare("UPDATE `orders` SET delivery_rider = ?, delivery_status = ? WHERE id = ?");
        $update->execute([$delivery_id, $status_string, $oid]);
    }
    
    $message[] = "Zone Accepted! Proceed to Active Tasks.";
    header('location:du_your_order.php'); 
    exit();
}

// --- HELPER: EXTRACT POSTCODE ONLY ---
function getBatchLocation($address) {
    if (preg_match('/(\d{5})/', $address, $matches)) {
        return $matches[1];
    }
    return 'UNKNOWN ZONE';
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
   <meta charset="UTF-8">
   <meta name="viewport" content="width=device-width, initial-scale=1.0">
   <title>Job Market | Rider Panel</title>

   <script src="https://cdn.tailwindcss.com"></script>
   <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800;900&display=swap" rel="stylesheet">
   <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

   <style>
      /* === NUCLEAR DESIGN SYSTEM === */
      html, body {
         margin: 0; padding: 0; height: 100vh; 
         overflow: hidden !important; font-family: 'Inter', sans-serif;
         background-color: #ffffff !important; color: #000;
      }
      .master-scroll-wrapper { height: 100vh; display: flex; justify-content: center; background-color: #fff; }
      .content-container { width: 100%; max-width: 1400px; display: grid; grid-template-columns: 280px 1fr; gap: 40px; padding: 0 30px; height: 100%; }
      .sidebar-scroll { height: 100%; overflow-y: auto; padding: 40px 0; scrollbar-width: none; }
      .content-scroll { height: 100%; overflow-y: auto; padding: 40px 0 100px; scrollbar-width: none; }

      .market-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(380px, 1fr)); gap: 30px; }

      /* --- BATCH CARD --- */
      .batch-card {
         background: #fff; border-radius: 24px; border: 1px solid #f3f4f6;
         box-shadow: 0 15px 40px -10px rgba(0,0,0,0.05); padding: 0;
         display: flex; flex-direction: column; overflow: hidden;
         transition: transform 0.2s;
      }
      .batch-card:hover { transform: translateY(-5px); border-color: #000; }

      .zone-header { 
          padding: 25px; background: #000; color: #fff;
          display: flex; justify-content: space-between; align-items: flex-start;
      }
      .zone-title { font-size: 2.5rem; font-weight: 900; line-height: 1; letter-spacing: -2px; }
      .zone-label { font-size: 0.65rem; font-weight: 800; text-transform: uppercase; color: #94a3b8; margin-bottom: 2px; }
      .zone-badge { background: #fff; color: #000; font-size: 0.7rem; font-weight: 800; padding: 6px 12px; border-radius: 8px; }

      .order-scroll-area {
          padding: 25px; max-height: 400px; overflow-y: auto; background: #fff;
      }

      /* ORDER ITEM DESIGN */
      .order-item { 
          margin-bottom: 20px; padding-bottom: 20px; border-bottom: 1px dashed #e2e8f0;
      }
      .order-item:last-child { border-bottom: none; margin-bottom: 0; padding-bottom: 0; }
      
      .customer-ref { font-size: 0.75rem; font-weight: 800; color: #94a3b8; text-transform: uppercase; margin-bottom: 8px; letter-spacing: 0.5px; }
      .customer-id { color: #000; font-weight: 900; }
      
      /* QUANTITY DESIGN */
      .prod-row { 
          display: flex; align-items: flex-start; gap: 10px; margin-bottom: 6px;
      }
      .qty-badge {
          background: #000; color: #4ade80; /* Neon Green Text */
          font-size: 0.7rem; font-weight: 800; padding: 4px 8px; border-radius: 6px;
          min-width: 35px; text-align: center; border: 1px solid #4ade80;
      }
      .prod-name {
          font-size: 0.85rem; font-weight: 700; color: #1e293b; line-height: 1.4;
      }

      /* FOOTER & ACTIONS */
      .card-footer {
          padding: 20px 25px; background: #f8fafc; border-top: 1px solid #f1f5f9;
          display: flex; flex-direction: column; gap: 10px;
      }
      
      .btn-mode {
          width: 100%; padding: 14px; border-radius: 12px; border: 2px solid transparent;
          cursor: pointer; display: flex; justify-content: space-between; align-items: center;
          transition: 0.2s; text-align: left;
      }
      .btn-direct { background: #f0fdf4; border-color: #dcfce7; color: #166534; }
      .btn-direct:hover { background: #16a34a; border-color: #16a34a; color: #fff; }
      
      .btn-logis { background: #fff; border-color: #e2e8f0; color: #64748b; }
      .btn-logis:hover { background: #cbd5e1; border-color: #94a3b8; color: #1e293b; }

      .earn-val { font-weight: 900; font-size: 1rem; }
      .mode-lbl { font-size: 0.7rem; font-weight: 800; text-transform: uppercase; display: block; }

      @media (max-width: 1024px) {
         .content-container { grid-template-columns: 1fr; padding: 0 20px; }
         .sidebar-scroll { display: none; }
      }
   </style>
</head>
<body>

<div class="master-scroll-wrapper">
   <div class="content-container">
      <div class="sidebar-scroll"><?php include 'du_header.php'; ?></div>
      <div class="content-scroll">
         
         <div class="mb-10">
            <h1 class="text-4xl font-black text-black uppercase tracking-tighter">Job Market</h1>
            <p class="text-sm font-bold text-gray-400 tracking-wide uppercase mt-1">Batched by Delivery Postcode</p>
         </div>

         <div class="market-grid">
            <?php
               $query = $conn->query("SELECT * FROM `orders` WHERE (delivery_status = 'Preparing Order' OR delivery_status = 'On the way') AND (delivery_rider = 0 OR delivery_rider IS NULL) ORDER BY id DESC");
               $all_orders = $query->fetchAll(PDO::FETCH_ASSOC);

               $batches = [];
               foreach ($all_orders as $order) {
                   $postcode = getBatchLocation($order['address']);
                   $batches[$postcode][] = $order;
               }

               if (count($batches) > 0) {
                   foreach ($batches as $postcode => $orders) {
                       $count = count($orders);
                       $batch_ids = implode(',', array_column($orders, 'id'));
                       $batch_key = md5($postcode); 
                       
                       $seed = intval(preg_replace('/\D/', '', $postcode));
                       if($seed == 0) $seed = 500; 
                       $dist = ($seed % 20) + 3; 
                       
                       $total_direct = 0; $total_logis = 0;
                       foreach($orders as $o) {
                           $total_direct += 5.00 + ($dist * 0.80);
                           $total_logis += 3.00;
                       }
            ?>
            
            <div class="batch-card">
               <div class="zone-header">
                   <div>
                       <div class="zone-label">Delivery Zone</div>
                       <div class="zone-title"><?= htmlspecialchars($postcode) ?></div>
                   </div>
                   <div class="zone-badge"><?= $count ?> ORDERS</div>
               </div>

               <div class="order-scroll-area">
                   <?php foreach($orders as $o): 
                       $products = explode(',', $o['total_products']);
                       $display_id = !empty($o['payment_id']) ? $o['payment_id'] : '#'.$o['id'];
                   ?>
                   <div class="order-item">
                       <div class="customer-ref">
                           <span class="customer-id"><?= htmlspecialchars($display_id) ?></span> &bull; <?= htmlspecialchars($o['name']) ?>
                       </div>
                       
                       <?php foreach($products as $prod): 
                           $qty = "1"; $name = trim($prod);
                           if(preg_match('/(.*)\(Qty:\s*(\d+)\)/i', $prod, $m)) { $name = trim($m[1]); $qty = $m[2]; }
                           elseif(preg_match('/(.*)\((\d+)\)/', $prod, $m)) { $name = trim($m[1]); $qty = $m[2]; }
                       ?>
                           <div class="prod-row">
                               <div class="qty-badge"><?= $qty ?>x</div>
                               <div class="prod-name"><?= htmlspecialchars($name) ?></div>
                           </div>
                       <?php endforeach; ?>
                   </div>
                   <?php endforeach; ?>
               </div>

               <form action="" method="POST" class="card-footer">
                   <input type="hidden" name="batch_ids" value="<?= $batch_ids ?>">
                   <input type="hidden" name="delivery_mode" id="mode_input_<?= $batch_key ?>" value="">

                   <button type="submit" name="accept_batch" onclick="document.getElementById('mode_input_<?= $batch_key ?>').value='Direct'" class="btn-mode btn-direct">
                       <div><span class="mode-lbl">Direct to Customer</span></div>
                       <span class="earn-val">RM<?= number_format($total_direct, 2) ?></span>
                   </button>

                   <button type="submit" name="accept_batch" onclick="document.getElementById('mode_input_<?= $batch_key ?>').value='Logistics'" class="btn-mode btn-logis">
                       <div><span class="mode-lbl">Logistics Drop-off</span></div>
                       <span class="earn-val">RM<?= number_format($total_logis, 2) ?></span>
                   </button>
               </form>
            </div>

            <?php
                   }
               } else {
                  echo '<div class="col-span-full py-20 text-center text-gray-400 font-bold uppercase">No Jobs Available</div>';
               }
            ?>
         </div>

      </div>
   </div>
</div>
</body>
</html>