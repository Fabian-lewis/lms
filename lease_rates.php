<?php
// Database connection
session_start();

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

require_once('auth/check_role.php');
requireRole(['ministry_official']);

require 'configs.php';

// Select the Lease lands
try{
    $query = "SELECT 
                p.titledeedno, 
                p.datecreated,
                o.owner_id,
				CONCAT(curr_owner.fname, ' ', curr_owner.sname) AS current_owner_name,
				u.phone,
				u.email,
                COALESCE(SUM(rp.amount), 0) AS total_paid  -- Corrected COALESCE syntax
                FROM parcel p
                JOIN ownership o ON p.titledeedno = o.titledeed_no
				Join users curr_owner on o.owner_id = curr_owner.id
                LEFT JOIN rate_payment rp ON p.titledeedno = rp.titledeed_no
				Left Join users u ON o.owner_id = u.id
                WHERE p.landtypeid = 2
                GROUP BY p.titledeedno, p.datecreated, o.owner_id, curr_owner.fname, curr_owner.sname, u.phone,u.email;";
    $stmt = $conn->prepare($query);
    $stmt -> execute();
    $lease_parcels = $stmt ->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOExeption $e){
    echo json_encode(["status" => "error", "message" => $e->getMessage()]);
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="css/lease_rates.css">
    <link href="https://stackpath.bootstrapcdn.com/bootstrap/5.0.0-beta2/css/bootstrap.min.css" rel="stylesheet">
    <script src="https://stackpath.bootstrapcdn.com/bootstrap/5.0.0-beta2/js/bootstrap.bundle.min.js"></script>

    <title>Lease Rates</title>
    <script>
        function calculateTotalAmount(startDate, durationMonths, rates) {
            const start = new Date(startDate);
            const totalAmount = {};

            for (let i = 0; i < durationMonths; i++) {
                const currentDate = new Date(start);
                currentDate.setMonth(start.getMonth() + i);

                const year = currentDate.getFullYear();
                const month = currentDate.getMonth() + 1;

                if (!rates[year]) {
                    console.error(`No rate found for year ${year}`);
                    continue;
                }

                const monthlyRate = rates[year] / 12;

                if (!totalAmount[year]) {
                    totalAmount[year] = 0;
                }

                totalAmount[year] += monthlyRate;
            }

            return totalAmount;
        }

        function leaseAmount() {
            const lease_titles = <?php echo json_encode($lease_parcels); ?>;
    
            fetch('api/get_amounts.php', {
                method: 'GET',
                headers: {
                    'Content-Type': 'application/json'
                },
            })
            .then(response => response.json())
            .then(rates => {
                console.log('Rates:', rates);

                lease_titles.forEach(parcel => {
                    const titledeed = parcel.titledeedno;
                    const startDateStr = parcel.datecreated;
                    const durationMonths = 24;
                    const cardId = `parcel-${titledeed}`;
                    const cardElement = document.getElementById(cardId);

                    if (!cardElement) {
                        console.error(`Card element not found for parcel ${titledeed}`);
                        return;
                    }

                    // Convert startDateStr to a JavaScript Date object
                    let startDate = new Date(startDateStr);
                    if (isNaN(startDate)) {
                        console.error("Invalid start date:", startDateStr);
                        return;
                    }

                    // Calculate lease end date
                    let endDate = new Date(startDate);
                    endDate.setMonth(endDate.getMonth() + durationMonths);
                    let formattedEndDate = endDate.toISOString().split('T')[0];

                    // Calculate the total amount
                    const totalAmount = calculateTotalAmount(startDateStr, durationMonths, rates);

                    let total = 0;
                    for (const [year, amount] of Object.entries(totalAmount)) {
                        total += amount;
                    }

                    // Update the specific card for this parcel
                    const rateElement = cardElement.querySelector('.expected-rate');
                    if (rateElement) {
                        rateElement.textContent = `Ksh ${total.toFixed(2)}`;
                    }
                });
            })
            .catch(error => {
                console.error('Error fetching data:', error);
            });
        }
        
        window.onload = function() {
            leaseAmount();
        };
    </script>
</head>
    <body>
        <div class="parcels-container">
            <h2>Lease Rates</h2>
            <div class="card-wrapper">
                <?php foreach ($lease_parcels as $parcel): ?>
                    <div class="card" id="parcel-<?php echo $parcel['titledeedno']; ?>">
                        <!-- Owner Information Section -->
                        <div class="card-section owner-info">
                            <h4>Owner Details</h4>
                            <p data-field="owner-name"><strong>Name:</strong> <?php echo $parcel['current_owner_name']; ?></p>
                            <p data-field="phone"><strong>Phone:</strong> <?php echo $parcel['phone']; ?></p>
                            <p data-field="email"><strong>Email:</strong> <?php echo $parcel['email']; ?></p>
                        </div>
        
                        <!-- Property Information Section -->
                        <div class="card-section property-info">
                            <h4>Property Details</h4>
                            <p data-field="titledeed"><strong>Title Deed No:</strong> <?php echo $parcel['titledeedno']; ?></p>
                            <p><strong>Date Created:</strong> <?php echo $parcel['datecreated']; ?></p>
                        </div>
            
                        <!-- Lease Information Section -->
                        <div class="card-section lease-info">
                            <h4>Lease Details</h4>
                            <p><strong>Duration:</strong> 2 years</p>
                            <p><strong>Amount Paid:</strong> <?php echo $parcel['total_paid']; ?></p>
                            <p><strong>Expected Rate:</strong> <span class="expected-rate">Calculating...</span></p>
                        </div>
            
                        <!-- Button at the bottom -->
                        <div class="card-actions">
                            <a href="" class="btn btn-primary send-notification-btn">Send Reminder Notification</a>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
        
        <script>
            document.addEventListener('DOMContentLoaded', function () {
                console.log("🚀 DOM fully loaded and script running");

                document.querySelectorAll('.send-notification-btn').forEach(button => {
                    console.log('✅ Button found and listener attached');

                    button.addEventListener('click', async function (e) {
                        e.preventDefault(); // Prevent the default anchor link behavior

                        const card = this.closest('.card');

                        // Extract data from the card
                        const ownerName = card.querySelector('[data-field="owner-name"]').innerText.replace(/^.*?:\s*/, '').trim();
                        const phone = card.querySelector('[data-field="phone"]').innerText.replace(/^.*?:\s*/, '').trim();
                        const email = card.querySelector('[data-field="email"]').innerText.replace(/^.*?:\s*/, '').trim();
                        const titledeed = card.querySelector('[data-field="titledeed"]').innerText.replace(/^.*?:\s*/, '').trim();

                        console.log({ ownerName, phone, email, titledeed });

                        // Store the original button text
                        const originalText = this.innerHTML;

                        // Show loading state
                        this.disabled = true;
                        this.innerHTML = '<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Sending...';

                        try {
                            // Send request to API
                            const response = await fetch('api/send-notification.php', {
                                method: 'POST',
                                headers: {
                                    'Content-Type': 'application/json',
                                },
                                body: JSON.stringify({ ownerName, phone, email, titledeed }),
                            });

                            if (!response.ok) {
                                throw new Error(`Server error: ${response.status}`);
                            }

                            const result = await response.json();

                            if (result.success) {
                                showAlert('Notification sent successfully!', 'success', card);
                            } else {
                                showAlert(`Failed to send: ${result.message}`, 'danger', card);
                            }
                        } catch (error) {
                            showAlert('Network error - please try again', 'danger', card);
                            console.error('Error:', error);
                        } finally {
                            this.disabled = false;
                            this.innerHTML = originalText;
                        }
                    });
                });


                // Helper function to show alerts within the relevant section
                function showAlert(message, type, referenceElement) {
                    const alertDiv = document.createElement('div');
                    alertDiv.className = `alert alert-${type} alert-dismissible fade show`;
                    alertDiv.role = 'alert';
                    alertDiv.innerHTML = `
                        ${message}
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    `;

                    // Find the closest container or default to document body
                    referenceElement.prepend(alertDiv);


                    // Auto-dismiss after 5 seconds
                    setTimeout(() => alertDiv.remove(), 15000);
                }
            });

        </script>
    </body>
</html>