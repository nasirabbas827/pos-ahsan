<?php
session_start();
include 'config.php';

if (!isset($_SESSION["id"]) || $_SESSION["usertype"] != "admin") {
    header("location: admin_login.php");
    exit;
}

$start_date = isset($_GET['start_date']) ? $_GET['start_date'] : '';
$end_date = isset($_GET['end_date']) ? $_GET['end_date'] : '';
$sales = [];
$total_sales = 0;
$total_amount = 0;

// Handle CSV download
if (isset($_GET['download_csv'])) {
    $csv_data = "Sale ID,Sale Date,Customer Name,Customer Phone,Cashier,Items,Total Amount\n";
    
    $query = "SELECT s.sale_id, s.sale_date, s.customer_name, s.customer_phone, u.full_name as cashier_name, s.total_amount
              FROM sales s
              JOIN cashiers u ON s.cashier_id = u.id";
    $params = [];
    $types = "";
    
    if ($start_date && $end_date) {
        $query .= " WHERE s.sale_date BETWEEN ? AND ?";
        $params[] = $start_date . " 00:00:00";
        $params[] = $end_date . " 23:59:59";
        $types .= "ss";
    }

    $stmt = mysqli_prepare($conn, $query);
    if ($params) {
        mysqli_stmt_bind_param($stmt, $types, ...$params);
    }
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);

    while ($sale = mysqli_fetch_assoc($result)) {
        $items_query = "SELECT p.ProductName, si.quantity, si.price FROM sale_items si
                        JOIN products p ON si.product_id = p.ProductID WHERE si.sale_id = ?";
        $items_stmt = mysqli_prepare($conn, $items_query);
        mysqli_stmt_bind_param($items_stmt, "i", $sale['sale_id']);
        mysqli_stmt_execute($items_stmt);
        $items_result = mysqli_stmt_get_result($items_stmt);

        $items = [];
        while ($item = mysqli_fetch_assoc($items_result)) {
            $items[] = "{$item['ProductName']} (Qty: {$item['quantity']}, Price: \${$item['price']})";
        }

        $items_string = implode("; ", $items);
        $csv_data .= "\"{$sale['sale_id']}\",\"{$sale['sale_date']}\",\"{$sale['customer_name']}\",\"{$sale['customer_phone']}\",\"{$sale['cashier_name']}\",\"{$items_string}\",\"{$sale['total_amount']}\"\n";
    }

    header('Content-Type: text/csv');
    header('Content-Disposition: attachment; filename="admin_sales_report_' . date('Y-m-d') . '.csv"');
    echo $csv_data;
    exit;
}

// Fetch all sales
$query = "SELECT s.sale_id, s.sale_date, s.customer_name, s.customer_phone, u.full_name as cashier_name, s.total_amount
          FROM sales s
          JOIN cashiers u ON s.cashier_id = u.id";
$params = [];
$types = "";

if ($start_date && $end_date) {
    $query .= " WHERE s.sale_date BETWEEN ? AND ?";
    $params[] = $start_date . " 00:00:00";
    $params[] = $end_date . " 23:59:59";
    $types .= "ss";
}

$query .= " ORDER BY s.sale_date DESC";
$stmt = mysqli_prepare($conn, $query);
if ($params) {
    mysqli_stmt_bind_param($stmt, $types, ...$params);
}
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);

while ($sale = mysqli_fetch_assoc($result)) {
    $items_query = "SELECT p.ProductName, si.quantity, si.price, si.total_amount
                    FROM sale_items si
                    JOIN products p ON si.product_id = p.ProductID
                    WHERE si.sale_id = ?";
    $items_stmt = mysqli_prepare($conn, $items_query);
    mysqli_stmt_bind_param($items_stmt, "i", $sale['sale_id']);
    mysqli_stmt_execute($items_stmt);
    $items_result = mysqli_stmt_get_result($items_stmt);

    $items = [];
    while ($item = mysqli_fetch_assoc($items_result)) {
        $items[] = $item;
    }

    $sales[] = [
        'sale_id' => $sale['sale_id'],
        'sale_date' => $sale['sale_date'],
        'customer_name' => $sale['customer_name'],
        'customer_phone' => $sale['customer_phone'],
        'cashier_name' => $sale['cashier_name'],
        'total_amount' => $sale['total_amount'],
        'items' => $items
    ];

    $total_sales++;
    $total_amount += $sale['total_amount'];
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Admin - All Sales</title>
    <meta charset="UTF-8">
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
    <link rel="stylesheet" href="./css/style.css">
</head>
<body>
<?php include 'admin_navbar.php'; ?>

<div class="container mt-4">
    <h2 class="mb-4 text-center">All Sales Records</h2>

    <div class="card p-3 mb-4">
        <form method="get" class="form-inline">
            <label class="mr-2">Start Date:</label>
            <input type="date" name="start_date" class="form-control mr-3" value="<?php echo htmlspecialchars($start_date); ?>" required>
            <label class="mr-2">End Date:</label>
            <input type="date" name="end_date" class="form-control mr-3" value="<?php echo htmlspecialchars($end_date); ?>" required>
            <button type="submit" class="btn btn-primary mr-2">Filter</button>
            <a href="?start_date=<?php echo urlencode($start_date); ?>&end_date=<?php echo urlencode($end_date); ?>&download_csv=1" class="btn btn-outline-secondary">Download CSV</a>
        </form>
    </div>

    <div class="mb-4 text-center bg-primary text-white p-3 rounded">
        <h5>Total Sales: <?php echo $total_sales; ?></h5>
        <h4>Total Amount: $<?php echo number_format($total_amount, 2); ?></h4>
    </div>

    <?php if (empty($sales)): ?>
        <div class="alert alert-warning text-center">No sales found for the selected date range.</div>
    <?php else: ?>
        <?php foreach ($sales as $sale): ?>
            <div class="card mb-4">
                <div class="card-header d-flex justify-content-between">
                    <strong>Sale #<?php echo $sale['sale_id']; ?></strong>
                    <span><?php echo $sale['sale_date']; ?></span>
                </div>
                <div class="card-body">
                    <p><strong>Customer:</strong> <?php echo $sale['customer_name'] ?: 'N/A'; ?></p>
                    <p><strong>Phone:</strong> <?php echo $sale['customer_phone'] ?: 'N/A'; ?></p>
                    <p><strong>Cashier:</strong> <?php echo htmlspecialchars($sale['cashier_name']); ?></p>

                    <div class="table-responsive">
                        <table class="table table-sm table-bordered mt-3">
                            <thead class="thead-light">
                                <tr>
                                    <th>Product</th>
                                    <th>Quantity</th>
                                    <th>Price ($)</th>
                                    <th>Total ($)</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($sale['items'] as $item): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($item['ProductName']); ?></td>
                                        <td><?php echo $item['quantity']; ?></td>
                                        <td><?php echo number_format($item['price'], 2); ?></td>
                                        <td><?php echo number_format($item['total_amount'], 2); ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>

                    <p class="mt-2"><strong>Total:</strong> $<?php echo number_format($sale['total_amount'], 2); ?></p>
                </div>
            </div>
        <?php endforeach; ?>
    <?php endif; ?>
</div>

</body>
</html>
