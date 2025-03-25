<?php
    session_start();
    // if(!isset($_SESSION['user_id'])){
    //     header('location:parcelDetails.php');
    // }

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Rates Payment</title>
    <link rel="stylesheet" href="css/pay_rates.css">
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
            <h2>Rate Payment</h2>
        </div>

    </header>
    <main>
    <div class="pay-rates-container">
        <h3>Rate Payment Details</h3>
        <form action="stk_push.php" method="POST">
            <label for="fname">First Name:</label>
            <input type="text" id="fname" name="fname" required>

            <label for="sname">Surname:</label>
            <input type="text" id="sname" name="sname" required>

            <label for="number">Check Out Number:</label>
            <input type="tel" id="number" name="number" required>

            <label for="titledeed">Title Deed Number:</label>
            <input type="text" id="titledeed" name="titledeed" required>

            <label for="amount">Amount:</label>
            <input type="text" id="amount" name="amount" required>

            <label for="date">Date of Payment:</label>
            <input type="date" id="date" name="date" required>

            <button type="submit">Checkout</button>
        </form>
    </div>
    </main>
    

    
</body>
</html>
