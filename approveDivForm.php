<?php
session_start();

if (!isset($_SESSION['user_id'])) {
    header("Location: mutationFormView.php");
    exit();
}

// Database connection
$host = "localhost";
$port = "5432";
$dbname = "klms";
$password = "gredev";
try {
    $conn = new PDO("pgsql:host=$host;port=$port;dbname=$dbname", 'postgres', $password);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("Connection failed: " . $e->getMessage());
}

$form_id = $_GET['form_id'] ?? null;
if (!$form_id) {
    die("Form ID not provided!");
}

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

if (empty($submittedForm)) {
    die("Form not found!");
}

// Handle form submission for new title IDs
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $newTitleDeeds = $_POST['new_title_deeds'] ?? [];

    if (!empty($newTitleDeeds)) {
        foreach ($newTitleDeeds as $index => $titleDeed) {
            // Insert new title deeds into the database
            $insertQuery = "INSERT INTO parcel (titledeedno, coordinates) VALUES (:titledeed, :coordinates)";
            $stmt = $conn->prepare($insertQuery);
            $stmt->bindValue(':titledeed', $titleDeed, PDO::PARAM_STR);
            $stmt->bindValue(':coordinates', json_encode($divisions[$index]), PDO::PARAM_STR);
            $stmt->execute();
        }
        echo "<script>alert('New title deeds created successfully!');</script>";
    }
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
        <div class="container">
            <div class="row">
                <div class="col-md-12">
                    <h1 class="text-center text-white">Parcel Details</h1>
                </div>
            </div>
            <div class="row">
                <div class="col-md-12">
                    <div class="card">
                        <div class="card-body">
                            <div class="row">
                                <div class="col-md-6">
                                    <h5 class="card-title">Title Deed: <?php echo $submittedForm['titledeed']; ?></h5>
                                    <p class="card-text">Surveyor: <?php echo $submittedForm['surveyor']; ?></p>
                                    <p class="card-text">Date Submitted: <?php echo $submittedForm['date_submitted']; ?></p>
                                    <p class="card-text">Status: <?php echo $submittedForm['status']; ?></p>
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
                                            $divisions = json_decode($submittedForm['divisions_coordinates'], true);
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
                                <div class="col-md-6">
                                    <div id="map2" style="height: 400px;"></div>
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-md-12">
                                    <br>
                                    <form method="POST" action="">
                                        <button type="submit" class="btn btn-success">Create New Title Deeds</button>
                                    </form>
                                    <br><br>
                                    <a href="approveDivForm.php?form_id=<?php echo $submittedForm['id']; ?>" class="btn btn-primary">Approve</a>
                                    <a href="rejectDivForm.php?form_id=<?php echo $submittedForm['id']; ?>" class="btn btn-danger">Reject</a>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </main>
    <footer></footer>
    <script>
    // Function to check if a title deed exists
    async function checkTitleDeedExists(titleDeed) {
        try {
            const response = await fetch(`fetchTitles.php?title_deed=${encodeURIComponent(titleDeed)}`);
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
            event.target.submit(); // Submit the form if all title deeds are valid
        }
    });

    // Optional: Add real-time validation as the user types
    document.querySelectorAll('input[name="new_title_deeds[]"]').forEach(input => {
        input.addEventListener('blur', async function () {
            const titleDeed = input.value.trim();

            if (titleDeed) {
                const exists = await checkTitleDeedExists(titleDeed);

                if (exists) {
                    alert(`Title deed "${titleDeed}" already exists!`);
                    
                    //nput.focus();
                }
            }
        });
    });

    // Map initialization (existing code)
    var map = L.map('map').setView([0.0236, 37.9062], 13);
    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
        maxZoom: 19,
    }).addTo(map);

    var parcelCoordinates = <?php echo $submittedForm['coordinates']; ?>;
    var parcel = L.geoJSON(parcelCoordinates, {color: 'blue'}).addTo(map);
    map.fitBounds(parcel.getBounds());

    var map2 = L.map('map2').setView([0.0236, 37.9062], 13);
    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
        maxZoom: 19,
    }).addTo(map2);

    var divisions = <?php echo $submittedForm['divisions_coordinates']; ?>;
    divisions.forEach(division => {
        var div = L.geoJSON(division, {color: 'red'}).addTo(map2);
        map2.fitBounds(div.getBounds());
    });
</script>
</body>
</html>