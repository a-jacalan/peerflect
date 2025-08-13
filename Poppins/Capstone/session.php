<?php
session_start(); // Start the session

// Check if the user is logged in
$loggedin = isset($_SESSION["loggedin"]) && $_SESSION["loggedin"] === true;

// Get username, userID, and admin status if logged in
$username = "";
$userID = "";
$isAdmin = false;
if ($loggedin) {
    $username = isset($_SESSION["username"]) ? $_SESSION["username"] : "";
    $userID = isset($_SESSION["id"]) ? $_SESSION["id"] : "";
    $isAdmin = isset($_SESSION["IsAdmin"]) ? $_SESSION["IsAdmin"] : false;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Session Information</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 20px;
        }
        h2 {
            margin-bottom: 10px;
        }
        .session-info {
            border: 1px solid #ccc;
            padding: 20px;
            max-width: 600px;
        }
        .session-info p {
            margin: 5px 0;
        }
    </style>
</head>
<body>

<div class="session-info">
    <h2>Session Information</h2>
    <p><strong>Session Status:</strong> <?php echo $loggedin ? "Active" : "Inactive"; ?></p>
    <?php if ($loggedin) { ?>
        <p><strong>User ID:</strong> <?php echo $userID; ?></p>
        <p><strong>Logged-in User:</strong> <?php echo $username; ?></p>
        <p><strong>Admin Status:</strong> <?php echo $isAdmin ? "Yes" : "No"; ?></p>
        <p><a href="logout.php">Logout</a></p>
    <?php } else { ?>
        <p><a href="login.php">Login</a></p>
    <?php } ?>
</div>

</body>
</html>
