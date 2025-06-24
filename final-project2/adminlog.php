<?php
$host = 'localhost';
$user = 'root';
$pass = '';
$db = 'buseasy';

$conn = new mysqli($host, $user, $pass, $db);
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $uname = $_POST['username'];
    $pwd = $_POST['password'];

    $stmt = $conn->prepare("SELECT * FROM users WHERE username=? AND password=?");
    $stmt->bind_param("ss", $uname, $pwd);
    $stmt->execute();
    $res = $stmt->get_result();

    if ($res->num_rows > 0) {
        echo "<script>alert('Login Successful'); window.location.href='http://localhost/final-project2/admindash.php';</script>";
    } else {
        echo "<script>alert('Invalid Credentials');</script>";
    }
    $stmt->close();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Login</title>
    <link rel="stylesheet" href="adminlog.css">
</head>
<body>
    <div class="top-bar">
        <img src="pics/1.png" alt="Logo" class="top-image">
    </div>

    <div class="login-container">
        <h1>Admin Login</h1>
        <form class="login-form" method="post" action="">
            <input type="text" name="username" placeholder="Username" required>
            <input type="password" name="password" placeholder="Password" required>
            <button type="submit">Login</button>
        </form>
    </div>
</body>
</html>
