<?php
    session_start();

    // Include config file and establish database connection
    require_once "config.php";
require_once "check-banned.php";;

    // Check if postID is set in the URL
    if(isset($_GET['postID'])) {
        $postID = $_GET['postID'];

        // Fetch questions from the database based on the given postID
        $stmt = $conn->prepare("SELECT * FROM Questions WHERE PostID = ?");
        $stmt->bind_param("i", $postID);
        $stmt->execute();
        $result = $stmt->get_result();

        // Store fetched questions in an array
        $questions = [];
        while($row = $result->fetch_assoc()) {
            $questions[] = $row;
        }
    } else {
        // Redirect to an error page or homepage if postID is not provided
        header("location: user-dashboard.php");
        exit;
    }
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Post - Questions</title>
    <link rel="stylesheet" href="style.css">
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
    <div class="topics-container">
        <!-- Display questions from the database -->
        <div class="contributor-container">
            <!-- Left Container for Contributor Information -->
            <div class="left-container">
                <!-- Contributor info -->
            </div>
            <!-- Right Container for Reviewer Content -->
            <div class="right-container">
                <?php if(count($questions) > 0): ?>
                    <div class="question">
                        <?php echo htmlspecialchars($questions[0]['QuestionContent']); ?>
                    </div>
                    <div class="answer" style="display: none;">
                        <?php echo htmlspecialchars($questions[0]['AnswerContent']); ?>
                    </div>
                    <button class="reveal-answer" onclick="revealAnswer()">Reveal Answer</button>
                    <!-- Navigation buttons (Next and Previous) -->
                    <div class="navigation">
                        <button class="nav-button" id="prevBtn" onclick="prevItem()">Previous</button>
                        <button class="nav-button" id="nextBtn" onclick="nextItem()">Next</button>
                    </div>
                <?php else: ?>
                    <p>No questions found for this post.</p>
                <?php endif; ?>
            </div>
        </div>
        <div class="comment-section">
            <div class="heartrate">
                <h3>Comments</h3>
                <div class="heartrate-btns">
                    <!-- Heart button and number of clicks -->
                    <button class="heart-btn" onclick="toggleLike()">&#10084;</button><span class="heart-count">(0)</span>
                    <!-- Ratings system -->
                    <div class="rating">
                        <span class="star">&#9733;</span>
                        <span class="star">&#9733;</span>
                        <span class="star">&#9733;</span>
                        <span class="star">&#9733;</span>
                        <span class="star">&#9733;</span>
                        <span class="user-count">(0)</span>
                    </div>
                </div>
            </div>
            <!-- Comment items -->
            <div class="comment">
                <img class="comment-profile-image" src="img/profile.jpg" alt="profile">
                <div class="comment-info">
                    <div class="commenter-name">Rey Gavilanes</div>
                    <div class="comment-text">
                        <p style="margin-left: 20px;">Wow, This is amazing!</p>
                    </div>
                </div>
            </div>
            <div class="comment">
                <img class="comment-profile-image" src="img/profile.jpg" alt="profile">
                <div class="comment-info">
                    <div class="commenter-name">John Abogadie</div>
                    <div class="comment-text">
                        <p style="margin-left: 20px;">I found the explanation very helpful</p>
                    </div>
                </div>
            </div>
            <!-- Add more comments as needed -->
        </div>
    </div>

    <script>
         // JavaScript logic for navigating through questions
         var currentQuestionIndex = 0;
        var questions = <?php echo json_encode($questions); ?>;

        function revealAnswer() {
            var answer = document.querySelector(".answer");
            answer.style.display = "block";
        }

        function prevItem() {
            if (currentQuestionIndex > 0) {
                currentQuestionIndex--;
                updateQuestion();
            }
        }

        function nextItem() {
            if (currentQuestionIndex < questions.length - 1) {
                currentQuestionIndex++;
                updateQuestion();
            }
        }

        function updateQuestion() {
            var question = document.querySelector(".question");
            var answer = document.querySelector(".answer");

            question.textContent = questions[currentQuestionIndex]['QuestionContent'];
            answer.textContent = questions[currentQuestionIndex]['AnswerContent'];
            answer.style.display = "none";
        }
    </script>
</body>
</html>
