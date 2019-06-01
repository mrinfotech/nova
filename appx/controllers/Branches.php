<?php

require(APPPATH . '/libraries/REST_Controller.php');

class Branches extends REST_Controller {

    public function __construct() {
        parent::__construct();
        $this->load->model('model_all');
    }

    function list_get() {

        $result_set = $this->model_all->getTableDataFromQuery("select b.id,b.name,b.email,b.mobile_numbs as mobile,b.addressline1 as address,b.district as district_id,b.country as country_id,b.state as state_id,b.district as district_id,b.latitude,b.longitude,b.gstin as gst,b.dp as pic,c.name as country_nm,s.state as state_nm,d.district as district_nm,b.bgcolor as color from branches b,districts d,states s,countries c where c.id=b.country and s.id=b.state and d.id=b.district ");
        if ($result_set->num_rows() > 0) {
            $result["status"] = 1;
            $result["message"] = "Records Found";
            foreach ($result_set->result_array() as $row) {
                 
                if ($row["pic"] != "") {
                        $file_headers = @get_headers(base_url() . 'branches/' . $row["pic"]);
                        if (stripos($file_headers[0], "404 Not Found") > 0 || (stripos($file_headers[0], "302 Found") > 0 && stripos($file_headers[7], "404 Not Found") > 0)) {
                            $row["pic"] = base_url() . 'branches/noimage.png';
                        } else {
                            $row["pic"] = base_url() . 'branches/' .$row["pic"];
                        }
                 }else{
                     $row["pic"] = base_url() . 'branches/' . $row["pic"];
                 }
                $row["pic"] = $row["pic"] . "?" . time();
                $result["records"][] = $row;
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
    
    
    function shortlist_get() {

        $company = $this->get("company");
        $branch = $this->get("branch");

        $query = "select b.id,b.name,b.bgcolor as color,c.company from branches b,companies c where b.company=c.company_id  and b.status='1' ";
        if($company > 0){
            $query = $query." and c.company_id='$company'";
        }
        if($branch!=""){
            $query = $query." and b.id!='$branch'";
        }
        $result_set = $this->model_all->getTableDataFromQuery($query." order by b.name asc");
        if ($result_set->num_rows() > 0) {
            $result["status"] = 1;
            $result["message"] = "Records Found";
            
            foreach ($result_set->result_array() as $row) {
               $row["name"] = $row["name"];
               $result["records"][] = $row;
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
    function manage_post() {



        $user = $this->post('user');
        $branch = $this->post('branch');
        $name = ucwords($this->post('name'));
        $email = $this->post('email');
        $mobile = $this->post('mobile');
        $land = $this->post('land');
        $addressline1 = $this->post('address');
        $addressline2 = $this->post('address2');
        $landmark = $this->post('landmark');
        $district = $this->post('district');
        $pincode = $this->post('pincode');
        $state = $this->post('state');
        $country = $this->post('country');
        $latitude = $this->post('latitude');
        $longitude = $this->post('longitude');
        $gst = $this->post('gst');
        $regd_no = $this->post('regd_no');
        $dt = date("Y-m-d H:i:s");
        $flag = false;

        $table = "branches";
        if($branch>0){
            $result_set = $this->model_all->getTableData($table, array("mobile_numbs" => $mobile,"name"=>$name,"id!="=>$branch));
        }else{
            $result_set = $this->model_all->getTableData($table, array("mobile_numbs" => $mobile,"name"=>$name));
        }
        if ($result_set->num_rows() == 0) {
            $data = array("name" => $name, "email" => $email, "mobile_numbs" => $mobile, "land_numbs" => $land, "addressline1" => $addressline1, "addressline2" => $addressline2, "landmark" => $landmark, "district" => $district, "pincode" => $pincode, "state" => $state, "country" => $country, "modified_on" => $dt, "status" => '1', "modified_by" => $user,"modified_on" => $dt, "latitude" => $latitude, "longitude" => $longitude, "gstin" => $gst, "regd_no" => $regd_no);
            if ($branch > 0) {
                $action_status = $this->model_all->update($data, array("id" => $branch), $table);
                if ($action_status) {
                    $flag = true;
                    $result["message"] = "Branch Details are updated Successfully";
                }
            } else {
                $data["created_on"] = $dt;
                $data["created_by"] = $user;
                $data["bgcolor"] = $this->rand_color();
                $branch = $this->model_all->save($data, $table);
                if ($branch > 0) {
                    $flag = true;
                     $result["message"] = "Branch Registered Successfully";
                }
            }
            if ($branch > 0) {
                if (isset($_FILES) && isset($_FILES['pic']) && $_FILES['pic']['size'] > 0 && $_FILES['pic']['error'] == 0) {
                    $ftype = substr(strrchr($_FILES['pic']['name'], "."), 1);
                    $pic_name = $branch.".".$ftype;
                    if (move_uploaded_file($_FILES['pic']['tmp_name'], "branches/" .$pic_name)) {
                        $action_status = $this->model_all->update(array("dp" => $pic_name), array("id" => $branch), $table);
                        if ($action_status) {
                            $result["message"] = "Branch Details are updated Successfully";
                        }
                    }
                }
            }
            if ($flag) {
                $result["status"] = 1;
                
            } else {
                $result["status"] = 0;
                $result["message"] = "Branch Registration Unsuccessful";
            }
        } else {
            $result["status"] = 0;
            $result["message"] = $name . " already Exists";
        }

        $this->response($result, 200);
        exit;
    }

    function delete_put() {
        $branch = $this->put('branch');
        $result_set = $this->model_all->update(array("status" => '0'), array("id" => $branch), "branches");
        if ($result_set) {
            $result["status"] = 1;
            $result["message"] = "Branch Deleted Successfully";
        } else {
            $result["status"] = 0;
            $result["message"] = "Branch Deletion Unsuccessful.";
        }
        $this->response($result, 200);
        exit;
    }



    function details_get() {
        $branch = $this->get('branch');
        $result_set = $this->model_all->getTableDataFromQuery("select b.id,b.name,b.email,b.mobile_numbs as mobile,b.addressline1 as address,b.country as country_id,b.district as district_id,b.state as state_id,b.district as district_id,b.latitude,b.longitude,b.gstin as gst,b.dp as pic,c.name as country_nm,s.state as state_nm,d.district as district_nm from branches b,districts d,states s,countries c where b.id='$branch' and c.id=b.country and s.id=b.state and d.id=b.district");
        if ($result_set->num_rows() > 0) {
            $result["status"] = 1;
            $result["message"] = "Records Found";
            foreach ($result_set->result_array() as $row) {

                if ($row["pic"] != "") {
                        $file_headers = @get_headers(base_url() . 'branches/' . $row["pic"]);
                        if (stripos($file_headers[0], "404 Not Found") > 0 || (stripos($file_headers[0], "302 Found") > 0 && stripos($file_headers[7], "404 Not Found") > 0)) {
                            $row["pic"] = base_url() . 'branches/noimage.png';
                        } else {
                            $row["pic"] = base_url() . 'branches/' .$row["pic"];
                        }
                 }else{
                     $row["row"] = base_url() . 'branches/' . $row["pic"];
                 }
                $row["pic"] = $row["pic"] . "?" . time();
                $result["details"] = $row;
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

    function rand_color() {
       return '#' . str_pad(dechex(mt_rand(0, 0xFFFFFF)), 6, '0', STR_PAD_LEFT);
    }

}
