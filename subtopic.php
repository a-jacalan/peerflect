<?php
session_start();

// Include database connection
require_once "config.php";
require_once "check-banned.php";

// Check if subtopic name is provided
if (!isset($_GET['name']) || empty($_GET['name'])) {
    // Redirect to topics page if no name is provided
    header("Location: topics.php");
    exit();
}

// Sanitize the subtopic name
$subtopic_name = mysqli_real_escape_string($conn, urldecode($_GET['name']));

// Fetch posts for this subtopic with author name, school, comments count, and average rating
$sql = "SELECT 
            Posts.PostID, 
            Posts.isApproved, 
            Posts.Title, 
            Posts.CreatedAt, 
            Users.ProfileImageURL, 
            CONCAT(Users.FirstName, ' ', Users.LastName) AS AuthorName,
            Users.school AS AuthorSchool,
            COALESCE(CommentCount.comment_count, 0) AS CommentCount,
            COALESCE(RatingAvg.avg_rating, 0) AS AverageRating
        FROM Posts 
        INNER JOIN Users ON Posts.UserID = Users.UserID 
        LEFT JOIN (
            SELECT PostID, COUNT(*) AS comment_count 
            FROM comment 
            GROUP BY PostID
        ) AS CommentCount ON Posts.PostID = CommentCount.PostID
        LEFT JOIN (
            SELECT PostID, AVG(Score) AS avg_rating 
            FROM rating 
            GROUP BY PostID
        ) AS RatingAvg ON Posts.PostID = RatingAvg.PostID
        WHERE Posts.SubCategory = LOWER(?)  
        AND Posts.isApproved = 1 
        AND (Users.isBan = 0 OR Users.isBan IS NULL)
        ORDER BY Posts.CreatedAt DESC";

// Prepare and execute statement
$stmt = $conn->prepare($sql);
$stmt->bind_param("s", $subtopic_name);
$stmt->execute();
$result = $stmt->get_result();

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
    <title><?php echo htmlspecialchars(ucwords($subtopic_name)); ?> Posts</title>
    <link rel="stylesheet" href="style.css">
    <style>
        .topics-container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 20px;
        }
        .posts-grid {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 30px;
            row-gap: 30px;
        }
        @media (max-width: 768px) {
            .posts-grid {
                grid-template-columns: 1fr;
                gap: 25px;
            }
        }
        .br-post-item {
            display: flex;
            align-items: flex-start;
            background-color: #f9f9f9;
            border-radius: 8px;
            padding: 20px;
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
            height: 80%;
            transition: transform 0.2s ease;
            cursor: pointer; /* Add cursor pointer */
        }
        .br-post-item:hover {
            transform: translateY(-5px);
        }
        .br-profile-image {
            width: 50px;
            height: 50px;
            border-radius: 50%;
            margin-right: 15px;
            object-fit: cover;
        }
        .br-post-content {
            flex-grow: 1;
            overflow: hidden;
        }
        .br-post-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
        }
        .br-post-title a {
            font-size: 1.1em;
            font-weight: bold;
            color: #333;
            text-decoration: none;
            display: block;
            max-width: 100%;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }
        .br-post-author {
            color: #666;
            font-size: 0.9em;
            margin-top: 5px;
        }
        .br-post-school {
            color: #888;
            font-size: 0.8em;
            margin-bottom: 5px;
        }
        .br-post-meta {
            display: flex;
            justify-content: space-between;
            margin-top: 10px;
            color: #888;
            font-size: 0.8em;
        }
        .br-post-date {
            white-space: nowrap;
        }
        .br-rating {
            display: flex;
            align-items: center;
        }
        .br-rating-star {
            color: #ffc107;
            margin-right: 3px;
        }
    </style>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.3/css/all.min.css">
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
    <h2><?php echo htmlspecialchars(ucwords($subtopic_name)); ?> Posts</h2>
        <?php if (empty($posts)): ?>
            <p>Nothing is posted yet</p>
        <?php else: ?>
            <div class="posts-grid">
                <?php foreach($posts as $post): ?>
                    <div class="br-post-item" onclick="window.location.href='post.php?postID=<?php echo htmlspecialchars($post['PostID']); ?>'">
                        <img class="br-profile-image" src="<?php echo htmlspecialchars($post['ProfileImageURL']); ?>" alt="Profile Image">
                        <div class="br-post-content">
                            <div class="br-post-header">
                                <div>
                                    <div class="br-post-title">
                                        <a href="post.php?postID=<?php echo htmlspecialchars($post['PostID']); ?>">
                                            <?php echo htmlspecialchars($post['Title']); ?>
                                        </a>
                                    </div>
                                    <div class="br-post-author">
                                        By <?php echo htmlspecialchars($post['AuthorName']); ?>
                                    </div>
                                    <div class="br-post-school">
                                        <?php echo htmlspecialchars($post['AuthorSchool']); ?>
                                    </div>
                                </div>
                                <div class="br-post-date"><?php echo htmlspecialchars(date('M d, Y', strtotime($post['CreatedAt']))); ?></div>
                            </div>
                            <div class="br-post-meta">
                                <div class="br-rating">
                                    <?php 
                                    $rating = round($post['AverageRating'], 1);
                                    for ($i = 1; $i <= 5; $i++) {
                                        echo $i <= $rating 
                                            ? '<i class="fas fa-star br-rating-star"></i>' 
                                            : '<i class="far fa-star br-rating-star"></i>';
                                    }
                                    echo " (" . number_format($rating, 1) . ")";
                                    ?>
                                </div>
                                <div>
                                    <i class="fas fa-comment"></i> <?php echo htmlspecialchars($post['CommentCount']); ?> Comments
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
    <script src="js/transition.js"></script>
    <script>
        // Optional: Add hover effect to show it's clickable
        document.querySelectorAll('.br-post-item').forEach(item => {
            item.addEventListener('mouseover', () => {
                item.style.cursor = 'pointer';
            });
        });
    </script>
</body>
</html>