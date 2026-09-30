<?php
header('Content-Type: application/json');
session_start();
// Pick the correct timezone for the user/business
date_default_timezone_set('Asia/Kuala_Lumpur');

if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'business_owner') {
    echo json_encode(["labels" => [], "values" => []]);
    exit;
}

$owner_id = (int)$_SESSION['user_id'];

// ==== DB connect (same style as yours) ====
$host = "localhost";
$user = "root";      // <-- change to your DB user
$pass = "";          // <-- change to your DB password
$db   = "ezaccount";

$conn = new mysqli($host, $user, $pass, $db);
if ($conn->connect_error) {
    http_response_code(500);
    echo json_encode(["error" => "Database connection failed"]);
    exit;
}

// ==== Inputs ====
$g = $_GET['granularity'] ?? 'month';

// Optional filters (keep if you already use them; harmless if not sent)
$outlet_id = isset($_GET['outlet_id']) && $_GET['outlet_id'] !== '' ? (int)$_GET['outlet_id'] : null;
$shift = isset($_GET['shift']) ? trim($_GET['shift']) : '';
if ($shift !== 'Morning' && $shift !== 'Evening') {
    $shift = null; // treat others / empty as "all"
}

// ==== SELECT label + GROUP/ORDER ====
switch ($g) {
    case 'year':
        $selectLabel = "YEAR(cr.report_date) AS label";
        $groupOrder  = "GROUP BY YEAR(cr.report_date) ORDER BY YEAR(cr.report_date)";
        break;

    case 'quarter': // NEW
        // Label like "2025-Q1"
        $selectLabel = "CONCAT(YEAR(cr.report_date), '-Q', QUARTER(cr.report_date)) AS label";
        $groupOrder  = "GROUP BY YEAR(cr.report_date), QUARTER(cr.report_date) ORDER BY YEAR(cr.report_date), QUARTER(cr.report_date)";
        break;

    case 'week':
        $selectLabel = "CONCAT(YEAR(cr.report_date), '-W', LPAD(WEEK(cr.report_date,3),2,'0')) AS label";
        $groupOrder  = "GROUP BY YEARWEEK(cr.report_date,3), label ORDER BY YEARWEEK(cr.report_date,3)";
        break;

    case 'day':
        $selectLabel = "DATE(cr.report_date) AS label";
        $groupOrder  = "GROUP BY DATE(cr.report_date) ORDER BY DATE(cr.report_date)";
        break;

    case 'month':
    default:
        $selectLabel = "DATE_FORMAT(cr.report_date,'%Y-%m') AS label";
        $groupOrder  = "GROUP BY DATE_FORMAT(cr.report_date,'%Y-%m') ORDER BY label";
}

// ==== Main SQL with COGS calculation (CORRECTED) ====
$sql = "
    SELECT
        $selectLabel,
        SUM(cr.cash_sale + IFNULL(cr.qr_sale,0) - cr.total_payment) - 
        COALESCE((
            SELECT SUM(sm.movement_quantity * si.base_price)
            FROM stock_movements sm
            JOIN stock_items si ON sm.item_id = si.item_id
            JOIN stock_category sc ON si.category_id = sc.category_id
            JOIN outlets o2 ON sc.outlet_id = o2.outlet_id
            WHERE o2.owner_id = ?
            AND sm.movement_type = 'OUT'
            AND DATE(sm.movement_date) = DATE(cr.report_date)
";

// Add outlet filter to COGS subquery if specified
if ($outlet_id !== null) {
    $sql .= " AND o2.outlet_id = ? ";
}

$sql .= "
        ), 0) AS value
    FROM closing_reports cr
    JOIN outlets o ON o.outlet_id = cr.outlet_id
    WHERE o.owner_id = ?
";

$types = 'i'; // For owner_id in COGS subquery
$params = [$owner_id]; // First param for COGS subquery owner_id

// Add outlet_id to COGS subquery params if specified
if ($outlet_id !== null) {
    $types .= 'i';
    $params[] = $outlet_id;
}

$types .= 'i'; // For main query owner_id
$params[] = $owner_id; // Main query owner_id

if ($outlet_id !== null) {
    $sql    .= " AND cr.outlet_id = ? ";
    $types  .= 'i';
    $params[] = $outlet_id;
}
if ($shift !== null) {
    $sql    .= " AND cr.shift = ? ";
    $types  .= 's';
    $params[] = $shift;
}

$sql .= " $groupOrder";

// ==== Execute & return
$stmt = $conn->prepare($sql);
if ($stmt === false) {
    http_response_code(500);
    echo json_encode(["error" => "Prepare failed: " . $conn->error]);
    exit;
}

$stmt->bind_param($types, ...$params);
$stmt->execute();
$res = $stmt->get_result();

$labels = [];
$values = [];
while ($row = $res->fetch_assoc()) {
    $labels[] = $row['label'];
    $values[] = (float)$row['value'];
}

echo json_encode(["labels" => $labels, "values" => $values]);

$stmt->close();
$conn->close();
?>