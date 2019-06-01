<?php

require(APPPATH . '/libraries/REST_Controller.php');

class Delivery extends REST_Controller {
    private $dt;
    public function __construct() {
      
        parent::__construct();
        $this->load->model('model_all');
        $present_time = date("H:i");
        $this->dt = date("Y-m-d",strtotime("-1 day"));
    }
    
      function check_deliveryorders_get() {
        $orderid = $this->get('orderid');
        $result_set = $this->model_all->getTableDataFromQuery("SELECT orders.order_value, items.itemname, order_items.qty FROM orders, items, order_items WHERE orders.order_id =  '$_REQUEST[orderid]' AND orders.id = order_items.orderid AND order_items.itemid = items.id AND orders.orderedon like '%$this->dt%'");        
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
    
    
    
    function store_orders_get(){
        $primaryid = $this->get('primaryid');
        $dt = date("Y-m-d");
        $result_set = $this->model_all->getTableDataFromQuery("SELECT orders.id, orders.order_id, stores.name, stores.mobile, stores.latitude, stores.longitude, stores.address from orders, stores where orders.orderedby = stores.id and orders.status='Packed' and orders.orderedon >= '$this->dt 00:00:00' and orders.orderedon <= '$dt 18:00:00'");
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
    
    
    
    function order_summary_get(){
    
          $order = $this->get('order');
          $result_set= $this->model_all->getTableDataFromQuery("select  s.name,o.order_id,o.order_value from orders o,stores s where o.orderedby=s.id and o.id='$order'");
          if ($result_set->num_rows() > 0) {
              foreach($result_set->result_array() as $row){
                  $result = $row;
              }
              $result_set2= $this->model_all->getTableData("packed_bags","order_id='$order'","pb_id,bag_name");
              $result["status"] = 1;
              $result["message"] = "Orders Found";
              foreach($result_set2->result() as $row){
                $result["records"][] = $row;
              
              }
            $unit_count = $this->model_all->getTableDataFromQuery("SELECT SUM(case when pack_type = 'Bag' then 1 else 0 end) as bag_count,SUM(case when pack_type = 'Tin' then 1 else 0 end) as tin_count,SUM(case when pack_type = 'Case' then 1 else 0 end) as case_count  FROM `packed_bags` where order_id='$order'")->row_array();
            $result['bag_count'] = ($unit_count["bag_count"]!="")?$unit_count["bag_count"]:"0";
            $result['case_count'] = ($unit_count["tin_count"]!="")?$unit_count["tin_count"]:"0";
            $result['tin_count'] = ($unit_count["case_count"]!="")?$unit_count["case_count"]:"0";
              
              
              
              
          }else{
              $result["status"] = 0;
              $result["message"] = "No Records Found";
              $this->response($result, 200);
              exit;
          }
          
          
          
          
          
         
          $this->response($result, 200);
          
          
    
    
    
    }


    function dboy_order_summary_get() {

        $order = $this->get('order');
        $screen= $this->get('screen');  // Decided to  know from where it was from
        $result_set = $this->model_all->getTableDataFromQuery("select  s.name,o.order_id,o.order_value from orders o,stores s where o.orderedby=s.id and o.id='$order'");
        if ($result_set->num_rows() > 0) {
            foreach ($result_set->result_array() as $row) {
                $result = $row;
            }
            $result_set2 = $this->model_all->getTableData("packed_bags", "order_id='$order'", "pb_id,bag_name,barcode,status");
            $result["status"] = 1;
            $result["message"] = "Orders Found";
            foreach ($result_set2->result_array() as $row) {
                $row["barcode_img"] = base_url() . 'barcodes/barcode.png';
                if($screen=="R"){
                    $receiving_qry = $this->model_all->getTableDataFromQuery("select status from sdboy_receivings where pb_id='".$row["pb_id"]."' order by sd_id desc");
                    if($receiving_qry->num_rows()>0){
                        $receiving_row = $receiving_qry->row();
                        $row["status"]=$receiving_row->status;
                    }else{
                        $row["status"]=0;
                    }
                    
                            
                }
                
                $result["records"][] = $row;
            }
            
            
            $unit_count = $this->model_all->getTableDataFromQuery("SELECT SUM(case when pack_type = 'Bag' then 1 else 0 end) as bag_count,SUM(case when pack_type = 'Tin' then 1 else 0 end) as tin_count,SUM(case when pack_type = 'Case' then 1 else 0 end) as case_count  FROM `packed_bags` where order_id='$order'")->row_array();
            $result['bag_count'] = ($unit_count["bag_count"] != "") ? $unit_count["bag_count"] : "0";
            $result['case_count'] = ($unit_count["tin_count"] != "") ? $unit_count["tin_count"] : "0";
            $result['tin_count'] = ($unit_count["case_count"] != "") ? $unit_count["case_count"] : "0";
        } else {
            $result["status"] = 0;
            $result["message"] = "No Records Found";
            $this->response($result, 200);
            exit;
        }
      $this->response($result, 200);
    }
}
