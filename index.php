<?php
session_start();

// Include config file
require_once "config.php";
require_once "check-banned.php";

$isLoggedIn = false;
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

// Function to get user type icon and label
function getUserTypeInfo($usertype) {
    switch ($usertype) {
        case 'admin':
            return ['icon' => '👑', 'label' => 'Admin'];
        case 'schooladmin':
            return ['icon' => '🏫', 'label' => 'School Admin'];
        case 'contributor':
            return ['icon' => '📚', 'label' => 'Contributor'];
        default:
            return ['icon' => '👤', 'label' => 'User'];
    }
}

// Handle AJAX shoutbox message submission
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['action']) && $_POST['action'] == 'send_message') {
    $message = $conn->real_escape_string($_POST['shoutbox_message']);
    $user_id = $_SESSION['id'] ?? 0; // Use 0 for guest users
    
    $sql = "INSERT INTO shoutbox_messages (user_id, message) VALUES (?, ?)";
    if ($stmt = $conn->prepare($sql)) {
        $stmt->bind_param("is", $user_id, $message);
        $stmt->execute();
        $stmt->close();
    }
    
    // Return the new message as JSON
    $userTypeInfo = getUserTypeInfo($_SESSION['usertype'] ?? 'user');
    $response = [
        'username' => $username ?? 'Guest',
        'message' => $message,
        'userTypeIcon' => $userTypeInfo['icon'],
        'userTypeLabel' => $userTypeInfo['label']
    ];
    echo json_encode($response);
    exit;
}

// Fetch shoutbox messages excluding banned users
$sql = "SELECT s.*, u.Username, u.usertype 
        FROM shoutbox_messages s 
        LEFT JOIN Users u ON s.user_id = u.UserID 
        WHERE u.isBan = 0 OR u.isBan IS NULL 
        ORDER BY s.timestamp DESC LIMIT 50";
$result = $conn->query($sql);
$shoutbox_messages = [];
if ($result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $shoutbox_messages[] = $row;
    }
}

// Reverse the array to display messages in ascending order
$shoutbox_messages = array_reverse($shoutbox_messages);

// Fetch top 5 contributors excluding banned users
$sql = "SELECT UserID, CONCAT(FirstName, ' ', LastName) AS Name, school, points, ProfileImageURL 
        FROM Users 
        WHERE usertype = 'contributor' 
        AND (isBan = 0 OR isBan IS NULL)
        ORDER BY points DESC 
        LIMIT 5";

$result = $conn->query($sql);

$top_contributors = [];
if ($result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $top_contributors[] = $row;
    }
}

// Also modify the shoutbox message submission to check if user is banned
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['action']) && $_POST['action'] == 'send_message') {
    $user_id = $_SESSION['id'] ?? 0;
    
    // Check if user is banned before allowing message submission
    $check_ban = $conn->prepare("SELECT isBan FROM Users WHERE UserID = ?");
    $check_ban->bind_param("i", $user_id);
    $check_ban->execute();
    $ban_result = $check_ban->get_result();
    $is_banned = false;
    
    if ($ban_result->num_rows > 0) {
        $ban_row = $ban_result->fetch_assoc();
        $is_banned = $ban_row['isBan'] == 1;
    }
    $check_ban->close();
    
    if (!$is_banned) {
        $message = $conn->real_escape_string($_POST['shoutbox_message']);
        
        $sql = "INSERT INTO shoutbox_messages (user_id, message) VALUES (?, ?)";
        if ($stmt = $conn->prepare($sql)) {
            $stmt->bind_param("is", $user_id, $message);
            $stmt->execute();
            $stmt->close();
        }
        
        // Return the new message as JSON
        $userTypeInfo = getUserTypeInfo($_SESSION['usertype'] ?? 'user');
        $response = [
            'username' => $username ?? 'Guest',
            'message' => $message,
            'userTypeIcon' => $userTypeInfo['icon'],
            'userTypeLabel' => $userTypeInfo['label']
        ];
        echo json_encode($response);
    } else {
        // Return error for banned users
        echo json_encode([
            'error' => true,
            'message' => 'You are not allowed to post messages.'
        ]);
    }
    exit;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>PeerFlect</title>
    <link rel="stylesheet" href="style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.6.0/jquery.min.js"></script>
    <style>
    .shoutbox-message {
        display: flex;
        align-items: flex-start;
        padding: 5px 10px;
    }

    .message-content {
        flex: 1;
        margin-right: 10px;
    }

    .report-btn {
        border: none;
        color: #999;
        cursor: pointer;
        padding: 2px 5px;
        font-size: 0.8em;
        transition: opacity 0.2s;
        width: 30px;
        color: red;
    }

    .report-btn:hover {
        opacity: 1;
        color: #ff4444;
    }

    .report-modal {
        display: none;
        position: fixed;
        top: 50%;
        left: 50%;
        transform: translate(-50%, -50%);
        background: white;
        padding: 20px;
        border-radius: 8px;
        box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
        z-index: 1000;
        width: 300px;
    }

    .modal-overlay {
        display: none;
        position: fixed;
        top: 0;
        left: 0;
        right: 0;
        bottom: 0;
        background: rgba(0, 0, 0, 0.5);
        z-index: 999;
    }
    </style>
</head>
<body class="home-bg">
    <div class="topnav">
        <div class="logo">
            <a href="index.php"><img src="img/logo.png" alt="Logo"></a>
        </div>
        <div class="menu">
            <a href="index.php" onclick="transitionToPage('index.php'); return false;">Home</a>
            <a href="topics.php">Topics</a>
            <a href="#about" class="smooth-scroll">About</a>
            <div class="search-bar">
                <form action="search-results.php" method="GET">
                    <input type="text" name="q" placeholder="Search...">
                    <button type="submit">Search</button>
                </form>
            </div>
            <div class="menu-loginreg">
                <?php if(isset($_SESSION["loggedin"]) && $_SESSION["loggedin"] === true) { ?>
                    <?php if($_SESSION["usertype"] === "admin") { ?>
                        <a href="admin.php">Admin</a>
                    <?php } else if($_SESSION["usertype"] === "schooladmin") { ?>
                        <a href="schooladmin.php"> School Admin</a>
                    <?php } else { ?>
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
                    <?php } ?>
                <?php } else { ?>
                    <a href="login.php">Login</a>
                    <a href="signup.php">Register</a>
                <?php } ?>
            </div>
        </div>
    </div>
    <div class="home-container">
        <div class="form-flex-container">
            <div class="left-column">
                <div class="welcome-container">
                    <div class="welcome-logo">
                        <img src="img/logo.png" alt="Logo">
                    </div>
                    <h1>Welcome to <a href="#about" style="text-decoration: none; color: #8f75ec;">PeerFlect!</a></h1>
                    <h4 style="margin-top:0;">A collaborative reviewer about Computer Networking!</a></h4>
                </div>
                <div class="shoutbox-container">
                    <div class="shoutbox">
                        <h3>Communicate with everyone!</h3>
                        <div class="shoutbox-messages" id="shoutboxMessages">
                            <?php foreach ($shoutbox_messages as $msg): 
                                $userTypeInfo = getUserTypeInfo($msg['usertype']);
                            ?>
                                <div class="shoutbox-message">
                                    <div class="message-content">
                                        <strong>
                                            <span class="user-type-icon" title="<?= htmlspecialchars($userTypeInfo['label']) ?>">
                                                <?= $userTypeInfo['icon'] ?>
                                            </span>
                                            <?= htmlspecialchars($msg['Username'] ?? 'Guest') ?>:
                                        </strong>
                                        <?= htmlspecialchars($msg['message']) ?>
                                    </div>
                                    <?php if(isset($_SESSION["loggedin"]) && $_SESSION["loggedin"] === true): ?>
                                        <button class="report-btn" 
                                                title="Report user" 
                                                onclick="reportUser(<?= htmlspecialchars($msg['user_id']) ?>, <?= htmlspecialchars($msg['id']) ?>)">
                                            <i class="fas fa-flag"></i>
                                        </button>
                                    <?php endif; ?>
                                </div>
                            <?php endforeach; ?>
                        </div>
                        <form class="shoutbox-form" id="shoutboxForm">
                            <input type="text" name="shoutbox_message" placeholder="Type your message..." required>
                            <button type="submit">Send</button>
                        </form>
                    </div>
                </div> 
            </div>
            <div class="right-column">
                <div class="ranking-container">
                    <p style="background-color: yellow; width: 100%; display: flex; flex-direction: column; align-items: center; margin: auto;">Top Contributors</p>
                    <div class="contributor-slideshow">
                        <?php foreach ($top_contributors as $index => $contributor): ?>
                            <div class="contributor-slide <?php echo $index === 0 ? 'active' : ''; ?>">
                                <img src="<?php echo htmlspecialchars($contributor['ProfileImageURL']); ?>" alt="<?php echo htmlspecialchars($contributor['Name']); ?>" class="contributor-image">
                                <div class="rank-author-info">
                                    <h3><?php echo htmlspecialchars($contributor['Name']); ?></h3>
                                    <p><?php echo htmlspecialchars($contributor['school']); ?></p>
                                    <p><strong><?php echo htmlspecialchars($contributor['points']); ?> points</strong></p>
                                </div>
                                <button class="slide-control prev" onclick="changeSlide(-1)">&#10094;</button>
                                <button class="slide-control next" onclick="changeSlide(1)">&#10095;</button>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
                <div class="box-container">
                    <h2>Recent Posts</h2>
                    <?php
                    // Fetch recent posts from the database
                    $stmt = $conn->prepare("
                        SELECT p.PostID, p.Title, p.CreatedAt 
                        FROM Posts p
                        JOIN Users u ON p.UserID = u.UserID
                        WHERE p.isApproved = 1 
                        AND (u.isBan = 0 OR u.isBan IS NULL)
                        ORDER BY p.CreatedAt DESC 
                        LIMIT 10
                    ");
                    $stmt->execute();
                    $result = $stmt->get_result();

                    if ($result->num_rows > 0) {
                        while ($row = $result->fetch_assoc()) {
                            echo '<div class="post-box">';
                            echo '<h3><a href="post.php?postID=' . htmlspecialchars($row['PostID']) . '">' . htmlspecialchars($row['Title']) . '</a></h3>';
                            echo '<p>Posted on: ' . htmlspecialchars($row['CreatedAt']) . '</p>';
                            echo '</div>';
                        }
                    } else {
                        echo '<p>No posts available.</p>';
                    }
                    $stmt->close();
                    ?>
                </div>
            </div>
        </div>
    </div>
    <main>
        <section id="about">
            <div class="content">
            <h2>Our Vision</h2>
                <p>Your go-to platform for collaborative networking reviews. Our platform aims to bring together experts and enthusiasts in the field of computer networking to collectively review and discuss various topics, technologies, and advancements in the networking domain.</p>
            </div>
            <div class="image-placeholder">
                <img src="./img/About.jpg" alt="About Image">
            </div>
        </section>

        <section id="mission">
            <div class="image-placeholder">
                <img src="./img/Mission.jpg" alt="Mission Image">
            </div>
            <div class="content">
                <h2>Our Mission</h2>
                <p>Our mission is to foster a community where knowledge is shared, novel ideas are collaboratively developed, and networking professionals can stay updated with the latest trends and best practices in computer networking.</p>
            </div>
        </section>

        <section id="faq" class="faq-section">
            <h2>Frequently Asked Questions</h2>
            <ul class="faq-list">
                <li class="faq-item">
                    <h4 class="faq-question">
                        How can my school join the collaboration?
                        <span class="faq-arrow">&#9662;</span>
                    </h4>
                    <div class="faq-answer">
                        <p>To join the collaboration, reach us out on our email peerflect@gmail.com</p>
                    </div>
                </li>
                <li class="faq-item">
                    <h4 class="faq-question">
                        What are the different user roles?
                        <span class="faq-arrow">&#9662;</span>
                    </h4>
                    <div class="faq-answer">
                        <p>These are the roles on our platform:</p>
                        <ul>
                            <li><strong>Guest Users:</strong> Can only view the content on the website.</li>
                            <li><strong>Regular Users:</strong> Can view, react (heart), and comment on posts made by contributors.</li>
                            <li><strong>Contributors:</strong> Can post questions and answers, which will be visible to regular users and guests.</li>
                        </ul>
                    </div>
                </li>
                <li class="faq-item">
                    <h4 class="faq-question">
                        Can I suggest topics for discussion?
                        <span class="faq-arrow">&#9662;</span>
                    </h4>
                    <div class="faq-answer">
                        <p>Absolutely! We welcome suggestions for topics from our community members. You can submit your topic ideas by reaching out to our support team.</p>
                    </div>
                </li>
            </ul>
        </section>
    </main>

    <footer>
        <div class="footer-container">
            <div class="footer-section">
                <h4>Contact Us</h4>
                <p>Email: peerflect@gmail.com</p>
                <p>Phone: 092709049616</p>
            </div>
            <div class="footer-section">
                <h4>Quick Links</h4>
                <ul>
                    <li><a href="index.php">Home</a></li>
                    <li><a href="topics.php">Topics</a></li>
                    <li><a href="index.php?scroll=about">About</a></li>
                </ul>
            </div>
            <div class="footer-section">
                <h4>Follow Us</h4>
                <div class="social-icons">
                    <a href="#"><i class="fa-brands fa-facebook-f"></i></a>
                    <a href="#"><i class="fa-brands fa-twitter"></i></a>
                    <a href="#"><i class="fa-brands fa-linkedin-in"></i></a>
                    <a href="#"><i class="fa-brands fa-instagram"></i></a>
                </div>
            </div>
        </div>
        <p>&copy; 2024 Collaborative Reviewer for Computer Networking. All rights reserved.</p>
    </footer>
    <div class="boxes">
        <ul class="single-box">
            <li></li>
            <li></li>
            <li></li>
            <li></li>
            <li></li>
            <li></li>
            <li></li>
        </ul>
    </div>
    <div class="modal-overlay" id="modalOverlay"></div>
    <div class="report-modal" id="reportModal">
        <h3>Report User</h3>
        <textarea id="reportReason" placeholder="Please describe why you're reporting this user..." rows="4" style="width: 100%; margin: 10px 0;"></textarea>
        <div style="display: flex; justify-content: space-between;">
            <button id="submitReportBtn">Submit Report</button>
            <button id="cancelReportBtn">Cancel</button>
        </div>
    </div>
    <script>
        $(document).ready(function() {
            // Get the URL parameters
            const urlParams = new URLSearchParams(window.location.search);
            const scrollTarget = urlParams.get('scroll');
            
            // Check if we should scroll to about section
            if (scrollTarget === 'about') {
                // Wait a tiny bit for page to fully load
                setTimeout(function() {
                    var target = $('#about');
                    if (target.length) {
                        $('html, body').animate({
                            scrollTop: target.offset().top - 100 // 100px offset for header
                        }, 1000, 'swing');
                        
                        // Update URL to remove query parameter
                        window.history.replaceState({}, document.title, 'index.php#about');
                    }
                }, 100);
            }
            
            // Also handle direct #about links for when already on index.php
            $('.smooth-scroll, a[href="#about"]').on('click', function(event) {
                if (window.location.pathname.includes('index.php')) {
                    event.preventDefault();
                    var target = $('#about');
                    if (target.length) {
                        $('html, body').animate({
                            scrollTop: target.offset().top - 100
                        }, 1000, 'swing');
                    }
                }
            });
        });

        document.addEventListener('DOMContentLoaded', function() {
            const faqItems = document.querySelectorAll('.faq-item');
            
            faqItems.forEach(item => {
                const question = item.querySelector('.faq-question');
                
                question.addEventListener('click', () => {
                    const currentlyActive = document.querySelector('.faq-item.active');
                    
                    if (currentlyActive && currentlyActive !== item) {
                        currentlyActive.classList.remove('active');
                    }
                    
                    item.classList.toggle('active');
                });
            });
        });

        $(document).ready(function() {
            function scrollShoutboxToBottom() {
                var shoutboxMessages = document.getElementById('shoutboxMessages');
                shoutboxMessages.scrollTop = shoutboxMessages.scrollHeight;
            }

            $('#shoutboxForm').submit(function(e) {
                e.preventDefault();
                var message = $('input[name="shoutbox_message"]').val();
                
                $.ajax({
                    url: 'index.php',
                    type: 'POST',
                    data: {
                        action: 'send_message',
                        shoutbox_message: message
                    },
                    dataType: 'json',
                    success: function(response) {
                        var newMessage = '<div class="shoutbox-message">' +
                            '<strong>' +
                            '<span class="user-type-icon" title="' + response.userTypeLabel + '">' +
                            response.userTypeIcon +
                            '</span>' +
                            response.username + ':' +
                            '</strong> ' +
                            response.message +
                            '</div>';
                        
                        $('#shoutboxMessages').append(newMessage);
                        $('input[name="shoutbox_message"]').val('');
                        scrollShoutboxToBottom();
                    },
                    error: function() {
                        alert('Error sending message. Please try again.');
                    }
                });
            });

            scrollShoutboxToBottom();
    });
    </script>
    <script>
        let currentSlide = 0;
        const slides = document.querySelectorAll('.contributor-slide');

        function showSlide(n) {
            slides[currentSlide].classList.remove('active');
            currentSlide = (n + slides.length) % slides.length;
            slides[currentSlide].classList.add('active');
        }

        function changeSlide(n) {
            showSlide(currentSlide + n);
        }

        function autoSlide() {
            changeSlide(1);
        }

        // Change slide every 5 seconds
        setInterval(autoSlide, 5000);
    </script>
<script>
    let reportedUserId = null;
    let reportedMessageId = null;

    function reportUser(userId, messageId) {
        reportedUserId = userId;
        reportedMessageId = messageId;
        document.getElementById('modalOverlay').style.display = 'block';
        document.getElementById('reportModal').style.display = 'block';
        document.getElementById('reportReason').value = '';
    }

    function closeReportModal() {
        document.getElementById('modalOverlay').style.display = 'none';
        document.getElementById('reportModal').style.display = 'none';
        reportedUserId = null;
        reportedMessageId = null;
    }

    function submitReport() {
        const reason = document.getElementById('reportReason').value;
        if (!reason.trim()) {
            alert('Please provide a reason for reporting.');
            return;
        }

        console.log('Submitting report for user ID:', reportedUserId);
        console.log('Message ID:', reportedMessageId);
        console.log('Reason:', reason);

        $.ajax({
            url: 'report_user.php',
            type: 'POST',
            data: {
                user_id: reportedUserId,
                message_id: reportedMessageId,
                reason: reason
            },
            dataType: 'json',
            success: function(response) {
                console.log('Server response:', response);
                if (response.success) {
                    alert(response.message);
                    closeReportModal();
                } else {
                    alert(response.message || 'Error reporting user. Please try again.');
                }
            },
            error: function(jqXHR, textStatus, errorThrown) {
                console.error('AJAX error:', textStatus, errorThrown);
                console.log('Response Text:', jqXHR.responseText);
                alert('Error reporting user. Please check the console for more details.');
            }
        });
    }

    // Event listeners
    document.getElementById('modalOverlay').addEventListener('click', closeReportModal);
    document.getElementById('submitReportBtn').addEventListener('click', function(e) {
        e.preventDefault();
        console.log('Submit button clicked');
        submitReport();
    });
    document.getElementById('cancelReportBtn').addEventListener('click', closeReportModal);

    // Stop propagation on modal to prevent closing when clicking inside
    document.getElementById('reportModal').addEventListener('click', function(e) {
        e.stopPropagation();
    });


</script>
</body>
</html>