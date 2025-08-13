<?php
session_start();
require_once "config.php";
require_once "email_utils.php";

header('Content-Type: application/json');

// Check if necessary session variables exist
if (!isset($_SESSION['temp_user_id']) || !isset($_SESSION['email'])) {
    echo json_encode(['success' => false, 'error' => 'Invalid session']);
    exit;
}

// Generate new OTP
function generateOTP() {
    return rand(100000, 999999);
}

$user_id = $_SESSION['temp_user_id'];
$email = $_SESSION['email'];
$new_otp = generateOTP();

// Prepare email body
$subject = "New Verification OTP";
$body = "
    <html>
    <body>
        <h2>Email Verification</h2>
        <p>Your new OTP code is: <strong>{$new_otp}</strong></p>
        <p>This code will expire soon. Please use it to verify your email.</p>
    </body>
    </html>
";

try {
    // Update OTP in database
    $update_stmt = $conn->prepare("UPDATE Users SET code = ? WHERE UserID = ?");
    $update_stmt->bind_param("si", $new_otp, $user_id);
    
    if ($update_stmt->execute() && sendEmail($email, $subject, $body, "Your new OTP code is: {$new_otp}")) {
        // Update session OTP
        $_SESSION['otp'] = $new_otp;
        
        echo json_encode(['success' => true]);
    } else {
        echo json_encode(['success' => false, 'error' => 'Failed to send OTP']);
    }
    
    $update_stmt->close();
} catch (Exception $e) {
    echo json_encode(['success' => false, 'error' => 'An error occurred: ' . $e->getMessage()]);
}

$conn->close();
?>