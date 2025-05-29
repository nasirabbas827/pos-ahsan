<?php
session_start();
include 'config.php';

if (!isset($_SESSION["id"]) || $_SESSION["usertype"] != "admin") {
    header("location: admin_login.php");
    exit;
}

// Fetch all returns with cashier info
$sql = "SELECT r.id, r.product_name, r.quantity, r.reason, r.return_date, c.full_name AS cashier_name
        FROM returns r
        JOIN cashiers c ON r.cashier_id = c.id
        ORDER BY r.return_date DESC";

$result = mysqli_query($conn, $sql);
?>

<!DOCTYPE html>
<html>

<head>
    <title>View Returns</title>
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
    <link rel="stylesheet" href="./css/style.css">
</head>

<body>
    <?php include 'admin_navbar.php'; ?>

    <div class="container mt-5">
        <h2 class="mb-4">All Returned Products</h2>
        
        <table class="table table-bordered table-striped">
            <thead class="thead-dark">
                <tr>
                    <th>ID</th>
                    <th>Product Name</th>
                    <th>Quantity</th>
                    <th>Reason</th>
                    <th>Return Date</th>
                    <th>Cashier</th>
                </tr>
            </thead>
            <tbody>
                <?php while ($row = mysqli_fetch_assoc($result)) : ?>
                    <tr>
                        <td><?php echo $row['id']; ?></td>
                        <td><?php echo htmlspecialchars($row['product_name']); ?></td>
                        <td><?php echo $row['quantity']; ?></td>
                        <td><?php echo htmlspecialchars($row['reason']); ?></td>
                        <td><?php echo $row['return_date']; ?></td>
                        <td><?php echo htmlspecialchars($row['cashier_name']); ?></td>
                    </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
    </div>
</body>

</html>
