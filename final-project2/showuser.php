<?php
$host = 'localhost';
$user = 'root';
$pass = '';
$db = 'buseasy';

$conn = new mysqli($host, $user, $pass, $db);
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Handle Delete
if (isset($_GET['delete'])) {
    $number = $_GET['delete'];
    $stmt = $conn->prepare("DELETE FROM localuser WHERE number = ?");
    $stmt->bind_param("s", $number);
    $stmt->execute();
    $stmt->close();
    header("Location: showuser.php");
    exit();
}

// Handle Add
if (isset($_POST['add'])) {
    $number = $_POST['number'];
    $password = $_POST['password']; 

    $stmtCheck = $conn->prepare("SELECT * FROM localuser WHERE number = ?");
    $stmtCheck->bind_param("s", $number);
    $stmtCheck->execute();
    $resultCheck = $stmtCheck->get_result();
    if ($resultCheck->num_rows === 0) {
        $stmt = $conn->prepare("INSERT INTO localuser (number, password) VALUES (?, ?)");
        $stmt->bind_param("ss", $number, $password);
        $stmt->execute();
        $stmt->close();
        header("Location: showuser.php");
        exit();
    } else {
        $error = "Phone number already exists.";
    }
    $stmtCheck->close();
}

// Handle Update/Edit
if (isset($_POST['update'])) {
    $id = $_POST['id'];
    $number = $_POST['number'];
    $passwordRaw = $_POST['password'];

    if (!empty($passwordRaw)) {
        $password = password_hash($passwordRaw, PASSWORD_DEFAULT);
        $stmt = $conn->prepare("UPDATE localuser SET number = ?, password = ? WHERE id = ?");
        $stmt->bind_param("ssi", $number, $password, $id);
    } else {
        $stmt = $conn->prepare("UPDATE localuser SET number = ? WHERE id = ?");
        $stmt->bind_param("si", $number, $id);
    }

    $stmt->execute();
    $stmt->close();
    header("Location: showuser.php");
    exit();
}

// For edit mode
$edit_id = $_GET['edit'] ?? null;
$edit_data = null;
if ($edit_id) {
    $stmt = $conn->prepare("SELECT * FROM localuser WHERE id = ?");
    $stmt->bind_param("i", $edit_id);
    $stmt->execute();
    $res = $stmt->get_result();
    if ($res->num_rows > 0) {
        $edit_data = $res->fetch_assoc();
    }
    $stmt->close();
}

$result = $conn->query("SELECT * FROM localuser ORDER BY id DESC");
?>

<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8" />
    <title>Manage Local Users - BusEasy</title>
    <link rel="stylesheet" href="showuser.css" />
</head>
<body>
  <div class="container">
    <h1>Manage Local Users</h1>

    <?php if (isset($error)): ?>
      <p style="color: #e74c3c; font-weight: bold;"><?= htmlspecialchars($error) ?></p>
    <?php endif; ?>

    <form method="POST" style="margin-top: 40px;">
      <?php if ($edit_data): ?>
        <input type="hidden" name="id" value="<?= $edit_data['id'] ?>" />
      <?php endif; ?>

      <table>
        <tr>
          <td>
            <input
              type="text"
              name="number"
              placeholder="Phone Number"
              required
              value="<?= htmlspecialchars($edit_data['number'] ?? '') ?>"
            />
          </td>
          <td>
            <input
              type="password"
              name="password"
              placeholder="<?= $edit_data ? 'Leave blank to keep current password' : 'Password' ?>"
              <?= $edit_data ? '' : 'required' ?>
            />
          </td>
          <td>
            <?php if ($edit_data): ?>
              <button type="submit" name="update" class="action-btn">Update</button>
              <a href="showuser.php" class="action-btn cancel-btn">Cancel</a>
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
          <th>Phone Number</th>
          <th>Password</th>
          <th>Action</th>
        </tr>
      </thead>
      <tbody>
      <?php if ($result->num_rows > 0): ?>
        <?php while ($row = $result->fetch_assoc()): ?>
          <tr>
            <td><?= htmlspecialchars($row['id']) ?></td>
            <td>
              <a href="userlog.php?auto_login=1&phone=<?= urlencode($row['number']) ?>" class="user-link">
                <?= htmlspecialchars($row['number']) ?>
              </a>
            </td>
            <td>******</td>
            <td>
              <a href="?edit=<?= $row['id'] ?>" class="edit-btn">Edit</a>
              <a href="?delete=<?= urlencode($row['number']) ?>" class="delete-btn" onclick="return confirm('Are you sure you want to delete this user?');">Delete</a>
            </td>
          </tr>
        <?php endwhile; ?>
      <?php else: ?>
        <tr><td colspan="4">No users found.</td></tr>
      <?php endif; ?>
      </tbody>
    </table>
  </div>
</body>
</html>

<?php $conn->close(); ?>
