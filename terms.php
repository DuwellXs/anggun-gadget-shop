<?php
@include 'config.php';
session_start();
$user_id = $_SESSION['user_id'] ?? null;
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Terms & Conditions | Anggun Gadget</title>
    
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;600;700;800;900&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <link rel="stylesheet" href="css/style.css">

    <style>
        /* === 1. NUCLEAR SCROLL FIX (MATCHING RATINGS.PHP) === */
        html, body {
            margin: 0; padding: 0;
            height: 100vh; 
            overflow: hidden !important;
            font-family: 'Inter', sans-serif;
            background-color: #ffffff !important;
        }

        .master-scroll-wrapper {
            height: 100vh;
            overflow-y: auto;
            scroll-behavior: smooth;
            padding-top: 100px;
            scrollbar-width: none; 
            -ms-overflow-style: none;
            background-color: #ffffff !important;
        }
        .master-scroll-wrapper::-webkit-scrollbar { display: none; }

        /* === 2. LAYOUT GRID === */
        .content-container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 60px 20px 150px;
            display: grid;
            grid-template-columns: 30% 65%; /* Adjusted for reading width */
            gap: 5%;
            align-items: start;
            min-height: 100vh;
        }

        /* === 3. LEFT PANEL STYLES === */
        .left-sticky-panel {
            position: sticky;
            top: 50px;
            padding-right: 20px;
            z-index: 30;
        }

        .hero-badge {
            background: #000; color: #fff; 
            font-size: 0.7rem; font-weight: 700; letter-spacing: 2px; text-transform: uppercase;
            padding: 6px 14px; border-radius: 50px; margin-bottom: 25px; display: inline-block;
        }

        .text-shadow-pop { text-shadow: 2px 2px 0px #cbd5e1; }

        .hero-title {
            font-size: 3rem; font-weight: 900; line-height: 1; color: #111;
            text-transform: uppercase; letter-spacing: -1px; margin-bottom: 20px;
        }
        
        .hero-desc {
            font-size: 0.9rem; line-height: 1.6; color: #666; font-weight: 500;
            border-left: 3px solid #eee; padding-left: 20px; margin-bottom: 40px;
        }

        /* Navigation Buttons (Matching Filter Buttons) */
        .nav-btn {
            background: #fff; border: 1px solid #eee; color: #111;
            padding: 14px 20px; border-radius: 12px; font-weight: 700; font-size: 0.75rem;
            text-transform: uppercase; letter-spacing: 1px; width: 100%; text-align: left;
            display: flex; justify-content: space-between; align-items: center;
            cursor: pointer; transition: all 0.2s; box-shadow: 0 4px 10px rgba(0,0,0,0.03);
            margin-bottom: 12px; text-decoration: none;
        }
        .nav-btn:hover { border-color: #000; transform: translateY(-2px); }
        .nav-btn.active { background: #000; color: #fff; border-color: #000; }
        .nav-btn.active i { color: #fff; }

        /* === 4. RIGHT PANEL (CARDS) === */
        .right-card-stack {
            display: flex; flex-direction: column; gap: 30px; 
            padding-bottom: 100px;
        }

        .term-card {
            background: #fff;
            border-radius: 24px;
            border: 1px solid rgba(0,0,0,0.04);
            box-shadow: 0 10px 40px -10px rgba(0,0,0,0.05);
            padding: 40px;
            transition: transform 0.3s ease, box-shadow 0.3s ease;
            scroll-margin-top: 120px; /* For smooth scrolling offset */
        }
        .term-card:hover { transform: translateY(-5px); box-shadow: 0 20px 50px rgba(0,0,0,0.1); }

        .card-header {
            display: flex; align-items: center; gap: 15px; margin-bottom: 25px;
            padding-bottom: 20px; border-bottom: 1px solid #f5f5f5;
        }

        .card-icon {
            width: 45px; height: 45px; background: #f8fafc; color: #1e293b; 
            border-radius: 12px; display: flex; align-items: center; justify-content: center;
            font-size: 1.2rem;
        }

        .card-title { font-size: 1.25rem; font-weight: 900; color: #111; text-transform: uppercase; letter-spacing: -0.5px; }
        
        .card-content { font-size: 0.95rem; line-height: 1.8; color: #475569; font-weight: 400; }
        .card-content p { margin-bottom: 15px; }
        .card-content strong { color: #111; font-weight: 700; }
        
        .card-list { list-style: none; padding: 0; margin-top: 15px; }
        .card-list li {
            position: relative; padding-left: 25px; margin-bottom: 10px;
            font-size: 0.9rem; font-weight: 500; color: #334155;
        }
        .card-list li::before {
            content: '\f00c'; font-family: 'Font Awesome 5 Free'; font-weight: 900;
            position: absolute; left: 0; top: 1px; color: #111; font-size: 0.7rem;
        }

        @media (max-width: 900px) {
            .content-container { display: block; padding: 40px 20px; }
            .left-sticky-panel { position: relative; top: 0; margin-bottom: 40px; padding-right: 0; }
            .term-card { padding: 25px; }
        }
    </style>
</head>
<body>
    
    <?php include 'header.php'; ?>

    <div class="master-scroll-wrapper">
        
        <div class="content-container">
            
            <div class="left-sticky-panel">
                <span class="hero-badge">Legal Center</span>
                <h1 class="hero-title text-shadow-pop">Terms &<br>Policies</h1>
                <p class="hero-desc">Please read these terms carefully before using our services. Last updated: January 2026.</p>

                <div class="flex flex-col">
                    <a href="#sec-general" class="nav-btn">
                        <span>1. General Terms</span>
                        <i class="fas fa-chevron-right text-xs text-gray-400"></i>
                    </a>
                    <a href="#sec-privacy" class="nav-btn">
                        <span>2. Privacy Policy</span>
                        <i class="fas fa-chevron-right text-xs text-gray-400"></i>
                    </a>
                    <a href="#sec-shipping" class="nav-btn">
                        <span>3. Shipping</span>
                        <i class="fas fa-chevron-right text-xs text-gray-400"></i>
                    </a>
                    <a href="#sec-returns" class="nav-btn">
                        <span>4. Returns & Refunds</span>
                        <i class="fas fa-chevron-right text-xs text-gray-400"></i>
                    </a>
                </div>
                
                <div class="mt-8 pt-8 border-t border-gray-100">
                    <p class="text-xs font-bold text-gray-400 uppercase tracking-widest mb-2">Need Help?</p>
                    <a href="mailto:support@anggungadget.com" class="text-sm font-black text-black hover:underline">support@anggungadget.com</a>
                </div>
            </div>

            <div class="right-card-stack">
                
                <div id="sec-general" class="term-card">
                    <div class="card-header">
                        <div class="card-icon"><i class="fas fa-gavel"></i></div>
                        <h2 class="card-title">General Terms</h2>
                    </div>
                    <div class="card-content">
                        <p>Welcome to <strong>Anggun Gadget</strong>. By accessing our website and placing orders, you agree to be bound by these Terms and Conditions.</p>
                        <p>We reserve the right to update these terms at any time without prior notice. Your continued use of the site following any changes signifies your acceptance of those changes.</p>
                        <ul class="card-list">
                            <li>You must be at least 18 years old to use this service.</li>
                            <li>We reserve the right to refuse service to anyone for any reason at any time.</li>
                        </ul>
                    </div>
                </div>

                <div id="sec-privacy" class="term-card">
                    <div class="card-header">
                        <div class="card-icon"><i class="fas fa-user-shield"></i></div>
                        <h2 class="card-title">Privacy Policy</h2>
                    </div>
                    <div class="card-content">
                        <p>Your privacy is critical to us. We collect information only to process your orders and improve your shopping experience.</p>
                        <ul class="card-list">
                            <li><strong>Data Collection:</strong> We collect personal data such as name, address, and email solely for order fulfillment.</li>
                            <li><strong>Security:</strong> All transactions are encrypted and secure. We do not store your credit card information.</li>
                            <li><strong>Cookies:</strong> Our site uses cookies to remember your cart preferences.</li>
                        </ul>
                    </div>
                </div>

                <div id="sec-shipping" class="term-card">
                    <div class="card-header">
                        <div class="card-icon"><i class="fas fa-shipping-fast"></i></div>
                        <h2 class="card-title">Shipping Policy</h2>
                    </div>
                    <div class="card-content">
                        <p>We provide reliable delivery services across Malaysia, with specialized expedited options for Sabah.</p>
                        <ul class="card-list">
                            <li><strong>Standard Delivery (Logistics Hub):</strong> Expected delivery within <strong>3 - 7 working days</strong> for all regions.</li>
                            <li><strong>Direct Delivery (Sabah Only):</strong> Orders within our direct coverage radius in Sabah are <strong>shipped daily</strong>.</li>
                            <li><strong>Processing:</strong> All orders are processed within 24 hours of payment confirmation.</li>
                            <li><strong>Tracking:</strong> A tracking number will be provided via email once your parcel is dispatched.</li>
                        </ul>
                    </div>
                </div>

                <div id="sec-returns" class="term-card">
                    <div class="card-header">
                        <div class="card-icon"><i class="fas fa-undo"></i></div>
                        <h2 class="card-title">Returns & Refunds</h2>
                    </div>
                    <div class="card-content">
                        <p>If you are not satisfied with your purchase, you may return items under the following conditions:</p>
                        <ul class="card-list">
                            <li>Items must be returned within <strong>7 days</strong> of receipt.</li>
                            <li>Product must be in original condition, unused, and with tags attached.</li>
                            <li>Return shipping costs are the responsibility of the customer unless the item is defective.</li>
                        </ul>
                    </div>
                </div>

            </div>

        </div>

        <?php include 'footer.php'; ?>
    
    </div>

</body>
</html>