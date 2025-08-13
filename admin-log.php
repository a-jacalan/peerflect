<?php
session_start();

// Check if the user is not logged in or is not an admin, redirect to login page
if (!isset($_SESSION["loggedin"]) || !isset($_SESSION["usertype"]) || $_SESSION["usertype"] !== "admin") {
    header("location: login.php");
    exit;
}

// Include database connection
require_once "config.php";
require_once "check-banned.php";;

// Function to fetch log history for school admins with pagination
function fetchSchoolAdminLogHistory($conn, $page = 1, $perPage = 5) {
    $offset = ($page - 1) * $perPage;
    $logs = array();

    $sql = "SELECT l.LogID, l.Action, l.Details, l.Timestamp, u.Username, u.School
            FROM AdminLog l
            JOIN Users u ON l.AdminID = u.UserID
            ORDER BY l.Timestamp DESC
            LIMIT ?, ?";

    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ii", $offset, $perPage);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        while($row = $result->fetch_assoc()) {
            $logs[] = $row;
        }
    }

    $stmt->close();

    return $logs;
}

// Function to get total number of log entries
function getTotalLogEntries($conn) {
    $sql = "SELECT COUNT(*) as total FROM AdminLog";
    $result = $conn->query($sql);
    $row = $result->fetch_assoc();
    return $row['total'];
}

// Set up pagination
$perPage = 5;
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$totalEntries = getTotalLogEntries($conn);
$totalPages = ceil($totalEntries / $perPage);

// Ensure the page number is within valid range
$page = max(1, min($page, $totalPages));

$logs = fetchSchoolAdminLogHistory($conn, $page, $perPage);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Log</title>
    <link rel="stylesheet" href="style.css">
</head>
<body class="topics-bg">
    <div class="topnav">
        <div class="logo">
            <a href="index.php"><img src="img/logo.png" alt="Logo"></a>
        </div>
        <div class="menu">
            <a href="index.php">Home</a>
            <a href="topics.php">Topics</a>
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
            <a href="user-management.php">User Management</a>
            <a href="violation-reports.php">Violation Reports</a>
            <a href="add-rewards.php">Rewards Management</a>
            <a class="active" href="admin-log.php">Log History</a>
            <a href="logout.php">Logout</a>
        </div>
        <div class="admin-content">
            <h1>Admin Log</h1>
            <table>
                <thead>
                    <tr>
                        <th>Log ID</th>
                        <th>User</th>
                        <th>School</th>
                        <th>Action</th>
                        <th>Details</th>
                        <th>Timestamp</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($logs as $log): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($log['LogID']); ?></td>
                            <td><?php echo htmlspecialchars($log['Username']); ?></td>
                            <td><?php echo htmlspecialchars($log['School']); ?></td>
                            <td><?php echo htmlspecialchars($log['Action']); ?></td>
                            <td><?php echo htmlspecialchars($log['Details']); ?></td>
                            <td><?php echo htmlspecialchars($log['Timestamp']); ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
            <div class="pagination">
                <?php if ($page > 1): ?>
                    <a href="?page=1">&laquo; First</a>
                    <a href="?page=<?php echo $page - 1; ?>">&lsaquo; Prev</a>
                <?php endif; ?>
                
                <?php
                $start = max(1, $page - 2);
                $end = min($totalPages, $page + 2);
                for ($i = $start; $i <= $end; $i++):
                ?>
                    <a href="?page=<?php echo $i; ?>" <?php echo ($i == $page) ? 'class="active"' : ''; ?>><?php echo $i; ?></a>
                <?php endfor; ?>
                
                <?php if ($page < $totalPages): ?>
                    <a href="?page=<?php echo $page + 1; ?>">Next &rsaquo;</a>
                    <a href="?page=<?php echo $totalPages; ?>">Last &raquo;</a>
                <?php endif; ?>
            </div>
        </div>
    </div>
    <script src="js/transition.js"></script>
</body>
</html>