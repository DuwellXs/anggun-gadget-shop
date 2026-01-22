<?php
@include 'config.php';
session_start();

// Enable Error Reporting
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

$user_id = $_SESSION['user_id'] ?? null;
if (!$user_id) { header('location:login.php'); exit(); }

// FIX: Ensure upload folder exists with correct permissions
if (!is_dir('uploaded_img')) { mkdir('uploaded_img', 0777, true); }

// --- 1. HANDLE NEW TICKET ---
if(isset($_POST['create_ticket'])){
    try {
        $name = filter_var($_POST['name'], FILTER_SANITIZE_STRING);
        $email = filter_var($_POST['email'], FILTER_SANITIZE_STRING);
        $number = filter_var($_POST['number'], FILTER_SANITIZE_STRING);
        $msg = filter_var($_POST['message'], FILTER_SANITIZE_STRING);
        $order_ref = filter_var($_POST['order_id'] ?? '', FILTER_SANITIZE_STRING);
        
        $ticket_id = mt_rand(100000, 999999); 

        $image = $_FILES['image']['name'] ?? '';
        if(!empty($image)){
            $image = filter_var($image, FILTER_SANITIZE_STRING);
            move_uploaded_file($_FILES['image']['tmp_name'], 'uploaded_img/'.$image);
        }

        $insert = $conn->prepare("INSERT INTO `message`(user_id, ticket_id, name, email, number, message, image, order_id) VALUES(?,?,?,?,?,?,?,?)");
        $insert->execute([$user_id, $ticket_id, $name, $email, $number, $msg, $image, $order_ref]);
        
        header("Location: contact.php?view_ticket=$ticket_id");
        exit();
    } catch(PDOException $e) {
        die("Error creating ticket: " . $e->getMessage());
    }
}

// --- 2. HANDLE REPLY ---
if(isset($_POST['reply_ticket'])){
    try {
        $ticket_id = $_POST['ticket_id'];
        $msg = filter_var($_POST['message'], FILTER_SANITIZE_STRING);
        
        $u = $conn->query("SELECT * FROM users WHERE id = $user_id")->fetch(PDO::FETCH_ASSOC);
        
        $image = $_FILES['image']['name'] ?? '';
        if(!empty($image)){
            $image = filter_var($image, FILTER_SANITIZE_STRING);
            move_uploaded_file($_FILES['image']['tmp_name'], 'uploaded_img/'.$image);
        }

        $insert = $conn->prepare("INSERT INTO `message`(user_id, ticket_id, name, email, number, message, image, order_id) VALUES(?,?,?,?,?,?,?,?)");
        $insert->execute([$user_id, $ticket_id, $u['name'], $u['email'], $u['number'] ?? '', $msg, $image, 0]);
        
        header("Location: contact.php?view_ticket=$ticket_id");
        exit();
    } catch(PDOException $e) {
        die("Error replying: " . $e->getMessage());
    }
}

// --- 3. FETCH TICKETS ---
$active_ticket = $_GET['view_ticket'] ?? null;
$prefill_order_id = $_GET['order_id'] ?? '';

$tickets_query = $conn->prepare("SELECT * FROM message WHERE user_id = ? AND ticket_id != 0 ORDER BY id DESC");
$tickets_query->execute([$user_id]);
$all_msgs = $tickets_query->fetchAll(PDO::FETCH_ASSOC);

$my_tickets = [];
$seen_tickets = [];
foreach($all_msgs as $m){
    if(!in_array($m['ticket_id'], $seen_tickets)){
        $seen_tickets[] = $m['ticket_id'];
        $my_tickets[] = $m; 
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
   <meta charset="UTF-8">
   <meta name="viewport" content="width=device-width, initial-scale=1.0">
   <title>Support Dashboard | Anggun Gadget</title>
   <script src="https://cdn.tailwindcss.com"></script>
   <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800;900&display=swap" rel="stylesheet">
   <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
   
   <style>
       html, body { font-family: 'Inter', sans-serif; background: #f8fafc; height: 100vh; overflow: hidden; }
       
       .dashboard-grid { 
           display: grid; 
           grid-template-columns: 350px 1fr; 
           height: calc(100vh - 80px); 
           margin-top: 80px; 
       }
       
       /* SIDEBAR */
       .sidebar { background: #fff; border-right: 1px solid #e2e8f0; overflow-y: auto; display: flex; flex-direction: column; height: 100%; }
       .sidebar-header { padding: 20px; border-bottom: 1px solid #f1f5f9; background: #fff; position: sticky; top: 0; z-index: 10; }
       .ticket-list { flex: 1; overflow-y: auto; }
       .ticket-item { padding: 15px 20px; border-bottom: 1px solid #f8fafc; cursor: pointer; transition: all 0.2s; display: flex; gap: 12px; }
       .ticket-item:hover { background: #f8fafc; }
       .ticket-item.active { background: #eff6ff; border-right: 3px solid #2563eb; }
       .t-avatar { width: 35px; height: 35px; background: #f1f5f9; border-radius: 50%; display: flex; align-items: center; justify-content: center; color: #64748b; font-size: 0.9rem; flex-shrink: 0; }
       .active .t-avatar { background: #dbeafe; color: #2563eb; }

       /* MAIN AREA */
       .main-area { background: #f1f5f9; display: flex; flex-direction: column; height: 100%; position: relative; overflow: hidden; }
       .chat-header { background: #fff; padding: 15px 25px; border-bottom: 1px solid #e2e8f0; display: flex; justify-content: space-between; align-items: center; z-index: 20; flex-shrink: 0; }
       
       /* FIXED SCROLL FEED */
       .chat-feed { 
           flex: 1; 
           overflow-y: auto !important; 
           padding: 25px; 
           display: flex; 
           flex-direction: column; 
           gap: 15px; 
           scroll-behavior: smooth;
       }
       
       /* MESSAGES */
       .msg-row { display: flex; width: 100%; margin-bottom: 5px; }
       .msg-row.me { justify-content: flex-end; }
       .msg-bubble { max-width: 70%; padding: 12px 16px; border-radius: 16px; font-size: 0.9rem; line-height: 1.5; box-shadow: 0 1px 2px rgba(0,0,0,0.05); }
       .msg-row.me .msg-bubble { background: #000; color: #fff; border-bottom-right-radius: 2px; }
       .msg-row.support .msg-bubble { background: #fff; color: #1e293b; border: 1px solid #e2e8f0; border-bottom-left-radius: 2px; }
       .msg-meta { font-size: 0.65rem; margin-top: 4px; opacity: 0.6; display: block; text-align: right; }

       /* INPUT */
       .input-area { background: #fff; padding: 15px 25px; border-top: 1px solid #e2e8f0; flex-shrink: 0; }
       .input-wrapper { background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 30px; padding: 6px 6px 6px 15px; display: flex; align-items: center; gap: 10px; }
       .chat-input { flex: 1; background: transparent; border: none; padding: 8px; outline: none; font-size: 0.9rem; }
       
       /* NEW FORM */
       .create-container { max-width: 500px; margin: 30px auto; background: #fff; padding: 35px; border-radius: 20px; box-shadow: 0 10px 30px -5px rgba(0,0,0,0.05); }
       .ag-input, .ag-textarea { width: 100%; background: #f8fafc; border: 1px solid #e2e8f0; padding: 12px; border-radius: 8px; margin-bottom: 15px; outline: none; font-size: 0.9rem; font-weight: 500; }
       .ag-input:focus { border-color: #000; background: #fff; }

       /* CART DRAWER */
       #cart-drawer { z-index: 9999 !important; }

       @media (max-width: 1024px) {
           .dashboard-grid { grid-template-columns: 1fr; }
           .sidebar { display: <?= $active_ticket ? 'none' : 'flex' ?>; width: 100%; }
           .main-area { display: <?= $active_ticket ? 'flex' : 'none' ?>; }
       }
   </style>
</head>
<body>

<?php include 'header.php'; ?>

<div id="cart-drawer" class="fixed inset-0 z-[100] hidden">
    <div class="absolute inset-0 bg-black/50 backdrop-blur-sm transition-opacity" onclick="toggleCartDrawer()"></div>
    <div class="absolute top-0 right-0 h-full w-full max-w-md bg-white shadow-2xl transform transition-transform translate-x-full duration-300 flex flex-col" id="cart-panel">
        <div class="p-5 border-b border-gray-100 flex justify-between items-center bg-white">
            <h2 class="text-lg font-black uppercase tracking-tight">Shopping Cart</h2>
            <button onclick="toggleCartDrawer()" class="w-8 h-8 rounded-full bg-gray-50 flex items-center justify-center hover:bg-gray-100 text-gray-500">
                <i class="fas fa-times"></i>
            </button>
        </div>
        <div class="flex-1 overflow-y-auto p-5 space-y-4">
            <?php
            $cart_q = $conn->prepare("SELECT * FROM `cart` WHERE user_id = ?");
            $cart_q->execute([$user_id]);
            if($cart_q->rowCount() > 0){
                while($cart_item = $cart_q->fetch(PDO::FETCH_ASSOC)){
            ?>
            <div class="flex gap-4 p-3 border border-gray-100 rounded-xl hover:border-gray-300 transition-colors bg-white">
                <img src="uploaded_img/<?= $cart_item['image']; ?>" class="w-16 h-16 object-contain rounded-lg bg-gray-50">
                <div class="flex-1">
                    <h3 class="text-sm font-bold text-gray-900 line-clamp-1"><?= $cart_item['name']; ?></h3>
                    <div class="flex justify-between items-end mt-2">
                        <span class="text-sm font-black text-black">RM<?= $cart_item['price']; ?></span>
                        <span class="text-xs font-bold text-gray-400">Qty: <?= $cart_item['quantity']; ?></span>
                    </div>
                </div>
                <a href="cart.php?delete=<?= $cart_item['id']; ?>" class="text-red-400 hover:text-red-600 self-center px-2"><i class="fas fa-trash"></i></a>
            </div>
            <?php } } else { echo '<div class="text-center py-10 text-gray-400 font-bold text-sm">Your cart is empty.</div>'; } ?>
        </div>
        <div class="p-5 border-t border-gray-100 bg-gray-50">
            <a href="cart.php" class="block w-full py-4 bg-black text-white text-center rounded-xl font-bold uppercase tracking-wider hover:bg-gray-900 transition-transform hover:-translate-y-1 shadow-lg">
                View Full Cart
            </a>
        </div>
    </div>
</div>

<div class="dashboard-grid">
    
    <div class="sidebar">
        <div class="sidebar-header">
            <div class="flex justify-between items-center mb-4">
                <h2 class="text-lg font-black uppercase tracking-tight text-slate-900">Support Inbox</h2>
                <a href="contact.php" class="w-8 h-8 rounded-full bg-slate-900 text-white flex items-center justify-center hover:bg-black transition-colors shadow-md">
                    <i class="fas fa-edit text-xs"></i>
                </a>
            </div>
            <a href="contact.php" class="block w-full py-3 bg-blue-50 text-blue-600 border border-blue-100 text-center rounded-xl text-xs font-black uppercase tracking-wider hover:bg-blue-100 transition-colors">
                <i class="fas fa-plus mr-1"></i> New Inquiry
            </a>
        </div>

        <div class="ticket-list">
            <?php if(empty($my_tickets)): ?>
                <div class="p-10 text-center opacity-50 mt-10">
                    <i class="far fa-comments text-4xl mb-3 text-slate-300"></i>
                    <p class="text-xs font-bold uppercase text-slate-400">No active chats</p>
                </div>
            <?php else: foreach($my_tickets as $t): 
                $isActive = ($active_ticket == $t['ticket_id']) ? 'active' : '';
                $preview = mb_strimwidth($t['message'], 0, 30, '...');
            ?>
                <a href="contact.php?view_ticket=<?= $t['ticket_id'] ?>" class="ticket-item <?= $isActive ?>">
                    <div class="t-avatar">
                        <?php if($t['order_id']): ?><i class="fas fa-box text-orange-400"></i><?php else: ?><i class="fas fa-user text-slate-400"></i><?php endif; ?>
                    </div>
                    <div class="flex-1 min-w-0">
                        <div class="flex justify-between mb-1">
                            <span class="text-xs font-bold text-slate-900 truncate"><?= $t['order_id'] ? 'Order #'.$t['order_id'] : 'Inquiry #'.$t['ticket_id'] ?></span>
                            <span class="text-[9px] font-bold text-slate-400"><?= date('d M', strtotime($t['created_at'])) ?></span>
                        </div>
                        <p class="text-xs text-slate-500 truncate"><?= htmlspecialchars($preview) ?></p>
                    </div>
                </a>
            <?php endforeach; endif; ?>
        </div>
    </div>

    <div class="main-area">
        <?php if($active_ticket): 
            $convo = $conn->prepare("SELECT * FROM message WHERE ticket_id = ? ORDER BY id ASC");
            $convo->execute([$active_ticket]);
        ?>
            <div class="chat-header">
                <div class="flex items-center gap-3">
                    <a href="contact.php" class="lg:hidden w-8 h-8 flex items-center justify-center rounded-full bg-white border border-slate-200 text-slate-500"><i class="fas fa-chevron-left"></i></a>
                    <div>
                        <h3 class="text-sm font-black uppercase tracking-wide text-slate-900">Ticket #<?= $active_ticket ?></h3>
                        <p class="text-[10px] font-bold text-green-600 flex items-center gap-1.5"><span class="w-1.5 h-1.5 bg-green-500 rounded-full"></span> Online</p>
                    </div>
                </div>
            </div>

            <div class="chat-feed" id="chatFeed">
                <?php while($msg = $convo->fetch(PDO::FETCH_ASSOC)): 
                    $is_me = ($msg['name'] !== 'Admin Support'); 
                ?>
                    <div class="msg-row <?= $is_me ? 'me' : 'support' ?>">
                        <div class="msg-bubble">
                            <?php if(!empty($msg['image'])): ?>
                                <img src="uploaded_img/<?= $msg['image'] ?>" class="max-w-[200px] rounded-lg mb-2 border border-white/20 cursor-pointer" onclick="window.open(this.src)">
                            <?php endif; ?>
                            <span class="whitespace-pre-wrap"><?= htmlspecialchars($msg['message']) ?></span>
                            <span class="msg-meta text-slate-400"><?= date('H:i', strtotime($msg['created_at'])) ?></span>
                        </div>
                    </div>
                <?php endwhile; ?>
            </div>

            <div class="input-area">
                <div id="reply-preview-container" class="hidden mb-2 relative w-fit">
                    <img id="reply-preview" class="h-16 rounded-lg border border-gray-200 object-cover">
                    <button type="button" onclick="clearPreview('reply-preview-container', 'reply-input')" class="absolute -top-2 -right-2 bg-red-500 text-white rounded-full w-5 h-5 flex items-center justify-center text-[10px]"><i class="fas fa-times"></i></button>
                </div>

                <form action="" method="POST" enctype="multipart/form-data" class="input-wrapper">
                    <input type="hidden" name="ticket_id" value="<?= $active_ticket ?>">
                    <label class="p-2 cursor-pointer text-slate-400 hover:text-blue-500 transition-colors">
                        <i class="fas fa-paperclip text-lg"></i>
                        <input type="file" name="image" id="reply-input" class="hidden" onchange="previewImage(this, 'reply-preview', 'reply-preview-container')">
                    </label>
                    <input type="text" name="message" class="chat-input" placeholder="Type your message..." required autocomplete="off">
                    <button type="submit" name="reply_ticket" class="w-9 h-9 bg-black text-white rounded-full flex items-center justify-center hover:bg-slate-800 transition-colors">
                        <i class="fas fa-arrow-up text-xs"></i>
                    </button>
                </form>
            </div>

        <?php else: ?>
            
            <div class="flex-1 overflow-y-auto flex items-center justify-center">
                <div class="create-container w-full">
                    <div class="text-center mb-6">
                        <h2 class="text-2xl font-black uppercase tracking-tight text-slate-900">New Inquiry</h2>
                        <p class="text-xs text-slate-400 mt-1 font-bold uppercase">We're here to help.</p>
                    </div>
                    <?php $u = $conn->query("SELECT * FROM users WHERE id = $user_id")->fetch(PDO::FETCH_ASSOC); ?>
                    <form action="" method="POST" enctype="multipart/form-data">
                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <label class="text-[10px] font-bold text-slate-400 uppercase ml-1">Name</label>
                                <input type="text" name="name" class="ag-input text-slate-500 cursor-not-allowed" value="<?= $u['name'] ?>" readonly>
                            </div>
                            <div>
                                <label class="text-[10px] font-bold text-slate-400 uppercase ml-1">Email</label>
                                <input type="email" name="email" class="ag-input text-slate-500 cursor-not-allowed" value="<?= $u['email'] ?>" readonly>
                            </div>
                        </div>
                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <label class="text-[10px] font-bold text-slate-400 uppercase ml-1">Phone</label>
                                <input type="number" name="number" class="ag-input" value="<?= $u['number'] ?? '' ?>" required>
                            </div>
                            <div>
                                <label class="text-[10px] font-bold text-slate-400 uppercase ml-1">Order Ref (Optional)</label>
                                <input type="text" name="order_id" class="ag-input" value="<?= $prefill_order_id ?>" <?= $prefill_order_id ? 'readonly' : '' ?>>
                            </div>
                        </div>
                        <div>
                            <label class="text-[10px] font-bold text-slate-400 uppercase ml-1">Message</label>
                            <textarea name="message" class="ag-textarea h-32" placeholder="Describe your issue..." required></textarea>
                        </div>
                        <label class="block w-full border border-dashed border-slate-300 rounded-xl p-3 text-center cursor-pointer hover:bg-slate-50 transition-colors mb-6">
                            <i class="fas fa-camera text-lg text-slate-400 mr-2"></i>
                            <span class="text-xs font-bold text-slate-500 uppercase">Attach Proof</span>
                            
                            <input type="file" name="image" class="hidden" onchange="previewImage(this, 'new-ticket-preview')">
                            <img id="new-ticket-preview" class="hidden mt-3 h-32 w-full object-contain mx-auto rounded-lg border border-gray-100">
                        </label>
                        <button type="submit" name="create_ticket" class="w-full bg-black text-white py-3.5 rounded-xl font-bold uppercase tracking-widest hover:bg-slate-800 transition-transform hover:-translate-y-0.5 shadow-lg text-xs">
                            Submit Inquiry
                        </button>
                    </form>
                </div>
            </div>
        <?php endif; ?>
    </div>
</div>

<script src="js/script.js"></script>

<script>
    // JS: IMAGE PREVIEW LOGIC
    function previewImage(input, imgId, containerId = null) {
        const file = input.files[0];
        if (file) {
            const reader = new FileReader();
            reader.onload = function(e) {
                document.getElementById(imgId).src = e.target.result;
                document.getElementById(imgId).classList.remove('hidden');
                if(containerId) document.getElementById(containerId).classList.remove('hidden');
            }
            reader.readAsDataURL(file);
        }
    }

    function clearPreview(containerId, inputId) {
        document.getElementById(containerId).classList.add('hidden');
        document.getElementById(inputId).value = '';
    }

    // JS: CART DRAWER
    function toggleCartDrawer() {
        const drawer = document.getElementById('cart-drawer');
        const panel = document.getElementById('cart-panel');
        if (drawer.classList.contains('hidden')) {
            drawer.classList.remove('hidden');
            setTimeout(() => { panel.classList.remove('translate-x-full'); }, 10);
        } else {
            panel.classList.add('translate-x-full');
            setTimeout(() => { drawer.classList.add('hidden'); }, 300);
        }
    }

    // JS: REAL-TIME REFRESH + SMART SCROLL
    const chatFeed = document.getElementById('chatFeed');
    const activeTicket = "<?= $active_ticket ?>";

    function fetchMessages() {
        if(!activeTicket) return;
        fetch(`fetch_messages.php?ticket_id=${activeTicket}`)
            .then(res => res.text())
            .then(html => {
                // Only update DOM if content CHANGED
                if (chatFeed.innerHTML !== html) {
                    const isAtBottom = chatFeed.scrollHeight - chatFeed.scrollTop <= chatFeed.clientHeight + 150;
                    chatFeed.innerHTML = html;
                    if(isAtBottom) {
                        chatFeed.scrollTo({ top: chatFeed.scrollHeight, behavior: 'smooth' });
                    }
                }
            });
    }

    if(chatFeed) {
        chatFeed.scrollTop = chatFeed.scrollHeight;
        if(activeTicket) { setInterval(fetchMessages, 3000); }
    }
</script>

</body>
</html>