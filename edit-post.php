<?php
session_start(); // Start the session
include_once 'config.php';

// Check if user is logged in
if (!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true) {
    header("location: login.php");
    exit;
}

// Check if PostID is provided in the URL
if (!isset($_GET['postID'])) {
    header("location: user-dashboard.php");
    exit;
}

$postID = $_GET['postID'];
$userID = $_SESSION["id"];

// Verify the user has permission to edit this post
$stmt_check_permission = $conn->prepare("SELECT UserID FROM Posts WHERE PostID = ?");
$stmt_check_permission->bind_param("i", $postID);
$stmt_check_permission->execute();
$result_check_permission = $stmt_check_permission->get_result();

if ($result_check_permission->num_rows === 0) {
    echo "Error: Post not found.";
    exit;
}

$post_owner = $result_check_permission->fetch_assoc()['UserID'];
if ($post_owner != $userID) {
    echo "Error: You do not have permission to edit this post.";
    exit;
}

// Fetch existing post details
$stmt_fetch_post = $conn->prepare("SELECT * FROM Posts WHERE PostID = ?");
$stmt_fetch_post->bind_param("i", $postID);
$stmt_fetch_post->execute();
$result_post = $stmt_fetch_post->get_result();
$post = $result_post->fetch_assoc();

// Fetch existing questions
$stmt_fetch_questions = $conn->prepare("SELECT * FROM Questions WHERE PostID = ?");
$stmt_fetch_questions->bind_param("i", $postID);
$stmt_fetch_questions->execute();
$result_questions = $stmt_fetch_questions->get_result();
$questions = [];
while ($row = $result_questions->fetch_assoc()) {
    $questions[] = $row;
}

// Handle form submission for editing
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Validate inputs
    if(empty($_POST['postTitle'])) {
        echo "Error: Title cannot be empty.";
        exit;
    }

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

        // Update Posts table
        $stmt_update_post = $conn->prepare("UPDATE Posts SET Title = ?, MainCategory = ?, SubCategory = ?, reference = ? WHERE PostID = ?");
        $stmt_update_post->bind_param("ssssi", $postTitle, $mainCategory, $subcategory, $postReference, $postID);
        $stmt_update_post->execute();
    
        // Delete existing questions for this post
        $stmt_delete_questions = $conn->prepare("DELETE FROM Questions WHERE PostID = ?");
        $stmt_delete_questions->bind_param("i", $postID);
        $stmt_delete_questions->execute();
    
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
            } elseif (isset($_POST['existing_question_image'][$i]) && !empty($_POST['existing_question_image'][$i])) {
                $questionImageURL = $_POST['existing_question_image'][$i];
            }
    
            // Handle answer image upload
            $answerImageURL = null;
            if (!empty($answerImages['name'][$i])) {
                $targetDir = "./answer-img/";
                $answerImageURL = $targetDir . basename($answerImages["name"][$i]);
                move_uploaded_file($answerImages["tmp_name"][$i], $answerImageURL);
            } elseif (isset($_POST['existing_answer_image'][$i]) && !empty($_POST['existing_answer_image'][$i])) {
                $answerImageURL = $_POST['existing_answer_image'][$i];
            }
    
            // Insert into Questions table
            $stmt_insert_question->bind_param("sissss", $postContent, $postID, $answer, $questionImageURL, $answerImageURL, $explanation);
            
            if (!$stmt_insert_question->execute()) {
                echo "Error: " . $stmt_insert_question->error;
                exit;
            }
        }

        // Log the activity
        $action = "edited";
        $stmt_log_activity = $conn->prepare("INSERT INTO ActivityLog (UserID, Action, PostID, CreatedAt) VALUES (?, ?, ?, NOW())");
        $stmt_log_activity->bind_param("isi", $userID, $action, $postID);
        $stmt_log_activity->execute();
        $stmt_log_activity->close();

        // Close statements
        $stmt_update_post->close();
        $stmt_delete_questions->close();
        $stmt_insert_question->close();

        // Set a session variable for the success message
        $_SESSION['post_edit_success'] = true;

        // Redirect to the post page
        header("Location: post.php?postID=" . $postID);
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
    <title>Edit Post</title>
    <link rel="stylesheet" href="style.css">
    <style>
        /* Reuse styles from create-post.php */
        .create-post-container {
            display: flex;
            max-width: 1200px;
            margin: 20px auto;
            gap: 20px;
            padding: 20px;
            flex-direction: row;
            flex-wrap: wrap;
        }

        .post-info-section {
            flex: 1;
            background: white;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
            height: fit-content;
            margin: 10px;
        }

        .questions-section {
            flex: 1;
            background: white;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
            margin: 10px;
        }

        .section-title {
            margin-bottom: 20px;
            padding-bottom: 10px;
            border-bottom: 2px solid #eee;
        }

        .form-group {
            margin-bottom: 15px;
        }

        .form-group label {
            display: block;
            margin-bottom: 5px;
            font-weight: bold;
        }

        .form-group select,
        .form-group input,
        .form-group textarea {
            width: 100%;
            padding: 8px;
            border: 1px solid #ddd;
            border-radius: 4px;
        }

        .question-answer-set {
            background: #f9f9f9;
            padding: 15px;
            margin-bottom: 20px;
            border-radius: 4px;
            border: 1px solid #eee;
        }

        .question-header {
            display: flex;
            justify-content: flex-end;
            align-items: center;
            margin-bottom: 15px;
        }

        .remove-question {
            background: #ff4444;
            color: white;
            border: none;
            border-radius: 4px;
            padding: 5px 10px;
            cursor: pointer;
        }

        .button-container {
            margin-top: 20px;
            text-align: center;
            display: flex;
            justify-content: space-between;
        }

        .action {
            background: #4CAF50;
            color: white;
            padding: 10px 20px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-size: 16px;
        }

        .action:hover {
            background: #45a049;
        }

        #postForm {
            display: flex;
            width: 100%;
        }

        .existing-image {
            max-width: 200px;
            margin-bottom: 10px;
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
            <div class="dropdown">
                    <a class="active" href="topics.php" class="dropbtn">Topics</a>
                    <div class="dropdown-content">
                        <a href="topics.php">View All Topics</a>
                        <a href="topic-suggestions.php">Suggested Topics</a>
                    </div>
                </div>
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
                            <a href="user-dashboard.php">Profile</a>
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
        <form id="postForm" method="post" action="edit-post.php?postID=<?php echo $postID; ?>" enctype="multipart/form-data">
            <!-- Left Side - Post Information -->
            <div class="post-info-section">
                <h2 class="section-title">Edit Post Information</h2>
                <div class="form-group">
                    <label for="mainCategory">Main Category: </label>
                    <select id="mainCategory" name="mainCategory" required>
                        <option value="" disabled selected>Select a category</option>
                    </select>
                </div>  
                <div class="form-group">
                    <label for="subcategory">Subcategory: </label>
                    <select id="subcategory" name="subcategory" required>
                        <option value="" disabled selected>Select a main category first</option>
                    </select>
                </div>
                <div class="form-group">
                    <label for="postTitle">Title: </label>
                    <input type="text" id="postTitle" name="postTitle" value="<?php echo htmlspecialchars($post['Title']); ?>" required>
                </div>
                <div class="form-group">
                    <label for="postReference">Reference (Book): </label>
                    <input type="text" id="postReference" name="postReference" value="<?php echo htmlspecialchars($post['reference']); ?>" required>
                </div>
            </div>

            <!-- Right Side - Questions -->
            <div class="questions-section">
                <h2 class="section-title">Edit Questions</h2>
                <div id="questionAnswerSets">
                    <?php foreach($questions as $index => $question): ?>
                    <div class="question-answer-set">
                        <div class="question-header">
                            <button type="button" class="remove-question" onclick="removeQuestion(this)">X</button>
                        </div>
                        <div class="form-group">
                            <label for="postContent">Question or Statement: </label>
                            <textarea name="postContent[]" required><?php echo htmlspecialchars($question['QuestionContent']); ?></textarea>
                        </div>
                        <div class="form-group">
                            <label for="questionImage">Question Image (optional): </label>
                            <?php if(!empty($question['QuestionImageURL'])): ?>
                                <input type="hidden" name="existing_question_image[]" value="<?php echo htmlspecialchars($question['QuestionImageURL']); ?>">
                                <img src="<?php echo htmlspecialchars($question['QuestionImageURL']); ?>" class="existing-image" alt="Existing Question Image">
                            <?php endif; ?>
                            <input type="file" name="questionImage[]" accept="image/*">
                        </div>
                        <div class="form-group">
                            <label for="answer">Answer: </label>
                            <textarea name="answer[]" required><?php echo htmlspecialchars($question['AnswerContent']); ?></textarea>
                        </div>
                        <div class="form-group">
                            <label for="answerImage">Answer Image (optional): </label>
                            <?php if(!empty($question['AnswerImageURL'])): ?>
                                <input type="hidden" name="existing_answer_image[]" value="<?php echo htmlspecialchars($question['AnswerImageURL']); ?>">
                                <img src="<?php echo htmlspecialchars($question['AnswerImageURL']); ?>" class="existing-image" alt="Existing Answer Image">
                            <?php endif; ?>
                            <input type="file" name="answerImage[]" accept="image/*">
                        </div>
                        <div class="form-group">
                            <label for="explanation">Explanation: </label>
                            <textarea name="explanation[]" required><?php echo htmlspecialchars($question['Explanation']); ?></textarea>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
                <div class="button-container">
                    <button type="button" class="action" onclick="addQuestion()">Add Question</button>
                    <button type="submit" class="action">Update Post</button>
                </div>
            </div>
        </form>
    </div>
    <script>
    let categoriesData = [];

    document.addEventListener('DOMContentLoaded', async function() {
        try {
            const response = await fetch('get-categories.php');
            categoriesData = await response.json();
            populateMainCategories();
            
            // Set the existing post's main category and subcategory
            const mainCategorySelect = document.getElementById('mainCategory');
            const subcategorySelect = document.getElementById('subcategory');
            const savedMainCategory = "<?php echo htmlspecialchars($post['MainCategory']); ?>";
            const savedSubcategory = "<?php echo htmlspecialchars($post['SubCategory']); ?>";
            
            // Set main category
            for (const option of mainCategorySelect.options) {
                if (option.textContent === savedMainCategory) {
                    option.selected = true;
                    // Populate and set subcategory
                    populateSubcategories();
                    setTimeout(() => {
                        for (const subOption of subcategorySelect.options) {
                            if (subOption.textContent === savedSubcategory) {
                                subOption.selected = true;
                                break;
                            }
                        }
                    }, 100);
                    break;
                }
            }
        } catch (error) {
            console.error('Error fetching categories:', error);
        }
    });

    function populateMainCategories() {
        const mainCategorySelect = document.getElementById('mainCategory');
        mainCategorySelect.innerHTML = '<option value="" disabled>Select a category</option>';
        
        categoriesData.forEach((category, index) => {
            const option = document.createElement('option');
            option.value = category.name; // Use category name as value
            option.textContent = category.name;
            mainCategorySelect.appendChild(option);
        });
    }

    function populateSubcategories() {
        const mainCategorySelect = document.getElementById('mainCategory');
        const subcategorySelect = document.getElementById('subcategory');
        const selectedMainCategory = mainCategorySelect.value;
        
        // Reset subcategory dropdown
        subcategorySelect.innerHTML = '<option value="" disabled selected>Select a subcategory</option>';
        
        if (selectedMainCategory) {
            const category = categoriesData.find(cat => cat.name === selectedMainCategory);
            if (category) {
                category.subtopics.forEach(subtopic => {
                    const option = document.createElement('option');
                    option.value = subtopic.name;
                    option.textContent = subtopic.name;
                    subcategorySelect.appendChild(option);
                });
            }
        }
    }

    // Add event listener for main category changes
    document.getElementById('mainCategory').addEventListener('change', populateSubcategories);

    function addQuestion() {
        var questionAnswerSets = document.getElementById("questionAnswerSets");
        var newSet = document.createElement("div");
        newSet.classList.add("question-answer-set");
        newSet.innerHTML = `
            <div class="question-header">
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
        // Prevent removing the last question set
        var questionSets = document.querySelectorAll('.question-answer-set');
        if (questionSets.length > 1) {
            var questionSet = button.closest('.question-answer-set');
            questionSet.remove();
        } else {
            alert("You must have at least one question set.");
        }
    }

    // Show success notification if needed
    <?php
    if (isset($_SESSION['post_edit_success']) && $_SESSION['post_edit_success']) {
        echo "
        document.addEventListener('DOMContentLoaded', function() {
            var notification = document.createElement('div');
            notification.className = 'notification';
            notification.textContent = 'Post updated successfully!';
            notification.style.display = 'block';
            document.body.appendChild(notification);
            
            setTimeout(function() {
                notification.style.display = 'none';
            }, 5000);
        });";
        unset($_SESSION['post_edit_success']); // Clear the session variable
    }
    ?>
</script>
</body>
</html>