<?php
session_start();
include 'config.php';

if (!isset($_SESSION["id"]) || $_SESSION["usertype"] != "admin") {
    header("location: admin_login.php");
    exit;
}

// Delete cashier if ID is passed via GET
if (isset($_GET['delete_id'])) {
    $delete_id = intval($_GET['delete_id']);
    $stmt = mysqli_prepare($conn, "DELETE FROM cashiers WHERE id = ?");
    mysqli_stmt_bind_param($stmt, "i", $delete_id);
    mysqli_stmt_execute($stmt);
    mysqli_stmt_close($stmt);
    header("Location: view_cashiers.php");
    exit;
}

// Fetch all cashiers
$result = mysqli_query($conn, "SELECT * FROM cashiers ORDER BY created_at DESC");
?>

<!DOCTYPE html>
<html>

<head>
    <title>View Cashiers</title>
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
    <link rel="stylesheet" href="./css/style.css">
</head>

<body>
    <?php include 'admin_navbar.php'; ?>

    <div class="container mt-5">
        <h2 class="mb-4">Cashiers List</h2>
        <a href="add_cashier.php" class="btn btn-success mb-3">Add Cashier</a>
        <table class="table table-bordered table-striped">
            <thead class="thead-dark">
                <tr>
                    <th>ID</th>
                    <th>Full Name</th>
                    <th>Email</th>
                    <th>Created At</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php while ($row = mysqli_fetch_assoc($result)) : ?>
                    <tr>
                        <td><?php echo $row['id']; ?></td>
                        <td><?php echo htmlspecialchars($row['full_name']); ?></td>
                        <td><?php echo htmlspecialchars($row['email']); ?></td>
                        <td><?php echo $row['created_at']; ?></td>
                        <td>
                            <a href="edit_cashier.php?id=<?php echo $row['id']; ?>" class="btn btn-primary btn-sm">Edit</a>
                            <a href="view_cashiers.php?delete_id=<?php echo $row['id']; ?>" class="btn btn-danger btn-sm" onclick="return confirm('Are you sure you want to delete this cashier?');">Delete</a>
                        </td>
                    </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
    </div>
</body>

</html>
