<?php
session_start();
include 'includes/conn.php';

// Check if user is logged in as client
if (!isset($_SESSION['client_id']) || $_SESSION['role'] !== 'client') {
    header("Location: login.php");
    exit();
}

$client_id = $_SESSION['client_id'];

// Get client information
$stmt = $conn->prepare("SELECT * FROM clients WHERE client_id = ?");
$stmt->bind_param("i", $client_id);
$stmt->execute();
$client = $stmt->get_result()->fetch_assoc();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Uploaded Photos - Bago City Veterinary Office</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <style>
        /* Base styles */
        body {
            background-color: #6c63ff;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        .container-fluid {
            padding-left: 0;
            padding-right: 0;
            overflow-x: hidden;
        }
        
        /* Sidebar styles handled by client_sidebar.php */
        .main-wrapper {
            background: white;
            margin-left: 320px;
            padding: 25px;
            border-radius: 10px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
            position: fixed;
            top: 20px;
            left: 20px;
            right: 20px;
            bottom: 20px;
            overflow-y: auto;
            overflow-x: hidden;
            min-height: calc(100vh - 40px);
            max-width: calc(100vw - 340px);
        }
        
        /* Large desktop styles */
        @media (min-width: 1400px) {
            .main-wrapper {
                margin-left: 320px;
                max-width: calc(100vw - 340px);
            }
            .photo-gallery {
                grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
                gap: 25px;
            }
        }
        
        /* Desktop styles */
        @media (min-width: 1200px) and (max-width: 1399px) {
            .main-wrapper {
                margin-left: 320px;
                max-width: calc(100vw - 340px);
            }
            .photo-gallery {
                grid-template-columns: repeat(auto-fill, minmax(250px, 1fr));
                gap: 20px;
            }
        }
        
        /* Tablet landscape styles */
        @media (min-width: 992px) and (max-width: 1199px) {
            .main-wrapper {
                margin-left: 320px;
                left: 15px;
                right: 15px;
                max-width: calc(100vw - 350px);
                padding: 20px;
            }
            .photo-gallery {
                grid-template-columns: repeat(auto-fill, minmax(220px, 1fr));
                gap: 18px;
            }
            .stats-cards {
                grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
                gap: 15px;
            }
        }
        
        /* Tablet portrait styles */
        @media (min-width: 769px) and (max-width: 991px) {
            .main-wrapper {
                margin-left: 320px;
                left: 10px;
                right: 10px;
                max-width: calc(100vw - 340px);
                padding: 18px;
            }
            .photo-gallery {
                grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
                gap: 15px;
            }
            .stats-cards {
                grid-template-columns: repeat(auto-fit, minmax(160px, 1fr));
                gap: 12px;
            }
            .filter-section {
                padding: 15px;
            }
        }
        
        /* Mobile landscape styles */
        @media (max-width: 768px) {
            .main-wrapper {
                margin-left: 0;
                top: 100px;
                left: 15px;
                right: 15px;
                bottom: 15px;
                max-width: calc(100vw - 30px);
                padding: 20px;
            }
            .photo-gallery {
                grid-template-columns: repeat(auto-fill, minmax(180px, 1fr));
                gap: 15px;
            }
            .stats-cards {
                grid-template-columns: repeat(2, 1fr);
                gap: 15px;
            }
            .filter-section {
                padding: 15px;
            }
            .filter-section .row {
                flex-direction: column;
                gap: 15px;
            }
            .filter-section .col-md-6 {
                width: 100%;
                text-align: left !important;
            }
        }
        
        /* Mobile portrait styles */
        @media (max-width: 576px) {
            .main-wrapper {
                left: 10px;
                right: 10px;
                top: 100px;
                bottom: 10px;
                max-width: calc(100vw - 20px);
                padding: 15px;
            }
            .photo-gallery {
                grid-template-columns: repeat(auto-fill, minmax(150px, 1fr));
                gap: 12px;
            }
            .stats-cards {
                grid-template-columns: repeat(2, 1fr);
                gap: 12px;
            }
            .stat-card {
                padding: 15px;
            }
            .stat-number {
                font-size: 1.5rem;
            }
            .filter-section {
                padding: 12px;
            }
            .page-header {
                flex-direction: column;
                align-items: flex-start;
                gap: 15px;
            }
            .page-header .btn {
                width: 100%;
            }
        }
        
        /* Small mobile styles */
        @media (max-width: 480px) {
            .main-wrapper {
                left: 5px;
                right: 5px;
                top: 100px;
                bottom: 5px;
                max-width: calc(100vw - 10px);
                padding: 12px;
            }
            .photo-gallery {
                grid-template-columns: repeat(auto-fill, minmax(130px, 1fr));
                gap: 10px;
            }
            .stats-cards {
                grid-template-columns: 1fr;
                gap: 10px;
            }
            .stat-card {
                padding: 12px;
            }
            .stat-number {
                font-size: 1.3rem;
            }
            .stat-label {
                font-size: 0.8rem;
            }
            .filter-section {
                padding: 10px;
            }
            .photo-card {
                border-radius: 8px;
            }
            .photo-image {
                height: 150px;
            }
            .photo-info {
                padding: 12px;
            }
            .photo-species {
                font-size: 1rem;
            }
        }
        
        /* Extra small mobile styles */
        @media (max-width: 360px) {
            .main-wrapper {
                left: 3px;
                right: 3px;
                top: 100px;
                bottom: 3px;
                max-width: calc(100vw - 6px);
                padding: 10px;
            }
            .photo-gallery {
                grid-template-columns: repeat(auto-fill, minmax(120px, 1fr));
                gap: 8px;
            }
            .photo-image {
                height: 120px;
            }
            .photo-info {
                padding: 10px;
            }
            .photo-species {
                font-size: 0.9rem;
            }
            .photo-date {
                font-size: 0.75rem;
            }
            .photo-status {
                font-size: 0.7rem;
                padding: 3px 8px;
            }
        }

        /* Photo Gallery Styles */
        .photo-gallery {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(250px, 1fr));
            gap: 20px;
            padding: 20px 0;
        }

        .photo-card {
            background: white;
            border-radius: 12px;
            box-shadow: 0 4px 12px rgba(0,0,0,0.1);
            overflow: hidden;
            transition: all 0.3s ease;
            cursor: pointer;
        }

        .photo-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 25px rgba(0,0,0,0.15);
        }

        .photo-image {
            width: 100%;
            height: 200px;
            object-fit: cover;
            border-bottom: 1px solid #e9ecef;
        }

        .photo-info {
            padding: 15px;
        }

        .photo-species {
            font-weight: 600;
            color: #333;
            margin-bottom: 8px;
            font-size: 1.1rem;
        }

        .photo-meta {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 10px;
        }

        .photo-date {
            font-size: 0.85rem;
            color: #6c757d;
        }

        .photo-status {
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 0.75rem;
            font-weight: 500;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .status-approved {
            background-color: #d4edda;
            color: #155724;
        }

        .status-pending {
            background-color: #fff3cd;
            color: #856404;
        }

        .status-rejected {
            background-color: #f8d7da;
            color: #721c24;
        }

        .empty-state {
            text-align: center;
            padding: 60px 20px;
            color: #6c757d;
        }

        .empty-state i {
            font-size: 4rem;
            margin-bottom: 20px;
            color: #dee2e6;
        }

        .loading-spinner {
            text-align: center;
            padding: 40px;
        }

        .filter-section {
            background: #f8f9fa;
            padding: 20px;
            border-radius: 10px;
            margin-bottom: 30px;
        }

        .stats-cards {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }

        /* ML Insights Style Metric Cards */
        .metric-card {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border-radius: 20px;
            padding: 20px 25px;
            margin-bottom: 20px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
            transition: all 0.4s cubic-bezier(0.175, 0.885, 0.32, 1.275);
            position: relative;
            overflow: hidden;
            border: 1px solid rgba(255, 255, 255, 0.1);
            height: 150px;
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
            cursor: pointer;
        }
        
        .metric-card:hover {
            transform: translateY(-8px) scale(1.02);
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.15);
        }
        
        .metric-card::after {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.1), transparent);
            transition: left 0.5s;
        }
        
        .metric-card:hover::after {
            left: 100%;
        }
        
        .metric-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 4px;
            background: rgba(255, 255, 255, 0.3);
        }
        
        
        .metric-card .metric-title {
            font-size: 1rem;
            font-weight: 500;
            opacity: 0.9;
            margin-bottom: 15px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        
        .metric-card .metric-value {
            font-size: 2.2rem;
            font-weight: 700;
            margin-bottom: 12px;
            line-height: 1;
        }
        
        .metric-card .metric-detail {
            font-size: 0.75rem;
            opacity: 0.8;
            font-weight: 400;
            margin-bottom: 0;
        }
        
        /* Individual card color schemes */
        .metric-card:nth-child(1) {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        }
        
        .metric-card:nth-child(2) {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        }
        
        .metric-card:nth-child(3) {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        }
        
        .metric-card:nth-child(4) {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        }
        
        /* Loading animation for metric cards */
        .metric-card.loading {
            animation: pulse 2s infinite;
        }
        
        @keyframes pulse {
            0% { opacity: 1; }
            50% { opacity: 0.7; }
            100% { opacity: 1; }
        }
        
        /* Responsive adjustments */
        @media (max-width: 768px) {
            .metric-card {
                margin-bottom: 15px;
                padding: 15px 12px;
            }
            
            
            .metric-card .metric-value {
                font-size: 1.8rem;
            }
            
            .metric-card .metric-title {
                font-size: 0.9rem;
                margin-bottom: 12px;
            }
            
            .metric-card .metric-detail {
                font-size: 0.7rem;
                margin-bottom: 0;
            }
        }

        .stat-label {
            font-size: 0.9rem;
            opacity: 0.9;
        }

        /* Additional responsive utilities */
        .page-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 15px;
        }

        .filter-section .form-select {
            min-width: 150px;
        }

        .filter-section .btn {
            white-space: nowrap;
        }

        /* Modal responsive adjustments */
        @media (max-width: 768px) {
            .modal-dialog {
                margin: 10px;
            }
            .modal-body .row {
                flex-direction: column;
            }
            .modal-body .col-md-8,
            .modal-body .col-md-4 {
                width: 100%;
                margin-bottom: 15px;
            }
        }

        /* Ensure proper spacing on all devices */
        .photo-gallery {
            margin-top: 20px;
        }

        .empty-state {
            margin-top: 40px;
        }

        .loading-spinner {
            margin-top: 40px;
        }

        /* Stats Modal Styles */
        .stats-detail-item {
            background: #f8f9fa;
            border-radius: 8px;
            padding: 15px;
            margin-bottom: 15px;
            border-left: 4px solid #6c63ff;
        }

        .stats-detail-item:last-child {
            margin-bottom: 0;
        }

        .stats-detail-species {
            font-weight: 600;
            color: #333;
            margin-bottom: 8px;
        }

        .stats-detail-meta {
            display: flex;
            justify-content: space-between;
            align-items: center;
            font-size: 0.9rem;
            color: #6c757d;
        }

        .stats-detail-date {
            font-size: 0.85rem;
        }

        .stats-detail-status {
            padding: 4px 8px;
            border-radius: 12px;
            font-size: 0.75rem;
            font-weight: 500;
            text-transform: uppercase;
        }

        .stats-detail-status.approved {
            background-color: #d4edda;
            color: #155724;
        }

        .stats-detail-status.pending {
            background-color: #fff3cd;
            color: #856404;
        }

        .stats-detail-status.rejected {
            background-color: #f8d7da;
            color: #721c24;
        }

        .stats-summary {
            background: linear-gradient(135deg, #6c63ff, #5a52d5);
            color: white;
            padding: 20px;
            border-radius: 10px;
            margin-bottom: 20px;
            text-align: center;
        }

        .stats-summary h4 {
            margin-bottom: 10px;
        }

        .stats-summary p {
            margin-bottom: 0;
            opacity: 0.9;
        }
    </style>
</head>
<body>
    <div class="container-fluid">
        <div class="row">
            <!-- Sidebar -->
            <div class="col-md-3">
                <?php include 'includes/client_sidebar.php'; ?>
            </div>

            <!-- Main Content -->
            <div class="col-md-9 main-wrapper main-content">
                <div class="d-flex justify-content-between align-items-center mb-4 page-header">
                    <div>
                        <h2 class="mb-1">Uploaded Photos</h2>
                        <p class="text-muted mb-0">View and manage your animal photos</p>
                    </div>
                    <button class="btn btn-primary" onclick="window.location.href='client_animals_owned.php'">
                        <i class="fas fa-upload me-2"></i>Upload New Photos
                    </button>
                </div>

                <!-- Stats Cards -->
                <div class="stats-cards">
                    <div class="metric-card" title="Total number of uploaded photos">
                        <div class="metric-title">Total Photos</div>
                        <div class="metric-value" id="totalPhotos">0</div>
                        <div class="metric-detail">All uploaded photos</div>
                    </div>
                    <div class="metric-card" onclick="openStatsModal('approved')" title="Click to view approved photos">
                        <div class="metric-title">Approved</div>
                        <div class="metric-value" id="approvedPhotos">0</div>
                        <div class="metric-detail">Verified photos</div>
                    </div>
                    <div class="metric-card" onclick="openStatsModal('pending')" title="Click to view pending photos">
                        <div class="metric-title">Pending</div>
                        <div class="metric-value" id="pendingPhotos">0</div>
                        <div class="metric-detail">Awaiting review</div>
                    </div>
                    <div class="metric-card" onclick="openStatsModal('rejected')" title="Click to view rejected photos">
                        <div class="metric-title">Rejected</div>
                        <div class="metric-value" id="rejectedPhotos">0</div>
                        <div class="metric-detail">Needs revision</div>
                    </div>
                </div>

                <!-- Filter Section -->
                <div class="filter-section">
                    <div class="row align-items-center">
                        <div class="col-md-6">
                            <h5 class="mb-0">Photo Gallery</h5>
                        </div>
                        <div class="col-md-6 text-end">
                            <select class="form-select d-inline-block w-auto" id="statusFilter">
                                <option value="">All Status</option>
                                <option value="Approved">Approved</option>
                                <option value="Pending">Pending</option>
                                <option value="Rejected">Rejected</option>
                            </select>
                            <button class="btn btn-outline-primary ms-2" onclick="refreshPhotos()">
                                <i class="fas fa-sync-alt"></i> Refresh
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Loading Spinner -->
                <div id="loadingSpinner" class="loading-spinner">
                    <div class="spinner-border text-primary" role="status">
                        <span class="visually-hidden">Loading...</span>
                    </div>
                    <p class="mt-2">Loading your photos...</p>
                </div>

                <!-- Photo Gallery -->
                <div id="photoGallery" class="photo-gallery" style="display: none;">
                    <!-- Photos will be loaded here -->
                </div>

                <!-- Empty State -->
                <div id="emptyState" class="empty-state" style="display: none;">
                    <i class="fas fa-images"></i>
                    <h4>No Photos Yet</h4>
                    <p>You haven't uploaded any photos yet. Start by uploading photos of your animals.</p>
                    <button class="btn btn-primary" onclick="window.location.href='client_animals_owned.php'">
                        <i class="fas fa-upload me-2"></i>Upload Your First Photo
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Photo Modal -->
    <div class="modal fade" id="photoModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="photoModalTitle">Photo Details</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="row">
                        <div class="col-md-8">
                            <img id="modalPhotoImage" src="" class="img-fluid rounded" alt="Animal Photo">
                        </div>
                                                 <div class="col-md-4">
                             <h6>Photo Information</h6>
                             <p><strong>Species:</strong> <span id="modalSpecies"></span></p>
                             <p><strong>Uploaded:</strong> <span id="modalUploadDate"></span></p>
                             <p><strong>Status:</strong> <span id="modalStatus"></span></p>
                             <div id="rejectionReason" style="display: none;">
                                 <p><strong>Rejection Reason:</strong></p>
                                 <p class="text-danger" id="modalRejectionReason"></p>
                             </div>
                         </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Stats Detail Modal -->
    <div class="modal fade" id="statsModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="statsModalTitle">Photo Statistics</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div id="statsModalContent">
                        <!-- Content will be populated by JavaScript -->
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        let allPhotos = [];
        let filteredPhotos = [];

        // Load photos when page loads
        document.addEventListener('DOMContentLoaded', function() {
            loadPhotos();
            
            // Add filter change listener
            document.getElementById('statusFilter').addEventListener('change', filterPhotos);
        });

        // Load photos from server
        function loadPhotos() {
            const loading = document.getElementById('loadingSpinner');
            const gallery = document.getElementById('photoGallery');
            const empty = document.getElementById('emptyState');
            
            loading.style.display = 'block';
            gallery.style.display = 'none';
            empty.style.display = 'none';
            
            fetch('get_client_photos.php')
                .then(response => response.json())
                .then(data => {
                    loading.style.display = 'none';
                    
                    if (data.success && data.photos.length > 0) {
                        allPhotos = data.photos;
                        filteredPhotos = [...allPhotos];
                        displayPhotos();
                        updateStats();
                    } else {
                        empty.style.display = 'block';
                        updateStats();
                    }
                })
                .catch(error => {
                    console.error('Error loading photos:', error);
                    loading.style.display = 'none';
                    empty.style.display = 'block';
                });
        }

        // Display photos in gallery
        function displayPhotos() {
            const gallery = document.getElementById('photoGallery');
            const empty = document.getElementById('emptyState');
            
            if (filteredPhotos.length === 0) {
                gallery.style.display = 'none';
                empty.style.display = 'block';
                return;
            }
            
            gallery.style.display = 'grid';
            empty.style.display = 'none';
            
            gallery.innerHTML = '';
            
            filteredPhotos.forEach(photo => {
                const photoCard = document.createElement('div');
                photoCard.className = 'photo-card';
                photoCard.onclick = () => openPhotoModal(photo);
                
                const statusClass = photo.status === 'Approved' ? 'status-approved' : 
                                  photo.status === 'Rejected' ? 'status-rejected' : 'status-pending';
                
                photoCard.innerHTML = `
                    <img src="${photo.photo_path}" class="photo-image" alt="Animal Photo">
                    <div class="photo-info">
                        <div class="photo-species">${photo.species}</div>
                        <div class="photo-meta">
                            <span class="photo-date">${photo.uploaded_at}</span>
                            <span class="photo-status ${statusClass}">${photo.status}</span>
                        </div>
                    </div>
                `;
                
                gallery.appendChild(photoCard);
            });
        }

        // Filter photos by status
        function filterPhotos() {
            const statusFilter = document.getElementById('statusFilter').value;
            
            if (statusFilter === '') {
                filteredPhotos = [...allPhotos];
            } else {
                filteredPhotos = allPhotos.filter(photo => photo.status === statusFilter);
            }
            
            displayPhotos();
        }

        // Update statistics
        function updateStats() {
            const total = allPhotos.length;
            const approved = allPhotos.filter(p => p.status === 'Approved').length;
            const pending = allPhotos.filter(p => p.status === 'Pending').length;
            const rejected = allPhotos.filter(p => p.status === 'Rejected').length;
            
            document.getElementById('totalPhotos').textContent = total;
            document.getElementById('approvedPhotos').textContent = approved;
            document.getElementById('pendingPhotos').textContent = pending;
            document.getElementById('rejectedPhotos').textContent = rejected;
        }

                 // Open photo modal
         function openPhotoModal(photo) {
             document.getElementById('modalPhotoImage').src = photo.photo_path;
             document.getElementById('modalSpecies').textContent = photo.species;
             document.getElementById('modalUploadDate').textContent = photo.uploaded_at;
             
             const statusClass = photo.status === 'Approved' ? 'badge bg-success' : 
                               photo.status === 'Rejected' ? 'badge bg-danger' : 'badge bg-warning';
             
             document.getElementById('modalStatus').innerHTML = `<span class="${statusClass}">${photo.status}</span>`;
             
             // Handle rejection reason
             const rejectionDiv = document.getElementById('rejectionReason');
             const rejectionReason = document.getElementById('modalRejectionReason');
             
             if (photo.status === 'Rejected' && photo.rejection_reason) {
                 rejectionDiv.style.display = 'block';
                 rejectionReason.textContent = photo.rejection_reason;
             } else {
                 rejectionDiv.style.display = 'none';
             }
             
             const modal = new bootstrap.Modal(document.getElementById('photoModal'));
             modal.show();
         }

        // Refresh photos
        function refreshPhotos() {
            loadPhotos();
        }

        // Open stats modal with detailed information
        function openStatsModal(status) {
            const photos = allPhotos.filter(photo => photo.status.toLowerCase() === status.toLowerCase());
            
            if (photos.length === 0) {
                showEmptyStatsModal(status);
                return;
            }

            const modalTitle = document.getElementById('statsModalTitle');
            const modalContent = document.getElementById('statsModalContent');
            
            // Set modal title
            modalTitle.textContent = `${status.charAt(0).toUpperCase() + status.slice(1)} Photos Details`;
            
            // Create modal content
            let content = `
                <div class="stats-summary">
                    <h4>${photos.length} ${status.charAt(0).toUpperCase() + status.slice(1)} Photo${photos.length !== 1 ? 's' : ''}</h4>
                    <p>Click on any photo below to view full details</p>
                </div>
                <div class="row">
            `;
            
            photos.forEach((photo, index) => {
                const statusClass = photo.status.toLowerCase();
                content += `
                    <div class="col-md-6 col-lg-4 mb-3">
                        <div class="stats-detail-item" onclick="openPhotoFromStats(${index}, '${status}')" style="cursor: pointer;">
                            <div class="stats-detail-species">${photo.species}</div>
                            <div class="stats-detail-meta">
                                <span class="stats-detail-date">${photo.uploaded_at}</span>
                                <span class="stats-detail-status ${statusClass}">${photo.status}</span>
                            </div>
                        </div>
                    </div>
                `;
            });
            
            content += '</div>';
            modalContent.innerHTML = content;
            
            // Show modal
            const modal = new bootstrap.Modal(document.getElementById('statsModal'));
            modal.show();
        }

        // Show empty stats modal
        function showEmptyStatsModal(status) {
            const modalTitle = document.getElementById('statsModalTitle');
            const modalContent = document.getElementById('statsModalContent');
            
            modalTitle.textContent = `${status.charAt(0).toUpperCase() + status.slice(1)} Photos`;
            
            modalContent.innerHTML = `
                <div class="text-center py-5">
                    <i class="fas fa-images fa-3x text-muted mb-3"></i>
                    <h5 class="text-muted">No ${status} photos</h5>
                    <p class="text-muted">You don't have any ${status} photos at the moment.</p>
                </div>
            `;
            
            const modal = new bootstrap.Modal(document.getElementById('statsModal'));
            modal.show();
        }

        // Open photo modal from stats modal
        function openPhotoFromStats(index, status) {
            const photos = allPhotos.filter(photo => photo.status.toLowerCase() === status.toLowerCase());
            if (photos[index]) {
                // Close stats modal first
                const statsModal = bootstrap.Modal.getInstance(document.getElementById('statsModal'));
                if (statsModal) {
                    statsModal.hide();
                }
                
                // Open photo modal
                setTimeout(() => {
                    openPhotoModal(photos[index]);
                }, 300);
            }
        }
    </script>
</body>
</html>
