<?php
$page_title = 'Order History';
require_once('includes/load.php');
page_require_level(3);

// Get the action filter (if applied)
$where = "";
if (isset($_GET['action']) && !empty($_GET['action'])) {
    $action = $db->escape($_GET['action']);
    $where = "WHERE oh.action = '{$action}'";
}

// Query to fetch the order history
$sql = "SELECT oh.id, oh.order_id, p.name AS product_name, s.name AS supplier_name, 
                oh.required_quantity, oh.sent_quantity, oh.expiry_time, 
                oh.action, oh.action_date 
        FROM order_history oh
        JOIN products p ON oh.product_id = p.id
        JOIN suppliers s ON oh.supplier_id = s.id
        {$where}
        ORDER BY oh.action_date DESC";

$history = $db->query($sql)->fetch_all(MYSQLI_ASSOC);
?>

<?php include_once('layouts/header.php'); ?>
<div class="row">
    <div class="col-md-6">
        <?php echo display_msg($msg); ?>
    </div>
</div>
<div class="workboard__heading">
    <h1 class="workboard__title">Order History</h1>
</div>
<div class="workpanel report__main">
    <div class="row">
        <div class="col xs-12">
            <form method="GET" action="history.php">
                <div class="form-group">
                    <label for="action">Filter by Action:</label>
                    <select name="action" id="action" class="form-control">
                        <option value="">All</option>
                        <option value="accepted" <?php if (isset($_GET['action']) && $_GET['action'] === 'accepted') echo 'selected'; ?>>Accepted</option>
                        <option value="declined" <?php if (isset($_GET['action']) && $_GET['action'] === 'declined') echo 'selected'; ?>>Declined</option>
                    </select>
                </div>
                <button type="submit" class="btn btn-primary">Filter</button>
            </form>
            <div class="row">
                            <div class="col xs-12">
                                <div class="questionaries__showcase" id="question_popup" style="display: flex;">
                                    <div class="tbl-wrap">
            <div class="table-responsive">
                <table id="history__table" class="table table-bordered">
                    <thead>
                        <tr>
                            <th>S.N.</th>
                            <th>Order ID</th>
                            <th>Supplier Name</th>
                            <th>Product</th>
                            <th>Required Qty</th>
                            <th>Sent Qty</th>
                            <th>Expiry Time</th>
                            <th>Action</th>
                            <th>Action Date</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (!empty($history)): ?>
                            <?php foreach ($history as $index => $entry): ?>
                                <tr>
                                    <td class="text-center"><?php echo $index + 1; ?></td>
                                    <td class="text-center"><?php echo $entry['order_id']; ?></td>
                                    <td><?php echo remove_junk($entry['supplier_name']); ?></td>
                                    <td><?php echo remove_junk($entry['product_name']); ?></td>
                                    <td class="text-center"><?php echo (int)$entry['required_quantity']; ?></td>
                                    <td class="text-center"><?php echo (int)$entry['sent_quantity']; ?></td>
                                    <td class="text-center"><?php echo date('Y-m-d', strtotime($entry['expiry_time'])); ?></td>
                                    <td class="text-center"><?php echo ucfirst($entry['action']); ?></td>
                                    <td class="text-center"><?php echo date('Y-m-d H:i:s', strtotime($entry['action_date'])); ?></td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="9" class="text-center">No history found.</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
            </div></div></div></div>
        </div>
    </div>
</div>
<?php include_once('layouts/footer.php'); ?>
