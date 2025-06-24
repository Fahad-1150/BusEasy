<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

$bus_number = $_GET['bus'] ?? '';

$conn = new mysqli("localhost", "root", "", "buseasy");
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}


if (isset($_GET['success'])) {
    echo "<script>alert('Booking successful!');</script>";
}


if (!empty($bus_number)) {
    $busInfoSql = "SELECT * FROM route WHERE bus_number=?";
    $stmt = $conn->prepare($busInfoSql);
    if (!$stmt) {
        die("Prepare failed: (" . $conn->errno . ") " . $conn->error);
    }
    $stmt->bind_param("s", $bus_number);
    if (!$stmt->execute()) {
        die("Execute failed: (" . $stmt->errno . ") " . $stmt->error);
    }
    $busInfoResult = $stmt->get_result();
    $busInfo = $busInfoResult->fetch_assoc();
    $stmt->close();
} else {
    die("Invalid bus number.");
}


if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = $_POST['name'] ?? '';
    $phone = $_POST['phone'] ?? '';
    $seat_numbers = $_POST['seat_number'] ?? '';
    $bus_number_post = $_POST['bus_number'] ?? '';

    if (empty($name) || empty($phone) || empty($seat_numbers)) {
        echo "<script>alert('Please fill all fields and select seats.');</script>";
    } else {
        
        $newSeatsArray = array_filter(array_map('trim', explode(',', $seat_numbers)));

       
        $sqlCheck = "SELECT booked_seats FROM `$bus_number_post` WHERE bus_number = ? AND Booked_By = ?";
        $stmtCheck = $conn->prepare($sqlCheck);
        if (!$stmtCheck) {
            die("Prepare failed: (" . $conn->errno . ") " . $conn->error);
        }
        $stmtCheck->bind_param("ss", $bus_number_post, $name);
        if (!$stmtCheck->execute()) {
            die("Execute failed: (" . $stmtCheck->errno . ") " . $stmtCheck->error);
        }
        $resultCheck = $stmtCheck->get_result();

        if ($resultCheck->num_rows > 0) {
            $row = $resultCheck->fetch_assoc();
            $existingSeats = array_filter(array_map('trim', explode(',', $row['booked_seats'])));
            $updatedSeats = array_unique(array_merge($existingSeats, $newSeatsArray));
            $updatedSeatsStr = implode(',', $updatedSeats);

            $updateSql = "UPDATE `$bus_number_post` SET booked_seats = ? WHERE bus_number = ? AND Booked_By = ?";
            $stmtUpdate = $conn->prepare($updateSql);
            if (!$stmtUpdate) {
                die("Prepare failed: (" . $conn->errno . ") " . $conn->error);
            }
            $stmtUpdate->bind_param("sss", $updatedSeatsStr, $bus_number_post, $name);
            if (!$stmtUpdate->execute()) {
                die("Execute failed: (" . $stmtUpdate->errno . ") " . $stmtUpdate->error);
            }
            $stmtUpdate->close();
        } else {
            $insertSql = "INSERT INTO `$bus_number_post` (bus_number, Booked_By, booked_seats, Phone) VALUES (?, ?, ?, ?)";
            $stmtInsert = $conn->prepare($insertSql);
            if (!$stmtInsert) {
                die("Prepare failed: (" . $conn->errno . ") " . $conn->error);
            }
            $seatNumbersStr = implode(',', $newSeatsArray);
            $stmtInsert->bind_param("ssss", $bus_number_post, $name, $seatNumbersStr, $phone);
            if (!$stmtInsert->execute()) {
                die("Execute failed: (" . $stmtInsert->errno . ") " . $stmtInsert->error);
            }
            $stmtInsert->close();
        }
        $stmtCheck->close();

        
        $sqlAvailable = "SELECT available_seats FROM `$bus_number_post` WHERE bus_number = ?";
        $stmtAvailable = $conn->prepare($sqlAvailable);
        if (!$stmtAvailable) {
            die("Prepare failed: (" . $conn->errno . ") " . $conn->error);
        }
        $stmtAvailable->bind_param("s", $bus_number_post);
        if (!$stmtAvailable->execute()) {
            die("Execute failed: (" . $stmtAvailable->errno . ") " . $stmtAvailable->error);
        }
        $resAvailable = $stmtAvailable->get_result();
        $rowAvailable = $resAvailable->fetch_assoc();
        $availableSeats = array_filter(array_map('trim', explode(',', $rowAvailable['available_seats'])));
        $newAvailableSeats = array_diff($availableSeats, $newSeatsArray);
        $newAvailableSeatsStr = implode(',', $newAvailableSeats);
        $stmtAvailable->close();

        $updateAvailableSql = "UPDATE `$bus_number_post` SET available_seats = ? WHERE bus_number = ?";
        $stmtUpdateAvailable = $conn->prepare($updateAvailableSql);
        if (!$stmtUpdateAvailable) {
            die("Prepare failed: (" . $conn->errno . ") " . $conn->error);
        }
        $stmtUpdateAvailable->bind_param("ss", $newAvailableSeatsStr, $bus_number_post);
        if (!$stmtUpdateAvailable->execute()) {
            die("Execute failed: (" . $stmtUpdateAvailable->errno . ") " . $stmtUpdateAvailable->error);
        }
        $stmtUpdateAvailable->close();

        
        $bookingDate = date('Y-m-d H:i:s'); 
        foreach ($newSeatsArray as $seatNum) {
            $insertBookingSql = "INSERT INTO booked_seats (booking_date, from_location, to_location, bus_number, seat_number, phone) VALUES (?, ?, ?, ?, ?, ?)";
            $stmtBooking = $conn->prepare($insertBookingSql);
            if (!$stmtBooking) {
                die("Prepare failed: (" . $conn->errno . ") " . $conn->error);
            }
            $stmtBooking->bind_param("ssssds", 
                $bookingDate, 
                $busInfo['from_location'], 
                $busInfo['to_location'], 
                $bus_number_post, 
                $seatNum, 
                $phone
            );
            if (!$stmtBooking->execute()) {
                die("Execute failed: (" . $stmtBooking->errno . ") " . $stmtBooking->error);
            }
            $stmtBooking->close();
        }

        
        header("Location: ?bus=" . urlencode($bus_number_post) . "&success=1");
        exit();
    }
}


$seatSql = "SELECT booked_seats FROM `$bus_number`";
$seatResult = $conn->query($seatSql);
$booked_seats = [];
if ($seatResult && $seatResult->num_rows > 0) {
    while ($seatData = $seatResult->fetch_assoc()) {
        $seats = array_filter(array_map('intval', explode(",", $seatData['booked_seats'])));
        $booked_seats = array_merge($booked_seats, $seats);
    }
    $booked_seats = array_unique($booked_seats);
}

$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <title>Bus Seat Booking</title>
  <link rel="stylesheet" href="book.css" />
  <script>
    function selectSeat(button) {
      if (!button.classList.contains('booked')) {
        button.classList.toggle('selected');

        
        const selectedSeats = [];
        document.querySelectorAll('.seat-column button.selected').forEach(btn => {
          selectedSeats.push(btn.innerText);
        });

        document.getElementById('seat_input').value = selectedSeats.join(',');
        document.getElementById('selected_seats_display').innerText = selectedSeats.length > 0 ? selectedSeats.join(', ') : 'None';
      }
    }
  </script>
  <style>
    
    .booked { background-color: #888; color: #fff; cursor: not-allowed; }
    .selected { background-color: #4CAF50; color: white; }
    button { margin: 3px; padding: 10px; cursor: pointer; }
  </style>
</head>
<body>
  <div class="container">
    <div class="seats">
      <div class="seat-column">
        <!-- Seats 1-20 -->
        <?php for ($i = 1; $i <= 20; $i += 2): ?>
          <div class="row">
            <?php for ($j = $i; $j <= $i + 1; $j++): 
              $isBooked = in_array($j, $booked_seats);
              $class = $isBooked ? 'booked' : '';
              $disabled = $isBooked ? 'disabled' : '';
            ?>
            <button onclick="selectSeat(this)" class="<?= $class ?>" <?= $disabled ?>><?= $j ?></button>
            <?php endfor; ?>
          </div>
        <?php endfor; ?>
      </div>

      <div class="seat-column">
        <!-- Seats 21-40 -->
        <?php for ($i = 21; $i <= 40; $i += 2): ?>
          <div class="row">
            <?php for ($j = $i; $j <= $i + 1; $j++): 
              $isBooked = in_array($j, $booked_seats);
              $class = $isBooked ? 'booked' : '';
              $disabled = $isBooked ? 'disabled' : '';
            ?>
            <button onclick="selectSeat(this)" class="<?= $class ?>" <?= $disabled ?>><?= $j ?></button>
            <?php endfor; ?>
          </div>
        <?php endfor; ?>
      </div>
    </div>

    <div class="booking-form">
      <h2>Book A Seat</h2>
      <p><strong>Bus Number:</strong> <?= htmlspecialchars($busInfo['bus_number']) ?></p>
      <p><strong>From:</strong> <?= htmlspecialchars($busInfo['from_location']) ?></p>
      <p><strong>To:</strong> <?= htmlspecialchars($busInfo['to_location']) ?></p>
      <p><strong>Time:</strong> <?= htmlspecialchars($busInfo['dispute_time']) ?></p>
      <p><strong>Date:</strong> <?= htmlspecialchars($busInfo['date']) ?></p>

      <form method="POST" action="">
        <input type="hidden" name="bus_number" value="<?= htmlspecialchars($bus_number) ?>" />
        <input type="hidden" name="seat_number" id="seat_input" required />

        <label>Selected Seats: <span id="selected_seats_display">None</span></label><br><br>

        <input type="text" name="name" placeholder="Enter Name" required />
        <input type="text" name="phone" placeholder="Enter Phone" required />
        <button class="buy-button" type="submit">BOOK</button>
      </form>
    </div>
  </div>
</body>
</html>
