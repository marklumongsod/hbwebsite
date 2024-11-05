<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <meta http-equiv="X-UA-Compatible" content="IE=edge">
  <title>Email Verification</title>
  <!-- Bootstrap CSS -->
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
  <style>
    .container {
      margin-top: 100px;
    }

    .card {
      padding: 20px;
      border-radius: 8px;
    }

    .btn-custom {
      background-color: #28a745;
      color: white;
      border-radius: 5px;
    }

    .btn-custom:hover {
      background-color: #218838;
      color: white;
    }

    .message {
      font-size: 18px;
      font-weight: bold;
      margin-bottom: 20px;
    }
  </style>
</head>

<body>
  <div class="container d-flex justify-content-center">
    <div class="col-md-6">
      <div class="card text-center shadow">
        <div class="card-body">
          <h3 class="card-title">Email Verification</h3>

          <!-- PHP Message Display (Success or Error) -->
          <div id="verificationMessage" class="message">
            <?php
            require('../admin/inc/db_config.php');

            if (isset($_GET['token'])) {
              $token = $_GET['token'];

              $query = "SELECT * FROM `user_cred` WHERE `token` = ? LIMIT 1";
              $result = select1($query, [$token], "s");

              if (mysqli_num_rows($result) > 0) {
                $updateQuery = "UPDATE `user_cred` SET `is_verified` = 1, `token` = NULL WHERE `token` = ?";
                update($updateQuery, [$token], "s");

                echo "Your email has been verified successfully.";
              } else {
                echo "Invalid or expired token.";
              }
            } else {
              echo "No token provided.";
            }
            ?>
          </div>

          <a href="../index.php" class="btn btn-custom">Proceed to Login</a>
        </div>
      </div>
    </div>
  </div>

  <!-- Bootstrap JS and Dependencies -->
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>
