<?php

require(APPPATH . '/libraries/REST_Controller.php');

class Deliveryboy extends REST_Controller {

    private $dt;

    public function __construct() {

        parent::__construct();
        $this->load->model('model_all');
        $present_time = date("H:i");
        $this->dt = date("Y-m-d", strtotime("-1 day"));
         $this->load->library('fcm');
    }

    function defineroute_post() {
        $order_str = $this->post('order_str');
        $emp_id = $this->post('emp_id');
        $latitude = $this->post('latitude');
        $longitude = $this->post('longitude');
        $ostore = 0;
        $dt = date("Y-m-d");
        $datetime = date("Y-m-d H:i:s");
        $result["status"] = 0;
        $result["message"] = "Something went wrong";
        $droute_order_id = 0;
        $list_arr = array();
        if ($order_str != "") {

            $list_arr = explode(",", $order_str);
            $data = array('emp_id' => $emp_id, 'deliver_date' => $dt, 'route_name' => 'Route', 'start_time' => $datetime, 'end_time' => '');
            $droute_id = $this->model_all->save($data, "delivery_route");
            if ($droute_id > 0) {
                $result["status"] = 1;
                $result["message"] = "Route Defined Successfully";
                for ($i = 0; $i < count($list_arr); $i++) {
                    $store_str = $list_arr[$i];
                    $object_arr = explode("~", $store_str);
                    if (count($object_arr) > 0) {

                        $store = $object_arr[0];

                        if (isset($object_arr[1])) {
                            $order = $object_arr[1];
                            if ($order == 1) {
                                $ostore = $store;
                            }

                            $order_array = array();
                            $order_str = "";
                            $result_set = $this->model_all->getTableDataFromQuery("SELECT orders.id from orders, stores,addresses where orders.address_id=addresses.id and orders.orderedby = stores.id and orders.status='Packed' and orders.received_by!=0 and stores.id='$store' ");
                            foreach ($result_set->result_array() as $order_row) {
                                $order_array[] = $order_row['id'];
                                $affected_rows = $this->model_all->update(array('status' => 'Dispatched'), array("id" => $order_row['id'], 'status' => 'Packed'), "orders");
                                if ($affected_rows > 0) {
                                    $this->model_all->save(array("order_id" => $order_row['id'], "order_status" => 'Dispatched', "changed_on" => $datetime), 'order_track');
                                }
                            }
                            if (count($order_array) > 0) {
                                $order_str = implode(",", $order_array);
                            }

                            $order_data = array('droute_id' => $droute_id, 'store_id' => $store, 'route_order' => $order, 'status' => '0', 'orders' => $order_str);
                            $insert_id = $this->model_all->save($order_data, "deliver_route_order");
                            if ($order == 1) {
                                $droute_order_id = $insert_id;
                            }
                        }
                    }
                }

                $dt = date("Y-m-d H:i:s");
                /* $track_data = array('store_id' => $ostore, 'route_order_id' => $droute_order_id, 'droute_id' => $droute_id, 'latitude' => $latitude, 'langitude' => $longitude, 'status' => '0', 'action_time' => $dt);
                  $this->model_all->save($track_data, "delivery_route_track"); */
            }
        }


        $this->response($result, 200);
        exit;
    }

    function editroute_post() {
        $order_str = $this->post('order_str');
        $action_flag = false;
        $result["status"] = 0;
        $result["message"] = "Something went wrong";
        $droute_order_id = 0;
        $list_arr = array();
        if ($order_str != "") {

            $list_arr = explode(",", $order_str);


            for ($i = 0; $i < count($list_arr); $i++) {
                $store_str = $list_arr[$i];
                $object_arr = explode("~", $store_str);
                if (count($object_arr) > 0) {

                    $id = $object_arr[0];

                    if (isset($object_arr[1])) {
                        $order = $object_arr[1];




                        $affected_rows = $this->model_all->update(array("route_order" => $order), array("id" => $id), "deliver_route_order");
                        if ($affected_rows) {
                            $action_flag = true;
                        }
                    }
                }
            }

            $dt = date("Y-m-d H:i:s");
        }

        if ($action_flag) {
            $result["status"] = 1;
            $result["message"] = "Route Defined Successfully";
        }

        $this->response($result, 200);
        exit;
    }

    function report_get() {
        $condition = "";
        $fromdate = $this->get('fromdate');
        $todate = $this->get('todate');
        $status = $this->get('status');
        $store = $this->get('store');
        $total_cost = 0.00;
        $result["status"] = 0;
        $result["message"] = "No records Found";
        if ($fromdate != "" && $todate == "") {
            $fromdate = date("Y-m-d", strtotime($fromdate));
            $condition .= " and date(o.`orderedon`)='$fromdate'";
        } else if ($fromdate != "" && $todate != "") {
            $fromdate = date("Y-m-d", strtotime($fromdate));
            $todate = date("Y-m-d", strtotime($todate));
            $condition .= " and o.`orderedon` between '$fromdate 00:00:00' and '$todate 23:59:59'";
        } else if ($fromdate == "" && $todate == "") {
            $fromdate = date("Y-m-d");
            $condition .= " and o.`orderedon` between '$fromdate 00:00:00' and '$fromdate 18:00:00'";
        }
        $result_set = $this->model_all->getTableDataFromQuery("select o.status,o.id,o.order_id,o.`orderedon`,s.name as store,s.address,o.order_value,(SELECT sum(oi.qty) FROM `order_items` oi where oi.orderid=o.id )as qty,(SELECT sum(oi.qty)-(IFNULL(sum(po.packed_qty),0)) as rem_qty FROM `order_items` oi LEFT JOIN packed_orders po on oi.id=po.order_item_id where oi.orderid=o.id ) as rem_qty  from orders o,stores s where o.orderedby = s.id $condition");
        if ($result_set->num_rows() > 0) {
            $result["status"] = 1;
            $result["message"] = "Records Found";
            $result["total_records"] = $result_set->num_rows();
            foreach ($result_set->result_array() as $row) {
                $row['orderedon'] = date("d-m-Y", strtotime($row['orderedon']));
                $total_cost = $total_cost + $row['order_value'];
                $unit_count = $this->model_all->getTableDataFromQuery("SELECT SUM(case when pack_type = 'Bag' then 1 else 0 end) as bag_count,SUM(case when pack_type = 'Tin' then 1 else 0 end) as tin_count,SUM(case when pack_type = 'Case' then 1 else 0 end) as case_count  FROM `packed_bags` where order_id='" . $row['id'] . "'")->row_array();
                $row['bag_count'] = ($unit_count["bag_count"] != "") ? $unit_count["bag_count"] : "0";
                $row['case_count'] = ($unit_count["tin_count"] != "") ? $unit_count["tin_count"] : "0";
                $row['tin_count'] = ($unit_count["case_count"] != "") ? $unit_count["case_count"] : "0";
                $row["status"] = $row['status'];
                if ($row["status"] == "Ordered") {
                    $row["status"] = "Not yet Processed";
                } else if ($row["status"] == "Packed") {
                    $row["status"] = "Processed";
                }
                $result["records"][] = $row;
            }
            $result["total_cost"] = "Rs " . $total_cost . " /-";
        }

        $this->response($result, 200);
        exit;
    }

    function routes_get() {
        $condition = "";
        $date = date("Y-m-d");
        $emp_id = $this->get('user');
        $result["status"] = 0;
        $result["message"] = "No records Found";
        $route_qry = $this->model_all->getTableData("delivery_route", array("deliver_date" => $date, "emp_id" => $emp_id));
        if ($route_qry->num_rows() > 0) {
            $result["status"] = 1;
            $result["message"] = "Records Found";
            $result["records"] = array();
            foreach ($route_qry->result() as $route_row) {
                $id = $route_row->id;
                $rorder_qry = $this->model_all->getTableDataFromQuery("select d.orders,d.status,d.route_order,s.id as store_id,s.name,s.mobile,s.latitude,s.longitude from deliver_route_order d, stores s where d.droute_id='$id' and d.store_id=s.id order by d.route_order");
                if ($rorder_qry->num_rows() > 0) {
                    foreach ($rorder_qry->result_array() as $rorder_row) {
                        $od_list = $rorder_row["orders"];
                        if ($od_list != "") {
                            $orders_qry = $this->model_all->getTableDataFromQuery("select o.id,o.order_id,o.order_value from orders o where o.id in ($od_list)");
                            if ($orders_qry->num_rows() > 0) {
                                $rorder_row["orders_count"] = $orders_qry->num_rows();
                                foreach ($orders_qry->result_array() as $order_row) {

                                    $rorder_row["orders_list"][] = $order_row;
                                }
                            } else {
                                $rorder_row["orders_list"] = array();
                            }
                        }

                        $result["records"][] = $rorder_row;
                    }
                }
            }
        }

        $this->response($result, 200);
        exit;
    }

    function stores_get() {
        $primaryid = $this->get('primaryid');
        $dt = date("Y-m-d");
        $stores = array();
       
        $result_set = $this->model_all->getTableDataFromQuery("SELECT orders.id, orders.order_id,orders.order_value, stores.id as store_id,stores.name, stores.mobile, addresses.latitude, addresses.longitude,addresses.locale, addresses.address from orders, stores,addresses where orders.orderedby = stores.id and   addresses.id=orders.address_id  and orders.status='Packed' and orders.orderedon <= '$dt 18:00:00'  ");
        if ($result_set->num_rows() > 0) {
            $result["status"] = 1;
            $result["message"] = "Orders Found";
            foreach ($result_set->result_array() as $row) {
                $object = array();
                if (!in_array($row["store_id"], $stores)) {
                    $stores[] = $row["store_id"];
                }
                $index = array_search($row["store_id"], $stores);
                if (!isset($result["records"][$index])) {
                    $object['id'] = $row["store_id"];
                    $object['name'] = $row["name"];
                    $object['mobile'] = $row["mobile"];
                    $object['address'] = $row["address"];
                    $object['latitude'] = $row["latitude"];
                    $object['longitude'] = $row["longitude"];
                    $result["records"][] = $object;
                }

                $order_object = array();
                $order_object["id"] = $row["id"];
                $order_object["order_id"] = $row["order_id"];
                $order_object["order_value"] = $row["order_value"];
                $result["records"][$index]['orders_list'][] = $order_object;
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

    function d_routes($emp_id) {
        $condition = "";
        $date = date("Y-m-d");

        $result["status"] = 0;
        $result["message"] = "No records Found";
        $result["is_define"] = 1;
        $result["total_records"] = 0;
        $result["status_count"] = 0;
        $route_qry = $this->model_all->getTableData("delivery_route", array("deliver_date" => $date, "emp_id" => $emp_id), "*");
        if ($route_qry->num_rows() > 0) {
            $result["status"] = 1;
            $result["message"] = "Records Found";
            $result["records"] = array();

            $status_count = 0;

            foreach ($route_qry->result() as $route_row) {
                $id = $route_row->id;
                $result["droute_id"] = $id;
                $rorder_qry = $this->model_all->getTableDataFromQuery("select d.id as pkey,d.droute_id,d.orders,d.status,d.route_order,s.id,s.name,s.mobile,s.latitude,s.longitude,s.address from deliver_route_order d, stores s where d.droute_id='$id' and d.store_id=s.id order by d.route_order");
                if ($rorder_qry->num_rows() > 0) {

                    $result["total_records"] = $route_qry->num_rows();
                    foreach ($rorder_qry->result_array() as $rorder_row) {
                        if ($rorder_row["status"] == 3) {
                            $status_count++;
                        }

                        $orders_qry = $this->model_all->getTableDataFromQuery("select o.id,o.order_id,o.order_value,o.status from orders o where o.orderedby='" . $rorder_row["id"] . "' and o.status='Packed'");
                        if ($orders_qry->num_rows() > 0) {
                            $rorder_row["orders_count"] = $orders_qry->num_rows();
                            foreach ($orders_qry->result_array() as $order_row) {

                                $rorder_row["orders_list"][] = $order_row;
                            }
                        } else {
                            $rorder_row["orders_list"] = array();
                        }


                        $result["records"][] = $rorder_row;
                    }
                }
            }
            $result["status_count"] = $status_count;
        }

        $this->response($result, 200);
        exit;
    }

    function d_stores($primaryid) {

        $dt = date("Y-m-d");
        $stores = array();
        $result["is_define"] = 0;
        $result["total_records"] = 0;
        $result["status_count"] = 0;

        $result_set = $this->model_all->getTableDataFromQuery("SELECT orders.id, orders.order_id,orders.order_value, stores.id as store_id,stores.name, stores.mobile, addresses.latitude, addresses.longitude,addresses.locale, addresses.address from orders, stores,addresses where orders.address_id=addresses.id and orders.orderedby = stores.id and orders.status='Packed' and orders.orderedon <= '$dt 23:59:59' ");
        if ($result_set->num_rows() > 0) {
            $result["status"] = 1;
            $result["message"] = "Orders Found";
            foreach ($result_set->result_array() as $row) {
                $object = array();
                if (!in_array($row["store_id"], $stores)) {
                    $stores[] = $row["store_id"];
                }
                $index = array_search($row["store_id"], $stores);
                if (!isset($result["records"][$index])) {
                    $object['id'] = $row["store_id"];
                    $object['name'] = $row["name"];
                    $object['mobile'] = $row["mobile"];
                    $object['address'] = $row["address"];
                    $object['latitude'] = $row["latitude"];
                    $object['longitude'] = $row["longitude"];
                    $object['droute_id'] = 0;
                    $object['pkey'] = 0;
                    $object['status'] = 0;
                    $object['route_order'] = 0;
                    $result["records"][] = $object;
                }

                $order_object = array();
                $order_object["id"] = $row["id"];
                $order_object["order_id"] = $row["order_id"];
                $order_object["order_value"] = $row["order_value"];
                $result["records"][$index]['orders_list'][] = $order_object;
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

    function stores_list_get() {
        $user = $this->get('user');
        $dt = date("Y-m-d");
        $results = $this->model_all->getTableDataFromQuery("select * from delivery_route where emp_id='$user' and deliver_date='$dt'");
        if ($results->num_rows() > 0) {
            $row = $results->row();
            
            if ($row->start_time != "0000-00-00 00:00:00" && $row->end_time != "0000-00-00 00:00:00") {
                $result["status"] = 0;
                $result["message"] = "No Records Found";
                $this->response($result, 200);
                
            } else {
                $this->d_routes($user);
            }

            
        } else {
            $this->d_stores($user);
        }
    }

    function do_action_post() {
        $action = $this->post('action');
        $user = $this->post('user');
        $pb_id = $this->post('id');
        $reason = $this->post('reason');
        $description = $this->post('description');
        $order = $this->post('order');
        $dt = date("Y-m-d H:i:s");
        $action_status = 0;
        $message = "Action not performed. Please try later";

        if ($action == 1) {
            $affected_rows = $this->model_all->update(array("status" => '1', "received_by" => $user, "received_time" => $dt, "reason" => $reason, "description" => $description), array("pb_id" => $pb_id), "sdboy_receivings");
            if ($affected_rows) {
                $message = "Item Received successfully";
                $action_status = 1;
            }
        } else if ($action == 2) {
            $img_name = "";
            if (isset($_FILES) && $_FILES['rej_img']['size'] > 0 && $_FILES['rej_img']['error'] == 0) {
                $name = "rej" . time() . "_" . $_FILES['rej_img']['name'];
                $source_url = $_FILES['rej_img']['tmp_name'];
                $destination_url = "rejections/" . $name;
                if (@move_uploaded_file($source_url, $destination_url)) {
                    $img_name = $name;
                } else {
                    $img_name = "";
                }
            }
            $affected_rows = $this->model_all->update(array("status" => '2', "received_by" => $user, "received_time" => $dt, "reason" => $reason, "description" => $description, "action_img" => $img_name), array("pb_id" => $pb_id), "sdboy_receivings");
            if ($affected_rows) {
                $message = "Item rejected successfully";
                $action_status = 1;
            }
        }
        $this->model_all->update(array("received_by" => $user), array("id" => $order), "orders");

        $result_set1 = $this->model_all->getTableDataFromQuery("SELECT p.pb_id FROM packed_bags p, sdboy_receivings s WHERE p.pb_id =s.pb_id  AND p.order_id='$order'");
        $result['total_records'] = $result_set1->num_rows();
        $result_set2 = $this->model_all->getTableDataFromQuery("SELECT p.pb_id FROM packed_bags p, sdboy_receivings s WHERE p.pb_id =s.pb_id  AND p.order_id='$order' and s.status!='0'");
        $result['total_processed'] = $result_set2->num_rows();




        $result["status"] = $action_status;
        $result["message"] = $message;
        $this->response($result, 200);
        exit;
    }

    function delivery_post() {
        $action = $this->post('action');
        $user = $this->post('user');
      //  $order = $this->post('id');
        $pb_id = $this->post('id');
        $reason = $this->post('reason');
        $description = $this->post('description');
        $dt = date("Y-m-d H:i:s");
        $order = $this->post('order');
        $order = $this->post('delivered_qty');
        $action_status = 0;
        $message = "Action not performed. Please try later";

        if ($action == 1) {
            $affected_rows = $this->model_all->update(array("status" => '1', "delivery_by" => $user, "action_dt" => $dt, "reason" => $reason, "description" => $description), array("pb_id" => $pb_id), "packed_bags");
            if ($affected_rows) {
                $message = "Item Delivered successfully";
                $action_status = 1;
            }
        } else if ($action == 2) {
            $img_name = "";
            if (isset($_FILES) && isset($_FILES['rej_img']) &&  $_FILES['rej_img']['size'] > 0 && $_FILES['rej_img']['error'] == 0) {
                $name = "rej" . time() . "_" . $_FILES['rej_img']['name'];
                $source_url = $_FILES['rej_img']['tmp_name'];
                $destination_url = "rejections/" . $name;
                if (@move_uploaded_file($source_url, $destination_url)) {
                    $img_name = $name;
                } else {
                    $img_name = "";
                }
            }
            $affected_rows = $this->model_all->update(array("status" => '2', "delivery_by" => $user, "action_dt" => $dt, "reason" => $reason, "description" => $description, "action_img" => $img_name), array("pb_id" => $pb_id), "packed_bags");
            if ($affected_rows) {
                $message = "Item rejected successfully";
                $action_status = 1;
            }
        }

        $result_set1 = $this->model_all->getTableDataFromQuery("SELECT p.pb_id FROM packed_bags p WHERE  p.order_id='$order'");
        $result['total_records'] = $result_set1->num_rows();
        $result_set2 = $this->model_all->getTableDataFromQuery("SELECT p.pb_id FROM packed_bags p WHERE  p.order_id='$order' and p.status!='0'");
        $result['total_processed'] = $result_set2->num_rows();
        if($result_set1->num_rows()==$result_set2->num_rows()){
            
        }




        $result["status"] = $action_status;
        $result["message"] = $message;

        $this->response($result, 200);
        exit;
    }

    function route_process_post() {
        $id = $this->post('pkey');
        $droute_id = $this->post('droute_id');
        $latitude = $this->post('latitude');
        $longitude = $this->post('langitude');
        $status = $this->post('status');
        $store = $this->post('store');
        $insert_id = 0;
        $dt = date("Y-m-d H:i:s"); 
        $order = 0;
        $emp_id = 0;
        $track_data = array('store_id' => $store, 'route_order_id' => $id, 'droute_id' => $droute_id, 'latitude' => $latitude, 'langitude' => $longitude, 'status' => $status, 'action_time' => $dt);
        $action_status = "0";
        $message = "Action not done. Please try later.";



        if ($this->model_all->getTableData("delivery_route_track", array("droute_id" => $droute_id, "route_order_id" => $id, "status" => $status))->num_rows() == 0) {
            $insert_id = $this->model_all->save($track_data, "delivery_route_track");
            $route_total_rows = $this->model_all->getTableData("deliver_route_order", array("droute_id" => $droute_id))->num_rows();
            $route_qry = $this->model_all->getTableDataFromQuery("select d.route_order,d.*,dr.emp_id from deliver_route_order d,delivery_route dr where d.id='$id' and dr.id=d.droute_id and dr.id='$droute_id'");
            foreach ($route_qry->result() as $route_rs) {
                $order = $route_rs->orders;
                $emp_id = $route_rs->emp_id;
                if ($route_rs->route_order == 1 && $status == 1) {
                    $this->model_all->update(array("start_time" => $dt), array("id" => $droute_id), "delivery_route");
                }
            }

            if ($status == 3) {
                if ($order != "") {
                    $this->model_all->getTableDataFromQuery("update seller_orders set status='Delivered',deliveredby='$emp_id',deliveredon='$dt',dboy_accept='1' where id in ($order)");
                    $order_arr = explode(",",$order);
                    for($a=0;$a<count($order_arr);$a++){
                     $this->model_all->save(array("order_id" => $order[$a], "order_status" => 'Delivered', "changed_on" => $dt), 'seller_order_track');
                
                    }
                }
            }
            if ($status == 4) {    // $route_rs->route_order==$route_total_rows && $status==3
                $this->model_all->update(array("end_time" => $dt), array("id" => $droute_id), "delivery_route");
                             
            }
        } else {
            $action_status = "1";
            $message = "Status already defined";
        }




        if ($insert_id > 0) {
            $action_status = 1;
            if ($status != 4)
                $this->model_all->update(array("status" => $status), array("id" => $id), "deliver_route_order");
            $message = "Action done Successfully";
        }



        $result["status"] = $action_status;
        $result["message"] = $message;
        $this->response($result, 200);
        exit;
    }

    function dboy_route_summary_get() {

        $user = $this->get('user');
        $id = $this->get('pkey');
        $dt = date("Y-m-d");
        $action_status = 0;
        $message = "No Records Found";
        $total_cost = 0;
        $total_processed = 0;
        $total_records = 0;
       
        $dRoute_set = $this->model_all->getTableDataFromQuery("select * from deliver_route_order where id='$id'");
        if ($dRoute_set->num_rows() > 0) {
            foreach ($dRoute_set->result() as $row) {
                $orders = $row->orders;
                $message = "Records Found";
                if ($orders != "") {
//echo "select  o.id,s.name,o.order_id,o.order_value from orders o,stores s where o.orderedby=s.id and o.id in('$orders')";
                    $result_set = $this->model_all->getTableDataFromQuery("select  o.id,s.name,o.order_id,o.order_value from orders o,stores s where o.orderedby=s.id and o.id in($orders)");
                    if ($result_set->num_rows() > 0) {

                        $action_status = 1;
                        foreach ($result_set->result_array() as $order_row) {
                            $total_cost = $total_cost + $order_row["order_value"];
                            $order_row["items"] = array();
                            $order = $order_row["id"];
                            $result_set2 = $this->model_all->getTableData("packed_bags", "order_id='$order'", "pb_id,bag_name,barcode,status");
                            $total_records = $total_records + $result_set2->num_rows();

                            foreach ($result_set2->result_array() as $item_row) {
                                $item_row["barcode_img"] = "";
                                $file_headers = @get_headers(base_url() . 'barcodes/' . $item_row["barcode"] . ".jpg");
                                if (stripos($file_headers[0], "404 Not Found") > 0 || (stripos($file_headers[0], "302 Found") > 0 && stripos($file_headers[7], "404 Not Found") > 0)) {
                                    
                                } else {
                                    $item_row["barcode_img"] = base_url() . 'barcodes/' . $item_row["barcode"] . ".jpg";
                                }
                                if ($item_row["status"] != 0) {
                                    $total_processed++;
                                }


                                $order_row["items"][] = $item_row;
                            }
                            $result["records"][] = $order_row;
                        }
                    }
                }
            }
        }

        $result["status"] = $action_status;
        $result["total_processed"] = $total_processed;
        $result["total_records"] = $total_records;
        $result["total_cost"] = $total_cost;
        $result["message"] = $message;
        $this->response($result, 200);
        exit;
    }

    function deliveryreport_get() {
        $condition = "";
        $fromdate = $this->get('fromdate');
        $todate = $this->get('todate');
        $status = $this->get('status');
        $store = $this->get('store');
        $user = $this->get('user');

        $total_cost = 0.00;
        $result["status"] = 0;
        $result["message"] = "No records Found";
        if ($fromdate != "" && $todate == "") {
            $fromdate = date("Y-m-d", strtotime($fromdate));
            $condition .= " and date(o.`orderedon`)='$fromdate'";
        } else if ($fromdate != "" && $todate != "") {
            $fromdate = date("Y-m-d", strtotime($fromdate));
            $todate = date("Y-m-d", strtotime($todate));
            $condition .= " and o.`orderedon` between '$fromdate 00:00:00' and '$todate 23:59:59'";
        } else if ($fromdate == "" && $todate == "") {
            $fromdate = date("Y-m-d");
            $condition .= " and o.`orderedon` <= '$fromdate 23:59:59'";
        }

        if ($status == 0) {
            $condition .= " and o.received_by ='$user'";
        } else if ($status == 1) {
            $condition .= " and o.seller_accept='1'";
        } else if ($status == 2) {
            $condition .= " and o.seller_accept='1' and delivery_reject='1'";
        }
        $result_set = $this->model_all->getTableDataFromQuery("select o.status,o.id,o.order_id,o.`orderedon`,s.name as store,s.address,o.order_value,(SELECT sum(oi.qty) FROM `order_items` oi where oi.orderid=o.id )as qty,(SELECT sum(oi.qty)-(IFNULL(sum(po.packed_qty),0)) as rem_qty FROM `order_items` oi LEFT JOIN packed_orders po on oi.id=po.order_item_id where oi.orderid=o.id ) as rem_qty  from orders o,stores s where o.orderedby = s.id $condition");
        if ($result_set->num_rows() > 0) {
            $result["status"] = 1;
            $result["message"] = "Records Found";
            $result["total_records"] = $result_set->num_rows();
            foreach ($result_set->result_array() as $row) {
                $row['orderedon'] = date("d-m-Y", strtotime($row['orderedon']));
                $total_cost = $total_cost + $row['order_value'];
                $unit_count = $this->model_all->getTableDataFromQuery("SELECT SUM(case when pack_type = 'Bag' then 1 else 0 end) as bag_count,SUM(case when pack_type = 'Tin' then 1 else 0 end) as tin_count,SUM(case when pack_type = 'Case' then 1 else 0 end) as case_count  FROM `packed_bags` where order_id='" . $row['id'] . "'")->row_array();
                $row['bag_count'] = ($unit_count["bag_count"] != "") ? $unit_count["bag_count"] : "0";
                $row['case_count'] = ($unit_count["tin_count"] != "") ? $unit_count["tin_count"] : "0";
                $row['tin_count'] = ($unit_count["case_count"] != "") ? $unit_count["case_count"] : "0";
                $row["status"] = $row['status'];
                if ($row["status"] == "Ordered") {
                    $row["status"] = "Not yet Processed";
                } else if ($row["status"] == "Packed") {
                    $row["status"] = "Processed";
                } else if ($row["status"] == "Delivered") {
                    $row["status"] = "Delivered";
                }
                $result["records"][] = $row;
            }
            $result["total_cost"] = "Rs " . $total_cost . " /-";
        }

        $this->response($result, 200);
        exit;
    }


    function pickreport_get() {
        $condition = "";
        $fromdate = $this->get('fromdate');
        $todate = $this->get('todate');
        $status = $this->get('status');
        $order = $this->get('order');

        $user = $this->get('user');
        $token = $this->get('token');
        $total_cost = 0.00;
        $result["status"] = 0;
        $result["message"] = "No records Found";
        if ($fromdate != "" && $todate == "") {
            $fromdate = date("Y-m-d", strtotime($fromdate));
            $condition .= " and date(s.`received_time`)='$fromdate'";
        } else if ($fromdate != "" && $todate != "") {
            $fromdate = date("Y-m-d", strtotime($fromdate));
            $todate = date("Y-m-d", strtotime($todate));
            $condition .= " and s.`received_time` between '$fromdate 00:00:00' and '$todate 23:59:59'";
        } else if ($fromdate == "" && $todate == "") {
            $fromdate = date("Y-m-d");
            $condition .= " and s.`received_time` between '$fromdate 00:00:00' and '$fromdate 23:59:59'";
        }


        if ($status != "") {
            $condition .= " and s.status='$status'";
        }

        if ($token == "") {

            $result_set = $this->model_all->getTableDataFromQuery("select p.pb_id ,p.bag_name,p.barcode, o.id, o.order_id,o.order_value, st.id as store_id,st.name,st.mobile from  packed_bags p,orders o,sdboy_receivings s,stores st where s.received_by='$user' and p.pb_id=s.pb_id and p.order_id=o.id and  o.orderedby = st.id $condition order by o.orderedon desc");
            if ($result_set->num_rows() > 0) {
                $result["status"] = 1;
                $result["message"] = "Orders Found";
                $orders = array();
                $records = array();
                foreach ($result_set->result_array() as $row) {

                    $object = array();
                    $object["bag_name"] = $row['bag_name'];
                    $object["barcode"] = $row['barcode'];
                    $object["pb_id"] = $row['pb_id'];
                    $object["barcode_img"] = '';
                    if ($row["barcode"] != "") {
                        $file_headers = @get_headers(base_url() . 'barcodes/' . $row["barcode"] . ".jpg");
                        if (stripos($file_headers[0], "404 Not Found") > 0 || (stripos($file_headers[0], "302 Found") > 0 && stripos($file_headers[7], "404 Not Found") > 0)) {
                            
                        } else {
                            $object["barcode_img"] = base_url() . 'barcodes/' . $row["barcode"] . ".jpg";
                        }
                    }


                    if (isset($row['bag_name']))
                        unset($row['bag_name']);
                    if (isset($row['barcode']))
                        unset($row['barcode']);
                    if (isset($row['pb_id']))
                        unset($row['pb_id']);




                    if (in_array($row["id"], $orders)) {
                        
                    } else {
                        $orders[] = $row["id"];
                        $records[] = $row;
                    }

                    $index = array_search($row["id"], $orders);

                    $records[$index]["records"][] = $object;
                }
                $result["stores"] = $records;
            }
            $this->response($result, 200);
            exit;
        } else {
            if ($order != "") {
                $condition .= " and o.id='$order'";
            }

            $result_set = $this->model_all->getTableDataFromQuery("select distinct o.id, o.order_id,o.order_value, st.id as store_id,st.name,st.mobile from  packed_bags p,orders o,sdboy_receivings s,stores st where s.received_by='$user' and p.pb_id=s.pb_id and p.order_id=o.id and  o.orderedby = st.id $condition");
            if ($result_set->num_rows() > 0) {
                $result["status"] = 1;
                $result["message"] = "Orders Found";
                $orders = array();
                $records = array();
                foreach ($result_set->result_array() as $row) {

                    $result["name"] = $row["name"];
                    $result["order_id"] = $row["order_id"];
                    $result["order_value"] = $row["order_value"];
                    $pack_set = $this->model_all->getTableDataFromQuery("select p.pb_id ,p.bag_name,p.barcode from  packed_bags p,orders o,sdboy_receivings s,stores st where s.received_by='$user' and p.pb_id=s.pb_id and p.order_id=o.id and  o.orderedby = st.id $condition");
                    $object = array();
                    foreach ($pack_set->result_array() as $pack_row) {

                        $pack_row["barcode_img"] = '';
                        if ($pack_row["barcode"] != "") {
                            $file_headers = @get_headers(base_url() . 'barcodes/' . $pack_row["barcode"] . ".jpg");
                            if (stripos($file_headers[0], "404 Not Found") > 0 || (stripos($file_headers[0], "302 Found") > 0 && stripos($file_headers[7], "404 Not Found") > 0)) {
                                
                            } else {
                                $pack_row["barcode_img"] = base_url() . 'barcodes/' . $pack_row["barcode"] . ".jpg";
                            }
                        }





                        $result["records"][] = $pack_row;
                    }
                }
            }


            $this->response($result, 200);
            exit;
        }
    }

    function deliveryorders_details_get() {

        $order = $this->get('order');
        $user = $this->get('user');
        $status = $this->get('status');
        if ($status == 1) {
            $result_set2 = $this->model_all->getTableData("packed_bags", "order_id='$order' and delivery_by='$user' and status='$status'", "pb_id,bag_name,barcode,status");
            if ($result_set2->num_rows() > 0) {
                $result["status"] = 1;
                $result["message"] = "Records Found";
                foreach ($result_set2->result_array() as $row) {
                    $result["records"][] = $row;
                }
                $this->response($result, 200);
                exit;
            } else {
                $result["status"] = 0;
                $result["message"] = "No Records Found";
                $this->response($result, 200);
                exit;
            }
        } else if ($status == 2) {
            $result_set2 = $this->model_all->getTableDataFromQuery("SELECT o.*, CONCAT(s.first_name,s.last_name)  as seller,i.itemname,i.brand,p.sellingprice FROM `order_items` o, items i ,sellers s,pricing p where i.id=o.itemid and s.id = o.sellerid and p.sellerid=s.id  and p.itemid = o.itemid and o.orderid='$order' and (o.action_status='2' or (o.action_status='1' and o.qty<o.picked_qty))");
            if ($result_set2->num_rows() > 0) {
                $result["status"] = 1;
                $result["message"] = "Records Found";
                foreach ($result_set2->result_array() as $row) {
                    $result["records"][] = $row;
                }
                $this->response($result, 200);
                exit;
            } else {
                $result["status"] = 0;
                $result["message"] = "No Records Found";
                $this->response($result, 200);
                exit;
            }
        }
    }

     //API - Fetch All Pincodes
    function list_get() {
        $user = $this->get('user');
        $fromdate = $this->get('fromdate');
        $todate = $this->get('todate');
        $status = $this->get('status');
       
       
        if ($status == "Cancelled") {
            $condition .= " and status='Cancelled'";
        } else if ($status == "Ordered") {
            $condition .= " and status='Ordered'";
        } else if ($status == "Delivered") {
            $condition .= " and status='Delivered' and seller_accept='1' ";  // and delivery_recieved='1'
        } else if ($status == "Received") {
            $condition .= " and status='Delivered' and dboy_accept='1' ";
        } else if ($status == "Rejected") {
            $condition .= " and status='Delivered' and seller_accept='2'";
        } else if ($status == "Pending") {
            $condition .= " and status not in('Cancelled','Delivered','Ordered')";
        } else if ($status == "track") {
            $condition .= " and status not in('Cancelled')";
        } else{
            $condition .= " and status in ('Packed')";
        }

        $branch = $this->get('branch');
        if($branch != "") {
            $condition .= " and branch_id='$branch'";
        }

        if ($fromdate != "" && $todate == "") {
            $fromdate = date("Y-m-d", strtotime($fromdate));
            $condition .= " and date(`orderedon`)='$fromdate'";
        } else if ($fromdate != "" && $todate != "") {
            $fromdate = date("Y-m-d", strtotime($fromdate));
            $todate = date("Y-m-d", strtotime($todate));
            $condition .= " and `orderedon` between '$fromdate 00:00:00' and '$todate 23:59:59'";
        }
        $result_set = $this->model_all->getTableData("seller_orders", $condition, "id,order_id,orderedon,status", "orderedon", "desc");
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


         //API - Fetch All Pincodes
    function dealers_get() {
         
         $branch = $this->get('branch');
         $dt = date("Y-m-d");
         $tommorow = date("Y-m-d",strtotime("+1 day"));
         $branch_str="";
         if($branch!=""){
            $branch_str =" and o.branch_id='$branch'";
         }
         $query = "SELECT o.id, o.order_id, s.id as store_id,CONCAT(s.first_name,s.last_name) as name, s.mobile, s.latitude, s.longitude, s.address from seller_orders o, sellers s where o.orderedby = s.id and o.status='Packed' and  o.received_by=0 $branch_str order by o.orderedon desc";
         $result_set = $this->model_all->getTableDataFromQuery($query);
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
    

 /*  Dealer Related Queries     */
    
    function order_summary_get() {

        $order = $this->get('order');
        $screen= $this->get('screen');  // Decided to  know from where it was from
        $result_set = $this->model_all->getTableDataFromQuery("select  CONCAT(s.first_name,s.last_name) as name,o.order_id,o.order_value from seller_orders o,sellers s where o.orderedby=s.id and o.id='$order'");
        if ($result_set->num_rows() > 0) {
            foreach ($result_set->result_array() as $row) {
                $result = $row;
            }
            $result_set2 = $this->model_all->getTableData("packed_bags", "order_id='$order' and order_from='seller'", "pb_id,bag_name,barcode,status");
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
    
    
    function so_pickreport_get() {
        $condition = "";
        $fromdate = $this->get('fromdate');
        $todate = $this->get('todate');
        $status = $this->get('status');
        $order = $this->get('order');

        $user = $this->get('user');
        $token = $this->get('token');
        $total_cost = 0.00;
        $result["status"] = 0;
        $result["message"] = "No records Found";
        if ($fromdate != "" && $todate == "") {
            $fromdate = date("Y-m-d", strtotime($fromdate));
            $condition .= " and date(s.`received_time`)='$fromdate'";
        } else if ($fromdate != "" && $todate != "") {
            $fromdate = date("Y-m-d", strtotime($fromdate));
            $todate = date("Y-m-d", strtotime($todate));
            $condition .= " and s.`received_time` between '$fromdate 00:00:00' and '$todate 23:59:59'";
        } else if ($fromdate == "" && $todate == "") {
            $fromdate = date("Y-m-d");
            $condition .= " and s.`received_time` between '$fromdate 00:00:00' and '$fromdate 23:59:59'";
        }


        if ($status != "") {
            $condition .= " and s.status='$status'";
        }

        if ($token == "") {

            $result_set = $this->model_all->getTableDataFromQuery("select p.pb_id ,p.bag_name,p.barcode, o.id, o.order_id,o.order_value, st.id as store_id,st.company_name as name,st.mobile from  packed_bags p,seller_orders o,sdboy_receivings s,sellers st where s.received_by='$user' and p.pb_id=s.pb_id and p.order_id=o.id and  o.orderedby = st.id $condition order by o.orderedon desc");
            if ($result_set->num_rows() > 0) {
                $result["status"] = 1;
                $result["message"] = "Orders Found";
                $orders = array();
                $records = array();
                foreach ($result_set->result_array() as $row) {

                    $object = array();
                    $object["bag_name"] = $row['bag_name'];
                    $object["barcode"] = $row['barcode'];
                    $object["pb_id"] = $row['pb_id'];
                    $object["barcode_img"] = '';
                    if ($row["barcode"] != "") {
                        $file_headers = @get_headers(base_url() . 'barcodes/' . $row["barcode"] . ".jpg");
                        if (stripos($file_headers[0], "404 Not Found") > 0 || (stripos($file_headers[0], "302 Found") > 0 && stripos($file_headers[7], "404 Not Found") > 0)) {
                            
                        } else {
                            $object["barcode_img"] = base_url() . 'barcodes/' . $row["barcode"] . ".jpg";
                        }
                    }


                    if (isset($row['bag_name']))
                        unset($row['bag_name']);
                    if (isset($row['barcode']))
                        unset($row['barcode']);
                    if (isset($row['pb_id']))
                        unset($row['pb_id']);




                    if (in_array($row["id"], $orders)) {
                        
                    } else {
                        $orders[] = $row["id"];
                        $records[] = $row;
                    }

                    $index = array_search($row["id"], $orders);

                    $records[$index]["records"][] = $object;
                }
                $result["stores"] = $records;
            }
            $this->response($result, 200);
            exit;
        } else {
            if ($order != "") {
                $condition .= " and o.id='$order'";
            }

            $result_set = $this->model_all->getTableDataFromQuery("select distinct o.id, o.order_id,o.order_value, st.id as store_id,st.company_name as name,st.mobile from  packed_bags p,seller_orders o,sdboy_receivings s,sellers st where s.received_by='$user' and p.pb_id=s.pb_id and p.order_id=o.id and  o.orderedby = st.id $condition");
            if ($result_set->num_rows() > 0) {
                $result["status"] = 1;
                $result["message"] = "Orders Found";
                $orders = array();
                $records = array();
                foreach ($result_set->result_array() as $row) {

                    $result["name"] = $row["name"];
                    $result["order_id"] = $row["order_id"];
                    $result["order_value"] = $row["order_value"];
                    $pack_set = $this->model_all->getTableDataFromQuery("select p.pb_id ,p.bag_name,p.barcode from  packed_bags p,seller_orders o,sdboy_receivings s,sellers st where s.received_by='$user' and p.pb_id=s.pb_id and p.order_id=o.id and  o.orderedby = st.id $condition");
                    $object = array();
                    foreach ($pack_set->result_array() as $pack_row) {

                        $pack_row["barcode_img"] = '';
                        if ($pack_row["barcode"] != "") {
                            $file_headers = @get_headers(base_url() . 'barcodes/' . $pack_row["barcode"] . ".jpg");
                            if (stripos($file_headers[0], "404 Not Found") > 0 || (stripos($file_headers[0], "302 Found") > 0 && stripos($file_headers[7], "404 Not Found") > 0)) {
                                
                            } else {
                                $pack_row["barcode_img"] = base_url() . 'barcodes/' . $pack_row["barcode"] . ".jpg";
                            }
                        }





                        $result["records"][] = $pack_row;
                    }
                }
            }


            $this->response($result, 200);
            exit;
        }
    }
    
    
      function dealers_list_get() {
        $user = $this->get('user');
        $branch = $this->get('branch');
        $dt = date("Y-m-d");
        $this->d_dealers($user,$branch);
       /* $results = $this->model_all->getTableDataFromQuery("select * from delivery_route where emp_id='$user' and deliver_date='$dt'");
        if ($results->num_rows() > 0) {
            $row = $results->row();
            if ($row->start_time != "0000-00-00 00:00:00" && $row->end_time != "0000-00-00 00:00:00") {
              
                $result["status"] = 0;
                $result["message"] = "No Records Found";
                $this->response($result, 200);
                
            } else {
                $this->so_dbody_routes($user);
            }

            
        } else {
            $this->d_dealers($user,$branch);
        }*/
    }
    
    
    
    function d_dealers($primaryid,$branch) {

        $dt = date("Y-m-d");
        $stores = array();
        $result["is_define"] = 0;
        $result["total_records"] = 0;
        $result["status_count"] = 0;
      
        $result_set = $this->model_all->getTableDataFromQuery("SELECT o.id,o.transport_id,o.order_id,o.order_value,o.final_value, s.contact1,s.id as store_id,s.company_name as name, s.mobile, a.door_no, a.street_name, a.city, d.district, st.state, a.latitude, a.longitude,a.locale, a.address, d.district, st.state from seller_orders o, sellers s,addresses a, states st, districts d where o.address_id=a.id and o.orderedby = s.id and a.state=st.id and d.id=a.district  and d.state=st.id and  o.status in ('Packed','Dispatched') and o.orderedon <= '$dt 23:59:59' and o.branch_id='$branch'");
        if ($result_set->num_rows() > 0) {
            $result["status"] = 1;
            $result["message"] = "Orders Found";
            foreach ($result_set->result_array() as $row) {
                
$transport_id =  $row["transport_id"];               
if($row["door_no"]!="NA"){
$address= $row["door_no"].',';
}
else{
$address="";
}
if($row["street_name"]!="NA"){
$address = $address.''.$row["street_name"].',';
}
if($row["city"]!="NA"){
$address = $address.''.$row["city"].',';
}
if($row["district"]!="NA"){
$address = $address.''.$row["district"].',';
}
if($row["state"]!="NA"){
$address = $address.''.$row["state"];
}

$address = trim($address,",");

                $object = array();
                if (!in_array($row["store_id"], $stores)) {
                    $stores[] = $row["store_id"];
                }
                $index = array_search($row["store_id"], $stores);
                if (!isset($result["records"][$index])) {
                    $object['id'] = $row["store_id"];
                    $object['name'] = $row["name"];
                    $object['mobile'] = "NA";
                    if($row["mobile"]!=0 && $row["mobile"]!="NA"){

                         $object['mobile'] = $row["mobile"];
                    }else if($row["contact1"]!=0 && $row["contact1"]!="NA"){
                      
                         $object['mobile'] = $row["contact1"];
                    }
                    $object['address'] = $address;
                    $object['latitude'] = $row["latitude"];
                    $object['longitude'] = $row["longitude"];
                    $object['droute_id'] = 0;
                    $object['pkey'] = 0;
                    $object['status'] = 0;
                    $object['route_order'] = 0;
                    $object["route_define"] = 0;
                    $object["total_orders"] =0;
                    $result["records"][] = $object;
                    
                }
                $result["records"][$index]["total_orders"]++;
                $order_object = array();
                $order =  $row["id"];
                $order_object["id"] = $row["id"];
                $order_object["order_id"] = $row["order_id"];
                $order_object["order_value"] =   $row["final_value"];    // $row["order_value"]; For Transfer Purpose
                $req_qry = $this->model_all->getTableDataFromQuery("select v.contact,t.transport_type,t.name,t.id from delivery_vehicles v, transport t where v.transport=t.id and FIND_IN_SET($order,v.orders)");
                if ($req_qry->num_rows() > 0) {
                   
                    $req_rs = $req_qry->row();
                    $order_object["is_define"] = 1;
                    $order_object["transport_id"] = $req_rs->id;
                    if ($req_rs->transport_type == "private") {
                        $order_object["transport_name"] = "Private";
                    } else {
                        $order_object["transport_name"] = ucwords($req_rs->name);
                    }
                    $order_object["contact"] = $req_rs->contact;
                     $result["records"][$index]["route_define"]++;
                } else {
                    $order_object["is_define"] = 0;
                    $order_object["transport_id"] = $transport_id;
                    if($transport_id==0){
                        $transport =""; 
                    }else{
                        $transport = $this->model_all->tableFieldData("select name from transport where id='$transport_id'","name");
                    }
                    $order_object["transport_name"] = $transport;
                    $order_object["contact"] ="";
                }

           


                $result["records"][$index]['orders_list'][] = $order_object;
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

     function so_dbody_routes($emp_id) {
        $condition = "";
        $date = date("Y-m-d");

        $result["status"] = 0;
        $result["message"] = "No records Found";
        $result["is_define"] = 1;
        $result["total_records"] = 0;
        $result["status_count"] = 0;
        $route_qry = $this->model_all->getTableData("delivery_route", array("deliver_date" => $date, "emp_id" => $emp_id), "*");

        if ($route_qry->num_rows() > 0) {
            $result["status"] = 1;
            $result["message"] = "Records Found";
            $result["records"] = array();

            $status_count = 0;

            foreach ($route_qry->result() as $route_row) {
                $id = $route_row->id;
                $result["droute_id"] = $id;

                $rorder_qry = $this->model_all->getTableDataFromQuery("select d.id as pkey,d.droute_id,d.orders,d.status,d.route_order,s.id,s.company_name as name ,s.mobile,s.latitude,s.longitude,s.address from deliver_route_order d, sellers s where d.droute_id='$id' and d.store_id=s.id order by d.route_order");
                if ($rorder_qry->num_rows() > 0) {

                    $result["total_records"] = $route_qry->num_rows();
                    foreach ($rorder_qry->result_array() as $rorder_row) {
                        if ($rorder_row["status"] == 3) {
                            $status_count++;
                        }

                        $orders_qry = $this->model_all->getTableDataFromQuery("select o.id,o.order_id,o.order_value,o.status from seller_orders o where o.orderedby='" . $rorder_row["id"] . "' and o.status='Packed'");
                        if ($orders_qry->num_rows() > 0) {
                            $rorder_row["orders_count"] = $orders_qry->num_rows();
                            foreach ($orders_qry->result_array() as $order_row) {

                                $rorder_row["orders_list"][] = $order_row;
                            }
                        } else {
                            $rorder_row["orders_list"] = array();
                        }


                        $result["records"][] = $rorder_row;
                    }
                }
            }
            $result["status_count"] = $status_count;
        }

        $this->response($result, 200);
        exit;
    }

    function so_defineroute_post() {
        $order_str = $this->post('order_str');
        $emp_id = $this->post('emp_id');
        $latitude = $this->post('latitude');
        $longitude = $this->post('longitude');
        $from_route = $this->post('from_route');  
        $to_route= $this->post('to_route');
        $estimation_time= $this->post('estimation_time');       
        $ostore = 0;
        $dt = date("Y-m-d");
        $datetime = date("Y-m-d H:i:s");
        $result["status"] = 0;
        $result["message"] = "Something went wrong";
         $result["route_id"] = 0;
        $droute_order_id = 0;
        $list_arr = array();
        if ($order_str != "") {

            $list_arr = explode(",", $order_str);

            $data = array('emp_id' => $emp_id, 'deliver_date' => $dt, 'route_name' => 'Route','from_route'=>$from_route,'to_route'=>$to_route,'estimation_time'=>$estimation_time ,'start_time' => $datetime, 'end_time' => '');
            $droute_id = $this->model_all->save($data, "delivery_route");
            if ($droute_id > 0) {
                $result["status"] = 1;
                $result["message"] = "Route Defined Successfully";
                $result["route_id"] = $droute_id;
                for ($i = 0; $i < count($list_arr); $i++) {
                    $store_str = $list_arr[$i];
                    $object_arr = explode("~", $store_str);
                    if (count($object_arr) > 0) {

                        $store = $object_arr[0];

                        if (isset($object_arr[1])) {
                            $order = $object_arr[1];
                            if ($order == 1) {
                                $ostore = $store;
                            }

                            $order_array = array();
                            $order_str = "";

                            $result_set = $this->model_all->getTableDataFromQuery("SELECT o.id from seller_orders o, sellers s,addresses a where o.address_id=a.id and o.orderedby = s.id and o.status='Packed' and o.received_by!=0 and s.id='$store'");
                            foreach ($result_set->result_array() as $order_row) {
                                $order_array[] = $order_row['id'];
                                $affected_rows = $this->model_all->update(array('status' => 'Dispatched'), array("id" => $order_row['id'], 'status' => 'Packed'), "seller_orders");
                                if ($affected_rows > 0) {
                                    $this->model_all->save(array("order_id" => $order_row['id'], "order_status" => 'Dispatched', "changed_on" => $datetime), 'seller_order_track');
                                    $this->model_all->track_parent_order($order_row['id'],'Dispatched');
                                }
                            }
                            if (count($order_array) > 0) {
                                $order_str = implode(",", $order_array);
                            }

                            $order_data = array('droute_id' => $droute_id, 'store_id' => $store, 'route_order' => $order, 'status' => '0', 'orders' => $order_str);
                            $insert_id = $this->model_all->save($order_data, "deliver_route_order");
                            if ($order == 1) {
                                $droute_order_id = $insert_id;
                            }
                        }
                    }
                }

                $dt = date("Y-m-d H:i:s");
                /* $track_data = array('store_id' => $ostore, 'route_order_id' => $droute_order_id, 'droute_id' => $droute_id, 'latitude' => $latitude, 'langitude' => $longitude, 'status' => '0', 'action_time' => $dt);
                  $this->model_all->save($track_data, "delivery_route_track"); */
            }
        }


        $this->response($result, 200);
        exit;
    }
    
    
    
     function manage_transport_post() {

        $user = $this->post('user');
        $seller = $this->post('seller');
        $order_str = $this->post('order_str');
        $vehicle_id = $this->post('vehicle_id');
        $route_id = $this->post('route_id');
        $vechicle_number = $this->post('vechicle_number');
        $driver_number = $this->post('driver_number');
        $driver_name = $this->post('driver_name');
        $lr_no = $this->post('lr_no');
        $transport = $this->post('transport');
        $branch = $this->post('branch');
        $transport_type = $this->post('transport_type');
        $amount = $this->post('amount');
        $paid = $this->post('paid');
        $ostore = 0;
        $table = "transport";
        $dt = date("Y-m-d H:i:s");
        $result["status"] = 0;
        $result["message"] = "Something went wrong";
        $from_route = $this->post('from_route');
        $to_route = $this->post('to_route');
        $estimation_time = $this->post('estimation_time');
        if ($estimation_time != "") {
            $estimation_time = date("Y-m-d");
        } else {
            $estimation_time = "";
        }
        $contact = $this->post('contact');
        $flag = false;

        $route_str = $from_route;
        if ($route_str != "") {
            $route_str .= "-";
        }
        $check_flag = true;
        $route_str = $route_str . $to_route;

        if ($vehicle_id == "" || $vehicle_id == 0) {
            $check_array = explode(",", $order_str);
            for ($i = 0; $i < count($check_array); $i++) {
                $check_rs = $this->model_all->getTableDataFromQuery("select * from delivery_vehicles where FIND_IN_SET($check_array[$i],orders)");
                if ($check_rs->num_rows() > 0) {
                    $check_flag = false;
                }
            }
        }

        if ($check_flag) {
            $data = array('emp_id' => $user, 'route_name' => $route_str, 'deliver_date' => $dt, 'route_name' => $route_str, 'from_route' => $from_route, 'to_route' => $to_route, 'estimation_time' => $estimation_time, 'amount' => $amount, 'paid' => $paid, 'start_time' => $dt, 'end_time' => '');
            $droute_id = $this->model_all->save($data, "delivery_route");
            if ($droute_id > 0) {
                if ($transport_type == "private") {
                    $result_set = $this->model_all->getTableData("transport", array("contact_no" => $driver_number));
                    $data = array('name' => $driver_name, 'contact_no' => $driver_number, 'email' => '', 'address' => '', 'transport_type' => 'private', 'branch' => $branch, 'modified_by' => $user, 'modified_on' => $dt);
                    if ($result_set->num_rows() > 0) {
                        $transport_rs = $result_set->row();
                        $transport = $transport_rs->id;
                    } else {
                        $data["created_by"] = $user;
                        $data["created_on"] = $dt;
                        $transport = $this->model_all->save($data, "transport");
                    }
                }

                $order_data = array('droute_id' => $droute_id, 'store_id' => $seller, 'route_order' => 1, 'status' => '0', 'orders' => $order_str);
                $this->model_all->save($order_data, "deliver_route_order");

                $route_data = array("route_id" => $droute_id, "seller_id" => $seller, "vechicle_number" => $vechicle_number, "driver_number" => $driver_number, "driver_name" => $driver_name, "lr_no" => $lr_no, "transport" => $transport, "contact" => $contact);
                if ($vehicle_id > 0) {
                    $route_data["orders"] = $order_str;
                    $action_status = $this->model_all->update($route_data, array("id" => $vehicle_id), "delivery_vehicles");
                    if ($action_status) {
                        $flag = true;
                    }
                } else {
                    $route_data["orders"] = $order_str;
                    $vehicle_id = $this->model_all->save($route_data, "delivery_vehicles");

                    $order_arr = explode(",", $order_str);
                    for ($a = 0; $a < count($order_arr); $a++) {
                        $this->model_all->save(array("order_id" => $order_arr[$a], "order_status" => 'Dispatched', "changed_on" => date("Y-m-d H:i:s")), 'seller_order_track');
                        $this->model_all->track_parent_order($order_arr[$a],'Dispatched');
                        $this->model_all->update(array("status" => 'Dispatched'), array("id" => $order_arr[$a]), 'seller_orders');

                        //  echo $this->db->last_query();
                    }


                    if ($vehicle_id > 0) {
                        $flag = true;

        //  Notification
       /* $order_string =  $row->order_id; 
        $order_id =  $row->id; */     
      
        $notify_data = $this->model_all->getDealerExecutive($seller,$branch);
        
        if($notify_data["dealer"]["fcm_key"]!=""){
            $payload = array();
            $data = array();
            $payload['title'] = "Welcome to Nova";
            $body = "Your order has dispatched.";
            $payload['body'] = $body;  /// Message goes here
            $payload['icon'] = "";  // Name of the icon in the play store
            $payload['click_action'] = "mainactivity";  // For android click activity
            $data['id'] = $vehicle_id;  // For custom value if any
            $payload['to'] = $notify_data["dealer"]["fcm_key"];   // Receiver FCM id
            $data['role'] = "DEALER";   // For custom value if any
            for ($a = 0; $a < count($order_arr); $a++) {
                       $data['id']= $order_arr[$a];
                       $this->model_all->save(array("notification"=>$body,"notify_type"=>"dispatched","user_role"=>"DEALER","user_id"=>$seller,"branch"=>$branch,"is_seen"=>"N","notifiy_on"=>date("Y-m-d H:i:s"),"related_id"=>$order_arr[$a]),"notifications");
                        $this->fcm->send($payload['to'], $payload, $data);
            }
            
            

        }

        if($notify_data["se"]["fcm_key"]!=""){
            $payload = array();
            $data = array();
            $payload['title'] = "Welcome to Nova";
            $body = "The order  placed by ".$notify_data["dealer"]["company_name"]."(".$notify_data["dealer"]["dealer_code"].") has dispatched."; /// Message goes here;
            $payload['body'] = $body; 
            $payload['icon'] = "";  // Name of the icon in the play store
            $payload['click_action'] = "mainactivity";  // For android click activity
            $data['id'] = $vehicle_id;  // For custom value if any
            $payload['to'] = $notify_data["se"]["fcm_key"];   // Receiver FCM id
            $data['role'] = "SE";   // For custom value if any
            for ($a = 0; $a < count($order_arr); $a++) {
                       $data['id']= $order_arr[$a];
                         $this->model_all->save(array("notification"=>$body,"notify_type"=>"dispatched","user_role"=>"SE","user_id"=>$notify_data["se"]["id"],"branch"=>$branch,"is_seen"=>"N","notifiy_on"=>date("Y-m-d H:i:s"),"related_id"=>$order_arr[$a]),"notifications");
                         $this->fcm->send( $payload['to'], $payload, $data);
            }
           

        }

        //  Notification






                    } else {
                        $flag = false;
                        $this->model_all->deleteRow("delivery_route", array("route_id" => $droute_id));
                    }
                }
            }

            if ($flag) {
                $result["status"] = 1;
                $result["message"] = "Vehicle Details Submitted Successfully";
            } else {
                $result["status"] = 0;
                $result["message"] = "Something went wrong while processing. Please Try Later";
            }
        } else {
            $result["status"] = 0;
            $result["message"] = "Transport Already Defined";
        }




        $this->response($result, 200);
        exit;
    }
    
    
    
   function so_dboy_route_summary_get() {

        $user = $this->get('user');
        $id = $this->get('order');  // pkey
        $dt = date("Y-m-d");
        $action_status = 0;
        $message = "No Records Found";
        $total_cost = 0;
        $total_processed = 0;
        $total_records = 0;

        $dRoute_set = $this->model_all->getTableDataFromQuery("select * from deliver_route_order where FIND_IN_SET($id,`orders`)");   //  id='$id'
        if ($dRoute_set->num_rows() > 0) {
            foreach ($dRoute_set->result() as $row) {
                $orders = $row->orders;
                $message = "Records Found";
                if ($orders != "") {
//echo "select  o.id,s.name,o.order_id,o.order_value from orders o,stores s where o.orderedby=s.id and o.id in('$orders')";
                    $result_set = $this->model_all->getTableDataFromQuery("select  o.id,o.remarks,s.company_name as name,o.order_id,o.order_value from seller_orders o,sellers s where o.orderedby=s.id and o.id in($id)");  //$orders
                    if ($result_set->num_rows() > 0) {

                        $action_status = 1;
                        foreach ($result_set->result_array() as $order_row) {
                            $total_cost = $total_cost + $order_row["order_value"];
                             $order_row["remarks"] = (!empty($order_row["remarks"])?$order_row["remarks"]:"NA");
                            $order_row["items"] = array();
                            $order = $order_row["id"];
                            $result_set2 = $this->model_all->getTableDataFromQuery("select sd.id,sd.order_item_id,sd.packed_qty,sd.status,sd.delivered_qty as balance_qty,i.itemname,u.unit_name as pack_type from seller_pack_details sd,seller_order_items o,items i,item_prices ip,branch_prices bp,unit_sizes u where sd.order_item_id=o.id and o.orderid='$order' and sd.order_from='seller' and u.unit_id=ip.unit_id  and o.branch_price_id=bp.id and bp.itemprice_id=ip.id and ip.item_id=i.id and sd.packed_qty!=0");
                            $total_records = $total_records + $result_set2->num_rows();

                            foreach ($result_set2->result_array() as $item_row) {
                                
                                if ($item_row["status"] != 0) {
                                    $total_processed++;
                                }


                                $order_row["items"][] = $item_row;
                            }
                            $result["records"][] = $order_row;
                        }
                    }
                }
            }
        }

        $result["status"] = $action_status;
        $result["total_processed"] = $total_processed;
        $result["total_records"] = $total_records;
        $result["total_cost"] = $total_cost;
        $result["message"] = $message;
        $this->response($result, 200);
        exit;
    }
    
    
function so_deliveryreport_get() {
        $condition = "";
        $fromdate = $this->get('fromdate');
        $todate = $this->get('todate');
        $status = $this->get('status');
        $store = $this->get('store');
        $user = $this->get('user');
        $branch = $this->get('branch');

        $total_cost = 0.00;
        $result["status"] = 0;
        $result["message"] = "No records Found";
        if ($fromdate != "" && $todate == "") {
            $fromdate = date("Y-m-d", strtotime($fromdate));
            $condition .= " and date(o.`orderedon`)='$fromdate'";
        } else if ($fromdate != "" && $todate != "") {
            $fromdate = date("Y-m-d", strtotime($fromdate));
            $todate = date("Y-m-d", strtotime($todate));
            $condition .= " and o.`orderedon` between '$fromdate 00:00:00' and '$todate 23:59:59'";
        } else if ($fromdate == "" && $todate == "") {
            $fromdate = date("Y-m-d");
            $condition .= " and o.`orderedon` <= '$fromdate 23:59:59'";
        }

        if ($status == 0) {
            $condition .= " and o.received_by ='$user'";
        } else if ($status == 1) {
            $condition .= " and o.dboy_accept='1'";   // 
        } else if ($status == 2) {
            $condition .= " and o.dboy_accept='1' and o.seller_accept='1' and delivery_reject='1'";   // 
        } else if ($status == 3) {
            $condition .= " and o.seller_accept='1' and delivery_reject='1'";  //  seller_accept    delivery_reject
        }
        $result_set = $this->model_all->getTableDataFromQuery("select o.status,o.id,o.order_id,o.`orderedon`,s.company_name as store,s.address,o.order_value from seller_orders o,sellers s where o.orderedby = s.id and o.branch_id='$branch' $condition order by o.orderedon desc");
        if ($result_set->num_rows() > 0) {
            $result["status"] = 1;
            $result["message"] = "Records Found";
            $result["total_records"] = $result_set->num_rows();
            foreach ($result_set->result_array() as $row) {
                $row['orderedon'] = date("d-m-Y", strtotime($row['orderedon']));
                $total_cost = $total_cost + $row['order_value'];
                $row["status"] = $row['status'];
                if ($row["status"] == "Ordered") {
                    $row["status"] = "Not yet Processed";
                } else if ($row["status"] == "Packed") {
                    $row["status"] = "Processed";
                } else if ($row["status"] == "Delivered") {
                    $row["status"] = "Delivered";
                }
                $result["records"][] = $row;
            }
            
        }

        $this->response($result, 200);
        exit;
    }
    
    
      function store_route_process_post() {
        $id = $this->post('pkey');
        $droute_id = $this->post('droute_id');
        $latitude = $this->post('latitude');
        $longitude = $this->post('langitude');
        $status = $this->post('status');
        $store = $this->post('store');
        $insert_id = 0;
        $dt = date("Y-m-d H:i:s"); 
        $order = 0;
        $emp_id = 0;
        $track_data = array('store_id' => $store, 'route_order_id' => $id, 'droute_id' => $droute_id, 'latitude' => $latitude, 'langitude' => $longitude, 'status' => $status, 'action_time' => $dt);
        $action_status = "0";
        $message = "Action not done. Please try later.";



        if ($this->model_all->getTableData("delivery_route_track", array("droute_id" => $droute_id, "route_order_id" => $id, "status" => $status))->num_rows() == 0) {
            $insert_id = $this->model_all->save($track_data, "delivery_route_track");
            $route_total_rows = $this->model_all->getTableData("deliver_route_order", array("droute_id" => $droute_id))->num_rows();
            $route_qry = $this->model_all->getTableDataFromQuery("select d.route_order,d.*,dr.emp_id from deliver_route_order d,delivery_route dr where d.id='$id' and dr.id=d.droute_id and dr.id='$droute_id'");
            foreach ($route_qry->result() as $route_rs) {
                $order = $route_rs->orders;
                $emp_id = $route_rs->emp_id;
                if ($route_rs->route_order == 1 && $status == 1) {
                    $this->model_all->update(array("start_time" => $dt), array("id" => $droute_id), "delivery_route");
                }
            }

            if ($status == 3) {
                if ($order != "") {
                    $this->model_all->getTableDataFromQuery("update orders set status='Delivered',deliveredby='$emp_id',deliveredon='$dt',delivery_accept='1' where id in ($order)");
                    $order_arr = explode(",",$order);
                    for($a=0;$a<count($order_arr);$a++){
                     $this->model_all->save(array("order_id" => $order[$a], "order_status" => 'Delivered', "changed_on" => $dt), 'order_track');
                    }
                }
            }
            if ($status == 4) {    // $route_rs->route_order==$route_total_rows && $status==3
                $this->model_all->update(array("end_time" => $dt), array("id" => $droute_id), "delivery_route");
                


                
            }
        } else {
            $action_status = "1";
            $message = "Status already defined";
        }




        if ($insert_id > 0) {
            $action_status = 1;
            if ($status != 4)
                $this->model_all->update(array("status" => $status), array("id" => $id), "deliver_route_order");
            $message = "Action done Successfully";
        }



        $result["status"] = $action_status;
        $result["message"] = $message;
        $this->response($result, 200);
        exit;
    }



    function so_delivery_post() {
        $action = $this->post('action');
        $user = $this->post('user');
        $pb_id = $this->post('id');
        $reason = $this->post('reason');
        $description = $this->post('description');
        $delivered_qty = $this->post('delivered_qty');
        $dt = date("Y-m-d H:i:s"); 
        $order = $this->post('order');
        $action_status = 0;
        $message = "Action not performed. Please try later";

        if ($action == 1) {
            $affected_rows = $this->model_all->update(array("status" => '1',"delivered_qty"=>$delivered_qty, "delivery_by" => $user, "action_dt" => $dt, "reason" => $reason, "description" => $description), array("id" => $pb_id), "seller_pack_details");
           // echo $this->db->last_query();
            if ($affected_rows) {
                $message = "Item Delivered successfully";
                $action_status = 1;
            }
        } else if ($action == 2) {
             $packed_qty = $this->model_all->tableFieldData("select packed_qty from seller_pack_details where id='$pb_id'","packed_qty");
            $img_name = "";
            if (isset($_FILES) && $_FILES['rej_img']['size'] > 0 && $_FILES['rej_img']['error'] == 0) {
                $name = $user."_delivery_rej" . time() . "_" . $_FILES['rej_img']['name'];
                $source_url = $_FILES['rej_img']['tmp_name'];
                $destination_url = "rejections/" . $name;
                if (@move_uploaded_file($source_url, $destination_url)) {
                    $img_name = $name;
                } else {
                    $img_name = "";
                }
            }
            if($packed_qty==$delivered_qty){
               $affected_rows = $this->model_all->update(array("status" => '2', "delivered_qty"=>$delivered_qty,"delivery_by" => $user, "action_dt" => $dt, "reason" => $reason, "description" => $description, "action_img" => $img_name), array("id" => $pb_id), "seller_pack_details");
           }else{
                $affected_rows = $this->model_all->update(array("status" => '1', "delivered_qty"=>$delivered_qty,"delivery_by" => $user, "action_dt" => $dt, "reason" => $reason, "description" => $description, "action_img" => $img_name), array("id" => $pb_id), "seller_pack_details");

           }

            if ($affected_rows) {
                $message = "Item rejected successfully";
                $action_status = 1;
            }
        }

        $result_set1 = $this->model_all->getTableDataFromQuery("SELECT p.id FROM seller_pack_details p,seller_order_items o WHERE  o.orderid='$order' and o.id=p.order_item_id  and p.packed_qty!=0");
        $result['total_records'] = $result_set1->num_rows();
        $result_set2 = $this->model_all->getTableDataFromQuery("SELECT p.id FROM seller_pack_details p,seller_order_items o WHERE  o.orderid='$order' and o.id=p.order_item_id and p.status!='0'");
        $result_set3 = $this->model_all->getTableDataFromQuery("SELECT p.id FROM seller_pack_details p,seller_order_items o WHERE  o.orderid='$order' and o.id=p.order_item_id and  p.packed_qty=p.delivered_qty and p.status='2'");


        $result['total_processed'] = $result_set2->num_rows();
        if ($result_set1->num_rows() == $result_set2->num_rows()) {
            if($result_set1->num_rows() == $result_set3->num_rows()){
              $this->model_all->update(array("status" => 'Rejected', "deliveredby"=>$user,"deliveredon"=>date("Y-m-d H:i:s"),"dboy_accept"=>'2'), array("id" => $order), "seller_orders");
              $this->model_all->track_parent_order($order,'Rejected');
              $this->model_all->save(array("order_id" => $order, "order_status" => 'Rejected', "changed_on" => $dt), 'seller_order_track');

            }else{
              $this->model_all->update(array("status" => 'Delivered', "deliveredby"=>$user,"deliveredon"=>date("Y-m-d H:i:s"),"dboy_accept"=>'1'), array("id" => $order), "seller_orders");
              $this->model_all->track_parent_order($order,'Delivered');
              $this->model_all->save(array("order_id" => $order, "order_status" => 'Delivered', "changed_on" => $dt), 'seller_order_track');
            }
        }

        $result["status"] = $action_status;
        $result["message"] = $message;

        $this->response($result, 200);
        exit;
    }


    function invoiceslist_get() {
        $user = $this->get('user');
        $fromdate = $this->get('fromdate');
        $todate = $this->get('todate');
        $status = $this->get('status');
        $role = $this->get('role');
       
       

       $condition="";

        $branch = $this->get('branch');
        if($branch != "") {
            $condition .= " and o.branch_id='$branch'";
        }

        if ($fromdate != "" && $todate == "") {
            $fromdate = date("Y-m-d", strtotime($fromdate));
            $condition .= " and date(o.`orderedon`)='$fromdate'";
        } else if ($fromdate != "" && $todate != "") {
            $fromdate = date("Y-m-d", strtotime($fromdate));
            $todate = date("Y-m-d", strtotime($todate));
            $condition .= " and o.`orderedon` between '$fromdate 00:00:00' and '$todate 23:59:59'";
        }

        $result_set = $this->model_all->getTableDataFromQuery("select o.id, o.order_id, o.orderedon, o.status, o.orderedby, o.created_by,o.order_value,o.payment_type,o.credit_date,s.id as dealer_id,s.company_name as dealer_name  from seller_orders o,sellers s where o.orderedby=s.id and o.deliveredby='$user' and o.dboy_accept='1' and o.status='Delivered' $condition order by o.orderedon desc");
       
        if ($result_set->num_rows() > 0) {
            $result["status"] = 1;
            $result["message"] = "Records Found";
            $result["total_records"] = $result_set->num_rows();       
            


            foreach ($result_set->result_array() as $row) {
                if($row["orderedby"]!=$row["created_by"]){

                     $emp_qry = $this->model_all->getTableDataFromQuery("select e.first_name as emp_name,e.mobile,b.name as branch_name from employees e,branches b where e.branch=b.id and e.id='$row[created_by]'");
                     if($emp_qry->num_rows()>0){
                          $emp_rs=$emp_qry->row();
                          $row["takenby_name"] = $emp_rs->emp_name;
                          $row["takenby_branch"] = $emp_rs->branch_name;
                          $row["takenby_contact"] = $emp_rs->mobile;
                     }else{
                          $row["takenby_name"] = "-";
                          $row["takenby_branch"] = "-";
                          $row["takenby_contact"] = "-"; 
                     }
                     
                }else{
                    $row["takenby_name"] = "Self";
                    $row["takenby_branch"] = "-";
                    $row["takenby_contact"] = "-"; 
                }
                if($row['credit_date']!=""){
                  $row['credit_date'] = date("d-m-Y", strtotime($row['credit_date']));
                } 
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
    
    
    function so_rejected_details_get() {
        $order = $this->get('order');
        $action = $this->get('action');
        $condition ="";
        if($action=="1"){
            $condition = " and   ((sd.packed_qty>sd.delivered_qty and sd.status='1') or (sd.packed_qty=sd.delivered_qty and sd.status='2')) and sd.delivered_qty!='0'";
        }else if($action=="2"){
            $condition = " and   sd.packed_qty=sd.delivered_qty  and sd.status='2'";
        }

      

        $result_set = $this->model_all->getTableDataFromQuery("SELECT o.id,o.mrp,o.qty,o.amount,o.total_cost,i.itemname,u.unit_name as pack_type,sd.packed_qty,sd.balance_qty,sd.delivered_qty,sd.description as balance_descr,(select r.rej_point from  rejection_points r where r.id=sd.reason) as rejection_point from seller_order_items o,items i,item_prices ip,branch_prices bp,unit_sizes u,seller_pack_details sd where o.orderid='$order' and u.unit_id=ip.unit_id  and o.branch_price_id=bp.id and bp.itemprice_id=ip.id and ip.item_id=i.id  and sd.order_item_id=o.id $condition");
        if ($result_set->num_rows() > 0) {
            $result["remarks"]=$this->model_all->tableFieldData("select remarks from seller_orders where id='$order'","remarks");
            $result["remarks"] = (!empty($result["remarks"])?$result["remarks"]:"NA");
            $store_query = $this->model_all->getTableDataFromQuery("select CONCAT(s.first_name,s.last_name) as name,a.address from sellers s,seller_orders o,addresses a where s.id=o.orderedby and o.id='$order' and a.user_id=s.id and (a.user_role='DEALER'or a.user_role='seller') and a.is_default='1' and a.status='1'");
            if ($store_rs = $store_query->row()) {
                
                $result["store_name"] = $store_rs->name;
                $result["store_address"] = $store_rs->address;
            }
            $result["status"] = 1;
            $result["message"] = "Records Found";
            $result["total_rows"] = $result_set->num_rows();
            $total_units = 0;
            $total_savings = 0;
            $total_sur_charge = 0;
            $total_pay = 0;
            
            foreach ($result_set->result_array() as $row) {
                $row['action_status']=0;
                if($row['packed_qty']!=0){
                    $row['action_status']=1;
                }else if($row['balance_qty']!=0 && $row['qty'] == $row['balance_qty']){
                    $row['action_status']=2;
                }
                $row['discount'] = "Rs " . ($row['mrp'] - $row['amount']) . " /-";
                $row['total_price'] = "Rs " . ($row['qty'] * $row['amount']) . " /-";
                $row['margin'] = round((($row['mrp'] - $row['amount']) / $row['amount']) * 100, 2);
                $total_units += $row['qty'];
                $total_savings += ($row['mrp'] - $row['amount']) * $row['qty'];
                $total_pay += ($row['qty'] * $row['amount']);
                $rem_qty = $row['qty'] - $row['packed_qty'];
                $row['rem_qty'] = ($rem_qty <= 0) ? 0 : $rem_qty;
                $total_sur_charge += 0.00;
                $result["records"][] = $row;
            }


            $result_set2 = $this->model_all->getTableDataFromQuery("SELECT o.id from seller_order_items o,seller_pack_details sd where o.orderid='$order' and sd.order_item_id=o.id");
            $result['total_processed'] = $result_set2->num_rows();
            $result['total_units'] = $total_units;
            $result['total_savings'] = "Rs " . $total_savings . " /-";
            $result['total_sur_charge'] = "Rs " . $total_sur_charge . " /-";
            $result['total_pay'] = "Rs " . $total_pay . " /-";
            $this->response($result, 200);
            exit;
        } else {
            $result["status"] = 0;
            $result["message"] = "No records Found";
            $this->response($result, 200);
            exit;
        }
    }

     function so_reject_report_get() {
        $condition = "";
        $fromdate = $this->get('fromdate');
        $todate = $this->get('todate');
        $status = $this->get('status');
        $store = $this->get('store');
        $user = $this->get('user');
        $branch = $this->get('branch');
       



        $total_cost = 0.00;
        $result["status"] = 0;
        $result["message"] = "No records Found";
        if ($fromdate != "" && $todate == "") {
            $fromdate = date("Y-m-d", strtotime($fromdate));
            $condition .= " and date(o.`orderedon`)='$fromdate'";
        } else if ($fromdate != "" && $todate != "") {
            $fromdate = date("Y-m-d", strtotime($fromdate));
            $todate = date("Y-m-d", strtotime($todate));
            $condition .= " and o.`orderedon` between '$fromdate 00:00:00' and '$todate 23:59:59'";
        } else if ($fromdate == "" && $todate == "") {
            $fromdate = date("Y-m-d");
            $condition .= " and o.`orderedon` <= '$fromdate 23:59:59'";
        }

       if($status==2){
         $condition .= " and o.dboy_accept='2'";  
       }else if($status==1){
         $condition .= " and o.dboy_accept='1'";  
       }
       
        $result_set = $this->model_all->getTableDataFromQuery("select o.status,o.id,o.order_id,o.`orderedon`,s.company_name as store,s.address,o.order_value from seller_orders o,sellers s where o.orderedby = s.id and o.branch_id='$branch' $condition order by o.orderedon desc");
        if ($result_set->num_rows() > 0) {
            
           if($status==2){

            $result["status"] = 1;
            $result["message"] = "Records Found";
            $result["total_records"] = $result_set->num_rows();
            foreach ($result_set->result_array() as $row) {
                $row['orderedon'] = date("d-m-Y", strtotime($row['orderedon']));
                $total_cost = $total_cost + $row['order_value'];
                $row["status"] = $row['status'];
                if ($row["status"] == "Ordered") {
                    $row["status"] = "Not yet Processed";
                } else if ($row["status"] == "Packed") {
                    $row["status"] = "Processed";
                } else if ($row["status"] == "Delivered") {
                    $row["status"] = "Delivered";
                }
                $result["records"][] = $row;
            }

          }else if($status==1){
               $result["records"] = array();
               foreach ($result_set->result_array() as $row) {
                    $packed_set = $this->model_all->getTableDataFromQuery("SELECT o.id,o.mrp,o.qty,o.amount,o.total_cost,i.itemname,u.unit_name as pack_type,sd.packed_qty,sd.balance_qty,sd.delivered_qty,sd.description as balance_descr,(select r.rej_point from  rejection_points r where r.id=sd.reason) as rejection_point from seller_order_items o,items i,item_prices ip,branch_prices bp,unit_sizes u,seller_pack_details sd where o.orderid='$row[id]' and u.unit_id=ip.unit_id  and o.branch_price_id=bp.id and bp.itemprice_id=ip.id and ip.item_id=i.id  and sd.order_item_id=o.id and (sd.status='2' or (sd.packed_qty>sd.delivered_qty and sd.status='1')) and sd.delivered_qty!='0'");
                    if($packed_set->num_rows()>0){
                        $row['orderedon'] = date("d-m-Y", strtotime($row['orderedon']));
	                $total_cost = $total_cost + $row['order_value'];
	                $row["status"] = $row['status'];
	                if ($row["status"] == "Ordered") {
	                    $row["status"] = "Not yet Processed";
	                } else if ($row["status"] == "Packed") {
	                    $row["status"] = "Processed";
	                } else if ($row["status"] == "Delivered") {
	                    $row["status"] = "Delivered";
	                }
                        $result["records"][] = $row;
                    
                    
                    }
                   if(count($result["records"])>0){
                       $result["status"] = 1;
                       $result["message"] = "Records Found";
                   }



               }
               
               
               
               

          }
            
        }

        $this->response($result, 200);
        exit;
    }


}

