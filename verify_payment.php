<?php

// Log incoming data
file_put_contents('debug_post_data.txt', print_r($_POST, true), FILE_APPEND);

// Display received data (for debugging)
echo "<pre>";
print_r($_POST);
echo "</pre>";
// eSewa Response Verification Script

// Get the eSewa response sent via POST (you may need to adjust this based on the actual response format)
$response = file_get_contents('php://input');

// Log the response (for debugging purposes)
file_put_contents('payment_log.txt', $response . PHP_EOL, FILE_APPEND);

// Parse the response (assuming it's a JSON response, modify if eSewa provides a different format)
$responseData = json_decode($response, true);

// Check the status of the payment (Assuming eSewa sends a 'status' field)
if ($responseData['status'] == 'success') {
    // Payment is successful, process further logic (update database, send confirmation to user, etc.)
    $orderId = $responseData['order_id']; // Capture the order ID
    $amount = $responseData['amount']; // Capture the amount
    $transactionId = $responseData['transaction_id']; // Capture the transaction ID

    // Update the order status in your database
    $conn = new mysqli('localhost', 'root', '', 'inventory_db'); // Replace with your DB credentials
    if ($conn->connect_error) {
        die("Connection failed: " . $conn->connect_error);
    }

    // Update the order status as 'Paid'
    $stmt = $conn->prepare("UPDATE orders SET status = 'Paid', transaction_id = ? WHERE order_id = ?");
    $stmt->bind_param('ss', $transactionId, $orderId);
    $stmt->execute();
    $stmt->close();
    $conn->close();

    // Respond to eSewa (if needed, eSewa might expect a response to acknowledge the payment)
    echo "Payment Verified Successfully!";
} else {
    // Payment failed, log or handle the failure
    echo "Payment Verification Failed!";
}
?>
