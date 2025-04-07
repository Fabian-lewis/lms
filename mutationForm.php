<?php
// Start session and check user authentication
session_start();

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

require_once('auth/check_role.php');
requireRole(['surveyor']);

 // Database connection details
 require 'configs.php';


// Check if the request method is POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $mutation_type = $_POST['mutation_type'];

    if ($mutation_type === 'division') {
        // Handle land division logic here (if needed)
        $title_deed = $_POST['title_deed'];
        $number_of_divs = (int)$_POST['number_of_divs'];
        $division_coordinates = $_POST['divisionCoordinates'];
        $surveyor_id = $_SESSION['user_id'];
        $status_id = 3; // Default status for "submitted"
        $date_submitted = date('Y-m-d'); // Format date as YYYY-MM-DD

       

        try{

            // Prepare the SQL query
            $stmt = $conn->prepare("INSERT INTO division_form (titledeed, number_of_divs, divisions_coordinates, surveyor_id, status_id, date_submitted) 
                                    VALUES (:titledeed, :number_of_divs, :divisions_coordinates, :surveyor_id, :status_id, :date_submitted)");

            // Bind parameters to the query
            $stmt->bindParam(':titledeed', $title_deed);
            $stmt->bindParam(':number_of_divs', $number_of_divs);
            $stmt->bindParam(':divisions_coordinates', $division_coordinates);
            $stmt->bindParam(':surveyor_id', $surveyor_id);
            $stmt->bindParam(':status_id', $status_id);
            $stmt->bindParam(':date_submitted', $date_submitted);

            // Execute the query and check for success
            if ($stmt->execute()) {
                echo '<script>
                        alert("Success! Division mutation details have been recorded successfully.");
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
    } else if ($mutation_type == 'ownership_change') {
        // Retrieve form data
        $title_deed = $_POST['title_deed'];
        $current_owner = $_POST['current_owner'];
        $new_owner = $_POST['new_owner'];
        $surveyor_id = $_SESSION['user_id'];
        $status_id = 3; // Default status for "submitted"
        $date_submitted = date('Y-m-d'); // Format date as YYYY-MM-DD

        // Database connection details
        require 'configs.php';

        try{

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
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Ownership Mutation Form</title>
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/leaflet.draw/1.0.4/leaflet.draw.css" />
    <link rel="stylesheet" href="css/mutationForm.css">
</head>
<body>
    <div class="container">
        <div class="left-section">
            <h2>Mutation Form</h2>
            <form action="mutationForm.php" method="POST">
                <div class="form-group">
                    <label for="title_deed">Title Deed:</label>
                    <input type="text" id="title_deed" name="title_deed" placeholder="Enter Title Deed" value="<?php echo isset($_GET['titledeed']) ? htmlspecialchars($_GET['titledeed']) : ''; ?>">
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
                <button class="submit-button">Submit</button>
            </form>
        </div>
        <div class="right-section">
            <div id="map"></div>
        </div>
    </div>

    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/leaflet.draw/1.0.4/leaflet.draw.js"></script>
    <script>
        // Initialize map
        const map = L.map('map').setView([0, 0], 13);
        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
            maxZoom: 19
        }).addTo(map);

        // Add Leaflet Draw plugin
        const drawnItems = new L.FeatureGroup();
        map.addLayer(drawnItems);

        const drawControl = new L.Control.Draw({
            edit: { featureGroup: drawnItems },
            draw: { polygon: true, rectangle: false, circle: false, marker: false, polyline: false },
        });
        map.addControl(drawControl);

        let polygons = []; // Array to store all polygons

        // Function to update the textarea with the current polygons
        function updateTextarea() {
            const textarea = document.getElementById('divisionCoordinates');
            if (textarea) {
                textarea.value = JSON.stringify(polygons, null, 2); // Pretty-print JSON
            }
        }

        // Capture coordinates on draw
        map.on('draw:created', function (event) {
            const layer = event.layer;
            drawnItems.addLayer(layer);

            // Get GeoJSON of drawn layer
            const geoJson = layer.toGeoJSON();

            polygons.push(geoJson);

             // Update the textarea with the coordinates
            updateTextarea();

            // Append to textarea
            const textarea = document.getElementById('divisionCoordinates');
            if (textarea) {
                textarea.value = JSON.stringify(polygons) + '\n';
            }
        });

        // Function to fetch and display coordinates
        function fetchAndDisplayCoordinates(titleDeed) {
            fetch('api/get_coordinates.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({ titleDeed: titleDeed })
            })
            .then(response => response.json())
            .then(data => {
                if (data.coordinates) {
                    // Clear previous layers
                    map.eachLayer(layer => {
                        if (layer instanceof L.GeoJSON) {
                            map.removeLayer(layer);
                        }
                    });

                    // Add new GeoJSON layer
                    const geoJsonLayer = L.geoJSON(data.coordinates).addTo(map);
                    map.fitBounds(geoJsonLayer.getBounds());
                } else {
                    alert('No coordinates found for the given title deed.');
                }
            })
            .catch(error => {
                console.error('Error fetching coordinates:', error);
            });
        }

        // Check if titledeed is present in the URL on page load
        const urlParams = new URLSearchParams(window.location.search);
        const titleDeedFromURL = urlParams.get('titledeed');
        if (titleDeedFromURL) {
            document.getElementById('title_deed').value = titleDeedFromURL;
            fetchAndDisplayCoordinates(titleDeedFromURL);
        }

        // Listen for changes in the title deed input
        document.getElementById('title_deed').addEventListener('change', function () {
            const titleDeed = this.value;
            fetchAndDisplayCoordinates(titleDeed);
        });

        // Dynamic form fields for mutation type
        document.getElementById('mutation_type').addEventListener('change', function () {
            const selectedValue = this.value;
            const dynamicFields = document.getElementById('dynamicFields');

            dynamicFields.innerHTML = '';
            if (selectedValue === 'division') {
                const divisionField = document.createElement('div');
                divisionField.className = 'form-group';
                divisionField.innerHTML = `
                    <label for="number_of_divs">Number of Divisions:</label>
                    <input type="text" id="number_of_divs" name="number_of_divs" placeholder="Enter number of Divisions">

                    <label for="divisionCoordinates">Coordinates:</label>
                    <textarea id="divisionCoordinates" name="divisionCoordinates" rows="5" placeholder="Draw on the map to capture coordinates..."></textarea>
                `;
                dynamicFields.appendChild(divisionField);
            } else if (selectedValue === 'ownership_change') {
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
        //Before submitting the Division Coordinates Form
       // Handle form submission
document.querySelector('form').addEventListener('submit', function (event) {
    // Convert the polygons array to JSON
    const coordinatesJSON = JSON.stringify(polygons);

    // Set the value of the hidden input field
    document.getElementById('divisionCoordinates').value = coordinatesJSON;

    // Clear the map and reset the polygons array
    resetMap();
});
    </script>
</body>
</html>