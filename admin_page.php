<?php
@include 'config.php';
session_start();

$admin_id = $_SESSION['admin_id'];
if(!isset($admin_id)){ header('location:login.php'); exit(); }

// --- DATA FETCHING & LIVE LOGIC ---

// 1. Pending Orders (Logic: Matches Dispatch Board - Excludes 'On the way')
// FIX: Query matches admin_pending.php exactly
$pendings_stmt = $conn->prepare("SELECT COUNT(*) FROM `orders` WHERE delivery_status IN ('Preparing Order', 'pending')");
$pendings_stmt->execute();
$total_pendings = $pendings_stmt->fetchColumn();

if($total_pendings > 10) {
    $p_status = "High Volume"; $p_msg = "Bottleneck Risk"; $p_badge = "badge-danger";
} elseif($total_pendings > 0) {
    $p_status = "Active Queue"; $p_msg = "Processing"; $p_badge = "badge-warning";
} else {
    $p_status = "System Idle"; $p_msg = "Ready for Orders"; $p_badge = "badge-success";
}

// 2. Revenue
$rev_stmt = $conn->prepare("SELECT SUM(total_price) FROM `orders` WHERE payment_status = 'completed'");
$rev_stmt->execute();
$total_revenue = $rev_stmt->fetchColumn() ?? 0;

// FIX: Changed DATE() to STR_TO_DATE() to handle text-based dates (e.g., 01-Jan-2026)
$today_rev_stmt = $conn->prepare("SELECT SUM(total_price) FROM `orders` WHERE payment_status = 'completed' AND STR_TO_DATE(placed_on, '%d-%b-%Y') = CURDATE()");
$today_rev_stmt->execute();
$today_revenue = $today_rev_stmt->fetchColumn() ?? 0;

// 3. Inventory
$prod_stmt = $conn->prepare("SELECT COUNT(*) FROM `products` WHERE category != 'Banner'");
$prod_stmt->execute();
$total_products = $prod_stmt->fetchColumn();

// 4. Messages (Logic: Safe Count)
// FIX: Removed "AND status = 'unread'" to prevent database error if column is missing
$msg_stmt = $conn->prepare("SELECT COUNT(DISTINCT ticket_id) FROM `message` WHERE ticket_id != 0");
$msg_stmt->execute();
$total_messages = $msg_stmt->fetchColumn();
$m_badge = ($total_messages > 0) ? "badge-danger" : "badge-success";
$m_text  = ($total_messages > 0) ? "Action Required" : "Inbox Cleared";

// 5. History
$hist_stmt = $conn->prepare("SELECT COUNT(*) FROM `orders`");
$hist_stmt->execute();
$total_history = $hist_stmt->fetchColumn();

// 6. Riders
$riders_stmt = $conn->prepare("SELECT COUNT(*) FROM `users` WHERE user_type = 'delivery'");
$riders_stmt->execute();
$total_riders = $riders_stmt->fetchColumn();

if($total_riders == 0) {
    $r_msg = "CRITICAL: No Fleet"; $r_badge = "badge-danger";
} elseif($total_riders < 3) {
    $r_msg = "Low Availability"; $r_badge = "badge-warning";
} else {
    $r_msg = "Fleet Healthy"; $r_badge = "badge-info";
}

// 7. Banners
$ban_stmt = $conn->prepare("SELECT COUNT(*) FROM `products` WHERE category = 'Banner'");
$ban_stmt->execute();
$total_banners = $ban_stmt->fetchColumn();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard | Anggun Gadget</title>
    
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800;900&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

    <style>
        /* === NUCLEAR DESIGN SYSTEM (GLOBAL) === */
        html, body {
            margin: 0; padding: 0;
            height: 100vh; 
            overflow: hidden !important;
            font-family: 'Inter', sans-serif;
            background-color: #ffffff !important;
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

        /* === RIGHT PANEL (CONTENT STACK) === */
        .right-card-stack {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 30px;
            padding-bottom: 100px;
        }

        /* Stat Card Design */
        .stat-card {
            background: #fff; border-radius: 24px; padding: 35px;
            border: 1px solid #f1f5f9;
            box-shadow: 0 10px 30px -10px rgba(0,0,0,0.05);
            transition: transform 0.3s ease, box-shadow 0.3s ease;
            display: flex; flex-direction: column;
            justify-content: space-between;
            height: 100%;
            position: relative; overflow: hidden;
            animation: popIn 0.5s cubic-bezier(0.2, 0.8, 0.2, 1) forwards;
            opacity: 0; transform: translateY(20px);
        }
        .stat-card:hover { transform: translateY(-5px); box-shadow: 0 20px 40px -10px rgba(0,0,0,0.1); border-color: #e2e8f0; }

        @keyframes popIn { to { opacity: 1; transform: translateY(0); } }

        .stat-icon {
            width: 50px; height: 50px; background: #f8fafc; border-radius: 14px;
            display: flex; align-items: center; justify-content: center;
            font-size: 1.4rem; margin-bottom: 20px; color: #1e293b;
            transition: 0.3s;
        }
        .stat-card:hover .stat-icon { background: #000; color: #fff; }

        .stat-value { font-size: 2.5rem; font-weight: 900; color: #000; line-height: 1; margin-bottom: 5px; letter-spacing: -1px; }
        .stat-label { font-size: 0.75rem; font-weight: 800; color: #94a3b8; text-transform: uppercase; letter-spacing: 1px; }
        
        .sub-stat { font-size: 0.7rem; font-weight: 700; color: #16a34a; margin-top: 5px; display: flex; align-items: center; gap: 4px; }
        
        /* Badges */
        .alert-badge {
            display: inline-flex; align-items: center; gap: 6px;
            padding: 8px 12px; border-radius: 8px;
            font-size: 0.7rem; font-weight: 800; text-transform: uppercase; letter-spacing: 0.5px;
            margin-top: 15px; width: fit-content;
        }
        .badge-warning { background: #fefce8; color: #ca8a04; border: 1px solid #fef9c3; }
        .badge-danger { background: #fee2e2; color: #dc2626; border: 1px solid #fecaca; animation: pulse 2s infinite; }
        .badge-success { background: #f0fdf4; color: #16a34a; border: 1px solid #dcfce7; }
        .badge-info { background: #eff6ff; color: #2563eb; border: 1px solid #dbeafe; }
        .badge-neutral { background: #f8fafc; color: #64748b; border: 1px solid #f1f5f9; }
        .badge-cyan { background: #ecfeff; color: #06b6d4; border: 1px solid #cffafe; }

        @keyframes pulse { 0% { opacity: 1; } 50% { opacity: 0.7; } 100% { opacity: 1; } }

        /* Accent Borders */
        .accent-l { border-left: 6px solid; } 
        .border-yellow { border-left-color: #facc15; }
        .border-green { border-left-color: #10b981; }
        .border-blue { border-left-color: #3b82f6; }
        .border-orange { border-left-color: #f97316; } 
        .border-pink { border-left-color: #ec4899; }   
        .border-purple { border-left-color: #8b5cf6; }
        .border-cyan { border-left-color: #06b6d4; }
    </style>
</head>
<body>

<div class="master-scroll-wrapper">
    <div class="content-container">
        
        <div class="sidebar-scroll">
            <?php include 'admin_header.php'; ?>
        </div>

        <div class="content-scroll">
            <div class="right-card-stack">
                
                <div class="stat-card accent-l border-yellow" style="animation-delay: 0.1s;">
                    <div class="stat-icon"><i class="fas fa-clock"></i></div>
                    <div>
                        <div class="stat-value"><?= number_format($total_pendings); ?></div>
                        <div class="stat-label"><?= $p_status ?></div>
                        
                        <div class="alert-badge <?= $p_badge ?>">
                            <i class="fas fa-exclamation-circle"></i> <?= $p_msg ?>
                        </div>
                    </div>
                    <a href="admin_pending.php" class="mt-6 text-[10px] font-bold uppercase tracking-widest hover:underline">Go to Dispatch &rarr;</a>
                </div>

                <div class="stat-card accent-l border-green" style="animation-delay: 0.2s;">
                    <div class="stat-icon"><i class="fas fa-wallet"></i></div>
                    <div>
                        <div class="stat-value">RM<?= number_format($total_revenue, 0); ?></div>
                        <div class="stat-label">Total Revenue</div>
                        
                        <?php if($today_revenue > 0): ?>
                            <div class="sub-stat"><i class="fas fa-arrow-up"></i> RM<?= number_format($today_revenue) ?> Today</div>
                        <?php else: ?>
                            <div class="sub-stat text-gray-400"><i class="fas fa-minus"></i> No sales today yet</div>
                        <?php endif; ?>
                    </div>
                    <a href="admin_orders.php" class="mt-6 text-[10px] font-bold uppercase tracking-widest hover:underline">Analytics &rarr;</a>
                </div>

                <div class="stat-card accent-l border-blue" style="animation-delay: 0.3s;">
                    <div class="stat-icon"><i class="fas fa-motorcycle"></i></div>
                    <div>
                        <div class="stat-value"><?= number_format($total_riders); ?></div>
                        <div class="stat-label">Delivery Fleet</div>
                        
                        <div class="alert-badge <?= $r_badge ?>">
                            <i class="fas fa-user-check"></i> <?= $r_msg ?>
                        </div>
                    </div>
                    <a href="admin_users.php" class="mt-6 text-[10px] font-bold uppercase tracking-widest hover:underline">Manage Fleet &rarr;</a>
                </div>

                <div class="stat-card accent-l border-orange" style="animation-delay: 0.4s;">
                    <div class="stat-icon"><i class="fas fa-cubes"></i></div>
                    <div>
                        <div class="stat-value"><?= number_format($total_products); ?></div>
                        <div class="stat-label">Active SKUs</div>
                        
                        <div class="alert-badge badge-neutral">
                            <i class="fas fa-box"></i> Live Inventory
                        </div>
                    </div>
                    <a href="admin_products.php" class="mt-6 text-[10px] font-bold uppercase tracking-widest hover:underline text-gray-400">Update Stock &rarr;</a>
                </div>

                <div class="stat-card accent-l border-pink" style="animation-delay: 0.5s;">
                    <div class="stat-icon"><i class="fas fa-envelope"></i></div>
                    <div>
                        <div class="stat-value"><?= number_format($total_messages); ?></div>
                        <div class="stat-label">Inquiries</div>
                        
                        <div class="alert-badge <?= $m_badge ?>">
                            <i class="fas fa-bell"></i> <?= $m_text ?>
                        </div>
                    </div>
                    <a href="admin_contacts.php" class="mt-6 text-[10px] font-bold uppercase tracking-widest hover:underline text-gray-400">Read Inbox &rarr;</a>
                </div>

                <div class="stat-card accent-l border-purple" style="animation-delay: 0.6s;">
                    <div class="stat-icon"><i class="fas fa-history"></i></div>
                    <div>
                        <div class="stat-value"><?= number_format($total_history); ?></div>
                        <div class="stat-label">Lifetime Orders</div>
                        
                        <div class="alert-badge badge-neutral">
                            <i class="fas fa-database"></i> Archive
                        </div>
                    </div>
                    <a href="admin_order_history.php" class="mt-6 text-[10px] font-bold uppercase tracking-widest hover:underline text-purple-500">Full History &rarr;</a>
                </div>

                <div class="stat-card accent-l border-cyan" style="animation-delay: 0.7s;">
                    <div class="stat-icon"><i class="fas fa-images"></i></div>
                    <div>
                        <div class="stat-value"><?= number_format($total_banners); ?></div>
                        <div class="stat-label">Active Banners</div>
                        
                        <div class="alert-badge badge-cyan">
                            <i class="fas fa-eye"></i> Live Display
                        </div>
                    </div>
                    <a href="admin_banner.php" class="mt-6 text-[10px] font-bold uppercase tracking-widest hover:underline text-cyan-600">Manage Banners &rarr;</a>
                </div>

            </div>
        </div>
    </div>
</div>

</body>
</html>