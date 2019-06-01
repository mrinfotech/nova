<?php

require(APPPATH . '/libraries/REST_Controller.php');

class Packer_sellers extends REST_Controller {

    public function __construct() {
        parent::__construct();
        $this->load->model('model_all');
    }

    function indexOld_get() {

        $result_set = $this->model_all->getTableDataInArray("sellers", array('status' => 1), "id,first_name,last_name,email,mobile,latitude,longitude,address");

        if (($result_set['total_rows']) > 0) {
            $result["status"] = 1;
            $result["message"] = "Success";
            $result["sellers"] = $result_set['records'];
            $this->response($result, 200);
            exit;
        } else {
            $result["status"] = 0;
            $result["message"] = "No Sellers Found";
            $this->response($result, 200);
            exit;
        }
    }

    function index_get() {
        
        $dt = date("Y-m-d");
       

        $result_set = $this->model_all->getTableDataFromQuery("SELECT distinct se.id,se.first_name,se.last_name,se.email,se.mobile,se.latitude,se.longitude,se.address  from seller_invoices si, seller_items s,sellers se where si.seller_id=se.id and si.order_date<='$dt' and si.is_picked='1' and si.id=s.sellet_invoice_pk and s.qty!=0 and se.status='1' and si.generate='1' and si.packer_status='0'");
       
        if ($result_set->num_rows() > 0) {
            $result["status"] = 1;
            $result["message"] = "Records Found";
            $total_amount = 0;
            foreach ($result_set->result_array() as $row) {
                
                $result["sellers"][] = $row;
            }
           
            $this->response($result, 200);
            exit;
        } else {
            $result["status"] = 0;
            $result["message"] = "No List Found";
            $this->response($result, 200);
            exit;
        }
    }


     function invoicelist_get() {
        $seller = $this->get('seller');
        $dt = date("Y-m-d");
        //echo "SELECT items.itemname, items.brand,seller_items.id , seller_items.amount,  seller_items.qty FROM seller_items, items WHERE seller_items.seller_id =$primaryid AND seller_items.item_id = items.id AND seller_items.order_date='$dt'";

        $result_set = $this->model_all->getTableDataFromQuery("SELECT distinct si.id,si.order_date,si.invoice_id,si.is_picked from seller_invoices si, seller_items s where si.seller_id='$seller' and si.order_date<='$dt' and si.is_picked='1' and si.id=s.sellet_invoice_pk and s.qty!=0 and si.generate='1' and si.packer_status='0' "); // order by si.order_date desc,si.id desc
       
        if ($result_set->num_rows() > 0) {
            $result["status"] = 1;
            $result["message"] = "Records Found";
            $total_amount = 0;
            foreach ($result_set->result_array() as $row) {
                $row["order_date"] = date("d-m-Y",strtotime($row["order_date"]));
                
                $result["records"][] = $row;
            }
           
            $this->response($result, 200);
            exit;
        } else {
            $result["status"] = 0;
            $result["message"] = "No List Found";
            $this->response($result, 200);
            exit;
        }
    }

    
    
   }