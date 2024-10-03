<?php
session_start(); 
require('admin/inc/db_config.php');
require('admin/inc/essentials.php');

// Set the correct timezone
date_default_timezone_set("Asia/Manila");

// Get the current date and time
$current_date = date("Y-m-d");
$current_time = date("H:i:s");

// Create a DateTime object for the current time
$current_datetime = new DateTime($current_date . ' ' . $current_time);
$current_timestamp = $current_datetime->getTimestamp();

// Query to get all room details from booking_details
$query = "SELECT * FROM booking_details";

// Call select with empty values and datatypes since there are no parameters
$result = select1($query, [], ''); // Pass empty array for values and empty string for datatypes

if ($result && mysqli_num_rows($result) > 0) {
    while ($booking = mysqli_fetch_assoc($result)) {
        $check_in_date = $booking['check_in_date'];
        $check_in_time = $booking['check_in_time'];
        $duration = $booking['duration'];
        $room_name = $booking['room_name'];
        $room_no = $booking['room_no'];

        // Print room details
        echo "Processing: Room $room_name (No. $room_no)<br>";

        // Create a DateTime object for the check-in time
        $check_in_datetime = new DateTime($check_in_date . ' ' . $check_in_time);
        $check_in_timestamp = $check_in_datetime->getTimestamp();

        // Calculate the check-out time
        $check_out_datetime = clone $check_in_datetime;
        $check_out_datetime->add(new DateInterval('PT' . $duration . 'H'));
        $check_out_timestamp = $check_out_datetime->getTimestamp();

        // Log current and check-in/check-out times
        echo "Current time: " . $current_datetime->format('Y-m-d H:i:s') . " (Timestamp: $current_timestamp)<br>";
        echo "Check-in time: " . $check_in_datetime->format('Y-m-d H:i:s') . " (Timestamp checkin: $check_in_timestamp)<br>";
        echo "Check-out time: " . $check_out_datetime->format('Y-m-d H:i:s') . " (Timestamp checkout: $check_out_timestamp)<br>";

        // Check if the current time is within the booking period
        if ($current_timestamp >= $check_in_timestamp && $current_timestamp < $check_out_timestamp) {
            // Update room status to "Not Available" only if removed = 0
            $update_query = "UPDATE rooms SET isAvailable = 0 WHERE name = ? AND room_no = ? AND removed = ?";
            if (insert($update_query, [$room_name, $room_no, 0], 'ssi')) {
                echo "Room status updated to 'Not Available' (Check-in time passed, room not removed).<br>";
            } else {
                echo "No update needed or error updating room status to 'Not Available'.<br>";
            }
        } elseif ($current_timestamp >= $check_out_timestamp) {
            // Update room status to "Available" only if removed = 0
            $update_query = "UPDATE rooms SET isAvailable = 1 WHERE name = ? AND room_no = ? AND removed = ?";
            if (insert($update_query, [$room_name, $room_no, 0], 'ssi')) {
                echo "Room status updated to 'Available' (Check-out time passed, room not removed).<br>";
            } else {
                echo "No update needed or error updating room status to 'Available'.<br>";
            }
        } else {
            echo "No status change needed (Current time is before check-in).<br>";
        }

        echo "<hr>"; // Add a separator between room updates
    }
} else {
    echo "No booking details found.";
}
?>
