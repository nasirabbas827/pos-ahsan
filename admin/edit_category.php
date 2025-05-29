<?php
session_start();
include 'config.php';

// Check if user is logged in as admin
if (!isset($_SESSION["id"]) || $_SESSION["usertype"] != "admin") {
    header("location: admin_login.php");
    exit;
}

// Check if category ID is provided
if (!isset($_GET['id'])) {
    header("location: admin_categories.php");
    exit;
}

$category_id = $_GET['id'];

// Fetch category details
$sql_select = "SELECT * FROM categories WHERE id = $category_id";
$result = mysqli_query($conn, $sql_select);
$category = mysqli_fetch_assoc($result);

// Handle category update
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['update_category'])) {
    $category_name = $_POST['category_name'];
    $sql_update = "UPDATE categories SET name = '$category_name' WHERE id = $category_id";
    if (mysqli_query($conn, $sql_update)) {
        $success_message = "Category updated successfully.";
    } else {
        $error_message = "Error updating category: " . mysqli_error($conn);
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Edit Category</title>
    <!-- Add Bootstrap CSS -->
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.0/css/bootstrap.min.css">
    <link rel="stylesheet" href="./css/style.css">
</head>
<body>
    <?php include 'admin_navbar.php'; ?>
    <div class="container mt-4">
    <div class="card mx-auto" style="max-width: 600px;">
    <div class="card-body">
        <h2>Edit Category</h2>
        <?php if (isset($success_message)) : ?>
            <div class="alert alert-success"><?php echo $success_message; ?></div>
        <?php endif; ?>
        <?php if (isset($error_message)) : ?>
            <div class="alert alert-danger"><?php echo $error_message; ?></div>
        <?php endif; ?>
        <form method="post">
            <div class="form-group">
                <label for="category_name">Category Name:</label>
                <input type="text" class="form-control" id="category_name" name="category_name" value="<?php echo $category['name']; ?>" required>
            </div>
            <button type="submit" class="btn btn-primary" name="update_category">Update Category</button>
        </form>
    </div>
    </div>
    </div>

    <!-- Add Bootstrap JS (Optional) -->
    <script src="https://maxcdn.bootstrapcdn.com/bootstrap/4.0.0/js/bootstrap.min.js"></script>
</body>
</html>
