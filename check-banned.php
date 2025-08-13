<?php
if (isset($_SESSION['id']) && !empty($_SESSION['id'])) {
    $user_id = $_SESSION['id'];
    
    // Prepare SQL to check ban status
    $check_ban = $conn->prepare("SELECT isBan FROM Users WHERE UserID = ?");
    $check_ban->bind_param("i", $user_id);
    $check_ban->execute();
    $result = $check_ban->get_result();
    
    if ($result->num_rows > 0) {
        $user = $result->fetch_assoc();
        
        if ($user['isBan'] == 1) {
            
            // Get current page URL
            $current_page = basename($_SERVER['PHP_SELF']);
            
            // Only redirect if not already on banned.php
            if ($current_page !== 'banned.php') {
                header("Location: banned.php");
                exit;
            }
        }
    }
    $check_ban->close();
}
?>