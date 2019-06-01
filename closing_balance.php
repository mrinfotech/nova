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
extract($_REQUEST);

$date = date("Y-m-d");
$day_before = date( 'Y-m-d', strtotime( $date . ' -1 day' ) );

$sellers = mysqli_query($con, "select id from sellers");
while($srs = mysqli_fetch_object($sellers)){

$closing_balance = mysqli_fetch_object(mysqli_query($con, "select closing_balance from closing_balance where dealer_id='$srs->id' and closing_date='$day_before'"));
$closing_bal = $closing_balance->closing_balance;

$debit_val = mysqli_fetch_object(mysqli_query($con, "select sum(amount) as debit_amount from wallet_history where transaction_mode='debit' and date(transaction_date)='$date' and user_id=$srs->id"));
$debit = $debit_val->debit_amount;

$credit_val = mysqli_fetch_object(mysqli_query($con, "select sum(amount) as credit_amount from wallet_history where transaction_mode='credit' and date(transaction_date)='$date' and user_id=$srs->id"));
$credit = $credit_val->credit_amount;

$clo_bal = ($closing_bal + $debit) - $credit;

if($clo_bal!=0){
$sql = mysqli_query($con, "INSERT INTO `closing_balance` (`id`, `dealer_id`, `closing_balance`, `closing_date`) VALUES (NULL, '$srs->id', '$clo_bal', '$date')");
}
}
?>