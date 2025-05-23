<?php
// Enable error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Start session if not already started
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

/**
 * Check if user is logged in
 * @return bool True if user is logged in, false otherwise
 */
function isLoggedIn() {
    return isset($_SESSION['user_id']) && isset($_SESSION['user_type']) && !empty($_SESSION['user_id']);
}

/**
 * Check if logged in user is an admin
 * @return bool True if user is an admin, false otherwise
 */
function isAdmin() {
    return isLoggedIn() && $_SESSION['user_type'] === 'admin';
}

/**
 * Check if logged in user is a judge
 * @return bool True if user is a judge, false otherwise
 */
function isJudge() {
    return isLoggedIn() && $_SESSION['user_type'] === 'judge';
}

/**
 * Redirect if user is not logged in
 * @param string $redirect_url URL to redirect to if not logged in
 */
function requireLogin($redirect_url = 'login.php') {
    if (!isLoggedIn()) {
        header("Location: $redirect_url");
        exit();
    }
}

/**
 * Redirect if user is not an admin
 * @param string $redirect_url URL to redirect to if not an admin
 */
function requireAdmin($redirect_url = 'login.php') {
    if (!isAdmin()) {
        header("Location: $redirect_url");
        exit();
    }
}

/**
 * Redirect if user is not a judge
 * @param string $redirect_url URL to redirect to if not a judge
 */
function requireJudge($redirect_url = 'login.php') {
    if (!isJudge()) {
        header("Location: $redirect_url");
        exit();
    }
}

/**
 * Log out the current user
 */
function logout() {
    // Unset all session variables
    $_SESSION = array();
    
    // Destroy the session cookie
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
    header("Location: login.php");
    exit();
}

/**
 * Debug function to show session information
 */
function debugSession() {
    if (isset($_GET['debug']) && $_GET['debug'] === '1') {
        echo "<div style='background: #f0f0f0; padding: 10px; margin: 10px; border-radius: 5px;'>";
        echo "<strong>Session Debug:</strong><br>";
        echo "Session Status: " . (session_status() === PHP_SESSION_ACTIVE ? 'Active' : 'Inactive') . "<br>";
        echo "Session ID: " . session_id() . "<br>";
        echo "Session Data: <pre>" . print_r($_SESSION, true) . "</pre>";
        echo "</div>";
    }
}
?>