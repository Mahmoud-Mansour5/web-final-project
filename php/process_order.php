<?php
require_once 'connection.php';
session_start();

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['status' => 'error', 'message' => 'Please login first']);
    exit();
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['status' => 'error', 'message' => 'Invalid request method']);
    exit();
}

try {
    // Get order data from POST request
    $cart_items = json_decode($_POST['cart_items'], true);
    $total_amount = floatval($_POST['total_amount']);
    $shipping_address = isset($_POST['shipping_address']) ? trim($_POST['shipping_address']) : '';
    $user_id = $_SESSION['user_id'];
    
    if (empty($cart_items)) {
        throw new Exception('Shopping cart is empty');
    }

    if (empty($shipping_address)) {
        throw new Exception('Shipping address is required');
    }

    // Start transaction
    $conn->begin_transaction();

    // Insert order into orders table
    $order_stmt = $conn->prepare("INSERT INTO orders (user_id, total_amount, shipping_address, status, order_data) VALUES (?, ?, ?, 'processing', NOW())");
    $order_stmt->bind_param("ids", $user_id, $total_amount, $shipping_address);
    
    if (!$order_stmt->execute()) {
        throw new Exception('Failed to create order');
    }
    
    $order_id = $conn->insert_id;

    // Check and update stock for each item
    foreach ($cart_items as $item) {
        // Verify stock availability
        $stock_check = $conn->prepare("SELECT in_stock FROM store WHERE product_id = ? FOR UPDATE");
        $stock_check->bind_param("i", $item['id']);
        $stock_check->execute();
        $result = $stock_check->get_result();
        
        if ($result->num_rows === 0) {
            throw new Exception('Product not found: ' . $item['name']);
        }
        
        $current_stock = $result->fetch_assoc()['in_stock'];
        
        if ($current_stock < $item['quantity']) {
            throw new Exception('Insufficient stock for: ' . $item['name']);
        }
        
        // Update stock
        $new_stock = $current_stock - $item['quantity'];
        $update_stock = $conn->prepare("UPDATE store SET in_stock = ? WHERE product_id = ?");
        $update_stock->bind_param("ii", $new_stock, $item['id']);
        
        if (!$update_stock->execute()) {
            throw new Exception('Failed to update stock');
        }

        // Insert order item details
        $item_stmt = $conn->prepare("INSERT INTO order_item (order_id, item_type, item_id, quantity, price, total_price) VALUES (?, 'product', ?, ?, ?, ?)");
        $total_item_price = $item['quantity'] * $item['price'];
        $item_stmt->bind_param("iiidi", $order_id, $item['id'], $item['quantity'], $item['price'], $total_item_price);
        
        if (!$item_stmt->execute()) {
            throw new Exception('Failed to save order details');
        }
    }

    // Commit transaction
    $conn->commit();
    
    echo json_encode([
        'status' => 'success',
        'message' => 'Order created successfully',
        'order_id' => $order_id
    ]);

} catch (Exception $e) {
    // Rollback on error
    if (isset($conn)) {
        $conn->rollback();
    }
    
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