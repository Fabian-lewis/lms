<?php
// Start session
session_start();

// Database connection
require 'configs.php';

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $fname = $_POST['fname'];
    $sname = $_POST['sname'];
    $password = $_POST['password'];

    if (isset($_POST['job_security_number'])) {
        // Professional user login
        try{
            $job_security_number = $_POST['job_security_number'];
            $stmt1 = $conn->prepare("SELECT usertype FROM user_type WHERE jobsecnumber = ?");
            $stmt1->execute([$job_security_number]);
            $user1 = $stmt1->fetch(PDO::FETCH_ASSOC);
        }catch (ERRMODE_EXCEPTION $e){
            die("Connection failed: " . $e->getMessage());
        }
        $stmt = $conn->prepare("SELECT * FROM users WHERE fname = ? AND sname = ? AND role = ?");
        $stmt->execute([$fname, $sname, $user1['usertype']]);

        
    } else {
        // General user login
        $stmt = $conn->prepare("SELECT * FROM users WHERE fname = ? AND sname = ? AND role = 'general_user'");
        $stmt->execute([$fname, $sname]);
    }

    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($user && password_verify($password, $user['password'])) {
        // Set session variables
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['role'] = $user['role'];
        header("Location: dashboard.php"); // Redirect to dashboard
        exit();
    } else {
        $error = "Invalid login credentials.";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login</title>
    <link rel="stylesheet" href="css/login.css">
</head>
<body>
    <header>
        <div class="logo">
            <img src="images/lms_logo2.PNG" alt="LMS Logo">
        </div>
        <div class="head">
            <h2>Login Page</h2>
        </div>
        <nav>
            <ul>
                <li><a href="index.php">Home</a></li>
                <li><a href="register.php">Register</a></li>
            </ul>
        </nav>
    </header>

    <main>
        <div class="container">
            <?php if (!empty($error)) { echo "<p class='error'>$error</p>"; } ?>

            <div class="forms">
                <!-- General User Login -->
                <div class="form-section">
                    <h3>General User Login</h3>
                    <form action="login.php" method="POST">
                        <label for="fname">First Name:</label>
                        <input type="text" id="fname" name="fname" required>

                        <label for="sname">Surname:</label>
                        <input type="text" id="sname" name="sname" required>

                        <label for="password">Password:</label>
                        <input type="password" id="password" name="password" required>

                        <button type="submit">Login</button>
                    </form>
                </div>

                <!-- Professional User Login -->
                <div class="form-section">
                    <h3>Professional User Login</h3>
                    <form action="login.php" method="POST">
                        <label for="fname">First Name:</label>
                        <input type="text" id="fname" name="fname" required>

                        <label for="sname">Surname:</label>
                        <input type="text" id="sname" name="sname" required>

                        <label for="password">Password:</label>
                        <input type="password" id="password" name="password" required>

                        <label for="job_security_number">Job Security Number:</label>
                        <input type="text" id="job_security_number" name="job_security_number" required>

                        <button type="submit">Login</button>
                    </form>
                </div>
            </div>
        </div>
    </main>
</body>
</html>
