<?php
session_start();
include '../config.php';

if (!isset($_SESSION["id"]) || $_SESSION["usertype"] != "cashier") {
    header("Location: ../login.php");
    exit;
}

$success = '';
$error = '';
$subtotal = 0;
$total = 0;

// Fetch cart items from database
$session_id = session_id();
$cart_query = "SELECT c.*, p.StockQuantity FROM cart c JOIN products p ON c.product_id = p.ProductID WHERE c.session_id = ?";
$cart_stmt = mysqli_prepare($conn, $cart_query);
mysqli_stmt_bind_param($cart_stmt, "s", $session_id);
mysqli_stmt_execute($cart_stmt);
$cart_result = mysqli_stmt_get_result($cart_stmt);
$cart = [];
while ($row = mysqli_fetch_assoc($cart_result)) {
    $cart[] = [
        'product_id' => $row['product_id'],
        'name' => $row['product_name'],
        'price' => $row['price'],
        'quantity' => $row['quantity'],
        'stock' => $row['StockQuantity']
    ];
    $subtotal += $row['price'] * $row['quantity'];
}
$total = $subtotal;

// Handle search
$search_query = isset($_GET['search']) ? trim($_GET['search']) : '';
$category_filter = isset($_GET['category']) ? (int)$_GET['category'] : 0;

// Add to cart
if (isset($_POST['add_to_cart'])) {
    $product_id = (int)$_POST['product_id'];
    $quantity = (int)$_POST['quantity'];

    if ($quantity > 0) {
        $query = "SELECT ProductID, ProductName, Price, StockQuantity FROM products WHERE ProductID = ?";
        $stmt = mysqli_prepare($conn, $query);
        mysqli_stmt_bind_param($stmt, "i", $product_id);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);

        if ($row = mysqli_fetch_assoc($result)) {
            if ($quantity <= $row['StockQuantity']) {
                // Check if product already in cart
                $check_query = "SELECT id, quantity FROM cart WHERE session_id = ? AND product_id = ?";
                $check_stmt = mysqli_prepare($conn, $check_query);
                mysqli_stmt_bind_param($check_stmt, "si", $session_id, $product_id);
                mysqli_stmt_execute($check_stmt);
                $check_result = mysqli_stmt_get_result($check_stmt);

                if ($check_row = mysqli_fetch_assoc($check_result)) {
                    // Update quantity
                    $new_quantity = $check_row['quantity'] + $quantity;
                    $update_query = "UPDATE cart SET quantity = ? WHERE id = ?";
                    $update_stmt = mysqli_prepare($conn, $update_query);
                    mysqli_stmt_bind_param($update_stmt, "ii", $new_quantity, $check_row['id']);
                    mysqli_stmt_execute($update_stmt);
                } else {
                    // Insert new cart item
                    $insert_query = "INSERT INTO cart (session_id, product_id, quantity, price, product_name) VALUES (?, ?, ?, ?, ?)";
                    $insert_stmt = mysqli_prepare($conn, $insert_query);
                    mysqli_stmt_bind_param($insert_stmt, "siids", $session_id, $product_id, $quantity, $row['Price'], $row['ProductName']);
                    mysqli_stmt_execute($insert_stmt);
                }
                header("Location: process_sale.php");
            } else {
                $error = "Insufficient stock for {$row['ProductName']}. Available: {$row['StockQuantity']} units.";
            }
        } else {
            $error = "Product not found.";
        }
    } else {
        $error = "Quantity must be greater than zero.";
    }
}

// Remove item from cart
if (isset($_POST['remove_item']) && isset($_POST['remove_index'])) {
    $index = (int)$_POST['remove_index'];
    if (isset($cart[$index])) {
        $cart_id = $cart[$index]['product_id'];
        $delete_query = "DELETE FROM cart WHERE session_id = ? AND product_id = ?";
        $delete_stmt = mysqli_prepare($conn, $delete_query);
        mysqli_stmt_bind_param($delete_stmt, "si", $session_id, $cart_id);
        mysqli_stmt_execute($delete_stmt);
        header("Location: process_sale.php");
    }
}

// Clear cart
if (isset($_POST['clear_cart'])) {
    $delete_query = "DELETE FROM cart WHERE session_id = ?";
    $delete_stmt = mysqli_prepare($conn, $delete_query);
    mysqli_stmt_bind_param($delete_stmt, "s", $session_id);
    mysqli_stmt_execute($delete_stmt);
    header("Location: process_sale.php");
}

// Process sale
if (isset($_POST['process_sale'])) {
    $customer_name = trim($_POST['customer_name']);
    $customer_phone = trim($_POST['customer_phone']);
    $cashier_id = $_SESSION['id'];

    if (empty($cart)) {
        $error = "Cart is empty.";
    } else {
        // Start transaction
        mysqli_begin_transaction($conn);
        $success = true;
        $receipt_items = [];

        // Validate stock for all items
        foreach ($cart as $item) {
            $product_id = $item['product_id'];
            $quantity = $item['quantity'];

            $query = "SELECT ProductName, StockQuantity FROM products WHERE ProductID = ? FOR UPDATE";
            $stmt = mysqli_prepare($conn, $query);
            mysqli_stmt_bind_param($stmt, "i", $product_id);
            mysqli_stmt_execute($stmt);
            $result = mysqli_stmt_get_result($stmt);
            $row = mysqli_fetch_assoc($result);

            if (!$row) {
                $success = false;
                $error = "Product ID {$product_id} not found.";
                break;
            }
            if ($quantity > $row['StockQuantity']) {
                $success = false;
                $error = "Insufficient stock for {$row['ProductName']}. Available: {$row['StockQuantity']} units.";
                break;
            }
        }

        if ($success) {
            // Insert sale record
            $insert_sale_query = "INSERT INTO sales (customer_name, customer_phone, cashier_id, total_amount) VALUES (?, ?, ?, ?)";
            $sale_stmt = mysqli_prepare($conn, $insert_sale_query);
            mysqli_stmt_bind_param($sale_stmt, "ssid", $customer_name, $customer_phone, $cashier_id, $total);
            if (mysqli_stmt_execute($sale_stmt)) {
                $sale_id = mysqli_insert_id($conn);

                // Process each item
                foreach ($cart as $item) {
                    $product_id = $item['product_id'];
                    $quantity = $item['quantity'];
                    $total_amount = $item['price'] * $quantity;

                    $insert_item_query = "INSERT INTO sale_items (sale_id, product_id, quantity, price, total_amount) VALUES (?, ?, ?, ?, ?)";
                    $item_stmt = mysqli_prepare($conn, $insert_item_query);
                    mysqli_stmt_bind_param($item_stmt, "iiidd", $sale_id, $product_id, $quantity, $item['price'], $total_amount);
                    
                    if (!mysqli_stmt_execute($item_stmt)) {
                        $success = false;
                        $error = "Failed to process sale item {$item['name']}.";
                        break;
                    }

                    $update_stock = "UPDATE products SET StockQuantity = StockQuantity - ? WHERE ProductID = ?";
                    $stock_stmt = mysqli_prepare($conn, $update_stock);
                    mysqli_stmt_bind_param($stock_stmt, "ii", $quantity, $product_id);
                    
                    if (!mysqli_stmt_execute($stock_stmt)) {
                        $success = false;
                        $error = "Failed to update stock for {$item['name']}.";
                        break;
                    }

                    $receipt_items[] = [
                        'name' => $item['name'],
                        'quantity' => $quantity,
                        'price' => $item['price'],
                        'total' => $total_amount
                    ];
                }
            } else {
                $success = false;
                $error = "Failed to create sale record.";
            }
        }

        if ($success) {
            // Clear cart
            $delete_query = "DELETE FROM cart WHERE session_id = ?";
            $delete_stmt = mysqli_prepare($conn, $delete_query);
            mysqli_stmt_bind_param($delete_stmt, "s", $session_id);
            mysqli_stmt_execute($delete_stmt);

            // Commit transaction
            mysqli_commit($conn);
            
            // Generate receipt content
            $receipt = "<div style='font-family: Arial, sans-serif; max-width: 300px; margin: 0 auto;'>
                <h2 style='text-align: center; border-bottom: 2px dashed #000; padding-bottom: 10px; margin-bottom: 10px;'>RECEIPT</h2>
                <p style='text-align: center; margin: 5px 0;'><strong>POS System</strong></p>
                <p style='text-align: center; margin: 5px 0;'>Date: " . date('Y-m-d H:i:s', time() + 5 * 3600) . " PKT</p>
                <p style='text-align: center; margin: 5px 0;'>Cashier: " . htmlspecialchars($_SESSION['full_name']) . "</p>";
            
            if (!empty($customer_name)) {
                $receipt .= "<p style='text-align: center; margin: 5px 0;'>Customer: " . htmlspecialchars($customer_name) . "</p>";
            }
            if (!empty($customer_phone)) {
                $receipt .= "<p style='text-align: center; margin: 5px 0;'>Phone: " . htmlspecialchars($customer_phone) . "</p>";
            }
            
            $receipt .= "<div style='border-top: 2px dashed #000; margin: 10px 0;'></div>";
            
            foreach ($receipt_items as $item) {
                $receipt .= "<div style='display: flex; justify-content: space-between; margin: 5px 0;'>
                    <span>" . htmlspecialchars($item['name']) . " x{$item['quantity']}</span>
                    <span>$" . number_format($item['total'], 2) . "</span>
                </div>";
            }
            
            $receipt .= "<div style='border-top: 2px dashed #000; margin: 10px 0;'></div>
                <div style='display: flex; justify-content: space-between; margin: 5px 0;'>
                    <span><strong>Subtotal:</strong></span>
                    <span>$" . number_format($subtotal, 2) . "</span>
                </div>
                <div style='display: flex; justify-content: space-between; margin: 5px 0;'>
                    <span><strong>TOTAL:</strong></span>
                    <span><strong>$" . number_format($total, 2) . "</strong></span>
                </div>
                <div style='border-top: 2px dashed #000; margin: 10px 0;'></div>
                <p style='text-align: center; margin: 5px 0;'>Thank you for your purchase!</p>
                <p style='text-align: center; margin: 5px 0;'>Please come again</p>
            </div>";

            $_SESSION['receipt'] = $receipt;
            $success = "Sale processed successfully. Total: $" . number_format($total, 2);
            $cart = [];
            $subtotal = 0;
            $total = 0;
        } else {
            // Rollback transaction
            mysqli_rollback($conn);
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <title>Process Sale - POS</title>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <link rel="stylesheet" href="../css/style.css">
    <style>
        :root {
            --primary-color: #4361ee;
            --secondary-color: #3f37c9;
            --accent-color: #4cc9f0;
            --success-color: #4ad66d;
            --danger-color: #f72585;
            --light-color: #f8f9fa;
            --dark-color: #212529;
        }
        
        body {
            background-color: #f5f7fa;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        
        .pos-container { 
            display: flex; 
            min-height: 80vh;
            background-color: white;
            border-radius: 10px;
            box-shadow: 0 0 20px rgba(0,0,0,0.1);
            overflow: hidden;
            margin: 20px auto;
            max-width: 95%;
        }
        
        .products-section { 
            flex: 2; 
            padding: 20px; 
            overflow-y: auto;
            background-color: white;
        }
        
        .checkout-section { 
            flex: 1; 
            padding: 20px; 
            border-left: 1px solid #e0e0e0; 
            background-color: #f8f9fa;
            display: flex;
            flex-direction: column;
        }
        
        .product-card { 
            border: 1px solid #e0e0e0; 
            border-radius: 10px; 
            padding: 15px; 
            margin-bottom: 15px; 
            text-align: center;
            transition: all 0.3s ease;
            background-color: white;
            box-shadow: 0 2px 5px rgba(0,0,0,0.05);
        }
        
        .product-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
            border-color: var(--accent-color);
        }
        
        .product-card img { 
            max-width: 100px; 
            height: auto;
            border-radius: 5px;
            margin-bottom: 10px;
        }
        
        .cart-item { 
            display: flex; 
            justify-content: space-between; 
            padding: 12px 0; 
            border-bottom: 1px solid #e0e0e0;
            align-items: center;
        }
        
        .cart-total { 
            margin-top: auto;
            padding-top: 20px;
        }
        
        .total-box { 
            background-color: var(--primary-color); 
            color: white; 
            padding: 15px; 
            text-align: center; 
            border-radius: 8px;
            margin-bottom: 20px;
            box-shadow: 0 3px 10px rgba(67, 97, 238, 0.3);
        }
        
        .btn-add-to-cart { 
            background-color: var(--success-color); 
            color: white;
            border: none;
            transition: all 0.3s ease;
        }
        
        .btn-add-to-cart:hover { 
            background-color: #3ac05a;
            transform: translateY(-2px);
        }
        
        .form-control-sm { 
            width: 80px; 
            text-align: center;
        }
        
        .search-container {
            background-color: white;
            padding: 15px;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
            margin-bottom: 20px;
        }
        
        .search-bar {
            max-width: 100%;
        }
        
        .category-select {
            border-radius: 0 5px 5px 0 !important;
        }
        
        .search-btn {
            border-radius: 5px 0 0 5px !important;
        }
        
        .product-title {
            font-weight: 600;
            color: var(--dark-color);
            margin-bottom: 5px;
        }
        
        .product-price {
            font-weight: 700;
            color: var(--primary-color);
            font-size: 1.1rem;
        }
        
        .product-stock {
            font-size: 0.85rem;
            color: #6c757d;
        }
        
        .cart-empty {
            text-align: center;
            padding: 30px;
            color: #6c757d;
        }
        
        .cart-empty i {
            font-size: 2rem;
            margin-bottom: 10px;
            color: #adb5bd;
        }
        
        .receipt-container {
            background-color: white;
            padding: 20px;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
        }
        
        .refresh-btn {
            position: fixed;
            bottom: 20px;
            right: 20px;
            width: 50px;
            height: 50px;
            border-radius: 50%;
            background-color: var(--primary-color);
            color: white;
            display: flex;
            align-items: center;
            justify-content: center;
            box-shadow: 0 3px 10px rgba(0,0,0,0.2);
            z-index: 1000;
            cursor: pointer;
            transition: all 0.3s ease;
        }
        
        .refresh-btn:hover {
            background-color: var(--secondary-color);
            transform: rotate(180deg);
        }
        
        @media print {
            body * {
                visibility: hidden;
            }
            #print-receipt, #print-receipt * {
                visibility: visible;
            }
            #print-receipt {
                position: absolute;
                left: 0;
                top: 0;
                width: 100%;
            }
            .no-print {
                display: none !important;
            }
        }
    </style>
</head>
<body>
<?php include 'navbar.php'; ?>

<div class="container-fluid">
    <!-- Search Bar -->
    <div class="search-container">
        <div class="input-group search-bar">
            <input type="text" class="form-control search-btn" id="search-input" placeholder="Search products..." value="<?php echo htmlspecialchars($search_query); ?>">
            <select class="form-control category-select" id="category-select">
                <option value="0">All Categories</option>
                <?php
                $categories = mysqli_query($conn, "SELECT id, name FROM categories ORDER BY name");
                while ($cat = mysqli_fetch_assoc($categories)) {
                    $selected = ($cat['id'] == $category_filter) ? 'selected' : '';
                    echo "<option value='{$cat['id']}' $selected>" . htmlspecialchars($cat['name']) . "</option>";
                }
                ?>
            </select>
            <div class="input-group-append">
                <button type="button" class="btn btn-primary" onclick="filterProducts()">
                    <i class="fas fa-search"></i>
                </button>
            </div>
        </div>
    </div>

    <div class="pos-container">
        <!-- Products Section -->
        <div class="products-section">
            <h4 class="mb-4"><i class="fas fa-store-alt mr-2"></i>Available Products</h4>
            <div class="row" id="product-list">
                <?php
                $sql = "SELECT p.*, c.name as category_name 
                        FROM products p 
                        LEFT JOIN categories c ON p.CategoryID = c.id 
                        WHERE p.StockQuantity > 0 
                        ORDER BY p.ProductName";
                $result = mysqli_query($conn, $sql);

                if (mysqli_num_rows($result) > 0) {
                    while ($product = mysqli_fetch_assoc($result)) {
                        echo "<div class='col-md-4 mb-4 product-card' 
                                  data-product-name='" . htmlspecialchars(strtolower($product['ProductName'])) . "' 
                                  data-category-id='{$product['CategoryID']}'>
                            <div class='product-card'>
                                <img src='../admin/products_images/{$product['ImageURL']}' alt='{$product['ProductName']}' class='img-fluid'>
                                <h5 class='product-title mt-2'>" . htmlspecialchars($product['ProductName']) . "</h5>
                                <p class='text-muted mb-2'><small>Category: " . htmlspecialchars($product['category_name']) . "</small></p>
                                <p class='product-price'>$" . number_format($product['Price'], 2) . "</p>
                                <p class='product-stock'><i class='fas fa-box-open'></i> " . $product['StockQuantity'] . " in stock</p>
                                <form method='post' class='form-inline justify-content-center'>
                                    <input type='hidden' name='product_id' value='{$product['ProductID']}'>
                                    <div class='form-group mx-1'>
                                        <div class='input-group input-group-sm'>
                                            <div class='input-group-prepend'>
                                                <button type='button' class='btn btn-outline-secondary quantity-minus' data-target='quantity-{$product['ProductID']}'><i class='fas fa-minus'></i></button>
                                            </div>
                                            <input type='number' name='quantity' id='quantity-{$product['ProductID']}' class='form-control form-control-sm' min='1' max='{$product['StockQuantity']}' value='1' required>
                                            <div class='input-group-append'>
                                                <button type='button' class='btn btn-outline-secondary quantity-plus' data-target='quantity-{$product['ProductID']}'><i class='fas fa-plus'></i></button>
                                            </div>
                                        </div>
                                    </div>
                                    <button type='submit' name='add_to_cart' class='mt-2 btn btn-sm btn-add-to-cart'>
                                        <i class='fas fa-cart-plus mr-1'></i> Add
                                    </button>
                                </form>
                            </div>
                        </div>";
                    }
                } else {
                    echo "<div class='col-12 text-center py-5' id='no-products'>
                        <i class='fas fa-search fa-3x text-muted mb-3'></i>
                        <h5>No products found</h5>
                        <p class='text-muted'>Try adjusting your search or filter</p>
                    </div>";
                }
                ?>
            </div>
        </div>

        <!-- Checkout Section -->
        <div class="checkout-section">
            <h4 class="mb-4"><i class="fas fa-shopping-cart mr-2"></i>Order Summary</h4>
            
            <?php if ($success): ?>
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                    <i class="fas fa-check-circle mr-2"></i><?php echo htmlspecialchars($success); ?>
                    <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                        <span aria-hidden="true">×</span>
                    </button>
                </div>
                <div class="receipt-container mb-3">
                    <div id="print-receipt">
                        <?php echo $_SESSION['receipt']; ?>
                    </div>
                    <div class="d-flex justify-content-between mt-3 no-print">
                        <button type="button" class="btn btn-outline-primary" onclick="downloadReceipt()">
                            <i class="fas fa-print mr-1"></i> Print
                        </button>

                        <a href="process_sale.php" class="btn btn-primary">
                            <i class="fas fa-redo mr-1"></i> New Sale
                        </a>
                    </div>
                </div>
            <?php elseif ($error): ?>
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    <i class="fas fa-exclamation-circle mr-2"></i><?php echo htmlspecialchars($error); ?>
                    <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                        <span aria-hidden="true">×</span>
                    </button>
                </div>
            <?php endif; ?>

            <div class="cart-items" style="flex-grow: 1; overflow-y: auto;">
                <?php
                if (empty($cart)) {
                    echo "<div class='cart-empty'>
                        <i class='fas fa-shopping-basket'></i>
                        <h5>Your cart is empty</h5>
                        <p>Add products to get started</p>
                    </div>";
                } else {
                    foreach ($cart as $index => $item) {
                        echo "<div class='cart-item'>
                            <div>
                                <span class='font-weight-bold'>" . htmlspecialchars($item['name']) . "</span><br>
                                <small class='text-muted'>" . $item['quantity'] . " × $" . number_format($item['price'], 2) . "</small>
                            </div>
                            <div>
                                <span class='font-weight-bold'>$" . number_format($item['price'] * $item['quantity'], 2) . "</span>
                                <form method='post' class='d-inline'>
                                    <input type='hidden' name='remove_index' value='$index'>
                                    <button type='submit' name='remove_item' class='btn btn-sm btn-danger ml-2'>
                                        <i class='fas fa-trash'></i>
                                    </button>
                                </form>
                            </div>
                        </div>";
                    }
                }
                ?>
            </div>

            <div class="cart-total no-print">
                <div class="d-flex justify-content-between mb-2">
                    <span>Subtotal</span>
                    <span>$<?php echo number_format($subtotal, 2); ?></span>
                </div>
                <div class="total-box mt-2 mb-3">
                    <h5 class="mb-0">TOTAL</h5>
                    <h3 class="mb-0">$<?php echo number_format($total, 2); ?></h3>
                </div>
                <form method="post" class="mb-2">
                    <button type="submit" name="clear_cart" class="btn btn-danger btn-block">
                        <i class="fas fa-trash-alt mr-2"></i> Clear Cart
                    </button>
                </form>
                <button class="btn btn-primary btn-lg btn-block mt-2" data-toggle="modal" data-target="#customerModal" <?php echo empty($cart) ? 'disabled' : ''; ?>>
                    <i class="fas fa-credit-card mr-2"></i> Process Payment
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Customer Details Modal -->
<div class="modal fade" id="customerModal" tabindex="-1" role="dialog" aria-labelledby="customerModalLabel" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="customerModalLabel"><i class="fas fa-user mr-2"></i>Customer Details</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">×</span>
                </button>
            </div>
            <form method="post">
                <div class="modal-body">
                    <div class="form-group">
                        <label for="customer_name">Customer Name (Optional)</label>
                        <input type="text" class="form-control" id="customer_name" name="customer_name" placeholder="John Doe">
                    </div>
                    <div class="form-group">
                        <label for="customer_phone">Customer Phone (Optional)</label>
                        <input type="text" class="form-control" id="customer_phone" name="customer_phone" placeholder="03001234567">
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                    <button type="submit" name="process_sale" class="btn btn-primary">
                        <i class="fas fa-check mr-1"></i> Confirm Sale
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Refresh Button -->
<div class="refresh-btn no-print" onclick="window.location.reload();">
    <i class="fas fa-sync-alt"></i>
</div>

<script src="https://code.jquery.com/jquery-3.5.1.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@4.5.2/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/html2canvas/1.4.1/html2canvas.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js"></script>
<script>
    // Quantity buttons functionality
    $(document).ready(function() {
        $('.quantity-plus').click(function() {
            var target = $(this).data('target');
            var input = $('#' + target);
            var max = parseInt(input.attr('max')) || 999;
            var value = parseInt(input.val()) || 0;
            if (value < max) {
                input.val(value + 1);
            }
        });
        
        $('.quantity-minus').click(function() {
            var target = $(this).data('target');
            var input = $('#' + target);
            var value = parseInt(input.val()) || 0;
            if (value > 1) {
                input.val(value - 1);
            }
        });

        // Debounced search
        let debounceTimer;
        function debounce(func, wait) {
            return function() {
                clearTimeout(debounceTimer);
                debounceTimer = setTimeout(func, wait);
            };
        }

        $('#search-input').on('input', debounce(filterProducts, 300));
        $('#category-select').on('change', filterProducts);

        function filterProducts() {
            var searchQuery = $('#search-input').val().toLowerCase().trim();
            var categoryId = $('#category-select').val();
            
            var products = $('.product-card');
            var visibleCount = 0;

            products.each(function() {
                var productName = $(this).data('product-name');
                var productCategory = $(this).data('category-id');
                
                var matchesSearch = searchQuery === '' || productName.includes(searchQuery);
                var matchesCategory = categoryId === '0' || productCategory == categoryId;
                
                if (matchesSearch && matchesCategory) {
                    $(this).show();
                    visibleCount++;
                } else {
                    $(this).hide();
                }
            });

            if (visibleCount === 0) {
                $('#no-products').show();
            } else {
                $('#no-products').hide();
            }
        }

        filterProducts();

        // Auto focus search field
        $('#search-input').focus();
    });

    function printReceipt() {
        // Ensure the receipt content is available
        var printContents = $('#print-receipt').html();
        if (!printContents) {
            alert('No receipt content available to print.');
            return;
        }

        // Create a new window for printing
        var printWindow = window.open('', '_blank');
        printWindow.document.write(`
            <html>
            <head>
                <title>Print Receipt</title>
                <style>
                    body {
                        font-family: Arial, sans-serif;
                        max-width: 300px;
                        margin: 20px auto;
                        font-size: 12px;
                    }
                    h2 {
                        text-align: center;
                        border-bottom: 2px dashed #000;
                        padding-bottom: 10px;
                        margin-bottom: 10px;
                    }
                    p {
                        text-align: center;
                        margin: 5px 0;
                    }
                    div[style*="flex"] {
                        display: flex;
                        justify-content: space-between;
                        margin: 5px 0;
                    }
                    div[style*="border-top"] {
                        border-top: 2px dashed #000;
                        margin: 10px 0;
                    }
                </style>
            </head>
            <body>
                ${printContents}
            </body>
            </html>
        `);
        printWindow.document.close();

        // Wait for the content to load and trigger print
        printWindow.onload = function() {
            printWindow.print();
            printWindow.close(); // Close the window after printing
        };

        // Submit form to clear cart after printing
        $.post('process_sale.php', { print_receipt: true }, function() {
            window.location.href = 'process_sale.php';
        });
    }

    function downloadReceipt() {
        var element = document.getElementById('print-receipt');
        if (!element) {
            alert('No receipt content available to download.');
            return;
        }

        // Use html2canvas to capture the receipt as an image
        html2canvas(element, {
            scale: 2, // Increase resolution for better quality
            useCORS: true
        }).then(canvas => {
            const { jsPDF } = window.jspdf;
            const pdf = new jsPDF({
                orientation: 'portrait',
                unit: 'mm',
                format: 'a4'
            });

            // Get the canvas dimensions
            const imgData = canvas.toDataURL('image/png');
            const imgWidth = 80; // Width in mm (approx. 300px at 96dpi)
            const pageHeight = pdf.internal.pageSize.height;
            const imgHeight = (canvas.height * imgWidth) / canvas.width;
            let heightLeft = imgHeight;

            let position = 10; // Top margin in mm

            // Add image to PDF
            pdf.addImage(imgData, 'PNG', 65, position, imgWidth, imgHeight); // Centered on A4 (210mm wide)

            // Save the PDF
            pdf.save('receipt_' + new Date().toISOString().replace(/[:.]/g, '-') + '.pdf');
        }).catch(error => {
            console.error('Error generating PDF:', error);
            alert('Failed to generate PDF. Please try again.');
        });
    }
</script>
</body>
</html>