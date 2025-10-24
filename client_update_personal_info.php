<?php
session_start();
include 'includes/conn.php';
include 'includes/geotagging_helper.php';

// Set headers for JSON response
header('Content-Type: application/json');

// Enable error reporting but don't display errors
error_reporting(E_ALL);
ini_set('display_errors', 0);

if (!isset($_SESSION['client_id']) || $_SESSION['role'] !== 'client') {
    echo json_encode(['success' => false, 'error' => 'Unauthorized access']);
    exit();
}

$client_id = $_SESSION['client_id'];

// Get form data
$full_name = trim($_POST['full_name'] ?? '');
$username = trim($_POST['username'] ?? '');
$contact_number = trim($_POST['contact_number'] ?? '');
$barangay = trim($_POST['barangay'] ?? '');
$latitude = trim($_POST['latitude'] ?? '');
$longitude = trim($_POST['longitude'] ?? '');

// Validation functions
function validateFullName($fullName) {
    $nameParts = explode(' ', trim($fullName));
    if (count($nameParts) < 2) {
        return 'Full name must contain at least first name and last name';
    }
    foreach ($nameParts as $part) {
        if (strlen($part) < 2) {
            return 'Each name part must be at least 2 characters long';
        }
    }
    return null;
}

function validateContactNumber($contactNumber) {
    if (!preg_match('/^09\d{9}$/', $contactNumber)) {
        return 'Contact number must start with 09 and be exactly 11 digits';
    }
    return null;
}

function validateUsername($username) {
    if (strlen($username) < 3) {
        return 'Username must be at least 3 characters long';
    }
    if (!preg_match('/^[a-zA-Z0-9_]+$/', $username)) {
        return 'Username can only contain letters, numbers, and underscores';
    }
    return null;
}

// Validate input
$errors = [];

// Validate full name
$fullNameError = validateFullName($full_name);
if ($fullNameError) {
    $errors[] = $fullNameError;
}

// Validate username
$usernameError = validateUsername($username);
if ($usernameError) {
    $errors[] = $usernameError;
}

// Validate contact number
$contactError = validateContactNumber($contact_number);
if ($contactError) {
    $errors[] = $contactError;
}

// Validate barangay
if (empty($barangay)) {
    $errors[] = 'Please select a barangay';
}

// If there are validation errors, return them
if (!empty($errors)) {
    echo json_encode(['success' => false, 'error' => implode(', ', $errors)]);
    exit();
}

// Check for duplicate full name (excluding current user)
$stmt = $conn->prepare("SELECT client_id FROM clients WHERE full_name = ? AND client_id != ?");
$stmt->bind_param("si", $full_name, $client_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows > 0) {
    echo json_encode(['success' => false, 'error' => 'Full name already exists. Please choose a different name.']);
    exit();
}
$stmt->close();

// Check for duplicate username (excluding current user)
$stmt = $conn->prepare("SELECT client_id FROM clients WHERE username = ? AND client_id != ?");
$stmt->bind_param("si", $username, $client_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows > 0) {
    echo json_encode(['success' => false, 'error' => 'Username already exists. Please choose a different username.']);
    exit();
}
$stmt->close();

// Check for duplicate contact number (excluding current user)
$stmt = $conn->prepare("SELECT client_id FROM clients WHERE contact_number = ? AND client_id != ?");
$stmt->bind_param("si", $contact_number, $client_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows > 0) {
    echo json_encode(['success' => false, 'error' => 'Contact number already exists. Please use a different number.']);
    exit();
}
$stmt->close();

// Process coordinates - only save if client has disseminated animals
$final_latitude = null;
$final_longitude = null;

// Check if client has disseminated animals (eligible for geotagging)
if (!isGeotaggingAllowed($conn, $client_id)) {
    // Client doesn't have disseminated animals, don't save coordinates
    $final_latitude = null;
    $final_longitude = null;
} else if (!empty($latitude) && !empty($longitude) && is_numeric($latitude) && is_numeric($longitude)) {
    // Client has disseminated animals, save coordinates
    $final_latitude = floatval($latitude);
    $final_longitude = floatval($longitude);
}

// Update the client information
$stmt = $conn->prepare("UPDATE clients SET full_name = ?, username = ?, contact_number = ?, barangay = ?, latitude = ?, longitude = ? WHERE client_id = ?");
if (!$stmt) {
    echo json_encode(['success' => false, 'error' => 'Database prepare failed: ' . $conn->error]);
    exit();
}

$stmt->bind_param("ssssssi", $full_name, $username, $contact_number, $barangay, $final_latitude, $final_longitude, $client_id);

if ($stmt->execute()) {
    echo json_encode(['success' => true, 'message' => 'Personal information updated successfully']);
} else {
    echo json_encode(['success' => false, 'error' => 'Failed to update information: ' . $stmt->error]);
}

$stmt->close();
$conn->close();
?>