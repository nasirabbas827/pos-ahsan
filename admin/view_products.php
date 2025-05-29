<?php
// Database connection details
session_start();
include 'config.php';

if (!isset($_SESSION["id"]) || $_SESSION["usertype"] != "admin") {
    header("location: admin_login.php");
    exit;
}


// Fetch all products with category names
$sql_products = "SELECT p.*, c.name AS category_name FROM products p INNER JOIN categories c ON p.CategoryID = c.id";
$stmt_products = mysqli_prepare($conn, $sql_products);
mysqli_stmt_execute($stmt_products);
$result_products = mysqli_stmt_get_result($stmt_products);

// Define success and error messages
$success_message = '';
$error_message = '';

// If product is to be deleted
if (isset($_POST['delete_product'])) {
    $product_id = $_POST['product_id'];
    // Delete product from database
    $sql_delete_product = "DELETE FROM products WHERE ProductID = ?";
    $stmt_delete_product = mysqli_prepare($conn, $sql_delete_product);
    mysqli_stmt_bind_param($stmt_delete_product, "i", $product_id);
    if (mysqli_stmt_execute($stmt_delete_product)) {
        $success_message = 'Product deleted successfully.';
    } else {
        $error_message = 'Failed to delete product. Please try again.';
    }
    mysqli_stmt_close($stmt_delete_product);
}
?>

<!DOCTYPE html>
<html>

<head>
    <title> View Products</title>
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
    <link rel="stylesheet" href="./css/style.css">
    <style>
        .low-stock {
            background-color: #f8d7da !important;
        }
    </style>
</head>

<body>
    <?php include 'admin_navbar.php'; ?>

    <div class="container mt-5 mb-5">
        <h2 class="text-center">View Products</h2>
        <!-- Display success message -->
        <?php if ($success_message) : ?>
            <div class="alert alert-success"><?php echo $success_message; ?></div>
        <?php endif; ?>
        <!-- Display error message -->
        <?php if ($error_message) : ?>
            <div class="alert alert-danger"><?php echo $error_message; ?></div>
        <?php endif; ?>
        <div class="table-responsive">
            <table class="table table-bordered">
                <thead>
                    <tr>
                        <th>Product Name</th>
                        <th>Description</th>
                        <th>Price</th>
                        <th>Stock Quantity</th>
                        <th>Category</th>
                        <th>Image</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while ($row_product = mysqli_fetch_assoc($result_products)) : ?>
                        <tr <?php echo ($row_product['StockQuantity'] < 10) ? 'class="low-stock"' : ''; ?>>
                            <td><?php echo $row_product['ProductName']; ?></td>
                            <td><?php echo $row_product['Description']; ?></td>
                            <td><?php echo $row_product['Price']; ?></td>
                            <td><?php echo $row_product['StockQuantity']; ?></td>
                            <td><?php echo $row_product['category_name']; ?></td>
                            <td><img src="products_images/<?php echo $row_product['ImageURL']; ?>" style="max-width: 100px; max-height: 100px;"></td>
                            <td>
                                <a href="edit_product.php?product_id=<?php echo $row_product['ProductID']; ?>" class="btn btn-primary btn-sm">Edit</a>
                                <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="post" style="display: inline;">
                                    <input type="hidden" name="product_id" value="<?php echo $row_product['ProductID']; ?>">
                                    <button type="submit" name="delete_product" class="btn btn-danger btn-sm " onclick="return confirm('Are you sure you want to delete this product?');">Delete</button>
                                </form>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>
    </div>
</body>

</html>
