<?php

// Prevent back-button caching
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Cache-Control: post-check=0, pre-check=0", false);
header("Pragma: no-cache");

// 1. Start session normally
if (session_status() === PHP_SESSION_NONE) {
    // We removed 'session.cookie_domain' because it's no longer needed for subdirectories
    // 10 hour cookie to reduce need to login again during school day
    ini_set('session.cookie_lifetime', 36000); 
    ini_set('session.gc_maxlifetime', 36000); 
    session_start();
}

/**
 * Global Login Check: 
 * If the user_id is not set, they are redirected to the root login page.
 */
if (!isset($_SESSION['user_id'])) {
    // Keeping the Absolute URL is still the best practice to prevent subdirectory appending
    header("Location: https://admin.pio-students.net/adminLogin.php");
    exit;
}

/**
 * Admin Protection Function
 */
function restrictToAdmin() {
    $allowedRoles = ['admin', 'dataEntry'];

    if (!isset($_SESSION['role']) || !in_array($_SESSION['role'], $allowedRoles)) {
        header("Location: https://admin.pio-students.net/adminLogin.php?error=unauthorized");
        exit;
    }
}
?>