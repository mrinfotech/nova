<?php

require(APPPATH . '/libraries/REST_Controller.php');

class Employee extends REST_Controller {

    public function __construct() {
        parent::__construct();
        $this->load->model('model_all');
    }

    //API - Fetch All Pincodes
    function roles_get() {

        $result_set = $this->model_all->getTableDataInArray("app_roles", array("id!="=>"1"), "id,role_name");
        if (($result_set['total_rows']) > 0) {
            $result["status"] = 1;
            $result["message"] = "Records Found";
            $result["roles"] = $result_set['records'];
            $this->response($result, 200);
            exit;
        } else {
            $result["status"] = 0;
            $result["message"] = "Detais Not Found";
            $this->response($result, 200);
            exit;
        }
    }


    function list_get() {

        $result_set = $this->model_all->getTableDataFromQuery("select e.*,a.role_name from employees e,app_roles a where a.id=e.role_id");
        if ($result_set->num_rows() > 0) {
            $result["status"] = 1;
            $result["message"] = "Records Found";
            foreach($result_set->result() as $row){
               $object = array();
               $object["id"] = $row->id;
               $object["name"] = $row->first_name." ".$row->last_name;
               $object["emp_id"] = $row->uniq_id;
               $object["address"] = $row->address;
               $object["role_name"] = $row->role_name;
               $result["employees"][] = $object;

            }
            
            $this->response($result, 200);
            exit;
        } else {
            $result["status"] = 0;
            $result["message"] = "Detais Not Found";
            $this->response($result, 200);
            exit;
        }
    }

    //API - Save Pin Code
    function add_post() {
        $user= $this->post('user');
        $first_name = $this->post('first_name');
        $last_name = $this->post('last_name');
        $role = $this->post('role');
        $mobile = $this->post('mobile');
        $email = $this->post('email');
        $latitude = $this->post('latitude');
        $longitude = $this->post('langitude');
        $dob = $this->post('dob');
        $dt = date("Y-m-d H:i:s");
 
        if($dob!="")
           $dob = date("Y-m-d",strtotime($dob));
        $address = $this->post('address');
        if($role=="9"){ $table = "sellers"; $error_name = "Seller";}else {  $table = "employees"; $error_name = "Employee";}
        $result_set = $this->model_all->getTableData($table, array("mobile" => $mobile));
        if ($result_set->num_rows() == 0) {
           if($role=="9"){
             $insert_id = $this->model_all->save(array("first_name" => $first_name, "last_name" => $last_name, "mobile" => $mobile, "email"=>$email,"latitude"=>$latitude,"longitude"=>$longitude,"address"=>$address,"createdon"=>$dt,"modifiedon"=>$dt,"createdby"=>$user,"modifiedby"=>$user), "sellers");
             
             $role_nm = "seller";
           }else{
             $insert_id = $this->model_all->save(array("first_name" => $first_name, "last_name" => $last_name,"role_id" => $role, "mobile" => $mobile, "email"=>$email,"dob" => $dob,"address"=>$address,"createdon"=>$dt,"modifiedon"=>$dt), "employees");
             $role_nm = "trade";
             if($insert_id > 0) 
                $this->model_all->update(array("uniq_id"=>'BT'.$this->model_all->prefix_zeros($insert_id)), array("id"=>$insert_id),"employees");
           }
            
            if ($insert_id > 0) {
                $this->model_all->save(array("dp" => 'noimage.png', "username" => $mobile,"password" => md5($this->model_all->randomPassword()),"role" => $role_nm, "pkid" => $insert_id, "status" => '1',"createdon"=>$dt,"modifiedon"=>$dt,"createdby"=>$user,"modifiedby"=>$user), "app_users");  
                  
                $result["status"] = 1;
                $result["message"] = "Submitted Successfully";
            } else {
                $result["status"] = 0;
                $result["message"] = "Employee Registration Unsuccessful";
            }
            $this->response($result, 200);
        } else {
            $result["status"] = 0;
            $result["message"] = $error_name." already Exists";
            $this->response($result, 200);
        }
        exit;
    }

    function update_put() {

        $pincode = $this->put('pincode');
        $primaryid = $this->put('primaryid');
        $pincodeid = $this->put('pincodeid');
        $result_set = $this->model_all->getTableData("pincodes", array("pincode" => $pincode,"id!="=>$pincodeid));
        if ($result_set->num_rows() == 0) {
            $insert_status = $this->model_all->update(array("pincode" => $pincode, "modifiedon" => date("Y-m-d H:i:s"), "modifiedby" => $primaryid),array("id"=>$pincodeid), "pincodes");
            if ($insert_status) {
                $result["status"] = 1;
                $result["message"] = "Pincode Updated Successfully";
            } else {
                $result["status"] = 0;
                $result["message"] = "Updation Unsuccessful.";
            }
            $this->response($result, 200);
        } else {
            $result["status"] = 0;
            $result["message"] = "Pincode with this value already Exists";
            $this->response($result, 200);
        }
        exit;
    }

    




}
