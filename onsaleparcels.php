<?php
// display lands on sale 
// 1. get all lands on sale from the database
// Database connection

session_start();

if (!isset($_SESSION['user_id'])) {
    header("Location: dashboard.php");
    exit();
}

// Database Conncetion
require 'configs.php';

// Get the parcels from the database
$query = "SELECT
            p.id,
            p.coordinates,
            p.datecreated,
            p.titledeedno,
            o.date_started,
            s.status,
            l.landtype,
            CONCAT(owner.fname, ' ', owner.sname) AS owner_name
        FROM
            parcel p
        JOIN ownership o ON p.titledeedno = o.titledeed_no
        JOIN status s ON o.status_id = s.id
        JOIN landtype l ON p.landtypeid = l.id
        JOIN users owner ON o.owner_id = owner.id
        WHERE
            s.status = 'active'";

$stmt = $conn->prepare($query);
$stmt->execute();

$parcels = $stmt->fetchAll(PDO::FETCH_ASSOC);
if(!$parcels){
    die("No parcels found!");
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>lands on sale</title>
    <link rel="stylesheet" href="css/onsaleparcels.css">
    <!-- Leaflet CSS -->
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.7.1/dist/leaflet.css" />

    <!-- Leaflet JS -->
    <script src="https://unpkg.com/leaflet@1.7.1/dist/leaflet.js"></script>

</head>
<body>
    <header>
        <div class="logo">
            <img src="images/lms_logo2.PNG" alt="LMS Logo">
        </div>
        <nav>
            <ul>
                <li><a href="index.php">Home</a></li>
                <li><a href="profile.php">Profile</a></li>
                <li>
                    <a href="logout.php" class="logout">Logout</a>
                </li>
            </ul>
        </nav>
    </header>

    <main>
    <div class="parcels-container">
    <h2>On Sale Lands</h2>
    <div class="card-wrapper">
        <?php foreach ($parcels as $parcel): ?>
            <div class="card">
                <div>
                    <h3>Parcel: <?php echo $parcel['titledeedno']; ?></h3>
                    <p><strong>Date Created:</strong> <?php echo $parcel['datecreated']; ?></p>
                    <p><strong>Land Type:</strong> <?php echo $parcel['landtype']; ?></p>
                    <p><strong>Status:</strong> On-Sale</p>
                    <p><strong>Owner:</strong> <?php echo $parcel['owner_name']; ?></p>
                </div>
                
                <!-- Map Container (added style inline to ensure visibility) -->
                <div id="map-<?php echo $parcel['titledeedno']; ?>" class="map-container" style="height: 250px; width: 100%;"></div>

                <div class="profile-actions">
                    <button onclick="notifyOwner('<?php echo $parcel['titledeedno']; ?>',<?php echo $_SESSION['user_id'];?>)">Reach Out to Owner</button>
                </div>
            </div>
            
            <!-- Initialize map for this parcel -->
            <script>
                document.addEventListener('DOMContentLoaded', function() {
                    try {
                        const coordinates = <?php echo $parcel['coordinates']; ?>;
                        const mapId = 'map-<?php echo $parcel['titledeedno']; ?>';
                        
                        if (coordinates && document.getElementById(mapId)) {
                            const map = L.map(mapId).setView([0, 0], 2);
                            
                            L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                                maxZoom: 19,
                                attribution: '© OpenStreetMap contributors'
                            }).addTo(map);
                            
                            const geoJsonLayer = L.geoJSON(coordinates).addTo(map);
                            map.fitBounds(geoJsonLayer.getBounds());
                        }
                    } catch (error) {
                        console.error('Error initializing map:', error);
                    }
                });
            </script>
        <?php endforeach; ?>
    </div>
</div>

<!-- Leaflet JS (make sure this is loaded) -->
<script src="https://unpkg.com/leaflet@1.7.1/dist/leaflet.js"></script>
        <script>
            
            // document.querySelectorAll('.map-container').forEach((mapDiv, index) => {
            //     const parcelCoordinates = <?php echo ($parcel['coordinates']); ?>;


            //     const map = L.map(mapDiv).setView([0, 0], 13);

            //     L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
            //         maxZoom: 19
            //     }).addTo(map);

            //     const geoJsonLayer = L.geoJSON(parcelCoordinates).addTo(map);
            //     map.fitBounds(geoJsonLayer.getBounds());
            // });

    
            function notifyOwner(parcelId, userId) {
                console.log("Parcel ID:", parcelId, "User ID:", userId);  // Debugging output

                if(confirm("The owner of this land parcel will be notified about your interest.\nYour number will be shared with the owner.\nAre you sure you want to reach out to the owner of this land parcel?")){
                    fetch('api/onsaleNotification.php', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json'
                        },
                        body: JSON.stringify({
                            parcelId: parcelId,
                            userId: userId
                        })
                    })

                    .then(response => {
                        console.log(response);
                        return response.json();
                    })
                    .then(data=>{
                        console.log(data);
                        
                        if(data.success){
                            alert("The owner of this land parcel will be notified about your interest. Your number will be shared with the owner.");
                        } else{
                            alert("An error occurred while trying to reach out to the owner of this land parcel. Please try again later.");
                        }
                    })
                    .catch(error =>{
                        console.error('Error:', error);
                        alert("An error occurred while trying to reach out to the owner of this land parcel. Please try again later.");
                    });
                }
                
            }

        </script>
    </main>
</body>
</html>