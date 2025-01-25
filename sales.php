<?php
$page_title = 'All Sales';
require_once('includes/load.php');
page_require_level(3);
$sales = find_all_sale(); // Assumes this function fetches all sales including payment method

// Navigation to add_sale.php
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_sale'])) {
	header("Location: add_sale.php");
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
	<h1 class="workboard__title">Sales</h1>
</div>
<div class="workpanel report__main">
	<div class="row">
		<div class="col xs-12">
			<div class="row">
				<div class="col xs-12">
					<div class="meta-info">
						<div class="row">
							<div class="col xs-12 sm-6">
								<h2 class="subheading">Selling Products</h2>
							</div>
							<div class="col xs-12 sm-6">
								<form method="POST">
									<div class="site-panel">
										<div class="form__module">
											<div class="form__action">
												<span class="icon-add"></span>
												<input type="submit" class="button primary-tint" value="Add Sale" name="add_sale">
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
													<th class="name">S.N.</th>
													<th class="name">Product Name</th>
													<th class="Quantity">Quantity</th>
													<th class="Total">Total</th>
													<th class="Payment">Payment Method</th>
													<th class="Date">Date</th>
													<th class="action">Action</th>
												</tr>
											</thead>
											<tbody>
												<?php foreach ($sales as $sale): ?>
													<tr>
														<td class="text-center"><?php echo count_id(); ?></td>
														<td><?php echo remove_junk($sale['name']); ?></td>
														<td class="text-center"><?php echo (int)$sale['qty']; ?></td>
														<td class="text-center"><?php echo remove_junk($sale['price']); ?></td>
														<td class="text-center"><?php echo !empty($sale['payment']) ? remove_junk($sale['payment']) : 'Not Paid'; ?></td>

														<td class="text-center"><?php echo $sale['date']; ?></td>
														<td class="text-center">
															<div class="btn-group">
																<a href="edit_sale.php?id=<?php echo (int)$sale['id']; ?>" class="btn btn-warning btn-xs" title="Edit" data-toggle="tooltip">
																	<span class="icon-edit"></span>
																</a>
																<a href="delete_sale.php?id=<?php echo (int)$sale['id']; ?>" class="btn btn-danger btn-xs" title="Delete" data-toggle="tooltip">
																	<span class="icon-trash"></span>
																</a>
															</div>
														</td>
													</tr>
												<?php endforeach; ?>
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
