<?php
session_start();

// Redirect if user is not logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: mutationFormView.php");
    exit();
}

// Database connection
require 'configs.php';

// Get form ID from URL
$form_id = $_GET['form_id'] ?? null;
if (!$form_id) {
    die("Form ID not provided!");
}

// Fetch form details from the database
$query1 = "SELECT
                d.id,
                d.titledeed,
                d.divisions_coordinates,
                d.number_of_divs,
                p.coordinates,
                p.landtypeid,
                d.date_submitted,
                CONCAT(surveyor.fname, ' ', surveyor.sname) AS surveyor,
                o.id AS ownership_id,
                o.date_started,
                o.date_end,
                o.status_id,
                o.owner_id,
                l.landtype,
                CONCAT(owner.fname, ' ', owner.sname) AS owner,
                d.surveyor_id,
                d.status_id,
                s.status
            FROM
                division_form d
                JOIN ownership o ON d.titledeed = o.titledeed_no
                JOIN users owner ON o.owner_id = owner.id
                JOIN status s ON d.status_id = s.id
                JOIN parcel p ON d.titledeed = p.titledeedno
                JOIN landtype l ON p.landtypeid = l.id
                JOIN users surveyor ON d.surveyor_id = surveyor.id
            WHERE
                d.id = :form_id";
$stmt6 = $conn->prepare($query1);
$stmt6->bindValue(':form_id', $form_id, PDO::PARAM_INT);
$stmt6->execute();
$submittedForm = $stmt6->fetch(PDO::FETCH_ASSOC);

if (empty($submittedForm)) {
    die("Form not found!");
}

// Decode coordinates for use in JavaScript
$parcelCoordinates = json_decode($submittedForm['coordinates'], true);
$divisionsCoordinates = json_decode($submittedForm['divisions_coordinates'], true);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Parcel Details</title>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/leaflet/1.9.4/leaflet.js"></script>
    <link rel="stylesheet" href="css/approveDivForm.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/leaflet/1.9.4/leaflet.css" />
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.0/css/bootstrap.min.css">
</head>
<body style="background-color: #008080">
    <header></header>
    <main>
        <form action="process_titledeeds.php" method="POST">
            <div class="container" style="background-color: #222; color:black;">
                <div class="row">
                    <div class="col-md-12">
                        <h1 class="text-center text-white">Parcel Details</h1>
                    </div>
                </div>
                <div class="row">
                    <div class="col-md-12">
                        <div class="card">
                            <div class="card-body" style="background-color: #333; color: white; border-radius: 10px; margin: none;">
                                <div class="row">
                                    <div class="col-md-6">
                                        <h5 class="card-title">PARCEL DETAILS</h5>
                                        <p class="card-title">Title Deed: <?php echo htmlspecialchars($submittedForm['titledeed']); ?></p>
                                        <p class="card-text">Land Type: <?php echo htmlspecialchars($submittedForm['landtype']); ?></p>
                                        <h5 class="card-title">DIVISION MUTATION FORM DETAILS</h5>
                                        <p class="card-text">Surveyor: <?php echo htmlspecialchars($submittedForm['surveyor']); ?></p>
                                        <p class="card-text">Date Submitted: <?php echo htmlspecialchars($submittedForm['date_submitted']); ?></p>
                                        <p class="card-text">Status: <?php echo htmlspecialchars($submittedForm['status']); ?></p>
                                        <h5 class="card-title">Current Owner</h5>
                                        <p class="card-text">Name: <?php echo htmlspecialchars($submittedForm['owner']); ?></p>
                                        <h5 class="card-title">OWNERSHIP DETAILS</h5>
                                        <p class="card-text">Date Started: <?php echo htmlspecialchars($submittedForm['date_started']); ?></p>
                                        <p class="card-text">Date Ended: <?php echo htmlspecialchars($submittedForm['date_end']); ?></p>
                                    </div>
                                    <div class="col-md-6">
                                        <div id="map" style="height: 400px;"></div>
                                    </div>
                                </div>
                                <div class="row">
                                    <div class="col-md-12">
                                        <h5 class="card-title">Divisions</h5>
                                        <table class="table table-bordered">
                                            <thead>
                                                <tr>
                                                    <th>#</th>
                                                    <th>Coordinates</th>
                                                    <th>New Title Deed</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php
                                                $divisions = $divisionsCoordinates;
                                                $i = 1;
                                                foreach ($divisions as $division) {
                                                    echo "<tr>";
                                                    echo "<td>{$i}</td>";
                                                    echo "<td>".json_encode($division) ."</td>";
                                                    echo "<td><input type='text' name='new_title_deeds[]' class='form-control' placeholder='Enter new title deed'></td>";
                                                    echo "</tr>";
                                                    $i++;
                                                }
                                                ?>
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                                <div class="row" id="newParcels">
                                    <div class="col-md-6">
                                        <h5 class="card-title">New Parcels</h5>
                                    </div>
                                    <div class="col-md-6">
                                        <div id="map2" style="height: 400px;"></div>
                                    </div>
                                </div>
                                <div class="row">
                                    <div class="col-md-12">
                                        <br>
                                        <button type="submit" class="btn btn-success">Create New Title Deeds</button>
                                        <br><br>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </form>
    </main>
    <footer></footer>
    <script>
         // Initialize the main map
    var map = L.map('map').setView([0.0236, 37.9062], 13);
    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
        maxZoom: 19,
    }).addTo(map);

    // Load parcel coordinates
    var parcelCoordinates = <?php echo json_encode($parcelCoordinates); ?>;
    if (parcelCoordinates && parcelCoordinates.coordinates) {
        var parcel = L.geoJSON(parcelCoordinates, { color: 'blue' }).addTo(map);
        map.fitBounds(parcel.getBounds());
    } else {
        console.error("Invalid parcel coordinates:", parcelCoordinates);
    }

    // Initialize the divisions map
    var map2 = L.map('map2').setView([0.0236, 37.9062], 13);
    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
        maxZoom: 19,
    }).addTo(map2);

    // Load divisions coordinates
    var divisions = <?php echo json_encode($divisionsCoordinates); ?>;
    if (divisions && Array.isArray(divisions)) {
        divisions.forEach(division => {
            if (division && division.geometry) {
                // Extract the geometry from the Feature
                var geometry = division.geometry;
                if (geometry.type === "Polygon" && geometry.coordinates) {
                    var div = L.geoJSON(geometry, { color: 'red' }).addTo(map2);
                    map2.fitBounds(div.getBounds());
                } else {
                    console.error("Invalid geometry in division:", geometry);
                }
            } else {
                console.error("Invalid division:", division);
            }
        });
    } else {
        console.error("Invalid divisions data:", divisions);
    }

        // Function to check if a title deed exists
        async function checkTitleDeedExists(titleDeed) {
            try {
                const response = await fetch(`api/fetchTitles.php?title_deed=${encodeURIComponent(titleDeed)}`);
                const data = await response.json();
                return data.exists;
            } catch (error) {
                console.error('Error checking title deed:', error);
                return false;
            }
        }

        // Event listener for form submission
        document.querySelector('form').addEventListener('submit', async function (event) {
            event.preventDefault(); // Prevent form submission

            const titleDeedInputs = document.querySelectorAll('input[name="new_title_deeds[]"]');
            let allValid = true;

            for (const input of titleDeedInputs) {
                const titleDeed = input.value.trim();

                if (titleDeed) {
                    const exists = await checkTitleDeedExists(titleDeed);

                    if (exists) {
                        alert(`Title deed "${titleDeed}" already exists!`);
                        input.focus();
                        allValid = false;
                        break;
                    }
                }
            }

            if (allValid) {
                const formData = {
                    form_id: <?php echo $form_id; ?>,
                    new_title_deeds: Array.from(titleDeedInputs).map(input => input.value.trim()),
                    owner_id: <?php echo $submittedForm['owner_id']; ?>,
                    landtypeid: <?php echo $submittedForm['landtypeid']; ?>,
                    divisions: <?php echo json_encode($divisionsCoordinates); ?>,
                    currentTitledeed: <?php echo json_encode($submittedForm['titledeed']); ?>
                };

                try {
                    const response = await fetch('api/process_titledeeds.php', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                        },
                        body: JSON.stringify(formData)
                    });

                    const result = await response.json();

                    if (result.success) {
                        alert('New title deeds created successfully!');
                        window.location.reload(); // Reload page after success
                    } else {
                        alert(`Error: ${result.message}`);
                    }
                } catch (error) {
                    console.error('Error submitting form:', error);
                    alert('An error occurred while processing the request.');
                }
            }
        });
    </script>
</body>
</html>