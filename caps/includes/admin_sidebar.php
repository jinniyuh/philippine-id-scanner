<?php
include 'session_validator.php';
requireActiveSession($conn, 'admin');
?>
<style>
    /* Sidebar styling */
    .sidebar {
      background-color: #6c63ff;
      width: 312px;
      min-width: 312px;
      max-width: 312px;
      color: #fff;
      padding: 2px 0px 10px 0;
      overflow-y: auto;
      z-index: 100;
      position: fixed;
      left: 0;
      top: 0;
      height: 100vh;
    }
    .sidebar .nav-link {
      font-size: 1.1rem;
      padding: 10px;
      margin-left: 0;
      border-radius: 8px;
      transition: background-color 0.3s ease;
      display: block;
      text-decoration: none;
      color: #fff;
      white-space: nowrap;
      padding-left: 23px;
    }
    .sidebar .nav-link:hover, 
    .sidebar .nav-link.active {
      background-color: rgba(255, 255, 255, 0.2);
      border-right: none;
    }
    .sidebar .nav-link.active {
      background-color: rgba(255, 255, 255, 0.3);
    }
    .sidebar .nav-link i {
      margin-right: 8px;
      margin-left: 0;
    }
    .sidebar .nav-link:hover {
      background-color: rgba(255, 255, 255, 0.2);
    }
</style>

<div class="sidebar">
    <div class="d-flex align-items-center mb-4">
        <img src="assets/vetlogo.png" alt="Logo" style="width: 55px; height: 55px; margin-left: 15px; margin-top: 30px;">
        <h5 class="ms-2 mb-0" style="padding-top: 30px;">Bago City Inventory Management System</h5>
    </div>
    <nav class="nav flex-column">
        <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'admin_dashboard.php' ? 'active' : ''; ?>" 
           href="admin_dashboard.php"><i class="fas fa-tachometer-alt"></i> Dashboard</a>
        <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'admin_clients.php' ? 'active' : ''; ?>" 
           href="admin_clients.php"><i class="fas fa-user-friends"></i> Clients Management</a>
        <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'admin_client_map.php' ? 'active' : ''; ?>" 
           href="admin_client_map.php"><i class="fas fa-map-marked-alt"></i> Client Map</a>
        <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'admin_livestock_poultry.php' ? 'active' : ''; ?>" 
           href="admin_livestock_poultry.php"><i class="fas fa-horse"></i> Livestock & Poultry</a>
        <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'admin_pharmaceuticals.php' ? 'active' : ''; ?>" 
           href="admin_pharmaceuticals.php"><i class="fas fa-pills"></i> Pharmaceuticals</a>
         <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'admin_pharmaceutical_request.php' ? 'active' : ''; ?>" 
           href="admin_pharmaceutical_request.php"><i class="fas fa-pills"></i> Pharmaceutical Requests</a>
        <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'admin_transactions.php' ? 'active' : ''; ?>" 
           href="admin_transactions.php"><i class="fas fa-exchange-alt"></i> Transactions</a>
        <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'admin_client_uploads.php' ? 'active' : ''; ?>" 
           href="admin_client_uploads.php"><i class="fas fa-cloud-upload-alt"></i> Client Uploads</a>
        <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'admin_notifications.php' ? 'active' : ''; ?>" 
            href="admin_notifications.php">
            <i class="fas fa-bell"></i> Notifications
            <span class="badge bg-danger ms-2" id="notif-badge" style="display: none;">0</span></a>
        <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'admin_compliance.php' ? 'active' : ''; ?>" 
           href="admin_compliance.php"><i class="fas fa-clipboard-check"></i> Compliance</a>
        <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'admin_reports.php' ? 'active' : ''; ?>" 
           href="admin_reports.php"><i class="fas fa-chart-bar"></i> Reports</a>
        <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'admin_profile.php' ? 'active' : ''; ?>" 
           href="admin_profile.php"><i class="fas fa-user-cog"></i> Profile</a>
    </nav>
</div>
<!-- <div class="modal fade" id="logoutModal" tabindex="-1" aria-labelledby="logoutModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content">
      <div class="modal-header">
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
</div> -->
<script>
function updateSidebarNotifBadge() {
    fetch('fetch_alerts.php')  // use this instead of fetch_notifications.php
        .then(response => response.text())
        .then(count => {
            const badge = document.getElementById('notif-badge');
            count = parseInt(count);
            if (count > 0) {
                badge.style.display = 'inline-block';
                badge.textContent = count;
            } else {
                badge.style.display = 'none';
            }
        });
}

setInterval(updateSidebarNotifBadge, 5000);
document.addEventListener('DOMContentLoaded', updateSidebarNotifBadge);
</script>

