<?php
session_start();

// Check if the user is not logged in or is not an admin, redirect to user dashboard
if (!isset($_SESSION["loggedin"]) || !isset($_SESSION["username"]) || $_SESSION["username"] !== "admin") {
    header("location: user-dashboard.php");
    exit;
}

// Include database connection
require_once "config.php";
require_once "check-banned.php";;

// Handle delete user action
if (isset($_POST["delete_user"])) {
    $userID = $_POST["delete_user"];
    // Perform deletion query
    $sqlDeleteUser = "DELETE FROM Users WHERE UserID = ?";
    $stmtDeleteUser = $conn->prepare($sqlDeleteUser);
    $stmtDeleteUser->bind_param("i", $userID);
    $stmtDeleteUser->execute();
    // Delete user's posts
    $sqlDeleteUserPosts = "DELETE FROM Question WHERE UserID = ?";
    $stmtDeleteUserPosts = $conn->prepare($sqlDeleteUserPosts);
    $stmtDeleteUserPosts->bind_param("i", $userID);
    $stmtDeleteUserPosts->execute();
    // Redirect or show message after deletion
}

// Handle approve/reject contributor application action
if (isset($_POST["approve_application"]) || isset($_POST["reject_application"])) {
    $userID = $_POST["userID"];
    // Determine action based on which button was clicked
    if (isset($_POST["approve_application"])) {
        // Update user's isContributor to 1
        $sqlUpdateIsContributor = "UPDATE Users SET IsContributor = 1 WHERE UserID = ?";
        $stmtUpdateIsContributor = $conn->prepare($sqlUpdateIsContributor);
        $stmtUpdateIsContributor->bind_param("i", $userID);
        $stmtUpdateIsContributor->execute();
    }
    // Delete application from ContributorApplication table
    $sqlDeleteApplication = "DELETE FROM ContributorApplication WHERE UserID = ?";
    $stmtDeleteApplication = $conn->prepare($sqlDeleteApplication);
    $stmtDeleteApplication->bind_param("i", $userID);
    $stmtDeleteApplication->execute();
    // Redirect or show message after updating status
}

// Fetch users
$users = [];
$sqlUsers = "SELECT UserID, Username, Email FROM Users";
$resultUsers = $conn->query($sqlUsers);
if ($resultUsers->num_rows > 0) {
    while ($row = $resultUsers->fetch_assoc()) {
        $users[$row['UserID']] = $row; // Store users
    }
}

// Fetch contributor applications
$applications = [];
$sqlApplications = "SELECT UserID, ApplicationText, AttachedFiles FROM ContributorApplication";
$resultApplications = $conn->query($sqlApplications);
if ($resultApplications->num_rows > 0) {
    while ($row = $resultApplications->fetch_assoc()) {
        $row['Username'] = isset($users[$row['UserID']]) ? $users[$row['UserID']]['Username'] : 'Unknown';
        $applications[] = $row;
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>User Management - Admin</title>
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
            <a href="admin.php">Admin Dashboard</a>
            <a href="post-management.php">Post Management</a>
            <a class="active" href="user-management.php">User Management</a>
            <a href="report.php">Report Management</a>
            <a href="logout.php">Logout</a>
        </div>
        <div class="admin-content">
            <div class="users">
                <h2>Users</h2>
                <?php foreach ($users as $userID => $user): ?>
                    <div class="user-item">
                        <div class="user-details">
                            <div class="user-username">Username: <?php echo $user['Username']; ?></div>
                            <div class="user-email">Email: <?php echo $user['Email']; ?></div>
                        </div>
                        <div class="user-actions">
                            <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="post">
                                <input type="hidden" name="delete_user" value="<?php echo $userID; ?>">
                                <button type="submit">Delete</button>
                            </form>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
            <div class="contributor-applications">
                <h2>Contributor Applications</h2>
                <?php foreach ($applications as $application): ?>
                    <div class="application-item">
                        <div class="application-details">
                            <div class="application-username">Username: <?php echo $application["Username"]; ?></div>
                            <div class="application-reason">Reason: <?php echo $application["ApplicationText"]; ?></div>
                            <!-- Display attached files if available -->
                            <?php if (!empty($application["AttachedFiles"])): ?>
                                <div class="application-files">
                                    <?php 
                                        $attachedFiles = json_decode($application["AttachedFiles"], true); 
                                        foreach ($attachedFiles as $index => $file) {
                                            echo '<a href="view-image.php?file=' . basename($file) . '" target="_blank">File ' . ($index + 1) . '</a><br>';
                                        }
                                    ?>
                                </div>
                            <?php endif; ?>
                        </div>
                        <div class="application-actions">
                            <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="post">
                                <input type="hidden" name="userID" value="<?php echo $application["UserID"]; ?>">
                                <button type="submit" name="approve_application">Approve</button>
                            </form>
                            <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="post">
                                <input type="hidden" name="userID" value="<?php echo $application["UserID"]; ?>">
                                <button type="submit" name="reject_application">Reject</button>
                            </form>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>
</body>
</html>
