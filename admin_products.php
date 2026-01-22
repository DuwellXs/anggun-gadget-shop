<?php

@include 'config.php';

session_start();

$admin_id = $_SESSION['admin_id'];

if (!isset($admin_id)) {
    header('location:login.php');
    exit;
};

// Ensure upload folder exists
if (!is_dir('uploaded_img')) { mkdir('uploaded_img', 0777, true); }

// --- FORM HANDLER ---
if (isset($_POST['add_product'])) {

    $name = filter_var($_POST['name'], FILTER_SANITIZE_STRING);
    $price = filter_var($_POST['price'], FILTER_SANITIZE_STRING);
    $category = filter_var($_POST['category'], FILTER_SANITIZE_STRING);
    $brand = filter_var($_POST['brand'], FILTER_SANITIZE_STRING); 
    $details = filter_var($_POST['details'], FILTER_SANITIZE_STRING);
    $quantity = filter_var($_POST['quantity'], FILTER_SANITIZE_NUMBER_INT);

    // Discount Logic
    $discount_percentage = ($category === 'SALES') ? $_POST['discount_percentage'] : 0;
    $discount_percentage = filter_var($discount_percentage, FILTER_SANITIZE_NUMBER_INT);
    $discounted_price = ($category === 'SALES' && $discount_percentage > 0) ? 
        $price - ($price * ($discount_percentage / 100)) : $price;

    // --- IMAGE 1 (MAIN - REQUIRED) ---
    $image = $_FILES['image']['name'];
    $image = filter_var($image, FILTER_SANITIZE_STRING);
    $image_size = $_FILES['image']['size'];
    $image_tmp_name = $_FILES['image']['tmp_name'];
    $image_folder = 'uploaded_img/' . $image;

    // --- IMAGE 2 (OPTIONAL) ---
    $image2 = $_FILES['image2']['name'] ?? '';
    $image2 = filter_var($image2, FILTER_SANITIZE_STRING);
    $image2_tmp_name = $_FILES['image2']['tmp_name'] ?? '';
    $image2_folder = 'uploaded_img/' . $image2;

    // --- IMAGE 3 (OPTIONAL) ---
    $image3 = $_FILES['image3']['name'] ?? '';
    $image3 = filter_var($image3, FILTER_SANITIZE_STRING);
    $image3_tmp_name = $_FILES['image3']['tmp_name'] ?? '';
    $image3_folder = 'uploaded_img/' . $image3;

    // Variants Logic (Preserved)
    $variant_string = "";
    if (isset($_POST['has_variants']) && $_POST['has_variants'] === 'yes') {
        $variant_data_array = [];
        if (isset($_POST['v_label']) && is_array($_POST['v_label'])) {
            $v_labels = $_POST['v_label'];
            $v_options = $_POST['v_option'];
            $v_prices = $_POST['v_price'];
            
            for ($i = 0; $i < count($v_labels); $i++) {
                $type = filter_var($v_labels[$i], FILTER_SANITIZE_STRING);
                $v_opt_name = filter_var($v_options[$i], FILTER_SANITIZE_STRING); 
                $v_specific_price = filter_var($v_prices[$i], FILTER_SANITIZE_STRING);
                
                $v_image_name = '';
                if (!empty($_FILES['v_image']['name'][$i])) {
                    $file_name = $_FILES['v_image']['name'][$i];
                    $file_tmp = $_FILES['v_image']['tmp_name'][$i];
                    $v_image_name = 'var_' . time() . '_' . $i . '_' . $file_name; 
                    move_uploaded_file($file_tmp, 'uploaded_img/' . $v_image_name);
                }

                if (!empty($type) && !empty($v_opt_name)) {
                    $variant_data_array[] = $type . '__' . $v_opt_name . '__' . $v_specific_price . '__' . $v_image_name;
                }
            }
        }
        $variant_string = implode('||', $variant_data_array);
    }

    $select_products = $conn->prepare("SELECT * FROM `products` WHERE name = ?");
    $select_products->execute([$name]);

    if ($select_products->rowCount() > 0) {
        $message[] = 'Product name already exists!';
    } else {
        // Updated INSERT to include image2 and image3
        $insert_products = $conn->prepare("INSERT INTO `products`(name, category, brand, details, price, quantity, image, image2, image3, discount_percentage, discounted_price, variants) VALUES(?,?,?,?,?,?,?,?,?,?,?,?)");
        $result = $insert_products->execute([$name, $category, $brand, $details, $price, $quantity, $image, $image2, $image3, $discount_percentage, $discounted_price, $variant_string]);

        if ($result) {
            if ($image_size > 2000000) {
                $message[] = 'Main image size is too large!';
            } else {
                move_uploaded_file($image_tmp_name, $image_folder);
                if(!empty($image2)) move_uploaded_file($image2_tmp_name, $image2_folder);
                if(!empty($image3)) move_uploaded_file($image3_tmp_name, $image3_folder);
                $message[] = 'New product added successfully!';
            }
        } else {
            $message[] = 'Failed to add product!';
        }
    }
}

if (isset($_GET['delete'])) {
    $delete_id = $_GET['delete'];
    // Select all images to delete them
    $select_delete_image = $conn->prepare("SELECT image, image2, image3 FROM `products` WHERE id = ?");
    $select_delete_image->execute([$delete_id]);
    $fetch_delete_image = $select_delete_image->fetch(PDO::FETCH_ASSOC);
    
    if($fetch_delete_image){
        if(!empty($fetch_delete_image['image']) && file_exists('uploaded_img/' . $fetch_delete_image['image'])) {
            unlink('uploaded_img/' . $fetch_delete_image['image']);
        }
        if(!empty($fetch_delete_image['image2']) && file_exists('uploaded_img/' . $fetch_delete_image['image2'])) {
            unlink('uploaded_img/' . $fetch_delete_image['image2']);
        }
        if(!empty($fetch_delete_image['image3']) && file_exists('uploaded_img/' . $fetch_delete_image['image3'])) {
            unlink('uploaded_img/' . $fetch_delete_image['image3']);
        }
    }
    $delete_products = $conn->prepare("DELETE FROM `products` WHERE id = ?");
    $delete_products->execute([$delete_id]);
    $delete_wishlist = $conn->prepare("DELETE FROM `wishlist` WHERE pid = ?");
    $delete_wishlist->execute([$delete_id]);
    $delete_cart = $conn->prepare("DELETE FROM `cart` WHERE pid = ?");
    $delete_cart->execute([$delete_id]);
    header('location:admin_products.php');
}

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Inventory | Admin Panel</title>

    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800;900&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

    <style>
        /* === NUCLEAR DESIGN SYSTEM (MATCHING REFERENCE) === */
        html, body {
            margin: 0; padding: 0;
            height: 100vh; 
            overflow: hidden !important;
            font-family: 'Inter', sans-serif;
            background-color: #ffffff !important;
            color: #000;
        }

        /* --- SPLIT PANE SCROLL LAYOUT --- */
        .master-scroll-wrapper {
            height: 100vh;
            width: 100%;
            display: flex;
            justify-content: center;
            background-color: #fff;
        }

        .content-container {
            width: 100%;
            max-width: 1400px;
            height: 100%;
            display: grid;
            grid-template-columns: 280px minmax(0, 1fr); 
            gap: 60px;
            padding: 0 30px;
        }

        .sidebar-scroll {
            height: 100%;
            overflow-y: auto;
            padding-top: 40px;
            padding-bottom: 40px;
            scrollbar-width: none; 
            -ms-overflow-style: none;
        }
        .sidebar-scroll::-webkit-scrollbar { display: none; }

        .content-scroll {
            height: 100%;
            overflow-y: auto;
            padding-top: 40px;
            padding-bottom: 100px;
            padding-right: 5px; 
            scrollbar-width: none; 
            -ms-overflow-style: none;
        }
        .content-scroll::-webkit-scrollbar { display: none; }

        /* --- CARD & LAYOUT STYLES --- */
        .card {
            background: #fff; 
            border-radius: 12px; 
            padding: 30px;
            border: 1px solid #f3f4f6; 
            box-shadow: 0 10px 30px -10px rgba(0,0,0,0.05); 
            margin-bottom: 40px;
            animation: fadeIn 0.3s ease-out;
        }
        @keyframes fadeIn { from { opacity:0; transform:translateY(10px); } to { opacity:1; transform:translateY(0); } }

        .section-header {
            display: flex; align-items: center; justify-content: space-between;
            margin-bottom: 25px; border-bottom: 1px solid #f3f4f6; padding-bottom: 15px;
        }
        .section-title { font-size: 1.1rem; font-weight: 800; color: #111; letter-spacing: -0.5px; }

        /* --- FORM ELEMENTS --- */
        .ag-label {
            font-size: 0.65rem; font-weight: 800; color: #9ca3af; 
            text-transform: uppercase; letter-spacing: 1px; margin-bottom: 8px; display: block;
        }
        .ag-input, .ag-select {
            width: 100%; background: #f9fafb; border: 1px solid #e5e7eb;
            border-radius: 10px; padding: 12px 16px; font-size: 0.9rem; font-weight: 600; color: #111;
            transition: 0.2s; outline: none;
        }
        .ag-input:focus, .ag-select:focus { background: #fff; border-color: #000; box-shadow: 0 0 0 4px rgba(0,0,0,0.05); }

        .btn-black {
            background: #000; color: #fff; padding: 12px 24px; border-radius: 10px;
            font-weight: 800; text-transform: uppercase; letter-spacing: 1px; font-size: 0.75rem;
            cursor: pointer; border: none; transition: 0.2s; display: inline-flex; align-items: center; gap: 8px;
        }
        .btn-black:hover { transform: translateY(-1px); box-shadow: 0 5px 15px rgba(0,0,0,0.2); }

        /* --- VARIANT UI --- */
        .variant-box {
            background: #f8fafc; border: 1px dashed #cbd5e1; border-radius: 12px; padding: 20px;
            margin-top: 20px;
        }
        .toggle-row { display: flex; align-items: center; gap: 10px; cursor: pointer; user-select: none; }
        .toggle-switch { width: 44px; height: 24px; background: #e2e8f0; border-radius: 20px; position: relative; transition: 0.3s; }
        .toggle-switch::after { content:''; position: absolute; left: 2px; top: 2px; width: 20px; height: 20px; background: #fff; border-radius: 50%; transition: 0.3s; box-shadow: 0 2px 5px rgba(0,0,0,0.1); }
        input:checked + .toggle-switch { background: #000; }
        input:checked + .toggle-switch::after { transform: translateX(20px); }

        .preset-area { display: none; margin-top: 20px; border-top: 1px solid #e2e8f0; padding-top: 20px; }
        .preset-area.active { display: block; }
        
        .pill-btn { background: #fff; border: 1px solid #e2e8f0; padding: 8px 14px; border-radius: 50px; font-size: 0.7rem; font-weight: 700; cursor: pointer; transition: 0.2s; display: inline-flex; align-items: center; gap: 6px; }
        .pill-btn:hover { border-color: #000; color: #000; }

        /* --- DATA TABLE --- */
        .data-table { width: 100%; border-collapse: separate; border-spacing: 0; }
        .data-table th { 
            text-align: left; color: #9ca3af; font-size: 0.65rem; font-weight: 800; 
            text-transform: uppercase; padding: 15px 20px; border-bottom: 2px solid #f3f4f6; 
            letter-spacing: 1px;
        }
        .data-table td { padding: 20px; border-bottom: 1px solid #f9fafb; vertical-align: middle; }
        .data-table tr:hover td { background: #fafafa; }
        
        .thumb-img { width: 40px; height: 40px; border-radius: 8px; object-fit: cover; border: 1px solid #eee; }
        .badge { display: inline-block; padding: 4px 10px; border-radius: 6px; font-size: 0.65rem; font-weight: 800; text-transform: uppercase; }
        .badge-brand { background: #f3f4f6; color: #374151; }
        
        .action-icon { width: 32px; height: 32px; display: inline-flex; align-items: center; justify-content: center; border-radius: 8px; color: #64748b; transition: 0.2s; }
        .action-icon:hover { background: #f1f5f9; color: #000; }
        .action-delete:hover { background: #fef2f2; color: #ef4444; }

        .gen-item-card { display: flex; align-items: center; justify-content: space-between; background: #fff; border: 1px solid #eee; padding: 12px; margin-bottom: 8px; border-radius: 12px; }
        .gen-icon { width: 32px; height: 32px; background: #f1f5f9; border-radius: 8px; display: flex; align-items: center; justify-content: center; font-size: 0.8rem; color: #64748b; }

        @media (max-width: 1024px) {
            .content-container { grid-template-columns: 1fr; }
            .sidebar-scroll { display: none; }
        }
    </style>
</head>
<body>

<div class="master-scroll-wrapper">
    <div class="content-container">
        
        <div class="sidebar-scroll">
            <?php include 'admin_header.php'; ?>
        </div>

        <div class="content-scroll">
            
            <div class="mb-12">
                <h1 class="text-4xl font-black text-black uppercase tracking-tighter">Inventory</h1>
                <div class="flex justify-between items-end mt-2">
                    <p class="text-sm font-bold text-gray-400 tracking-wide uppercase">
                        Manage Products & Variants
                    </p>
                    <div class="flex items-center gap-2 bg-green-50 px-3 py-1.5 rounded-lg border border-green-100">
                        <span class="relative flex h-2 w-2">
                          <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-green-400 opacity-75"></span>
                          <span class="relative inline-flex rounded-full h-2 w-2 bg-green-500"></span>
                        </span>
                        <span class="text-[10px] font-black text-green-600 uppercase tracking-wider">Live</span>
                    </div>
                </div>
            </div>

            <div class="card">
                <div class="section-header">
                    <span class="section-title">Add New Product</span>
                    <i class="fas fa-plus-circle text-gray-300"></i>
                </div>

                <form action="" method="POST" enctype="multipart/form-data" autocomplete="off">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
                        <div>
                            <label class="ag-label">Product Name</label>
                            <input type="text" name="name" class="ag-input" required placeholder="e.g. iPhone 15 Pro">
                        </div>
                        <div>
                            <label class="ag-label">Category</label>
                            <select name="category" class="ag-select" required id="categorySelect">
                                <option value="" selected disabled>Select Category</option>
                                <option value="SALES">SALES</option>
                                <option value="IPHONE">IPHONE</option>
                                <option value="ANDROID">ANDROID</option>
                                <option value="AUDIO">AUDIO</option>  
                                <option value="POWER">POWER</option>  
                                <option value="ACCESSORIES">ACCESSORIES</option>
                            </select>
                        </div>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-6">
                        <div>
                            <label class="ag-label">Brand</label>
                            <select name="brand" class="ag-select" required>
                                <option value="" selected disabled>Select Brand</option>
                                <option value="Apple">Apple</option>
                                <option value="Samsung">Samsung</option>
                                <option value="Xiaomi">Xiaomi</option>
                                <option value="Sony">Sony</option>
                                <option value="JBL">JBL</option>
                                <option value="Anker">Anker</option>
                                <option value="Baseus">Baseus</option>
                                <option value="Realme">Realme</option>
                                <option value="Spigen">Spigen</option>
                                <option value="Rhinoshield">Rhinoshield</option>
                                <option value="Generic">Generic</option>
                            </select>
                        </div>
                        <div>
                            <label class="ag-label">Price (RM)</label>
                            <input type="number" min="0" name="price" class="ag-input" required placeholder="0.00">
                        </div>
                        <div>
                            <label class="ag-label">Stock Qty</label>
                            <input type="number" min="0" name="quantity" class="ag-input" required placeholder="0">
                        </div>
                    </div>

                    <div class="mb-6">
                        <label class="ag-label">Product Gallery (Max 3)</label>
                        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                            <div class="bg-gray-50 border border-dashed border-gray-300 rounded-lg p-4 text-center hover:bg-white transition-colors">
                                <div class="text-[10px] font-bold text-gray-400 mb-2 uppercase">Main Image (Required)</div>
                                <input type="file" name="image" class="ag-input text-xs" required accept="image/jpg, image/jpeg, image/png">
                            </div>
                            <div class="bg-gray-50 border border-dashed border-gray-300 rounded-lg p-4 text-center hover:bg-white transition-colors">
                                <div class="text-[10px] font-bold text-gray-400 mb-2 uppercase">Image 2 (Optional)</div>
                                <input type="file" name="image2" class="ag-input text-xs" accept="image/jpg, image/jpeg, image/png">
                            </div>
                            <div class="bg-gray-50 border border-dashed border-gray-300 rounded-lg p-4 text-center hover:bg-white transition-colors">
                                <div class="text-[10px] font-bold text-gray-400 mb-2 uppercase">Image 3 (Optional)</div>
                                <input type="file" name="image3" class="ag-input text-xs" accept="image/jpg, image/jpeg, image/png">
                            </div>
                        </div>
                    </div>

                    <div class="mb-6" id="discountFields" style="display:none;">
                        <label class="ag-label text-red-500">Discount Percentage (%)</label>
                        <input type="number" min="0" max="100" name="discount_percentage" class="ag-input border-red-200 bg-red-50" placeholder="e.g. 10">
                    </div>

                    <div class="mb-6">
                        <label class="ag-label">Description</label>
                        <textarea name="details" class="ag-input" rows="4" required placeholder="Product details..."></textarea>
                    </div>

                    <div class="variant-box">
                        <label class="toggle-row">
                            <input type="checkbox" name="has_variants" value="yes" class="hidden" id="variantToggle">
                            <div class="toggle-switch"></div>
                            <span class="text-sm font-bold text-gray-700">Enable Product Variants (Color, Storage, etc.)</span>
                        </label>

                        <div class="preset-area" id="presetArea">
                            <div class="flex flex-wrap gap-3 mb-4">
                                <button type="button" class="pill-btn" onclick="addAttribute('Color')"><i class="fas fa-palette"></i> Color</button>
                                <button type="button" class="pill-btn" onclick="addAttribute('Storage')"><i class="fas fa-hdd"></i> Storage</button>
                                <button type="button" class="pill-btn" onclick="addAttribute('Size')"><i class="fas fa-ruler"></i> Size</button>
                                <button type="button" class="pill-btn" onclick="addAttribute('Custom')"><i class="fas fa-plus"></i> Custom</button>
                            </div>

                            <div id="attributeInputs" class="space-y-2 mb-4"></div>

                            <div class="flex justify-between items-center pt-4 border-t border-dashed border-gray-300">
                                <button type="button" class="text-xs font-bold text-red-400 hover:text-red-600" onclick="clearAllVariants()">Reset All</button>
                                <button type="button" class="bg-blue-600 text-white px-4 py-2 rounded-lg text-xs font-bold hover:bg-blue-700" onclick="generatePreview()">Generate Variants</button>
                            </div>

                            <div class="generated-list mt-4" id="generatedList" style="display:none;">
                                <h4 class="ag-label">Active Variants</h4>
                                <div id="previewContainer"></div>
                            </div>
                        </div>
                    </div>

                    <div class="mt-8 text-right">
                        <button type="submit" class="btn-black" name="add_product">
                            <i class="fas fa-save"></i> Save Product
                        </button>
                    </div>
                </form>
            </div>

            <div class="card">
                <div class="section-header">
                    <span class="section-title">Inventory List</span>
                    <span class="text-xs font-bold bg-gray-100 px-3 py-1 rounded-full text-gray-500">Total: <?= $conn->query("SELECT count(*) FROM products")->fetchColumn(); ?></span>
                </div>

                <div class="overflow-x-auto">
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>Image</th>
                                <th>Product Details</th>
                                <th>Category</th>
                                <th>Price</th>
                                <th>Stock</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            $select_products = $conn->prepare("SELECT * FROM `products` ORDER BY id DESC");
                            $select_products->execute();
                            if($select_products->rowCount() > 0){
                                while($fetch_products = $select_products->fetch(PDO::FETCH_ASSOC)){
                            ?>
                            <tr>
                                <td><img src="uploaded_img/<?= $fetch_products['image']; ?>" class="thumb-img" alt=""></td>
                                <td>
                                    <div class="font-bold text-sm text-black"><?= $fetch_products['name']; ?></div>
                                    <span class="badge badge-brand mt-1"><?= $fetch_products['brand'] ?? 'Generic'; ?></span>
                                    <?php if(!empty($fetch_products['variants'])): ?>
                                        <span class="text-[10px] bg-black text-white px-2 py-0.5 rounded ml-1 font-bold">VARIANTS</span>
                                    <?php endif; ?>
                                </td>
                                <td><span class="text-xs font-bold text-gray-500"><?= $fetch_products['category']; ?></span></td>
                                <td class="font-black text-black">RM<?= $fetch_products['price']; ?></td>
                                <td><span class="font-bold text-gray-700"><?= $fetch_products['quantity']; ?></span></td>
                                <td>
                                    <div class="flex gap-2">
                                        <a href="admin_update_product.php?update=<?= $fetch_products['id']; ?>" class="action-icon" title="Edit"><i class="fas fa-pen"></i></a>
                                        <a href="admin_products.php?delete=<?= $fetch_products['id']; ?>" class="action-icon action-delete" onclick="return confirm('Delete this product?');" title="Delete"><i class="fas fa-trash"></i></a>
                                    </div>
                                </td>
                            </tr>
                            <?php
                                }
                            } else {
                                echo '<tr><td colspan="6" class="text-center py-10 text-gray-400 font-bold uppercase text-xs tracking-wide">No products found.</td></tr>';
                            }
                            ?>
                        </tbody>
                    </table>
                </div>
            </div>

        </div>
    </div>
</div>

<script src="js/script.js"></script>
<script>
    // JS Logic for Variants
    document.getElementById('categorySelect').addEventListener('change', function() {
        document.getElementById('discountFields').style.display = (this.value === 'SALES') ? 'block' : 'none';
    });

    const variantToggle = document.getElementById('variantToggle');
    const presetArea = document.getElementById('presetArea');
    
    variantToggle.addEventListener('click', function(e) {
        if(this.checked) presetArea.classList.add('active');
        else presetArea.classList.remove('active');
    });

    function addAttribute(type) {
        const container = document.getElementById('attributeInputs');
        const id = Date.now();
        let ph = "Options (comma separated)";
        if(type === 'Color') ph = "Red, Blue, Black";
        if(type === 'Storage') ph = "128GB, 256GB";
        
        const html = `
            <div class="flex gap-2 items-center attribute-input-row" id="row-${id}">
                <input type="text" class="ag-input w-24 bg-blue-50 text-blue-600 font-bold border-blue-100" value="${type}" readonly>
                <input type="text" class="ag-input flex-1" placeholder="${ph}">
                <i class="fas fa-times text-red-400 cursor-pointer hover:text-red-600 p-3" onclick="document.getElementById('row-${id}').remove()"></i>
            </div>
        `;
        container.insertAdjacentHTML('beforeend', html);
    }

    function generatePreview() {
        const container = document.getElementById('previewContainer');
        const listArea = document.getElementById('generatedList');
        const rows = document.querySelectorAll('.attribute-input-row');
        
        if(rows.length === 0) return;
        listArea.style.display = 'block';

        rows.forEach(row => {
            const label = row.querySelector('input:nth-child(1)').value;
            const opts = row.querySelector('input:nth-child(2)').value.split(',');
            
            opts.forEach(opt => {
                if(opt.trim() === '') return;
                const uid = Math.random().toString(36).substr(2, 5);
                const html = `
                    <div class="gen-item-card" id="card-${uid}">
                        <div class="flex items-center gap-3">
                            <div class="gen-icon"><i class="fas fa-tag"></i></div>
                            <div>
                                <div class="text-[10px] font-bold text-gray-400 uppercase">${label}</div>
                                <div class="font-bold text-sm">${opt.trim()}</div>
                                <input type="hidden" name="v_label[]" value="${label}">
                                <input type="hidden" name="v_option[]" value="${opt.trim()}">
                            </div>
                        </div>
                        <div class="flex items-center gap-2">
                            <div class="flex items-center">
                                <label class="cursor-pointer bg-gray-100 hover:bg-gray-200 px-3 py-1 rounded text-xs font-bold transition">
                                    + IMG <input type="file" name="v_image[]" class="hidden" onchange="previewVariantImage(this, 'img-${uid}')">
                                </label>
                                <img id="img-${uid}" class="hidden w-8 h-8 rounded border border-gray-200 object-cover ml-2">
                            </div>
                            <input type="number" name="v_price[]" placeholder="Price override" class="ag-input py-1 px-2 w-24 text-xs">
                            <i class="fas fa-trash text-gray-300 hover:text-red-500 cursor-pointer p-2" onclick="document.getElementById('card-${uid}').remove()"></i>
                        </div>
                    </div>
                `;
                container.insertAdjacentHTML('beforeend', html);
            });
            row.remove();
        });
    }

    // New Function to preview uploaded variant images
    function previewVariantImage(input, imgId) {
        if (input.files && input.files[0]) {
            const reader = new FileReader();
            reader.onload = function(e) {
                const img = document.getElementById(imgId);
                img.src = e.target.result;
                img.classList.remove('hidden');
            }
            reader.readAsDataURL(input.files[0]);
        }
    }

    function clearAllVariants() {
        document.getElementById('attributeInputs').innerHTML = '';
        document.getElementById('previewContainer').innerHTML = '';
        document.getElementById('generatedList').style.display = 'none';
    }
</script>

</body>
</html>