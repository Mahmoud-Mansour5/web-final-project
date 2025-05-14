<?php
require_once 'connection.php';
session_start();

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: ../html/login.html");
    exit();
}

$user_id = $_SESSION['user_id'];

// Handle update request
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Validate input
    $name = filter_var($_POST['name'], FILTER_SANITIZE_STRING);
    $email = filter_var($_POST['email'], FILTER_SANITIZE_EMAIL);
    $address = filter_var($_POST['address'], FILTER_SANITIZE_STRING);

    if (!empty($name) && !empty($email) && !empty($address)) {
        $stmt = $conn->prepare("UPDATE users SET name=?, email=?, address=? WHERE user_id=?");
        $stmt->bind_param("sssi", $name, $email, $address, $user_id);
        
        if ($stmt->execute()) {
            $_SESSION['message'] = "Data updated successfully";
        } else {
            $_SESSION['error'] = "Error updating data";
        }
        $stmt->close();
    } else {
        $_SESSION['error'] = "All fields are required";
    }

    header("Location: ../html/user-dashboard.html");
    exit();
}

// Handle fetch request
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $stmt = $conn->prepare("SELECT name, email, address FROM users WHERE user_id=?");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    // Add additional user data
    $result['orders_count'] = getOrdersCount($conn, $user_id);
    $result['recent_orders'] = getRecentOrders($conn, $user_id);
    
    header('Content-Type: application/json');
    echo json_encode($result);
}

// Helper function to get orders count
function getOrdersCount($conn, $user_id) {
    $stmt = $conn->prepare("SELECT COUNT(*) as count FROM orders WHERE user_id = ?");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    return $result['count'];
}

// Helper function to get recent orders
function getRecentOrders($conn, $user_id) {
    $stmt = $conn->prepare("SELECT order_id, order_data, total_amount, status 
                           FROM orders 
                           WHERE user_id = ? 
                           ORDER BY order_data DESC 
                           LIMIT 5");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt->close();
    return $result;
}

$conn->close();
?>
