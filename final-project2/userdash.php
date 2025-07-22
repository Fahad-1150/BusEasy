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


if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['cancel_bus'], $_POST['cancel_seat'])) {
    $busNum = $_POST['cancel_bus'];
    $cancelSeat = $_POST['cancel_seat'];

    
    $stmtSeats = $conn->prepare("SELECT available_seats, booked_seats FROM `$busNum` WHERE from_location IS NOT NULL LIMIT 1");
    $stmtSeats->execute();
    $resSeats = $stmtSeats->get_result();
    $rowSeats = $resSeats->fetch_assoc();
    $stmtSeats->close();

    $availableSeatsArr = [];
    $bookedSeatsArr = [];

    if ($rowSeats) {
        if (!empty($rowSeats['available_seats'])) {
            $availableSeatsArr = array_filter(array_map('intval', explode(',', $rowSeats['available_seats'])));
        }
        if (!empty($rowSeats['booked_seats'])) {
            $bookedSeatsArr = array_filter(array_map('intval', explode(',', $rowSeats['booked_seats'])));
        }
    }

    
    $bookedSeatsArr = array_filter($bookedSeatsArr, fn($s) => $s != intval($cancelSeat));

    
    if (!in_array(intval($cancelSeat), $availableSeatsArr)) {
        $availableSeatsArr[] = intval($cancelSeat);
    }

    
    sort($availableSeatsArr, SORT_NUMERIC);
    sort($bookedSeatsArr, SORT_NUMERIC);

    
    $newAvailableSeatsStr = implode(',', $availableSeatsArr);
    $newBookedSeatsStr = implode(',', $bookedSeatsArr);

    $stmtUpdateSeats = $conn->prepare("UPDATE `$busNum` SET available_seats = ?, booked_seats = ? WHERE from_location IS NOT NULL LIMIT 1");
    $stmtUpdateSeats->bind_param("ss", $newAvailableSeatsStr, $newBookedSeatsStr);
    $stmtUpdateSeats->execute();
    $stmtUpdateSeats->close();

    
    $stmtDeleteMaster = $conn->prepare("DELETE FROM booked_seats WHERE bus_number = ? AND seat_number = ? AND phone = ?");
    $stmtDeleteMaster->bind_param("sis", $busNum, $cancelSeat, $phone);
    $stmtDeleteMaster->execute();
    $stmtDeleteMaster->close();

    
    header("Location: " . $_SERVER['PHP_SELF'] . "?cancel_success=1");
    exit();
}


$sql = "
    SELECT 
  
    bs.id,
    bs.booking_date,

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
    WHERE bs.phone = ?
    ORDER BY bs.booking_date DESC
";

$stmt = $conn->prepare($sql);
$stmt->bind_param("s", $phone);
$stmt->execute();
$result = $stmt->get_result();
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
  .btn {
    padding: 6px 12px;
    margin: 0 4px;
    border: none;
    border-radius: 4px;
    cursor: pointer;
    font-weight: bold;
  }
  .btn-cancel {
    background-color: #e74c3c;
    color: white;
  }
  .btn-print {
    background-color: #3498db;
    color: white;
  }
</style>
<?php if (isset($_GET['cancel_success'])): ?>
<script>alert("Seat cancellation successful.");</script>
<?php endif; ?>





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
        <th>Actions</th>
      </tr>
    </thead>
    <tbody>
      <?php
      if ($result && $result->num_rows > 0) {
          $count = 0;
          while ($row = $result->fetch_assoc()) {
              $rowId = 'row-' . $count++;
              echo "<tr id='{$rowId}'>";
              echo "<td>" . htmlspecialchars($row['booking_date']) . "</td>";
              echo "<td>" . htmlspecialchars($row['from_location']) . "</td>";
              echo "<td>" . htmlspecialchars($row['to_location']) . "</td>";
              echo "<td><a href='editbususer.php?bus=" . urlencode($row['bus_number']) . "'>" . htmlspecialchars($row['bus_number']) . "</a></td>";
              echo "<td>" . htmlspecialchars($row['dispute_time']) . "</td>";
              echo "<td>" . htmlspecialchars($row['date']) . "</td>";
              echo "<td>" . htmlspecialchars($row['seat_number']) . "</td>";
              echo "<td>" . htmlspecialchars($row['phone']) . "</td>";
              echo "<td>
                      <form method='POST' style='display:inline;' onsubmit='return confirm(\"Are you sure you want to cancel this booking?\");'>
                        <input type='hidden' name='cancel_bus' value='" . htmlspecialchars($row['bus_number']) . "' />
                        <input type='hidden' name='cancel_seat' value='" . htmlspecialchars($row['seat_number']) . "' />
                        <button type='submit' class='btn btn-cancel'>Cancel</button>
                      </form>
                     <a href='printticket.php?booking_id=" . urlencode($row['id']) . "' target='_blank'>
    <button type='button' class='btn btn-print'>Print</button>
</a>

                    </td>";
              echo "</tr>";
          }
      } else {
          echo '<tr><td colspan="9" style="text-align:center;">No booked seats found.</td></tr>';
      }
      ?>
    </tbody>
  </table>
</div>

</body>
</html>

<?php
$stmt->close();
$conn->close();
?>
