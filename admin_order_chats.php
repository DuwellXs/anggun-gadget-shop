<?php
@include 'config.php';
session_start();

$admin_id = $_SESSION['admin_id'];
if(!isset($admin_id)){ header('location:login.php'); exit(); }

if(isset($_GET['delete_convo'])){
   $order_id_to_delete = $_GET['delete_convo'];
   $delete_messages = $conn->prepare("DELETE FROM `message` WHERE order_id = ?");
   $delete_messages->execute([$order_id_to_delete]);
   header('location:admin_order_chats.php');
   exit();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
   <meta charset="UTF-8">
   <meta name="viewport" content="width=device-width, initial-scale=1.0">
   <title>Order Chats | Admin Panel</title>
   <script src="https://cdn.tailwindcss.com"></script>
   <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800;900&display=swap" rel="stylesheet">
   <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
   
   <style>
      html, body { margin: 0; padding: 0; height: 100vh; overflow: hidden !important; font-family: 'Inter', sans-serif; background-color: #ffffff; color: #000; }
      .master-scroll-wrapper { height: 100vh; width: 100%; display: flex; justify-content: center; background-color: #fff; }
      .content-container { width: 100%; max-width: 1400px; height: 100%; display: grid; grid-template-columns: 280px minmax(0, 1fr); gap: 60px; padding: 0 30px; }
      .sidebar-scroll { height: 100%; overflow-y: auto; padding-top: 40px; }
      .content-scroll { height: 100%; overflow-y: auto; padding-top: 40px; padding-bottom: 100px; }
      
      /* CHAT CARD */
      .chat-card { background: #fff; border-radius: 16px; border: 1px solid #f3f4f6; box-shadow: 0 10px 30px -10px rgba(0,0,0,0.05); padding: 25px; margin-bottom: 20px; transition: 0.2s; }
      .chat-card:hover { border-color: #e2e8f0; box-shadow: 0 20px 40px -10px rgba(0,0,0,0.08); }
      
      .chat-history { display: none; background: #f9fafb; border-radius: 12px; margin-top: 20px; padding: 20px; max-height: 400px; overflow-y: auto; border: 1px solid #f1f5f9; }
      .msg { margin-bottom: 10px; padding: 10px 14px; border-radius: 10px; font-size: 0.85rem; max-width: 80%; width: fit-content; }
      .msg-user { background: #fff; border: 1px solid #e5e7eb; color: #334155; margin-right: auto; border-bottom-left-radius: 2px; }
      .msg-rider { background: #0f172a; color: #fff; margin-left: auto; border-bottom-right-radius: 2px; }
   </style>
</head>
<body>

<div class="master-scroll-wrapper">
   <div class="content-container">
      <div class="sidebar-scroll"><?php include 'admin_header.php'; ?></div>
      <div class="content-scroll">
         
         <div class="mb-12">
             <h1 class="text-4xl font-black uppercase tracking-tighter">Order Chats</h1>
             <p class="text-sm font-bold text-gray-400 mt-2 uppercase">Monitor Active Delivery Conversations</p>
         </div>

         <div class="grid grid-cols-1 gap-6">
            <?php
               $select_chats = $conn->prepare("SELECT m.order_id, MAX(m.created_at) as last_msg, u.name as customer_name, r.name as rider_name 
                  FROM message m 
                  LEFT JOIN orders o ON m.order_id = o.id
                  LEFT JOIN users u ON o.user_id = u.id
                  LEFT JOIN users r ON o.delivery_rider = r.id
                  WHERE m.order_id > 0 GROUP BY m.order_id ORDER BY last_msg DESC");
               $select_chats->execute();
               
               if($select_chats->rowCount() > 0){
                  while($chat = $select_chats->fetch(PDO::FETCH_ASSOC)){
            ?>
            <div class="chat-card">
               <div class="flex justify-between items-center">
                  <div>
                     <span class="bg-gray-100 text-gray-600 px-3 py-1 rounded-full text-xs font-black uppercase tracking-wider">Order #<?= $chat['order_id']; ?></span>
                     <div class="text-sm font-bold mt-2 text-gray-900"><?= $chat['customer_name']; ?> <span class="text-gray-300 mx-2">/</span> <?= $chat['rider_name'] ?? 'Pending Rider'; ?></div>
                  </div>
                  <div class="text-right">
                      <button onclick="toggleChat(this)" class="bg-white border border-gray-200 text-gray-600 px-4 py-2 rounded-lg text-xs font-bold uppercase tracking-wider hover:bg-black hover:text-white transition-colors">View Chat</button>
                  </div>
               </div>

               <div class="chat-history">
                  <?php
                     $msgs = $conn->prepare("SELECT * FROM message WHERE order_id = ? ORDER BY created_at ASC");
                     $msgs->execute([$chat['order_id']]);
                     while($m = $msgs->fetch(PDO::FETCH_ASSOC)){
                        // Assuming 'sender_role' exists or inferred from logic
                        $is_rider = (isset($m['sender_role']) && $m['sender_role'] == 'rider'); 
                        $class = $is_rider ? 'msg-rider' : 'msg-user';
                  ?>
                     <div class="msg <?= $class ?>">
                        <div class="text-[9px] font-bold uppercase opacity-50 mb-1"><?= $is_rider ? 'Rider' : 'Customer' ?></div>
                        <?= htmlspecialchars($m['message']); ?>
                     </div>
                  <?php } ?>
               </div>
            </div>
            <?php } } else { echo '<p class="text-center text-gray-400 font-bold uppercase text-xs py-10">No active conversations found.</p>'; } ?>
         </div>

      </div>
   </div>
</div>

<script>
    function toggleChat(btn) {
        const history = btn.closest('.chat-card').querySelector('.chat-history');
        history.style.display = history.style.display === 'block' ? 'none' : 'block';
        btn.textContent = history.style.display === 'block' ? 'Hide Chat' : 'View Chat';
    }
</script>

</body>
</html>