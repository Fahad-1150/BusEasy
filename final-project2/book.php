<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

$bus_number = $_GET['bus'] ?? '';

$conn = new mysqli("localhost", "root", "", "buseasy");
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
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

// ✅ Fetch price per seat
$price_per_seat = 0;
$priceSql = "SELECT price FROM route_price WHERE from_location = ? AND to_location = ? LIMIT 1";
$priceStmt = $conn->prepare($priceSql);
$priceStmt->bind_param("ss", $busInfo['from_location'], $busInfo['to_location']);
$priceStmt->execute();
$priceResult = $priceStmt->get_result();

if ($priceResult && $priceResult->num_rows > 0) {
    $priceRow = $priceResult->fetch_assoc();
    $price_per_seat = floatval($priceRow['price']);
}
$priceStmt->close();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $seat_numbers_arr = $_POST['seat_number'] ?? [];

    if (empty($name) || empty($phone) || empty($seat_numbers_arr)) {
        echo "<script>alert('Please fill all fields and select at least one seat.');</script>";
    } else {
        $newSeatsArray = array_filter(array_map('intval', $seat_numbers_arr));
        sort($newSeatsArray);

        $routeRowSql = "SELECT available_seats, booked_seats FROM `$bus_number` WHERE from_location IS NOT NULL LIMIT 1";
        $result = $conn->query($routeRowSql);
        if (!$result || $result->num_rows === 0) {
            die("Bus seats info not found.");
        }
        $row = $result->fetch_assoc();

        $available_seats = array_filter(array_map('intval', explode(',', $row['available_seats'])));
        $existingBookedSeats = array_filter(array_map('intval', explode(',', $row['booked_seats'])));

        $newAvailableSeats = array_diff($available_seats, $newSeatsArray);
        $newBookedSeats = array_unique(array_merge($existingBookedSeats, $newSeatsArray));

        sort($newAvailableSeats);
        sort($newBookedSeats);

        $newAvailableSeatsStr = implode(',', $newAvailableSeats);
        $newBookedSeatsStr = implode(',', $newBookedSeats);

        $updateSeatsSql = "UPDATE `$bus_number` SET available_seats = ?, booked_seats = ? WHERE from_location IS NOT NULL LIMIT 1";
        $stmtUpdateSeats = $conn->prepare($updateSeatsSql);
        $stmtUpdateSeats->bind_param("ss", $newAvailableSeatsStr, $newBookedSeatsStr);
        $stmtUpdateSeats->execute();
        $stmtUpdateSeats->close();

        $bookingDate = date('Y-m-d');
        foreach ($newSeatsArray as $seatNum) {
            $insertBookingSql = "INSERT INTO booked_seats (booking_date, from_location, to_location, bus_number, seat_number, phone) VALUES (?, ?, ?, ?, ?, ?)";
            $stmtBooking = $conn->prepare($insertBookingSql);
            $seatNumStr = (string)$seatNum;
            $stmtBooking->bind_param("ssssss", $bookingDate, $busInfo['from_location'], $busInfo['to_location'], $bus_number, $seatNumStr, $phone);
            $stmtBooking->execute();
            $stmtBooking->close();
        }

        header("Location: ?bus=" . urlencode($bus_number) . "&success=1");
        exit();
    }
}

$seatSql = "SELECT booked_seats FROM `$bus_number` WHERE from_location IS NOT NULL LIMIT 1";
$seatResult = $conn->query($seatSql);
$booked_seats = [];
if ($seatResult && $seatResult->num_rows > 0) {
    $seatData = $seatResult->fetch_assoc();
    if (!empty($seatData['booked_seats'])) {
        $booked_seats = array_filter(array_map('intval', explode(',', $seatData['booked_seats'])));
    }
}

$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8" />
<title>Bus Seat Booking - <?= htmlspecialchars($bus_number) ?></title>
<link rel="stylesheet" href="book.css" />
<style>
  .seat-checkbox {
    display: none;
  }

  .seat-label {
    display: inline-block;
    width: 50px;
    height: 50px;
    background: #eee;
    border-radius: 10px;
    font-weight: bold;
    line-height: 50px;
    text-align: center;
    margin: 3px;
    cursor: pointer;
    user-select: none;
    transition: 0.3s;
    color: black;
  }

  .seat-checkbox:checked + .seat-label {
    background-color: green;
    color: white;
    box-shadow: 0 0 10px lime;
  }

  .seat-label.booked {
    background-color: gray;
    color: black;
    cursor: not-allowed;
  }
</style>
<?php if (isset($_GET['success'])): ?>
<script>alert("Booking successful!");</script>
<?php endif; ?>
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
              $disabled = $isBooked ? 'disabled' : '';
              $labelClass = $isBooked ? 'seat-label booked' : 'seat-label';
            ?>
              <input type="checkbox" name="seat_number[]" value="<?= $j ?>" id="seat<?= $j ?>" class="seat-checkbox" <?= $disabled ?> form="bookingForm" />
              <label for="seat<?= $j ?>" class="<?= $labelClass ?>"><?= $j ?></label>
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
              $disabled = $isBooked ? 'disabled' : '';
              $labelClass = $isBooked ? 'seat-label booked' : 'seat-label';
            ?>
              <input type="checkbox" name="seat_number[]" value="<?= $j ?>" id="seat<?= $j ?>" class="seat-checkbox" <?= $disabled ?> form="bookingForm" />
              <label for="seat<?= $j ?>" class="<?= $labelClass ?>"><?= $j ?></label>
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

      <!-- ✅ Price Info -->
      <p><strong>Price per Seat:</strong> ৳ <?= number_format($price_per_seat, 2) ?></p>
      
        <?php
         
         
        ?>
      </p>

      <form method="POST" action="" id="bookingForm">
        <input type="hidden" name="bus_number" value="<?= htmlspecialchars($bus_number) ?>" />
        <input type="text" name="name" placeholder="Enter Name" required />
        <input type="text" name="phone" placeholder="Enter Phone" required />
        <button class="buy-button" type="submit">BOOK</button>
      </form>
    </div>
  </div>
</body>
</html>
