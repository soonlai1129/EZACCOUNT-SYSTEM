<?php
include('connection.php');

if (!isset($_GET['id'])) {
    echo json_encode(['success'=>false, 'message'=>'Invalid report ID']);
    exit;
}

$report_id = intval($_GET['id']);

// Delete related purchases first
$conn->query("DELETE FROM closing_report_purchases WHERE report_id=$report_id");

// Delete related cash breakdown
$conn->query("DELETE FROM closing_report_cash_breakdown WHERE report_id=$report_id");

// Delete report itself
if ($conn->query("DELETE FROM closing_reports WHERE report_id=$report_id")) {
    echo json_encode(['success'=>true]);
} else {
    echo json_encode(['success'=>false, 'message'=>$conn->error]);
}
