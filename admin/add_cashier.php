<?php
session_start();
include 'config.php';

if (!isset($_SESSION["id"]) || $_SESSION["usertype"] != "admin") {
    header("location: admin_login.php");
    exit;
}

// Define success message
$success_message = '';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $full_name = $_POST['full_name'];
    $email = $_POST['email'];
    $password = password_hash($_POST['password'], PASSWORD_DEFAULT); // Hash password
    $created_at = date('Y-m-d H:i:s');

    $sql_insert_cashier = "INSERT INTO cashiers (full_name, email, password, created_at) VALUES (?, ?, ?, ?)";
    $stmt = mysqli_prepare($conn, $sql_insert_cashier);
    mysqli_stmt_bind_param($stmt, "ssss", $full_name, $email, $password, $created_at);
    
    if (mysqli_stmt_execute($stmt)) {
        $success_message = "Cashier added successfully.";
    } else {
        $success_message = "Error adding cashier. Please try again.";
    }
    mysqli_stmt_close($stmt);
}
?>

<!DOCTYPE html>
<html>

<head>
    <title>Add Cashier</title>
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
    <link rel="stylesheet" href="./css/style.css">

</head>

<body>
    <?php include 'admin_navbar.php'; ?>

    <div class="container mt-5 mb-5">
        <div class="card mx-auto" style="max-width: 600px;">
            <div class="card-body">
                <h2 class="text-center">Add Cashier</h2>
                <?php if ($success_message) : ?>
                    <div class="alert alert-success"><?php echo $success_message; ?></div>
                <?php endif; ?>
                <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="post">
                    <div class="form-group">
                        <label>Full Name</label>
                        <input type="text" class="form-control" name="full_name" required>
                    </div>
                    <div class="form-group">
                        <label>Email</label>
                        <input type="email" class="form-control" name="email" required>
                    </div>
                    <div class="form-group">
                        <label>Password</label>
                        <input type="password" class="form-control" name="password" required>
                    </div>
                    <button type="submit" class="btn btn-primary">Add Cashier</button>
                    <a class="btn btn-outline-dark" href="view_cashiers.php">View Cashiers</a>
                </form>
            </div>
        </div>
    </div>
</body>

</html>
