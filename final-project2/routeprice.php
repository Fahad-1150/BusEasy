<?php
$conn = new mysqli("localhost", "root", "", "buseasy");
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Handle Add
if (isset($_POST['add'])) {
    $from = $_POST['from_location'];
    $to = $_POST['to_location'];
    $price = $_POST['price'];
    $stmt = $conn->prepare("INSERT INTO route_price (from_location, to_location, price) VALUES (?, ?, ?)");
    $stmt->bind_param("ssd", $from, $to, $price);
    $stmt->execute();
    header("Location: routeprice.php");
    exit();
}

// Handle Edit
if (isset($_POST['update'])) {
    $id = $_POST['id'];
    $from = $_POST['from_location'];
    $to = $_POST['to_location'];
    $price = $_POST['price'];
    $stmt = $conn->prepare("UPDATE route_price SET from_location=?, to_location=?, price=? WHERE id=?");
    $stmt->bind_param("ssdi", $from, $to, $price, $id);
    $stmt->execute();
    header("Location: routeprice.php");
    exit();
}

// Handle Delete
if (isset($_GET['delete'])) {
    $id = intval($_GET['delete']);
    $conn->query("DELETE FROM route_price WHERE id=$id");
    header("Location: routeprice.php");
    exit();
}

// Get all rows
$result = $conn->query("SELECT * FROM route_price");

// For edit mode
$edit_id = $_GET['edit'] ?? null;
$edit_data = null;

if ($edit_id) {
    $stmt = $conn->prepare("SELECT * FROM route_price WHERE id = ?");
    $stmt->bind_param("i", $edit_id);
    $stmt->execute();
    $edit_result = $stmt->get_result();
    if ($edit_result->num_rows > 0) {
        $edit_data = $edit_result->fetch_assoc();
    }
}
?>

<!DOCTYPE html>
<html>
<head>
  <title>Set Route Price</title>
  <link rel="stylesheet" href="admindash.css">
</head>
<body>
  <div class="container">
    <div class="header">
      <img src="pics/1.png" alt="Bus Logo" class="logo">
      <h1>BusEasy</h1>
    </div>

    <form method="POST" style="margin-top: 40px;">
      <?php if ($edit_data): ?>
        <input type="hidden" name="id" value="<?= $edit_data['id'] ?>">
      <?php endif; ?>

      <table>
        <tr>
          <td><input type="text" name="from_location" placeholder="From Location" required value="<?= $edit_data['from_location'] ?? '' ?>"></td>
          <td><input type="text" name="to_location" placeholder="To Location" required value="<?= $edit_data['to_location'] ?? '' ?>"></td>
          <td><input type="number" step="0.01" name="price" placeholder="Price" required value="<?= $edit_data['price'] ?? '' ?>"></td>
          <td>
            <?php if ($edit_data): ?>
              <button type="submit" name="update" class="action-btn">Update</button>
              <a href="routeprice.php" class="action-btn">Cancel</a>
            <?php else: ?>
              <button type="submit" name="add" class="action-btn">Add</button>
            <?php endif; ?>
          </td>
        </tr>
      </table>
    </form>

    <table>
      <thead>
        <tr>
          <th>ID</th>
          <th>From</th>
          <th>To</th>
          <th>Price (৳)</th>
          <th>Action</th>
        </tr>
      </thead>
      <tbody>
        <?php if ($result->num_rows > 0): ?>
          <?php while($row = $result->fetch_assoc()): ?>
            <tr>
              <td><?= $row['id'] ?></td>
              <td><?= htmlspecialchars($row['from_location']) ?></td>
              <td><?= htmlspecialchars($row['to_location']) ?></td>
              <td><?= htmlspecialchars($row['price']) ?></td>
              <td>
                <a href="routeprice.php?edit=<?= $row['id'] ?>" class="action-btn">Edit</a>
                <a href="routeprice.php?delete=<?= $row['id'] ?>" class="action-btn" onclick="return confirm('Delete this price?');">Delete</a>
              </td>
            </tr>
          <?php endwhile; ?>
        <?php else: ?>
          <tr><td colspan="5">No route prices found.</td></tr>
        <?php endif; ?>
      </tbody>
    </table>
  </div>
</body>
</html>
