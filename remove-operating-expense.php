<?php
/**
 * ezAccount System - Delete Operating Expense (and its children)
 * Deletes rows from:
 *   - salary_expenses
 *   - rental_expenses
 *   - utilities_expenses
 *   - advertisement_expenses
 *   - others_expenses
 *   - operating_expense_records
 * in a single transaction (child tables first).
 */

session_start();
include('connection.php');

// Only allow logged-in business owners
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'business_owner') {
    header("Location: index.php");
    exit();
}

// Validate input
if (!isset($_GET['id']) || !ctype_digit($_GET['id'])) {
    header("Location: operating-expenses-table.php?err=invalid_id");
    exit();
}

$recordId = (int) $_GET['id'];

// Start transaction
$conn->begin_transaction();

try {
    // Delete from child tables first (all expense tables)
    $stmt1 = $conn->prepare("DELETE FROM salary_expenses WHERE record_id = ?");
    if (!$stmt1) { throw new Exception("Prepare failed: " . $conn->error); }
    $stmt1->bind_param("i", $recordId);
    if (!$stmt1->execute()) { throw new Exception("Execute failed: " . $stmt1->error); }
    $stmt1->close();

    $stmt2 = $conn->prepare("DELETE FROM rental_expenses WHERE record_id = ?");
    if (!$stmt2) { throw new Exception("Prepare failed: " . $conn->error); }
    $stmt2->bind_param("i", $recordId);
    if (!$stmt2->execute()) { throw new Exception("Execute failed: " . $stmt2->error); }
    $stmt2->close();

    $stmt3 = $conn->prepare("DELETE FROM utilities_expenses WHERE record_id = ?");
    if (!$stmt3) { throw new Exception("Prepare failed: " . $conn->error); }
    $stmt3->bind_param("i", $recordId);
    if (!$stmt3->execute()) { throw new Exception("Execute failed: " . $stmt3->error); }
    $stmt3->close();

    $stmt4 = $conn->prepare("DELETE FROM advertisement_expenses WHERE record_id = ?");
    if (!$stmt4) { throw new Exception("Prepare failed: " . $conn->error); }
    $stmt4->bind_param("i", $recordId);
    if (!$stmt4->execute()) { throw new Exception("Execute failed: " . $stmt4->error); }
    $stmt4->close();

    $stmt5 = $conn->prepare("DELETE FROM others_expenses WHERE record_id = ?");
    if (!$stmt5) { throw new Exception("Prepare failed: " . $conn->error); }
    $stmt5->bind_param("i", $recordId);
    if (!$stmt5->execute()) { throw new Exception("Execute failed: " . $stmt5->error); }
    $stmt5->close();

    // Delete parent row
    $stmt6 = $conn->prepare("DELETE FROM operating_expense_records WHERE record_id = ?");
    if (!$stmt6) { throw new Exception("Prepare failed: " . $conn->error); }
    $stmt6->bind_param("i", $recordId);
    if (!$stmt6->execute()) { throw new Exception("Execute failed: " . $stmt6->error); }

    // Check if a row was actually deleted (in case id didn't exist)
    if ($stmt6->affected_rows < 1) {
        $stmt6->close();
        throw new Exception("No operating expense record found with id=$recordId");
    }
    $stmt6->close();

    // All good
    $conn->commit();
    header("Location: operating-expenses-table.php?deleted=1");
    exit();

} catch (Throwable $e) {
    // Rollback on any failure
    $conn->rollback();

    // (Optional) Log the error somewhere:
    // error_log("[DELETE_OPERATING_EXPENSE] id=$recordId error=" . $e->getMessage());

    // Redirect with error flag
    header("Location: operating-expenses-table.php?err=delete_failed");
    exit();
}
?>