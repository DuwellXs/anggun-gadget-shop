<?php

@include 'config.php';

session_start();

$admin_id = $_SESSION['admin_id'];

if (!isset($admin_id)) {
    header('location:login.php');
    exit;
}

// ENSURE UPLOAD FOLDER EXISTS
$target_dir = "uploaded_img/";
if (!is_dir($target_dir)) {
    mkdir($target_dir, 0777, true);
}

if (isset($_POST['update_product'])) {
    $pid = $_POST['pid'];
    
    // 1. STANDARD FIELDS
    $name = filter_var($_POST['name'], FILTER_SANITIZE_STRING);
    $price = filter_var($_POST['price'], FILTER_SANITIZE_STRING);
    $category = filter_var($_POST['category'], FILTER_SANITIZE_STRING);
    $brand = filter_var($_POST['brand'], FILTER_SANITIZE_STRING);
    $details = filter_var($_POST['details'], FILTER_SANITIZE_STRING);

    // 2. STOCK HANDLING
    $current_qty = $_POST['quantity']; 
    $restock_qty = (int)$_POST['restock_qty'];
    $final_qty = $current_qty + $restock_qty;

    // 3. DISCOUNT LOGIC
    $discount_percentage = ($category === 'SALES') ? $_POST['discount_percentage'] : 0;
    $discounted_price = ($category === 'SALES' && $discount_percentage > 0) ? 
        $price - ($price * ($discount_percentage / 100)) : $price;

    // 4. MAIN IMAGE UPDATE
    $image_name = $_FILES['image']['name'];
    $image_tmp = $_FILES['image']['tmp_name'];
    $image_size = $_FILES['image']['size'];
    $old_image = $_POST['old_image'];

    if (!empty($image_name)) {
        if ($image_size > 5000000) { // Increased limit to 5MB
            $message[] = 'Image size is too large!';
        } else {
            $new_filename = time() . '_' . $image_name; // Unique name to prevent conflicts
            $target_file = $target_dir . $new_filename;
            
            // Only update DB if upload succeeds
            if (move_uploaded_file($image_tmp, $target_file)) {
                $update_image = $conn->prepare("UPDATE `products` SET image = ? WHERE id = ?");
                $update_image->execute([$new_filename, $pid]);
                
                // Delete old image if it exists and isn't empty
                if (!empty($old_image) && file_exists($target_dir . $old_image)) {
                    unlink($target_dir . $old_image);
                }
                $message[] = 'Product image updated successfully!';
            } else {
                $message[] = 'Failed to upload image file (Check folder permissions).';
            }
        }
    }

    // 5. VARIANTS UPDATE
    $variant_string = "";
    if (isset($_POST['v_label'])) {
        $variant_data_array = [];
        $v_labels = $_POST['v_label'];
        $v_options = $_POST['v_option'];
        $v_prices = $_POST['v_price'];
        $v_old_images = $_POST['v_old_image'] ?? [];

        for ($i = 0; $i < count($v_labels); $i++) {
            $type = filter_var($v_labels[$i], FILTER_SANITIZE_STRING);
            $v_opt_name = filter_var($v_options[$i], FILTER_SANITIZE_STRING);
            $v_specific_price = filter_var($v_prices[$i], FILTER_SANITIZE_STRING);
            
            // Default to old image
            $v_img_final = $v_old_images[$i] ?? ''; 
            
            // Check if NEW image uploaded for this variant
            if (!empty($_FILES['v_image']['name'][$i])) {
                $v_file_name = $_FILES['v_image']['name'][$i];
                $v_file_tmp = $_FILES['v_image']['tmp_name'][$i];
                
                $unique_v_name = 'var_' . time() . '_' . $i . '_' . $v_file_name;
                $v_target = $target_dir . $unique_v_name;

                if (move_uploaded_file($v_file_tmp, $v_target)) {
                    $v_img_final = $unique_v_name;
                }
            }

            if (!empty($type) && !empty($v_opt_name)) {
                $variant_data_array[] = $type . '__' . $v_opt_name . '__' . $v_specific_price . '__' . $v_img_final;
            }
        }
        $variant_string = implode('||', $variant_data_array);
    }

    // EXECUTE MAIN UPDATE
    $update_query = $conn->prepare("UPDATE `products` SET name=?, category=?, brand=?, details=?, price=?, quantity=?, discount_percentage=?, discounted_price=?, variants=? WHERE id=?");
    $update_query->execute([$name, $category, $brand, $details, $price, $final_qty, $discount_percentage, $discounted_price, $variant_string, $pid]);

    $message[] = 'Product details updated!';
}

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Update Product | Admin Panel</title>

    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800;900&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

    <style>
        /* === NUCLEAR DESIGN SYSTEM === */
        html, body {
            margin: 0; padding: 0;
            height: 100vh; 
            overflow: hidden !important;
            font-family: 'Inter', sans-serif;
            background-color: #ffffff !important;
        }
        .master-scroll-wrapper {
            height: 100vh; overflow-y: auto; scroll-behavior: smooth; padding-top: 60px;
            scrollbar-width: none; background-color: #ffffff !important;
        }
        .master-scroll-wrapper::-webkit-scrollbar { display: none; }
        .content-container {
            max-width: 1400px; margin: 0 auto; padding: 40px 30px 150px;
            display: grid; grid-template-columns: 280px 1fr; gap: 60px;
            align-items: start; min-height: 100vh;
        }
        .right-content { width: 100%; display: flex; flex-direction: column; gap: 40px; }
        .stack-card {
            background: #fff; border-radius: 24px; border: 1px solid #f1f5f9;
            box-shadow: 0 10px 40px -10px rgba(0,0,0,0.05); padding: 40px; 
            animation: popIn 0.5s ease forwards;
        }
        @keyframes popIn { from { opacity: 0; transform: translateY(20px); } to { opacity: 1; transform: translateY(0); } }
        .ag-label { font-size: 0.7rem; font-weight: 800; color: #94a3b8; text-transform: uppercase; letter-spacing: 1px; margin-bottom: 8px; display: block; }
        .ag-input, .ag-select { width: 100%; background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 12px; padding: 14px 16px; font-size: 0.9rem; font-weight: 600; color: #0f172a; transition: 0.2s; outline: none; }
        .ag-input:focus, .ag-select:focus { background: #fff; border-color: #000; }
        .current-img-box { width: 120px; height: 120px; border-radius: 16px; overflow: hidden; border: 1px solid #eee; margin-bottom: 20px; position: relative; }
        .current-img-box img { width: 100%; height: 100%; object-fit: cover; }
        .edit-overlay { position: absolute; inset: 0; background: rgba(0,0,0,0.5); display: flex; align-items: center; justify-content: center; opacity: 0; transition: 0.2s; pointer-events: none; }
        .current-img-box:hover .edit-overlay { opacity: 1; }
        .btn-black { background: #000; color: #fff; padding: 14px 30px; border-radius: 12px; font-weight: 700; text-transform: uppercase; letter-spacing: 1px; font-size: 0.8rem; cursor: pointer; border: none; transition: 0.2s; }
        .btn-black:hover { background: #333; transform: translateY(-2px); }
        .btn-cancel { display: inline-block; padding: 14px 30px; border-radius: 12px; font-weight: 700; text-transform: uppercase; letter-spacing: 1px; font-size: 0.8rem; color: #64748b; border: 1px solid transparent; transition: 0.2s; }
        .btn-cancel:hover { color: #000; border-color: #e2e8f0; }
        .variant-box { background: #f8fafc; border: 1px dashed #cbd5e1; border-radius: 16px; padding: 25px; margin-top: 30px; }
        .variant-item { display: flex; items-center; justify-content: space-between; background: #fff; border: 1px solid #f1f5f9; padding: 12px; border-radius: 12px; margin-bottom: 10px; }
        .pill-btn { background: #fff; border: 1px solid #e2e8f0; padding: 8px 16px; border-radius: 50px; font-size: 0.75rem; font-weight: 700; cursor: pointer; transition: 0.2s; display: inline-flex; align-items: center; gap: 6px; }
        .pill-btn:hover { border-color: #000; color: #000; }
    </style>
</head>
<body>

<div class="master-scroll-wrapper">
   <div class="content-container">
      <?php include 'admin_header.php'; ?>
      <div class="right-content">
         <div class="stack-card">
            <div class="flex justify-between items-center mb-8 border-b border-gray-100 pb-6">
                <h1 class="text-2xl font-black text-slate-900 uppercase">Edit Product</h1>
                <a href="admin_products.php" class="text-xs font-bold text-gray-400 uppercase tracking-widest hover:text-black">&larr; Back to List</a>
            </div>

            <?php
               $update_id = $_GET['update'];
               $select_products = $conn->prepare("SELECT * FROM `products` WHERE id = ?");
               $select_products->execute([$update_id]);
               if($select_products->rowCount() > 0){
                  while($fetch_products = $select_products->fetch(PDO::FETCH_ASSOC)){ 
            ?>

            <form action="" method="post" enctype="multipart/form-data" autocomplete="off">
               <input type="hidden" name="old_image" value="<?= $fetch_products['image']; ?>">
               <input type="hidden" name="pid" value="<?= $fetch_products['id']; ?>">
               
               <div class="grid grid-cols-1 lg:grid-cols-12 gap-10">
                   <div class="lg:col-span-3">
                       <label class="ag-label">Product Image</label>
                       <div class="current-img-box group relative">
                           <img id="mainImgPreview" src="<?= !empty($fetch_products['image']) ? 'uploaded_img/'.$fetch_products['image'] : 'images/no-image.png' ?>" alt="">
                           <div class="edit-overlay"><i class="fas fa-camera text-white text-xl"></i></div>
                           <input type="file" name="image" accept="image/*" class="absolute inset-0 w-full h-full opacity-0 cursor-pointer z-10" onchange="previewMainImage(this)">
                       </div>
                       <p class="text-[10px] text-gray-400 font-bold uppercase tracking-wide text-center">Click Image to Change</p>
                   </div>

                   <div class="lg:col-span-9 space-y-6">
                       <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                           <div>
                               <label class="ag-label">Product Name</label>
                               <input type="text" name="name" class="ag-input" required value="<?= $fetch_products['name']; ?>">
                           </div>
                           <div>
                               <label class="ag-label">Category</label>
                               <select name="category" class="ag-select" required id="categorySelect">
                                    <option value="<?= $fetch_products['category']; ?>" selected><?= $fetch_products['category']; ?> (Current)</option>
                                    <option value="SALES">SALES</option>
                                    <option value="IPHONE">IPHONE</option>
                                    <option value="ANDROID">ANDROID</option>
                                    <option value="AUDIO">AUDIO</option>  
                                    <option value="POWER">POWER</option>  
                                    <option value="ACCESSORIES">ACCESSORIES</option>
                               </select>
                           </div>
                       </div>

                       <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                           <div>
                               <label class="ag-label">Brand</label>
                               <select name="brand" class="ag-select" required>
                                    <option value="<?= $fetch_products['brand']; ?>" selected><?= $fetch_products['brand']; ?> (Current)</option>
                                    <option value="Apple">Apple</option>
                                    <option value="Samsung">Samsung</option>
                                    <option value="Xiaomi">Xiaomi</option>
                                    <option value="Sony">Sony</option>
                                    <option value="JBL">JBL</option>
                                    <option value="Anker">Anker</option>
                                    <option value="Generic">Generic</option>
                               </select>
                           </div>
                           <div>
                               <label class="ag-label">Price (RM)</label>
                               <input type="number" min="0" name="price" class="ag-input" required value="<?= $fetch_products['price']; ?>">
                           </div>
                           <div class="bg-blue-50 p-3 rounded-xl border border-blue-100">
                               <label class="ag-label text-blue-600">Restock (+)</label>
                               <div class="flex gap-2">
                                   <input type="number" name="quantity" class="hidden" value="<?= $fetch_products['quantity']; ?>"> 
                                   <div class="text-sm font-bold flex items-center px-2 text-gray-500">Now: <?= $fetch_products['quantity']; ?></div>
                                   <input type="number" name="restock_qty" class="ag-input text-xs" placeholder="+ Add Qty">
                               </div>
                           </div>
                       </div>

                       <div class="mb-6" id="discountFields" style="display: <?= $fetch_products['category'] == 'SALES' ? 'block' : 'none'; ?>;">
                            <label class="ag-label text-red-500">Discount Percentage (%)</label>
                            <input type="number" min="0" max="100" name="discount_percentage" value="<?= $fetch_products['discount_percentage']; ?>" class="ag-input border-red-200 bg-red-50">
                       </div>

                       <div>
                           <label class="ag-label">Description</label>
                           <textarea name="details" class="ag-input" rows="4" required><?= $fetch_products['details']; ?></textarea>
                       </div>

                       <div class="variant-box">
                           <div class="flex justify-between items-center mb-4">
                               <h3 class="font-bold text-sm uppercase tracking-wider text-gray-700">Variant Manager</h3>
                               <div class="space-x-2">
                                   <button type="button" class="pill-btn" onclick="addVariantRow('Color')">+ Color</button>
                                   <button type="button" class="pill-btn" onclick="addVariantRow('Storage')">+ Storage</button>
                               </div>
                           </div>

                           <div id="variantContainer" class="space-y-3">
                               <?php 
                               if (!empty($fetch_products['variants'])) {
                                   $variants_arr = explode('||', $fetch_products['variants']);
                                   foreach ($variants_arr as $var_str) {
                                       $v_parts = explode('__', $var_str);
                                       if(count($v_parts) >= 2) {
                                           $v_label = $v_parts[0];
                                           $v_opt = $v_parts[1];
                                           $v_pr = isset($v_parts[2]) ? $v_parts[2] : '';
                                           $v_img = isset($v_parts[3]) ? $v_parts[3] : '';
                                           $uid = uniqid();
                                           $has_img = !empty($v_img) && file_exists('uploaded_img/'.$v_img);
                               ?>
                                   <div class="variant-item relative" id="var-<?= $uid ?>">
                                       <div class="flex items-center gap-3">
                                           <div class="w-8 h-8 bg-gray-50 rounded-lg flex items-center justify-center text-gray-400 text-xs font-bold"><?= substr($v_label,0,1) ?></div>
                                           <div>
                                               <input type="text" name="v_label[]" value="<?= $v_label ?>" class="text-xs font-bold text-gray-400 uppercase w-16 bg-transparent border-none p-0 focus:ring-0" readonly>
                                               <input type="text" name="v_option[]" value="<?= $v_opt ?>" class="font-bold text-sm text-gray-800 bg-transparent border-b border-dashed border-gray-300 w-full focus:outline-none">
                                           </div>
                                       </div>
                                       <div class="flex items-center gap-2">
                                            <div class="relative w-8 h-8 flex-shrink-0 bg-gray-50 rounded border border-gray-200 flex items-center justify-center group">
                                                <i class="fas fa-camera text-gray-400 text-[10px]"></i>
                                                <img src="<?= $has_img ? 'uploaded_img/'.$v_img : '' ?>" class="v_img_preview absolute inset-0 w-full h-full object-cover rounded <?= $has_img ? '' : 'hidden' ?>">
                                                
                                                <input type="file" name="v_image[]" accept="image/*" class="absolute inset-0 w-full h-full opacity-0 cursor-pointer z-10" onchange="previewVariantImage(this)">
                                                <input type="hidden" name="v_old_image[]" value="<?= $v_img ?>" class="v_old_img_input">

                                                <button type="button" class="v_img_del_btn absolute -top-2 -right-2 bg-red-500 text-white rounded-full w-4 h-4 flex items-center justify-center text-[8px] shadow-sm z-20 <?= $has_img ? '' : 'hidden' ?>" onclick="removeVariantImage(this)">
                                                    <i class="fas fa-times"></i>
                                                </button>
                                            </div>

                                            <input type="number" name="v_price[]" value="<?= $v_pr ?>" placeholder="+RM" class="ag-input py-1 px-2 w-20 text-xs">
                                            <i class="fas fa-trash text-gray-300 hover:text-red-500 cursor-pointer p-2" onclick="document.getElementById('var-<?= $uid ?>').remove()"></i>
                                       </div>
                                   </div>
                               <?php 
                                       }
                                   }
                               }
                               ?>
                           </div>
                       </div>

                       <div class="flex gap-4 pt-6 border-t border-gray-100">
                           <a href="admin_products.php" class="btn-cancel w-full text-center">Cancel</a>
                           <input type="submit" value="Save Changes" name="update_product" class="btn-black w-full">
                       </div>
                   </div>
               </div>
            </form>
            <?php } } else { echo '<p class="empty">no product found!</p>'; } ?>
         </div>
      </div>
   </div>
</div>

<script>
    document.getElementById('categorySelect').addEventListener('change', function() {
        document.getElementById('discountFields').style.display = (this.value === 'SALES') ? 'block' : 'none';
    });

    // 1. MAIN IMAGE PREVIEW
    function previewMainImage(input) {
        if (input.files && input.files[0]) {
            const reader = new FileReader();
            reader.onload = function(e) { document.getElementById('mainImgPreview').src = e.target.result; }
            reader.readAsDataURL(input.files[0]);
        }
    }

    // 2. VARIANT IMAGE PREVIEW
    function previewVariantImage(input) {
        const wrapper = input.closest('.relative');
        const preview = wrapper.querySelector('.v_img_preview');
        const delBtn = wrapper.querySelector('.v_img_del_btn');
        if (input.files && input.files[0]) {
            const reader = new FileReader();
            reader.onload = function(e) {
                preview.src = e.target.result;
                preview.classList.remove('hidden'); 
                delBtn.classList.remove('hidden');
            }
            reader.readAsDataURL(input.files[0]);
        }
    }

    // 3. REMOVE VARIANT IMAGE
    function removeVariantImage(btn) {
        const wrapper = btn.closest('.relative');
        const oldInput = wrapper.querySelector('.v_old_img_input');
        const fileInput = wrapper.querySelector('input[type="file"]');
        const preview = wrapper.querySelector('.v_img_preview');
        
        oldInput.value = '';  // Clear old DB value
        fileInput.value = ''; // Clear file input
        preview.src = '';     
        
        preview.classList.add('hidden');
        btn.classList.add('hidden');
    }

    function addVariantRow(type) {
        const container = document.getElementById('variantContainer');
        const uid = Date.now();
        const html = `
            <div class="variant-item relative" id="var-${uid}">
               <div class="flex items-center gap-3">
                   <div class="w-8 h-8 bg-blue-50 rounded-lg flex items-center justify-center text-blue-500 text-xs font-bold">+</div>
                   <div>
                       <input type="text" name="v_label[]" value="${type}" class="text-xs font-bold text-blue-500 uppercase w-16 bg-transparent border-none p-0 focus:ring-0" readonly>
                       <input type="text" name="v_option[]" placeholder="Value" class="font-bold text-sm text-gray-800 bg-transparent border-b border-dashed border-gray-300 w-full focus:outline-none" required>
                   </div>
               </div>
               <div class="flex items-center gap-2">
                    <div class="relative w-8 h-8 flex-shrink-0 bg-gray-50 rounded border border-gray-200 flex items-center justify-center group">
                        <i class="fas fa-camera text-gray-400 text-[10px]"></i>
                        <img src="" class="v_img_preview absolute inset-0 w-full h-full object-cover rounded hidden">
                        <input type="file" name="v_image[]" accept="image/*" class="absolute inset-0 w-full h-full opacity-0 cursor-pointer z-10" onchange="previewVariantImage(this)">
                        <input type="hidden" name="v_old_image[]" value="" class="v_old_img_input">
                        <button type="button" class="v_img_del_btn absolute -top-2 -right-2 bg-red-500 text-white rounded-full w-4 h-4 flex items-center justify-center text-[8px] shadow-sm z-20 hidden" onclick="removeVariantImage(this)"><i class="fas fa-times"></i></button>
                    </div>
                    <input type="number" name="v_price[]" placeholder="+RM" class="ag-input py-1 px-2 w-20 text-xs">
                    <i class="fas fa-trash text-gray-300 hover:text-red-500 cursor-pointer p-2" onclick="document.getElementById('var-${uid}').remove()"></i>
               </div>
           </div>`;
        container.insertAdjacentHTML('beforeend', html);
    }
</script>
<script src="js/script.js"></script>
</body>
</html>