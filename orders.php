<?php
$page_title = 'All sale';
require_once('includes/load.php');
page_require_level(3);

// Query to fetch data based on your provided SQL
$sql = "SELECT o.id, o.required_quantity, p.name AS product_name, 
                o.expiry_time, o.sent_quantity,
                s.name AS supplier_name, s.contact AS supplier_contact 
        FROM orders o
        JOIN products p ON o.product_id = p.id
        JOIN suppliers s ON o.supplier_id = s.id
		WHERE o.is_accepted = 0
		";
$sales = $db->query($sql)->fetch_all(MYSQLI_ASSOC);

// Handling the Accept or Decline action
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        $order_id = $_POST['order_id'];
        $action = $_POST['action'];

        if ($action === 'accept') {
            // Get the sent quantity and product id to update the product quantity
            $order_query = $db->query("SELECT product_id, sent_quantity, supplier_id, required_quantity, expiry_time FROM orders WHERE id = '{$order_id}' LIMIT 1");
            $order_data = $order_query->fetch_assoc();
            $product_id = $order_data['product_id'];
            $sent_quantity = $order_data['sent_quantity'];
            $supplier_id = $order_data['supplier_id'];
            $required_quantity = $order_data['required_quantity'];
            $expiry_time = $order_data['expiry_time'];

            // Update the product's quantity
            $db->query("UPDATE products SET quantity = quantity + {$sent_quantity} WHERE id = '{$product_id}'");

            // Mark the order as accepted
            $db->query("UPDATE orders SET is_accepted = 1 WHERE id = '{$order_id}'");

            // Log the accepted action into order_history
            $db->query("INSERT INTO order_history (order_id, product_id, supplier_id, required_quantity, sent_quantity, expiry_time, action) 
                        VALUES ('{$order_id}', '{$product_id}', '{$supplier_id}', '{$required_quantity}', '{$sent_quantity}', '{$expiry_time}', 'accepted')");

            $session->msg('s', "Order accepted and product quantity updated.");
            redirect('orders.php');

        } elseif ($action === 'decline') {
            // Get order details for logging
            $order_query = $db->query("SELECT product_id, supplier_id, required_quantity, sent_quantity, expiry_time FROM orders WHERE id = '{$order_id}' LIMIT 1");
            $order_data = $order_query->fetch_assoc();
            $product_id = $order_data['product_id'];
            $supplier_id = $order_data['supplier_id'];
            $required_quantity = $order_data['required_quantity'];
            $sent_quantity = $order_data['sent_quantity'];
            $expiry_time = $order_data['expiry_time'];

            // Log the declined action into order_history
            $db->query("INSERT INTO order_history (order_id, product_id, supplier_id, required_quantity, sent_quantity, expiry_time, action) 
                        VALUES ('{$order_id}', '{$product_id}', '{$supplier_id}', '{$required_quantity}', '{$sent_quantity}', '{$expiry_time}', 'declined')");

            // Delete the order from the orders table
            $db->query("DELETE FROM orders WHERE id = '{$order_id}'");

            $session->msg('w', "Order declined and logged in history.");
            redirect('orders.php');
        }
    }
}



// Navigation to add_product.php
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_orders'])) {
    header("Location: add_orders.php");
    exit;
}


?>

<?php include_once('layouts/header.php'); ?>
<div class="row">
    <div class="col-md-6">
        <?php echo display_msg($msg); ?>
    </div>
</div>
<div class="workboard__heading">
    <h1 class="workboard__title">Orders</h1>
</div>
<div class="workpanel report__main">
    <div class="row">
        <div class="col xs-12">
            <div class="row">
                <div class="col xs-12">
                    <div class="meta-info">
                        <div class="row">
                            <div class="col xs-12 sm-6">
                                <h2 class="subheading">Orders List</h2>
                            </div>
                            <div class="col xs-12 sm-6">
                                <form method="POST">
                                    <div class="site-panel">
                                        <div class="form__module">
                                            <div class="form__action">
                                                <span class="icon-add"></span>
                                                <input type="submit" class="button primary-tint" value="Add orders" name="add_orders">
                                            </div>
                                        </div>
                                    </div>
                                </form>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col xs-12">
                                <div class="questionaries__showcase" id="question_popup" style="display: flex;">
                                    <div class="tbl-wrap">
                                        <table id="sales__table">
                                            <thead>
                                                <tr>
                                                    <th class="S.N">S.N.</th>
                                                    <th class="supplier">Supplier Name</th>
                                                    <th class="product">Product</th>
                                                    <th class="reqQty">Required Qty</th>
                                                    <th class="sentQty">Sent Qty</th>
                                                    <th class="expiry">Expiry Time</th>
                                                    <th class="action">Action</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php foreach ($sales as $sale): ?>
                                                    <tr>
                                                        <td class="text-center"><?php echo count_id();?></td>
                                                        <td><?php echo remove_junk($sale['supplier_name']); ?></td>
                                                        <td><?php echo remove_junk($sale['product_name']); ?></td>
                                                        <td class="text-center"><?php echo (int)$sale['required_quantity']; ?></td>
                                                        <td class="text-center"><?php echo (int)$sale['sent_quantity']; ?></td>
                                                        <td class="text-center"><?php echo date('Y-m-d', strtotime($sale['expiry_time'])); ?></td>
                                                        <td class="text-center">
                                                            <div class="form__module">
                                                                <form method="POST" style="display:inline;">
                                                                    <input type="hidden" name="order_id" value="<?php echo $sale['id']; ?>">
                                                                    <?php if ((int)$sale['sent_quantity'] === 0): ?>
                                                                        <button type='button' class='btn btn-warning btn-sm' disabled>Pending</button>
                                                                        <button type='submit' class='btn btn-danger btn-sm' name="action" value="decline">Decline</button>
                                                                    <?php else: ?>
                                                                        <button type='submit' class='btn btn-success btn-sm' name="action" value="accept">Accept</button>
                                                                        <button type='submit' class='btn btn-danger btn-sm' name="action" value="decline">Decline</button>
                                                                    <?php endif; ?>
                                                                </form>
                                                            </div>
                                                        </td>
                                                    </tr>
                                                <?php endforeach;?>
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
<?php include_once('layouts/footer.php'); ?>
