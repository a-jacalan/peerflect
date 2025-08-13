<?php
session_start(); // Start the session

// Check if the user is already logged in, redirect to dashboard if true
if(isset($_SESSION["loggedin"]) && $_SESSION["loggedin"] === true){
    header("location: user-dashboard.php");
    exit;
}

require_once "config.php";
require_once "check-banned.php";;
// Initialize $error variable
$errors = [];

// Check if form is submitted
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Store form data
    $username = $_POST["username"];
    $email = $_POST["email"];
    $firstname = $_POST["firstname"];
    $lastname = $_POST["lastname"];
    $password = $_POST["password"];
    $confirm_password = $_POST["confirm_password"];

    // Check if username already exists
    $check_username_query = "SELECT * FROM Users WHERE Username = '$username'";
    $check_username_result = $conn->query($check_username_query);
    if ($check_username_result->num_rows > 0) {
        $errors[] = "Username already exists!";
    }

    // Check if email already exists
    $check_email_query = "SELECT * FROM Users WHERE Email = '$email'";
    $check_email_result = $conn->query($check_email_query);
    if ($check_email_result->num_rows > 0) {
        $errors[] = "Email already exists!";
    }

    // Validate passwords match
    if ($password !== $confirm_password) {
        $errors[] = "Passwords do not match!";
    }
    
    // Check if a file was uploaded
    if(isset($_FILES["profile-image"]) && !empty($_FILES["profile-image"]["name"])) {
        $target_dir = "./profile-img/"; 
        $target_file = $target_dir . basename($_FILES["profile-image"]["name"]);
        $uploadOk = 1;
        $imageFileType = strtolower(pathinfo($target_file, PATHINFO_EXTENSION));

        // Check if image file is a actual image or fake image
        $check = getimagesize($_FILES["profile-image"]["tmp_name"]);
        if($check !== false) {
            // File is an image
            $uploadOk = 1;
        } else {
            // File is not an image
            $errors[] = "File is not an image.";
            $uploadOk = 0;
        }

        // Check file size
        if ($_FILES["profile-image"]["size"] > 500000) {
            // File is too large
            $errors[] = "Sorry, your file is too large.";
            $uploadOk = 0;
        }

        // Allow certain file formats
        if($imageFileType != "jpg" && $imageFileType != "png" && $imageFileType != "jpeg"
        && $imageFileType != "gif" ) {
            // Unsupported file format
            $errors[] = "Sorry, only JPG, JPEG, PNG & GIF files are allowed.";
            $uploadOk = 0;
        }

        // Check if $uploadOk is set to 0 by an error
        if ($uploadOk == 0) {
            // File was not uploaded
            $errors[] = "Sorry, your file was not uploaded.";
        } else {
            // File was uploaded successfully
            // Generate a unique filename based on the username
            $new_filename = $username . '.' . $imageFileType;

            // Construct the target file path with the new filename
            $target_file = $target_dir . $new_filename;

            // Move the uploaded file to the target directory with the new filename
            if (move_uploaded_file($_FILES["profile-image"]["tmp_name"], $target_file)) {
                // File was moved to the target directory successfully
                // You can save the file path to the database or perform other actions here
            } else {
                // Error moving file to the target directory
                $errors[] = "Sorry, there was an error uploading your file.";
            }
        }
    } else {
        // No file uploaded
        $errors[] = "Please select a file to upload.";
    }
    // If no errors, proceed with insertion
    if (empty($errors)) {
        // Prepare SQL statement to insert user data into database
        $stmt = $conn->prepare("INSERT INTO users (Username, FirstName, LastName, Email, Password, ProfileImageURL) VALUES (?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("ssssss", $username, $firstname, $lastname, $email, $password, $profile_path);

        // Set default profile path if no file uploaded
        $profile_path = null;
        if(isset($_FILES["profile-image"]) && !empty($_FILES["profile-image"]["name"])) {
            $profile_path = "./profile-img/" . $new_filename;
        }

        // Set user type (default is 'user')
        $type = "user";

        if ($stmt->execute()) {
            // Redirect to login page upon successful signup
            header("Location: login.php");
            exit();
        } else {
            $errors[] = "Error: " . $stmt->error;
        }

        $stmt->close();
    }
}


// Close database connection
$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>User Sign Up</title>
    <link rel="stylesheet" href="style.css">
</head>
<body class="topics-bg">
    <div class="topnav">
        <div class="logo">
            <a href="index.php"><img src="img/logo.png" alt="Logo"></a>
        </div>
        <div class="menu">
            <a href="index.php">Home</a>
            <a href="branches.php">Branches</a>
            <a href="about.php">About</a>
            <div class="search-bar">
                <form action="search-results.php" method="GET">
                    <input type="text" name="q" placeholder="Search...">
                    <button type="submit">Search</button>
                </form>
            </div>
            <div class="menu-loginreg">
                <a href="login.php">Login</a>
                <a class="active" href="user-signup.php">Register</a>
            </div>
        </div>
    </div>
    <div class="container">
        <div class="login-logo">
            <a href="index.php"><img src="img/logo-name.png" alt="Logo"></a>
        </div>
        <div class="signup-container">
            <h2>User Sign Up</h2>
            <div class="error-container">
            <?php if(!empty($errors)) { ?>
                <ul class="error-message">
                    <?php foreach($errors as $error) { ?>
                        <li><?php echo $error; ?></li>
                    <?php } ?>
                </ul>
            <?php } ?>
            </div>
            <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="post" enctype="multipart/form-data">
                <div class="input-group">
                    <label for="username">Username:</label>
                    <input type="text" id="username" name="username" required>
                </div>
                <div class="input-group">
                    <label for="email">Email:</label>
                    <input type="email" id="email" name="email" required>
                </div>
                <div class="input-group">
                    <label for="firstname">First Name:</label>
                    <input type="text" id="firstname" name="firstname" required>
                </div>
                <div class="input-group">
                    <label for="lastname">Last Name:</label>
                    <input type="text" id="lastname" name="lastname" required>
                </div>
                <div class="input-group">
                    <label for="password">Password:</label>
                    <input type="password" id="password" name="password" required>
                </div>
                <div class="input-group">
                    <label for="confirm_password">Confirm Password:</label>
                    <input type="password" id="confirm_password" name="confirm_password" required>
                </div>
                <div class="input-group">
                    <label for="profile-image">Profile Picture:</label>
                    <input type="file" id="profile-image" name="profile-image">
                </div>
                <button type="submit">Sign Up</button>
            </form>
            <div class="additional-links">
                <p>Already have an account? <a href="login.php">Login</a></p>
            </div>
        </div>
    </div>
</body>
</html>
