<?php
session_start();
include 'config.php';

if (!isset($_SESSION["id"]) || $_SESSION["usertype"] != "admin") {
    header("location: admin_login.php");
    exit;
}

// Count total cashiers
$res_cashier_count = mysqli_query($conn, "SELECT COUNT(*) AS total_cashiers FROM cashiers");
$total_cashiers = mysqli_fetch_assoc($res_cashier_count)['total_cashiers'];

// Count total products
$res_product_count = mysqli_query($conn, "SELECT COUNT(*) AS total_products FROM products");
$total_products = mysqli_fetch_assoc($res_product_count)['total_products'];

// Total sales for last 7 days
$seven_days_ago = date('Y-m-d H:i:s', strtotime('-7 days'));
$total_sales_query = "SELECT SUM(total_amount) as total_sales 
                     FROM sales 
                     WHERE sale_date >= ?";
$total_sales_stmt = mysqli_prepare($conn, $total_sales_query);
mysqli_stmt_bind_param($total_sales_stmt, "s", $seven_days_ago);
mysqli_stmt_execute($total_sales_stmt);
$total_sales_result = mysqli_stmt_get_result($total_sales_stmt);
$total_sales = mysqli_fetch_assoc($total_sales_result)['total_sales'] ?? 0.00;

// Low stock products
$low_stock_query = "SELECT ProductName, StockQuantity, Price, ProductID 
                   FROM products 
                   WHERE StockQuantity <= 10
                   ORDER BY StockQuantity ASC";
$low_stock_result = mysqli_query($conn, $low_stock_query);

// Recent product
$res_recent_product = mysqli_query($conn, "SELECT * FROM products ORDER BY Timestamp DESC LIMIT 1");
$recent_product = mysqli_fetch_assoc($res_recent_product);

// Recent cashier
$res_recent_cashier = mysqli_query($conn, "SELECT * FROM cashiers ORDER BY created_at DESC LIMIT 1");
$recent_cashier = mysqli_fetch_assoc($res_recent_cashier);

// Daily sales data for chart
$daily_sales_data = [];
$dates = [];
for ($i = 6; $i >= 0; $i--) {
    $date = date('Y-m-d', strtotime("-$i days"));
    $dates[] = date('M d', strtotime($date));
    $daily_query = "SELECT SUM(total_amount) as daily_total 
                    FROM sales 
                    WHERE DATE(sale_date) = ?";
    $daily_stmt = mysqli_prepare($conn, $daily_query);
    mysqli_stmt_bind_param($daily_stmt, "s", $date);
    mysqli_stmt_execute($daily_stmt);
    $daily_result = mysqli_stmt_get_result($daily_stmt);
    $daily_sales_data[] = mysqli_fetch_assoc($daily_result)['daily_total'] ?? 0.00;
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Admin Dashboard</title>
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <link rel="stylesheet" href="./css/style.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</head>
<body>
<?php include 'admin_navbar.php'; ?>

<div class="container mt-5">
    <h2 class="mb-4"><i class="fas fa-user-shield mr-2"></i>Admin Dashboard</h2>

    <!-- Summary Cards -->
    <div class="row mb-4">
        <div class="col-md-4">
            <div class="card text-white bg-info mb-3">
                <div class="card-body">
                    <h4 class="card-title"><i class="fas fa-users mr-2"></i>Total Cashiers</h4>
                    <p class="card-text"><?php echo $total_cashiers; ?></p>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card text-white bg-success mb-3">
                <div class="card-body">
                    <h4 class="card-title"><i class="fas fa-boxes mr-2"></i>Total Products</h4>
                    <p class="card-text"><?php echo $total_products; ?></p>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card text-white bg-primary mb-3">
                <div class="card-body">
                    <h4 class="card-title"><i class="fas fa-dollar-sign mr-2"></i>Last 7 Days Sales</h4>
                    <p class="card-text">$<?php echo number_format($total_sales, 2); ?></p>
                </div>
            </div>
        </div>
    </div>

    <!-- Low Stock Products -->
    <div class="card mb-4">
        <div class="card-header bg-warning text-dark">
            <i class="fas fa-exclamation-triangle mr-2"></i>Low Stock Products (≤ 5 items)
        </div>
        <div class="card-body">
            <?php if (mysqli_num_rows($low_stock_result) > 0): ?>
                <ul class="list-group">
                    <?php while ($low_stock = mysqli_fetch_assoc($low_stock_result)): ?>
                        <li class="list-group-item">
                            <i class="fas fa-box-open mr-2"></i>
                            <?php echo htmlspecialchars($low_stock['ProductName']); ?>: 
                            <span class="badge badge-danger"><?php echo $low_stock['StockQuantity']; ?> in stock</span>
                            (Price: $<?php echo number_format($low_stock['Price'], 2); ?>, ID: <?php echo $low_stock['ProductID']; ?>)
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

    <!-- Recent Actions -->
    <h4 class="mt-5"><i class="fas fa-clock mr-2"></i>Recent Actions</h4>
    <div class="row">
        <div class="col-md-6">
            <div class="card border-primary mb-3">
                <div class="card-header"><i class="fas fa-box mr-2"></i>Recent Product Added</div>
                <div class="card-body">
                    <?php if ($recent_product): ?>
                        <h5 class="card-title"><?php echo htmlspecialchars($recent_product['ProductName']); ?></h5>
                        <p class="card-text">
                            <i class="fas fa-cubes mr-2"></i>Stock: <?php echo $recent_product['StockQuantity']; ?><br>
                            <i class="fas fa-dollar-sign mr-2"></i>Price: $<?php echo number_format($recent_product['Price'], 2); ?><br>
                            <i class="fas fa-clock mr-2"></i>Added: <?php echo $recent_product['Timestamp']; ?>
                        </p>
                    <?php else: ?>
                        <p class="card-text">No products found.</p>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        <div class="col-md-6">
            <div class="card border-secondary mb-3">
                <div class="card-header"><i class="fas fa-user-plus mr-2"></i>Recent Cashier Added</div>
                <div class="card-body">
                    <?php if ($recent_cashier): ?>
                        <h5 class="card-title"><?php echo htmlspecialchars($recent_cashier['full_name']); ?></h5>
                        <p class="card-text">
                            <i class="fas fa-envelope mr-2"></i>Email: <?php echo htmlspecialchars($recent_cashier['email']); ?><br>
                            <i class="fas fa-clock mr-2"></i>Joined: <?php echo $recent_cashier['created_at']; ?>
                        </p>
                    <?php else: ?>
                        <p class="card-text">No cashiers found.</p>
                    <?php endif; ?>
                </div>
            </div>
        </div>
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