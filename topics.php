<?php
session_start();


// Include database connection
require_once "config.php";
require_once "check-banned.php";;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reydy To Review</title>
    <link rel="stylesheet" href="style.css">
</head>
<body class="topics-bg">
    <div class="topnav">
        <div class="logo">
            <a href="index.php"><img src="img/logo.png" alt="Logo"></a>
        </div>
        <div class="menu">
            <a href="index.php" onclick="transitionToPage('index.php'); return false;">Home</a>
            <a href="topics.php">Topics</a>
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
            <h2>Network Fundamentals</h2>
            <div class="subcategory">
                <div class="sub-box">
                    <a href="osi.php">
                        <span>OSI Model</span>
                    </a>
                </div>
                <div class="sub-box">
                    <a href="tcp-ip.php"><span>TCP/IP Protocol Suite</span></a>
                </div>
                <div class="sub-box">
                    <a href="ethernet-lan-tech.php"><span>Ethernet and LAN Technologies</span</a>
                </div>
                <div class="sub-box">
                    <a href="wan-tech.php"><span>WAN Technologies</span></a>
                </div>
                <div class="sub-box">
                    <a href="ipv4-ipv6.php"><span>IPv4 and IPv6 Addressing</span></a>
                </div>
                <div class="sub-box">
                    <a href="sub-sup-net.php"><span>Subnetting and Supernetting</span></a>
                </div>
            </div>
        </div>
        <div class="category">
            <h2>Network Infrastracture</h2>
            <div class="subcategory">
                <div class="sub-box">
                    <a href="routers-protocol.php"><span>Routers and Routing Protocols</span></a>
                </div>
                <div class="sub-box">
                    <a href="switches-vlan.php"><span>Switches and VLANs</span></a>
                </div>
                <div class="sub-box">
                    <a href="access-points-wlan.php"><span>Wireless Access Points and WLANs</span></a>
                </div>
                <div class="sub-box">
                    <a href="nat.php"><span>Network Address Translation (NAT)</span></a>
                </div>
                <div class="sub-box">
                    <a href="qos.php"><span>Quality of Service (QoS)</span></a>
                </div>
            </div>
        </div>
        <div class="category">
            <h2>Network Security</h2>
            <div class="subcategory">
                <div class="sub-box overflow">
                    <a href="ids.php"><span>Firewalls and Intrusion Detection Systems (IDS)</span></a>
                </div>
                <div class="sub-box">
                    <a href="vpn.php"><span>Virtual Private Networks (VPN)</span></a>
                </div>
                <div class="sub-box">
                    <a href="acl.php"><span>Access Control Lists (ACLs)</span></a>
                </div>
                <div class="sub-box overflow">
                    <a href="ssh-ssl.php"><span>Secure Shell (SSH) and Secure Sockets Layer (SSL)</span></a>
                </div>
                <div class="sub-box">
                    <a href="hardening-techniques.php"><span>Network Hardening Techniques</span></a>
                </div>
            </div>
        </div>
    </div>
    <script src="js/transition.js"></script>
<script src="js/transition.js"></script>
</body>
</html>