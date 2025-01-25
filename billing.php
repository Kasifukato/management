<?php
$page_title = 'Billing';
require_once('includes/load.php');
require 'phpqrcode/qrlib.php';
page_require_level(3);

// Database connection
$con = mysqli_connect("localhost", "root", "", "inventory_system");
if (!$con) {
    die("Connection failed: " . mysqli_connect_error());
}

// Handle barcode search via AJAX
if(isset($_POST['search_barcode'])) {
    $barcode = mysqli_real_escape_string($con, $_POST['search_barcode']);
    $sql = "SELECT p.*, c.name as category_name 
            FROM products p 
            LEFT JOIN categories c ON p.categorie_id = c.id 
            WHERE p.barcode = '$barcode'";
    $result = mysqli_query($con, $sql);
    
    if($result && mysqli_num_rows($result) > 0) {
        $product = mysqli_fetch_assoc($result);
        echo json_encode(['status' => 'success', 'data' => $product]);
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Product not found']);
    }
    exit;
}

// Handle payment processing
if(isset($_POST['process_payment'])) {
    $items = json_decode($_POST['items'], true);
    $payment_method = mysqli_real_escape_string($con, $_POST['payment_method']);  // 'cash' or 'online'
    
    mysqli_begin_transaction($con);
    try {
        foreach($items as $item) {
            $id = (int)$item['id'];
            $qty = (int)$item['quantity'];
            $price = (float)$item['price'];
            $total = $qty * $price;
            
            // Update product quantity
            $update_sql = "UPDATE products SET quantity = quantity - $qty 
                          WHERE id = $id AND quantity >= $qty";
            if(!mysqli_query($con, $update_sql)) {
                throw new Exception("Failed to update quantity");
            }
            
            // Record sale with correct payment column
            $sale_sql = "INSERT INTO sales (product_id, qty, price, total, date, payment) 
                         VALUES ($id, $qty, $price, $total, NOW(), '$payment_method')";
            if(!mysqli_query($con, $sale_sql)) {
                throw new Exception("Failed to record sale");
            }
        }
        
        mysqli_commit($con);
        echo json_encode(['status' => 'success']);
    } catch(Exception $e) {
        mysqli_rollback($con);
        echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
    }
    exit;
}


// Fetch products from the database
$sql = "SELECT * FROM products"; // Replace with your products table name
$result = $con->query($sql);

// Initialize the product list
$products = [];

if ($result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $products[] = $row;
    }
} else {
    echo "<p>No products found in the database.</p>";
}

// Handle form submission (when user clicks the 'Pay' button)
$qrFile = '';
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['totalAmount'])) {
    // Get the total amount from the form
    $totalAmount = (float)$_POST['totalAmount']; // Ensure it is a valid number
    $merchantId = 'EPAYTEST'; // Replace with your eSewa Merchant ID
    $orderId = uniqid('order_');     // Generate a unique order ID
    $callbackUrl = 'http://localhost/qr/verify_payment.php'; // Replace with your actual callback URL

    // Generate QR data (format for eSewa payment)
    $qrData = "https://esewa.com.np/qrcode?amt=$totalAmount&pid=$orderId&scd=$merchantId&su=$callbackUrl";

    // Generate QR code and save it to a file
    $qrFile = 'qr_payment.png';
    QRcode::png($qrData, $qrFile, QR_ECLEVEL_L, 10);
}
?>

<?php include_once('layouts/header.php'); ?>

<div class="workboard__heading">
    <h1 class="workboard__title">Billing</h1>
</div>
<div class="workpanel sales">
    <div class="row">
        <!-- Left Panel -->
        <div class="col xs-12 sx-6">
            <div class="overall-info">
                <div class="info">
                    <div class="row">
                        <div class="col xs-12 sx-6">
                            <span> Product</span>
                        </div>
                        <div class="col xs-12 sx-6">
                            <form method="POST">
                                <div class="site-panel">
                                    <div class="form__action">
                                        <span class="icon-add"></span>
                                        <input type="submit" class="button primary-tint" value="Add Products" name="add_product">
                                    </div>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
                <div class="col xs-12 sx-6">
                    <form class="general--form access__form info" id="product-form">
                        <div class="form__module">
                            <label for="brcode" class="form__label">Barcode</label>
                            <div class="form__set">
                                <input type="text" id="brcode" placeholder="12345" autofocus>
                            </div>
                        </div>
                        <div class="form__module">
                            <label for="prodname" class="form__label">Name</label>
                            <div class="form__set">
                                <input type="text" id="prodname" placeholder="Product Name" readonly>
                            </div>
                        </div>
                        <div class="form__module">
                            <div class="form__set">
                                <label for="mrp" class="form__label">MRP</label>
                                <input type="text" id="mrp" placeholder="Rs." readonly>
                            </div>
                        </div>
                        <div class="form__module">
                            <div class="form__set">
                                <label for="qty" class="form__label">Quantity</label>
                                <input type="text" id="qty" placeholder="Qty." value="1">
                            </div>
                        </div>
                        <div class="form__module">
                            <div class="form__set">
                                <label for="avi_qty" class="form__label">Available Quantity</label>
                                <input type="text" id="avi_qty" placeholder="Qty." readonly>
                            </div>
                        </div>
                        <div class="form__module">
                            <div class="form__set">
                                <label for="s_price" class="form__label">Sale Price</label>
                                <input type="text" id="s_price" placeholder="Rs." readonly>
                            </div>
                        </div>
                        <input type="hidden" id="product-id">
                    </form>
                </div>
            </div>
        </div>

        <!-- Right Panel -->
        <div class="col xs-12 sx-6">
            <div class="overall-info">
                <div class="info">
                    <div class="row">
                        <div class="col xs-12 sx-6">
                            <span>Bill</span>
                        </div>
                        <div class="col xs-12 sx-6">
                            <form method="POST">
                                <div class="site-panel">
                                    <div class="form__action">
                                        <input type="submit" class="button primary-tint" id="print-order" value="Print" name="print">
                                    </div>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
                <div class="col xs-12">
                    <div class="questionaries__showcase" id="question_popup" style="display: flex;">
                        <div class="tbl-wrap">
                            <table id="cart-table">
                                <thead>
                                    <tr>
                                        <th>Name</th>
                                        <th>Quantity</th>
                                        <th>Price</th>
                                        <th>Total</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
                <div class="col xs-12 sx-6 sm-3">
                    <form class="general--form access__form info">
                        <div class="form__module">
                            <select class="form-control" id="payment-method">
                                <option value="cash">Cash</option>
                                <option value="online">Online</option>
                            </select>
                        </div>
                    </form>
                </div>
                <form method="POST">
                <div class="col xs-12 sx-6 sm-3">
                    <div class="ttl_pric">
                        <span>Grand Total </span><span id="totalAmountDisplay">Rs.0.00</span>
                        <input type="hidden" name="totalAmount" id="totalAmount" value="0">
                    </div>
                </div>
                <div class="col xs-12">
                    
                        <div class="site-panel">
                            <div class="form__action">
                                <input type="button" class="button primary-tint" value="Pay" id="process-payment">
                                <button type="submit" name="pay">Generate QR Code</button>
                                
                            </div>
                        </div>
                        <div id="qr-code-container" style="display: none;"></div>
                    </form>
                    <?php if ($qrFile): ?>
        <h2>Total Amount: NPR <?php echo htmlspecialchars($totalAmount); ?></h2>
        <h3>Scan the QR Code to Pay</h3>
        <img src="<?php echo htmlspecialchars($qrFile); ?>" alt="QR Code">
    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>


<script>
    document.addEventListener('DOMContentLoaded', function() {
    const cart = [];
    const qrCodeContainer = document.getElementById('qr-code-container');

    // Barcode scanner handling
    document.getElementById('brcode').addEventListener('keypress', function(e) {
        if(e.key === 'Enter') {
            e.preventDefault();
            const barcode = this.value;
            
            fetch('billing.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: 'search_barcode=' + encodeURIComponent(barcode)
            })
            .then(response => response.json())
            .then(data => {
                if(data.status === 'success') {
                    document.getElementById('prodname').value = data.data.name;
                    document.getElementById('mrp').value = data.data.buy_price;
                    document.getElementById('s_price').value = data.data.sale_price;
                    document.getElementById('avi_qty').value = data.data.quantity;
                    document.getElementById('product-id').value = data.data.id;
                    document.getElementById('qty').focus();
                } else {
                    alert('Product not found!');
                    document.getElementById('brcode').value = '';
                }
            });
        }
    });

    // Add to cart button handling
    document.querySelector('input[name="add_product"]').addEventListener('click', function(e) {
        e.preventDefault();
        
        const productId = document.getElementById('product-id').value;
        if(!productId) {
            alert('Please scan a product first!');
            return;
        }

        const item = {
            id: productId,
            name: document.getElementById('prodname').value,
            unitPrice: parseFloat(document.getElementById('s_price').value),
            quantity: parseInt(document.getElementById('qty').value),
            get totalPrice() {
                return this.unitPrice * this.quantity;
            }
        };
        
        cart.push(item);
        updateCart();
        
        // Clear form
        document.getElementById('product-form').reset();
        document.getElementById('brcode').focus();
    });

    // Process payment handling
    document.getElementById('process-payment').addEventListener('click', function() {
        if(cart.length === 0) {
            alert('Cart is empty!');
            return;
        }
        
        const paymentMethod = document.getElementById('payment-method').value;
        
        // Prepare items for backend processing
        const items = cart.map(item => ({
            id: item.id,
            quantity: item.quantity,
            price: item.unitPrice
        }));
        
        fetch('billing.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: 'process_payment=1&items=' + encodeURIComponent(JSON.stringify(items)) + 
                  '&payment_method=' + encodeURIComponent(paymentMethod)
        })
        .then(response => response.json())
        .then(data => {
            if(data.status === 'success') {
                alert('Payment processed successfully!');
                cart.length = 0; // Clear cart
                updateCart();
            } else {
                alert('Error: ' + data.message);
            }
        });
    });

    // Generate QR Code button handling
    document.getElementById('generate-qr-code').addEventListener('click', function() {
        generateQRCode();
    });

    // Updated updateCart function
    function updateCart() {
        const tbody = document.querySelector('#cart-table tbody');
        tbody.innerHTML = '';
        let grandTotal = 0;
        
        cart.forEach((item, index) => {
            grandTotal += item.totalPrice;
            
            const row = `
                <tr style="text-align: center;">
                    <td>${item.name}</td>
                    <td>${item.quantity}</td>
                    <td>Rs.${item.unitPrice.toFixed(2)}</td>
                    <td>Rs.${item.totalPrice.toFixed(2)}</td>
                    <td class='text-center'>
                        <div class='btn-group'>
                            <a href='javascript:void(0)' onclick="removeItem(${index})" class='btn btn-danger btn-xs' title='Delete'>
                                <span class='icon-trash'></span>
                            </a>
                        </div>
                    </td>
                </tr>
            `;
            tbody.innerHTML += row;
        });
        
        document.getElementById('totalAmountDisplay').textContent = 'Rs.' + grandTotal.toFixed(2);
        document.getElementById('totalAmount').value = grandTotal.toFixed(2);
    }

    // Function to generate the QR code
    function generateQRCode() {
    const totalAmount = parseFloat(document.getElementById('totalAmount').value);

    if (totalAmount === 0) {
        alert('Cart is empty!');
        return;
    }

    fetch('billing.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: 'totalAmount=' + encodeURIComponent(totalAmount) + '&generate_qr=1',
    })
    .then((response) => response.json())
    .then((data) => {
        if (data.status === 'success') {
            qrCodeContainer.innerHTML = `<img src="${data.qrFile}" alt="QR Code" />`;
            qrCodeContainer.style.display = 'block';
        } else {
            alert('Error generating QR code: ' + data.message);
        }
    })
    .catch((error) => {
        console.error('Error:', error);
        alert('An error occurred while generating the QR code.');
    });
}
    // Global removeItem function
    window.removeItem = function(index) {
        cart.splice(index, 1);
        updateCart();
    };
});

document.addEventListener('DOMContentLoaded', function() {
    // Add this function to check for scanned barcodes
    function checkScannedBarcode() {
    fetch('check_barcode.php')
    .then(response => response.json())
    .then(data => {
        if (data.status === 'success' && data.barcode) {
            // Check if barcode is actually different from current input
            const currentBarcode = document.getElementById('brcode').value;
            if (data.barcode !== currentBarcode) {
                document.getElementById('brcode').value = data.barcode;
                
                // Trigger barcode search
                const event = new KeyboardEvent('keypress', {
                    'key': 'Enter',
                    'bubbles': true
                });
                document.getElementById('brcode').dispatchEvent(event);
            }
        }
    })
    .catch(error => console.error('Error:', error));
}

    // Check for scanned barcode every 2 seconds
    setInterval(checkScannedBarcode, 2000);
});

document.getElementById('print-order').addEventListener('click', function() {
    const orderSummaryTable = document.getElementById('cart-table').outerHTML;
    const totalAmount = document.getElementById('totalAmount').value;
    const printWindow = window.open('', 'Print Order', 'height=600,width=800');
    printWindow.document.write('<html><head><title>Bill</title>');
    printWindow.document.write('</head><body>');
    printWindow.document.write('<h2>Bill</h2>');
    printWindow.document.write(orderSummaryTable);
    printWindow.document.write('<h2>Total Amount: Rs. ' + totalAmount + '</h2>');
    printWindow.document.write('</body></html>');
    printWindow.document.close();
    printWindow.print();
});
</script>

<?php include_once('layouts/footer.php'); ?>