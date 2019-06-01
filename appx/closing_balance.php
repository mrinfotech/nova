<?php

error_reporting(E_ERROR | E_WARNING);

$user_ip = $_SERVER["REMOTE_ADDR"];
if ($user_ip == '127.0.0.1') {
    $server = "localhost"; // MySQL server
    $db_user = "root"; // MySQL user
    $db_pass = "root"; // MySQL user's password
    $database = ""; // MySQL database
} else {
    $server = "localhost"; // MySQL server
    $db_user = "bt_b2b"; // MySQL user
    $db_pass = "Userb2b@$%"; // MySQL user's password
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
extract($_REQUEST);

$date = date("Y-m-d");
$day_before = date( 'Y-m-d', strtotime( $date . ' -1 day' ) );
echo "select c.closing_balance from closing_balance c, sellers s where c.dealer_id=s.id and c.closing_date=$day_before";
$cbalance = mysqli_fetch_object(mysqli_query($con, "select c.closing_balance from closing_balance c, sellers s where c.dealer_id=s.id and c.closing_date=$day_before"));
$closing_balance = $cbalance->closing_balance;
echo $closing_balance;
?>