// Inventory Validation Logic (White Box Test)
if(isset($_POST['add_to_cart'])){
   
    $pid = $_POST['pid'];
    $p_qty = $_POST['p_qty'];

    // 1. Check current stock level from database
    $check_stock = $conn->prepare("SELECT stock_quantity FROM `products` WHERE id = ?");
    $check_stock->execute([$pid]);
    $fetch_stock = $check_stock->fetch(PDO::FETCH_ASSOC);

    // 2. Compare requested quantity vs available stock
    if($p_qty > $fetch_stock['stock_quantity']){
        // Test Case Passed: System prevents ordering more than available
        $message[] = 'Quantity not available! Only ' . $fetch_stock['stock_quantity'] . ' left.';
    } else {
        // Proceed to add to cart
        $insert_cart = $conn->prepare("INSERT INTO `cart`(user_id, pid, quantity) VALUES(?,?,?)");
        $insert_cart->execute([$user_id, $pid, $p_qty]);
        $message[] = 'Added to cart successfully!';
    }
}