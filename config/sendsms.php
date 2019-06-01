<?php

function send_sms($mobile, $msg) {
//set POST varia bles
    $url = 'http://www.siegsms.in/postsms.aspx';
    $fields = array('userid' => urlencode('mitraya'),
        'pass' => urlencode('welcome@123'),
        'phone' => urlencode($mobile),
        'msg' => urlencode($msg));
//url-ify the da ta for the POST 
    foreach ($fields as $key => $value) {
        $fields_string .= $key . '=' . $value . '&';
    }
    rtrim($fields_string, ' &');
//open connectio n 
    $ch = curl_init();
//set the url, n umber of POST vars, POST data
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_POST, count($fields));
    curl_setopt($ch, CURLOPT_POSTFIELDS, $fields_string);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
//execute post
    $curlData = curl_exec($ch);

//close connecti on 
    curl_close($ch);
//return $result;
}

?>