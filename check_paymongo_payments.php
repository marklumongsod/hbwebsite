<?php
require_once('admin/inc/db_config.php');
require_once('vendor/autoload.php'); 

use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;

function updateBookingStatusBulk() {
    // Prepare the query to fetch only pending booking orders
    $query = "SELECT link_id, booking_id FROM booking_order WHERE booking_status = 'pending'";
    
    // Fetch the booking orders using the select function
    $bookingOrders = select1($query, [], ''); // No values or datatypes needed for this query

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

            if ($paymongoStatus === 'paid') {
                $updateQuery = "UPDATE booking_order SET booking_status = 'booked' WHERE link_id = ?";
                update($updateQuery, [$linkId], 's'); // Using your update function
                echo "Booking order with link_id $linkId has been updated to 'booked' status.\n";
            } else {
                echo "Booking order with link_id $linkId has not been paid yet. Status: $paymongoStatus.\n";
            }
        } else {
            echo "Error: Could not retrieve payment status for link_id $linkId.\n";
        }
    }
}

function getPaymongoPaymentData($linkId) {
    $paymongoApiKey = 'sk_test_72MbdmpCmG9hYTN74LvC6bBJ';  
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

updateBookingStatusBulk();
?>