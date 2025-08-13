<?php
session_start();
require_once "config.php";
require_once "check-banned.php";;

// If user is not logged in or not banned, redirect to home
if (!isset($_SESSION['id'])) {
    header("Location: index.php");
    exit;
}

// Get ban details
$banReason = $_SESSION['banReason'] ?? 'Violation of community guidelines';
$banExpiry = $_SESSION['banExpiry'] ?? null;
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Account Suspended - PeerFlect</title>
    <link rel="stylesheet" href="style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <style>
        .banned-container {
            max-width: 600px;
            margin: 50px auto;
            padding: 30px;
            background-color: white;
            border-radius: 10px;
            box-shadow: 0 0 20px rgba(0, 0, 0, 0.1);
            text-align: center;
        }

        .banned-icon {
            font-size: 64px;
            color: #ff4444;
            margin-bottom: 20px;
        }

        .banned-title {
            color: #ff4444;
            margin-bottom: 20px;
            font-size: 24px;
        }

        .banned-message {
            margin-bottom: 30px;
            line-height: 1.6;
            color: #666;
        }

        .banned-details {
            background-color: #f8f9fa;
            padding: 20px;
            border-radius: 8px;
            margin-bottom: 30px;
            text-align: left;
        }

        .banned-details h3 {
            color: #333;
            margin-bottom: 10px;
        }

        .banned-details p {
            margin: 5px 0;
            color: #666;
        }

        .contact-support {
            background-color: #007bff;
            color: white;
            padding: 12px 25px;
            border-radius: 5px;
            text-decoration: none;
            display: inline-block;
            transition: background-color 0.3s;
        }

        .contact-support:hover {
            background-color: #0056b3;
        }

        .logout-link {
            display: block;
            margin-top: 20px;
            color: #666;
            text-decoration: none;
        }

        .logout-link:hover {
            color: #333;
        }
    </style>
</head>
<body class="home-bg">
    <div class="banned-container">
        <div class="banned-icon">
            <i class="fas fa-ban"></i>
        </div>
        <h1 class="banned-title">Account Suspended</h1>
        <div class="banned-message">
            <p>We regret to inform you that your account has been suspended from PeerFlect.</p>
        </div>
        
        <div class="banned-details">
            <h3>Suspension Details</h3>
            <p><strong>Reason:</strong> <?php echo htmlspecialchars($banReason); ?></p>
            <?php if ($banExpiry): ?>
                <p><strong>Duration:</strong> Until <?php echo htmlspecialchars(date('F j, Y', strtotime($banExpiry))); ?></p>
            <?php else: ?>
                <p><strong>Duration:</strong> Indefinite</p>
            <?php endif; ?>
        </div>

        <p>If you believe this is a mistake or would like to appeal this decision, please contact our support team.</p>
        
        <a href="mailto:support@peerflect.com" class="contact-support">
            <i class="fas fa-envelope"></i> Contact Support
        </a>
        
        <a href="logout.php" class="logout-link">
            <i class="fas fa-sign-out-alt"></i> Logout
        </a>
    </div>

    <script>
        // Disable back button
        history.pushState(null, null, document.URL);
        window.addEventListener('popstate', function () {
            history.pushState(null, null, document.URL);
        });
    </script>
</body>
</html>