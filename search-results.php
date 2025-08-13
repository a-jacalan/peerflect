<?php
session_start();
require_once "config.php";
require_once "check-banned.php";;
require_once "check-banned.php";

// Get the search query
$search_query = isset($_GET['q']) ? trim($_GET['q']) : '';

// Initialize variables for user info (similar to index.php)
$isLoggedIn = false;
$username = "";
$fullname = "";
$profile_image = "";
$isVerified = false;

// Check if user is logged in (reusing the logic from index.php)
if (isset($_SESSION['id']) && !empty($_SESSION['id'])) {
    $isLoggedIn = true;
    
    $sql = "SELECT username, email, CONCAT(firstname, ' ', lastname) AS fullname, profileimageurl, isVerified FROM Users WHERE userid = ?";
    if ($stmt = $conn->prepare($sql)) {
        $stmt->bind_param("i", $_SESSION["id"]);
        if ($stmt->execute()) {
            $stmt->store_result();
            if ($stmt->num_rows == 1) {
                $stmt->bind_result($username, $email, $fullname, $profile_image, $isVerified);
                $stmt->fetch();
            }
        }
        $stmt->close();
    }
}

// Search posts
$search_results = [];
if (!empty($search_query)) {
    $sql = "SELECT p.PostID, p.Title, p.CreatedAt, p.MainCategory, p.SubCategory, 
                   CONCAT(u.FirstName, ' ', u.LastName) as AuthorName
            FROM Posts p
            LEFT JOIN Users u ON p.UserID = u.UserID
            WHERE p.isApproved = 1 
                        AND (u.isBan = 0 OR u.isBan IS NULL)
            AND p.Title LIKE ?
            ORDER BY p.CreatedAt DESC";
            
    if ($stmt = $conn->prepare($sql)) {
        $search_param = "%" . $search_query . "%";
        $stmt->bind_param("s", $search_param);
        
        if ($stmt->execute()) {
            $result = $stmt->get_result();
            while ($row = $result->fetch_assoc()) {
                $search_results[] = $row;
            }
        }
        $stmt->close();
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Search Results - PeerFlect</title>
    <link rel="stylesheet" href="style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <style>
        .search-results-container {
            max-width: 1200px;
            margin: 20px auto;
            padding: 20px;
            background-color: #fff;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
        }

        .search-header {
            margin-bottom: 20px;
            padding-bottom: 10px;
            border-bottom: 1px solid #eee;
        }

        .search-result-item {
            padding: 15px;
            margin-bottom: 15px;
            border: 1px solid #eee;
            border-radius: 4px;
            transition: background-color 0.2s;
        }

        .search-result-item:hover {
            background-color: #f9f9f9;
        }

        .search-result-item h3 {
            margin: 0 0 10px 0;
            color: #333;
        }

        .search-result-item p {
            margin: 5px 0;
            color: #666;
        }

        .search-result-item .metadata {
            font-size: 0.9em;
            color: #888;
        }

        .no-results {
            text-align: center;
            padding: 40px;
            color: #666;
        }

        .category-tag {
            display: inline-block;
            padding: 2px 8px;
            margin-right: 5px;
            background-color: #e9ecef;
            border-radius: 4px;
            font-size: 0.85em;
            color: #495057;
        }
    </style>
</head>
<body>
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
                    <input type="text" name="q" placeholder="Search..." value="<?php echo htmlspecialchars($search_query); ?>">
                    <button type="submit">Search</button>
                </form>
            </div>
            <div class="menu-loginreg">
                <?php if(isset($_SESSION["loggedin"]) && $_SESSION["loggedin"] === true) { ?>
                    <?php if($_SESSION["usertype"] === "admin") { ?>
                        <a href="admin.php">Admin</a>
                    <?php } else if($_SESSION["usertype"] === "schooladmin") { ?>
                        <a href="schooladmin.php">School Admin</a>
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

    <div class="search-results-container">
        <div class="search-header">
            <h2>Search Results for "<?php echo htmlspecialchars($search_query); ?>"</h2>
            <p>Found <?php echo count($search_results); ?> result(s)</p>
        </div>

        <?php if (!empty($search_results)): ?>
            <?php foreach ($search_results as $result): ?>
                <div class="search-result-item">
                    <h3><a href="post.php?postID=<?php echo htmlspecialchars($result['PostID']); ?>">
                        <?php echo htmlspecialchars($result['Title']); ?>
                    </a></h3>
                    <div class="metadata">
                        <span class="category-tag"><?php echo htmlspecialchars($result['MainCategory']); ?></span>
                        <span class="category-tag"><?php echo htmlspecialchars($result['SubCategory']); ?></span>
                        <br>
                        <span>By <?php echo htmlspecialchars($result['AuthorName']); ?></span>
                        <span> • </span>
                        <span>Posted on <?php echo date('F j, Y', strtotime($result['CreatedAt'])); ?></span>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php else: ?>
            <div class="no-results">
                <?php if (empty($search_query)): ?>
                    <p>Please enter a search term.</p>
                <?php else: ?>
                    <p>No results found for "<?php echo htmlspecialchars($search_query); ?>".</p>
                    <p>Try different keywords or check your spelling.</p>
                <?php endif; ?>
            </div>
        <?php endif; ?>
    </div>

    <footer>
        <div class="footer-container">
            <div class="footer-section">
                <h4>Contact Us</h4>
                <p>Email: peerflect@gmail.com</p>
                <p>Phone: 092709049616</p>
            </div>
            <div class="footer-section">
                <h4>Quick Links</h4>
                <ul>
                    <li><a href="index.php">Home</a></li>
                    <li><a href="topics.php">Topics</a></li>
                    <li><a href="index.php?scroll=about">About</a></li>
                </ul>
            </div>
            <div class="footer-section">
                <h4>Follow Us</h4>
                <div class="social-icons">
                    <a href="#"><i class="fa-brands fa-facebook-f"></i></a>
                    <a href="#"><i class="fa-brands fa-twitter"></i></a>
                    <a href="#"><i class="fa-brands fa-linkedin-in"></i></a>
                    <a href="#"><i class="fa-brands fa-instagram"></i></a>
                </div>
            </div>
        </div>
        <p>&copy; 2024 Collaborative Reviewer for Computer Networking. All rights reserved.</p>
    </footer>
</body>
</html>