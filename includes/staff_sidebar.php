<div class="d-flex align-items-center mb-4">
    <img src="bcvo.png" alt="Logo" style="width: 55px; height: 55px; margin-left: 15px; margin-top: 40px;">
    <h5 class="ms-2 mb-0" style="padding-top: 40px;">Bago City Inventory Management System</h5>
</div>

<nav class="nav flex-column">
    <?php
    // Get current page filename
    $currentPage = basename($_SERVER['PHP_SELF']);
    ?>
    <style>
        /* Sidebar styling */
        .sidebar {
            background-color: #6c63ff;
            width: 312px;
            min-width: 312px;
            max-width: 312px;
            color: #fff;
            padding: 2px 0px 20px 0;
            overflow-y: auto;
            z-index: 10;
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

    <a class="nav-link <?php echo ($currentPage == 'staff_dashboard.php') ? 'active' : ''; ?>" href="staff_dashboard.php"><i class="fas fa-dashboard"></i> Dashboard</a>
    <a class="nav-link <?php echo ($currentPage == 'staff_clients.php') ? 'active' : ''; ?>" href="staff_clients.php"><i class="fas fa-users"></i> Clients Management</a>
    <a class="nav-link <?php echo ($currentPage == 'staff_livestock_poultry.php') ? 'active' : ''; ?>" href="staff_livestock_poultry.php"><i class="fas fa-paw"></i> Livestock & Poultry</a>
    <a class="nav-link <?php echo ($currentPage == 'staff_compliance.php') ? 'active' : ''; ?>" href="staff_compliance.php"><i class="fas fa-check-circle"></i> Compliance</a>
    <a class="nav-link <?php echo ($currentPage == 'staff_client_uploads.php') ? 'active' : ''; ?>" href="staff_client_uploads.php"><i class="fas fa-upload"></i> Client Uploads</a>
    <a class="nav-link <?php echo ($currentPage == 'staff_pharmaceuticals.php') ? 'active' : ''; ?>" href="staff_pharmaceuticals.php"><i class="fas fa-pills"></i> Pharmaceuticals</a>
    <a class="nav-link <?php echo ($currentPage == 'staff_pharmaceutical_request.php') ? 'active' : ''; ?>" href="staff_pharmaceutical_request.php"><i class="fas fa-clipboard-list"></i> Pharma Requests</a>
    <a class="nav-link <?php echo ($currentPage == 'staff_transactions.php') ? 'active' : ''; ?>" href="staff_transactions.php"><i class="fas fa-exchange-alt"></i> Transactions</a>
    <a class="nav-link <?php echo ($currentPage == 'staff_reports.php') ? 'active' : ''; ?>" href="staff_reports.php"><i class="fas fa-chart-bar"></i> Reports & Analytics</a>
    <a class="nav-link <?php echo ($currentPage == 'staff_notifications.php') ? 'active' : ''; ?>" href="staff_notifications.php"><i class="fas fa-bell"></i> Notifications</a>
    <a class="nav-link <?php echo ($currentPage == 'staff_profile.php') ? 'active' : ''; ?>" href="staff_profile.php"><i class="fas fa-user"></i> Profile</a>
  </nav>


<!-- Bootstrap JS Bundle (ensure it's included before </body>) -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
