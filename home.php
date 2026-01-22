<?php
@include 'config.php';
session_start();

$user_id = $_SESSION['user_id'] ?? null;

// [CART & WISHLIST LOGIC]
if(isset($_POST['add_to_wishlist'])){
   if($user_id == null){
      // [MODIFIED] Redirect guest to login, then back to home
      header('location:login.php?redirect=home.php');
      exit();
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
         $message[] = 'already added to wishlist!';
      }elseif($check_cart_numbers->rowCount() > 0){
         $message[] = 'already added to cart!';
      }else{
         $insert_wishlist = $conn->prepare("INSERT INTO `wishlist`(user_id, pid, name, price, image) VALUES(?,?,?,?,?)");
         $insert_wishlist->execute([$user_id, $pid, $p_name, $p_price, $p_image]);
         $message[] = 'added to wishlist!';
      }
   }
}

if(isset($_POST['add_to_cart'])){
   if($user_id == null){
      // [MODIFIED] Redirect guest to login, then back to home
      header('location:login.php?redirect=home.php');
      exit();
   } else {
      $pid = $_POST['pid'];
      $p_name = $_POST['p_name'];
      $p_price = $_POST['p_price'];
      $p_image = $_POST['p_image'];
      $p_qty = $_POST['p_qty'];
      $check_cart_numbers = $conn->prepare("SELECT * FROM `cart` WHERE name = ? AND user_id = ?");
      $check_cart_numbers->execute([$p_name, $user_id]);
      if($check_cart_numbers->rowCount() > 0){
         $message[] = 'already added to cart!';
      }else{
         $check_wishlist_numbers = $conn->prepare("SELECT * FROM `wishlist` WHERE name = ? AND user_id = ?");
         $check_wishlist_numbers->execute([$p_name, $user_id]);
         if($check_wishlist_numbers->rowCount() > 0){
            $delete_wishlist = $conn->prepare("DELETE FROM `wishlist` WHERE name = ? AND user_id = ?");
            $delete_wishlist->execute([$p_name, $user_id]);
         }
         $insert_cart = $conn->prepare("INSERT INTO `cart`(user_id, pid, name, price, quantity, image) VALUES(?,?,?,?,?,?)");
         $insert_cart->execute([$user_id, $pid, $p_name, $p_price, $p_qty, $p_image]);
         $message[] = 'added to cart!';
      }
   }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
   <meta charset="UTF-8">
   <meta name="viewport" content="width=device-width, initial-scale=1.0">
   <title>Anggun Gadget | Premium Tech</title>
   
   <script src="https://cdn.tailwindcss.com"></script>
   
   <link rel="preconnect" href="https://fonts.googleapis.com">
   <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
   <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800;900&display=swap" rel="stylesheet">
   
   <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

   <style>
      html { scroll-behavior: smooth; }
      
      body {
         font-family: 'Inter', sans-serif;
         background-color: #f8f9fa; 
         color: #111;
         overflow-x: hidden;
      }

      /* === TITLE STYLE === */
      .text-shadow-pop {
         text-shadow: 2px 2px 0px #cbd5e1; 
      }

      /* === SOFTENED GRADIENT === */
      .bg-gradient-soft {
         background: linear-gradient(135deg, #f5f7fa 0%, #c3cfe2 100%);
      }

      /* === CAROUSEL FADE MASK === */
      .carousel-mask {
         mask-image: linear-gradient(to right, transparent 0%, black 20%, black 80%, transparent 100%);
         -webkit-mask-image: linear-gradient(to right, transparent 0%, black 20%, black 80%, transparent 100%);
      }

      /* === TYPEWRITER ANIMATION === */
      .typewriter {
         overflow: hidden; 
         white-space: nowrap; 
         display: inline-block;
         vertical-align: bottom;
         width: 0;
         animation: typing 3s steps(var(--steps), end) forwards;
         animation-delay: 0.2s; 
      }

      @keyframes typing {
         from { width: 0 }
         to { width: 100% }
      }

      .no-scrollbar::-webkit-scrollbar { display: none; }
      .no-scrollbar { -ms-overflow-style: none; scrollbar-width: none; }
      header { position: fixed !important; top: 0; width: 100%; z-index: 50; }
   </style>
</head>
<body>

<?php include 'header.php'; ?>

<div id="intro-banner" class="sticky top-0 left-0 w-full h-screen z-0 overflow-hidden bg-white flex items-center justify-center">
   <div id="banner-content" class="relative w-full h-full">
      <?php
         $select_banners = $conn->prepare("SELECT * FROM `products` WHERE category = 'Banner'");
         $select_banners->execute();
         if($select_banners->rowCount() > 0){
            while($fetch_banner = $select_banners->fetch(PDO::FETCH_ASSOC)){
      ?>
         <div class="hero-slide absolute inset-0 w-full h-full opacity-0 transition-opacity duration-1000 ease-in-out">
            <img src="<?= (strpos($fetch_banner['image'], 'http') === 0) ? $fetch_banner['image'] : 'uploaded_img/' . $fetch_banner['image']; ?>" 
                 class="w-full h-full object-cover object-center" alt="Banner">
         </div>
      <?php
            }
         } else {
      ?>
         <div class="hero-slide active absolute inset-0 w-full h-full">
            <img src="images/home5.png" class="w-full h-full object-cover object-center" alt="Default Banner">
         </div>
      <?php } ?>
   </div>
   <div class="absolute inset-0 bg-black/5 z-10 pointer-events-none"></div>
</div>

<section class="sticky top-0 z-10 w-full min-h-screen bg-[#f3f4f6] rounded-t-[40px] shadow-[0_-20px_60px_-15px_rgba(0,0,0,0.1)] flex flex-col justify-center px-4 md:px-12 py-24 border-t border-white/50">
   
   <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 w-full max-w-[90rem] mx-auto">
      
      <a href="shop.php?category=SALES" class="group relative col-span-1 md:col-span-2 lg:row-span-2 bg-gradient-soft rounded-[40px] p-10 overflow-hidden transition-all duration-500 hover:shadow-2xl hover:-translate-y-2 block min-h-[400px]">
         <div class="relative z-10 h-full flex flex-col justify-between">
            <div>
               <span class="inline-block px-3 py-1.5 bg-black text-white text-[11px] font-semibold uppercase tracking-widest rounded-full mb-4">Hot Deals</span>
               <h3 class="text-4xl md:text-5xl font-black tracking-tighter text-gray-900 leading-[0.9] text-shadow-pop uppercase">
                  FLASH SALES
               </h3>
            </div>
            <div class="mt-auto">
               <p class="text-[11px] font-bold text-gray-600 mb-2 w-2/3 uppercase tracking-wide leading-relaxed">Up to 50% off on selected premium items.</p>
               <div class="text-sm font-bold text-gray-900 flex items-center gap-2 uppercase tracking-wide">
                  Shop Now <i class="fas fa-arrow-right"></i>
               </div>
            </div>
         </div>
         <div class="absolute -bottom-8 -right-8 text-[12rem] text-white/40 group-hover:text-white/60 transition-colors duration-500 transform group-hover:rotate-12">
            <i class="fas fa-tags"></i>
         </div>
      </a>

      <a href="shop.php?category=IPHONE" class="group relative col-span-1 lg:row-span-2 bg-white rounded-[40px] p-8 overflow-hidden transition-all duration-500 hover:shadow-2xl hover:-translate-y-2 block min-h-[400px]">
         <div class="relative z-10 h-full flex flex-col justify-between">
            <div>
               <span class="block text-[10px] font-bold uppercase tracking-widest text-gray-400 mb-1">Apple</span>
               <h3 class="text-3xl font-black tracking-tighter text-gray-900 text-shadow-pop uppercase">IPHONE</h3>
            </div>
            <div class="relative w-full h-48 flex items-center justify-center mt-4">
                 <div class="text-8xl text-gray-200 group-hover:text-black group-hover:scale-110 transition-all duration-500">
                    <i class="fas fa-mobile-alt"></i>
                 </div>
            </div>
            <div class="text-xs font-bold text-gray-900 flex items-center gap-2 uppercase tracking-wide mt-auto">
               View <i class="fas fa-chevron-right"></i>
            </div>
         </div>
      </a>

      <a href="shop.php?category=ANDROID" class="group relative bg-white rounded-[40px] p-8 overflow-hidden transition-all duration-500 hover:shadow-xl hover:-translate-y-1 block min-h-[200px]">
         <div class="relative z-10 flex flex-col h-full justify-between">
            <div>
               <span class="block text-[10px] font-bold uppercase tracking-widest text-gray-400 mb-1">Google</span>
               <h3 class="text-2xl font-black tracking-tighter text-gray-900 text-shadow-pop uppercase">ANDROID</h3>
            </div>
            <div class="absolute bottom-4 right-4 text-5xl text-gray-200 group-hover:text-black transition-colors duration-500">
               <i class="fab fa-android"></i>
            </div>
         </div>
      </a>

      <a href="shop.php?category=AUDIO" class="group relative bg-white rounded-[40px] p-8 overflow-hidden transition-all duration-500 hover:shadow-xl hover:-translate-y-1 block min-h-[200px]">
         <div class="relative z-10 flex flex-col h-full justify-between">
            <div>
               <span class="block text-[10px] font-bold uppercase tracking-widest text-gray-400 mb-1">Sound</span>
               <h3 class="text-2xl font-black tracking-tighter text-gray-900 text-shadow-pop uppercase">AUDIO</h3>
            </div>
            <div class="absolute bottom-4 right-4 text-5xl text-gray-200 group-hover:text-black transition-colors duration-500">
               <i class="fas fa-headphones"></i>
            </div>
         </div>
      </a>

      <a href="shop.php?category=POWER" class="group relative col-span-1 md:col-span-2 bg-white rounded-[40px] p-8 overflow-hidden transition-all duration-500 hover:shadow-xl hover:-translate-y-1 block min-h-[200px]">
         <div class="relative z-10 h-full flex items-center justify-between">
            <div>
               <span class="block text-[10px] font-bold uppercase tracking-widest text-gray-400 mb-1">Charge</span>
               <h3 class="text-3xl font-black tracking-tighter text-gray-900 text-shadow-pop uppercase">POWER</h3>
            </div>
            <div class="text-7xl text-gray-200 group-hover:text-black transition-colors duration-500 pr-4">
               <i class="fas fa-bolt"></i>
            </div>
         </div>
      </a>

      <a href="shop.php?category=ACCESSORIES" class="group relative col-span-1 md:col-span-2 bg-gradient-soft rounded-[40px] p-8 overflow-hidden transition-all duration-500 hover:shadow-xl hover:-translate-y-1 block min-h-[200px]">
         <div class="relative z-10 h-full flex items-center justify-between">
            <div>
               <span class="block text-[10px] font-bold uppercase tracking-widest text-gray-600 mb-1">Gear</span>
               <h3 class="text-3xl font-black tracking-tighter text-gray-900 text-shadow-pop uppercase">ACCESSORIES</h3>
            </div>
            <div class="text-7xl text-white/50 group-hover:text-white transition-colors duration-500 pr-4">
               <i class="fas fa-shield-alt"></i>
            </div>
         </div>
      </a>
   </div>
</section>

<div class="relative z-20 bg-[#dde1e7] rounded-t-[40px] shadow-[0_-20px_60px_-15px_rgba(0,0,0,0.1)] border-t border-white/20 pt-10 -mt-10">
   
   <div class="min-h-[90vh] flex flex-col justify-center pt-8 pb-10">
      
      <div class="w-full px-4 md:px-12 mb-2 flex-none relative z-30">
         <div class="flex flex-col items-center justify-center text-center">
            
            <div class="mx-auto py-2">
               <span class="typewriter text-xs font-bold uppercase tracking-[0.3em] text-red-600 mb-1 block" 
                     style="--steps: 20">Limited Time Offers</span>
               
               <h2 class="text-4xl md:text-5xl font-black text-black tracking-tighter mb-0 leading-[0.9] text-shadow-pop uppercase">FLASH SALE</h2>
               
               <p class="typewriter text-[10px] font-bold text-gray-600 mt-1 uppercase tracking-wide mb-0"
                  style="--steps: 35">Swipe to discover exclusive drops.</p>
            </div>

         </div>
      </div>

      <section class="relative w-full overflow-hidden h-[60vh] py-0">
         
         <div id="sliderTrack" class="carousel-mask flex items-center absolute left-0 top-0 h-full w-full transition-transform duration-700 ease-out-expo">
            <?php
               $select_products = $conn->prepare("SELECT * FROM `products` WHERE category != 'Banner' LIMIT 10");
               $select_products->execute();
               if($select_products->rowCount() > 0){
                  while($fetch_products = $select_products->fetch(PDO::FETCH_ASSOC)){ 
            ?>
            
            <div onclick="window.location.href='view_page.php?pid=<?= $fetch_products['id']; ?>'" 
                 class="carousel-item relative min-w-[33.33%] md:min-w-[20%] h-full flex flex-col items-center justify-center p-2 cursor-pointer transition-all duration-700">
               
               <div class="inner-box w-full max-w-[260px] h-full p-3 rounded-[35px] flex flex-col items-center justify-between transition-all duration-700 bg-white border border-gray-100 shadow-xl">
                  
                  <div class="relative w-full h-[65%] mb-0 flex items-center justify-center bg-gray-50 rounded-[30px] overflow-hidden">
                     <img src="<?= (strpos($fetch_products['image'], 'http') === 0) ? $fetch_products['image'] : 'uploaded_img/' . $fetch_products['image']; ?>" 
                          alt="" class="w-full h-full object-contain drop-shadow-lg p-4">
                  </div>
                  
                  <div class="details-panel opacity-0 transition-opacity duration-300 text-center w-full h-[35%] flex flex-col justify-center">
                     
                     <h3 class="text-sm font-black text-gray-900 mb-0 leading-none truncate w-full uppercase tracking-tight">
                        <?= $fetch_products['name']; ?>
                     </h3>
                     
                     <div class="flex items-center justify-center gap-2 mb-1 mt-1">
                        <?php if(isset($fetch_products['discount_percentage']) && $fetch_products['discount_percentage'] > 0): ?>
                           <span class="text-[10px] text-gray-400 line-through font-medium">RM<?= $fetch_products['price']; ?></span>
                           <span class="text-lg font-black text-black tracking-tight">RM<?= $fetch_products['discounted_price']; ?></span>
                        <?php else: ?>
                           <span class="text-lg font-black text-black tracking-tight">RM<?= $fetch_products['price']; ?></span>
                        <?php endif; ?>
                     </div>
                     <div class="w-full py-3 bg-black text-white rounded-full font-bold shadow-md text-[10px] uppercase tracking-widest hover:scale-105 transition-transform">
                        View Details
                     </div>
                  </div>
               </div>
            </div>
            <?php
                  }
               }
            ?>
         </div>
      </section>

      <div class="w-full flex justify-center flex-none relative z-20 mt-6">
         <a href="shop.php" class="inline-flex items-center justify-center px-12 py-3 bg-white text-black rounded-full font-bold uppercase tracking-widest text-[11px] hover:bg-black hover:text-white transition-colors duration-300 text-decoration-none no-underline shadow-lg border border-gray-100">
            Shop All Products <i class="fas fa-arrow-right ml-2"></i>
         </a>
      </div>

   </div> 

   <?php include 'footer.php'; ?>
</div>

<script>
   document.addEventListener("DOMContentLoaded", function() {
      // 1. BANNER SLIDER
      let slides = document.querySelectorAll('.hero-slide');
      let currentSlide = 0;
      if(slides.length > 0) {
         slides[0].classList.remove('opacity-0'); 
         setInterval(() => {
            slides[currentSlide].classList.add('opacity-0');
            currentSlide = (currentSlide + 1) % slides.length;
            slides[currentSlide].classList.remove('opacity-0');
         }, 5000);
      }

      // 3. CAROUSEL
      const track = document.getElementById('sliderTrack');
      
      function updateActiveClasses() {
         const allItems = document.getElementsByClassName('carousel-item');
         const isMobile = window.innerWidth < 768;
         const centerIndex = isMobile ? 1 : 2; 

         for (let i = 0; i < allItems.length; i++) {
            const box = allItems[i].querySelector('.inner-box');
            const details = allItems[i].querySelector('.details-panel');
            
            if (i === centerIndex) { 
               // --- CENTER CARD ---
               box.classList.remove('scale-90', 'opacity-50', 'z-0');
               box.classList.add('scale-100', 'opacity-100', 'z-10', 'shadow-2xl'); 
               
               // SHOW Details
               details.classList.remove('opacity-0');
               details.classList.add('opacity-100');

            } else {
               // --- SIDE CARDS ---
               box.classList.remove('scale-100', 'opacity-100', 'z-10', 'shadow-2xl');
               box.classList.add('scale-90', 'opacity-50', 'z-0');
               
               // HIDE Details
               details.classList.remove('opacity-100');
               details.classList.add('opacity-0');
            }
         }
      }
      
      updateActiveClasses();

      setInterval(() => {
         const isMobile = window.innerWidth < 768;
         const moveAmount = isMobile ? "-33.33%" : "-20%";
         track.style.transform = `translateX(${moveAmount})`; 
         
         setTimeout(() => {
            track.appendChild(track.firstElementChild);
            track.style.transition = "none";
            track.style.transform = "translateX(0)";
            setTimeout(() => {
               track.style.transition = "transform 700ms cubic-bezier(0.19, 1, 0.22, 1)";
            }, 50);
            updateActiveClasses();
         }, 700);
      }, 3500);
   });
</script>

</body>
</html>