<?php

require(APPPATH . '/libraries/REST_Controller.php');

class Grades extends REST_Controller {

    public function __construct() {
        parent::__construct();
        $this->load->model('model_all');
    }

    //API - Fetch All Pincodes
    function list_get() {
        $branch = $this->get("branch");
        $result_set = $this->model_all->getTableDataInArray("grades");
        if (($result_set['total_rows']) > 0) {
            $result["status"] = 1;
            $result["message"] = "Records Found";
            $result["records"] = $result_set['records'];
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

        $id = $this->post('id');
        $grade = $this->post('grade');
        $grade_amount = $this->post('grade_amount');
        $table="grades";
        $dt = date("Y-m-d H:i:s");
        $flag = false;
        //  print_r($_FILES);
      
      //echo '$id';

        if ($id> 0) {
            $result_set = $this->model_all->getTableData($table, array("name"=>$grade, "id!="=> $id));
        } else {
            $result_set = $this->model_all->getTableData($table, array("name"=>$grade));
        }

        if ($result_set->num_rows() == 0) {
        
                  $data = array('name'=>$grade ,'grade_amount'=>$grade_amount,'status'=>'1');
                if ($id > 0) {
                    $action_status = $this->model_all->update($data, array("id" => $id), $table);
                    if ($action_status) {
                        $flag = true;
                    }
                } else {
                    $id = $this->model_all->save($data, $table);
                    if ($id > 0) {
                        $flag = true;
                    }
                }

           
            if ($flag) {
                $result["status"] = 1;
                $result["message"] = "Details Submission Successful";   
            } else {
                $result["status"] = 0;
                $result["message"] = "Details Submission Unsuccessful";
            }
            $this->response($result, 200);
        } else {
            $result["status"] = 0;
            $result["message"] = " Grade already Exists";
            $this->response($result, 200);
        }
        exit;
    }

    function delete_put() {
        $grade = $this->put('grade');
        
        $dt = date("Y-m-d H:i:s");
        $action_status = $this->model_all->update(array("status" => '0'), array("id" => $grade), "grades");
        if ($action_status>0) {
            $result["status"] = 1;
            $result["message"] = "Grade Deleted Successfully";
        } else {
            $result["status"] = 0;
            $result["message"] = "Grade  Deletion Unsuccessful.";
        }
        $this->response($result, 200);
        exit;
    }


    

}
