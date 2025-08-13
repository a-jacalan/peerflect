<?php
session_start();

// Include database connection
require_once "config.php";
require_once "check-banned.php";

// Handle all POST actions
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Suggestion submission and editing
    if (!isset($_POST['action'])) {
        // Ensure user is logged in
        if (!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true) {
            echo json_encode(['error' => 'Please login to submit a suggestion']);
            exit;
        }

        // Check if user is a contributor
        if ($_SESSION["usertype"] !== 'contributor') {
            echo json_encode(['error' => 'Only contributors can submit suggestions']);
            exit;
        }

        $topic = trim($_POST["topic"]);
        $subtopics = trim($_POST["subtopics"]); 
        $description = trim($_POST["description"]);
        
        // Validate required fields
        if (empty($topic) || empty($description)) {
            echo json_encode(['error' => 'Please fill all required fields']);
            exit;
        }

        // Check if this is an edit or a new submission
        if (isset($_POST['suggestion_id']) && !empty($_POST['suggestion_id'])) {
            // Edit existing suggestion
            $suggestionId = $_POST['suggestion_id'];
            
            // Verify the user is the author
            $checkAuthor = $conn->prepare("SELECT UserID FROM TopicSuggestions WHERE SuggestionID = ?");
            $checkAuthor->bind_param("i", $suggestionId);
            $checkAuthor->execute();
            $result = $checkAuthor->get_result()->fetch_assoc();
            
            if ($result['UserID'] != $_SESSION["id"]) {
                echo json_encode(['error' => 'You can only edit your own suggestions']);
                exit;
            }

            // Update suggestion
            $sql = "UPDATE TopicSuggestions SET Topic = ?, Subtopics = ?, Description = ? WHERE SuggestionID = ?";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("sssi", $topic, $subtopics, $description, $suggestionId);
        } else {
            // Insert new suggestion
            $sql = "INSERT INTO TopicSuggestions (UserID, Topic, Subtopics, Description, Status) VALUES (?, ?, ?, ?, 'PENDING')";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("isss", $_SESSION["id"], $topic, $subtopics, $description);
        }
        
        if ($stmt->execute()) {
            echo json_encode(['success' => true]);
        } else {
            echo json_encode(['error' => 'Failed to submit/update suggestion']);
        }
        exit;
    }

    // Handle voting
    if (isset($_POST['action']) && $_POST['action'] == 'vote') {
        if (!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true) {
            echo json_encode(['error' => 'Please login to vote']);
            exit;
        }

        // Check if user is a contributor
        if ($_SESSION["usertype"] !== 'contributor') {
            echo json_encode(['error' => 'Only contributors can vote']);
            exit;
        }

        $suggestionId = $_POST['suggestionId'];
        $voteType = $_POST['voteType']; 

        // Check if the user is the author of the suggestion
        $checkAuthor = $conn->prepare("SELECT UserID FROM TopicSuggestions WHERE SuggestionID = ?");
        $checkAuthor->bind_param("i", $suggestionId);
        $checkAuthor->execute();
        $authorResult = $checkAuthor->get_result()->fetch_assoc();

        if ($authorResult['UserID'] == $_SESSION["id"]) {
            echo json_encode(['error' => 'You cannot vote on your own suggestion']);
            exit;
        }

        // Check if suggestion is still in PENDING status
        $checkStatus = $conn->prepare("SELECT Status FROM TopicSuggestions WHERE SuggestionID = ?");
        $checkStatus->bind_param("i", $suggestionId);
        $checkStatus->execute();
        $statusResult = $checkStatus->get_result()->fetch_assoc();

        if ($statusResult['Status'] !== 'Pending') {
            echo json_encode(['error' => 'Voting is only allowed for pending suggestions']);
            exit;
        }

        // Check existing vote
        $checkVote = $conn->prepare("SELECT VoteType FROM SuggestionVotes WHERE UserID = ? AND SuggestionID = ?");
        $checkVote->bind_param("ii", $_SESSION["id"], $suggestionId);
        $checkVote->execute();
        $existingVote = $checkVote->get_result()->fetch_assoc();

        if ($existingVote) {
            if ($existingVote['VoteType'] == $voteType) {
                $sql = "DELETE FROM SuggestionVotes WHERE UserID = ? AND SuggestionID = ?";
                $stmt = $conn->prepare($sql);
                $stmt->bind_param("ii", $_SESSION["id"], $suggestionId);
            } else {
                $sql = "UPDATE SuggestionVotes SET VoteType = ? WHERE UserID = ? AND SuggestionID = ?";
                $stmt = $conn->prepare($sql);
                $stmt->bind_param("iii", $voteType, $_SESSION["id"], $suggestionId);
            }
        } else {
            $sql = "INSERT INTO SuggestionVotes (UserID, SuggestionID, VoteType) VALUES (?, ?, ?)";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("iii", $_SESSION["id"], $suggestionId, $voteType);
        }
        
        if ($stmt->execute()) {
            echo json_encode(['success' => true]);
        } else {
            echo json_encode(['error' => 'Failed to record vote']);
        }
        exit;
    }
    
    // Handle suggestion deletion
    if (isset($_POST['action']) && $_POST['action'] == 'delete') {
        if (!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true) {
            echo json_encode(['error' => 'Please login to delete a suggestion']);
            exit;
        }
        
        $suggestionId = $_POST['suggestionId'];
        
        // Verify the user is the author or an admin
        $checkAuthor = $conn->prepare("SELECT UserID FROM TopicSuggestions WHERE SuggestionID = ?");
        $checkAuthor->bind_param("i", $suggestionId);
        $checkAuthor->execute();
        $result = $checkAuthor->get_result()->fetch_assoc();
        
        if ($result['UserID'] != $_SESSION["id"] && $_SESSION["usertype"] !== 'admin') {
            echo json_encode(['error' => 'You can only delete your own suggestions or be an admin']);
            exit;
        }
        
        try {
            // Start a transaction
            $conn->begin_transaction();
            
            // Delete associated votes
            $sqlDeleteVotes = "DELETE FROM SuggestionVotes WHERE SuggestionID = ?";
            $stmtVotes = $conn->prepare($sqlDeleteVotes);
            $stmtVotes->bind_param("i", $suggestionId);
            $stmtVotes->execute();
            
            // Delete the suggestion
            $sqlDelete = "DELETE FROM TopicSuggestions WHERE SuggestionID = ?";
            $stmt = $conn->prepare($sqlDelete);
            $stmt->bind_param("i", $suggestionId);
            $stmt->execute();
            
            // Commit the transaction
            $conn->commit();
            
            echo json_encode(['success' => true]);
        } catch (Exception $e) {
            // Rollback the transaction in case of error
            $conn->rollback();
            
            echo json_encode([
                'error' => 'Failed to delete suggestion',
                'debug' => $e->getMessage()
            ]);
        }
        exit;
    }

    // Handle admin status update
    if (isset($_POST['action']) && $_POST['action'] == 'update_status' && 
       isset($_SESSION["usertype"]) && ($_SESSION["usertype"] == 'admin')) {
        
        $suggestionId = $_POST['suggestionId'];
        $status = $_POST['status']; // 'APPROVED', 'ADDED', or 'REJECTED'

        $sql = "UPDATE TopicSuggestions SET Status = ? WHERE SuggestionID = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("si", $status, $suggestionId);
        
        if ($stmt->execute()) {
            echo json_encode(['success' => true]);
        } else {
            echo json_encode(['error' => 'Failed to update status']);
        }
        exit;
    }
}

// Fetch topic suggestions 
$sql = "SELECT 
            TopicSuggestions.SuggestionID,
            TopicSuggestions.UserID,
            TopicSuggestions.Topic,
            TopicSuggestions.Subtopics,
            TopicSuggestions.Description,
            TopicSuggestions.CreatedAt,
            TopicSuggestions.Status,
            Users.ProfileImageURL,
            CONCAT(Users.FirstName, ' ', Users.LastName) AS AuthorName,
            Users.School AS AuthorSchool,
            COALESCE(UpVotes.up_count, 0) AS UpVotes,
            COALESCE(DownVotes.down_count, 0) AS DownVotes,
            CASE 
                WHEN UserVotes.VoteType IS NOT NULL THEN UserVotes.VoteType
                ELSE 0
            END AS UserVoteType
        FROM TopicSuggestions 
        INNER JOIN Users ON TopicSuggestions.UserID = Users.UserID 
        LEFT JOIN (
            SELECT SuggestionID, COUNT(*) AS up_count 
            FROM SuggestionVotes 
            WHERE VoteType = 1
            GROUP BY SuggestionID
        ) AS UpVotes ON TopicSuggestions.SuggestionID = UpVotes.SuggestionID
        LEFT JOIN (
            SELECT SuggestionID, COUNT(*) AS down_count 
            FROM SuggestionVotes 
            WHERE VoteType = -1
            GROUP BY SuggestionID
        ) AS DownVotes ON TopicSuggestions.SuggestionID = DownVotes.SuggestionID
        LEFT JOIN (
            SELECT SuggestionID, VoteType
            FROM SuggestionVotes
            WHERE UserID = ?
        ) AS UserVotes ON TopicSuggestions.SuggestionID = UserVotes.SuggestionID
        WHERE (Users.isBan = 0 OR Users.isBan IS NULL)
        ORDER BY 
            CASE WHEN TopicSuggestions.Status = 'PENDING' THEN 0 ELSE 1 END, 
            TopicSuggestions.CreatedAt DESC";

$stmt = $conn->prepare($sql);
$userId = isset($_SESSION["id"]) ? $_SESSION["id"] : 0;
$stmt->bind_param("i", $userId);
$stmt->execute();
$result = $stmt->get_result();

$suggestions = [];
while ($row = $result->fetch_assoc()) {
    $suggestions[] = $row;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Topic Suggestions</title>
    <link rel="stylesheet" href="style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.3/css/all.min.css">
    <style>
        .admin-status-actions {
            display: flex;
            justify-content: space-between;
            margin-top: 10px;
        }
        .admin-status-actions button {
            padding: 5px 10px;
            margin: 0 5px;
            font-size: 0.8em;
        }
        .vote-disabled {
            opacity: 0.5;
            cursor: not-allowed;
        }

        .vote-button:disabled {
            pointer-events: none;
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
    <div class="suggestions-container">
        <h2>Topic Suggestions</h2>
        <p> Note: Only contributor can suggest topics and vote.</p>
        <?php if(isset($_SESSION["loggedin"]) && $_SESSION["loggedin"] === true && $_SESSION["usertype"] === "contributor"): ?>
            <a href="#" class="add-suggestion-btn" id="openModalBtn">
                <i class="fas fa-plus"></i> Suggest New Topic
            </a>
        <?php endif; ?>

        <?php if (empty($suggestions)): ?>
            <p>No topic suggestions yet</p>
        <?php else: ?>
            <div class="suggestions-grid">
                <?php foreach($suggestions as $suggestion): ?>
                    <div class="suggestion-item">
                        <div class="suggestion-header">
                            <img class="author-image" src="<?php echo htmlspecialchars($suggestion['ProfileImageURL']); ?>" alt="Profile Image">
                            <div class="suggestion-info">
                                <div class="suggestion-title">Main Topic: <?php echo htmlspecialchars($suggestion['Topic']); ?></div>
                                <div class="subtopics-list">Subtopics: 
                                    <?php 
                                    $subtopics = explode(',', $suggestion['Subtopics']);
                                    foreach($subtopics as $subtopic): 
                                        if(trim($subtopic)):
                                    ?>
                                        <span class="subtopic-tag"><?php echo htmlspecialchars(trim($subtopic)); ?></span>
                                    <?php 
                                        endif;
                                    endforeach; 
                                    ?>
                                </div>
                                <div class="suggestion-author">By <?php echo htmlspecialchars($suggestion['AuthorName']); ?></div>
                                <div class="suggestion-school"><?php echo htmlspecialchars($suggestion['AuthorSchool']); ?></div>
                            </div>
                            <div class="status-badge status-<?php echo strtolower($suggestion['Status']); ?>">
                                <?php echo htmlspecialchars($suggestion['Status']); ?>
                            </div>
                        </div>
                        <div class="suggestion-description">
                            <?php echo htmlspecialchars($suggestion['Description']); ?>
                        </div>
                        <div class="suggestion-meta">
                            <div class="voting-buttons">
                                <button class="vote-button <?php echo (isset($_SESSION["usertype"]) && $_SESSION["usertype"] === 'contributor' && $suggestion['Status'] === 'Pending') ? '' : 'vote-disabled'; ?>"
                                        <?php echo (isset($_SESSION["usertype"]) && $_SESSION["usertype"] === 'contributor' && $suggestion['Status'] === 'Pending') ? "onclick=\"vote({$suggestion['SuggestionID']}, 1)\"" : 'disabled'; ?>
                                        data-suggestion-id="<?php echo $suggestion['SuggestionID']; ?>"
                                        data-vote-type="up">
                                    <i class="fas fa-thumbs-up"></i>
                                    <span class="upvote-count"><?php echo $suggestion['UpVotes']; ?></span>
                                </button>
                                <button class="vote-button <?php echo (isset($_SESSION["usertype"]) && $_SESSION["usertype"] === 'contributor' && $suggestion['Status'] === 'Pending') ? '' : 'vote-disabled'; ?>"
                                        <?php echo (isset($_SESSION["usertype"]) && $_SESSION["usertype"] === 'contributor' && $suggestion['Status'] === 'Pending') ? "onclick=\"vote({$suggestion['SuggestionID']}, -1)\"" : 'disabled'; ?>
                                        data-suggestion-id="<?php echo $suggestion['SuggestionID']; ?>"
                                        data-vote-type="down">
                                    <i class="fas fa-thumbs-down"></i>
                                    <span class="downvote-count"><?php echo $suggestion['DownVotes']; ?></span>
                                </button>
                                <?php if(
                                    (isset($_SESSION["id"]) && $_SESSION["id"] == $suggestion['UserID'] && $suggestion['Status'] === 'Pending') || 
                                    (isset($_SESSION["usertype"]) && ($_SESSION["usertype"] === 'admin' || $_SESSION["usertype"] === 'schooladmin'))
                                ): ?>
                                    <div class="author-actions">
                                        <?php if(isset($_SESSION["id"]) && $_SESSION["id"] == $suggestion['UserID'] && $suggestion['Status'] === 'Pending'): ?>
                                            <button class="edit-btn" onclick="editSuggestion(<?php echo $suggestion['SuggestionID']; ?>, 
                                                '<?php echo addslashes($suggestion['Topic']); ?>', 
                                                '<?php echo addslashes($suggestion['Subtopics']); ?>', 
                                                '<?php echo addslashes($suggestion['Description']); ?>')">
                                                <i class="fas fa-edit"></i> Edit
                                            </button>
                                        <?php endif; ?>
                                        <button class="delete-btn" onclick="deleteSuggestion(<?php echo $suggestion['SuggestionID']; ?>)">
                                            <i class="fas fa-trash"></i> Delete
                                        </button>
                                    </div>
                                <?php endif; ?>
                            </div>

                            <!-- Admin status update buttons -->
                            <?php if(isset($_SESSION["usertype"]) && ($_SESSION["usertype"] === 'admin' || $_SESSION["usertype"] === 'schooladmin') && $suggestion['Status'] === 'Pending'): ?>
                                <div class="admin-status-actions approve">
                                    <button class="btn btn-success" onclick="updateStatus(<?php echo $suggestion['SuggestionID']; ?>, 'APPROVED')">
                                        <i class="fas fa-check"></i> Approve
                                    </button>
                                    <button class="btn btn-danger reject" onclick="updateStatus(<?php echo $suggestion['SuggestionID']; ?>, 'REJECTED')">
                                        <i class="fas fa-times"></i> Reject
                                    </button>
                                </div>
                            <?php endif; ?>

                            <div class="suggestion-date">
                                <?php echo date('M d, Y', strtotime($suggestion['CreatedAt'])); ?>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
    <div id="suggestionModal" class="modal">
        <div class="modal-content">
            <span class="close">&times;</span>
            <h3>Suggest New Topic</h3>
            <form id="suggestionForm" method="POST">
                <input type="hidden" id="suggestion_id" name="suggestion_id" value="">
                <div class="form-group">
                    <label for="topic">Main Topic</label>
                    <input type="text" id="topic" name="topic" required>
                </div>
                <div class="form-group">
                    <label for="subtopics">Subtopics</label>
                    <input type="text" id="subtopics" name="subtopics" class="subtopics-input">
                    <div class="subtopics-help">Enter subtopics separated by commas (e.g., "OSI Model, Topology, VPN")</div>
                </div>
                <div class="form-group">
                    <label for="description">Description</label>
                    <textarea id="description" name="description" rows="4" required></textarea>
                </div>
                <button type="submit" class="submit-btn">Submit Suggestion</button>
            </form>
        </div>
    </div>

    <script>
        function vote(suggestionId, voteType) {
            fetch('topic-suggestions.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: `action=vote&suggestionId=${suggestionId}&voteType=${voteType}`
            })
            .then(response => response.json())
            .then(data => {
                if(data.error) {
                    alert(data.error);
                    if(data.error === 'Please login to vote' || 
                    data.error === 'Only contributors can vote' ||
                    data.error === 'Voting is only allowed for pending suggestions' ||
                    data.error === 'You cannot vote on your own suggestion') {
                        window.location.href = 'topic-suggestions.php';
                    }
                } else {
                    location.reload();
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('An error occurred while voting');
            });
        }

        function updateStatus(suggestionId, status) {
            fetch('topic-suggestions.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: `action=update_status&suggestionId=${suggestionId}&status=${status}`
            })
            .then(response => response.json())
            .then(data => {
                if(data.success) {
                    alert(`Suggestion ${status.toLowerCase()} successfully!`);
                    location.reload();
                } else {
                    alert(data.error || 'An error occurred while updating status');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('An error occurred while updating status');
            });
        }

        const modal = document.getElementById("suggestionModal");
        const btn = document.getElementById("openModalBtn");
        const span = document.getElementsByClassName("close")[0];
        const form = document.getElementById("suggestionForm");

        btn.onclick = function(e) {
            e.preventDefault();
            // Clear the form and EXPLICITLY remove suggestion_id
            form.reset();
            const suggestionIdInput = document.getElementById('suggestion_id');
            if (suggestionIdInput) {
                suggestionIdInput.value = ''; // Explicitly set to empty string
            }
            modal.style.display = "block";
        }

        span.onclick = function() {
            modal.style.display = "none";
        }

        window.onclick = function(event) {
            if (event.target == modal) {
                modal.style.display = "none";
            }
        }

        form.onsubmit = function(e) {
            e.preventDefault();
            
            const formData = new FormData(form);
            const suggestionId = document.getElementById('suggestion_id').value;
            
            // If we have a suggestion ID, this is an edit operation
            if (suggestionId) {
                formData.append('suggestion_id', suggestionId);
            }
            
            fetch('topic-suggestions.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if(data.success) {
                    alert(suggestionId ? 'Suggestion updated successfully!' : 'Suggestion submitted successfully!');
                    modal.style.display = "none";
                    form.reset();
                    // Remove the suggestion_id hidden input if it exists
                    const hiddenInput = form.querySelector('input[name="suggestion_id"]');
                    if (hiddenInput) {
                        hiddenInput.remove();
                    }
                    location.reload();
                } else {
                    alert(data.error || 'An error occurred');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('An error occurred');
            });
        }

        function editSuggestion(id, topic, subtopics, description) {
            // Populate the modal with existing data
            document.getElementById('topic').value = topic;
            document.getElementById('subtopics').value = subtopics;
            document.getElementById('description').value = description;
            
            // Set the suggestion ID
            document.getElementById('suggestion_id').value = id;
            
            // Show the modal
            document.getElementById('suggestionModal').style.display = "block";
        }

        function deleteSuggestion(id) {
            // Check if the suggestion is already in a final state
            const suggestionElement = document.querySelector(`.suggestion-item:has([data-suggestion-id="${id}"])`);
            const statusBadge = suggestionElement ? suggestionElement.querySelector('.status-badge') : null;
            const status = statusBadge ? statusBadge.textContent.trim() : '';

            // If the suggestion is already rejected or approved, confirm permanent deletion
            if (status === 'REJECTED' || status === 'APPROVED') {
                if (!confirm('This suggestion has already been processed. Do you want to permanently delete it?')) {
                    return;
                }
            } else {
                // For pending suggestions, use standard confirmation
                if (!confirm('Are you sure you want to delete this suggestion?')) {
                    return;
                }
            }

            fetch('topic-suggestions.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: `action=delete&suggestionId=${id}`
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    alert('Suggestion deleted successfully!');
                    location.reload();
                } else {
                    alert(data.error || 'An error occurred while deleting the suggestion');
                    if(data.error === 'Please login to delete a suggestion') {
                        window.location.href = 'login.php';
                    }
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('An error occurred while deleting the suggestion');
            });
        }
    </script>
</body>
</html>