<?php
session_start(); // Start the session

// Check if the user is logged in, otherwise redirect to login page
if (!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true) {
    header("location: login.php");
    exit;
}

require_once "config.php";
require_once "check-banned.php";;

// Initialize $error variable
$errors = [];

// Get user information from session
$username = $_SESSION["username"];

// Fetch current user details
$sql = "SELECT * FROM users WHERE Username = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("s", $username);
$stmt->execute();
$result = $stmt->get_result();
$user = $result->fetch_assoc();

// Check if form is submitted
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    if (isset($_POST['update_name'])) {
        $new_firstname = $_POST["firstname"];
        $new_lastname = $_POST["lastname"];
        $update_sql = "UPDATE users SET FirstName = ?, LastName = ? WHERE Username = ?";
        $stmt = $conn->prepare($update_sql);
        $stmt->bind_param("sss", $new_firstname, $new_lastname, $username);
        if ($stmt->execute()) {
            $_SESSION["firstname"] = $new_firstname;
            $_SESSION["lastname"] = $new_lastname;
            header("Location: user-dashboard.php");
            exit();
        } else {
            $errors[] = "Error: " . $stmt->error;
        }
        $stmt->close();
    } elseif (isset($_POST['update_password'])) {
        $original_password = $_POST["original_password"];
        $stored_password = $user['Password']; // Retrieve stored password from database
        if ($original_password === $stored_password) {
            $new_password = $_POST["new_password"];
            $confirm_password = $_POST["confirm_password"];
            if (!empty($new_password) && $new_password === $confirm_password) {
                $update_sql = "UPDATE users SET Password = ? WHERE Username = ?";
                $stmt = $conn->prepare($update_sql);
                $stmt->bind_param("ss", $new_password, $username);
                if ($stmt->execute()) {
                    header("Location: user-dashboard.php");
                    exit();
                } else {
                    $errors[] = "Error: " . $stmt->error;
                }
                $stmt->close();
            } else {
                $errors[] = "Passwords do not match!";
            }
        } else {
            $errors[] = "Original password is incorrect.";
        }
    } elseif (isset($_POST['update_image'])) {
        if (isset($_FILES["profile-image"]) && !empty($_FILES["profile-image"]["name"])) {
            $target_dir = "./profile-img/";
            $target_file = $target_dir . basename($_FILES["profile-image"]["name"]);
            $uploadOk = 1;
            $imageFileType = strtolower(pathinfo($target_file, PATHINFO_EXTENSION));
            $check = getimagesize($_FILES["profile-image"]["tmp_name"]);
            if ($check !== false) {
                $uploadOk = 1;
            } else {
                $errors[] = "File is not an image.";
                $uploadOk = 0;
            }
            if ($_FILES["profile-image"]["size"] > 500000) {
                $errors[] = "Sorry, your file is too large.";
                $uploadOk = 0;
            }
            if ($imageFileType != "jpg" && $imageFileType != "png" && $imageFileType != "jpeg" && $imageFileType != "gif") {
                $errors[] = "Sorry, only JPG, JPEG, PNG & GIF files are allowed.";
                $uploadOk = 0;
            }
            if ($uploadOk == 0) {
                $errors[] = "Sorry, your file was not uploaded.";
            } else {
                $new_filename = $username . '.' . $imageFileType;
                $target_file = $target_dir . $new_filename;
                if (move_uploaded_file($_FILES["profile-image"]["tmp_name"], $target_file)) {
                    $profile_path = "./profile-img/" . $new_filename;
                    $update_sql = "UPDATE users SET ProfileImageURL = ? WHERE Username = ?";
                    $stmt = $conn->prepare($update_sql);
                    $stmt->bind_param("ss", $profile_path, $username);
                    if ($stmt->execute()) {
                        header("Location: user-dashboard.php");
                        exit();
                    } else {
                        $errors[] = "Error: " . $stmt->error;
                    }
                    $stmt->close();
                } else {
                    $errors[] = "Sorry, there was an error uploading your file.";
                }
            }
        }
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
    <title>User Settings</title>
    <link rel="stylesheet" href="style.css">
    <style>
        .profile-image-container {
            display: flex;
            align-items: center;
        }
        .profile-image-container img {
            margin-right: 20px;
        }
        .input-group input[type="text"],
        .input-group input[type="password"] {
            padding-left: 10px;
        }

    </style>
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
                <?php if(isset($_SESSION["loggedin"]) && $_SESSION["loggedin"] === true) { ?>
                    <?php if($_SESSION["usertype"] === "admin") { ?>
                        <a href="admin.php">Admin</a>
                    <?php } else if($_SESSION["usertype"] === "schooladmin") { ?>
                        <a href="schooladmin.php"> School Admin</a>
                    <?php } else { ?>
                        <div class="dropdown">
                            <a href="#" class="account-link"><?php echo htmlspecialchars($fullname); ?></a>
                            <div class="dropdown-content">
                                <a href="user-dashboard.php">Dashboard</a>
                                <a href="user-settings.php">Settings</a>
                                <?php if($_SESSION["usertype"] === "contributor") { ?>
                                    <a href="redeem-rewards.php">Redeem Rewards</a>
                                <?php } ?>
                                <a href="logout.php">Logout</a>
                            </div>
                        </div>
                    <?php } ?>
                <?php } else { ?>
                    <a href="login.php">Login</a>
                    <a href="signup.php">Register</a>
                <?php } ?>
            </div>  
        </div>
    </div>
    <div class="container">
        <div class="signup-container">
            <h2>User Settings</h2>
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
                    <label for="profile-image">Profile Picture:</label>
                    <div class="profile-image-container">
                    <?php if (!empty($user['ProfileImageURL'])) { ?>
                        <img src="<?php echo $user['ProfileImageURL']; ?>" alt="Current Profile Picture" width="100">
                    <?php } ?>
                    <input type="file" id="profile-image" name="profile-image">
                    </div>
                </div>
                <button type="submit" style="width: 20%; display: block; margin: auto;" name="update_image">Update Image</button>
            </form>
            <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="post">
                <div class="personal-settings">
                    <div class="input-group">
                        <label for="firstname">First Name:</label>
                        <input type="text" id="firstname" name="firstname" value="<?php echo htmlspecialchars($user['FirstName']); ?>" required>
                    </div>
                    <div class="input-group">
                        <label for="lastname">Last Name:</label>
                        <input type="text" id="lastname" name="lastname" value="<?php echo htmlspecialchars($user['LastName']); ?>" required>
                    </div>
                </div>
                <button type="submit" style="width: 20%; display: block; margin: auto;" name="update_name">Update Name</button>
            </form>
            <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="post">
                <div class="security-settings">
                    <div class="input-group">
                        <label for="original_password">Original Password:</label>
                        <input type="password" id="original_password" name="original_password" required>
                    </div>
                    <div class="input-group">
                        <label for="new_password">New Password:</label>
                        <input type="password" id="new_password" name="new_password">
                    </div>
                    <div class="input-group">
                        <label for="confirm_password">Confirm New Password:</label>
                        <input type="password" id="confirm_password" name="confirm_password">
                    </div>
                </div>
                <button type="submit" style="width: 20%; display: block; margin: auto;" name="update_password">Update Password</button>
            </form>
        </div>
    </div>
<script src="js/transition.js"></script>
</body>
</html>

