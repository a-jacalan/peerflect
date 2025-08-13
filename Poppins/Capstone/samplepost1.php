<?php
    session_start();

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Post 1 - Arithmetic Review</title>
    <link rel="stylesheet" href="style.css">
    <style>
        .contributor-container {
            display: flex;
            align-items: center;
            padding: 20px;
            border: 2px solid #8f75ec;
            border-radius: 5px;
            margin-bottom: 10px;
            box-shadow: 0 0 10px rgba(0, 0, 0, 0.479);
            height: 300px; /* 70% of the parent container */
        }
        .left-container {
            width: 40%;
            padding: 20px;
            border-right: 2px solid #8f75ec; /* Adding a border on the right side */
            box-sizing: border-box; /* Include padding and border in the width calculation */
        }
        .contributor-info {
            text-align: center;
        }
        .profile-image {
            width: 100px; /* Set the desired width */
            height: 100px; /* Set the desired height */
            border-radius: 50%; /* Ensure the image is displayed as a circle */
            margin-bottom: 10px; /* Adjust margin as needed */
            object-fit: cover; /* Ensure the image covers the specified dimensions */
        }
        .right-container {
            display: flex;
            justify-content: space-evenly;
            width: 60%;
            padding: 20px;
            height: 100%;
            flex-direction: column;
            text-align: center;
        }
        .question {
            font-weight: bold;
            padding: 20px;
        }
        .reveal-answer {
            background-color: #4CAF50;
            color: white;
            padding: 10px 20px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            margin-top: 10px;
        }
        .reveal-answer:hover {
            background-color: #45a049;
        }
        .navigation {
            display: flex;
            justify-content: space-between;
            width: 100%;
        }
        .nav-button {
            background-color: #8f75ec;
            color: white;
            padding: 10px 20px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            margin: 0 10px;
        }
        .nav-button:hover {
            background-color: #7a5cc5;
        }
        .comment-section {
            margin-top: 20px;
            padding: 20px;
            border: 2px solid #8f75ec;
            border-radius: 5px;
            box-shadow: 0 0 10px rgba(0, 0, 0, 0.479);
        }
        .comment {
            display: flex;
            align-items: flex-start;
            margin-bottom: 10px;
            border: 1px solid #ddd; /* Add border to each comment */
            padding: 10px; /* Add padding for better spacing */
            border-radius: 5px; /* Add border radius for rounded corners */
            color: #777;
        }
        .comment-profile-image {
            width: 50px;
            height: 50px;
            border-radius: 50%;
            margin-right: 10px;
        }
        .comment-info {
            margin-right: 10px;
        }
        .comment-text {
            flex: 1;
        }
        .commenter-name {
            font-weight: bold;
        }
        .heartrate {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 10px;
            background-color: rgba(0, 0, 0, 0.1);
        }
        .heartrate button{
            width: 50px;
        }
        .heartrate-btns{
            float: right;
        }
        .heart-btn {
            background-color: transparent;
            border: none;
            cursor: pointer;
            font-size: 20px;
            color: #777; /* Default color */
        }
        .heart-btn.clicked {
            color: red; /* Color when clicked */
        }
        .rating {
            margin-left: 20px;
            display: inline-block;
        }
        .star {
            font-size: 20px;
            color: #777; /* Default color */
            cursor: pointer;
        }

        .star:hover,
        .star.clicked {
            color: yellow; /* Color on hover or when clicked */
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
    <div class="topics-container">
        <h2><?php echo htmlspecialchars($postDetails['Title']); ?></h2>
        <div class="contributor-container">
            <!-- Left Container for Contributor Information -->
            <div class="left-container">
                <div class="contributor-info">
                    <img class="profile-image" src="img/profile.jpg" alt="Profile Image">
                    <h3>Contributor Name</h3>
                    <p>Contributor Bio</p>
                    <!-- Add more contributor information as needed -->
                </div>
            </div>
            <!-- Right Container for Reviewer Content -->
            <div class="right-container">
                <div class="question">
                </div>
                <div id="answer" style="display: none;">
                </div>
                <button class="reveal-answer" onclick="revealAnswer()">Reveal Answer</button>
                <!-- Navigation buttons (Next and Previous) -->
                <div class="navigation">
                    <button class="nav-button" id="prevBtn" onclick="prevItem()">Previous</button>
                    <button class="nav-button" id="nextBtn" onclick="nextItem()">Next</button>
                </div>
            </div>
        </div>
        <!-- Comment section -->
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
        // Sample review items
        var reviewItems = [
            {
                question: "What is the OSI model?",
                answer: "Answer: It is conceptual framework used to understand and describe how different networking protocols interact within a network. It consists of seven layers, each responsible for specific functions in data communication."
            },
            {
                question: "What are the seven layers of the OSI model?",
                answer: "Answer: Physical Layer, Data Link Layer, Network Layer, Transport Layer, Session Layer, Presentation Layer, Application Layer"
            }
            // Add more review items as needed
        ];

        // Variable to keep track of the current review item
        var currentReviewIndex = 0;

        // Function to reveal the answer
        function revealAnswer() {
            var answer = document.getElementById("answer");
            answer.style.display = "block";
        }

        // Function for navigating to the previous item
        function prevItem() {
            currentReviewIndex--;
            if (currentReviewIndex < 0) {
                currentReviewIndex = reviewItems.length - 1;
            }
            updateReview();
        }

        // Function for navigating to the next item
        function nextItem() {
            currentReviewIndex++;
            if (currentReviewIndex >= reviewItems.length) {
                currentReviewIndex = 0;
            }
            updateReview();
        }

        // Function to update the review content based on the current index
        function updateReview() {
            var question = document.querySelector(".question");
            var answer = document.getElementById("answer");

            question.textContent = reviewItems[currentReviewIndex].question;
            answer.textContent = reviewItems[currentReviewIndex].answer;
            answer.style.display = "none";
        }

        // Initialize review content on page load
        updateReview();

        function toggleLike() {
            var heartBtn = document.querySelector('.heart-btn');
            heartBtn.classList.toggle('clicked');
        }
        // Select all star elements
        var stars = document.querySelectorAll('.star');

        // Add click event listener to each star
        stars.forEach(function(star, index) {
            star.addEventListener('click', function() {
                // Toggle the clicked class on the clicked star and previous stars
                for (var i = 0; i <= index; i++) {
                    stars[i].classList.add('clicked');
                }
                for (var i = index + 1; i < stars.length; i++) {
                    stars[i].classList.remove('clicked');
                }
            });
        });
    </script>
</body>
</html>
