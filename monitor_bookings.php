<?php
session_start();
require('admin/inc/db_config.php');
require('admin/inc/essentials.php');
require('phpmailer/src/PHPMailer.php');
require('phpmailer/src/SMTP.php');
require('phpmailer/src/Exception.php');

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

date_default_timezone_set("Asia/Manila");

$current_date = date("Y-m-d");
$current_time = date("H:i:s");

$current_datetime = new DateTime($current_date . ' ' . $current_time);
$current_timestamp = $current_datetime->getTimestamp();

if (!isset($_SESSION['emailSent'])) {
    $_SESSION['emailSent'] = [];
}

$sql = "SELECT * FROM admin_email WHERE id = ?";
$result = select1($sql, [1], "i");
$adminEmail = null;
if (mysqli_num_rows($result) > 0) {
    $row = $result->fetch_assoc();
    $adminEmail = htmlspecialchars($row['email']);
}

$query = "SELECT bd.*, bo.booking_status, u.email, u.name 
          FROM booking_details bd
          JOIN booking_order bo ON bd.booking_id = bo.booking_id
          JOIN user_cred u ON bo.user_id = u.id
          WHERE bo.booking_status = 'booked'";

$result = select1($query, [], '');

if ($result && mysqli_num_rows($result) > 0) {
    while ($booking = mysqli_fetch_assoc($result)) {
        $check_in_date = $booking['check_in_date'];
        $check_in_time = $booking['check_in_time'];
        $duration = $booking['duration'];
        $room_name = $booking['room_name'];
        $room_no = $booking['room_no'];
        $customer_email = $booking['email'];
        $customer_name = $booking['name'];

        echo "Processing: Room $room_name (No. $room_no)<br>";

        $check_in_datetime = new DateTime($check_in_date . ' ' . $check_in_time);
        $check_in_timestamp = $check_in_datetime->getTimestamp();

        $check_out_datetime = clone $check_in_datetime;
        $check_out_datetime->add(new DateInterval('PT' . $duration . 'H'));
        $check_out_timestamp = $check_out_datetime->getTimestamp();

        $notification_time_20min = $check_out_timestamp - (20 * 60); 
        $notification_time_10min = $check_out_timestamp - (10 * 60); 

        if (!isset($_SESSION['emailSent'][$booking['booking_id']])) {
            $_SESSION['emailSent'][$booking['booking_id']] = ['20min' => false, '10min' => false];
        }

        $notificationSent = false;

        if (
            !$_SESSION['emailSent'][$booking['booking_id']]['20min'] &&
            $current_timestamp < $check_out_timestamp &&
            $current_timestamp >= $notification_time_20min
        ) {
            sendMail($customer_email, $customer_name, $room_no, $room_name, '20-minute reminder', $check_out_timestamp);

            if ($adminEmail) {
                sendMail($adminEmail, 'Admin', $room_no, $room_name, '20-minute reminder', $check_out_timestamp, true);
            }

            $_SESSION['emailSent'][$booking['booking_id']]['20min'] = true;
            $notificationSent = true;
        }

        if (
            !$_SESSION['emailSent'][$booking['booking_id']]['10min'] &&
            $current_timestamp < $check_out_timestamp &&
            $current_timestamp >= $notification_time_10min
        ) {
            sendMail($customer_email, $customer_name, $room_no, $room_name, '10-minute reminder', $check_out_timestamp);

            if ($adminEmail) {
                sendMail($adminEmail, 'Admin', $room_no, $room_name,  '10-minute reminder', $check_out_timestamp, true);
            }

            $_SESSION['emailSent'][$booking['booking_id']]['10min'] = true;
            $notificationSent = true;
        }

        if (!$notificationSent) {
            echo "No notifications sent for Room $room_name (No. $room_no).<br>";
        }

        echo "<hr>";
    }
} else {
    echo "No booking details found with 'booked' status.";
}

function sendMail($recipientEmail, $name, $room_no, $room_name, $notification_type, $check_out_timestamp, $isAdmin = false)
{
    $mail = new PHPMailer(true);

    try {
        $mail->isSMTP();
        $mail->Host = 'smtp.gmail.com';
        $mail->SMTPAuth = true;
        $mail->Username = 'resort.villaocampo@gmail.com';
        $mail->Password = 'acjkbsyaldymquuo';
        $mail->SMTPSecure = 'tls';
        $mail->Port = 587;

        $mail->setFrom('resort.villaocampo@gmail.com', 'Villa Ocampo Resort');
        $mail->addAddress($recipientEmail);

        $mail->isHTML(true);
        $mail->Subject = 'Booking Notification - Villa Ocampo Resort';

        if (!$isAdmin) {
            $mail->Body = '
            <h3>' . htmlspecialchars($notification_type) . ' for ' . htmlspecialchars($name) . '</h3>
            <p>Dear ' . htmlspecialchars($name) . ',</p>
            <p>We hope you are enjoying your stay at Villa Ocampo Resort! This is a friendly reminder that your booking for <strong>' . htmlspecialchars($room_name) . ' (Room No: ' . htmlspecialchars($room_no) . ')</strong> is approaching its end.</p>
            <p><strong>Check-out Time:</strong> ' . date("Y-m-d H:i:s", $check_out_timestamp) . '</p>
            <p>Thank you for choosing Villa Ocampo Resort!</p>
            <p>Best regards,<br>The Villa Ocampo Resort Team</p>';
        } else { 
            $mail->Body = '
            <h3>' . htmlspecialchars($notification_type) . ' for ' . htmlspecialchars($name) . '</h3>
            <p>Dear Admin,</p>
            <p>This is a reminder that the booking for <strong>' . htmlspecialchars($room_name) . ' (Room No: ' . htmlspecialchars($room_no) . ')</strong> is approaching its end.</p>
            <p><strong>Check-out Time:</strong> ' . date("Y-m-d H:i:s", $check_out_timestamp) . '</p>
            <p>Please ensure the room is prepared for the next guest.</p>
            <p>Best regards,<br>The Villa Ocampo Resort System</p>';
        }

        $mail->send();
        echo 'Message has been sent to ' . htmlspecialchars($recipientEmail) . ' for ' . $notification_type . ' <br>' ;
    } catch (Exception $e) {
        echo "Message could not be sent. Mailer Error: {$mail->ErrorInfo}";
    }
}

