<?php
header('Content-Type: application/json');

// Database connection
$con = mysqli_connect("localhost", "root", "", "inventory_system");
if (!$con) {
    echo json_encode(['status' => 'error', 'message' => 'Connection failed']);
    exit;
}

// Get the most recent unprocessed scanned barcode
$sql = "SELECT barcode FROM scanned_barcodes 
        WHERE processed = 0 
        ORDER BY timestamp DESC 
        LIMIT 1";
$result = mysqli_query($con, $sql);

if ($row = mysqli_fetch_assoc($result)) {
    // Mark barcode as processed immediately
    $update_sql = "UPDATE scanned_barcodes SET processed = 1 WHERE barcode = '" . 
        mysqli_real_escape_string($con, $row['barcode']) . "'";
    mysqli_query($con, $update_sql);
    
    echo json_encode([
        'status' => 'success', 
        'barcode' => $row['barcode']
    ]);
} else {
    echo json_encode([
        'status' => 'error', 
        'message' => 'No barcode found'
    ]);
}

mysqli_close($con);
?>