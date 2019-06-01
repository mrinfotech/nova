<?php

defined('BASEPATH') OR exit('No direct script access allowed');

class Csvimport extends CI_Controller {

    public function __construct() {
        parent::__construct();
        $this->load->model('model_all');
        $this->load->library('csvimport'); 
    }

    //API - Fetch All Pincodes
    

    //API - Save Pin Code
    function import_post() {

        $flag1 = true;
        $flag2 = true;
        
        $fields = $this->csvimport->get_array($_FILES['csv_file']['tmp_name']);
        $result["records"] = $fields;
        $result["status"] = "1";
        $result["message"] = "Records saved successfully";

        $this->response($result, 200);

        exit;
    }
    
    function view_get() {
       echo "Testing";
       $this->load->view('importfile');
    }

    

}
