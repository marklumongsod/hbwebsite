<?php

require('admin/inc/essentials.php');
require('admin/inc/db_config.php');
require('admin/inc/mpdf/vendor/autoload.php');

session_start();

if (!(isset($_SESSION['login']) && $_SESSION['login'] == true)) {
  redirect('index.php');
}

if (isset($_GET['gen_pdf']) && isset($_GET['id'])) {
  $frm_data = filteration($_GET);

  $query = "SELECT bo.*, bd.*,uc.email FROM `booking_order` bo
      INNER JOIN `booking_details` bd ON bo.booking_id = bd.booking_id
      INNER JOIN `user_cred` uc ON bo.user_id = uc.id
      WHERE ((bo.booking_status='booked' AND bo.arrival=1) 
      OR (bo.booking_status='cancelled' AND bo.refund=1)
      OR (bo.booking_status='payment failed')) 
      AND bo.booking_id = '$frm_data[id]'";

  $res = mysqli_query($con, $query);
  $total_rows = mysqli_num_rows($res);

  if ($total_rows == 0) {
    header('location: index.php');
    exit;
  }

  $data = mysqli_fetch_assoc($res);

  $date = date("h:ia | d-m-Y", strtotime($data['datentime']));
  $checkin = date("d-m-Y", strtotime($data['check_in']));
  $checkout = date("d-m-Y", strtotime($data['check_out']));

  $table_data = "
   <style>
        body { font-family: Arial, sans-serif; line-height: 1.6; }
        h2 { text-align: center; color: #333; margin-bottom: 20px; }
        .table { width: 100%; border-collapse: collapse; margin: 0 auto; }
        .table th, .table td { border: 1px solid #ddd; padding: 8px; text-align: left; }
        .table th { background-color: #f4f4f4; font-weight: bold; }
        .header, .footer { text-align: center; margin-top: 20px; }
        .footer { font-size: 0.9em; color: #666; }
    </style>
    
    <div class='header'>
      <h2>Booking Receipt</h2>
      <p>Thank you for your booking!</p>
    </div>

      <table class='table'>
      
      <tr>
        <th colspan='2'>Customer Information</th>
      </tr>
      <tr>
        <td>Name</td>
        <td>$data[user_name]</td>
      </tr>
      <tr>
        <td>Email</td>
        <td>$data[email]</td>
      </tr>
      <tr>
        <td>Phone Number</td>
        <td>$data[phonenum]</td>
      </tr>
      <tr>
        <td>Address</td>
        <td>$data[address]</td>
      </tr>
      <tr>
        <th colspan='2'>Booking Details</th>
      </tr>
      <tr>
        <td>Order ID</td>
        <td>$data[order_id]</td>
      </tr>
      <tr>
        <td>Booking Date</td>
        <td>$date</td>
      </tr>
      <tr>
        <td>Status</td>
        <td>$data[booking_status]</td>
      </tr>
      <tr>
        <th colspan='2'>Payment Details</th>
      </tr>
      <tr>
        <td>Room Name</td>
        <td>$data[room_name]</td>
      </tr>
      <tr>
        <td>Room Number</td>
        <td>$data[room_no]</td>
      </tr>
      <tr>
        <td>Cost per Night</td>
        <td>₱$data[price]</td>
      </tr>
      <tr>
        <td>Check-in</td>
        <td>$checkin</td>
      </tr>
      <tr>
        <td>Check-out</td>
        <td>$checkout</td>
      </tr>
    ";

  if ($data['booking_status'] == 'cancelled') {
    $refund = ($data['refund']) ? "Amount Refunded" : "Not Yet Refunded";
    $table_data .= "<tr>
          <td>Amount Paid</td>
          <td>₱$data[trans_amt]</td>
        </tr>
        <tr>
          <td>Refund Status</td>
          <td>$refund</td>
        </tr>";
  } elseif ($data['booking_status'] == 'payment failed') {
    $table_data .= "<tr>
          <td>Transaction Amount</td>
          <td>₱$data[trans_amt]</td>
        </tr>
        <tr>
          <td>Failure Response</td>
          <td>$data[trans_resp_msg]</td>
        </tr>";
  } else {
    $table_data .= "<tr>
          <td>Amount Paid</td>
          <td>₱$data[trans_amt]</td>
        </tr>";
  }

  $table_data .= "</table>
   <div class='footer'>
        <p>We look forward to hosting you. Have a great stay!</p>
    </div>";

  $mpdf = new \Mpdf\Mpdf();
  $mpdf->WriteHTML($table_data);
  $mpdf->Output($data['order_id'] . '.pdf', 'D');
} else {
  header('location: index.php');
}
