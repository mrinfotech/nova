<?php

require(APPPATH . '/libraries/REST_Controller.php');

class Orders extends REST_Controller {

    public function __construct() {
        parent::__construct();
        $this->load->model('model_all');
    }

    //API - Fetch All Pincodes
    function list_get() {
        $user = $this->get('user');
        $fromdate = $this->get('fromdate');
        $todate = $this->get('todate');
        $status = $this->get('status');
        $condition = "orderedby='$user'";
        if ($status == "Cancelled") {
            $condition .= " and status='Cancelled'";
        } else if ($status == "Ordered") {
            $condition .= " and status='Ordered'";
        } else if ($status == "Delivered") {
            $condition .= " and status='Delivered' and delivery_accept='1' ";  // and delivery_recieved='1'
        } else if ($status == "Received") {
            $condition .= " and status='Delivered' and delivery_accept='1' ";
        } else if ($status == "Rejected") {
            $condition .= " and status='Delivered' and delivery_reject='1'";
        } else if ($status == "Pending") {
            $condition .= " and status not in('Cancelled','Delivered','Ordered')";
        } else if ($status == "track") {
            $condition .= " and status not in('Cancelled')";
        }

        if ($fromdate != "" && $todate == "") {
            $fromdate = date("Y-m-d", strtotime($fromdate));
            $condition .= " and date(`orderedon`)='$fromdate'";
        } else if ($fromdate != "" && $todate != "") {
            $fromdate = date("Y-m-d", strtotime($fromdate));
            $todate = date("Y-m-d", strtotime($todate));
            $condition .= " and `orderedon` between '$fromdate 00:00:00' and '$todate 23:59:59'";
        }
        $result_set = $this->model_all->getTableData("orders", $condition, "id,order_id,orderedon,status", "orderedon", "desc");
        //  echo $this->db->last_query();
        if ($result_set->num_rows() > 0) {
            $result["status"] = 1;
            $result["message"] = "Records Found";
            $result["total_records"] = $result_set->num_rows();
            foreach ($result_set->result_array() as $row) {
                $row['orderedon'] = date("d-m-Y", strtotime($row['orderedon']));
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

    function details_get() {
        $order = $this->get('order');

        $result_set = $this->model_all->getTableDataFromQuery("SELECT o.*, CONCAT(s.first_name,s.last_name)  as seller,i.itemname,i.brand,p.sellingprice FROM `order_items` o, items i ,sellers s,pricing p where i.id=o.itemid and s.id = o.sellerid and p.sellerid=s.id  and p.itemid = o.itemid and o.orderid='$order' ");
        if ($result_set->num_rows() > 0) {
            //echo "select s.name,s.address from stores s,orders o where s.id=o.orderedby and o=id='$order' ";

            $store_query = $this->model_all->getTableDataFromQuery("select s.name,a.address,o.status,o.est_date,o.est_time,o.delivery_charges,o.service_charge from stores s,orders o,addresses a where s.id=o.orderedby and o.id='$order' and a.user_id=s.id and a.user_role='store' and a.is_default='1'");
            $deliver_charges = 0.00;
            $total_sur_charge = 0.00;

            if ($store_rs = $store_query->row()) {
                $result["store_name"] = $store_rs->name;
                $result["order_status"] = $store_rs->status;
                $result["delivery_date"] = date("d-M-Y", strtotime($store_rs->est_date));
                $result["delivery_time"] = $store_rs->est_time;
                $deliver_charges = $store_rs->delivery_charges;
                $result["delivery_charges"] = $store_rs->delivery_charges;
                $total_sur_charge = $store_rs->service_charge;
                $result["store_address"] = $store_rs->address;
            }
            $result["status"] = 1;
            $result["message"] = "Records Found";
            $result["total_rows"] = $result_set->num_rows();
            $total_units = 0;
            $total_savings = 0;
            $total_pay = 0;
            foreach ($result_set->result_array() as $row) {
                $row['images'] = array();
                $image_qry = $this->model_all->getTableData("item_images", array("item" => $row['itemid']));
                foreach ($image_qry->result() as $img_rs) {
                    if ($img_rs->img_name != "") {
                        $file_headers = @get_headers(base_url() . 'item_pics/' . $img_rs->img_name);
                        if (stripos($file_headers[0], "404 Not Found") > 0 || (stripos($file_headers[0], "302 Found") > 0 && stripos($file_headers[7], "404 Not Found") > 0)) {
                            $picture = base_url() . 'item_pics/noimage.png';
                        } else {
                            $picture = base_url() . 'item_pics/' . $img_rs->img_name;
                        }
                    } else {
                        $picture = base_url() . "item_pics/noimage.png";
                    }
                    $row['images'][] = $picture;
                }
                $row['discount'] = ($row['mrp'] - $row['amount']);
                $row['total_price'] = ($row['qty'] * $row['amount']);
                $row['margin'] = round((($row['mrp'] - $row['amount']) / $row['amount']) * 100, 2);
                $total_units += $row['qty'];
                $total_savings += ($row['mrp'] - $row['amount']) * $row['qty'];
                $total_pay += ($row['qty'] * $row['amount']);
                // $total_sur_charge += 0.00;
                $result["records"][] = $row;
            }
            $result['total_units'] = $total_units;

            $result['total_savings'] = $total_savings;
            $result['total_sur_charge'] = $total_sur_charge;
            $result['sub_total'] = $total_pay;
            $result['total_pay'] = ($total_pay + $total_sur_charge + $deliver_charges);
            $this->response($result, 200);
            exit;
        } else {
            $result["status"] = 0;
            $result["message"] = "No records Found";
            $this->response($result, 200);
            exit;
        }
    }

    function cancel_put() {
        $order = $this->put('order');
        $reason = $this->put('reason');
        $dt = date("Y-m-d H:i:s");
        $status_qry = $this->model_all->getTableDataFromQuery("select * from order_track where order_id='$order' order by track_id desc limit 0,1")->row_array();
        if ($status_qry['order_status'] == "Cancelled") {
            $result["status"] = 0;
            $result["message"] = "Order already Cancelled.";
        } else if ($status_qry['order_status'] != "Ordered") {
            $result["status"] = 0;
            $result["message"] = "Order already Processed. So we cannot cancel this.";
        } else {

            $this->model_all->save(array("order_id" => $order, "order_status" => 'Cancelled', "changed_on" => $dt, "comments" => $reason), 'order_track');
            $this->model_all->update(array("status" => 'Cancelled'), array("id" => $order), 'orders');
           // echo "select * from seller_items where FIND_IN_SET($order,order_item )";
            
                $order_item_qry = $this->model_all->getTableDataFromQuery("select * from order_items where orderid='$order'");
               // echo $this->db->last_query();
//  and itemid='$item_rs->item_id' and sellerid='$item_rs->seller_id'
                foreach ($order_item_qry->result() as $order_rs) {
                   $seller_item_qry = $this->model_all->getTableDataFromQuery("select * from seller_items where FIND_IN_SET($order_rs->id,order_item)");
                   foreach ($seller_item_qry->result() as $item_rs) {
                     $qty = $item_rs->qty - $order_rs->qty;
                     $amount = $item_rs->amount - $order_rs->sp_amount;
                     $this->model_all->update(array("qty" => $qty, "amount" => $amount), array("id" => $item_rs->id), "seller_items");
                     //echo $this->db->last_query();
                   }
                }


            $result["status"] = 1;
            $result["message"] = "Order Cancelled Successfully";
        }

        $this->response($result, 200);
        exit;
    }

    function track_get() {
        $order = $this->get('order');
        $status_qry = $this->model_all->getTableData("orders", array("id" => $order), "status");
        // echo $this->db->last_query();
        if ($status_qry->num_rows() > 0) {
            $status_rs = $status_qry->row_array();
            $result["order_status"] = $status_rs['status'];
            $track_data = $this->model_all->getTableData('order_track', array("order_id" => $order), "order_status,changed_on,comments", "changed_on", "asc");
            foreach ($track_data->result_array() as $row) {
                $row['changed_on'] = date("d-m-Y h:i A", strtotime($row['changed_on']));
                $row['comments'] = ($row['comments'] != "") ? $row['comments'] : "";
                $result["track"][] = $row;
            }
            $result["status"] = 1;
            $result["message"] = "Records Found";
        } else {
            $result["status"] = 0;
            $result["message"] = "No Such order found";
        }



        $this->response($result, 200);
        exit;
    }

    //API - Fetch All Pincodes
    function daylist_get() {
        $condition = "";
        $fromdate = $this->get('fromdate');
        $todate = $this->get('todate');
        $status = $this->get('status');
        $store = $this->get('store');
        $total_cost = 0.00;

        if ($status != "") {
            if ($status == "Delivered") {
               $condition .= " and o.status='Delivered' and o.delivery_accept='1' ";  // and delivery_recieved='1'
            }else if ($status == "Rejected") {
               $condition .= " and o.status='Delivered' and o.delivery_reject='1'";
            }else{
               $condition .= " and o.status='$status'"; 
            }
           
        };
        

        if ($store != "")
            $condition .= " and o.orderedby='$store'";
        if ($fromdate != "" && $todate == "") {
            $fromdate = date("Y-m-d", strtotime($fromdate));
            if($status == "Delivered") {
              $condition .= " and o.`deliveredon` between '$fromdate 00:00:00' and '$fromdate 23:59:59'";
            }else{
               $condition .= " and o.`orderedon` between '$fromdate 00:00:00' and '$fromdate 23:59:59'";
            }
           
        } else if ($fromdate != "" && $todate != "") {
            $fromdate = date("Y-m-d", strtotime($fromdate));
            $todate = date("Y-m-d", strtotime($todate));
             if($status == "Delivered") {
              $condition .= " and o.`deliveredon` between '$fromdate 00:00:00' and '$todate 23:59:59'";
             }else{
              $condition .= " and o.`orderedon` between '$fromdate 00:00:00' and '$todate 23:59:59'";
             }
        } else if ($fromdate == "" && $todate == "") {

            $fromdate = date("Y-m-d");
            if($status == "Delivered") {
              $condition .= " and o.`deliveredon` between '$fromdate 00:00:00' and '$fromdate 23:59:59'";
            }else{
              $condition .= " and o.`orderedon` between '$fromdate 00:00:00' and '$fromdate 23:59:59'";
            }
            
        }
       // echo "select o.id,o.order_id,o.`orderedon`,s.name as store,o.order_value  from orders o,stores s where o.orderedby = s.id $condition";
        $result_set = $this->model_all->getTableDataFromQuery("select o.id,o.order_id,o.`orderedon`,s.name as store,o.order_value  from orders o,stores s where o.orderedby = s.id $condition");
        //echo $this->db->last_query();
        if ($result_set->num_rows() > 0) {
            $result["status"] = 1;
            $result["message"] = "Records Found";
            $result["total_records"] = $result_set->num_rows();
            foreach ($result_set->result_array() as $row) {
                $row['orderedon'] = date("d-m-Y", strtotime($row['orderedon']));
                $total_cost = $total_cost + $row['order_value'];
                $result["records"][] = $row;
            }
            $result["total_cost"] = "Rs " . $total_cost . " /-";

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
