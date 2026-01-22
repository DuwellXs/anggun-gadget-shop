<?php
@include 'config.php';
session_start();

$user_id = $_SESSION['user_id'] ?? null;

// [1] CAPTURE URL FILTERS
$filter_pid = isset($_GET['pid']) ? $_GET['pid'] : null;
$filter_cat = isset($_GET['category']) ? $_GET['category'] : null;
$filter_rating = isset($_GET['rating']) ? $_GET['rating'] : null;

// [2] DYNAMIC PAGE TITLE
$page_header = "COMMUNITY<br>REVIEWS";
$page_sub = "Explore real stories from our verified customers. Filter by rating or category to find exactly what you need.";

if($filter_pid){
    $stmt = $conn->prepare("SELECT name FROM products WHERE id = ?");
    $stmt->execute([$filter_pid]);
    $prod = $stmt->fetch();
    if($prod) {
        $page_header = "REVIEWS:<br>" . htmlspecialchars($prod['name']);
        $page_sub = "Showing all verified feedback for this specific item.";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Customer Reviews | Anggun Gadget</title>
    
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;600;700;800;900&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <link rel="stylesheet" href="css/style.css">

    <style>
        /* === 1. NUCLEAR BACKGROUND FIX === */
        html, body {
            margin: 0; padding: 0;
            height: 100vh; 
            overflow: hidden !important;
            font-family: 'Inter', sans-serif;
            background-color: #ffffff !important;
            background-image: none !important;
            background: #ffffff !important;
        }

        /* Master Scroll Wrapper */
        .master-scroll-wrapper {
            height: 100vh;
            overflow-y: auto;
            scroll-behavior: smooth;
            padding-top: 100px;
            scrollbar-width: none; 
            -ms-overflow-style: none;
            background-color: #ffffff !important;
            background-image: none !important;
        }
        .master-scroll-wrapper::-webkit-scrollbar { display: none; }

        /* Grid Container */
        .content-container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 60px 20px 150px;
            display: grid;
            grid-template-columns: 35% 60%; 
            gap: 5%;
            align-items: start;
            min-height: 100vh;
        }

        /* === 2. LEFT PANEL (STICKY FILTERS) === */
        .left-sticky-panel {
            position: sticky;
            top: 50px;
            padding-right: 20px;
            z-index: 30; /* CHANGED: Lowered from 50 to 30 to allow cart overlay to cover it */
        }

        .hero-badge {
            background: #000; color: #fff; 
            font-size: 0.7rem; font-weight: 700; letter-spacing: 2px; text-transform: uppercase;
            padding: 6px 14px; border-radius: 50px; margin-bottom: 25px; display: inline-block;
        }

        .text-shadow-pop { text-shadow: 2px 2px 0px #cbd5e1; }

        .hero-title {
            font-size: 3rem; 
            font-weight: 900; 
            line-height: 1; 
            color: #111;
            text-transform: uppercase; 
            letter-spacing: -1px; 
            margin-bottom: 20px;
        }
        
        .hero-desc {
            font-size: 0.9rem; line-height: 1.6; color: #666; font-weight: 500;
            border-left: 3px solid #eee; padding-left: 20px; margin-bottom: 40px;
        }

        /* Filter Buttons Styling */
        .filter-group { display: flex; flex-direction: column; gap: 15px; }
        
        .filter-btn {
            background: #fff; border: 1px solid #eee; color: #111;
            padding: 14px 20px; border-radius: 12px; font-weight: 700; font-size: 0.75rem;
            text-transform: uppercase; letter-spacing: 1px; width: 100%; text-align: left;
            display: flex; justify-content: space-between; align-items: center;
            cursor: pointer; transition: all 0.2s; box-shadow: 0 4px 10px rgba(0,0,0,0.03);
            position: relative; z-index: 2;
        }
        .filter-btn:hover { border-color: #000; transform: translateY(-2px); }
        
        .clear-btn {
            background: #ffffff; 
            color: #000000; 
            border: 1px solid #e5e7eb;
            padding: 12px 20px; border-radius: 50px; font-weight: 800; font-size: 0.7rem;
            text-transform: uppercase; letter-spacing: 1px; width: fit-content;
            display: inline-flex; align-items: center; gap: 8px; margin-bottom: 20px;
            transition: all 0.2s; text-decoration: none;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
        }
        .clear-btn:hover { 
            border-color: #000; 
            transform: translateY(-2px); 
            box-shadow: 0 5px 15px rgba(0,0,0,0.1); 
        }
        .clear-btn i { color: #000; } 

        .dropdown-menu {
            display: none;
            margin-top: 10px;
            background: #fff; 
            border-radius: 16px; 
            border: 1px solid #eee;
            box-shadow: 0 10px 40px rgba(0,0,0,0.05); 
            width: 100%;
            overflow: hidden;
            animation: slideDown 0.3s ease forwards;
        }
        
        @keyframes slideDown {
            from { opacity: 0; transform: translateY(-10px); }
            to { opacity: 1; transform: translateY(0); }
        }

        .dropdown-menu.active { display: block; }

        /* === 3. RIGHT PANEL (SCROLLABLE CARDS) === */
        .right-card-stack {
            position: relative;
            padding-bottom: 100px;
            /* Changed to allow normal flow so cards don't hide each other */
            display: flex;
            flex-direction: column;
            gap: 30px; 
        }

        .stack-card {
            /* FIX: Changed from sticky to relative to prevent overlapping/hiding */
            position: relative; 
            width: 100%;
            /* FIX: Added Internal Scroller Logic */
            max-height: 450px; 
            overflow-y: auto;
            
            background: #fff;
            border-radius: 24px;
            border: 1px solid rgba(0,0,0,0.04);
            box-shadow: 0 10px 40px -10px rgba(0,0,0,0.05);
            padding: 40px;
            display: flex; flex-direction: column;
            transform-origin: center top;
            transition: transform 0.3s ease, box-shadow 0.3s ease;
            
            /* Custom Scrollbar for internal card */
            scrollbar-width: thin;
            scrollbar-color: #e5e7eb transparent;
        }
        
        .stack-card::-webkit-scrollbar { width: 6px; }
        .stack-card::-webkit-scrollbar-thumb { background: #e5e7eb; border-radius: 10px; }

        @keyframes dealIn { from { opacity: 0; transform: translateY(50px) scale(0.95); } to { opacity: 1; transform: translateY(0) scale(1); } }
        .stack-card { animation: dealIn 0.6s cubic-bezier(0.2, 0.8, 0.2, 1) forwards; }

        .stack-card:hover { transform: translateY(-5px); z-index: 10; box-shadow: 0 20px 50px rgba(0,0,0,0.1); }

        .rev-header { display: flex; align-items: center; justify-content: space-between; margin-bottom: 20px; }
        .user-info { display: flex; align-items: center; gap: 15px; }
        .avatar { 
            width: 45px; height: 45px; background: #111; color: #fff; border-radius: 50%; 
            display: flex; align-items: center; justify-content: center; font-weight: 800; font-size: 1rem; 
        }
        .u-name { font-weight: 800; font-size: 0.9rem; text-transform: uppercase; color: #111; }
        .u-date { font-size: 0.7rem; color: #999; font-weight: 600; text-transform: uppercase; letter-spacing: 1px; }
        
        .verified-badge { 
            background: #dcfce7; color: #166534; font-size: 0.65rem; font-weight: 800; 
            padding: 4px 10px; border-radius: 20px; text-transform: uppercase; letter-spacing: 1px;
        }

        .star-row { font-size: 0.9rem; margin-bottom: 15px; display: flex; gap: 4px; }
        .gold-star { color: #fbbf24 !important; }
        .gray-star { color: #e5e7eb !important; }
        
        .comment-text { font-size: 1rem; line-height: 1.6; color: #444; font-weight: 500; margin-bottom: 30px; }

        .prod-link {
            display: flex; align-items: center; gap: 15px; padding-top: 20px; border-top: 1px solid #f5f5f5;
            text-decoration: none; transition: all 0.2s; margin-top: auto; /* Push to bottom */
        }
        .prod-thumb {
            width: 50px; height: 50px; background: #fff; border: 1px solid #eee; border-radius: 10px;
            display: flex; align-items: center; justify-content: center; padding: 5px; flex-shrink: 0;
        }
        .prod-thumb img { width: 100%; height: 100%; object-fit: contain; mix-blend-mode: multiply; }
        
        .prod-meta { flex: 1; }
        .prod-label { font-size: 0.65rem; font-weight: 800; color: #999; text-transform: uppercase; letter-spacing: 1px; }
        .prod-name-txt { font-size: 0.85rem; font-weight: 800; color: #111; transition: color 0.2s; }
        .prod-link:hover .prod-name-txt { color: #2563eb; }

        .empty-area { width: 100%; text-align: center; padding: 100px 0; opacity: 0.5; }

        @media (max-width: 900px) {
            .content-container { display: block; padding: 40px 20px; }
            .left-sticky-panel { position: relative; top: 0; margin-bottom: 40px; padding-right: 0; }
            .stack-card { margin-bottom: 25px; max-height: none; overflow: visible; }
        }

        /* === 4. CART DRAWER OVERRIDE PATCH === */
        /* Ensures the cart drawer & backdrop are ALWAYS on top of everything */
        #cartDrawerBackdrop { z-index: 9998 !important; }
        #cartDrawer { z-index: 9999 !important; }
    </style>
</head>
<body>
    
    <?php include 'header.php'; ?>

    <div class="master-scroll-wrapper">
        
        <div class="content-container">
            
            <div class="left-sticky-panel">
                <span class="hero-badge">Verified Feedback</span>
                <h1 class="hero-title text-shadow-pop"><?= $page_header ?></h1>
                <p class="hero-desc"><?= $page_sub ?></p>

                <?php if($filter_pid || $filter_cat || $filter_rating): ?>
                    <a href="ratings.php" class="clear-btn">
                        <i class="fas fa-times"></i> Clear Filters
                    </a>
                <?php endif; ?>

                <div class="filter-group">
                    <div>
                        <button onclick="toggleDropdown('rateDropdown')" class="filter-btn">
                            <?php if($filter_rating): ?>
                                <span class="flex gap-2 gold-star">
                                    <?php for($i=0; $i<$filter_rating; $i++) echo '<i class="fas fa-star text-xs"></i>'; ?>
                                </span>
                            <?php else: ?>
                                <span>All Ratings</span>
                            <?php endif; ?>
                            <i class="fas fa-chevron-down text-xs"></i>
                        </button>
                        
                        <div id="rateDropdown" class="dropdown-menu">
                            <a href="ratings.php?category=<?= $filter_cat ?>" class="block px-5 py-3 text-xs font-bold uppercase hover:bg-gray-50">All Ratings</a>
                            <?php for($r=5; $r>=1; $r--): 
                                $url = "ratings.php?rating=$r" . ($filter_cat ? "&category=$filter_cat" : "");
                            ?>
                                <a href="<?= $url ?>" class="flex items-center gap-2 px-5 py-3 hover:bg-gray-50 text-xs">
                                    <?php for($s=1; $s<=5; $s++) echo "<i class='fas fa-star ".($s<=$r ? 'gold-star':'gray-star')."'></i>"; ?>
                                </a>
                            <?php endfor; ?>
                        </div>
                    </div>

                    <div>
                        <button onclick="toggleDropdown('catDropdown')" class="filter-btn">
                            <span><?= $filter_cat ? $filter_cat : 'All Categories' ?></span>
                            <i class="fas fa-chevron-down text-xs"></i>
                        </button>
                        
                        <div id="catDropdown" class="dropdown-menu">
                            <a href="ratings.php?rating=<?= $filter_rating ?>" class="block px-5 py-3 text-xs font-bold uppercase hover:bg-gray-50">All Categories</a>
                            <?php 
                                $cats = ['iPhone', 'Android', 'Audio', 'Power', 'Accessories'];
                                foreach($cats as $c):
                                    $url = "ratings.php?category=".strtoupper($c) . ($filter_rating ? "&rating=$filter_rating" : "");
                            ?>
                                <a href="<?= $url ?>" class="block px-5 py-3 text-xs font-bold uppercase hover:bg-gray-50"><?= $c ?></a>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>
            </div>

            <div class="right-card-stack">
                <?php
                $sql = "SELECT 
                            r.rating, r.comment, r.created_at, r.pid,
                            u.name AS username,
                            p.name AS product_name, p.category, p.image AS product_image
                        FROM review r
                        JOIN users u ON r.user_id = u.id
                        JOIN products p ON r.pid = p.id";
                
                $params = [];
                $clauses = [];

                if ($filter_pid) { $clauses[] = "r.pid = ?"; $params[] = $filter_pid; }
                if ($filter_cat) { $clauses[] = "p.category = ?"; $params[] = $filter_cat; }
                if ($filter_rating) { $clauses[] = "r.rating = ?"; $params[] = $filter_rating; }

                if (!empty($clauses)) { $sql .= " WHERE " . implode(" AND ", $clauses); }
                $sql .= " ORDER BY r.created_at DESC";

                $select_ratings = $conn->prepare($sql);
                $select_ratings->execute($params);

                if ($select_ratings->rowCount() > 0) {
                    $idx = 0;
                    while ($rating = $select_ratings->fetch(PDO::FETCH_ASSOC)) {
                        $initials = strtoupper(substr($rating['username'], 0, 1));
                        $delay = $idx * 0.1; // Staggered animation delay
                ?>
                
                <div class="stack-card" style="animation-delay: <?= $delay; ?>s;">
                    
                    <div class="rev-header">
                        <div class="user-info">
                            <div class="avatar"><?= $initials ?></div>
                            <div>
                                <div class="u-name"><?= htmlspecialchars($rating['username']) ?></div>
                                <div class="u-date"><?= date('M d, Y', strtotime($rating['created_at'])) ?></div>
                            </div>
                        </div>
                        <div class="verified-badge"><i class="fas fa-check-circle"></i> Verified</div>
                    </div>

                    <div class="star-row">
                        <?php 
                            for ($i = 1; $i <= 5; $i++) {
                                if ($i <= $rating['rating']) {
                                    echo '<i class="fas fa-star gold-star"></i>';
                                } else {
                                    echo '<i class="far fa-star gray-star"></i>';
                                }
                            }
                        ?>
                    </div>

                    <p class="comment-text">
                        <?= !empty($rating['comment']) ? "&ldquo;" . htmlspecialchars($rating['comment']) . "&rdquo;" : "<span class='text-gray-400 italic'>Rating provided without comment.</span>" ?>
                    </p>

                    <a href="view_page.php?pid=<?= $rating['pid']; ?>" class="prod-link">
                        <div class="prod-thumb">
                            <img src="<?= (strpos($rating['product_image'], 'http') === 0) ? $rating['product_image'] : 'uploaded_img/' . $rating['product_image']; ?>" alt="">
                        </div>
                        <div class="prod-meta">
                            <div class="prod-label">Purchased Item</div>
                            <div class="prod-name-txt"><?= htmlspecialchars($rating['product_name']) ?></div>
                        </div>
                        <i class="fas fa-arrow-right text-gray-300"></i>
                    </a>

                </div>

                <?php 
                        $idx++;
                    } 
                } else {
                ?>
                    <div class="empty-area">
                        <div class="w-20 h-20 bg-white rounded-full flex items-center justify-center mx-auto mb-4 shadow-sm">
                            <i class="far fa-comment-dots text-3xl text-gray-300"></i>
                        </div>
                        <h3 class="text-xl font-black uppercase text-gray-800">No Reviews Found</h3>
                        <p class="text-sm text-gray-500 mt-2">Try adjusting your filters.</p>
                    </div>
                <?php } ?>
            </div>

        </div>

        <?php include 'footer.php'; ?>
    
    </div>

    <script>
        function toggleDropdown(id) {
            const all = document.querySelectorAll('.dropdown-menu');
            all.forEach(d => { if(d.id !== id) d.classList.remove('active'); });
            document.getElementById(id).classList.toggle('active');
        }

        window.onclick = function(event) {
            if (!event.target.closest('.relative') && !event.target.closest('.filter-btn')) {
                document.querySelectorAll('.dropdown-menu').forEach(d => d.classList.remove('active'));
            }
        }
    </script>

</body>
</html>