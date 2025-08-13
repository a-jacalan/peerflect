<?php
session_start();

// Include database connection
require_once "config.php";
require_once "check-banned.php";

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Uploaded Presentations</title>
    <link rel="stylesheet" href="style.css">
    <style>
        .topics-container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 20px;
        }
        .posts-grid {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 30px;
            row-gap: 30px;
        }
        @media (max-width: 768px) {
            .posts-grid {
                grid-template-columns: 1fr;
                gap: 25px;
            }
        }
        .br-post-item {
            display: flex;
            align-items: flex-start;
            background-color: #f9f9f9;
            border-radius: 8px;
            padding: 20px;
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
            height: 80%;
            transition: transform 0.2s ease;
            cursor: pointer;
        }
        .br-post-item:hover {
            transform: translateY(-5px);
        }
        .br-presentation-thumbnail {
            width: 150px;
            height: 150px;
            margin-right: 15px;
            object-fit: cover;
            border-radius: 8px;
        }
        .br-post-content {
            flex-grow: 1;
            overflow: hidden;
        }
        .br-post-title a {
            font-size: 1.1em;
            font-weight: bold;
            color: #333;
            text-decoration: none;
            display: block;
            max-width: 100%;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }
        .br-post-date {
            color: #888;
            font-size: 0.8em;
            margin-top: 10px;
        }
        .button-primary {
            display: inline-block;
            padding: 10px 20px;
            background-color: #007bff;
            color: white;
            text-decoration: none;
            border-radius: 5px;
            transition: background-color 0.2s ease;
        }
        .button-primary:hover {
            background-color: #0056b3;
        }
        .modal {
            display: none;
            position: fixed;
            z-index: 1000;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0,0,0,0.5);
        }

        .modal-content {
            background-color: #fefefe;
            margin: 15% auto;
            padding: 20px;
            border: 1px solid #888;
            width: 80%;
            max-width: 500px;
            border-radius: 8px;
            position: relative;
        }

        .close {
            position: absolute;
            right: 20px;
            top: 10px;
            color: #aaa;
            font-size: 28px;
            font-weight: bold;
            cursor: pointer;
        }

        .close:hover {
            color: black;
        }

        .upload-form {
            padding: 20px;
        }

        .form-group {
            margin-bottom: 15px;
        }

        .form-group label {
            display: block;
            margin-bottom: 5px;
        }

        .form-group input {
            width: 100%;
            padding: 8px;
            border: 1px solid #ddd;
            border-radius: 4px;
        }

        .upload-btn {
            background: #007bff;
            color: white;
            padding: 10px 20px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
        }

        .upload-btn:hover {
            background: #0056b3;
        }

        .delete-btn {
            background-color: #dc3545;
            color: white;
            border: none;
            padding: 5px 10px;
            border-radius: 4px;
            cursor: pointer;
            margin-top: 10px;
        }

        .delete-btn:hover {
            background-color: #c82333;
        }

        .br-post-item-link {
            text-decoration: none;
            color: inherit;
            display: block;
        }
        
        .br-post-title {
            font-size: 1.1em;
            font-weight: bold;
            color: #333;
            max-width: 100%;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
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

    <div class="topics-container">
        <h2>Uploaded Presentations</h2>
        <?php if(isset($_SESSION["loggedin"]) && $_SESSION["loggedin"] === true && $_SESSION["usertype"] === "admin") { ?>
            <div style="margin-bottom: 20px;">
                <button onclick="openUploadModal()" class="button-primary">Upload New Presentation</button>
            </div>
        <?php } ?>
        
        <!-- Add Modal HTML -->
        <div id="uploadModal" class="modal">
            <div class="modal-content">
                <span class="close">&times;</span>
                <div class="upload-form">
                    <h2>Upload PowerPoint</h2>
                    <form action="upload.php" method="post" enctype="multipart/form-data">
                        <div class="form-group">
                            <label for="title">Presentation Title:</label>
                            <input type="text" name="title" id="title" required>
                        </div>
                        <div class="form-group">
                            <label for="presentation">Select PowerPoint File:</label>
                            <input type="file" name="presentation" id="presentation" accept=".ppt,.pptx" required>
                        </div>
                        <div class="form-group">
                            <label for="thumbnail">Select Thumbnail Image (JPEG):</label>
                            <input type="file" name="thumbnail" id="thumbnail" accept=".jpg,.jpeg" required>
                        </div>
                        <button type="submit" class="upload-btn">Upload</button>
                    </form>
                </div>
            </div>
        </div>

        <div class="posts-grid">
            <?php
            // Database connection
            $db = new PDO("mysql:host=localhost;dbname=peerflect", "root", "");
            $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

            // Fetch presentations from the database
            $stmt = $db->query("SELECT * FROM presentations ORDER BY upload_date DESC");
            $presentations = $stmt->fetchAll(PDO::FETCH_ASSOC);

            if ($presentations) {
                foreach ($presentations as $presentation) {
                    echo "<a href='viewer.php?file=output/{$presentation['pdf_filename']}' class='br-post-item-link'>";
                    echo "<div class='br-post-item'>";
                    echo "<img class='br-presentation-thumbnail' src='thumbnails/{$presentation['thumb_filename']}' alt='Presentation Thumbnail'>";
                    echo "<div class='br-post-content'>";
                    echo "<div class='br-post-title'>" . htmlspecialchars($presentation['title']) . "</div>";
                    echo "<div class='br-post-date'>Uploaded: {$presentation['upload_date']}</div>";
                    if(isset($_SESSION["loggedin"]) && $_SESSION["usertype"] === "admin") {
                        echo "<button class='delete-btn' onclick='deletePresentation({$presentation['id']}); event.preventDefault();'>Delete</button>";
                    }
                    echo "</div>";
                    echo "</div>";
                    echo "</a>";
                }
            }
            ?>
        </div>
    </div>

    <script src="js/transition.js"></script>
    <script>
        const modal = document.getElementById("uploadModal");
        const closeBtn = document.getElementsByClassName("close")[0];

        function openUploadModal() {
            modal.style.display = "block";
        }

        closeBtn.onclick = function() {
            modal.style.display = "none";
        }

        window.onclick = function(event) {
            if (event.target == modal) {
                modal.style.display = "none";
            }
        }

        function deletePresentation(id) {
            if (confirm('Are you sure you want to delete this presentation?')) {
                fetch('delete-presentation.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify({ id: id })
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        location.reload();
                    } else {
                        alert('Error deleting presentation');
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('Error deleting presentation');
                });
            }
        }
    </script>
</body>
</html>