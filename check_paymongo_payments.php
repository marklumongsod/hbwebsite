<?php

require_once('admin/inc/db_config.php');
require_once('vendor/autoload.php');
require('phpmailer/src/PHPMailer.php');
require('phpmailer/src/SMTP.php');
require('phpmailer/src/Exception.php');

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;

function updateBookingStatusBulk()
{
    $query = "SELECT link_id, booking_id FROM booking_order WHERE booking_status = 'pending'";

    $bookingOrders = select1($query, [], '');

    if (mysqli_num_rows($bookingOrders) === 0) {
        echo 'No pending booking orders found.';
        return;
    }

    while ($bookingOrder = mysqli_fetch_assoc($bookingOrders)) {
        $linkId = $bookingOrder['link_id'];
        $bookingId = $bookingOrder['booking_id'];

        $paymongoData = getPaymongoPaymentData($linkId);

        if ($paymongoData && isset($paymongoData['data']['attributes']['status'])) {
            $paymongoStatus = $paymongoData['data']['attributes']['status'];
            $paymongoClientName = $paymongoData['data']['attributes']['payments'][0]['data']['attributes']['billing']['name'];
            $amount = $paymongoData['data']['attributes']['reference_number'];
            $currency = $paymongoData['data']['attributes']['currency'];
            $description = $paymongoData['data']['attributes']['description'];

            $sql = "SELECT * FROM admin_email WHERE id = ?";
            $result = select1($sql, [1], "i");
            $adminEmail = null;
            if (mysqli_num_rows($result) > 0) {
                $row = $result->fetch_assoc();
                $adminEmail = htmlspecialchars($row['email']); // Ensure to sanitize the output
            }

            if ($paymongoStatus === 'paid') {
                $updateQuery = "UPDATE booking_order SET booking_status = 'booked' WHERE link_id = ?";
                update($updateQuery, [$linkId], 's');
                sendMail($adminEmail, $paymongoClientName, $amount, $currency, $description, $linkId, $paymongoStatus);
                echo "Booking order with link_id $linkId has been updated to 'booked' status.\n";
            } else {
                echo "Booking order with link_id $linkId has not been paid yet. Status: $paymongoStatus.\n";
            }
        } else {
            echo "Error: Could not retrieve payment status for link_id $linkId.\n";
        }
    }
}

function getPaymongoPaymentData($linkId)
{
    $paymongoApiKey = 'sk_test_Kn8SLEjoKT6QSBWfJYAmxJ4P';
    $url = "https://api.paymongo.com/v1/links/$linkId";

    $client = new Client();

    try {
        $response = $client->request('GET', $url, [
            'headers' => [
                'Accept' => 'application/json',
                'Authorization' => 'Basic ' . base64_encode($paymongoApiKey . ':'),
            ],
        ]);

        $responseData = json_decode($response->getBody(), true);

        if (isset($responseData['data'])) {
            return $responseData;
        } else {
            echo "Error: Invalid response from Paymongo API for link_id $linkId.";
            return null;
        }
    } catch (RequestException $e) {
        if ($e->hasResponse()) {
            $errorResponse = json_decode($e->getResponse()->getBody(), true);
            echo "Error: " . htmlspecialchars(print_r($errorResponse, true));
        } else {
            echo "Request Error: " . htmlspecialchars($e->getMessage());
        }
        return null;
    }
}


function sendMail($recipientEmail, $paymongoClientName, $amount, $currency, $description, $linkId, $paymentStatus)
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
        $mail->Subject = 'Booking Confirmation - Villa Ocampo Resort';
        $mail->Body    =    '<h3>New Booking Confirmation for ' . htmlspecialchars($paymongoClientName) . '</h3>
                            <p>Dear Admin,</p>
                            <p>This is to inform you of a new booking that has been confirmed. Here are the details:</p>
                            <ul>
                                <li><strong>Booking ID:</strong> ' . htmlspecialchars($linkId) . '</li>
                                <li><strong>Client Name:</strong> ' . htmlspecialchars($paymongoClientName) . '</li>
                                <li><strong>Reference No:</strong> ' . htmlspecialchars($amount) . ' ' . htmlspecialchars($currency) . '</li>
                                <li><strong>Transaction Description:</strong> ' . htmlspecialchars($description) . '</li>
                                <li><strong>Status:</strong> ' . htmlspecialchars($paymentStatus) . '</li>
                            </ul>
                            <p>Please ensure to update the records accordingly. Thank you!</p>
                            <p>Best regards,<br>Villa Ocampo Resort Team</p>';
        $mail->send();
        echo 'Message has been sent to ' . htmlspecialchars($recipientEmail);
    } catch (Exception $e) {
        echo "Message could not be sent. Mailer Error: {$mail->ErrorInfo}";
    }
}


updateBookingStatusBulk();
