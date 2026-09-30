<?php
// stock_value_aggregate.php
// Returns per-outlet total stock value for the logged-in owner.
// Response: { labels:[], values:[], percents:[], sort:"asc|desc" }

session_start();
// Pick the correct timezone for the user/business
date_default_timezone_set('Asia/Kuala_Lumpur');

header('Content-Type: application/json');

// --- Auth guard (adjust if your session keys differ) ---
if (!isset($_SESSION['user_type']) || $_SESSION['user_type'] !== 'business_owner') {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}
$owner_id = $_SESSION['owner_id'] ?? $_SESSION['user_id'] ?? null;
if (!$owner_id) {
    http_response_code(400);
    echo json_encode(['error' => 'Missing owner_id in session']);
    exit;
}

// --- DB connect (update creds if needed) ---
$mysqli = new mysqli('localhost', 'root', '', 'ezaccount');
if ($mysqli->connect_errno) {
    http_response_code(500);
    echo json_encode(['error' => 'DB connection failed']);
    exit;
}
$mysqli->set_charset('utf8mb4');

// --- Input ---
$sort = strtolower($_GET['sort'] ?? 'desc');
$sort = in_array($sort, ['asc','desc']) ? $sort : 'desc';

// --- Query: sum item total_stock_value -> category -> outlet (include outlets with no items) ---
$sql = "
    SELECT 
        o.outlet_id,
        o.outlet_name,
        COALESCE(SUM(si.total_stock_value), 0) AS total_value
    FROM outlets o
    LEFT JOIN stock_category sc ON sc.outlet_id = o.outlet_id
    LEFT JOIN stock_items si ON si.category_id = sc.category_id
    WHERE o.owner_id = ?
    GROUP BY o.outlet_id, o.outlet_name
";
$stmt = $mysqli->prepare($sql);
$stmt->bind_param('i', $owner_id);
$stmt->execute();
$res = $stmt->get_result();

$data = [];
while ($row = $res->fetch_assoc()) {
    $data[] = [
        'label' => $row['outlet_name'],
        'value' => (float)$row['total_value'] // can be negative if remaining_quantity < 0 at item level
    ];
}
$stmt->close();
$mysqli->close();

// --- Sort by value (asc/desc) so UI toggle is instant ---
usort($data, function($a, $b) use($sort) {
    if ($a['value'] == $b['value']) return 0;
    return ($sort === 'asc')
        ? (($a['value'] < $b['value']) ? -1 : 1)
        : (($a['value'] > $b['value']) ? -1 : 1);
});

// --- Build arrays + % of total (handles mixed positive/negative correctly) ---
$labels = array_column($data, 'label');
$values = array_map(fn($r) => $r['value'], $data);

$total = array_sum($values);
$percents = [];
if ($total == 0) {
    foreach ($values as $_) $percents[] = 0.0; // avoid div/0; show 0%
} else {
    foreach ($values as $v) $percents[] = round(($v / $total) * 100, 1);
}

echo json_encode([
    'labels'   => $labels,
    'values'   => $values,
    'percents' => $percents,
    'sort'     => $sort
]);
