<?php
@include 'config.php';

session_start();

$user_id = $_SESSION['user_id'] ?? null;

if(!isset($user_id)){
   header('location:login.php');
   exit;
}

// 1. ADD TO CART
if(isset($_POST['add_to_cart'])){
    $pid = $_POST['pid'];
    $p_name = $_POST['name'];
    $p_price = $_POST['price'];
    $p_image = $_POST['image'];
    $p_qty = 1; 
    
    $check_cart = $conn->prepare("SELECT * FROM `cart` WHERE name = ? AND user_id = ?");
    $check_cart->execute([$p_name, $user_id]);

    if($check_cart->rowCount() > 0){
        $message[] = 'Item already in cart!';
    } else {
        $insert_cart = $conn->prepare("INSERT INTO `cart`(user_id, pid, name, price, quantity, image, selected_variants) VALUES(?,?,?,?,?,?,?)");
        $insert_cart->execute([$user_id, $pid, $p_name, $p_price, $p_qty, $p_image, '']);
        $message[] = 'Added to cart!';
    }
}

// 2. DELETE
if(isset($_GET['delete'])){
    $delete_id = $_GET['delete'];
    $delete_wishlist_item = $conn->prepare("DELETE FROM `wishlist` WHERE id = ?");
    $delete_wishlist_item->execute([$delete_id]);
    header('location:wishlist.php');
    exit;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Wishlist | Anggun Gadget</title>
    
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;600;700;800;900&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <style>
        /* === STANDARD LAYOUT (No Custom Scrollbars) === */
        body {
            font-family: 'Inter', sans-serif;
            background-color: #ffffff; /* Clean White */
            color: #111;
            margin: 0; padding: 0;
            overflow-x: hidden;
        }

        /* === LAYOUT GRID === */
        .page-container {
            max-width: 1200px;
            margin: 0 auto;
            /* INCREASED TOP PADDING (160px) TO PUSH CONTENT DOWN */
            padding: 160px 20px 100px; 
            min-height: 80vh;
            display: grid;
            grid-template-columns: 35% 60%;
            gap: 5%;
            align-items: start;
        }

        /* === LEFT SIDE (STICKY) === */
        .left-sticky-panel {
            position: sticky;
            top: 140px; /* Adjusted for new spacing */
            padding-right: 20px;
        }

        .hero-badge {
            background: #000; color: #fff; 
            font-size: 0.7rem; font-weight: 700; letter-spacing: 2px; text-transform: uppercase;
            padding: 6px 14px; border-radius: 50px; margin-bottom: 20px; display: inline-block;
        }

        .hero-title {
            font-size: 3.5rem; font-weight: 900; line-height: 1; color: #111;
            text-transform: uppercase; letter-spacing: -1px; margin-bottom: 15px;
        }
        
        .text-shadow-pop { text-shadow: 2px 2px 0px #cbd5e1; }

        .hero-desc {
            font-size: 0.9rem; line-height: 1.6; color: #666; font-weight: 600;
            border-left: 3px solid #ddd; padding-left: 20px; margin-bottom: 30px;
        }

        .item-counter {
            font-weight: 800; font-size: 0.8rem; text-transform: uppercase; letter-spacing: 1px;
            border-bottom: 2px solid #000; padding-bottom: 5px; display: inline-block;
        }

        /* === RIGHT SIDE (CARDS) === */
        .right-card-list {
            display: flex;
            flex-direction: column;
            gap: 25px; /* TIGHTENED SPACING (was 40px) */
        }

        /* === CARD DESIGN === */
        .wish-card {
            background: #fff;
            border-radius: 20px;
            border: 1px solid rgba(0,0,0,0.04);
            box-shadow: 0 15px 40px -10px rgba(0,0,0,0.06);
            display: flex;
            height: 260px; /* TIGHTER HEIGHT (was 300px) */
            overflow: hidden;
            transition: transform 0.3s ease, box-shadow 0.3s ease;
        }

        .wish-card:hover { transform: translateY(-5px); box-shadow: 0 20px 50px -10px rgba(0,0,0,0.1); }

        .card-img-box {
            width: 40%;
            background: #fff;
            border-right: 1px solid #f9f9f9;
            display: flex; align-items: center; justify-content: center;
            padding: 15px; /* Reduced padding */
        }
        .card-img-box img {
            width: 90%; height: 90%; object-fit: contain; mix-blend-mode: multiply;
            transition: transform 0.5s ease;
        }
        .wish-card:hover .card-img-box img { transform: scale(1.08); }

        .card-info-box {
            width: 60%;
            padding: 25px; /* Reduced padding */
            display: flex; flex-direction: column; justify-content: center;
            text-align: left;
        }

        .prod-title {
            font-size: 1.2rem; font-weight: 800; color: #000; 
            text-transform: uppercase; line-height: 1.2; margin-bottom: 8px;
            display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical; overflow: hidden;
        }

        .prod-price {
            font-size: 1.4rem; font-weight: 900; color: #111; margin-bottom: 15px;
        }
        .currency { font-size: 0.9rem; color: #888; font-weight: 700; margin-right: 4px; }

        /* Buttons */
        .btn-black-tight {
            background: #000; color: #fff; border: none; padding: 12px 20px; border-radius: 10px;
            font-weight: 700; font-size: 0.75rem; letter-spacing: 1px; text-transform: uppercase;
            display: flex; align-items: center; justify-content: center; gap: 8px; 
            transition: all 0.2s; width: fit-content; margin-bottom: 10px; cursor: pointer;
            width: 100%;
        }
        .btn-black-tight:hover { background: #333; transform: translateY(-2px); }

        .btn-remove-simple {
            background: transparent; color: #888; border: 1px solid #eee; padding: 10px;
            border-radius: 10px; font-weight: 700; font-size: 0.7rem; letter-spacing: 1px;
            text-transform: uppercase; width: 100%; cursor: pointer; transition: all 0.2s;
            display: flex; align-items: center; justify-content: center; gap: 6px;
        }
        .btn-remove-simple:hover { border-color: #ef4444; color: #ef4444; background: #fff; }

        .empty-area {
            grid-column: 1 / -1; 
            text-align: center; padding: 100px 0; opacity: 0.5;
        }

        /* Mobile */
        @media (max-width: 900px) {
            .page-container { display: block; padding: 120px 20px 40px; }
            .left-sticky-panel { position: static; margin-bottom: 40px; text-align: center; }
            .hero-desc { margin: 0 auto 30px; border: none; padding: 0; }
            .wish-card { height: auto; flex-direction: column; }
            .card-img-box { width: 100%; height: 250px; border-right: none; border-bottom: 1px solid #f9f9f9; }
            .card-info-box { width: 100%; padding: 25px; }
        }
    </style>
</head>
<body>
    
    <?php include 'header.php'; ?>

    <div class="page-container">
        
        <div class="left-sticky-panel">
            <span class="hero-badge">Your Collection</span>
            <h1 class="hero-title text-shadow-pop">My<br>Wishlist</h1>
            <p class="hero-desc">
                Review your saved items. Keep track of what you love and move them to your cart when ready.
            </p>
            <div class="item-counter">
                <?php
                    $count_query = $conn->prepare("SELECT COUNT(*) FROM `wishlist` WHERE user_id = ?");
                    $count_query->execute([$user_id]);
                    echo $count_query->fetchColumn();
                ?> Items Saved
            </div>
        </div>

        <div class="right-card-list">
            <?php
                // === FIXED QUERY: FETCH LIVE DATA FROM PRODUCTS TABLE ===
                // We join wishlist (w) with products (p) to get the real price and image.
                // We also alias w.id as 'wishlist_id' so the delete button works.
                $select_wishlist = $conn->prepare("
                    SELECT w.id AS wishlist_id, w.pid, w.user_id, 
                           p.name, p.price, p.image 
                    FROM wishlist w 
                    JOIN products p ON w.pid = p.id 
                    WHERE w.user_id = ?
                ");
                $select_wishlist->execute([$user_id]);
                
                if($select_wishlist->rowCount() > 0){
                    while($fetch_wishlist = $select_wishlist->fetch(PDO::FETCH_ASSOC)){
            ?>
            
            <div class="wish-card">
                <a href="view_page.php?pid=<?= $fetch_wishlist['pid']; ?>" class="card-img-box">
                    <img src="uploaded_img/<?= $fetch_wishlist['image']; ?>" alt="">
                </a>

                <div class="card-info-box">
                    <h2 class="prod-title"><?= $fetch_wishlist['name']; ?></h2>
                    <div class="prod-price">
                        <span class="currency">RM</span><?= number_format($fetch_wishlist['price'], 2); ?>
                    </div>

                    <form action="" method="POST">
                        <input type="hidden" name="pid" value="<?= $fetch_wishlist['pid']; ?>">
                        <input type="hidden" name="name" value="<?= $fetch_wishlist['name']; ?>">
                        <input type="hidden" name="price" value="<?= $fetch_wishlist['price']; ?>">
                        <input type="hidden" name="image" value="<?= $fetch_wishlist['image']; ?>">
                        
                        <button type="submit" name="add_to_cart" class="btn-black-tight">
                            Add to Cart
                        </button>
                    </form>

                    <a href="wishlist.php?delete=<?= $fetch_wishlist['wishlist_id']; ?>" 
                       class="btn-remove-simple" 
                       onclick="return confirm('Remove item?');">
                       <i class="far fa-trash-alt"></i> Remove
                    </a>
                </div>
            </div>

            <?php
                    }
                } else {
            ?>
            
            <div class="empty-area">
                <i class="far fa-heart fa-3x mb-3 text-gray-300"></i>
                <h3 class="fw-bold text-uppercase h5">Your list is empty</h3>
                <a href="shop.php" class="btn-black-tight" style="width: auto; padding: 12px 30px; margin: 15px auto 0;">
                    Go Shopping
                </a>
            </div>

            <?php } ?>
        </div>

    </div>

    <?php include 'footer.php'; ?>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/js/bootstrap.bundle.min.js"></script>

</body>
</html>