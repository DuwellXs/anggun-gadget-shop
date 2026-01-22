<?php

@include 'config.php';

session_start();

$user_id = $_SESSION['user_id'];

if(!isset($user_id)){
   header('location:login.php');
   exit;
};

// 1. GENERATE OR RETRIEVE THE REFERENCE ID
// This ensures the ID stays the same until the order is placed
if(!isset($_SESSION['ref_id'])) {
    $_SESSION['ref_id'] = 'ORD-' . strtoupper(substr(uniqid(), -5)) . '-' . date('dm');
}
$reference_id = $_SESSION['ref_id'];

// --- CAPTURE SELECTED ITEMS FROM CART ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['selected_items'])) {
    $_SESSION['checkout_ids'] = $_POST['selected_items'];
}

$filter_ids = $_SESSION['checkout_ids'] ?? [];
$use_filter = !empty($filter_ids);

// LOAD USER PROFILE
$fetch_profile = [];
try {
   $profile_query = $conn->prepare("SELECT * FROM `users` WHERE id = ? LIMIT 1");
   $profile_query->execute([$user_id]);
   if ($profile_query->rowCount() > 0) {
      $fetch_profile = $profile_query->fetch(PDO::FETCH_ASSOC);
   }
} catch (Exception $e) {
   $message[] = 'Error loading user profile.';
}

if(isset($_POST['order'])){
      $p_method = 'PayPal';

      $name = htmlspecialchars($_POST['name']);
      $number = htmlspecialchars($_POST['number']);
      $email = htmlspecialchars($_POST['email']);

      $address = 'House No '. $_POST['unit'] .' '. $_POST['street'] .' '. $_POST['city'] .' '. $_POST['state'] .' - '. $_POST['pin_code'];
      $address = htmlspecialchars($address);
      $placed_on = date('d-M-Y');

      $cart_total = 0;
      $cart_products = [];
   
      // FILTER CART ITEMS
      $cart_sql = "SELECT * FROM `cart` WHERE user_id = ?";
      $cart_params = [$user_id];

      if($use_filter){
          $placeholders = implode(',', array_fill(0, count($filter_ids), '?'));
          $cart_sql .= " AND id IN ($placeholders)";
          $cart_params = array_merge($cart_params, $filter_ids);
      }

      $cart_query = $conn->prepare($cart_sql);
      $cart_query->execute($cart_params);

      if($cart_query->rowCount() > 0){
         while($cart_item = $cart_query->fetch(PDO::FETCH_ASSOC)){
            $variant_info = !empty($cart_item['selected_variants']) ? ' ['.$cart_item['selected_variants'].']' : '';
            $cart_products[] = $cart_item['name'] . $variant_info . ' (Qty: '.$cart_item['quantity'].')';
            $sub_total = ($cart_item['price'] * $cart_item['quantity']);
            $cart_total += $sub_total;
         };
      };
   
      $total_products = implode(', ', $cart_products);
      
      // === SAVE ORDER TO DATABASE ===
      // We are saving $reference_id into the 'payment_id' column
      $insert_order = $conn->prepare("INSERT INTO `orders`(user_id, name, number, email, method, address, total_products, total_price, placed_on, payment_id, order_type, payment_status, delivery_status) VALUES(?,?,?,?,?,?,?,?,?,?, 1, 'completed', 'Preparing Order')");
      $insert_order->execute([$user_id, $name, $number, $email, $p_method, $address, $total_products, $cart_total, $placed_on, $reference_id]);
      
      // SALES COUNTER UPDATE
      $sales_cart_query = $conn->prepare($cart_sql); 
      $sales_cart_query->execute($cart_params); 

      if($sales_cart_query->rowCount() > 0){
         while($sales_item = $sales_cart_query->fetch(PDO::FETCH_ASSOC)){
            $update_product_sales = $conn->prepare("UPDATE `products` SET sales_count = sales_count + ? WHERE id = ?");
            $update_product_sales->execute([$sales_item['quantity'], $sales_item['pid']]);
         }
      }
      
      // DELETE ITEMS FROM CART
      if($use_filter){
          $placeholders = implode(',', array_fill(0, count($filter_ids), '?'));
          $delete_cart = $conn->prepare("DELETE FROM `cart` WHERE user_id = ? AND id IN ($placeholders)");
          $delete_params = array_merge([$user_id], $filter_ids);
          $delete_cart->execute($delete_params);
      } else {
          $delete_cart = $conn->prepare("DELETE FROM `cart` WHERE user_id = ?");
          $delete_cart->execute([$user_id]);
      }

      // UPDATE USER POINTS
      $select_point = $conn->prepare("SELECT `point` FROM `users` WHERE id = ?");
      $select_point->execute([$user_id]);
      $user_point = $select_point->fetch(PDO::FETCH_ASSOC);

      $new_point = $user_point['point'] + $cart_total;

      $update_point = $conn->prepare("UPDATE `users` SET point = ? WHERE id = ?");
      $update_point->execute([$new_point, $user_id]);

      // RESET SESSION ID (So next order gets a fresh ID)
      unset($_SESSION['checkout_ids']);
      unset($_SESSION['ref_id']); 

      $message[] = 'Order Placed Successfully!';

      header('location:complete_msg.php');
   
}

?>

<!DOCTYPE html>
<html lang="en">
<head>
   <meta charset="UTF-8">
   <meta name="viewport" content="width=device-width, initial-scale=1.0">
   <title>Secure Checkout | Anggun Gadget</title>

   <script src="https://cdn.tailwindcss.com"></script>
   <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.1.1/css/all.min.css">
   <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800;900&display=swap" rel="stylesheet">
   
   <link rel="stylesheet" href="css/style.css">

   <style>
      /* === LIGHT BLUE-GRAY THEME === */
      html, body {
          font-family: 'Inter', sans-serif;
          background: linear-gradient(180deg, #f8fafc 0%, #cbdceb 100%) !important;
          background-attachment: fixed !important;
          color: #1e293b !important;
          margin: 0; padding: 0;
          height: 100vh;
          width: 100%;
          overflow: auto;
          scrollbar-width: none;
      }
      html::-webkit-scrollbar, body::-webkit-scrollbar { display: none; }

      input:-webkit-autofill,
      input:-webkit-autofill:hover, 
      input:-webkit-autofill:focus, 
      input:-webkit-autofill:active {
          -webkit-box-shadow: 0 0 0 30px #ffffff inset !important;
          -webkit-text-fill-color: #1e293b !important;
          transition: background-color 5000s ease-in-out 0s;
      }

      .overlay { 
          position: fixed; inset: 0; z-index: 100;
          background-color: rgba(15, 23, 42, 0.4);
          backdrop-filter: blur(8px);
          display: flex; align-items: center; justify-content: center;
      }
      
      .content-overlay { 
          background-color: #ffffff; 
          border-radius: 24px;
          padding: 40px; 
          width: 90%; max-width: 500px;
          box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.15);
          animation: slideUp 0.4s cubic-bezier(0.16, 1, 0.3, 1);
          color: #333;
      }

      @keyframes slideUp { from { opacity: 0; transform: translateY(30px); } to { opacity: 1; transform: translateY(0); } }

      .ag-input {
          width: 100%;
          background-color: #ffffff;
          border: 1px solid #cbd5e1;
          color: #334155;
          padding: 14px 16px;
          border-radius: 12px;
          font-size: 0.9rem;
          font-weight: 500;
          outline: none;
          transition: all 0.2s ease;
          box-shadow: 0 2px 5px rgba(0,0,0,0.02);
      }
      
      .ag-input:focus {
          border-color: #64748b;
          background-color: #ffffff;
          box-shadow: 0 4px 12px rgba(0,0,0,0.05);
      }
      
      .ag-input::placeholder { color: #94a3b8; font-weight: 400; }
      
      .ag-label {
          display: block; font-size: 0.7rem; font-weight: 800;
          color: #475569; text-transform: uppercase; margin-bottom: 8px; letter-spacing: 1px;
      }

      .summary-card {
          background: rgba(255, 255, 255, 0.85); 
          backdrop-filter: blur(12px);
          border-radius: 24px;
          padding: 35px;
          border: 1px solid #ffffff;
          position: sticky; top: 120px;
          box-shadow: 0 20px 40px -10px rgba(0,0,0,0.08);
      }
      
      .ref-badge {
          display: inline-block; background: #f1f5f9; color: #475569;
          font-size: 0.65rem; font-weight: 800; padding: 6px 12px;
          border-radius: 50px; letter-spacing: 1px; border: 1px solid #e2e8f0;
      }
      
      .step-circle {
          width: 32px; height: 32px; background: #334155; color: white;
          border-radius: 50%; display: flex; align-items: center; justify-content: center;
          font-weight: 800; font-size: 0.8rem; box-shadow: 0 4px 6px rgba(0,0,0,0.1);
      }
   </style>

</head>
<body>

<div id="overlay" class="overlay">
   <div class="content-overlay text-center">
      <div class="mb-8">
          <div class="w-16 h-16 bg-slate-100 text-slate-800 rounded-full flex items-center justify-center mx-auto mb-5">
              <i class="fas fa-file-contract text-2xl"></i>
          </div>
          <h2 class="text-2xl font-black uppercase mb-3 text-slate-900 tracking-tight">Terms & Conditions</h2>
          
          <p class="text-slate-500 text-sm leading-relaxed max-w-sm mx-auto">
              By proceeding with this transaction, you agree to Anggun Gadget's 
              <a href="terms.php" target="_blank" class="text-slate-900 font-bold underline hover:text-blue-600 transition-colors">
                  Terms of Service & Privacy Policy
              </a>.
          </p>

      </div>
      <div class="flex gap-4 justify-center">
         <button type="button" class="decline-button px-8 py-3 rounded-xl border border-slate-200 text-sm font-bold text-slate-500 hover:bg-slate-50 transition-all">Cancel</button>
         <button type="button" class="accept-button px-8 py-3 rounded-xl bg-black text-white text-sm font-bold hover:bg-gray-800 transition-all shadow-lg hover:scale-105 transform duration-200">I Agree</button>
      </div>
   </div>
</div>

<?php include 'header.php'; ?>

<div class="max-w-7xl mx-auto px-6 pt-32 pb-20">
    
    <form action="" method="POST" id="formSubmit">
        <div class="grid grid-cols-1 lg:grid-cols-12 gap-16">
            
            <div class="lg:col-span-7 space-y-12">
                
                <div class="space-y-6">
                    <div class="flex items-center gap-4 mb-8">
                        <div class="step-circle">1</div>
                        <h3 class="text-xl font-black uppercase tracking-tight text-slate-800">Contact Information</h3>
                    </div>
                    
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div>
                            <label class="ag-label">Full Name</label>
                            <input type="text" name="name" id="input_name" placeholder="John Doe" class="ag-input" value="<?php echo isset($fetch_profile['name']) ? htmlspecialchars($fetch_profile['name']) : ''; ?>" required>
                        </div>
                        <div>
                            <label class="ag-label">Phone Number</label>
                            <input type="number" name="number" id="input_number" placeholder="012-3456789" class="ag-input" value="<?php echo isset($fetch_profile['p_num']) ? htmlspecialchars($fetch_profile['p_num']) : ''; ?>" required>
                        </div>
                    </div>
                    <div>
                        <label class="ag-label">Email Address</label>
                        <input type="email" name="email" id="input_email" placeholder="you@example.com" class="ag-input" value="<?php echo isset($fetch_profile['email']) ? htmlspecialchars($fetch_profile['email']) : ''; ?>" required>
                    </div>
                </div>

                <div class="space-y-6">
                    <div class="flex items-center gap-4 mb-8 pt-8 border-t border-slate-200">
                        <div class="step-circle">2</div>
                        <h3 class="text-xl font-black uppercase tracking-tight text-slate-800">Shipping Address</h3>
                    </div>

                    <div class="grid grid-cols-1 gap-6">
                        <div>
                            <label class="ag-label">Address Line 1 (Unit/House)</label>
                            <input type="text" name="unit" id="input_address_1" placeholder="e.g. B-12-3" class="ag-input" value="<?php echo isset($address[0]) ? htmlspecialchars(trim($address[0])) : ''; ?>" required>
                        </div>
                        <div>
                            <label class="ag-label">Address Line 2 (Street/Area)</label>
                            <input type="text" name="street" id="input_address_2" placeholder="e.g. Jalan Tun Razak" class="ag-input" value="<?php echo isset($address[1]) ? htmlspecialchars(trim($address[1])) : ''; ?>" required>
                        </div>
                    </div>

                    <div class="grid grid-cols-2 md:grid-cols-3 gap-6">
                        <div>
                            <label class="ag-label">Postcode</label>
                            <input type="number" name="pin_code" id="input_code" placeholder="87000" class="ag-input" value="<?php echo isset($address[4]) ? htmlspecialchars(trim($address[4])) : ''; ?>" required>
                        </div>
                        <div>
                            <label class="ag-label">City</label>
                            <input type="text" name="city" id="input_city" placeholder="Labuan" class="ag-input" value="<?php echo isset($address[2]) ? htmlspecialchars(trim($address[2])) : ''; ?>" required>
                        </div>
                        <div class="col-span-2 md:col-span-1">
                            <label class="ag-label">State</label>
                            <input type="text" name="state" id="input_state" placeholder="W.P. Labuan" class="ag-input" value="<?php echo isset($address[3]) ? htmlspecialchars(trim($address[3])) : ''; ?>" required>
                        </div>
                    </div>
                </div>

                <input type="hidden" name="method" value="PayPal">

            </div>

            <div class="lg:col-span-5">
                <div class="summary-card">
                    <div class="flex justify-between items-center mb-8">
                        <h3 class="text-lg font-black uppercase flex items-center gap-2 text-slate-800 tracking-widest">
                            Order Summary
                        </h3>
                        <span class="ref-badge"><?= $reference_id ?></span>
                    </div>

                    <div class="space-y-5 mb-8 max-h-[400px] overflow-y-auto pr-2 custom-scroll">
                        <?php
                            $cart_grand_total = 0;
                            $display_sql = "SELECT * FROM `cart` WHERE user_id = ?";
                            $display_params = [$user_id];
                            if($use_filter){
                                $placeholders = implode(',', array_fill(0, count($filter_ids), '?'));
                                $display_sql .= " AND id IN ($placeholders)";
                                $display_params = array_merge($display_params, $filter_ids);
                            }
                            $select_cart = $conn->prepare($display_sql);
                            $select_cart->execute($display_params);

                            if($select_cart->rowCount() > 0){
                                while($item = $select_cart->fetch(PDO::FETCH_ASSOC)){
                                    $total = $item['price'] * $item['quantity'];
                                    $cart_grand_total += $total;
                        ?>
                        <div class="flex justify-between items-start text-sm py-3 border-b border-slate-100 last:border-0">
                            <div>
                                <p class="font-bold text-slate-800 text-base"><?= $item['name']; ?></p>
                                <p class="text-xs text-slate-500 font-medium mt-1">Qty: <?= $item['quantity']; ?></p>
                                <?php if(!empty($item['selected_variants'])): ?>
                                    <p class="text-[10px] text-slate-400 uppercase tracking-wide mt-1">
                                        <?= str_replace(';', ' | ', $item['selected_variants']); ?>
                                    </p>
                                <?php endif; ?>
                            </div>
                            <p class="font-bold text-slate-900">RM<?= number_format($total, 2); ?></p>
                        </div>
                        <?php }} else { echo '<p class="text-sm text-slate-400 italic">No items selected.</p>'; } ?>
                    </div>

                    <div class="bg-slate-50 rounded-xl p-5 mb-6 border border-slate-100">
                        <div class="flex justify-between text-sm text-slate-500 font-medium mb-3">
                            <span>Subtotal</span>
                            <span>RM<?= number_format($cart_grand_total, 2); ?></span>
                        </div>
                        <div class="flex justify-between text-sm text-slate-500 font-medium mb-4">
                            <span>Shipping</span>
                            <span class="text-green-600 font-bold uppercase text-xs tracking-wider">Free Shipping</span>
                        </div>
                        <div class="flex justify-between text-2xl font-black text-slate-900 pt-4 border-t border-slate-200">
                            <span>Total</span>
                            <span>RM<?= number_format($cart_grand_total, 2); ?></span>
                        </div>
                    </div>

                    <div class="paypal-wrapper">
                        <div id="paypal-button-container" class="w-full"></div>
                        <div class="flex items-center justify-center gap-2 mt-4 text-[10px] uppercase tracking-widest text-slate-400 font-bold">
                            <i class="fas fa-lock"></i> SSL Secure Encrypted Payment
                        </div>
                    </div>

                </div>
            </div>

        </div>
    </form>
</div>

<?php include 'footer.php'; ?>

<script src="https://www.paypal.com/sdk/js?client-id=AcVtwr6vYjotW4ERNbyKeUdi8NESBYAiGqYTpSkrioRGbxE-5Wn8lm2eH5HEQOt2B_R0qJHJGp_6csk1&currency=MYR&intent=capture&components=buttons&enable-funding=venmo&disable-funding=card,credit"></script>
<script src="https://ajax.googleapis.com/ajax/libs/jquery/3.7.1/jquery.min.js"></script>

<script>
   const actBtn = document.querySelector('.accept-button');
   const dclBtn = document.querySelector('.decline-button');
   const overlay = document.querySelector('.overlay');

   if(actBtn) {
       actBtn.addEventListener('click', () => { overlay.style.display = 'none'; });
   }
   if(dclBtn) {
       dclBtn.addEventListener('click', () => { window.location.href = "cart.php"; });
   }

   // ===== PayPal Logic =====
   paypal.Buttons({
      onClick: function(data, actions) {
         let isValid = true;
         // Validate fields before opening PayPal
         const fields = ['input_name', 'input_number', 'input_email', 'input_address_1', 'input_address_2', 'input_city', 'input_state', 'input_code'];
         
         fields.forEach(id => {
             const input = document.getElementById(id);
             if (input.value.trim() === "") {
                 input.style.borderColor = "#ef4444"; 
                 input.style.backgroundColor = "#fff5f5"; 
                 isValid = false;
             } else {
                 input.style.borderColor = "#cbd5e1";
                 input.style.backgroundColor = "#ffffff";
             }
         });

         if (!isValid) {
            alert("Please fill in all address details.");
            return actions.reject();
         }
         return actions.resolve();
      },
      style: { layout: 'vertical', color: 'blue', shape: 'rect', label: 'pay', height: 50 },

      createOrder: (data, actions) => {
         return actions.order.create({
            purchase_units: [{
               // THIS is where we send the Reference ID to PayPal
               reference_id: '<?= $reference_id ?>',
               description: 'Order <?= $reference_id ?>',
               amount: { value: '<?= $cart_grand_total ?>' }
            }]
         });
      },

      onApprove: (data, actions) => {
         return actions.order.capture().then(function(orderData) {
            // Once PayPal says success, we submit the PHP form to save to database
            const form = document.getElementById('formSubmit');
            const hiddenInput = document.createElement('input');
            hiddenInput.type = 'hidden';
            hiddenInput.name = 'order'; 
            hiddenInput.value = '1';
            form.appendChild(hiddenInput);
            form.submit();
         });
      },

      onError: (err) => {
         console.error('PayPal Error:', err);
         alert('An error occurred with PayPal.');
      }

   }).render('#paypal-button-container');
</script>
<script src="js/script.js"></script>

</body>
</html>