<?php

require('../admin/inc/db_config.php');
require('../admin/inc/essentials.php');
require('../phpmailer/src/PHPMailer.php');
require('../phpmailer/src/SMTP.php');
require('../phpmailer/src/Exception.php');

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

date_default_timezone_set("Asia/Manila");

function send_mail($uemail, $type, $token)
{
  $mail = new PHPMailer(true);

  try {
    $mail->isSMTP();
    $mail->Host = 'smtp.gmail.com';
    $mail->SMTPAuth = true;
    $mail->Username = 'resort.villaocampo@gmail.com';
    $mail->Password = 'acjkbsyaldymquuo';
    $mail->SMTPSecure = 'tls';
    $mail->Port = 587;

    $mail->setFrom('resort.villaocampo@gmail.com', 'Villa Ocampo Resort');
    $mail->addAddress($uemail);

    if ($type == "email_confirmation") {
      $subject = "Account Verification";
      $verificationLink = "http://localhost/hbwebsite/ajax/verify.php?token=" . $token;  
      $content = "
                <h1>Thank you for registering with us!</h1>
                <p>Please verify your email address to complete your registration.</p>
                <a href='" . $verificationLink . "' style='
                    display: inline-block; 
                    padding: 10px 20px; 
                    margin: 20px 0; 
                    background-color: #28a745; 
                    color: white; 
                    text-decoration: none; 
                    border-radius: 5px;' >
                    Verify Email
                </a>
                <p>If the button above does not work, please copy and paste the following URL into your browser:</p>
                <p><a href='" . $verificationLink . "'>" . $verificationLink . "</a></p>
            ";
    } else {
      // Handle other email types
    }

    $mail->isHTML(true);
    $mail->Subject = $subject;
    $mail->Body = $content;

    $mail->send();
    return 1;
  } catch (Exception $e) {
    error_log("Mailer Error: {$mail->ErrorInfo}");
    return 0;
  }
}



if (isset($_POST['register'])) {
  $data = filteration($_POST);

  if ($data['pass'] != $data['cpass']) {
    echo 'pass_mismatch';
    exit;
  }

  $u_exist = select(
    "SELECT * FROM `user_cred` WHERE `email` = ? OR `phonenum` = ? LIMIT 1",
    [$data['email'], $data['phonenum']],
    "ss"
  );

  if (mysqli_num_rows($u_exist) != 0) {
    $u_exist_fetch = mysqli_fetch_assoc($u_exist);
    echo ($u_exist_fetch['email'] == $data['email']) ? 'email_already' : 'phone_already';
    exit;
  }

  $img = uploadUserImage($_FILES['profile']);
  if ($img == 'inv_img') {
    echo 'inv_img';
    exit;
  } else if ($img == 'upd_failed') {
    echo 'upd_failed';
    exit;
  }

  $enc_pass = password_hash($data['pass'], PASSWORD_BCRYPT);

  $token = bin2hex(random_bytes(16));  // Generate a random token

  $query = "INSERT INTO `user_cred`(`name`, `email`, `address`, `phonenum`, `pincode`, `dob`, `profile`, `password`, `token`, `is_verified`) 
            VALUES (?,?,?,?,?,?,?,?,?, 0)";
  $values = [$data['name'], $data['email'], $data['address'], $data['phonenum'], $data['pincode'], $data['dob'], $img, $enc_pass, $token];
  
  if (insert($query, $values, 'sssssssss')) {
      if (send_mail($data['email'], "email_confirmation", $token)) {  // Pass the token to the email function
          echo 1;
      } else {
          error_log("Failed to send confirmation email to " . $data['email']);
          echo 1;
      }
  } else {
      echo 'ins_failed';
      error_log("Database insertion failed: " . mysqli_error($conn));
  }
}

if (isset($_POST['login'])) {
  $data = filteration($_POST);

  $u_exist = select(
    "SELECT * FROM `user_cred` WHERE `email`=? OR `phonenum`=? LIMIT 1",
    [$data['email_mob'], $data['email_mob']],
    "ss"
  );

  if (mysqli_num_rows($u_exist) == 0) {
    echo 'inv_email_mob';
  } else {
    $u_fetch = mysqli_fetch_assoc($u_exist);
    if ($u_fetch['is_verified'] == 0) {
      echo 'not_verified';
    } else if ($u_fetch['status'] == 0) {
      echo 'inactive';
    } else {
      if (!password_verify($data['pass'], $u_fetch['password'])) {
        echo 'invalid_pass';
      } else {
        session_start();
        $_SESSION['login'] = true;
        $_SESSION['uId'] = $u_fetch['id'];
        $_SESSION['uName'] = $u_fetch['name'];
        $_SESSION['uPic'] = $u_fetch['profile'];
        $_SESSION['uPhone'] = $u_fetch['phonenum'];
        echo 1;
      }
    }
  }
}

if (isset($_POST['forgot_pass'])) {
  $data = filteration($_POST);

  $u_exist = select("SELECT * FROM `user_cred` WHERE `email`=? LIMIT 1", [$data['email']], "s");

  if (mysqli_num_rows($u_exist) == 0) {
    echo 'inv_email';
  } else {
    $u_fetch = mysqli_fetch_assoc($u_exist);
    if ($u_fetch['is_verified'] == 0) {
      echo 'not_verified';
    } else if ($u_fetch['status'] == 0) {
      echo 'inactive';
    } else {
      // send reset link to email
      $token = bin2hex(random_bytes(16));

      if (!send_mail($data['email'], $token, 'account_recovery')) {
        echo 'mail_failed';
      } else {
        $date = date("Y-m-d");

        $query = mysqli_query($con, "UPDATE `user_cred` SET `token`='$token', `t_expire`='$date' 
            WHERE `id`='$u_fetch[id]'");

        if ($query) {
          echo 1;
        } else {
          echo 'upd_failed';
        }
      }
    }
  }
}

if (isset($_POST['recover_user'])) {
  $data = filteration($_POST);

  $enc_pass = password_hash($data['pass'], PASSWORD_BCRYPT);

  $query = "UPDATE `user_cred` SET `password`=?, `token`=?, `t_expire`=? 
      WHERE `email`=? AND `token`=?";

  $values = [$enc_pass, null, null, $data['email'], $data['token']];

  if (update($query, $values, 'sssss')) {
    echo 1;
  } else {
    echo 'failed';
  }
}
