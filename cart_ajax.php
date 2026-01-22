<?php
// FILE: cart_ajax.php
include 'config.php';
session_start();

header('Content-Type: application/json');

$user_id = $_SESSION['user_id'] ?? null;
$response = ['status' => 'error', 'message' => 'Unauthorized'];

if ($user_id && $_SERVER['REQUEST_METHOD'] === 'POST') {
    
    $action = $_POST['action'] ?? '';

    // 1. DELETE SINGLE ITEM
    if ($action === 'delete') {
        $cart_id = $_POST['cart_id'];
        
        // Restore stock
        $get_item = $conn->prepare("SELECT pid, quantity FROM cart WHERE id = ?");
        $get_item->execute([$cart_id]);
        
        if($item = $get_item->fetch(PDO::FETCH_ASSOC)){
            $restore = $conn->prepare("UPDATE products SET quantity = quantity + ? WHERE id = ?");
            $restore->execute([$item['quantity'], $item['pid']]);
        }

        $delete = $conn->prepare("DELETE FROM cart WHERE id = ? AND user_id = ?");
        if ($delete->execute([$cart_id, $user_id])) {
            $response = ['status' => 'success'];
        }
    }

    // 2. CLEAR ENTIRE CART
    if ($action === 'delete_all') {
        // Restore all stock
        $get_items = $conn->prepare("SELECT pid, quantity FROM cart WHERE user_id = ?");
        $get_items->execute([$user_id]);
        while($item = $get_items->fetch(PDO::FETCH_ASSOC)){
            $restore = $conn->prepare("UPDATE products SET quantity = quantity + ? WHERE id = ?");
            $restore->execute([$item['quantity'], $item['pid']]);
        }

        $delete = $conn->prepare("DELETE FROM cart WHERE user_id = ?");
        if ($delete->execute([$user_id])) {
            $response = ['status' => 'success'];
        }
    }

    // 3. UPDATE QUANTITY
    if ($action === 'update_qty') {
        $cart_id = $_POST['cart_id'];
        $new_qty = (int)$_POST['qty'];

        $get_cart = $conn->prepare("SELECT pid, quantity FROM cart WHERE id = ?");
        $get_cart->execute([$cart_id]);
        $cart_item = $get_cart->fetch(PDO::FETCH_ASSOC);

        if($cart_item) {
            $pid = $cart_item['pid'];
            $old_qty = $cart_item['quantity'];
            $diff = $new_qty - $old_qty;

            // Check stock limit
            $check_stock = $conn->prepare("SELECT quantity FROM products WHERE id = ?");
            $check_stock->execute([$pid]);
            $product = $check_stock->fetch(PDO::FETCH_ASSOC);

            if($product['quantity'] < $diff) {
                $response = ['status' => 'error', 'message' => 'Max stock reached'];
            } else {
                $update_prod = $conn->prepare("UPDATE products SET quantity = quantity - ? WHERE id = ?");
                $update_prod->execute([$diff, $pid]);
                $update_cart = $conn->prepare("UPDATE cart SET quantity = ? WHERE id = ?");
                $update_cart->execute([$new_qty, $cart_id]);
                $response = ['status' => 'success'];
            }
        }
    }

    // 4. UPDATE VARIANT (NEW ADDITION)
    if ($action === 'update_variant') {
        $cart_id = $_POST['cart_id'];
        $variant = $_POST['variant'];
        
        $update = $conn->prepare("UPDATE cart SET selected_variants = ? WHERE id = ? AND user_id = ?");
        if($update->execute([$variant, $cart_id, $user_id])){
            $response = ['status' => 'success'];
        }
    }

    // Return fresh cart count
    if($response['status'] === 'success'){
        $count_q = $conn->prepare("SELECT * FROM cart WHERE user_id = ?");
        $count_q->execute([$user_id]);
        $response['cart_count'] = $count_q->rowCount();
    }
}

echo json_encode($response);
exit;
?>