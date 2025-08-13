<?php
session_start();

// Check if the user is not logged in or is not an admin, redirect to user dashboard
if (!isset($_SESSION["loggedin"]) || !isset($_SESSION["usertype"]) || $_SESSION["usertype"] !== "admin") {
    header("location: login.php");
    exit;
}

// Include database connection
require_once "config.php";
require_once "check-banned.php";;

// Check if the form is submitted
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Collect form data
    $username = trim($_POST["username"]);
    $email = trim($_POST["email"]);
    $firstname = trim($_POST["firstname"]);
    $lastname = trim($_POST["lastname"]);
    $school = trim($_POST["school"]);
    $usertype = trim($_POST["usertype"]);
    $password = trim($_POST["password"]);
    $profileImageUrl = "/profile-image/default.jpg"; // Default profile image

    // Validate input
    if (empty($username) || empty($email) || empty($firstname) || empty($lastname) || empty($school) || empty($password)) {
        $_SESSION["error"] = "Please fill all required fields.";
        header("location: user-management.php");
        exit;
    }

    // Check if username or email already exists
    $sql_check = "SELECT * FROM Users WHERE Username = ? OR Email = ?";
    $stmt_check = $conn->prepare($sql_check);
    $stmt_check->bind_param("ss", $username, $email);
    $stmt_check->execute();
    $result_check = $stmt_check->get_result();

    if ($result_check->num_rows > 0) {
        $_SESSION["error"] = "Username or email already exists.";
        header("location: user-management.php");
        exit;
    }

    // Hash the password
    $hashedPassword = $password;

    // Prepare an insert statement
    $sql = "INSERT INTO Users (Username, Email, FirstName, LastName, School, UserType, Password, ProfileImageURL) VALUES (?, ?, ?, ?, ?, ?, ?, ?)";

    if ($stmt = $conn->prepare($sql)) {
        // Bind variables to the prepared statement as parameters
        $stmt->bind_param("ssssssss", $username, $email, $firstname, $lastname, $school, $usertype, $password, $profileImageUrl);

        // Attempt to execute the prepared statement
        if ($stmt->execute()) {
            // Get the ID of the newly added user
            $newUserID = $conn->insert_id;
            
            // Log the addition
            $adminID = $_SESSION['id'];
            $action = "Added " . ucfirst($usertype); // e.g., "Added Professor" or "Added Student"
            $details = "Added $usertype with ID: $newUserID, Username: $username, School: $school";
            $sqlLogAddition = "INSERT INTO adminlog (AdminID, Action, Details) VALUES (?, ?, ?)";
            $stmtLogAddition = $conn->prepare($sqlLogAddition);
            $stmtLogAddition->bind_param("iss", $adminID, $action, $details);
            $stmtLogAddition->execute();

            // User added successfully
            $_SESSION["success"] = "New user added successfully.";
            header("location: user-management.php");
            exit;
        } else {
            $_SESSION["error"] = "Something went wrong. Please try again later.";
            header("location: user-management.php");
            exit;
        }

        // Close statement
        $stmt->close();
    }

    // Close connection
    $conn->close();
} else {
    // If not a POST request, redirect to user management page
    header("location: user-management.php");
    exit;
}
?>