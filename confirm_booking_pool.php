<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <?php require('inc/links.php'); ?>
    <title><?php echo $settings_r['site_title'] ?> - CONFIRM POOL BOOKING</title>
</head>

<body class="bg-light">

    <?php require('inc/header.php'); ?>

    <?php
    if (!isset($_GET['id']) || $settings_r['shutdown'] == true) {
        redirect('pools.php');
    } else if (!(isset($_SESSION['login']) && $_SESSION['login'] == true)) {
        redirect('pools.php');
    }

    $data = filteration($_GET);
    $pool_res = select("SELECT * FROM `pools` WHERE `id`=? AND `status`=? AND `removed`=?", [$data['id'], 1, 0], 'iii');

    if (mysqli_num_rows($pool_res) == 0) {
        redirect('pools.php');
    }

    $pool_data = mysqli_fetch_assoc($pool_res);

    $_SESSION['pool'] = [
        "id" => $pool_data['id'],
        "name" => $pool_data['name'],
        "price" => $pool_data['price'],
        "available" => false,
    ];

    $user_res = select("SELECT * FROM `user_cred` WHERE `id`=? LIMIT 1", [$_SESSION['uId']], "i");
    $user_data = mysqli_fetch_assoc($user_res);
    ?>

    <div class="container">
        <div class="row">
            <div class="col-12 my-5 mb-4 px-4">
                <h2 class="fw-bold">CONFIRM POOL BOOKING</h2>
                <div style="font-size: 14px;">
                    <a href="index.php" class="text-secondary text-decoration-none">HOME</a>
                    <span class="text-secondary"> > </span>
                    <a href="pools.php" class="text-secondary text-decoration-none">POOLS</a>
                    <span class="text-secondary"> > </span>
                    <a href="#" class="text-secondary text-decoration-none">CONFIRM</a>
                </div>
            </div>

            <div class="col-lg-7 col-md-12 px-4">
                <?php
                echo <<<data
                <div class="card p-3 shadow-sm rounded">
                    <img src="villaocampo.jpg" class="img-fluid rounded mb-3">
                    <h5>$pool_data[name]</h5>
                    <h5>$pool_data[description]</h5>
                    <h6>₱$pool_data[price] per pax</h6>
                </div>
                data;
                ?>
            </div>

            <div class="col-lg-5 col-md-12 px-4">
                <div class="card mb-4 border-0 shadow-sm rounded-3">
                    <div class="card-body">
                        <form action="pay_now_pool.php" method="POST" id="booking_form">
                            <h6 class="mb-3">BOOKING DETAILS</h6>
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">Name</label>
                                    <input name="name" type="text" value="<?php echo $user_data['name'] ?>" class="form-control shadow-none" required>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">Phone Number</label>
                                    <input name="phonenum" type="number" value="<?php echo $user_data['phonenum'] ?>" class="form-control shadow-none" required>
                                </div>
                                <div class="col-md-12 mb-3">
                                    <label class="form-label">Address</label>
                                    <textarea name="address" class="form-control shadow-none" rows="1" required><?php echo $user_data['address'] ?></textarea>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">Check-in Date</label>
                                    <input name="checkin_date" type="date" class="form-control shadow-none" required min="<?php echo date('Y-m-d'); ?>">
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">Check-in Time</label>
                                    <select name="checkin_time" class="form-control shadow-none" required>
                                        <option value="">Select Time</option>
                                        <option value="8am - 5pm (Day)">8am - 5pm (Day)</option>
                                        <option value="6pm - 3am (Night)">6pm - 3am (Night)</option>
                                    </select>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">Quantity</label>
                                    <div class="input-group">
                                        <button type="button" class="btn btn-outline-secondary" id="decrease-btn">-</button>
                                        <input name="quantity" type="number" class="form-control shadow-none" id="quantity-input" min="1" value="1" required placeholder="Enter quantity">
                                        <button type="button" class="btn btn-outline-secondary" id="increase-btn">+</button>
                                    </div>
                                </div>

                                <div class="col-md-6 mb-3">
                                    <label class="form-label">Price</label>
                                    <input name="price" type="number" class="form-control shadow-none" readonly id="price-input" value="<?php echo $pool_data['price']; ?>">
                                </div>
                                <div class="col-12">
                                    <div class="spinner-border text-info mb-3 d-none" id="info_loader" role="status">
                                        <span class="visually-hidden">Loading...</span>
                                    </div>
                                    <h6 class="mb-3 text-danger" id="pay_info">Please enter your check-in date and time to proceed with your booking!</h6>
                                    <button name="pay_now" class="btn w-100 text-white custom-bg shadow-none mb-1">Pay Now</button>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <?php require('inc/footer.php'); ?>
</body>

</html>

<script>
    document.getElementById('increase-btn').addEventListener('click', function() {
        var quantityInput = document.getElementById('quantity-input');
        var currentValue = parseInt(quantityInput.value);
        quantityInput.value = currentValue + 1;
        updatePrice();
    });

    document.getElementById('decrease-btn').addEventListener('click', function() {
        var quantityInput = document.getElementById('quantity-input');
        var currentValue = parseInt(quantityInput.value);
        if (currentValue > 1) {
            quantityInput.value = currentValue - 1;
            updatePrice();
        }
    });

    function updatePrice() {
        var quantityInput = document.getElementById('quantity-input');
        var priceInput = document.getElementById('price-input');
        var basePrice = <?php echo $pool_data['price']; ?>;
        var quantity = parseInt(quantityInput.value);
        priceInput.value = basePrice * quantity;
    }

    updatePrice();
</script>