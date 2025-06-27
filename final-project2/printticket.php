<?php
// printticket.php

$conn = new mysqli("localhost", "root", "", "buseasy");
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

if (!isset($_GET['booking_id']) || !is_numeric($_GET['booking_id'])) {
    die("Invalid booking ID.");
}

$booking_id = intval($_GET['booking_id']);

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
    LEFT JOIN route r ON bs.bus_number = r.bus_number
    WHERE bs.id = ?
    LIMIT 1
";

$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $booking_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    die("Booking not found.");
}

$booking = $result->fetch_assoc();

$conn->close();
?>

<!DOCTYPE html>
<html>
<head>
    <title>Print Ticket - <?php echo htmlspecialchars($booking['bus_number']); ?></title>
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 30px;
            max-width: 600px;
            position: relative;
        }
        .logo {
            position: left;
            top: 10px;
            right: 10px;
            width: 120px;
            height: auto;
        }
        h2 {
            text-align: center;
            margin-bottom: 30px;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 30px;
        }
        th, td {
            padding: 10px;
            border: 1px solid #444;
            text-align: left;
        }
        th {
            background-color: #555;
            color: white;
            width: 40%;
        }
        #print-btn {
            display: block;
            margin: 0 auto;
            padding: 10px 20px;
            font-size: 16px;
            cursor: pointer;
            background-color: #3498db;
            color: white;
            border: none;
            border-radius: 5px;
        }
        @media print {
            #print-btn {
                display: none;
            }
        }
    </style>
</head>
<body>

<!-- Logo -->
<img src="/final-project2/pics/logowithoutbackground.png" alt="BusEasy Logo" class="logo" />

<h2>BusEasy</h2>

<table>
    <tr>
        <th>Booking Date</th>
        <td><?php echo htmlspecialchars($booking['booking_date']); ?></td>
    </tr>
    <tr>
        <th>From</th>
        <td><?php echo htmlspecialchars($booking['from_location']); ?></td>
    </tr>
    <tr>
        <th>To</th>
        <td><?php echo htmlspecialchars($booking['to_location']); ?></td>
    </tr>
    <tr>
        <th>Bus Number</th>
        <td><?php echo htmlspecialchars($booking['bus_number']); ?></td>
    </tr>
    <tr>
        <th>Dispute Time</th>
        <td><?php echo htmlspecialchars($booking['dispute_time']); ?></td>
    </tr>
    <tr>
        <th>Date</th>
        <td><?php echo htmlspecialchars($booking['date']); ?></td>
    </tr>
    <tr>
        <th>Seat Number</th>
        <td><?php echo htmlspecialchars($booking['seat_number']); ?></td>
    </tr>
    <tr>
        <th>Phone</th>
        <td><?php echo htmlspecialchars($booking['phone']); ?></td>
    </tr>
</table>

<button id="print-btn" onclick="window.print()">Print Ticket</button>

</body>
</html>
