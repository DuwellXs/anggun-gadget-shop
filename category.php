<?php

@include 'config.php';

session_start();

$user_id = $_SESSION['user_id'];

if (!isset($user_id)) {
    header('location:login.php');
};

if (isset($_POST['add_to_wishlist'])) {
    $pid = $_POST['pid'];
    $pid = filter_var($pid, FILTER_SANITIZE_STRING);
    $p_name = $_POST['p_name'];
    $p_name = filter_var($p_name, FILTER_SANITIZE_STRING);
    $p_price = $_POST['p_price'];
    $p_price = filter_var($p_price, FILTER_SANITIZE_STRING);
    $p_image = $_POST['p_image'];
    $p_image = filter_var($p_image, FILTER_SANITIZE_STRING);

    $check_wishlist_numbers = $conn->prepare("SELECT * FROM `wishlist` WHERE name = ? AND user_id = ?");
    $check_wishlist_numbers->execute([$p_name, $user_id]);

    $check_cart_numbers = $conn->prepare("SELECT * FROM `cart` WHERE name = ? AND user_id = ?");
    $check_cart_numbers->execute([$p_name, $user_id]);

    if ($check_wishlist_numbers->rowCount() > 0) {
        $message[] = 'Already added to wishlist!';
    } elseif ($check_cart_numbers->rowCount() > 0) {
        $message[] = 'Already added to cart!';
    } else {
        $insert_wishlist = $conn->prepare("INSERT INTO `wishlist`(user_id, pid, name, price, image) VALUES(?,?,?,?,?)");
        $insert_wishlist->execute([$user_id, $pid, $p_name, $p_price, $p_image]);
        $message[] = 'Added to wishlist!';
    }
}

if (isset($_POST['add_to_cart'])) {
    $pid = $_POST['pid'];
    $pid = filter_var($pid, FILTER_SANITIZE_STRING);
    $p_name = $_POST['p_name'];
    $p_name = filter_var($p_name, FILTER_SANITIZE_STRING);
    $p_price = $_POST['p_price'];
    $p_price = filter_var($p_price, FILTER_SANITIZE_STRING);
    $p_image = $_POST['p_image'];
    $p_image = filter_var($p_image, FILTER_SANITIZE_STRING);
    $p_qty = $_POST['p_qty'];
    $p_qty = filter_var($p_qty, FILTER_SANITIZE_STRING);

    // Check available stock
    $check_stock = $conn->prepare("SELECT quantity FROM `products` WHERE id = ?");
    $check_stock->execute([$pid]);
    $product = $check_stock->fetch(PDO::FETCH_ASSOC);

    if ($product && $product['quantity'] >= $p_qty) {
        $check_cart_numbers = $conn->prepare("SELECT * FROM `cart` WHERE name = ? AND user_id = ?");
        $check_cart_numbers->execute([$p_name, $user_id]);

        if ($check_cart_numbers->rowCount() > 0) {
            echo '<script>alert("Product already in cart!");</script>';
        } else {
            // Remove from wishlist if exists
            $check_wishlist_numbers = $conn->prepare("SELECT * FROM `wishlist` WHERE name = ? AND user_id = ?");
            $check_wishlist_numbers->execute([$p_name, $user_id]);
            if ($check_wishlist_numbers->rowCount() > 0) {
                $delete_wishlist = $conn->prepare("DELETE FROM `wishlist` WHERE name = ? AND user_id = ?");
                $delete_wishlist->execute([$p_name, $user_id]);
            }

            // Add to cart
            $insert_cart = $conn->prepare("INSERT INTO `cart`(user_id, pid, name, price, quantity, image) VALUES(?,?,?,?,?,?)");
            $insert_cart->execute([$user_id, $pid, $p_name, $p_price, $p_qty, $p_image]);

            // Reduce stock quantity
            $new_qty = $product['quantity'] - $p_qty;
            $update_stock = $conn->prepare("UPDATE `products` SET quantity = ? WHERE id = ?");
            $update_stock->execute([$new_qty, $pid]);
        }
    } else {
        echo '<script>alert("Insufficient stock!");</script>';
    }
}

?>

<!DOCTYPE html>
<html lang="en">

<head>
<meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Category</title>

    <!-- font awesome cdn link  -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.1.1/css/all.min.css">

    <!-- custom css file link  -->
    <link rel="stylesheet" href="css/style.css">

    <style>
        .discount-badge {
            background-color: #ff4444;
            color: white;
            padding: 5px 10px;
            border-radius: 5px;
            font-size: 11px;
            position: absolute;
            top: 10px;
            right: 10px;
            z-index: 1;
        }

        .original-price {
            text-decoration: line-through;
            color: #999;
            font-size: 0.9em;
            display: block;
        }

        .discounted-price {
            color: #ff4444;
            font-weight: bold;
            font-size: 1.2em;
            display: block;
        }

        .box {
            position: relative;
        }
    </style>
</head>

<body>

    <?php include 'header.php'; ?>

    <section class="products">
        <h1 class="title">Products Categories</h1>

        <div class="box-container">
            <?php
            $category_name = $_GET['category'];
            $select_products = $conn->prepare("SELECT * FROM `products` WHERE category = ?");
            $select_products->execute([$category_name]);
            if ($select_products->rowCount() > 0) {
                while ($fetch_products = $select_products->fetch(PDO::FETCH_ASSOC)) {
            ?>
<form action="" class="box" method="POST">
   <div class="image-container">
      <?php if ($fetch_products['category'] === 'SALES' && isset($fetch_products['discount_percentage']) && $fetch_products['discount_percentage'] > 0) : ?>
         <div class="discount-badge"><?= $fetch_products['discount_percentage']; ?>% OFF</div>
      <?php endif; ?>
      
      <a href="view_page.php?pid=<?= $fetch_products['id']; ?>" class="fas fa-eye"></a>
      <img src="uploaded_img/<?= $fetch_products['image']; ?>" alt="">
   </div>
   
   <div class="content">
      <div class="name"><?= $fetch_products['name']; ?></div>
      
      <?php if ($fetch_products['category'] === 'SALES' && isset($fetch_products['discount_percentage']) && $fetch_products['discount_percentage'] > 0) : ?>
         <div class="price">
            <span class="original-price">RM<?= $fetch_products['price']; ?></span>
            <span class="discounted-price">RM<?= $fetch_products['discounted_price']; ?></span>
         </div>
      <?php else : ?>
         <div class="price">RM<span><?= $fetch_products['price']; ?></span></div>
      <?php endif; ?>

      <div class="details"><?= $fetch_products['details']; ?></div>
      
      <div class="stock-info">
         <span class="quantity">Stock: <?= $fetch_products['quantity']; ?></span>
         <div class="qty-wrapper">
            <input type="number" min="1" max="<?= $fetch_products['quantity']; ?>" value="1" name="p_qty" class="qty">
         </div>
      </div>
      
      <input type="hidden" name="pid" value="<?= $fetch_products['id']; ?>">
      <input type="hidden" name="p_name" value="<?= $fetch_products['name']; ?>">
      <input type="hidden" name="p_price" value="<?= ($fetch_products['category'] === 'SALES' && isset($fetch_products['discounted_price'])) ? $fetch_products['discounted_price'] : $fetch_products['price']; ?>">
      <input type="hidden" name="p_image" value="<?= $fetch_products['image']; ?>">
   </div>

   <div class="button-wrapper">
      <button type="submit" name="add_to_wishlist" class="modern-icon-btn heart">
         <i class="fas fa-heart"></i>
      </button>
      <button type="submit" name="add_to_cart" class="cart_btn">
         Add to Cart
      </button>
   </div>
</form>
            <?php
                }
            } else {
                echo '<p class="empty">No products available!</p>';
            }
            ?>
        </div>
    </section>

    <?php include 'footer.php'; ?>

    <script src="js/script.js"></script>

</body>

</html>
