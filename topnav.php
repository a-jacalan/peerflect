<div class="topnav">
        <div class="logo">
            <a href="index.php"><img src="img/logo.png" alt="Logo"></a>
        </div>
        <div class="menu">
            <a href="index.php" onclick="transitionToPage('index.php'); return false;">Home</a>
            <a href="topics.php">Topics</a>
            <a href="#about">About</a>
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