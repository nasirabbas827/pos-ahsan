<?php
session_start();
include '../config.php';

if (!isset($_SESSION["id"])) {
    header("Location: ../login.php");
    exit;
}

$is_cashier = $_SESSION["usertype"] === 'cashier';
$is_admin = $_SESSION["usertype"] === 'admin';

// ADD or EDIT Return
if ($is_cashier && $_SERVER["REQUEST_METHOD"] == "POST") {
    $id = $_POST['id'] ?? null;
    $product_name = trim($_POST['product_name']);
    $quantity = $_POST['quantity'];
    $reason = $_POST['reason'];

    if ($id) {
        $stmt = $conn->prepare("UPDATE returns SET product_name=?, quantity=?, reason=? WHERE id=? AND cashier_id=?");
        $stmt->bind_param("sisii", $product_name, $quantity, $reason, $id, $_SESSION["id"]);
    } else {
        $stmt = $conn->prepare("INSERT INTO returns (product_name, quantity, reason, cashier_id) VALUES (?, ?, ?, ?)");
        $stmt->bind_param("sisi", $product_name, $quantity, $reason, $_SESSION["id"]);
    }
    $stmt->execute();
    header("Location: returns.php");
    exit;
}

// DELETE Return
if ($is_cashier && isset($_GET['delete'])) {
    $delete_id = intval($_GET['delete']);
    $stmt = $conn->prepare("DELETE FROM returns WHERE id=? AND cashier_id=?");
    $stmt->bind_param("ii", $delete_id, $_SESSION["id"]);
    $stmt->execute();
    header("Location: returns.php");
    exit;
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Returned Products</title>
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
    <link rel="stylesheet" href="../css/style.css">

</head>
<body>
<?php include('navbar.php'); ?>

<div class="container mt-5">
    <h3>Returned Products</h3>

    <?php if ($is_cashier): ?>
    <form method="POST" class="mb-4">
        <input type="hidden" name="id" id="return_id">
        <div class="form-row">
            <div class="col">
                <input type="text" name="product_name" class="form-control" placeholder="Product Name" required>
            </div>
            <div class="col">
                <input type="number" name="quantity" class="form-control" placeholder="Quantity" required>
            </div>
            <div class="col">
                <input type="text" name="reason" class="form-control" placeholder="Reason">
            </div>
            <div class="col">
                <button type="submit" class="btn btn-primary">Save Return</button>
            </div>
        </div>
    </form>
    <?php endif; ?>

    <table class="table table-bordered">
        <thead class="thead-dark">
            <tr>
                <th>#</th>
                <th>Product Name</th>
                <th>Qty</th>
                <th>Reason</th>
                <th>Date</th>
                <th>Cashier</th>
                <?php if ($is_cashier): ?><th>Actions</th><?php endif; ?>
            </tr>
        </thead>
        <tbody>
            <?php
            $sql = "SELECT r.*, u.full_name 
                    FROM returns r
                    JOIN cashiers u ON r.cashier_id = u.id";
            if ($is_cashier) {
                $sql .= " WHERE r.cashier_id = " . $_SESSION['id'];
            }
            $sql .= " ORDER BY r.return_date DESC";
            $res = mysqli_query($conn, $sql);
            $i = 1;
            while ($row = mysqli_fetch_assoc($res)):
            ?>
            <tr>
                <td><?= $i++ ?></td>
                <td><?= htmlspecialchars($row['product_name']) ?></td>
                <td><?= $row['quantity'] ?></td>
                <td><?= $row['reason'] ?></td>
                <td><?= $row['return_date'] ?></td>
                <td><?= $row['full_name'] ?></td>
                <?php if ($is_cashier): ?>
                <td>
                    <a href="?delete=<?= $row['id'] ?>" class="btn btn-sm btn-danger" onclick="return confirm('Delete this return?')">Delete</a>
                </td>
                <?php endif; ?>
            </tr>
            <?php endwhile; ?>
        </tbody>
    </table>
</div>
</body>
</html>


