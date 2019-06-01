<?php

require(APPPATH . '/libraries/REST_Controller.php');

class Packers extends REST_Controller {

    public function __construct() {
        parent::__construct();
        $this->load->model('model_all');
        $this->load->library('barcode');
    }

    function index_get() {
        $primaryid = $this->get("primaryid");
//echo $primaryid;
        $result_set = $this->model_all->getTableDataInArray("sellers", array('status' => 1, 'pickerid' => $primaryid), "id,first_name,last_name,email,mobile,latitude,longitude,address");

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

    function list_get() {
        $primaryid = $this->get('primaryid');
        $invoice = $this->get('invoice');
        $dt = date("Y-m-d");


        $result_set = $this->model_all->getTableDataFromQuery("SELECT seller_items.sellingprice,items.itemname , items.brand,seller_items.id , seller_items.amount,  seller_items.qty,seller_items.picked_qty,seller_items.packer_qty,seller_items.packer_status as items_tatus FROM seller_items, items WHERE seller_items.seller_id ='$primaryid' AND seller_items.item_id = items.id AND  seller_items.sellet_invoice_pk='$invoice' and seller_items.status='1' order by seller_items.order_date desc,seller_items.id desc");
        $result['total_records'] = $result_set->num_rows();
        $result['total_processed'] = $result_set->num_rows();
        if ($result_set->num_rows() > 0) {
            $result["status"] = 1;
            $result["message"] = "Records Found";
            $total_amount = 0;
            foreach ($result_set->result_array() as $row) {

                if ($row['qty'] != $row['picked_qty']) {
                    $row['amount'] = $row['picked_qty'] * $row['sellingprice'];
                }
                $total_amount += $row['amount'];
                $row["cost"] = $row["amount"];
                if ($row["items_tatus"] == 0) {
                    $result['total_processed'] --;
                }
                $result["items"][] = $row;
            }
            $result['total_amount'] = "Rs " . $total_amount . " /-";
            $this->response($result, 200);
            exit;
        } else {
            $result["status"] = 0;
            $result["message"] = "No List Found";
            $this->response($result, 200);
            exit;
        }
    }

    function orders_get() {
        $condition = "";
        $fromdate = $this->get('fromdate');
        $todate = $this->get('todate');
        $status = $this->get('status');
        $store = $this->get('store');
        $total_cost = 0.00;

        if ($status == "") {
            // $status = "Ordered";
        } else {
            $condition .= " and o.status='$status'";
        }



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
        $result_set = $this->model_all->getTableDataFromQuery("select o.id,o.order_id,o.`orderedon`,s.name as store,o.order_value,(SELECT sum(oi.qty)-(IFNULL(sum(po.packed_qty),0)) as rem_qty FROM `order_items` oi LEFT JOIN packed_orders po on oi.id=po.order_item_id where oi.orderid=o.id ) as rem_qty  from orders o,stores s where o.orderedby = s.id and o.status!='packed' $condition order by o.orderedon");
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

    //API - Fetch All Pincodes
    function pack_post() {
        $packer = $this->post('packer');
        $pack_type = $this->post('pack_type');
        $items = $this->post('items');
        $order = $this->post('order');
        $items = explode(",", $items);
        $dt = date("Y-m-d H:i:s");
        $order_id = "";
        $flag = false;
        /*    $pb_id = $this->model_all->getTableData("packed_bags",array("bag_name" => $pack_type, "pack_type" => $pack_type, "packed_by" => $packer, "packed_on" => $dt,"order_id"=>$order));  */
        $exist_count = 0;
        $packed_query = $this->model_all->getTableData("packed_bags", array("pack_type" => $pack_type, "order_id" => $order));
        $exist_count = $packed_query->num_rows();


        $order_qry = $this->model_all->getTableData("orders", array("id" => $order));
        if ($order_qry->num_rows() > 0) {
            $order_row = $order_qry->row();
            $order_id = $order_row->order_id;
        }
        if ($order_id != "") {
            if ($pack_type == 'tin') {

                for ($i = 0; $i < count($items); $i++) {
                    $row = explode("~", $items[$i]);
                    if (isset($row[1]))
                        $qty = $row[1];
                    else
                        $qty = 0;
                    $id = $row[0];

                    for ($i = 1; $i <= $qty; $i++) {
                        $pb_id = $this->model_all->save(array("bag_name" => (ucwords($pack_type) . ($exist_count + $i)), "pack_type" => $pack_type, "packed_by" => $packer, "packed_on" => $dt, "status" => '0', "order_id" => $order), "packed_bags");
                        if ($pb_id > 0) {
                            $this->model_all->save(array('pb_id' => $pb_id, 'status' => '0', 'reason' => '', 'description' => '', 'received_by' => 0, 'received_time' => '', 'action_img' => ''), "sdboy_receivings");
                            // $barcode = $order_id . $pb_id;
                            $barcode = "";
                            $this->model_all->update(array("barcode" => $barcode), array("pb_id" => $pb_id), "packed_bags");
                            $flag = true;
                            $this->model_all->save(array("order_item_id" => $id, "packed_qty" => 1, "pb_id" => $pb_id), "packed_orders");
                        } else {
                            $flag = false;
                        }
                    }
                }
                if (!$flag) {
                    $result["status"] = 0;
                    $result["message"] = "No Records Found";
                } else {
                    $this->model_all->update(array("status" => "Packed"), array("id" => $order), "orders");
                    $this->model_all->save(array("order_id" => $order, "order_status" => 'Packed', "changed_on" => $dt), 'order_track');
                    $result["status"] = 1;
                    $result["message"] = "Packed Successfully";
                }

                //  $this->model_all->update(array("order_item_id" => $id, "packed_qty" => $qty, "pb_id" => $pb_id), "packed_orders");
            } else {
                $exist_count++;
                $pb_id = $this->model_all->save(array("bag_name" => ucwords($pack_type) . $exist_count, "pack_type" => $pack_type, "packed_by" => $packer, "packed_on" => $dt, "status" => '0', "order_id" => $order), "packed_bags");
                $barcode = $order_id . $pb_id;
                $this->barcode->draw($barcode);
                $this->model_all->update(array("barcode" => $barcode), array("pb_id" => $pb_id), "packed_bags");
                if ($pb_id > 0) {
                    $this->model_all->save(array('pb_id' => $pb_id, 'status' => '0', 'reason' => '', 'description' => '', 'received_by' => 0, 'received_time' => '', 'action_img' => ''), "sdboy_receivings");
                    for ($i = 0; $i < count($items); $i++) {
                        $row = explode("~", $items[$i]);
                        if (isset($row[1]))
                            $qty = $row[1];
                        else
                            $qty = 0;
                        $id = $row[0];
                        $this->model_all->save(array("order_item_id" => $id, "packed_qty" => $qty, "pb_id" => $pb_id), "packed_orders");
                    }
                    $this->model_all->update(array("status" => "Packed"), array("id" => $order), "orders");
                    $result["status"] = 1;
                    $result["message"] = "Packed Successfully";
                    //  $this->model_all->update(array("order_item_id" => $id, "packed_qty" => $qty, "pb_id" => $pb_id), "packed_orders");
                } else {
                    $result["status"] = 0;
                    $result["message"] = "No Records Found";
                }
            }
        } else {
            
        }


        $this->response($result, 200);
        exit;
    }

    function details_get() {
        $order = $this->get('order');

        $result_set = $this->model_all->getTableDataFromQuery("SELECT o.*, CONCAT(s.first_name,s.last_name)  as seller,i.itemname,i.brand,p.sellingprice,(SELECT IFNULL(sum(po.packed_qty),0) FROM `packed_orders` po,`packed_bags` pb , order_items oi where po.pb_id= pb.pb_id and po.order_item_id=oi.id and oi.orderid='$order' and oi.itemid=o.itemid) as packed_qty FROM `order_items` o, items i ,sellers s,pricing p where i.id=o.itemid and s.id = o.sellerid and p.sellerid=s.id  and p.itemid = o.itemid and o.orderid='$order'");
        // echo $this->db->last_query();
        if ($result_set->num_rows() > 0) {

            $store_query = $this->model_all->getTableDataFromQuery("select s.name,s.address from stores s,orders o where s.id=o.orderedby and o.id='$order' ");
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


            $unit_count = $this->model_all->getTableDataFromQuery("SELECT SUM(case when pack_type = 'Bag' then 1 else 0 end) as bag_count,SUM(case when pack_type = 'Tin' then 1 else 0 end) as tin_count,SUM(case when pack_type = 'Case' then 1 else 0 end) as case_count  FROM `packed_bags` where order_id='$order'")->row_array();
            $result['bag_count'] = ($unit_count["bag_count"] != "") ? $unit_count["bag_count"] : "0";
            $result['tin_count'] = ($unit_count["tin_count"] != "") ? $unit_count["tin_count"] : "0";
            $result['case_count'] = ($unit_count["case_count"] != "") ? $unit_count["case_count"] : "0";
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

    function invoicedetails_get() {

        $seller = $this->get('seller');
        $invoiceid = $this->get('invoiceid');
        if ($invoiceid == "")
            $invoiceid = date("mdY") . $seller;

        $result_set = $this->model_all->getTableDataFromQuery("SELECT si.qty,si.amount, CONCAT(s.first_name,s.last_name)  as seller,i.itemname,i.brand,u.unit_name FROM `seller_items` si, items i ,sellers s,unit_sizes u where i.id=si.item_id and s.id = si.seller_id and s.id='$seller' and u.unit_id=i.unit_size and si.invoice_id='$invoiceid'");
        if ($result_set->num_rows() > 0) {

            $result["status"] = 1;
            $result["message"] = "Records Found";
            $result["total_rows"] = $result_set->num_rows();
            $total_amount = 0;
            $total_units = 0;
            foreach ($result_set->result_array() as $row) {
                $total_amount += $row['amount'];
                $row['amount'] = "Rs " . ($row['amount']) . " /-";
                $total_units += $row['qty'];
                $result["records"][] = $row;
            }
            $result['total_units'] = $total_units;
            $result['total_amount'] = "Rs " . $total_amount . " /-";

            $this->response($result, 200);
            exit;
        } else {
            $result["status"] = 0;
            $result["message"] = "No records Found";
            $this->response($result, 200);
            exit;
        }
    }

    function selleritems_get() {

        $seller = $this->get('seller');
        $invoiceid = $this->get('invoiceid');
        if ($invoiceid == "")
            $invoiceid = date("dmY") . $seller;

        $result_set = $this->model_all->getTableDataFromQuery("SELECT si.id as list_id,si.qty,si.amount, CONCAT(s.first_name,s.last_name)  as seller,i.itemname,i.brand,u.unit_name FROM `seller_items` si, items i ,sellers s,unit_sizes u where i.id=si.item_id and s.id = si.seller_id and s.id='$seller' and u.unit_id=i.unit_size and si.invoice_id='$invoiceid'");
        if ($result_set->num_rows() > 0) {

            $result["status"] = 1;
            $result["message"] = "Records Found";
            $result["total_rows"] = $result_set->num_rows();
            $total_amount = 0;
            $total_units = 0;
            foreach ($result_set->result_array() as $row) {
                $total_amount += $row['amount'];
                $row['amount'] = "Rs " . ($row['amount']) . " /-";
                $total_units += $row['qty'];
                $result["records"][] = $row;
            }
            $result['total_units'] = $total_units;
            $result['total_amount'] = "Rs " . $total_amount . " /-";

            $this->response($result, 200);
            exit;
        } else {
            $result["status"] = 0;
            $result["message"] = "No records Found";
            $this->response($result, 200);
            exit;
        }
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

        if ($status == "") {
            //$status = "Ordered";
        } else {
            $condition .= " and o.status='$status'";
        }


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
        $result_set = $this->model_all->getTableDataFromQuery("select o.id,o.order_id,o.`orderedon`,s.name as store,s.address,o.order_value,(SELECT sum(oi.qty) FROM `order_items` oi where oi.orderid=o.id )as qty,(SELECT sum(oi.qty)-(IFNULL(sum(po.packed_qty),0)) as rem_qty FROM `order_items` oi LEFT JOIN packed_orders po on oi.id=po.order_item_id where oi.orderid=o.id ) as rem_qty  from orders o,stores s where o.orderedby = s.id $condition");
        if ($result_set->num_rows() > 0) {
            $result["status"] = 1;
            $result["message"] = "Records Found";
            $result["total_records"] = $result_set->num_rows();
            foreach ($result_set->result_array() as $row) {
                $row['orderedon'] = date("d-m-Y", strtotime($row['orderedon']));
                $total_cost = $total_cost + $row['order_value'];
                $unit_count = $this->model_all->getTableDataFromQuery("SELECT SUM(case when pack_type = 'Bag' then 1 else 0 end) as bag_count,SUM(case when pack_type = 'Tin' then 1 else 0 end) as tin_count,SUM(case when pack_type = 'Case' then 1 else 0 end) as case_count  FROM `packed_bags` where order_id='" . $row['id'] . "'")->row_array();
                $row['bag_count'] = ($unit_count["bag_count"] != "") ? $unit_count["bag_count"] : "0";
                $row['tin_count'] = ($unit_count["tin_count"] != "") ? $unit_count["tin_count"] : "0";
                $row['case_count'] = ($unit_count["case_count"] != "") ? $unit_count["case_count"] : "0";



                $result["records"][] = $row;
            }
            $result["total_cost"] = "Rs " . $total_cost . " /-";
        }

        $this->response($result, 200);
        exit;
    }

    function statusreport_get() {
        $primaryid = $this->get('primaryid');
        $status = $this->get('status');
        $dt = date("Y-m-d");



        $result_set = $this->model_all->getTableDataFromQuery("SELECT items.itemname , items.brand,seller_items.id , seller_items.amount,  seller_items.qty,seller_items.picked_qty,seller_items.packer_status as items_tatus FROM seller_items, items WHERE seller_items.seller_id ='$primaryid' AND seller_items.item_id = items.id AND seller_items.order_date='$dt' and seller_items.packer_status='$status'");
        $result['total_records'] = $result_set->num_rows();
        $result['total_processed'] = $result_set->num_rows();
        if ($result_set->num_rows() > 0) {
            $result["status"] = 1;
            $result["message"] = "Records Found";
            $total_amount = 0;
            foreach ($result_set->result_array() as $row) {
                $total_amount += $row['amount'];
                $row["cost"] = $row["amount"];
                if ($row["items_tatus"] == 0) {
                    $result['total_processed'] --;
                }
                $result["items"][] = $row;
            }
            $result['total_amount'] = "Rs " . $total_amount . " /-";
            $this->response($result, 200);
            exit;
        } else {
            $result["status"] = 0;
            $result["message"] = "No List Found";
            $this->response($result, 200);
            exit;
        }
    }

    function barcodes_get() {
        $order = $this->get('id');
        $result_set = $this->model_all->getTableData("packed_bags", array("order_id" => $order), "pb_id,bag_name,barcode,pack_type");

        $bag = 0;
        $tin = 0;
        $case = 0;


        if ($result_set->num_rows() > 0) {

            $total_amount = 0;
            $result["barcodes"] = array();
            foreach ($result_set->result_array() as $row) {

                $row["barcode_img"] = "";
                if ($row["barcode"] != "") {
                    $file_headers = @get_headers(base_url() . 'barcodes/' . $row["barcode"] . ".jpg");
                    if (stripos($file_headers[0], "404 Not Found") > 0 || (stripos($file_headers[0], "302 Found") > 0 && stripos($file_headers[7], "404 Not Found") > 0)) {
                        
                    } else {
                        $row["barcode_img"] = base_url() . 'barcodes/' . $row["barcode"] . ".jpg";
                        $result["barcodes"][] = $row;
                    }
                }

                /* if($row["pack_type"]=="Bag"){
                  $bag++;
                  $row["bag_name"]= $row["bag_name"]." ".$bag;
                  }else if($row["pack_type"]=="Tin"){
                  $tin++;
                  $row["bag_name"]= $row["bag_name"]." ".$tin;
                  }else if($row["pack_type"]=="Case"){
                  $case++;
                  $row["bag_name"]= $row["bag_name"]." ".$case;
                  }
                  $row["bag_name"] = ucwords($row["bag_name"]); */
            }
            if (count($result["barcodes"]) > 0) {
                $result["status"] = 1;
                $result["message"] = "Records Found";
            } else {
                $result["status"] = 0;
                $result["message"] = "No List Found";
            }
            $result['total_amount'] = "Rs " . $total_amount . " /-";
            $this->response($result, 200);
            exit;
        } else {
            $result["status"] = 0;
            $result["message"] = "No List Found";
            $this->response($result, 200);
            exit;
        }
    }

    function pickreport_get() {
        $primaryid = $this->get('user');
        $status = $this->get('status');
        $store = $this->get('store');
        $fromdate = $this->get('fromdate');
        $todate = $this->get('todate');
        $condition = "";


        if ($fromdate != "" && $todate == "") {
            $fromdate = date("Y-m-d", strtotime($fromdate));
            $condition .= " and date(si.`orderedon`)='$fromdate'";
        } else if ($fromdate != "" && $todate != "") {
            $fromdate = date("Y-m-d", strtotime($fromdate));
            $todate = date("Y-m-d", strtotime($todate));
            $condition .= " and si.`action_date` between '$fromdate 00:00:00' and '$todate 23:59:59'";
        } else if ($fromdate == "" && $todate == "") {
            $fromdate = date("Y-m-d");
            $condition .= " and si.`action_date` between '$fromdate 00:00:00' and '$fromdate 23:59:59'";
        }

        $dt = date("Y-m-d");
        if ($status != "") {

            $condition .= " and packer_status='$status'";
        }



        $result_set = $this->model_all->getTableDataFromQuery("SELECT distinct si.sellet_invoice_pk,si.invoice_id,si.order_date  FROM seller_items si WHERE  si.packed_by='$primaryid'  $condition order by si.order_date desc");

        if ($result_set->num_rows() > 0) {
            $result["status"] = 1;
            $result["message"] = "Records Found";
            $total_amount = 0;
            foreach ($result_set->result_array() as $row) {

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

    function pickreportdetails_get() {
        $id = $this->get('id');
        $status = $this->get('status');
        $dt = date("Y-m-d");


        $result_set = $this->model_all->getTableDataFromQuery("SELECT items.itemname , items.brand,seller_items.id , seller_items.amount,  seller_items.qty,seller_items.picked_qty,seller_items.packer_status as items_tatus,seller_items.sellingprice FROM seller_items, items WHERE seller_items.sellet_invoice_pk ='$id' AND seller_items.item_id = items.id AND seller_items.packer_status='$status'");
        $result['total_records'] = $result_set->num_rows();
        $result['total_processed'] = $result_set->num_rows();
        if ($result_set->num_rows() > 0) {
            $result["status"] = 1;
            $result["message"] = "Records Found";
            $total_amount = 0;
            foreach ($result_set->result_array() as $row) {
                if ($row['qty'] != $row['picked_qty']) {
                    $row['amount'] = $row['picked_qty'] * $row['sellingprice'];
                    $row['qty'] = $row['picked_qty'];
                }
                $total_amount += $row['amount'];
                $row["cost"] = $row["amount"];
                if ($row["items_tatus"] == 0) {
                    $result['total_processed'] --;
                }
                $result["records"][] = $row;
            }
            $result['total_amount'] = "Rs " . $total_amount . " /-";
            $this->response($result, 200);
            exit;
        } else {
            $result["status"] = 0;
            $result["message"] = "No List Found";
            $this->response($result, 200);
            exit;
        }
    }

    function packreport_get() {
        $primaryid = $this->get('user');
        $status = $this->get('status');

        $fromdate = $this->get('fromdate');
        $todate = $this->get('todate');
        $condition = "";


        if ($fromdate != "" && $todate == "") {
            $fromdate = date("Y-m-d", strtotime($fromdate));
            $condition .= " and date(p.`packed_on`)='$fromdate'";
        } else if ($fromdate != "" && $todate != "") {
            $fromdate = date("Y-m-d", strtotime($fromdate));
            $todate = date("Y-m-d", strtotime($todate));
            $condition .= " and p.`packed_on` between '$fromdate 00:00:00' and '$todate 23:59:59'";
        } else if ($fromdate == "" && $todate == "") {
            $fromdate = date("Y-m-d");
            $condition .= " and p.`packed_on` between '$fromdate 00:00:00' and '$fromdate 23:59:59'";
        }

        $dt = date("Y-m-d");



        if ($status == 1) {
            $result_set = $this->model_all->getTableDataFromQuery("SELECT distinct p.order_id as id,o.order_id,date(p.packed_on) as packed_date FROM  packed_bags p,orders o WHERE  p.packed_by='$primaryid' and p.order_id=o.id  $condition order by p.packed_on desc");

            if ($result_set->num_rows() > 0) {
                $result["status"] = 1;
                $result["message"] = "Records Found";
                $total_amount = 0;
                foreach ($result_set->result_array() as $row) {
                    $row['packed_date'] = date("d-m-Y", strtotime($row['packed_date']));
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
        } else if ($status == 2) {

            $result_set = $this->model_all->getTableDataFromQuery("SELECT distinct p.order_id as id,o.order_id,date(p.packed_on) as packed_date FROM  packed_bags p,sdboy_receivings s,orders o WHERE  p.packed_by='$primaryid' and p.order_id=o.id  and s.pb_id=p.pb_id and s.status='2' $condition");

            if ($result_set->num_rows() > 0) {
                $result["status"] = 1;
                $result["message"] = "Records Found";
                $total_amount = 0;
                foreach ($result_set->result_array() as $row) {
                    $row['packed_date'] = date("d-m-Y", strtotime($row['packed_date']));
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

    function packorders_deatails_get() {

        $order = $this->get('order');
        $status = $this->get('status');
        if ($status == 1) {
            $result_set2 = $this->model_all->getTableData("packed_bags", "order_id='$order'", "pb_id,bag_name,barcode,status", "pack_type");
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
            $result_set2 = $this->model_all->getTableDataFromQuery("SELECT  p.bag_name  FROM  packed_bags p,sdboy_receivings s,orders o WHERE  p.order_id='$order' and p.order_id=o.id  and s.pb_id=p.pb_id and s.status='2'");
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

    function process_put() {

        $invoice = $this->put('invoice');
        $user = $this->put('user');



        $dt = date("Y-m-d");
        $status = $this->model_all->update(array("packer_status" => '1', "packed_by" => $user), array("id" => $invoice), "seller_invoices");
        if ($status) {
            // $status = $this->model_all->update(array("is_processed" => '1'), array("id" => $invoice), "seller_items");
            $result["status"] = 1;
            $result["message"] = "Processed Successfully";
            $this->response($result, 200);
            exit;
        } else {
            $result["status"] = 0;
            $result["message"] = "You have made no changes to save";
            $this->response($result, 200);
            exit;
        }
    }

    /*       Sellers Related Queries Orders  */

    function seller_orders_get() {
        $condition = "";
        $fromdate = $this->get('fromdate');
        $todate = $this->get('todate');
        $status = $this->get('status');
        $store = $this->get('store');
        $total_cost = 0.00;

        if ($status == "") {
            // $status = "Ordered";
        } else {
            $condition .= " and o.status='$status'";
        }



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

        $branch = $this->get('branch');
        if ($branch != "") {
            $condition .= " and o.branch_id='$branch'";
        }

        $result_set = $this->model_all->getTableDataFromQuery("select o.id,o.order_id,o.`orderedon`,s.company_name,s.dealer_code,CONCAT(s.first_name,s.first_name) as store,o.order_value,(SELECT sum(oi.qty)-(IFNULL(sum(po.packed_qty),0)) as rem_qty FROM `seller_order_items` oi LEFT JOIN packed_orders po on oi.id=po.order_item_id where oi.orderid=o.id ) as rem_qty  from seller_orders o,sellers s where o.orderedby = s.id and o.status='Accepted' and  o.admin_status='1' $condition order by o.orderedon desc");   // o.status!='packed'  o.fa_status='1' and
        if ($result_set->num_rows() > 0) {
            $result["status"] = 1;
            $result["message"] = "Records Found";
            $result["total_records"] = $result_set->num_rows();
            foreach ($result_set->result_array() as $row) {
                $row['orderedon'] = date("d-m-Y", strtotime($row['orderedon']));
                $total_cost = $total_cost + $row['order_value'];
                $row['order_id'] = $row['order_id']." (".$row['company_name']." - ".$row['dealer_code'].")";
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

     function pack_seller_post() {
        $packer = $this->post('user');
        $status = $this->post('action');
        $items = $this->post('item');
        $order = $this->post('order');
        $qty = $this->post('qty');
        //  $ori_qty = $this->post('ori_qty');
        $reason = $this->post('reason');
        $descr = $this->post('description');

        $batch_no = $this->post('batch_no');
        $mfg_date = $this->post('mfg_date');
        if($mfg_date!="" && $mfg_date!="NA"){
           $mfg_date = date("Y-m-d",strtotime($mfg_date));
        }
        $exp_date = $this->post('exp_date');
        if($exp_date!="" && $exp_date!="NA"){
           $exp_date = date("Y-m-d",strtotime($exp_date));
        }


        $dt = date("Y-m-d H:i:s");
        $order_id = "";
        $flag = false;
        $ori_qty = 0;
        $ori_qty_qry = $this->model_all->getTableData("seller_order_items", array("id" => $items));
        if ($ori_qty_qry->num_rows() > 0) {
            $ori_rs = $ori_qty_qry->row();
            $ori_qty = $ori_rs->qty-$ori_rs->transfer_qty;
        }
        if ($status == 1) {
            $packed_qty = $qty;
            $balance_qty = $ori_qty - $qty;
        } else if ($status == 2) {
            $balance_qty = $qty;
            $packed_qty = $ori_qty - $qty;
        }

        $exist_count = 0;
        $total_rows = $this->model_all->getTableData("seller_order_items", array("orderid" => $order))->num_rows();
        $packed_query = $this->model_all->getTableData("seller_pack_details", array("order_item_id" => $items));
        $exist_count = $packed_query->num_rows();



        if ($exist_count == 0) {

            //if ($balance_qty <= $ori_qty && $packed_qty <= $ori_qty) {
                //if ($pack_type == 'tin') {
                $packed_data = array("order_item_id" => $items, "order_from" => 'seller',"batch_no"=>$batch_no,"mfg_date"=>$mfg_date,"exp_date"=>$exp_date, "packed_qty" => $packed_qty, "balance_qty" => $balance_qty, "balance_reason" => $reason, "balance_descr" => $descr, "balance_img" => '', "packed_by" => $packer, "status" => '0', "delivery_by" => 0, "reason" => '', "description" => '', "action_img" => '', "action_dt" => '', "packed_on" => $dt);
                $img_name = "";
                if (isset($_FILES) && isset($_FILES['rej_img']) && $_FILES['rej_img']['size'] > 0 && $_FILES['rej_img']['error'] == 0) {
                    $name = $packer . "_rej" . time() . "_" . $_FILES['rej_img']['name'];
                    $source_url = $_FILES['rej_img']['tmp_name'];
                    $destination_url = "rejections/pack_" . $name;
                    if (@move_uploaded_file($source_url, $destination_url)) {
                        $img_name = $name;
                    } else {
                        $img_name = "";
                    }
                }
                $packed_data["balance_img"] = $img_name;
                $id = $this->model_all->save($packed_data, "seller_pack_details");
                if ($id > 0) {
                    $message = "Item rejected successfully";
                    $action_status = 1;
                    $result_set2 = $this->model_all->getTableDataFromQuery("SELECT o.id from seller_order_items o,seller_pack_details sd where o.orderid='$order' and sd.order_item_id=o.id");
                    $total_processed = $result_set2->num_rows();
                    if ($total_processed == $total_rows) {
                        $this->model_all->update(array("received_by" => $packer, "status" => "Packed"), array("id" => $order), "seller_orders");
                        $this->model_all->track_parent_order($order,'Packed');
                        $this->model_all->save(array("order_id" => $order, "order_status" => 'Packed', "changed_on" => $dt), "seller_order_track");
                    }

                    if ($action_status == 0) {
                        $result["status"] = 0;
                        $result["message"] = "No Records Found";
                    } else {

                        // for only packer $this->model_all->update(array("status" => "Packed"), array("id" => $order), "seller_orders");
                        if ($status == 1) {
                            $result["status"] = 1;
                            $result["message"] = "Packed Successfully";
                        } else if($status == 2) {
                            $result["status"] = 1;
                            if($packed_qty==0)
                             $result["message"] = "Rejected Successfully";
                            else
                             $result["message"] = "Packed Successfully";
                            
                        }
                    }
                } else {
                    $result_set2 = $this->model_all->getTableDataFromQuery("SELECT o.id from seller_order_items o,seller_pack_details sd where o.orderid='$order' and sd.order_item_id=o.id");
                    $total_processed = $result_set2->num_rows();
                    $result["status"] = 0;
                    $result["message"] = "Something went wrong Successfully";
                }
           /* } else {
                $result_set2 = $this->model_all->getTableDataFromQuery("SELECT o.id from seller_order_items o,seller_pack_details sd where o.orderid='$order' and sd.order_item_id=o.id");
                $total_processed = $result_set2->num_rows();
                $result["status"] = 0;
                $result["message"] = "Value exceeds the ordered quantity";
            }*/

            $total_processed = $result_set2->num_rows();
            $result["total_processed"] = $total_processed;
        } else {
            $result["status"] = 1;
            $result["message"] = "item Alrady Processed";
        }
        $result["total_rows"] = $total_rows;

        $this->response($result, 200);
        exit;
    }



    function so_details_get() {
        $order = $this->get('order');

        $result_set = $this->model_all->getTableDataFromQuery("SELECT o.*, b.name  as seller,i.itemname,i.brand,bp.margin_price as sellingprice,(SELECT IFNULL(sum(po.packed_qty),0) FROM `packed_orders` po,`seller_packed_bags` pb , seller_order_items oi where po.pb_id= pb.pb_id and po.order_item_id=oi.id and oi.orderid='$order' and oi.branch_price_id=o.branch_price_id) as packed_qty,us.unit_name as pack_type FROM `seller_order_items` o, items i ,branch_prices bp,item_prices ip,branches b,unit_sizes us where bp.id=o.branch_price_id and b.id = bp.branch_id and bp.itemprice_id=ip.id  and ip.item_id=i.id and us.unit_id=ip.unit_id and o.orderid='$order'");

        if ($result_set->num_rows() > 0) {


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


            $unit_count = $this->model_all->getTableDataFromQuery("SELECT SUM(case when pack_type = 'Bag' then 1 else 0 end) as bag_count,SUM(case when pack_type = 'Tin' then 1 else 0 end) as tin_count,SUM(case when pack_type = 'Case' then 1 else 0 end) as case_count  FROM `packed_bags` where order_id='$order'")->row_array();
            $result['bag_count'] = ($unit_count["bag_count"] != "") ? $unit_count["bag_count"] : "0";
            $result['tin_count'] = ($unit_count["tin_count"] != "") ? $unit_count["tin_count"] : "0";
            $result['case_count'] = ($unit_count["case_count"] != "") ? $unit_count["case_count"] : "0";
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

     function so_packreport_get() {
        $primaryid = $this->get('user');
        $status = $this->get('status');
        $branch = $this->get('branch');

        $fromdate = $this->get('fromdate');
        $todate = $this->get('todate');
        $condition = "";


        if ($fromdate != "" && $todate == "") {
            $fromdate = date("Y-m-d", strtotime($fromdate));
            $condition .= " and date(p.`packed_on`)='$fromdate'";
        } else if ($fromdate != "" && $todate != "") {
            $fromdate = date("Y-m-d", strtotime($fromdate));
            $todate = date("Y-m-d", strtotime($todate));
            $condition .= " and p.`packed_on` between '$fromdate 00:00:00' and '$todate 23:59:59'";
        } else if ($fromdate == "" && $todate == "") {
            $fromdate = date("Y-m-d");
            $condition .= " and p.`packed_on` between '$fromdate 00:00:00' and '$fromdate 23:59:59'";
        }

        $dt = date("Y-m-d");



        if ($status == 1) {


            $result_set = $this->model_all->getTableDataFromQuery("SELECT distinct oi.orderid as id,o.order_id,date(p.packed_on) as packed_date FROM  seller_pack_details p,seller_orders o,seller_order_items oi WHERE  p.packed_by='$primaryid' and oi.orderid=o.id and p.order_item_id=oi.id and p.packed_qty!=0 and o.branch_id='$branch' $condition order by p.packed_on desc");

            if ($result_set->num_rows() > 0) {
                $result["status"] = 1;
                $result["message"] = "Records Found";
                $total_amount = 0;
                foreach ($result_set->result_array() as $row) {
                    $row['packed_date'] = date("d-m-Y", strtotime($row['packed_date']));
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
        } else if ($status == 2) {

            $result_set = $this->model_all->getTableDataFromQuery("SELECT distinct oi.orderid as id,o.order_id,date(p.packed_on) as packed_date FROM  seller_pack_details p,seller_orders o,seller_order_items oi WHERE  p.packed_by='$primaryid' and oi.orderid=o.id and p.order_item_id=oi.id and p.balance_qty!=0 $condition order by p.packed_on desc");

            if ($result_set->num_rows() > 0) {
                $result["status"] = 1;
                $result["message"] = "Records Found";
                $total_amount = 0;
                foreach ($result_set->result_array() as $row) {
                    $row['packed_date'] = date("d-m-Y", strtotime($row['packed_date']));
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
        } else {


            $result_set = $this->model_all->getTableDataFromQuery("SELECT distinct oi.orderid as id,o.order_id,date(p.packed_on) as packed_date FROM  seller_pack_details p,seller_orders o,seller_order_items oi WHERE  p.packed_by='$primaryid' and oi.orderid=o.id and p.order_item_id=oi.id  $condition order by p.packed_on desc");

            if ($result_set->num_rows() > 0) {
                $result["status"] = 1;
                $result["message"] = "Records Found";
                $total_amount = 0;
                foreach ($result_set->result_array() as $row) {
                    $row['packed_date'] = date("d-m-Y", strtotime($row['packed_date']));
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

    function so_packorders_deatails_get() {

        $order = $this->get('order');
        $status = $this->get('status');
        if ($status == 1) {
            $result_set2 = $this->model_all->getTableData("packed_bags", "order_id='$order'", "pb_id,bag_name,barcode,status", "pack_type");
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
            $result_set2 = $this->model_all->getTableDataFromQuery("SELECT  p.bag_name  FROM  packed_bags p,sdboy_receivings s,seller_orders o WHERE  p.order_id='$order' and p.order_id=o.id  and s.pb_id=p.pb_id and s.status='2'");
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


    function so2_details_get() {
        $order = $this->get('order');
        $result_set = $this->model_all->getTableDataFromQuery("SELECT o.*,i.itemname,u.unit_name as pack_type,(SELECT IFNULL(sum(sd.packed_qty),0) FROM `seller_pack_details` sd where sd.order_item_id=o.id and o.orderid='$order') as packed_qty,(SELECT IFNULL(sum(sd.balance_qty),0) FROM `seller_pack_details` sd where  sd.order_item_id= o.id and o.orderid='$order') as balanced_qty from seller_order_items o,items i,item_prices ip,branch_prices bp,unit_sizes u where o.orderid='$order' and u.unit_id=ip.unit_id  and o.branch_price_id=bp.id and bp.itemprice_id=ip.id and ip.item_id=i.id");
        if ($result_set->num_rows() > 0) {
            $store_query = $this->model_all->getTableDataFromQuery("select CONCAT(s.first_name,s.last_name) as name,a.address,o.remarks from sellers s,seller_orders o,addresses a where s.id=o.orderedby and o.id='$order' and a.user_id=s.id and (a.user_role='DEALER'or a.user_role='seller') and a.is_default='1' and a.status='1'");
            $result["remarks"]=$this->model_all->tableFieldData("select remarks from seller_orders where id='$order'","remarks");
            $result["remarks"] = (!empty($result["remarks"])?$result["remarks"]:"NA");
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

                $row['batch_no']  =  "";
                $row['mfg_date']  =  "";
                $row['exp_date']  =  "";

                $batch_query = $this->model_all->getTableDataFromQuery("SELECT batch_no,mfg_date,exp_date FROM `seller_pack_details` sd where sd.order_item_id='$row[id]'  ");
                if($batch_query->num_rows()>0){
                   $batch_rs = $batch_query->row_array();
                   $row['batch_no']  =  $batch_rs["batch_no"];
                  if($batch_rs["mfg_date"]!="" && $batch_rs["mfg_date"]!="0000-00-00"){
                          $row['mfg_date'] = $batch_rs["mfg_date"];
                  }
                  if($batch_rs["exp_date"]!="" && $batch_rs["exp_date"]!="0000-00-00"){
                          $row['exp_date'] = $batch_rs["exp_date"];
                  }
                }
                $row['qty'] = $row['qty']-$row['transfer_qty']; // For Transfer purpose
                $row['action_status']=0;
                if($row['packed_qty']!=0){
                    $row['action_status']=1;
                }else if($row['balanced_qty']!=0 && $row['qty'] == $row['balanced_qty']){
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


    function so_packed_details_get() {
        $order = $this->get('order');
        $action = $this->get('action');
        $condition ="";
        if($action=="1"){
            $condition = " and   sd.packed_qty!='0'";
        }else if($action=="2"){
            $condition = " and   sd.balance_qty!='0'";

        }


        $result_set = $this->model_all->getTableDataFromQuery("SELECT o.id,o.mrp,o.qty,o.amount,o.total_cost,i.itemname,u.unit_name as pack_type,sd.packed_qty,sd.balance_qty,sd.balance_descr,(select r.rej_point from  rejection_points r where r.id=sd.balance_reason) as rejection_point from seller_order_items o,items i,item_prices ip,branch_prices bp,unit_sizes u,seller_pack_details sd where o.orderid='$order' and u.unit_id=ip.unit_id  and o.branch_price_id=bp.id and bp.itemprice_id=ip.id and ip.item_id=i.id  and sd.order_item_id=o.id $condition");
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

}
