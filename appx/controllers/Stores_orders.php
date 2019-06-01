<?php
require(APPPATH . '/libraries/REST_Controller.php');
class Stores_orders extends REST_Controller {

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
$tommorow = date("Y-m-d",strtotime("+1 day"));
//echo "SELECT orders.id, orders.order_id, stores.name, stores.mobile, stores.latitude, stores.longitude, stores.address from orders, stores where orders.orderedby = stores.id and orders.status='ordered' and orders.orderedon >= '$this->dt 00:00:00' and orders.orderedon <= '$dt 18:00:00'";
        $result_set = $this->model_all->getTableDataFromQuery("SELECT orders.id, orders.order_id, stores.id as store_id,stores.name, stores.mobile, stores.latitude, stores.longitude, stores.address from orders, stores where orders.orderedby = stores.id and orders.status='Packed' and orders.orderedon < '$tommorow' and orders.received_by=0 order by orderedon desc");
        if ($result_set->num_rows() > 0) {
            $result["status"] = 1;
            $result["message"] = "Orders Found";          
            foreach($result_set->result_array() as $row){
            $row["id"] = $row["id"];
            $row["order_id"] = $row["order_id"] ;
            $row["name"] = $row["name"];
            $row["mobile"] = $row["mobile"]; 
            $row["latitude"] = $row["latitude"]; 
            $row["longitude"] = $row["longitude"];
            $row["address"] = $row["address"];  
            $result["stores"][] = $row;  
            }
            $this->response($result, 200);
            exit;
        } else {
            $result["status"] = 0;
            $result["message"] = "No Orders Found";
            $this->response($result, 200);
            exit;
        }
    }
    
    }
    
    ?>