<?php 
// Database connection parameters
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "peerflect";

// Create connection
$conn = new mysqli($servername, $username, $password, $dbname);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

$username = "";
$fullname = "";
$profile_image = "";
$isVerified = false;

// Check if user is logged in
if (isset($_SESSION['id']) && !empty($_SESSION['id'])) {
    $isLoggedIn = true;
    
    // Prepare a select statement
    $sql = "SELECT username, email, CONCAT(firstname, ' ', lastname) AS fullname, profileimageurl, isVerified FROM Users WHERE userid = ?";

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
                $stmt->bind_result($username, $email, $fullname, $profile_image, $isVerified);
                $stmt->fetch();
            }
        }
        $stmt->close();
    }
}
?>