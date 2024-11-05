<?php
session_start();

if (!isset($_SESSION['payment_success']) || $_SESSION['payment_success'] !== true) {
    // If there’s no successful payment session, redirect to the home page or booking page
    header('Location: index.php');
    exit();
}

// Unset the payment success session variable after displaying the message
unset($_SESSION['payment_success']);
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Payment Successful</title>
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
        }

        .button:hover {
            background-color: #0056b3;
            transform: translateY(-2px);
        }
    </style>
</head>

<body>
    <h1>Payment Successful!</h1>
    <p>Your payment has been processed successfully. Thank you for your reservation!</p>
    <a href="index.php" class="button">Go Back</a> <!-- Change to your desired page -->
</body>

</html>
