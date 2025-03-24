<?php
// Database connection
require 'configs.php';

// Check if the form is submitted

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $userNatId = $_POST['userNatId'];
    $ownerNatId = $_POST['ownerNatId'];
    $titledeed = $_POST['titledeed'];

    // Basic validation
    if (empty($userNatId) || empty($ownerNatId) || empty($titledeed)) {
        die("All fields are required.");
    }

    // check if the user exists
    $stmt = $conn->prepare("SELECT * FROM users WHERE nat_id = :nat_id");
    $stmt->bindParam(':nat_id', $userNatId);
    $stmt->execute();
    $user = $stmt->fetch();

     // check if the owner exists
     $stmt1 = $conn->prepare("SELECT * FROM users WHERE nat_id = :nat_id");
     $stmt1->bindParam(':nat_id', $ownerNatId);
     $stmt1->execute();
     $owner = $stmt1->fetch();

    // check if title deed exists. Fetch the parcel id
    $stmt2 = $conn->prepare("SELECT id FROM parcel WHERE titledeedno = :titledeed");
    $stmt2->bindParam(':titledeed', $titledeed);
    $stmt2->execute();
    $parcel_id = $stmt2->fetch();

    if (!$user || !$owner || !$parcel_id) {
        die("User or owner or parcel does not exist.");
    }else{

        // Insert data into the users table
    try {
        $stmt = $conn->prepare("INSERT INTO landsearch (user_natId, owner_natId, titledeed, date) VALUES (:userNatId, :ownerNatId, :titledeed, NOW())");
        $stmt->bindParam(':userNatId', $userNatId);
        $stmt->bindParam(':ownerNatId', $ownerNatId);
        $stmt->bindParam(':titledeed', $titledeed);
        
        if ($stmt->execute()) {
            echo '<script>
                    alert("Sucess Land search has been registered successfully");
                </script>';
            
        } else {
            echo "Error: Could not register the Land search.";
        }
    } catch (PDOException $e) {
        echo "Error: " . $e->getMessage();
    }
    // Get user ID
    $stmt = $conn->prepare("SELECT * FROM users WHERE nat_id = :nat_id");
    $stmt->bindParam(':nat_id', $userNatId);
    $stmt->execute();
    $user = $stmt->fetch();

   // Get owner id
    $stmt1 = $conn->prepare("SELECT id FROM users WHERE nat_id = :nat_id");
    $stmt1->bindParam(':nat_id', $ownerNatId);
    $stmt1->execute();
    $owner = $stmt1->fetch();
    $owner_id = $owner['id'];

     // Send Notification to owner
        $stmt3 = $conn->prepare("INSERT INTO notifications (receiver_id, message, date, status_id) VALUES (:receiver_id, :message, NOW(), 9)");
        $stmt3->bindParam(':receiver_id', $owner_id);
        $stmt3->bindParam(':message', 'Your land has been searched by '.$user['fname'].' '.$user['sname']. 'Phone number: '.$user['phone']);
        $stmt3->execute();


        // Redirect to parcelDetails.php use the titledeed to get the parcel details
        header('location:parcelDetails.php?titledeed='.$parcel_id);
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
                function showAlert(){
                    alert("Sucess User has been registered successfully");
                }
            </script>
</head>
<body>
    <header>
    <div class="logo">
            <img src="images/lms-logo2.png" alt="LMS Logo">
        </div>
        <div class="head">
            <h2>Land Search</h2>
        </div>

    </header>
    <main>
    <div class="registration-container">
        <h2>Land search</h2>
        <form action="landsearch.php" method="POST">
            <label for="userNatId">National ID:</label>
            <input type="text" id="userNatId" name="userNatId" required>

            <label for="ownerNatId">Land Owner's National ID:</label>
            <input type="text" id="ownerNatId" name="ownerNatId" required>

            <label for="titledeed">National ID:</label>
            <input type="text" id="titledeed" name="titledeed" required>


            <button type="submit">Search</button>
        </form>
    </div>
    </main>
    

    
</body>
</html>