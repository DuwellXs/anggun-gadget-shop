<?php
@include 'config.php';

session_start();

$user_id = $_SESSION['user_id'];

if (!isset($user_id)) {
    header('location:login.php');
    exit;
}
    
    // Begin transaction
    if (isset($_GET['delete'])) {
        $delete_id = $_GET['delete'];
        
        // Begin transaction
        $conn->beginTransaction();
        
        try {
            // Get the quantity and product ID before deleting
            $select_cart_item = $conn->prepare("SELECT quantity, pid FROM `cart` WHERE id = ?");
            $select_cart_item->execute([$delete_id]);
            $cart_item = $select_cart_item->fetch(PDO::FETCH_ASSOC);
            
            if($cart_item) {
                // Restore the quantity in products table
                $restore_qty = $conn->prepare("UPDATE `products` SET quantity = quantity + ? WHERE id = ?");
                $restore_qty->execute([$cart_item['quantity'], $cart_item['pid']]);
                
                // Delete the cart item
                $delete_cart_item = $conn->prepare("DELETE FROM `cart` WHERE id = ?");
                $delete_cart_item->execute([$delete_id]);
                
                // Commit transaction
                $conn->commit();
            }
            
            header('location:cart.php');
            exit;
            
        } catch(Exception $e) {
            // Rollback transaction on error
            $conn->rollBack();
            $message[] = 'Error occurred while deleting cart item!';
        }
    }
    
    if (isset($_GET['delete_all'])) {
        // Begin transaction
        $conn->beginTransaction();
        
        try {
            // Get all cart items for this user
            $select_cart_items = $conn->prepare("SELECT quantity, pid FROM `cart` WHERE user_id = ?");
            $select_cart_items->execute([$user_id]);
            $cart_items = $select_cart_items->fetchAll(PDO::FETCH_ASSOC);
            
            // Restore quantities for all items
            foreach($cart_items as $item) {
                $restore_qty = $conn->prepare("UPDATE `products` SET quantity = quantity + ? WHERE id = ?");
                $restore_qty->execute([$item['quantity'], $item['pid']]);
            }
            
            // Delete all cart items
            $delete_cart_item = $conn->prepare("DELETE FROM `cart` WHERE user_id = ?");
            $delete_cart_item->execute([$user_id]);
            
            // Commit transaction
            $conn->commit();
            
            header('location:cart.php');
            exit;
            
        } catch(Exception $e) {
            // Rollback transaction on error
            $conn->rollBack();
            $message[] = 'Error occurred while deleting all cart items!';
        }
    }
    
    if (isset($_POST['update_qty'])) {
        $cart_id = $_POST['cart_id'];
        $p_qty = $_POST['p_qty'];
        $p_qty = filter_var($p_qty, FILTER_SANITIZE_STRING);
        
        // Begin transaction
        $conn->beginTransaction();
        
        try {
            // Get current cart item details
            $select_cart_item = $conn->prepare("SELECT quantity, pid FROM `cart` WHERE id = ?");
            $select_cart_item->execute([$cart_id]);
            $cart_item = $select_cart_item->fetch(PDO::FETCH_ASSOC);
            
            if($cart_item) {
                // Calculate quantity difference
                $qty_difference = $p_qty - $cart_item['quantity'];
                
                // Check if enough stock is available for increase
                if($qty_difference > 0) {
                    $check_stock = $conn->prepare("SELECT quantity FROM `products` WHERE id = ?");
                    $check_stock->execute([$cart_item['pid']]);
                    $product = $check_stock->fetch(PDO::FETCH_ASSOC);
                    
                    if($product['quantity'] < $qty_difference) {
                        $message[] = 'Insufficient stock!';
                        $conn->rollBack();
                        goto skip_update;
                    }
                }
                
                // Update product quantity (add if decreasing cart, subtract if increasing cart)
                $update_product_qty = $conn->prepare("UPDATE `products` SET quantity = quantity - ? WHERE id = ?");
                $update_product_qty->execute([$qty_difference, $cart_item['pid']]);
                
                // Update cart quantity
                $update_qty = $conn->prepare("UPDATE `cart` SET quantity = ? WHERE id = ?");
                $update_qty->execute([$p_qty, $cart_id]);
                
                // Commit transaction
                $conn->commit();
                $message[] = 'Cart quantity updated!';
            }
        } catch(Exception $e) {
            // Rollback transaction on error
            $conn->rollBack();
            $message[] = 'Error occurred while updating quantity!';
        }
    }

skip_update:
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Shopping Cart</title>

    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.1.1/css/all.min.css">

    <!-- Link to the CSS file -->
    <link rel="stylesheet" href="css/style.css">

    <style>
        .discount-badge {
            background-color: #ff4444;
            color: white;
            padding: 5px 10px;
            border-radius: 5px;
            font-size: 14px;
            position: absolute;
            top: 10px;
            right: 55px;
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

    <section class="shopping-cart">
        <h1 class="title">Products Added</h1>

        <div class="box-container">
            <?php
            $grand_total = 0;
            $select_cart = $conn->prepare("SELECT c.*, p.details, p.quantity as stock_quantity, 
                                         p.discount_percentage, p.discounted_price, p.price as original_price 
                                         FROM `cart` c
                                         LEFT JOIN `products` p ON c.pid = p.id
                                         WHERE c.user_id = ?");
            $select_cart->execute([$user_id]);

            if ($select_cart->rowCount() > 0) {
                while ($fetch_cart = $select_cart->fetch(PDO::FETCH_ASSOC)) {
            ?>
            <form action="" method="POST" class="box">
                <div class="image-container">
                    <?php if(isset($fetch_cart['discount_percentage']) && $fetch_cart['discount_percentage'] > 0): ?>
                        <div class="discount-badge"><?= $fetch_cart['discount_percentage']; ?>% OFF</div>
                    <?php endif; ?>

                    <a href="cart.php?delete=<?= $fetch_cart['id']; ?>" class="fas fa-times" onclick="return confirm('Delete this item?');"></a>
                    <a href="view_page.php?pid=<?= $fetch_cart['pid']; ?>" class="fas fa-eye"></a>
                    <img src="uploaded_img/<?= $fetch_cart['image']; ?>" alt="">
                </div>
                
                <div class="content">
                    <div class="name"><?= $fetch_cart['name']; ?></div>

                    <?php if(isset($fetch_cart['discount_percentage']) && $fetch_cart['discount_percentage'] > 0): ?>
                        <div class="price">
                            <span class="original-price">RM<?= $fetch_cart['original_price']; ?></span>
                            <span class="discounted-price">RM<?= $fetch_cart['discounted_price']; ?></span>
                        </div>
                    <?php else: ?>
                        <div class="price">RM<?= $fetch_cart['price']; ?></div>
                    <?php endif; ?>

                    <div class="details"><?= $fetch_cart['details']; ?></div>
                    <div class="stock-info">
                        <span>Available Stock: <?= $fetch_cart['stock_quantity']; ?></span>
                    </div>
                    
                    <div class="sub-total" style="margin-top: 0.5rem; font-weight: 600; color: var(--primary);">
                        Sub Total: <span>RM<?= $sub_total = ($fetch_cart['price'] * $fetch_cart['quantity']); ?></span>
                    </div>
                </div>

                <input type="hidden" name="cart_id" value="<?= $fetch_cart['id']; ?>">
                
                <div class="button-wrapper">
                    <div class="qty-wrapper" style="display: flex; gap: 0.5rem; align-items: center;">
                        <input type="number" min="1" value="<?= $fetch_cart['quantity']; ?>" class="qty" name="p_qty" style="width: 60px; padding: 0.6rem; border: 1px solid var(--border-color); border-radius: 0.5rem; text-align: center;">
                    </div>
                    <input type="submit" value="Update" name="update_qty" class="option-btn">
                </div>
            </form>
            <?php
                $grand_total += $sub_total;
                }
            } else {
                echo '<p class="empty">Your cart is empty</p>';
            }
            ?>
        </div>

        <div class="cart-total">
            <p>Grand Total: <span>RM<?= $grand_total; ?></span></p>
            <a href="shop.php" class="option-btn">Continue Shopping</a>
            <a href="cart.php?delete_all" class="delete-btn <?= ($grand_total > 0) ? '' : 'disabled'; ?>" onclick="return confirm('Delete all items from cart?');">Delete All</a>
            <a href="checkout.php" class="btn <?= ($grand_total > 0) ? '' : 'disabled'; ?>">Proceed to Checkout</a>
        </div>
    </section>

    <?php include 'footer.php'; ?>
</body>
</html>