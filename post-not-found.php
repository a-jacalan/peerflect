<?php
session_start();
require_once 'config.php';
require_once 'check-banned.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Post Not Found</title>
    <link rel="stylesheet" href="style.css">
    <link rel="stylesheet" href="post.css">
</head>
<body class="topics-bg">
<div class="topnav">
        <div class="logo">
            <a href="index.php"><img src="img/logo.png" alt="Logo"></a>
        </div>
        <div class="menu">
            <a href="index.php">Home</a>
            <div class="dropdown">
                    <a class="active" href="topics.php" class="dropbtn">Topics</a>
                    <div class="dropdown-content">
                        <a href="topics.php">View All Topics</a>
                        <a href="topic-suggestions.php">Suggested Topics</a>
                    </div>
                </div>
            <a href="index.php?scroll=about">About</a>
            <div class="search-bar">
                <form action="search-results.php" method="GET">
                    <input type="text" name="q" placeholder="Search...">
                    <button type="submit">Search</button>
                </form>
            </div>
            <div class="menu-loginreg">
                <?php if(isset($_SESSION["loggedin"]) && $_SESSION["loggedin"] === true) { ?>
                    <div class="dropdown">
                        <a href="#" class="account-link"><?php echo htmlspecialchars($fullname); ?></a>
                        <div class="dropdown-content">
                            <a href="user-dashboard.php">Profile</a>
                            <a href="user-settings.php">Settings</a>
                            <?php if($_SESSION["usertype"] === "contributor") { ?>
                                <a href="redeem-rewards.php">Redeem Rewards</a>
                            <?php } ?>
                            <a href="logout.php">Logout</a>
                        </div>
                    </div>
                <?php } else { ?>
                    <a href="login.php">Login</a>
                    <a href="signup.php">Register</a>
                <?php } ?>
            </div>
        </div>
    </div>
    <div class="error-container">
        <h1 class="error-title">Post doesn't exist or has been removed</h1>
        <a class="error-link" href="topics.php">Return to Topics</a>
    </div>
</body>
</html> 