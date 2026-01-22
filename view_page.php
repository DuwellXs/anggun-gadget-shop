<?php
@include 'config.php';
session_start();

$user_id = $_SESSION['user_id'] ?? null;

// [HANDLE CART]
if(isset($_POST['add_to_cart'])){
   if(!isset($user_id)){
      header('location:login.php');
      exit; 
   }
   $pid = $_POST['pid'];
   $p_name = $_POST['p_name'];
   $p_price = $_POST['p_price']; 
   $p_image = $_POST['p_image'];
   $p_qty = $_POST['p_qty'];
   $p_variants = $_POST['selected_variants'] ?? '';

   $insert_cart = $conn->prepare("INSERT INTO `cart`(user_id, pid, name, price, quantity, image, selected_variants) VALUES(?,?,?,?,?,?,?)");
   $insert_cart->execute([$user_id, $pid, $p_name, $p_price, $p_qty, $p_image, $p_variants]);
   $toast_message[] = 'Added to cart!';
}

// [HANDLE WISHLIST]
if(isset($_POST['add_to_wishlist'])){
   if(!isset($user_id)){
      header('location:login.php');
      exit;
   }
   $pid = $_POST['pid'];
   $p_name = $_POST['p_name'];
   $p_price = $_POST['p_price'];
   $p_image = $_POST['p_image'];

   $check_wishlist = $conn->prepare("SELECT * FROM `wishlist` WHERE name = ? AND user_id = ?");
   $check_wishlist->execute([$p_name, $user_id]);

   if($check_wishlist->rowCount() > 0){
      $toast_message[] = 'Already in wishlist!';
   }else{
      $insert_wishlist = $conn->prepare("INSERT INTO `wishlist`(user_id, pid, name, price, image) VALUES(?,?,?,?,?)");
      $insert_wishlist->execute([$user_id, $pid, $p_name, $p_price, $p_image]);
      $toast_message[] = 'Added to wishlist!';
   }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
   <meta charset="UTF-8">
   <meta name="viewport" content="width=device-width, initial-scale=1.0">
   <title>Product Details | Anggun Gadget</title>
   
   <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.1.1/css/all.min.css">
   <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.4.1/font/bootstrap-icons.css" rel="stylesheet">
   <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" />
   
   <link rel="stylesheet" href="css/style.css">

   <style>
      :root { --ag-dark: #111; }
      
      html, body {
          margin: 0; padding: 0;
          font-family: 'Segoe UI', Roboto, Helvetica, Arial, sans-serif;
          min-height: 100vh;
          width: 100%;
          background-image: none !important;
          background-color: #ffffff !important;
          background: linear-gradient(180deg, #FFFFFF 0%, #dde1e7 100%) !important;
          background-size: cover !important;
          background-repeat: no-repeat !important;
          background-attachment: fixed !important;
          display: flex; 
          flex-direction: column;
          overflow-y: auto; 
      }

      body::before, body::after { content: none !important; display: none !important; }

      * { scrollbar-width: thin; scrollbar-color: #ccc transparent; }
      ::-webkit-scrollbar { width: 8px; height: 8px; }
      ::-webkit-scrollbar-track { background: transparent; }
      ::-webkit-scrollbar-thumb { background: #ccc; border-radius: 10px; }
      ::-webkit-scrollbar-thumb:hover { background: #999; }

      .message { display: none !important; } 

      .page-content {
          flex: 1; display: flex; align-items: center; justify-content: center;
          padding: 100px 20px 50px; 
      }

      .product-main { 
          display: grid; grid-template-columns: 1fr 1fr; gap: 40px; 
          width: 100%; max-width: 950px; height: 80vh; min-height: 600px;
          margin: 0 auto; background: #fff; border-radius: 20px; padding: 40px; 
          box-shadow: 0 20px 60px rgba(0,0,0,0.08); overflow: hidden; 
          position: relative; z-index: 10;
      }
      
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
      
      .product-info-container {
          display: flex; flex-direction: column; height: 100%; overflow: hidden;
      }

      .static-details {
          flex-shrink: 0; padding-bottom: 20px; border-bottom: 1px solid #f0f0f0;
      }

      .details-header { margin-bottom: 15px; }
      .badges-wrapper { display: flex; gap: 8px; margin-bottom: 8px; }
      .category-badge { display: inline-block; padding: 4px 10px; font-size: 0.65rem; font-weight: 700; border-radius: 4px; background: #000; color: #fff; text-transform: uppercase; }
      .brand-badge { display: inline-block; padding: 4px 10px; font-size: 0.65rem; font-weight: 700; border-radius: 4px; background: #f1f1f1; color: #555; text-transform: uppercase; }

      .product-name { font-size: 2rem; font-weight: 800; color: var(--ag-dark); line-height: 1.1; margin-bottom: 5px; }
      
      .stock-status { 
          font-size: 0.75rem; font-weight: 700; color: #2ecc71; 
          margin-bottom: 10px; display: block; 
          text-transform: uppercase; letter-spacing: 0.5px;
      }
      .stock-status.low { color: #e74c3c; }

      .controls-section { display: flex; flex-direction: column; gap: 12px; margin-bottom: 15px; }
      .control-row { display: flex; align-items: center; gap: 15px; }
      
      .info-label { font-weight: 700; font-size: 0.75rem; color: #888; text-transform: uppercase; min-width: 50px; }
      .variant-options { display: flex; flex-wrap: wrap; gap: 6px; }
      .variant-btn { 
          padding: 6px 14px; border: 1px solid #ddd; background: #fff; color: #333; 
          cursor: pointer; font-size: 0.8rem; font-weight: 600; border-radius: 6px; 
          transition: all 0.2s; min-width: 36px; text-align: center;
      }
      .variant-btn:hover { border-color: #000; }
      .variant-input:checked + .variant-btn { border-color: #000; background: #000; color: #fff; }
      .variant-input { display: none; }
      .qty-input { width: 50px; padding: 6px; border: 1px solid #ddd; text-align: center; border-radius: 6px; font-weight: 700; font-size: 0.9rem; }

      .bottom-action-row { display: flex; justify-content: space-between; align-items: center; margin-top: 10px; }
      .action-left { display: flex; gap: 10px; flex: 1; }
      .btn-add-cart { 
          background: #000; color: #fff; padding: 10px 20px; border: none; 
          font-weight: 700; font-size: 0.85rem; cursor: pointer; border-radius: 8px; 
          display: flex; align-items: center; gap: 8px; box-shadow: 0 4px 10px rgba(0,0,0,0.2);
      }
      .btn-add-cart i { color: #fff !important; }
      .btn-add-cart:hover { background: #333; }

      .btn-wishlist { 
          width: 40px; height: 40px; border: 1px solid #eee; border-radius: 8px; 
          background: #fff; cursor: pointer; position: relative;
          display: flex; align-items: center; justify-content: center; transition: all 0.2s; 
      }
      .btn-wishlist i { font-size: 1.1rem; }
      .btn-wishlist:hover { border-color: #ef4444; background: #fff5f5; }
      
      .price-main { font-size: 1.6rem; font-weight: 800; color: #000; }
      .price-currency { font-size: 0.9rem; font-weight: 600; color: #333; margin-right: 2px; }

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
          position: absolute;
          font-size: 0.8rem; font-weight: 700; color: #999; text-transform: uppercase; letter-spacing: 0.5px;
          transition: all 0.4s cubic-bezier(0.175, 0.885, 0.32, 1.275);
          top: 50%; transform: translateY(-50%);
      }
      
      .tab-stars {
          position: absolute;
          display: flex; align-items: center; justify-content: center; gap: 2px;
          color: #ffc107 !important; 
          top: 150%; 
          opacity: 0;
          transition: all 0.4s cubic-bezier(0.175, 0.885, 0.32, 1.275);
      }
      
      .tab-link::after { 
          content: ''; position: absolute; bottom: 0; left: 0; width: 100%; height: 2px; background: #000; 
          transform: scaleX(0); transition: transform 0.3s; 
      }
      .tab-link.active::after { transform: scaleX(1); }
      .tab-link.active .tab-text { color: #000; }
      .tab-link.active .tab-stars { opacity: 1; top: 50%; transform: translateY(-50%); }

      .tab-link.has-stars:hover .tab-text { top: 30%; opacity: 0.5; }
      .tab-link.has-stars:hover .tab-stars { top: 70%; opacity: 0.5; }

      .tab-link.active.has-stars .tab-text { top: -50%; opacity: 0; } 
      .tab-link.active.has-stars .tab-stars { top: 50%; transform: translateY(-50%); opacity: 1; } 

      .tab-content { display: none; color: #666; line-height: 1.6; font-size: 0.85rem; text-align: left; padding-bottom: 20px; }
      .tab-content.active { display: block; animation: fadeIn 0.4s ease; }

      .review-card { padding: 15px 0; border-bottom: 1px solid #f9f9f9; transition: background 0.2s; cursor: default; }
      .review-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 6px; }
      .review-user { font-weight: 700; font-size: 0.85rem; color: #333; }
      
      .review-stars { color: #ffc107 !important; font-size: 0.75rem; opacity: 1 !important; visibility: visible !important; transition: all 0.3s; }
      
      .review-comment { font-size: 0.85rem; color: #777; font-style: italic; line-height: 1.5; }
      .empty-reviews { text-align: center; padding: 30px 0; color: #999; font-size: 0.9rem; font-style: italic; }

      .toast {
           visibility: hidden; min-width: 300px; background-color: #111; color: #fff !important; 
           text-align: center; border-radius: 50px; padding: 12px 24px; position: fixed; z-index: 9999; 
           left: 50%; top: 100px; transform: translateX(-50%) translateY(-20px);
           font-size: 13px; font-weight: 600; opacity: 0; transition: all 0.4s;
           box-shadow: 0 10px 30px rgba(0,0,0,0.15); display: flex; align-items: center; justify-content: center; gap: 10px;
      }
      .toast span { color: #fff !important; font-family: 'Inter', sans-serif; }
      .toast i { color: #4ade80 !important; font-size: 1.1rem; }
      .toast.show { visibility: visible; opacity: 1; transform: translateX(-50%) translateY(0); }

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

<div id="toast" class="toast">
    <i class="fas fa-check-circle"></i>
    <span id="toast-message">Notification</span>
</div>
<?php if(isset($toast_message)){ foreach($toast_message as $msg){ echo '<script>document.addEventListener("DOMContentLoaded", function() { showToast("'.addslashes($msg).'"); });</script>'; } } ?>

<div class="page-content">
   <?php
      if(isset($_GET['pid'])){
         $pid = $_GET['pid'];
         $select_products = $conn->prepare("SELECT * FROM `products` WHERE id = ?");
         $select_products->execute([$pid]);
         
         if($select_products->rowCount() > 0){
            while($fetch_products = $select_products->fetch(PDO::FETCH_ASSOC)){ 
                
                $base_price = ($fetch_products['category'] === 'SALES') ? $fetch_products['discounted_price'] : $fetch_products['price'];
                $base_image = "uploaded_img/".$fetch_products['image'];
                $stock_qty = $fetch_products['quantity'];

                $db_variants = $fetch_products['variants'] ?? '';
                $parsed_variants = []; 
                
                // Initialize gallery with main image
                $gallery_images = [$base_image];

                // NEW: Add Image 2 if exists
                if(!empty($fetch_products['image2'])){
                    $gallery_images[] = "uploaded_img/".$fetch_products['image2'];
                }
                // NEW: Add Image 3 if exists
                if(!empty($fetch_products['image3'])){
                    $gallery_images[] = "uploaded_img/".$fetch_products['image3'];
                }

                if(!empty($db_variants)){
                    if(strpos($db_variants, '__') !== false) {
                        $rows = explode('||', $db_variants);
                        foreach($rows as $row) {
                            $cols = explode('__', $row);
                            if(count($cols) >= 2) {
                                $type = $cols[0];
                                $name = $cols[1];
                                $v_price = $cols[2] ?? '';
                                $v_img = $cols[3] ?? '';
                                $parsed_variants[$type][] = ['name'=>$name, 'price'=>$v_price, 'img'=>$v_img];
                                
                                // Also append variant images to gallery
                                if(!empty($v_img)) {
                                    $full_img_path = "uploaded_img/" . $v_img;
                                    if(!in_array($full_img_path, $gallery_images)){
                                        $gallery_images[] = $full_img_path;
                                    }
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
                               foreach($opts as $opt) {
                                   $parsed_variants[$type][] = ['name'=>trim($opt), 'price'=>'', 'img'=>''];
                               }
                           }
                        }
                    }
                }

                // Average Rating Calculation Logic
                $avg_rating = 0;
                $total_reviews = 0;
                $select_avg = $conn->prepare("SELECT AVG(rating) as avg_r, COUNT(*) as total_r FROM review WHERE pid = ?");
                $select_avg->execute([$pid]);
                if($row_avg = $select_avg->fetch(PDO::FETCH_ASSOC)){
                    $avg_rating = round($row_avg['avg_r']);
                    $total_reviews = $row_avg['total_r'];
                }
   ?>

   <div class="product-main">
      <div class="product-image-container">
         <button class="slider-arrow prev" onclick="moveSlide(-1)"><i class="fas fa-chevron-left"></i></button>
         
         <div class="gallery-slider" id="gallerySlider">
             <?php foreach($gallery_images as $g_img): ?>
                <img class="gallery-img" src="<?= $g_img; ?>" alt="Product Image" data-src="<?= $g_img; ?>">
             <?php endforeach; ?>
         </div>

         <button class="slider-arrow next" onclick="moveSlide(1)"><i class="fas fa-chevron-right"></i></button>
      </div>

      <div class="product-info-container">
         
         <div class="static-details">
             <form action="" method="POST" id="addToCartForm">
                
                <div class="details-header">
                    <div class="badges-wrapper">
                        <span class="category-badge"><?= $fetch_products['category']; ?></span>
                        <?php if(!empty($fetch_products['brand'])): ?>
                            <span class="brand-badge"><?= $fetch_products['brand']; ?></span>
                        <?php endif; ?>
                    </div>

                    <h1 class="product-name"><?= $fetch_products['name']; ?></h1>
                    
                    <p class="stock-status <?= ($stock_qty < 5) ? 'low' : ''; ?>">
                        <?= ($stock_qty > 0) ? "IN STOCK: $stock_qty UNITS" : "OUT OF STOCK"; ?>
                    </p>
                </div>

                <div class="controls-section">
                    <?php if(!empty($parsed_variants)): ?>
                        <?php foreach($parsed_variants as $type => $options): ?>
                            <div class="variant-group control-row">
                                <span class="info-label"><?= $type; ?></span>
                                <div class="variant-options">
                                    <?php foreach($options as $index => $opt): 
                                        $img_path = (!empty($opt['img'])) ? 'uploaded_img/'.$opt['img'] : '';
                                    ?>
                                        <label>
                                            <input type="radio" 
                                                   name="variant_<?= str_replace(' ','_',$type); ?>" 
                                                   value="<?= $type.': '.$opt['name']; ?>" 
                                                   class="variant-input"
                                                   data-type="<?= $type; ?>"
                                                   data-name="<?= $opt['name']; ?>"
                                                   data-price="<?= $opt['price']; ?>"
                                                   data-img="<?= $img_path; ?>"
                                                   data-was-checked="false"
                                                   onclick="toggleVariant(this)"
                                                   >
                                            <div class="variant-btn"><?= $opt['name']; ?></div>
                                        </label>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                    
                    <div class="qty-wrapper control-row">
                       <span class="info-label">QTY</span>
                       <input type="number" min="1" max="<?= $stock_qty; ?>" value="1" name="p_qty" class="qty-input" oninput="checkMax(this, <?= $stock_qty; ?>)">
                    </div>
                </div>

                <div class="bottom-action-row">
                   <div class="action-left">
                       <button type="button" class="btn-add-cart" onclick="validateAndSubmit()">
                          <i class="fas fa-shopping-cart"></i> Add to Cart
                       </button>
                       <button type="submit" name="add_to_wishlist" class="btn-wishlist" title="Add to Wishlist">
                          <i class="far fa-heart"></i>
                       </button>
                   </div>

                   <div class="price-display-bottom">
                       <span class="price-currency">RM</span> 
                       <span class="price-main" id="displayPrice"><?= number_format($base_price, 2); ?></span>
                   </div>
                </div>

                <input type="hidden" name="pid" value="<?= $fetch_products['id']; ?>">
                <input type="hidden" name="p_name" value="<?= $fetch_products['name']; ?>">
                <input type="hidden" name="p_price" id="finalPriceInput" value="<?= $base_price; ?>">
                <input type="hidden" name="p_image" value="<?= $fetch_products['image']; ?>">
                <input type="hidden" name="selected_variants" id="finalVariantString" value="">
                <input type="hidden" name="add_to_cart" value="1"> 

             </form>
         </div>

         <div class="tabs-container">
            <div class="tab-header">
                <button class="tab-link active" onclick="switchTab(event, 'desc')">
                    <span class="tab-text">Description</span>
                </button>
                
                <button class="tab-link has-stars" onclick="switchTab(event, 'reviews')">
                    <span class="tab-text">Reviews</span>
                    <div class="tab-stars">
                        <?php 
                            for($i=1; $i<=5; $i++){
                                if($i <= $avg_rating) echo '<i class="fas fa-star" style="color: #ffc107 !important;"></i>';
                                else echo '<i class="far fa-star" style="color: #cbd5e1 !important;"></i>';
                            }
                        ?>
                    </div>
                </button>
            </div>
            
            <div id="desc" class="tab-content active">
                <p style="white-space: pre-line;"><?= $fetch_products['details']; ?></p>
            </div>
            <div id="reviews" class="tab-content">
                <?php
                  $select_reviews = $conn->prepare("SELECT r.*, u.name as user_name FROM review r JOIN users u ON r.user_id = u.id WHERE r.pid = ? ORDER BY r.created_at DESC");
                  $select_reviews->execute([$pid]);
                  
                  if($select_reviews->rowCount() > 0){
                      while($rev = $select_reviews->fetch(PDO::FETCH_ASSOC)){
                          echo '<div class="review-card">';
                          echo '<div class="review-header">';
                          echo '<span class="review-user">'.htmlspecialchars($rev['user_name']).'</span>';
                          echo '<div class="review-stars">';
                          for($i=1; $i<=5; $i++) {
                              if($i <= $rev['rating']) echo '<i class="fas fa-star" style="color: #ffc107 !important;"></i>';
                              else echo '<i class="far fa-star" style="color: #cbd5e1 !important;"></i>';
                          }
                          echo '</div>'; 
                          echo '</div>'; 
                          echo '<p class="review-comment">"'.htmlspecialchars($rev['comment']).'"</p>';
                          echo '</div>';
                      }
                  } else {
                      echo '<div class="empty-reviews">No reviews yet. Be the first to share your thoughts!</div>';
                  }
                ?>
            </div>
         </div>

      </div>
   </div>

   <?php
            }
         }
      }
   ?>
</div>

<?php include 'footer.php'; ?>

<script>
    function showToast(message) {
        const toast = document.getElementById("toast");
        const msgSpan = document.getElementById("toast-message");
        msgSpan.innerText = message;
        toast.className = "toast show";
        setTimeout(function(){ toast.className = toast.className.replace("show", ""); }, 3000);
    }

    const basePrice = <?= $base_price; ?>;
    const baseImage = "<?= $base_image; ?>";

    function moveSlide(direction) {
        const slider = document.getElementById('gallerySlider');
        const scrollAmount = slider.clientWidth; 
        slider.scrollBy({ left: scrollAmount * direction, behavior: 'smooth' });
    }

    function checkMax(input, maxStock) {
        if(parseInt(input.value) > maxStock) {
            input.value = maxStock;
            showToast("Limit reached! Only " + maxStock + " left.");
        }
        if(parseInt(input.value) < 1) {
            input.value = 1;
        }
    }

    function toggleVariant(element) {
        if (element.getAttribute('data-was-checked') === 'true') {
            element.checked = false;
            element.setAttribute('data-was-checked', 'false');
            revertToBase();
        } else {
            let groupName = element.name;
            document.querySelectorAll(`input[name="${groupName}"]`).forEach(el => {
                el.setAttribute('data-was-checked', 'false');
            });
            element.setAttribute('data-was-checked', 'true');
            applyVariantState(element);
        }
        updateCartVariantString();
    }

    function revertToBase() {
        const slider = document.getElementById('gallerySlider');
        if(slider.children.length > 0) {
            slider.scrollTo({ left: 0, behavior: 'smooth' });
        }
        const priceDisplay = document.getElementById('displayPrice');
        const priceInput = document.getElementById('finalPriceInput');
        priceDisplay.textContent = basePrice.toFixed(2);
        priceInput.value = basePrice;
    }

    function applyVariantState(element) {
        const newImg = element.getAttribute('data-img');
        const newPrice = element.getAttribute('data-price');
        
        if(newImg && newImg !== '') {
            const targetPath = newImg; 
            const slider = document.getElementById('gallerySlider');
            const images = slider.querySelectorAll('img');
            
            for(let img of images) {
                if(img.getAttribute('data-src') && img.getAttribute('data-src') === targetPath) {
                    slider.scrollTo({ left: img.offsetLeft, behavior: 'smooth' });
                    break;
                }
            }
        }
        
        const priceDisplay = document.getElementById('displayPrice');
        const priceInput = document.getElementById('finalPriceInput');
        
        if(newPrice && newPrice !== '' && parseFloat(newPrice) > 0) {
            priceDisplay.textContent = parseFloat(newPrice).toFixed(2);
            priceInput.value = newPrice;
        } else {
            priceDisplay.textContent = basePrice.toFixed(2);
            priceInput.value = basePrice;
        }
    }

    function updateCartVariantString() {
        let selected = [];
        document.querySelectorAll('.variant-input:checked').forEach((radio) => {
            selected.push(radio.value);
        });
        document.getElementById('finalVariantString').value = selected.join('; ');
    }

    function validateAndSubmit() {
        updateCartVariantString();
        const variantGroups = document.querySelectorAll('.variant-group');
        if(variantGroups.length > 0) {
            let allSelected = true;
            variantGroups.forEach(group => {
                const checked = group.querySelector('input[type="radio"]:checked');
                if(!checked) allSelected = false;
            });
            if(!allSelected) {
                showToast("Please select an option for each category!");
                return; 
            }
        }
        document.getElementById('addToCartForm').submit();
    }
    
    function switchTab(evt, tabName) {
        if(evt) evt.preventDefault();
        document.querySelectorAll(".tab-content").forEach(c => c.classList.remove("active"));
        document.querySelectorAll(".tab-link").forEach(b => b.classList.remove("active"));
        document.getElementById(tabName).classList.add("active");
        evt.currentTarget.classList.add("active");
    }

    const wishlistBtn = document.querySelector('.btn-wishlist');
    if(wishlistBtn) {
        wishlistBtn.addEventListener('mouseenter', () => {
            wishlistBtn.querySelector('.far').style.opacity = '0';
            wishlistBtn.querySelector('.fas').style.opacity = '1';
        });
        wishlistBtn.addEventListener('mouseleave', () => {
            wishlistBtn.querySelector('.far').style.opacity = '1';
            wishlistBtn.querySelector('.fas').style.opacity = '0';
        });
    }

    document.addEventListener('DOMContentLoaded', function() {
        updateCartVariantString();
    });
</script>
</body>
</html>