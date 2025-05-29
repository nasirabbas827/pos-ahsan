<?php
session_start();
include 'config.php';

if (!isset($_SESSION["id"]) || $_SESSION["usertype"] != "admin") {
    header("location: admin_login.php");
    exit;
}

$success_message = '';
$id = isset($_GET['id']) ? intval($_GET['id']) : 0;

// Fetch existing cashier
$stmt = mysqli_prepare($conn, "SELECT * FROM cashiers WHERE id = ?");
mysqli_stmt_bind_param($stmt, "i", $id);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$cashier = mysqli_fetch_assoc($result);
mysqli_stmt_close($stmt);

if (!$cashier) {
    die("Cashier not found.");
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $full_name = $_POST['full_name'];
    $email = $_POST['email'];
    $update_password = !empty($_POST['password']);
    $query = "UPDATE cashiers SET full_name = ?, email = ?" . ($update_password ? ", password = ?" : "") . " WHERE id = ?";
    
    if ($update_password) {
        $password = password_hash($_POST['password'], PASSWORD_DEFAULT);
        $stmt = mysqli_prepare($conn, $query);
        mysqli_stmt_bind_param($stmt, "sssi", $full_name, $email, $password, $id);
    } else {
        $stmt = mysqli_prepare($conn, $query);
        mysqli_stmt_bind_param($stmt, "ssi", $full_name, $email, $id);
    }

    if (mysqli_stmt_execute($stmt)) {
        $success_message = "Cashier updated successfully.";
        // Refresh cashier data
        $cashier['full_name'] = $full_name;
        $cashier['email'] = $email;
    } else {
        $success_message = "Failed to update cashier.";
    }

    mysqli_stmt_close($stmt);
}
?>

<!DOCTYPE html>
<html>

<head>
    <title>Edit Cashier</title>
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
    <link rel="stylesheet" href="./css/style.css">
</head>

<body>
    <?php include 'admin_navbar.php'; ?>

            <div class="container mt-5 mb-5">
        <div class="card mx-auto" style="max-width: 600px;">
            <div class="card-body">
        <h2>Edit Cashier</h2>
        <?php if ($success_message) : ?>
            <div class="alert alert-info"><?php echo $success_message; ?></div>
        <?php endif; ?>
        <form method="post">
            <div class="form-group">
                <label>Full Name</label>
                <input type="text" name="full_name" class="form-control" value="<?php echo htmlspecialchars($cashier['full_name']); ?>" required>
            </div>
            <div class="form-group">
                <label>Email</label>
                <input type="email" name="email" class="form-control" value="<?php echo htmlspecialchars($cashier['email']); ?>" required>
            </div>
            <div class="form-group">
                <label>Password (leave blank to keep unchanged)</label>
                <input type="password" name="password" class="form-control">
            </div>
            <button type="submit" class="btn btn-primary">Update Cashier</button>
            <a href="view_cashiers.php" class="btn btn-secondary">Back</a>
        </form>
    </div>
    </div>
    </div>
</body>

</html>
