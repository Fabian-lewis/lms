<?php
session_start();
if(!isset($_SESSION['user_id'])){
    header('location:pay_rates.php');
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $phone = $_POST['number']; // User's phone number
    $amount = $_POST['amount']; // Payment amount
    $titleDeed = $_POST['titledeed']; // Title deed number

    // Format phone number correctly for Safaricom (2547XXXXXXXX)
    $phone = preg_replace('/^0/', '254', $phone);

    function getAccessToken() {
      $consumerKey = getenv('CONS_KEY');; //Fill with your app Consumer Key
      $consumerSecret = getenv('CONS_SEC');; // Fill with your app Secret

        $url = "https://sandbox.safaricom.co.ke/oauth/v1/generate?grant_type=client_credentials";
        
        $curl = curl_init();
        curl_setopt($curl, CURLOPT_URL, $url);
        curl_setopt($curl, CURLOPT_HTTPHEADER, ["Authorization: Basic " . base64_encode("$consumerKey:$consumerSecret")]);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        
        $response = curl_exec($curl);
        curl_close($curl);
        
        $json = json_decode($response);
        return $json->access_token ?? null;
    }

    function stkPush($phone, $amount, $titleDeed) {
        $accessToken = getAccessToken();
        
        $url = "https://sandbox.safaricom.co.ke/mpesa/stkpush/v1/processrequest";
        
        $shortCode = "174379";
        $passKey = 'bfb279f9aa9bdbcf158e97dd71a467cd2e0c893059b10f78e6b72ada1ed2c919';  
        $timestamp = date("YmdHis");
        $password = base64_encode($shortCode . $passKey . $timestamp);

        $data = [
            "BusinessShortCode" => $shortCode,
            "Password" => $password,
            "Timestamp" => $timestamp,
            "TransactionType" => "CustomerPayBillOnline",
            "Amount" => $amount,
            "PartyA" => $phone,
            "PartyB" => $shortCode,
            "PhoneNumber" => $phone,
            "CallBackURL" => 'https://lms-system-ufsc.onrender.com/callback_url.php',
            "AccountReference" => $titleDeed,
            "TransactionDesc" => "Land rate payment"
        ];

        // Modify  to Json
        $data = json_encode($data);
        
        $curl = curl_init();
        curl_setopt($curl, CURLOPT_URL, $url);
        curl_setopt($curl, CURLOPT_HTTPHEADER, [
            "Authorization: Bearer $accessToken",
            "Content-Type: application/json"
        ]);
        curl_setopt($curl, CURLOPT_POST, true);
        curl_setopt($curl, CURLOPT_POSTFIELDS, $data);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        
        $response = curl_exec($curl);
        curl_close($curl);
        
        return json_decode($response);
    }

    $response = stkPush($phone, $amount, $titleDeed);
    
    if ($response->ResponseCode == "0") {
        echo "<script>alert('Payment request sent successfully! Check your phone to complete payment.');</script>";
        echo "<script>window.location.href = 'success_page.php';</script>"; // Redirect to success page
    } else {
        echo "<script>alert('Payment failed. Please try again.');</script>";
        echo "<script>window.location.href = 'pay_rates.php';</script>"; // Redirect back to payment page
    }

// # access token
// $consumerKey = getenv('CONS_KEY');; //Fill with your app Consumer Key
// $consumerSecret = getenv('CONS_SEC');; // Fill with your app Secret
// $BusinessShortCode = '174379';
// $Passkey = 'bfb279f9aa9bdbcf158e97dd71a467cd2e0c893059b10f78e6b72ada1ed2c919';  
// # callback url
// $CallBackURL = 'https://lms-system-ufsc.onrender.com/callback_url.php';    
}
?>



 
  