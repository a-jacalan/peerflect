<?php
// verify-otp.php
session_start();
require_once "config.php";
require_once "check-banned.php";

$errors = [];

// Check if necessary session variables exist
if (!isset($_SESSION['temp_user_id']) || !isset($_SESSION['otp']) || !isset($_SESSION['email'])) {
    header("Location: signup.php");
    exit;
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $entered_otp = $_POST["otp"];
    $stored_otp = $_SESSION['otp'];
    $user_id = $_SESSION['temp_user_id'];

    if (!preg_match('/^[0-9]{6}$/', $entered_otp)) {
        $errors[] = "OTP must be exactly 6 digits.";
    } else {
        if ($entered_otp == $stored_otp) {
            // Start transaction
            $conn->begin_transaction();
            
            try {
                // Update user as verified
                $verify_stmt = $conn->prepare("UPDATE Users SET isVerified = 1, code = NULL WHERE UserID = ?");
                $verify_stmt->bind_param("i", $user_id);
                $verify_stmt->execute();

                // Fetch user details for session
                $user_stmt = $conn->prepare("SELECT UserID, Username, Email, UserType, ProfileImageURL FROM Users WHERE UserID = ?");
                $user_stmt->bind_param("i", $user_id);
                $user_stmt->execute();
                $result = $user_stmt->get_result();
                
                if ($user = $result->fetch_assoc()) {
                    // Clear verification session variables
                    unset($_SESSION['otp']);
                    unset($_SESSION['temp_user_id']);
                    unset($_SESSION['email']);

                    // Set login session variables
                    $_SESSION['loggedin'] = true;
                    $_SESSION['id'] = $user['UserID'];
                    $_SESSION['username'] = $user['Username'];
                    $_SESSION['email'] = $user['Email'];
                    $_SESSION['usertype'] = $user['UserType'];
                    $_SESSION['profile_image'] = $user['ProfileImageURL'];

                    // Commit transaction
                    $conn->commit();

                    // Redirect to dashboard or home page
                    header("Location: index.php");
                    exit();
                } else {
                    throw new Exception("Error fetching user details");
                }
            } catch (Exception $e) {
                // Rollback transaction on error
                $conn->rollback();
                $errors[] = "An error occurred: " . $e->getMessage();
            }

            $verify_stmt->close();
            $user_stmt->close();
        } else {
            $errors[] = "Incorrect OTP. Please try again.";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Verify OTP</title>
    <link rel="stylesheet" href="style.css">
    <style>
        .resend-container {
            margin-top: 15px;
            text-align: center;
        }
        .resend-button {
            background: none;
            border: none;
            color: #007bff;
            cursor: pointer;
            text-decoration: underline;
        }
        .resend-button:disabled {
            color: #6c757d;
            cursor: not-allowed;
            text-decoration: none;
        }
        .countdown {
            color: #6c757d;
            font-size: 0.9em;
            margin-left: 5px;
        }
    </style>
</head>
<body class="topics-bg">
    <div class="container">
        <div class="login-logo">
            <a href="index.php"><img src="img/logo-name.png" alt="Logo"></a>
        </div>
        <div class="verify-container">
            <h2>Verify Your Email</h2>
            <p>An OTP has been sent to your email: <?php echo $_SESSION['email']; ?></p>
            <div class="error-container">
                <?php if(!empty($errors)) { ?>
                    <ul class="error-message">
                        <?php foreach($errors as $error) { ?>
                            <li><?php echo $error; ?></li>
                        <?php } ?>
                    </ul>
                <?php } ?>
            </div>
            <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="post">
                <div class="input-group">
                    <label for="otp">Enter OTP:</label>
                    <input type="number" 
                           id="otp" 
                           name="otp" 
                           required 
                           maxlength="6" 
                           oninput="javascript: if (this.value.length > this.maxLength) this.value = this.value.slice(0, this.maxLength);"
                           pattern="[0-9]{6}"
                           title="Please enter 6 digits only">
                </div>
                <button type="submit">Verify</button>
            </form>
            <div class="resend-container">
                <button id="resendButton" class="resend-button">Resend Code</button>
                <span id="countdown" class="countdown"></span>
            </div>
        </div>
    </div>
    <script src="js/transition.js"></script>
    <script>
    // OTP input validation
    document.getElementById('otp').addEventListener('keypress', function(e) {
        if (e.key.match(/[^0-9]/g)) {
            e.preventDefault();
        }
        if (this.value.length >= 6) {
            e.preventDefault();
        }
    });

    document.getElementById('otp').addEventListener('paste', function(e) {
        let pastedData = e.clipboardData.getData('text');
        if (!pastedData.match(/^[0-9]{1,6}$/)) {
            e.preventDefault();
        }
    });

    // Resend OTP functionality
    const resendButton = document.getElementById('resendButton');
    const countdownDisplay = document.getElementById('countdown');
    let timeLeft = 0;

    function startCountdown(duration) {
        timeLeft = duration;
        resendButton.disabled = true;
        
        const timer = setInterval(() => {
            if (timeLeft <= 0) {
                clearInterval(timer);
                countdownDisplay.textContent = '';
                resendButton.disabled = false;
                return;
            }
            
            countdownDisplay.textContent = `(${timeLeft}s)`;
            timeLeft--;
        }, 1000);
    }

    resendButton.addEventListener('click', async function() {
        if (this.disabled) return;
        
        try {
            const response = await fetch('resend-otp.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                }
            });
            
            const data = await response.json();
            
            if (data.success) {
                alert('New OTP has been sent to your email');
                startCountdown(60); // Start 60-second countdown
            } else {
                alert(data.error || 'Failed to resend OTP');
            }
        } catch (error) {
            alert('An error occurred while resending the OTP');
        }
    });

    // Start initial countdown if needed (e.g., if page was just loaded after first OTP send)
    startCountdown(60);
    </script>
</body>
</html>