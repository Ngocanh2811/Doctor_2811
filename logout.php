<?php
// logout.php

// Display errors while debugging (remove these two lines after stable)
ini_set('display_errors', 1);
error_reporting(E_ALL);

session_start();
// Clear all session data
$_SESSION = [];

// Delete session cookie if any
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000,
        $params["path"], $params["domain"],
        $params["secure"], $params["httponly"]
    );
}

// Destroy the session
session_destroy();

// Redirect to login page
header("Location: logindoctor.php");
exit;
