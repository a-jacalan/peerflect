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
    <title>Reydy To Review</title>
    <link rel="stylesheet" href="style.css">
    <style>
        .box-container {
            border: 2px solid #8f75ec;
            padding: 20px;
            border-radius: 8px;
            width: 500px; /* Adjust width as needed */
            z-index: 100;
            overflow-y: auto; /* Add vertical scrollbar if needed */
            max-height: 600px; /* Adjust height as needed */
        }
      .boxes{
        z-index: 0;
      }
      .single-box {
        position: absolute;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        overflow: hidden;
        padding-inline-start: 0px;
        margin-block-start: 0em;
      }
      .single-box li {
        position: absolute;
        display: block;
        list-style: none;
        width: 25px;
        height: 25px;
        background: rgba(186, 69, 240, 0.418);
        animation: animate 20s linear infinite;
        bottom: -150px;
      }
      .single-box li:nth-child(1) {
        left: 86%;
        width: 80px;
        height: 80px;
        animation-delay: 0s;
      }
      .single-box li:nth-child(2) {
        left: 12%;
        width: 30px;
        height: 30px;
        animation-delay: 1.5s;
        animation-duration: 10s;
      }
      .single-box li:nth-child(3) {
        left: 29%;
        width: 100px;
        height: 100px;
        animation-delay: 5.5s;
      }
      .single-box li:nth-child(4) {
        left: 42%;
        width: 150px;
        height: 150px;
        animation-delay: 0s;
        animation-duration: 15s;
      }
      .single-box li:nth-child(5) {
        left: 65%;
        width: 40px;
        height: 40px;
        animation-delay: 0s;
      }
      .single-box li:nth-child(6) {
        left: 15%;
        width: 110px;
        height: 110px;
        animation-delay: 3.5s;
      }
      .single-box li:nth-child(7) {
        left: 75%;
        width: 200px;
        height: 200px;
        animation-delay: 4.5s;
        bottom: -200px;
      }
      @keyframes animate {
        0% {
          transform: translateY(0) rotate(0deg);
          opacity: 1;
        }
        100% {
          transform: translateY(-800px) rotate(360deg);
          opacity: 0;
        }
        .post-box {
            border: 1px solid #ddd;
            padding: 10px;
            margin-bottom: 10px;
            border-radius: 5px;
            background-color: #f9f9f9;
        }
      }</style>
</head>
<body class="home-bg">
    <div class="topnav">
        <div class="logo">
            <a href="index.php"><img src="img/logo.png" alt="Logo"></a>
        </div>
        <div class="menu">
            <a class="active" href="index.php">Home</a>
            <a href="branches.php">Branches</a>
            <a href="about.php">About</a>
            <div class="search-bar">
                <input type="text" placeholder="Search...">
                
                <button type="submit">Search</button>
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
    <div class="home-container">
        <div class="welcome-container">
            <h2>Welcome!</h2>
            <h1>
                <?php 
                if(isset($_SESSION["loggedin"]) && $_SESSION["loggedin"] === true) {
                  echo "HELLO, " . strtoupper(htmlspecialchars($_SESSION["username"])) . "!";
                } else {
                    echo "HELLO, WORLD!";
                }
                ?>
            </h1>
            <button><a href="branches.php">Let's Review! &rarr;</a></button>
        </div>
        <div class="box-container">
            <h2>Recent Posts</h2>
            <?php
            // Fetch recent posts from the database
            $stmt = $conn->prepare("SELECT Title, CreatedAt FROM Posts ORDER BY CreatedAt DESC LIMIT 10");
            $stmt->execute();
            $result = $stmt->get_result();

            if ($result->num_rows > 0) {
                while ($row = $result->fetch_assoc()) {
                    echo '<div class="post-box">';
                    echo '<h3>' . htmlspecialchars($row['Title']) . '</h3>';
                    echo '<p>Posted on: ' . htmlspecialchars($row['CreatedAt']) . '</p>';
                    echo '</div>';
                }
            } else {
                echo '<p>No posts available.</p>';
            }
            $stmt->close();
            ?>
        </div>
        <div class="boxes">
        <ul class="single-box">
            <li></li>
            <li></li>
            <li></li>
            <li></li>
            <li></li>
            <li></li>
            <li></li>
        </ul>
        </div>
    </div>
</body>
</html>