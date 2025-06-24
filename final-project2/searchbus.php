<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>BusEasy Search</title>
  <link rel="stylesheet" href="searchbus.css">
</head>
<body>
  <div class="container">
    <div class="search-box">
      <img src="pics/1.png" alt="Bus Logo" class="logo">
      <form method="POST" action="">
        <input type="date" name="date" placeholder="DATE : MM/DD/YYYY" required>
        <input type="text" name="from" placeholder="FROM" required>
        <input type="text" name="to" placeholder="TO" required>
        <button type="submit" name="search">Search</button>
      </form>
    </div>

    <div class="result-box">
      <?php
      if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['search'])) {
          $conn = new mysqli("localhost", "root", "", "buseasy");

          if ($conn->connect_error) {
              die("Connection failed: " . $conn->connect_error);
          }

          $date = $conn->real_escape_string($_POST['date']);
          $from = $conn->real_escape_string($_POST['from']);
          $to = $conn->real_escape_string($_POST['to']);

        
          $sql = "SELECT * FROM route WHERE date = '$date' AND from_location = '$from' AND to_location = '$to'";
          $result = $conn->query($sql);

          if ($result->num_rows > 0) {
              echo "<table>
                      <thead>
                        <tr>
                          <th>Bus Number</th>
                          <th>From</th>
                          <th>To</th>
                          <th>Dispute Time</th>
                          <th>Date</th>
                          <th>Available Seats</th>
                          <th>Booked Seats</th>
                        </tr>
                      </thead>
                      <tbody>";

              while ($row = $result->fetch_assoc()) {
                  $bus_number = $row['bus_number'];
                  $from = $row['from_location'];
                  $to = $row['to_location'];
                  $time = $row['dispute_time'];
                  $date = $row['date'];

                  $seat_sql = "SELECT available_seats, booked_seats FROM `$bus_number` WHERE bus_number = '$bus_number'";
                  $seat_result = $conn->query($seat_sql);

                  $available_count = 0;
                  $booked_count = 0;

                  if ($seat_result && $seat_result->num_rows > 0) {
                      $seat_data = $seat_result->fetch_assoc();
                      $available = array_filter(explode(",", $seat_data['available_seats']));
                      $booked = array_filter(explode(",", $seat_data['booked_seats']));
                      $available_count = count($available);
                      $booked_count = count($booked);
                  }

                  echo "<tr>";
                  echo "<td><a href='book.php?bus=$bus_number' class='bus-btn'>$bus_number</a></td>";
                  echo "<td>$from</td>";
                  echo "<td>$to</td>";
                  echo "<td>$time</td>";
                  echo "<td>$date</td>";
                  echo "<td>$available_count</td>";
                  echo "<td>$booked_count</td>";
                  echo "</tr>";
              }

              echo "</tbody></table>";
          } else {
              echo "No matching bus found.";
          }

          $conn->close();
      }
      ?>
    </div>
  </div>
</body>
</html>
