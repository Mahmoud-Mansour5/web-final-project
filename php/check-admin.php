<?php
require_once 'connection.php';
session_start();

header('Content-Type: application/json');

if (!isset($_SESSION['user_id']) || !isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'admin') {
    echo json_encode([
        'isAdmin' => false,
        'message' => 'Unauthorized access'
    ]);
    exit;
}

echo json_encode([
    'isAdmin' => true,
    'name' => $_SESSION['user_name'] ?? 'Admin'
]);
?> 