<style>
   /* COMPACT FOOTER GRADIENT */
   .footer-gradient {
      background: linear-gradient(180deg, #FFFFFF 0%, #CBDCEB 100%);
   }
</style>

<footer class="relative z-30 footer-gradient rounded-t-[30px] shadow-[0_-10px_40px_-15px_rgba(0,0,0,0.05)] pt-16 pb-8 mt-auto border-t border-white/50">
   
   <div class="w-full px-8 md:px-12">
      
      <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-12 mb-16 text-center md:text-left">

         <div class="space-y-6 flex flex-col items-center md:items-start">
            <a href="home.php" class="text-2xl font-black tracking-tighter text-black uppercase text-decoration-none no-underline block">
               ANGGUN<span class="text-gray-500">GADGET</span>
            </a>
            <p class="text-gray-600 text-sm leading-relaxed font-medium">
               Your trusted destination for premium electronics. We deliver authenticity, quality, and style directly to your doorstep.
            </p>
            <div class="flex gap-4">
               <a href="#" class="w-10 h-10 flex items-center justify-center rounded-full bg-white shadow-sm text-gray-800 hover:bg-black hover:text-white transition-all"><i class="fab fa-facebook-f"></i></a>
               <a href="#" class="w-10 h-10 flex items-center justify-center rounded-full bg-white shadow-sm text-gray-800 hover:bg-black hover:text-white transition-all"><i class="fab fa-twitter"></i></a>
               <a href="#" class="w-10 h-10 flex items-center justify-center rounded-full bg-white shadow-sm text-gray-800 hover:bg-black hover:text-white transition-all"><i class="fab fa-instagram"></i></a>
               <a href="#" class="w-10 h-10 flex items-center justify-center rounded-full bg-white shadow-sm text-gray-800 hover:bg-black hover:text-white transition-all"><i class="fab fa-tiktok"></i></a>
            </div>
         </div>

         <div class="flex flex-col items-center md:items-start">
            <h3 class="text-xs font-black uppercase tracking-widest text-gray-400 mb-6">Shop Categories</h3>
            <ul class="space-y-4">
               <li><a href="shop.php" class="text-sm font-bold text-gray-700 hover:text-blue-600 transition-colors no-underline">New Arrivals</a></li>
               <li><a href="shop.php" class="text-sm font-bold text-gray-700 hover:text-blue-600 transition-colors no-underline">Smartphones</a></li>
               <li><a href="shop.php" class="text-sm font-bold text-gray-700 hover:text-blue-600 transition-colors no-underline">Audio & Sound</a></li>
               <li><a href="shop.php" class="text-sm font-bold text-gray-700 hover:text-blue-600 transition-colors no-underline">Accessories</a></li>
               <li><a href="shop.php" class="text-sm font-bold text-red-500 hover:text-red-600 transition-colors no-underline">On Sale</a></li>
            </ul>
         </div>

         <div class="flex flex-col items-center md:items-start">
            <h3 class="text-xs font-black uppercase tracking-widest text-gray-400 mb-6">Customer Care</h3>
            <ul class="space-y-4">
               <li><a href="orders.php" class="text-sm font-bold text-gray-700 hover:text-blue-600 transition-colors no-underline">My Orders</a></li>
               <li><a href="contact.php" class="text-sm font-bold text-gray-700 hover:text-blue-600 transition-colors no-underline">Contact Support</a></li>
               <li><a href="#" class="text-sm font-bold text-gray-700 hover:text-blue-600 transition-colors no-underline">Shipping Policy</a></li>
               <li><a href="#" class="text-sm font-bold text-gray-700 hover:text-blue-600 transition-colors no-underline">Returns & Refunds</a></li>
               <li><a href="#" class="text-sm font-bold text-gray-700 hover:text-blue-600 transition-colors no-underline">FAQ</a></li>
            </ul>
         </div>

         <div class="flex flex-col items-center md:items-start">
            <h3 class="text-xs font-black uppercase tracking-widest text-gray-400 mb-6">Stay Connected</h3>
            <p class="text-gray-600 text-xs font-bold mb-4">Subscribe for exclusive deals and updates.</p>
            
            <form action="#" class="w-full relative flex items-center mb-6">
                <input type="email" placeholder="Email Address" class="w-full bg-white border border-gray-200 rounded-xl px-4 py-3 text-sm font-bold outline-none focus:border-black transition-colors">
                <button class="absolute right-2 bg-black text-white w-8 h-8 rounded-lg flex items-center justify-center hover:bg-gray-800"><i class="fas fa-arrow-right text-xs"></i></button>
            </form>
            
            <div class="space-y-2 w-full">
                <div class="flex items-center gap-3 text-gray-800 text-sm font-bold justify-center md:justify-start">
                  <i class="fas fa-phone text-gray-400 text-xs"></i> <span>010-8202130</span>
               </div>
               <div class="flex items-center gap-3 text-gray-800 text-sm font-bold justify-center md:justify-start">
                  <i class="fas fa-envelope text-gray-400 text-xs"></i> <span>support@anggun.com</span>
               </div>
               <div class="flex items-center gap-3 text-gray-800 text-sm font-bold justify-center md:justify-start">
                  <i class="fas fa-map-marker-alt text-gray-400 text-xs"></i> <span>Sipitang, Sabah</span>
               </div>
            </div>
         </div>

      </div>

      <div class="border-t border-black/5 pt-8 flex flex-col-reverse md:flex-row justify-between items-center gap-6">
         
         <div class="flex flex-col md:flex-row items-center gap-1 md:gap-6 text-center md:text-left">
             <p class="text-[10px] text-gray-500 font-bold uppercase tracking-wide">
                &copy; <?= date('Y'); ?> Anggun Gadget
             </p>
             <div class="hidden md:block w-1 h-1 bg-gray-300 rounded-full"></div>
             <div class="flex gap-4">
                <a href="#" class="text-[10px] text-gray-400 font-bold hover:text-black no-underline uppercase">Privacy</a>
                <a href="#" class="text-[10px] text-gray-400 font-bold hover:text-black no-underline uppercase">Terms</a>
                <a href="#" class="text-[10px] text-gray-400 font-bold hover:text-black no-underline uppercase">Sitemap</a>
             </div>
         </div>

         <div class="flex items-center gap-3 opacity-60 grayscale hover:grayscale-0 transition-all duration-500">
            <i class="fab fa-cc-visa text-2xl text-blue-900"></i>
            <i class="fab fa-cc-mastercard text-2xl text-red-600"></i>
            <i class="fab fa-cc-amex text-2xl text-blue-500"></i>
            <i class="fab fa-cc-paypal text-2xl text-blue-700"></i>
         </div>

      </div>

   </div>

   <?php include 'cart_drawer.php'; ?>

</footer>

<script>
    (function() {
        // This forces the phone to act like a small laptop (1024px)
        // It makes the header buttons visible and "zooms out" the site perfectly.
        var meta = document.querySelector('meta[name="viewport"]');
        var content = 'width=1024'; 

        if (meta) {
            meta.setAttribute('content', content);
        } else {
            var newMeta = document.createElement('meta');
            newMeta.name = 'viewport';
            newMeta.content = content;
            document.getElementsByTagName('head')[0].appendChild(newMeta);
        }
    })();
</script>