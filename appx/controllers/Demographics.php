<?php

require(APPPATH . '/libraries/REST_Controller.php');

class Demographics extends REST_Controller {

    public function __construct() {
        parent::__construct();
        $this->load->model('model_all');
    }

    function countries_get() {

        $result_set = $this->model_all->getTableDataFromQuery("select c.* from countries c order by c.name");
        if ($result_set->num_rows() > 0) {
            $result["status"] = 1;
            $result["message"] = "Records Found";
            foreach ($result_set->result_array() as $row) {
                $result["records"][] =$row;
            }
           
        } else {
            $result["status"] = 0;
            $result["message"] = "Records Not Found";
           
        }
         $this->response($result, 200);
            exit;
    }

    function states_get() {
        $country = $this->get('country');
        $query = "select s.id,s.state as name from states s,countries c where c.id=s.country ";
        if($country>0){
          $query .= " and c.id='$country' ";
        }
        $query .= "  order by s.state";
        $result_set = $this->model_all->getTableDataFromQuery($query);
        if ($result_set->num_rows() > 0) {
            $result["status"] = 1;
            $result["message"] = "Records Found";
            foreach ($result_set->result_array() as $row) {
                $result["records"][] =$row;
            }
           
        } else {
            $result["status"] = 0;
            $result["message"] = "Records Not Found";
           
        }
         $this->response($result, 200);
            exit;
    }

    function districts_get() {
        $state = $this->get('state');
        $query = "select d.id,d.district as name from states s,districts d where d.state=s.id ";
        if($state>0){
          $query .= " and s.id='$state' ";
        }
        $query .= "  order by d.district";
        $result_set = $this->model_all->getTableDataFromQuery($query);
        if ($result_set->num_rows() > 0) {
            $result["status"] = 1;
            $result["message"] = "Records Found";
            foreach ($result_set->result_array() as $row) {
                $result["records"][] =$row;
            }
           
        } else {
            $result["status"] = 0;
            $result["message"] = "Records Not Found";
           
        }
         $this->response($result, 200);
            exit;
    }


    
    

}