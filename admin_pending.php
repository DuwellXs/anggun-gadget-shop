<?php
@include 'config.php';
session_start();

$admin_id = $_SESSION['admin_id'];
if (!isset($admin_id)) { header('location:login.php'); exit; }

if (isset($_POST['update_order'])) {
    $order_id = $_POST['order_id'];
    $update = filter_var($_POST['update'], FILTER_SANITIZE_STRING);
    if($update){
        $conn->prepare("UPDATE `orders` SET delivery_status = ? WHERE id = ?")->execute([$update, $order_id]);
        $message[] = 'Status updated!';
    }
}

$pending_count = $conn->query("SELECT count(*) FROM orders WHERE delivery_status = 'pending'")->fetchColumn();
$history_count = $conn->query("SELECT count(*) FROM orders WHERE delivery_status LIKE 'Delivered%'")->fetchColumn();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dispatch & History | Admin Panel</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800;900&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        html, body { margin: 0; padding: 0; height: 100vh; overflow: hidden !important; font-family: 'Inter', sans-serif; background-color: #fff; color: #000; }
        .master-scroll-wrapper { height: 100vh; display: flex; justify-content: center; }
        .content-container { width: 100%; max-width: 1400px; display: grid; grid-template-columns: 280px 1fr; gap: 60px; padding: 0 30px; height: 100%; }
        .no-scrollbar::-webkit-scrollbar { display: none; }
        .no-scrollbar { -ms-overflow-style: none; scrollbar-width: none; }
        .sidebar-scroll { height: 100%; overflow-y: auto; padding: 40px 0; }
        .content-scroll { height: 100%; overflow-y: auto; padding: 40px 0 100px; }
        .card { background: #fff; border-radius: 12px; border: 1px solid #f3f4f6; box-shadow: 0 10px 30px -10px rgba(0,0,0,0.05); overflow: hidden; margin-bottom: 40px; }
        .dispatch-table { width: 100%; border-collapse: separate; border-spacing: 0; }
        .dispatch-table th { text-align: left; color: #9ca3af; font-size: 0.65rem; font-weight: 800; text-transform: uppercase; padding: 18px 24px; border-bottom: 2px solid #f3f4f6; background: #fff; }
        .dispatch-table td { padding: 24px; border-bottom: 1px solid #f9fafb; vertical-align: top; }
        .dispatch-table tr:hover td { background: #fafafa; }
        .ag-select-modern { appearance: none; background-color: #fff; border: 1px solid #e5e7eb; border-radius: 8px; padding: 10px; font-size: 0.75rem; font-weight: 700; width: 100%; cursor: pointer; }
        .section-label { font-size: 0.85rem; font-weight: 900; letter-spacing: 0.5px; color: #111; text-transform: uppercase; margin-bottom: 15px; display: flex; align-items: center; gap: 10px; }
        .proof-thumb { width: 50px; height: 50px; border-radius: 8px; border: 1px solid #e5e7eb; object-fit: cover; cursor: zoom-in; }
        .img-modal { display: none; position: fixed; inset: 0; background: rgba(0,0,0,0.9); z-index: 2000; align-items: center; justify-content: center; }
        .img-modal img { max-height: 90vh; max-width: 90vw; border-radius: 8px; }
        .img-close { position: absolute; top: 20px; right: 20px; color: #fff; font-size: 2rem; cursor: pointer; }
        
        .hub-badge { 
            background: #fffbeb; color: #b45309; border: 1px solid #fcd34d; 
            padding: 6px 12px; border-radius: 50px; font-size: 0.65rem; font-weight: 800; 
            text-transform: uppercase; display: inline-flex; align-items: center; gap: 6px;
        }
        
        @media (max-width: 1024px) { .content-container { grid-template-columns: 1fr; } .sidebar-scroll { display: none; } }
    </style>
</head>
<body>

<div class="master-scroll-wrapper">
    <div class="content-container">
        <div class="sidebar-scroll no-scrollbar"><?php include 'admin_header.php'; ?></div>
        <div class="content-scroll no-scrollbar">
            
            <div class="mb-12">
                <h1 class="text-4xl font-black text-black uppercase tracking-tighter">Dispatch Board</h1>
                <div class="flex items-center gap-2 bg-gray-100 px-3 py-1.5 rounded-lg border border-gray-200 mt-2 w-fit">
                    <span class="text-[10px] font-black text-gray-600 uppercase tracking-wider"><?= $pending_count ?> Queue &bull; <?= $history_count ?> Delivered</span>
                </div>
            </div>

            <div class="section-label"><span class="text-yellow-500"><i class="fas fa-inbox"></i></span> Order Queue</div>
            <div class="card">
                <div class="overflow-x-auto no-scrollbar">
                    <table class="dispatch-table">
                        <thead>
                            <tr>
                                <th width="15%">Ref ID</th>
                                <th width="20%">Customer</th>
                                <th width="25%">Summary</th>
                                <th width="15%">Amount</th>
                                <th width="25%">Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            // Order Queue: Sorted by ID ASC (Oldest first for processing)
                            $res = $conn->query("SELECT * FROM `orders` WHERE delivery_status IN ('pending', 'Preparing Order') ORDER BY id ASC");
                            if($res->rowCount() > 0){
                                while($row = $res->fetch(PDO::FETCH_ASSOC)){
                                    $status = $row['delivery_status'];
                                    $display_id = !empty($row['payment_id']) ? $row['payment_id'] : '#'.$row['id'];
                            ?>
                            <tr>
                                <td>
                                    <span class="block text-xs font-black uppercase"><?= $display_id ?></span>
                                    <span class="text-[10px] text-gray-400 font-bold"><?= date('H:i', strtotime($row['placed_on'])) ?></span>
                                </td>
                                <td><div class="font-bold text-sm"><?= $row['name'] ?></div><div class="text-[10px] text-gray-400 font-bold uppercase truncate w-32"><?= $row['address'] ?></div></td>
                                <td class="text-xs text-gray-600 font-medium"><?= $row['total_products'] ?></td>
                                <td class="font-black">RM<?= $row['total_price'] ?></td>
                                <td>
                                    <form action="" method="POST">
                                        <input type="hidden" name="order_id" value="<?= $row['id'] ?>">
                                        <?php if($status == 'pending'): ?>
                                            <button type="submit" name="update" value="Preparing Order" class="w-full bg-yellow-400 hover:bg-yellow-500 text-white font-bold py-2 px-4 rounded-lg text-xs uppercase shadow-lg shadow-yellow-200">Approve</button>
                                        <?php else: ?>
                                            <div class="flex items-center gap-2">
                                                <div class="w-full bg-green-50 text-green-600 border border-green-100 font-bold py-2 px-4 rounded-lg text-[10px] uppercase text-center"><i class="fas fa-check-circle mr-1"></i> Live</div>
                                                <button type="submit" name="update" value="pending" class="text-gray-400 hover:text-red-500"><i class="fas fa-undo"></i></button>
                                            </div>
                                        <?php endif; ?>
                                    </form>
                                </td>
                            </tr>
                            <?php } } else { echo '<tr><td colspan="5" class="text-center py-10 text-gray-400 font-bold text-xs uppercase">Empty</td></tr>'; } ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <div class="section-label"><span class="text-blue-500"><i class="fas fa-history"></i></span> Delivery History</div>
            <div class="card">
                <div class="overflow-x-auto no-scrollbar">
                    <table class="dispatch-table">
                        <thead>
                            <tr>
                                <th width="15%">Ref</th>
                                <th width="20%">Rider</th>
                                <th width="30%">Delivery Location</th>
                                <th width="10%">Status</th>
                                <th width="25%">Proof of Delivery</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            // Fetch all delivered orders - FORCED: ORDER BY o.id DESC (Newest First)
                            $hist_res = $conn->query("SELECT o.*, u.name as rider_name FROM `orders` o LEFT JOIN `users` u ON o.delivery_rider = u.id WHERE o.delivery_status LIKE 'Delivered%' ORDER BY o.id DESC LIMIT 50");
                            if($hist_res->rowCount() > 0){
                                while($hist = $hist_res->fetch(PDO::FETCH_ASSOC)){
                                    $display_id = !empty($hist['payment_id']) ? $hist['payment_id'] : '#'.$hist['id'];
                                    
                                    // CHECK IF HUB DELIVERY
                                    $is_hub = (strpos($hist['delivery_status'], 'Hub') !== false);
                            ?>
                            <tr>
                                <td><span class="block text-xs font-black uppercase"><?= $display_id ?></span></td>
                                <td><div class="font-bold text-sm"><?= $hist['rider_name'] ?? 'Unknown' ?></div></td>
                                
                                <td>
                                    <?php if($is_hub): ?>
                                        <div class="hub-badge">
                                            <i class="fas fa-warehouse"></i> LOGISTICS HUB
                                        </div>
                                    <?php else: ?>
                                        <div class="text-[10px] font-bold text-gray-400 uppercase">Customer Address</div>
                                        <div class="text-xs font-bold text-gray-800 leading-snug"><?= $hist['address'] ?></div>
                                    <?php endif; ?>
                                </td>

                                <td><span class="text-xs font-black text-green-600 uppercase">Delivered</span></td>
                                <td>
                                    <?php if(!empty($hist['delivery_image'])): ?>
                                        <img src="uploaded_img/<?= $hist['delivery_image'] ?>" class="proof-thumb" onclick="openImage(this.src)">
                                    <?php else: ?>
                                        <span class="text-[10px] text-gray-300 font-bold uppercase">No Image</span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                            <?php } } else { echo '<tr><td colspan="5" class="text-center py-10 text-gray-400 font-bold text-xs uppercase">No history</td></tr>'; } ?>
                        </tbody>
                    </table>
                </div>
            </div>

        </div>
    </div>
</div>

<div id="imgModal" class="img-modal" onclick="this.style.display='none'"><span class="img-close">&times;</span><img id="modalImg" src=""></div>
<script> function openImage(src) { document.getElementById('modalImg').src = src; document.getElementById('imgModal').style.display = 'flex'; } </script>
<?php if(isset($message)): foreach($message as $msg): ?><div class="fixed bottom-5 right-5 bg-black text-white px-6 py-3 rounded-xl shadow-2xl z-50 text-sm font-bold animate-bounce-in"><?= $msg ?></div><?php endforeach; endif; ?>
</body>
</html>