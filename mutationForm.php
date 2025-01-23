<?php
// Start session and check user authentication
session_start();
$host = "localhost";
        $port = "5432";
        $dbname = "klms";
        $user = "postgres";
        $password = "gredev";
if (!isset($_SESSION['user_id'])) {
    header("Location: dashboard.php");
    exit();
}

// Check if the request method is POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $mutation_type = $_POST['mutation_type'];

    if ($mutation_type === 'division') {
        // Handle land division logic here (if needed)
    } else if ($mutation_type == 'ownership_change') {
        // Retrieve form data
        $title_deed = $_POST['title_deed'];
        $current_owner = $_POST['current_owner'];
        $new_owner = $_POST['new_owner'];
        $surveyor_id = $_SESSION['user_id'];
        $status_id = 3; // Default status for "submitted"
        $date_submitted = date('Y-m-d'); // Format date as YYYY-MM-DD

        // Database connection details
        $host = "localhost";
        $port = "5432";
        $dbname = "klms";
        $user = "postgres";
        $password = "gredev";

        try {
            // Establish database connection
            $conn = new PDO("pgsql:host=$host;port=$port;dbname=$dbname", $user, $password);
            $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

            // Prepare the SQL query
            $stmt = $conn->prepare("INSERT INTO ownership_form (titledeed_no, current_owner_natid, proposed_owner_natid, surveyor_id, status_id, date_submitted) 
                                    VALUES (:titledeed_no, :current_owner_natid, :proposed_owner_natid, :surveyor_id, :status_id, :date_submitted)");

            // Bind parameters to the query
            $stmt->bindParam(':titledeed_no', $title_deed);
            $stmt->bindParam(':current_owner_natid', $current_owner);
            $stmt->bindParam(':proposed_owner_natid', $new_owner);
            $stmt->bindParam(':surveyor_id', $surveyor_id);
            $stmt->bindParam(':status_id', $status_id);
            $stmt->bindParam(':date_submitted', $date_submitted);

            // Execute the query and check for success
            if ($stmt->execute()) {
                echo '<script>
                        alert("Success! Ownership mutation details have been recorded successfully.");
                      </script>';
            } else {
                echo '<script>
                        alert("Error: Could not record the mutation details.");
                      </script>';
            }
        } catch (PDOException $e) {
            // Handle database connection or query errors
            die("Database error: " . $e->getMessage());
        }
    }
}
if (isset($_GET['titledeed'])) {
    $titleDeed = $_GET['titledeed'];

    try {
        // Establish database connection
        $conn = new PDO("pgsql:host=$host;port=$port;dbname=$dbname", $user, $password);
        $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        // Query for parcel coordinates
        $query = "SELECT coordinates FROM parcel WHERE titledeedno = :titledeed";
        $stmt = $conn->prepare($query);
        $stmt->bindParam(':titledeed', $titleDeed);
        $stmt->execute();
        $parcel_Coordinates = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($parcel_Coordinates) {
            // Return coordinates as JSON response
            echo json_encode($parcel_Coordinates['coordinates']);
        } else {
            echo json_encode(null);  // No coordinates found for the given title deed
        }
    } catch (PDOException $e) {
        // Handle database connection errors
        echo json_encode("Database error: " . $e->getMessage());
    }
}
?>



<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Ownership Mutation Form</title>
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
    <link rel="stylesheet" href="css/mutationForm.css">
</head>
<body>
    <div class="container">
    <div class="left-section">
        <h2>Mutation Form</h2>
        <form action="mutationForm.php" method="POST">
            <div class="form-group">
                <label for="title_deed">Title Deed:</label>
                <input type="text" id="title_deed" name="title_deed" placeholder="Enter Title Deed">
            </div>
            <div class="form-group">
                <label for="mutation_type">Mutation Type:</label>
                <select id="mutation_type" name="mutation_type">
                <option value="selectedValue">Selected Value</option>
                    <option value="division">Land Division</option>
                    <option value="ownership_change">Ownership Change</option>
                </select>
            </div>
            <div class="form-group" id="dynamicFields"></div>
            
            <!-- Additional fields for the form 
            <div class="form-group">
                <label for="details">Details:</label>
                <textarea id="details" name="details" rows="5" placeholder="Enter additional details..."></textarea>
            </div>
            -->
            <button class="submit-button">Submit</button>
        </form>
        
    </div>
    <script>
document.getElementById('title_deed').addEventListener('change', function(){
    const titleDeed = this.value;

    // Send AJAX request to fetch coordinates based on title deed
    const xhr = new XMLHttpRequest();
    xhr.open("GET", "fetch_coordinates.php?titledeed=" + titleDeed, true);
    xhr.onload = function() {
        if (xhr.status == 200) {
            // Parse the JSON response
            const parcelCoordinates = JSON.parse(xhr.responseText);

            if (parcelCoordinates) {
                // Initialize map and add parcel coordinates
                const map = L.map('map').setView([0, 0], 13);
                L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                    maxZoom: 19
                }).addTo(map);

                const geoJsonLayer = L.geoJSON(parcelCoordinates).addTo(map);
                map.fitBounds(geoJsonLayer.getBounds());
            }
        }
    }
    xhr.send();
});
</script>

    <script>
                document.getElementById('mutation_type').addEventListener('change',function(){
                    const selectedValue = this.value;
                    const dynamicFields = document.getElementById('dynamicFields');

                    dynamicFields.innerHTML = '';
                    if(selectedValue === 'division'){
                        const divisionField = document.createElement('div');
                        divisionField.className='form-group';
                        divisionField.innerHTML = `
                            <label for="number_of_divs">Number of Divisions:</label>
                            <input type="text" id="number_of_divs" name="number_of_divs" placeholder="Enter number of Divisions">

                            <label for="coordinates">Coordinates:</label>
                            <textarea id="coordinates" name="coordinates" rows="5" placeholder="Enter coordinates for the divisions..."></textarea>
                        `;
                        dynamicFields.appendChild(divisionField);
                    } else if(selectedValue === 'ownership_change'){
                        const ownerField = document.createElement('div');
                        ownerField.className = 'form-group';
                        ownerField.innerHTML = `
                            <label for="current_owner">Current Owner:</label>
                            <input type="text" id="current_owner" name="current_owner" placeholder="Enter current owner national ID" required>

                            <label for="new_owner">Proposed New Owner:</label>
                            <input type="text" id="new_owner" name="new_owner" placeholder="Enter new owner's National ID" required>
                        `;
                        dynamicFields.appendChild(ownerField);
                    }
                });
    </script>
    <div class="right-section">
        <div id="map"></div>
        
        
    </div>
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
    <script>
        // Initialize map
        const map = L.map('map').setView([0, 0], 13);

        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
            maxZoom: 19
        }).addTo(map);

        // Fetch parcel coordinates dynamically (replace with PHP if needed)
        const parcelCoordinates = {
            "type": "Feature",
            "properties": {},
            "geometry": {
                "type": "Polygon",
                "coordinates": [
                    [
                        [36.8218994140625, -1.2920769877174376],
                        [36.8218994140625, -1.2920769877174376],
                        [36.8218994140625, -1.2920769877174376],
                        [36.8218994140625, -1.2920769877174376],
                        [36.8218994140625, -1.2920769877174376]
                    ]
                ]
            }
        };

        const geoJsonLayer = L.geoJSON(parcelCoordinates).addTo(map);
        map.fitBounds(geoJsonLayer.getBounds());
    </script>
    
    </div>
    
    
    
    
</body>
</html>
