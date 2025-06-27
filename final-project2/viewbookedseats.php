<?php
$conn = new mysqli("localhost", "root", "", "buseasy");
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

if (!isset($_GET['bus'])) {
    die("No bus selected.");
}
$bus_number = $conn->real_escape_string($_GET['bus']);

// Fetch dispute_time from route table for this bus
$route_sql = "SELECT dispute_time FROM route WHERE bus_number='$bus_number' LIMIT 1";
$route_res = $conn->query($route_sql);
$dispute_time_route = '';
if ($route_res && $route_res->num_rows > 0) {
    $route_row = $route_res->fetch_assoc();
    $dispute_time_route = $route_row['dispute_time'] ?? '';
}

// Helper function: Update seat lists in bus table after changes in booked_seats
function updateBusSeatLists($conn, $bus_number) {
    $sql = "SELECT seat_number FROM booked_seats WHERE bus_number='$bus_number'";
    $res = $conn->query($sql);
    $booked_seats_arr = [];
    if ($res && $res->num_rows > 0) {
        while ($row = $res->fetch_assoc()) {
            $booked_seats_arr[] = $row['seat_number'];
        }
    }
    $all_seats = range(1, 40); // adjust total seats if needed
    $available_seats_arr = array_diff($all_seats, $booked_seats_arr);

    $available_seats_str = implode(',', $available_seats_arr);
    $booked_seats_str = implode(',', $booked_seats_arr);

    $update_sql = "UPDATE `$bus_number` SET 
        available_seats='$available_seats_str',
        booked_seats='$booked_seats_str'
        WHERE bus_number='$bus_number'";

    if (!$conn->query($update_sql)) {
        return "Failed to update bus seat lists: " . $conn->error;
    }
    return true;
}

// Handle POST requests (edit or cancel)
$message = $error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    $booking_id = intval($_POST['booking_id'] ?? 0);

    if ($booking_id <= 0) {
        $error = "Invalid booking ID.";
    } else {
        if ($action === 'edit') {
            // Only seat_number and phone are editable
            $seat_number = intval($_POST['seat_number'] ?? 0);
            $phone = $conn->real_escape_string($_POST['phone'] ?? '');

            if ($seat_number < 1 || $seat_number > 40) {
                $error = "Seat number must be between 1 and 40.";
            } else {
                // Check if the seat number is already booked by someone else
                $check_sql = "SELECT id FROM booked_seats WHERE bus_number='$bus_number' AND seat_number=$seat_number AND id != $booking_id";
                $check_res = $conn->query($check_sql);
                if ($check_res && $check_res->num_rows > 0) {
                    $error = "Seat number $seat_number is already booked.";
                } else {
                    // Update only seat_number and phone for the booking
                    $update_sql = "UPDATE booked_seats SET 
                        seat_number=$seat_number,
                        phone='$phone'
                        WHERE id=$booking_id";

                    if ($conn->query($update_sql)) {
                        $res_update = updateBusSeatLists($conn, $bus_number);
                        if ($res_update === true) {
                            $message = "Booking updated successfully.";
                        } else {
                            $error = $res_update;
                        }
                    } else {
                        $error = "Failed to update booking: " . $conn->error;
                    }
                }
            }
        } elseif ($action === 'cancel') {
            // Delete booking
            $del_sql = "DELETE FROM booked_seats WHERE id=$booking_id";
            if ($conn->query($del_sql)) {
                $res_update = updateBusSeatLists($conn, $bus_number);
                if ($res_update === true) {
                    $message = "Booking cancelled successfully.";
                } else {
                    $error = $res_update;
                }
            } else {
                $error = "Failed to cancel booking: " . $conn->error;
            }
        }
    }
}

// Fetch all bookings for this bus
$bookings_sql = "SELECT * FROM booked_seats WHERE bus_number='$bus_number' ORDER BY booking_date, seat_number";
$bookings_res = $conn->query($bookings_sql);

$conn->close();
?>

<!DOCTYPE html>
<html>
<head>
    <title>View Booked Seats - <?php echo htmlspecialchars($bus_number); ?></title>
    <link rel="stylesheet" href="viewbookedseats.css">
</head>
<body>

<h2>Booked Seats for Bus: <?php echo htmlspecialchars($bus_number); ?></h2>

<?php if ($message): ?>
    <div class="message"><?php echo $message; ?></div>
<?php endif; ?>

<?php if ($error): ?>
    <div class="error"><?php echo $error; ?></div>
<?php endif; ?>

<?php if ($bookings_res && $bookings_res->num_rows > 0): ?>
<table>
    <thead>
        <tr>
            <th>Booking Date</th>
            <th>From</th>
            <th>To</th>
            <th>Dispute Time</th>
            <th>Seat Number</th>
            <th>Phone</th>
            <th>Actions</th>
        </tr>
    </thead>
    <tbody>
        <?php while ($row = $bookings_res->fetch_assoc()): ?>
        <tr>
            <form method="post" class="inline-form">
                <input type="hidden" name="booking_id" value="<?php echo $row['id'] ?? ''; ?>">

                <td><?php echo htmlspecialchars($row['booking_date'] ?? ''); ?></td>
                <td><?php echo htmlspecialchars($row['from_location'] ?? ''); ?></td>
                <td><?php echo htmlspecialchars($row['to_location'] ?? ''); ?></td>
                <td><?php echo htmlspecialchars($dispute_time_route); ?></td>

                <td>
                    <input type="number" name="seat_number" value="<?php echo htmlspecialchars($row['seat_number'] ?? ''); ?>" min="1" max="40" required>
                </td>

                <td>
                    <input type="text" name="phone" value="<?php echo htmlspecialchars($row['phone'] ?? ''); ?>" required>
                </td>

                <td>
                    <button type="submit" name="action" value="edit" class="edit-btn" title="Update Booking">Edit</button>
                    <button type="submit" name="action" value="cancel" class="cancel-btn" title="Cancel Booking" onclick="return confirm('Are you sure to cancel this booking?');">Cancel</button>
                    <button type="button" class="print-btn" title="Print Ticket" onclick="window.open('printticket.php?booking_id=<?php echo $row['id']; ?>', '_blank')">Print</button>
                    <script>
function printBooking(rowId) {
    var row = document.getElementById(rowId);
    if (!row) return;

    // Use your logo path relative to your project root (htdocs/final-project2)
    var logoPath = 'pics/logowithoutbackground.png';

    var printWindow = window.open('', '', 'height=600,width=800');
    printWindow.document.write('<html><head><title>Print Booking</title>');
    printWindow.document.write('<style>');
    printWindow.document.write('body { font-family: Arial, sans-serif; margin: 20px; }');
    printWindow.document.write('.header { display: flex; align-items: center; justify-content: center; position: relative; margin-bottom: 20px; }');
    printWindow.document.write('.logo { position: absolute; left: 0; width: 80px; height: auto; }');
    printWindow.document.write('.title { font-size: 24px; font-weight: bold; }');
    printWindow.document.write('.table-container { display: flex; justify-content: center; }');
    printWindow.document.write('table { border-collapse: collapse; width: 100%; max-width: 600px; }');
    printWindow.document.write('td, th { border: 1px solid #aaa; padding: 8px; }');
    printWindow.document.write('</style>');
    printWindow.document.write('</head><body>');

    printWindow.document.write('<div class="header">');
    printWindow.document.write('<img src="' + logoPath + '" alt="BusEasy Logo" class="logo" />');
    printWindow.document.write('<div class="title">BusEasy</div>');
    printWindow.document.write('</div>');

    printWindow.document.write('<div class="table-container">');
    printWindow.document.write('<table>');
    printWindow.document.write('<tr><th>Field</th><th>Value</th></tr>');

    var cells = row.querySelectorAll('td');
    var headers = ['Booking Date','From','To','Bus Number','Dispute Time','Date','Seat Number','Phone'];

    for (var i = 0; i < headers.length; i++) {
        printWindow.document.write('<tr><td>' + headers[i] + '</td><td>' + cells[i].innerText + '</td></tr>');
    }

    printWindow.document.write('</table>');
    printWindow.document.write('</div>');

    printWindow.document.write('</body></html>');

    printWindow.document.close();
    printWindow.focus();
    printWindow.print();
}
</script>

                </td>
            </form>
        </tr>
        <?php endwhile; ?>
    </tbody>
</table>
<?php else: ?>
<p style="text-align:center; margin-top: 30px;">No bookings found for this bus.</p>
<?php endif; ?>

</body>
</html>
