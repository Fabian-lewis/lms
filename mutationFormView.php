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
    $submittedForm = $stmt6->fetch(PDO::FETCH_ASSOC);

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
    <link rel="stylesheet" href="css/mutationFormView.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/leaflet/1.9.4/leaflet.css" />
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.0/css/bootstrap.min.css">
    <script>
        const map = L.map('map').setView([0, 0], 13);

        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
            maxZoom: 19
        }).addTo(map);

        const parcelCoordinates = <?php echo $parcel['coordinates']; ?>;
        const geoJsonLayer = L.geoJSON(parcelCoordinates).addTo(map);

        map.fitBounds(geoJsonLayer.getBounds());
    </script>


    
</head>
<body style="background-color: #2C3E50">
    <header>

    </header>
    <main>
        <?php if ($form_type == "ownership") { ?>
            <div class="header">
                <h1>OWNERSHIP MUTATION FORM</h1>
            </div>
        
        <div class="body">
            <div class="container">
            <!-- Parcel Details -->
        
            <div id= "parcel-details"class="details-card">
            

            <div class="deep-details">
                <p><strong>Form ID:</strong> <?php echo $submittedForm['id']; ?></p>
                <p><strong>Date Submitted:</strong> <?php echo $submittedForm['date_submitted']; ?></p>
                <p><strong>Surveyor:</strong> <?php echo $submittedForm['surveyor']; ?></p>
                <p><strong>Title Deed No:</strong> <?php echo $submittedForm['titledeed_no']; ?></p>
                <p><strong>Status:</strong> <?php echo $submittedForm['status']; ?></p>

            </div>
            <div class="deep-details">
                <p><strong>Current Owner:</strong> <?php echo $submittedForm['current_owner_name']; ?></p>
                <p><strong>Current Owner ID:</strong> <?php echo $submittedForm['current_owner_natid']; ?></p>
            </div>
            <div class="deep-details">
                <p><strong>Proposed Owner:</strong> <?php echo $submittedForm['proposed_owner_name']; ?></p>
                <p><strong>Proposed Owner ID:</strong> <?php echo $submittedForm['proposed_owner_natid']; ?></p>
            </div>
            
            </div>
            <div>
                <button>Approve</button>
                <button>Reject</button>
            </div>
            </div>
        
        
            
        
         <!-- Map -->
        <div id="map"></div>
        </div>
        
        
            
        
       
       
        <?php } ?>
        </div>
    </main>

    
</body>
</html>

