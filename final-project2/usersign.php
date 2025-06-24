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

$message = "";

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $number = $_POST['number'];
    $pwd = $_POST['password'];
    $retype_pwd = $_POST['retype_password'];

    if ($pwd === $retype_pwd) {
        
        $stmt = $conn->prepare("SELECT * FROM localuser WHERE number = ?");
        $stmt->bind_param("s", $number);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows > 0) {
            $message = "Phone number already registered!";
        } else {
           
            $stmt = $conn->prepare("INSERT INTO localuser (number, password) VALUES (?, ?)");
            $stmt->bind_param("ss", $number, $pwd);
            if ($stmt->execute()) {
                $message = "THANKS FOR CONNECTING WITH US.";
            } else {
                $message = "Error occurred. Please try again.";
            }
        }
        $stmt->close();
    } else {
        $message = "Passwords do not match!";
    }
}

$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>User Sign Up</title>
    <link rel="stylesheet" href="usersign.css" />
</head>
<body>

<div class="signup-container">
    <h1>User Sign Up</h1>

    <?php if (!empty($message)): ?>
        <div class="message"><?php echo $message; ?></div>
    <?php endif; ?>

    <form class="signup-form" method="post" action="">
        <input type="text" name="number" placeholder="Phone Number" required />
        <input type="password" name="password" placeholder="Password" required />
        <input type="password" name="retype_password" placeholder="Retype Password" required />
        <button type="submit">Sign Up</button>
    </form>

    <a href="userlog.php" class="login-link">Already have an account? Login</a>
</div>

</body>
</html>
