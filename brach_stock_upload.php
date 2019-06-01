<?php

$user_ip = $_SERVER["REMOTE_ADDR"];
if ($user_ip == '127.0.0.1') {
    $server = "localhost"; // MySQL server
    $db_user = "root"; // MySQL user
    $db_pass = "root"; // MySQL user's password
    $database = "garbage"; // MySQL database
} else {
    $server = "localhost"; // MySQL server
    $db_user = "v1_user"; // MySQL user
    $db_pass = "User@$%123"; // MySQL user's password
    $database = "nova_v1"; // MySQL database
}

define("burl", "http://mitrayainfo.com/hyd_cndwm/");
define("MAP_STATIC_KEY", "AIzaSyAchsFNMAiVSmzR12fUt0YlfUsMWAKwYtU");

$con = mysqli_connect($server, $db_user, $db_pass) or die(mysqli_error($con));
if (mysqli_connect_errno()) {
    echo "Failed to connect to MySQL: " . mysqli_connect_error($con);
} else {
    mysqli_select_db($con, $database) or die(mysqli_error($con));
}
date_default_timezone_set("Asia/Calcutta");
$dt = date("Y-m-d H:i:s");






$branch_qry = mysqli_query($con,"select * from branches where company='2'");

while($branch_rs = mysqli_fetch_object($branch_qry)){

$branch = $branch_rs->id;
$item_qry = mysqli_query($con,"select * from items where productid='32'");
while($item_rs = mysqli_fetch_object($item_qry)){

    $price_qry = mysqli_query($con,"select * from item_prices where item_id='$item_rs->id'");
    while($price_rs = mysqli_fetch_object($price_qry)){

      //echo "INSERT INTO `branch_prices` (`id`, `itemprice_id`, `branch_id`, `company_mrp`, `margin_price`, `pay`, `qty`, `is_bt`, `barcode`, `modified_on`) VALUES (NULL, '$price_rs->id', '$branch', '$price_rs->company_mrp', '$price_rs->mrp', '$price_rs->dealer_price', '0', '0', NULL, CURRENT_TIMESTAMP)";
      mysqli_query($con,"INSERT INTO `branch_prices` (`id`, `itemprice_id`, `branch_id`, `company_mrp`, `margin_price`, `pay`, `qty`, `is_bt`, `barcode`, `modified_on`) VALUES (NULL, '$price_rs->id', '$branch', '$price_rs->company_mrp', '$price_rs->mrp', '$price_rs->dealer_price', '0', '0', NULL, CURRENT_TIMESTAMP)");

    }


}

}


?>