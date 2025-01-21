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
        <form action="process_mutation.php" method="POST">
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
        </form>
        
    </div>
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
        <button class="submit-button">Submit</button>
        
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
            "type": "Polygon",
            "coordinates": [[[37.08, -0.982], [37.081, -0.982], [37.083, -0.981], [37.082, -0.981], [37.08, -0.982]]]
        };

        const geoJsonLayer = L.geoJSON(parcelCoordinates).addTo(map);
        map.fitBounds(geoJsonLayer.getBounds());
    </script>
    
    </div>
    
    
    
    
</body>
</html>
