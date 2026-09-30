<?php
session_start();
include('connection.php');

// Check if user is logged in
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'business_owner') {
    echo json_encode(['success' => false, 'message' => 'Unauthorized access']);
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['outlet_id'])) {
    $outlet_id = intval($_POST['outlet_id']);
    $user_id = $_SESSION['user_id'];
    
    // Verify that the outlet belongs to the current user
    $verify_sql = "SELECT outlet_id FROM outlets WHERE outlet_id = ? AND owner_id = ?";
    $stmt = $conn->prepare($verify_sql);
    $stmt->bind_param("ii", $outlet_id, $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        // Fetch staff for this outlet
        $staff_sql = "SELECT staff_id, staff_name FROM staff WHERE outlet_id = ? ORDER BY staff_name";
        $staff_stmt = $conn->prepare($staff_sql);
        $staff_stmt->bind_param("i", $outlet_id);
        $staff_stmt->execute();
        $staff_result = $staff_stmt->get_result();
        
        $staff_list = [];
        while ($staff = $staff_result->fetch_assoc()) {
            $staff_list[] = [
                'staff_id' => $staff['staff_id'],
                'staff_name' => $staff['staff_name']
            ];
        }
        
        echo json_encode([
            'success' => true,
            'data' => $staff_list
        ]);
    } else {
        echo json_encode([
            'success' => false,
            'message' => 'Outlet not found or access denied'
        ]);
    }
} else {
    echo json_encode([
        'success' => false,
        'message' => 'Invalid request'
    ]);
}
?>