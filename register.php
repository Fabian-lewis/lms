<?php
// Database connection details
require 'configs.php';

// Check if the form is submitted
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $fname = $_POST['fname'];
    $sname = $_POST['sname'];
    $role = $_POST['role'];
    $phone = $_POST['phone'];
    $email = $_POST['email'];
    $password = $_POST['password'];
    $nat_id = $_POST['nat_id'];
    $confirm_password = $_POST['confirm_password'];

    // Basic validation
    if (empty($fname) || empty($sname) || empty($role) || empty($password) || empty($confirm_password) || empty($nat_id) || empty($phone) || empty($email)) {
        die("All fields are required.");
    }

    if ($password !== $confirm_password) {
        die("Passwords do not match.");
    }

    // Hash the password
    $hashed_password = password_hash($password, PASSWORD_DEFAULT);

    // Insert data into the users table
    try {
        $stmt = $conn->prepare("INSERT INTO users (fname, sname, role, password, nat_id, email, phone) VALUES (:fname, :sname, :role, :password, :nat_id, :email, :phone)");
        $stmt->bindParam(':fname', $fname);
        $stmt->bindParam(':sname', $sname);
        $stmt->bindParam(':role', $role);
        $stmt->bindParam(':password', $hashed_password);
        $stmt->bindParam(':nat_id', $nat_id);
        $stmt->bindParam(':email', $email);
        $stmt->bindParam(':phone', $phone);
        
        if ($stmt->execute()) {
            echo '<script>
                    alert("Sucess User has been registered successfully");
                </script>';
        } else {
            echo "Error: Could not register the user.";
        }
        header("Location: login.php"); // Redirect to login page after successful registration
        exit();
    } catch (PDOException $e) {
        echo "Error: " . $e->getMessage();
    }
}
?>



<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register</title>
    <link rel="stylesheet" href="css/register.css">
    <script>
        function showAlert() {
            alert("Success! User has been registered successfully.");
        }
    </script>
</head>
<body>
    <header>
        <div class="logo">
            <img src="images/lms_logo2.PNG" alt="LMS Logo">
        </div>
        <div class="head">
            <h2>Registration</h2>
        </div>
    </header>
    <main>
        <div class="registration-container">
            <h2>Registration Details</h2>
            <form action="register.php" method="POST" onsubmit="showAlert()">
                <label for="fname">First Name:</label>
                <input type="text" id="fname" name="fname" required>

                <label for="sname">Surname:</label>
                <input type="text" id="sname" name="sname" required>

                <label for="nat_id">National ID:</label>
                <input type="text" id="nat_id" name="nat_id" required>

                <label for="phone">Phone:</label>
                <input type="text" id="phone" name="phone" required>

                <label for="email">Email:</label>
                <input type="text" id="email" name="email" required>

                <label for="role">Role:</label>
                <select id="role" name="role" required>
                    <option value="">--Select Role--</option>
                    <option value="general_user">General User</option>
                    <option value="surveyor">Surveyor</option>
                    <option value="ministry_official">Ministry Official</option>
                </select>

                <label for="password">Password:</label>
                <input type="password" id="password" name="password" required>

                <label for="confirm_password">Confirm Password:</label>
                <input type="password" id="confirm_password" name="confirm_password" required>

                <button type="submit">Register</button>
            </form>
        </div>
    </main>
</body>
</html>