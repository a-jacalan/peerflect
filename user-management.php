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

// Handle delete user action
if (isset($_POST["delete_user"])) {
    $userID = $_POST["delete_user"];
    
    // Get the username and school of the user being deleted
    $sqlGetUserInfo = "SELECT Username, School FROM Users WHERE UserID = ?";
    $stmtGetUserInfo = $conn->prepare($sqlGetUserInfo);
    $stmtGetUserInfo->bind_param("i", $userID);
    $stmtGetUserInfo->execute();
    $resultUserInfo = $stmtGetUserInfo->get_result();
    $userInfo = $resultUserInfo->fetch_assoc();
    
    // Perform deletion query
    $sqlDeleteUser = "DELETE FROM Users WHERE UserID = ?";
    $stmtDeleteUser = $conn->prepare($sqlDeleteUser);
    $stmtDeleteUser->bind_param("i", $userID);
    
    if ($stmtDeleteUser->execute()) {
        // Log the deletion
        $adminID = $_SESSION['id'];
        $action = "Deleted User";
        $details = "Deleted user with ID: $userID, Username: {$userInfo['Username']}, School: {$userInfo['School']}";
        $sqlLogDeletion = "INSERT INTO adminlog (AdminID, Action, Details) VALUES (?, ?, ?)";
        $stmtLogDeletion = $conn->prepare($sqlLogDeletion);
        $stmtLogDeletion->bind_param("iss", $adminID, $action, $details);
        $stmtLogDeletion->execute();
    }
}

// Handle bulk delete action
if (isset($_POST["bulk_delete_users"])) {
    $selectedUsers = $_POST["selected_users"] ?? [];
    if (!empty($selectedUsers)) {
        $placeholders = implode(',', array_fill(0, count($selectedUsers), '?'));
        
        // Get user info of users being deleted
        $sqlGetUserInfo = "SELECT UserID, Username, School FROM Users WHERE UserID IN ($placeholders)";
        $stmtGetUserInfo = $conn->prepare($sqlGetUserInfo);
        $stmtGetUserInfo->bind_param(str_repeat('i', count($selectedUsers)), ...$selectedUsers);
        $stmtGetUserInfo->execute();
        $resultUserInfo = $stmtGetUserInfo->get_result();
        $usersInfo = $resultUserInfo->fetch_all(MYSQLI_ASSOC);
        
        $sqlBulkDelete = "DELETE FROM Users WHERE UserID IN ($placeholders)";
        $stmtBulkDelete = $conn->prepare($sqlBulkDelete);
        $stmtBulkDelete->bind_param(str_repeat('i', count($selectedUsers)), ...$selectedUsers);
        
        if ($stmtBulkDelete->execute()) {
            // Log the bulk deletion
            $adminID = $_SESSION['id'];
            $action = "Bulk Deleted Users";
            $sqlLogDeletion = "INSERT INTO adminlog (AdminID, Action, Details) VALUES (?, ?, ?)";
            $stmtLogDeletion = $conn->prepare($sqlLogDeletion);
            foreach ($usersInfo as $user) {
                $details = "Deleted user with ID: {$user['UserID']}, Username: {$user['Username']}, School: {$user['School']}";
                $stmtLogDeletion->bind_param("iss", $adminID, $action, $details);
                $stmtLogDeletion->execute();
            }
        }
    }
}

// Pagination function
function generatePagination($currentPage, $totalPages, $type) {
    $output = '<div class="pagination">';
    $output .= '<a href="?' . $type . '_page=1"' . ($currentPage == 1 ? ' class="active"' : '') . '>1</a>';
    $startPage = max(2, $currentPage - 1);
    $endPage = min($totalPages - 1, $currentPage + 1);
    if ($startPage > 2) {
        $output .= '<span class="ellipsis">...</span>';
    }
    for ($i = $startPage; $i <= $endPage; $i++) {
        $output .= '<a href="?' . $type . '_page=' . $i . '"' . ($i == $currentPage ? ' class="active"' : '') . '>' . $i . '</a>';
    }
    if ($endPage < $totalPages - 1) {
        $output .= '<span class="ellipsis">...</span>';
    }
    if ($totalPages > 1 && $endPage < $totalPages) {
        $output .= '<a href="?' . $type . '_page=' . $totalPages . '"' . ($currentPage == $totalPages ? ' class="active"' : '') . '>' . $totalPages . '</a>';
    }
    $output .= '</div>';
    return $output;
}

// Fetch users with pagination
$usersPerPage = 5;
$userPage = isset($_GET['user_page']) ? (int)$_GET['user_page'] : 1;
$userOffset = ($userPage - 1) * $usersPerPage;

$search = isset($_GET['search']) ? $_GET['search'] : '';
$sqlUsers = "SELECT UserID, Username, Email, CONCAT(Firstname, ' ', LastName) AS Fullname, School, UserType 
             FROM Users 
             WHERE Username LIKE ? OR Email LIKE ? OR CONCAT(Firstname, ' ', LastName) LIKE ?
             LIMIT ? OFFSET ?";
$searchParam = "%$search%";
$stmtUsers = $conn->prepare($sqlUsers);
$stmtUsers->bind_param("sssii", $searchParam, $searchParam, $searchParam, $usersPerPage, $userOffset);
$stmtUsers->execute();
$resultUsers = $stmtUsers->get_result();
$users = $resultUsers->fetch_all(MYSQLI_ASSOC);

$sqlTotalUsers = "SELECT COUNT(*) as total FROM Users 
                  WHERE Username LIKE ? OR Email LIKE ? OR CONCAT(Firstname, ' ', LastName) LIKE ?";
$stmtTotalUsers = $conn->prepare($sqlTotalUsers);
$stmtTotalUsers->bind_param("sss", $searchParam, $searchParam, $searchParam);
$stmtTotalUsers->execute();
$totalUsers = $stmtTotalUsers->get_result()->fetch_assoc()['total'];
$totalUserPages = ceil($totalUsers / $usersPerPage);

// Fetch admin's full name and user type
$adminUsername = $_SESSION["username"];
$sqlAdminInfo = "SELECT CONCAT(FirstName, ' ', LastName) AS FullName, UserType FROM Users WHERE Username = ?";
$stmtAdminInfo = $conn->prepare($sqlAdminInfo);
$stmtAdminInfo->bind_param("s", $adminUsername);
$stmtAdminInfo->execute();
$resultAdminInfo = $stmtAdminInfo->get_result();
$adminInfo = $resultAdminInfo->fetch_assoc();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>User Management - Admin</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.1.1/css/all.min.css">
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
            <a href="index.php?scroll=about">About</a>
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
            <a href="violation-reports.php">Violation Reports</a>
            <a href="add-rewards.php">Rewards Management</a>
            <a href="admin-log.php">Log History</a>
            <a href="logout.php">Logout</a>
        </div>
        <div class="admin-content">
            <div class="users">
                <h2>Users</h2>
                <div class="search-add-container">
                    <form action="" method="get" class="search-form">
                        <input type="text" name="search" placeholder="Search users..." value="<?php echo htmlspecialchars($search); ?>">
                        <button type="submit">Search</button>
                    </form>
                    <button id="addUserBtn" class="add-user-btn">Add User</button>
                </div>
                <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="post" class="users-table">
                    <div class="bulk-actions">
                        <input type="checkbox" id="select-all-users">
                        <label for="select-all-users" class="select-all-label">Select All</label>
                        <button type="submit" name="bulk_delete_users" class="icon-button delete-icon" title="Delete Selected" onclick="return confirm('Are you sure you want to delete the selected users?');">
                            <i class="fas fa-trash-alt"></i>
                        </button>
                    </div>
                    <table class="user-table">
                        <thead>
                            <tr>
                                <th class="checkbox-column"></th>
                                <th>Username</th>
                                <th>Email</th>
                                <th>Full Name</th>
                                <th>School</th>
                                <th>User Type</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($users as $user): ?>
                                <tr>
                                    <td class="checkbox-column">
                                        <input type="checkbox" name="selected_users[]" value="<?php echo $user['UserID']; ?>" class="user-checkbox">
                                    </td>
                                    <td><?php echo htmlspecialchars($user['Username']); ?></td>
                                    <td><?php echo htmlspecialchars($user['Email']); ?></td>
                                    <td><?php echo htmlspecialchars($user['Fullname']); ?></td>
                                    <td><?php echo htmlspecialchars($user['School']); ?></td>
                                    <td><?php echo htmlspecialchars($user['UserType']); ?></td>
                                    <td>
                                        <button type="submit" name="delete_user" value="<?php echo $user['UserID']; ?>" class="icon-button delete-icon" title="Delete User" onclick="return confirm('Are you sure you want to delete this user?');">
                                            <i class="fas fa-trash-alt"></i>
                                        </button>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </form>
                <?php echo generatePagination($userPage, $totalUserPages, 'user'); ?>
            </div>
        </div>
    </div>
    <!-- Modal -->
    <div id="addUserModal" class="modal">
        <div class="modal-content">
            <span class="close">&times;</span>
            <h2>Add New User</h2>
            <form id="addUserForm" action="add-user.php" method="post">
                <label for="username">Username:</label>
                <input type="text" id="username" name="username" required><br><br>
                <label for="email">Email:</label>
                <input type="email" id="email" name="email" required><br><br>
                <label for="firstname">First Name:</label>
                <input type="text" id="firstname" name="firstname" required><br><br>
                <label for="lastname">Last Name:</label>
                <input type="text" id="lastname" name="lastname" required><br><br>
                <label for="school">School:</label>
                <input type="text" id="school" name="school" required><br><br>
                <label for="usertype">User Type:</label>
                <select id="usertype" name="usertype">
                    <option value="regular">User</option>
                    <option value="contributor">Contributor</option>
                    <option value="schooladmin">School Admin</option>
                </select><br><br>
                <label for="password">Password:</label>
                <input type="password" id="password" name="password" required><br><br>
                <input type="submit" value="Add User">
            </form>
        </div>
    </div>
    <script>
        // JavaScript for "Select All" functionality
        document.getElementById('select-all-users').addEventListener('change', function() {
            var checkboxes = document.getElementsByClassName('user-checkbox');
            for (var checkbox of checkboxes) {
                checkbox.checked = this.checked;
            }
        });

        // Modal JavaScript
        // Get the modal
        var modal = document.getElementById("addUserModal");

        // Get the button that opens the modal
        var btn = document.getElementById("addUserBtn");

        // Get the <span> element that closes the modal
        var span = document.getElementsByClassName("close")[0];

        // When the user clicks the button, open the modal 
        btn.onclick = function() {
            modal.style.display = "block";
        }

        // When the user clicks on <span> (x), close the modal
        span.onclick = function() {
            modal.style.display = "none";
        }

        // When the user clicks anywhere outside of the modal, close it
        window.onclick = function(event) {
            if (event.target == modal) {
                modal.style.display = "none";
            }
        }
    </script>
    <script src="js/transition.js"></script>
</body>
</html>