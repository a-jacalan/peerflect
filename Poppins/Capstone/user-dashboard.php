<?php
session_start();

// Check if the user is logged in, if not redirect to login page
if (!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true) {
    header("location: login.php");
    exit;
}

// Include config file
require_once "config.php";
require_once "check-banned.php";;

// Define variables and initialize with empty values
$username = $email = $fullname = $profile_image = $user_type = "";

// Prepare a select statement
$sql = "SELECT username, email, CONCAT(firstname, ' ', lastname) AS fullname, profileimageurl, isContributor, isAdmin FROM Users WHERE userid = ?";

if ($stmt = $conn->prepare($sql)) {
    // Bind variables to the prepared statement as parameters
    $stmt->bind_param("i", $param_id);

    // Set parameters
    $param_id = $_SESSION["id"];

    // Attempt to execute the prepared statement
    if ($stmt->execute()) {
        // Store result
        $stmt->store_result();

        // Check if username exists, if yes then fetch user information
        if ($stmt->num_rows == 1) {
            // Bind result variables
            $stmt->bind_result($username, $email, $fullname, $profile_image, $isContributor, $isAdmin);
            if ($stmt->fetch()) {
            }
        }
    }
}

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>User Dashboard</title>
    <link rel="stylesheet" href="style.css">
    <style>
        .user-profile {
            display: flex;
            border: 2px solid #8f75ec;
            padding: 20px;
            border-radius: 8px;
            width: 500px;
        }
        .profile-info {
            display: flex;
            flex-direction: column;
            margin-right: 15px;
        }
        .profile-info img {
            width: 150px;
            height: 150px;
            border-radius: 50%;
            margin-bottom: 10px;
        }
        .name {
            font-weight: bold;
            text-transform: capitalize;
        }
        .contributor-status {
            margin-top: auto;
        }
        .contributor-status button {
            background-color: #007bff;
            color: #fff;
            border: none;
            padding: 5px 10px;
            border-radius: 5px;
            cursor: pointer;
        }
        .comments-section {
            position: relative;
        }
        .rating {
            position: absolute;
            top: 0;
            right: 0;
            margin: 10px;
        }
        .activity-log {
            border: 3px solid #8f75ec;
            border-radius: 5px;
            padding: 10px;
            margin-top: 10px;
            margin-bottom: 10px;
            margin-left: 60px;
            margin-right: 60px;
        }
        .activity-log .activity {
            border-bottom: 3px solid #8f75ec;
            padding-bottom: 10px;
            margin-bottom: 10px;
        }
        .activity-log .activity:last-child {
            border-bottom: none;
            margin-bottom: 0;
        }
        .activity-log .activity p {
            margin: 5px 0;
        }
        .activity-log .activity-date {
            float: right;
        }
    </style>
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
                <?php if(isset($_SESSION["loggedin"]) && $_SESSION["loggedin"] === true) { ?>
                    <div class="dropdown">
                        <a href="#" class="account-link active">Account</a>
                        <div class="dropdown-content">
                            <a href="user-dashboard.php">Dashboard</a>
                            <a href="user-settings.php">Settings</a>
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
    <div class="user-profile">
        <div class="profile-info">
            <img src="<?php echo htmlspecialchars($profile_image); ?>" alt="Profile Image">
        </div>
        <div>
            <div class="name"><?php echo htmlspecialchars($fullname); ?></div>
            <?php if($isContributor == 0): ?>
            <div class="contributor-status">
                <p><strong>Contributor:</strong> Not a Contributor</p>
                <button onclick="openApplyContributorPage()">Apply as Contributor</button>
            </div>
            <?php elseif($isContributor == 1): ?>
            <div class="contributor-status">
                <p><strong>Contributor:</strong> Yes</p>
                <button onclick="openCreatePostPage()">Create Post</button>
            </div>
            <?php endif; ?>
        </div>
    </div>
    <div class="activity-log">
        <h2>Activity Logs</h2>
        <?php
        $userID = $_SESSION["id"]; // Assuming the user ID is stored in the session

        $activity_query = "SELECT a.Action, p.Title, a.CreatedAt, a.PostID, u.firstname, u.lastname 
                           FROM ActivityLog a
                           JOIN Posts p ON a.PostID = p.PostID
                           JOIN Users u ON a.UserID = u.userid
                           WHERE a.UserID = ?
                           ORDER BY a.CreatedAt DESC";

        if ($stmt = $conn->prepare($activity_query)) {
            $stmt->bind_param("i", $userID);
            if ($stmt->execute()) {
                $result = $stmt->get_result();
                while ($row = $result->fetch_assoc()) {
                    $firstname = ucfirst(strtolower($row["firstname"]));
                    $lastname = ucfirst(strtolower($row["lastname"]));
                    $formatted_date = date("F j, Y", strtotime($row["CreatedAt"]));
                    echo '<div class="activity">';
                    echo '<p>' . htmlspecialchars($firstname) . ' ' . htmlspecialchars($lastname) . ' ' . htmlspecialchars($row["Action"]) . ' <a href="post.php?postID=' . htmlspecialchars($row["PostID"]) . '">' . htmlspecialchars($row["Title"]) . '</a>. <span class="activity-date">' . $formatted_date . '</span></p>';
                    echo '</div>';
                }
            } else {
                echo "Error executing activity query: " . $stmt->error;
            }
            $stmt->close(); // Close the statement after use
        } else {
            echo "Error preparing activity query: " . $conn->error;
        }
        ?>
    </div>

    <?php $conn->close(); ?>

    <script>
    function openApplyContributorPage() {
        var width = 600; // Width of the new window
        var height = 400; // Height of the new window

        // Calculate the position to center the window
        var left = (screen.width / 2) - (width / 2);
        var top = (screen.height / 2) - (height / 2);

        // Open the new window with the calculated position
        window.open('apply-contributor.php', 'ApplyContributorWindow', 'width=' + width + ', height=' + height + ', left=' + left + ', top=' + top);
    }
    function openCreatePostPage() {
        window.location.href = 'create-post.php';
    }
    </script>
</body>
</html>
