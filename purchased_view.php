<?php
@include 'config.php';
session_start();

$user_id = $_SESSION['user_id'] ?? null;

if (!$user_id) {
    header('location:login.php');
    exit();
}

// GET PARAMETERS
$pid = $_GET['pid'] ?? null;
$oid = $_GET['oid'] ?? null;

if(!$pid || !$oid){
    header('location:orders.php'); 
    exit();
}

// 1. FETCH PRODUCT
$select_products = $conn->prepare("SELECT * FROM `products` WHERE id = ?");
$select_products->execute([$pid]);
$fetch_products = $select_products->fetch(PDO::FETCH_ASSOC);

// 2. FETCH ORDER (To get the date)
$select_order = $conn->prepare("SELECT * FROM `orders` WHERE id = ? AND user_id = ?");
$select_order->execute([$oid, $user_id]);
$order = $select_order->fetch(PDO::FETCH_ASSOC);

if(!$fetch_products || !$order){
    header('location:orders.php');
    exit();
}

// Process Variants (IDENTICAL LOGIC TO VIEW_PAGE)
$base_price = ($fetch_products['category'] === 'SALES') ? $fetch_products['discounted_price'] : $fetch_products['price'];
$base_image = "uploaded_img/".$fetch_products['image'];
$stock_qty = $fetch_products['quantity'];
$db_variants = $fetch_products['variants'] ?? '';
$parsed_variants = []; 
$gallery_images = [$base_image];

if(!empty($db_variants)){
    if(strpos($db_variants, '__') !== false) {
        $rows = explode('||', $db_variants);
        foreach($rows as $row) {
            $cols = explode('__', $row);
            if(count($cols) >= 2) {
                $type = $cols[0];
                $name = $cols[1];
                $v_img = $cols[3] ?? '';
                $parsed_variants[$type][] = ['name'=>$name, 'price'=>$cols[2]??'', 'img'=>$v_img];
                if(!empty($v_img)) {
                    $full_img_path = "uploaded_img/" . $v_img;
                    if(!in_array($full_img_path, $gallery_images)) $gallery_images[] = $full_img_path;
                }
            }
        }
    } else {
        $groups = explode(';', $db_variants); 
        foreach($groups as $group){
           $parts = explode(':', $group);
           if(count($parts) == 2){
               $type = trim($parts[0]);
               $opts = explode(',', $parts[1]);
               foreach($opts as $opt) $parsed_variants[$type][] = ['name'=>trim($opt), 'price'=>'', 'img'=>''];
           }
        }
    }
}

// AVG RATING
$avg_rating = 0; $total_reviews = 0;
$select_avg = $conn->prepare("SELECT AVG(rating) as avg_r, COUNT(*) as total_r FROM review WHERE pid = ?");
$select_avg->execute([$pid]);
if($row_avg = $select_avg->fetch(PDO::FETCH_ASSOC)){
    $avg_rating = round($row_avg['avg_r']);
    $total_reviews = $row_avg['total_r'];
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
   <meta charset="UTF-8">
   <meta name="viewport" content="width=device-width, initial-scale=1.0">
   <title><?= $fetch_products['name'] ?> | Snapshot</title>
   
   <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.1.1/css/all.min.css">
   <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.4.1/font/bootstrap-icons.css" rel="stylesheet">
   
   <link rel="stylesheet" href="css/style.css">
   <style>
      :root { --ag-dark: #111; }
      
      /* === 1. NUCLEAR BACKGROUND FIX === */
      html, body {
          margin: 0; padding: 0;
          font-family: 'Segoe UI', Roboto, Helvetica, Arial, sans-serif;
          min-height: 100vh;
          width: 100%;
          background-image: none !important;
          background-color: #ffffff !important;
          background: #ffffff !important; /* Double force white */
          display: flex; 
          flex-direction: column;
          overflow-y: auto; 
      }

      body::before, body::after { content: none !important; display: none !important; }

      /* Scrollbars */
      * { scrollbar-width: thin; scrollbar-color: #ccc transparent; }
      ::-webkit-scrollbar { width: 8px; height: 8px; }
      ::-webkit-scrollbar-track { background: transparent; }
      ::-webkit-scrollbar-thumb { background: #ccc; border-radius: 10px; }
      ::-webkit-scrollbar-thumb:hover { background: #999; }

      .page-content {
          flex: 1; display: flex; align-items: center; justify-content: center;
          padding: 100px 20px 50px; 
      }

      /* 2. PRODUCT CARD (EXACTLY FROM VIEW_PAGE) */
      .product-main { 
          display: grid; grid-template-columns: 1fr 1fr; gap: 40px; 
          width: 100%; max-width: 950px; height: 80vh; min-height: 600px;
          margin: 0 auto; background: #fff; border-radius: 20px; padding: 40px; 
          box-shadow: 0 20px 60px rgba(0,0,0,0.08); overflow: hidden; 
          position: relative; z-index: 10;
      }
      
      /* LEFT: GALLERY */
      .product-image-container { 
          position: relative; height: 100%; width: 100%;
          border-radius: 15px; background: #fff; border: 1px solid #f0f0f0; 
          overflow: hidden; display: flex; align-items: center; justify-content: center;
      }
      
      .gallery-slider {
          display: flex; width: 100%; height: 100%;
          overflow-x: auto; scroll-snap-type: x mandatory;
          scrollbar-width: none; position: relative;
      }
      .gallery-slider::-webkit-scrollbar { display: none; }
      
      .gallery-img {
          flex: 0 0 100%; width: 100%; height: 100%;
          object-fit: contain; scroll-snap-align: center; padding: 30px;
      }

      .slider-arrow {
          position: absolute; top: 50%; transform: translateY(-50%);
          width: 36px; height: 36px;
          background: rgba(255, 255, 255, 0.9);
          border: 1px solid #eee; border-radius: 50%;
          cursor: pointer; z-index: 10;
          display: flex; align-items: center; justify-content: center;
          box-shadow: 0 4px 10px rgba(0,0,0,0.1);
          color: #333; font-size: 0.9rem; transition: all 0.2s;
      }
      .slider-arrow:hover { background: #000; color: #fff; border-color: #000; }
      .slider-arrow.prev { left: 15px; }
      .slider-arrow.next { right: 15px; }
      
      /* RIGHT: FLEX COLUMN */
      .product-info-container {
          display: flex; flex-direction: column; height: 100%; overflow: hidden;
      }

      /* STATIC TOP SECTION */
      .static-details {
          flex-shrink: 0; padding-bottom: 20px; border-bottom: 1px solid #f0f0f0;
      }

      .details-header { margin-bottom: 15px; }
      .badges-wrapper { display: flex; gap: 8px; margin-bottom: 8px; }
      .category-badge { display: inline-block; padding: 4px 10px; font-size: 0.65rem; font-weight: 700; border-radius: 4px; background: #000; color: #fff; text-transform: uppercase; }
      .brand-badge { display: inline-block; padding: 4px 10px; font-size: 0.65rem; font-weight: 700; border-radius: 4px; background: #f1f1f1; color: #555; text-transform: uppercase; }

      .product-name { font-size: 2rem; font-weight: 800; color: var(--ag-dark); line-height: 1.1; margin-bottom: 5px; }
      
      /* Purchased Specific Styling */
      .stock-status { 
          font-size: 0.75rem; font-weight: 700; color: #16a34a; 
          margin-bottom: 10px; display: block; 
          text-transform: uppercase; letter-spacing: 0.5px;
      }

      .controls-section { display: flex; flex-direction: column; gap: 12px; margin-bottom: 15px; opacity: 0.7; pointer-events: none; /* Read Only Look */ }
      .control-row { display: flex; align-items: center; gap: 15px; }
      
      .info-label { font-weight: 700; font-size: 0.75rem; color: #888; text-transform: uppercase; min-width: 50px; }
      .variant-options { display: flex; flex-wrap: wrap; gap: 6px; }
      .variant-btn { 
          padding: 6px 14px; border: 1px solid #ddd; background: #fff; color: #333; 
          font-size: 0.8rem; font-weight: 600; border-radius: 6px; 
          min-width: 36px; text-align: center;
      }

      /* ACTIONS ROW */
      .bottom-action-row { display: flex; justify-content: space-between; align-items: center; margin-top: 10px; }
      .action-left { display: flex; gap: 10px; flex: 1; }
      
      /* "Record" Button (Like Wishlist) */
      .btn-snapshot { 
          width: 40px; height: 40px; border: 1px solid #eee; border-radius: 8px; 
          background: #fff; cursor: default; 
          display: flex; align-items: center; justify-content: center; color: #999;
      }
      
      /* "Buy Again" Button (Like Add to Cart) */
      .btn-buy-again { 
          background: #000; color: #fff; padding: 10px 20px; border: none; 
          font-weight: 700; font-size: 0.85rem; cursor: pointer; border-radius: 8px; 
          display: flex; align-items: center; gap: 8px; box-shadow: 0 4px 10px rgba(0,0,0,0.2);
          text-decoration: none;
      }
      .btn-buy-again i { color: #fff !important; } /* FORCE WHITE ICON */
      .btn-buy-again:hover { background: #333; color: #fff; }
      
      .price-main { font-size: 1.6rem; font-weight: 800; color: #000; }
      .price-currency { font-size: 0.9rem; font-weight: 600; color: #333; margin-right: 2px; }

      /* TABS (Identical to View Page) */
      .tabs-container { 
          flex: 1; overflow-y: auto; margin-top: 20px; padding-right: 5px; 
          scrollbar-width: thin; scrollbar-color: #ddd transparent;
      }
      .tabs-container::-webkit-scrollbar { width: 6px; }
      .tabs-container::-webkit-scrollbar-thumb { background-color: #ddd; border-radius: 10px; }
      
      .tab-header { 
          display: flex; gap: 40px; margin-bottom: 15px; border-bottom: 1px solid #eee; 
          position: sticky; top: 0; background: #fff; z-index: 10; padding-top: 5px; 
      }
      
      .tab-link { 
          background: none; border: none; cursor: pointer; position: relative; 
          width: 100px; height: 30px; 
          display: flex; align-items: center; justify-content: center;
          overflow: hidden; 
      }
      .tab-text {
          position: absolute; font-size: 0.8rem; font-weight: 700; color: #999; text-transform: uppercase; letter-spacing: 0.5px;
          top: 50%; transform: translateY(-50%);
      }
      .tab-link::after { 
          content: ''; position: absolute; bottom: 0; left: 0; width: 100%; height: 2px; background: #000; 
          transform: scaleX(0); transition: transform 0.3s; 
      }
      .tab-link.active::after { transform: scaleX(1); }
      .tab-link.active .tab-text { color: #000; }

      .tab-content { display: none; color: #666; line-height: 1.6; font-size: 0.85rem; text-align: left; padding-bottom: 20px; }
      .tab-content.active { display: block; animation: fadeIn 0.4s ease; }

      /* Watermark & Back Button */
      .watermark { 
          position: absolute; top: 20px; left: 20px; z-index: 20; 
          background: #22c55e; color: white; padding: 6px 14px; 
          border-radius: 50px; font-weight: 800; font-size: 0.7rem; text-transform: uppercase; letter-spacing: 1px; box-shadow: 0 4px 10px rgba(0,0,0,0.1);
      }
      .back-btn { 
          position: absolute; top: 20px; right: 20px; z-index: 50; 
          background: white; width: 40px; height: 40px; border-radius: 50%; 
          display: flex; align-items: center; justify-content: center; 
          box-shadow: 0 4px 10px rgba(0,0,0,0.1); color: #333; text-decoration: none; transition: 0.2s; 
      }
      .back-btn:hover { transform: scale(1.1); color: #000; }

      @media (max-width: 900px) { 
          .page-content { padding: 100px 20px 40px; }
          .product-main { grid-template-columns: 1fr; height: auto; max-width: 100%; gap: 30px; padding: 30px; } 
          .product-image-container { height: 400px; }
          .product-info-container { height: auto; overflow: visible; }
          .tabs-container { overflow: visible; height: auto; }
      }
      @keyframes fadeIn { from{opacity:0; transform:translateY(5px)} to{opacity:1; transform:translateY(0)} }
   </style>
</head>
<body>
   
<?php include 'header.php'; ?>

<div class="page-content">
   
   <div class="product-main">
      <a href="orders.php" class="back-btn"><i class="fas fa-times"></i></a>

      <div class="product-image-container">
         <div class="watermark"><i class="fas fa-check-circle"></i> Purchased</div>
         
         <button class="slider-arrow prev" onclick="moveSlide(-1)"><i class="fas fa-chevron-left"></i></button>
         
         <div class="gallery-slider" id="gallerySlider">
             <?php foreach($gallery_images as $g_img): ?>
                <img class="gallery-img" src="<?= $g_img; ?>">
             <?php endforeach; ?>
         </div>

         <button class="slider-arrow next" onclick="moveSlide(1)"><i class="fas fa-chevron-right"></i></button>
      </div>

      <div class="product-info-container">
         
         <div class="static-details">
            <div class="details-header">
                <div class="badges-wrapper">
                    <span class="category-badge"><?= $fetch_products['category']; ?></span>
                    <?php if(!empty($fetch_products['brand'])): ?>
                        <span class="brand-badge"><?= $fetch_products['brand']; ?></span>
                    <?php endif; ?>
                </div>

                <h1 class="product-name"><?= $fetch_products['name']; ?></h1>
                
                <p class="stock-status">
                    PURCHASED ON: <?= date('d M Y', strtotime($order['placed_on'])) ?>
                </p>
            </div>

            <div class="controls-section">
                <?php if(!empty($parsed_variants)): ?>
                    <?php foreach($parsed_variants as $type => $options): ?>
                        <div class="control-row">
                            <span class="info-label"><?= $type; ?></span>
                            <div class="variant-options">
                                <?php foreach($options as $opt): ?>
                                    <div class="variant-btn"><?= $opt['name']; ?></div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>

            <div class="bottom-action-row">
               <div class="action-left">
                   <a href="view_page.php?pid=<?= $pid; ?>" class="btn-buy-again">
                      <i class="fas fa-redo"></i> Buy Again
                   </a>
                   <div class="btn-snapshot" title="This is a snapshot record">
                      <i class="fas fa-history"></i>
                   </div>
               </div>

               <div class="price-display-bottom">
                   <span class="price-currency">RM</span> 
                   <span class="price-main"><?= number_format($base_price, 2); ?></span>
               </div>
            </div>
         </div>

         <div class="tabs-container">
            <div class="tab-header">
                <button class="tab-link active">
                    <span class="tab-text">Description</span>
                </button>
            </div>
            
            <div id="desc" class="tab-content active">
                <p style="white-space: pre-line;"><?= $fetch_products['details']; ?></p>
            </div>
         </div>

      </div>
   </div>

</div>

<?php include 'footer.php'; ?>

<script>
    function moveSlide(direction) {
        const slider = document.getElementById('gallerySlider');
        const scrollAmount = slider.clientWidth; 
        slider.scrollBy({ left: scrollAmount * direction, behavior: 'smooth' });
    }
</script>
</body>
</html>