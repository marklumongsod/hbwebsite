<?php
require('inc/db_config.php'); 
require('inc/essentials.php');
adminLogin();

if (isset($_POST["submit"])) {
  $email = filter_var(trim($_POST["inputEmail"]), FILTER_SANITIZE_EMAIL);

  if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    $_SESSION['statuss'] = "Invalid email address.";
    $_SESSION['status_code'] = "error";
    header("Location: config_email.php"); 
    exit;
  }

  $check_query = "SELECT * FROM admin_email WHERE id = ?";
  $check_result = select1($check_query, [1], "i");

  if (mysqli_num_rows($check_result) > 0) {
    $update_query = "UPDATE admin_email SET email = ? WHERE id = ?";
    if (update($update_query, [$email, 1], "si")) {
      $_SESSION['statuss'] = "Email address updated successfully.";
      $_SESSION['status_code'] = "success";
    } else {
      $_SESSION['statuss'] = "Error updating email address.";
      $_SESSION['status_code'] = "error";
    }
  } else {
    $insert_query = "INSERT INTO admin_email (id, email) VALUES (?, ?)";
    if (insert($insert_query, [1, $email], "is")) {
      $_SESSION['statuss'] = "Email address stored successfully.";
      $_SESSION['status_code'] = "success";
    } else {
      $_SESSION['statuss'] = "Error storing email address.";
      $_SESSION['status_code'] = "error";
    }
  }

  header("Location: config_email.php"); 
  exit;
}

?>

<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8">
  <meta http-equiv="X-UA-Compatible" content="IE=edge">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Admin Panel - Email Configuration</title>
  <?php require('inc/links.php'); ?>
  <style>
    .form-control {
      font-size: 1rem;
      padding: 10px;
      max-width: 100%;
      margin: 0 auto;
    }

    .btn-primary {
      background-color: #007bff;
      border: none;
      transition: background-color 0.3s ease, box-shadow 0.3s ease;
    }

    .btn-primary:hover {
      background-color: #0056b3;
      box-shadow: 0px 4px 10px rgba(0, 0, 0, 0.1);
    }

    .card {
      max-width: 500px;
      margin: 20px auto;
    }

    @media (max-width: 768px) {
      .card {
        margin: 10px;
        padding: 10px;
      }

      .btn {
        width: 100%;
      }
    }
  </style>
  <script src="https://unpkg.com/sweetalert/dist/sweetalert.min.js"></script>
</head>

<body class="bg-light">

  <?php require('inc/header.php'); ?>

  <div class="container-fluid" id="main-content">
    <div class="row">
      <div class="col-lg-10 ms-auto p-4 overflow-hidden">
        <h3 class="mb-4">CONFIGURATION FOR NOTIFICATION</h3>
        <div class="card border-0 shadow-sm mb-8">
          <div class="card-body">
            <div class="d-flex align-items-center justify-content-between mb-3">
              <h5 class="card-title m-0">CONFIGURATION SETTINGS</h5>
            </div>
          </div>
        </div>

        <div class="card shadow-lg border-0">
          <div class="card-header p-4 bg-primary text-white text-center">
            <h5 class="mb-0">Insert/Update Admin Email</h5>
          </div>
          <div class="card-body px-4 py-5">
            <div class="table-responsive text-center">
              <form action="" method="POST">
                <div class="mb-4">
                  <label for="inputEmail" class="form-label fw-bold">Enter Email Address:</label>

                  <?php
                  $sql = "SELECT * FROM admin_email WHERE id = ?";
                  $result = select1($sql, [1], "i");

                  if (mysqli_num_rows($result) > 0) {
                    while ($row = $result->fetch_assoc()) {
                  ?>
                      <input type="email"
                        class="form-control shadow-sm rounded border border-primary text-center"
                        id="inputEmail"
                        name="inputEmail"
                        placeholder="Enter email"
                        value="<?php echo htmlspecialchars($row['email']); ?>"
                        required>
                    <?php
                    }
                  } else {
                    ?>
                    <input type="email"
                      class="form-control shadow-sm rounded border border-primary text-center"
                      id="inputEmail"
                      name="inputEmail"
                      placeholder="Enter email"
                      required>
                  <?php
                  }
                  ?>
                </div>
                <?php
                if (isset($_SESSION['statuss'])) {
                ?>
                  <script>
                    swal({
                      title: "<?php echo $_SESSION['statuss']; ?>",
                      icon: "<?php echo $_SESSION['status_code']; ?>",
                      button: "OK",
                    }).then(function() {
                      window.location.href = "config_email.php";
                    });
                  </script>
                <?php
                  unset($_SESSION['statuss']);
                }
                ?>
                <div class="col-auto">
                  <button type="submit" name="submit" class="btn btn-primary shadow-sm px-4 py-2">
                    <i class="material-icons text-sm">Save Email
                  </button>
                </div>
              </form>
            </div>

          </div>
        </div>

      </div>
    </div>
  </div>


  <?php require('inc/scripts.php'); ?>
</body>

</html>