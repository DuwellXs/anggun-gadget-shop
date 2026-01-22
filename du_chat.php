<?php
@include 'config.php';
session_start();

$rider_id = $_SESSION['rider_id'] ?? null; // Assuming rider logs in
// For testing, if you don't have rider login session yet, remove the check
if(!$rider_id) { echo "Rider not logged in."; exit; }

$order_id = $_GET['oid'] ?? null;
if(!$order_id) { header('location:rider_dashboard.php'); exit; }

// --- HANDLE FILE UPLOADS ---
if(isset($_POST['upload_proof'])){
    $type = $_POST['proof_type']; // 'pickup' or 'delivery'
    $img = $_FILES['proof_img']['name'];
    $tmp = $_FILES['proof_img']['tmp_name'];
    
    if($img){
        $new_name = $type . "_" . time() . "_" . $img;
        move_uploaded_file($tmp, 'uploaded_img/' . $new_name);
        
        // Update Database
        $col = ($type == 'pickup') ? 'pickup_image' : 'delivery_image';
        $conn->prepare("UPDATE orders SET $col = ? WHERE id = ?")->execute([$new_name, $order_id]);
        
        // Auto-update status logic
        if($type == 'delivery') {
            $conn->prepare("UPDATE orders SET delivery_status = 'Completed' WHERE id = ?")->execute([$order_id]);
        }
        
        header("location:rider_job_view.php?oid=$order_id");
    }
}

// --- HANDLE CHAT ---
if(isset($_POST['send_msg'])){
    $msg = filter_var($_POST['msg'], FILTER_SANITIZE_STRING);
    $conn->prepare("INSERT INTO message (user_id, order_id, message, sender_role) VALUES (?, ?, ?, 'rider')")
         ->execute([$rider_id, $order_id, $msg]);
}

$order = $conn->query("SELECT * FROM orders WHERE id = $order_id")->fetch(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <title>Rider Job #<?= $order_id ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>body { font-family: 'Inter', sans-serif; background: #f8fafc; }</style>
</head>
<body class="p-4 max-w-lg mx-auto">

    <div class="bg-white p-6 rounded-2xl shadow-sm border border-gray-200 mb-6">
        <div class="flex justify-between items-center mb-4">
            <span class="bg-black text-white text-xs font-bold px-3 py-1 rounded-full">ORDER #<?= $order_id ?></span>
            <span class="text-sm font-bold text-blue-600 uppercase"><?= $order['delivery_status'] ?></span>
        </div>
        <h2 class="text-xl font-black uppercase"><?= $order['name'] ?></h2>
        <p class="text-gray-500 text-sm mt-1"><?= $order['address'] ?></p>
        <a href="tel:<?= $order['number'] ?>" class="inline-block mt-4 bg-green-500 text-white px-4 py-2 rounded-lg text-sm font-bold"><i class="fas fa-phone"></i> Call Customer</a>
    </div>

    <div class="bg-white p-6 rounded-2xl shadow-sm border border-gray-200 mb-6">
        <h3 class="text-sm font-black uppercase mb-4">1. Proof of Pickup</h3>
        <?php if($order['pickup_image']): ?>
            <img src="uploaded_img/<?= $order['pickup_image'] ?>" class="w-full h-40 object-cover rounded-lg border border-green-200">
            <p class="text-green-600 text-xs font-bold mt-2"><i class="fas fa-check-circle"></i> Pickup Confirmed</p>
        <?php else: ?>
            <form action="" method="POST" enctype="multipart/form-data">
                <input type="hidden" name="proof_type" value="pickup">
                <input type="file" name="proof_img" required class="block w-full text-sm text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded-full file:border-0 file:text-xs file:font-semibold file:bg-blue-50 file:text-blue-700 hover:file:bg-blue-100 mb-3">
                <button type="submit" name="upload_proof" class="w-full bg-blue-600 text-white font-bold py-3 rounded-xl">Confirm Pickup</button>
            </form>
        <?php endif; ?>
    </div>

    <div class="bg-white p-6 rounded-2xl shadow-sm border border-gray-200 mb-6">
        <h3 class="text-sm font-black uppercase mb-4">2. Proof of Delivery</h3>
        <?php if($order['delivery_image']): ?>
            <img src="uploaded_img/<?= $order['delivery_image'] ?>" class="w-full h-40 object-cover rounded-lg border border-green-200">
            <p class="text-green-600 text-xs font-bold mt-2"><i class="fas fa-check-circle"></i> Job Completed</p>
        <?php else: ?>
            <?php if(!$order['pickup_image']): ?>
                <div class="p-4 bg-gray-100 rounded-lg text-center text-gray-400 text-sm font-bold">Complete Pickup First</div>
            <?php else: ?>
                <form action="" method="POST" enctype="multipart/form-data">
                    <input type="hidden" name="proof_type" value="delivery">
                    <input type="file" name="proof_img" required class="block w-full text-sm text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded-full file:border-0 file:text-xs file:font-semibold file:bg-green-50 file:text-green-700 hover:file:bg-green-100 mb-3">
                    <button type="submit" name="upload_proof" class="w-full bg-black text-white font-bold py-3 rounded-xl">Complete Delivery</button>
                </form>
            <?php endif; ?>
        <?php endif; ?>
    </div>

    <div class="bg-white p-6 rounded-2xl shadow-sm border border-gray-200">
        <h3 class="text-sm font-black uppercase mb-4">Chat with Customer</h3>
        <div class="h-64 overflow-y-auto bg-gray-50 rounded-lg p-4 mb-4 space-y-3">
            <?php
            $msgs = $conn->query("SELECT * FROM message WHERE order_id = $order_id ORDER BY id ASC");
            while($m = $msgs->fetch(PDO::FETCH_ASSOC)):
                $is_me = ($m['user_id'] == $rider_id); // Simplified logic
            ?>
                <div class="flex <?= $is_me ? 'justify-end' : 'justify-start' ?>">
                    <div class="<?= $is_me ? 'bg-blue-600 text-white' : 'bg-white border border-gray-200 text-gray-800' ?> px-4 py-2 rounded-lg text-sm max-w-[80%]">
                        <?= htmlspecialchars($m['message']) ?>
                    </div>
                </div>
            <?php endwhile; ?>
        </div>
        <form action="" method="POST" class="flex gap-2">
            <input type="text" name="msg" placeholder="Type message..." class="flex-1 bg-gray-100 border-0 rounded-lg px-4 font-medium outline-none">
            <button type="submit" name="send_msg" class="bg-black text-white px-4 py-3 rounded-lg"><i class="fas fa-paper-plane"></i></button>
        </form>
    </div>

</body>
</html>