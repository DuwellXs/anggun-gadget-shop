<?php
@include 'config.php';
session_start();

$user_id = $_SESSION['user_id'];

if(!isset($user_id)){
   header('location:login.php');
   exit;
}

// Fetch just the ID and PAYMENT_ID for the link
$stmt = $conn->prepare("SELECT id, payment_id FROM `orders` WHERE user_id = ? ORDER BY id DESC LIMIT 1");
$stmt->execute([$user_id]);
$latest_order = $stmt->fetch(PDO::FETCH_ASSOC);

$receipt_link = $latest_order ? "receipt.php?order_id=" . $latest_order['id'] : "home.php";
$display_ref = !empty($latest_order['payment_id']) ? $latest_order['payment_id'] : '';
?>

<!DOCTYPE html>
<html lang="en">
<head>
   <meta charset="UTF-8">
   <meta name="viewport" content="width=device-width, initial-scale=1.0">
   <title>Success | Anggun Gadget</title>

   <script src="https://cdn.tailwindcss.com"></script>
   <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.1.1/css/all.min.css">
   <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800;900&display=swap" rel="stylesheet">
   
   <style>
      /* === SAME DESIGN SYSTEM === */
      html, body {
          margin: 0; padding: 0;
          width: 100%; min-height: 100vh;
          font-family: 'Inter', sans-serif;
          background-color: #f8fafc !important;
          background: radial-gradient(circle at center, #ffffff 0%, #f1f5f9 100%) !important;
          display: flex;
          align-items: center;
          justify-content: center;
          overflow: hidden;
      }
      .phone-wrapper {
          position: relative;
          width: 380px; height: 720px;
          display: flex; align-items: center; justify-content: center;
          animation: levitate 6s ease-in-out infinite;
      }
      .phone-chassis {
          width: 100%; height: 100%;
          border-radius: 55px; padding: 12px;
          background: linear-gradient(180deg, #38bdf8 0%, #2563eb 50%, #1d4ed8 100%);
          box-shadow: 0 30px 60px -15px rgba(37, 99, 235, 0.35), 0 0 0 1px rgba(255,255,255,0.2);
          position: relative; z-index: 10;
      }
      .phone-screen {
          width: 100%; height: 100%;
          background: #ffffff;
          border-radius: 44px; overflow: hidden;
          position: relative; display: flex; flex-direction: column;
          box-shadow: inset 0 0 20px rgba(0,0,0,0.05);
      }
      .notch {
          position: absolute; top: 0; left: 50%; transform: translateX(-50%);
          width: 150px; height: 32px; background: #0f172a;
          border-bottom-left-radius: 22px; border-bottom-right-radius: 22px;
          z-index: 50;
      }
      .float-badge {
          position: absolute; background: #ffffff; padding: 14px 22px;
          border-radius: 18px;
          box-shadow: 0 20px 40px -5px rgba(0,0,0,0.15), 0 0 0 1px rgba(0,0,0,0.02);
          font-weight: 800; font-size: 0.7rem; text-transform: uppercase; letter-spacing: 0.5px;
          display: flex; align-items: center; gap: 12px; white-space: nowrap; z-index: 30; 
      }
      .float-left { top: 20%; left: -140px; animation: swayLeft 5s ease-in-out infinite; }
      .float-right { bottom: 20%; right: -140px; animation: swayRight 5s ease-in-out infinite 1s; }

      @keyframes levitate { 0%, 100% { transform: translateY(0px); } 50% { transform: translateY(-15px); } }
      @keyframes swayLeft { 0%, 100% { transform: translateX(0px); } 50% { transform: translateX(-10px); } }
      @keyframes swayRight { 0%, 100% { transform: translateX(0px); } 50% { transform: translateX(10px); } }
   </style>
</head>
<body>

    <div class="phone-wrapper">
        
        <div class="float-badge float-left">
            <div class="w-8 h-8 rounded-full bg-slate-50 flex items-center justify-center text-slate-500 shadow-sm border border-slate-100"><i class="fas fa-check text-sm"></i></div>
            <div class="flex flex-col">
                <span class="text-[9px] text-gray-400 font-bold leading-tight">ORDER</span>
                <span class="text-slate-800 leading-tight">Confirmed</span>
            </div>
        </div>

        <div class="float-badge float-right">
            <div class="w-8 h-8 rounded-full bg-blue-50 flex items-center justify-center text-blue-500 shadow-sm border border-blue-100"><i class="fas fa-smile text-sm"></i></div>
            <div class="flex flex-col">
                <span class="text-[9px] text-gray-400 font-bold leading-tight">THANKS</span>
                <span class="text-slate-800 leading-tight">Come Again</span>
            </div>
        </div>

        <div class="phone-chassis">
            <div class="phone-screen relative">
                <div class="notch"></div>

                <div class="flex-1 flex flex-col items-center justify-center p-8 text-center mt-8">
                    
                    <div class="w-24 h-24 bg-blue-50 text-blue-600 rounded-full flex items-center justify-center mb-6 border-4 border-blue-100 animate-bounce">
                        <i class="fas fa-thumbs-up text-3xl"></i>
                    </div>

                    <h1 class="text-2xl font-black uppercase tracking-tight text-slate-900 mb-2">
                        Order Placed!
                    </h1>
                    
                    <?php if($display_ref): ?>
                    <p class="text-[10px] font-bold text-slate-400 uppercase tracking-widest mb-4">
                        Ref: <?= htmlspecialchars($display_ref) ?>
                    </p>
                    <?php endif; ?>

                    <p class="text-sm font-medium text-slate-500 leading-relaxed mb-8">
                        Thank you for your purchase.<br>
                        Your order is being processed and will be shipped shortly.
                    </p>

                    <div class="w-full space-y-3">
                        <a href="<?= $receipt_link ?>" class="block w-full py-4 bg-slate-900 text-white text-[10px] font-bold uppercase tracking-widest rounded-xl hover:bg-black transition-all shadow-xl shadow-slate-200 transform hover:scale-[1.02]">
                            View Official Receipt
                        </a>

                        <a href="home.php" class="block w-full py-4 bg-white text-slate-900 border-2 border-slate-100 text-[10px] font-bold uppercase tracking-widest rounded-xl hover:bg-slate-50 transition-all">
                            Back to Home
                        </a>
                    </div>

                </div>

                <div class="pb-8 text-center opacity-30">
                     <i class="fas fa-grip-lines text-slate-300"></i>
                </div>

            </div>
        </div>
    </div>

</body>
</html>