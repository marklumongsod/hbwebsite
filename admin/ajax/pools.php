<?php
require('../inc/db_config.php');
require('../inc/essentials.php');
adminLogin();

if (isset($_POST['add_pool'])) {
    $frm_data = filteration($_POST);
    $flag = 0;

    $q1 = "INSERT INTO `pools` (`name`, `description`, `price`) VALUES (?,?,?)";
    $values = [
        $frm_data['name'],
        $frm_data['description'],
        $frm_data['price'],
    ];

    if (insert($q1, $values, 'ssi')) {
        $flag = 1;
    } else {
        $flag = 0;
    }

    if ($flag) {
        echo 1;
    } else {
        echo 0;
    }
}


if (isset($_POST['get_all_pools'])) {
    $res = select("SELECT * FROM `pools` WHERE `removed`=?", [0], 'i');
    $i = 1;

    $data = "";

    while ($row = mysqli_fetch_assoc($res)) {
        if ($row['status'] == 1) {
            $status = "<button onclick='toggleStatus($row[id],0)' class='btn btn-dark btn-sm shadow-none'>active</button>";
        } else {
            $status = "<button onclick='toggleStatus($row[id],1)' class='btn btn-warning btn-sm shadow-none'>inactive</button>";
        }


        $data .= "
          <tr class='align-middle'>
            <td>$i</td>
            <td>$row[name]</td>
            <td>$row[description]</td>
            <td>$row[price]</td>
            <td>$status</td>
            <td>
              <button type='button' onclick='edit_details($row[id])' class='btn btn-primary shadow-none btn-sm' data-bs-toggle='modal' data-bs-target='#edit-pool'>
                <i class='bi bi-pencil-square'></i> 
              </button>
              <button type='button' onclick='remove_pool($row[id])' class='btn btn-danger shadow-none btn-sm'>
                <i class='bi bi-trash'></i> 
              </button>
            </td>
          </tr>
        ";
        $i++;
    }

    echo $data;
}


if (isset($_POST['get_pool'])) {
    $frm_data = filteration($_POST);

    // Fetch data from the pools table only
    $res1 = select("SELECT * FROM `pools` WHERE `id`=?", [$frm_data['get_pool']], 'i');

    if ($res1) {
        $pooldata = mysqli_fetch_assoc($res1);
        
        if ($pooldata) {
            $data = ["pooldata" => $pooldata];
        } else {
            $data = ["error" => "Pool not found."];
        }
    } else {
        $data = ["error" => "Database query failed."];
    }

    echo json_encode($data);
}




if (isset($_POST['edit_pool'])) {
    $frm_data = filteration($_POST);
    $flag = 0;

    // Corrected SQL query
    $q1 = "UPDATE `pools` SET `name`=?, `description`=?, `price`=? WHERE `id`=?";
    $values = [
        $frm_data['name'],
        $frm_data['description'],
        $frm_data['price'],
        $frm_data['pool_id']
    ];

    // Check if update was successful
    if (update($q1, $values, 'ssii')) {
        $flag = 1;
    } else {
        $flag = 0;
    }

    echo $flag ? 1 : 0; // Directly echoing the flag value
}


if (isset($_POST['toggle_status'])) {
    $frm_data = filteration($_POST);

    $q = "UPDATE `pools` SET `status`=? WHERE `id`=?";
    $v = [$frm_data['value'], $frm_data['toggle_status']];

    if (update($q, $v, 'ii')) {
        echo 1;
    } else {
        echo 0;
    }
}

if (isset($_POST['remove_pool'])) {
    $frm_data = filteration($_POST);

    $res5 = update("UPDATE `pools` SET `removed`=? WHERE `id`=?", [1, $frm_data['pool_id']], 'ii');

    if ($res5) {
        echo 1;
    } else {
        echo 0;
    }
}
