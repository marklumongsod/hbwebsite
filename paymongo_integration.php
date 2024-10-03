<?php

require 'vendor/autoload.php'; // Ensure to include Guzzle's autoloader

use GuzzleHttp\Client;

function createPaymentLink() {
    $client = new Client();

    // The API endpoint for creating payment links
    $url = 'https://api.paymongo.com/v1/links';

    // Define the payload (body) for the payment link
    $payload = json_encode([
        'data' => [
            'attributes' => [
                'amount' => 100000, // Amount in cents (200.00 PHP)
                'description' => 'Payment for Order #1234',
                'remarks' => 'Payment for online purchase',
                'payment_method_allowed' => ['gcash', 'card', 'grabpay', 'paymaya'],
                'payment_method_types' => ['gcash', 'card', 'grabpay', 'paymaya'],
                'redirect' => [
                    'success' => 'http://localhost/hbwebsite/payment_success.php', // URL for success
                    'failure' => 'http://www.google.com', // URL for failure
                ],
            ],
        ],
    ]);

    try {
        // Make the POST request to create the payment link
        $response = $client->request('POST', $url, [
            'body' => $payload,
            'headers' => [
                'accept' => 'application/json',
                'authorization' => 'Basic c2tfdGVzdF83Mk1iZG1wQ21HOWhZVE43NEx2QzZiQko6', // Replace with your actual API key
                'content-type' => 'application/json',
            ],
        ]);

        // Get the response body and decode it
        $responseData = json_decode($response->getBody(), true);

        // Debug the full response to see all available fields
        echo '<pre>';
        print_r($responseData); // Print the full response for debugging
        echo '</pre>';

        // Check for success and display the result
        if (isset($responseData['data'])) {
            echo 'Payment Link created successfully!<br>';
            echo 'Payment Link ID: ' . $responseData['data']['id'] . '<br>';
            
            // Check if 'checkout_url' is available in the response
            if (isset($responseData['data']['attributes']['checkout_url'])) {
                echo 'Payment Link URL: ' . $responseData['data']['attributes']['checkout_url'] . '<br>';
            } else {
                echo 'Payment Link URL not found in the response.<br>';
            }
        } else {
            echo 'Failed to create payment link.<br>';
            print_r($responseData); // Print the response for debugging
        }

    } catch (Exception $e) {
        echo 'An error occurred: ' . $e->getMessage();
    }
}

// Call the function to create the payment link
createPaymentLink();
