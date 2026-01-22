<?php
@include 'config.php';
session_start();

$user_id = $_SESSION['user_id'] ?? null;

function get_count($conn, $table) {
    try {
        $stmt = $conn->prepare("SELECT COUNT(*) FROM `$table`");
        $stmt->execute();
        return $stmt->fetchColumn();
    } catch (PDOException $e) { return 0; }
}

$totalUsers = get_count($conn, 'users'); 
$totalProducts = get_count($conn, 'products');
$totalOrders = get_count($conn, 'orders'); 
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>About Us | Anggun Gadget</title>
    
    <script src="https://cdn.tailwindcss.com"></script>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800;900&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.4.1/font/bootstrap-icons.css" rel="stylesheet">
    
    <style>
        /* === RESET & BASE === */
        body {
            font-family: 'Inter', sans-serif;
            background-color: #ffffff;
            color: #111;
            margin: 0; padding: 0;
            overflow-x: hidden;
        }

        .page-container {
            max-width: 1200px;
            margin: 0 auto;
            padding-top: 120px; 
            padding-bottom: 80px;
            padding-left: 20px;
            padding-right: 20px;
        }

        /* === ANIMATIONS (RESTORED) === */
        
        /* 1. FLIP ANIMATION (Stats) */
        .stat-num-box {
            display: inline-block;
            transform-style: preserve-3d; 
            perspective: 1000px;
        }
        @keyframes calendarFlip { 
            0% { transform: rotateX(0deg); } 
            100% { transform: rotateX(1080deg); } 
        }
        .do-flip .stat-num-box { 
            animation: calendarFlip 1.5s cubic-bezier(0.25, 1, 0.5, 1) forwards; 
        }
        .delay-1 .stat-num-box { animation-delay: 0.1s; }
        .delay-2 .stat-num-box { animation-delay: 0.3s; }
        .delay-3 .stat-num-box { animation-delay: 0.5s; }

        /* 2. TYPEWRITER ANIMATION */
        .type-target { min-height: 80px; display: block; }
        .typing-cursor::after { 
            content: '|'; 
            animation: blink 1s step-start infinite; 
            color: #000; font-weight: 900; margin-left: 2px; 
        }
        @keyframes blink { 50% { opacity: 0; } }

        /* Typography */
        .section-title { font-weight: 900; letter-spacing: -0.05em; color: #000; text-transform: uppercase; }
        .text-shadow-pop { text-shadow: 2px 2px 0px #cbd5e1; }

        /* CARDS */
        .ag-card {
            background: #fff; border-radius: 24px; 
            box-shadow: 0 20px 60px -10px rgba(0,0,0,0.08);
            border: 1px solid rgba(0,0,0,0.05);
            padding: 40px; height: 100%; transition: transform 0.3s ease;
            position: relative; overflow: hidden;
        }
        .ag-card:hover { transform: translateY(-5px); }

        .inner-box { background: #f8f9fa; border-radius: 16px; padding: 30px; border: 1px solid rgba(0,0,0,0.03); margin-bottom: 25px; }

        /* STATS CARD */
        .stat-card {
            background: #fff; border-radius: 20px; padding: 30px 20px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.05); border: 1px solid rgba(0,0,0,0.05);
            height: 100%; display: flex; flex-direction: column; align-items: center; justify-content: center; text-align: center;
            transition: transform 0.3s ease;
        }
        .stat-card:hover { transform: translateY(-3px); }

        .feature-icon {
            width: 50px; height: 50px; display: inline-flex; align-items: center; justify-content: center;
            border-radius: 14px; background: #f4f4f5; color: #111; font-size: 1.2rem; margin-bottom: 15px;
            transition: all 0.3s;
        }
        .stat-card:hover .feature-icon { background: #000; color: #fff; }

        /* PHONE ANIMATION */
        .visual-container {
            position: relative; border-radius: 24px; overflow: hidden; background: #fff; 
            border: 1px solid #f0f0f0; min-height: 500px; display: flex; align-items: center; justify-content: center;
            box-shadow: 0 20px 60px -10px rgba(0,0,0,0.08);
        }
        .ag-phone-wrapper { width: 180px; height: 360px; position: relative; z-index: 2; animation: float 6s ease-in-out infinite; }
        .phone-border-layer { position: absolute; inset: -4px; border-radius: 34px; background: #333; transition: all 0.3s ease; z-index: 1; }
        .phone-body-layer { position: absolute; inset: 0; background: #1a1a1a; border-radius: 30px; z-index: 2; overflow: hidden; }
        .phone-body-layer::before { content: ''; position: absolute; top: 6px; left: 6px; right: 6px; bottom: 6px; background: linear-gradient(135deg, #222, #000); border-radius: 26px; }
        .visual-container:hover .phone-border-layer { background: conic-gradient(from var(--border-angle), #0d6efd, #00d4ff, #0d6efd, #00d4ff, #0d6efd); animation: borderRotate 2s linear infinite; }
        @property --border-angle { syntax: '<angle>'; initial-value: 0deg; inherits: false; }
        @keyframes borderRotate { to { --border-angle: 360deg; } }
        @keyframes float { 0%, 100% { transform: translateY(-15px); } 50% { transform: translateY(-25px); } }

        /* UI FLOATING CARDS */
        .ui-card {
            position: absolute; background: #fff; padding: 12px 20px; border-radius: 12px;
            box-shadow: 0 8px 25px rgba(0,0,0,0.1); font-size: 0.75rem; font-weight: 800; color: #111;
            display: flex; align-items: center; gap: 10px; animation: floatUI 5s ease-in-out infinite; border: 1px solid #eee; z-index: 5;
            text-transform: uppercase; letter-spacing: 1px;
        }
        .ui-1 { top: 15%; left: 10%; animation-delay: 0s; }
        .ui-2 { bottom: 20%; right: 10%; animation-delay: 2.5s; }
        .ui-icon { width: 28px; height: 28px; background: #f4f4f5; border-radius: 8px; display: flex; align-items: center; justify-content: center; }
        @keyframes floatUI { 0%, 100% { transform: translateY(0); } 50% { transform: translateY(12px); } }

        /* ACCORDION */
        .faq-item { border-bottom: 1px solid #eee; }
        .faq-btn { width: 100%; text-align: left; padding: 20px 0; display: flex; justify-content: space-between; align-items: center; font-weight: 800; text-transform: uppercase; font-size: 0.85rem; letter-spacing: 0.5px; transition: color 0.2s; }
        .faq-btn:hover { color: #555; }
        .faq-content { max-height: 0; overflow: hidden; transition: max-height 0.3s ease-out; }
        .faq-inner { padding-bottom: 20px; font-size: 0.8rem; line-height: 1.6; color: #555; font-weight: 500; text-align: justify; }
        .faq-icon { transition: transform 0.3s; }
        .faq-item.active .faq-icon { transform: rotate(180deg); }
    </style>
</head>

<body>

    <?php include 'header.php'; ?>

    <div class="page-container">
        
        <div class="text-center mb-16">
            <span class="inline-block py-1 px-4 bg-black text-white text-[10px] font-bold rounded-full tracking-[2px] uppercase mb-4">Est. 2024</span>
            <h1 class="text-5xl md:text-6xl font-black text-black uppercase tracking-tighter mb-4 text-shadow-pop">We Are Anggun Gadget</h1>
            <p class="text-sm font-semibold text-gray-500 tracking-wide max-w-2xl mx-auto leading-relaxed">
                Bridging the gap between global innovation and local accessibility. 
                Genuine devices, trusted support, and a passion for technology.
            </p>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-20 border-b border-gray-200 pb-16">
            
            <div class="stat-card delay-1"> <div class="feature-icon"><i class="fas fa-users"></i></div>
                <div class="stat-num-box bg-white border border-gray-200 rounded-xl px-6 py-2 mb-3 shadow-sm inline-block">
                    <h2 class="text-3xl font-black text-black"><?php echo $totalUsers + 500; ?>+</h2>
                </div>
                <div class="text-[10px] font-bold text-gray-400 uppercase tracking-[2px]">Happy Customers</div>
            </div>

            <div class="stat-card delay-2">
                <div class="feature-icon"><i class="fas fa-box-open"></i></div>
                <div class="stat-num-box bg-white border border-gray-200 rounded-xl px-6 py-2 mb-3 shadow-sm inline-block">
                    <h2 class="text-3xl font-black text-black"><?php echo $totalProducts; ?>+</h2>
                </div>
                <div class="text-[10px] font-bold text-gray-400 uppercase tracking-[2px]">Premium Products</div>
            </div>

            <div class="stat-card delay-3">
                <div class="feature-icon"><i class="fas fa-shopping-bag"></i></div>
                <div class="stat-num-box bg-white border border-gray-200 rounded-xl px-6 py-2 mb-3 shadow-sm inline-block">
                    <h2 class="text-3xl font-black text-black"><?php echo $totalOrders + 1200; ?>+</h2>
                </div>
                <div class="text-[10px] font-bold text-gray-400 uppercase tracking-[2px]">Orders Completed</div>
            </div>

        </div>

        <div class="grid grid-cols-1 lg:grid-cols-2 gap-10 mb-20">
            
            <div class="ag-card">
                <div class="inner-box">
                    <h2 class="section-title mb-3">Who We Are</h2>
                    <p class="text-gray-600 text-xs font-bold leading-relaxed text-justify type-target" 
                       data-text="Anggun Gadget is Sabah's premier destination for cutting-edge technology. We don't just sell gadgets; we curate experiences. From flagship smartphones to essential accessories, every item in our inventory is hand-picked to ensure it meets our strict quality standards.">
                    </p>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-8">
                    <div>
                        <h6 class="font-black text-xs uppercase mb-2">100% Original</h6>
                        <p class="text-[10px] text-gray-500 font-semibold">Sourced directly from authorized distributors.</p>
                    </div>
                    <div>
                        <h6 class="font-black text-xs uppercase mb-2">Fast Delivery</h6>
                        <p class="text-[10px] text-gray-500 font-semibold">Rapid dispatch guaranteed for all orders.</p>
                    </div>
                    <div>
                        <h6 class="font-black text-xs uppercase mb-2">Official Warranty</h6>
                        <p class="text-[10px] text-gray-500 font-semibold">Full manufacturer coverage included.</p>
                    </div>
                    <div>
                        <h6 class="font-black text-xs uppercase mb-2">Expert Support</h6>
                        <p class="text-[10px] text-gray-500 font-semibold">Real humans ready to help you choose.</p>
                    </div>
                </div>

                <div class="grid grid-cols-2 gap-4">
                    <a href="shop.php" class="flex items-center justify-center gap-2 bg-black text-white py-3 rounded-xl text-[10px] font-bold uppercase tracking-wider hover:bg-gray-800 transition-transform hover:-translate-y-1">
                        <i class="bi bi-shop"></i> Browse Shop
                    </a>
                    <a href="contact.php" class="flex items-center justify-center gap-2 bg-white text-black border border-gray-200 py-3 rounded-xl text-[10px] font-bold uppercase tracking-wider hover:border-black transition-transform hover:-translate-y-1">
                        <i class="bi bi-chat-dots"></i> Contact Us
                    </a>
                </div>
            </div>

            <div class="visual-container">
                <div class="ag-phone-wrapper">
                    <div class="phone-border-layer"></div>
                    <div class="phone-body-layer"></div>
                </div>
                <div class="ui-card ui-1"><div class="ui-icon"><i class="fas fa-check"></i></div><span>Authentic</span></div>
                <div class="ui-card ui-2"><div class="ui-icon"><i class="fas fa-bolt"></i></div><span>Fast</span></div>
            </div>
        </div>

        <div class="max-w-3xl mx-auto">
            <h3 class="text-center section-title text-2xl mb-10">Frequently Asked Questions</h3>
            
            <div class="faq-list">
                <div class="faq-item">
                    <button class="faq-btn" onclick="toggleFaq(this)">
                        <span>Are your products original?</span>
                        <i class="fas fa-chevron-down faq-icon"></i>
                    </button>
                    <div class="faq-content">
                        <div class="faq-inner">
                            Yes, absolutely. We only sell 100% original products sourced directly from authorized brand distributors (Apple, Samsung, Xiaomi, etc.).
                        </div>
                    </div>
                </div>

                <div class="faq-item">
                    <button class="faq-btn" onclick="toggleFaq(this)">
                        <span>Do you offer installment plans?</span>
                        <i class="fas fa-chevron-down faq-icon"></i>
                    </button>
                    <div class="faq-content">
                        <div class="faq-inner">
                            Currently, we accept Credit Card, Debit Card, and Online Banking. Please check our payment page during checkout for any available installment options via third-party providers.
                        </div>
                    </div>
                </div>

                <div class="faq-item">
                    <button class="faq-btn" onclick="toggleFaq(this)">
                        <span>How long does shipping take?</span>
                        <i class="fas fa-chevron-down faq-icon"></i>
                    </button>
                    <div class="faq-content">
                        <div class="faq-inner">
                            For local Sabah orders, delivery takes 1-3 working days. For West Malaysia, please allow 3-5 working days via our trusted courier partners.
                        </div>
                    </div>
                </div>
            </div>
        </div>

    </div> 
    
    <?php include 'footer.php'; ?>
    
    <script>
        // TYPEWRITER SCRIPT
        document.addEventListener('DOMContentLoaded', function() {
            class Typewriter {
                constructor(el) {
                    this.el = el;
                    this.text = el.getAttribute('data-text') || "";
                    this.duration = 3000; 
                    this.speed = this.text.length > 0 ? (this.duration / this.text.length) : 50;
                    this.index = 0;
                    this.timer = null;
                    this.isTyping = false;
                    this.el.innerHTML = ""; 
                }
                type() {
                    if (this.isTyping) return;
                    this.isTyping = true;
                    this.el.innerHTML = "";
                    this.index = 0;
                    this.el.classList.add('typing-cursor');
                    const step = () => {
                        if (this.index < this.text.length) {
                            this.el.innerHTML += this.text.charAt(this.index);
                            this.index++;
                            this.timer = setTimeout(step, this.speed);
                        } else {
                            this.el.classList.remove('typing-cursor');
                        }
                    };
                    step();
                }
                untype() {
                    this.isTyping = false;
                    clearTimeout(this.timer);
                    this.el.classList.add('typing-cursor');
                    const step = () => {
                        if (this.el.innerHTML.length > 0) {
                            this.el.innerHTML = this.el.innerHTML.slice(0, -1);
                            this.timer = setTimeout(step, 10); 
                        }
                    };
                    step();
                }
            }

            const typeTargets = document.querySelectorAll('.type-target');
            const writers = new Map();
            typeTargets.forEach(el => { writers.set(el, new Typewriter(el)); });

            const scrollObserver = new IntersectionObserver((entries) => {
                entries.forEach(entry => {
                    // Trigger Typewriter
                    if(writers.has(entry.target)) {
                        const writer = writers.get(entry.target);
                        if (entry.isIntersecting) writer.type();
                        else writer.untype();
                    }
                    // Trigger Flip Animation
                    if(entry.target.classList.contains('stat-card')) {
                        if (entry.isIntersecting) entry.target.classList.add('do-flip');
                        else entry.target.classList.remove('do-flip');
                    }
                });
            }, { threshold: 0.2 });

            typeTargets.forEach(el => scrollObserver.observe(el));
            document.querySelectorAll('.stat-card').forEach(el => scrollObserver.observe(el));
        });

        // FAQ TOGGLE SCRIPT
        function toggleFaq(btn) {
            const item = btn.parentElement;
            const content = item.querySelector('.faq-content');
            
            document.querySelectorAll('.faq-item').forEach(other => {
                if(other !== item) {
                    other.classList.remove('active');
                    other.querySelector('.faq-content').style.maxHeight = null;
                }
            });

            item.classList.toggle('active');
            if (item.classList.contains('active')) {
                content.style.maxHeight = content.scrollHeight + "px";
            } else {
                content.style.maxHeight = null;
            }
        }
    </script>

    <script>
    function autoResize() {
        // Force the site to think it is 500px wide
        // This makes everything look huge and readable on mobile
        let desiredWidth = 500; 

        let screenWidth = window.innerWidth;

        if (screenWidth < desiredWidth) {
            let scale = screenWidth / desiredWidth;
            document.body.style.transform = 'scale(' + scale + ')';
            document.body.style.transformOrigin = 'top left';
            document.body.style.width = (100 / scale) + '%';
            document.body.style.overflowX = 'hidden';
        } else {
            // Reset on Desktop
            document.body.style.transform = 'none';
            document.body.style.width = '100%';
        }
    }

    // Run immediately and whenever the screen spins
    window.addEventListener('load', autoResize);
    window.addEventListener('resize', autoResize);
</script>

</body>
</html>