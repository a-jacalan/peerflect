<?php
session_start(); // Start the session
include_once 'config.php';

// Check if the form is submitted
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Check if the user is logged in
    if (!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true) {
        header("location: login.php");
        exit;
    }

    // Get the UserID from the session variable
    $userID = $_SESSION["id"];

    // Include config file and establish database connection
    require_once "config.php";
require_once "check-banned.php";

    // Check if the user exists
    $stmt_check_user = $conn->prepare("SELECT UserID FROM Users WHERE UserID = ?");
    $stmt_check_user->bind_param("i", $userID);
    $stmt_check_user->execute();
    $result_check_user = $stmt_check_user->get_result();

    if ($result_check_user->num_rows === 0) {
        echo "Error: User not found.";
        exit;
    }

    // Fetch the UserID
    $row = $result_check_user->fetch_assoc();
    $userID = $row['UserID'];

    // Check if postTitle is empty
    if(empty($_POST['postTitle'])) {
        echo "Error: Title cannot be empty.";
        exit;
    }

    // Check if postReference is empty
    if(empty($_POST['postReference'])) {
        echo "Error: Reference cannot be empty.";
        exit;
    }
    
    $postTitle = $_POST['postTitle'];
    $postReference = $_POST['postReference'];
    $mainCategory = $_POST['mainCategory']; 
    $subcategory = $_POST['subcategory'];
    
    // Check if postContent, answer, and explanation arrays are set
    if(isset($_POST['postContent']) && isset($_POST['answer']) && isset($_POST['explanation'])) {
        $postContents = $_POST['postContent'];
        $answers = $_POST['answer'];
        $explanations = $_POST['explanation'];
    
        // Initialize the $images array
        $questionImages = isset($_FILES['questionImage']) ? $_FILES['questionImage'] : [];
        $answerImages = isset($_FILES['answerImage']) ? $_FILES['answerImage'] : [];    

        // Insert into Posts table
        $stmt_insert_post = $conn->prepare("INSERT INTO Posts (Title, MainCategory, SubCategory, UserID, IsApproved, reference, CreatedAt) VALUES (?, ?, ?, ?, ?, ?, NOW())");
        $stmt_insert_post->bind_param("ssssis", $postTitle, $mainCategory, $subcategory, $userID, $isApproved, $postReference);
        $isApproved = 1; // Assuming new posts are not approved by default
        $stmt_insert_post->execute();
        $postID = $stmt_insert_post->insert_id; // Get the auto-generated PostID
    
        // Prepare and execute insert statement for each set of questions and answers
        $stmt_insert_question = $conn->prepare("INSERT INTO Questions (QuestionContent, PostID, AnswerContent, QuestionImageURL, AnswerImageURL, Explanation) VALUES (?, ?, ?, ?, ?, ?)");
        
        for ($i = 0; $i < count($postContents); $i++) {
            $postContent = $postContents[$i];
            $answer = $answers[$i];
            $explanation = $explanations[$i];
    
            // Handle question image upload
            $questionImageURL = null;
            if (!empty($questionImages['name'][$i])) {
                $targetDir = "./question-img/";
                $questionImageURL = $targetDir . basename($questionImages["name"][$i]);
                move_uploaded_file($questionImages["tmp_name"][$i], $questionImageURL);
            }
    
            // Handle answer image upload
            $answerImageURL = null;
            if (!empty($answerImages['name'][$i])) {
                $targetDir = "./answer-img/";
                $answerImageURL = $targetDir . basename($answerImages["name"][$i]);
                move_uploaded_file($answerImages["tmp_name"][$i], $answerImageURL);
            }
    
            // Insert into Questions table
            $stmt_insert_question->bind_param("sissss", $postContent, $postID, $answer, $questionImageURL, $answerImageURL, $explanation);
            
            if (!$stmt_insert_question->execute()) {
                echo "Error: " . $stmt_insert_question->error;
                exit;
            }
        }

        // Log the activity
        $action = "posted";
        $stmt_log_activity = $conn->prepare("INSERT INTO ActivityLog (UserID, Action, PostID, CreatedAt) VALUES (?, ?, ?, NOW())");
        $stmt_log_activity->bind_param("isi", $userID, $action, $postID);
        $stmt_log_activity->execute();
        $stmt_log_activity->close();

        // Close statements and connection
        $stmt_check_user->close();
        $stmt_insert_post->close();
        $stmt_insert_question->close();
        $conn->close();

        // Set a session variable for the success message
        $_SESSION['post_success'] = true;

        // Redirect to the same page to avoid form resubmission
        header("Location: ".$_SERVER['PHP_SELF']);
        exit();
    } else {
        echo "Error: Missing postContent, answer, or explanation data.";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Create Post</title>
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
                    <div class="dropdown">
                        <a href="#" class="account-link"><?php echo htmlspecialchars($fullname); ?></a>
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
    <div class="create-post-container">
        <div class="create-post-form">
            <h2>Create a New Post</h2>
            <form id="postForm" method="post" action="create-post.php" enctype="multipart/form-data">
            <div class="form-group">
                <label for="mainCategory">Main Category: </label>
                <select id="mainCategory" name="mainCategory" onchange="populateSubcategories()">
                    <option value="" disabled selected>---------------</option>
                    <option value="fundamentals">Network Fundamentals</option>
                    <option value="infrastracture">Network Infrastructure</option>
                    <option value="security">Network Security</option>
                </select>
            </div>
                <div class="form-group">
                    <label for="subcategory">Subcategory: </label>
                    <select id="subcategory" name="subcategory">
                        <!-- Subcategories will be populated dynamically based on the selected main category -->
                    </select>
                </div>
                <div class="form-group">
                    <label for="postTitle">Title: </label>
                    <input type="text" id="postTitle" name="postTitle" required>
                </div>
                <div class="form-group">
                    <label for="postReference">Reference (Book): </label>
                    <input type="text" id="postReference" name="postReference" required>
                </div>
                <div id="questionAnswerSets">
                    <div class="question-answer-set">
                        <div class="question-header">
                            <h3>Question</h3>
                            <button type="button" class="remove-question" onclick="removeQuestion(this)">X</button>
                        </div>
                        <div class="form-group">
                            <label for="postContent">Question or Statement: </label>
                            <textarea name="postContent[]" required></textarea>
                        </div>
                        <div class="form-group">
                            <label for="questionImage">Add Question Image (optional): </label>
                            <input type="file" name="questionImage[]" accept="image/*">
                        </div>
                        <div class="form-group">
                            <label for="answer">Answer: </label>
                            <textarea name="answer[]" required></textarea>
                        </div>
                        <div class="form-group">
                            <label for="answerImage">Add Answer Image (optional): </label>
                            <input type="file" name="answerImage[]" accept="image/*">
                        </div>
                        <div class="form-group">
                            <label for="explanation">Explanation: </label>
                            <textarea name="explanation[]" required></textarea>
                        </div>
                    </div>
                </div>
                <div class="button-container">
                    <button type="button" class="action" onclick="addQuestion()">Add Question</button>
                </div>
                <div class="button-container">
                    <button type="submit" class="action">Submit</button>
                </div>
            </form>
        </div>
    </div>

    <div id="successNotification" class="notification">
        Posted successfully!
    </div>

    <script>
        function addQuestion() {
            var questionAnswerSets = document.getElementById("questionAnswerSets");
            var newSet = document.createElement("div");
            newSet.classList.add("question-answer-set");
            newSet.innerHTML = `
                <div class="question-header">
                    <h3>Question</h3>
                    <button type="button" class="remove-question" onclick="removeQuestion(this)">X</button>
                </div>
                <div class="form-group">
                    <label for="postContent">Question or Statement: </label>
                    <textarea name="postContent[]" required></textarea>
                </div>
                <div class="form-group">
                    <label for="questionImage">Add Question Image (optional): </label>
                    <input type="file" name="questionImage[]" accept="image/*">
                </div>
                <div class="form-group">
                    <label for="answer">Answer: </label>
                    <textarea name="answer[]" required></textarea>
                </div>
                <div class="form-group">
                    <label for="answerImage">Add Answer Image (optional): </label>
                    <input type="file" name="answerImage[]" accept="image/*">
                </div>
                <div class="form-group">
                    <label for="explanation">Explanation: </label>
                    <textarea name="explanation[]" required></textarea>
                </div>
            `;
            questionAnswerSets.appendChild(newSet);
        }
        
        function removeQuestion(button) {
            var questionSet = button.closest('.question-answer-set');
            questionSet.remove();
        }

        function populateSubcategories() {
            var mainCategory = document.getElementById("mainCategory").value;
            var subcategoryDropdown = document.getElementById("subcategory");
            subcategoryDropdown.innerHTML = ""; // Clear existing options
            switch (mainCategory) {
                case "fundamentals":
                    subcategoryDropdown.innerHTML += "<option value='osi'>OSI Model</option>";
                    subcategoryDropdown.innerHTML += "<option value='tcp-ip'>TCP/IP Protocol Suite</option>";
                    subcategoryDropdown.innerHTML += "<option value='ethernet-lan-tech'>Ethernet and LAN Technologies</option>";
                    subcategoryDropdown.innerHTML += "<option value='wan'>WAN Technologies</option>";
                    subcategoryDropdown.innerHTML += "<option value='ipv4-ipv6'>IPv4 and IPv6 Addressing</option>";
                    subcategoryDropdown.innerHTML += "<option value='sub-sup-net'>Subnetting and Supernetting</option>";
                    break;
                case "infrastracture":
                    subcategoryDropdown.innerHTML += "<option value='routers-protocol'>Router and Routing Protocol</option>";
                    subcategoryDropdown.innerHTML += "<option value='switches-vlan'>Switches and VLANs</option>";
                    subcategoryDropdown.innerHTML += "<option value='access-points-wlan'>Wireless Access Points and WLANs</option>";
                    subcategoryDropdown.innerHTML += "<option value='nat'>Network Address Translation (NAT)</option>";
                    subcategoryDropdown.innerHTML += "<option value='qos'>Quality of Services (QoS)</option>";
                    break;
                case "security":
                    subcategoryDropdown.innerHTML += "<option value='ids'>Firewalls and Intrusion Detection Systems (IDS)</option>";
                    subcategoryDropdown.innerHTML += "<option value='vpn'>Virtual Private Networks (VPN)</option>";
                    subcategoryDropdown.innerHTML += "<option value='acl'>Access Control Lists (ACLs)</option>";
                    subcategoryDropdown.innerHTML += "<option value='ssl'>Secure Shell (SSH) and Secure Sockets Layer (SSL)</option>";
                    subcategoryDropdown.innerHTML += "<option value='hardening-techniques'>Network Hardening Techniques</option>";
                    break;
            }
        }

        function showNotification() {
            var notification = document.getElementById('successNotification');
            notification.style.display = 'block';
            setTimeout(function() {
                notification.style.display = 'none';
            }, 5000); // Hide after 5 seconds
        }

        // Check if the success message should be shown
        <?php
        if (isset($_SESSION['post_success']) && $_SESSION['post_success']) {
            echo "showNotification();";
            unset($_SESSION['post_success']); // Clear the session variable
        }
        ?>
    </script>
<script src="js/transition.js"></script>
</body>
</html>