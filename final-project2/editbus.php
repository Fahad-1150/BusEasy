<?php
$conn = new mysqli("localhost", "root", "", "buseasy");
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

if (!isset($_GET['bus'])) {
    die("No bus selected.");
}

$bus_number = $conn->real_escape_string($_GET['bus']);


$from = $to = $dispute_time = $date = "";
$available_seats = $booked_seats = "";
$message = "";


if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $from = $conn->real_escape_string($_POST['from_location']);
    $to = $conn->real_escape_string($_POST['to_location']);
    $dispute_time = $conn->real_escape_string($_POST['dispute_time']);
    $date = $conn->real_escape_string($_POST['date']);

    
    $update_route_sql = "UPDATE route SET 
        from_location='$from', 
        to_location='$to', 
        dispute_time='$dispute_time', 
        date=" . ($date ? "'$date'" : "NULL") . " 
        WHERE bus_number='$bus_number'";

    if ($conn->query($update_route_sql) === TRUE) {
       
        $update_bus_sql = "UPDATE `$bus_number` SET 
            from_location='$from', 
            to_location='$to', 
            dispute_time='$dispute_time' 
            WHERE bus_number='$bus_number'";

        if ($conn->query($update_bus_sql) === TRUE) {
            $message = "Bus route updated successfully.";
        } else {
            $message = "Error updating bus table: " . $conn->error;
        }
    } else {
        $message = "Error updating route table: " . $conn->error;
    }
}


$route_sql = "SELECT * FROM route WHERE bus_number='$bus_number' LIMIT 1";
$route_res = $conn->query($route_sql);
if ($route_res && $route_res->num_rows > 0) {
    $route_row = $route_res->fetch_assoc();
    $from = htmlspecialchars($route_row['from_location']);
    $to = htmlspecialchars($route_row['to_location']);
    $dispute_time = htmlspecialchars($route_row['dispute_time']);
    $date = $route_row['date'];  // date may be NULL, handle in form
} else {
    die("Route data not found for bus: $bus_number");
}


$available_count = 0;
$booked_count = 0;
if ($conn->query("SHOW TABLES LIKE '$bus_number'")->num_rows > 0) {
    $bus_sql = "SELECT available_seats, booked_seats FROM `$bus_number` WHERE bus_number='$bus_number' LIMIT 1";
    $bus_res = $conn->query($bus_sql);
    if ($bus_res && $bus_res->num_rows > 0) {
        $bus_row = $bus_res->fetch_assoc();
        $available_seats = $bus_row['available_seats'];
        $booked_seats = $bus_row['booked_seats'];

        $available_count = count(array_filter(explode(",", $available_seats)));
        $booked_count = count(array_filter(explode(",", $booked_seats)));
    }
} else {
    die("Bus table '$bus_number' does not exist.");
}

$conn->close();
?>

<!DOCTYPE html>
<html>
<head>
    <title>Edit Bus Route - <?php echo htmlspecialchars($bus_number); ?></title>
    <link rel="stylesheet" href="editbus.css">

</head>
<body>

    <h2>Edit Bus Route: <?php echo htmlspecialchars($bus_number); ?></h2>

    <?php if ($message): ?>
        <div class="message"><?php echo $message; ?></div>
    <?php endif; ?>

    <form method="post" action="">
        <label for="from_location">From Location:</label>
        <input type="text" id="from_location" name="from_location" value="<?php echo $from; ?>" required>

        <label for="to_location">To Location:</label>
        <input type="text" id="to_location" name="to_location" value="<?php echo $to; ?>" required>

        <label for="dispute_time">Dispute Time:</label>
        <input type="text" id="dispute_time" name="dispute_time" value="<?php echo $dispute_time; ?>" required>

        <label for="date">Date:</label>
        <input type="date" id="date" name="date" value="<?php echo $date ? $date : ''; ?>">

        <div class="seat-info">
            Available Seats: <strong><?php echo $available_count; ?></strong><br>
            Booked Seats: <strong><?php echo $booked_count; ?></strong><br>
            <a href="viewbookedseats.php?bus=<?php echo urlencode($bus_number); ?>" class="link-btn">View Booked Seats</a>
        </div>

        <button type="submit">Confirm Update</button>
    </form>

</body>
</html>
