<?php
session_start();


$host = 'localhost';
$user = 'root';
$pass = '';
$db = 'buseasy';

$conn = new mysqli($host, $user, $pass, $db);


if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}


if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $number = $_POST['number'];
    $pwd = $_POST['password'];

    
    $stmt = $conn->prepare("SELECT * FROM localuser WHERE number=? AND password=?");
    $stmt->bind_param("ss", $number, $pwd);
    $stmt->execute();
    $res = $stmt->get_result();

    if ($res->num_rows > 0) {
       
        $_SESSION['phone'] = $number;

        echo "<script>alert('Login Successful'); window.location.href='http://localhost/final-project2/userdash.php';</script>";
        exit();
    } else {
        echo "<script>alert('No Match');</script>";
    }
    $stmt->close();
}

$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>User Login</title>
    <link rel="stylesheet" href="userlog.css" />
</head>
<body>
    <div class="top-bar">
        <img src="pics/1.png" alt="Logo" class="top-image" />
        <a href="http://localhost/final-project2/usersign.php" class="signup-btn">Sign Up</a>
    </div>

    <div class="login-container">
        <h1>User Login</h1>
        <form class="login-form" method="post" action="">
            <input type="text" name="number" placeholder="Phone Number" required />
            <input type="password" name="password" placeholder="Password" required />
            <button type="submit">Login</button>
        </form>
    </div>
</body>
</html>
