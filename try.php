<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>One Page Website</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 0;
            padding: 0;
            scroll-behavior: smooth;
        }
        header {
            background-color: #333;
            color: white;
            padding: 1rem;
            text-align: center;
            position: fixed;
            width: 100%;
            top: 0;
            z-index: 1000;
            transition: background-color 0.3s, padding 0.3s;
        }
        header.shrink {
            background-color: #222;
            padding: 0.5rem 1rem;
        }
        header nav a {
            color: white;
            margin: 0 1rem;
            text-decoration: none;
            transition: color 0.3s;
        }
        header nav a:hover {
            color: #ff6347; /* Tomato color for hover effect */
        }
        section {
            padding: 100px 20px 20px 20px;
            min-height: 100vh;
        }
        #home {
            background: #f4f4f4;
        }
        #about {
            background: #e2e2e2;
        }
        #recent-post {
            background: #cccccc;
        }
        footer {
            background-color: #333;
            color: white;
            text-align: center;
            padding: 1rem 0;
            transition: background-color 0.3s;
        }
    </style>
</head>
<body>
    <header>
        <nav>
            <a href="#home">Home</a>
            <a href="#about">About</a>
            <a href="#recent-post">Recent Post</a>
        </nav>
    </header>

    <section id="home">
        <h1>Welcome to Our Website</h1>
        <p>This is the home section of the website.</p>
    </section>

    <section id="about">
        <h1>About Us</h1>
        <p>This is the about section where you can learn more about us.</p>
    </section>

    <section id="recent-post">
        <h1>Recent Post</h1>
        <p>This is where you will find our most recent post.</p>
    </section>

    <footer>
        <p>&copy; 2024 One Page Website. All Rights Reserved.</p>
    </footer>

    <script>
        window.addEventListener('scroll', function() {
            const header = document.querySelector('header');
            if (window.scrollY > 50) {
                header.classList.add('shrink');
            } else {
                header.classList.remove('shrink');
            }
        });
    </script>
<script src="js/transition.js"></script>
</body>
</html>
