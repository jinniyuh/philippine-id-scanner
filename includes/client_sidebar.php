<?php
// Session validation is handled in the main file

// Get notification count for the client
$client_id = $_SESSION['client_id'];
$user_query = $conn->prepare("SELECT user_id FROM users WHERE name = (SELECT full_name FROM clients WHERE client_id = ?) AND role = 'client'");
if ($user_query) {
    $user_query->bind_param("i", $client_id);
    $user_query->execute();
    $user_result = $user_query->get_result();
    $user_data = $user_result->fetch_assoc();
    
    if ($user_data) {
        $user_id = $user_data['user_id'];
        // Count unread notifications for both user_id and client_id
        $notif_query = $conn->prepare("SELECT COUNT(*) as unread_count FROM notifications WHERE (user_id = ? OR client_id = ?) AND (status = 'Unread' OR status = 'unread' OR status = 0)");
        $notif_query->bind_param("ii", $user_id, $client_id);
        $notif_query->execute();
        $notif_result = $notif_query->get_result();
        $unread_data = $notif_result->fetch_assoc();
        $unread_count = $unread_data['unread_count'];
    } else {
        // If no user found, still check for notifications by client_id
        $notif_query = $conn->prepare("SELECT COUNT(*) as unread_count FROM notifications WHERE client_id = ? AND (status = 'Unread' OR status = 'unread' OR status = 0)");
        $notif_query->bind_param("i", $client_id);
        $notif_query->execute();
        $notif_result = $notif_query->get_result();
        $unread_data = $notif_result->fetch_assoc();
        $unread_count = $unread_data['unread_count'];
    }
} else {
    $unread_count = 0;
}
?>
<style>
    .sidebar {
        background-color: #6c63ff;
        height: 100vh;
        padding: 20px 0 20px 20px;
        color: white;
        width: 300px;
        position: fixed;
        left: 0;
        top: 0;
        z-index: 1000;
        transition: transform 0.3s ease-in-out;
        overflow-y: auto;
    }
    
    /* Mobile responsive styles */
    @media screen and (max-width: 768px) {
        .sidebar {
            transform: translateX(-100%);
            width: 280px;
        }
        
        .sidebar.show {
            transform: translateX(0);
        }
        
        .main-content {
            margin-left: 0 !important;
        }
        
        .hamburger-menu {
            display: block !important;
            visibility: visible !important;
            opacity: 1 !important;
            top: 20px !important;
            left: 20px !important;
            pointer-events: auto !important;
        }
    }
    
    @media (min-width: 769px) {
        .sidebar {
            transform: translateX(0);
        }
        
        .hamburger-menu {
            display: none !important;
        }
    }
    
    /* Hamburger menu button */
    .hamburger-menu {
        position: fixed;
        top: 20px;
        left: 20px;
        z-index: 1001;
        background: #6c63ff;
        border: none;
        color: white;
        padding: 12px;
        border-radius: 8px;
        cursor: pointer;
        box-shadow: 0 2px 10px rgba(0,0,0,0.2);
        transition: all 0.3s ease;
        display: block; /* Temporarily visible for testing */
        visibility: visible;
        opacity: 1;
        pointer-events: auto;
    }
    
    .hamburger-menu:hover {
        background: #5a52d5;
        transform: scale(1.05);
    }
    
    .hamburger-icon {
        width: 24px;
        height: 24px;
        display: flex;
        flex-direction: column;
        justify-content: space-around;
    }
    
    .hamburger-line {
        width: 100%;
        height: 3px;
        background: white;
        border-radius: 2px;
        transition: all 0.3s ease;
    }
    
    .hamburger-menu.active .hamburger-line:nth-child(1) {
        transform: rotate(45deg) translate(6px, 6px);
    }
    
    .hamburger-menu.active .hamburger-line:nth-child(2) {
        opacity: 0;
    }
    
    .hamburger-menu.active .hamburger-line:nth-child(3) {
        transform: rotate(-45deg) translate(6px, -6px);
    }
    
    /* Overlay for mobile */
    .sidebar-overlay {
        position: fixed;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        background: rgba(0, 0, 0, 0.5);
        z-index: 999;
        opacity: 0;
        visibility: hidden;
        transition: all 0.3s ease;
    }
    
    .sidebar-overlay.show {
        opacity: 1;
        visibility: visible;
    }
    
    .sidebar .nav-link {
        color: #fff;
        padding: 12px 15px;
        margin-bottom: 5px;
        border-radius: 8px !important;
        transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        margin-right: 20px;
        position: relative;
        overflow: hidden;
    }

    .sidebar .nav-link:hover {
        background-color: rgba(255, 255, 255, 0.15);
        color: #fff;
        transform: translateX(8px) scale(1.02);
        box-shadow: 0 4px 12px rgba(255, 255, 255, 0.1);
    }

    .sidebar .nav-link.active {
        background-color: rgba(255, 255, 255, 0.25);
        transform: translateX(5px);
        box-shadow: 0 2px 8px rgba(255, 255, 255, 0.15);
    }

    .sidebar .nav-link i {
        margin-right: 20px;
        width: 20px;
        transition: transform 0.3s ease;
    }
    
    .sidebar .nav-link:hover i {
        transform: scale(1.1);
    }
    
    .sidebar .nav-link .badge {
        font-size: 0.75rem;
        padding: 0.25rem 0.5rem;
        border-radius: 50%;
        position: absolute;
        right: 10px;
        top: 50%;
        transform: translateY(-50%);
        animation: pulse 1.5s infinite;
    }
    
    @keyframes pulse {
        0% { transform: translateY(-50%) scale(1); }
        50% { transform: translateY(-50%) scale(1.1); }
        100% { transform: translateY(-50%) scale(1); }
    }
    

</style>

<!-- Hamburger Menu Button -->
<button class="hamburger-menu" id="hamburgerMenu" onclick="toggleSidebar()">
    <div class="hamburger-icon">
        <div class="hamburger-line"></div>
        <div class="hamburger-line"></div>
        <div class="hamburger-line"></div>
    </div>
</button>

<!-- Sidebar Overlay -->
<div class="sidebar-overlay" id="sidebarOverlay" onclick="closeSidebar()"></div>

<div class="sidebar" id="sidebar">
    <div class="d-flex align-items-center mb-4">
        <img src="bcvo.png" alt="Logo" style="width: 50px; height: 50px;">
        <h5 class="ms-2 mb-0">Bago City Inventory Management System</h5>
    </div>
    <nav class="nav flex-column">
        <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'client_dashboard.php' ? 'active' : ''; ?>" 
           href="client_dashboard.php"><i class="fas fa-dashboard"></i> Dashboard</a>
        <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'client_animals_owned.php' ? 'active' : ''; ?>" 
           href="client_animals_owned.php"><i class="fas fa-paw"></i> Animals Owned</a>
        <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'client_pharmaceuticals_request.php' ? 'active' : ''; ?>" 
           href="client_pharmaceuticals_request.php"><i class="fas fa-pills"></i> Request</a>
        <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'client_request_history.php' ? 'active' : ''; ?>" 
            href="client_request_history.php"><i class="fas fa-history"></i> Request History</a>

        <a href="client_notifications.php" class="nav-link">
            <i class="fas fa-bell"></i>
            <span>Notifications</span>
        </a>
        <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'client_uploaded_photos.php' ? 'active' : ''; ?>" 
           href="client_uploaded_photos.php"><i class="fas fa-images"></i> Uploaded Photos</a>
        <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'client_account_settings.php' ? 'active' : ''; ?>" 
           href="client_account_settings.php"><i class="fas fa-user-cog"></i> Account Settings</a>
        <a class="nav-link" href="#" data-bs-toggle="modal" data-bs-target="#logoutModal">
    <i class="fas fa-sign-out-alt"></i> Logout
</a>
    </nav>
</div>


<!-- Logout Confirmation Modal -->
<div class="modal fade" id="logoutModal" tabindex="-1" aria-labelledby="logoutModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content">
      <div class="modal-header" style="background-color: #6c63ff; color: #fff;">
        <h5 class="modal-title" id="logoutModalLabel">Confirm Logout</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body">
        Are you sure you want to log out?
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
        <a href="logout.php" class="btn btn-danger">Logout</a>
      </div>
    </div>
  </div>
</div>

<script>
function toggleSidebar() {
    const sidebar = document.getElementById('sidebar');
    const overlay = document.getElementById('sidebarOverlay');
    const hamburger = document.getElementById('hamburgerMenu');
    
    sidebar.classList.toggle('show');
    overlay.classList.toggle('show');
    hamburger.classList.toggle('active');
}

function closeSidebar() {
    const sidebar = document.getElementById('sidebar');
    const overlay = document.getElementById('sidebarOverlay');
    const hamburger = document.getElementById('hamburgerMenu');
    
    sidebar.classList.remove('show');
    overlay.classList.remove('show');
    hamburger.classList.remove('active');
}

// Close sidebar when clicking on nav links (mobile)
document.addEventListener('DOMContentLoaded', function() {
    const navLinks = document.querySelectorAll('.sidebar .nav-link');
    navLinks.forEach(link => {
        link.addEventListener('click', function() {
            // Only close on mobile devices
            if (window.innerWidth <= 768) {
                closeSidebar();
            }
        });
    });
    
    // Close sidebar when window is resized to desktop
    window.addEventListener('resize', function() {
        if (window.innerWidth > 768) {
            closeSidebar();
        }
    });
});
</script>
