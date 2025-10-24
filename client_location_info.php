<?php
session_start();
include 'includes/conn.php';

// Check if user is logged in
if (!isset($_SESSION['client_id']) || $_SESSION['role'] !== 'client') {
    header("Location: login.php");
    exit();
}

// Get client information
$client_id = $_SESSION['client_id'];
$stmt = $conn->prepare("SELECT * FROM clients WHERE client_id = ?");
$stmt->bind_param("i", $client_id);
$stmt->execute();
$client = $stmt->get_result()->fetch_assoc();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <!-- Add Leaflet CSS -->
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" integrity="sha256-p4NxAoJBhIIN+hmNHrzRCf9tD/miZyoHS5obTRR9BMY=" crossorigin=""/>
    <title>Location Info - Bago City Veterinary Office</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <style>
        body {
            background-color: #6c63ff;
        }
        .container-fluid {
            padding-left: 0;
            padding-right: 0;
            overflow-x: hidden;
        }
        .main-wrapper {
            background: white;
            margin-left: 312px;
            padding: 25px;
            border-radius: 10px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
            position: fixed;
            top: 20px;
            left: 1px;
            right: 20px;
            bottom: 20px;
            overflow-y: auto;
            overflow-x: hidden;
        }
        /* Remove all sidebar styles */
        
        .location-card {
            background: white;
            border: 1px solid #eee;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 2px 5px rgba(0,0,0,0.05);
            margin-bottom: 20px;
        }

        .map-container {
            height: 400px;
            width: 100%;
            border-radius: 8px;
            overflow: hidden;
            margin-top: 20px;
            position: relative;
        }
        
        #map {
            height: 100%;
            width: 100%;
            z-index: 1;
        }
    </style>
</head>
<body>
    <div class="container-fluid">
        <div class="row">
            <!-- Replace sidebar HTML with include -->
            <div class="col-md-3">
                <?php include 'includes/client_sidebar.php'; ?>
            </div>

            <!-- Main Content -->
            <div class="col-md-9 main-wrapper">
                <h2>Location Information</h2>
                
                <div class="location-card">
                    <h4>Your Address</h4>
                    <div class="location-details">
                        <p><i class="fas fa-map-marker-alt"></i> <?php echo htmlspecialchars($client['address'] ?? 'Address not set'); ?></p>
                        <p><i class="fas fa-phone"></i> <?php echo htmlspecialchars($client['contact_number'] ?? 'Contact number not set'); ?></p>
                        <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#editLocationModal">
                            <i class="fas fa-edit"></i> Update Location
                        </button>
                    </div>
                </div>

                <!-- Make sure the map div is properly structured -->
                <div class="map-container">
                    <div id="map"></div>
                </div>
            </div> <!-- Close main-wrapper -->
        </div> <!-- Close row -->
    </div> <!-- Close container-fluid -->

    <!-- Edit Location Modal -->
    <div class="modal fade" id="editLocationModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Update Location</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form id="updateLocationForm">
                    <div class="modal-body">
                        <div id="modalMap" style="height: 400px; width: 100%;"></div>
                        <input type="hidden" name="latitude" id="modal_latitude" value="<?php echo htmlspecialchars($client['latitude'] ?? ''); ?>">
                        <input type="hidden" name="longitude" id="modal_longitude" value="<?php echo htmlspecialchars($client['longitude'] ?? ''); ?>">
                    </div>
                    <div class="modal-footer">
                        <button type="submit" class="btn btn-primary">Save Changes</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js" integrity="sha256-20nQCchB9co0qIjJZRGuk2/Z9VM+kNiyxNV1lvTlZBo=" crossorigin=""></script>
    
    <!-- Your map initialization script -->
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            let map, marker;
            
            // Initialize the map with client's location or default to Bago City
            const defaultLocation = [10.5373, 122.8370];
            const clientLocation = [
                <?php echo !empty($client['latitude']) ? $client['latitude'] : '10.5373' ?>,
                <?php echo !empty($client['longitude']) ? $client['longitude'] : '122.8370' ?>
            ];

            try {
                map = L.map('map', {
                    center: clientLocation,
                    zoom: 15,
                    zoomControl: true
                });

                L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                    maxZoom: 19,
                    attribution: '© OpenStreetMap contributors'
                }).addTo(map);

                marker = L.marker(clientLocation, {
                    draggable: false
                }).addTo(map);

                // Force a map refresh
                setTimeout(() => {
                    map.invalidateSize();
                }, 100);

                // Update coordinates when marker is dragged
                // Removed: marker.on('dragend', function(event) { ... });
                
                // Removed: map.on('click', function(event) { ... });
            } catch (error) {
                console.error('Map initialization error:', error);
            }

            // Modal map logic
            var modalMap, modalMarker;
            var modal = document.getElementById('editLocationModal');
            var shown = false;
            
            var showModalMap = function() {
                if (shown) return;
                shown = true;
                const clientLocation = [
                    <?php echo !empty($client['latitude']) ? $client['latitude'] : '10.5373' ?>,
                    <?php echo !empty($client['longitude']) ? $client['longitude'] : '122.8370' ?>
                ];
                
                modalMap = L.map('modalMap', {
                    center: clientLocation,
                    zoom: 15,
                    zoomControl: true
                });
                
                L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                    maxZoom: 19,
                    attribution: '© OpenStreetMap contributors'
                }).addTo(modalMap);
                
                // Make the marker draggable
                modalMarker = L.marker(clientLocation, { draggable: true }).addTo(modalMap);
                
                // Update coordinates when marker is dragged
                modalMarker.on('dragend', function(event) {
                    const position = event.target.getLatLng();
                    document.getElementById('modal_latitude').value = position.lat;
                    document.getElementById('modal_longitude').value = position.lng;
                });
                
                // Allow clicking on map to move marker
                modalMap.on('click', function(event) {
                    const position = event.latlng;
                    modalMarker.setLatLng(position);
                    document.getElementById('modal_latitude').value = position.lat;
                    document.getElementById('modal_longitude').value = position.lng;
                });
                
                setTimeout(() => { 
                    modalMap.invalidateSize(); 
                }, 100);
            };

            var bsModal = document.getElementById('editLocationModal');
            bsModal.addEventListener('shown.bs.modal', showModalMap);

            // Reset the modal when it's closed
            bsModal.addEventListener('hidden.bs.modal', function() {
                shown = false;
                if (modalMap) {
                    modalMap.remove();
                    modalMap = null;
                    modalMarker = null;
                }
            });

            // Form submission event listener
            document.getElementById('updateLocationForm').addEventListener('submit', function(e) {
                e.preventDefault();
                const formData = new FormData(this);
            
                fetch('client_update_location.php', {
                    method: 'POST',
                    body: formData
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        window.location.reload();
                    } else {
                        alert('Error updating location: ' + (data.error || 'Unknown error'));
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('Error updating location. Please try again.');
                });
            });
        });
    </script>
</body>