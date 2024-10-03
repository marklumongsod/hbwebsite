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

$booking_exists = false;

if (isset($_POST['pay_now'])) {
    $frm_data = filteration($_POST);

    $check_booking_query = "SELECT * FROM `booking_details` 
    WHERE `check_in_date` = ? 
    AND `check_in_time` = ? 
    AND `room_name` = ? 
    AND `room_no` = ?";
    $check_booking_result = select($check_booking_query, [
        $frm_data['checkin_date'],
        $frm_data['checkin_time'],
        $_SESSION['room']['name'],
        $_SESSION['room']['room_no']
    ], 'ssss');

    if (mysqli_num_rows($check_booking_result) > 0) {
        $booking_exists = true;
    } else {
        $ORDER_ID = 'ORD_' . $_SESSION['uId'] . random_int(11111, 9999999);
        $CUST_ID = $_SESSION['uId'];
        $price_rate = $frm_data['price_rate'];
        $TXN_AMOUNT = $price_rate * 100;

        // Rest of your existing code for processing the booking
        // ...

        if (!isset($_SESSION['room']) || empty($_SESSION['room']['id'])) {
            echo "Error: Room data is not available.";
            return;
        }

        $room_id = $_SESSION['room']['id'];

        $room_res = select("SELECT * FROM `rooms` WHERE `id` = ? LIMIT 1", [$room_id], "i");

        if ($room_res && mysqli_num_rows($room_res) > 0) {
            $room_data = mysqli_fetch_assoc($room_res);
        } else {
            echo "Error: Room data not found.";
            return;
        }

        if (empty($room_data)) {
            echo "Error: No room data found for the specified ID.";
            return;
        }

        $price = 0;
        $duration = 0;

        switch ($price_rate) {
            case $room_data['rate_3hrs']:
                $price = $room_data['rate_3hrs'];
                $duration = 3;
                break;
            case $room_data['rate_6hrs']:
                $price = $room_data['rate_6hrs'];
                $duration = 6;
                break;
            case $room_data['rate_12hrs']:
                $price = $room_data['rate_12hrs'];
                $duration = 12;
                break;
            default:
                echo "Error: Invalid price rate selected.";
                return;
        }

        $query1 = "INSERT INTO `booking_order`(`user_id`, `room_id`, `check_in`, `check_in_time`, `order_id`) VALUES (?, ?, ?, ?, ?)";
        insert($query1, [$CUST_ID, $_SESSION['room']['id'], $frm_data['checkin_date'], $frm_data['checkin_time'], $ORDER_ID], 'issss');

        $booking_id = mysqli_insert_id($con);

        $query2 = "INSERT INTO `booking_details`(`booking_id`, `room_name`, `price`, `duration`, `check_in_date`, `check_in_time`, `room_no`, `user_name`, `phonenum`, `address`) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
        insert($query2, [
            $booking_id,
            $_SESSION['room']['name'],
            $price_rate,
            $duration,
            $frm_data['checkin_date'],
            $frm_data['checkin_time'],
            $_SESSION['room']['room_no'],
            $frm_data['name'],
            $frm_data['phonenum'],
            $frm_data['address']
        ], 'isiissssss');

        $checkoutUrl = createPaymentLink($TXN_AMOUNT, $ORDER_ID);

        if ($checkoutUrl && isset($checkoutUrl['data'])) {
            $_SESSION['payment_link_id'] = $checkoutUrl['data']['id'] ?? null;

            $reference_number = $_POST['reference_number'] ?? null;
            $referenceNumber = $checkoutUrl['data']['attributes']['reference_number'] ?? null;

            $linkId = $checkoutUrl['data']['id'] ?? null;

            $amount = $checkoutUrl['data']['attributes']['amount'] ?? null;
            if ($amount !== null) {
                $formattedAmount = number_format($amount / 100, 2, '.', '');
            } else {
                $formattedAmount = null;
            }

            if (isset($ORDER_ID)) {
                $updateQuery = "UPDATE `booking_order` SET `reference_number` = ?, `link_id` = ?, `trans_amt` = ? WHERE `order_id` = ?";
                insert($updateQuery, [$referenceNumber, $linkId, $formattedAmount, $ORDER_ID], 'ssss');
            } else {
                echo "Error: ORDER_ID is not defined.";
            }
        } else {
            echo "Error: Payment link creation failed or response is invalid.";
        }
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
                'description' => 'Payment for Order #' . $orderId,
                'remarks' => 'Payment for online purchase',
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
                'authorization' => 'Basic ' . base64_encode('sk_test_72MbdmpCmG9hYTN74LvC6bBJ'),
                'content-type' => 'application/json',
            ],
        ]);

        $responseData = json_decode($response->getBody(), true);

        // echo '<pre>';
        // echo htmlspecialchars(print_r($responseData, true)); 
        // echo '</pre>';

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
    <?php if ($booking_exists): ?>
        <h1>Booking Already Exists</h1>
        <p>A booking for this date and time already exists. Please choose a different date or time.</p>
        <a href="index.php" class="button button-back">Go Back</a>
    <?php else: ?>
        <h1>Your booking is being processed...</h1>
        <p>Thank you for your patience! We're currently finalizing your reservation details.</p>

        <?php if (isset($checkoutUrl) && $checkoutUrl): ?>
            <p>To complete your booking, please click the button below to proceed to payment:</p>
            <a href="<?php echo htmlspecialchars($checkoutUrl['data']['attributes']['checkout_url']); ?>" class="button" target="_blank">Proceed to Payment</a>

            <a href="index.php" class="button button-back">Go Back</a>
        <?php else: ?>
            <p class="message">Oops! We couldn't generate a payment link. Please try again later.</p>
        <?php endif; ?>
    <?php endif; ?>
</body>

</html>