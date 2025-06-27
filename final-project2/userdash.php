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

// Handle cancel request
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['cancel_bus']) && isset($_POST['cancel_seat'])) {
    $busNum = $_POST['cancel_bus'];
    $cancelSeat = $_POST['cancel_seat'];

    // 1. Get route row (main route info with available seats)
    $stmtRoute = $conn->prepare("SELECT available_seats FROM `$busNum` WHERE bus_number = ? AND from_location IS NOT NULL");
    $stmtRoute->bind_param("s", $busNum);
    $stmtRoute->execute();
    $resRoute = $stmtRoute->get_result();
    $routeRow = $resRoute->fetch_assoc();
    $availableSeatsArr = [];
    if ($routeRow && !empty($routeRow['available_seats'])) {
        $availableSeatsArr = array_filter(array_map('trim', explode(',', $routeRow['available_seats'])));
    }

    // 2. Find booking row(s) for this user & bus where seat is booked
    $stmtBooking = $conn->prepare("SELECT booked_seats, Seat_number FROM `$busNum` WHERE bus_number = ? AND Phone = ?");
    $stmtBooking->bind_param("ss", $busNum, $phone);
    $stmtBooking->execute();
    $resBooking = $stmtBooking->get_result();

    while ($bookingRow = $resBooking->fetch_assoc()) {
        $bookedSeatsArr = [];
        if (!empty($bookingRow['booked_seats'])) {
            $bookedSeatsArr = array_filter(array_map('trim', explode(',', $bookingRow['booked_seats'])));
        }
        // Check if the seat is booked here
        if (in_array($cancelSeat, $bookedSeatsArr)) {
            // Remove this seat from booked seats
            $newBookedSeats = array_filter($bookedSeatsArr, fn($s) => $s != $cancelSeat);

            if (count($newBookedSeats) === 0) {
                // No seats left in this booking row -> delete it
                $stmtDelete = $conn->prepare("DELETE FROM `$busNum` WHERE bus_number = ? AND Phone = ? AND FIND_IN_SET(?, booked_seats) > 0");
                $stmtDelete->bind_param("sss", $busNum, $phone, $cancelSeat);
                $stmtDelete->execute();
                $stmtDelete->close();
            } else {
                // Update booking row with new booked seats
                $newBookedSeatsStr = implode(',', $newBookedSeats);
                $stmtUpdate = $conn->prepare("UPDATE `$busNum` SET booked_seats = ? WHERE bus_number = ? AND Phone = ? AND FIND_IN_SET(?, booked_seats) > 0");
                $stmtUpdate->bind_param("ssss", $newBookedSeatsStr, $busNum, $phone, $cancelSeat);
                $stmtUpdate->execute();
                $stmtUpdate->close();
            }

            // Add seat back to available seats if not present
            if (!in_array($cancelSeat, $availableSeatsArr)) {
                $availableSeatsArr[] = $cancelSeat;
                sort($availableSeatsArr);
            }

            break; // seat processed, no need to continue
        }
    }
    $resBooking->close();
    $stmtBooking->close();
    $stmtRoute->close();

    // 3. Update route row's available_seats
    $newAvailableSeatsStr = implode(',', $availableSeatsArr);
    $stmtUpdateRoute = $conn->prepare("UPDATE `$busNum` SET available_seats = ? WHERE bus_number = ? AND from_location IS NOT NULL");
    $stmtUpdateRoute->bind_param("ss", $newAvailableSeatsStr, $busNum);
    $stmtUpdateRoute->execute();
    $stmtUpdateRoute->close();

    // ✅ Delete from booked_seats table
    $stmtDeleteBookedSeats = $conn->prepare("DELETE FROM booked_seats WHERE bus_number = ? AND seat_number = ? AND phone = ?");
    $stmtDeleteBookedSeats->bind_param("sis", $busNum, $cancelSeat, $phone);
    $stmtDeleteBookedSeats->execute();
    $stmtDeleteBookedSeats->close();

    // Redirect to avoid form resubmission
    header("Location: " . $_SERVER['PHP_SELF']);
    exit();
}

// Fetch all bookings for logged-in user
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
<script>
function printBooking(rowId) {
    var row = document.getElementById(rowId);
    if (!row) return;
    var printWindow = window.open('', '', 'height=600,width=800');
    printWindow.document.write('<html><head><title>Print Booking</title>');
    printWindow.document.write('<style>body{font-family: Arial, sans-serif;} table {border-collapse: collapse; width: 100%;} td, th {border: 1px solid #aaa; padding: 8px;}</style>');
    printWindow.document.write('</head><body>');
    printWindow.document.write('<h2>Booking Details</h2>');
    printWindow.document.write('<table>');
    printWindow.document.write('<tr><th>Field</th><th>Value</th></tr>');
    var cells = row.querySelectorAll('td');
    var headers = ['Booking Date','From','To','Bus Number','Dispute Time','Date','Seat Number','Phone'];
    for (var i=0; i < headers.length; i++) {
        printWindow.document.write('<tr><td>' + headers[i] + '</td><td>' + cells[i].innerText + '</td></tr>');
    }
    printWindow.document.write('</table>');
    printWindow.document.write('</body></html>');
    printWindow.document.close();
    printWindow.focus();
    printWindow.print();
}
</script>
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
                      <button class='btn btn-print' onclick='printBooking(\"{$rowId}\")'>Print</button>
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
