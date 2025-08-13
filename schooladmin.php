<?php
session_start();

// Check if the user is not logged in or is not an admin, redirect to user dashboard
if(!isset($_SESSION["loggedin"]) || !isset($_SESSION["usertype"]) || $_SESSION["usertype"] !== "schooladmin") {
    header("location: login.php");
    exit;
}

// Include database connection
require_once "config.php";
require_once "check-banned.php";;

// Fetch distinct schools from the Users table for the dropdown
$sqlSchools = "SELECT DISTINCT School FROM Users WHERE School IS NOT NULL";
$resultSchools = $conn->query($sqlSchools);
$schools = [];
while ($row = $resultSchools->fetch_assoc()) {
    $schools[] = $row['School'];
}

// Handle delete user action
if (isset($_POST["delete_user"])) {
    $userID = $_POST["delete_user"];
    // Perform deletion query
    $sqlDeleteUser = "DELETE FROM Users WHERE UserID = ?";
    $stmtDeleteUser = $conn->prepare($sqlDeleteUser);
    $stmtDeleteUser->bind_param("i", $userID);
    $stmtDeleteUser->execute();
}

// Handle bulk delete action
if (isset($_POST["bulk_delete_users"])) {
    $selectedUsers = $_POST["selected_users"] ?? [];
    if (!empty($selectedUsers)) {
        $placeholders = implode(',', array_fill(0, count($selectedUsers), '?'));
        $sqlBulkDelete = "DELETE FROM Users WHERE UserID IN ($placeholders)";
        $stmtBulkDelete = $conn->prepare($sqlBulkDelete);
        $stmtBulkDelete->bind_param(str_repeat('i', count($selectedUsers)), ...$selectedUsers);
        $stmtBulkDelete->execute();
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
$usersPerPage = 10;
$userPage = isset($_GET['user_page']) ? (int)$_GET['user_page'] : 1;
$userOffset = ($userPage - 1) * $usersPerPage;

$userSchool = $_SESSION['school'];
$sqlUsers = "SELECT UserID, Username, Email, School, usertype FROM Users WHERE School = ? AND usertype = 'contributor' LIMIT ? OFFSET ?";
$stmtUsers = $conn->prepare($sqlUsers);
$stmtUsers->bind_param("sii", $userSchool, $usersPerPage, $userOffset);
$stmtUsers->execute();
$resultUsers = $stmtUsers->get_result();
$users = $resultUsers->fetch_all(MYSQLI_ASSOC);

$sqlTotalUsers = "SELECT COUNT(*) as total FROM Users WHERE School = ? AND usertype = 'contributor'";
$stmtTotalUsers = $conn->prepare($sqlTotalUsers);
$stmtTotalUsers->bind_param("s", $userSchool);
$stmtTotalUsers->execute();
$totalUsers = $stmtTotalUsers->get_result()->fetch_assoc()['total'];
$totalUserPages = ceil($totalUsers / $usersPerPage);

// Define variables and initialize with empty values
$username = $password = $email = $firstname = $lastname = $school = "";
$username_err = $password_err = $email_err = $firstname_err = $lastname_err = $school_err = "";

// Process form data when the form is submitted
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST["add_professor"])) {
    // Validate username
    if (empty(trim($_POST["username"]))) {
        $username_err = "Please enter a username.";
    } else {
        $sql = "SELECT UserID FROM Users WHERE username = ?";
        
        if ($stmt = $conn->prepare($sql)) {
            $stmt->bind_param("s", $param_username);
            $param_username = trim($_POST["username"]);
            
            if ($stmt->execute()) {
                $stmt->store_result();
                
                if ($stmt->num_rows > 0) {
                    $username_err = "This username is already taken.";
                } else {
                    $username = trim($_POST["username"]);
                }
            } else {
                echo "Oops! Something went wrong. Please try again later.";
            }
    
            $stmt->close();
        }
    }

    // Validate password
    if (empty(trim($_POST["password"]))) {
        $password_err = "Please enter a password.";
    } else {
        $password = trim($_POST["password"]);
    }

    // Validate email
    if (empty(trim($_POST["email"]))) {
        $email_err = "Please enter the email address.";
    } else {
        $email = trim($_POST["email"]);
    }

    // Validate firstname
    if (empty(trim($_POST["firstname"]))) {
        $firstname_err = "Please enter the first name.";
    } else {
        $firstname = trim($_POST["firstname"]);
    }

    // Validate lastname
    if (empty(trim($_POST["lastname"]))) {
        $lastname_err = "Please enter the last name.";
    } else {
        $lastname = trim($_POST["lastname"]);
    }

    // Validate school
    if (empty(trim($_POST["school"]))) {
        $school_err = "Please choose the school.";
    } else {
        $school = trim($_POST["school"]);
    }

    // Check input errors before inserting in database
    if (empty($username_err) && empty($password_err) && empty($email_err) && empty($firstname_err) && empty($lastname_err) && empty($school_err)) {
        // Start transaction
        $conn->begin_transaction();

        try {
            // Prepare an insert statement with ProfileImageURL set to default
            $sql = "INSERT INTO Users (username, password, email, firstname, lastname, school, usertype, isVerified, ProfileImageURL) VALUES (?, ?, ?, ?, ?, ?, 'contributor', 1, './profile-img/default.jpg')";
            
            $stmt = $conn->prepare($sql);
            // Bind variables to the prepared statement as parameters
            $stmt->bind_param("ssssss", $param_username, $param_password, $param_email, $param_firstname, $param_lastname, $param_school);
            
            // Set parameters
            $param_username = $username;
            $param_password = $password;
            $param_email = $email;
            $param_firstname = $firstname;
            $param_lastname = $lastname;
            $param_school = $school;
    
            // Execute the prepared statement
            $stmt->execute();
            
            // Get the ID of the newly inserted professor
            $newProfessorId = $conn->insert_id;
            
            // Log the action in AdminLog
            $adminId = $_SESSION['id']; // Assuming you store the admin's ID in the session
            $action = "Added Professor";
            $details = "Added professor with ID: $newProfessorId, Username: $username, School: $school";
            
            $logSql = "INSERT INTO AdminLog (AdminID, Action, Details) VALUES (?, ?, ?)";
            $logStmt = $conn->prepare($logSql);
            $logStmt->bind_param("iss", $adminId, $action, $details);
            $logStmt->execute();
            
            // Commit the transaction
            $conn->commit();
            
            // Redirect to user management page
            header("location: schooladmin.php");
            exit;
        } catch (Exception $e) {
            // An error occurred, rollback the transaction
            $conn->rollback();
            echo "Oops! Something went wrong. Please try again later. Error: " . $e->getMessage();
        }

        // Close statements
        $stmt->close();
        $logStmt->close();
    }
}

// Close connection
$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>School Admin</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.1.1/css/all.min.css">
    <link rel="stylesheet" href="style.css">
    <style>
        .application-actions form {
            display: flex;
            justify-content: space-between;
            margin: 10px;
        }
        .pagination {
            margin-top: 20px;
            text-align: center;
        }
        .pagination a, .pagination .ellipsis {
            color: black;
            display: inline-block;
            padding: 8px 16px;
            text-decoration: none;
            transition: background-color .3s;
            border: 1px solid #ddd;
            margin: 0 4px;
        }
        .pagination a.active {
            background-color: #4CAF50;
            color: white;
            border: 1px solid #4CAF50;
        }
        .pagination a:hover:not(.active) {
            background-color: #ddd;
        }
        .pagination .ellipsis {
            border: none;
            padding: 8px 0;
        }
        .bulk-actions {
            margin-bottom: 10px;
            display: flex;
            align-items: center;
        }
        .checkbox-column {
            width: 30px;
        }
        .icon-button {
            background: #8f75ec;
            border: none;
            cursor: pointer;
            font-size: 1.2em;
            padding: 5px;
            margin-left: 10px;
        }
        .delete-icon {
            color: #ff4136;
        }
        .approve-icon {
            color: #2ecc40;
        }
        .reject-icon {
            color: #ff851b;
        }
        .select-all-label {
            margin-right: 15px;
        }
        .professors-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .add-professor-btn {
            background-color: #4CAF50;
            border: none;
            color: white;
            padding: 5px 10px;
            cursor: pointer;
            width: 100px;
        }
        .modal {
            display: none;
            position: fixed;
            z-index: 1;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            overflow: auto;
            background-color: rgba(0,0,0,0.4);
        }
        .modal-content {
            background-color: #fefefe;
            margin: 15% auto;
            padding: 20px;
            border: 1px solid #888;
            width: 80%;
            max-width: 500px;
        }
        .close {
            color: #aaa;
            float: right;
            font-size: 28px;
            font-weight: bold;
        }
        .close:hover,
        .close:focus {
            color: black;
            text-decoration: none;
            cursor: pointer;
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
            <a href="topics.php">Topics</a>
            <a href="index.php?scroll=about">About</a>
            <div class="search-bar">
                <form action="search-results.php" method="GET">
                    <input type="text" name="q" placeholder="Search...">
                    <button type="submit">Search</button>
                </form>
            </div>
            <div class="menu-loginreg">
                <a class="active" href="schooladmin.php">School Admin</a>
            </div>
        </div>
    </div>
    <div class="admin-container">
        <div class="admin-nav">
            <a class="active" href="schooladmin.php">Manage Professors</a>
            <a href="logout.php">Logout</a>
        </div>
        <div class="admin-content">
            <div class="users">
                <div class="professors-header">
                    <h2>Professors</h2>
                    <button id="openModalBtn" class="add-professor-btn">Add Professor</button>
                </div>
                <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="post">
                    <div class="bulk-actions">
                        <input type="checkbox" id="select-all-users">
                        <label for="select-all-users" class="select-all-label">Select All</label>
                        <button type="submit" name="bulk_delete_users" class="icon-button delete-icon" title="Delete Selected" onclick="return confirm('Are you sure you want to delete the selected users?');">
                            <i class="fas fa-trash-alt"></i>
                        </button>
                    </div>
                    <?php foreach ($users as $user): ?>
                        <div class="user-item">
                            <div class="checkbox-column">
                                <input type="checkbox" name="selected_users[]" value="<?php echo $user['UserID']; ?>" class="user-checkbox">
                            </div>
                            <div class="user-details">
                                <div class="user-username">Username: <?php echo htmlspecialchars($user['Username']); ?></div>
                                <div class="user-email">Email: <?php echo htmlspecialchars($user['Email']); ?></div>
                                <div class="user-email">User Type: <?php echo htmlspecialchars($user['usertype']); ?></div>
                            </div>
                            <div class="user-actions">
                                <button type="submit" name="delete_user" value="<?php echo $user['UserID']; ?>" class="icon-button delete-icon" title="Delete User" onclick="return confirm('Are you sure you want to delete this user?');">
                                    <i class="fas fa-trash-alt"></i>
                                </button>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </form>
                <?php echo generatePagination($userPage, $totalUserPages, 'user'); ?>
            </div>
        </div>
    </div>

    <!-- The Modal -->
    <div id="addProfessorModal" class="modal">
        <!-- Modal content -->
        <div class="modal-content">
            <span class="close">&times;</span>
            <h2>Add a Professor</h2>
            <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="post">
                <div>
                    <label>Username</label>
                    <input type="text" name="username" value="<?php echo htmlspecialchars($username); ?>">
                    <span style="color: red"><?php echo htmlspecialchars($username_err); ?></span>
                </div>
                <div>
                    <label>Password</label>
                    <input type="password" name="password">
                    <span style="color: red"><?php echo htmlspecialchars($password_err); ?></span>
                </div>
                <div>
                    <label>Email</label>
                    <input type="email" name="email" value="<?php echo htmlspecialchars($email); ?>">
                    <span style="color: red"><?php echo htmlspecialchars($email_err); ?></span>
                </div>
                <div>
                    <label>First Name</label>
                    <input type="text" name="firstname" value="<?php echo htmlspecialchars($firstname); ?>">
                    <span style="color: red"><?php echo htmlspecialchars($firstname_err); ?></span>
                </div>
                <div>
                    <label>Last Name</label>
                    <input type="text" name="lastname" value="<?php echo htmlspecialchars($lastname); ?>">
                    <span style="color: red"><?php echo htmlspecialchars($lastname_err); ?></span>
                </div>
                <div>
                    <label>School: </label>
                    <select name="school">
                        <option value="">Select a School</option>
                        <?php foreach ($schools as $schoolOption): ?>
                            <option value="<?php echo htmlspecialchars($schoolOption); ?>" <?php echo ($school === $schoolOption) ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($schoolOption); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <span style="color: red"><?php echo htmlspecialchars($school_err); ?></span>
                </div>
                <div>
                    <input type="submit" name="add_professor" value="Add Professor">
                </div>
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

        // Get the modal
        var modal = document.getElementById("addProfessorModal");

        // Get the button that opens the modal
        var btn = document.getElementById("openModalBtn");

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