<?php
@include 'config.php';
session_start();

$admin_id = $_SESSION['admin_id'];
if (!isset($admin_id)) { header('location:login.php'); exit(); }

// 1. DATA CALCULATIONS
$rev_stmt = $conn->query("SELECT SUM(total_price) FROM orders WHERE payment_status = 'completed'");
$total_revenue = $rev_stmt->fetchColumn() ?? 0;

$ord_stmt = $conn->query("SELECT COUNT(*) FROM orders");
$total_orders = $ord_stmt->fetchColumn() ?? 0;

$net_profit = $total_revenue * 0.35;
$avg_order = ($total_orders > 0) ? $total_revenue / $total_orders : 0;

// 2. TREND DATA
$trend_stmt = $conn->prepare("
    SELECT DATE_FORMAT(STR_TO_DATE(placed_on, '%d-%M-%Y'), '%d %b') as day, 
           SUM(total_price) as daily_rev,
           COUNT(*) as daily_ord
    FROM orders 
    WHERE payment_status = 'completed'
    GROUP BY day 
    ORDER BY STR_TO_DATE(placed_on, '%d-%M-%Y') DESC LIMIT 7
");
$trend_stmt->execute();
$raw_data = array_reverse($trend_stmt->fetchAll(PDO::FETCH_ASSOC));

$dates = []; $sales_data = []; $order_data = [];
foreach($raw_data as $d){
    $dates[] = $d['day'];
    $sales_data[] = $d['daily_rev'];
    $order_data[] = $d['daily_ord'];
}

// 3. DISTRICT ANALYTICS
$loc_query = $conn->query("SELECT address, total_price FROM orders WHERE payment_status = 'completed'");
$location_stats = [];
while($row = $loc_query->fetch(PDO::FETCH_ASSOC)){
    $parts = explode(',', $row['address']);
    $district = "Unknown";
    foreach($parts as $part) {
        $part = trim($part);
        if(preg_match('/(\d{5})\s+(.*)/', $part, $matches)) {
            $district = trim($matches[2]); 
            break; 
        }
    }
    if($district == "Unknown" && count($parts) >= 2) {
        $district = trim($parts[count($parts)-2]);
    }
    $district = str_ireplace(['Daerah', 'Wilayah', 'Bandar', 'Bandaraya', 'Jalan', 'Lorong'], '', $district);
    $district = ucwords(strtolower(trim($district))); 
    if(!isset($location_stats[$district])){ $location_stats[$district] = 0; }
    $location_stats[$district] += $row['total_price'];
}
arsort($location_stats);
$top_locations = array_slice($location_stats, 0, 5);

$cat_stmt = $conn->query("SELECT category, COUNT(*) as cnt FROM products GROUP BY category ORDER BY cnt DESC LIMIT 5");
$cat_labels = []; $cat_data = [];
while($row = $cat_stmt->fetch(PDO::FETCH_ASSOC)){ $cat_labels[] = $row['category']; $cat_data[] = $row['cnt']; }
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Analytics | Admin Panel</title>

    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800;900&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

    <style>
        /* Strict Sync with admin_order_history.php */
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
            grid-template-columns: 280px minmax(0, 1fr); 
            gap: 60px;
            padding: 0 30px;
        }

        .sidebar-scroll {
            height: 100%;
            overflow-y: auto;
            padding-top: 40px;
            scrollbar-width: none; 
            -ms-overflow-style: none;
        }
        .sidebar-scroll::-webkit-scrollbar { display: none; }

        .content-scroll {
            height: 100%;
            overflow-y: auto; 
            padding-top: 40px;
            padding-bottom: 100px;
            scrollbar-width: none; 
            -ms-overflow-style: none;
        }
        .content-scroll::-webkit-scrollbar { display: none; }

        /* Elevation and Depth */
        .card {
            background: #fff; border-radius: 12px; padding: 30px;
            border: 1px solid #f3f4f6; 
            box-shadow: 0 10px 30px -10px rgba(0,0,0,0.05);
            display: flex; flex-direction: column; justify-content: space-between;
        }
        .card-dark { background: #000; color: #fff; border: 1px solid #000; }
        .lbl { font-size: 0.65rem; font-weight: 800; color: #9ca3af; text-transform: uppercase; letter-spacing: 1.5px; margin-bottom: 12px; }
        .val { font-size: 2.2rem; font-weight: 900; color: #000; line-height: 0.9; letter-spacing: -2px; }
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
                <h1 class="text-4xl font-black text-black uppercase tracking-tighter">Analytics</h1>
                <div class="flex justify-between items-end mt-2">
                    <p class="text-sm font-bold text-gray-400 tracking-wide uppercase">Store Performance Dashboard</p>
                    <div class="flex items-center gap-2 bg-green-50 px-3 py-1.5 rounded-lg border border-green-100">
                        <span class="relative flex h-2 w-2">
                          <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-green-400 opacity-75"></span>
                          <span class="relative inline-flex rounded-full h-2 w-2 bg-green-500"></span>
                        </span>
                        <span class="text-[10px] font-black text-green-600 uppercase tracking-wider">Live System</span>
                    </div>
                </div>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-4 gap-6 mb-10">
                <div class="card">
                    <div class="lbl">Total Revenue</div>
                    <div class="val">RM<?= number_format($total_revenue) ?></div>
                    <div class="text-[0.75rem] font-bold text-green-600 mt-4"><i class="fas fa-arrow-trend-up"></i> Sales Volume</div>
                </div>
                <div class="card">
                    <div class="lbl">Total Orders</div>
                    <div class="val"><?= number_format($total_orders) ?></div>
                    <div class="text-[0.75rem] font-bold text-blue-600 mt-4"><i class="fas fa-check-circle"></i> Sync Live</div>
                </div>
                <div class="card card-dark">
                    <div class="lbl" style="color:#6b7280;">Est. Net Profit</div>
                    <div class="val" style="color:#fff;">RM<?= number_format($net_profit) ?></div>
                    <div class="text-[0.75rem] font-bold text-emerald-400 mt-4">~35% Margin</div>
                </div>
                <div class="card">
                    <div class="lbl">Avg. Ticket Size</div>
                    <div class="flex items-end justify-between">
                        <div class="val">RM<?= number_format($avg_order, 0) ?></div>
                        <div style="width: 60px; height: 60px;"><canvas id="gaugeTiny"></canvas></div>
                    </div>
                </div>
            </div>

            <div class="grid grid-cols-1 xl:grid-cols-2 gap-8 mb-10">
                <div class="card">
                    <div class="lbl">Revenue Momentum (7 Days)</div>
                    <div style="height: 300px; width: 100%;"><canvas id="revenueChart"></canvas></div>
                </div>
                <div class="card">
                    <div class="lbl">Order Frequency</div>
                    <div style="height: 300px; width: 100%;"><canvas id="orderChart"></canvas></div>
                </div>
            </div>

            <div class="grid grid-cols-1 xl:grid-cols-2 gap-8">
                <div class="card">
                    <div class="lbl mb-6 border-b border-gray-100 pb-4">Sales by District (Top 5)</div>
                    <div style="min-height: 250px;">
                        <?php 
                        $i = 0; $colors = ['bg-blue-600', 'bg-blue-500', 'bg-blue-400', 'bg-blue-300', 'bg-blue-200'];
                        foreach($top_locations as $loc => $amt): 
                            $pct = ($total_revenue > 0) ? ($amt / $total_revenue) * 100 : 0;
                            $bg = $colors[$i % 5]; $i++;
                        ?>
                        <div class="mb-5">
                            <div class="flex justify-between mb-2">
                                <span class="text-[0.75rem] font-black text-gray-800 uppercase tracking-tight"><?= $loc ?></span>
                                <span class="text-[0.75rem] font-bold text-black">RM<?= number_format($amt) ?></span>
                            </div>
                            <div class="w-full bg-gray-100 h-2.5 rounded-full overflow-hidden">
                                <div class="<?= $bg ?> h-full rounded-full" style="width: <?= $pct ?>%"></div>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>
                <div class="card">
                    <div class="lbl mb-6 border-b border-gray-100 pb-4">Inventory Composition</div>
                    <div style="height: 250px;"><canvas id="catChart"></canvas></div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
    const noGrow = { responsive: true, maintainAspectRatio: false, plugins: { legend: { display: false } } };

    new Chart(document.getElementById('gaugeTiny'), {
        type: 'doughnut',
        data: { datasets: [{ data: [<?= $avg_order ?>, <?= max(0, 300 - $avg_order) ?>], backgroundColor: ['#10b981', '#f1f5f9'], borderWidth: 0, cutout: '80%' }] },
        options: { ...noGrow, plugins: { tooltip: {enabled: false}, legend: {display: false} } }
    });

    const ctxRev = document.getElementById('revenueChart').getContext('2d');
    const grad = ctxRev.createLinearGradient(0,0,0,300);
    grad.addColorStop(0, 'rgba(0,0,0,0.05)'); grad.addColorStop(1, 'rgba(0,0,0,0)');
    new Chart(ctxRev, {
        type: 'line',
        data: { labels: <?= json_encode($dates) ?>, datasets: [{ label: 'Revenue', data: <?= json_encode($sales_data) ?>, borderColor: '#000', backgroundColor: grad, borderWidth: 3, fill: true, tension: 0.4, pointRadius: 4, pointBackgroundColor: '#fff', pointBorderColor:'#000' }] },
        options: { ...noGrow, scales: { y: { beginAtZero: true, grid: { borderDash: [5,5], color:'#f1f5f9'} }, x: { grid: { display: false } } } }
    });

    new Chart(document.getElementById('orderChart'), {
        type: 'bar',
        data: { labels: <?= json_encode($dates) ?>, datasets: [{ data: <?= json_encode($order_data) ?>, backgroundColor: '#000', borderRadius: 4, barThickness: 30 }] },
        options: { ...noGrow, scales: { y: { display: false }, x: { grid: { display: false } } } }
    });

    new Chart(document.getElementById('catChart'), {
        type: 'bar',
        data: { labels: <?= json_encode($cat_labels) ?>, datasets: [{ data: <?= json_encode($cat_data) ?>, backgroundColor: ['#000', '#3b82f6', '#10b981', '#f59e0b', '#ef4444'], borderRadius: 4, barThickness: 20 }] },
        options: { ...noGrow, indexAxis: 'y', scales: { x: { display: false }, y: { grid: { display: false }, ticks: { font: { size: 10, weight: 'bold' } } } } }
    });
</script>
</body>
</html>