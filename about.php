<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta http-equiv="X-UA-Compatible" content="IE=edge">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <link  rel="stylesheet" href="https://unpkg.com/swiper@7/swiper-bundle.min.css">
  <?php require('inc/links.php'); ?>
  <title><?php echo $settings_r['site_title'] ?> - ABOUT</title>
  <style>
    .box{
      border-top-color: var(--teal) !important;
    }
  </style>
</head>
<body class="bg-light">

  <?php require('inc/header.php'); ?>

  <div class="my-5 px-4">
  <h2 class="fw-bold h-font text-center">ABOUT US</h2>
  <div class="h-line bg-dark"></div>
  <h1 class="h-font text-center">
  A place designed to provide recreation, entertainment, and to relax.
    </h1>
</div>
  
<div class="container mt-5">
<div class="row">
    <div class="col-lg-3 col-md-6 mb-4 px-4">
    <div class="bg-white rounded shadow p-4 border-top border-4 text-center box">
        <img src="images/couple room.jpg" width="200px">
        <h4 class="mt-3">11 Rooms</h4>
    </div>
    </div>

    <div class="col-lg-3 col-md-6 mb-4 px-4">
    <div class="bg-white rounded shadow p-4 border-top border-4 text-center box">
        <img src="images/smallnip.png" width="200px">
        <h4 class="mt-3">26 Cottage</h4>
    </div>
    </div>

    <div class="col-lg-3 col-md-6 mb-4 px-4">
    <div class="bg-white rounded shadow p-4 border-top border-4 text-center box">
        <img src="images/pool.jpg" width="200px">
        <h4 class="mt-3">2 Pools</h4>
    </div>
    </div>

    <div class="col-lg-3 col-md-6 mb-4 px-4">
    <div class="bg-white rounded shadow p-4 border-top border-4 text-center box">
        <img src="images/ev.jpg" width="200px">
        <h4 class="mt-3">1 Event Space</h4>
    </div>
    </div>
</div>
</div>

</body>
</html>