<?php
    session_start();

    // Include config file and establish database connection
    require_once "config.php";
require_once "check-banned.php";;

    // Fetch posts with subcategory OSI Model from the database
    $sql = "SELECT Posts.PostID, Posts.isApproved, Posts.Title, Posts.CreatedAt, Users.ProfileImageURL 
            FROM Posts 
            INNER JOIN Users ON Posts.UserID = Users.UserID 
            WHERE Posts.SubCategory = 'Access Control Lists (ACLs)' 
            AND Posts.isApproved = 1 
            AND (Users.isBan = 0 OR Users.isBan IS NULL)
            ORDER BY Posts.CreatedAt DESC";
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
    <title>Access Control Lists (ACLs) Posts</title>
    <link rel="stylesheet" href="style.css">
</head>
<body class="topics-bg">
    <div class="topnav">
        <div class="logo">
            <a href="index.php"><img src="img/logo.png" alt="Logo"></a>
        </div>
        <div class="menu">
            <a href="index.php">Home</a>
            <a class="active" href="topics.php">Topics</a>
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
    <div class="topics-container">
        <h2>Access Control Lists (ACLs) Posts</h2>
        <?php if (empty($posts)): ?>
            <p>Nothing is posted yet</p>
        <?php else: ?>
            <?php foreach($posts as $post): ?>
                <div class="br-post-item">
                    <img class="br-profile-image" src="<?php echo htmlspecialchars($post['ProfileImageURL']); ?>" alt="Profile Image">
                    <div class="br-post-content">
                        <div class="br-post-title">
                            <a href="post.php?postID=<?php echo htmlspecialchars($post['PostID']); ?>">
                                <?php echo htmlspecialchars($post['Title']); ?>
                            </a>
                        </div>
                        <div class="br-post-date"><?php echo htmlspecialchars($post['CreatedAt']); ?></div>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
<script src="js/transition.js"></script>
</body>
</html>
