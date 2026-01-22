<?php
// Handle Toast Messages (Popups)
if(isset($message)){
   foreach($message as $msg){
      echo '
      <div class="message" style="position:fixed; top:20px; right:20px; background:#000; color:#fff; padding:12px 20px; border-radius:12px; z-index:10000; box-shadow:0 10px 20px rgba(0,0,0,0.2); animation: slideIn 0.3s ease; font-size: 0.85rem; font-weight: 600; display:flex; align-items:center; gap:10px;">
         <span>'.$msg.'</span>
         <i class="fas fa-times" style="cursor:pointer; opacity:0.7;" onclick="this.parentElement.remove();"></i>
      </div>
      ';
   }
}

// Helper: Check Active Page
function isActive($page) {
    return basename($_SERVER['PHP_SELF']) == $page ? 'active-nav' : '';
}

// Helper: Dynamic Title
$cur_page = basename($_SERVER['PHP_SELF']);
$page_title = 'Admin Panel';
if($cur_page == 'admin_page.php') $page_title = 'Overview';
elseif($cur_page == 'admin_products.php') $page_title = 'Inventory';
elseif($cur_page == 'admin_orders.php') $page_title = 'Analytics';
elseif($cur_page == 'admin_users.php') $page_title = 'User Roles';
elseif($cur_page == 'admin_pending.php') $page_title = 'Dispatch';
elseif($cur_page == 'admin_contacts.php') $page_title = 'Help Desk';
elseif($cur_page == 'admin_banner.php') $page_title = 'Banners';
elseif($cur_page == 'admin_order_history.php') $page_title = 'History';
elseif($cur_page == 'admin_update_item.php') $page_title = 'Edit Item';
elseif($cur_page == 'admin_update_profile.php') $page_title = 'Profile';

// --- NOTIFICATION COUNTERS ---

// 1. Help Desk Count (Active Tickets)
$msg_count = 0;
try {
    // FIX: Removed "AND status = 'unread'" to prevent DB crash. 
    // Now it counts all active conversations.
    $msg_count_stmt = $conn->query("SELECT COUNT(DISTINCT ticket_id) FROM `message` WHERE ticket_id != 0");
    $msg_count = $msg_count_stmt->fetchColumn();
} catch(PDOException $e) { $msg_count = 0; }

// 2. Dispatch Queue Count
$dispatch_count = 0;
try {
    // FIX: Strictly matches the "Queue" (Pending + Preparing)
    $dispatch_count_stmt = $conn->query("SELECT COUNT(*) FROM `orders` WHERE delivery_status IN ('pending', 'Preparing Order')");
    $dispatch_count = $dispatch_count_stmt->fetchColumn();
} catch(PDOException $e) { $dispatch_count = 0; }
?>

<style>
    /* Global Sidebar Style Sync */
    .left-sticky-panel { width: 100%; position: static !important; }
    .admin-nav { display: flex; flex-direction: column !important; gap: 8px; }
    
    .nav-item {
        display: flex; align-items: center; gap: 15px; padding: 14px 20px;
        border-radius: 16px; font-size: 0.8rem; font-weight: 700;
        color: #94a3b8; text-transform: uppercase; letter-spacing: 0.5px;
        transition: all 0.2s; text-decoration: none; position: relative;
    }
    .nav-item i { width: 20px; text-align: center; }
    .nav-item:hover { background: #f8fafc; color: #000; }
    .nav-item.active-nav { background: #000; color: #fff; }

    /* SIDEBAR NOTIFICATION BADGE */
    .nav-badge {
        position: absolute; right: 20px; top: 50%; transform: translateY(-50%);
        background: #ef4444; color: white; font-size: 0.65rem; font-weight: 800;
        padding: 2px 8px; border-radius: 10px; min-width: 24px; text-align: center;
        box-shadow: 0 2px 5px rgba(239, 68, 68, 0.4);
    }
    /* Invert colors when active so it's visible on black background */
    .active-nav .nav-badge { background: #fff; color: #ef4444; }

    .hero-title {
        font-family: 'Inter', sans-serif; font-size: 3rem; font-weight: 900; 
        line-height: 0.9; color: #111; text-transform: uppercase; 
        letter-spacing: -2px; margin-bottom: 40px; word-wrap: break-word; hyphens: auto;
    }

    @media (max-width: 1024px) {
        .content-container { display: flex !important; flex-direction: column !important; padding: 20px !important; }
        .sidebar-scroll { width: 100% !important; height: auto !important; margin-bottom: 40px; }
        .hero-title { font-size: 2.5rem; }
    }
</style>

<div class="left-sticky-panel">
    <span class="hero-badge" style="background:#000; color:#fff; font-size:0.65rem; font-weight:800; padding:6px 14px; border-radius:50px; text-transform:uppercase; margin-bottom:25px; display:inline-block;">Administrator</span>
    
    <h1 class="hero-title"><?= $page_title ?></h1>
    
    <nav class="admin-nav">
        <a href="admin_page.php" class="nav-item <?= isActive('admin_page.php'); ?>">
            <i class="fas fa-home"></i> Dashboard
        </a>
        <a href="admin_orders.php" class="nav-item <?= isActive('admin_orders.php'); ?>">
            <i class="fas fa-chart-pie"></i> Analytics
        </a>
        <a href="admin_products.php" class="nav-item <?= isActive('admin_products.php'); ?>">
            <i class="fas fa-box-open"></i> Inventory
        </a>
        <a href="admin_users.php" class="nav-item <?= isActive('admin_users.php'); ?>">
            <i class="fas fa-users"></i> Users & Riders
        </a>
        
        <a href="admin_pending.php" class="nav-item <?= isActive('admin_pending.php'); ?>">
            <i class="fas fa-truck-fast"></i> Dispatch Board
            <?php if($dispatch_count > 0): ?>
                <span class="nav-badge"><?= $dispatch_count ?></span>
            <?php endif; ?>
        </a>
        
        <a href="admin_contacts.php" class="nav-item <?= isActive('admin_contacts.php'); ?>">
            <i class="fas fa-headset"></i> Help Desk
            <?php if($msg_count > 0): ?>
                <span class="nav-badge"><?= $msg_count ?></span>
            <?php endif; ?>
        </a>
        
        <a href="admin_banner.php" class="nav-item <?= isActive('admin_banner.php'); ?>">
            <i class="fas fa-image"></i> Banners
        </a>

        <a href="admin_order_history.php" class="nav-item <?= isActive('admin_order_history.php'); ?>">
            <i class="fas fa-history"></i> Order History
        </a>
        
        <div class="mt-8 pt-6 border-t border-gray-100">
            <a href="admin_update_profile.php" class="nav-item <?= isActive('admin_update_profile.php'); ?>">
                <i class="fas fa-user-cog"></i> My Profile
            </a>
            <a href="logout.php" class="nav-item text-red-500 hover:bg-red-50 hover:text-red-600 mt-2">
                <i class="fas fa-sign-out-alt"></i> Logout
            </a>
        </div>
    </nav>
</div>