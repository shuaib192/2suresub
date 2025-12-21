<?php
/**
 * 2SureSub - Email Helper
 * Handles sending emails using SMTP settings from database
 */

function sendEmail($to, $subject, $message, $isHtml = true) {
    $fromName = getSetting('smtp_from_name', getSetting('site_name', '2SureSub'));
    $fromEmail = getSetting('smtp_user', getSetting('site_email'));
    
    $headers = "From: $fromName <$fromEmail>\r\n";
    $headers .= "Reply-To: $fromEmail\r\n";
    $headers .= "X-Mailer: PHP/" . phpversion() . "\r\n";
    
    if ($isHtml) {
        $headers .= "MIME-Version: 1.0\r\n";
        $headers .= "Content-Type: text/html; charset=UTF-8\r\n";
        
        // Wrap in a basic template
        $siteName = getSetting('site_name', '2SureSub');
        $siteLogo = APP_URL . '/assets/img/logo.png'; // Assuming logo path
        
        $emailContent = "
        <div style='font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto; padding: 20px; border: 1px solid #eee; border-radius: 10px;'>
            <div style='text-align: center; margin-bottom: 20px;'>
                <h1 style='color: #007bff; margin: 0;'>$siteName</h1>
            </div>
            <div style='color: #333; line-height: 1.6;'>
                $message
            </div>
            <hr style='border: 0; border-top: 1px solid #eee; margin: 20px 0;'>
            <div style='text-align: center; font-size: 12px; color: #999;'>
                <p>&copy; " . date('Y') . " $siteName. All rights reserved.</p>
                <p>" . getSetting('site_address') . "</p>
            </div>
        </div>";
        
        return mail($to, $subject, $emailContent, $headers, "-f$fromEmail");
    } else {
        return mail($to, $subject, $message, $headers, "-f$fromEmail");
    }
}

/**
 * Send Welcome Email
 */
function sendWelcomeEmail($userEmail, $name) {
    $siteName = getSetting('site_name', '2SureSub');
    $subject = "Welcome to $siteName!";
    $message = "
    <h2>Hello, $name!</h2>
    <p>Welcome to <strong>$siteName</strong>, your one-stop platform for all your VTU needs.</p>
    <p>We are excited to have you on board. You can now fund your wallet and start buying data, airtime, and paying bills at great rates.</p>
    <div style='margin-top: 20px;'>
        <a href='" . APP_URL . "/auth/login.php' style='background: #007bff; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px;'>Login to Dashboard</a>
    </div>
    <p>If you have any questions, feel free to contact our support team.</p>";
    
    return sendEmail($userEmail, $subject, $message);
}

/**
 * Send Transaction Success Email
 */
function sendTransactionEmail($userEmail, $userName, $type, $amount, $details) {
    $siteName = getSetting('site_name', '2SureSub');
    $subject = "Transaction Success - $siteName";
    $message = "
    <h2>Transaction Successful</h2>
    <p>Hi $userName,</p>
    <p>Your transaction for <strong>$type</strong> was successful.</p>
    <div style='background: #f8f9fa; padding: 15px; border-radius: 8px; margin: 15px 0;'>
        <p style='margin: 5px 0;'><strong>Amount:</strong> " . formatMoney($amount) . "</p>
        <p style='margin: 5px 0;'><strong>Details:</strong> $details</p>
        <p style='margin: 5px 0;'><strong>Date:</strong> " . date('Y-m-d H:i:s') . "</p>
    </div>
    <p>Thank you for choosing $siteName.</p>";
    
    return sendEmail($userEmail, $subject, $message);
}

/**
 * Send Verification Email
 */
function sendVerificationEmail($email, $token) {
    $siteName = getSetting('site_name', '2SureSub');
    $verifyUrl = APP_URL . "/auth/verify.php?token=" . $token;
    $subject = "Verify Your Email - $siteName";
    
    $message = "
    <h2>Verify Your Email Address</h2>
    <p>Thank you for signing up on <strong>$siteName</strong>!</p>
    <p>To complete your registration and start using our services, please verify your email address by clicking the button below:</p>
    <div style='margin: 30px 0; text-align: center;'>
        <a href='$verifyUrl' style='background: #007bff; color: white; padding: 12px 25px; text-decoration: none; border-radius: 5px; font-weight: bold;'>Verify Email Address</a>
    </div>
    <p>Or copy and paste this link in your browser:</p>
    <p><a href='$verifyUrl'>$verifyUrl</a></p>
    <p>If you did not create an account, please ignore this email.</p>";
    
    return sendEmail($email, $subject, $message);
}
