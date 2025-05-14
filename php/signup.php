<?php
require_once 'connection.php';

// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

header('Content-Type: application/json');

try {
    if ($_SERVER["REQUEST_METHOD"] == "POST") {
        // Validate and sanitize inputs
        $username = isset($_POST["name"]) ? trim($_POST["name"]) : '';
        $email = isset($_POST["email"]) ? trim($_POST["email"]) : '';
        $address = isset($_POST["address"]) ? trim($_POST["address"]) : '';
        $password = isset($_POST["password"]) ? $_POST["password"] : '';

        // Validate inputs
        if (empty($username) || empty($email) || empty($address) || empty($password)) {
            throw new Exception('All fields are required');
        }

        // Validate email format
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            throw new Exception('Invalid email format');
        }

        // Check if email exists
        $check = $conn->prepare("SELECT user_id FROM users WHERE email = ?");
        if (!$check) {
            throw new Exception('Database prepare error: ' . $conn->error);
        }
        
        $check->bind_param("s", $email);
        if (!$check->execute()) {
            throw new Exception('Error checking email: ' . $check->error);
        }
        
        $check->store_result();
        if ($check->num_rows > 0) {
            throw new Exception('Email is already registered');
        }
        $check->close();

        // Hash password
        $hashed_password = password_hash($password, PASSWORD_DEFAULT);
        if ($hashed_password === false) {
            throw new Exception('Error hashing password');
        }

        // Insert new user
        $stmt = $conn->prepare("INSERT INTO users (name, email, address, password, role) VALUES (?, ?, ?, ?, 'customer')");
        if (!$stmt) {
            throw new Exception('Database prepare error: ' . $conn->error);
        }

        $stmt->bind_param("ssss", $username, $email, $address, $hashed_password);
        
        if (!$stmt->execute()) {
            throw new Exception('Error creating account: ' . $stmt->error);
        }

        echo json_encode([
            'status' => 'success',
            'message' => 'Registration successful! Please login.',
            'redirect' => '../html/login.html'
        ]);

        $stmt->close();
    } else {
        throw new Exception('Invalid request method');
    }
} catch (Exception $e) {
    error_log("Signup Error: " . $e->getMessage());
    echo json_encode([
        'status' => 'error',
        'message' => $e->getMessage()
    ]);
} finally {
    if (isset($conn)) {
        $conn->close();
    }
}
?>
