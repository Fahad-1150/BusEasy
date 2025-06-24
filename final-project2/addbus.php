<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);


$conn = new mysqli("localhost", "root", "", "buseasy");
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $bus_number = preg_replace('/[^a-zA-Z0-9_]/', '_', $_POST['bus_number']);
    $from = $_POST['from'];
    $to = $_POST['to'];
    $dispute_time = $_POST['time'];
    $date = $_POST['date']; 

    
    $sql_route = "INSERT INTO route (bus_number, from_location, to_location, dispute_time, date) 
                  VALUES ('$bus_number', '$from', '$to', '$dispute_time', '$date')";

    if ($conn->query($sql_route) === TRUE) {
        
        $sql_create_table = "CREATE TABLE `$bus_number` (
            bus_number VARCHAR(50) ,
            from_location VARCHAR(255),
            to_location VARCHAR(255),
            dispute_time VARCHAR(255),
            available_seats TEXT,
            booked_seats TEXT,
            Seat_number VARCHAR(255),
            Booked_By VARCHAR(255),
            Phone VARCHAR(255),
            date DATE
        )";

        if ($conn->query($sql_create_table) === TRUE) {
            $available_seats = implode(",", range(1, 40));
            $booked_seats = "";

            
            $sql_insert_initial = "INSERT INTO `$bus_number` 
                (bus_number, from_location, to_location, dispute_time, available_seats, booked_seats, date)
                VALUES ('$bus_number', '$from', '$to', '$dispute_time', '$available_seats', '$booked_seats', '$date')";

            if ($conn->query($sql_insert_initial) === TRUE) {
                echo "<p style='color: green;'> '$bus_number' created successfully!</p>";
            } else {
                echo "<p style='color: red;'> Error : " . $conn->error . "</p>";
            }
        } else {
            echo "<p style='color: red;'>Error: " . $conn->error . "</p>";
        }
    } else {
        echo "<p style='color: red;'> Error : " . $conn->error . "</p>";
    }

    $conn->close();
}
?>


<!DOCTYPE html>
<html>
<head>
 
  <link rel="stylesheet" href="addbus.css">
</head>
<body>
  <div class="container">
    <div class="top-bar">
      <img src="pics/1.png" alt="Logo" class="logo">
      
    </div>

    <div class="form-section">
      <form method="post">
        <div class="input-row">
          <label>Bus Num</label>
          <input type="text" name="bus_number" placeholder="Text Field" required>
        </div>
        <div class="input-row">
          <label>From</label>
          <input type="text" name="from" placeholder="Text Field" required>
        </div>
        <div class="input-row">
          <label>To</label>
          <input type="text" name="to" placeholder="Text Field" required>
        </div>
        <div class="input-row">
          <label>Dispute Time</label>
          <input type="text" name="time" placeholder="Text Field" required>
        </div>
        <div class="input-row">
          <label>Date</label>
          <input type="date" name="date" required>
        </div>
        <div class="add-btn-container">
          <button type="submit" class="add-btn">ADD</button>
        </div>
      </form>
    </div>

    <div class="image-section">
      <img src="pics/pngwing.com (6).png" alt="Bus" class="bus-img">
    </div>
  </div>
</body>
</html>
