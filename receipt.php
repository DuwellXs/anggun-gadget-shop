<?php
@include 'config.php';
session_start();

$user_id = $_SESSION['user_id'];

if(!isset($user_id)){
   header('location:login.php');
   exit;
}

// Logic: Check if specific ID is requested, otherwise get latest
if(isset($_GET['order_id'])){
    $oid = $_GET['order_id'];
    $stmt = $conn->prepare("SELECT * FROM `orders` WHERE id = ? AND user_id = ?");
    $stmt->execute([$oid, $user_id]);
} else {
    $stmt = $conn->prepare("SELECT * FROM `orders` WHERE user_id = ? ORDER BY id DESC LIMIT 1");
    $stmt->execute([$user_id]);
}
$order_data = $stmt->fetch(PDO::FETCH_ASSOC);

if(!$order_data){
    echo "Order not found.";
    exit;
}

// === UNIFIED REFERENCE ID FROM PAYMENT_ID COLUMN ===
// We use the existing 'payment_id' column to display your unique Order Ref
$display_ref = !empty($order_data['payment_id']) 
    ? $order_data['payment_id'] 
    : 'ORD-' . str_pad($order_data['id'], 6, '0', STR_PAD_LEFT);

function parseProductString($str) {
    $item = ['name' => $str, 'variant' => '', 'qty' => '1'];
    if (preg_match('/\(Qty:\s*(\d+)\)/', $str, $matches)) {
        $item['qty'] = $matches[1];
        $str = str_replace($matches[0], '', $str);
    }
    if (preg_match('/\[(.*?)\]/', $str, $matches)) {
        $item['variant'] = $matches[1];
        $str = str_replace($matches[0], '', $str);
    }
    $item['name'] = trim($str);
    return $item;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
   <meta charset="UTF-8">
   <meta name="viewport" content="width=device-width, initial-scale=1.0">
   <title>Receipt <?= htmlspecialchars($display_ref) ?> | Anggun Gadget</title>

   <script src="https://cdn.tailwindcss.com"></script>
   <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.1.1/css/all.min.css">
   <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800;900&display=swap" rel="stylesheet">
   
   <style>
      /* === 1. NUCLEAR BACKGROUND === */
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

      /* === 2. CENTERED PHONE WRAPPER === */
      .phone-wrapper {
          position: relative;
          width: 380px;
          height: 720px;
          display: flex;
          align-items: center;
          justify-content: center;
          animation: levitate 6s ease-in-out infinite;
      }

      /* === 3. PHONE CHASSIS === */
      .phone-chassis {
          width: 100%; height: 100%;
          border-radius: 55px;
          padding: 12px;
          background: linear-gradient(180deg, #38bdf8 0%, #2563eb 50%, #1d4ed8 100%);
          box-shadow: 0 30px 60px -15px rgba(37, 99, 235, 0.35), 0 0 0 1px rgba(255,255,255,0.2);
          position: relative;
          z-index: 10;
      }

      /* === 4. SCREEN === */
      .phone-screen {
          width: 100%; height: 100%;
          background: #ffffff;
          border-radius: 44px;
          overflow: hidden;
          position: relative;
          display: flex;
          flex-direction: column;
          box-shadow: inset 0 0 20px rgba(0,0,0,0.05);
      }

      .notch {
          position: absolute; top: 0; left: 50%;
          transform: translateX(-50%);
          width: 150px; height: 32px;
          background: #0f172a;
          border-bottom-left-radius: 22px;
          border-bottom-right-radius: 22px;
          z-index: 50;
      }

      /* === 5. FLOATING BADGES === */
      .float-badge {
          position: absolute;
          background: #ffffff;
          padding: 14px 22px;
          border-radius: 18px;
          box-shadow: 0 20px 40px -5px rgba(0,0,0,0.15), 0 0 0 1px rgba(0,0,0,0.02);
          font-weight: 800; font-size: 0.7rem; text-transform: uppercase; letter-spacing: 0.5px;
          display: flex; align-items: center; gap: 12px; white-space: nowrap;
          z-index: 30; 
      }
      .float-left { top: 15%; left: -160px; animation: swayLeft 5s ease-in-out infinite; }
      .float-right { bottom: 15%; right: -160px; animation: swayRight 5s ease-in-out infinite 1s; }

      @keyframes levitate { 0%, 100% { transform: translateY(0px); } 50% { transform: translateY(-15px); } }
      @keyframes swayLeft { 0%, 100% { transform: translateX(0px); } 50% { transform: translateX(-10px); } }
      @keyframes swayRight { 0%, 100% { transform: translateX(0px); } 50% { transform: translateX(10px); } }
      .custom-scroll::-webkit-scrollbar { width: 0px; display: none; }
   </style>
</head>
<body>

    <div class="phone-wrapper">
        
        <div class="float-badge float-left">
            <div class="w-8 h-8 rounded-full bg-emerald-50 flex items-center justify-center text-emerald-500 shadow-sm border border-emerald-100"><i class="fas fa-shield-alt text-sm"></i></div>
            <div class="flex flex-col">
                <span class="text-[9px] text-gray-400 font-bold leading-tight">STATUS</span>
                <span class="text-slate-800 leading-tight">Payment Verified</span>
            </div>
        </div>

        <div class="float-badge float-right">
            <div class="w-8 h-8 rounded-full bg-blue-50 flex items-center justify-center text-blue-500 shadow-sm border border-blue-100"><i class="fas fa-paper-plane text-sm"></i></div>
            <div class="flex flex-col">
                <span class="text-[9px] text-gray-400 font-bold leading-tight">EMAIL</span>
                <span class="text-slate-800 leading-tight">Receipt Sent</span>
            </div>
        </div>

        <div class="phone-chassis">
            <div class="phone-screen pb-6 pt-14">
                <div class="notch"></div>

                <div class="px-6 text-center mb-6">
                    <div class="w-16 h-16 bg-green-50 text-green-500 rounded-full flex items-center justify-center mx-auto mb-4 border-4 border-green-100/50 shadow-sm">
                        <i class="fas fa-check text-2xl"></i>
                    </div>
                    <h1 class="text-xl font-black uppercase tracking-tight text-slate-900">Payment Success</h1>
                    
                    <p class="text-[10px] font-bold text-slate-400 uppercase tracking-widest mt-1">
                        Ref: <?= htmlspecialchars($display_ref) ?>
                    </p>
                </div>

                <div class="flex-1 px-5 overflow-hidden flex flex-col">
                    <div class="bg-slate-50 rounded-2xl flex-1 flex flex-col shadow-inner border border-slate-100 overflow-hidden relative">
                        <div class="p-4 border-b border-slate-200/50 bg-white/50 backdrop-blur-sm">
                            <h3 class="text-[10px] font-black uppercase tracking-widest text-slate-400">Items Purchased</h3>
                        </div>

                        <div class="flex-1 overflow-y-auto p-4 space-y-4 custom-scroll">
                            <?php
                                $products = explode(', ', $order_data['total_products']);
                                foreach($products as $product_str){
                                    $item = parseProductString($product_str);
                            ?>
                            <div class="flex justify-between items-start group">
                                <div class="flex-1 pr-3">
                                    <p class="font-bold text-slate-800 text-xs leading-tight"><?= htmlspecialchars($item['name']); ?></p>
                                    <?php if(!empty($item['variant'])): ?>
                                        <span class="inline-block mt-1 text-[9px] font-bold text-blue-600 bg-blue-50 px-1.5 py-0.5 rounded border border-blue-100/50">
                                            <?= str_replace(';', ' | ', htmlspecialchars($item['variant'])); ?>
                                        </span>
                                    <?php endif; ?>
                                </div>
                                <div class="flex flex-col items-end">
                                    <span class="text-xs font-black text-slate-900 bg-white border border-slate-200 px-2 py-0.5 rounded shadow-sm">x<?= $item['qty']; ?></span>
                                </div>
                            </div>
                            <div class="border-b border-dashed border-slate-200 last:hidden"></div>
                            <?php } ?>
                        </div>

                        <div class="bg-white p-4 border-t border-slate-200 flex justify-between items-center shadow-sm">
                            <span class="text-[10px] font-bold text-slate-400 uppercase">Total Paid</span>
                            <span class="text-xl font-black text-slate-900">RM<?= number_format($order_data['total_price'], 2); ?></span>
                        </div>
                    </div>
                </div>

                <div class="px-6 mt-6 space-y-4">
                    <a href="shop.php" class="block w-full py-4 bg-slate-900 text-white text-[10px] font-bold uppercase tracking-widest rounded-xl hover:bg-black transition-all shadow-xl shadow-slate-200 text-center transform hover:scale-[1.02]">
                        Continue Shopping
                    </a>
                    <div class="text-center pb-2">
                        <p class="text-[9px] text-slate-400 font-bold uppercase tracking-widest">Receipt sent to</p>
                        <p class="text-[10px] font-bold text-blue-600 mt-0.5 break-all"><?= htmlspecialchars($order_data['email']); ?></p>
                    </div>
                </div>

            </div>
        </div>
    </div>

</body>
</html>