<?php
// Start session
session_start();

// Database connection
$host = "localhost";
$port = "5432";
$dbname = "klms";
$user = "postgres";
$password = "gredev";

try {
    $conn = new PDO("pgsql:host=$host;port=$port;dbname=$dbname", $user, $password);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("Connection failed: " . $e->getMessage());
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $fname = $_POST['fname'];
    $sname = $_POST['sname'];
    $password = $_POST['password'];

    if (isset($_POST['job_security_number'])) {
        // Professional user login
        $job_security_number = $_POST['job_security_number'];
        $stmt = $conn->prepare("SELECT * FROM users WHERE fname = ? AND sname = ? AND job_security_number = ?");
        $stmt->execute([$fname, $sname, $job_security_number]);
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
        exit;
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
    <div class="container">
        <h2>Login Page</h2>

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
</body>
</html>
