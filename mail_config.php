<?php
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

// Ensure these paths match where you extracted PHPMailer
require 'PHPMailer/src/Exception.php';
require 'PHPMailer/src/PHPMailer.php';
require 'PHPMailer/src/SMTP.php';

// --- FUNCTION 1: VERIFICATION EMAIL (For Registration) ---
function sendVerificationEmail($user_email, $user_name, $code) {
    $mail = new PHPMailer(true);

    try {
        // --- SERVER SETTINGS ---
        $mail->isSMTP();
        $mail->Host       = 'smtp.gmail.com'; 
        $mail->SMTPAuth   = true;
        
        // YOUR REAL EMAIL (SENDER)
        $mail->Username   = 'akirakatana54@gmail.com'; 
        
        // YOUR APP PASSWORD
        $mail->Password   = 'stnq ksbz vuuy dvxs'; 
        
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port       = 587;

        // --- RECIPIENTS ---
        $mail->setFrom('akirakatana54@gmail.com', 'Anggun Gadget');
        $mail->addReplyTo('akirakatana54@gmail.com', 'Anggun Gadget'); // Helps avoid spam folders
        $mail->addAddress($user_email, $user_name);

        // --- CONTENT ---
        $mail->isHTML(true);
        $mail->Subject = 'Verify Your Account - Anggun Gadget';
        
        // Dynamic Link Generation
        $protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http";
        $host = $_SERVER['HTTP_HOST'];
        $path = dirname($_SERVER['PHP_SELF']);
        $link = "$protocol://$host$path/verify.php?email=" . $user_email . "&code=" . $code;
        
        $mail->Body    = "
            <div style='font-family: Arial, sans-serif; padding: 20px; color: #333; border: 1px solid #ddd; border-radius: 10px; max-width: 500px;'>
                <h2 style='color: #1D1D1F;'>Welcome, $user_name!</h2>
                <p>Thank you for joining Anggun Gadget.</p>
                <p>Please click the button below to verify your email address:</p>
                <br>
                <a href='$link' style='background-color: #000; color: #fff; padding: 12px 24px; text-decoration: none; border-radius: 6px; font-weight: bold; display: inline-block;'>Verify Account</a>
                <br><br>
                <p style='font-size: 12px; color: #666;'>If the button doesn't work, copy and paste this link:<br>$link</p>
            </div>
        ";
        
        // Plain text version for non-HTML email clients (Helps spam score)
        $mail->AltBody = "Welcome $user_name! Please verify your account by visiting this link: $link";

        $mail->send();
        return true;
    } catch (Exception $e) {
        // echo "Message could not be sent. Mailer Error: {$mail->ErrorInfo}";
        return false;
    }
}

// --- FUNCTION 2: RESET PASSWORD EMAIL (For Forgot Password) ---
function sendResetEmail($user_email, $token) {
    $mail = new PHPMailer(true);

    try {
        $mail->isSMTP();
        $mail->Host       = 'smtp.gmail.com'; 
        $mail->SMTPAuth   = true;
        $mail->Username   = 'akirakatana54@gmail.com';
        $mail->Password   = 'stnq ksbz vuuy dvxs';
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port       = 587;

        $mail->setFrom('akirakatana54@gmail.com', 'Anggun Gadget');
        $mail->addReplyTo('akirakatana54@gmail.com', 'Anggun Gadget');
        $mail->addAddress($user_email);

        $mail->isHTML(true);
        $mail->Subject = 'Reset Your Password - Anggun Gadget';
        
        $protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http";
        $host = $_SERVER['HTTP_HOST'];
        $path = dirname($_SERVER['PHP_SELF']);
        $link = "$protocol://$host$path/reset_password.php?email=" . $user_email . "&token=" . $token;
        
        $mail->Body    = "
            <div style='font-family: Arial, sans-serif; padding: 20px; color: #333; border: 1px solid #ddd; border-radius: 10px; max-width: 500px;'>
                <h2 style='color: #1D1D1F;'>Password Reset</h2>
                <p>We received a request to reset your password.</p>
                <p>Click the button below to set a new password:</p>
                <br>
                <a href='$link' style='background-color: #000; color: #fff; padding: 12px 24px; text-decoration: none; border-radius: 6px; font-weight: bold; display: inline-block;'>Reset Password</a>
                <br><br>
                <p style='font-size: 12px; color: #666;'>This link expires in 1 hour.</p>
            </div>
        ";

        $mail->AltBody = "Reset your password by visiting this link: $link";

        $mail->send();
        return true;
    } catch (Exception $e) {
        return false;
    }
}
?>