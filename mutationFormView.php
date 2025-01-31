<?php
// Database connection
$host = "localhost";
$port = "5432";
$dbname = "klms";
//$user = "postgres";
$password = "gredev";
try {
    $conn = new PDO("pgsql:host=$host;port=$port;dbname=$dbname", 'postgres', $password);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("Connection failed: " . $e->getMessage());
}

// Get FormID ID from URL
$form_id = $_GET['form_id'];
$form_type = $_GET['form_type'];

if($form_type =="ownership"){
    $query1 = "SELECT
                o.id,
                o.titledeed_no,
                o.current_owner_natid,
                o.proposed_owner_natid,
                o.date_submitted,
                CONCAT(curr_owner.fname, ' ', curr_owner.sname) AS current_owner_name,
                CONCAT(prop_owner.fname, ' ', prop_owner.sname) AS proposed_owner_name,
                CONCAT(surveyor.fname, ' ', surveyor.sname) AS surveyor,
                o.surveyor_id,
                o.status_id,
                s.status
                FROM
                ownership_form o
                JOIN status s ON o.status_id = s.id
                JOIN users surveyor ON o.surveyor_id = surveyor.id
                JOIN users curr_owner ON o.current_owner_natid = curr_owner.nat_id
                JOIN users prop_owner ON o.proposed_owner_natid = prop_owner.nat_id
                WHERE
                o.id = :form_id";
    $stmt6 = $conn->prepare($query1);
    $stmt6->bindValue(':form_id', $form_id, PDO::PARAM_INT);
    $stmt6->execute();
    $submittedForm = $stmt6->fetchAll(PDO::FETCH_ASSOC);

}

if (!$form_id) {
    die("Parcel not found!");
}
?>


<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Parcel Details</title>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/leaflet/1.9.4/leaflet.js"></script>
    <link rel="stylesheet" href="css/parcelDetails.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/leaflet/1.9.4/leaflet.css" />
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.0/css/bootstrap.min.css">

    <script>
        function calculateTotalAmount(startDate, durationMonths, rates) {
        alert("Calculating total amount");
        const start = new Date(startDate); // Lease start date
        const totalAmount = {};

        for (let i = 0; i < durationMonths; i++) {
            const currentDate = new Date(start);
            currentDate.setMonth(start.getMonth() + i); // Move to the next month

            const year = currentDate.getFullYear(); // Get the current year
            const month = currentDate.getMonth() + 1; // Get the current month (1-12)

            if (!rates[year]) {
                console.error(`No rate found for year ${year}`);
                continue;
            }

            const monthlyRate = rates[year] / 12; // Calculate monthly rate for the year

            if (!totalAmount[year]) {
                totalAmount[year] = 0; // Initialize the year's total if it doesn't exist
            }

            totalAmount[year] += monthlyRate; // Add the monthly rate to the year's total
            //alert("Year: " + year + " Month: " + month + " Amount: " + monthlyRate);
        }

        return totalAmount;
    }

function leaseAmount() {
    const titledeed = "<?php echo $parcel['titledeedno']; ?>"; // Get the title deed number from PHP
    const startDate = "<?php echo $parcel['date_started']; ?>"; // Lease start date
    const durationMonths = <?php echo $parcel['duration']; ?>; // Lease duration in months

    fetch('get_amounts.php', {
        method: 'GET',
        headers: {
            'Content-Type': 'application/json'
        }
    })
    .then(response => response.json())
    .then(rates => {
        console.log('Rates:', rates);

        // Calculate the total amount
        const totalAmount = calculateTotalAmount(startDate, durationMonths, rates);

        // Display the result
        const leaseDetails = document.createElement('div');

        //leaseDetails.innerHTML = '<h3>Lease Payment Breakdown</h3>';

        let total = 0;
        for (const [year, amount] of Object.entries(totalAmount)) {
            //leaseDetails.innerHTML += `<p><strong>Year ${year}:</strong> ${amount.toFixed(2)}</p>`;
            total += amount;
        }

        end_date = new Date(startDate) + durationMonths;

        // Format the end date to show only the date (YYYY-MM-DD)
        //let formatted_end_date = end_date.toISOString().split('T')[0];

        leaseDetails.innerHTML = `<p><strong>End Date:</strong> ${end_date}</p>`;

        

        leaseDetails.innerHTML += `<p><strong>Expected Rates Amount:</strong> ${total.toFixed(2)}</p>`;
        leaseDetails.innerHTML += '<a href="pay_rates.php"><button>Pay Rates</button></a>';
        

        // Append the lease details to the details card
        document.querySelector('.details-card').appendChild(leaseDetails);
    })
    .catch(error => {
        console.error('Error fetching data:', error);
    });
}
    </script>
    
</head>
<body style="background-color: #2C3E50">
    <div class="container">
        <!-- Parcel Details -->
        
        <div id= "parcel-details"class="details-card">
            <h1>Parcel Details</h1>
            <p><strong>Date Submitted:</strong> <?php echo $submittedForm['date_submitted']; ?></p>
                        <p><strong>Surveyor:</strong> <?php echo $submittedForm['surveyor']; ?></p>
                        <p><strong>Current Owner:</strong> <?php echo $submittedForm['current_owner_name']; ?></p>
                        <p><strong>Proposed Owner:</strong> <?php echo $submittedForm['proposed_owner_name']; ?></p>
                        <p><strong>Status:</strong> <?php echo $submittedForm['status']; ?></p>
            
        </div>


        <!-- Map -->
        <div id="map"></div>
    </div>
    

    <script>
        const map = L.map('map').setView([0, 0], 13);

        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
            maxZoom: 19
        }).addTo(map);

        const parcelCoordinates = <?php echo $parcel['coordinates']; ?>;
        const geoJsonLayer = L.geoJSON(parcelCoordinates).addTo(map);

        map.fitBounds(geoJsonLayer.getBounds());
    </script>

    
</body>
</html>

