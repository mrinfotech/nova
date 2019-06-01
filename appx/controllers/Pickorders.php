<?php

require(APPPATH . '/libraries/REST_Controller.php');

//echo $dt;
class Pickorders extends REST_Controller {

    private $dt;

    public function __construct() {
        parent::__construct();
        $this->load->model('model_all');
        $present_time = date("H:i");
        $this->dt = date("Y-m-d", strtotime("-1 day"));
    }

    function list_get() {
        $primaryid = $this->get('primaryid');
        $invoice  =  $this->get('invoice');
        $dt = date("Y-m-d");
        //echo "SELECT items.itemname, items.brand,seller_items.id , seller_items.amount,  seller_items.qty FROM seller_items, items WHERE seller_items.seller_id =$primaryid AND seller_items.item_id = items.id AND seller_items.order_date='$dt'";

        $result_set = $this->model_all->getTableDataFromQuery("SELECT seller_items.picked_qty,items.itemname , items.brand,seller_items.id , seller_items.amount,  seller_items.qty,seller_items.status as items_tatus FROM seller_items, items WHERE seller_items.seller_id ='$primaryid' AND seller_items.item_id = items.id AND seller_items.order_date='$dt' and seller_items.qty!=0 and   seller_items.sellet_invoice_pk='$invoice' order by  seller_items.`order_date` desc,seller_items.`id` desc");
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


    function invoicelist_get() {
        $seller = $this->get('seller');
        $dt = date("Y-m-d");
        //echo "SELECT items.itemname, items.brand,seller_items.id , seller_items.amount,  seller_items.qty FROM seller_items, items WHERE seller_items.seller_id =$primaryid AND seller_items.item_id = items.id AND seller_items.order_date='$dt'";

        $result_set = $this->model_all->getTableDataFromQuery("SELECT distinct si.id,si.order_date,si.invoice_id,si.is_picked from seller_invoices si, seller_items s where si.seller_id='$seller' and si.order_date<='$dt' and si.is_picked='0' and si.id=s.sellet_invoice_pk and s.qty!=0 order by si.order_date desc,si.id desc");
       
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


     function sellers_get() {
        $primaryid = $this->get("primaryid");
        $dt = date("Y-m-d");
       

        $result_set = $this->model_all->getTableDataFromQuery("SELECT distinct se.id,se.first_name,se.last_name,se.email,se.mobile,se.latitude,se.longitude,se.address  from seller_invoices si, seller_items s,sellers se where si.seller_id=se.id and si.order_date<='$dt' and si.is_picked='0' and si.id=s.sellet_invoice_pk and s.qty!=0 and se.status='1' and se.`pickerid` = '$primaryid'");
       
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





    function listitems($cid) {
        $primaryid = $this->get('primaryid');
        $result = array();

        $result_set = $this->model_all->getTableDataFromQuery("SELECT items.itemname, items.brand,seller_items.id , seller_items.amount,  seller_items.qty FROM seller_items, items WHERE seller_items.seller_id =$primaryid AND seller_items.item_id = items.id AND items.productid =$cid AND seller_items.order_date='$this->dt'");
        if ($result_set->num_rows() > 0) {
            foreach ($result_set->result() as $irow) {
                $object = array();
                $object["id"] = $irow->id;
                $object["itemname"] = $irow->itemname;
                $object["qty"] = $irow->qty;
                $object["cost"] = $irow->amount;
                $result[] = $object;
            }
        }
        return $result;
    }

    function do_action_post() {
        $action = $this->post('action');
        $seller = $this->post('seller');
        $invoice = $this->post('invoice');
        $primary_key = $this->post('primary_key');
        $role = $this->post('role');
        $item = $this->post('item');
        $qty = $this->post('qty');
        $reason = $this->post('reason');
        $description = $this->post('description');
        $dt = date("Y-m-d H:i:s");
        $today = date("Y-m-d");
        $action_status = 0;
        $message = "Action not performed. Please try later";
        if ($role == "picker") {
            if ($action == 1) {
                $affected_rows = $this->model_all->update(array("picked_qty" => $qty, "status" => '1', "picked_by" => $primary_key, "picked_time" => $dt, "reason" => $reason, "description" => $description), array("id" => $item), "seller_items");
                if ($affected_rows) {
                    $message = "Item Received successfully";
                    $action_status = 1;
                }
            } else if ($action == 0) {
                $img_name = "";
                if ($_FILES['rej_img']['size'] > 0 && $_FILES['rej_img']['error'] == 0) {
                    $name = "rej" . time() . "_" . $_FILES['rej_img']['name'];
                    $source_url = $_FILES['rej_img']['tmp_name'];
                    $destination_url = "rejections/" . $name;
                    if (@move_uploaded_file($source_url, $destination_url)) {
                        $img_name = $name;
                    } else {
                        $img_name = "";
                    }
                }
                $affected_rows = $this->model_all->update(array("picked_qty" => $qty, "status" => '2', "picked_by" => $primary_key, "picked_time" => $dt, "reason" => $reason, "description" => $description, "picked_image" => $img_name), array("id" => $item), "seller_items");
                if ($affected_rows) {
                    $message = "Item rejected successfully";
                    $action_status = 1;
                }
            }

          
            $result_set1 = $this->model_all->getTableDataFromQuery("SELECT seller_items.picked_qty,items.itemname , items.brand,seller_items.id , seller_items.amount,  seller_items.qty,seller_items.status as items_tatus FROM seller_items, items WHERE seller_items.seller_id ='$seller' AND seller_items.item_id = items.id AND seller_items.order_date='$today' and  seller_items.sellet_invoice_pk='$invoice'");
            $result['total_records'] = $result_set1->num_rows();
            $result_set2 = $this->model_all->getTableDataFromQuery("SELECT seller_items.picked_qty,items.itemname , items.brand,seller_items.id , seller_items.amount,  seller_items.qty,seller_items.status as items_tatus FROM seller_items, items WHERE seller_items.seller_id ='$seller' AND seller_items.item_id = items.id AND seller_items.order_date='$today' and (seller_items.status='2' or seller_items.status='1')  and seller_items.sellet_invoice_pk='$invoice'");
            $result['total_processed'] = $result_set2->num_rows();
            if($result['total_processed']==$result['total_records']){
                  $this->model_all->getTableDataFromQuery("update seller_invoices set is_picked='1',picked_by='$primary_key' where id='$invoice'");
            }



        } else if ($role == "packer") {
            if ($action == 1) {
                $affected_rows = $this->model_all->update(array("packer_status" => '1',"packer_qty"=>$qty, "packed_by" => $primary_key, "packer_reason" => $reason, "packer_reason_descr" => $description,"action_date"=>$dt), array("id" => $item), "seller_items");
                if ($affected_rows) {
                    $message = "Item Received successfully";
                    $action_status = 1;
                }
            } else if ($action == 2) {
                $img_name = "";
                if ($_FILES['rej_img']['size'] > 0 && $_FILES['rej_img']['error'] == 0) {
                    $name = "rej" . time() . "_" . $_FILES['rej_img']['name'];
                    $source_url = $_FILES['rej_img']['tmp_name'];
                    $destination_url = "rejections/" . $name;
                    if (@move_uploaded_file($source_url, $destination_url)) {
                        $img_name = $name;
                    } else {
                        $img_name = "";
                    }
                }
                $affected_rows = $this->model_all->update(array("packer_status" => '2',  $primary_key, "packer_reason" => $reason, "packer_reason_descr" => $description, "packer_image" => $img_name,"action_date"=>$dt), array("id" => $item), "seller_items");
                if ($affected_rows) {
                    $message = "Item rejected successfully";
                    $action_status = 1;
                }
            }

            $result_set1 = $this->model_all->getTableDataFromQuery("SELECT seller_items.picked_qty,items.itemname , items.brand,seller_items.id , seller_items.amount,  seller_items.qty,seller_items.status as items_tatus FROM seller_items, items WHERE seller_items.seller_id ='$seller' AND seller_items.item_id = items.id  and  seller_items.status='1' and seller_items.sellet_invoice_pk='$invoice' and seller_items.packer_status=0");
            $result['total_records'] = $result_set1->num_rows();
            $result_set2 = $this->model_all->getTableDataFromQuery("SELECT seller_items.picked_qty,items.itemname , items.brand,seller_items.id , seller_items.amount,  seller_items.qty,seller_items.status as items_tatus FROM seller_items, items WHERE seller_items.seller_id ='$seller' AND seller_items.item_id = items.id  and seller_items.status='1'  and seller_items.sellet_invoice_pk='$invoice' and seller_items.packer_status!=0");
            $result['total_processed'] = $result_set2->num_rows();
            if($result['total_processed']==$result['total_records']){
                  $this->model_all->getTableDataFromQuery("update seller_invoices set packer_status='1',packed_by='$primary_key' where id='$invoice'");
            }

 

        }


        $result["status"] = $action_status;
        $result["message"] = $message;
        $this->response($result, 200);
        exit;
    }

    function sellerorders_get() {
        $condition = "";
        $fromdate = $this->get('from_date');
        $todate = $this->get('to_date');
        $picker = $this->get('picker');
        $total_cost = 0.00;



        if ($fromdate != "" && $todate == "") {
            $fromdate = date("Y-m-d", strtotime($fromdate));
            $condition .= " and o.`order_date`='$fromdate'";
        } else if ($fromdate != "" && $todate != "") {
            $fromdate = date("Y-m-d", strtotime($fromdate));
            $todate = date("Y-m-d", strtotime($todate));
            $condition .= " and o.`order_date` between '$fromdate' and '$todate'";
        } else if ($fromdate == "" && $todate == "") {
            $fromdate = date("Y-m-d");
            $condition .= " and o.`order_date`='$fromdate'";
        }

        $presult_set = $this->model_all->getTableData("sellers", array('status' => 1, "pickerid" => $picker), "id,first_name,last_name,email,mobile,latitude,longitude,address");
        if ($presult_set->num_rows() > 0) {
            foreach ($presult_set->result_array() as $row) {
                $row['orders'] = array();
                $result_set = $this->model_all->getTableDataFromQuery("select distinct  o.order_date,(select SUM(amount) from seller_items where order_date=o.order_date) as amount from seller_items o where o.seller_id='" . $row['id'] . "' $condition");
                if ($result_set->num_rows() > 0) {

                    foreach ($result_set->result_array() as $sub_row) {
                        $sub_row['order_date'] = date("d-m-Y", strtotime($sub_row['order_date']));
                        $total_cost = $total_cost + $sub_row['amount'];
                        $row['orders'][] = $sub_row;
                    }
                    $result["records"][] = $row;
                }
            }
            if (count($result["records"]) > 0) {
                $result["status"] = 1;
                $result["message"] = "Records Found";
                $result["total_records"] = count($result["records"]);
                $result["total_cost"] = "Rs " . $total_cost . " /-";
            } else {
                $result["status"] = 0;
                $result["message"] = "No records Found";
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

    function orders_get() {
        $condition = "";
        $fromdate = $this->get('from_date');
        $todate = $this->get('to_date');
        $picker = $this->get('picker');
        $status = $this->get('status');
        $total_cost = 0.00;
        $sub_condition = "";


        if ($fromdate != "" && $todate == "") {
            $fromdate = date("Y-m-d", strtotime($fromdate));
            $condition .= " and o.`order_date`='$fromdate'";
        } else if ($fromdate != "" && $todate != "") {
            $fromdate = date("Y-m-d", strtotime($fromdate));
            $todate = date("Y-m-d", strtotime($todate));
            $condition .= " and o.`order_date` between '$fromdate' and '$todate'";
        } else if ($fromdate == "" && $todate == "") {
            $fromdate = date("Y-m-d");
            $condition .= " and o.`order_date`='$fromdate'";
        }

        if ($status == 0) {
            $condition .= " and o.`is_picked`='0'";
            $condition .= " and si.`status`='0'";
            $sub_condition .= " and `status`='0'";
        } else if ($status == 1) {
            $condition .= " and o.`is_picked`='1'";
            $condition .= " and si.`status`='1'";
            $sub_condition .= " and `status`='1'";
        } else if ($status == 2) {
            $condition .= " and o.`is_picked`='1'";
            $condition .= " and (si.`status`='2' or (si.status='1' and si.qty!=si.picked_qty))";
            $sub_condition .= " and (`status`='2' or (status='1' and qty!=picked_qty))";
        }


        $row['orders'] = array();
       
        $result_set = $this->model_all->getTableDataFromQuery("select distinct o.id,o.order_date,o.invoice_id  from seller_invoices o,sellers s,seller_items si where o.seller_id=s.id and s.pickerid='$picker' and o.id=si.sellet_invoice_pk $condition order by o.order_date desc");
       /* $result_set = $this->model_all->getTableDataFromQuery("select distinct  o.order_date,(select SUM(amount) from seller_items   where order_date=o.order_date) as amount from seller_items o,sellers s where o.seller_id=s.id and s.pickerid='$picker' $condition"); */
        if ($result_set->num_rows() > 0) {
            $result["status"] = 1;
            $result["message"] = "Records Found";
            $result["total_records"] = $result_set->num_rows();
            foreach ($result_set->result_array() as $sub_row) {
                $sub_row['order_date'] = date("d-m-Y", strtotime($sub_row['order_date']));
                
                $amount_query = $this->model_all->getTableDataFromQuery("select picked_qty,qty,amount,seller_items.qty,sellingprice  from seller_items where sellet_invoice_pk='$sub_row[id]' $sub_condition"); 
                $sub_row['amount']=0.00;
                foreach($amount_query->result_array() as $amount_rs){
                    
                     if($status==0){
                       $amount=$amount_rs['amount'];

                     }else if($status==1){
                      if($amount_rs['qty']!=$amount_rs['picked_qty']){
                          $amount=$amount_rs['picked_qty']*$amount_rs['sellingprice'];
                      }else{
                        $amount=$amount_rs['amount'];
                      }

                     }else if($status==2){
                       if($amount_rs['qty']!=$amount_rs['picked_qty']){
                         $amount=($amount_rs['qty']-$amount_rs['picked_qty'])*$amount_rs['sellingprice'];
                       }else{
                        $amount=$amount_rs['amount'];
                       }
                     }
                    $sub_row['amount'] = $sub_row['amount']+$amount;
                    $total_cost = $total_cost + $amount; 
                     
                }
                
                
                $result['orders'][] = $sub_row;
            }
            $result["total_cost"] = "Rs " . $total_cost . " /-";
        } else {
            $result["status"] = 0;
            $result["message"] = "No records Found";
        }
        $this->response($result, 200);
        exit;
    }

    function report_get() {
        $condition = "";
        $order_date = $this->get('order_date');
        $invoice = $this->get('invoice');
        $status = $this->get('status');
        $picker = $this->get('picker');
        $total_cost = 0.00;



        if ($order_date == "") {
            $order_date = date("Y-m-d");
            $condition .= " and o.`order_date`='$order_date'";
        } else {
            $order_date = date("Y-m-d", strtotime($order_date));
            $condition .= " and o.`order_date`='$order_date'";
        }

        if ($status == 0) {
            $condition .= " and s.`pickerid`='$picker'";
        } else if ($status == 1) {
            $condition .= " and o.`status`='1'";
        } else if ($status == 2) {
            $condition .= "  and (o.`status`='2' or (o.status='1' and o.qty!=o.picked_qty))";
        }


        $result['orders'] = array();
      
        
        $result_set = $this->model_all->getTableDataFromQuery("select o.mrp,o.sellingprice,o.order_date, o.seller_id,o.qty,o.amount,o.picked_qty,o.status,o.picked_time,i.id as item_id,i.itemname,i.brand,c.categoryname,p.name as pack_type from seller_items o,sellers s,items i,categories c,pack_types p where o.seller_id=s.id and s.pickerid='$picker' and o.item_id=i.id and i.productid=c.id and p.id=i.pack_type and o.sellet_invoice_pk='$invoice'  $condition");
        
        if ($result_set->num_rows() > 0) {
            $result["status"] = 1;
            $result["message"] = "Records Found";
            $result["total_records"] = $result_set->num_rows();
            foreach ($result_set->result_array() as $sub_row) {
                $sub_row['dept'] = 'Grocery';
                $sub_row['ean'] = '1403256';
                if ($sub_row["picked_time"] != "") {
                    $sub_row["picked_time"] = date("h:i A", strtotime($sub_row["picked_time"]));
                } else {
                    $sub_row["picked_time"] = "";
                }
                if ($sub_row['status'] == 0) {
                    $sub_row['status'] = "Pending";
                } else if ($sub_row['status'] == 1) {
                    if ($sub_row['qty'] == $sub_row['picked_qty']) {
                        $sub_row['status'] = "Processed";
                    } else {
                       
                       if($status==1) {
                         $sub_row['amount'] = ($sub_row['picked_qty'])*$sub_row['sellingprice'];
                         $sub_row['qty'] =  $sub_row['picked_qty'];
                       }else {
                         $sub_row['amount'] = ($sub_row['qty']-$sub_row['picked_qty'])*$sub_row['sellingprice'];
                         $sub_row['qty'] =  $sub_row['qty']-$sub_row['picked_qty'];
                       }
                        $sub_row['status'] = "Part of order";
                    }
                    
                }else if ($sub_row['status'] == 2) {
                    $sub_row['status'] = "Rejected";
                    

                    
                }

                $sub_row['seller'] = "ID" . $sub_row['seller_id'];

                $sub_row['order_date'] = date("d-m-Y", strtotime($sub_row['order_date']));
                $total_cost = $total_cost + $sub_row['amount'];
                $result['orders'][] = $sub_row;
            }
            $result["total_cost"] = "Rs " . $total_cost . " /-";
        } else {
            $result["status"] = 0;
            $result["message"] = "No records Found";
        }
        $this->response($result, 200);
        exit;
    }

    function process_put() {

        $seller = $this->put('seller_id');
        $user  = $this->put('user');
        
        
        $dt = date("Y-m-d");
        $status = $this->model_all->update(array("is_picked" => '1',"picked_by"=>$user), array("seller_id" => $seller, "order_date" => $dt), "seller_invoices");
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

    function invoices_get() {
        $user = $this->get('user');
        $status = $this->get('status');
        $fromdate = $this->get('fromdate');
        $todate = $this->get('todate');
        $condition = "";
        if ($status == "")
            $staus = 0;
        if ($fromdate != "" && $todate == "") {
            $fromdate = date("Y-m-d", strtotime($fromdate));
            $condition .= " and `order_date`='$fromdate'";
        } else if ($fromdate != "" && $todate != "") {
            $fromdate = date("Y-m-d", strtotime($fromdate));
            $todate = date("Y-m-d", strtotime($todate));
            $condition .= " and `order_date` between '$fromdate' and '$todate'";
        }


        /* $result_set = $this->model_all->getTableDataFromQuery("select distinct invoice_id,order_date,description,reason from  seller_items where status='$status' and seller_id='$seller' $condition order by order_date desc"); */
        $result_set = $this->model_all->getTableDataFromQuery("select seller_invoices.*,(select count(*) from seller_items where sellet_invoice_pk=seller_invoices.id and status='$status') as sc_cnt from  seller_invoices where  picked_by='$user' and  is_picked='1' and is_processed='1' and generate='1' $condition order by order_date desc");
        // echo $this->db->last_query();
        if ($result_set->num_rows() > 0) {


            $k = 0;
            $result["total_amount"] = 0.00;
            foreach ($result_set->result_array() as $row) {
                if ($row["sc_cnt"] > 0) {
                    $row['order_date'] = date("d-m-Y", strtotime($row['order_date']));
                    $result["records"][] = $row;
                    $total_qry = $this->model_all->getTableDataFromQuery("select sum(amount) as total_amount from  seller_items where status='$status'  and sellet_invoice_pk='" . $row['id'] . "'"); //and seller_id='$seller'
                    $total_rs = $total_qry->row_array();
                    if ($total_rs['total_amount'] != "")
                        $result["total_amount"] = $result["total_amount"] + $total_rs['total_amount'];
                    $k++;
                }
            }

            if ($k > 0) {
                $result["status"] = 1;
                $result["message"] = "Records Found";
                $result["total_records"] = $result_set->num_rows();
            } else {
                $result["status"] = 0;
                $result["message"] = "No records Found";
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

}
