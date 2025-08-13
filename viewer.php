<?php
session_start();

// Include database connection
require_once "config.php";
require_once "check-banned.php";

if (!isset($_GET['file']) || empty($_GET['file'])) {
    die("No file specified");
}

// Sanitize and validate the file parameter
$file = basename($_GET['file']);
$pdfPath = "output/" . $file;

if (!file_exists($pdfPath) || !is_file($pdfPath)) {
    die("File not found");
}

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reydy To Review - Presentation Viewer</title>
    <link rel="stylesheet" href="style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <script src="https://cdnjs.cloudflare.com/ajax/libs/pdf.js/2.11.338/pdf.min.js"></script>
    <script>
        // Configure PDF.js worker
        pdfjsLib.GlobalWorkerOptions.workerSrc = 'https://cdnjs.cloudflare.com/ajax/libs/pdf.js/2.11.338/pdf.worker.min.js';
    </script>
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
        <div class="category">
            <h2>Presentation Viewer</h2>
            <div class="subcategory viewer-content">
                <div class="viewer-layout">
                    <canvas id="pdf-viewer"></canvas>
                    <div class="controls">
                        <div class="nav-buttons">
                            <button onclick="prevPage()" title="Previous Page">
                                <i class="fas fa-chevron-left"></i>
                            </button>
                            <div class="page-info">
                                <span><span id="page_num"></span> / <span id="page_count"></span></span>
                            </div>
                            <button onclick="nextPage()" title="Next Page">
                                <i class="fas fa-chevron-right"></i>
                            </button>
                        </div>
                        <div class="download-section">
                            <a href="<?php echo $pdfPath; ?>" download class="download-btn" title="Download PDF">
                                <i class="fas fa-download"></i>
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

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

    <style>
        .viewer-content {
            padding: 20px;
        }
        .viewer-layout {
            display: flex;
            gap: 20px;
            align-items: flex-start;
            width: 100%;
            max-width: 1400px;
            margin: 0 auto;
            padding: 20px;
        }
        #pdf-viewer {
            flex: 1;
            width: 100%;
            max-width: 100%;
            height: auto;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
        }
        .controls {
            width: 200px;
            min-width: 200px;
            display: flex;
            flex-direction: column;
            gap: 20px;
            padding: 15px;
            background: #f5f5f5;
            border-radius: 5px;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
            position: sticky;
            top: 20px;
        }
        .nav-buttons {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 10px;
        }
        .controls button, .download-btn {
            width: 40px;
            height: 40px;
            cursor: pointer;
            border: none;
            border-radius: 50%;
            transition: all 0.2s;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .controls button {
            background: #007bff;
            color: white;
        }
        .download-btn {
            background: #28a745;
            color: white;
            text-decoration: none;
            margin: 0 auto;
        }
        .controls button:hover, .download-btn:hover {
            transform: scale(1.1);
        }
        .controls button:hover {
            background: #0056b3;
        }
        .download-btn:hover {
            background: #218838;
        }
        .controls button i, .download-btn i {
            font-size: 1.2em;
        }
        .page-info {
            text-align: center;
            font-weight: 500;
            font-size: 0.9em;
            min-width: 60px;
        }
        .download-section {
            text-align: center;
            padding-top: 15px;
            border-top: 1px solid #ddd;
        }

        /* Responsive styles */
        @media (max-width: 1024px) {
            .viewer-layout {
                flex-direction: column;
                align-items: center;
            }
            .controls {
                width: 100%;
                max-width: 300px;
                flex-direction: row;
                align-items: center;
                justify-content: center;
                gap: 30px;
                position: static;
                margin-top: 20px;
            }
            .download-section {
                padding-top: 0;
                border-top: none;
                border-left: 1px solid #ddd;
                padding-left: 20px;
            }
        }

        @media (max-width: 480px) {
            .controls {
                padding: 10px;
                gap: 15px;
            }
            .nav-buttons {
                gap: 8px;
            }
        }
    </style>

    <!-- Keep the existing JavaScript code -->
    <script src="js/transition.js"></script>
    <script>
        let currentPage = 1;
        let pdfDoc = null;
        let pageRendering = false;
        let pageNumPending = null;
        const scale = 1.5;
        const canvas = document.getElementById('pdf-viewer');
        const ctx = canvas.getContext('2d');

        pdfjsLib.GlobalWorkerOptions.workerSrc = 'https://cdnjs.cloudflare.com/ajax/libs/pdf.js/2.11.338/pdf.worker.min.js';

        function renderPage(num) {
            pageRendering = true;
            pdfDoc.getPage(num).then(function(page) {
                // Calculate scale based on container width
                const container = document.querySelector('.viewer-layout');
                const containerWidth = container.clientWidth - (window.innerWidth > 768 ? 120 : 20); // Account for controls and padding
                const viewport = page.getViewport({ scale: 1 });
                const scale = containerWidth / viewport.width;
                
                // Update viewport with new scale
                const scaledViewport = page.getViewport({ scale: scale });

                // Update canvas size
                canvas.height = scaledViewport.height;
                canvas.width = scaledViewport.width;

                const renderContext = {
                    canvasContext: ctx,
                    viewport: scaledViewport
                };

                page.render(renderContext).promise.then(function() {
                    pageRendering = false;
                    if (pageNumPending !== null) {
                        renderPage(pageNumPending);
                        pageNumPending = null;
                    }
                });

                document.getElementById('page_num').textContent = num;
            });
        }

        function queueRenderPage(num) {
            if (pageRendering) {
                pageNumPending = num;
            } else {
                renderPage(num);
            }
        }

        function prevPage() {
            if (currentPage <= 1) return;
            currentPage--;
            queueRenderPage(currentPage);
        }

        function nextPage() {
            if (currentPage >= pdfDoc.numPages) return;
            currentPage++;
            queueRenderPage(currentPage);
        }

        // Get PDF file from URL parameter
        const urlParams = new URLSearchParams(window.location.search);
        const pdfFile = urlParams.get('file');

        // Load the PDF
        pdfjsLib.getDocument('<?php echo $pdfPath; ?>').promise.then(function(pdf) {
            pdfDoc = pdf;
            document.getElementById('page_count').textContent = pdf.numPages;
            renderPage(currentPage);
        }).catch(function(error) {
            console.error("Error loading PDF:", error);
            alert("Error loading the presentation. Please try again later.");
        });

        // Add resize handler with debouncing
        let resizeTimeout;
        window.addEventListener('resize', function() {
            clearTimeout(resizeTimeout);
            resizeTimeout = setTimeout(function() {
                renderPage(currentPage);
            }, 250); // Debounce time of 250ms
        });
    </script>
</body>
</html>