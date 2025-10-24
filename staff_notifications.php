<?php
session_start();
include 'includes/conn.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'staff') {
    header("Location: login.php");
    exit();
}

$staff_id = $_SESSION['user_id'];

// Fetch all notifications for this staff
$query = "SELECT * FROM notifications WHERE user_id = ? ORDER BY timestamp DESC";
$stmt = $conn->prepare($query);
$stmt->bind_param("i", $staff_id);
$stmt->execute();
$result = $stmt->get_result();
?> 

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Staff Notifications</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet"/>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet"/>
    <style>
        body {
            background-color: #6c63ff;
            font-family: Arial, sans-serif;
        }
        .container-fluid {
            padding-left: 0;
            padding-right: 0;
            overflow-x: hidden;
        }
        .wrapper {
            display: flex;
            align-items: flex-start;
        }
        .main-content {
            background: white;
            margin: 20px;
            margin-left: 312px;
            padding: 0 25px 25px 25px;
            border-radius: 10px;
            min-height: 600px;
            height: calc(100vh - 40px);
            overflow-y: auto;
            flex: 1;
            display: flex;
            flex-direction: column;
        }
        .staff-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            width: calc(100% + 50px);
            min-height: 80px;
            margin: 0 -25px 0 -25px;
            border-top-left-radius: 10px;
            border-top-right-radius: 10px;
            padding: 10px 25px 0 25px;
            position: sticky;
            top: 0;
            background: white;
            z-index: 1040;
        }
        .staff-header h2 {
            margin: 0;
            font-weight: bold;
        }
        .staff-profile {
            display: flex;
            align-items: center;
            position: relative;
            z-index: 1050;
        }
        
        .staff-profile .dropdown {
            position: relative;
            z-index: 1050;
        }
        .staff-profile img {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            margin-right: 10px;
        }
        
        /* Avatar Dropdown Button */
        .avatar-dropdown-btn {
            background: none;
            border: none;
            padding: 0;
            display: flex;
            align-items: center;
            transition: all 0.3s ease;
            cursor: pointer;
            position: relative;
            z-index: 1050;
        }
        
        .avatar-dropdown-btn:hover {
            transform: scale(1.05);
        }
        
        .avatar-dropdown-btn:focus {
            box-shadow: none;
        }
        
        .avatar-container {
            position: relative;
            display: inline-block;
        }
        
        .avatar-img {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            margin-right: 10px;
            transition: all 0.3s ease;
        }
        
        .dropdown-indicator {
            position: absolute;
            bottom: -2px;
            right: 8px;
            background: #6c63ff;
            color: white;
            width: 16px;
            height: 16px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 8px;
            border: 2px solid white;
        }
        
        .staff-name {
            margin-left: 5px;
            color: #333;
            text-decoration: none;
        }
        /* Modern Notification Cards */
        .notification-card {
            background: white;
            border: none;
            border-radius: 12px;
            margin-bottom: 16px;
            padding: 20px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.08);
            transition: all 0.3s ease;
            position: relative;
            overflow: hidden;
        }
        
        .notification-card::before {
            content: '';
            position: absolute;
            left: 0;
            top: 0;
            width: 4px;
            height: 100%;
            background: linear-gradient(135deg, #6c63ff, #5a52d5);
            transition: all 0.3s ease;
        }
        
        .notification-card.unread::before {
            background: linear-gradient(135deg, #ff6b6b, #ee5a52);
        }
        
        .notification-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(0,0,0,0.15);
        }
        
        .notification-card.unread {
            background: linear-gradient(135deg, #fff5f5, #ffffff);
            border-left: 4px solid #ff6b6b;
        }
        
        .notification-card.read {
            background: #fafbff;
            opacity: 0.9;
        }
        
        .notification-header {
            display: flex;
            align-items: flex-start;
            gap: 12px;
            margin-bottom: 12px;
        }
        
        .notification-icon {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 16px;
            color: white;
            flex-shrink: 0;
        }
        
        .notification-icon.upload {
            background: linear-gradient(135deg, #4ecdc4, #44a08d);
        }
        
        .notification-icon.request {
            background: linear-gradient(135deg, #ff9a9e, #fecfef);
        }
        
        .notification-icon.general {
            background: linear-gradient(135deg, #a8edea, #fed6e3);
        }
        
        .notification-content {
            flex: 1;
        }
        
        .notification-title {
            font-weight: 600;
            color: #2c3e50;
            margin: 0 0 4px 0;
            font-size: 15px;
            line-height: 1.4;
        }
        
        .notification-message {
            color: #5a6c7d;
            font-size: 14px;
            line-height: 1.5;
            margin: 0;
        }
        
        .notification-meta {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-top: 12px;
            padding-top: 12px;
            border-top: 1px solid #f0f2f5;
        }
        
        .notification-time {
            color: #8b9dc3;
            font-size: 12px;
            font-weight: 500;
        }
        
        .notification-actions {
            display: flex;
            gap: 8px;
            align-items: center;
        }
        
        .notification-status {
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 11px;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        
        .notification-status.unread {
            background: linear-gradient(135deg, #ff6b6b, #ee5a52);
            color: white;
        }
        
        .notification-status.read {
            background: #e9ecef;
            color: #6c757d;
        }
        
        .action-btn {
            width: 32px;
            height: 32px;
            border-radius: 50%;
            border: none;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 12px;
            transition: all 0.3s ease;
            cursor: pointer;
        }
        
        .action-btn.delete {
            background: #ffebee;
            color: #e53e3e;
        }
        
        .action-btn.delete:hover {
            background: #e53e3e;
            color: white;
            transform: scale(1.1);
        }
        
        .action-btn.mark-read {
            background: #e8f5e8;
            color: #38a169;
        }
        
        .action-btn.mark-read:hover {
            background: #38a169;
            color: white;
            transform: scale(1.1);
        }
        
        .notification-link {
            color: #6c63ff;
            cursor: pointer;
            text-decoration: none;
            transition: all 0.3s ease;
        }
        
        .notification-link:hover {
            color: #5a52d5;
            text-decoration: none;
        }
        
        .notification-card {
            cursor: pointer;
        }
        
        .notification-card .notification-actions {
            cursor: default;
        }
        
        .notification-card .action-btn {
            cursor: pointer;
        }
        
        .empty-state {
            text-align: center;
            padding: 60px 20px;
            color: #8b9dc3;
        }
        
        .empty-state i {
            font-size: 48px;
            margin-bottom: 16px;
            opacity: 0.5;
        }
        
        .empty-state h4 {
            margin-bottom: 8px;
            color: #5a6c7d;
        }
        
        .empty-state p {
            margin: 0;
            font-size: 14px;
        }
        
        /* Enhanced animations and effects */
        .notification-card {
            animation: slideInUp 0.3s ease-out;
        }
        
        @keyframes slideInUp {
            from {
                opacity: 0;
                transform: translateY(20px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
        
        .notification-card.processing {
            opacity: 0.6;
            pointer-events: none;
            position: relative;
        }
        
        .notification-card.processing::after {
            content: '';
            position: absolute;
            top: 50%;
            left: 50%;
            width: 20px;
            height: 20px;
            margin: -10px 0 0 -10px;
            border: 2px solid #f3f3f3;
            border-top: 2px solid #6c63ff;
            border-radius: 50%;
            animation: spin 1s linear infinite;
        }
        
        .notification-card.read-success {
            animation: readSuccess 0.5s ease-in-out;
        }
        
        @keyframes readSuccess {
            0% { transform: scale(1); }
            50% { transform: scale(1.02); }
            100% { transform: scale(1); }
        }
        
        /* Custom scrollbar for notifications container */
        .notifications-container::-webkit-scrollbar {
            width: 6px;
        }
        
        .notifications-container::-webkit-scrollbar-track {
            background: #f1f1f1;
            border-radius: 3px;
        }
        
        .notifications-container::-webkit-scrollbar-thumb {
            background: #c1c1c1;
            border-radius: 3px;
        }
        
        .notifications-container::-webkit-scrollbar-thumb:hover {
            background: #a8a8a8;
        }
        
        /* Responsive design */
        @media (max-width: 768px) {
            .notification-card {
                margin-bottom: 12px;
                padding: 16px;
            }
            
            .notification-header {
                gap: 10px;
            }
            
            .notification-icon {
                width: 36px;
                height: 36px;
                font-size: 14px;
            }
            
            .notification-meta {
                flex-direction: column;
                align-items: flex-start;
                gap: 8px;
            }
            
            .notification-actions {
                width: 100%;
                justify-content: space-between;
            }
        }

        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }
        
        /* Activity Logs Dropdown Styles */
        .activity-logs-dropdown {
            width: 350px;
            max-height: 400px;
            overflow-y: auto;
            padding: 0;
        }
        .activity-logs-header {
            padding: 12px 16px;
            background: #f8f9fa;
            border-bottom: 1px solid #e9ecef;
            font-weight: 600;
            color: #495057;
            position: sticky;
            top: 0;
            z-index: 1;
        }
        .activity-item {
            display: flex;
            align-items: flex-start;
            padding: 12px 16px;
            border-bottom: 1px solid #f5f5f5;
            transition: all 0.3s ease;
        }
        .activity-item:last-child {
            border-bottom: none;
        }
        .activity-item:hover {
            background-color: #fafbff;
        }
        .activity-avatar {
            position: relative;
            margin-right: 10px;
            flex-shrink: 0;
        }
        .activity-avatar .avatar-img {
            width: 30px;
            height: 30px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 600;
            font-size: 0.8rem;
            color: white;
        }
        .activity-icon {
            position: absolute;
            bottom: -2px;
            right: -2px;
            width: 14px;
            height: 14px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 7px;
            border: 2px solid white;
        }
        .activity-icon.icon-login { background: #28a745; color: white; }
        .activity-icon.icon-logout { background: #dc3545; color: white; }
        .activity-icon.icon-add { background: #17a2b8; color: white; }
        .activity-icon.icon-edit { background: #ffc107; color: white; }
        .activity-icon.icon-delete { background: #dc3545; color: white; }
        .activity-icon.icon-view { background: #6c63ff; color: white; }
        .activity-icon.icon-approve { background: #28a745; color: white; }
        .activity-icon.icon-default { background: #6c757d; color: white; }
        .activity-content {
            flex: 1;
        }
        .activity-text {
            margin: 0;
            font-size: 13px;
            line-height: 1.3;
            color: #333;
        }
        .activity-time {
            font-size: 11px;
            color: #6c757d;
            margin-top: 2px;
        }
    </style>
</head>
<body>
    <div class="container-fluid">
        <div class="wrapper">
            <!-- Sidebar -->
            <div class="sidebar">
                <?php include 'includes/staff_sidebar.php'; ?>
            </div>

            <!-- Main Content -->
            <div class="main-content">
                <div class="staff-header">
                    <h2>Notifications Management</h2>
                    <div class="staff-profile">
                        <!-- Avatar with Dropdown -->
                        <div class="dropdown">
                            <button class="btn btn-link avatar-dropdown-btn" type="button" data-bs-toggle="dropdown" aria-expanded="false" id="avatarDropdownToggle">
                                <div class="avatar-container">
                                    <img src="assets/default-avatar.png" alt="Staff Profile" class="avatar-img" onerror="this.src='data:image/svg+xml;base64,PHN2ZyB3aWR0aD0iNDAiIGhlaWdodD0iNDAiIHZpZXdCb3g9IjAgMCA0MCA0MCIgZmlsbD0ibm9uZSIgeG1sbnM9Imh0dHA6Ly93d3cudzMub3JnLzIwMDAvc3ZnIj4KPGNpcmNsZSBjeD0iMjAiIGN5PSIyMCIgcj0iMjAiIGZpbGw9IiM2YzYzZmYiLz4KPHN2ZyB4PSIxMCIgeT0iMTAiIHdpZHRoPSIyMCIgaGVpZ2h0PSIyMCIgdmlld0JveD0iMCAwIDI0IDI0IiBmaWxsPSJub25lIj4KPHBhdGggZD0iTTEyIDEyQzE0LjIwOTEgMTIgMTYgMTAuMjA5MSAxNiA4QzE2IDUuNzkwODYgMTQuMjA5MSA0IDEyIDRDOS43OTA4NiA0IDggNS43OTA4NiA4IDhDOCAxMC4yMDkxIDkuNzkwODYgMTIgMTIgMTJaIiBmaWxsPSJ3aGl0ZSIvPgo8cGF0aCBkPSJNMTIgMTRDOC42ODYyOSAxNCA2IDE2LjY4NjMgNiAyMFYyMkgxOFYyMEMxOCAxNi42ODYzIDE1LjMxMzcgMTQgMTIgMTRaIiBmaWxsPSJ3aGl0ZSIvPgo8L3N2Zz4KPC9zdmc+';">
                                    <div class="dropdown-indicator"><i class="fas fa-chevron-down"></i></div>
                                </div>
                            </button>
                            <ul class="dropdown-menu dropdown-menu-end" id="avatarDropdown" style="z-index: 1060;">
                                <li><a class="dropdown-item" href="#" id="viewActivityLogs"><i class="fas fa-history me-2"></i>View Activity Logs</a></li>
                                <li><hr class="dropdown-divider"></li>
                                <li><a class="dropdown-item" href="#" data-bs-toggle="modal" data-bs-target="#logoutModal"><i class="fas fa-sign-out-alt me-2"></i>Logout</a></li>
                            </ul>
                        </div>
                    </div>
                </div>

                <!-- Mark All as Read Button -->
                <div class="mt-3 mb-4 d-flex justify-content-end">
                    <button type="button" class="btn btn-primary" id="markAllAsReadBtn">
                        <i class="fas fa-check-double me-2"></i>Mark All as Read
                    </button>
                </div>

                <!-- Notifications List -->
                <?php if ($result->num_rows > 0): ?>
                    <div class="notifications-container">
                        <?php while ($notif = $result->fetch_assoc()): ?>
                            <?php
                            // Determine notification type and icon
                            $iconClass = 'general';
                            $iconSymbol = 'fas fa-bell';
                            
                            if (strpos($notif['message'], 'New pharmaceutical request from') !== false) {
                                $iconClass = 'request';
                                $iconSymbol = 'fas fa-pills';
                            } else if (strpos($notif['message'], 'uploaded new photos for') !== false || 
                                strpos($notif['message'], 'New photo uploaded for') !== false) {
                                $iconClass = 'upload';
                                $iconSymbol = 'fas fa-camera';
                            }
                            
                            // Extract title and message
                            $title = '';
                            $message = $notif['message'];
                            
                            if (strpos($notif['message'], 'uploaded new photos for') !== false) {
                                preg_match('/(.+?) uploaded new photos for (.+?) \((.+?)\)/', $notif['message'], $matches);
                                if ($matches) {
                                    $title = 'New Photo Upload';
                                    $message = $matches[1] . ' uploaded new photos for ' . $matches[2] . ' (' . $matches[3] . ')';
                                }
                            } else if (strpos($notif['message'], 'New pharmaceutical request from') !== false) {
                                $title = 'Pharmaceutical Request';
                            }
                            ?>
                            
                            <div class="notification-card <?= $notif['status'] === 'Unread' ? 'unread' : 'read' ?>"
                                 data-notification-id="<?= $notif['notification_id'] ?>">
                                
                                <div class="notification-header">
                                    <div class="notification-icon <?= $iconClass ?>">
                                        <i class="<?= $iconSymbol ?>"></i>
                                    </div>
                                    
                                    <div class="notification-content">
                                        <?php if ($title): ?>
                                            <h6 class="notification-title"><?= htmlspecialchars($title) ?></h6>
                                        <?php endif; ?>
                                        
                                        <div class="notification-message">
                                            <?php 
                                            echo htmlspecialchars($message);
                                            ?>
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="notification-meta">
                                    <div class="notification-time">
                                        <i class="fas fa-clock me-1"></i>
                                        <?= date('M d, Y H:i', strtotime($notif['timestamp'])) ?>
                                    </div>
                                    
                                    <div class="notification-actions">
                                        <span class="notification-status <?= strtolower($notif['status']) ?>">
                                            <?= ucfirst($notif['status']) ?>
                                        </span>
                                        
                                        <?php if ($notif['status'] === 'Unread'): ?>
                                            <button class="action-btn mark-read" 
                                                    onclick="markNotificationAsRead(<?= $notif['notification_id'] ?>)"
                                                    title="Mark as Read">
                                                <i class="fas fa-check"></i>
                                            </button>
                                        <?php endif; ?>
                                        
                                        <button class="action-btn delete delete-notification" 
                                                data-notification-id="<?= $notif['notification_id'] ?>"
                                                title="Delete Notification">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    </div>
                                </div>
                            </div>
                        <?php endwhile; ?>
                    </div>
                <?php else: ?>
                    <div class="empty-state">
                        <i class="fas fa-bell-slash"></i>
                        <h4>No Notifications</h4>
                        <p>You're all caught up! No new notifications at the moment.</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>


        // Function to show read success indicator
        function showReadSuccess(notificationItem) {
            // Add a temporary success class
            notificationItem.classList.add('read-success');
            
            // Remove the success class after animation
            setTimeout(() => {
                notificationItem.classList.remove('read-success');
            }, 2000);
        }





        // Add click handlers for all notification cards to navigate and mark as read
        document.querySelectorAll('.notification-card').forEach(card => {
            card.addEventListener('click', function(e) {
                // Don't trigger if clicking on action buttons or the actions area
                if (e.target.closest('.action-btn') || e.target.closest('.notification-actions')) {
                    return;
                }
                
                const notificationId = this.getAttribute('data-notification-id');
                const isUnread = this.classList.contains('unread');
                const message = this.querySelector('.notification-message').textContent;
                
                // Mark as read first
                if (isUnread) {
                    markNotificationAsRead(notificationId);
                }
                
                // Navigate based on notification type
                if (message.includes('New pharmaceutical request from')) {
                    // Navigate to pharmaceutical requests
                    window.location.href = 'staff_pharmaceutical_request.php';
                } else if (message.includes('uploaded new photos for') || message.includes('New photo uploaded for')) {
                    // Extract information from notification message for photo uploads
                    let url = 'staff_client_uploads.php?auto_open=1';
                    
                    if (message.includes('uploaded new photos for')) {
                        // New format: "jenny patricio uploaded new photos for Sheep (Livestock)"
                        const matches = message.match(/(.+?) uploaded new photos for (.+?) \((.+?)\)/);
                        if (matches) {
                            const client_name = matches[1] || '';
                            const species = matches[2] || '';
                            const animal_type = matches[3] || '';
                            
                            console.log('Photo upload notification detected:', { client_name, species, animal_type });
                            
                            url += '&type=animal&species=' + encodeURIComponent(species) + '&animal_type=' + encodeURIComponent(animal_type);
                            if (client_name) {
                                url += '&client_name=' + encodeURIComponent(client_name);
                            }
                            
                            console.log('Navigating to:', url);
                        }
                    } else {
                        // Old format: "New photo uploaded for Carabao (Livestock)"
                        const matches = message.match(/New photo uploaded for (.+?) \((.+?)\)/);
                        if (matches) {
                            const species = matches[1] || '';
                            const animal_type = matches[2] || '';
                            
                            console.log('Old format photo upload notification:', { species, animal_type });
                            
                            url += '&type=animal&species=' + encodeURIComponent(species) + '&animal_type=' + encodeURIComponent(animal_type);
                            
                            console.log('Navigating to:', url);
                        }
                    }
                    
                    // Add a small delay to ensure the notification is marked as read first
                    setTimeout(() => {
                        window.location.href = url;
                    }, 100);
                } else {
                    // Fallback: if no specific match, just navigate to the uploads page
                    console.log('Photo upload notification detected but no specific match, navigating to general uploads page');
                    setTimeout(() => {
                        window.location.href = 'staff_client_uploads.php?auto_open=1';
                    }, 100);
                }
                // For other notification types, just mark as read (no navigation)
            });
        });



        // Delete notification functionality
        document.querySelectorAll('.delete-notification').forEach(button => {
            button.addEventListener('click', function() {
                const notificationId = this.getAttribute('data-notification-id');
                
                // Create custom confirmation modal
                const confirmModal = document.createElement('div');
                confirmModal.className = 'modal fade';
                confirmModal.innerHTML = `
                    <div class="modal-dialog modal-dialog-centered modal-sm">
                        <div class="modal-content border-0 shadow">
                            <div class="modal-body text-center p-4">
                                <div class="mb-3">
                                    <i class="fas fa-exclamation-triangle text-warning" style="font-size: 2rem;"></i>
                                </div>
                                <h6 class="mb-3">Delete Notification</h6>
                                <p class="text-muted mb-4">Are you sure you want to delete this notification?</p>
                                <div class="d-flex gap-2 justify-content-center">
                                    <button type="button" class="btn btn-light px-4" data-bs-dismiss="modal">Cancel</button>
                                    <button type="button" class="btn btn-danger px-4 confirm-delete">Delete</button>
                                </div>
                            </div>
                        </div>
                    </div>
                `;
               
                document.body.appendChild(confirmModal);
                
                const modal = new bootstrap.Modal(confirmModal);
                modal.show();
                
                // Handle delete confirmation
                confirmModal.querySelector('.confirm-delete').addEventListener('click', function() {
                    modal.hide();
                    
                    fetch('delete_notification.php', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/x-www-form-urlencoded',
                        },
                        body: 'notification_id=' + notificationId
                    })
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            // Remove the notification from the DOM
                            button.closest('.notification-card').remove();
                            
                            // Show custom success modal
                            const successModal = document.createElement('div');
                            successModal.className = 'modal fade';
                            successModal.innerHTML = `
                                <div class="modal-dialog modal-dialog-centered modal-sm">
                                    <div class="modal-content border-0 shadow">
                                        <div class="modal-body text-center p-4">
                                            <div class="mb-3">
                                                <i class="fas fa-check-circle text-success" style="font-size: 2rem;"></i>
                                            </div>
                                            <h6 class="mb-3">Success</h6>
                                            <p class="text-muted mb-4">Notification deleted successfully!</p>
                                            <button type="button" class="btn btn-success px-4" data-bs-dismiss="modal">OK</button>
                                        </div>
                                    </div>
                                </div>
                            `;
                            
                            document.body.appendChild(successModal);
                            const successBootstrapModal = new bootstrap.Modal(successModal);
                            successBootstrapModal.show();
                            
                            successModal.addEventListener('hidden.bs.modal', function() {
                                document.body.removeChild(successModal);
                            });
                        } else {
                            // Show custom error modal
                            const errorModal = document.createElement('div');
                            errorModal.className = 'modal fade';
                            errorModal.innerHTML = `
                                <div class="modal-dialog modal-dialog-centered modal-sm">
                                    <div class="modal-content border-0 shadow">
                                        <div class="modal-body text-center p-4">
                                            <div class="mb-3">
                                                <i class="fas fa-exclamation-circle text-danger" style="font-size: 2rem;"></i>
                                            </div>
                                            <h6 class="mb-3">Error</h6>
                                            <p class="text-muted mb-4">Error deleting notification: ${data.error || 'Unknown error'}</p>
                                            <button type="button" class="btn btn-danger px-4" data-bs-dismiss="modal">OK</button>
                                        </div>
                                    </div>
                                </div>
                            `;
                            
                            document.body.appendChild(errorModal);
                            const errorBootstrapModal = new bootstrap.Modal(errorModal);
                            errorBootstrapModal.show();
                            
                            errorModal.addEventListener('hidden.bs.modal', function() {
                                document.body.removeChild(errorModal);
                            });
                        }
                    })
                    .catch(error => {
                        console.error('Error:', error);
                        
                        // Show custom error modal for catch block
                        const errorModal = document.createElement('div');
                        errorModal.className = 'modal fade';
                        errorModal.innerHTML = `
                            <div class="modal-dialog modal-dialog-centered modal-sm">
                                <div class="modal-content border-0 shadow">
                                    <div class="modal-body text-center p-4">
                                        <div class="mb-3">
                                            <i class="fas fa-exclamation-circle text-danger" style="font-size: 2rem;"></i>
                                        </div>
                                        <h6 class="mb-3">Error</h6>
                                        <p class="text-muted mb-4">Error deleting notification</p>
                                        <button type="button" class="btn btn-danger px-4" data-bs-dismiss="modal">OK</button>
                                    </div>
                                </div>
                            </div>
                        `;
                        
                        document.body.appendChild(errorModal);
                        const errorBootstrapModal = new bootstrap.Modal(errorModal);
                        errorBootstrapModal.show();
                        
                        errorModal.addEventListener('hidden.bs.modal', function() {
                            document.body.removeChild(errorModal);
                        });
                    });
                });
                
                // Clean up modal when hidden
                confirmModal.addEventListener('hidden.bs.modal', function() {
                    document.body.removeChild(confirmModal);
                });
            });
        });

        // Load activity logs function
        function loadActivityLogs() {
            fetch('get_staff_activity_logs.php')
                .then(response => response.text())
                .then(data => {
                    // Create the activity logs dropdown content
                    const dropdownContent = `
                        <div class="activity-logs-header">
                            <i class="fas fa-arrow-left me-2" id="backToMenu" style="cursor: pointer;"></i>Activity Logs
                        </div>
                        ${data}
                    `;
                    document.getElementById('avatarDropdown').innerHTML = dropdownContent;
                    document.getElementById('avatarDropdown').classList.add('activity-logs-dropdown');
                    
                    // Add back button functionality
                    const backButton = document.getElementById('backToMenu');
                    if (backButton) {
                        backButton.addEventListener('click', function(e) {
                            e.preventDefault();
                            e.stopPropagation();
                            // Restore original dropdown menu
                            document.getElementById('avatarDropdown').innerHTML = `
                                <li><a class="dropdown-item" href="#" id="viewActivityLogs"><i class="fas fa-history me-2"></i>View Activity Logs</a></li>
                                <li><hr class="dropdown-divider"></li>
                                <li><a class="dropdown-item" href="#" data-bs-toggle="modal" data-bs-target="#logoutModal"><i class="fas fa-sign-out-alt me-2"></i>Logout</a></li>
                            `;
                            document.getElementById('avatarDropdown').classList.remove('activity-logs-dropdown');
                            
                            // Re-attach the event listener for "View Activity Logs"
                            const newViewActivityLogsLink = document.getElementById('viewActivityLogs');
                            if (newViewActivityLogsLink) {
                                newViewActivityLogsLink.addEventListener('click', function(e) {
                                    e.preventDefault();
                                    e.stopPropagation();
                                    loadActivityLogs();
                                });
                            }
                        });
                    }
                })
                .catch(error => {
                    console.error('Error loading activity logs:', error);
                    document.getElementById('avatarDropdown').innerHTML = 
                        '<div class="activity-logs-header"><i class="fas fa-history me-2"></i>Activity Logs</div><div class="text-center text-muted p-3"><i class="fas fa-exclamation-triangle me-2"></i>Error loading activity logs</div>';
                });
        }

        // Initialize dropdown functionality
        document.addEventListener('DOMContentLoaded', function() {
            // Initialize Bootstrap dropdown
            const avatarDropdown = new bootstrap.Dropdown(document.getElementById('avatarDropdownToggle'));
            
            // Add multiple click event listeners for better reliability
            const avatarButton = document.getElementById('avatarDropdownToggle');
            const avatarContainer = document.querySelector('.avatar-container');
            const avatarImage = document.querySelector('.avatar-img');
            const dropdownMenu = document.getElementById('avatarDropdown');
            
            if (avatarButton) {
                avatarButton.addEventListener('click', function(e) {
                    e.preventDefault();
                    e.stopPropagation();
                    avatarDropdown.toggle();
                });
            }
            
            if (avatarContainer) {
                avatarContainer.addEventListener('click', function(e) {
                    e.preventDefault();
                    e.stopPropagation();
                    avatarDropdown.toggle();
                });
            }
            
            if (avatarImage) {
                avatarImage.addEventListener('click', function(e) {
                    e.preventDefault();
                    e.stopPropagation();
                    avatarDropdown.toggle();
                });
            }
            
            if (dropdownMenu) {
                dropdownMenu.addEventListener('click', function(e) {
                    e.stopPropagation();
                });
            }
        });

        // Load activity logs when "View Activity Logs" is clicked
        const viewActivityLogsLink = document.getElementById('viewActivityLogs');
        if (viewActivityLogsLink) {
            viewActivityLogsLink.addEventListener('click', function(e) {
                e.preventDefault();
                e.stopPropagation(); // Prevent dropdown from closing
                loadActivityLogs();
            });
        }

        // Mark All as Read functionality
        const markAllAsReadBtn = document.getElementById('markAllAsReadBtn');
        if (markAllAsReadBtn) {
            markAllAsReadBtn.addEventListener('click', function() {
                // Get all unread notification cards
                const unreadNotifications = document.querySelectorAll('.notification-card.unread');
                
                if (unreadNotifications.length === 0) {
                    // Show message if no unread notifications
                    // No toast when everything is already read
                    return;
                }
                
                // Disable button and show loading state
                markAllAsReadBtn.disabled = true;
                markAllAsReadBtn.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i>Marking as Read...';
                
                // Process each unread notification
                let processedCount = 0;
                const totalCount = unreadNotifications.length;
                
                unreadNotifications.forEach((notificationCard, index) => {
                    const notificationId = notificationCard.getAttribute('data-notification-id');
                    
                    // Add a small delay between requests to avoid overwhelming the server
                    setTimeout(() => {
                        markNotificationAsRead(notificationId, () => {
                            processedCount++;
                            
                            // Check if all notifications have been processed
                            if (processedCount === totalCount) {
                                // Re-enable button and show success message
                                markAllAsReadBtn.disabled = false;
                                markAllAsReadBtn.innerHTML = '<i class="fas fa-check-double me-2"></i>Mark All as Read';
                                showMessage(`Successfully marked ${totalCount} notification(s) as read!`, 'success');
                            }
                        });
                    }, index * 100); // 100ms delay between each request
                });
            });
        }

        // Enhanced markNotificationAsRead function with callback
        function markNotificationAsRead(notificationId, callback) {
            if (!notificationId) {
                if (callback) callback();
                return;
            }
            
            // Show processing state
            const notificationCard = document.querySelector(`[data-notification-id="${notificationId}"]`);
            if (notificationCard) {
                notificationCard.classList.add('processing');
            }
            
            fetch('mark_single_notification_read.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: 'notification_id=' + notificationId
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    // Update the notification card styling
                    if (notificationCard) {
                        notificationCard.classList.remove('processing');
                        notificationCard.classList.remove('unread');
                        notificationCard.classList.add('read');
                        
                        // Update the status badge
                        const statusBadge = notificationCard.querySelector('.notification-status');
                        if (statusBadge) {
                            statusBadge.textContent = 'Read';
                            statusBadge.className = 'notification-status read';
                        }
                        
                        // Remove the mark as read button
                        const markReadBtn = notificationCard.querySelector('.action-btn.mark-read');
                        if (markReadBtn) {
                            markReadBtn.remove();
                        }
                        
                        // Show success indicator
                        showReadSuccess(notificationCard);
                    }
                }
                
                if (callback) callback();
            })
            .catch(error => {
                console.error('Error marking notification as read:', error);
                // Remove processing state on error
                if (notificationCard) {
                    notificationCard.classList.remove('processing');
                }
                if (callback) callback();
            });
        }

        // Function to show messages
        function showMessage(message, type = 'info') {
            // Create message element
            const messageDiv = document.createElement('div');
            messageDiv.className = `alert alert-${type === 'success' ? 'success' : type === 'error' ? 'danger' : 'info'} alert-dismissible fade show`;
            messageDiv.style.position = 'fixed';
            messageDiv.style.top = '20px';
            messageDiv.style.right = '20px';
            messageDiv.style.zIndex = '9999';
            messageDiv.style.minWidth = '300px';
            messageDiv.innerHTML = `
                ${message}
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            `;
            
            // Add to page
            document.body.appendChild(messageDiv);
            
            // Auto remove after 3 seconds
            setTimeout(() => {
                if (messageDiv.parentNode) {
                    messageDiv.remove();
                }
            }, 3000);
        }
    </script>
    
    <!-- Logout Confirmation Modal -->
    <div class="modal fade" id="logoutModal" tabindex="-1" aria-labelledby="logoutModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="logoutModalLabel">Confirm Logout</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="d-flex align-items-center mb-3">
                        <i class="fas fa-sign-out-alt text-primary me-3" style="font-size: 2rem;"></i>
                        <div>
                            <h6 class="mb-1">Are you sure you want to logout?</h6>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <a href="logout.php" class="btn btn-primary">Logout</a>
                </div>
            </div>
        </div>
    </div>
</body>
</html>
