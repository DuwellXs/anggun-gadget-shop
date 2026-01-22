<?php
@include 'config.php';
session_start();
$admin_id = $_SESSION['admin_id'];
if(!isset($admin_id)){
   header('location:login.php');
}

// Get the user ID from the URL, but make sure it's a number
$user_id_to_view = isset($_GET['user_id']) ? (int)$_GET['user_id'] : 0;

if($user_id_to_view === 0){
    echo 'No user selected.';
    exit;
}

// Get the details of the user we are viewing
$get_user_info = $conn->prepare("SELECT name, email FROM users WHERE id = ?");
$get_user_info->execute([$user_id_to_view]);
$user_info = $get_user_info->fetch(PDO::FETCH_ASSOC);

?>
<!DOCTYPE html>
<html lang="en">
<head>
   <meta charset="UTF-8">
   <meta http-equiv="X-UA-Compatible" content="IE=edge">
   <meta name="viewport" content="width=device-width, initial-scale=1.0">
   <title>View Conversation</title>
   <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.1.1/css/all.min.css">
   <link rel="stylesheet" href="css/admin_style.css">
   <style>
      .chat-log {
         max-width: 800px;
         margin: 20px auto;
         padding: 20px;
         background: #f9f9f9;
         border: 1px solid #ddd;
         border-radius: 5px;
      }
      .message-bubble {
         background: white;
         padding: 15px;
         margin-bottom: 15px;
         border-radius: 5px;
         border-left: 5px solid #4a90e2;
      }
      .message-bubble p {
         margin: 0 0 10px 0;
         font-size: 1.1em;
      }
      .message-bubble small {
         color: #777;
         font-style: italic;
      }
   </style>
</head>
<body>
   
<?php include 'admin_header.php'; ?>

<section class="messages">
   <h1 class="title">Chat with <?= $user_info['name'] ?? 'User' ?></h1>
   
   <div class="chat-log">
   <?php
      // Now, get all messages just from this specific user
      $select_messages = $conn->prepare("SELECT * FROM `message` WHERE user_id = ? ORDER BY created_at ASC");
      $select_messages->execute([$user_id_to_view]);
      if($select_messages->rowCount() > 0){
         while($fetch_message = $select_messages->fetch(PDO::FETCH_ASSOC)){
   ?>
      <div class="message-bubble">
         <p><?= htmlspecialchars($fetch_message['message']); ?></p>
         <small>Sent on: <?= $fetch_message['created_at']; ?></small>
      </div>
   <?php
         }
      }else{
         echo '<p class="empty">No messages in this conversation.</p>';
      }
   ?>
   </div>
</section>

<script src="js/script.js"></script>
</body>
</html>