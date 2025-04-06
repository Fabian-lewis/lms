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
    const startDateStr = "<?php echo $parcel['date_started']; ?>"; // Lease start date
    const durationMonths = <?php echo (int)$parcel['duration']; ?>; // Lease duration in months

    fetch('api/get_amounts.php', {
        method: 'GET',
        headers: {
            'Content-Type': 'application/json'
        },
    })
    .then(response => response.json())
    .then(rates => {
        console.log('Rates:', rates);

        // Convert startDateStr to a JavaScript Date object
        let startDate = new Date(startDateStr);
        if (isNaN(startDate)) {
            console.error("Invalid start date:", startDateStr);
            return;
        }

        // Correctly calculate the lease end date
        let endDate = new Date(startDate);
        endDate.setMonth(endDate.getMonth() + durationMonths);

        // Format the end date to show only YYYY-MM-DD
        let formattedEndDate = endDate.toISOString().split('T')[0];

        // Update the HTML span element with the end date
        document.getElementById("lease-end-date").innerText = formattedEndDate;

        // Calculate the total amount
        const totalAmount = calculateTotalAmount(startDateStr, durationMonths, rates);

        let total = 0;
        for (const [year, amount] of Object.entries(totalAmount)) {
            total += amount;
        }

        // Display the total amount in another span (optional)
        document.querySelector(".details-card").innerHTML += `<p><strong>Expected Rates Amount:</strong> ${total.toFixed(2)}</p>`;
    })
    .catch(error => {
        console.error('Error fetching data:', error);
    });
}


function calculate_payed_rates(titledeedno) {
    fetch('api/get_payed_rates.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json'
        },
        body: JSON.stringify({titledeedno:titledeedno})   
    })
    .then(response => response.json())
    .then(data => {
        console.log("API Response:", data);

        if (!data || !data.success) {
            console.error('Error fetching payments:', data.message);
            return;
        }

        // Ensure total_paid is a number and set default to 0
        const totalPaid = parseFloat(data.total_paid || 0).toFixed(2);


        // Select and update the payment details element
        const paymentDetails = document.querySelector('.payment-details');
        if (paymentDetails) {
            paymentDetails.innerHTML = `<p><strong>Payed Rates Amount: KSH </strong> ${totalPaid}</p>`;
        } else {
            console.error("❌ Element '.payment-details' not found in the DOM.");
        }
    })
    .catch(error => {
        console.error('❌ Error fetching data:', error);
    });
}


    </script>
    
</head>
<body style="background-color: #008080">
    <div class="container">
        <!-- Parcel Details -->
        <?php
            if ($parcel['landtype'] === 'leasehold') {
               echo '<script>';
               echo 'var parcel = ' . json_encode($parcel) . ';';
               echo 'leaseAmount();';
               echo 'calculate_payed_rates(parcel.titledeedno);';
               echo '</script>';
            }
            
        ?>
        
        <div id= "parcel-details"class="details-card">
            <div>
                <h1>Parcel Details</h1>
                <div>
                    <p><strong>Title Deed Number:</strong> <?php echo htmlspecialchars($parcel['titledeedno']); ?></p>
                    <p><strong>Date Created:</strong> <?php echo htmlspecialchars($parcel['datecreated']); ?></p>
                    <p><strong>Owner:</strong> <?php echo htmlspecialchars($parcel['owner_name']); ?></p>
                    <p><strong>Land Type:</strong> <?php echo htmlspecialchars($parcel['landtype']); ?></p>
                    <p><strong>Status:</strong> <?php echo htmlspecialchars($parcel['status']); ?></p>
                </div>

                <div>
                    <?php if ($parcel['landtype'] === 'leasehold') : ?>
                    <p><strong>Lease Duration:</strong> <?php echo htmlspecialchars($parcel['duration']); ?> months</p>
                    <p><strong>Ending Date:</strong> <span id="lease-end-date">Loading...</span></p>


                    
                    <?php endif; ?>
                </div>
                
                <div class="payment-details"></div>
            </div>
            
        </div>


        <!-- Map -->
        <div id="map"></div>
    </div>
    

    <script>
        const map = L.map('map').setView([0, 0], 13);

        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
            maxZoom: 13
        }).addTo(map);

        const parcelCoordinates = <?php echo $parcel['coordinates']; ?>;
        const geoJsonLayer = L.geoJSON(parcelCoordinates).addTo(map);

        map.fitBounds(geoJsonLayer.getBounds());

        // Add a marker at the center of the shape
        const center = geoJsonLayer.getBounds().getCenter();
        L.marker(center, {
        icon: L.icon({
            iconUrl: 'https://unpkg.com/leaflet@1.9.3/dist/images/marker-icon.png',
            iconSize: [25, 41],
            iconAnchor: [12, 41]
        })
        }).addTo(map).bindPopup("Parcel Center");
    </script>

    
</body>
</html>

