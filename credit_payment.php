<?php

@include 'config.php';

session_start();

$user_id = $_SESSION['user_id'];

if(!isset($user_id)){
   header('location:login.php');
};

   
   $name = $_POST['name'];
   $number = $_POST['number'];
   $email = $_POST['email'];
   $method = $_POST['method'];
   $address = $_POST['address'];
   $placed_on = $_POST['placed_on'];

   $cart_total = 0;

   $cart_query = $conn->prepare("SELECT * FROM `cart` WHERE user_id = ?");
   $cart_query->execute([$user_id]);
   if($cart_query->rowCount() > 0){
      while($cart_item = $cart_query->fetch(PDO::FETCH_ASSOC)){
         $cart_products[] = $cart_item['name'].' ('.$cart_item['pid'].')';
         $sub_total = ($cart_item['price'] * $cart_item['quantity']);
         $cart_total += $sub_total;
      };
   };

   $total_products = implode(', ', $cart_products);



   // try server-side geocoding for card payments as well
   $latitude = null;
   $longitude = null;
   if (file_exists(__DIR__ . '/geocode.php')) {
       include_once __DIR__ . '/geocode.php';
       $coords = geocode_address_nominatim($address);
       if ($coords && isset($coords['lat']) && isset($coords['lng'])) {
           $latitude = $coords['lat'];
           $longitude = $coords['lng'];
       }
   }

   if($cart_total == 0){
      $message[] = 'Your Cart is Empty';
      echo 'total';
   }else{
      $insert_order = $conn->prepare("INSERT INTO `orders`(user_id, name, number, email, method, address, latitude, longitude, total_products, total_price, placed_on, order_type, payment_status, delivery_status) VALUES(?,?,?,?,?,?,?,?,?,?, 1, 'Completed', 'Preparing Order')");
      $insert_order->execute([$user_id, $name, $number, $email, $method, $address, $latitude, $longitude, $total_products, $cart_total, $placed_on]);
      $delete_cart = $conn->prepare("DELETE FROM `cart` WHERE user_id = ?");
      $delete_cart->execute([$user_id]);

      $select_point = $conn->prepare("SELECT `point` FROM `users` WHERE id = ?");
      $select_point->execute([$user_id]);
      $user_point = $select_point->fetch(PDO::FETCH_ASSOC);

      $new_point = $user_point['point'] + $cart_total;


      $update_point = $conn->prepare("UPDATE `users` SET point = ? WHERE id = ?");
      $update_point->execute([$new_point, $user_id]);

      $message[] = 'Order Placed Successfully!';
      header('Location: receipt.php');
exit;

   }
?>