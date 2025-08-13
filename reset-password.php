<?php
session_start();
require_once "config.php";

// Check if user is logged in for password reset
if (!isset($_SESSION["reset_email"]) || !isset($_SESSION["reset_otp"])) {
    header("location: forgot-password.php");
    exit;
}

$otp = $new_password = $confirm_password = "";
$otp_err = $password_err = $confirm_password_err = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    
    // Validate OTP
    if (empty(trim($_POST["otp"]))) {
        $otp_err = "Please enter the OTP.";
    } else {
        $otp = trim($_POST["otp"]);
        if ($otp != $_SESSION["reset_otp"]) {
            $otp_err = "Invalid OTP code.";
        }
    }
    
    // Validate password
    if (empty(trim($_POST["new_password"]))) {
        $password_err = "Please enter a password.";
    } elseif (strlen(trim($_POST["new_password"])) < 6) {
        $password_err = "Password must have at least 6 characters.";
    } else {
        $new_password = trim($_POST["new_password"]);
    }
    
    // Validate confirm password
    if (empty(trim($_POST["confirm_password"]))) {
        $confirm_password_err = "Please confirm the password.";
    } else {
        $confirm_password = trim($_POST["confirm_password"]);
        if (empty($password_err) && ($new_password != $confirm_password)) {
            $confirm_password_err = "Passwords did not match.";
        }
    }
    
    // Check input errors before updating the database
    if (empty($otp_err) && empty($password_err) && empty($confirm_password_err)) {
        
        $sql = "UPDATE Users SET password = ?, reset_code = NULL WHERE Email = ?";
        
        if ($stmt = $conn->prepare($sql)) {
            $stmt->bind_param("ss", $param_password, $param_email);
            
            $param_password = $new_password;
            $param_email = $_SESSION["reset_email"];
            
            if ($stmt->execute()) {
                // Password updated successfully. Destroy the session and redirect to login page
                session_destroy();
                header("location: login.php");
                exit();
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
    <title>Reset Password</title>
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
                <h2>Reset Password</h2>
                <?php if (!empty($otp_err) || !empty($password_err) || !empty($confirm_password_err)): ?>
                    <div class="error-container">
                        <p class="error-message"><?php echo $otp_err; ?></p>
                        <p class="error-message"><?php echo $password_err; ?></p>
                        <p class="error-message"><?php echo $confirm_password_err; ?></p>
                    </div>
                <?php endif; ?>
                <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="post">
                    <div class="input-group <?php echo (!empty($otp_err)) ? 'has-error' : ''; ?>">
                        <label for="otp">Enter OTP:</label>
                        <input type="text" id="otp" name="otp" required>
                    </div>
                    <div class="input-group <?php echo (!empty($password_err)) ? 'has-error' : ''; ?>">
                        <label for="new_password">New Password:</label>
                        <div style="position: relative; width: 100%;">
                            <input type="password" id="new_password" name="new_password" style="width: 94%; padding-right: 30px;" required>
                            <span onclick="togglePasswordVisibility('new_password')" style="position: absolute; right: 1px; top: 50%; transform: translateY(-50%); cursor: pointer; font-size: 18px;">
                                👁️
                            </span>
                        </div>
                    </div>
                    <div class="input-group <?php echo (!empty($confirm_password_err)) ? 'has-error' : ''; ?>">
                        <label for="confirm_password">Confirm Password:</label>
                        <div style="position: relative; width: 100%;">
                            <input type="password" id="confirm_password" name="confirm_password" style="width: 94%; padding-right: 30px;" required>
                            <span onclick="togglePasswordVisibility('confirm_password')" style="position: absolute; right: 1px; top: 50%; transform: translateY(-50%); cursor: pointer; font-size: 18px;">
                                👁️
                            </span>
                        </div>
                    </div>
                    <button type="submit">Reset Password</button>
                </form>
            </div>
        </div>
    </div>
    <script src="js/transition.js"></script>
    <script>
    function togglePasswordVisibility(fieldId) {
        const passwordField = document.getElementById(fieldId);
        if (passwordField.type === "password") {
            passwordField.type = "text";
        } else {
            passwordField.type = "password";
        }
    }
    </script>
</body>
</html>