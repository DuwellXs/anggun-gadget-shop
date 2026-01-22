<?php
@include 'config.php';
session_start();

$delivery_id = $_SESSION['delivery_id'];
if (!isset($delivery_id)) { header('location:login.php'); exit(); }

// --- 1. HANDLE MODE CHANGE ---
if (isset($_POST['change_mode'])) {
    $batch_ids = explode(',', $_POST['batch_ids']);
    $new_mode = $_POST['new_mode'];
    $status_str = 'On Route - ' . $new_mode;
    
    foreach ($batch_ids as $oid) {
        $conn->prepare("UPDATE `orders` SET delivery_status = ? WHERE id = ?")->execute([$status_str, $oid]);
    }
    $message[] = "Batch updated to: $new_mode";
}

// --- 2. HANDLE DELIVERY PROOF (FINAL STEP) ---
if (isset($_POST['upload_proof'])) {
    $order_id = $_POST['order_id'];
    
    if(isset($_FILES['proof_image']) && $_FILES['proof_image']['error'] == 0){
        $img = $_FILES['proof_image']['name'];
        $tmp = $_FILES['proof_image']['tmp_name'];
        $ext = pathinfo($img, PATHINFO_EXTENSION);
        $new_name = "proof_" . $order_id . "_" . time() . "." . $ext;
        $target_dir = 'uploaded_img/';
        
        if (!is_dir($target_dir)) { mkdir($target_dir, 0777, true); }

        if(move_uploaded_file($tmp, $target_dir . $new_name)){
            
            // CHECK CURRENT MODE BEFORE SAVING
            $check_stmt = $conn->prepare("SELECT delivery_status FROM `orders` WHERE id = ?");
            $check_stmt->execute([$order_id]);
            $current_status = $check_stmt->fetchColumn(); 

            // Determine final status based on route mode
            if (strpos($current_status, 'Logistics') !== false) {
                $final_status = 'Delivered (Hub)';
            } else {
                $final_status = 'Delivered (Direct)';
            }

            // SAVE TO DATABASE
            $update = $conn->prepare("UPDATE `orders` SET delivery_image = ?, delivery_status = ? WHERE id = ?");
            $update->execute([$new_name, $final_status, $order_id]);
            
            $message[] = "Order #$order_id Completed!";
        } else {
            $message[] = "Failed to upload image.";
        }
    }
}

function getBatchLocation($address) {
    if (preg_match('/(\d{5})/', $address, $matches)) { return $matches[1]; }
    return 'UNKNOWN ZONE';
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
   <meta charset="UTF-8">
   <meta name="viewport" content="width=device-width, initial-scale=1.0">
   <title>Active Tasks | Rider Panel</title>
   <script src="https://cdn.tailwindcss.com"></script>
   <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800;900&display=swap" rel="stylesheet">
   <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
   <style>
      html, body { margin: 0; padding: 0; height: 100vh; overflow: hidden !important; font-family: 'Inter', sans-serif; background-color: #fff; }
      .master-scroll-wrapper { height: 100vh; display: flex; justify-content: center; }
      .content-container { width: 100%; max-width: 1400px; display: grid; grid-template-columns: 280px 1fr; gap: 40px; padding: 0 30px; height: 100%; }
      .sidebar-scroll { height: 100%; overflow-y: auto; padding: 40px 0; }
      .content-scroll { height: 100%; overflow-y: auto; padding: 40px 0 100px; }

      .batch-container { border: 1px solid #e5e7eb; border-radius: 24px; padding: 30px; margin-bottom: 40px; background: #fff; box-shadow: 0 10px 30px -10px rgba(0,0,0,0.03); }
      .batch-header { display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 30px; padding-bottom: 20px; border-bottom: 1px solid #f3f4f6; }
      .batch-title { font-size: 3rem; font-weight: 900; text-transform: uppercase; color: #111; line-height: 1; letter-spacing: -2px; }
      .batch-sub { font-size: 0.7rem; font-weight: 800; color: #94a3b8; text-transform: uppercase; letter-spacing: 1px; margin-bottom: 5px; }

      .mode-switch { display: flex; background: #f1f5f9; padding: 4px; border-radius: 12px; height: 36px; }
      .mode-opt { padding: 0 16px; font-size: 0.65rem; font-weight: 800; cursor: pointer; border-radius: 8px; text-transform: uppercase; color: #64748b; border: none; background: transparent; transition: 0.2s; }
      .mode-active { background: #000; color: #fff; box-shadow: 0 2px 5px rgba(0,0,0,0.1); }

      .job-item { background: #fff; border: 1px solid #f3f4f6; padding: 25px; border-radius: 20px; margin-bottom: 20px; display: grid; grid-template-columns: 1fr 200px; gap: 30px; align-items: center; box-shadow: 0 4px 6px -1px rgba(0,0,0,0.02); }
      .job-item:hover { border-color: #000; transform: translateY(-2px); transition: 0.2s; }
      
      .job-meta-label { font-size: 0.65rem; font-weight: 800; color: #94a3b8; text-transform: uppercase; margin-bottom: 4px; }
      .job-user { font-size: 1.1rem; font-weight: 800; color: #1e293b; margin-bottom: 5px; }
      .job-addr { font-size: 0.8rem; font-weight: 600; color: #64748b; margin-bottom: 15px; }
      
      .mini-prod { display: flex; gap: 8px; align-items: center; margin-bottom: 4px; }
      .mini-qty { background: #000; color: #4ade80; font-size: 0.6rem; font-weight: 800; padding: 2px 6px; border-radius: 4px; border: 1px solid #4ade80; }
      .mini-name { font-size: 0.75rem; font-weight: 600; color: #475569; }

      .upload-btn { position: relative; overflow: hidden; background: #f8fafc; border: 2px dashed #cbd5e1; color: #64748b; font-weight: 800; font-size: 0.75rem; text-transform: uppercase; text-align: center; padding: 20px; border-radius: 14px; cursor: pointer; transition: 0.2s; display: flex; flex-direction: column; align-items: center; justify-content: center; gap: 8px; }
      .upload-btn:hover { border-color: #16a34a; background: #f0fdf4; color: #16a34a; }
      .upload-btn input { position: absolute; top:0; left:0; width:100%; height:100%; opacity:0; cursor: pointer; }

      @media (max-width: 1024px) { .content-container { grid-template-columns: 1fr; } .sidebar-scroll { display: none; } .job-item { grid-template-columns: 1fr; } }
   </style>
</head>
<body>

<div class="master-scroll-wrapper">
   <div class="content-container">
      <div class="sidebar-scroll"><?php include 'du_header.php'; ?></div>
      <div class="content-scroll">
         
         <div class="mb-12">
            <h1 class="text-4xl font-black text-black uppercase tracking-tighter">My Active Tasks</h1>
            <p class="text-sm font-bold text-gray-400 tracking-wide uppercase mt-1">Upload proof to complete tasks</p>
         </div>

         <?php
            // Keeps the safety-net logic from previous fix
            $query = $conn->prepare("SELECT * FROM `orders` WHERE delivery_rider = ? AND (delivery_status LIKE 'On Route%' OR delivery_status = 'On the way') ORDER BY id DESC");
            $query->execute([$delivery_id]);
            $active_orders = $query->fetchAll(PDO::FETCH_ASSOC);

            if(count($active_orders) > 0) {
                $batches = [];
                foreach($active_orders as $order) {
                    $location = getBatchLocation($order['address']);
                    $batches[$location][] = $order;
                }

                foreach($batches as $location => $orders) {
                    $current_status = $orders[0]['delivery_status'];
                    $current_mode = str_replace('On Route - ', '', $current_status);
                    $batch_ids = implode(',', array_column($orders, 'id'));
         ?>
            
            <div class="batch-container">
                <div class="batch-header">
                    <div>
                        <div class="batch-sub">Delivery Zone</div>
                        <div class="batch-title"><?= htmlspecialchars($location) ?></div>
                    </div>
                    
                    <form action="" method="POST" class="mode-switch">
                        <input type="hidden" name="batch_ids" value="<?= $batch_ids ?>">
                        <button type="submit" name="change_mode" value="1" class="mode-opt <?= $current_mode == 'Direct' ? 'mode-active' : '' ?>" onclick="this.form.appendChild(createHidden('new_mode', 'Direct'))">Direct</button>
                        <button type="submit" name="change_mode" value="1" class="mode-opt <?= $current_mode == 'Logistics' ? 'mode-active' : '' ?>" onclick="this.form.appendChild(createHidden('new_mode', 'Logistics'))">Logistics</button>
                    </form>
                </div>

                <?php foreach($orders as $o): 
                    $products = explode(',', $o['total_products']);
                    $display_id = !empty($o['payment_id']) ? $o['payment_id'] : '#'.$o['id'];
                ?>
                <div class="job-item">
                    <div>
                        <div class="job-meta-label">Ref <?= htmlspecialchars($display_id) ?></div>
                        <div class="job-user"><?= htmlspecialchars($o['name']) ?></div>
                        <div class="job-addr"><i class="fas fa-map-pin mr-1"></i> <?= htmlspecialchars($o['address']) ?></div>
                        <div>
                            <?php foreach($products as $prod): 
                                $qty = "1"; $name = trim($prod);
                                if(preg_match('/(.*)\(Qty:\s*(\d+)\)/i', $prod, $m)) { $name = trim($m[1]); $qty = $m[2]; }
                                elseif(preg_match('/(.*)\((\d+)\)/', $prod, $m)) { $name = trim($m[1]); $qty = $m[2]; }
                            ?>
                            <div class="mini-prod"><span class="mini-qty"><?= $qty ?>x</span><span class="mini-name"><?= htmlspecialchars($name) ?></span></div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                    
                    <form action="" method="POST" enctype="multipart/form-data">
                        <input type="hidden" name="order_id" value="<?= $o['id'] ?>">
                        <input type="hidden" name="upload_proof" value="1"> 
                        <label class="upload-btn">
                            <input type="file" name="proof_image" accept="image/*" required onchange="confirmUpload(this)">
                            <i class="fas fa-camera text-2xl"></i>
                            <span>Delivery Proof</span>
                        </label>
                    </form>
                </div>
                <?php endforeach; ?>
            </div>

         <?php } } else { echo '<div class="py-20 text-center text-gray-400 font-bold uppercase">No active jobs found.</div>'; } ?>

      </div>
   </div>
</div>

<script>
    function createHidden(name, value) {
        let input = document.createElement('input');
        input.type = 'hidden'; input.name = name; input.value = value;
        return input;
    }

    // [FIX] NEW CONFIRMATION FUNCTION
    function confirmUpload(inputElement) {
        if(inputElement.files.length === 0) return;

        if (confirm("Confirm delivery completion? This action cannot be undone.")) {
            inputElement.form.submit();
        } else {
            inputElement.value = ''; // Clear file if cancelled
        }
    }
</script>

<?php if(isset($message)): foreach($message as $msg): ?>
   <div class="fixed bottom-5 right-5 bg-black text-white px-6 py-3 rounded-xl shadow-2xl z-50 text-sm font-bold animate-bounce-in"><?= $msg ?></div>
<?php endforeach; endif; ?>

</body>
</html>