<?php
session_start();

// Include config file
require_once "config.php";
require_once "check-banned.php";;
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>About - Reydy To Review</title>
    <link rel="stylesheet" href="style.css">
    <style>
      /* Add additional styles specific to the about page if needed */
    </style>
</head>
<body>
    <div class="topnav">
        <div class="logo">
            <a href="index.php"><img src="img/logo.png" alt="Logo"></a>
        </div>
        <div class="menu">
            <a href="index.php">Home</a>
            <a href="branches.php">Branches</a>
            <a class="active" href="about.php">About</a>
            <div class="search-bar">
                <form action="search-results.php" method="GET">
                    <input type="text" name="q" placeholder="Search...">
                    <button type="submit">Search</button>
                </form>
            </div>
            <div class="menu-loginreg">
                <?php if(isset($_SESSION["loggedin"]) && $_SESSION["loggedin"] === true) { ?>
                    <?php if($_SESSION["username"] === "admin") { ?>
                        <a href="admin.php">Admin</a>
                    <?php } else { ?>
                        <div class="dropdown">
                            <a href="#" class="account-link">Account</a>
                            <div class="dropdown-content">
                                <a href="user-dashboard.php">Dashboard</a>
                                <a href="user-settings.php">Settings</a>
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
    <div class="content-container">
        <div class="about-section">
            <h2>About Collaborative Reviewer for Computer Networking</h2>
            <p>Welcome to Collaborative Reviewer for Computer Networking, your go-to platform for collaborative networking reviews.</p>
            <p>Our platform aims to bring together experts and enthusiasts in the field of computer networking to collectively review and discuss various topics, technologies, and advancements in the networking domain.</p>
            <p>Whether you're a seasoned professional or a newcomer to the field, you'll find valuable insights, discussions, and resources to enhance your knowledge and stay updated with the latest trends in computer networking.</p>
            <p>Join us today and become part of a vibrant community dedicated to advancing the field of computer networking through collaborative learning and sharing!</p>
        </div>
    </div>
</body>
</html>
