<?php
session_start();
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
            <a href="index.php">Home</a>
            <a class="active" href="branches.php">Branches</a>
            <a href="about.php">About</a>
            <div class="search-bar">
                <form action="search-results.php" method="GET">
                    <input type="text" name="q" placeholder="Search...">
                    <button type="submit">Search</button>
                </form>
            </div>
            <div class="menu-loginreg">
                <?php if(isset($_SESSION["loggedin"]) && $_SESSION["loggedin"] === true) { ?>
                    <?php if($_SESSION["username"] === "admin") { ?>
                        <a href="admin.php">Admin</a>
                    <?php } else { ?>
                        <div class="dropdown">
                            <a href="#" class="account-link">Account</a>
                            <div class="dropdown-content">
                                <a href="user-dashboard.php">Dashboard</a>
                                <a href="user-settings.php">Settings</a>
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
                    <a href="osi.php">OSI Model</a>
                </div>
                <div class="sub-box">
                    <a href="tcp-ip.php">TCP/IP Protocol Suite</a>
                </div>
                <div class="sub-box">
                    <a href="ethernet-lan-tech.php">Ethernet and LAN Technologies</a>
                </div>
                <div class="sub-box">
                    <a href="wan-tech.php">WAN Technologies</a>
                </div>
                <div class="sub-box">
                    <a href="ipv4-ipv6.php">IPv4 and IPv6 Addressing</a>
                </div>
                <div class="sub-box">
                    <a href="sub-sup-net.php">Subnetting and Supernetting</a>
                </div>
            </div>
        </div>
        <div class="category">
            <h2>Network Infrastracture</h2>
            <div class="subcategory">
                <div class="sub-box">
                    <a href="routers-protocol.php">Routers and Routing Protocols</a>
                </div>
                <div class="sub-box">
                    <a href="switches-vlan.php">Switches and VLANs</a>
                </div>
                <div class="sub-box">
                    <a href="access-points-wlan.php">Wireless Access Points and WLANs</a>
                </div>
                <div class="sub-box">
                    <a href="nat.php">Network Address Translation (NAT)</a>
                </div>
                <div class="sub-box">
                    <a href="qos.php">Quality of Service (QoS)</a>
                </div>
            </div>
        </div>
        <div class="category">
            <h2>Network Security</h2>
            <div class="subcategory">
                <div class="sub-box overflow">
                    <a href="ids.php">Firewalls and Intrusion Detection Systems (IDS)</a>
                </div>
                <div class="sub-box">
                    <a href="vpn.php">Virtual Private Networks (VPN)</a>
                </div>
                <div class="sub-box">
                    <a href="acl.php">Access Control Lists (ACLs)</a>
                </div>
                <div class="sub-box overflow">
                    <a href="ssh-ssl.php">Secure Shell (SSH) and Secure Sockets Layer (SSL)</a>
                </div>
                <div class="sub-box">
                    <a href="hardening-techniques.php">Network Hardening Techniques</a>
                </div>
            </div>
        </div>
    </div>
</body>
</html>