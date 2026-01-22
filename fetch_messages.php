<?php
@include 'config.php';
session_start();

if (isset($_GET['ticket_id'])) {
    $id = $_GET['ticket_id'];
    $stmt = $conn->prepare("SELECT * FROM message WHERE ticket_id = ? ORDER BY id ASC");
    $stmt->execute([$id]);
} elseif (isset($_GET['order_id'])) {
    $id = $_GET['order_id'];
    $stmt = $conn->prepare("SELECT * FROM message WHERE order_id = ? ORDER BY created_at ASC");
    $stmt->execute([$id]);
} else { exit; }

while($msg = $stmt->fetch(PDO::FETCH_ASSOC)){
    if (isset($_GET['is_admin'])) {
        // Admin View (Matches admin_contacts.php design)
        $isAdmin = ($msg['name'] === 'Admin Support');
        echo '<div class="msg-row '.($isAdmin ? 'admin' : 'user').'"><div class="msg-bubble">';
        if($msg['order_id'] && !$isAdmin) echo '<div class="flex items-center gap-2 border-b border-gray-200/50 pb-2 mb-2"><span class="bg-orange-100 text-orange-600 p-1 rounded"><i class="fas fa-box text-xs"></i></span><span class="text-[10px] font-black text-gray-400 uppercase tracking-wide">Order #'.$msg['order_id'].'</span></div>';
        if(!empty($msg['image'])) echo '<img src="uploaded_img/'.$msg['image'].'" class="max-w-[200px] rounded-lg mb-2 border border-gray-200 cursor-pointer" onclick="window.open(this.src)">';
        echo '<span class="block">'.nl2br(htmlspecialchars($msg['message'])).'</span>';
        echo '<div class="mt-1 text-[9px] font-bold uppercase tracking-wide opacity-40 text-right">'.date('H:i', strtotime($msg['created_at'])).'</div></div></div>';
    } else {
        // Customer View (Matches contact.php design)
        $is_me = ($msg['name'] !== 'Admin Support');
        echo '<div class="msg-row '.($is_me ? 'me' : 'support').'"><div class="msg-bubble">';
        if(!empty($msg['image'])) echo '<img src="uploaded_img/'.$msg['image'].'" class="max-w-[200px] rounded-lg mb-2 border border-white/20 cursor-pointer" onclick="window.open(this.src)">';
        echo '<span class="whitespace-pre-wrap">'.htmlspecialchars($msg['message']).'</span>';
        echo '<span class="msg-meta text-slate-400">'.date('H:i', strtotime($msg['created_at'])).'</span></div></div>';
    }
}
?>