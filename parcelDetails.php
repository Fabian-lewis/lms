<?php
// Database connection
require 'configs.php';

// Get parcel ID from URL
$parcel_id = $_GET['parcel_id'];

$query = "SELECT
                p.id,
                p.coordinates,
                p.datecreated,
                p.titledeedno,
                o.date_started,
                s.status,
                l.landtype,
                CONCAT(owner.fname, ' ', owner.sname) AS owner_name,
                CASE
                    WHEN l.landtype = 'leasehold' THEN lf.duration_months
                    WHEN l.landtype = 'freehold' THEN null
                    ELSE null
                END AS duration
            FROM
                parcel p
            JOIN ownership o ON p.titledeedno = o.titledeed_no
            JOIN status s ON o.status_id = s.id
            JOIN landtype l ON p.landtypeid = l.id
            JOIN users owner ON o.owner_id = owner.id
            LEFT JOIN lease_form lf ON p.titledeedno = lf.titledeed AND l.landtype = 'leasehold'
            WHERE
                p.id = :parcel_id";

$stmt = $conn->prepare($query);
$stmt->bindParam(':parcel_id', $parcel_id, PDO::PARAM_INT);
$stmt->execute();


$parcel = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$parcel) {
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
        <?php
            if ($parcel['landtype'] === 'leasehold') {
                echo '<script>leaseAmount();</script>';
                //echo '<script>alert("The details are being worked on")</script>';
            }
        ?>
        
        <div id= "parcel-details"class="details-card">
            <h1>Parcel Details</h1>
            <p><strong>Title Deed Number:</strong> <?php echo htmlspecialchars($parcel['titledeedno']); ?></p>
            <p><strong>Date Created:</strong> <?php echo htmlspecialchars($parcel['datecreated']); ?></p>
            <p><strong>Owner:</strong> <?php echo htmlspecialchars($parcel['owner_name']); ?></p>
            <p><strong>Land Type:</strong> <?php echo htmlspecialchars($parcel['landtype']); ?></p>
            <p><strong>Status:</strong> <?php echo htmlspecialchars($parcel['status']); ?></p>
            <?php if ($parcel['landtype'] === 'leasehold') : ?>
                <p><strong>Lease Duration:</strong> <?php echo htmlspecialchars($parcel['duration']); ?> months</p>
                <p><strong>Ending Date:</strong> <script>document.write(start+durationMonths);</script></p>
            <?php endif; ?>
            
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

