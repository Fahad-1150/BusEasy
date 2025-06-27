<!DOCTYPE html>
<html>
<head>
  <title>BusEasy Dashboard</title>
  <link rel="stylesheet" href="admindash.css">
</head>
<body>
  <div class="container">
    <div class="header">
      <img src="pics/1.png" alt="Bus Logo" class="logo">
      <h1>BusEasy</h1> 
      <div class="button-group">
        <a href="addbus.php" class="add-bus">+ Add bus</a>
        <a href="searchbus.php" class="search-bus">🔍 Search</a>
      </div>
    </div>

    <table>
      <thead>
        <tr>
          <th>Bus Number</th>
          <th>From</th>
          <th>To</th>
          <th>Dispute Time</th>
          <th>Date</th> 
          <th>Available Seats</th>
          <th>Booked Seats</th>
          <th>Action</th>
        </tr>
      </thead>
      <tbody>

<?php
$conn = new mysqli("localhost", "root", "", "buseasy");
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}


function tableExists($conn, $tableName) {
    $result = $conn->query("SHOW TABLES LIKE '$tableName'");
    return $result && $result->num_rows > 0;
}

$sql = "SELECT * FROM route";
$result = $conn->query($sql);

if ($result->num_rows > 0) {
    while($row = $result->fetch_assoc()) {
        $bus_number = $row['bus_number'];
        $from = $row['from_location'];
        $to = $row['to_location'];
        $time = $row['dispute_time'];
        $date = isset($row['date']) ? $row['date'] : 'N/A'; 

        $available_count = 0;
        $booked_count = 0;

        if (tableExists($conn, $bus_number)) {
            $seat_sql = "SELECT available_seats, booked_seats FROM `$bus_number` WHERE bus_number = '$bus_number' LIMIT 1";
            $seat_result = $conn->query($seat_sql);

            if ($seat_result && $seat_result->num_rows > 0) {
                $seat_data = $seat_result->fetch_assoc();
                $available = array_filter(explode(",", $seat_data['available_seats']));
                $booked = array_filter(explode(",", $seat_data['booked_seats']));
                $available_count = count($available);
                $booked_count = count($booked);
            }
        }

        echo "<tr>";
        echo "<td><a href='book.php?bus=" . htmlspecialchars($bus_number) . "' class='bus-btn'>" . htmlspecialchars($bus_number) . "</a></td>";
        echo "<td>" . htmlspecialchars($from) . "</td>";
        echo "<td>" . htmlspecialchars($to) . "</td>";
        echo "<td>" . htmlspecialchars($time) . "</td>";
        echo "<td>" . htmlspecialchars($date) . "</td>"; 
        echo "<td>" . $available_count . "</td>";
        echo "<td>" . $booked_count . "</td>";
        echo "<td><a href='editbus.php?bus=" . urlencode($bus_number) . "' class='action-btn'>Edit</a></td>";
        echo "</tr>";
    }
} else {
    echo "<tr><td colspan='8'>No buses available.</td></tr>";
}

$conn->close();
?>

      </tbody>
    </table>
  </div>
</body>
</html>
