<?php

require('inc/essentials.php');
require('inc/db_config.php');
require('inc/mpdf/vendor/autoload.php');

adminLogin();

if (isset($_GET['gen_pdf']) && isset($_GET['id'])) {
  $frm_data = filteration($_GET);

  $data = getBookingData($frm_data['id']);

  if (!$data) {
    redirectToDashboard();
  }

  $table_data = generateTableData($data);

  createPDF($data['order_id'], $table_data);
} else {
  redirectToDashboard();
}

function getBookingData($bookingId)
{
  global $con;

  $query = "SELECT bo.*, bd.*, uc.email 
              FROM `booking_order` bo
              INNER JOIN `booking_details` bd ON bo.booking_id = bd.booking_id
              INNER JOIN `user_cred` uc ON bo.user_id = uc.id
              WHERE ((bo.booking_status='booked' AND bo.arrival=1) 
              OR (bo.booking_status='cancelled' AND bo.refund=1)
              OR (bo.booking_status='payment failed')) 
              AND bo.booking_id = '$bookingId'";

  $res = mysqli_query($con, $query);
  return mysqli_num_rows($res) > 0 ? mysqli_fetch_assoc($res) : null;
}

function redirectToDashboard()
{
  header('location: dashboard.php');
  exit;
}

function generateTableData($data)
{
  date_default_timezone_set('Asia/Manila');
  $date_downloaded = date("d-m-Y h:i A");
  $date = date("h:ia | d-m-Y", strtotime($data['datentime']));
  $checkin = date("d-m-Y", strtotime($data['check_in']));
  $checkout = date("d-m-Y", strtotime($data['check_out']));

  $table_data = "
    <style>
        body { font-family: 'Arial'; }
        h2 { text-align: center; color: #333; }
        table { width: 100%; border-collapse: collapse; margin-bottom: 20px; }
        table, th, td { border: 1px solid #ddd; }
        th, td { padding: 10px; text-align: left; }
        th { background-color: #f2f2f2; }
        td { vertical-align: middle; }
        .header { background-color: #4CAF50; color: white; text-align: center; }
        .status { color: red; font-weight: bold; }
        .resort-header { display: flex; }
        .logo { width: 40px; }
        .resort-name-container { display: flex; flex-direction: column; margin-left: 20px; }
        .resort-name { font-size: 24px; font-weight: bold; text-align: left; }
        .download-date { font-size: 12px; color: #555; text-align: right; }
        .resort-header-right { display: flex; flex-direction: column; text-align: right; }
    </style>

    <div class='resort-header'>
    <img src='../logo.jpg' alt='Villa Ocampo Resort Logo' class='logo'>
        <h3>Villa Ocampo Resort</h3>
    </div>

    <h2>BOOKING RECEIPT</h2>

    <table>
        <tr class='header'>
            <th colspan='2'>Booking Details</th>
        </tr>
        <tr>
            <td><b>Order ID:</b> {$data['order_id']}</td>
            <td><b>Booking Date:</b> $date</td>
        </tr>
        <tr>
            <td colspan='2' class='status'><b>Status:</b> {$data['booking_status']}</td>
        </tr>
        <tr class='header'>
            <th colspan='2'>User Information</th>
        </tr>
        <tr>
            <td><b>Name:</b> {$data['user_name']}</td>
            <td><b>Email:</b> {$data['email']}</td>
        </tr>
        <tr>
            <td><b>Phone Number:</b> {$data['phonenum']}</td>
            <td><b>Address:</b> {$data['address']}</td>
        </tr>
        <tr class='header'>
            <th colspan='2'>Room Information</th>
        </tr>
        <tr>
            <td><b>Room Name:</b> {$data['room_name']}</td>
            <td><b>Cost:</b> ₱{$data['price']} per night</td>
        </tr>
        <tr>
            <td><b>Check-in:</b> $checkin</td>
            <td><b>Check-out:</b> $checkout</td>
        </tr>";

  $table_data .= generateAdditionalDetails($data);
  $table_data .= "</table>    
    <div class='download-date'>
        <b>Date Downloaded:</b> $date_downloaded
    </div>";

  return $table_data;
}

function generateAdditionalDetails($data)
{
  if ($data['booking_status'] == 'cancelled') {
    $refund = ($data['refund']) ? "Amount Refunded" : "Not Yet Refunded";
    return "
        <tr>
            <td><b>Amount Paid:</b> ₱{$data['trans_amt']}</td>
            <td><b>Refund Status:</b> $refund</td>
        </tr>";
  } elseif ($data['booking_status'] == 'payment failed') {
    return "
        <tr>
            <td><b>Transaction Amount:</b> ₱{$data['trans_amt']}</td>
            <td><b>Failure Reason:</b> {$data['trans_resp_msg']}</td>
        </tr>";
  } else {
    return "
        <tr>
            <td><b>Room Number:</b> {$data['room_no']}</td>
            <td><b>Amount Paid:</b> ₱{$data['trans_amt']}</td>
        </tr>";
  }
}

function createPDF($orderId, $table_data)
{
  $mpdf = new \Mpdf\Mpdf();
  $mpdf->WriteHTML($table_data);
  $mpdf->Output("$orderId.pdf", 'D');
}
