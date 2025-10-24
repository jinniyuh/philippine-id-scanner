<?php
session_start();
include 'includes/conn.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];

if (isset($_POST['update_profile'])) {
    $name = $_POST['name'];
    $contact = $_POST['contact_number'];
    $username = $_POST['username'];

    // Server-side validation for contact number digits only
    if (!preg_match('/^\d*$/', $contact)) {
        $_SESSION['error'] = "Contact number must contain digits only.";
        header("Location: edit_profile.php");
        exit();
    }

    // Handle photo upload
    if (!empty($_FILES['profile_photo']['name'])) {
        $photoName = time() . '_' . basename($_FILES['profile_photo']['name']);
        $photoTmp = $_FILES['profile_photo']['tmp_name'];
        move_uploaded_file($photoTmp, 'uploads/' . $photoName);

        $conn->query("UPDATE users SET profile_photo='$photoName' WHERE user_id=$user_id");
    }

    $conn->query("UPDATE users SET name='$name', contact_number='$contact', username='$username' WHERE user_id=$user_id");
    $_SESSION['success'] = "Profile updated successfully.";
    header("Location: edit_profile.php");
    exit();
}

if (isset($_POST['delete_photo'])) {
    $result = $conn->query("SELECT profile_photo FROM users WHERE user_id=$user_id");
    $row = $result->fetch_assoc();
    if (!empty($row['profile_photo']) && file_exists('uploads/' . $row['profile_photo'])) {
        unlink('uploads/' . $row['profile_photo']);
    }

    $conn->query("UPDATE users SET profile_photo=NULL WHERE user_id=$user_id");
    $_SESSION['success'] = "Profile photo deleted.";
    header("Location: edit_profile.php");
    exit();
}

if (isset($_POST['change_password'])) {
    $new_pass = $_POST['new_password'];
    $hashed = password_hash($new_pass, PASSWORD_DEFAULT);
    $conn->query("UPDATE users SET password='$hashed' WHERE user_id=$user_id");
    $_SESSION['success'] = "Password updated successfully.";
    header("Location: edit_profile.php");
    exit();
}

$result = $conn->query("SELECT * FROM users WHERE user_id=$user_id");
$row = $result->fetch_assoc();
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <title>Edit Profile</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" />
  
  <!-- FontAwesome 5 CSS for icons -->
  <link
    href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css"
    rel="stylesheet"
  />

  <style>
    body, html {
      margin: 0; padding: 0; height: 100%;
      background: #f8f9fa;
      font-family: Arial, sans-serif;
    }
    .container-fluid {
      height: 100vh;
      display: flex;
      overflow: hidden;
      padding: 0;
    }
    .sidebar {
      width: 320px;
      background: #343a40;
      overflow-y: auto;
      height: 100vh;
      position: fixed;
      left: 0;
      top: 0;
      padding-top: 20px;
      color: #fff;
    }
    .main-content {
      margin-left: 320px;
      padding: 30px;
      width: calc(100% - 320px);
      overflow-y: auto;
      height: 100vh;
      box-sizing: border-box;
    }
    .profile-photo {
      width: 140px;
      height: 140px;
      border-radius: 50%;
      object-fit: cover;
      border: 2px solid #6c63ff;
      cursor: pointer;
      transition: box-shadow 0.3s ease;
    }
    .profile-photo:hover {
      box-shadow: 0 0 10px #6c63ff;
    }
    @media (max-width: 768px) {
      .sidebar {
        position: relative;
        width: 100%;
        height: auto;
      }
      .main-content {
        margin-left: 0;
        width: 100%;
        height: auto;
        padding: 20px;
      }
      .profile-photo {
        margin: 0 auto 15px auto;
        display: block;
      }
    }
    /* Password input group styling */
    .password-wrapper {
      position: relative;
    }
    .toggle-password {
      position: absolute;
      right: 12px;
      top: 50%;
      transform: translateY(-50%);
      cursor: pointer;
      user-select: none;
      color: #6c757d;
    }
  </style>
</head>
<body>
  <div class="container-fluid">
    <div class="sidebar">
      <?php include 'includes/admin_sidebar.php'; ?>
    </div>
    <div class="main-content">
      <h2>Edit Profile</h2>

      <?php if (isset($_SESSION['success'])): ?>
        <div class="alert alert-success"><?= $_SESSION['success']; unset($_SESSION['success']); ?></div>
      <?php endif; ?>
      <?php if (isset($_SESSION['error'])): ?>
        <div class="alert alert-danger"><?= $_SESSION['error']; unset($_SESSION['error']); ?></div>
      <?php endif; ?>

      <form method="POST" enctype="multipart/form-data" class="mb-4" id="profileForm">
        <div class="mb-3 text-center">
          <label for="profile_photo" title="Click to change photo" style="display:inline-block;cursor:pointer;">
            <img
              src="<?= $row['profile_photo'] ? 'uploads/' . $row['profile_photo'] : 'assets/default.jpg' ?>"
              alt="Profile Photo"
              class="profile-photo"
              id="previewPhoto"
            />
          </label>
          <input type="file" id="profile_photo" name="profile_photo" accept="image/*" onchange="previewImage(event)" hidden />
        </div>

        <div class="mb-3 text-center">
          <button type="submit" name="delete_photo" class="btn btn-outline-danger" onclick="return confirm('Delete profile photo?')">Delete Photo</button>
        </div>

        <div class="mb-3">
          <label for="name" class="form-label">Full Name</label>
          <input
            type="text"
            id="name"
            name="name"
            class="form-control"
            value="<?= htmlspecialchars($row['name']) ?>"
            required
          />
        </div>

        <div class="mb-3">
          <label for="contact_number" class="form-label">Contact Number</label>
          <input
            type="text"
            id="contact_number"
            name="contact_number"
            class="form-control"
            value="<?= htmlspecialchars($row['contact_number']) ?>"
            maxlength="15"
            required
          />
          <div id="contact-error" class="text-danger small" style="display:none;">
            Contact number must contain digits only.
          </div>
        </div>

        <div class="mb-3">
          <label for="username" class="form-label">Username</label>
          <input
            type="text"
            id="username"
            name="username"
            class="form-control"
            value="<?= htmlspecialchars($row['username']) ?>"
            required
          />
        </div>

        <button type="submit" name="update_profile" class="btn btn-primary">Save Changes</button>
      </form>

      <hr />

      <div>
        <h4>Change Password</h4>
        <form method="POST" id="passwordForm">
          <div class="mb-3 password-wrapper">
            <label for="new_password" class="form-label">New Password</label>
            <input
              type="password"
              id="new_password"
              name="new_password"
              class="form-control"
              required
              minlength="6"
              placeholder="At least 6 characters"
            />
            <span class="toggle-password" onclick="togglePassword()" title="Show/Hide Password">
              <i class="fas fa-eye" id="toggleIcon"></i>
            </span>
          </div>
          <button type="submit" name="change_password" class="btn btn-warning">Change Password</button>
        </form>
      </div>
    </div>
  </div>

  <script>
    function previewImage(event) {
      const reader = new FileReader();
      reader.onload = function () {
        document.getElementById('previewPhoto').src = reader.result;
      };
      reader.readAsDataURL(event.target.files[0]);
    }

    // Restrict contact_number input to digits only with live error message
    const contactInput = document.getElementById('contact_number');
    const contactError = document.getElementById('contact-error');

    contactInput.addEventListener('input', function(e) {
      if (/\D/.test(this.value)) {
        contactError.style.display = 'block';
        this.value = this.value.replace(/\D/g, '');
      } else {
        contactError.style.display = 'none';
      }
    });

    // Toggle password visibility
    function togglePassword() {
      const passwordInput = document.getElementById('new_password');
      const toggleIcon = document.getElementById('toggleIcon');
      if (passwordInput.type === 'password') {
        passwordInput.type = 'text';
        toggleIcon.classList.remove('fa-eye');
        toggleIcon.classList.add('fa-eye-slash');
      } else {
        passwordInput.type = 'password';
        toggleIcon.classList.remove('fa-eye-slash');
        toggleIcon.classList.add('fa-eye');
      }
    }
  </script>
</body>
</html>
