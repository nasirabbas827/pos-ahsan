<?php
session_start();
include '../config.php';

if (!isset($_SESSION["id"]) || $_SESSION["usertype"] != "cashier") {
    header("Location: ../login.php");
    exit;
}

// Get total sales for last 7 days
$seven_days_ago = date('Y-m-d H:i:s', strtotime('-7 days'));
$total_sales_query = "SELECT SUM(total_amount) as total_sales 
                     FROM sales 
                     WHERE cashier_id = ? AND sale_date >= ?";
$total_sales_stmt = mysqli_prepare($conn, $total_sales_query);
mysqli_stmt_bind_param($total_sales_stmt, "is", $_SESSION['id'], $seven_days_ago);
mysqli_stmt_execute($total_sales_stmt);
$total_sales_result = mysqli_stmt_get_result($total_sales_stmt);
$total_sales = mysqli_fetch_assoc($total_sales_result)['total_sales'] ?? 0.00;

// Get low stock products
$low_stock_query = "SELECT ProductName, StockQuantity 
                   FROM products 
                   WHERE StockQuantity <= 10 
                   ORDER BY StockQuantity ASC";
$low_stock_result = mysqli_query($conn, $low_stock_query);

// Get daily sales data for chart
$daily_sales_data = [];
$dates = [];
for ($i = 6; $i >= 0; $i--) {
    $date = date('Y-m-d', strtotime("-$i days"));
    $dates[] = date('M d', strtotime($date));
    $daily_query = "SELECT SUM(total_amount) as daily_total 
                    FROM sales 
                    WHERE cashier_id = ? AND DATE(sale_date) = ?";
    $daily_stmt = mysqli_prepare($conn, $daily_query);
    mysqli_stmt_bind_param($daily_stmt, "is", $_SESSION['id'], $date);
    mysqli_stmt_execute($daily_stmt);
    $daily_result = mysqli_stmt_get_result($daily_stmt);
    $daily_sales_data[] = mysqli_fetch_assoc($daily_result)['daily_total'] ?? 0.00;
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Cashier Dashboard</title>
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <link rel="stylesheet" href="../css/style.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</head>
<body>
<?php include('navbar.php'); ?>

<div class="container mt-5">
    <h2 class="mb-4"><i class="fas fa-user-circle mr-2"></i>Welcome, <?php echo $_SESSION['full_name']; ?>!</h2>

    <!-- Action Buttons -->
    <div class="mb-4">
        <a href="process_sale.php" class="btn btn-success"><i class="fas fa-shopping-cart mr-2"></i>Process Sale</a>
        <a href="reports.php" class="btn btn-success"><i class="fas fa-file-alt mr-2"></i>Generate Report</a>
        <a href="returns.php" class="btn btn-success"><i class="fas fa-boxes mr-2"></i>Return Products</a>
    </div>

    <!-- Sales Summary -->
    <div class="card mb-4">
        <div class="card-header bg-primary text-white">
            <i class="fas fa-dollar-sign mr-2"></i>Last 7 Days Sales
        </div>
        <div class="card-body">
            <h4>Total Sales: $<?php echo number_format($total_sales, 2); ?></h4>
        </div>
    </div>

    <!-- Low Stock Products -->
    <div class="card mb-4">
        <div class="card-header bg-warning text-dark">
            <i class="fas fa-exclamation-triangle mr-2"></i>Low Stock Products (≤ 10 items)
        </div>
        <div class="card-body">
            <?php if (mysqli_num_rows($low_stock_result) > 0): ?>
                <ul class="list-group">
                    <?php while ($low_stock = mysqli_fetch_assoc($low_stock_result)): ?>
                        <li class="list-group-item">
                            <i class="fas fa-box-open mr-2"></i>
                            <?php echo $low_stock['ProductName']; ?>: 
                            <span class="badge badge-danger"><?php echo $low_stock['StockQuantity']; ?> in stock</span>
                        </li>
                    <?php endwhile; ?>
                </ul>
            <?php else: ?>
                <p>No low stock products at this time.</p>
            <?php endif; ?>
        </div>
    </div>

    <!-- Sales Chart -->
    <div class="card mb-4">
        <div class="card-header bg-info text-white">
            <i class="fas fa-chart-line mr-2"></i>Daily Sales (Last 7 Days)
        </div>
        <div class="card-body">
            <canvas id="salesChart"></canvas>
        </div>
    </div>

    <!-- Available Products -->
    <h4><i class="fas fa-boxes mr-2"></i>Available Products</h4>
    <div class="table-responsive mb-5">
        <table class="table table-bordered table-striped">
            <thead class="thead-dark">
                <tr>
                    <th>ProductID</th>
                    <th>ProductName</th>
                    <th>Description</th>
                    <th>Price</th>
                    <th>StockQuantity</th>
                    <th>Category</th>
                    <th>Image</th>
                    <th>Timestamp</th>
                </tr>
            </thead>
            <tbody>
                <?php
                $query = "SELECT p.*, c.name as category_name 
                          FROM products p 
                          LEFT JOIN categories c ON p.CategoryID = c.id";
                $result = mysqli_query($conn, $query);
                while ($row = mysqli_fetch_assoc($result)) {
                    echo "<tr>
                        <td>{$row['ProductID']}</td>
                        <td>{$row['ProductName']}</td>
                        <td>{$row['Description']}</td>
                        <td>\${$row['Price']}</td>
                        <td>{$row['StockQuantity']}</td>
                        <td>{$row['category_name']}</td>
                        <td><img src='../admin/products_images/{$row['ImageURL']}' alt='Image' width='50'></td>
                        <td>{$row['Timestamp']}</td>
                    </tr>";
                }
                ?>
            </tbody>
        </table>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const ctx = document.getElementById('salesChart').getContext('2d');
    new Chart(ctx, {
        type: 'line',
        data: {
            labels: <?php echo json_encode($dates); ?>,
            datasets: [{
                label: 'Daily Sales ($)',
                data: <?php echo json_encode($daily_sales_data); ?>,
                borderColor: 'rgba(54, 162, 235, 1)',
                backgroundColor: 'rgba(54, 162, 235, 0.2)',
                fill: true,
                tension: 0.4
            }]
        },
        options: {
            responsive: true,
            scales: {
                y: {
                    beginAtZero: true,
                    title: {
                        display: true,
                        text: 'Sales Amount ($)'
                    }
                },
                x: {
                    title: {
                        display: true,
                        text: 'Date'
                    }
                }
            }
        }
    });
});
</script>
</body>
</html>