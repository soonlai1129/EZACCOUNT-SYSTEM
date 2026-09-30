<?php
session_start();

include('connection.php');

if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'business_owner') {
    echo json_encode(["success" => false, "message" => "Unauthorized"]);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && !empty($_POST['outlet_name'])) {
    $outlet_name = trim($_POST['outlet_name']);
    $stmt = $conn->prepare("INSERT INTO outlets (outlet_name) VALUES (?)");
    $stmt->bind_param("s", $outlet_name);

    if ($stmt->execute()) {
        echo json_encode(["success" => true, "outlet_id" => $stmt->insert_id, "outlet_name" => $outlet_name]);
    } else {
        echo json_encode(["success" => false, "message" => "Failed to save"]);
    }
    exit;
}

echo json_encode(["success" => false, "message" => "Invalid request"]);
