<?php
session_start();
include 'config.php';

// Check if user is logged in as admin
if (!isset($_SESSION["id"]) || $_SESSION["usertype"] != "admin") {
  header("location: admin_login.php");
  exit;
}

// Handle category deletion
if (isset($_GET['delete_id'])) {
  $delete_id = $_GET['delete_id'];
  $sql_delete = "DELETE FROM categories WHERE id = $delete_id";
  if (mysqli_query($conn, $sql_delete)) {
    $success_message = "Category deleted successfully.";
  } else {
    $error_message = "Error deleting category: " . mysqli_error($conn);
  }
}

// Handle category addition
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['add_category'])) {
  $category_name = $_POST['category_name'];
  $sql_insert = "INSERT INTO categories (name) VALUES ('$category_name')";
  if (mysqli_query($conn, $sql_insert)) {
    $success_message = "Category added successfully.";
  } else {
    $error_message = "Error adding category: " . mysqli_error($conn);
  }
}

// Fetch all categories
$sql_select = "SELECT * FROM categories";
$result = mysqli_query($conn, $sql_select);
?>

<!DOCTYPE html>
<html>
<head>
  <title>Admin Panel - Categories</title>
  <!-- Add Bootstrap CSS -->
  <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.0/css/bootstrap.min.css">
  <link rel="stylesheet" href="./css/style.css">
</head>
<body>
  <?php include 'admin_navbar.php'; ?>
  <div class="container mt-4">
    <h2>Categories</h2>
    <?php if (isset($success_message)) : ?>
      <div class="alert alert-success"><?php echo $success_message; ?></div>
    <?php endif; ?>
    <?php if (isset($error_message)) : ?>
      <div class="alert alert-danger"><?php echo $error_message; ?></div>
    <?php endif; ?>
    <h4>Add New Category</h4>
    <form method="post">
      <div class="form-group">
        <input type="text" class="form-control" name="category_name" placeholder="Category Name" required>
      </div>
      <button type="submit" class="btn btn-primary" name="add_category">Add Category</button>
    </form>
    <h4 class="mt-4">Existing Categories</h4>
    <table class="table">
      <thead>
        <tr>
          <th>ID</th>
          <th>Name</th>
          <th>Action</th>
        </tr>
      </thead>
      <tbody>
        <?php while ($row = mysqli_fetch_assoc($result)) : ?>
          <tr>
            <td><?php echo $row['id']; ?></td>
            <td><?php echo $row['name']; ?></td>
            <td>
              <a href="edit_category.php?id=<?php echo $row['id']; ?>" class="btn btn-sm btn-primary">Edit</a>
              <a href="?delete_id=<?php echo $row['id']; ?>" class="btn btn-sm btn-danger" onclick="return confirm('Are you sure you want to delete this category?')">Delete</a>
            </td>
          </tr>
        <?php endwhile; ?>
      </tbody>
    </table>
  </div>

  <!-- Add Bootstrap JS (Optional) -->
  <script src="https://maxcdn.bootstrapcdn.com/bootstrap/4.0.0/js/bootstrap.min.js"></script>
</body>
</html>
