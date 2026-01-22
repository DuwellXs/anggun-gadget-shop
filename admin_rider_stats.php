<?php
@include 'config.php';
session_start();

$admin_id = $_SESSION['admin_id'];
if (!isset($admin_id)) { header('location:login.php'); exit; }

// --- CALCULATE STATS ---
// 1. Total Active Riders (Anyone with 'On Route' status)
$active_riders_count = $conn->query("SELECT COUNT(DISTINCT delivery_rider) FROM orders WHERE delivery_status LIKE 'On Route%'")->fetchColumn();

// 2. Completed Jobs This Month
$month_start = date('Y-m-01');
$completed_month = $conn->prepare("SELECT COUNT(*) FROM orders WHERE delivery_status = 'Delivered' AND placed_on >= ?");
$completed_month->execute([$month_start]);
$month_jobs = $completed_month->fetchColumn();

// --- HELPER: SIMULATE EARNINGS ---
// Since we didn't save exact commission in DB, we estimate:
// Logistics = RM3, Direct = Avg RM12. We'll use RM8.00 as a safe average for "Delivered" history.
function estimateEarnings($job_count) {
    return $job_count * 8.00; 
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Rider Activity | Admin Panel</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800;900&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <style>
        /* === NUCLEAR DESIGN SYSTEM === */
        html, body { margin: 0; padding: 0; height: 100vh; overflow: hidden !important; font-family: 'Inter', sans-serif; background-color: #fff; color: #000; }
        .master-scroll-wrapper { height: 100vh; display: flex; justify-content: center; }
        .content-container { width: 100%; max-width: 1400px; display: grid; grid-template-columns: 280px 1fr; gap: 60px; padding: 0 30px; height: 100%; }
        .sidebar-scroll { height: 100%; overflow-y: auto; padding: 40px 0; }
        .content-scroll { height: 100%; overflow-y: auto; padding: 40px 0 100px; }

        /* CARDS */
        .stat-card { background: #f8fafc; padding: 25px; border-radius: 20px; border: 1px solid #e2e8f0; }
        .stat-val { font-size: 2.5rem; font-weight: 900; line-height: 1; margin-bottom: 5px; }
        .stat-lbl { font-size: 0.7rem; font-weight: 800; text-transform: uppercase; color: #64748b; }

        /* TABLES */
        .ag-table { width: 100%; border-collapse: separate; border-spacing: 0; }
        .ag-table th { text-align: left; color: #94a3b8; font-size: 0.65rem; font-weight: 800; text-transform: uppercase; padding: 20px; border-bottom: 1px solid #f1f5f9; }
        .ag-table td { padding: 20px; border-bottom: 1px solid #f8fafc; vertical-align: middle; }
        .ag-table tr:hover td { background: #fafafa; }

        /* BADGES */
        .status-badge { display: inline-flex; align-items: center; gap: 6px; padding: 6px 12px; border-radius: 50px; font-size: 0.65rem; font-weight: 800; text-transform: uppercase; }
        .st-direct { background: #f0fdf4; color: #166534; border: 1px solid #dcfce7; }
        .st-logis { background: #eff6ff; color: #1e40af; border: 1px solid #dbeafe; }

        /* PROOF THUMB */
        .proof-img { width: 40px; height: 40px; border-radius: 8px; border: 1px solid #e2e8f0; object-fit: cover; cursor: zoom-in; }
        .img-modal { display: none; position: fixed; inset:0; background: rgba(0,0,0,0.9); z-index: 50; align-items: center; justify-content: center; }
        .img-modal img { max-height: 90vh; max-width: 90vw; border-radius: 10px; }
    </style>
</head>
<body>

<div class="master-scroll-wrapper">
    <div class="content-container">
        
        <div class="sidebar-scroll"><?php include 'admin_header.php'; ?></div>

        <div class="content-scroll">
            
            <div class="mb-10">
                <h1 class="text-4xl font-black uppercase tracking-tighter">Rider Activity</h1>
                <p class="text-sm font-bold text-gray-400 tracking-wide uppercase mt-1">Live Tracking & Performance</p>
            </div>

            <div class="grid grid-cols-2 md:grid-cols-4 gap-6 mb-12">
                <div class="stat-card">
                    <div class="stat-val"><?= $active_riders_count ?></div>
                    <div class="stat-lbl">Riders Active Now</div>
                </div>
                <div class="stat-card">
                    <div class="stat-val"><?= $month_jobs ?></div>
                    <div class="stat-lbl">Jobs This Month</div>
                </div>
                <div class="stat-card bg-black text-white border-black">
                    <div class="stat-val">RM<?= number_format(estimateEarnings($month_jobs), 2) ?></div>
                    <div class="stat-lbl text-gray-400">Total Payout (Est.)</div>
                </div>
            </div>

            <div class="mb-12">
                <div class="flex items-center gap-3 mb-6">
                    <div class="w-2 h-2 bg-green-500 rounded-full animate-pulse"></div>
                    <h2 class="text-xl font-black uppercase">Live Tracking</h2>
                </div>
                
                <div class="border border-gray-100 rounded-2xl overflow-hidden shadow-sm">
                    <table class="ag-table">
                        <thead>
                            <tr>
                                <th>Order / Location</th>
                                <th>Rider</th>
                                <th>Mode & Status</th>
                                <th>Proof (Pickup)</th>
                                <th>Proof (Delivery)</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            // Fetch active jobs (On Route...)
                            $track_sql = "SELECT o.*, u.name as r_name, u.phone as r_phone 
                                          FROM orders o 
                                          JOIN users u ON o.delivery_rider = u.id 
                                          WHERE o.delivery_status LIKE 'On Route%' 
                                          ORDER BY o.id DESC";
                            $track_res = $conn->query($track_sql);

                            if($track_res->rowCount() > 0){
                                while($row = $track_res->fetch(PDO::FETCH_ASSOC)){
                                    $mode = str_replace('On Route - ', '', $row['delivery_status']);
                                    $badgeClass = ($mode == 'Direct') ? 'st-direct' : 'st-logis';
                            ?>
                            <tr>
                                <td>
                                    <div class="font-black text-sm text-slate-900">#<?= $row['id'] ?></div>
                                    <div class="text-xs font-bold text-gray-400 uppercase truncate w-48"><?= $row['address'] ?></div>
                                </td>
                                <td>
                                    <div class="font-bold text-sm text-slate-900"><?= $row['r_name'] ?></div>
                                    <div class="text-[10px] text-gray-400 font-mono"><?= $row['r_phone'] ?></div>
                                </td>
                                <td>
                                    <span class="status-badge <?= $badgeClass ?>">
                                        <i class="fas fa-route"></i> <?= $mode ?>
                                    </span>
                                </td>
                                <td>
                                    <?php if($row['pickup_image']): ?>
                                        <img src="uploaded_img/<?= $row['pickup_image'] ?>" class="proof-img" onclick="viewImg(this.src)">
                                    <?php else: ?>
                                        <span class="text-[10px] text-gray-300 font-bold uppercase">Pending</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php if($row['delivery_image']): ?>
                                        <img src="uploaded_img/<?= $row['delivery_image'] ?>" class="proof-img" onclick="viewImg(this.src)">
                                    <?php else: ?>
                                        <span class="text-[10px] text-gray-300 font-bold uppercase">In Progress</span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                            <?php 
                                }
                            } else {
                                echo '<tr><td colspan="5" class="text-center py-10 text-gray-400 font-bold uppercase text-xs">No active riders on the road</td></tr>';
                            }
                            ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <div>
                <div class="flex items-center gap-3 mb-6">
                    <i class="fas fa-wallet text-gray-300"></i>
                    <h2 class="text-xl font-black uppercase">Rider Payroll (<?= date('F Y') ?>)</h2>
                </div>

                <div class="border border-gray-100 rounded-2xl overflow-hidden shadow-sm">
                    <table class="ag-table">
                        <thead>
                            <tr>
                                <th>Rider Name</th>
                                <th>Contact</th>
                                <th>Jobs Completed</th>
                                <th>Performance</th>
                                <th class="text-right">Est. Earnings</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            // Aggregate stats per rider for current month
                            $pay_sql = "SELECT u.id, u.name, u.phone, u.email, COUNT(o.id) as job_count 
                                        FROM users u 
                                        LEFT JOIN orders o ON u.id = o.delivery_rider 
                                        WHERE u.user_type = 'delivery' 
                                        AND (o.delivery_status = 'Delivered' AND o.placed_on >= '$month_start')
                                        GROUP BY u.id ORDER BY job_count DESC";
                            $pay_res = $conn->query($pay_sql);

                            if($pay_res->rowCount() > 0){
                                while($rider = $pay_res->fetch(PDO::FETCH_ASSOC)){
                                    $earnings = estimateEarnings($rider['job_count']);
                            ?>
                            <tr>
                                <td>
                                    <div class="flex items-center gap-3">
                                        <div class="w-8 h-8 rounded-full bg-gray-100 flex items-center justify-center font-bold text-xs">
                                            <?= strtoupper(substr($rider['name'], 0, 1)) ?>
                                        </div>
                                        <div class="font-bold text-sm text-slate-900"><?= $rider['name'] ?></div>
                                    </div>
                                </td>
                                <td class="text-xs font-bold text-gray-500"><?= $rider['email'] ?></td>
                                <td>
                                    <span class="font-black text-lg text-slate-900"><?= $rider['job_count'] ?></span>
                                    <span class="text-[10px] font-bold text-gray-400 uppercase ml-1">Jobs</span>
                                </td>
                                <td>
                                    <div class="w-24 h-1.5 bg-gray-100 rounded-full overflow-hidden">
                                        <div class="h-full bg-green-500" style="width: <?= min(100, $rider['job_count']*5) ?>%"></div>
                                    </div>
                                </td>
                                <td class="text-right">
                                    <div class="font-black text-lg text-slate-900">RM<?= number_format($earnings, 2) ?></div>
                                    <div class="text-[9px] font-bold text-gray-400 uppercase">~RM8.00 avg/job</div>
                                </td>
                            </tr>
                            <?php
                                }
                            } else {
                                echo '<tr><td colspan="5" class="text-center py-10 text-gray-400 font-bold uppercase text-xs">No payment data for this month</td></tr>';
                            }
                            ?>
                        </tbody>
                    </table>
                </div>
            </div>

        </div>
    </div>
</div>

<div id="imgModal" class="img-modal" onclick="this.style.display='none'">
    <img id="modalImg" src="">
</div>

<script>
    function viewImg(src) { document.getElementById('modalImg').src = src; document.getElementById('imgModal').style.display = 'flex'; }
</script>

</body>
</html>