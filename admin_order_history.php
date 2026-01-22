<?php
@include 'config.php';
session_start();

// FIX: Set Timezone to Malaysia (GMT+8) so dates match local time
date_default_timezone_set('Asia/Kuala_Lumpur');

if(!isset($_SESSION['admin_id'])) {
    header('location:login.php');
    exit();
}

// 1. FILTER LOGIC
$status_filter = $_GET['status'] ?? 'all';
$date_filter = $_GET['date'] ?? 'all';
$search = $_GET['search'] ?? '';

// Base query
$query = "
    SELECT o.*, 
           c.name AS customer_name,
           c.email AS customer_email
    FROM `orders` o
    LEFT JOIN `users` c ON o.user_id = c.id
    WHERE 1=1
";

$params = [];

if($status_filter != 'all') {
    if($status_filter == 'completed') {
        $query .= " AND (o.delivery_status = 'completed' OR o.delivery_status LIKE 'Delivered%')";
    } else {
        $query .= " AND o.delivery_status = :status";
        $params[':status'] = $status_filter;
    }
}

if($date_filter != 'all') {
    // FIX: Using STR_TO_DATE ensures we parse '02-January-2026' correctly for SQL filtering
    switch($date_filter) {
        case 'today': $query .= " AND STR_TO_DATE(o.placed_on, '%d-%M-%Y') = CURDATE()"; break;
        case 'week': $query .= " AND STR_TO_DATE(o.placed_on, '%d-%M-%Y') >= DATE_SUB(NOW(), INTERVAL 1 WEEK)"; break;
        case 'month': $query .= " AND STR_TO_DATE(o.placed_on, '%d-%M-%Y') >= DATE_SUB(NOW(), INTERVAL 1 MONTH)"; break;
    }
}

if($search) {
    $query .= " AND (o.id LIKE :search OR o.payment_id LIKE :search OR c.name LIKE :search OR c.email LIKE :search)";
    $params[':search'] = "%$search%";
}

$query .= " ORDER BY o.id DESC";

$stmt = $conn->prepare($query);
foreach($params as $key => $value) { $stmt->bindValue($key, $value); }
$stmt->execute();
$orders = $stmt->fetchAll(PDO::FETCH_ASSOC);

// HELPER: Item Parser
function formatItems($itemStr) {
    $items = explode(',', $itemStr);
    $html = '<div class="flex flex-col gap-3">';
    
    foreach($items as $item) {
        $item = trim($item);
        
        $qty = '1';
        if (preg_match('/\(Qty:\s*(\d+)\)/i', $item, $match)) {
            $qty = $match[1];
            $item = str_replace($match[0], '', $item);
        }

        $variants = [];
        if (preg_match_all('/\[(.*?)\]/', $item, $matches)) {
            foreach($matches[1] as $m) { $variants[] = $m; }
            $item = preg_replace('/\[.*?\]/', '', $item);
        }
        if (preg_match_all('/\((.*?)\)/', $item, $matches)) {
            foreach($matches[1] as $m) { $variants[] = $m; }
            $item = preg_replace('/\(.*?\)/', '', $item);
        }

        $name = trim($item);
        $variantHtml = '';
        foreach($variants as $v) {
            $variantHtml .= '<span class="badge-meta">'.$v.'</span>';
        }

        $html .= '
        <div class="item-row">
            <div class="flex-1 pr-2">
                <span class="item-name">'.$name.'</span>
                '.($variantHtml ? '<div class="flex flex-wrap gap-1 mt-1">'.$variantHtml.'</div>' : '').'
            </div>
            <div class="flex items-center">
                <span class="badge-qty">x'.$qty.'</span>
            </div>
        </div>';
    }
    $html .= '</div>';
    return $html;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Order Master List | Admin Panel</title>

    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800;900&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

    <style>
        html, body {
            margin: 0; padding: 0;
            height: 100vh; 
            overflow: hidden !important;
            font-family: 'Inter', sans-serif;
            background-color: #ffffff !important;
            color: #000;
        }

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

        .left-sticky-panel { 
            height: 100%;
            overflow-y: auto; 
            padding-top: 40px;
            padding-bottom: 40px;
            scrollbar-width: none; 
            -ms-overflow-style: none;
        }
        .left-sticky-panel::-webkit-scrollbar { display: none; }

        .right-content { 
            height: 100%;
            overflow-y: auto; 
            padding-top: 40px;
            padding-bottom: 100px;
            scrollbar-width: none; 
            -ms-overflow-style: none;
        }
        .right-content::-webkit-scrollbar { display: none; }

        .hero-title { font-size: 3.5rem; font-weight: 900; line-height: 0.9; color: #111; text-transform: uppercase; letter-spacing: -2px; margin-bottom: 40px; }
        .history-card { background: #fff; margin-bottom: 50px; width: 100%; }

        /* FILTER BAR */
        .filter-container {
            display: flex; gap: 12px; flex-wrap: wrap; align-items: center;
            padding-bottom: 30px; border-bottom: 1px solid #f3f4f6; margin-bottom: 30px;
        }
        .ag-select, .ag-input {
            background: #fff; border: 1px solid #e5e5e5; border-radius: 8px;
            padding: 10px 14px; font-size: 0.75rem; font-weight: 700; color: #1f2937;
            outline: none; transition: 0.2s; min-width: 140px;
            text-transform: uppercase; letter-spacing: 0.5px;
        }
        .ag-select:focus, .ag-input:focus { border-color: #000; background: #fafafa; }
        
        .ag-btn-search {
            background: #000; color: #fff; border: none; padding: 0 20px; border-radius: 8px;
            font-size: 0.75rem; font-weight: 800; text-transform: uppercase; letter-spacing: 1px;
            cursor: pointer; height: 38px; display: flex; align-items: center;
        }
        .btn-print {
            background: #fff; border: 1px solid #e5e5e5; color: #000;
            padding: 0 16px; font-size: 0.7rem; font-weight: 800; text-transform: uppercase;
            cursor: pointer; height: 38px; display: flex; align-items: center; gap: 8px;
            margin-left: auto; border-radius: 8px;
        }
        .btn-print:hover { border-color: #000; }

        .search-wrapper { position: relative; display: flex; align-items: center; gap: 10px; }
        .btn-clear { position: absolute; right: 100px; color: #9ca3af; cursor: pointer; font-weight: 900; font-size: 1rem; line-height: 1; padding: 5px; transition: 0.2s; z-index: 5; }
        .btn-clear:hover { color: #ef4444; }

        /* TABLE */
        .data-table { width: 100%; border-collapse: separate; border-spacing: 0; }
        .data-table th { 
            text-align: left; color: #9ca3af; font-size: 0.65rem; font-weight: 800; 
            text-transform: uppercase; padding: 15px 20px; border-bottom: 2px solid #f3f4f6; 
            letter-spacing: 1px; white-space: nowrap;
        }
        .data-table td { padding: 24px 20px; border-bottom: 1px solid #f9fafb; vertical-align: top; }
        .data-table tr:hover td { background: #fafafa; }

        .trans-date { font-size: 0.85rem; font-weight: 800; color: #000; display: block; text-transform: uppercase; letter-spacing: -0.5px; }
        .trans-id { font-family: 'Courier New', monospace; font-size: 0.7rem; color: #9ca3af; font-weight: 700; margin-top: 4px; display: block; }

        .item-row { display: flex; justify-content: space-between; align-items: flex-start; padding-bottom: 12px; margin-bottom: 12px; border-bottom: 1px dashed #f3f4f6; }
        .item-row:last-child { border-bottom: none; margin-bottom: 0; padding-bottom: 0; }
        .item-name { font-size: 0.85rem; font-weight: 700; color: #0f172a; display: block; line-height: 1.3; }
        
        .badge-meta { display: inline-block; font-size: 0.65rem; font-weight: 700; text-transform: uppercase; letter-spacing: 0.5px; color: #64748b; background: #f1f5f9; padding: 2px 6px; border-radius: 4px; border: 1px solid #e2e8f0; margin-right: 4px; }
        .badge-qty { display: inline-flex; align-items: center; justify-content: center; background: #000; color: #fff; font-size: 0.7rem; font-weight: 800; height: 24px; min-width: 30px; padding: 0 8px; border-radius: 6px; box-shadow: 0 2px 5px rgba(0,0,0,0.2); }

        .st-badge { display: inline-flex; align-items: center; font-size: 0.6rem; font-weight: 900; text-transform: uppercase; padding: 6px 12px; border-radius: 6px; letter-spacing: 0.5px; }
        .st-done { background: #f0fdf4; color: #16a34a; }
        .st-cancel { background: #fef2f2; color: #ef4444; }
        .st-wait { background: #fffbeb; color: #d97706; border: 1px solid #fcd34d; }
        .st-other { background: #f3f4f6; color: #6b7280; }

        /* MODAL */
        .modal { display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(255,255,255,0.95); z-index: 1000; align-items: center; justify-content: center; }
        .modal-content { background: #fff; padding: 50px; width: 100%; max-width: 600px; border: 1px solid #f3f4f6; box-shadow: 0 30px 60px -15px rgba(0,0,0,0.1); animation: fadeIn 0.2s ease; }
        @keyframes fadeIn { from { opacity:0; transform:scale(0.98); } to { opacity:1; transform:scale(1); } }
        
        @media print {
            .left-sticky-panel, .master-scroll-wrapper, .filter-container { display: none !important; }
            .content-container { display: block; width: 100%; padding: 0; height: auto; }
            .right-content { width: 100%; overflow: visible; height: auto; }
            .data-table th, .data-table td { border-bottom: 1px solid #000; font-size: 10px; padding: 5px; }
            .badge-qty { background: #fff; border: 1px solid #000; color: #000; box-shadow: none; }
        }
    </style>
</head>
<body>

<div class="master-scroll-wrapper">
    <div class="content-container">
        
        <?php include 'admin_header.php'; ?>

        <div class="right-content">
            
            <div class="history-card">
                
                <div class="mb-8">
                    <h1 class="text-4xl font-black text-black uppercase tracking-tighter">Full History</h1>
                    <p class="text-sm font-bold text-gray-400 mt-2 tracking-wide uppercase">All Transactions (Active & Completed)</p>
                </div>

                <form method="GET" action="" class="filter-container" id="searchForm">
                    
                    <select name="status" class="ag-select" onchange="this.form.submit()">
                        <option value="all" <?= $status_filter == 'all' ? 'selected' : '' ?>>All Status</option>
                        <option value="completed" <?= $status_filter == 'completed' ? 'selected' : '' ?>>Completed / Delivered</option>
                        <option value="cancelled" <?= $status_filter == 'cancelled' ? 'selected' : '' ?>>Cancelled</option>
                    </select>

                    <select name="date" class="ag-select" onchange="this.form.submit()">
                        <option value="all" <?= $date_filter == 'all' ? 'selected' : '' ?>>All Time</option>
                        <option value="today" <?= $date_filter == 'today' ? 'selected' : '' ?>>Today</option>
                        <option value="week" <?= $date_filter == 'week' ? 'selected' : '' ?>>This Week</option>
                        <option value="month" <?= $date_filter == 'month' ? 'selected' : '' ?>>This Month</option>
                    </select>

                    <div class="search-wrapper">
                        <input type="text" name="search" id="searchInput" class="ag-input" placeholder="REF ID / CUSTOMER" 
                               value="<?= htmlspecialchars($search) ?>" style="min-width: 220px; padding-right: 30px;">
                        
                        <?php if(!empty($search)): ?>
                            <span class="btn-clear" onclick="clearSearch()" title="Clear Search">&times;</span>
                        <?php endif; ?>

                        <button type="submit" class="ag-btn-search">Search</button>
                    </div>

                    <button type="button" onclick="window.print()" class="btn-print">
                        <i class="fas fa-print"></i> Report
                    </button>
                </form>

                <div class="overflow-x-auto rounded-xl border border-gray-100 shadow-sm">
                    <table class="data-table">
                        <thead>
                            <tr class="bg-gray-50">
                                <th width="15%">Date & Time</th>
                                <th width="20%">Customer</th>
                                <th width="35%">Purchased Items</th>
                                <th>Total</th>
                                <th>Status</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach($orders as $order): 
                                $st = strtolower($order['delivery_status']);
                                $badge_class = 'st-other';
                                
                                if(strpos($st, 'complete') !== false) $badge_class = 'st-done';
                                elseif(strpos($st, 'deliver') !== false) $badge_class = 'st-done';
                                elseif(strpos($st, 'cancel') !== false) $badge_class = 'st-cancel';
                                elseif(strpos($st, 'pending') !== false || strpos($st, 'prepar') !== false || strpos($st, 'way') !== false || strpos($st, 'picked') !== false) $badge_class = 'st-wait';
                                
                                $display_id = !empty($order['payment_id']) ? $order['payment_id'] : '#'.$order['id'];
                            ?>
                            <tr>
                                <td>
                                    <div class="font-bold text-gray-900 text-sm"><?= date('d M Y', strtotime($order['placed_on'])) ?></div>
                                    <div class="text-xs text-gray-500 mt-1 font-bold"><?= date('h:i A', strtotime($order['placed_on'])) ?></div>
                                    <span class="trans-id mt-2"><?= htmlspecialchars($display_id) ?></span>
                                </td>
                                <td>
                                    <div class="font-bold text-sm text-black"><?= htmlspecialchars($order['customer_name']) ?></div>
                                    <div class="text-[10px] text-gray-400 uppercase tracking-wide mt-1"><?= htmlspecialchars($order['customer_email']) ?></div>
                                </td>
                                <td>
                                    <?= formatItems(htmlspecialchars($order['total_products'])) ?>
                                </td>
                                <td class="font-black text-black">RM<?= htmlspecialchars($order['total_price']) ?></td>
                                <td><span class="st-badge <?= $badge_class ?>"><?= htmlspecialchars($order['delivery_status']) ?></span></td>
                                <td>
                                    <button class="text-[10px] font-black border-b-2 border-black hover:text-gray-500 hover:border-gray-500 transition-colors uppercase tracking-wider pb-1"
                                            onclick="viewDetails(
                                                '<?= $display_id ?>',
                                                '<?= addslashes($order['customer_name']) ?>',
                                                '<?= addslashes($order['address']) ?>',
                                                '<?= addslashes($order['total_products']) ?>',
                                                '<?= $order['total_price'] ?>'
                                            )">
                                        View
                                    </button>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                            <?php if(empty($orders)) echo '<tr><td colspan="6" class="text-center py-20 text-gray-400 font-bold uppercase tracking-widest text-xs">No orders found.</td></tr>'; ?>
                        </tbody>
                    </table>
                </div>
            </div>

        </div>
    </div>
</div>

<div id="orderModal" class="modal" onclick="closeModal(event)">
    <div class="modal-content">
        <div class="flex justify-between items-start mb-10">
            <div>
                <h2 class="text-3xl font-black uppercase text-black tracking-tighter">Order<br>Receipt</h2>
                <p id="modalRef" class="text-sm font-mono text-gray-400 mt-2 font-bold"></p>
            </div>
            <button onclick="document.getElementById('orderModal').style.display='none'" class="text-2xl font-bold hover:text-red-500">&times;</button>
        </div>
        
        <div class="space-y-8">
            <div class="grid grid-cols-2 gap-4">
                <div>
                    <p class="text-[10px] font-bold text-gray-400 uppercase tracking-widest mb-1">Customer</p>
                    <p id="modalName" class="font-bold text-black text-sm"></p>
                </div>
                <div class="text-right">
                    <p class="text-[10px] font-bold text-gray-400 uppercase tracking-widest mb-1">Total Paid</p>
                    <span id="modalTotal" class="text-xl font-black text-black"></span>
                </div>
            </div>
            <div>
                <p class="text-[10px] font-bold text-gray-400 uppercase tracking-widest mb-1">Delivery To</p>
                <p id="modalAddress" class="text-sm text-gray-600 leading-relaxed font-medium"></p>
            </div>
            <div class="border-t border-gray-100 py-6">
                <p class="text-[10px] font-bold text-gray-400 uppercase tracking-widest mb-4">Purchased Items</p>
                <div id="modalItems" class="space-y-3"></div>
            </div>
        </div>
    </div>
</div>

<script>
    function clearSearch() { document.getElementById('searchInput').value = ''; document.getElementById('searchForm').submit(); }
    function viewDetails(ref, name, addr, itemsStr, price) {
        document.getElementById('modalRef').textContent = ref;
        document.getElementById('modalName').textContent = name;
        document.getElementById('modalAddress').textContent = addr;
        document.getElementById('modalTotal').textContent = 'RM' + price;
        const itemsDiv = document.getElementById('modalItems'); itemsDiv.innerHTML = ''; 
        const items = itemsStr.split(',');
        items.forEach(item => {
            const div = document.createElement('div');
            div.className = 'flex justify-between items-start text-sm border-b border-dashed border-gray-100 pb-2';
            let qty = "1"; let nameClean = item.trim();
            let qMatch = item.match(/\(Qty:\s*(\d+)\)/i);
            if(qMatch) { qty = qMatch[1]; nameClean = nameClean.replace(qMatch[0], '').trim(); }
            div.innerHTML = `<span class="font-bold text-gray-900">${nameClean}</span> <span class="font-mono text-xs bg-black text-white px-2 py-1 rounded">x${qty}</span>`;
            itemsDiv.appendChild(div);
        });
        document.getElementById('orderModal').style.display = 'flex';
    }
    function closeModal(e) { if(e.target === document.getElementById('orderModal')) document.getElementById('orderModal').style.display = 'none'; }
</script>

</body>
</html>