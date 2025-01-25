<?php
// Database connection
$con = mysqli_connect("localhost", "root", "", "inventory_system");
if (!$con) {
    die("Connection failed: " . mysqli_connect_error());
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['barcode'])) {
    $barcode = mysqli_real_escape_string($con, $_POST['barcode']);
    
    // Store the scanned barcode in a temporary table or file
    $sql = "INSERT INTO scanned_barcodes (barcode, timestamp) VALUES ('$barcode', NOW())";
    mysqli_query($con, $sql);
    
    echo "Barcode saved successfully";
}
mysqli_close($con);
?>