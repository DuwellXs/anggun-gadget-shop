<?php
// 1. GET CURRENT PAGE
$current_page = basename($_SERVER['PHP_SELF']);

// 2. FETCH COUNTS
$count_available = 0;
$count_active = 0;

if(isset($conn) && isset($_SESSION['delivery_id'])){
    $did = $_SESSION['delivery_id'];
    
    // Market: Available Orders
    try {
        $stmt_a = $conn->query("SELECT COUNT(*) FROM orders WHERE (delivery_status = 'Preparing Order' OR delivery_status = 'On the way') AND (delivery_rider = 0 OR delivery_rider IS NULL)");
        $count_available = $stmt_a->fetchColumn();
    } catch(PDOException $e) {}

    // Active: ONLY count orders that are part of the new Batch System ('On Route...')
    try {
        $stmt_m = $conn->prepare("SELECT COUNT(*) FROM orders WHERE delivery_rider = ? AND delivery_status LIKE 'On Route%'");
        $stmt_m->execute([$did]);
        $count_active = $stmt_m->fetchColumn();
    } catch(PDOException $e) {}
}

// 3. TOAST MESSAGES (Synced with Admin Style)
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
    global $current_page;
    return $current_page == $page ? 'active-nav' : '';
}
?>

<style>
    /* Global Sidebar Style Sync (From Admin) */
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

    /* RIDER SPECIFIC BADGES (Matches Admin Geometry, Keeps Logic Colors) */
    .nav-badge {
        position: absolute; right: 20px; top: 50%; transform: translateY(-50%);
        font-size: 0.65rem; font-weight: 800;
        padding: 2px 8px; border-radius: 10px; min-width: 24px; text-align: center;
        box-shadow: 0 2px 5px rgba(0,0,0,0.1);
    }
    .badge-green { background: #10b981; color: white; }
    .badge-blue { background: #2563eb; color: white; }
    
    /* Invert/Adjust when active */
    .active-nav .nav-badge { background: #fff; color: #000; box-shadow: none; }

    .hero-title {
        font-family: 'Inter', sans-serif; font-size: 3rem; font-weight: 900; 
        line-height: 0.9; color: #111; text-transform: uppercase; 
        letter-spacing: -2px; margin-bottom: 40px; word-wrap: break-word; hyphens: auto;
    }
    
    .hero-badge { background:#000; color:#fff; font-size:0.65rem; font-weight:800; padding:6px 14px; border-radius:50px; text-transform:uppercase; margin-bottom:25px; display:inline-block; }

    @media (max-width: 1024px) {
        .content-container { display: flex !important; flex-direction: column !important; padding: 20px !important; }
        .sidebar-scroll { width: 100% !important; height: auto !important; margin-bottom: 40px; }
        .hero-title { font-size: 2.5rem; }
    }
</style>

<div class="left-sticky-panel">
    <span class="hero-badge">Rider Unit</span>
    
    <h1 class="hero-title">ANGGUN<br><span class="text-gray-300">LOGISTICS</span></h1>
    
    <nav class="admin-nav">
        <a href="du_page.php" class="nav-item <?= isActive('du_page.php'); ?>">
            <i class="fas fa-home"></i> Dashboard
        </a>
        
        <a href="du_order.php" class="nav-item <?= isActive('du_order.php'); ?>">
            <i class="fas fa-globe"></i> Job Market
            <?php if($count_available > 0): ?>
                <span class="nav-badge badge-green"><?= $count_available ?></span>
            <?php endif; ?>
        </a>
        
        <a href="du_your_order.php" class="nav-item <?= isActive('du_your_order.php'); ?>">
            <i class="fas fa-motorcycle"></i> My Active Jobs
            <?php if($count_active > 0): ?>
                <span class="nav-badge badge-blue"><?= $count_active ?></span>
            <?php endif; ?>
        </a>
        
        <a href="du_completed.php" class="nav-item <?= isActive('du_completed.php'); ?>">
            <i class="fas fa-history"></i> History
        </a>
        
        <div class="mt-8 pt-6 border-t border-gray-100">
            <a href="du_update_profile.php" class="nav-item <?= isActive('du_update_profile.php'); ?>">
                <i class="fas fa-user-circle"></i> My Profile
            </a>
            <a href="logout.php" onclick="return confirm('End shift and logout?');" class="nav-item text-red-500 hover:bg-red-50 hover:text-red-600 mt-2">
                <i class="fas fa-sign-out-alt"></i> Logout
            </a>
        </div>
    </nav>
</div>