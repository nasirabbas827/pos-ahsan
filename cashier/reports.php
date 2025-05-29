<?php
session_start();
include '../config.php';

if (!isset($_SESSION["id"]) || $_SESSION["usertype"] != "cashier") {
    header("Location: ../login.php");
    exit;
}

$cashier_id = $_SESSION['id'];
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
              JOIN cashiers u ON s.cashier_id = u.id
              WHERE s.cashier_id = ?";
    $params = [$cashier_id];
    $types = "i";
    
    if ($start_date && $end_date) {
        $query .= " AND s.sale_date BETWEEN ? AND ?";
        $params[] = $start_date . " 00:00:00";
        $params[] = $end_date . " 23:59:59";
        $types .= "ss";
    }
    
    $stmt = mysqli_prepare($conn, $query);
    mysqli_stmt_bind_param($stmt, $types, ...$params);
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
            $items[] = "{$item['ProductName']} (Qty: {$item['quantity']}, Price: \${$item['price']})";
        }
        
        $items_string = implode("; ", $items);
        $customer_name = $sale['customer_name'] ? $sale['customer_name'] : 'N/A';
        $customer_phone = $sale['customer_phone'] ? $sale['customer_phone'] : 'N/A';
        
        $csv_data .= "\"{$sale['sale_id']}\",\"{$sale['sale_date']}\",\"{$customer_name}\",\"{$customer_phone}\",\"{$sale['cashier_name']}\",\"{$items_string}\",\"\${$sale['total_amount']}\"\n";
    }
    
    header('Content-Type: text/csv');
    header('Content-Disposition: attachment; filename="sales_report_' . date('Y-m-d') . '.csv"');
    echo $csv_data;
    exit;
}

// Fetch sales data
$query = "SELECT s.sale_id, s.sale_date, s.customer_name, s.customer_phone, u.full_name as cashier_name, s.total_amount
          FROM sales s
          JOIN cashiers u ON s.cashier_id = u.id
          WHERE s.cashier_id = ?";
$params = [$cashier_id];
$types = "i";

if ($start_date && $end_date) {
    $query .= " AND s.sale_date BETWEEN ? AND ?";
    $params[] = $start_date . " 00:00:00";
    $params[] = $end_date . " 23:59:59";
    $types .= "ss";
}

$query .= " ORDER BY s.sale_date DESC";
$stmt = mysqli_prepare($conn, $query);
mysqli_stmt_bind_param($stmt, $types, ...$params);
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
<html lang="en">
<head>
    <title>Sales Reports - POS</title>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
    <link rel="stylesheet" href="../css/style.css">
</head>
<body>
<?php include 'navbar.php'; ?>

<div class="container mt-4 mb-5">
    <div class="reports-container">
        <h2 class="mb-4"><i class="fas fa-chart-bar mr-2"></i>Sales Reports</h2>

        <!-- Filter Form -->
        <div class="filter-container mb-3 p-3 bg-light rounded">
            <form method="get" class="form-inline">
                <label class="mr-2">Start Date:</label>
                <input type="date" name="start_date" class="form-control mr-3" value="<?php echo htmlspecialchars($start_date); ?>" required>
                <label class="mr-2">End Date:</label>
                <input type="date" name="end_date" class="form-control mr-3" value="<?php echo htmlspecialchars($end_date); ?>" required>
                <button type="submit" class="btn btn-primary mr-2">Filter</button>
                <a href="?start_date=<?php echo urlencode($start_date); ?>&end_date=<?php echo urlencode($end_date); ?>&download_csv=1" class="btn btn-outline-secondary">Download CSV</a>
            </form>
        </div>

        <!-- Summary -->
        <div class="total-box text-white bg-primary text-center p-3 mb-4 rounded">
            <h5>Total Sales: <?php echo $total_sales; ?></h5>
            <h4>Total Amount: $<?php echo number_format($total_amount, 2); ?></h4>
        </div>

        <!-- Sales Records -->
        <?php if (empty($sales)): ?>
            <div class="text-center text-muted p-5 border rounded">
                <h5>No sales found</h5>
                <p>Try changing the date range</p>
            </div>
        <?php else: ?>
            <?php foreach ($sales as $sale): ?>
                <div class="sale-card mb-4 p-3 border rounded bg-white">
                    <div class="d-flex justify-content-between">
                        <h5>Sale #<?php echo $sale['sale_id']; ?></h5>
                        <span><?php echo $sale['sale_date']; ?></span>
                    </div>
                    <p><strong>Customer:</strong> <?php echo $sale['customer_name'] ?: 'N/A'; ?></p>
                    <p><strong>Phone:</strong> <?php echo $sale['customer_phone'] ?: 'N/A'; ?></p>
                    <p><strong>Cashier:</strong> <?php echo htmlspecialchars($sale['cashier_name']); ?></p>

                    <h6>Items:</h6>
                    <div class="table-responsive">
                        <table class="table table-bordered table-sm">
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

                    <p><strong>Total:</strong> $<?php echo number_format($sale['total_amount'], 2); ?></p>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
</div>

<script src="https://code.jquery.com/jquery-3.5.1.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@4.5.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
