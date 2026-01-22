<?php
// 1. GET CURRENT PAGE NAME
$current_page = basename($_SERVER['PHP_SELF']);

// Display messages
if(isset($message)){
   foreach($message as $msg){
      echo '
      <div class="fixed top-5 left-1/2 transform -translate-x-1/2 z-[100] flex items-center gap-3 px-6 py-3 bg-black text-white rounded-full shadow-2xl animate-bounce-in">
         <span class="text-sm font-bold">'.$msg.'</span>
         <i class="fas fa-times cursor-pointer hover:text-gray-300" onclick="this.parentElement.remove();"></i>
      </div>
      ';
   }
}
?>

<script src="https://cdn.tailwindcss.com"></script>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800;900&display=swap" rel="stylesheet">

<style>
   /* === GLOBAL STYLES FOR ALL PAGES === */
   body {
      font-family: 'Inter', sans-serif;
      background-color: #f8f9fa;
      color: #111;
      overflow-x: hidden;
      display: flex;
      flex-direction: column;
      min-height: 100vh;
   }

   .ease-flip { transition-timing-function: cubic-bezier(0.4, 0, 0.2, 1); }
   
   .header-gradient {
      background: linear-gradient(90deg, #FFFFFF 20%, #CBDCEB 100%);
   }

   .text-shadow-pop { text-shadow: 3px 3px 0px #cbd5e1; }
   .no-scrollbar::-webkit-scrollbar { display: none; }
   .no-scrollbar { -ms-overflow-style: none; scrollbar-width: none; }
</style>

<header class="fixed top-0 left-0 right-0 z-50 header-gradient backdrop-blur-md border-b border-white/40 h-20 transition-all shadow-sm">
   
   <div class="w-full px-4 md:px-12 h-full flex items-center justify-between">

      <a href="home.php" class="text-2xl font-black tracking-tighter text-black uppercase text-decoration-none no-underline flex items-center gap-1">
         ANGGUN<span class="text-gray-500">GADGET</span>
      </a>

      <nav class="hidden md:flex items-center gap-12">
         <?php
            function renderFlipLink($text, $url, $isActive) {
               $commonClass = "block h-[20px] flex items-center justify-center"; 
               $activeColor = $isActive ? "text-black font-bold" : "text-gray-600 group-hover:text-black";

               echo '
               <a href="'.$url.'" class="group relative h-[20px] overflow-hidden text-xs font-bold uppercase tracking-widest text-decoration-none no-underline cursor-pointer">
                  <div class="w-full transition-transform duration-300 ease-flip group-hover:-translate-y-[20px]">
                     <span class="'.$commonClass.' '.$activeColor.'">'.$text.'</span>
                     <span class="'.$commonClass.' text-black font-black">'.$text.'</span>
                  </div>
               </a>
               ';
            }

            renderFlipLink('Home', 'home.php', $current_page == 'home.php');
            renderFlipLink('Shop', 'shop.php', ($current_page == 'shop.php' || $current_page == 'view_page.php'));
            renderFlipLink('About', 'about.php', $current_page == 'about.php');
            renderFlipLink('Review', 'ratings.php', $current_page == 'ratings.php');
         ?>
      </nav>

      <div class="flex items-center gap-3">
         
         <a href="orders.php" class="w-10 h-10 flex items-center justify-center rounded-full hover:bg-white/50 transition-colors text-decoration-none" title="My Orders">
            <i class="fas fa-box text-gray-800 hover:text-black transition-colors"></i>
         </a>

         <?php
            $uid_safe = $user_id ?? 0;
            $count_wishlist_items = $conn->prepare("SELECT * FROM `wishlist` WHERE user_id = ?");
            $count_wishlist_items->execute([$uid_safe]);
            $wishlist_num = $count_wishlist_items->rowCount();
         ?>
         <a href="wishlist.php" class="relative w-10 h-10 flex items-center justify-center rounded-full hover:bg-white/50 transition-colors text-decoration-none" title="Wishlist">
            <i class="fas fa-heart text-gray-800 hover:text-red-500 transition-colors"></i>
            <?php if($wishlist_num > 0): ?>
               <span class="absolute top-2 right-2 w-2 h-2 bg-red-500 rounded-full border border-white"></span>
            <?php endif; ?>
         </a>

         <a href="contact.php" class="relative w-10 h-10 flex items-center justify-center rounded-full hover:bg-white/50 transition-colors text-decoration-none" title="Customer Support">
            <i class="fas fa-headset text-gray-800 hover:text-blue-600 transition-colors"></i>
         </a>

         <?php
            $count_cart_items = $conn->prepare("SELECT * FROM `cart` WHERE user_id = ?");
            $count_cart_items->execute([$uid_safe]);
            $cart_num = $count_cart_items->rowCount();
         ?>
         <div onclick="toggleCartDrawer()" class="relative w-10 h-10 flex items-center justify-center rounded-full hover:bg-white/50 transition-colors cursor-pointer" title="Cart">
            <i class="fas fa-shopping-cart text-gray-800 hover:text-blue-600 transition-colors"></i>
            <?php if($cart_num > 0): ?>
               <span class="absolute top-2 right-2 w-2 h-2 bg-blue-600 rounded-full border border-white"></span>
            <?php endif; ?>
         </div>

         <div id="user-btn" class="w-10 h-10 flex items-center justify-center rounded-full bg-black text-white cursor-pointer hover:scale-105 transition-transform ml-2 shadow-lg" title="Profile">
            <i class="fas fa-user text-sm"></i>
         </div>

      </div>

      <div class="profile-dropdown absolute top-24 right-6 w-64 bg-white/95 backdrop-blur-md rounded-2xl shadow-2xl border border-gray-100 p-5 z-[60] hidden transition-all duration-300 origin-top-right scale-95 opacity-0">
         <?php
            if($uid_safe > 0){
               $select_profile = $conn->prepare("SELECT * FROM `users` WHERE id = ?");
               $select_profile->execute([$uid_safe]);
               if($select_profile->rowCount() > 0){
                  $fetch_profile = $select_profile->fetch(PDO::FETCH_ASSOC);
         ?>
            <div class="flex flex-col items-center">
               <img src="uploaded_img/<?= $fetch_profile['image']; ?>" class="w-16 h-16 rounded-full object-cover mb-3 border border-gray-200" alt="">
               <p class="font-bold text-gray-900 mb-4"><?= $fetch_profile['name']; ?></p>
               
               <a href="user_profile_update.php" class="w-full py-2 bg-gray-100 text-black text-center rounded-lg text-xs font-bold uppercase tracking-wider hover:bg-gray-200 transition-colors mb-2 text-decoration-none no-underline">
                  Edit Profile
               </a>
               <a href="logout.php" class="w-full py-2 border border-gray-200 text-gray-500 text-center rounded-lg text-xs font-bold uppercase tracking-wider hover:bg-red-50 hover:text-red-600 hover:border-red-100 transition-colors text-decoration-none no-underline" onclick="return confirm('Logout?');">
                  Sign Out
               </a>
            </div>
         <?php 
               }
            } else { 
               echo '
               <div class="text-center">
                  <p class="text-sm text-gray-500 mb-4">You are not logged in.</p>
                  <a href="login.php" class="block w-full py-2 bg-black text-white rounded-lg text-xs font-bold uppercase mb-2 no-underline">Login</a>
                  <a href="register.php" class="block w-full py-2 border border-black text-black rounded-lg text-xs font-bold uppercase no-underline">Register</a>
               </div>'; 
            } 
         ?>
      </div>

   </div>
</header>

<script>
   const userBtn = document.querySelector('#user-btn');
   const dropdown = document.querySelector('.profile-dropdown');

   if(userBtn && dropdown){
      userBtn.onclick = () => {
         if (dropdown.classList.contains('hidden')) {
            dropdown.classList.remove('hidden');
            setTimeout(() => {
               dropdown.classList.remove('scale-95', 'opacity-0');
               dropdown.classList.add('scale-100', 'opacity-100');
            }, 10);
         } else {
            dropdown.classList.remove('scale-100', 'opacity-100');
            dropdown.classList.add('scale-95', 'opacity-0');
            setTimeout(() => dropdown.classList.add('hidden'), 300);
         }
      }

      window.onscroll = () => {
         dropdown.classList.remove('scale-100', 'opacity-100');
         dropdown.classList.add('scale-95', 'opacity-0');
         setTimeout(() => dropdown.classList.add('hidden'), 300);
      }
   }
</script>
