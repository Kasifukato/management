<?php
if (file_exists("scanned_barcode.txt")) {
    $barcode = file_get_contents("scanned_barcode.txt");

    $conn = new mysqli("localhost", "root", "", "inventory_system");
    if ($conn->connect_error) {
        die("Connection failed: " . $conn->connect_error);
    }

    // Modify query to join both categories, suppliers, and media table to fetch image details
    $stmt = $conn->prepare("
        SELECT p.barcode, p.name, p.status, p.sale_price, p.quantity, c.name AS category_name, 
               s.name AS supplier_name, s.contact AS supplier_contact, 
               m.file_name AS media_file_name
        FROM products p
        LEFT JOIN categories c ON p.categorie_id = c.id
        LEFT JOIN suppliers s ON p.id = s.product_id
        LEFT JOIN media m ON p.media_id = m.id
        WHERE p.barcode = ?
    ");
    $stmt->bind_param("s", $barcode);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        $product = $result->fetch_assoc();

        // Prepend the upload folder path to the file name
        $product['media_file_name'] = "uploads/products/" . $product['media_file_name'];

        echo json_encode($product); // Return all product details including category, supplier info, and media (image) info
    } else {
        echo json_encode(null); // If no product found
    }

    $stmt->close();
    $conn->close();
} else {
    echo json_encode(null); // If barcode.txt file is missing
}
?>
