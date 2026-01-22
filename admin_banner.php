<?php
@include 'config.php';
session_start();

$admin_id = $_SESSION['admin_id'];

if(!isset($admin_id)){
   header('location:login.php');
   exit;
};

// FIX 1: Ensure upload folder exists
if (!is_dir('uploaded_img')) { mkdir('uploaded_img', 0777, true); }

// 1. HANDLE ADDING BANNER
if(isset($_POST['add_banner'])){

   $name = filter_var($_POST['title'], FILTER_SANITIZE_STRING);
   $details = filter_var($_POST['subtitle'], FILTER_SANITIZE_STRING);
   $price = 0; 
   $category = 'Banner'; 

   $select_banner = $conn->prepare("SELECT * FROM `products` WHERE name = ? AND category = 'Banner'");
   $select_banner->execute([$name]);

   if($select_banner->rowCount() > 0){
      $message[] = 'A banner with this title already exists!';
   }else{
      
      // FIX 2: Handle Base64 Image
      if(isset($_POST['cropped_image']) && !empty($_POST['cropped_image'])){
         
         $data = $_POST['cropped_image'];
         
         // Securely parse Base64 (Supports PNG and JPEG)
         if (preg_match('/^data:image\/(\w+);base64,/', $data, $type)) {
             $data = substr($data, strpos($data, ',') + 1);
             $type = strtolower($type[1]); // jpg, png, gif
             $data = base64_decode($data);
             
             if ($data === false) {
                 $message[] = 'Base64 decode failed!';
             } else {
                 // Force extension to .jpg for consistency
                 $image_name = 'banner_' . time() . '.jpg';
                 $image_folder = 'uploaded_img/' . $image_name;
                 
                 // Save File
                 if(file_put_contents($image_folder, $data)){
                     $insert_banner = $conn->prepare("INSERT INTO `products`(name, details, price, category, image) VALUES(?,?,?,?,?)");
                     $insert_banner->execute([$name, $details, $price, $category, $image_name]);
                     $message[] = 'New banner published successfully!';
                 } else {
                     $message[] = 'Failed to save image file. Check folder permissions.';
                 }
             }
         } else {
             $message[] = 'Invalid image data format.';
         }

      } else {
         $message[] = 'Please select and crop an image before publishing.';
      }
   }
}

// 2. HANDLE DELETING BANNER
if(isset($_GET['delete'])){
   $delete_id = $_GET['delete'];
   $select_delete_image = $conn->prepare("SELECT image FROM `products` WHERE id = ?");
   $select_delete_image->execute([$delete_id]);
   $fetch_delete_image = $select_delete_image->fetch(PDO::FETCH_ASSOC);
   
   if($fetch_delete_image){
       if(file_exists('uploaded_img/'.$fetch_delete_image['image'])){
           unlink('uploaded_img/'.$fetch_delete_image['image']);
       }
   }
   
   $delete_banner = $conn->prepare("DELETE FROM `products` WHERE id = ?");
   $delete_banner->execute([$delete_id]);
   header('location:admin_banner.php');
}

// FETCH BANNERS
$select_banners = $conn->prepare("SELECT * FROM `products` WHERE category = 'Banner'");
$select_banners->execute();
$banner_count = $select_banners->rowCount();
?>

<!DOCTYPE html>
<html lang="en">
<head>
   <meta charset="UTF-8">
   <meta name="viewport" content="width=device-width, initial-scale=1.0">
   <title>Banners | Admin Panel</title>

   <script src="https://cdn.tailwindcss.com"></script>
   <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800;900&display=swap" rel="stylesheet">
   <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
   <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/cropperjs/1.5.13/cropper.min.css"/>

   <style>
      html, body { margin: 0; padding: 0; height: 100vh; overflow: hidden !important; font-family: 'Inter', sans-serif; background-color: #ffffff !important; color: #000; }
      .master-scroll-wrapper { height: 100vh; width: 100%; display: flex; justify-content: center; background-color: #fff; }
      .content-container { width: 100%; max-width: 1400px; height: 100%; display: grid; grid-template-columns: 280px minmax(0, 1fr); gap: 60px; padding: 0 30px; }
      .sidebar-scroll { height: 100%; overflow-y: auto; padding-top: 40px; padding-bottom: 40px; scrollbar-width: none; }
      .sidebar-scroll::-webkit-scrollbar { display: none; }
      .content-scroll { height: 100%; overflow-y: auto; padding-top: 40px; padding-bottom: 100px; padding-right: 5px; scrollbar-width: none; }
      .content-scroll::-webkit-scrollbar { display: none; }

      /* Message Toast Style */
      .message { position: fixed; top: 20px; right: 20px; background: #333; color: #fff; padding: 12px 20px; border-radius: 8px; z-index: 10000; display: flex; align-items: center; gap: 10px; animation: slideIn 0.3s ease; box-shadow: 0 5px 15px rgba(0,0,0,0.2); }
      .message i { cursor: pointer; color: #aaa; }
      .message i:hover { color: #fff; }
      @keyframes slideIn { from { transform: translateX(100%); opacity: 0; } to { transform: translateX(0); opacity: 1; } }

      .editor-card { background: #fff; border-radius: 12px; border: 1px solid #f3f4f6; box-shadow: 0 4px 6px -1px rgba(0,0,0,0.02); padding: 30px; margin-bottom: 40px; }
      .ag-label { font-size: 0.7rem; font-weight: 800; color: #94a3b8; text-transform: uppercase; letter-spacing: 1px; margin-bottom: 8px; display: block; }
      .ag-input { width: 100%; background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 12px; padding: 14px 16px; font-size: 0.9rem; font-weight: 600; color: #0f172a; transition: 0.2s; outline: none; }
      .ag-input:focus { background: #fff; border-color: #000; }
      
      .btn-black { background: #000; color: #fff; padding: 14px 30px; border-radius: 12px; font-weight: 700; text-transform: uppercase; letter-spacing: 1px; font-size: 0.8rem; cursor: pointer; border: none; transition: 0.2s; display: inline-block; }
      .btn-black:hover { background: #333; transform: translateY(-2px); }
      .btn-black:disabled { background: #ccc; cursor: not-allowed; transform: none; }

      .banner-grid { display: grid; grid-template-columns: 1fr; gap: 30px; }
      .banner-item { position: relative; border-radius: 20px; overflow: hidden; border: 1px solid #f3f4f6; box-shadow: 0 10px 30px -10px rgba(0,0,0,0.05); background: #fff; transition: transform 0.2s; }
      .banner-item:hover { transform: translateY(-3px); }
      .banner-img { width: 100%; height: 280px; object-fit: cover; display: block; }
      .banner-overlay { position: absolute; bottom: 0; left: 0; right: 0; background: linear-gradient(to top, rgba(0,0,0,0.9), transparent); padding: 30px; color: #fff; display: flex; justify-content: space-between; align-items: flex-end; }
      .banner-title { font-size: 1.5rem; font-weight: 900; text-transform: uppercase; letter-spacing: -1px; line-height: 1; }
      .banner-sub { font-size: 0.8rem; font-weight: 600; opacity: 0.8; margin-bottom: 5px; text-transform: uppercase; letter-spacing: 1px; }
      
      .btn-delete { background: rgba(255, 255, 255, 0.1); backdrop-filter: blur(10px); width: 44px; height: 44px; border-radius: 12px; display: flex; align-items: center; justify-content: center; color: #fff; transition: 0.2s; border: 1px solid rgba(255,255,255,0.2); }
      .btn-delete:hover { background: #ef4444; border-color: #ef4444; }

      /* CROPPER MODAL */
      .crop-modal { position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0, 0, 0, 0.85); backdrop-filter: blur(5px); z-index: 1000; display: none; align-items: center; justify-content: center; }
      .crop-box { background: #111; padding: 20px; border-radius: 20px; width: 90%; max-width: 800px; box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.5); }
      .img-container { max-height: 500px; overflow: hidden; background: #000; border-radius: 10px; }
      .img-container img { max-width: 100%; display: block; }
      .modal-actions { display: flex; justify-content: flex-end; gap: 15px; margin-top: 20px; }
      .btn-modal-cancel { color: #aaa; padding: 10px 20px; cursor: pointer; font-weight: 600; font-size: 0.9rem; }
      .btn-modal-save { background: #fff; color: #000; padding: 10px 25px; border-radius: 8px; font-weight: 800; text-transform: uppercase; font-size: 0.8rem; }

      .preview-box { margin-top: 20px; border: 2px dashed #e2e8f0; border-radius: 12px; padding: 10px; display: none; }
      .preview-box img { width: 100%; border-radius: 8px; }

      @media (max-width: 1024px) { .content-container { grid-template-columns: 1fr; } .sidebar-scroll { display: none; } }
   </style>
</head>
<body>

<?php
if(isset($message)){
   foreach($message as $msg){
      echo '
      <div class="message">
         <span>'.$msg.'</span>
         <i class="fas fa-times" onclick="this.parentElement.remove();"></i>
      </div>
      ';
   }
}
?>

<div class="master-scroll-wrapper">
   <div class="content-container">
      <div class="sidebar-scroll"><?php include 'admin_header.php'; ?></div>
      <div class="content-scroll">
         
         <div class="mb-12">
            <h1 class="text-4xl font-black text-black uppercase tracking-tighter">Banners</h1>
            <div class="flex justify-between items-end mt-2">
               <p class="text-sm font-bold text-gray-400 tracking-wide uppercase">Homepage Hero Management</p>
               <div class="flex items-center gap-2 bg-green-50 px-3 py-1.5 rounded-lg border border-green-100">
                  <span class="text-[10px] font-black text-green-600 uppercase tracking-wider"><?= $banner_count ?> Active</span>
               </div>
            </div>
         </div>

         <div class="editor-card">
             <div class="flex justify-between items-center mb-6 border-b border-gray-100 pb-6">
                 <h2 class="text-lg font-black text-slate-900 uppercase">New Banner</h2>
                 <span class="text-[10px] font-bold text-gray-400 uppercase tracking-widest bg-gray-50 px-2 py-1 rounded">Visual Editor</span>
             </div>

            <form action="" method="post" enctype="multipart/form-data" id="bannerForm" onsubmit="return validateForm()">
               <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
                  <div>
                     <label class="ag-label">Headline Title</label>
                     <input type="text" name="title" class="ag-input" placeholder="e.g. SUMMER SALE" required>
                  </div>
                  <div>
                     <label class="ag-label">Subtitle / Caption</label>
                     <input type="text" name="subtitle" class="ag-input" placeholder="e.g. Up to 50% Off All Items" required>
                  </div>
               </div>

               <div class="mb-6">
                  <label class="ag-label">Upload Hero Image (1920 x 1080 Required)</label>
                  <label class="flex flex-col items-center justify-center w-full h-32 border-2 border-gray-200 border-dashed rounded-xl cursor-pointer bg-gray-50 hover:bg-gray-100 transition-colors group">
                      <div class="flex flex-col items-center justify-center pt-5 pb-6">
                          <i class="fas fa-cloud-upload-alt text-3xl text-gray-300 group-hover:text-black mb-2 transition-colors"></i>
                          <p class="text-xs text-gray-400 font-bold uppercase group-hover:text-black transition-colors">Click to Upload</p>
                      </div>
                      <input type="file" class="hidden" id="imageToCrop" accept="image/*" />
                  </label>
               </div>

               <input type="hidden" name="cropped_image" id="croppedImageData">
               
               <div class="preview-box" id="previewContainer">
                   <p class="text-[10px] font-bold text-gray-400 uppercase mb-2 text-center">Ready to Publish</p>
                   <img id="previewImage" src="">
               </div>

               <div class="text-right mt-6 border-t border-gray-100 pt-6">
                  <input type="submit" value="Publish Banner" name="add_banner" class="btn-black">
               </div>
            </form>
         </div>

         <div class="banner-grid">
            <?php
               $show_banners = $conn->prepare("SELECT * FROM `products` WHERE category = 'Banner' ORDER BY id DESC");
               $show_banners->execute();
               if($show_banners->rowCount() > 0){
                  while($banner = $show_banners->fetch(PDO::FETCH_ASSOC)){
            ?>
               <div class="banner-item">
                  <img src="uploaded_img/<?= $banner['image']; ?>" class="banner-img" alt="">
                  <div class="banner-overlay">
                     <div>
                        <div class="banner-sub"><?= htmlspecialchars($banner['details']); ?></div>
                        <div class="banner-title"><?= htmlspecialchars($banner['name']); ?></div>
                     </div>
                     <a href="admin_banner.php?delete=<?= $banner['id']; ?>" class="btn-delete" onclick="return confirm('Delete this banner?');">
                        <i class="fas fa-trash"></i>
                     </a>
                  </div>
               </div>
            <?php } } else { echo '<div class="text-center py-10 text-gray-400 font-bold">No Active Banners</div>'; } ?>
         </div>
      </div>
   </div>
</div>

<div class="crop-modal" id="cropModal">
   <div class="crop-box">
      <div class="flex justify-between items-center mb-4 text-white">
          <h3 class="font-bold uppercase tracking-widest text-sm">Adjust Crop</h3>
          <i class="fas fa-times cursor-pointer hover:text-red-500" onclick="closeModal()"></i>
      </div>
      <div class="img-container">
         <img src="" id="cropImageTarget" style="max-width: 100%;">
      </div>
      <div class="modal-actions">
         <button onclick="closeModal()" class="btn-modal-cancel">Cancel</button>
         <button onclick="performCrop()" class="btn-modal-save">Crop & Save</button>
      </div>
   </div>
</div>

<script src="https://cdnjs.cloudflare.com/ajax/libs/cropperjs/1.5.13/cropper.min.js"></script>
<script>
   let cropper;
   const cropModal = document.getElementById('cropModal');
   const imageElement = document.getElementById('cropImageTarget');
   const fileInput = document.getElementById('imageToCrop');
   const hiddenInput = document.getElementById('croppedImageData');
   const previewContainer = document.getElementById('previewContainer');
   const previewImage = document.getElementById('previewImage');

   // FIX 5: Validate that an image has been cropped before submitting
   function validateForm() {
       if (hiddenInput.value === "") {
           alert("Please upload and crop an image first!");
           return false;
       }
       return true;
   }

   fileInput.addEventListener('change', (e) => {
      const files = e.target.files;
      if (files && files.length > 0) {
         const file = files[0];
         const reader = new FileReader();
         reader.onload = (e) => {
            imageElement.src = e.target.result;
            cropModal.style.display = 'flex';
            
            if (cropper) { cropper.destroy(); }
            cropper = new Cropper(imageElement, {
               aspectRatio: 16 / 9, // FIX 6: Changed to 16:9 for Full Screen
               viewMode: 1,
               autoCropArea: 1,
            });
         };
         reader.readAsDataURL(file);
      }
   });

   function closeModal() {
      cropModal.style.display = 'none';
      fileInput.value = ''; 
      if (cropper) { cropper.destroy(); }
   }

   function performCrop() {
      if (cropper) {
         // FIX 7: Output size 1920x1080 (Matches Home Screen)
         const canvas = cropper.getCroppedCanvas({
            width: 1920, 
            height: 1080 
         });
         
         // FIX 8: Use JPEG 0.8 quality to reduce string size
         const base64Image = canvas.toDataURL('image/jpeg', 0.8);
         hiddenInput.value = base64Image;
         previewImage.src = base64Image;
         previewContainer.style.display = 'block';
         
         closeModal();
      }
   }
</script>

<script src="js/script.js"></script>

</body>
</html>