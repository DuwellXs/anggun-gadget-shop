<?php
@include 'config.php';
session_start();

$user_id = $_SESSION['user_id'] ?? null;

// ==========================================
// 0. HELPER FUNCTIONS
// ==========================================
function format_sold($num) {
    if ($num >= 1000000) return number_format($num / 1000000, 1) . 'M';
    if ($num >= 1000) return number_format($num / 1000, 1) . 'k';
    return $num;
}

// ==========================================
// 1. CART & WISHLIST LOGIC
// ==========================================
if(isset($_POST['add_to_wishlist'])){
   if($user_id == null){
      $message[] = 'Please login to use the wishlist!';
   } else {
      $pid = $_POST['pid'];
      $p_name = $_POST['p_name'];
      $p_price = $_POST['p_price'];
      $p_image = $_POST['p_image'];

      $check_wishlist_numbers = $conn->prepare("SELECT * FROM `wishlist` WHERE name = ? AND user_id = ?");
      $check_wishlist_numbers->execute([$p_name, $user_id]);
      $check_cart_numbers = $conn->prepare("SELECT * FROM `cart` WHERE name = ? AND user_id = ?");
      $check_cart_numbers->execute([$p_name, $user_id]);

      if($check_wishlist_numbers->rowCount() > 0){
         $message[] = 'Already in your wishlist!';
      }elseif($check_cart_numbers->rowCount() > 0){
         $message[] = 'Already in your cart!';
      }else{
         $insert_wishlist = $conn->prepare("INSERT INTO `wishlist`(user_id, pid, name, price, image) VALUES(?,?,?,?,?)");
         $insert_wishlist->execute([$user_id, $pid, $p_name, $p_price, $p_image]);
         $message[] = 'Added to wishlist!';
      }
   }
}

if(isset($_POST['add_to_cart'])){
   if($user_id == null){
      $message[] = 'Please login to add to cart!';
   } else {
      $pid = $_POST['pid'];
      $p_name = $_POST['p_name'];
      $p_price = $_POST['p_price'];
      $p_image = $_POST['p_image'];
      $p_qty = $_POST['p_qty'];

      $check_cart_numbers = $conn->prepare("SELECT * FROM `cart` WHERE name = ? AND user_id = ?");
      $check_cart_numbers->execute([$p_name, $user_id]);

      if($check_cart_numbers->rowCount() > 0){
         $message[] = 'Already added to cart!';
      }else{
         $check_wishlist_numbers = $conn->prepare("SELECT * FROM `wishlist` WHERE name = ? AND user_id = ?");
         $check_wishlist_numbers->execute([$p_name, $user_id]);

         if($check_wishlist_numbers->rowCount() > 0){
            $delete_wishlist = $conn->prepare("DELETE FROM `wishlist` WHERE name = ? AND user_id = ?");
            $delete_wishlist->execute([$p_name, $user_id]);
         }
         $empty_variant = ''; 
         $insert_cart = $conn->prepare("INSERT INTO `cart`(user_id, pid, name, price, quantity, image, selected_variants) VALUES(?,?,?,?,?,?,?)");
         $insert_cart->execute([$user_id, $pid, $p_name, $p_price, $p_qty, $p_image, $empty_variant]);
         $message[] = 'Added to cart!';
      }
   }
}

// ==========================================
// 2. HANDLE AJAX REQUESTS
// ==========================================
if (isset($_GET['ajax_mode'])) {
    
    $where_clauses = ["p.category != 'Banner'"];
    $params = [];

    if (!empty($_GET['search'])) {
        $where_clauses[] = "p.name LIKE ?";
        $params[] = "%" . $_GET['search'] . "%";
    }
    if (!empty($_GET['category'])) {
        $where_clauses[] = "p.category = ?";
        $params[] = $_GET['category'];
    }
    if (!empty($_GET['brands']) && is_array($_GET['brands'])) {
        $in_placeholders = implode(',', array_fill(0, count($_GET['brands']), '?'));
        $where_clauses[] = "p.brand IN ($in_placeholders)";
        foreach ($_GET['brands'] as $b) {
            $params[] = $b;
        }
    }
    if (!empty($_GET['min_price'])) {
        $where_clauses[] = "p.price >= ?";
        $params[] = $_GET['min_price'];
    }
    if (!empty($_GET['max_price'])) {
        $where_clauses[] = "p.price <= ?";
        $params[] = $_GET['max_price'];
    }

    $sort_option = $_GET['sort'] ?? ''; 
    switch ($sort_option) {
        case 'name_asc': $order = "ORDER BY p.name ASC"; break;
        case 'latest': $order = "ORDER BY p.id DESC"; break;
        case 'top_sales': $order = "ORDER BY p.sales_count DESC"; break;
        case 'popular': $order = "ORDER BY p.views DESC"; break;
        default: $order = "ORDER BY p.views DESC"; break; 
    }

    // Fixed SQL logic: Use strict equality and rounding to ensure precise star filter
    $sql = "SELECT p.*, IFNULL(AVG(r.rating), 0) as avg_rating 
            FROM `products` p 
            LEFT JOIN `review` r ON p.id = r.pid 
            WHERE " . implode(" AND ", $where_clauses) . "
            GROUP BY p.id";

    if (!empty($_GET['rating_filter'])) {
        // We ROUND the average rating to match the integer filter (e.g. 4.5 becomes 5, or use FLOOR/CEIL as per your need)
        $sql .= " HAVING ROUND(avg_rating) = " . (int)$_GET['rating_filter'];
    }

    $sql .= " " . $order;

    $stmt = $conn->prepare($sql);
    $stmt->execute($params);

    if ($stmt->rowCount() > 0) {
        while ($fetch_products = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $avg_rating = round($fetch_products['avg_rating'], 1);
            $img_src = (strpos($fetch_products['image'], 'http') === 0) ? $fetch_products['image'] : 'uploaded_img/' . $fetch_products['image'];
            
            echo '
            <form action="" method="POST" class="group block h-full">
                <div class="relative w-full bg-white border border-gray-100 rounded-[20px] overflow-hidden transition-all duration-300 hover:shadow-2xl hover:border-gray-200 hover:-translate-y-1 h-full flex flex-col">
                    <div class="relative w-full aspect-[4/4] card-gradient p-6 overflow-hidden">
                        <a href="view_page.php?pid='.$fetch_products['id'].'" class="flex items-center justify-center w-full h-full">
                            <img src="'.$img_src.'" class="w-full h-full object-contain mix-blend-multiply group-hover:scale-105 transition-transform duration-500 ease-out" alt="'.$fetch_products['name'].'">
                        </a>
                        <div class="absolute top-4 left-4 flex flex-col gap-2 pointer-events-none z-10">';
                            if($fetch_products['category'] === 'SALES' || ($fetch_products['discount_percentage'] ?? 0) > 0) {
                                echo '<span class="px-2.5 py-1 bg-red-600 text-white text-[10px] font-bold uppercase tracking-wider rounded-md shadow-sm">-'.$fetch_products['discount_percentage'].'%</span>';
                            }
            echo '      </div>
                        <button type="submit" name="add_to_wishlist" class="group/wishlist absolute top-4 right-4 bg-transparent transition-all duration-300 z-20 opacity-0 group-hover:opacity-100 -translate-y-2 group-hover:translate-y-0 active:scale-90 active:opacity-50 outline-none border-none">
                            <div class="relative w-6 h-6 flex items-center justify-center">
                                <i class="far fa-heart text-lg text-black transition-opacity duration-200 group-hover/wishlist:opacity-0"></i>
                                <i class="fas fa-heart text-lg text-red-500 absolute inset-0 opacity-0 transition-opacity duration-200 group-hover/wishlist:opacity-100 drop-shadow-sm"></i>
                            </div>
                        </button>
                        '.(empty($fetch_products['variants']) 
                            ? '<button type="submit" name="add_to_cart" class="absolute bottom-4 right-4 w-10 h-10 rounded-full bg-black text-white flex items-center justify-center translate-y-20 opacity-0 group-hover:translate-y-0 group-hover:opacity-100 transition-all duration-300 shadow-xl z-20"><i class="fas fa-plus"></i></button>' 
                            : '<a href="view_page.php?pid='.$fetch_products['id'].'" class="absolute bottom-4 right-4 w-10 h-10 rounded-full bg-black text-white flex items-center justify-center translate-y-20 opacity-0 group-hover:translate-y-0 group-hover:opacity-100 transition-all duration-300 shadow-xl z-20"><i class="fas fa-eye"></i></a>'
                        ).'
                    </div>
                    <div class="p-5 flex flex-col flex-grow border-t border-gray-50 bg-white z-10 relative">
                        <div class="mb-2">';
                            if(isset($fetch_products['discount_percentage']) && $fetch_products['discount_percentage'] > 0){
                                echo '<div class="flex items-baseline gap-2">
                                    <span class="text-xl font-black text-red-600 uppercase tracking-tight">RM'.$fetch_products['discounted_price'].'</span>
                                    <span class="text-xs text-gray-400 line-through font-medium tracking-normal">RM'.$fetch_products['price'].'</span>
                                </div>';
                            } else {
                                echo '<span class="text-xl font-black text-gray-900 uppercase tracking-tight">RM'.$fetch_products['price'].'</span>';
                            }
            echo '      </div>
                        <a href="view_page.php?pid='.$fetch_products['id'].'" class="text-sm font-bold text-gray-800 uppercase tracking-tight leading-tight hover:text-black transition-colors block no-underline line-clamp-2 mb-1">'.$fetch_products['name'].'</a>
                        <div class="text-[10px] font-bold text-gray-400 uppercase tracking-widest mb-4">'.$fetch_products['category'].'</div>
                        <div class="mt-auto"></div>
                        <div class="flex justify-between items-center pt-3 border-t border-gray-100">
                            <a href="ratings.php?pid='.$fetch_products['id'].'" class="flex text-yellow-400 gap-0.5 text-[10px] no-underline hover:scale-105 transition-transform" title="Read Reviews">';
                                for($i = 1; $i <= 5; $i++){
                                    if($i <= $avg_rating) echo '<i class="fas fa-star"></i>';
                                    elseif($i - 0.5 <= $avg_rating) echo '<i class="fas fa-star-half-alt"></i>';
                                    else echo '<i class="far fa-star text-gray-200"></i>';
                                }
            echo '              <span class="ml-1 text-gray-400 font-bold">'.($avg_rating > 0 ? $avg_rating : '').'</span>
                            </a>
                            <div class="text-[10px] font-bold text-gray-500 uppercase tracking-wide">'.format_sold($fetch_products['sales_count'] ?? 0).' Sold</div>
                        </div>
                        <input type="hidden" name="pid" value="'.$fetch_products['id'].'">
                        <input type="hidden" name="p_name" value="'.$fetch_products['name'].'">
                        <input type="hidden" name="p_image" value="'.$fetch_products['image'].'">
                        <input type="hidden" name="p_qty" value="1">
                        <input type="hidden" name="p_price" value="'.((isset($fetch_products['discount_percentage']) && $fetch_products['discount_percentage'] > 0) ? $fetch_products['discounted_price'] : $fetch_products['price']).'">
                    </div>
                </div>
            </form>';
        }
    } else {
        echo '<div class="col-span-full py-20 text-center bg-gray-50 rounded-[30px] border border-dashed border-gray-200">
            <div class="text-6xl text-gray-300 mb-4"><i class="fas fa-search"></i></div>
            <h3 class="text-xl font-bold text-gray-900 mb-2 uppercase tracking-tight">No Products Found</h3>
            <p class="text-gray-500 text-sm mb-6">We couldn\'t find what you were looking for.</p>
            <button onclick="resetFilters()" class="inline-block px-8 py-3 bg-black text-white text-xs font-bold uppercase tracking-widest rounded-full hover:bg-gray-800 transition-all shadow-lg">Clear All Filters</button>
        </div>';
    }
    exit;
}

// ==========================================
// 3. INITIAL PAGE SETUP
// ==========================================
$page_title = "All Products";
if (isset($_GET['search']) && !empty($_GET['search'])) {
    $page_title = 'Search: "' . htmlspecialchars($_GET['search']) . '"';
} 
if (isset($_GET['category']) && !empty($_GET['category'])) {
    $cat_val = $_GET['category'];
    $titles = ['SALES' => 'Sales', 'IPHONE' => 'iPhone', 'ANDROID' => 'Android', 'AUDIO' => 'Audio', 'POWER' => 'Power', 'ACCESSORIES' => 'Accessories'];
    $page_title = $titles[strtoupper($cat_val)] ?? ucfirst(strtolower($cat_val));
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
   <meta charset="UTF-8">
   <meta name="viewport" content="width=device-width, initial-scale=1.0">
   <title><?= $page_title; ?> | Anggun Gadget</title>
   
   <script src="https://cdn.tailwindcss.com"></script>
   <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" integrity="sha512-iecdLmaskl7CVkqkXNQ/ZH/XLlvWZOJyj7Yy7tcenmpD1ypASozpmT/E0iPtmFIB46ZmdtAc9eNBvH0H/ZpiBw==" crossorigin="anonymous" referrerpolicy="no-referrer" />
   <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800;900&display=swap" rel="stylesheet">
   
   <style>
       /* Design preserved exactly */
       body { font-family: 'Inter', sans-serif; background-color: #ffffff; color: #111; overflow-x: hidden; }
       .card-gradient { background: linear-gradient(180deg, #FFFFFF 0%, #f8fafd 100%); }
       .no-scrollbar::-webkit-scrollbar { display: none; }
       .no-scrollbar { -ms-overflow-style: none; scrollbar-width: none; }
       .fas, .far, .fab { font-family: "Font Awesome 6 Free" !important; }
       .text-shadow-pop { text-shadow: 2px 2px 0px #cbd5e1; }
       #filter-drawer { transition: transform 0.4s cubic-bezier(0.16, 1, 0.3, 1); }
       #filter-overlay { transition: opacity 0.4s ease; }
       .loader { border: 3px solid #f3f3f3; border-top: 3px solid #000; border-radius: 50%; width: 20px; height: 20px; animation: spin 1s linear infinite; display: none; margin: 20px auto; }
       @keyframes spin { 0% { transform: rotate(0deg); } 100% { transform: rotate(360deg); } }
       .checkbox-wrapper input:checked + div { background-color: #000; border-color: #000; }
       .checkbox-wrapper input:checked + div:after {
           content: ''; display: block; width: 4px; height: 8px; border: solid white; border-width: 0 2px 2px 0; transform: rotate(45deg);
       }
   </style>
</head>
<body class="pt-24 flex flex-col min-h-screen"> 
   
<?php include 'header.php'; ?>

<div id="filter-overlay" onclick="closeFilter()" class="fixed inset-0 bg-black/20 backdrop-blur-sm z-[55] opacity-0 pointer-events-none transition-opacity duration-300 lg:hidden"></div>
<aside id="filter-drawer" class="fixed top-0 left-0 h-full w-[320px] bg-white z-[60] transform -translate-x-full border-r border-gray-100 flex flex-col lg:hidden">
    <div class="p-6"><button onclick="closeFilter()" class="w-full bg-black text-white py-3 rounded-lg">Close</button></div>
</aside>

<div class="max-w-[95rem] mx-auto px-4 md:px-8 py-6 w-full flex flex-col lg:flex-row gap-10">

    <aside class="hidden lg:block w-[280px] shrink-0">
        <div class="sticky top-28 h-fit max-h-[calc(100vh-120px)] overflow-y-auto no-scrollbar pr-4">
            <div class="pb-3 mb-4 border-b border-gray-100">
                <h3 class="text-lg font-black tracking-tight text-black uppercase">Filters</h3>
            </div>

            <div id="desktopFilterContainer">
                <div class="mb-5">
                    <h4 class="text-[10px] font-bold uppercase tracking-widest text-gray-400 mb-3">Search</h4>
                    <div class="relative">
                        <input type="text" id="searchInput" oninput="debounceFetch()" placeholder="Search..." class="w-full pl-10 pr-4 py-3 bg-white border border-gray-200 rounded-xl text-xs font-bold text-black focus:outline-none focus:border-black transition-colors">
                        <i class="fas fa-search absolute left-3 top-1/2 -translate-y-1/2 text-gray-400"></i>
                    </div>
                </div>

                <div class="mb-5">
                    <h4 class="text-[10px] font-bold uppercase tracking-widest text-gray-400 mb-3">Categories</h4>
                    <div class="space-y-2">
                        <button type="button" onclick="setFilter('category', '')" class="filter-btn block w-full text-left px-4 py-2.5 rounded-lg text-xs font-bold uppercase transition-all bg-black text-white shadow-md" data-val="">All Products</button>
                        <?php 
                           $cats = ['SALES', 'IPHONE', 'ANDROID', 'AUDIO', 'POWER', 'ACCESSORIES'];
                           foreach($cats as $c): 
                        ?>
                        <button type="button" onclick="setFilter('category', '<?= $c ?>')" class="filter-btn block w-full text-left px-4 py-2.5 rounded-lg text-xs font-bold uppercase transition-all text-gray-500 hover:bg-gray-100" data-val="<?= $c ?>"><?= ucfirst(strtolower($c)) ?></button>
                        <?php endforeach; ?>
                    </div>
                </div>

                <div class="mb-5">
                    <h4 class="text-[10px] font-bold uppercase tracking-widest text-gray-400 mb-3">Rating</h4>
                    <div class="flex items-center gap-2 cursor-pointer group" id="starContainer">
                        <?php for($i=1; $i<=5; $i++): ?>
                            <i class="far fa-star text-lg text-gray-300 hover:text-yellow-400 transition-colors star-icon" onclick="toggleRating(<?= $i ?>)" data-val="<?= $i ?>"></i>
                        <?php endfor; ?>
                    </div>
                    <p class="text-[10px] text-gray-400 mt-2">Click to filter by rating (Toggle)</p>
                </div>

                <div class="mb-5">
                    <h4 class="text-[10px] font-bold uppercase tracking-widest text-gray-400 mb-3">Brands</h4>
                    <div class="space-y-2 max-h-48 overflow-y-auto no-scrollbar">
                        <?php 
                            $brand_query = $conn->prepare("SELECT DISTINCT brand FROM products WHERE brand IS NOT NULL AND brand != '' ORDER BY brand ASC");
                            $brand_query->execute();
                            $selected_brands = (isset($_GET['brands']) && is_array($_GET['brands'])) ? $_GET['brands'] : [];
                            if($brand_query->rowCount() > 0) {
                                while($row = $brand_query->fetch(PDO::FETCH_ASSOC)) {
                                    $b = htmlspecialchars($row['brand']);
                                    $isChecked = in_array($b, $selected_brands) ? 'checked' : '';
                        ?>
                        <label class="flex items-center gap-3 cursor-pointer group checkbox-wrapper p-1 hover:bg-gray-50 rounded-md transition-colors">
                            <input type="checkbox" name="brands" value="<?= $b; ?>" class="hidden brand-checkbox" <?= $isChecked; ?> onchange="handleBrandChange()">
                            <div class="w-4 h-4 border border-gray-300 rounded flex items-center justify-center transition-all group-hover:border-black bg-white"></div>
                            <span class="text-xs font-bold text-gray-500 uppercase tracking-wide group-hover:text-black transition-colors"><?= $b; ?></span>
                        </label>
                        <?php } } ?>
                    </div>
                </div>

                <div class="mb-8">
                    <h4 class="text-[10px] font-bold uppercase tracking-widest text-gray-400 mb-3">Price Range</h4>
                    <div class="flex items-center gap-2 mb-4">
                        <input type="number" id="minPrice" placeholder="RM Min" class="w-full px-3 py-2 bg-white border border-gray-200 rounded-lg text-xs font-bold text-black">
                        <span class="text-gray-300">-</span>
                        <input type="number" id="maxPrice" placeholder="RM Max" class="w-full px-3 py-2 bg-white border border-gray-200 rounded-lg text-xs font-bold text-black">
                    </div>
                    <div class="flex gap-2">
                        <button type="button" onclick="fetchProducts()" class="flex-1 py-2.5 bg-black text-white text-[10px] font-bold uppercase tracking-widest rounded-lg hover:bg-gray-800 transition-colors shadow-lg">Apply</button>
                        <button type="button" onclick="resetFilters()" class="flex-1 py-2.5 bg-gray-100 text-gray-500 text-center text-[10px] font-bold uppercase tracking-widest rounded-lg hover:bg-gray-200 transition-colors no-underline">Reset</button>
                    </div>
                </div>
            </div>
        </div>
    </aside>

    <main class="flex-1">
        <div class="flex flex-col md:flex-row items-end md:items-center justify-between gap-4 pb-2 mb-4 border-b border-gray-100">
            <div>
                <span class="text-xs font-bold uppercase tracking-widest text-gray-500 mb-1 block">Shop Premium Tech</span>
                <h1 id="pageTitle" class="text-[3rem] leading-[1.1] font-black tracking-tighter text-black uppercase text-shadow-pop transition-all duration-300"><?= $page_title; ?></h1>
            </div>
            <div class="flex items-center gap-4">
                <div id="itemCount" class="px-4 py-1.5 bg-gray-50 rounded-full text-[10px] font-bold text-gray-500 border border-gray-100 uppercase tracking-wide">... Items</div>
            </div>
        </div>

        <div class="mb-8 flex flex-col md:flex-row items-center justify-between gap-4">
            <button onclick="openFilter()" class="lg:hidden flex items-center gap-2 px-6 py-3 bg-black text-white rounded-full text-xs font-bold uppercase tracking-widest hover:bg-gray-800 transition-all shadow-lg w-full md:w-auto justify-center"><i class="fas fa-sliders-h"></i> Filters</button>
            <div class="flex flex-wrap items-center gap-2 w-full md:w-auto justify-center md:justify-end">
                <button type="button" onclick="setFilter('sort', 'popular')" class="sort-btn px-4 py-2 rounded-full text-[10px] font-bold uppercase transition-all bg-white text-gray-500 border border-gray-200" data-val="popular">Popular</button>
                <button type="button" onclick="setFilter('sort', 'latest')" class="sort-btn px-4 py-2 rounded-full text-[10px] font-bold uppercase transition-all bg-white text-gray-500 border border-gray-200" data-val="latest">Newest</button>
                <button type="button" onclick="setFilter('sort', 'top_sales')" class="sort-btn px-4 py-2 rounded-full text-[10px] font-bold uppercase transition-all bg-white text-gray-500 border border-gray-200" data-val="top_sales">Top Sales</button>
                <button type="button" onclick="setFilter('sort', 'name_asc')" class="sort-btn px-4 py-2 rounded-full text-[10px] font-bold uppercase transition-all bg-white text-gray-500 border border-gray-200" data-val="name_asc">A-Z</button>
            </div>
        </div>

        <div id="productGrid" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-3 2xl:grid-cols-4 gap-6 min-h-[500px]">
            <div class="loader" style="display:block;"></div>
        </div>
    </main>
</div>

<?php include 'footer.php'; ?>

<script>
    // Functions preserved exactly
    document.addEventListener("DOMContentLoaded", function() {
        const msgs = document.querySelectorAll('.message');
        if(msgs.length > 0) {
            setTimeout(() => {
                msgs.forEach(msg => {
                    msg.style.transition = "opacity 0.5s ease, transform 0.5s ease";
                    msg.style.opacity = "0";
                    msg.style.transform = "translateY(-20px)";
                    setTimeout(() => msg.remove(), 500);
                });
            }, 3000);
        }
    });

    let state = {
        search: '',
        category: '<?= isset($_GET['category']) ? $_GET['category'] : '' ?>',
        brands: [],
        rating_filter: 0,
        min_price: '',
        max_price: '',
        sort: ''
    };

    document.addEventListener("DOMContentLoaded", () => {
        const urlParams = new URLSearchParams(window.location.search);
        if(urlParams.has('category')) state.category = urlParams.get('category');
        if(urlParams.has('sort')) state.sort = urlParams.get('sort');
        updateUI();
        fetchProducts();
    });

    function fetchProducts() {
        const grid = document.getElementById('productGrid');
        grid.style.opacity = '0.5';
        const params = new URLSearchParams();
        params.append('ajax_mode', '1');
        if(state.search) params.append('search', state.search);
        if(state.category) params.append('category', state.category);
        if(state.rating_filter > 0) params.append('rating_filter', state.rating_filter);
        if(state.sort) params.append('sort', state.sort);
        if(state.brands.length > 0) { state.brands.forEach(b => params.append('brands[]', b)); }
        if(document.getElementById('minPrice').value) params.append('min_price', document.getElementById('minPrice').value);
        if(document.getElementById('maxPrice').value) params.append('max_price', document.getElementById('maxPrice').value);

        const displayParams = new URLSearchParams(params);
        displayParams.delete('ajax_mode');
        window.history.replaceState({}, '', '?' + displayParams.toString());
        updateTitle();

        fetch('shop.php?' + params.toString())
            .then(response => response.text())
            .then(html => {
                grid.innerHTML = html;
                grid.style.opacity = '1';
                const count = (html.match(/<form/g) || []).length;
                document.getElementById('itemCount').innerText = count + " Items";
            })
            .catch(err => console.error('Error:', err));
    }

    function updateTitle() {
        const titleEl = document.getElementById('pageTitle');
        if (state.search) titleEl.innerText = 'Search: "' + state.search + '"';
        else if (state.category) {
            const mapping = {'SALES':'Sales', 'IPHONE':'iPhone', 'ANDROID':'Android', 'AUDIO':'Audio', 'POWER':'Power', 'ACCESSORIES':'Accessories'};
            titleEl.innerText = mapping[state.category.toUpperCase()] || state.category;
        } else titleEl.innerText = 'All Products';
    }

    function toggleRating(val) {
        if (state.rating_filter === val) { state.rating_filter = 0; } 
        else { state.rating_filter = val; }
        updateStars();
        fetchProducts();
    }

    function updateStars() {
        const stars = document.querySelectorAll('.star-icon');
        stars.forEach(star => {
            const starVal = parseInt(star.dataset.val);
            if (starVal <= state.rating_filter) {
                star.classList.remove('far', 'text-gray-300');
                star.classList.add('fas', 'text-yellow-400');
            } else {
                star.classList.remove('fas', 'text-yellow-400');
                star.classList.add('far', 'text-gray-300');
            }
        });
    }

    function setFilter(type, val) {
        if(type === 'sort') state.sort = (state.sort === val) ? '' : val;
        else state[type] = val;
        updateUI();
        fetchProducts();
    }

    function handleBrandChange() {
        const checkboxes = document.querySelectorAll('.brand-checkbox:checked');
        state.brands = Array.from(checkboxes).map(cb => cb.value);
        fetchProducts();
    }

    function updateUI() {
        document.querySelectorAll('.filter-btn').forEach(btn => {
            const isMatch = btn.dataset.val === state.category;
            btn.className = `filter-btn block w-full text-left px-4 py-2.5 rounded-lg text-xs font-bold uppercase transition-all ${isMatch ? 'bg-black text-white shadow-md' : 'text-gray-500 hover:bg-gray-100'}`;
        });
        document.querySelectorAll('.sort-btn').forEach(btn => {
            const isMatch = btn.dataset.val === state.sort;
            btn.className = `sort-btn px-4 py-2 rounded-full text-[10px] font-bold uppercase transition-all ${isMatch ? 'bg-black text-white shadow-md' : 'bg-white text-gray-500 border border-gray-200'}`;
        });
        updateStars();
    }

    let timeout = null;
    function debounceFetch() {
        state.search = document.getElementById('searchInput').value;
        clearTimeout(timeout);
        timeout = setTimeout(fetchProducts, 500);
    }

    function resetFilters() {
        state = { search: '', category: '', brands: [], rating_filter: 0, min_price: '', max_price: '', sort: '' };
        document.getElementById('searchInput').value = '';
        document.getElementById('minPrice').value = '';
        document.getElementById('maxPrice').value = '';
        document.querySelectorAll('.brand-checkbox').forEach(cb => cb.checked = false);
        updateUI();
        fetchProducts();
    }

    function openFilter() { document.getElementById('filter-drawer').classList.remove('-translate-x-full'); document.getElementById('filter-overlay').classList.remove('opacity-0', 'pointer-events-none'); }
    function closeFilter() { document.getElementById('filter-drawer').classList.add('-translate-x-full'); document.getElementById('filter-overlay').classList.add('opacity-0', 'pointer-events-none'); }
</script>
</body>
</html>