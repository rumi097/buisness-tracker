<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Ensure FPDF library is present
if (!file_exists('fpdf.php')) {
    die("Error: FPDF library not found. Please download it from fpdf.org and place 'fpdf.php' and the 'font' folder in your project directory.");
}
require('fpdf.php');
include 'db.php'; // Your PostgreSQL connection file

// Check if an invoice_id is provided in the URL
if (isset($_GET['invoice_id'])) {
    $invoice_id = $_GET['invoice_id'];
    $action = $_GET['action'] ?? 'print'; // Default to 'print'

    // Get Sale and User Details using pgsql functions
    $sql_sale_details = "SELECT st.sale_date, u.store_name FROM sales_transactions st JOIN users u ON st.user_id = u.id WHERE st.invoice_id = $1 LIMIT 1";
    pg_prepare($conn, "get_sale_details", $sql_sale_details);
    $result_sale_details = pg_execute($conn, "get_sale_details", array($invoice_id));
    
    $sale_details = pg_fetch_assoc($result_sale_details);
    if (!$sale_details) { die("Error: Invoice ID not found."); }

    // Get All Sale Items for this Invoice
    $sql_items = "SELECT product_name, quantity_sold, sale_price_each, total_amount FROM sales_transactions WHERE invoice_id = $1";
    pg_prepare($conn, "get_sale_items", $sql_items);
    $items_result = pg_execute($conn, "get_sale_items", array($invoice_id));
    
    // Create PDF using FPDF (this part remains the same)
    $pdf = new FPDF('P', 'mm', 'A4');
    $pdf->AddPage();
    
    // Header
    $pdf->SetFont('Arial', 'B', 16);
    $pdf->Cell(0, 10, 'Sale Invoice', 0, 1, 'C');
    $pdf->SetFont('Arial', '', 12);
    $pdf->Cell(0, 7, 'Store: ' . htmlspecialchars($sale_details['store_name']), 0, 1, 'C');
    $pdf->Ln(5);

    // Info
    $pdf->SetFont('Arial', 'B', 10);
    $pdf->Cell(40, 7, 'Invoice ID:');
    $pdf->SetFont('Arial', '', 10);
    $pdf->Cell(0, 7, $invoice_id, 0, 1);

    $pdf->SetFont('Arial', 'B', 10);
    $pdf->Cell(40, 7, 'Date:');
    $pdf->SetFont('Arial', '', 10);
    $pdf->Cell(0, 7, date('Y-m-d H:i:s', strtotime($sale_details['sale_date'])), 0, 1);
    $pdf->Ln(10);
    
    // Table Header
    $pdf->SetFont('Arial', 'B', 12);
    $pdf->Cell(100, 10, 'Product', 1, 0, 'C');
    $pdf->Cell(25, 10, 'Quantity', 1, 0, 'C');
    $pdf->Cell(30, 10, 'Unit Price', 1, 0, 'C');
    $pdf->Cell(35, 10, 'Subtotal', 1, 1, 'C');
    
    // Table Rows
    $pdf->SetFont('Arial', '', 12);
    $grand_total = 0;
    while ($item = pg_fetch_assoc($items_result)) { // CHANGED
        $grand_total += $item['total_amount'];
        $pdf->Cell(100, 10, htmlspecialchars($item['product_name']), 1);
        $pdf->Cell(25, 10, $item['quantity_sold'], 1, 0, 'C');
        $pdf->Cell(30, 10, '$' . number_format($item['sale_price_each'], 2), 1, 0, 'R');
        $pdf->Cell(35, 10, '$' . number_format($item['total_amount'], 2), 1, 1, 'R');
    }

    // Grand Total
    $pdf->SetFont('Arial', 'B', 14);
    $pdf->Cell(155, 12, 'Total Amount', 1, 0, 'R');
    $pdf->Cell(35, 12, '$' . number_format($grand_total, 2), 1, 1, 'R');
    
    // Output PDF based on action
    if ($action == 'print') {
        // Add JavaScript to trigger the print dialog for PostgreSQL-generated PDFs
        $pdf->SetJS('this.print();');
        $pdf->Output('I', 'invoice_'.$invoice_id.'.pdf');
    } else {
        $pdf->Output('D', 'invoice_'.$invoice_id.'.pdf');
    }

} else {
    echo "No invoice ID provided.";
}

pg_close($conn);
?>