<?php
require_once 'connection.php';
session_start();

header('Content-Type: application/json');

error_log("Starting get_order_details.php");
error_log("Session user_id: " . ($_SESSION['user_id'] ?? 'not set'));
error_log("GET order_id: " . ($_GET['order_id'] ?? 'not set'));

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['status' => 'error', 'message' => 'Please login first']);
    exit();
}

if (!isset($_GET['order_id'])) {
    echo json_encode(['status' => 'error', 'message' => 'Order ID not found']);
    exit();
}

try {
    $order_id = filter_var($_GET['order_id'], FILTER_SANITIZE_NUMBER_INT);
    $user_id = $_SESSION['user_id'];

    // First, check if order exists
    $check_order = $conn->prepare("SELECT * FROM orders WHERE order_id = ?");
    $check_order->bind_param("i", $order_id);
    $check_order->execute();
    $order_result = $check_order->get_result();
    
    error_log("Checking order existence - Found rows: " . $order_result->num_rows);
    if ($order_result->num_rows > 0) {
        error_log("Order data: " . print_r($order_result->fetch_assoc(), true));
    }

    // Check order items
    $check_items = $conn->prepare("SELECT * FROM order_item WHERE order_id = ?");
    $check_items->bind_param("i", $order_id);
    $check_items->execute();
    $items_result = $check_items->get_result();
    
    error_log("Checking order items - Found rows: " . $items_result->num_rows);
    if ($items_result->num_rows > 0) {
        while ($row = $items_result->fetch_assoc()) {
            error_log("Order item data: " . print_r($row, true));
        }
    }

    // Get order details with full query
    $query = "
        SELECT o.order_id, o.total_amount, o.status, o.order_data,
               oi.quantity, oi.price, oi.total_price,
               s.product_name, s.image_name
        FROM orders o
        JOIN order_item oi ON o.order_id = oi.order_id
        JOIN store s ON oi.item_id = s.product_id
        WHERE o.order_id = ? AND o.user_id = ? AND oi.item_type = 'product'
    ";
    
    error_log("Final query: " . str_replace('?', '%s', $query));
    error_log(sprintf("Parameters: order_id = %d, user_id = %d", $order_id, $user_id));

    $order_stmt = $conn->prepare($query);
    if (!$order_stmt) {
        throw new Exception('Database prepare error: ' . $conn->error);
    }
    
    $order_stmt->bind_param("ii", $order_id, $user_id);
    if (!$order_stmt->execute()) {
        throw new Exception('Database execute error: ' . $order_stmt->error);
    }
    
    $result = $order_stmt->get_result();
    error_log("Final query results - Number of rows: " . $result->num_rows);

    if ($result->num_rows === 0) {
        throw new Exception('Order not found or no matching products');
    }

    $order_details = [
        'items' => [],
        'total_amount' => 0,
        'status' => '',
        'order_date' => ''
    ];

    while ($row = $result->fetch_assoc()) {
        error_log("Row data from final query: " . print_r($row, true));
        
        $order_details['items'][] = [
            'name' => $row['product_name'],
            'quantity' => $row['quantity'],
            'price' => $row['price'],
            'image' => $row['image_name']
        ];
        
        if (empty($order_details['status'])) {
            $order_details['status'] = $row['status'];
            $order_details['order_date'] = $row['order_data'];
            $order_details['total_amount'] = $row['total_amount'];
            $order_details['order_id'] = $row['order_id'];
        }
    }

    echo json_encode([
        'status' => 'success',
        'data' => $order_details
    ]);

} catch (Exception $e) {
    error_log("Error in get_order_details.php: " . $e->getMessage());
    error_log("Stack trace: " . $e->getTraceAsString());
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