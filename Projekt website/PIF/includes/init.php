<?php
// auth.php - Authentication helper functions

function requireLogin() {
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    
    if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
        // Store the page they were trying to access
        $_SESSION['redirect_url'] = $_SERVER['REQUEST_URI'];
        header('Location: ../auth/login.php');  // Changed from 'login.php'
        exit;
    }
    
    // Ensure username is set in both formats for backward compatibility
    if (isset($_SESSION['username']) && !isset($_SESSION['pk_username'])) {
        $_SESSION['pk_username'] = $_SESSION['username'];
    }
    if (isset($_SESSION['pk_username']) && !isset($_SESSION['username'])) {
        $_SESSION['username'] = $_SESSION['pk_username'];
    }
}

function getCurrentUser() {
    return $_SESSION['pk_username'] ?? $_SESSION['username'] ?? null;
}

function isAdmin() {
    return isset($_SESSION['role']) && $_SESSION['role'] === 'Admin';
}