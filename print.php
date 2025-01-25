<?php
$page_title = 'Print';
require_once('includes/load.php');
page_require_level(3);

try {
    $sales = $db->query("
        SELECT 
            p.name AS product_name, 
            s.qty AS quantity, 
            p.sale_price AS unit_price, 
            (s.qty * p.sale_price) AS total_price 
        FROM 
            sales s
        JOIN 
            products p 
        ON 
            s.product_id = p.id
    ");
} catch (Exception $e) {
    die("Database error: " . $e->getMessage());
}

$total_grand = 0;
?>
<!doctype html>
<html lang="en-US">
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8">
    <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.4/css/bootstrap.min.css"/>
    <style>
        @media print {
            html, body {
                font-size: 9.5pt;
                margin: 0;
                padding: 0;
            }

            .page-break {
                page-break-before: always;
                width: auto;
                margin: auto;
            }
        }

        .page-break {
            width: 980px;
            margin: 0 auto;
        }

        .sale-head {
            margin: 40px 0;
            text-align: center;
        }

        .sale-head h1, .sale-head strong {
            padding: 10px 20px;
            display: block;
        }

        .sale-head h1 {
            margin: 0;
            border-bottom: 1px solid #212121;
        }

        .table > thead:first-child > tr:first-child > th {
            border-top: 1px solid #000;
        }

        table thead tr th {
            text-align: center;
            border: 1px solid #ededed;
        }

        table tbody tr td {
            vertical-align: middle;
        }

        .sale-head, table.table thead tr th, table tbody tr td, table tfoot tr td {
            border: 1px solid #212121;
            white-space: nowrap;
        }

        .sale-head h1, table thead tr th, table tfoot tr td {
            background-color: #f8f8f8;
        }

        tfoot {
            color: #000;
            text-transform: uppercase;
            font-weight: 500;
        }
    </style>
</head>
<body>
<div class="page-break">
    <div class="sale-head">
        <h1>Inventory Management System - Sales Report</h1>
    </div>
    <table class="table table-border">
        <thead>
        <tr>
            <th>Name</th>
            <th>Quantity</th>
            <th>Unit Price</th>
            <th>Total Price</th>
        </tr>
        </thead>
        <tbody>
        <?php if ($sales->num_rows > 0): ?>
            <?php while ($sale = $sales->fetch_assoc()): ?>
                <tr>
                    <td><?php echo htmlspecialchars($sale['product_name']); ?></td>
                    <td class="text-right"><?php echo $sale['quantity']; ?></td>
                    <td class="text-right">Rs. <?php echo number_format($sale['unit_price'], 2); ?></td>
                    <td class="text-right">Rs. <?php echo number_format($sale['total_price'], 2); ?></td>
                </tr>
                <?php $total_grand += $sale['total_price']; ?>
            <?php endwhile; ?>
        <?php else: ?>
            <tr>
                <td colspan="4" class="text-center">No sales data available.</td>
            </tr>
        <?php endif; ?>
        </tbody>
        <tfoot>
        <tr class="text-right">
            <td colspan="3">Grand Total</td>
            <td>Rs. <?php echo number_format($total_grand, 2); ?></td>
        </tr>
        </tfoot>
    </table>
    <div style="text-align: center; margin-top: 20px;">
        <button onclick="window.print()" class="btn btn-primary">Print</button>
    </div>
</div>
</body>
</html>
