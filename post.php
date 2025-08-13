<?php
session_start();

// Include config file and establish database connection
require_once "config.php";
require_once "check-banned.php";
require_once "pdf_generator.php";

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

// Fetch post author information
$stmt = $conn->prepare("SELECT u.Username, u.ProfileImageURL, u.Points FROM Posts p JOIN Users u ON p.UserID = u.UserID WHERE p.PostID = ?");
$stmt->bind_param("i", $postID);
$stmt->execute();
$result = $stmt->get_result();
$postAuthor = $result->fetch_assoc();

// Fetch post count for the author
$stmt = $conn->prepare("SELECT COUNT(*) as postCount FROM Posts WHERE UserID = (SELECT UserID FROM Posts WHERE PostID = ?)");
$stmt->bind_param("i", $postID);
$stmt->execute();
$result = $stmt->get_result();
$postCount = $result->fetch_assoc()['postCount'];

// Fetch comments for this post
$stmt = $conn->prepare("SELECT c.*, u.Username, u.ProfileImageURL FROM Comment c JOIN Users u ON c.UserID = u.UserID WHERE c.PostID = ? ORDER BY c.CreatedAt DESC");
$stmt->bind_param("i", $postID);
$stmt->execute();
$commentResult = $stmt->get_result();

$comments = [];
while($row = $commentResult->fetch_assoc()) {
    $comments[] = $row;
}

// Function to get the average rating for a post
function getAverageRating($conn, $postID) {
    $stmt = $conn->prepare("SELECT AVG(Score) as avg_score, COUNT(*) as count FROM rating WHERE PostID = ?");
    $stmt->bind_param("i", $postID);
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result->fetch_assoc();
    return ['avg' => round($row['avg_score'], 1), 'count' => $row['count']];
}

// Function to get user's rating for a post
function getUserRating($conn, $userID, $postID) {
    $stmt = $conn->prepare("SELECT Score FROM rating WHERE UserID = ? AND PostID = ?");
    $stmt->bind_param("ii", $userID, $postID);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($row = $result->fetch_assoc()) {
        return $row['Score'];
    }
    return null;
}

// Get average rating and user's rating
$avgRating = getAverageRating($conn, $postID);
$userRating = isset($_SESSION['id']) ? getUserRating($conn, $_SESSION['id'], $postID) : null;

// Check if user has permission to delete (admin or schooladmin)
$hasAdminPermission = false;
if (isset($_SESSION['id'])) {
    $stmt = $conn->prepare("SELECT usertype FROM Users WHERE UserID = ?");
    $stmt->bind_param("i", $_SESSION['id']);
    $stmt->execute();
    $result = $stmt->get_result();
    $user = $result->fetch_assoc();
    $hasAdminPermission = in_array($user['usertype'], ['admin', 'schooladmin']);
}

// Check if current user is the post author
$isAuthor = false;
if (isset($_SESSION['id'])) {
    $stmt = $conn->prepare("SELECT u.UserID, u.usertype, u.school, p.UserID as PostUserID, author.school as AuthorSchool 
                           FROM Posts p 
                           JOIN Users u ON u.UserID = ? 
                           JOIN Users author ON author.UserID = p.UserID 
                           WHERE p.PostID = ?");
    $stmt->bind_param("ii", $_SESSION['id'], $postID);
    $stmt->execute();
    $result = $stmt->get_result();
    $userPost = $result->fetch_assoc();
    
    $isAuthor = $userPost['PostUserID'] == $_SESSION['id'];
    
    // School admin can only delete posts from their school
    if ($userPost['usertype'] == 'schooladmin') {
        $hasAdminPermission = $userPost['school'] == $userPost['AuthorSchool'];
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Post - Questions</title>
    <link rel="stylesheet" href="style.css">
    <link rel="stylesheet" href="post.css">
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
                            <?php if($_SESSION["usertype"] === "contributor") { ?>
                                <a href="redeem-rewards.php">Redeem Rewards</a>
                            <?php } ?>
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
        <div class="profile-and-contributor-container">
            <div class="profile-container">
                <?php if(isset($postAuthor)): ?>
                    <img src="<?php echo htmlspecialchars($postAuthor['ProfileImageURL']); ?>" alt="Profile Image" class="profile-image">
                    <h2 class="profile-name"><?php echo htmlspecialchars($postAuthor['Username']); ?></h2>
                    <div class="profile-divider"></div>
                    <p class="post-count">Posts: <?php echo $postCount; ?></p>
                        <a href="download_post.php?postID=<?php echo $postID; ?>" class="download-btn" onclick="handleDownload(<?php echo $postID; ?>, event)">
                            <button class="download-pdf-btn">Download PDF</button>
                        </a>
                    <?php if ($isAuthor || $hasAdminPermission): ?>
                        <div class="profile-divider"></div>
                        <button id="deletePostBtn" class="delete-btn" onclick="confirmDelete()">Delete Post</button>
                    <?php endif; ?>
                    <p class="post-count">Points: <?php echo htmlspecialchars($postAuthor['Points']); ?></p>
                <?php else: ?>
                    <p>Author information not available</p>
                <?php endif; ?>
            </div>
            <div class="contributor-container">
                <div class="left-container">
                    <?php if(count($questions) > 0): ?>
                        <div class="question">
                            <?php echo htmlspecialchars($questions[0]['QuestionContent']); ?>
                        </div>
                        <?php if(!empty($questions[0]['QuestionImageURL'])): ?>
                            <img src="<?php echo htmlspecialchars($questions[0]['QuestionImageURL']); ?>" alt="Question Image" class="question-image">
                        <?php endif; ?>
                        <div class="navigation">
                            <button class="nav-button" id="prevBtn" onclick="prevItem()">Previous</button>
                            <button class="nav-button" id="nextBtn" onclick="nextItem()">Next</button>
                        </div>
                        <div class="pagination-dots"></div>
                    <?php else: ?>
                        <p>No questions found for this post.</p>
                    <?php endif; ?>
                </div>
                <div class="right-container">
                    <div class="reveal-text" onclick="revealAnswer()">
                        <p>Click to Reveal Answer</p>
                    </div>
                    <?php if(count($questions) > 0): ?>
                        <div class="answer">
                            <p><?php echo htmlspecialchars($questions[0]['AnswerContent']); ?></p>
                            <?php if(!empty($questions[0]['AnswerImageURL'])): ?>
                                <img src="<?php echo htmlspecialchars($questions[0]['AnswerImageURL']); ?>" alt="Answer Image" class="answer-image">
                            <?php endif; ?>
                            <button id="explanation-button" onclick="revealExplanation()">Explanation</button>
                            <div id="explanation-content" style="display: none;">
                                <p><?php echo htmlspecialchars($questions[0]['Explanation']); ?></p>
                            </div>
                            <button id="hide-button" onclick="hideAnswer(event)">Hide Answer</button>
                        </div>
                    <?php else: ?>
                        <p>No questions found for this post.</p>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        <div class="comment-section">
            <div class="heartrate">
                <h3>Comments</h3>
                <div class="heartrate-btns">
                    <div class="user-rating">
                        <span id="user-rating">
                        </span>
                    </div>
                    <div class="rating">
                        <?php for ($i = 1; $i <= 5; $i++): ?>
                            <span class="star <?php echo ($userRating !== null && $i <= $userRating) ? 'active' : ''; ?>" data-rating="<?php echo $i; ?>">&#9733;</span>
                        <?php endfor; ?>
                        <span class="user-count" id="ratingUserCount" title="Click to see all ratings">(<?php echo $avgRating['count']; ?> people rated this post)</span>
                    </div>
                </div>
                <button id="report-button" class="report-button">Report</button>
            </div>
            <div id="comments-container">
                <?php foreach($comments as $comment): ?>
                    <div class="comment">
                        <img class="comment-profile-image" src="<?php echo htmlspecialchars($comment['ProfileImageURL']); ?>" alt="profile">
                        <div class="comment-info">
                            <div class="commenter-name"><?php echo htmlspecialchars($comment['Username']); ?></div>
                            <div class="comment-text">
                                <p style="margin-left: 20px;"><?php echo htmlspecialchars($comment['Content']); ?></p>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
            <form id="comment-form">
                <textarea id="comment-input" placeholder="Write a comment..."></textarea>
                <button type="submit">Submit</button>
            </form>
        </div>
    </div>
    <!-- Rating Modal -->
    <div id="ratingModal" class="modal">
        <div class="modal-content">
            <span class="close">&times;</span>
            <h2>Ratings</h2>
            <div class="average-rating">
                Average Rating: <span id="avg-rating"><?php echo $avgRating['avg']; ?></span>
            </div>
            <div id="ratingsList"></div>
        </div>
    </div>
    <!-- Report Modal -->
    <div id="reportModal" class="report-modal">
        <div class="report-modal-content">
            <span class="close-report">&times;</span>
            <h2>Report Content</h2>
            <textarea id="report-reason" placeholder="Please enter the reason for reporting..."></textarea>
            <button id="submit-report">Submit Report</button>
        </div>
    </div>
    <!-- Delete Modal -->
    <div id="deleteModal" class="modal">
    <div class="modal-content">
        <h3>Confirm Deletion</h3>
        <p>Are you sure you want to delete this post? This action cannot be undone.</p>
        <div class="modal-buttons">
            <button class="cancel-delete" onclick="closeModal()">Cancel</button>
            <button class="confirm-delete" onclick="deletePost()">Delete</button>
        </div>
    </div>
</div>
    <script>
var currentQuestionIndex = 0;
var questions = <?php echo json_encode($questions); ?>;

function updateNavButtons() {
    document.getElementById('prevBtn').disabled = currentQuestionIndex === 0;
    document.getElementById('nextBtn').disabled = currentQuestionIndex === questions.length - 1;
}

function updatePaginationDots() {
    var dotsContainer = document.querySelector('.pagination-dots');
    dotsContainer.innerHTML = '';
    for (var i = 0; i < questions.length; i++) {
        var dot = document.createElement('span');
        dot.className = 'dot' + (i === currentQuestionIndex ? ' active' : '');
        dotsContainer.appendChild(dot);
    }
}

function revealExplanation() {
    const explanationContent = document.getElementById('explanation-content');
    const explanationButton = document.getElementById('explanation-button');
    
    if (explanationContent.style.display === 'none') {
        explanationContent.style.display = 'block';
        explanationButton.textContent = 'Hide Explanation';
    } else {
        explanationContent.style.display = 'none';
        explanationButton.textContent = 'Explanation';
    }
}

function revealAnswer() {
    var rightContainer = document.querySelector(".right-container");
    rightContainer.classList.add("flip");
}

function hideAnswer(event) {
    event.stopPropagation();
    var rightContainer = document.querySelector(".right-container");
    rightContainer.classList.remove("flip");
}

function prevItem() {
    if (currentQuestionIndex > 0) {
        animateTransition('right', () => {
            currentQuestionIndex--;
            updateQuestion();
        });
    }
}

function nextItem() {
    if (currentQuestionIndex < questions.length - 1) {
        animateTransition('left', () => {
            currentQuestionIndex++;
            updateQuestion();
        });
    }
}

function animateTransition(direction, callback) {
    var leftContainer = document.querySelector(".left-container");
    var rightContainer = document.querySelector(".right-container");
    
    leftContainer.classList.remove('slide-left', 'slide-right');
    rightContainer.classList.remove('slide-left', 'slide-right', 'flip');
    
    void leftContainer.offsetWidth;
    void rightContainer.offsetWidth;
    
    if (direction === 'left') {
        leftContainer.classList.add('slide-left');
        rightContainer.classList.add('slide-left');
    } else {
        leftContainer.classList.add('slide-right');
        rightContainer.classList.add('slide-right');
    }
    
    setTimeout(() => {
        callback();
        leftContainer.classList.remove('slide-left', 'slide-right');
        rightContainer.classList.remove('slide-left', 'slide-right');
        }, 500);
    }

    function updateQuestion() {
        var question = document.querySelector(".question");
        var answer = document.querySelector(".answer");
        var rightContainer = document.querySelector(".right-container");

        question.innerHTML = questions[currentQuestionIndex]['QuestionContent'];
        if (questions[currentQuestionIndex]['QuestionImageURL']) {
            question.innerHTML += `<img src="${questions[currentQuestionIndex]['QuestionImageURL']}" alt="Question Image" class="question-image">`;
        }

        answer.innerHTML = `
            <p>${questions[currentQuestionIndex]['AnswerContent']}</p>
            ${questions[currentQuestionIndex]['AnswerImageURL'] ? 
                `<img src="${questions[currentQuestionIndex]['AnswerImageURL']}" alt="Answer Image" class="answer-image">` : ''}
            <button id="explanation-button" onclick="revealExplanation()">Explanation</button>
            <div id="explanation-content" style="display: none;">
                <p>${questions[currentQuestionIndex]['Explanation']}</p>
            </div>
            <button id="hide-button" onclick="hideAnswer(event)">Hide Answer</button>
        `;
        
        rightContainer.classList.remove("flip");
        
        updateNavButtons();
        updatePaginationDots();
    }

    // Initialize the navigation buttons and pagination dots
    updateNavButtons();
    updatePaginationDots();


        document.getElementById('comment-form').addEventListener('submit', function(e) {
            e.preventDefault();
            var commentContent = document.getElementById('comment-input').value;
            if (commentContent.trim() === '') return;

            // AJAX request to submit comment
            var xhr = new XMLHttpRequest();
            xhr.open('POST', 'submit_comment.php', true);
            xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
            xhr.onload = function() {
                if (this.status === 200) {
                    var response = JSON.parse(this.responseText);
                    if (response.success) {
                        // Add new comment to the page
                        var commentsContainer = document.getElementById('comments-container');
                        var newComment = document.createElement('div');
                        newComment.className = 'comment';
                        newComment.innerHTML = `
                            <img class="comment-profile-image" src="${response.profileImage}" alt="profile">
                            <div class="comment-info">
                                <div class="commenter-name">${response.username}</div>
                                <div class="comment-text">
                                    <p style="margin-left: 20px;">${response.content}</p>
                                </div>
                            </div>
                        `;
                        commentsContainer.insertBefore(newComment, commentsContainer.firstChild);
                        document.getElementById('comment-input').value = '';
                    }
                }
            };
            xhr.send('postID=' + encodeURIComponent(<?php echo $postID; ?>) + '&content=' + encodeURIComponent(commentContent));
        });

        document.querySelectorAll('.star').forEach(star => {
            star.addEventListener('click', function() {
                if (!<?php echo isset($_SESSION['id']) ? 'true' : 'false'; ?>) {
                    alert('Please log in to rate.');
                    return;
                }
                
                const rating = this.dataset.rating;
                fetch('submit_rating.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: `postID=<?php echo $postID; ?>&rating=${rating}`
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        updateUserStars(rating);
                        updateAverageRating(data.avgRating, data.count);
                    } else {
                        alert(data.message || 'An error occurred while submitting your rating.');
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('An error occurred while submitting your rating.');
                });
            });
        });

        function updateUserStars(userRating) {
            document.querySelectorAll('.star').forEach((star, index) => {
                if (index < userRating) {
                    star.classList.add('active');
                } else {
                    star.classList.remove('active');
                }
            });
        }

        function updateAverageRating(avgRating, count) {
            document.getElementById('avg-rating').textContent = avgRating.toFixed(1);
            document.querySelector('.user-count').textContent = `(${count})`;
        }

        document.addEventListener('DOMContentLoaded', function() {
            const userRating = <?php echo $userRating !== null ? $userRating : 0; ?>;
            updateUserStars(userRating);
        });

        document.addEventListener('DOMContentLoaded', function() {
    const userRating = <?php echo $userRating !== null ? $userRating : 0; ?>;
    updateUserStars(userRating);

    const modal = document.getElementById("ratingModal");
    const userCountSpan = document.getElementById("ratingUserCount");
    const closeSpan = document.getElementsByClassName("close")[0];

    userCountSpan.onclick = function() {
        fetchRatings();
        modal.style.display = "block";
    }

    closeSpan.onclick = function() {
        modal.style.display = "none";
    }

    window.onclick = function(event) {
        if (event.target == modal) {
            modal.style.display = "none";
        }
    }

    document.querySelectorAll('.star').forEach(star => {
        star.addEventListener('click', function() {
            if (!<?php echo isset($_SESSION['id']) ? 'true' : 'false'; ?>) {
                alert('Please log in to rate.');
                return;
            }
            
            const rating = this.dataset.rating;
            fetch('submit_rating.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: `postID=<?php echo $postID; ?>&rating=${rating}`
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    updateUserRating(rating);
                    document.getElementById('avg-rating').textContent = data.avgRating.toFixed(1);
                    document.querySelector('.user-count').textContent = `(${data.count})`;
                } else {
                    alert(data.message || 'An error occurred while submitting your rating.');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('An error occurred while submitting your rating.');
            });
        });
    });
});

function updateUserStars(userRating) {
    document.querySelectorAll('.star').forEach((star, index) => {
        if (index < userRating) {
            star.classList.add('active');
        } else {
            star.classList.remove('active');
        }
    });
}

function updateUserRating(rating) {
    document.getElementById('user-rating').textContent = rating;
    updateUserStars(rating);
}

function fetchRatings() {
    fetch('get_ratings.php?postID=<?php echo $postID; ?>')
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            displayRatings(data.ratings);
        } else {
            alert(data.message || 'An error occurred while fetching ratings.');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('An error occurred while fetching ratings.');
    });
}

function displayRatings(ratings) {
    const ratingsList = document.getElementById('ratingsList');
    ratingsList.innerHTML = '';
    ratings.forEach(rating => {
        const ratingItem = document.createElement('div');
        ratingItem.className = 'rating-item';
        const createdAt = new Date(rating.createdAt).toLocaleString();
        ratingItem.innerHTML = `
            <span>${rating.username}</span>
            <span>${'★'.repeat(rating.score)}${'☆'.repeat(5 - rating.score)}</span>
            <span>${createdAt}</span>
        `;
        ratingsList.appendChild(ratingItem);
    });
}

        // Report feature
        const reportButton = document.getElementById('report-button');
        const reportModal = document.getElementById('reportModal');
        const closeReport = document.getElementsByClassName('close-report')[0];
        const submitReport = document.getElementById('submit-report');

        reportButton.onclick = function() {
            reportModal.style.display = "block";
        }

        closeReport.onclick = function() {
            reportModal.style.display = "none";
        }

        window.onclick = function(event) {
            if (event.target == reportModal) {
                reportModal.style.display = "none";
            }
        }

        submitReport.onclick = function() {
            const reason = document.getElementById('report-reason').value;
            if (reason.trim() === '') {
                alert('Please enter a reason for reporting.');
                return;
            }

            // AJAX request to submit report
            const xhr = new XMLHttpRequest();
            xhr.open('POST', 'submit_report.php', true);
            xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
            xhr.onload = function() {
                if (this.status === 200) {
                    const response = JSON.parse(this.responseText);
                    if (response.success) {
                        alert('Report submitted successfully.');
                        reportModal.style.display = "none";
                        document.getElementById('report-reason').value = '';
                    } else {
                        alert(response.message || 'An error occurred while submitting your report.');
                    }
                }
            };
            xhr.send('postID=' + encodeURIComponent(<?php echo $postID; ?>) + '&reason=' + encodeURIComponent(reason));
        }
    </script>
    <script>
    function confirmDelete() {
        document.getElementById('deleteModal').style.display = 'block';
    }

    function closeModal() {
        document.getElementById('deleteModal').style.display = 'none';
    }

    function deletePost() {
        fetch('delete_post.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: 'postID=<?php echo $postID; ?>'
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                window.location.href = 'user-dashboard.php';
            } else {
                alert(data.message || 'An error occurred while deleting the post.');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('An error occurred while deleting the post.');
        });
    }

    // Close modal when clicking outside of it
    window.onclick = function(event) {
        var modal = document.getElementById('deleteModal');
        if (event.target == modal) {
            modal.style.display = "none";
        }
    }

    function handleDownload(postID, event) {
    event.preventDefault();
    
    // Create a hidden iframe for download
    let downloadFrame = document.createElement('iframe');
    downloadFrame.style.display = 'none';
    document.body.appendChild(downloadFrame);
    
    // Set iframe source to download URL
    downloadFrame.src = `download_post.php?postID=${postID}`;
    
    // Remove the iframe after download starts
    setTimeout(() => {
        document.body.removeChild(downloadFrame);
    }, 2000);
}
    </script>
<script src="js/transition.js"></script>
</body>
</html>