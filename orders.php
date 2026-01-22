<?php
@include 'config.php';
session_start();

$user_id = $_SESSION['user_id'] ?? null;

if (!$user_id) {
    header('location:login.php');
    exit();
}

// [1] PRE-FETCH PRODUCTS
$product_map = [];
$stmt_pmap = $conn->prepare("SELECT id, name, image FROM products");
$stmt_pmap->execute();
while($row = $stmt_pmap->fetch(PDO::FETCH_ASSOC)){
    $product_map[trim($row['name'])] = $row;
}

// [2] FETCH & SORT ORDERS
$orders_preparing = [];
$orders_delivery = [];
$orders_completed = [];
$latest_order = null; 

$select_orders = $conn->prepare("
    SELECT o.*, u.name AS rider_name 
    FROM `orders` o
    LEFT JOIN `users` u ON o.delivery_rider = u.id
    WHERE o.user_id = ? 
    ORDER BY o.id DESC
");
$select_orders->execute([$user_id]);

$counter = 0;
while ($order = $select_orders->fetch(PDO::FETCH_ASSOC)) {
    if ($counter === 0) { $latest_order = $order; }
    $counter++;

    // [FIX] STRICT STATUS CHECKING
    
    $status = strtolower($order['delivery_status']);
    $order['item_count'] = count(explode(',', $order['total_products']));

    // 1. PROCESSING TAB
    if (in_array($status, ['preparing order', 'pending', 'confirmed'])) {
        $orders_preparing[] = $order;
    } 
    // 2. DELIVERY TAB
    elseif (in_array($status, ['on the way', 'out for delivery']) || strpos($status, 'on route') !== false) {
        $orders_delivery[] = $order;
    } 
    // 3. HISTORY TAB (Strictly completed/ended orders only)
    // [UPDATED] Added 'delivered (direct)' and 'delivered (hub)' to history logic
    elseif (in_array($status, ['completed', 'cancelled', 'refunded', 'delivered']) || strpos($status, 'delivered') !== false) {
        $orders_completed[] = $order;
    }
}

// --- HELPER FUNCTIONS ---
function getOrderImage($order_str, $map) {
    $raw_items = explode(',', $order_str);
    $first_item = $raw_items[0] ?? '';
    $clean_name = preg_replace('/(\s\(Qty:\s\d+\)$)|(\s\[.*?\])|(\s\(\d+\)$)/', '', trim($first_item));
    if(isset($map[trim($clean_name)])) {
        $img = $map[trim($clean_name)]['image'];
        return (strpos($img, 'http') === 0) ? $img : 'uploaded_img/' . $img;
    }
    return 'images/no-image.jpg';
}

function parseProductString($str) {
    $name = $str;
    $variant = '';
    $qty = '1';
    if (preg_match('/\(Qty:\s*(\d+)\)$/', $str, $matches)) {
        $qty = $matches[1];
        $str = trim(str_replace($matches[0], '', $str));
    }
    if (preg_match('/\[(.*?)\]$/', $str, $matches)) {
        $variant = $matches[1];
        $str = trim(str_replace($matches[0], '', $str));
    }
    return ['name' => $str, 'variant' => $variant, 'qty' => $qty];
}

function getFirstProductId($order_str, $map) {
    $raw_items = explode(',', $order_str);
    $first_item = $raw_items[0] ?? '';
    $clean_name = preg_replace('/(\s\(Qty:\s\d+\)$)|(\s\[.*?\])|(\s\(\d+\)$)/', '', trim($first_item));
    return $map[trim($clean_name)]['id'] ?? null;
}

function getOrderItemsJson($order_str, $map) {
    $raw_items = explode(',', $order_str);
    $items_data = [];
    foreach($raw_items as $item_str){
        $clean_name = preg_replace('/(\s\(Qty:\s\d+\)$)|(\s\[.*?\])|(\s\(\d+\)$)/', '', trim($item_str));
        $clean_name = trim($clean_name);
        if(isset($map[$clean_name])){
            $items_data[] = [
                'id' => $map[$clean_name]['id'],
                'name' => $map[$clean_name]['name'],
                'image' => $map[$clean_name]['image']
            ];
        }
    }
    return htmlspecialchars(json_encode($items_data), ENT_QUOTES, 'UTF-8');
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Order Menu | Anggun Gadget</title>
    
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;600;700;800;900&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <link rel="stylesheet" href="css/style.css">

    <style>
        html, body {
            margin: 0; padding: 0;
            height: 100vh;
            overflow: hidden; 
            font-family: 'Inter', sans-serif;
            background-color: #ffffff !important;
        }

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

        .content-container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 80px 20px 100px;
            min-height: 80vh;
            display: grid;
            grid-template-columns: 35% 60%;
            gap: 5%;
            align-items: start;
        }

        .left-sticky-panel {
            position: sticky;
            top: 40px; 
            padding-right: 20px;
        }

        .hero-badge {
            background: #000; color: #fff; 
            font-size: 0.7rem; font-weight: 700; letter-spacing: 2px; text-transform: uppercase;
            padding: 6px 14px; border-radius: 50px; margin-bottom: 20px; display: inline-block;
        }

        .hero-title {
            font-size: 3.5rem; font-weight: 900; line-height: 1; color: #111;
            text-transform: uppercase; letter-spacing: -1px; margin-bottom: 15px;
        }
        
        .text-shadow-pop { text-shadow: 2px 2px 0px #cbd5e1; }

        .hero-desc {
            font-size: 0.9rem; line-height: 1.6; color: #666; font-weight: 600;
            border-left: 3px solid #ddd; padding-left: 20px; margin-bottom: 40px;
        }

        .nav-stack { display: flex; flex-direction: column; gap: 15px; }
        
        .nav-btn {
            background: #fff; 
            border: 1px solid #f0f0f0; 
            color: #888;
            padding: 18px 24px; border-radius: 16px; text-align: left;
            font-weight: 700; font-size: 0.8rem; text-transform: uppercase; letter-spacing: 1px;
            cursor: pointer; transition: all 0.2s;
            display: flex; justify-content: space-between; align-items: center;
            box-shadow: 0 4px 6px rgba(0,0,0,0.02);
        }
        .nav-btn:hover { 
            border-color: #000; color: #000; transform: translateY(-2px); 
        }
        
        .nav-btn.active { 
            background: #fff; 
            color: #000 !important; 
            border: 2px solid #000; 
            box-shadow: 0 10px 25px rgba(0,0,0,0.08); 
        }
        
        .nav-badge {
            background: #f3f4f6; color: #555; font-size: 0.7rem; padding: 4px 10px; border-radius: 20px;
            font-weight: 800;
        }
        .nav-btn.active .nav-badge { background: #000; color: #fff; }

        .right-card-list {
            display: flex; flex-direction: column; gap: 40px; 
        }

        .featured-card {
            background: #f8f9fa; border-radius: 24px; padding: 30px; border: 1px solid #eee;
            margin-bottom: 20px; position: relative; overflow: hidden;
        }
        .featured-label {
            position: absolute; top: 20px; right: 20px; background: #2563eb; color: #fff;
            font-size: 0.65rem; font-weight: 800; padding: 5px 12px; border-radius: 20px; text-transform: uppercase; letter-spacing: 1px;
        }

        .order-card {
            background: #fff; 
            border-radius: 24px;
            border: 1px solid #e5e7eb; 
            box-shadow: 0 10px 30px -5px rgba(0,0,0,0.08); 
            display: flex; flex-direction: column;
            overflow: hidden; transition: transform 0.3s ease;
        }
        .order-card:hover { 
            transform: translateY(-5px); 
            box-shadow: 0 20px 40px -10px rgba(0,0,0,0.12);
            border-color: #d1d5db; 
        }

        .card-top { display: flex; padding: 30px; gap: 30px; align-items: center; border-bottom: 1px solid #f9f9f9; }
        
        .img-wrapper {
            position: relative; width: 80px; height: 80px;
            display: flex; align-items: center; justify-content: center;
            border-radius: 12px; overflow: hidden;
            background: #fff; border: 1px solid #f0f0f0;
        }
        .card-img { width: 100%; height: 100%; object-fit: contain; mix-blend-mode: multiply; transition: transform 0.3s; }
        
        .hover-eye {
            position: absolute; inset: 0; background: rgba(0,0,0,0.4);
            display: flex; align-items: center; justify-content: center;
            opacity: 0; transition: opacity 0.2s;
        }
        .hover-eye i { color: #fff; font-size: 1.2rem; }
        
        .img-wrapper:hover .hover-eye { opacity: 1; }
        .img-wrapper:hover .card-img { transform: scale(1.1); }
        
        .card-details { flex: 1; }
        .o-title { font-size: 1.1rem; font-weight: 800; color: #111; text-transform: uppercase; margin-bottom: 5px; }
        .o-meta { font-size: 0.75rem; color: #888; font-weight: 600; text-transform: uppercase; letter-spacing: 0.5px; }
        .o-price { font-size: 1.3rem; font-weight: 900; color: #000; margin-top: 10px; }

        .card-actions { padding: 20px 30px; background: #fcfcfc; display: flex; gap: 15px; flex-wrap: wrap; }

        .btn-action {
            flex: 1; padding: 12px; border-radius: 10px; font-weight: 700; font-size: 0.7rem; text-transform: uppercase;
            text-align: center; cursor: pointer; transition: all 0.2s; letter-spacing: 1px; text-decoration: none;
            display: flex; align-items: center; justify-content: center; gap: 8px; min-width: 100px;
        }
        .btn-dark { background: #000; color: #fff; border: 1px solid #000; }
        .btn-dark:hover { background: #333; transform: translateY(-2px); color: #fff; }
        
        .btn-light { background: #fff; color: #111; border: 1px solid #eee; }
        .btn-light:hover { border-color: #000; }

        .animate-fade { animation: fadeIn 0.4s ease-out forwards; }
        @keyframes fadeIn { from { opacity: 0; transform: translateY(10px); } to { opacity: 1; transform: translateY(0); } }
        .hidden { display: none; }

        /* FIXED: Added explicit RTL direction for radio rating to ensure logical order */
        .rating-group {
            display: flex;
            justify-content: center;
            flex-direction: row-reverse;
        }
        .rating-group input:checked ~ label,
        .rating-group label:hover,
        .rating-group label:hover ~ label {
            color: #fbbf24;
        }

        @media (max-width: 900px) {
            .content-container { display: block; padding: 40px 20px; }
            .left-sticky-panel { position: static; margin-bottom: 40px; }
            .card-top { flex-direction: column; text-align: center; }
            .card-actions { flex-direction: column; }
        }
    </style>
</head>
<body>
    
    <?php include 'header.php'; ?>

    <div class="master-scroll-wrapper">
        
        <div class="content-container">
            
            <div class="left-sticky-panel">
                <span class="hero-badge">Your History</span>
                <h1 class="hero-title text-shadow-pop">Orders<br>Menu</h1>
                <p class="hero-desc">
                    Track your active shipments and review your purchase history. Select a category below to filter.
                </p>

                <div class="nav-stack">
                    <button onclick="switchTab('processing')" id="tab-processing" class="nav-btn active">
                        <span><i class="fas fa-box-open mr-2"></i> Processing</span>
                        <span class="nav-badge"><?= count($orders_preparing) ?></span>
                    </button>
                    
                    <button onclick="switchTab('delivery')" id="tab-delivery" class="nav-btn">
                        <span><i class="fas fa-shipping-fast mr-2"></i> Delivery</span>
                        <span class="nav-badge"><?= count($orders_delivery) ?></span>
                    </button>
                    
                    <button onclick="switchTab('history')" id="tab-history" class="nav-btn">
                        <span><i class="fas fa-history mr-2"></i> History</span>
                        <span class="nav-badge"><?= count($orders_completed) ?></span>
                    </button>
                </div>
            </div>

            <div class="right-card-list">
                
                <?php if ($latest_order): 
                    $l_str = explode(',', $latest_order['total_products'])[0];
                    $l_data = parseProductString($l_str);
                    $l_pid = getFirstProductId($latest_order['total_products'], $product_map);
                    $l_link = $l_pid ? "purchased_view.php?pid=$l_pid&oid=" . $latest_order['id'] : "shop.php";
                ?>
                <div class="featured-card">
                    <span class="featured-label">Latest Activity</span>
                    <div class="flex items-center gap-6">
                        <a href="<?= $l_link ?>" class="img-wrapper" style="width:100px; height:100px;">
                            <img src="<?= getOrderImage($latest_order['total_products'], $product_map) ?>" class="card-img">
                            <div class="hover-eye"><i class="fas fa-eye"></i></div>
                        </a>
                        <div>
                            <div class="text-xs font-bold text-gray-400 uppercase tracking-widest mb-1">Order #<?= $latest_order['id'] ?></div>
                            <h3 class="text-xl font-black text-slate-900 uppercase"><?= $l_data['name'] ?></h3>
                            <div class="text-sm font-bold text-blue-600 uppercase mt-1"><?= $latest_order['delivery_status'] ?></div>
                        </div>
                    </div>
                </div>
                <?php endif; ?>

                <div id="view-processing" class="animate-fade">
                    <?php if (empty($orders_preparing)): ?>
                        <div class="text-center py-20 opacity-50"><i class="fas fa-box text-4xl mb-3 text-gray-300"></i><p class="font-bold text-sm uppercase">No active orders</p></div>
                    <?php else: foreach($orders_preparing as $order): 
                        $pid = getFirstProductId($order['total_products'], $product_map);
                        $link = $pid ? "purchased_view.php?pid=$pid&oid=" . $order['id'] : "shop.php";
                        $item_str = explode(',', $order['total_products'])[0];
                        $d = parseProductString($item_str);
                    ?>
                        <div class="order-card">
                            <div class="card-top">
                                <a href="<?= $link ?>" class="img-wrapper">
                                    <img src="<?= getOrderImage($order['total_products'], $product_map) ?>" class="card-img">
                                    <div class="hover-eye"><i class="fas fa-eye"></i></div>
                                </a>
                                
                                <div class="card-details">
                                    <div class="o-meta">Order #<?= $order['id'] ?> • <?= date('M d, Y', strtotime($order['placed_on'])) ?></div>
                                    <div class="o-title"><?= $d['name'] ?></div>
                                    <div class="o-meta">
                                        Qty: <?= $d['qty'] ?> 
                                        <?= !empty($d['variant']) ? ' • <span style="color:#000;">'.$d['variant'].'</span>' : '' ?>
                                    </div>
                                    <div class="o-price">RM <?= number_format($order['total_price'], 2) ?></div>
                                </div>
                            </div>
                            <div class="card-actions">
                                <a href="<?= $link ?>" class="btn-action btn-light">View Details</a>
                                <button onclick="requestRefund(<?= $order['id'] ?>)" class="btn-action btn-light" style="color:#ef4444; border-color:#fee2e2;">Cancel</button>
                            </div>
                        </div>
                    <?php endforeach; endif; ?>
                </div>

                <div id="view-delivery" class="hidden animate-fade">
                    <?php if (empty($orders_delivery)): ?>
                        <div class="text-center py-20 opacity-50"><i class="fas fa-truck text-4xl mb-3 text-gray-300"></i><p class="font-bold text-sm uppercase">No deliveries in progress</p></div>
                    <?php else: foreach($orders_delivery as $order): 
                        $pid = getFirstProductId($order['total_products'], $product_map);
                        $link = $pid ? "purchased_view.php?pid=$pid&oid=" . $order['id'] : "shop.php";
                        $item_str = explode(',', $order['total_products'])[0];
                        $d = parseProductString($item_str);
                    ?>
                        <div class="order-card">
                            <div class="card-top">
                                <a href="<?= $link ?>" class="img-wrapper">
                                    <img src="<?= getOrderImage($order['total_products'], $product_map) ?>" class="card-img">
                                    <div class="hover-eye"><i class="fas fa-eye"></i></div>
                                </a>
                                <div class="card-details">
                                    <div class="o-meta" style="color:#f97316;">In Transit • <?= htmlspecialchars($order['rider_name'] ?? 'Rider') ?></div>
                                    <div class="o-title"><?= $d['name'] ?></div>
                                    <div class="o-meta">
                                        Qty: <?= $d['qty'] ?> 
                                        <?= !empty($d['variant']) ? ' • <span style="color:#000;">'.$d['variant'].'</span>' : '' ?>
                                    </div>
                                    <div class="o-price">RM <?= number_format($order['total_price'], 2) ?></div>
                                </div>
                            </div>
                            <div class="card-actions">
                                <button onclick="window.open('receipt.php?order_id=<?= $order['id'] ?>', '_blank')" class="btn-action btn-light">Receipt</button>
                                </div>
                        </div>
                    <?php endforeach; endif; ?>
                </div>

                <div id="view-history" class="hidden animate-fade">
                    <?php if (empty($orders_completed)): ?>
                        <div class="text-center py-20 opacity-50"><i class="fas fa-history text-4xl mb-3 text-gray-300"></i><p class="font-bold text-sm uppercase">No past orders</p></div>
                    <?php else: foreach($orders_completed as $order): 
                        $is_refunded = (in_array(strtolower($order['delivery_status']), ['cancelled', 'refunded']));
                        // FIXED: Ensuring is_rated check is accurate
                        $is_rated = $order['is_rated'] ?? 0;
                        $pid = getFirstProductId($order['total_products'], $product_map);
                        $link = $pid ? "purchased_view.php?pid=$pid&oid=" . $order['id'] : "shop.php";
                        $item_str = explode(',', $order['total_products'])[0];
                        $d = parseProductString($item_str);
                        
                        $proof_img = !empty($order['delivery_image']) ? 'uploaded_img/'.$order['delivery_image'] : null;
                    ?>
                        <div class="order-card" style="opacity: <?= $is_refunded ? '0.6' : '1' ?>;">
                            <div class="card-top">
                                <a href="<?= $link ?>" class="img-wrapper">
                                    <img src="<?= getOrderImage($order['total_products'], $product_map) ?>" class="card-img">
                                    <div class="hover-eye"><i class="fas fa-eye"></i></div>
                                </a>
                                <div class="card-details">
                                    <div class="o-meta text-<?= $is_refunded ? 'red-500' : 'green-600' ?>"><?= $order['delivery_status'] // Display actual status ?> • <?= date('M Y', strtotime($order['placed_on'])) ?></div>
                                    
                                    <?php if($proof_img): ?>
                                    <div class="mt-2 mb-2">
                                        <a href="<?= $proof_img ?>" target="_blank" class="inline-flex items-center gap-3 bg-slate-50 border border-slate-200 rounded-xl p-2 pr-4 hover:bg-slate-100 hover:border-slate-300 transition-all group no-underline">
                                            <img src="<?= $proof_img ?>" class="w-8 h-8 rounded-lg object-cover border border-slate-200">
                                            <div class="flex flex-col">
                                                <span class="text-[0.6rem] font-bold text-slate-400 uppercase leading-none">Rider Upload</span>
                                                <span class="text-[0.7rem] font-black text-slate-700 uppercase leading-tight group-hover:text-black">View Proof</span>
                                            </div>
                                        </a>
                                    </div>
                                    <?php endif; ?>
                                    <div class="o-title"><?= $d['name'] ?></div>
                                    <div class="o-meta">
                                        Qty: <?= $d['qty'] ?> 
                                        <?= !empty($d['variant']) ? ' • <span style="color:#000;">'.$d['variant'].'</span>' : '' ?>
                                    </div>
                                    <div class="o-price">RM <?= number_format($order['total_price'], 2) ?></div>
                                </div>
                            </div>
                            <div class="card-actions">
                                <?php if(!$is_refunded): ?>
                                    <button onclick="window.open('receipt.php?order_id=<?= $order['id'] ?>', '_blank')" class="btn-action btn-light">Receipt</button>
                                    
                                    <?php if($is_rated == 0): ?>
                                        <button onclick='openRateModal(<?= getOrderItemsJson($order['total_products'], $product_map) ?>, <?= $order['id'] ?>)' class="btn-action btn-dark" style="background:#fbbf24; border-color:#fbbf24; color:#000;">Rate</button>
                                    <?php else: ?>
                                        <a href="<?= $link ?>" class="btn-action btn-dark">Buy Again</a>
                                    <?php endif; ?>

                                    <a href="contact.php?order_id=<?= $order['id'] ?>" class="btn-action btn-light" style="color:#ef4444; border-color:#fee2e2;">
                                        <i class="fas fa-exclamation-circle"></i> Report / Return
                                    </a>

                                <?php else: ?>
                                    <div class="btn-action btn-light" style="cursor:default; color:#888;">Refund Processed</div>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endforeach; endif; ?>
                </div>

            </div>
        </div>

        <div id="rateModal" class="fixed inset-0 z-50 hidden">
            <div class="absolute inset-0 bg-white/80 backdrop-blur-sm" onclick="closeRateModal()"></div>
            <div class="absolute top-1/2 left-1/2 transform -translate-x-1/2 -translate-y-1/2 w-full max-w-md bg-white rounded-3xl p-8 shadow-2xl border border-gray-100">
                <div class="text-center mb-6">
                    <h3 class="text-2xl font-black text-slate-900 uppercase">Rate Purchase</h3>
                </div>
                <form id="ratingForm" class="space-y-5">
                    <input type="hidden" name="order_id" id="modalOrderId" value="">
                    <div>
                        <select name="pid" id="modalProductSelect" class="w-full bg-gray-50 border border-gray-200 text-sm rounded-xl block p-3 font-bold outline-none"></select>
                    </div>
                    <div class="rating-group text-3xl text-gray-200">
                        <input type="radio" name="rating" id="r5" value="5" class="hidden peer" required><label for="r5" class="fas fa-star cursor-pointer px-1 hover:text-yellow-400"></label>
                        <input type="radio" name="rating" id="r4" value="4" class="hidden peer"><label for="r4" class="fas fa-star cursor-pointer px-1 hover:text-yellow-400"></label>
                        <input type="radio" name="rating" id="r3" value="3" class="hidden peer"><label for="r3" class="fas fa-star cursor-pointer px-1 hover:text-yellow-400"></label>
                        <input type="radio" name="rating" id="r2" value="2" class="hidden peer"><label for="r2" class="fas fa-star cursor-pointer px-1 hover:text-yellow-400"></label>
                        <input type="radio" name="rating" id="r1" value="1" class="hidden peer"><label for="r1" class="fas fa-star cursor-pointer px-1 hover:text-yellow-400"></label>
                    </div>
                    <textarea name="comment" rows="3" class="w-full bg-gray-50 border border-gray-200 rounded-xl p-3 text-sm outline-none resize-none" placeholder="Write review..."></textarea>
                    <div class="flex gap-3">
                        <button type="button" onclick="closeRateModal()" class="btn-action btn-light">Cancel</button>
                        <button type="submit" class="btn-action btn-dark">Submit</button>
                    </div>
                </form>
            </div>
        </div>

        <?php include 'footer.php'; ?>
    
    </div>

    <script>
        function switchTab(tabName) {
            ['processing', 'delivery', 'history'].forEach(t => {
                document.getElementById('view-' + t).classList.add('hidden');
                document.getElementById('tab-' + t).classList.remove('active');
            });
            document.getElementById('view-' + tabName).classList.remove('hidden');
            document.getElementById('tab-' + tabName).classList.add('active');
        }

        function requestRefund(orderId) {
            if(!confirm("Are you sure you want to cancel this order?")) return;
            const formData = new FormData(); formData.append('action', 'cancel_order'); formData.append('order_id', orderId);
            fetch('order_handler.php', { method: 'POST', body: formData }).then(res => res.json()).then(data => { if(data.success) { alert("Refund initiated."); location.reload(); } else { alert(data.message); } });
        }

        // FIXED: openRateModal logic to correctly handle the passed JSON data
        function openRateModal(products, orderId) {
            const modal = document.getElementById('rateModal'); 
            const select = document.getElementById('modalProductSelect');
            document.getElementById('modalOrderId').value = orderId; 
            select.innerHTML = '';
            
            if(!products || products.length === 0) { 
                select.innerHTML = '<option value="">No rateable items</option>'; 
            } else { 
                products.forEach(p => { 
                    let opt = document.createElement('option'); 
                    opt.value = p.id; 
                    opt.textContent = p.name; 
                    select.appendChild(opt); 
                }); 
            }
            modal.classList.remove('hidden');
        }

        function closeRateModal() { 
            document.getElementById('rateModal').classList.add('hidden'); 
            document.getElementById('ratingForm').reset(); 
        }

        document.getElementById('ratingForm').addEventListener('submit', function(e) {
            e.preventDefault(); 
            const formData = new FormData(this); 
            formData.append('action', 'submit_rating');
            fetch('rating_handler.php', { 
                method: 'POST', 
                body: formData 
            }).then(res => res.json()).then(data => { 
                if(data.success) { 
                    alert('Review submitted!'); 
                    location.reload(); 
                } else { 
                    alert(data.message); 
                } 
            });
        });
        
        function openChatModal(oid, rid, rname) { alert("Chatting with " + rname); }
    </script>

</body>
</html>