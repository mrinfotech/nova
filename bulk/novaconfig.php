<?php 
session_start();
$current_page =  basename($_SERVER['PHP_SELF']);;
// echo $current_page;

error_reporting(E_ERROR | E_WARNING);
if (!isset($_SESSION))
    session_start();
$user_ip = $_SERVER["REMOTE_ADDR"];
if ($user_ip == '127.0.0.1') {
    $server = "localhost"; // MySQL server
    $db_user = "root"; // MySQL user
    $db_pass = "rootroot"; // MySQL user's password
    $database = "nova_newdata"; // MySQL database
} else {
    $server = "localhost"; // MySQL server
    $db_user = "v1_user"; // MySQL user
    $db_pass = "User@$%123"; // MySQL user's password
    $database = "nova_v1"; // MySQL database
}
define("burl", "http://mitrayainfo.com/hyd_cndwm/");
define("MAP_STATIC_KEY", "AIzaSyAchsFNMAiVSmzR12fUt0YlfUsMWAKwYtU");

$con = mysqli_connect($server, $db_user, $db_pass) or die(mysqli_error($con));
if (mysqli_connect_errno($con)) {
    echo "Failed to connect to MySQL: " . mysqli_connect_error($con);
} else {
   // echo "connected";
    
    mysqli_select_db($con, $database) or die(mysqli_error($con));
}
date_default_timezone_set("Asia/Calcutta");
extract($_REQUEST);


function is_empty($na) {
    $na = trim($na);

    if (!empty($na) || $na != '..') {
        return $na;
    } else {

        return "NA";
    }
}function mysqli_data($con, $query, $data) {
    $res = mysqli_query($con, $query) or die(mysql_error());
    $row = mysqli_fetch_object($res);
    return $row->$data;
}

function test_input($data) {
    $data = trim($data);
    $data = stripslashes($data);
    $data = htmlspecialchars($data);
    return $data;
}

function numToMoney($amt) {

    if ($amt >= 10000000) {
        return number_format($amt / 10000000, 2) . " Cr.";
    } else if ($amt >= 100000) {
        return number_format($amt / 100000, 2) . " L"; //Lacs
    } else if ($amt >= 1000) {
        return number_format($amt / 1000, 2) . " K";
    } else {
        return $amt;
    }
}

function moneyFormatIndia($num) {

    $explrestunits = "";
    $num = preg_replace('/,+/', '', $num);
    $words = explode(".", $num);
    $des = "00";
    if (count($words) <= 2) {
        $num = $words[0];
        if (count($words) >= 2) {
            $des = $words[1];
        }
        if (strlen($des) < 2) {
            $des = "$des0";
        } else {
            $des = substr($des, 0, 2);
        }
    }
    if (strlen($num) > 3) {
        $lastthree = substr($num, strlen($num) - 3, strlen($num));
        $restunits = substr($num, 0, strlen($num) - 3); // extracts the last three digits
        $restunits = (strlen($restunits) % 2 == 1) ? "0" . $restunits : $restunits; // explodes the remaining digits in 2's formats, adds a zero in the beginning to maintain the 2's grouping.
        $expunit = str_split($restunits, 2);
        for ($i = 0; $i < sizeof($expunit); $i++) {
            // creates each of the 2's group and adds a comma to the end
            if ($i == 0) {
                $explrestunits .= (int) $expunit[$i] . ","; // if is first value , convert into integer
            } else {
                $explrestunits .= $expunit[$i] . ",";
            }
        }
        $thecash = $explrestunits . $lastthree;
    } else {
        $thecash = $num;
    }
    return $thecash; // writes the final format where $currency is the currency symbol. $thecash.$des
}

function user_name($con, $sid) {
    $user_name = mysqli_data($con, "select  user_name from ss_users where ss_user_id='$sid'", "user_name");

    return $user_name;
}

function dist_name($con, $id) {
    $name = mysqli_data($con, "select district from  districts where district_id='$id'", "district");
    return $name;
}

function city_name($con, $ctyid) {
    $name = mysqli_data($con, "select city from cities where city_id='$ctyid'", "city");
    return $name;
}

function state_name($con, $stateid) {
    $state = mysqli_data($con, "select state from states where state_id='$stateid'", "state");
    return $state;
}

function country_name($con, $countryId) {
    $state = mysqli_data($con, "select country_name from countries where country_id='$countryId'", "country_name");
    return $state;
}

function interestName($con, $lookingFor) {
    //echo "select b_catg_name from sub_categories where sub_catg_id='$lookingFor'";
    $lookingForCat = mysqli_data($con, "select sub_catg_name from sub_categories where sub_catg_id='$lookingFor'", "sub_catg_name");
    return $lookingForCat;
}

function interestsList($con, $lookingFor) {
    //echo "select b_catg_name from sub_categories where sub_catg_id='$lookingFor'";
    $str = "";
    if ($lookingFor != "") {
        $lookingForCatQry = mysqli_query($con, "select sub_catg_name from sub_categories where sub_catg_id IN ('$lookingFor')");
        while ($lookingForCatRs = mysqli_fetch_object($lookingForCatQry)) {

            if ($str != "") {
                $str = $str . ",";
            }
            $str = $str . $lookingForCatRs->sub_catg_name;
        }
    }
    return $str;
}

function checkEmpty($shareId) {
    if ($shareId == null || $shareId == "") {
        return 0;
    } else {
        return $shareId;
    }
}

function numToWords($number) {
    $no = round($number);
    $point = round($number - $no, 2) * 100;
    $hundred = null;
    $digits_1 = strlen($no);
    $i = 0;
    $str = array();
    $words = array('0' => '', '1' => 'one', '2' => 'two',
        '3' => 'three', '4' => 'four', '5' => 'five', '6' => 'six',
        '7' => 'seven', '8' => 'eight', '9' => 'nine',
        '10' => 'ten', '11' => 'eleven', '12' => 'twelve',
        '13' => 'thirteen', '14' => 'fourteen',
        '15' => 'fifteen', '16' => 'sixteen', '17' => 'seventeen',
        '18' => 'eighteen', '19' => 'nineteen', '20' => 'twenty',
        '30' => 'thirty', '40' => 'forty', '50' => 'fifty',
        '60' => 'sixty', '70' => 'seventy',
        '80' => 'eighty', '90' => 'ninety');
    $digits = array('', 'hundred', 'thousand', 'lakh', 'crore');
    while ($i < $digits_1) {
        $divider = ($i == 2) ? 10 : 100;
        $number = floor($no % $divider);
        $no = floor($no / $divider);
        $i += ($divider == 10) ? 1 : 2;
        if ($number) {
            $plural = (($counter = count($str)) && $number > 9) ? 's' : null;
            $hundred = ($counter == 1 && $str[0]) ? ' and ' : null;
            $str [] = ($number < 21) ? $words[$number] .
                    " " . $digits[$counter] . $plural . " " . $hundred :
                    $words[floor($number / 10) * 10]
                    . " " . $words[$number % 10] . " "
                    . $digits[$counter] . $plural . " " . $hundred;
        } else
            $str[] = null;
    }
    $str = array_reverse($str);
    $result = implode('', $str);
    $points = ($point) ?
            "." . $words[$point / 10] . " " .
            $words[$point = $point % 10] : '';
    if($points!=0){
        return $result . "Rupees  " . $points . " Paise";
    }else{
        return $result . "Rupees  ";
    }
    
}

function prefix_zeros($number,$prefix_zeros=5){
    $numbLength = strlen($number);
    $prefix_string ="";
    if($numbLength>$prefix_zeros){
       $prefix_zeros =0;
    }else{
       $prefix_zeros = $prefix_zeros-$numbLength;

    }
    for($i=1;$i<=$prefix_zeros;$i++){
       $prefix_string .= "0";
    }
    $number = $prefix_string.$number;

    /*
       switch($numbLength){
        case 1: $number =  "0000000".$number;break; 
        case 2: $number =  "000000".$number;break;
        case 3: $number =  "00000".$number;break;
        case 4: $number =  "0000".$number;break;
        case 5: $number =  "000".$number;break;
        case 6: $number =  "00".$number;break;
        case 7: $number =  "0".$number;break;
        default : break;
      }
   */
        




      
        
    
    return $number;
}


function getPlaceName($latitude, $longitude)
{
   //This below statement is used to send the data to google maps api and get the place name in different formats. we need to convert it as required. 
   $geocode=file_get_contents('http://maps.googleapis.com/maps/api/geocode/json?latlng='
                                         .$latitude.','.$longitude.'&sensor=false');

   
   $output= json_decode($geocode);

   //Here "formatted_address" is used to display the address in a user friendly format.
   return $output->results[0]->formatted_address;
   
   
}

function distance($lat1, $lon1, $lat2, $lon2, $unit) {

  $theta = $lon1 - $lon2;
  $dist = sin(deg2rad($lat1)) * sin(deg2rad($lat2)) +  cos(deg2rad($lat1)) * cos(deg2rad($lat2)) * cos(deg2rad($theta));
  $dist = acos($dist);
  $dist = rad2deg($dist);
  $miles = $dist * 60 * 1.1515;
  $unit = strtoupper($unit);

  if ($unit == "K") {
    return ($miles * 1.609344);
  } else if ($unit == "N") {
      return ($miles * 0.8684);
    } else {
        return $miles;
      }
}


function timeToDays($sec_time){
 $time_str = "";
 $rem_time = $sec_time;
 $days = floor($rem_time/86400);
 $rem_time = $rem_time - ($days*86400);
 $hours = floor($rem_time/1440);
 $rem_time = $rem_time - ($hours*1440);
 $minutes = floor($rem_time/60);
 
 if($days > 0){
    $time_str .= $days." Days ";
 }
 
 if($hours > 0){
    $time_str .= $hours." hr ";
 }
 
 if($minutes > 0){
    $time_str .= $minutes." min";
 }
 
return $time_str;
}

?>
