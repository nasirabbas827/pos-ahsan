<?php
session_start();
include('config.php');

if ($_SERVER["REQUEST_METHOD"] == "POST") {

    // retrieve form data
    $username = $_POST["username"];
    $password = $_POST["password"];

    // check if admin credentials are valid
    $sql = "SELECT * FROM admins WHERE username = ? AND password = ?";
    $stmt = mysqli_prepare($conn, $sql);
    mysqli_stmt_bind_param($stmt, "ss", $username, $password);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);

    if (mysqli_num_rows($result) == 1) {
        // set session variables and redirect to admin home page
        $row = mysqli_fetch_assoc($result);
        $_SESSION["id"] = $row["id"];
        $_SESSION["usertype"] = "admin";
        $_SESSION["username"] = $row["username"];
        header("location: admin_home.php");
        exit;
    } else {
        $error = "Invalid username or password.";
    }

    mysqli_stmt_close($stmt);
    mysqli_close($conn);
}
?>

<!DOCTYPE html>
<html>

<head>
    <title>Admin Login</title>
    <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
  <link rel="stylesheet" href="css/style.css">
</head>

<body>

    <div class="container mt-5">
    <div class="card mx-auto" style="max-width: 400px;">
    <div class="card-body">
        <h2 class="text-center">Admin Login</h2>

        <?php if (isset($error)) { ?>
            <p style="color: red;"><?php echo $error; ?></p>
        <?php } ?>

        <form method="post" class="mt-3">

            <div class="form-group">
                <label for="username">Username:</label>
                <input type="text" class="form-control" name="username" required>
            </div>

            <div class="form-group">
                <label for="password">Password:</label>
                <input type="password" class="form-control" name="password" required>
            </div>
            <div class="form-group text-center">
            <button type="submit" class=" btn btn-primary mt-3  ">Log In</button>
            <a class="btn btn-outline-dark mt-3" href="../index.php">Go To HomePage</a>

            </div>
            </div>
            </div>


        </form>
    </div>

    <script src="https://ajax.googleapis.com/ajax/libs/jquery/3.5.1/jquery.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/popper.js/1.16.0/umd/popper.min.js"></script>
    <script src="https://maxcdn.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>

</body>

</html>

