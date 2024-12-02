<?php

require('../inc/db_config.php');
require('../inc/essentials.php');
adminLogin();

if (isset($_POST['get_bookings'])) {
  $frm_data = filteration($_POST);

  $query = "SELECT bo.*, bd.* FROM `booking_order_pool` bo
      INNER JOIN `booking_details_pool` bd ON bo.booking_id = bd.booking_id
      WHERE (bo.order_id LIKE ? OR bd.phonenum LIKE ? OR bd.user_name LIKE ?) 
      AND (bo.booking_status=? AND bo.arrival=?) ORDER BY bo.booking_id ASC";

  $res = select($query, ["%$frm_data[search]%", "%$frm_data[search]%", "%$frm_data[search]%", "0", 0], 'sssss');

  $i = 1;
  $table_data = "";

  if (mysqli_num_rows($res) == 0) {
    echo "<b>No Data Found!</b>";
    exit;
  }

  while ($data = mysqli_fetch_assoc($res)) {
    $date = date("d-m-Y", strtotime($data['datentime']));
    $checkin = date("d-m-Y", strtotime($data['check_in_date']));
    $checkin_time = $data['check_in_time']; 
    $checkin_time_12hr = date("g:i A", strtotime($checkin_time));


    $table_data .= "
        <tr>
          <td>$i</td>
          <td>
            <span class='badge bg-primary'>
              Order ID: $data[order_id]
            </span>
            <br>
            <b>Name:</b> $data[user_name]
            <br>
            <b>Phone No:</b> $data[phonenum]
          </td>
          <td>
            <b>Pool Name:</b> $data[pool_name]
            <br>
            <b>Price:</b> ₱$data[price]
          </td>
          <td>
            <b>Amount:</b> ₱$data[trans_amt]
            <br>
            <b>Check-in-Date:</b> $data[check_in]<br>
              <b>Time:</b> ₱$data[check_in_time]
            <br>
          </td>
          <td>
            <button type='button' onclick='assign_room($data[booking_id])' class='btn text-white btn-sm fw-bold custom-bg shadow-none' data-bs-toggle='modal' data-bs-target='#assign-room'>
              <i class='bi bi-check2-square'></i> Confirm Arrival
            </button>
            <br>
            <button type='button' onclick='cancel_booking($data[booking_id])' class='mt-2 btn btn-outline-danger btn-sm fw-bold shadow-none'>
              <i class='bi bi-trash'></i> Cancel Booking
            </button>
          </td>
        </tr>
      ";

    $i++;
  }

  echo $table_data;
}

if (isset($_POST['assign_room'])) {
  $frm_data = filteration($_POST);

  $query = "UPDATE `booking_order_pool` bo INNER JOIN `booking_details_pool` bd
      ON bo.booking_id = bd.booking_id
      SET bo.arrival = ?, bo.rate_review = ? 
      WHERE bo.booking_id = ?";

  $values = [1, 0, $frm_data['booking_id']];

  $res = update($query, $values, 'iii'); // it will update 2 rows so it will return 2

  echo ($res == 2) ? 1 : 1;
}

if (isset($_POST['cancel_booking'])) {
  $frm_data = filteration($_POST);

  $query = "UPDATE `booking_order_pool` SET `booking_status`=?, `refund`=? WHERE `booking_id`=?";
  $values = ['cancelled', 0, $frm_data['booking_id']];
  $res = update($query, $values, 'sii');
  
}
