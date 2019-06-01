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




$req_qry = mysqli_query($con,"select * from item_prices order by id");
while($req_rs = mysqli_fetch_object($req_qry)){
    echo $query = "update `branch_prices` set company_mrp='$req_rs->company_mrp' ,`margin_price`='$req_rs->mrp'  where  `itemprice_id`='$req_rs->id'";
    mysqli_query($con,$query);
 
}






?>