<?php
// Enable all errors and display them
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

echo "<!DOCTYPE html><html><head><title>Add Bus</title>
<style>
  body {
    margin: 0;
    padding: 0;
    background-color: #000;
    font-family: Arial, sans-serif;
    color: white;
  }
  
  .container {
    display: flex;
    flex-direction: row;
    justify-content: space-between;
    border: 4px solid #000000;
    padding: 30px;
    height: 100vh;
    box-sizing: border-box;
  }
  
  .top-bar {
    position: absolute;
    top: 10px;
    left: 10px;
  }
  
  .logo {
    width: 70px;
    height: 70px;
  }
  
  .top-bar h1 {
    text-align: center;
    margin-top: 5px;
    font-size: 32px;
    font-weight: bold;
    color: white; 
  }
  
  .form-section {
    width: 50%;
    display: flex;
    flex-direction: column;
    justify-content: center;
    padding-left: 50px;
  }
  
  .input-row {
    display: flex;
    align-items: center;
    margin: 15px 0;
  }
  
  .input-row label {
    width: 120px;
    font-size: 18px;
    font-weight: bold;
    margin-right: 20px;
    background: white;
    color: black;
    padding: 10px 15px;
    border-radius: 25px;
    text-align: center;
  }
  
  .input-row input {
    padding: 10px 20px;
    border-radius: 25px;
    border: none;
    outline: none;
    font-size: 16px;
    background: linear-gradient(to right, #ffffff, #dcdcdc);
    width: 250px;
  }
  
  .add-btn-container {
    margin-top: 100px;
  }
  
  button[type=submit] {
    padding: 10px 40px;
    font-size: 18px;
    font-weight: bold;
    border-radius: 25px;
    border: none;
    background: linear-gradient(to right, #ffffff, #dcdcdc);
    cursor: pointer;
    transition: background 0.3s ease;
  }
  
  button[type=submit]:hover {
    background: linear-gradient(to right, #b0b0b0, #e0e0e0);
  }
  
  h2 {
    margin-bottom: 20px;
  }

  form {
    max-width: 500px;
  }
</style>
</head><body>";

$conn = new mysqli("localhost", "root", "", "buseasy");
if ($conn->connect_error) {
    die("<p style='color:red;'>Connection failed: " . $conn->connect_error . "</p></body></html>");
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Sanitize bus number to avoid SQL injection and illegal table name chars
    $bus_number = preg_replace('/[^a-zA-Z0-9_]/', '_', $_POST['bus_number']);
    $from = $conn->real_escape_string($_POST['from']);
    $to = $conn->real_escape_string($_POST['to']);
    $dispute_time = $conn->real_escape_string($_POST['time']);
    $date = $conn->real_escape_string($_POST['date']); 

    // Insert route info
    $sql_route = "INSERT INTO route (bus_number, from_location, to_location, dispute_time, date) 
                  VALUES ('$bus_number', '$from', '$to', '$dispute_time', '$date')";

    if ($conn->query($sql_route) === TRUE) {
        // Create bus table with exact columns
        $sql_create_table = "CREATE TABLE `$bus_number` (
            bus_number VARCHAR(50),
            from_location VARCHAR(255),
            to_location VARCHAR(255),
            dispute_time VARCHAR(255),
            available_seats TEXT,
            booked_seats TEXT
        )";

        if ($conn->query($sql_create_table) === TRUE) {
            $available_seats = implode(',', range(1, 40));
            $booked_seats = "";

            // Insert initial row
            $sql_insert_initial = "INSERT INTO `$bus_number` 
                (bus_number, from_location, to_location, dispute_time, available_seats, booked_seats)
                VALUES ('$bus_number', '$from', '$to', '$dispute_time', '$available_seats', '$booked_seats')";

            if ($conn->query($sql_insert_initial) === TRUE) {
                echo "<p style='color: green;'>Bus <strong>$bus_number</strong> created successfully!</p>";
            } else {
                echo "<p style='color: red;'>Error inserting initial row: " . $conn->error . "</p>";
            }
        } else {
            echo "<p style='color: red;'>Error creating bus table: " . $conn->error . "</p>";
        }
    } else {
        echo "<p style='color: red;'>Error inserting into route: " . $conn->error . "</p>";
    }
}

$conn->close();

echo '
<h2>Add New Bus</h2>
<form method="post" action="">
  <div class="input-row">
    <label for="bus_number">Bus Number:</label>
    <input type="text" id="bus_number" name="bus_number" required>
  </div>
  <div class="input-row">
    <label for="from">From:</label>
    <input type="text" id="from" name="from" required>
  </div>
  <div class="input-row">
    <label for="to">To:</label>
    <input type="text" id="to" name="to" required>
  </div>
  <div class="input-row">
    <label for="time">Dispute Time:</label>
    <input type="text" id="time" name="time" required>
  </div>
  <div class="input-row">
    <label for="date">Date:</label>
    <input type="date" id="date" name="date" required>
  </div>
  <div class="add-btn-container">
    <button type="submit">Add Bus</button>
  </div>
</form>
</body></html>';
?>
