<?php
$bus_number = $_GET['bus'];

$conn = new mysqli("localhost", "root", "", "buseasy");
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}


$busInfoSql = "SELECT * FROM route WHERE bus_number='$bus_number'";
$busInfoResult = $conn->query($busInfoSql);
$busInfo = $busInfoResult->fetch_assoc();


$seatSql = "SELECT booked_seats FROM `$bus_number` WHERE bus_number='$bus_number'";
$seatResult = $conn->query($seatSql);
$booked_seats = [];

if ($seatResult && $seatResult->num_rows > 0) {
    $seatData = $seatResult->fetch_assoc();
    $booked_seats = array_filter(explode(",", $seatData['booked_seats']));
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Book A Seat</title>
    <link rel="stylesheet" href="viewbus.css">
</head>
<body>
    <div class="container">
        <div class="left-panel">
            <img src="logo.png" alt="BusEasy Logo" class="logo">
            <div class="seat-layout">
                <?php
                for ($i = 1; $i <= 40; $i++) {
                    $class = in_array($i, $booked_seats) ? "seat booked" : "seat available";
                    echo "<button class='$class'>$i</button>";
                    if ($i % 2 == 0 && $i % 4 != 0) echo "<div class='gap'></div>";
                    if ($i % 4 == 0) echo "<br>";
                }
                ?>
            </div>
        </div>

        <div class="right-panel">
            <h2>Book A Seat</h2>
            <form action="buy.php" method="POST">
                <input type="hidden" name="bus_number" value="<?php echo $bus_number; ?>">
                <input type="text" name="name" placeholder="Name" required>
                <input type="text" name="phone" placeholder="Phone" required>
                <button type="submit" class="buy-button">BUY</button>
            </form>
        </div>
    </div>
</body>
</html>
