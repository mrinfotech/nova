<?php

require(APPPATH . '/libraries/REST_Controller.php');

class Profiles extends REST_Controller {

    public function __construct() {
        parent::__construct();
        $this->load->model('model_all');
    }

    //API - Fetch All Pincodes



    function dp_put() {

        $user = $this->put('user');
        
        $result = array();
        $base = $this->put('myFile');
        $filename = $this->put('picName');
        $ftype = substr(strrchr($filename, "."), 1);
// Decode Image
        $binary = base64_decode($base);
        $dt = date("Y-m-d H:i:s");
        $picName = $user . "_" . time() . "." . $ftype;
       // header('Content-Type: bitmap; charset=utf-8');
// Images will be saved under 'www/sms/school/book/' folder
        $file = fopen('dps/' . $picName, 'wb');
// Create File
        $fwrite = fwrite($file, $binary);
        fclose($file);
        if ($fwrite !== false) {
            $this->model_all->update(array("dp" => $picName, "modifiedon" => $dt, "modifiedby" => $user), array("id" => $user), "app_users");
            $status = 1;
            $message = "Profile pic changed successfully.";
        } else {
            $status = 0;
            $message = "Something went wrong. Please try again.";
        }
        $result["status"] = $status;
        $result["message"] = $message;

        $this->response($result, 200);

        exit;
    }

}
