<?php
session_start();
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Database configuration - UPDATE THESE!
$host = "localhost";
$username = "root"; // Usually 'root' for XAMPP/WAMP
$password = ""; // Usually empty for XAMPP/WAMP
$database = "lesopo"; // Your database name

// Database connection
$conn = new mysqli($host, $username, $password, $database);
if ($conn->connect_error) {
    die("Database connection failed: " . $conn->connect_error);
}

$message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = $_POST['email'] ?? '';
    $pass = $_POST['password'] ?? '';
    
    // Test with hardcoded credentials first
    if ($email === 'test@example.com' && $pass === 'password') {
        $_SESSION['user'] = [
            'id' => 1,
            'name' => 'Test User',
            'email' => 'test@example.com',
            'role' => 'teacher'
        ];
        header('Location: dashboard.php');
        exit;
    } else {
        $message = 'Email au password si sahihi.';
    }
}
?>