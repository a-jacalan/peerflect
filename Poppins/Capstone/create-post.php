<?php
session_start(); // Start the session

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
require_once "check-banned.php";;

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
    
    $postTitle = $_POST['postTitle'];
    $mainCategory = $_POST['mainCategory']; 
    $subcategory = $_POST['subcategory']; // Ensure to retrieve subcategory data as well
    
    // Check if postContent and answer arrays are set
    if(isset($_POST['postContent']) && isset($_POST['answer'])) {
        $postContents = $_POST['postContent'];
        $answers = $_POST['answer'];
    
        // Initialize the $images array
        if(isset($_FILES['image']) && !empty($_FILES['image']['name'])) {
            $images = $_FILES['image'];
        } else {
            // If no files are uploaded, initialize $images as an empty array
            $images = [];
        }

        // Insert into Posts table
        $stmt_insert_post = $conn->prepare("INSERT INTO Posts (Title, MainCategory, SubCategory, UserID, IsApproved, CreatedAt) VALUES (?, ?, ?, ?, ?, NOW())");
        $stmt_insert_post->bind_param("ssssi", $postTitle, $mainCategory, $subcategory, $userID, $isApproved);
        $isApproved = false; // Assuming new posts are not approved by default
        $stmt_insert_post->execute();
        $postID = $stmt_insert_post->insert_id; // Get the auto-generated PostID
    
        // Prepare and execute insert statement for each set of questions and answers
        for ($i = 0; $i < count($postContents); $i++) {
            $postContent = $postContents[$i];
            $answer = $answers[$i];
    
            // Handle image upload
            $imageURL = null;
            if (!empty($images['name'][$i])) {
                $targetDir = "./post-img/";
                $imageURL = $targetDir . basename($images["name"][$i]);
                move_uploaded_file($images["tmp_name"][$i], $imageURL);
            }
    
            // Insert into Questions table
            $stmt_insert_question = $conn->prepare("INSERT INTO Questions (QuestionContent, PostID, AnswerContent, ImageURL) VALUES (?, ?, ?, ?)");
            $stmt_insert_question->bind_param("siss", $postContent, $postID, $answer, $imageURL);
            $stmt_insert_question->execute();
        }
    
        // Close statements
        $stmt_check_user->close();
        $stmt_insert_post->close();
        $stmt_insert_question->close();
    
        // Close connection
        $conn->close();
    
        echo "Post submitted successfully!";
    } else {
        echo "Error: Missing postContent or answer data.";
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
    <style>
        .create-post-container{
            margin: 0;
            padding: 0;
            display: flex;
            justify-content: center;
            align-items: center;
            height: 90vh;
        }
        .create-post-form {
            width: 600px;
            background-color: #fff;
            border-radius: 10px;
            border: 2px solid #8f75ec;
            padding: 20px;
            box-shadow: 0 0 20px rgba(0, 0, 0, 0.479);
            margin: auto;
        }

        .form-group {
            margin-bottom: 20px;
        }

        label {
            font-size: 14px;
            display: block;
            margin-bottom: 5px;
        }

        select,
        input[type="text"],
        textarea {
            width: 100%;
            padding: 10px;
            font-size: 14px;
            border: 1px solid #ccc;
            border-radius: 5px;
            box-sizing: border-box;
        }

        textarea {
            width: 100%;
            padding: 10px;
            font-size: 14px;
            border: 1px solid #ccc;
            border-radius: 5px;
            box-sizing: border-box;
            resize: none; /* Disable resizing */
            height: 50px; /* Set initial height */
        }

        button[type="submit"] {
            color: #fff;
            padding: 10px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-size: 16px;
            margin-top: 10px;
            height: 40px;
        }

        button[type="submit"]:hover {
            background-color: #7a5cc5;
        }

        .question-answer-set {
            border: 2px solid black;
            padding: 15px;
            border-radius: 10px;
            margin-bottom: 20px;
        }   
        #postForm button{
            width: 20%;
            display: block;
        }
        input[type="file"] {
            width: 100%;
            padding: 10px;
            font-size: 14px;
            border: 1px solid #ccc;
            border-radius: 5px;
            box-sizing: border-box;
            margin-bottom: 10px;
        }
        .button-container{
            display: flex;
            justify-content: center;
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
            <a class="active" href="branches.php">Branches</a>
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
                <div id="questionAnswerSets">
                    <!-- Initially one set of question-answer fields -->
                    <div class="question-answer-set">
                        <div class="form-group">
                            <label for="postContent">Question or Statement: </label>
                            <textarea name="postContent[]" required></textarea>
                        </div>
                        <div class="form-group">
                            <label for="image">Add Image (optional): </label>
                            <input type="file" id="image" name="image[]" accept="image/*">
                        </div>
                        <div class="form-group">
                            <label for="answer">Answer: </label>
                            <textarea name="answer[]" required></textarea>
                        </div>
                    </div>
                </div>
                <div class="button-container">
                    <button type="button" onclick="addQuestion()">Add Question</button>
                </div>
                <div class="button-container">
                    <button type="submit">Submit</button>
                </div>
            </form>
        </div>
    </div>

    <script>
        function addQuestion() {
            var questionAnswerSets = document.getElementById("questionAnswerSets");
            var newSet = document.createElement("div");
            newSet.classList.add("question-answer-set");
            newSet.innerHTML = `
                <div class="form-group">
                    <label for="postContent">Question or Statement: </label>
                    <textarea name="postContent[]" required></textarea>
                </div>
                <div class="form-group">
                    <label for="image">Add Image (optional): </label>
                    <input type="file" id="image" name="image[]" accept="image/*">
                </div>
                <div class="form-group">
                    <label for="answer">Answer: </label>
                    <textarea name="answer[]" required></textarea>
                </div>
            `;
            questionAnswerSets.appendChild(newSet);
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
                // Add more cases for additional main categories
            }
        }
    </script>
</body>
</html>
