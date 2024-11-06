<?php
require('admin/inc/db_config.php');
require('admin/inc/essentials.php');
require 'vendor/autoload.php';

use GuzzleHttp\Client;

date_default_timezone_set("Asia/Kolkata");

session_start();

if (!(isset($_SESSION['login']) && $_SESSION['login'] == true)) {
    redirect('index.php');
}

if (isset($_POST['pay_now'])) {
    $frm_data = filteration($_POST);

    $ORDER_ID = 'ORD_' . $_SESSION['uId'] . random_int(11111, 9999999);
    $CUST_ID = $_SESSION['uId'];
    $price_rate = $frm_data['price'];
    $TXN_AMOUNT = $price_rate * 100; 

    if (!isset($_SESSION['pool']) || empty($_SESSION['pool']['id'])) {
        echo "Error: Pool data is not available.";
        return;
    }

    $pool_id = $_SESSION['pool']['id'];

    $query1 = "INSERT INTO `booking_order_pool`(`user_id`, `pool_id`, `check_in`, `check_in_time`, `order_id`, `booking_status`, `arrival`, `datentime`) 
                VALUES (?, ?, ?, ?, ?, ?, ?, NOW())";
    insert($query1, [
        $CUST_ID, 
        $pool_id, 
        $frm_data['checkin_date'], 
        $frm_data['checkin_time'], 
        $ORDER_ID,
        'pending', 
        0 
    ], 'issssii');

    $booking_id = mysqli_insert_id($con);

    // Insert into booking_details_pool table
    $query2 = "INSERT INTO `booking_details_pool`(`booking_id`, `pool_name`, `price`, `quantity`, `check_in_date`, `check_in_time`, `user_name`, `phonenum`, `address`) 
    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)";
insert($query2, [
$booking_id,
$_SESSION['pool']['name'],
$price_rate,
$frm_data['quantity'],
$frm_data['checkin_date'],
$frm_data['checkin_time'],
$frm_data['name'],
$frm_data['phonenum'],
$frm_data['address']
], 'isiisssss');  

    // Create payment link
    $checkoutUrl = createPaymentLink($TXN_AMOUNT, $ORDER_ID);

    if ($checkoutUrl && isset($checkoutUrl['data'])) {
        $_SESSION['payment_link_id'] = $checkoutUrl['data']['id'] ?? null;

        $reference_number = $checkoutUrl['data']['attributes']['reference_number'] ?? null;
        $linkId = $checkoutUrl['data']['id'] ?? null;
        $amount = $checkoutUrl['data']['attributes']['amount'] ?? null;
        $formattedAmount = $amount !== null ? number_format($amount / 100, 2, '.', '') : null;

        if (isset($ORDER_ID)) {
            $updateQuery = "UPDATE `booking_order_pool` SET `reference_number` = ?, `link_id` = ?, `trans_amt` = ? WHERE `order_id` = ?";
            insert($updateQuery, [$reference_number, $linkId, $formattedAmount, $ORDER_ID], 'ssss');
        } else {
            echo "Error: ORDER_ID is not defined.";
        }
    } else {
        echo "Error: Payment link creation failed or response is invalid.";
    }
}

function createPaymentLink($amount, $orderId)
{
    $client = new Client();
    $url = 'https://api.paymongo.com/v1/links';

    $payload = json_encode([
        'data' => [
            'attributes' => [
                'amount' => $amount,
                'description' => 'Payment for Pool Order #' . $orderId,
                'remarks' => 'Payment for online pool booking',
                'payment_method_allowed' => ['gcash', 'card', 'grabpay', 'paymaya'],
                'payment_method_types' => ['gcash', 'card', 'grabpay', 'paymaya'],
            ],
        ],
    ]);

    try {
        $response = $client->request('POST', $url, [
            'body' => $payload,
            'headers' => [
                'accept' => 'application/json',
                'authorization' => 'Basic ' . base64_encode('sk_test_Kn8SLEjoKT6QSBWfJYAmxJ4P'),
                'content-type' => 'application/json',
            ],
        ]);

        $responseData = json_decode($response->getBody(), true);

        if (isset($responseData['data'])) {
            return $responseData;
        } else {
            echo 'Failed to create payment link.<br>';
            return null;
        }
    } catch (Exception $e) {
        echo 'An error occurred: ' . htmlspecialchars($e->getMessage());
        return null;
    }
}

if (isset($_GET['payment_status']) && $_GET['payment_status'] === 'success') {
    $_SESSION['payment_success'] = true;
    unset($_SESSION['payment_link_id']);
    header('Location: http://localhost/hbwebsite/payment_success.php');
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Processing</title>
    <style>
        body {
            font-family: 'Arial', sans-serif;
            background-color: #f4f4f4;
            color: #333;
            text-align: center;
            margin: 0;
            padding: 50px;
        }

        h1 {
            color: #28a745;
            font-size: 2.5em;
            margin-bottom: 20px;
        }

        p {
            font-size: 1.2em;
            margin-bottom: 30px;
        }

        .button {
            display: inline-block;
            padding: 15px 30px;
            font-size: 1.2em;
            color: #fff;
            background-color: #007bff;
            border: none;
            border-radius: 5px;
            text-decoration: none;
            transition: background-color 0.3s, transform 0.3s;
            margin: 10px;
        }

        .button:hover {
            background-color: #0056b3;
            transform: translateY(-2px);
        }

        .button-back {
            background-color: #6c757d;
        }

        .button-back:hover {
            background-color: #5a6268;
        }

        .message {
            font-size: 1.1em;
            margin-top: 20px;
            color: #d9534f;
        }

        @media (max-width: 600px) {
            h1 {
                font-size: 2em;
            }

            .button,
            .button-back {
                padding: 12px 25px;
                font-size: 1em;
            }

            p {
                font-size: 1em;
            }
        }
    </style>
</head>

<body>
    <h1>Your booking is being processed...</h1>
    <p>Thank you for your patience! We're currently finalizing your reservation details.</p>

    <?php if (isset($checkoutUrl) && $checkoutUrl): ?>
        <p>To complete your booking, please click the button below to proceed to payment:</p>
        <a href="<?php echo htmlspecialchars($checkoutUrl['data']['attributes']['checkout_url']); ?>" class="button" target="_blank">Proceed to Payment</a>

        <a href="index.php" class="button button-back">Go Back</a>
    <?php else: ?>
        <p class="message">There was an error processing your payment link. Please try again later.</p>
        <a href="index.php" class="button button-back">Go Back</a>
    <?php endif; ?>
</body>

</html>
