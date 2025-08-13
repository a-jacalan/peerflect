<?php
session_start();
require_once "config.php";
require_once "email_utils.php";

// Define variables and initialize with empty values
$email = "";
$email_err = "";

// Function to generate OTP (reusing from login.php)
function generateOTP() {
    return rand(100000, 999999);
}

// Processing form data when form is submitted
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    
    // Validate email
    if (empty(trim($_POST["email"]))) {
        $email_err = "Please enter your email address.";
    } else {
        $email = trim($_POST["email"]);
        
        // Prepare a select statement
        $sql = "SELECT UserID, Email FROM Users WHERE Email = ?";
        
        if ($stmt = $conn->prepare($sql)) {
            $stmt->bind_param("s", $param_email);
            $param_email = $email;
            
            if ($stmt->execute()) {
                $stmt->store_result();
                
                if ($stmt->num_rows == 1) {
                    $stmt->bind_result($id, $email);
                    $stmt->fetch();
                    
                    // Generate and store OTP
                    $otp = generateOTP();
                    
                    // Update OTP in database
                    $update_stmt = $conn->prepare("UPDATE Users SET reset_code = ? WHERE UserID = ?");
                    $update_stmt->bind_param("si", $otp, $id);
                    
                    // Prepare email body
                    $subject = "Password Reset OTP";
                    $body = "
                        <html>
                        <body>
                            <h2>Password Reset</h2>
                            <p>Your OTP code for password reset is: <strong>{$otp}</strong></p>
                            <p>If you did not request this, please ignore this email.</p>
                        </body>
                        </html>
                    ";
                    
                    if ($update_stmt->execute() && sendEmail($email, $subject, $body, "Your OTP code is: {$otp}")) {
                        // Store data in session variables
                        $_SESSION['reset_email'] = $email;
                        $_SESSION['reset_otp'] = $otp;
                        
                        header("location: reset-password.php");
                        exit;
                    } else {
                        $email_err = "Failed to send reset email. Please try again.";
                    }
                    $update_stmt->close();
                } else {
                    $email_err = "No account found with that email address.";
                }
            } else {
                echo "Oops! Something went wrong. Please try again later.";
            }
            $stmt->close();
        }
    }
    $conn->close();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Forgot Password</title>
    <link rel="stylesheet" href="style.css">
</head>

<body class="topics-bg">
    <div class="topnav">
        <div class="logo">
            <a href="index.php"><img src="img/logo.png" alt="Logo"></a>
        </div>
        <div class="menu">
            <a href="index.php">Home</a>
            <a href="topics.php">Topics</a>
            <a href="index.php?scroll=about">About</a>
            <div class="search-bar">
                <form action="search-results.php" method="GET">
                    <input type="text" name="q" placeholder="Search...">
                    <button type="submit">Search</button>
                </form>
            </div>
            <div class="menu-loginreg">
                <a href="login.php">Login</a>
                <a href="signup.php">Register</a>
            </div>
        </div>
    </div>
    <div class="flex-container">
        <div class="left-container">
            <div class="login-logo">
                <a href="index.php"><img src="img/logo-name.png" alt="Logo"></a>
            </div>
        </div>
        <div class="right-container">
            <div class="login-container">
                <h2>Forgot Password</h2>
                <?php if (!empty($email_err)): ?>
                    <div class="error-container">
                        <p class="error-message"><?php echo $email_err; ?></p>
                    </div>
                <?php endif; ?>
                <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="post">
                    <div class="input-group <?php echo (!empty($email_err)) ? 'has-error' : ''; ?>">
                        <label for="email">Email:</label>
                        <input type="email" id="email" name="email" value="<?php echo $email; ?>" required>
                    </div>
                    <button type="submit">Send Reset Code</button>
                </form>
                <p>Remember your password? <a href="login.php">Login here</a></p>
            </div>
        </div>
    </div>
    <script src="js/transition.js"></script>
</body>
</html>