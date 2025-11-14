<?php
require_once 'config.php';
require_once 'functions.php';

// Check if user is logged in
function isLoggedIn() {
    return isset($_SESSION['user']);
}

// Redirect if not logged in
function requireLogin() {
    if (!isLoggedIn()) {
        header('Location: ../index.php');
        exit;
    }
}

// Get current user
function getCurrentUser() {
    return $_SESSION['user'] ?? null;
}
?>