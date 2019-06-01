<?php

$user_ip = $_SERVER["REMOTE_ADDR"];
if ($user_ip == '127.0.0.1') {
    $server = "localhost"; // MySQL server
    $db_user = "root"; // MySQL user
    $db_pass = "root"; // MySQL user's password
    $database = "garbage"; // MySQL database
} else {
    $server = "localhost"; // MySQL server
    $db_user = "bt_b2b"; // MySQL user
    $db_pass = "Userb2b@$%"; // MySQL user's password
    $database = "nova_production"; // MySQL database
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



$seller_qry = mysqli_query($con,"select pkid,username from app_users where  role='trade'");
while($seller_rs=mysqli_fetch_object($seller_qry)){
  mysqli_query($con,"update  `employees` set uniq_id='$seller_rs->username' where id='$seller_rs->pkid'");
}







?>