<?php
/**
 * AJAX endpoint to get stock items for selected outlet
 */

session_start();
include("connection.php");

// Check if user is authenticated
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'business_owner') {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized']);
    exit();
}

// Get outlet ID from request
$outletId = isset($_GET['outlet_id']) ? intval($_GET['outlet_id']) : 0;

if ($outletId <= 0) {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid outlet ID']);
    exit();
}

// Get items for the selected outlet
$query = "
    SELECT si.item_id, si.item_name, si.base_price, si.remaining_quantity, sc.category_name
    FROM stock_items si
    JOIN stock_category sc ON si.category_id = sc.category_id
    WHERE sc.outlet_id = ?
    ORDER BY sc.category_name, si.item_name
";

$stmt = $conn->prepare($query);
$stmt->bind_param("i", $outletId);
$stmt->execute();
$result = $stmt->get_result();

$items = [];
while ($row = $result->fetch_assoc()) {
    $items[] = $row;
}

// Return JSON response
header('Content-Type: application/json');
echo json_encode($items);
?>