<?php
session_start();
include 'config.php';

$error_message = '';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $email = $_POST['email'];
    $password = $_POST['password'];

    $sql = "SELECT id, full_name, password FROM cashiers WHERE email = ?";
    $stmt = mysqli_prepare($conn, $sql);
    mysqli_stmt_bind_param($stmt, "s", $email);
    mysqli_stmt_execute($stmt);
    mysqli_stmt_store_result($stmt);

    if (mysqli_stmt_num_rows($stmt) == 1) {
        mysqli_stmt_bind_result($stmt, $id, $full_name, $hashed_password);
        mysqli_stmt_fetch($stmt);

        if (password_verify($password, $hashed_password)) {
            $_SESSION['id'] = $id;
            $_SESSION['usertype'] = 'cashier';
            $_SESSION['full_name'] = $full_name;
            header("Location: cashier/home.php");
            exit;
        } else {
            $error_message = "Invalid credentials.";
        }
    } else {
        $error_message = "Invalid credentials.";
    }

    mysqli_stmt_close($stmt);
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Cashier Login</title>
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
    <link rel="stylesheet" href="./css/style.css">

</head>
<body>
<div class="container mt-5">
    <div class="card mx-auto" style="max-width: 400px;">
        <div class="card-body">
            <h3 class="text-center">Cashier Login</h3>
            <?php if ($error_message): ?>
                <div class="alert alert-danger"><?php echo $error_message; ?></div>
            <?php endif; ?>
            <form method="post" action="">
                <div class="form-group">
                    <label>Email</label>
                    <input type="email" class="form-control" name="email" required>
                </div>
                <div class="form-group">
                    <label>Password</label>
                    <input type="password" class="form-control" name="password" required>
                </div>
                <button type="submit" class="btn btn-primary">Login</button>
            <a class="btn btn-outline-dark " href="index.php">Go To HomePage</a>

            </form>
        </div>
    </div>
</div>
</body>
</html>
