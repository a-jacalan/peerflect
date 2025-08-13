<?php
session_start();

// Check if the user is already logged in
if (isset($_SESSION["loggedin"]) && $_SESSION["loggedin"] === true) {
    if (isset($_SESSION["usertype"]) && $_SESSION["usertype"] === "banned") {
        header("location: banned.php");
        exit;
    }else if (isset($_SESSION["usertype"]) && $_SESSION["usertype"] === "admin") {
        header("location: admin.php");
        exit;
    } else if (isset($_SESSION["usertype"]) && $_SESSION["usertype"] === "schooladmin") {
        header("location: schooladmin.php");
        exit;
    } else {
        header("location: user-dashboard.php");
        exit;
    }
}

require_once "config.php";
require_once "check-banned.php";;

// Define variables and initialize with empty values
$username = $password = "";
$username_err = $password_err = "";

// Function to generate OTP
function generateOTP() {
    return rand(100000, 999999);
}

// Function to send OTP email
function sendOTPEmail($email, $otp) {
    $subject = "Your OTP Code";
    $message = "Your OTP code is: $otp";
    $headers = "From: your-email@example.com"; // Replace with your sender email
    return mail($email, $subject, $message, $headers);
}

// Processing form data when form is submitted
if ($_SERVER["REQUEST_METHOD"] == "POST") {

    // Check if username is empty
    if (empty(trim($_POST["username"]))) {
        $username_err = "Please enter username.";
    } else {
        $username = trim($_POST["username"]);
    }

    // Check if password is empty
    if (empty(trim($_POST["password"]))) {
        $password_err = "Please enter your password.";
    } else {
        $password = trim($_POST["password"]);
    }

    // Validate credentials
    if (empty($username_err) && empty($password_err)) {
        // Prepare a select statement
        $sql = "SELECT UserID, username, password, usertype, school FROM Users WHERE username = ?";
    
        if ($stmt = $conn->prepare($sql)) {
            // Bind variables to the prepared statement as parameters
            $stmt->bind_param("s", $param_username);
    
            // Set parameters
            $param_username = $username;
    
            // Attempt to execute the prepared statement
            if ($stmt->execute()) {
                // Store result
                $stmt->store_result();    

                // Check if username exists, if yes then verify password
                if ($stmt->num_rows == 1) {
                    // Bind result variables
                    $stmt->bind_result($id, $username, $db_password, $user_type, $user_school);
                    if ($stmt->fetch()) {
                        if (password_verify($password, $db_password)) {
                            // Password is correct, so start a new session
                            session_start();

                            // Store data in session variables
                            $_SESSION["loggedin"] = true;
                            $_SESSION["id"] = $id;
                            $_SESSION["username"] = $username;
                            $_SESSION["usertype"] = $user_type;
                            $_SESSION["school"] = $user_school;

                            // Check if the user is verified and get their email
                            $stmt = $conn->prepare("SELECT isVerified, Email FROM Users WHERE UserID = ?");
                            $stmt->bind_param("i", $id);
                            $stmt->execute();
                            $stmt->bind_result($isVerified, $email);
                            $stmt->fetch();
                            $stmt->close();

                            if ($isVerified == 0) {
                                // User is not verified, generate and send OTP
                                $otp = generateOTP();

                                // Update OTP in the database
                                $stmt = $conn->prepare("UPDATE Users SET code = ? WHERE UserID = ?");
                                $stmt->bind_param("si", $otp, $id);
                                $stmt->execute();
                                $stmt->close();

                                // Store OTP in session
                                $_SESSION['otp'] = $otp;

                                // Send OTP email
                                if (sendOTPEmail($email, $otp)) {
                                    // Store necessary data in session and redirect to verify-otp.php
                                    $_SESSION['temp_user_id'] = $id;
                                    $_SESSION['email'] = $email;
                                    header("location: verify-otp.php");
                                    exit;
                                } else {
                                    $password_err = "Failed to send OTP email. Please try again.";
                                }
                            } else {
                                // User is verified, proceed with normal login
                                if ($user_type === "admin") {
                                    header("location: admin.php");
                                } else if ($user_type === "schooladmin") {
                                    header("location: schooladmin.php");
                                }else {
                                    header("location: user-dashboard.php");
                                }
                            }
                            exit;
                        } else {
                            // Display an error message if password is not valid
                            $password_err = "The password you entered is not valid.";
                        }
                    }
                } else {
                    // Display an error message if username doesn't exist
                    $username_err = "No account found with that username.";
                }
            } else {
                echo "Oops! Something went wrong. Please try again later.";
            }

            // Close statement
            $stmt->close();
        }
    }

    // Close connection
    $conn->close();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login</title>
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
                <a class="active" href="login.php">Login</a>
                <a href="signup.php">Register</a>
            </div>
        </div>
    </div>
    <div class="flex-container">
        <div id="login" class="left-container">
            <div class="login-logo">
                <a href="index.php"><img src="img/logo-name.png" alt="Logo"></a>
            </div>
        </div>
        <div id="login" class="right-container">
            <div class="login-container">
                <h2>Login</h2>
                <?php if (!empty($username_err) || !empty($password_err)): ?>
                    <div class="error-container">
                        <p class="error-message"><?php echo $username_err; ?></p>
                        <p class="error-message"><?php echo $password_err; ?></p>
                    </div>
                <?php endif; ?>
                <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="post">
                    <div class="input-group <?php echo (!empty($username_err)) ? 'has-error' : ''; ?>">
                        <label for="username">Username:</label>
                        <input type="text" id="username" name="username" value="<?php echo $username; ?>" required>
                    </div>
                    <div class="input-group <?php echo (!empty($password_err)) ? 'has-error' : ''; ?>">
                        <label for="password">Password:</label>
                        <div style="position: relative; width: 100%;">
                        <input type="password" id="password" name="password" style="width: 94%; padding-right: 30px;" required>
                        <span onclick="togglePasswordVisibility()" style="position: absolute; right: 1px; top: 50%; transform: translateY(-50%); cursor: pointer; font-size: 18px;">
                            👁️
                        </span>
                    </div>
                   </div>
                    <button type="submit">Login</button>
                    <!--<p><a href="forgot-password.php">Forgot Password?</a></p>-->
                </form>
            </div>
            <p>Not registered yet? <a href="signup.php">Sign Up</a></p>
        </div>
    </div>
<script src="js/transition.js"></script>

<script>
function togglePasswordVisibility() {
    const passwordField = document.getElementById("password");
    if (passwordField.type === "password") {
        passwordField.type = "text";
    } else {
        passwordField.type = "password";
    }
}
</script>
</body>
</html>
