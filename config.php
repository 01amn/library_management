<?php
// Database configuration
$host = "localhost";
$username = "root";
$password = "";
$database = "library_management";

// Create database connection
$conn = new mysqli($host, $username, $password, $database);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Start session if not already started
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Function to check if user is logged in
function isLoggedIn() {
    return isset($_SESSION['user_id']);
}

// Function to check if user is admin
function isAdmin() {
    return isset($_SESSION['user_type']) && $_SESSION['user_type'] == 'admin';
}

// Function to redirect to a page
function redirect($page) {
    header("Location: $page");
    exit;
}

// Function to display error message
function showError($message) {
    return "<div class='alert alert-danger'>$message</div>";
}

// Function to display success message
function showSuccess($message) {
    return "<div class='alert alert-success'>$message</div>";
}

// Function to display info message
function showInfo($message) {
    return "<div class='alert alert-info'>$message</div>";
}

// Function to sanitize input data
function sanitizeInput($data) {
    global $conn;
    return $conn->real_escape_string(trim($data));
}
?>