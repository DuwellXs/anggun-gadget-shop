<?php
@include 'config.php';
session_start();

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

$admin_id = $_SESSION['admin_id'];
if(!isset($admin_id)){ header('location:login.php'); exit(); };

// FIX: Ensure upload folder exists
if (!is_dir('uploaded_img')) { mkdir('uploaded_img', 0777, true); }

// --- 1. HANDLE ADMIN REPLY ---
if(isset($_POST['send_reply'])){
    try {
        $ticket_id = $_POST['ticket_id'];
        $user_id_target = $_POST['user_id']; 
        $msg = filter_var($_POST['message'], FILTER_SANITIZE_STRING);
        
        $image = $_FILES['image']['name'] ?? '';
        if(!empty($image)){
            $image = filter_var($image, FILTER_SANITIZE_STRING);
            move_uploaded_file($_FILES['image']['tmp_name'], 'uploaded_img/'.$image);
        }

        $insert = $conn->prepare("INSERT INTO `message`(user_id, ticket_id, name, email, number, message, image, order_id) VALUES(?,?,?,?,?,?,?,?)");
        $insert->execute([$user_id_target, $ticket_id, 'Admin Support', 'admin@support.com', '', $msg, $image, 0]);
        
        header("Location: admin_contacts.php?view_ticket=$ticket_id");
        exit();
    } catch(PDOException $e) {
        die("Error sending reply: " . $e->getMessage());
    }
}

// --- 2. DELETE TICKET ---
if(isset($_GET['delete_ticket'])){
    $tid = $_GET['delete_ticket'];
    $conn->prepare("DELETE FROM `message` WHERE ticket_id = ?")->execute([$tid]);
    header('location:admin_contacts.php');
    exit();
}

// --- 3. FETCH DATA ---
$active_ticket = $_GET['view_ticket'] ?? null;

$tickets_query = $conn->prepare("SELECT * FROM message WHERE ticket_id != 0 ORDER BY id DESC");
$tickets_query->execute();
$all_msgs = $tickets_query->fetchAll(PDO::FETCH_ASSOC);

$all_tickets = [];
$seen = [];
foreach($all_msgs as $m){
    if(!in_array($m['ticket_id'], $seen)){
        $seen[] = $m['ticket_id'];
        $all_tickets[] = $m; 
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
   <meta charset="UTF-8">
   <meta name="viewport" content="width=device-width, initial-scale=1.0">
   <title>Help Desk | Admin Panel</title>
   
   <script src="https://cdn.tailwindcss.com"></script>
   <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800;900&display=swap" rel="stylesheet">
   <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
   
   <style>
       html, body {
           margin: 0; padding: 0;
           height: 100vh; 
           overflow: hidden !important;
           font-family: 'Inter', sans-serif;
           background-color: #ffffff !important;
           color: #000;
       }

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
           gap: 40px;
           padding: 0 30px;
       }

       .sidebar-area {
           height: 100%;
           overflow-y: auto;
           padding-top: 40px;
           scrollbar-width: none; 
       }
       .sidebar-area::-webkit-scrollbar { display: none; }

       .main-content {
           height: 100%;
           display: flex;
           flex-direction: column;
           padding-top: 40px;
           padding-bottom: 0px; 
           overflow: hidden;
       }

       .chat-interface-card {
           flex: 1; 
           background: #fff; 
           border-radius: 20px 20px 0 0; 
           border: 1px solid #f3f4f6; 
           border-bottom: none;
           box-shadow: 0 -10px 40px -10px rgba(0,0,0,0.05);
           display: flex; 
           overflow: hidden;
           margin-top: 20px;
           position: relative;
       }

       .inbox-col {
           width: 320px; 
           flex-shrink: 0; 
           background: #fcfcfc;
           border-right: 1px solid #f3f4f6;
           display: flex;
           flex-direction: column;
           overflow: hidden;
           z-index: 5;
       }
       .inbox-header {
           padding: 20px 25px;
           border-bottom: 1px solid #f3f4f6;
           background: #fcfcfc;
           flex-shrink: 0;
       }
       .inbox-list {
           flex: 1;
           overflow-y: auto;
       }
       
       .ticket-item {
           display: block; 
           width: 100%;
           padding: 18px 25px;
           border-bottom: 1px solid #f8fafc;
           cursor: pointer;
           transition: background 0.2s ease;
           background: #fff;
           position: relative;
           text-decoration: none;
       }
       .ticket-item:hover { background: #f8fafc; }
       .ticket-item.active { 
           background: #f0f9ff; 
           border-left: 4px solid #000;
       }

       .chat-col {
           flex: 1; 
           min-width: 0; 
           background: #fff;
           display: flex;
           flex-direction: column;
           position: relative;
       }
       .chat-header {
           padding: 15px 30px;
           border-bottom: 1px solid #f3f4f6;
           display: flex; justify-content: space-between; align-items: center;
           height: 70px;
           background: #fff;
           z-index: 10;
           flex-shrink: 0;
       }
       .chat-feed {
           flex: 1;
           overflow-y: scroll;
           padding: 30px;
           display: flex;
           flex-direction: column;
           gap: 15px;
           background: #ffffff;
       }
       
       .msg-row { display: flex; width: 100%; }
       .msg-row.admin { justify-content: flex-end; }
       
       .msg-bubble {
           max-width: 75%;
           padding: 12px 18px;
           border-radius: 16px;
           font-size: 0.9rem;
           line-height: 1.5;
           font-weight: 500;
           box-shadow: 0 2px 4px rgba(0,0,0,0.02);
           word-wrap: break-word;
       }
       .msg-row.admin .msg-bubble { 
           background: #000; color: #fff; 
           border-bottom-right-radius: 2px;
       }
       .msg-row.user .msg-bubble { 
           background: #f3f4f6; color: #1f2937; 
           border-bottom-left-radius: 2px;
       }

       .input-box {
           padding: 20px 30px;
           border-top: 1px solid #f3f4f6;
           background: #fff;
           margin-bottom: 20px; 
           flex-shrink: 0;
       }

       ::-webkit-scrollbar { width: 5px; }
       ::-webkit-scrollbar-thumb { background: #e2e8f0; border-radius: 4px; }
       ::-webkit-scrollbar-track { background: transparent; }

       @media (max-width: 1024px) {
           .content-container { grid-template-columns: 1fr; padding: 0; }
           .sidebar-area { display: none; } 
           .chat-interface-card { border-radius: 0; border: none; margin-top: 0; }
           .inbox-col { display: <?= $active_ticket ? 'none' : 'flex' ?>; width: 100%; }
           .chat-col { display: <?= $active_ticket ? 'flex' : 'none' ?>; width: 100%; }
           .main-content { padding-top: 0; }
       }
   </style>
</head>
<body>

<div class="master-scroll-wrapper">
    <div class="content-container">
        
        <div class="sidebar-area">
            <?php include 'admin_header.php'; ?>
        </div>

        <div class="main-content">
            
            <div class="flex justify-between items-end mb-4 px-4 lg:px-0 flex-shrink-0">
                <div>
                    <h1 class="text-4xl font-black text-black uppercase tracking-tighter">Help Desk</h1>
                    <p class="text-xs font-bold text-gray-400 tracking-widest uppercase mt-1">Customer Support Center</p>
                </div>
                <div class="hidden md:flex items-center gap-2 bg-green-50 px-3 py-1.5 rounded-lg border border-green-100">
                    <span class="relative flex h-2 w-2">
                      <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-green-400 opacity-75"></span>
                      <span class="relative inline-flex rounded-full h-2 w-2 bg-green-500"></span>
                    </span>
                    <span class="text-[10px] font-black text-green-600 uppercase tracking-wider">
                        <?= count($all_tickets) ?> Tickets Active
                    </span>
                </div>
            </div>

            <div class="chat-interface-card">
                
                <div class="inbox-col">
                    <div class="inbox-header">
                        <h2 class="text-[10px] font-black uppercase text-gray-400 tracking-widest">Recent Inquiries</h2>
                    </div>
                    
                    <div class="inbox-list">
                        <?php if(empty($all_tickets)): ?>
                            <div class="flex flex-col items-center justify-center h-full text-gray-300 py-10">
                                <i class="fas fa-inbox text-3xl mb-2"></i>
                                <p class="text-xs font-bold uppercase mt-2">No Tickets Found</p>
                            </div>
                        <?php else: foreach($all_tickets as $t): 
                            $isActive = ($active_ticket == $t['ticket_id']) ? 'active' : '';
                            $preview = mb_strimwidth($t['message'], 0, 30, '...');
                            $time = date('M d', strtotime($t['created_at'] ?? 'now'));
                            $isReturn = ($t['order_id'] && $t['order_id'] != 0);
                        ?>
                            <a href="admin_contacts.php?view_ticket=<?= $t['ticket_id'] ?>" class="ticket-item <?= $isActive ?>">
                                
                                <div class="flex justify-between items-center w-full mb-1">
                                    <div class="font-black text-sm text-gray-900 uppercase truncate flex-1 min-w-0 pr-2">
                                        <?= htmlspecialchars($t['name']) ?>
                                    </div>
                                    <div class="text-[9px] font-bold text-gray-400 whitespace-nowrap flex-shrink-0">
                                        <?= $time ?>
                                    </div>
                                </div>
                                
                                <?php if($isReturn): ?>
                                    <div class="inline-flex items-center gap-1 bg-orange-50 text-orange-600 border border-orange-100 px-2 py-0.5 rounded mb-1.5 w-fit">
                                        <i class="fas fa-box text-[9px]"></i>
                                        <span class="text-[9px] font-bold uppercase">Return #<?= $t['order_id'] ?></span>
                                    </div>
                                <?php endif; ?>
                                
                                <p class="text-xs font-medium text-gray-500 truncate w-full">
                                    <?= htmlspecialchars($preview) ?>
                                </p>
                            </a>
                        <?php endforeach; endif; ?>
                    </div>
                </div>

                <div class="chat-col">
                    <?php if($active_ticket): 
                        $convo = $conn->prepare("SELECT * FROM message WHERE ticket_id = ? ORDER BY id ASC");
                        $convo->execute([$active_ticket]);
                        $msgs = $convo->fetchAll(PDO::FETCH_ASSOC);
                        
                        $first_msg = $msgs[0] ?? [];
                        $customer_name = $first_msg['name'] ?? 'Customer';
                        $customer_id = $first_msg['user_id'] ?? 0;
                    ?>
                        <div class="chat-header">
                            <div class="flex items-center gap-4">
                                <a href="admin_contacts.php" class="lg:hidden w-8 h-8 flex items-center justify-center rounded-full bg-gray-100 text-gray-600">
                                    <i class="fas fa-arrow-left"></i>
                                </a>
                                <div>
                                    <h3 class="text-lg font-black uppercase tracking-tight text-gray-900 leading-none"><?= $customer_name ?></h3>
                                    <span class="text-[10px] font-bold text-gray-400 uppercase tracking-wide">Ticket #<?= $active_ticket ?></span>
                                </div>
                            </div>
                            <a href="admin_contacts.php?delete_ticket=<?= $active_ticket ?>" onclick="return confirm('Archive this ticket permanently?')" class="w-8 h-8 flex items-center justify-center rounded bg-red-50 text-red-500 hover:bg-red-500 hover:text-white transition-colors" title="Delete Ticket">
                                <i class="fas fa-trash text-xs"></i>
                            </a>
                        </div>

                        <div class="chat-feed" id="chatFeed">
                            <?php foreach($msgs as $msg): 
                                $isAdmin = ($msg['name'] === 'Admin Support');
                            ?>
                                <div class="msg-row <?= $isAdmin ? 'admin' : 'user' ?>">
                                    <div class="msg-bubble">
                                        
                                        <?php if($msg['order_id'] && !$isAdmin): ?>
                                            <div class="flex items-center gap-2 border-b border-gray-200/50 pb-2 mb-2">
                                                <span class="bg-orange-100 text-orange-600 p-1 rounded"><i class="fas fa-box text-xs"></i></span>
                                                <span class="text-[10px] font-black text-gray-400 uppercase tracking-wide">Order #<?= $msg['order_id'] ?></span>
                                            </div>
                                        <?php endif; ?>

                                        <?php if($msg['image']): ?>
                                            <img src="uploaded_img/<?= $msg['image'] ?>" class="max-w-[200px] rounded-lg mb-2 border border-gray-200 cursor-pointer" onclick="window.open(this.src)">
                                        <?php endif; ?>
                                        
                                        <span class="block"><?= nl2br(htmlspecialchars($msg['message'])) ?></span>
                                        
                                        <div class="mt-1 text-[9px] font-bold uppercase tracking-wide opacity-40 text-right">
                                            <?= date('H:i', strtotime($msg['created_at'] ?? 'now')) ?>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>

                        <div class="input-box">
                            <div id="reply-preview-container" class="hidden mb-2 relative w-fit">
                                <img id="reply-preview" class="h-16 rounded-lg border border-gray-200 object-cover">
                                <button type="button" onclick="clearPreview()" class="absolute -top-2 -right-2 bg-red-500 text-white rounded-full w-5 h-5 flex items-center justify-center text-[10px]"><i class="fas fa-times"></i></button>
                            </div>
                            
                            <form action="" method="POST" enctype="multipart/form-data" class="flex items-center gap-3 bg-f8fafc border border-gray-200 p-2 rounded-xl focus-within:border-black focus-within:bg-white transition-all">
                                <input type="hidden" name="ticket_id" value="<?= $active_ticket ?>">
                                <input type="hidden" name="user_id" value="<?= $customer_id ?>">
                                
                                <label class="p-2 text-gray-400 hover:text-black cursor-pointer transition-colors" title="Attach Image">
                                    <i class="fas fa-paperclip text-lg"></i>
                                    <input type="file" name="image" id="reply-input" class="hidden" onchange="previewImage(this)">
                                </label>
                                
                                <input type="text" name="message" class="flex-1 bg-transparent outline-none text-sm font-bold text-gray-900 placeholder:text-gray-400 h-full py-2" placeholder="Type reply..." required autocomplete="off">
                                
                                <button type="submit" name="send_reply" class="bg-black text-white px-5 py-2 rounded-lg text-xs font-black uppercase tracking-wider hover:bg-gray-800 transition-transform hover:scale-105 shadow-md">
                                    Send
                                </button>
                            </form>
                        </div>

                    <?php else: ?>
                        
                        <div class="flex flex-col items-center justify-center h-full text-gray-300">
                            <div class="w-20 h-20 bg-gray-50 rounded-full flex items-center justify-center mb-4">
                                <i class="fas fa-comments text-3xl text-gray-300"></i>
                            </div>
                            <h3 class="text-lg font-black uppercase text-gray-400 mb-1 tracking-tight">Select a Ticket</h3>
                            <p class="text-[10px] font-bold uppercase tracking-widest text-gray-300">View conversation details here</p>
                        </div>

                    <?php endif; ?>
                </div>

            </div>
        </div>
    </div>
</div>

<script>
    const chatFeed = document.getElementById('chatFeed');
    const activeTicket = "<?= $active_ticket ?>";

    // JS: PREVIEW
    function previewImage(input) {
        const file = input.files[0];
        if (file) {
            const reader = new FileReader();
            reader.onload = function(e) {
                document.getElementById('reply-preview').src = e.target.result;
                document.getElementById('reply-preview-container').classList.remove('hidden');
            }
            reader.readAsDataURL(file);
        }
    }

    function clearPreview() {
        document.getElementById('reply-preview-container').classList.add('hidden');
        document.getElementById('reply-input').value = '';
    }

    // JS: REAL-TIME REFRESH
    function fetchAdminMessages() {
        if(!activeTicket) return;
        fetch(`fetch_messages.php?ticket_id=${activeTicket}&is_admin=1`)
            .then(res => res.text())
            .then(html => {
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
        if(activeTicket) { setInterval(fetchAdminMessages, 3000); }
    }
</script>

</body>
</html>