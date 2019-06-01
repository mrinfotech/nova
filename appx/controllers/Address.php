<?php

require(APPPATH . '/libraries/REST_Controller.php');

class Address extends REST_Controller {

    public function __construct() {
        parent::__construct();
        $this->load->model('model_all');
    }

    function add_post() {
        $user = $this->post('id');
        $role = $this->post('role');
        $longitude = $this->post('longitude');
        $latitude = $this->post('latitude');
        $address = $this->post('address');
        $locale = $this->post('locale');
        $is_default = $this->post('is_default');
        $data = array('latitude' => $latitude, 'longitude' => $longitude, 'address' => $address, 'locale' => $locale, 'user_id' => $user, 'user_role' => $role,'is_default'=>$is_default, 'status' => '1');
        $id = $this->model_all->save($data, "addresses");
        if ($id > 0) {
            $status = 1;
            $message = "Addess saved successfully.";
            $this->model_all->getTableDataFromQuery("update addresses set is_default='0' where id!='$id' and user_id='$user' and user_role='$role'");
            $result["address"] = $id;
        } else {
            $status = 0;
            $message = "Something went wrong. Please try again.";
        }
        $result["status"] = $status;
        $result["message"] = $message;
        $this->response($result, 200);
        exit;
    }

     function list_get() {
        $user = $this->get('id');
        $role = $this->get('role');
        $result_set = $this->model_all->getTableData("addresses", array("user_id" => $user, "user_role" => $role, "status" => '1'));
        if ($result_set->num_rows() > 0) {
            $result["status"] = 1;
            $result["message"] = "Records Found";
            $result["total_rows"] = $result_set->num_rows();
            $result["records"] = array();
            foreach ($result_set->result_array() as $row) {
                $result["records"][] = $row;
            }

            $this->response($result, 200);
            exit;
        } else {
            $result["status"] = 0;
            $result["message"] = "No records Found";
            $this->response($result, 200);
            exit;
        }
    }

}
