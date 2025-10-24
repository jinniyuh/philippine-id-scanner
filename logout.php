<?php
session_start();

// Unset all of the session variables.
$_SESSION = [];

// Destroy the session.
session_destroy();

// Optionally, clear the session cookie (if you use one)
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000,
        $params["path"], $params["domain"],
        $params["secure"], $params["httponly"]
    );
}

// Redirect the user to the login page (adjust the path as needed)
header("Location: login.php");
exit();
?>
