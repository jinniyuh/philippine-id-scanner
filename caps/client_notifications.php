<?php
session_start();
include 'includes/conn.php';

if (!isset($_SESSION['client_id']) || $_SESSION['role'] !== 'client') {
    header("Location: login.php");
    exit();
}

$client_id = $_SESSION['client_id'];

// Fetch notifications targeted to this client
$sql = "SELECT notification_id, user_id, message, timestamp, status, client_id
        FROM notifications
        WHERE client_id = ?
        ORDER BY timestamp DESC";
$stmt = $conn->prepare($sql);
if (!$stmt) {
    die("Prepare failed: " . $conn->error);
}
$stmt->bind_param("i", $client_id);
if (!$stmt->execute()) {
    die("Execute failed: " . $stmt->error);
}
$notifications = $stmt->get_result();
if (!$notifications) {
    die("Get result failed: " . $stmt->error);
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Notifications - Bago City Veterinary Office</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <style>
        body { 
            background-color: #6c63ff; 
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        .main-wrapper {
            background: white;
            margin-left: 320px;
            padding: 25px;
            border-radius: 10px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
            min-height: calc(100vh - 40px);
            position: fixed;
            top: 20px;
            left: 20px;
            right: 20px;
            bottom: 20px;
            overflow-y: auto;
            overflow-x: hidden;
            max-width: calc(100vw - 340px);
        }
        
        /* Tablet responsive styles */
        @media (max-width: 1024px) {
            .main-wrapper {
                margin-left: 320px;
                left: 20px;
                right: 20px;
                max-width: calc(100vw - 340px);
            }
        }
        
        /* Mobile responsive styles */
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
            
            .page-header {
                flex-direction: column;
                align-items: flex-start;
                gap: 10px;
            }
            
            .notification-item {
                padding: 15px;
            }
        }
        
        @media (max-width: 576px) {
            .main-wrapper {
                left: 10px;
                right: 10px;
                top: 100px;
                bottom: 10px;
                max-width: calc(100vw - 20px);
                padding: 15px;
            }
            
            .notification-item {
                padding: 12px;
            }
            
            .notification-message {
                font-size: 14px;
            }
        }
        
        @media (max-width: 480px) {
            .main-wrapper {
                left: 5px;
                right: 5px;
                top: 100px;
                bottom: 5px;
                max-width: calc(100vw - 10px);
                padding: 10px;
            }
            
            .notification-item {
                padding: 10px;
            }
            
            .notification-message {
                font-size: 13px;
            }
        }
        .page-header {
            border-bottom: 1px solid #e9ecef;
            padding-bottom: 15px;
            margin-bottom: 20px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .notification-item {
            padding: 18px;
            border-radius: 8px;
            margin-bottom: 12px;
            transition: all 0.3s ease;
            border-left: 4px solid transparent;
            box-shadow: 0 2px 5px rgba(0,0,0,0.05);
            cursor: pointer;
        }
        .notification-item:hover {
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
            transform: translateY(-2px);
            background-color: #f8f9fa;
        }
        .notification-content {
            flex: 1;
        }
        .notification-message {
            font-size: 15px;
            color: #333;
            margin-bottom: 6px;
            line-height: 1.5;
        }
        .notification-time {
            color: #6c757d;
            font-size: 0.85em;
            display: flex;
            align-items: center;
        }
        .notification-time i {
            margin-right: 5px;
            font-size: 0.9em;
        }
        .notification-badge {
            padding: 5px 10px;
            border-radius: 50px;
            font-weight: 500;
            font-size: 0.75rem;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        .unread {
            background-color: #f8f9ff;
            border-left: 4px solid #4e73df;
        }
        .notification-icon {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background-color: #e9ecef;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-right: 15px;
            color: #6c63ff;
        }
        .empty-state {
            text-align: center;
            padding: 40px 0;
            color: #6c757d;
        }
        .empty-state i {
            font-size: 4rem;
            margin-bottom: 20px;
            color: #dee2e6;
        }
        .empty-state p {
            font-size: 1.1rem;
            max-width: 300px;
            margin: 0 auto;
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
                <div class="page-header">
                    <h2>Notifications</h2>
                    <div>
                        <button class="btn btn-outline-primary" onclick="markAllAsRead()" title="Mark all as read">
                            <i class="fas fa-check-double"></i> Mark All as Read
                        </button>
                    </div>
                </div>
                
                <div class="notifications-container">
                <?php if ($notifications->num_rows > 0): ?>
                    <?php while ($notification = $notifications->fetch_assoc()): ?>
                        <div class="notification-item <?php echo (strtolower($notification['status']) !== 'read') ? 'unread' : ''; ?>"
                            onclick="handleNotificationClick(<?php echo $notification['notification_id']; ?>, '<?php echo htmlspecialchars($notification['message'], ENT_QUOTES); ?>')">
                            <div class="d-flex align-items-start">
                                <div class="notification-icon">
                                    <i class="fas fa-bell"></i>
                                </div>
                                <div class="notification-content">
                                    <div class="d-flex justify-content-between align-items-center mb-2">
                                        <h6 class="mb-0 fw-bold">Bago City Veterinary Office</h6>
                                        <?php if ($notification['status'] == 0): ?>
                                            <span class="notification-badge bg-primary">New</span>
                                        <?php endif; ?>
                                    </div>
                                    <p class="notification-message"><?php echo htmlspecialchars($notification['message']); ?></p>
                                    <div class="notification-time">
                                        <i class="far fa-clock"></i>
                                        <?php echo date('F j, Y g:i A', strtotime($notification['timestamp'])); ?>
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php endwhile; ?>
                <?php else: ?>
                    <div class="empty-state">
                        <i class="fas fa-bell-slash"></i>
                        <h4>No Notifications</h4>
                        <p>You don't have any notifications at the moment. We'll notify you when there's an update.</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>


    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function handleNotificationClick(notificationId, message) {
            // First mark as read
            markAsRead(notificationId);
            
            // Determine redirect URL based on notification message
            let redirectUrl = getNotificationRedirectUrl(message);
            
            // Redirect after a short delay to allow the read status to update
            setTimeout(() => {
                if (redirectUrl) {
                    window.location.href = redirectUrl;
                }
            }, 300);
        }
        
        function getNotificationRedirectUrl(message) {
            // Photo rejection notifications
            if (message.includes('Your animal photo has been rejected')) {
                return 'client_uploaded_photos.php';
            }
            
            // Pharmaceutical request notifications
            if (message.includes('New pharmaceutical request from') || 
                message.includes('pharmaceutical request')) {
                return 'client_pharmaceuticals_request.php';
            }
            
            // Photo upload notifications (when admin notifies about new photos)
            if (message.includes('uploaded new photos for') || 
                message.includes('New photo uploaded for')) {
                return 'client_uploaded_photos.php';
            }
            
            // Transaction notifications
            if (message.includes('transaction for medicine has been approved') ||
                message.includes('transaction for medicine has been rejected')) {
                return 'client_pharmaceuticals_request.php';
            }
            
            // Animal health notifications
            if (message.includes('animal') && (message.includes('health') || message.includes('vaccination'))) {
                return 'client_animals_owned.php';
            }
            
            // Default fallback - stay on notifications page
            return null;
        }
        
        function markAsRead(notificationId) {
            fetch('client_mark_notification_read.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: 'notification_id=' + notificationId
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    // Update the visual state immediately
                    const notificationElement = document.querySelector(`[onclick*="${notificationId}"]`);
                    if (notificationElement) {
                        notificationElement.classList.remove('unread');
                        const badge = notificationElement.querySelector('.notification-badge');
                        if (badge) {
                            badge.remove();
                        }
                    }
                }
            })
            .catch(error => {
                console.error('Error marking notification as read:', error);
            });
        }
        
        function markAllAsRead() {
            // Get all unread notification IDs
            const unreadItems = document.querySelectorAll('.notification-item.unread');
            
            if (unreadItems.length === 0) {
                // Show toast or alert that there are no unread notifications
                showToast('No unread notifications');
                return;
            }
            
            // Mark all notifications as read in a single request
            fetch('client_mark_all_read.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                }
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    showToast(`Marked ${data.count} notification(s) as read`);
                    setTimeout(() => {
                        location.reload();
                    }, 1000);
                } else {
                    showToast('Error: ' + (data.error || 'Unknown error'));
                }
            })
            .catch(error => {
                console.error('Error marking all notifications as read:', error);
                showToast('Error marking notifications as read');
            });
        }
        
        function showToast(message) {
            // Create toast element
            const toast = document.createElement('div');
            toast.className = 'toast align-items-center text-white bg-primary border-0 position-fixed';
            toast.style.bottom = '20px';
            toast.style.right = '20px';
            toast.style.zIndex = '1050';
            toast.setAttribute('role', 'alert');
            toast.setAttribute('aria-live', 'assertive');
            toast.setAttribute('aria-atomic', 'true');
            
            toast.innerHTML = `
                <div class="d-flex">
                    <div class="toast-body">
                        ${message}
                    </div>
                    <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast" aria-label="Close"></button>
                </div>
            `;
            
            document.body.appendChild(toast);
            
            const bsToast = new bootstrap.Toast(toast, { delay: 3000 });
            bsToast.show();
            
            // Remove toast after it's hidden
            toast.addEventListener('hidden.bs.toast', function() {
                document.body.removeChild(toast);
            });
        }
    </script>
</body>
</html>