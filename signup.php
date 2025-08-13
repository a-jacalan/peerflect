<?php
session_start();
require_once "config.php";
require_once "check-banned.php";

$errors = [];

function generateOTP() {
    return rand(100000, 999999);
}

function sendOTPEmail($email, $otp) {
    require_once 'email_utils.php';
    
    $subject = 'Your OTP Code';
    $body = "Your OTP code is: <b>$otp</b>";
    $altBody = "Your OTP code is: $otp";
    
    return sendEmail($email, $subject, $body, $altBody);
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $username = $_POST["username"];
    $email = $_POST["email"];
    $firstname = $_POST["firstname"];
    $lastname = $_POST["lastname"];
    $password = $_POST["password"];
    $confirm_password = $_POST["confirm_password"];

    // Enhanced validation rules
    if (!preg_match('/^[A-Za-z0-9]+$/', $username)) {
        $errors[] = "Username can only contain letters and numbers!";
    }

    if (!preg_match('/^[A-Za-z]+$/', $firstname)) {
        $errors[] = "First name can only contain letters!";
    }

    if (!preg_match('/^[A-Za-z]+$/', $lastname)) {
        $errors[] = "Last name can only contain letters!";
    }

    // Simplified password validation
    if (!(strlen($password) >= 8 && 
        preg_match('/[A-Z]/', $password) && 
        preg_match('/[a-z]/', $password) && 
        preg_match('/[0-9]/', $password) && 
        preg_match('/[^A-Za-z0-9]/', $password))) {
        $errors[] = "Password must be at least 8 characters and include uppercase, lowercase, number and symbol";
    }

    // Validate passwords match
    if ($password !== $confirm_password) {
        $errors[] = "Passwords do not match!";
    }

    // Check if username already exists
    $check_username_query = "SELECT * FROM Users WHERE Username = ?";
    $stmt = $conn->prepare($check_username_query);
    $stmt->bind_param("s", $username);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($result->num_rows > 0) {
        $errors[] = "Username already exists!";
    }

    // Check if email already exists
    $check_email_query = "SELECT * FROM Users WHERE Email = ?";
    $stmt = $conn->prepare($check_email_query);
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($result->num_rows > 0) {
        $errors[] = "Email already exists!";
    }
    
    // Initialize profile path as null
    $profile_path = null;
    
    // Check if a file was uploaded
    if(isset($_FILES["profile-image"]) && !empty($_FILES["profile-image"]["name"])) {
        $target_dir = "./profile-img/"; 
        $target_file = $target_dir . basename($_FILES["profile-image"]["name"]);
        $uploadOk = 1;
        $imageFileType = strtolower(pathinfo($target_file, PATHINFO_EXTENSION));

        // Check if image file is a actual image or fake image
        $check = getimagesize($_FILES["profile-image"]["tmp_name"]);
        if($check !== false) {
            $uploadOk = 1;
        } else {
            $errors[] = "File is not an image.";
            $uploadOk = 0;
        }

        // Check file size
        if ($_FILES["profile-image"]["size"] > 5000000) {
            $errors[] = "Sorry, your file is too large.";
            $uploadOk = 0;
        }

        // Allow certain file formats
        if($imageFileType != "jpg" && $imageFileType != "png" && $imageFileType != "jpeg" && $imageFileType != "gif" ) {
            $errors[] = "Sorry, only JPG, JPEG, PNG & GIF files are allowed.";
            $uploadOk = 0;
        }

        if ($uploadOk == 1) {
            $new_filename = $username . '.' . $imageFileType;
            $target_file = $target_dir . $new_filename;

            if (move_uploaded_file($_FILES["profile-image"]["tmp_name"], $target_file)) {
                $profile_path = "./profile-img/" . $new_filename;
            } else {
                $errors[] = "Sorry, there was an error uploading your file.";
            }
        }
    }

    if (empty($errors)) {
        // Hash the password
        $hashed_password = password_hash($password, PASSWORD_DEFAULT);
        
        $otp = generateOTP();
        
        // Start a transaction
        $conn->begin_transaction();
        
        try {
            // Insert user data
            $stmt = $conn->prepare("INSERT INTO users (Username, FirstName, LastName, Email, Password, ProfileImageURL, code, isVerified, usertype) VALUES (?, ?, ?, ?, ?, ?, ?, 0, 'regular')");
            $stmt->bind_param("sssssss", $username, $firstname, $lastname, $email, $hashed_password, $profile_path, $otp);

            if ($stmt->execute()) {
                $user_id = $conn->insert_id;
                
                // Store necessary information in session
                $_SESSION['temp_user_id'] = $user_id;
                $_SESSION['otp'] = $otp;
                $_SESSION['email'] = $email;

                if (sendOTPEmail($email, $otp)) {
                    // Commit the transaction
                    $conn->commit();
                    
                    // Redirect to OTP verification page
                    header("Location: verify-otp.php");
                    exit();
                } else {
                    throw new Exception("Failed to send OTP email.");
                }
            } else {
                throw new Exception("Error inserting user data: " . $stmt->error);
            }
        } catch (Exception $e) {
            // Rollback the transaction on any error
            $conn->rollback();
            $errors[] = $e->getMessage();
        }

        $stmt->close();
    }
}

$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>User Sign Up</title>
    <link rel="stylesheet" href="style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
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
                <a class="active" href="signup.php">Register</a>
            </div>
        </div>
    </div>
    <div class="form-flex-container">
        <div class="left-container">
            <div class="login-logo">
                <a href="index.php"><img src="img/logo-name.png" alt="Logo"></a>
            </div>
        </div>
        <div class="right-container">
            <div class="signup-container">
                <h2>Register an Account</h2>
                <div class="error-container">
                    <?php if(!empty($errors)) { ?>
                        <ul class="error-message">
                            <?php foreach($errors as $error) { ?>
                                <li><?php echo $error; ?></li>
                            <?php } ?>
                        </ul>
                    <?php } ?>
                </div>
                <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="post" enctype="multipart/form-data" id="signupForm">
                    <div class="input-group">
                        <label for="username">Username:</label>
                        <input type="text" id="username" name="username" pattern="[A-Za-z0-9]+" title="Only letters and numbers are allowed" required>
                    </div>

                    <div class="input-group">
                        <label for="email">Email:</label>
                        <input type="email" id="email" name="email" required>
                    </div>

                    <div class="input-group">
                        <label for="firstname">First Name:</label>
                        <input type="text" id="firstname" name="firstname" pattern="[A-Za-z]+" title="Only letters are allowed" required>
                    </div>

                    <div class="input-group">
                        <label for="lastname">Last Name:</label>
                        <input type="text" id="lastname" name="lastname" pattern="[A-Za-z]+" title="Only letters are allowed" required>
                    </div>

                    <div class="input-group">
                        <label for="password">Password:</label>
                        <div class="password-container">
                            <input type="password" id="password" name="password" required>
                            <i class="toggle-password fas fa-eye" id="togglePassword" onclick="togglePassword('password')"></i>
                        </div>
                        <div class="password-hint" id="password-hint">
                            Password must be at least 8 characters and include uppercase, lowercase, number and symbol
                        </div>
                    </div>

                    <div class="input-group">
                        <label for="confirm_password">Confirm Password:</label>
                        <div class="password-container">
                            <input type="password" id="confirm_password" name="confirm_password" required>
                            <i class="toggle-password fas fa-eye" id="toggleConfirmPassword" onclick="togglePassword('confirm_password')"></i>
                        </div>
                    </div>

                    <div class="input-group">
                        <label for="profile-image">Profile Picture:</label>
                        <input type="file" id="profile-image" name="profile-image" accept="image/*">
                    </div>

                    <button type="submit">Sign Up</button>
                </form>
                <div class="additional-links">
                    <p>Already have an account? <a href="login.php">Login</a></p>
                </div>
            </div>
        </div>
    </div>
    <script>
    function togglePassword(fieldId) {
        const passwordField = document.getElementById(fieldId);
        const icon = passwordField.nextElementSibling;
        
        if (passwordField.type === "password") {
            passwordField.type = "text";
            icon.classList.remove("fa-eye");
            icon.classList.add("fa-eye-slash");
        } else {
            passwordField.type = "password";
            icon.classList.remove("fa-eye-slash");
            icon.classList.add("fa-eye");
        }
    }

    document.getElementById('password').addEventListener('input', function() {
        const password = this.value;
        const hint = document.getElementById('password-hint');
        
        // Check all requirements
        const isValid = password.length >= 8 && 
                       /[A-Z]/.test(password) && 
                       /[a-z]/.test(password) && 
                       /[0-9]/.test(password) && 
                       /[^A-Za-z0-9]/.test(password);
        
        hint.className = 'password-hint ' + (isValid ? 'valid' : 'invalid');
    });

    // Real-time validation for username
    document.getElementById('username').addEventListener('input', function() {
        this.value = this.value.replace(/[^A-Za-z0-9]/g, '');
    });

    // Real-time validation for first name and last name
    document.getElementById('firstname').addEventListener('input', function() {
        this.value = this.value.replace(/[^A-Za-z]/g, '');
    });

    document.getElementById('lastname').addEventListener('input', function() {
        this.value = this.value.replace(/[^A-Za-z]/g, '');
    });
    </script>
    <script src="js/transition.js"></script>
</body>
</html>
