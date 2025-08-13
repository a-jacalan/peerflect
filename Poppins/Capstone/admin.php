<?php
session_start();

// Check if the user is not logged in or is not an admin, redirect to user dashboard
if(!isset($_SESSION["loggedin"]) || !isset($_SESSION["username"]) || $_SESSION["username"] !== "admin") {
    header("location: user-dashboard.php");
    exit;
}

// Include config file
require_once "config.php";
require_once "check-banned.php";;

// Function to fetch statistics
function fetchStatistics($conn) {
    $statistics = array(
        'contributors' => 0,
        'regular_users' => 0,
        'total_users' => 0,
        'total_posts' => 0,
        'total_comments' => 0
    );

    // Fetch number of contributors
    $sql_contributors = "SELECT COUNT(*) AS count FROM Users WHERE IsContributor = 1";
    $result_contributors = $conn->query($sql_contributors);
    $row_contributors = $result_contributors->fetch_assoc();
    $statistics['contributors'] = $row_contributors['count'];

    // Fetch number of regular users
    $sql_regular_users = "SELECT COUNT(*) AS count FROM Users WHERE IsContributor = 0";
    $result_regular_users = $conn->query($sql_regular_users);
    $row_regular_users = $result_regular_users->fetch_assoc();
    $statistics['regular_users'] = $row_regular_users['count'];

    // Fetch total number of registered users
    $sql_total_users = "SELECT COUNT(*) AS count FROM Users";
    $result_total_users = $conn->query($sql_total_users);
    $row_total_users = $result_total_users->fetch_assoc();
    $statistics['total_users'] = $row_total_users['count'];

    // Fetch total number of posts
    $sql_total_posts = "SELECT COUNT(*) AS count FROM Posts";
    $result_total_posts = $conn->query($sql_total_posts);
    $row_total_posts = $result_total_posts->fetch_assoc();
    $statistics['total_posts'] = $row_total_posts['count'];

    // Fetch total number of comments
    $sql_total_comments = "SELECT COUNT(*) AS count FROM Comment";
    $result_total_comments = $conn->query($sql_total_comments);
    $row_total_comments = $result_total_comments->fetch_assoc();
    $statistics['total_comments'] = $row_total_comments['count'];

    return $statistics;
}

// Fetch statistics
$statistics = fetchStatistics($conn);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard</title>
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
                <a class="active" href="admin.php">Admin</a>
            </div>
        </div>
    </div>
    <div class="admin-container">
        <div class="admin-nav">
            <a class="active" href="admin.php">Admin Dashboard</a>
            <a href="post-management.php">Post Management</a>
            <a href="user-management.php">User Management</a>
            <a href="report.php">Report Management</a>
            <a href="logout.php">Logout</a>
        </div>
        <div class="admin-content">
            <div class="info-box">
                <h2>Numbers of Contributors:</h2>
                <p><?php echo $statistics['contributors']; ?></p>
            </div>
            <div class="info-box">
                <h2>Numbers of Regular Users:</h2>
                <p><?php echo $statistics['regular_users']; ?></p>
            </div>
            <div class="info-box">
                <h2>Total Number of Registered Users:</h2>
                <p><?php echo $statistics['total_users']; ?></p>
            </div>
            <div class="info-box">
                <h2>Total Number of Posts:</h2>
                <p><?php echo $statistics['total_posts']; ?></p>
            </div>
            <div class="info-box">
                <h2>Total Number of Comments:</h2>
                <p><?php echo $statistics['total_comments']; ?></p>
            </div>
        </div>
    </div>
</body>
</html>
