
<?php
session_start();
// Pick the correct timezone for the user/business
date_default_timezone_set('Asia/Kuala_Lumpur');
require('connection.php');
require('fpdf/fpdf.php');

// Redirect if not business owner
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'business_owner') {
    header("Location: index.php");
    exit();
}

$userID = $_SESSION['user_id'];

// Get parameters from URL
$year = isset($_GET['year']) ? intval($_GET['year']) : date('Y');
$fromDate = isset($_GET['from']) ? $_GET['from'] : $year . '-01-01';
$toDate = isset($_GET['to']) ? $_GET['to'] : $year . '-12-31';
$outletId = isset($_GET['outlet']) ? $_GET['outlet'] : 'all';

// Validate dates
if ($toDate > date('Y-m-d')) {
    $toDate = date('Y-m-d');
}

// ===== GET BUSINESS OWNER CONTACT INFORMATION =====
$contactQuery = "SELECT owner_name, email, phone_number FROM business_owners WHERE owner_id = ?";
$contactStmt = $conn->prepare($contactQuery);
$contactStmt->bind_param("i", $userID);
$contactStmt->execute();
$contactResult = $contactStmt->get_result();
$contactInfo = $contactResult->fetch_assoc();
$contactStmt->close();

class PDF extends FPDF {
    function Header() {
        $this->SetFont('Arial', 'B', 16);
        $this->Cell(0, 10, 'PROFIT AND LOSS STATEMENT', 0, 1, 'C');
        $this->SetFont('Arial', '', 10);
        $this->Cell(0, 6, 'Period: ' . date('F j, Y', strtotime($_GET['from'])) . ' to ' . date('F j, Y', strtotime($_GET['to'])), 0, 1, 'C');
        $this->SetFont('Arial', 'I', 9);
        $this->Cell(0, 6, 'Generated on: ' . date('F j, Y g:i A'), 0, 1, 'C');
        $this->Ln(5);
    }
    
    function Footer() {
        // Empty footer to remove page number
    }
    
    function FinancialRow($description, $amount, $bold = false, $isDeduction = false, $isProfit = false) {
    $this->SetFont('Arial', $bold ? 'B' : '', 10);
    $this->Cell(140, 8, $description);

    // Format amount with parentheses for deductions OR negative values
    if ($isDeduction && $amount > 0) {
        $amountText = '(RM ' . number_format($amount, 2) . ')';
    } elseif ($amount < 0) {
        $amountText = '(RM ' . number_format(abs($amount), 2) . ')';
    } else {
        $amountText = 'RM ' . number_format($amount, 2);
    }

    // Set color for profit/loss
    if ($isProfit) {
        if ($amount >= 0) {
            $this->SetTextColor(0, 128, 0); // Green for profit
        } else {
            $this->SetTextColor(255, 0, 0); // Red for loss
        }
    } else {
        $this->SetTextColor(0, 0, 0); // Black
    }

    $this->Cell(50, 8, $amountText, 0, 1, 'R');
    $this->SetTextColor(0, 0, 0);
}

    
    function SectionHeader($title) {
        $this->SetFont('Arial', 'B', 12);
        $this->SetFillColor(240, 240, 240);
        $this->Cell(0, 8, $title, 0, 1, 'L', true);
        $this->Ln(2);
    }
    
    function HorizontalLine() {
        $this->SetDrawColor(200, 200, 200);
        $this->Cell(0, 0, '', 'T', 1);
        $this->Ln(2);
    }
    
    function DoubleLine() {
        $this->SetDrawColor(0, 0, 0);
        $this->Cell(0, 0, '', 'B', 1);
        $this->Ln(2);
    }
}

// Create PDF instance
$pdf = new PDF();
$pdf->AddPage();
$pdf->SetFont('Arial', '', 10);

// ===== CALCULATE SALES =====
$salesQuery = "
    SELECT COALESCE(SUM(cash_sale + qr_sale), 0) as total_sales 
    FROM closing_reports cr 
    INNER JOIN outlets o ON cr.outlet_id = o.outlet_id 
    WHERE o.owner_id = ? 
    AND cr.report_date BETWEEN ? AND ?
";

if ($outletId !== 'all') {
    $salesQuery .= " AND cr.outlet_id = ?";
    $stmt = $conn->prepare($salesQuery);
    $stmt->bind_param("issi", $userID, $fromDate, $toDate, $outletId);
} else {
    $stmt = $conn->prepare($salesQuery);
    $stmt->bind_param("iss", $userID, $fromDate, $toDate);
}

$stmt->execute();
$salesResult = $stmt->get_result();
$totalSales = $salesResult->fetch_assoc()['total_sales'];
$stmt->close();

// ===== CALCULATE COST OF SALES =====
$costOfSalesQuery = "
    SELECT COALESCE(SUM(sm.movement_quantity * si.base_price), 0) as cost_of_sales 
    FROM stock_movements sm 
    INNER JOIN stock_items si ON sm.item_id = si.item_id 
    INNER JOIN stock_category sc ON si.category_id = sc.category_id 
    INNER JOIN outlets o ON sc.outlet_id = o.outlet_id 
    WHERE o.owner_id = ? 
    AND sm.movement_type = 'OUT' 
    AND sm.movement_date BETWEEN ? AND ?
";

if ($outletId !== 'all') {
    $costOfSalesQuery .= " AND o.outlet_id = ?";
    $stmt = $conn->prepare($costOfSalesQuery);
    $stmt->bind_param("issi", $userID, $fromDate, $toDate, $outletId);
} else {
    $stmt = $conn->prepare($costOfSalesQuery);
    $stmt->bind_param("iss", $userID, $fromDate, $toDate);
}

$stmt->execute();
$costResult = $stmt->get_result();
$costOfSales = $costResult->fetch_assoc()['cost_of_sales'];
$stmt->close();

// ===== CALCULATE OPERATING EXPENSES =====

// 1. Salary Expenses
$salaryQuery = "
    SELECT COALESCE(SUM(se.amount), 0) as total_salary
    FROM salary_expenses se 
    INNER JOIN operating_expense_records oer ON se.record_id = oer.record_id 
    INNER JOIN outlets o ON oer.outlet_id = o.outlet_id 
    WHERE o.owner_id = ? 
    AND oer.record_date BETWEEN ? AND ?
";

if ($outletId !== 'all') {
    $salaryQuery .= " AND oer.outlet_id = ?";
    $stmt = $conn->prepare($salaryQuery);
    $stmt->bind_param("issi", $userID, $fromDate, $toDate, $outletId);
} else {
    $stmt = $conn->prepare($salaryQuery);
    $stmt->bind_param("iss", $userID, $fromDate, $toDate);
}

$stmt->execute();
$salaryResult = $stmt->get_result();
$salaryExpense = $salaryResult->fetch_assoc()['total_salary'];
$stmt->close();

// 2. Rental Expenses
$rentalQuery = "
    SELECT COALESCE(SUM(re.amount), 0) as total_rental
    FROM rental_expenses re 
    INNER JOIN operating_expense_records oer ON re.record_id = oer.record_id 
    INNER JOIN outlets o ON oer.outlet_id = o.outlet_id 
    WHERE o.owner_id = ? 
    AND oer.record_date BETWEEN ? AND ?
";

if ($outletId !== 'all') {
    $rentalQuery .= " AND oer.outlet_id = ?";
    $stmt = $conn->prepare($rentalQuery);
    $stmt->bind_param("issi", $userID, $fromDate, $toDate, $outletId);
} else {
    $stmt = $conn->prepare($rentalQuery);
    $stmt->bind_param("iss", $userID, $fromDate, $toDate);
}

$stmt->execute();
$rentalResult = $stmt->get_result();
$rentalExpense = $rentalResult->fetch_assoc()['total_rental'];
$stmt->close();

// 3. Utilities Expenses
$utilitiesQuery = "
    SELECT COALESCE(SUM(ue.amount), 0) as total_utilities
    FROM utilities_expenses ue 
    INNER JOIN operating_expense_records oer ON ue.record_id = oer.record_id 
    INNER JOIN outlets o ON oer.outlet_id = o.outlet_id 
    WHERE o.owner_id = ? 
    AND oer.record_date BETWEEN ? AND ?
";

if ($outletId !== 'all') {
    $utilitiesQuery .= " AND oer.outlet_id = ?";
    $stmt = $conn->prepare($utilitiesQuery);
    $stmt->bind_param("issi", $userID, $fromDate, $toDate, $outletId);
} else {
    $stmt = $conn->prepare($utilitiesQuery);
    $stmt->bind_param("iss", $userID, $fromDate, $toDate);
}

$stmt->execute();
$utilitiesResult = $stmt->get_result();
$utilitiesExpense = $utilitiesResult->fetch_assoc()['total_utilities'];
$stmt->close();

// 4. Advertisement Expenses
$advertisementQuery = "
    SELECT COALESCE(SUM(ae.amount), 0) as total_advertisement
    FROM advertisement_expenses ae 
    INNER JOIN operating_expense_records oer ON ae.record_id = oer.record_id 
    INNER JOIN outlets o ON oer.outlet_id = o.outlet_id 
    WHERE o.owner_id = ? 
    AND oer.record_date BETWEEN ? AND ?
";

if ($outletId !== 'all') {
    $advertisementQuery .= " AND oer.outlet_id = ?";
    $stmt = $conn->prepare($advertisementQuery);
    $stmt->bind_param("issi", $userID, $fromDate, $toDate, $outletId);
} else {
    $stmt = $conn->prepare($advertisementQuery);
    $stmt->bind_param("iss", $userID, $fromDate, $toDate);
}

$stmt->execute();
$advertisementResult = $stmt->get_result();
$advertisementExpense = $advertisementResult->fetch_assoc()['total_advertisement'];
$stmt->close();

// 5. Others Expenses + Total Payment (COMBINED)
$othersQuery = "
    SELECT 
        COALESCE(SUM(oe.amount), 0) + 
        COALESCE((
            SELECT SUM(cr.total_payment) 
            FROM closing_reports cr 
            INNER JOIN outlets o2 ON cr.outlet_id = o2.outlet_id 
            WHERE o2.owner_id = ? 
            AND cr.report_date BETWEEN ? AND ?
            " . ($outletId !== 'all' ? " AND cr.outlet_id = ?" : "") . "
        ), 0) as total_others
    FROM others_expenses oe 
    INNER JOIN operating_expense_records oer ON oe.record_id = oer.record_id 
    INNER JOIN outlets o ON oer.outlet_id = o.outlet_id 
    WHERE o.owner_id = ? 
    AND oer.record_date BETWEEN ? AND ?
";

if ($outletId !== 'all') {
    $othersQuery .= " AND oer.outlet_id = ?";
    $stmt = $conn->prepare($othersQuery);
    $stmt->bind_param("issiissi", $userID, $fromDate, $toDate, $outletId, $userID, $fromDate, $toDate, $outletId);
} else {
    $stmt = $conn->prepare($othersQuery);
    $stmt->bind_param("ississ", $userID, $fromDate, $toDate, $userID, $fromDate, $toDate);
}

$stmt->execute();
$othersResult = $stmt->get_result();
$othersExpense = $othersResult->fetch_assoc()['total_others'];
$stmt->close();

// Calculate financial metrics
$grossProfit = $totalSales - $costOfSales;
$totalOperatingExpenses = $salaryExpense + $rentalExpense + $utilitiesExpense + $advertisementExpense + $othersExpense;
$netProfit = $grossProfit - $totalOperatingExpenses;

// ===== GET OUTLET NAMES FOR "ALL OUTLETS" DISPLAY =====
$outletNames = '';
if ($outletId === 'all') {
    $outletsQuery = $conn->prepare("SELECT outlet_name FROM outlets WHERE owner_id = ? ORDER BY outlet_name");
    $outletsQuery->bind_param("i", $userID);
    $outletsQuery->execute();
    $outletsResult = $outletsQuery->get_result();
    
    $outletNamesArray = [];
    while($outlet = $outletsResult->fetch_assoc()) {
        $outletNamesArray[] = $outlet['outlet_name'];
    }
    $outletNames = ' (' . implode(', ', $outletNamesArray) . ')';
} else {
    $outletQuery = $conn->prepare("SELECT outlet_name FROM outlets WHERE outlet_id = ?");
    $outletQuery->bind_param("i", $outletId);
    $outletQuery->execute();
    $outletResult = $outletQuery->get_result();
    $outletName = $outletResult->fetch_assoc()['outlet_name'];
}

// ===== GENERATE PDF CONTENT =====

// Company Information
$pdf->SetFont('Arial', 'B', 12);
$pdf->Cell(0, 8, 'Company: ' . $_SESSION['company_name'], 0, 1);
$pdf->SetFont('Arial', '', 10);

// ADD CONTACT INFORMATION
$pdf->Cell(0, 6, 'Proprietor: ' . $contactInfo['owner_name'], 0, 1);
$pdf->Cell(0, 6, 'Contact: ' . $contactInfo['phone_number'] . ' | ' . $contactInfo['email'], 0, 1);
$pdf->Ln(5); // Add some space

if ($outletId !== 'all') {
    $pdf->Cell(0, 6, 'Outlet: ' . $outletName, 0, 1);
} else {
    $pdf->Cell(0, 6, 'Outlet: All Outlets' . $outletNames, 0, 1);
}
$pdf->Ln(8);

// Sales Section
$pdf->SectionHeader('REVENUE');
$pdf->FinancialRow('Sales', $totalSales);
$pdf->FinancialRow('Cost of Sales', $costOfSales, false, true); // true for deduction
$pdf->HorizontalLine(); // Line after cost of sales
$pdf->FinancialRow('Gross Profit from Sales', $grossProfit, true);
$pdf->DoubleLine(); // Double line after gross profit
$pdf->Ln(5);

// Operating Expenses Section
$pdf->SectionHeader('OPERATING EXPENSES');
$pdf->FinancialRow('Salary Expense', $salaryExpense, false, true);
$pdf->FinancialRow('Rental Expense', $rentalExpense, false, true);
$pdf->FinancialRow('Utilities Expense', $utilitiesExpense, false, true);
$pdf->FinancialRow('Advertisement Expense', $advertisementExpense, false, true);
$pdf->FinancialRow('Others Expense', $othersExpense, false, true);
$pdf->HorizontalLine(); // Line before total
$pdf->FinancialRow('Total Operating Expenses', $totalOperatingExpenses, true, true);
$pdf->DoubleLine(); // Double line after total expenses
$pdf->Ln(5);

// Final Profit Section
$pdf->SectionHeader('PROFIT SUMMARY');
// Format net profit with color and brackets if negative
$pdf->FinancialRow('Net Profit for the Period', $netProfit, true, false, true);
$pdf->DoubleLine(); // Final double line

// Add summary box
$pdf->Ln(10);
$pdf->SetFont('Arial', 'B', 11);
$pdf->SetFillColor(220, 230, 241);
$pdf->Cell(0, 10, 'FINANCIAL SUMMARY', 0, 1, 'C', true);
$pdf->SetFont('Arial', '', 10);

if ($totalSales > 0) {
    $grossMargin = ($grossProfit / $totalSales) * 100;
    $netMargin = ($netProfit / $totalSales) * 100;
    $expenseRatio = ($totalOperatingExpenses / $totalSales) * 100;
    
    $pdf->Cell(0, 6, 'Gross Profit Margin: ' . number_format($grossMargin, 2) . '%', 0, 1);
    $pdf->Cell(0, 6, 'Net Profit Margin: ' . number_format($netMargin, 2) . '%', 0, 1);
    $pdf->Cell(0, 6, 'Operating Expense Ratio: ' . number_format($expenseRatio, 2) . '%', 0, 1);
} else {
    $pdf->Cell(0, 6, 'No sales data available for calculation', 0, 1);
}

// Add Notes Section
//$pdf->Ln(10);
//$pdf->SetFont('Arial', 'B', 10);
//$pdf->Cell(0, 8, 'Notes:', 0, 1);
//$pdf->SetFont('Arial', '', 9);
//$pdf->MultiCell(0, 5, '1. This statement is prepared for internal management purposes.', 0, 1);
//$pdf->MultiCell(0, 5, '2. Figures are based on daily closing reports and expense records.', 0, 1);
//$pdf->MultiCell(0, 5, '3. Cost of sales calculated based on stock movements and base prices.', 0, 1);

// Add Signature Section
//$pdf->Ln(8);
//$pdf->Cell(60, 8, 'Prepared By:', 0, 0);
//$pdf->Cell(60, 8, 'Verified By:', 0, 1);
//$pdf->Cell(60, 2, '____________________', 0, 0);
//$pdf->Cell(60, 2, '____________________', 0, 1);
//$pdf->Cell(60, 8, $contactInfo['owner_name'], 0, 0);
//$pdf->Cell(60, 8, 'Name/Signature', 0, 1);
//$pdf->Cell(60, 6, 'Date: ' . date('d/m/Y'), 0, 0);
//$pdf->Cell(60, 6, 'Date: ' . date('d/m/Y'), 0, 1);

// Generate filename with current date and time
$generationDate = date('Y-m-d_H-i-s');
$filename = "profit_loss_statement_{$generationDate}.pdf";

// Set PDF metadata
$pdf->SetTitle("Profit / Loss Statement - " . date('F j, Y'));
$pdf->SetAuthor($_SESSION['company_name']);
$pdf->SetSubject('Financial Report');
$pdf->SetKeywords('profit, loss, financial, statement');

// **SOLUTION: Generate PDF content first, then output with strong cache control**
$pdfContent = $pdf->Output('S', ''); // Get PDF as string

// Clear any existing output
while (ob_get_level()) {
    ob_end_clean();
}

// Set aggressive cache control headers
header('Content-Type: application/pdf');
header('Content-Disposition: inline; filename="' . $filename . '"');
header('Content-Length: ' . strlen($pdfContent));
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0, post-check=0, pre-check=0');
header('Pragma: no-cache');
header('Expires: Mon, 26 Jul 1997 05:00:00 GMT'); // Past date
header('Last-Modified: ' . gmdate('D, d M Y H:i:s') . ' GMT');

// Output the PDF content
echo $pdfContent;

$conn->close();
exit;
?>