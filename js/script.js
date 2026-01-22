// Modern JavaScript for smooth interactions and animations

// DOM Elements
const navbar = document.querySelector('.header .flex .navbar');
const menuBtn = document.querySelector('#menu-btn');
const userBtn = document.querySelector('#user-btn');
const searchBtn = document.querySelector('.search-icon-btn');
const profile = document.querySelector('.header .flex .profile');

// ==========================================
// ACTIVE PAGE HIGHLIGHTING
// ==========================================
/* ==========================================
   ACTIVE PAGE HIGHLIGHTER (Robust Fix)
   ========================================== */
/* ==========================================
   ACTIVE PAGE HIGHLIGHTER (Robust Fix)
   ========================================== */
document.addEventListener('DOMContentLoaded', () => {
    // 1. Get current file name from URL
    const path = window.location.pathname; 
    // Example results: "home.php", "index.php", or "folder_name" (if at root)
    let page = path.split("/").pop().split('?')[0]; 

    // 2. Smart Home Detection
    // If page is empty, 'index.php', or DOES NOT contain '.php' (like a folder root), treat as Home
    if (page === '' || page === 'index.php' || !page.includes('.php')) {
        page = 'home.php';
    }

    // 3. Loop through links and highlight match
    const navLinks = document.querySelectorAll('.header .navbar a');

    navLinks.forEach(link => {
        link.classList.remove('active'); // Clean reset
        
        const linkHref = link.getAttribute('href');

        // Compare calculated page with the link href
        if (linkHref === page) {
            link.classList.add('active');
        }
    });
});
// ==========================================
// USER PROFILE TOGGLE & NAVIGATION
// Using BOTH delegated and direct listeners for maximum robustness
// ==========================================

// 1. DELEGATED LISTENER (catches clicks on #user-btn even if added late)
document.addEventListener('click', function(e) {
   const userBtnClicked = e.target.closest('#user-btn');
   if (userBtnClicked) {
      e.preventDefault();
      e.stopPropagation();
      const profile = document.querySelector('.header .flex .profile');
      const navbar = document.querySelector('.header .flex .navbar');
      if (profile) {
         profile.classList.toggle('active');
         if (navbar) navbar.classList.remove('active');
      }
      console.log('Profile toggled via delegated listener');
      return;
   }
}, { passive: false, capture: true });

// 2. DIRECT LISTENER (for the specific #user-btn element at page load)
function attachUserBtnListener() {
   const userBtn = document.querySelector('#user-btn');
   if (userBtn) {
      userBtn.addEventListener('click', function(e) {
         e.preventDefault();
         e.stopPropagation();
         const profile = document.querySelector('.header .flex .profile');
         const navbar = document.querySelector('.header .flex .navbar');
         if (profile) {
            profile.classList.toggle('active');
            if (navbar) navbar.classList.remove('active');
         }
         console.log('Profile toggled via direct listener');
      }, { passive: false });
   }
}

// Attach on DOMContentLoaded and also on page load
if (document.readyState === 'loading') {
   document.addEventListener('DOMContentLoaded', attachUserBtnListener);
} else {
   attachUserBtnListener();
}

// ==========================================
// PROFILE DROPDOWN LINK HANDLING
// ==========================================
if (profile) {
   // Get all links in the profile dropdown
   const profileLinks = profile.querySelectorAll('a');
   
   profileLinks.forEach(link => {
      link.addEventListener('click', (e) => {
         // Allow natural link behavior for logout and profile update
         if (link.href.includes('logout.php') || link.href.includes('user_profile_update.php')) {
            // Natural navigation will occur
            console.log('Navigating to:', link.href);
         }
         // Close the dropdown after clicking
         setTimeout(() => {
            profile.classList.remove('active');
         }, 100);
      });
   });
}

// ==========================================
// SEARCH ICON CLICK HANDLER
// ==========================================
if (searchBtn) {
   searchBtn.addEventListener('click', (e) => {
      // Allow default navigation to search_page.php
      console.log('Search icon clicked - navigating to search page');
   });
}

// ==========================================
// CLOSE MENUS ON SCROLL
// ==========================================
window.addEventListener('scroll', () => {
   profile?.classList.remove('active');
   navbar?.classList.remove('active');
   
   // Smooth scroll effect for header
   const header = document.querySelector('.header');
   if (window.scrollY > 0) {
      header?.style.boxShadow = '0 4px 12px rgba(0, 0, 0, 0.15)';
   } else {
      header?.style.boxShadow = '';
   }
}, { passive: true });

// ==========================================
// CLOSE MENUS ON OUTSIDE CLICK
// ==========================================
document.addEventListener('click', (e) => {
   const headerFlex = document.querySelector('.header .flex');
   const profile = document.querySelector('.header .flex .profile');
   const navbar = document.querySelector('.header .flex .navbar');
   
   // If click is outside header, close menus
   if (!headerFlex?.contains(e.target)) {
      if (profile) profile.classList.remove('active');
      if (navbar) navbar.classList.remove('active');
   }
}, { passive: true });

// ==========================================
// SCROLL TO TOP FUNCTIONALITY
// ==========================================
const scrollToTopBtn = document.querySelector('.scroll-to-top');
if (scrollToTopBtn) {
   window.addEventListener('scroll', () => {
      if (window.scrollY > 300) {
         scrollToTopBtn.classList.add('show');
      } else {
         scrollToTopBtn.classList.remove('show');
      }
   }, { passive: true });

   scrollToTopBtn.addEventListener('click', (e) => {
      e.preventDefault();
      window.scrollTo({ top: 0, behavior: 'smooth' });
   });
}

// ==========================================
// SMOOTH SCROLL FOR ANCHOR LINKS
// ==========================================
document.querySelectorAll('a[href^="#"]').forEach(anchor => {
   anchor.addEventListener('click', function (e) {
      e.preventDefault();
      const target = document.querySelector(this.getAttribute('href'));
      if (target) {
         target.scrollIntoView({ behavior: 'smooth', block: 'start' });
      }
   });
});

// ==========================================
// RIPPLE EFFECT ON BUTTONS
// ==========================================
document.querySelectorAll('.btn, .cart-btn, .option-btn, .delete-btn, .icon-btn').forEach(button => {
   button.addEventListener('click', function(e) {
      // Don't add ripple to buttons that navigate away
      if (this.classList.contains('icon-btn') && !this.id) {
         return;
      }
      
      const ripples = document.createElement('span');
      const rect = this.getBoundingClientRect();
      const size = Math.max(rect.width, rect.height);
      const x = e.clientX - rect.left - size / 2;
      const y = e.clientY - rect.top - size / 2;
      
      ripples.style.width = ripples.style.height = size + 'px';
      ripples.style.left = x + 'px';
      ripples.style.top = y + 'px';
      ripples.classList.add('ripple');
      
      this.appendChild(ripples);
      
      setTimeout(() => ripples.remove(), 600);
   });
});

// ==========================================
// FADE IN ANIMATION ON SCROLL (Intersection Observer)
// ==========================================
const observerOptions = {
   threshold: 0.1,
   rootMargin: '0px 0px -100px 0px'
};

const observer = new IntersectionObserver((entries) => {
   entries.forEach(entry => {
      if (entry.isIntersecting) {
         entry.target.style.opacity = '1';
         entry.target.style.transform = 'translateY(0)';
         observer.unobserve(entry.target);
      }
   });
}, observerOptions);

// Observe product cards, sections, etc.
document.querySelectorAll('.box, .card, section').forEach(el => {
   el.style.opacity = '0';
   el.style.transform = 'translateY(20px)';
   el.style.transition = 'opacity 0.6s ease, transform 0.6s ease';
   observer.observe(el);
});

// ==========================================
// FORM INPUT FOCUS ANIMATION
// ==========================================
document.querySelectorAll('input[type="text"], input[type="email"], input[type="password"], textarea, input[type="number"]').forEach(input => {
   input.addEventListener('focus', function() {
      this.parentElement?.style.transform = 'scale(1.01)';
   }, { passive: true });
   
   input.addEventListener('blur', function() {
      this.parentElement?.style.transform = 'scale(1)';
   }, { passive: true });
});

// ==========================================
// LAZY LOAD IMAGES
// ==========================================
if ('IntersectionObserver' in window) {
   const imageObserver = new IntersectionObserver((entries) => {
      entries.forEach(entry => {
         if (entry.isIntersecting) {
            entry.target.style.opacity = '1';
            imageObserver.unobserve(entry.target);
         }
      });
   });

   document.querySelectorAll('img').forEach(img => {
      img.style.opacity = img.dataset.src ? '0' : '1';
      img.style.transition = 'opacity 0.3s ease';
      if (img.dataset.src) {
         imageObserver.observe(img);
      }
   });
}

// ==========================================
// ADD RIPPLE STYLE TO CSS DYNAMICALLY
// ==========================================
const style = document.createElement('style');
style.textContent = `
   button, .btn, .cart-btn, .option-btn, .delete-btn, .icon-btn {
      position: relative;
      overflow: hidden;
   }
   
   .ripple {
      position: absolute;
      border-radius: 50%;
      background: rgba(255, 255, 255, 0.6);
      transform: scale(0);
      animation: ripple-animation 0.6s ease-out;
      pointer-events: none;
      z-index: 50;
   }
   
   @keyframes ripple-animation {
      to {
         transform: scale(4);
         opacity: 0;
      }
   }
`;
document.head.appendChild(style);

// ==========================================
// PERFORMANCE OPTIMIZATION: Debounce scroll events
// ==========================================
let scrollTimeout;
window.addEventListener('scroll', () => {
   clearTimeout(scrollTimeout);
   scrollTimeout = setTimeout(() => {
      // Any heavy operations on scroll can go here
   }, 150);
}, { passive: true });

// ==========================================
// ICON CLICK TRACKING (For debugging/analytics)
// ==========================================
console.log('✓ Modern interactive scripts loaded successfully!');
console.log('✓ Icons are now fully clickable and functional');
console.log('✓ User icon: Toggle profile or navigate');
console.log('✓ Search icon: Navigate to search page');
console.log('✓ Cart & Wishlist: Full navigation support');

/* =====================================================
   DRAWER TAB FILTERING LOGIC
   ===================================================== */
document.addEventListener('DOMContentLoaded', () => {
    const tabs = document.querySelectorAll('.drawer-tab');
    const orderCards = document.querySelectorAll('.drawer-order-card');

    tabs.forEach(tab => {
        tab.addEventListener('click', (e) => {
            e.preventDefault(); // Stop page jump

            // 1. Visual: Switch Active Class
            tabs.forEach(t => t.classList.remove('active'));
            tab.classList.add('active');

            // 2. Logic: Filter Orders
            const filter = tab.getAttribute('data-filter'); // We will add this to HTML next
            
            orderCards.forEach(card => {
                const statusElement = card.querySelector('.doc-status');
                const statusText = statusElement ? statusElement.textContent.toLowerCase().trim() : '';

                if (filter === 'all') {
                    card.style.display = 'block';
                } else if (filter === 'shipping' && (statusText.includes('on the way') || statusText.includes('preparing'))) {
                    card.style.display = 'block';
                } else if (statusText.includes(filter)) {
                    card.style.display = 'block';
                } else {
                    card.style.display = 'none';
                }
            });
        });
    });
});

/* =====================================================
   ORDER DRAWER TOGGLE FUNCTION
   ===================================================== */
function toggleOrderDrawer() {
    const drawer = document.getElementById('orderDrawer');
    const overlay = document.getElementById('orderDrawerOverlay');
    
    // Safety check: Does the drawer exist on this page?
    if (!drawer || !overlay) {
        console.error("Order Drawer elements not found! Check if order_drawer.php is included.");
        return;
    }

    if (drawer.classList.contains('active')) {
        // Close Drawer
        drawer.classList.remove('active');
        overlay.classList.remove('active');
        document.body.style.overflow = 'auto'; // Unlock scroll
    } else {
        // Open Drawer
        drawer.classList.add('active');
        overlay.classList.add('active');
        document.body.style.overflow = 'hidden'; // Lock scroll
    }
}

/* =========================================
   HERO SLIDER LOGIC (Fixed & Responsive)
   ========================================= */
let slideIndex = 1; // Start at 1
let slideTimer;     // Variable to hold the timer

// Select elements once
const slides = document.querySelectorAll(".hero-slide");
const dots = document.querySelectorAll(".dot");

function showSlides(n) {
    let i;
    
    // 1. Loop Logic: If we go past max, go to 1. If less than 1, go to max.
    if (n > slides.length) { slideIndex = 1; }
    if (n < 1) { slideIndex = slides.length; }

    // 2. Hide Everything
    for (i = 0; i < slides.length; i++) {
        slides[i].classList.remove("active");
    }
    for (i = 0; i < dots.length; i++) {
        dots[i].classList.remove("active");
    }

    // 3. Show Active Slide & Dot
    if (slides[slideIndex - 1]) {
        slides[slideIndex - 1].classList.add("active");
    }
    if (dots[slideIndex - 1]) {
        dots[slideIndex - 1].classList.add("active");
    }
}

// Function to handle Manual Clicks
function currentSlide(n) {
    // Stop the auto-timer so it doesn't jump immediately after you click
    clearInterval(slideTimer);
    
    // Update the slide immediately
    showSlides(slideIndex = n);
    
    // Restart the auto-timer
    startAutoSlide();
}

// Function to Start the Auto-Player
function startAutoSlide() {
    slideTimer = setInterval(function() {
        showSlides(slideIndex += 1);
    }, 3500); // 3.5 Seconds (Faster than 5s)
}

// Initialize when page loads
document.addEventListener("DOMContentLoaded", () => {
    if (slides.length > 0) {
        showSlides(slideIndex); // Show first slide
        startAutoSlide();       // Start timer
    }
});

/* ==========================================
   PRODUCT PAGE LOGIC (Tabs & Variants)
   ========================================== */

// 1. TAB SWITCHING FUNCTION
// Called directly by onclick="..." in view_page.php
function openTab(evt, tabName) {
   // Prevent default button behavior (prevents page jump)
   if(evt) evt.preventDefault();

   // A. Hide all tab panes
   const tabPanes = document.querySelectorAll(".tab-pane");
   tabPanes.forEach(pane => pane.classList.remove("active"));

   // B. Remove 'active' class from all buttons
   const tabBtns = document.querySelectorAll(".tab-btn");
   tabBtns.forEach(btn => btn.classList.remove("active"));

   // C. Show the specific tab and activate the clicked button
   const targetTab = document.getElementById(tabName);
   if (targetTab) {
      targetTab.classList.add("active");
      evt.currentTarget.classList.add("active");
   }
}

// 2. VARIANT SELECTION LOGIC
// Updates the hidden input whenever a pill is clicked
function combineVariants() {
   const selected = [];
   // Find all checked radio buttons
   const radios = document.querySelectorAll('.variant-input:checked');
   
   if (radios.length > 0) {
      radios.forEach((radio) => {
         selected.push(radio.value);
      });
      
      // Update the hidden input field that sends data to the cart
      const hiddenInput = document.getElementById('final_variants');
      if (hiddenInput) {
         hiddenInput.value = selected.join(', ');
      }
   }
}

// 3. AUTO-INITIATE ON PAGE LOAD
document.addEventListener('DOMContentLoaded', () => {
   // Only run if we are on the product page (check if variants exist)
   if(document.querySelector('.variant-input')) {
      combineVariants();
   }
});