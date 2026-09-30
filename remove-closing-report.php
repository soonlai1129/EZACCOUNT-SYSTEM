<?php
/**
 * ezAccount System - Delete Closing Report (and its children)
 * Deletes rows from:
 *   - closing_report_cash_breakdown
 *   - closing_report_payments
 *   - closing_reports
 * in a single transaction (child tables first).
 */

session_start();
include('connection.php');

// Optional: only allow logged-in business owners (match your edit-page guard)
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'business_owner') {
    header("Location: index.php");
    exit();
}

// Validate input
if (!isset($_GET['id']) || !ctype_digit($_GET['id'])) {
    header("Location: closing-report-table.php?err=invalid_id");
    exit();
}

$reportId = (int) $_GET['id'];

// Start transaction
$conn->begin_transaction();

try {
    // Delete from child tables first (cash breakdown, then payments)
    $stmt1 = $conn->prepare("DELETE FROM closing_report_cash_breakdown WHERE report_id = ?");
    if (!$stmt1) { throw new Exception("Prepare failed: " . $conn->error); }
    $stmt1->bind_param("i", $reportId);
    if (!$stmt1->execute()) { throw new Exception("Execute failed: " . $stmt1->error); }
    $stmt1->close();

    $stmt2 = $conn->prepare("DELETE FROM closing_report_payments WHERE report_id = ?");
    if (!$stmt2) { throw new Exception("Prepare failed: " . $conn->error); }
    $stmt2->bind_param("i", $reportId);
    if (!$stmt2->execute()) { throw new Exception("Execute failed: " . $stmt2->error); }
    $stmt2->close();

    // Delete parent row
    $stmt3 = $conn->prepare("DELETE FROM closing_reports WHERE report_id = ?");
    if (!$stmt3) { throw new Exception("Prepare failed: " . $conn->error); }
    $stmt3->bind_param("i", $reportId);
    if (!$stmt3->execute()) { throw new Exception("Execute failed: " . $stmt3->error); }

    // Check if a row was actually deleted (in case id didn’t exist)
    if ($stmt3->affected_rows < 1) {
        $stmt3->close();
        throw new Exception("No report found with id=$reportId");
    }
    $stmt3->close();

    // All good
    $conn->commit();
    header("Location: closing-report-table.php?msg=deleted");
    exit();

} catch (Throwable $e) {
    // Rollback on any failure
    $conn->rollback();

    // (Optional) Log the error somewhere:
    // error_log("[DELETE_REPORT] id=$reportId error=" . $e->getMessage());

    // Redirect with error flag
    header("Location: closing-report-table.php?err=delete_failed");
    exit();
}
