<?php
session_start();

if (!isset($_SESSION['phone'])) {
    
    header('Location: login.php');
    exit();
}

$phone = $_SESSION['phone'];

$host = 'localhost';
$user = 'root';
$pass = '';
$db = 'buseasy';

$conn = new mysqli($host, $user, $pass, $db);
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}


$sql = "
    SELECT 
        bs.booking_date,
        bs.from_location,
        bs.to_location,
        bs.bus_number,
        bs.seat_number,
        bs.phone,
        r.dispute_time,
        r.date
    FROM booked_seats bs
    INNER JOIN route r ON bs.bus_number = r.bus_number
    WHERE bs.phone = '$phone'
";

$result = $conn->query($sql);
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8" />
<meta name="viewport" content="width=device-width, initial-scale=1" />
<title>BusEasy User Dashboard</title>
<link rel="stylesheet" href="userdash.css" />
<style>
  table {
    width: 100%;
    border-collapse: collapse;
  }
  table, th, td {
    border: 1px solid #aaa;
  }
  th, td {
    padding: 8px;
    text-align: left;
  }
 
  thead tr {
    background-color: #808080; 
    color: white;
  }
</style>
</head>
<body>

<div class="top-bar">
  <img src="pics/1.png" alt="BusEasy Logo" class="top-image" />
  <a href="http://localhost/final-project2/searchbus.php" class="buy-seat-btn">Buy New Seat</a>
</div>

<div class="container">
  <h1>My Booked Seats</h1>

  <table>
    <thead>
      <tr>
        <th>Booking Date</th>
        <th>From</th>
        <th>To</th>
        <th>Bus Number</th>
        <th>Dispute Time</th>
        <th>Date</th>
        <th>Seat Number</th>
        <th>Phone</th>
      </tr>
    </thead>
    <tbody>
      <?php
      if ($result && $result->num_rows > 0) {
          while ($row = $result->fetch_assoc()) {
              echo "<tr>";
              echo "<td>" . htmlspecialchars($row['booking_date']) . "</td>";
              echo "<td>" . htmlspecialchars($row['from_location']) . "</td>";
              echo "<td>" . htmlspecialchars($row['to_location']) . "</td>";
              echo "<td><a href='userbooked.php?bus=" . urlencode($row['bus_number']) . "'>" . htmlspecialchars($row['bus_number']) . "</a></td>";
              echo "<td>" . htmlspecialchars($row['dispute_time']) . "</td>";
              echo "<td>" . htmlspecialchars($row['date']) . "</td>";
              echo "<td>" . htmlspecialchars($row['seat_number']) . "</td>";
              echo "<td>" . htmlspecialchars($row['phone']) . "</td>";
              echo "</tr>";
          }
      } else {
          echo '<tr><td colspan="8" style="text-align:center;">No booked seats found.</td></tr>';
      }
      ?>
    </tbody>
  </table>
</div>

</body>
</html>

<?php
$conn->close();
?>
