<?php
require(APPPATH . '/libraries/REST_Controller.php');
class Delv_orders_check extends REST_Controller {

    private $dt;
    public function __construct() {
        parent::__construct();
        $this->load->model('model_all');
        $present_time = date("H:i");
        $this->dt = date("Y-m-d",strtotime("-1 day"));
        
    }

    function list_get() {
$orderid = $this->get('orderid');
//$dt = date("Y-m-d");
//echo "SELECT orders.order_value, items.itemname, order_items.qty FROM orders, items, order_items WHERE orders.order_id =  '$_REQUEST[orderid]' AND orders.id = order_items.orderid AND order_items.itemid = items.id AND orders.orderedon like '%$this->dt%'";
        $result_set = $this->model_all->getTableDataFromQuery("SELECT orders.order_value, items.itemname, order_items.qty FROM orders, items, order_items WHERE orders.id =  '$_REQUEST[orderid]' AND orders.id = order_items.orderid AND order_items.itemid = items.id AND order_items.created_on like '%$this->dt%'");        
        if ($result_set->num_rows() > 0) {
            $result["status"] = 1;
            $result["message"] = "Items Found";
            $result['orderid'] =  $orderid;                    
            foreach($result_set->result_array() as $row){ 
            $result["order_value"] = $row['order_value'];
            unset($row['order_value']); 
            $result["order_items"][] = $row;  
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