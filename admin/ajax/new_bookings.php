<?php

require('../inc/db_config.php');
require('../inc/essentials.php');
adminLogin();

if (isset($_POST['get_bookings'])) {
  $frm_data = filteration($_POST);

  $query = "SELECT bo.*, bd.* FROM `booking_order` bo
      INNER JOIN `booking_details` bd ON bo.booking_id = bd.booking_id
      WHERE (bo.order_id LIKE ? OR bd.phonenum LIKE ? OR bd.user_name LIKE ?) 
      AND (bo.booking_status=? AND bo.arrival=?) ORDER BY bo.booking_id ASC";

  $res = select($query, ["%$frm_data[search]%", "%$frm_data[search]%", "%$frm_data[search]%", "booked", 0], 'sssss');

  $i = 1;
  $table_data = "";

  if (mysqli_num_rows($res) == 0) {
    echo "<b>No Data Found!</b>";
    exit;
  }

  while ($data = mysqli_fetch_assoc($res)) {
    $date = date("d-m-Y", strtotime($data['datentime']));
    $checkin = date("d-m-Y", strtotime($data['check_in_date']));
    $checkin_time = $data['check_in_time']; // Assume this is in 24-hour format, e.g., "14:30:00"
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
            <b>Room:</b> $data[room_name]
            <br>
            <b>Room No:</b> $data[room_no]
            <br>
            <b>Price:</b> ₱$data[price]
          </td>
          <td>
            <b>Check-in-date:</b> $checkin
            <br>
            <b>Check-in-time:</b> $checkin_time_12hr
            <br>
            <b>Paid:</b> ₱$data[trans_amt]
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

  $query = "UPDATE `booking_order` bo INNER JOIN `booking_details` bd
      ON bo.booking_id = bd.booking_id
      SET bo.arrival = ?, bo.rate_review = ? 
      WHERE bo.booking_id = ?";

  $values = [1, 0, $frm_data['booking_id']];

  $res = update($query, $values, 'iii'); // it will update 2 rows so it will return 2

  echo ($res == 2) ? 1 : 0;
}

if (isset($_POST['cancel_booking'])) {
  $frm_data = filteration($_POST);

  $query = "UPDATE `booking_order` SET `booking_status`=?, `refund`=? WHERE `booking_id`=?";
  $values = ['cancelled', 0, $frm_data['booking_id']];
  $res = update($query, $values, 'sii');

  if ($res) {
    $fetch_room_query = "SELECT `room_id` FROM `booking_order` WHERE `booking_id`=?";
    $room_data = select($fetch_room_query, [$frm_data['booking_id']], 'i');

    if ($room_data) {
      $room_id = $room_data[0]['room_id'];

      $update_room_query = "UPDATE `room` SET `isAvailable`=? WHERE `id`=?";
      $room_update_res = update($update_room_query, [1, $room_id], 'ii');

      if ($room_update_res) {
        echo "Room availability updated successfully.";
      } else {
        echo "Failed to update room availability.";
      }
    } else {
      echo "Room not found for the given booking.";
    }
  } else {
    echo "Failed to cancel the booking.";
  }
}
