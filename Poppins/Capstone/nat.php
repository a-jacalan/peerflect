<?php
    session_start();

    // Include config file and establish database connection
    require_once "config.php";
require_once "check-banned.php";;

    // Fetch posts with subcategory OSI Model from the database
    $sql = "SELECT Posts.PostID, Posts.isApproved, Posts.Title, Posts.CreatedAt, Users.ProfileImageURL 
            FROM Posts 
            INNER JOIN Users ON Posts.UserID = Users.UserID 
            WHERE Posts.SubCategory = 'nat' 
            AND Posts.isApproved = 1 
            AND (Users.isBan = 0 OR Users.isBan IS NULL)";
    $result = $conn->query($sql);

    // Check if there are any posts
    if ($result->num_rows > 0) {
        // Store fetched posts in an array
        $posts = [];
        while($row = $result->fetch_assoc()) {
            $posts[] = $row;
        }
    } else {
        $posts = [];
    }
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Network Address Translation (NAT) Posts</title>
    <link rel="stylesheet" href="style.css">
</head>
<body class="topics-bg">
    <div class="topnav">
        <div class="logo">
            <a href="index.php"><img src="img/logo.png" alt="Logo"></a>
        </div>
        <div class="menu">
            <a href="index.php">Home</a>
            <a class="active" href="branches.php">Branches</a>
            <a href="about.php">About</a>
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
    <div class="topics-container">
        <h2>Network Address Translation (NAT) Posts</h2>
        <?php foreach($posts as $post): ?>
            <div class="br-post-item">
                <img class="br-profile-image" src="<?php echo htmlspecialchars($post['ProfileImageURL']); ?>" alt="Profile Image">
                <div class="br-post-content">
                    <div class="br-post-title">
                        <a href="post.php?=<?php echo htmlspecialchars($post['PostID']); ?>">
                            <?php echo htmlspecialchars($post['Title']); ?>
                        </a>
                    </div>
                    <div class="br-post-date"><?php echo htmlspecialchars($post['CreatedAt']); ?></div>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
</body>
</html>
