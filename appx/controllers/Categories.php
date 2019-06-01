<?php
require(APPPATH . '/libraries/REST_Controller.php');
class Categories extends REST_Controller {

    public function __construct() {
        parent::__construct();
        $this->load->model('model_all');
    }

    //API - Fetch All Pincodes
    function index_get() {
 
        $company = $this->get("category");
        $condition = array("parentid"=>0);
        if($company!=""){
         $condition["company"] = $company;
        }
      
        $result = array();
        $result_set = $this->model_all->getTableData("categories",$condition , "id,categoryname");
        if ($result_set->num_rows() > 0) {
            $result["status"] = 1;
            $result["message"] = "Success";
            foreach($result_set->result() as $rs){
            $object=array();
            $object['id'] = $rs->id;
            $object['category'] = $rs->categoryname;
            $object['children'] = $this->sub_categories($rs->id);
            $result['categories'][] = $object;
            }            
            $this->response($result, 200);
            exit;
        } else {
            $result["status"] = 0;
            $result["message"] = "No Categories Found";
            $this->response($result, 200);
            exit;
        }
    }
    
    function sub_categories($parent_id){
        $result = array();
        $result_set = $this->model_all->getTableData("categories", "parentid='$parent_id'", "id,categoryname");
        if ($result_set->num_rows() > 0) {
            
            foreach($result_set->result() as $rs){
             $object=array();
             $object['id'] = $rs->id;
             $object['category'] = $rs->categoryname;
             $object['children'] = $this->sub_categories($rs->id);
             $result[] = $object;
            }            
            
        }
        return $result;
    
    }
    
    
               
}
