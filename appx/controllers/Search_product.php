<?php
require(APPPATH . '/libraries/REST_Controller.php');
class Search_product extends REST_Controller {

    private $dt;
    public function __construct() {
        parent::__construct();
        $this->load->model('model_all');
        $present_time = date("H:i");
        $this->dt = date("Y-m-d",strtotime("-1 day"));
        
    }

    function list_get() {
$primaryid = $this->get('primaryid');
$dt = date("Y-m-d");
$item = $_REQUEST['itemname'];
//select items.id, items.itemname, pricing.mrp, pricing.pay, quantity.qty from items, pricing, quantity where itemname like '%$item%' and items.id=pricing.itemid and items.id = quantity.itemid
        $result_set = $this->model_all->getTableDataFromQuery("select items.id, items.itemname, pricing.mrp, pricing.pay, quantity.qty from items, pricing, quantity where itemname like '%$item%' and items.id = pricing.itemid and pricing.sellerid=quantity.sellerid and items.id=quantity.itemid");
        if ($result_set->num_rows() > 0) {
            $result["status"] = 1;
            $result["message"] = "Items Found";          
            foreach($result_set->result_array() as $row){
            $row["id"] = $row["id"];
            $row["itemname"] = $row["itemname"] ;
            $row["mrp"] = $row["mrp"];
            $row["pay"] = $row["pay"];
            $row["profit"] = $row["mrp"] - $row["pay"];
            $row["margin"] = ceil(($row["profit"] / $row["pay"]) * 100);
            $row["qty"] = $row["qty"];
            $result["items"][] = $row;  
            }
            $this->response($result, 200);
            exit;
        } else {
            $result["status"] = 0;
            $result["message"] = "No Items Found";
            $this->response($result, 200);
            exit;
        }
    }
    
    }
    
    ?>