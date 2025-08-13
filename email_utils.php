<?php
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;

require 'vendor/autoload.php';

function sendEmail($to, $subject, $body, $altBody = '') {
    // Load sensitive configuration from a separate, non-versioned file
    $configFile = __DIR__ . '/email_config.php';
    
    if (!file_exists($configFile)) {
        error_log('Email configuration file not found');
        return false;
    }
    
    $emailConfig = require $configFile;

    $mail = new PHPMailer(true);

    try {
        // Server settings
        $mail->SMTPDebug = SMTP::DEBUG_OFF;
        $mail->isSMTP();
        $mail->Host       = $emailConfig['host'];
        $mail->SMTPAuth   = true;
        $mail->Username   = $emailConfig['username'];
        $mail->Password   = $emailConfig['password'];
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port       = 587;

        // Recipients
        $mail->setFrom($emailConfig['from'], $emailConfig['from_name']);
        $mail->addAddress($to);

        // Content
        $mail->isHTML(true);
        $mail->Subject = $subject;
        $mail->Body    = $body;
        
        if (!empty($altBody)) {
            $mail->AltBody = $altBody;
        }

        $mail->send();
        return true;
    } catch (Exception $e) {
        // Log the error (consider using a proper logging mechanism)
        error_log('Email could not be sent. Mailer Error: ' . $mail->ErrorInfo);
        return false;
    }
}

// No example usage or hardcoded credentials
?>
