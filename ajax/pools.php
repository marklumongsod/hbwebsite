<?php
require('../inc/db_config.php');
require('../inc/essentials.php');
adminLogin();

if(isset($_POST['get_all_pools'])) {
    get_all_pools();
}

if(isset($_POST['add_pool'])) {
    add_pool();
}

if(isset($_POST['edit_pool'])) {
    edit_pool();
}

if(isset($_POST['get_pool'])) {
    get_pool($_POST['get_pool']);
}

if(isset($_POST['toggle_status'])) {
    toggle_status($_POST['toggle_status'], $_POST['value']);
}

if(isset($_POST['add_image'])) {
    add_image();
}

function get_all_pools() {
    global $pdo;
    $query = "SELECT * FROM pools";
    $stmt = $pdo->query($query);
    $result = '';

    if($stmt->rowCount() > 0) {
        $pools = $stmt->fetchAll(PDO::FETCH_ASSOC);
        foreach($pools as $index => $pool) {
            $status = $pool['status'] == 1 ? 'Active' : 'Inactive';
            $result .= "
                <tr>
                    <td>".($index + 1)."</td>
                    <td>{$pool['name']}</td>
                    <td>{$pool['description']}</td>
                    <td>{$pool['price']}</td>
                    <td>{$status}</td>
                    <td>
                        <button onclick=\"edit_details({$pool['id']})\" class='btn btn-warning btn-sm'>Edit</button>
                        <button onclick=\"toggle_status({$pool['id']},{$pool['status']})\" class='btn btn-info btn-sm'>Toggle Status</button>
                    </td>
                </tr>
            ";
        }
    } else {
        $result = "<tr><td colspan='6'>No pools found</td></tr>";
    }

    echo $result;
}

function add_pool() {
    global $pdo;
    $name = $_POST['name'];
    $description = $_POST['description'];
    $price = $_POST['price'];

    $query = "INSERT INTO pools (name, description, price, status) VALUES (?, ?, ?, 1)";
    $stmt = $pdo->prepare($query);
    $result = $stmt->execute([$name, $description, $price]);

    echo $result ? '1' : '0';
}

function edit_pool() {
    global $pdo;
    $id = $_POST['pool_id'];
    $name = $_POST['name'];
    $description = $_POST['description'];
    $price = $_POST['price'];

    $query = "UPDATE pools SET name = ?, description = ?, price = ? WHERE id = ?";
    $stmt = $pdo->prepare($query);
    $result = $stmt->execute([$name, $description, $price, $id]);

    echo $result ? '1' : '0';
}

function get_pool($id) {
    global $pdo;
    $query = "SELECT * FROM pools WHERE id = ?";
    $stmt = $pdo->prepare($query);
    $stmt->execute([$id]);
    $pool = $stmt->fetch(PDO::FETCH_ASSOC);

    echo json_encode(['pooldata' => $pool]);
}

function toggle_status($id, $current_status) {
    global $pdo;
    $new_status = $current_status == 1 ? 0 : 1;
    $query = "UPDATE pools SET status = ? WHERE id = ?";
    $stmt = $pdo->prepare($query);
    $result = $stmt->execute([$new_status, $id]);

    echo $result ? '1' : '0';
}

function add_image() {
    global $pdo;

    if(isset($_FILES['image']['name'])) {
        $pool_id = $_POST['pool_id'];
        $image_name = $_FILES['image']['name'];
        $tmp_name = $_FILES['image']['tmp_name'];
        $path = '../images/pools/' . $image_name;

        // Move uploaded image to server directory
        if(move_uploaded_file($tmp_name, $path)) {
            $query = "INSERT INTO pool_images (pool_id, image) VALUES (?, ?)";
            $stmt = $pdo->prepare($query);
            $result = $stmt->execute([$pool_id, $image_name]);

            echo $result ? '1' : '0';
        } else {
            echo '0';
        }
    }
}
