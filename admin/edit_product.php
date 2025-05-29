<?php
// Database connection details
session_start();
include 'config.php';

if (!isset($_SESSION["id"]) || $_SESSION["usertype"] != "admin") {
    header("location: admin_login.php");
    exit;
}

// Check if product ID is provided in the URL
if (!isset($_GET['product_id'])) {
    header("location: view_products.php");
    exit;
}

$product_id = $_GET['product_id'];

// Fetch product details
$sql_product = "SELECT * FROM products WHERE ProductID = ?";
$stmt_product = mysqli_prepare($conn, $sql_product);
mysqli_stmt_bind_param($stmt_product, "i", $product_id);
mysqli_stmt_execute($stmt_product);
$result_product = mysqli_stmt_get_result($stmt_product);

// If product not found, redirect to view_products.php
if (mysqli_num_rows($result_product) == 0) {
    header("location: view_products.php");
    exit;
}

$row_product = mysqli_fetch_assoc($result_product);

// If form is submitted, update product details
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $product_name = $_POST['product_name'];
    $description = $_POST['description'];
    $price = $_POST['price'];
    $stock_quantity = $_POST['stock_quantity'];
    $category_id = $_POST['category_id'];
    $updated_image = false;

    if (isset($_FILES['product_image']) && $_FILES['product_image']['error'] === UPLOAD_ERR_OK) {
        $file_extension = pathinfo($_FILES['product_image']['name'], PATHINFO_EXTENSION);
        $image_filename = uniqid() . '.' . $file_extension;
        $upload_path = 'products_images/' . $image_filename;
        if (move_uploaded_file($_FILES['product_image']['tmp_name'], $upload_path)) {
            $updated_image = true;
        }
    }

    $sql_update_product = "UPDATE products 
                           SET ProductName = ?, Description = ?, Price = ?, StockQuantity = ?, CategoryID = ?";
    if ($updated_image) {
        $sql_update_product .= ", ImageURL = ?";
    }
    $sql_update_product .= " WHERE ProductID = ?";
    $stmt_update_product = mysqli_prepare($conn, $sql_update_product);

    if ($updated_image) {
        mysqli_stmt_bind_param($stmt_update_product, "ssdissi", $product_name, $description, $price, $stock_quantity, $category_id, $image_filename, $product_id);
    } else {
        mysqli_stmt_bind_param($stmt_update_product, "ssdissi", $product_name, $description, $price, $stock_quantity, $category_id, $product_id);
    }

    mysqli_stmt_execute($stmt_update_product);
    mysqli_stmt_close($stmt_update_product);

    header("location: view_products.php");
    exit;
}

// Fetch categories
$sql_categories = "SELECT id, name FROM categories";
$result_categories = mysqli_query($conn, $sql_categories);
?>

<!DOCTYPE html>
<html>

<head>
    <title>Edit Product</title>
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
    <link rel="stylesheet" href="./css/style.css">
</head>

<body>
    <?php include 'admin_navbar.php'; ?>

    <div class="container mt-5 mb-5">
    <div class="card mx-auto" style="max-width: 600px;">
    <div class="card-body">
        <h2 class="text-center">Edit Product</h2>
        <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>?product_id=<?php echo $product_id; ?>" method="post" enctype="multipart/form-data">
            <div class="form-group">
                <label>Product Name</label>
                <input type="text" class="form-control" name="product_name" value="<?php echo htmlspecialchars($row_product['ProductName']); ?>" required>
            </div>
            <div class="form-group">
                <label>Description</label>
                <textarea class="form-control" name="description" required><?php echo htmlspecialchars($row_product['Description']); ?></textarea>
            </div>
            <div class="form-group">
                <label>Price</label>
                <input type="number" class="form-control" name="price" value="<?php echo $row_product['Price']; ?>" required>
            </div>
            <div class="form-group">
                <label>Stock Quantity</label>
                <input type="number" class="form-control" name="stock_quantity" value="<?php echo $row_product['StockQuantity']; ?>" required>
            </div>
            <div class="form-group">
                <label>Category</label>
                <select class="form-control" name="category_id" required>
                    <?php while ($row_category = mysqli_fetch_assoc($result_categories)) : ?>
                        <option value="<?php echo $row_category['id']; ?>" <?php if ($row_category['id'] == $row_product['CategoryID']) echo 'selected'; ?>>
                            <?php echo $row_category['name']; ?>
                        </option>
                    <?php endwhile; ?>
                </select>
            </div>
            <div class="form-group">
                <label>Product Image</label><br>
                <img src="products_images/<?php echo $row_product['ImageURL']; ?>" alt="Product Image" style="max-width: 200px; max-height: 200px;">
                <input type="file" class="form-control-file mt-3" name="product_image" accept="image/png, image/jpeg, image/jpg">
                <small class="form-text text-muted">Upload a new image to replace the existing one. Leave blank to keep the current image.</small>
            </div>
            <button type="submit" class="btn btn-primary">Update </button>
            <a class="btn btn-outline-dark" href="view_products.php">Cancel</a>
        </form>
    </div>
    </div>
    </div>
</body>

</html>
