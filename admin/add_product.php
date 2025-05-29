<?php
// Database connection details
session_start();
include 'config.php';

if (!isset($_SESSION["id"]) || $_SESSION["usertype"] != "admin") {
    header("location: admin_login.php");
    exit;
}

// Fetch categories from the database
$sql_categories = "SELECT id, name FROM categories";
$result_categories = mysqli_query($conn, $sql_categories);

// Define variables for success message
$success_message = '';

// If form is submitted, add product
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $product_name = $_POST['product_name'];
    $description = $_POST['description'];
    $price = $_POST['price'];
    $stock_quantity = $_POST['stock_quantity'];
    $category_id = $_POST['category_id'];

    // Get current timestamp
    $timestamp = date('Y-m-d H:i:s');

    // Check if file has been uploaded
    if ($_FILES['product_image']['error'] === UPLOAD_ERR_OK) {
        // Get file extension
        $file_extension = pathinfo($_FILES['product_image']['name'], PATHINFO_EXTENSION);
        // Generate unique filename
        $image_filename = uniqid() . '.' . $file_extension;
        // Set upload path
        $upload_path = 'products_images/' . $image_filename;
        // Upload image
        if (move_uploaded_file($_FILES['product_image']['tmp_name'], $upload_path)) {
            // Insert product into the database
            $sql_insert_product = "INSERT INTO products (ProductName, Description, Price, StockQuantity, CategoryID, ImageURL, Timestamp) VALUES (?, ?, ?, ?, ?, ?, ?)";
            $stmt_insert_product = mysqli_prepare($conn, $sql_insert_product);
            mysqli_stmt_bind_param($stmt_insert_product, "ssdisss", $product_name, $description, $price, $stock_quantity, $category_id, $image_filename, $timestamp);
            if (mysqli_stmt_execute($stmt_insert_product)) {
                $success_message = 'Product added successfully.';
            } else {
                $success_message = 'Failed to add product. Please try again.';
            }
            mysqli_stmt_close($stmt_insert_product);
        } else {
            $success_message = 'Failed to upload product image. Please try again.';
        }
    } else {
        $success_message = 'No product image uploaded.';
    }
}
?>

<!DOCTYPE html>
<html>

<head>
    <title>Admin Panel - Add Product</title>
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
    <link rel="stylesheet" href="./css/style.css">
</head>

<body>
    <?php include 'admin_navbar.php'; ?>

    <div class="container mt-5 mb-5">
        <div class="card mx-auto" style="max-width: 600px;">
            <div class="card-body">
                <h2 class="text-center">Add Product</h2>
                <!-- Display success message -->
                <?php if ($success_message) : ?>
                    <div class="alert alert-success"><?php echo $success_message; ?></div>
                <?php endif; ?>
                <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="post" enctype="multipart/form-data">
                    <div class="form-group">
                        <label>Product Name</label>
                        <input type="text" class="form-control" name="product_name" required>
                    </div>
                    <div class="form-group">
                        <label>Description</label>
                        <textarea class="form-control" name="description" required></textarea>
                    </div>
                    <div class="form-group">
                        <label>Price</label>
                        <input type="number" class="form-control" name="price" required>
                    </div>
                    <div class="form-group">
                        <label>Stock Quantity</label>
                        <input type="number" class="form-control" name="stock_quantity" required>
                    </div>
                    <div class="form-group">
                        <label>Category</label>
                        <select class="form-control" name="category_id" required>
                            <?php while ($row_category = mysqli_fetch_assoc($result_categories)) : ?>
                                <option value="<?php echo $row_category['id']; ?>"><?php echo $row_category['name']; ?></option>
                            <?php endwhile; ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Product Image</label>
                        <input type="file" class="form-control-file" name="product_image" accept="image/png, image/jpeg, image/jpg" required>
                    </div>
                    <button type="submit" class="btn btn-primary">Add Product</button>
                    <a class="btn btn-outline-dark" href="view_products.php">View Product</a>
                </form>
            </div>
        </div>
    </div>
</body>

</html>
