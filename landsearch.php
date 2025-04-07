<?php
// Database connection
session_start();

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

require 'configs.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $userNatId = $_POST['userNatId'];
    $ownerNatId = $_POST['ownerNatId'];
    $titledeed = $_POST['titledeed'];

    // Basic validation
    if (empty($userNatId) || empty($ownerNatId) || empty($titledeed)) {
        die("All fields are required.");
    }

    try {
        // Start Transaction
        $conn->beginTransaction();

        // Check if the user exists
        $stmt = $conn->prepare("SELECT * FROM users WHERE nat_id = :nat_id AND id = :id");
        $stmt->bindParam(':nat_id', $userNatId);
        $stmt->bindParam(':id', $_SESSION['user_id']);
        $stmt->execute();
        $user = $stmt->fetch();

        if (!$user) {
            throw new Exception("Your user National ID does not match.");
        }

        // Check if the owner exists
        $stmt1 = $conn->prepare("SELECT id FROM users WHERE nat_id = :nat_id");
        $stmt1->bindParam(':nat_id', $ownerNatId);
        $stmt1->execute();
        $owner = $stmt1->fetch();

        if (!$owner) {
            throw new Exception("Owner not found.");
        }
        $owner_id = $owner['id'];

        // Check if title deed exists and fetch parcel ID
        $stmt2 = $conn->prepare("SELECT id FROM parcel WHERE titledeedno = :titledeed");
        $stmt2->bindParam(':titledeed', $titledeed);
        $stmt2->execute();
        $parcel = $stmt2->fetch();

        if (!$parcel) {
            throw new Exception("Parcel not found.");
        }
        $parcel_id = $parcel['id'];

        // Ensure the owner owns the parcel
        $stmt3 = $conn->prepare("SELECT * FROM ownership WHERE titledeed_no = :titledeed AND owner_id = :owner_id");
        $stmt3->bindParam(':titledeed', $titledeed);
        $stmt3->bindParam(':owner_id', $owner_id);
        $stmt3->execute();
        $ownership = $stmt3->fetch();

        if (!$ownership) {
            throw new Exception("Owner does not own the parcel.");
        }

        // Insert into landsearch table
        $stmt4 = $conn->prepare("INSERT INTO landsearch (user_natid, owner_natid, titledeed, date) VALUES (:userNatId, :ownerNatId, :titledeed, NOW())");
        $stmt4->bindParam(':userNatId', $userNatId);
        $stmt4->bindParam(':ownerNatId', $ownerNatId);
        $stmt4->bindParam(':titledeed', $titledeed);
        $stmt4->execute();

        // Send Notification to owner
        $message = "Your land has been searched by " . $user['fname'] . " " . $user['sname'] . ". Phone number: " . $user['phone'];
        $stmt5 = $conn->prepare("INSERT INTO notifications (sender_id, receiver_id, message, date, status_id) VALUES (11, :receiver_id, :message, NOW(), 9)");
        $stmt5->bindParam(':receiver_id', $owner_id);
        $stmt5->bindParam(':message', $message);
        $stmt5->execute();

        // Commit transaction if everything is successful
        $conn->commit();

        // Redirect to parcel details page
        header('Location: parcelDetails.php?parcel_id=' . urlencode($parcel_id));
        exit();

    } catch (Exception $e) {
        // Rollback if any error occurs
        $conn->rollBack();
        die("Transaction failed: " . $e->getMessage());
    }
}


?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register</title>
    <link rel="stylesheet" href="css/landsearch.css">
    <script>
                function showAlert(){
                    alert("Sucess User has been registered successfully");
                }
            </script>
</head>
<body>
    <header>
        <div class="logo">
            <img src="images/lms_logo2.PNG" alt="LMS Logo">
        </div>
        <div class="head">
            <h2>Land Search</h2>
            </div>

        <nav>
            <ul>
            <li><a href="dashboard.php">Dashboard</a></li> 
            </ul>
        
        </nav>

    </header>
    <main>
    <div class="registration-container">
        <h2>Land search</h2>
        <form action="landsearch.php" method="POST">
            <label for="userNatId">National ID:</label>
            <input type="text" id="userNatId" name="userNatId" required>

            <label for="ownerNatId">Land Owner's National ID:</label>
            <input type="text" id="ownerNatId" name="ownerNatId" required>

            <label for="titledeed">Title Deed Number:</label>
            <input type="text" id="titledeed" name="titledeed" required>


            <button type="submit">Search</button>
        </form>
    </div>
    </main>
    

    
</body>
</html>