<?php
session_start();

// Check if the user is not logged in or is not an admin, redirect to login page
if (!isset($_SESSION["loggedin"]) || !isset($_SESSION["usertype"]) || $_SESSION["usertype"] !== "admin") {
    header("location: login.php");
    exit;
}

// Include database connection
require_once "config.php";
require_once "check-banned.php";

// Handle delete topic action
if (isset($_POST["delete_topic"])) {
    $topicID = $_POST["delete_topic"];
    
    // Get the topic name before deletion
    $sqlGetTopicInfo = "SELECT topic_name FROM topics WHERE topic_id = ?";
    $stmtGetTopicInfo = $conn->prepare($sqlGetTopicInfo);
    $stmtGetTopicInfo->bind_param("i", $topicID);
    $stmtGetTopicInfo->execute();
    $resultTopicInfo = $stmtGetTopicInfo->get_result();
    $topicInfo = $resultTopicInfo->fetch_assoc();
    
    // Perform deletion query
    $sqlDeleteTopic = "DELETE FROM topics WHERE topic_id = ?";
    $stmtDeleteTopic = $conn->prepare($sqlDeleteTopic);
    $stmtDeleteTopic->bind_param("i", $topicID);
    
    if ($stmtDeleteTopic->execute()) {
        // Log the deletion
        $adminID = $_SESSION['id'];
        $action = "Deleted Topic";
        $details = "Deleted topic with ID: $topicID, Name: {$topicInfo['topic_name']}";
        $sqlLogDeletion = "INSERT INTO adminlog (AdminID, Action, Details) VALUES (?, ?, ?)";
        $stmtLogDeletion = $conn->prepare($sqlLogDeletion);
        $stmtLogDeletion->bind_param("iss", $adminID, $action, $details);
        $stmtLogDeletion->execute();
    }
}

// Handle bulk delete action
if (isset($_POST["bulk_delete_topics"])) {
    $selectedTopics = $_POST["selected_topics"] ?? [];
    if (!empty($selectedTopics)) {
        $placeholders = implode(',', array_fill(0, count($selectedTopics), '?'));
        
        // Get topic info of topics being deleted
        $sqlGetTopicInfo = "SELECT topic_id, topic_name FROM topics WHERE topic_id IN ($placeholders)";
        $stmtGetTopicInfo = $conn->prepare($sqlGetTopicInfo);
        $stmtGetTopicInfo->bind_param(str_repeat('i', count($selectedTopics)), ...$selectedTopics);
        $stmtGetTopicInfo->execute();
        $resultTopicInfo = $stmtGetTopicInfo->get_result();
        $topicsInfo = $resultTopicInfo->fetch_all(MYSQLI_ASSOC);
        
        $sqlBulkDelete = "DELETE FROM topics WHERE topic_id IN ($placeholders)";
        $stmtBulkDelete = $conn->prepare($sqlBulkDelete);
        $stmtBulkDelete->bind_param(str_repeat('i', count($selectedTopics)), ...$selectedTopics);
        
        if ($stmtBulkDelete->execute()) {
            // Log the bulk deletion
            $adminID = $_SESSION['id'];
            $action = "Bulk Deleted Topics";
            $sqlLogDeletion = "INSERT INTO adminlog (AdminID, Action, Details) VALUES (?, ?, ?)";
            $stmtLogDeletion = $conn->prepare($sqlLogDeletion);
            foreach ($topicsInfo as $topic) {
                $details = "Deleted topic with ID: {$topic['topic_id']}, Name: {$topic['topic_name']}";
                $stmtLogDeletion->bind_param("iss", $adminID, $action, $details);
                $stmtLogDeletion->execute();
            }
        }
    }
}

// Pagination function (same as user management)
function generatePagination($currentPage, $totalPages, $type) {
    $output = '<div class="pagination">';
    $output .= '<a href="?' . $type . '_page=1"' . ($currentPage == 1 ? ' class="active"' : '') . '>1</a>';
    $startPage = max(2, $currentPage - 1);
    $endPage = min($totalPages - 1, $currentPage + 1);
    if ($startPage > 2) {
        $output .= '<span class="ellipsis">...</span>';
    }
    for ($i = $startPage; $i <= $endPage; $i++) {
        $output .= '<a href="?' . $type . '_page=' . $i . '"' . ($i == $currentPage ? ' class="active"' : '') . '>' . $i . '</a>';
    }
    if ($endPage < $totalPages - 1) {
        $output .= '<span class="ellipsis">...</span>';
    }
    if ($totalPages > 1 && $endPage < $totalPages) {
        $output .= '<a href="?' . $type . '_page=' . $totalPages . '"' . ($currentPage == $totalPages ? ' class="active"' : '') . '>' . $totalPages . '</a>';
    }
    $output .= '</div>';
    return $output;
}


// Fetch topics with pagination
$topicsPerPage = 5;
$topicPage = isset($_GET['topic_page']) ? (int)$_GET['topic_page'] : 1;
$topicOffset = ($topicPage - 1) * $topicsPerPage;

$search = isset($_GET['search']) ? $_GET['search'] : '';
$sqlTopics = "SELECT t.topic_id, t.topic_name, t.topic_order, 
             (SELECT COUNT(*) FROM subtopics s WHERE s.topic_id = t.topic_id) as subtopic_count
             FROM topics t
             WHERE t.topic_name LIKE ?
             LIMIT ? OFFSET ?";
$searchParam = "%$search%";
$stmtTopics = $conn->prepare($sqlTopics);
$stmtTopics->bind_param("sii", $searchParam, $topicsPerPage, $topicOffset);
$stmtTopics->execute();
$resultTopics = $stmtTopics->get_result();
$topics = $resultTopics->fetch_all(MYSQLI_ASSOC);

$sqlTotalTopics = "SELECT COUNT(*) as total FROM topics 
                   WHERE topic_name LIKE ?";
$stmtTotalTopics = $conn->prepare($sqlTotalTopics);
$stmtTotalTopics->bind_param("s", $searchParam);
$stmtTotalTopics->execute();
$totalTopics = $stmtTotalTopics->get_result()->fetch_assoc()['total'];
$totalTopicPages = ceil($totalTopics / $topicsPerPage);

// Add this function to fetch subtopics
function fetchSubtopics($conn, $topicID) {
    $sqlSubtopics = "SELECT subtopic_id, subtopic_name, subtopic_order 
                     FROM subtopics 
                     WHERE topic_id = ? 
                     ORDER BY subtopic_order";
    $stmtSubtopics = $conn->prepare($sqlSubtopics);
    $stmtSubtopics->bind_param("i", $topicID);
    $stmtSubtopics->execute();
    $resultSubtopics = $stmtSubtopics->get_result();
    return $resultSubtopics->fetch_all(MYSQLI_ASSOC);
}

// Add an AJAX endpoint to get topic details
if (isset($_GET['get_topic_details']) && isset($_GET['topic_id'])) {
    $topicID = $_GET['topic_id'];
    
    // Fetch topic details
    $sqlTopicDetails = "SELECT topic_id, topic_name, topic_order FROM topics WHERE topic_id = ?";
    $stmtTopicDetails = $conn->prepare($sqlTopicDetails);
    $stmtTopicDetails->bind_param("i", $topicID);
    $stmtTopicDetails->execute();
    $resultTopicDetails = $stmtTopicDetails->get_result();
    $topicDetails = $resultTopicDetails->fetch_assoc();
    
    // Fetch subtopics
    $subtopics = fetchSubtopics($conn, $topicID);
    
    // Prepare response
    $response = [
        'topic' => $topicDetails,
        'subtopics' => $subtopics
    ];
    
    header('Content-Type: application/json');
    echo json_encode($response);
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Topic Management - Admin</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.1.1/css/all.min.css">
    <link rel="stylesheet" href="style.css">
    <style>
        .topics{
            width: 100%;
        }
        .topics-action{
            display: flex;
            padding: 30px;
            justify-content: space-evenly;
            align-items: center;
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
                    <a href="topics.php" class="dropbtn">Topics</a>
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
                <a class="active" href="admin.php">Admin</a>
            </div>
        </div>
    </div>
    <div class="admin-container">
        <div class="admin-nav">
            <a href="admin.php">
                <img src="img/admin-dashboard.png" alt="Dashboard Icon" class="nav-icon">
                <span>Admin Dashboard</span>
            </a>
            <a href="post-management.php">
                <img src="img/post-management.png" alt="Post Management Icon" class="nav-icon">
                <span>Post Management</span>
            </a>
            <a href="user-management.php">
                <img src="img/user-management.png" alt="User Management Icon" class="nav-icon">
                <span>User Management</span>
            </a>
            <a class="active" href="topics-management.php">
                <img src="img/user-management.png" alt="User Management Icon" class="nav-icon">
                <span>Topics Management</span>
            </a>
            <a href="violation-reports.php">
                <img src="img/violation-reports.png" alt="Violation Reports Icon" class="nav-icon">
                <span>Violation Reports</span>
            </a>
            <a href="add-rewards.php">
                <img src="img/rewards-management.png" alt="Rewards Icon" class="nav-icon">
                <span>Rewards</span>
            </a>
            <a href="admin-log.php">
                <img src="img/log-history.png" alt="Log History Icon" class="nav-icon">
                <span>Log History</span>
            </a>
            <a href="logout.php">
                <img src="img/logout.png" alt="Logout Icon" class="nav-icon">
                <span>Logout</span>
            </a>
        </div>
        <div class="admin-content">
            <div class="topics">
                <h2>Topics Management</h2>
                <div class="search-add-container">
                    <form action="" method="get" class="search-form">
                        <input type="text" name="search" placeholder="Search topics..." value="<?php echo htmlspecialchars($search); ?>">
                        <button type="submit">Search</button>
                    </form>
                    <button id="addTopicBtn" class="add-topic-btn">Add Topic</button>
                </div>
                <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="post" class="topics-table">
                    <div class="bulk-actions">
                        <input type="checkbox" id="select-all-topics">
                        <label for="select-all-topics" class="select-all-label">Select All</label>
                        <button type="submit" name="bulk_delete_topics" class="icon-button delete-icon" title="Delete Selected" onclick="return confirm('Are you sure you want to delete the selected topics?');">
                            <i class="fas fa-trash-alt"></i>
                        </button>
                    </div>
                    <table class="topic-table">
                        <thead>
                            <tr>
                                <th class="checkbox-column"></th>
                                <th>Topic Name</th>
                                <th>Topic Order</th>
                                <th>Subtopics Count</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($topics as $topic): ?>
                                <tr>
                                    <td class="checkbox-column">
                                        <input type="checkbox" name="selected_topics[]" value="<?php echo $topic['topic_id']; ?>" class="topic-checkbox">
                                    </td>
                                    <td><?php echo htmlspecialchars($topic['topic_name']); ?></td>
                                    <td><?php echo htmlspecialchars($topic['topic_order']); ?></td>
                                    <td><?php echo htmlspecialchars($topic['subtopic_count']); ?></td>
                                    <td class="topics-action">
                                        <a href="#" class="icon-button edit-icon edit-topic-btn" data-topic-id="<?php echo $topic['topic_id']; ?>" title="Edit Topic">
                                            <i class="fas fa-edit"></i>
                                        </a>
                                        <button type="submit" name="delete_topic" value="<?php echo $topic['topic_id']; ?>" class="icon-button delete-icon" title="Delete Topic" onclick="return confirm('Are you sure you want to delete this topic?');">
                                            <i class="fas fa-trash-alt"></i>
                                        </button>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </form>
                <?php echo generatePagination($topicPage, $totalTopicPages, 'topic'); ?>
            </div>
        </div>
    </div>
    <!-- Modal for Adding Topic -->
    <div id="addTopicModal" class="modal">
        <div class="modal-content">
            <span class="close">&times;</span>
            <h2>Add New Topic</h2>
            <form id="addTopicForm" action="add-topic.php" method="post" onsubmit="return validateForm()">
                <label for="topic_name">Topic Name:</label>
                <input type="text" id="topic_name" name="topic_name" required>
                
                <label for="topic_order">Topic Order:</label>
                <input type="number" id="topic_order" name="topic_order" required min="1">
                
                <input type="submit" value="Create Topic">
            </form>
        </div>
    </div>
    <div id="editTopicModal" class="modal">
        <div class="modal-content wide-modal">
            <span class="close">&times;</span>
            <div class="edit-topic-container">
                <div class="topic-details-section">
                    <h2>Edit Topic</h2>
                    <form id="editTopicForm">
                        <input type="hidden" id="edit_topic_id" name="topic_id">
                        
                        <label for="edit_topic_name">Topic Name:</label>
                        <input type="text" id="edit_topic_name" name="topic_name" required 
                            pattern="[A-Za-z0-9\s]+" 
                            title="Only letters, numbers, and spaces allowed">
                        
                        <label for="edit_topic_order">Topic Order:</label>
                        <input type="number" id="edit_topic_order" name="topic_order" required min="1">
                        
                        <button type="submit" class="btn-update">Update Topic</button>
                    </form>
                </div>
                
                <div class="subtopics-section">
                    <h2>Subtopics 
                        <button id="addSubtopicBtn" class="btn-add">+ Add Subtopic</button>
                    </h2>
                    <table id="subtopicsTable">
                        <thead>
                            <tr>
                                <th>Subtopic Name</th>
                                <th>Order</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody id="subtopicsList">
                            <!-- Subtopics will be dynamically populated here -->
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
    <div id="addSubtopicModal" class="modal">
        <div class="modal-content">
            <span class="close">&times;</span>
            <h2>Add Subtopic</h2>
            <form id="addSubtopicForm">
                <input type="hidden" id="add_subtopic_topic_id" name="topic_id">
                
                <label for="new_subtopic_name">Subtopic Name:</label>
                <input type="text" id="new_subtopic_name" name="subtopic_name" required 
                    pattern="[A-Za-z0-9\s]+" 
                    title="Only letters, numbers, and spaces allowed">
                
                <label for="new_subtopic_order">Subtopic Order:</label>
                <input type="number" id="new_subtopic_order" name="subtopic_order" required min="1">
                
                <button type="submit" class="btn-add">Add Subtopic</button>
            </form>
        </div>
    </div>
    <script>
        // JavaScript for "Select All" functionality
        document.getElementById('select-all-topics').addEventListener('change', function() {
            var checkboxes = document.getElementsByClassName('topic-checkbox');
            for (var checkbox of checkboxes) {
                checkbox.checked = this.checked;
            }
        });

        // Modal JavaScript
        var modal = document.getElementById("addTopicModal");
        var btn = document.getElementById("addTopicBtn");
        var span = document.getElementsByClassName("close")[0];

        btn.onclick = function() {
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

        function validateForm() {
            // Topic Name validation (letters, numbers, and spaces)
            const topicName = document.getElementById('topic_name');
            const topicNameRegex = /^[A-Za-z0-9\s]+$/;
            if (!topicNameRegex.test(topicName.value)) {
                alert('Topic Name must contain only letters, numbers, and spaces');
                topicName.focus();
                return false;
            }

            // Topic Order validation (positive numbers)
            const topicOrder = document.getElementById('topic_order');
            if (topicOrder.value < 1) {
                alert('Topic Order must be a positive number');
                topicOrder.focus();
                return false;
            }

            return true;
        }

        document.addEventListener('DOMContentLoaded', function() {
    const editTopicModal = document.getElementById('editTopicModal');
    const addSubtopicModal = document.getElementById('addSubtopicModal');
    
    // Create Edit Subtopic Modal with proper styling
    const editSubtopicModal = document.createElement('div');
    editSubtopicModal.id = 'editSubtopicModal';
    editSubtopicModal.className = 'modal';
    editSubtopicModal.innerHTML = `
        <div class="modal-content">
            <span class="close">&times;</span>
            <h2>Edit Subtopic</h2>
            <form id="editSubtopicForm">
                <input type="hidden" id="edit_subtopic_id" name="subtopic_id">
                <input type="hidden" id="edit_subtopic_topic_id" name="topic_id">
                
                <label for="edit_subtopic_name">Subtopic Name:</label>
                <input type="text" id="edit_subtopic_name" name="subtopic_name" required 
                    pattern="[A-Za-z0-9\s]+" 
                    title="Only letters, numbers, and spaces allowed">
                
                <label for="edit_subtopic_order">Subtopic Order:</label>
                <input type="number" id="edit_subtopic_order" name="subtopic_order" required min="1">
                
                <button type="submit" class="btn-update">Update Subtopic</button>
            </form>
        </div>
    `;
    document.body.appendChild(editSubtopicModal);

    const editTopicForm = document.getElementById('editTopicForm');
    const addSubtopicForm = document.getElementById('addSubtopicForm');
    const subtopicsList = document.getElementById('subtopicsList');
    const addSubtopicBtn = document.getElementById('addSubtopicBtn');
    const editSubtopicForm = document.getElementById('editSubtopicForm');

    // Subtopics List Event Delegation
    subtopicsList.addEventListener('click', function(e) {
        const row = e.target.closest('tr');
        if (!row) return;

        const subtopicId = row.getAttribute('data-subtopic-id');
        const topicId = document.getElementById('edit_topic_id').value;

        // Edit Subtopic
        if (e.target.classList.contains('edit-subtopic')) {
            const subtopicName = row.querySelector('td:first-child').textContent;
            const subtopicOrder = row.querySelector('td:nth-child(2)').textContent;

            // Populate edit subtopic modal
            document.getElementById('edit_subtopic_id').value = subtopicId;
            document.getElementById('edit_subtopic_topic_id').value = topicId;
            document.getElementById('edit_subtopic_name').value = subtopicName;
            document.getElementById('edit_subtopic_order').value = subtopicOrder;

            // Show edit subtopic modal
            editSubtopicModal.style.display = 'block';
        }

        // Delete Subtopic
        if (e.target.classList.contains('delete-subtopic')) {
            if (confirm('Are you sure you want to delete this subtopic?')) {
                fetch('delete-subtopic.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: `subtopic_id=${subtopicId}&topic_id=${topicId}`
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        alert(data.message);
                        row.remove(); // Remove the row from the table
                    } else {
                        alert('Error: ' + data.message);
                    }
                });
            }
        }
    });

    // Edit Subtopic Form Submit
    editSubtopicForm.addEventListener('submit', function(e) {
        e.preventDefault();
        const formData = new FormData(this);
        
        fetch('update-subtopic.php', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                alert(data.message);
                
                // Update the row in the table
                const subtopicId = document.getElementById('edit_subtopic_id').value;
                const row = document.querySelector(`tr[data-subtopic-id="${subtopicId}"]`);
                row.querySelector('td:first-child').textContent = formData.get('subtopic_name');
                row.querySelector('td:nth-child(2)').textContent = formData.get('subtopic_order');

                // Close the modal
                editSubtopicModal.style.display = 'none';
            } else {
                alert('Error: ' + data.message);
            }
        });
    });

    // Close Edit Subtopic Modal
    editSubtopicModal.querySelector('.close').addEventListener('click', function() {
        editSubtopicModal.style.display = 'none';
    });

    // Close modal when clicking outside
    window.addEventListener('click', function(event) {
        if (event.target == editSubtopicModal) {
            editSubtopicModal.style.display = 'none';
        }
    });
            // Edit Topic Modal Trigger
            document.querySelectorAll('.edit-topic-btn').forEach(btn => {
                btn.addEventListener('click', function(e) {
                    e.preventDefault();
                    const topicId = this.getAttribute('data-topic-id');
                    fetchTopicDetails(topicId);
                });
            });

            // Fetch Topic Details
            function fetchTopicDetails(topicId) {
                fetch(`topics-management.php?get_topic_details=1&topic_id=${topicId}`)
                    .then(response => response.json())
                    .then(data => {
                        // Populate topic details
                        document.getElementById('edit_topic_id').value = data.topic.topic_id;
                        document.getElementById('edit_topic_name').value = data.topic.topic_name;
                        document.getElementById('edit_topic_order').value = data.topic.topic_order;

                        // Populate subtopics
                        subtopicsList.innerHTML = data.subtopics.map(subtopic => `
                            <tr data-subtopic-id="${subtopic.subtopic_id}">
                                <td>${subtopic.subtopic_name}</td>
                                <td>${subtopic.subtopic_order}</td>
                                <td>
                                    <button class="edit-subtopic">Edit</button>
                                    <button class="delete-subtopic">Delete</button>
                                </td>
                            </tr>
                        `).join('');

                        // Show modal
                        editTopicModal.style.display = 'block';
                    });
            }

            // Close Modal Functions
            document.querySelectorAll('.close').forEach(closeBtn => {
                closeBtn.addEventListener('click', function() {
                    this.closest('.modal').style.display = 'none';
                });
            });

            // Add Subtopic Button
            addSubtopicBtn.addEventListener('click', function() {
                const topicId = document.getElementById('edit_topic_id').value;
                document.getElementById('add_subtopic_topic_id').value = topicId;
                addSubtopicModal.style.display = 'block';
            });

            // Update Topic Form Submit
            editTopicForm.addEventListener('submit', function(e) {
                e.preventDefault();
                const formData = new FormData(this);
                
                fetch('update-topic.php', {
                    method: 'POST',
                    body: formData
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        alert(data.message);
                        location.reload();
                    } else {
                        alert('Error: ' + data.message);
                    }
                });
            });

            // Add Subtopic Form Submit
            addSubtopicForm.addEventListener('submit', function(e) {
                e.preventDefault();
                const formData = new FormData(this);
                
                fetch('add-subtopic.php', {
                    method: 'POST',
                    body: formData
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        alert(data.message);
                        location.reload();
                    } else {
                        alert('Error: ' + data.message);
                    }
                });
            });
        });
    </script>
    <script src="js/transition.js"></script>
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
</body>
</html>