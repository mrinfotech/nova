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
    $db_user = "bulktrade"; // MySQL user
    $db_pass = "bulktrade@456!@#"; // MySQL user's password
    $database = "bulktrade"; // MySQL database
}

define("burl", "http://mitrayainfo.com/bulktrade/");
define("MAP_STATIC_KEY", "AIzaSyAchsFNMAiVSmzR12fUt0YlfUsMWAKwYtU");

$con = mysqli_connect($server, $db_user, $db_pass) or die(mysqli_error($con));
if (mysqli_connect_errno()) {
    echo "Failed to connect to MySQL: " . mysqli_connect_error($con);
} else {
    mysqli_select_db($con, $database) or die(mysqli_error($con));
}
date_default_timezone_set("Asia/Calcutta");
extract($_REQUEST);

function is_login() {
    if (isset($_SESSION[login]) && $_SESSION[login] == true) {
        return true;
    } else {
        return false;
    }
}

include_once "function.php";
include_once "sendsms.php";
?>