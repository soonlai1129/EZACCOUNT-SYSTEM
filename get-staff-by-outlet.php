<?php
/**
 * Get Staff by Outlet - AJAX endpoint
 */

session_start();
include('connection.php');

// Check if user is logged in and is business owner
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'business_owner') {
    echo json_encode(['success' => false, 'message' => 'Unauthorized access']);
    exit();
}

// Get outlet ID from POST request
$outletId = isset($_POST['outlet_id']) ? intval($_POST['outlet_id']) : 0;

if ($outletId <= 0) {
    echo json_encode(['success' => false, 'message' => 'Invalid outlet ID']);
    exit();
}

// Fetch staff for the selected outlet
$sql = "SELECT staff_id, staff_name FROM staff WHERE outlet_id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $outletId);
$stmt->execute();
$result = $stmt->get_result();

$staffData = [];
while ($staff = $result->fetch_assoc()) {
    $staffData[] = [
        'staff_id' => $staff['staff_id'],
        'staff_name' => $staff['staff_name']
    ];
}

// Return JSON response
echo json_encode([
    'success' => true,
    'data' => $staffData
]);

$stmt->close();
$conn->close();
?>