<?php
    // This is your basic PHP template (optional, but can be used for future functionality like authentication)
    //echo "<!DOCTYPE html>";
?>

<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Land Management System</title>
    <link rel="stylesheet" href="css/styles.css">
   
</head>
<body>
    <header>
        <div class="logo">
            <img src="images/lms_logo2.PNG" alt="LMS Logo">
        </div>
        <nav>
            <ul>
                <li><a href="#">Home</a></li>
                <li><a href="#about">About</a></li>
                <li><a href="#services">Services</a></li>
                <li><a href="#">Contact</a></li>
                <li><a href="register.php">Register</a></li>
                <li><a href="login.php">Login</a></li>
            </ul>
        </nav>
    </header>

    <main>
        <section class="welcome">
            <h1>Helloo, <span class="typing"></span></h1>
            <p>Manage your land efficiently and easily with our LMS. You can search land parcels, make payments, and track land ownership.</p>
        </section>
        

    </main>

    <footer>
        <p>&copy; 2025 Land Management System. All rights reserved.</p>
    </footer>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/typed.js/2.0.10/typed.min.js"></script>
    <script>
    // Creating a typing effect
    var welcome = new Typed(".typing",{
        strings: ["Welcome, to the Land Management System"],
        typeSpeed: 100,
        loop: false
    })
</script>
</body>

</html>
