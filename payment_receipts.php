<?php

session_start(); // Start the session
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}


// Gett the user id from the session
$user_id = $_SESSION['user_id'];

// Database connection
require 'configs.php';


// Get the payments from the database
$stmt = $conn -> prepare ("SELECT * FROM rate_payment where user_id = :id ORDER BY datepayed DESC");
$stmt -> bindParam(':id', $user_id, PDO :: PARAM_INT);
$stmt -> execute();
$payments = $stmt -> fetchAll(PDO :: FETCH_ASSOC);

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Payment Receipts</title>
    <link rel="stylesheet" href="css/notification.css">
</head>
<body>
    <header>
        <div class="logo">
            <img src="images/lms_logo2.PNG" alt="LMS Logo">
        </div>
        <div class="head">
            <h2>Payment Receipts</h2>
        </div>
    </header>
    <main>
        <div class="card-wrapper">
                <?php foreach($payments as $payment): ?>
                    <div class="card">
                        <h3>DATE: <?= $payment['datepayed'];?></h3>
                        <p>TITLE DEED NO: <?= $payment['titledeed_no']; ?></p>
                        <p>AMOUNT: KSH <?= $payment['amount']; ?></p>
                        <button class="mark-read-btn">Generate Receipt</button>
                    </div>
                <?php endforeach; ?>
        </div>
    </main>
