<?php
$page_title = 'All Product';
require_once('includes/load.php');
page_require_level(2);
$products = join_product_table();

// Establish database connection
$con = mysqli_connect("localhost", "root", "", "inventory_system");

if (isset($_FILES['product_image']) && $_FILES['product_image']['error'] == 0) {
	$upload_dir = 'uploads/products/';
	$file_name = $_FILES['product_image']['name'];
	$file_tmp = $_FILES['product_image']['tmp_name'];
	$file_path = $upload_dir . $file_name;

	if (move_uploaded_file($file_tmp, $file_path)) {
		echo "File uploaded successfully!";
	} else {
		echo "Failed to move file.";
	}
}

// Check connection
if (!$con) {
	die("Connection failed: " . mysqli_connect_error());
}

// Navigation to add_product.php
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_product'])) {
	header("Location: add_product.php");
	exit;
}

// Handle the search query
$search_query = '';
if (isset($_POST["submit"])) {
	$search_query = mysqli_real_escape_string($con, $_POST["title"]);
	$sql = "SELECT media.file_name as image,products.id, products.barcode ,products.media_id, products.name, products.date, products.quantity, products.buy_price, products.sale_price, categories.name AS categorie
	FROM products
	LEFT JOIN categories ON products.categorie_id = categories.id
	LEFT JOIN media ON products.media_id = media.id
	WHERE products.id = '$search_query' 
	OR products.name LIKE '%$search_query%' 
	OR categories.name LIKE '%$search_query%'";
	$search_result = mysqli_query($con, $sql);
} else {
	$search_result = null;
}

$last_category_update = get_last_categories_update_time(); 
$last_product_update = get_last_product_update_time();
$c_categorie = count_by_id('categories');
$total_products_query = "SELECT COUNT(*) AS total_products FROM products";
$total_products_result = mysqli_query($con, $total_products_query);
$total_products_row = mysqli_fetch_assoc($total_products_result);
$total_products = $total_products_row['total_products'];

// Calculate total revenue
$total_revenue_query = "SELECT SUM(sale_price * quantity) AS total_revenue FROM products";
$total_revenue_result = mysqli_query($con, $total_revenue_query);
$total_revenue_row = mysqli_fetch_assoc($total_revenue_result);
$total_revenue = $total_revenue_row['total_revenue'] ?? 0;


// Calculate top selling
$top_selling_query = "
    SELECT 
        products.name, 
        SUM(sales.qty) AS total_sold 
    FROM sales
    JOIN products ON sales.product_id = products.id
    WHERE sales.date >= DATE_SUB(NOW(), INTERVAL 7 DAY)
    GROUP BY sales.product_id
    ORDER BY total_sold DESC
    LIMIT 2";
$top_selling_result = mysqli_query($con, $top_selling_query);

$top_selling_items = [];
if ($top_selling_result) {
    while ($row = mysqli_fetch_assoc($top_selling_result)) {
        $top_selling_items[] = $row;
    }
}

// Query to get the count of low-stock products based on category thresholds
$low_stock_query = "
    SELECT COUNT(*) AS low_stock_count
    FROM products 
    JOIN categories ON products.categorie_id = categories.id
    WHERE products.quantity < categories.threshold
";


$low_stock_result = mysqli_query($con, $low_stock_query);

// Error checking
if ($low_stock_result === false) {
    die("Error executing query: " . mysqli_error($con));
}

$low_stock_count = 0;
$row = mysqli_fetch_assoc($low_stock_result);
$low_stock_count = $row['low_stock_count'] ?? 0; // Default to 0 if null

?>

<?php include_once('layouts/header.php'); ?>
<!-- <?php echo display_msg($msg); ?> -->
<div class="workboard__heading">
	<h1 class="workboard__title">Time Tracking</h1>
</div>
<div class="workpanel inventory__main ">
	<div class="overall-info">
		<div class="row">
			<div class="col xs-12">
				<div class="info">
					<div class="row">
						<div class="col xs-12 sx-6">
							<span>Overall Inventory</span>
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
				<div class="info--counter">
					<div class="row">
						<div class="col xs-12 sx-6 sm-3">
							<div class="infocounter">
								<div class="infocounter__title">
									<span class="text">Category</span>
									<a href="categorie.php"><span class="icon-ellipses"></span></a>
								</div>
								<div class="infocounter__details">
									<span class="counter"><?php echo $c_categorie['total']; ?></span>
								</div>
								<small class="text-muted"><?php echo date('M d,h:i A', strtotime($last_category_update)); ?></small>
							</div>
						</div>
						<div class="col xs-12 sx-6 sm-3">
							<div class="infocounter">
								<div class="infocounter__title">
									<span class="text"style="color: #fdb000;">Total Products</span>
									<!-- <a href="#"><span class="icon-ellipses"></span></a> -->
								</div>
								<div class="infocounter__details">
									<div class="overall-meta">
										<div class="meta-info">
											<span class="counter"><?php echo $total_products; ?></span>
										</div>
										<div class="last_updated">
											<small class="text-muted"><?php echo date('M d', strtotime($last_product_update)); ?></small>
										</div>
									</div>
									<div class="overall-meta">
										<div class="meta-info">
											<span class="counter"><?php echo "Rs." . number_format($total_revenue, 2); ?></span>
										</div>
										<div class="last_updated">
											<small class="text-muted">Total Value</small>
										</div>
									</div>
								</div>
							</div>
						</div>
						<div class="col xs-12 sx-6 sm-3">
							<div class="infocounter">
								<div class="infocounter__title">
									<span class="text" style="color: #BF40BF;" >Top Selling</span>
									<a href="#"><span class="icon-ellipses"></span></a>
								</div>
								<div class="infocounter__details">
    <?php if (!empty($top_selling_items)) : ?>
        <?php foreach ($top_selling_items as $item) : ?>
            <div class="overall-meta">
                <div class="meta-info">
                    <span class="counter"><?php echo htmlspecialchars($item['total_sold']); ?></span>
                </div>
                <div class="last_updated">
                    <small class="text-muted"><?php echo htmlspecialchars($item['name']); ?></small>
                </div>
            </div>
        <?php endforeach; ?>
    <?php else : ?>
        <div class="overall-meta">
            <small class="text-muted">No data available</small>
        </div>
    <?php endif; ?>
</div>
								<!-- <div class="infocounter__details">
									<div class="overall-meta">
										<div class="meta-info">
											<span class="counter">5</span>
										</div>
										<div class="last_updated">
											<small class="text-muted">Last 7 Days</small>
										</div>
									</div>
									<div class="overall-meta">
										<div class="meta-info">
											<span class="counter">5</span>
										</div>
										<div class="last_updated">
											<small class="text-muted">Last 7 Days</small>
										</div>
									</div>
								</div> -->
							</div>
							


						</div>
						<div class="col xs-12 sx-6 sm-3">
						<div class="infocounter">
    <div class="infocounter__title">
        <span class="text" style="color: #C70039;">Low Stocks</span>
    </div>
    <div class="infocounter__details">
        <div class="meta-info">
            <span class="counter"><?php echo $low_stock_count; ?></span>
        </div>
    </div>
</div>

						</div>
					</div>
				</div>
			</div>
		</div>
	</div>
	<div class="meta-info">
		<div class="row">
			<div class="col xs-12">
				<form method="POST"  autocomplete="off" id="sug-form">
					<div class="site-panel">
						<div class="form__module">
							<div class="form__set">
								<button type="submit" name="submit" class="icon-search"></button>
								<input class="search-input" id="search-input" type="text" name="title" placeholder="Search">
								<ul id="suggestions" class="list-group position-absolute w-100" style="z-index: 1000;"></ul>
							</div>
							<div id="result" class="list-group"></div>
						</div>
						<div class="downoad">
							<a href="#" id="download_btn"><span class="icon-download"></span>Download</a>
						</div>
					</div>
				</form>
			</div>
		</div>
		<div class="row">
			<div class="col xs-12">
				<div class="questionaries__showcase" id="question_popup" style="display: flex;">
					<div class="tbl-wrap">
						<table id="tracking__table">
							<thead>
								<tr>
									<th class="dtser">Product ID</th>
									<th>barcode</th>
									<th>Photo</th>
									<th>Product Title</th>
									<th>Category</th>
									<th>Quantity</th>
									<th>Buying Price</th>
									<th>Selling Price</th>
									<th>Product Added</th>
									<th>Actions</th>
								</tr>
							</thead>
							<tbody>
								<?php
    // Display search results or all products if no search
								if ($search_result && mysqli_num_rows($search_result) > 0) {
									while ($row = mysqli_fetch_assoc($search_result)) {
										$formatted_date = read_date($row['date']);
										echo "<tr>
										<td>{$row['id']}</td>
										<td>{$row['barcode']}</td>
										<td>";
            // Display image based on media_id
										if ($row['media_id'] == '0' || empty($row['media_id'])) {
        // Display a default image
											echo "<img class='img-avatar img-circle profile-photo' src='uploads/products/no_image.png' alt='No image available'>";
										} else {
        // Check if the file exists at the given path
											$image_path = 'uploads/products/' . $row['image'];

        // Check if file exists and is accessible
											if (file_exists($image_path)) {
												echo "<img class='img-avatar img-circle profile-photo' src='{$image_path}' alt='{$row['name']}'>";
											} else {
            // If file doesn't exist, show the default image
												echo "<img class='img-avatar img-circle profile-photo' src='uploads/products/no_image.png' alt='Image not found'>";
											}
										}
										echo "</td>
										<td>{$row['name']}</td>
										<td>{$row['categorie']}</td>
										<td>{$row['quantity']}</td>
										<td>{$row['buy_price']}</td>
										<td>{$row['sale_price']}</td>
										<td>{$formatted_date}</td>
										<td class='text-center'>
										<div class='btn-group'>
										<a href='edit_product.php?id={$row['id']}' class='btn btn-info btn-xs' title='Edit' data-toggle='tooltip'>
										<span class='icon-edit'></span>
										</a>
										<a href='delete_product.php?id={$row['id']}' class='btn btn-danger btn-xs' title='Delete' data-toggle='tooltip'>
										<span class='icon-trash'></span>
										</a>
										</div>
										</td>
										</tr>";
									}
								} else {
        // Show all products if no search
									foreach ($products as $product) {
										$formatted_date = read_date($product['date']);
										echo "<tr>
										<td>" . remove_junk($product['id']) . "</td>
										<td>" . remove_junk($product['barcode']) . "</td>
										<td>";
            // Display image based on media_id
										if ($product['media_id'] == '0' || empty($product['media_id'])) {
											echo "<img class='img-avatar img-circle profile-photo' src='uploads/products/no_image.png' alt='No image available'>";
										} else {
											echo "<img class='img-avatar img-circle profile-photo' src='uploads/products/{$product['image']}' alt='{$product['name']}'>";
										}
										echo "</td>
										<td>" . remove_junk($product['name']) . "</td>
										<td class='text-center'>" . remove_junk($product['categorie']) . "</td>
										<td class='text-center'>" . remove_junk($product['quantity']) . "</td>
										<td class='text-center'>" . remove_junk($product['buy_price']) . "</td>
										<td class='text-center'>" . remove_junk($product['sale_price']) . "</td>
										<td class='text-center'>{$formatted_date}</td>
										<td class='text-center'>
										<div class='btn-group'>
										<a href='edit_product.php?id=" . (int)$product['id'] . "' class='btn btn-info btn-xs' title='Edit' data-toggle='tooltip'>
										<span class='icon-edit'></span>
										</a>
										<a href='delete_product.php?id=" . (int)$product['id'] . "' class='btn btn-danger btn-xs' title='Delete' data-toggle='tooltip'>
										<span class='icon-trash'></span>
										</a>
										</div>
										</td>
										</tr>";
									}
								}
								?>
							</tbody>
						</table>
					</div>
				</div>
			</div>
		</div>

		<?php include_once('layouts/footer.php'); ?>
		<script>
			document.getElementById('download_btn').addEventListener('click', function (event) {
				event.preventDefault();

        // Clone the table so original stays intact
				const tableClone = document.getElementById('tracking__table').cloneNode(true);

        // Iterate over rows and remove the last cell
				Array.from(tableClone.rows).forEach(row => {
					if (row.cells.length > 0) {
						row.deleteCell(row.cells.length - 1);
						row.deleteCell(row.cells.length - 7);
					}
				});

        // Get the modified table's HTML content
				const htmlContent = tableClone.innerHTML;
				console.log(htmlContent);

				const formData = new FormData();
				formData.append('htmlContent', htmlContent);
				formData.append('title', "Inventory Report");

				fetch('pdfDownload.php', {
					method: 'POST',
					body: formData
				})
				.then(response => response.blob())
				.then(blob => {
					const link = document.createElement('a');
					link.href = window.URL.createObjectURL(blob);
					link.download = 'document.pdf';
					link.click();
				})
				.catch(error => console.error('Error:', error));
			});
		</script>