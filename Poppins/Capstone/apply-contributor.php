<?php
session_start();

// Check if the user is logged in
if (!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true) {
    header("location: login.php");
    exit;
}

// Include config file
require_once "config.php";
require_once "check-banned.php";;

// Check if the form is submitted
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Process the form submission

    // Retrieve form data
    $reason = $_POST["reason"]; // Assuming you have a field named reason in the form

    // Directory to save uploaded files
    $target_dir = "application-files/";

    // Check if the directory exists, if not, create it
    if (!file_exists($target_dir)) {
        mkdir($target_dir, 0777, true);
    }

    // Process each uploaded file
    $uploaded_files = [];
    foreach ($_FILES["files"]["tmp_name"] as $key => $tmp_file) {
        $file_name = $_SESSION["username"] . "_" . ($key + 1); // Construct filename
        $target_file = $target_dir . basename($_FILES["files"]["name"][$key]); // Get original file name
        $file_extension = strtolower(pathinfo($target_file, PATHINFO_EXTENSION)); // Get file extension
        $new_file_name = $target_dir . $file_name . "." . $file_extension; // Construct new filename with extension
        move_uploaded_file($tmp_file, $new_file_name); // Move uploaded file to destination with new filename
        $uploaded_files[] = $new_file_name; // Store uploaded file path
    }

    // Convert uploaded file paths to JSON string
    $attached_files_json = json_encode($uploaded_files);

    // Insert the application into the database
    $sql = "INSERT INTO ContributorApplication (UserID, Status, ApplicationText, AttachedFiles) VALUES (?, 'Pending', ?, ?)";

    if ($stmt = $conn->prepare($sql)) {
        // Bind variables to the prepared statement as parameters
        $stmt->bind_param("iss", $param_user_id, $param_reason, $param_attached_files);

        // Set parameters
        $param_user_id = $_SESSION["id"]; // Retrieve user ID from the session
        $param_reason = $reason;
        $param_attached_files = $attached_files_json;

        // Attempt to execute the prepared statement
        if ($stmt->execute()) {
            // Close the window after successful submission
            echo '<script>window.close();</script>';
            exit;
        } else {
            echo "Oops! Something went wrong. Please try again later.";
        }
        // Close statement
        $stmt->close();
    }

    // Close connection
    $conn->close();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Apply as Contributor</title>
    <link rel="stylesheet" href="style.css">
    <style>
        /* Style for the textarea */
        textarea {
            width: 100%;
            height: 200px; /* Fixed height */
            resize: none; /* Disable resizing */
            overflow-y: auto; /* Enable vertical scrolling */
        }
    </style>
</head>
<body class="topics-bg">
    <div class="container">
        <p>Please fill in the form below to apply as a contributor.<br> You can attach some images that could help you qualify.</p>
        <form id="contributorForm" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="post" enctype="multipart/form-data">
            <div class="form-group">
                <label>Reason for Qualification:</label>
                <textarea name="reason" rows="5" cols="40" required></textarea>
            </div>
            <div class="form-group">
                <label>Attach Files:</label>
                <input type="file" name="files[]" multiple required>
            </div>
            <div class="form-group">
                <input type="submit" class="btn" value="Submit">
                <button type="button" class="btn btn-secondary" onclick="closeWindow()">Cancel</button>
            </div>
        </form>
    </div>

    <script>
        function closeWindow() {
            window.close(); // Close the current window
        }
    </script>
</body>
</html>
