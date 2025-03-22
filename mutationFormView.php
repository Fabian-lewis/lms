<?php

session_start();

if (!isset($_SESSION['user_id'])) {
    header("Location: dashboard.php");
    exit();
}

// Database connection
require 'configs.php';

// Get FormID and FormType from URL
$form_id = $_GET['form_id'] ?? null;
$form_type = $_GET['form_type'] ?? null;

if (!$form_id || !$form_type) {
    die("Form ID or Form Type not provided!");
}

if ($form_type === "ownership") {
    $query1 = "SELECT
                o.id,
                o.titledeed_no,
                p.coordinates,
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
                JOIN parcel p ON o.titledeed_no = p.titledeedno
                JOIN users surveyor ON o.surveyor_id = surveyor.id
                JOIN users curr_owner ON o.current_owner_natid = curr_owner.nat_id
                JOIN users prop_owner ON o.proposed_owner_natid = prop_owner.nat_id
                WHERE
                o.id = :form_id";
    $stmt6 = $conn->prepare($query1);
    $stmt6->bindValue(':form_id', $form_id, PDO::PARAM_INT);
    $stmt6->execute();
    $submittedForm = $stmt6->fetch(PDO::FETCH_ASSOC);

} elseif ($form_type === "division") {
    $query1 = "SELECT
                d.id,
                d.titledeed,
                d.divisions_coordinates,
                d.number_of_divs,
                p.coordinates,
                d.date_submitted,
                CONCAT(surveyor.fname, ' ', surveyor.sname) AS surveyor,
                d.surveyor_id,
                d.status_id,
                s.status
                FROM
                division_form d
                JOIN status s ON d.status_id = s.id
                JOIN parcel p ON d.titledeed = p.titledeedno
                JOIN users surveyor ON d.surveyor_id = surveyor.id
                WHERE
                d.id = :form_id";
    $stmt6 = $conn->prepare($query1);
    $stmt6->bindValue(':form_id', $form_id, PDO::PARAM_INT);
    $stmt6->execute();
    $submittedForm = $stmt6->fetch(PDO::FETCH_ASSOC);
}

if (empty($submittedForm)) {
    die("Form not found!");
}

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Parcel Details</title>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/leaflet/1.9.4/leaflet.js"></script>
    <link rel="stylesheet" href="css/mutationFormView.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/leaflet/1.9.4/leaflet.css" />
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.0/css/bootstrap.min.css">
</head>
<body style="background-color: #2C3E50">
    <header></header>
    <main>
        <?php if ($form_type == "ownership" && !empty($submittedForm)): ?>
            <div class="header">
                <h1>OWNERSHIP MUTATION FORM</h1>
            </div>
            <div class="body">
                <div class="container">
                    <!-- Parcel Details -->
                    <div id="parcel-details" class="details-card">
                        <div class="deep-details">
                            <p><strong>Form ID:</strong> <?php echo htmlspecialchars($submittedForm['id']); ?></p>
                            <p><strong>Date Submitted:</strong> <?php echo htmlspecialchars($submittedForm['date_submitted']); ?></p>
                            <p><strong>Surveyor:</strong> <?php echo htmlspecialchars($submittedForm['surveyor']); ?></p>
                            <p><strong>Title Deed No:</strong> <?php echo htmlspecialchars($submittedForm['titledeed_no']); ?></p>
                            <p><strong>Status:</strong> <?php echo htmlspecialchars($submittedForm['status']); ?></p>
                        </div>
                        <div class="deep-details">
                            <p><strong>Current Owner:</strong> <?php echo htmlspecialchars($submittedForm['current_owner_name']); ?></p>
                            <p><strong>Current Owner ID:</strong> <?php echo htmlspecialchars($submittedForm['current_owner_natid']); ?></p>
                        </div>
                        <div class="deep-details">
                            <p><strong>Proposed Owner:</strong> <?php echo htmlspecialchars($submittedForm['proposed_owner_name']); ?></p>
                            <p><strong>Proposed Owner ID:</strong> <?php echo htmlspecialchars($submittedForm['proposed_owner_natid']); ?></p>
                        </div>
                    </div>
                    <div>
                        <button onclick="acceptMutation()">Approve</button>
                        <button onclick="rejectMutation()">Reject</button>
                    </div>
                </div>
                <!-- Map -->
                <div id="map"></div>
            </div>
            <script>
                const map = L.map('map').setView([0, 0], 13); // Note: Latitude comes before longitude

                L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                    maxZoom: 19,
                }).addTo(map);

                const parcelCoordinates = <?php echo $submittedForm['coordinates']; ?>;
                const geoJsonLayer = L.geoJSON(parcelCoordinates).addTo(map);
                map.fitBounds(geoJsonLayer.getBounds());
            </script>
            <script>
                function acceptMutation() {
                    if (confirm("Are you sure you want to approve this mutation?")) {
                        fetch('approveMutation.php', {
                            method: 'POST',
                            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                            body: new URLSearchParams({
                                form_id: "<?php echo $submittedForm['id']; ?>",
                                current_owner_natid: "<?php echo $submittedForm['current_owner_natid']; ?>",
                                proposed_owner_natid: "<?php echo $submittedForm['proposed_owner_natid']; ?>",
                                titledeed_no: "<?php echo $submittedForm['titledeed_no']; ?>"
                            })
                        })
                        .then(response => response.text())
                        .then(data => alert(data)) // Show response from PHP
                        .catch(error => console.error('Error:', error));
                    }
                }

                function rejectMutation() {
                    if (confirm("Are you sure you want to reject this mutation?")) {
                        fetch('rejectMutation.php', {
                            method: 'POST',
                            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                            body: new URLSearchParams({ 
                                form_id: "<?php echo $submittedForm['id']; ?>",
                                form_type: "ownership"})
                        })
                        .then(response => response.text()) 
                        .then(data => {
                            if (data.trim() === "success") {
                                $_SESSION['role'] = "ministry_official";
                                header("Location: dashboard.php"); // Redirect upon success
                            //window.location.href = "dashboard.php"; // Redirect upon success
                        } else {
                            alert(data); // Show error message from PHP
                        }
                     })
                        .catch(error => console.error('Error:', error));
                    }
                }
            </script>

        <?php elseif ($form_type == "division" && !empty($submittedForm)): ?>
    <div class="header">
        <h1>DIVISION MUTATION FORM</h1>
    </div>
    <div class="body">
        <div class="container">
            <!-- Parcel Details -->
            <div id="parcel-details" class="details-card">
                <div class="deep-details">
                    <p><strong>Form ID:</strong> <?php echo htmlspecialchars($submittedForm['id']); ?></p>
                    <p><strong>Date Submitted:</strong> <?php echo htmlspecialchars($submittedForm['date_submitted']); ?></p>
                    <p><strong>Surveyor:</strong> <?php echo htmlspecialchars($submittedForm['surveyor']); ?></p>
                    <p><strong>Title Deed No:</strong> <?php echo htmlspecialchars($submittedForm['titledeed']); ?></p>
                    <p><strong>Title Deed No:</strong> <?php echo htmlspecialchars($submittedForm['coordinates']); ?></p>
                    <p><strong>Number of Divisions:</strong> <?php echo htmlspecialchars($submittedForm['number_of_divs']); ?></p>
                    <p><strong>Status:</strong> <?php echo htmlspecialchars($submittedForm['status']); ?></p>
                </div>

                <!-- Extract and Display Division Coordinates -->
                <div class="deep-details">
                    <h2>Division Coordinates</h2>
                    <?php
                    $divisions = json_decode($submittedForm['divisions_coordinates'], true);

                    if (!empty($divisions)) {
                        foreach ($divisions as $index => $division) {
                            $coordinates = $division["geometry"]["coordinates"][0]; // Extract coordinates
                            echo "<h3>Coordinates for Division " . ($index + 1) . ":</h3>";
                            echo "<ul>";
                            foreach ($coordinates as $coord) {
                                echo "<li>Lat: " . htmlspecialchars($coord[1]) . ", Lng: " . htmlspecialchars($coord[0]) . "</li>";
                            }
                            echo "</ul>";
                        }
                    } else {
                        echo "<p>No division coordinates available.</p>";
                    }
                    ?>
                </div>
            </div>

            <div>
                <a href="approveDivForm.php?form_id=<?php echo $submittedForm['id'];?>"><button onclick="acceptMutation()">Approve</button></a>
               
                <button onclick="rejectDMutation()">Reject</button></a>
                
            </div>
            <script>
                function rejectDMutation() {
                    if (confirm("Are you sure you want to reject this mutation?")) {
                        fetch('rejectMutation.php', {
                            method: 'POST',
                            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                            body: new URLSearchParams({ 
                                form_id: "<?php echo $submittedForm['id']; ?>",
                                form_type: "division"})
                        })
                        .then(data => {
                            if (data.status === "success") {
                                $_SESSION['role'] = "ministry_official";
                                $_SESSION['user_id'] = $_SESSION['user_id'];
                                alert(data.message);
                                header("Location: dashboard.php"); // Redirect upon success
                            //window.location.href = "dashboard.php"; // Redirect upon success
                        } else {
                            alert("Error: " + data.message); // Show error message from PHP
                        }
                     })
                        .catch(error => console.error('Error:', error));
                    }
                }
            </script>
        </div>
        <!-- Map -->
         <!--
        <div id="map"></div>
        <script>
                const map = L.map('map').setView([0, 0], 13); // Note: Latitude comes before longitude

                L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                    maxZoom: 19,
                }).addTo(map);

                const divisionsCoordinates = <?php// echo $submittedForm['divisions_coordinates']; ?>;
                const parcelCoordinates = <?php //echo $submittedForm['coordinates']; ?>;

                // Function to style divisions with a unique color
function styleDivisions(feature) {
    return {
        color: 'blue', // Border color
        weight: 2,     // Line thickness
        fillColor: 'lightblue', // Fill color
        fillOpacity: 0.4 // Transparency
    };
}

// Function to style parcel with a unique color
function styleParcel(feature) {
    return {
        color: 'green', // Border color
        weight: 2,      // Line thickness
        fillColor: 'lightgreen', // Fill color
        fillOpacity: 0.4 // Transparency
    };
}

// Add GeoJSON layers with different colors
const divisionsLayer = L.geoJSON(divisionsCoordinates, { style: styleDivisions }).addTo(map);
const parcelLayer = L.geoJSON(parcelCoordinates, { style: styleParcel }).addTo(map);

                // Fit map to bounds of both layers
                const allBounds = L.featureGroup([divisionsLayer, parcelLayer]).getBounds();
                map.fitBounds(geoJsonLayer.allBounds());
            </script>
    </div>
                -->
<?php endif; ?>

    </main>
</body>
</html>