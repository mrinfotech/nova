<?php

require(APPPATH . '/libraries/REST_Controller.php');

class Dealerorders extends REST_Controller {

    public function __construct() {
        parent::__construct();
        $this->load->model('model_all');
        $this->load->library('fcm');

    }

    //API - Fetch All Pincodes
    function list_get() {
        $user = $this->get('user');
        $fromdate = $this->get('fromdate');
        $todate = $this->get('todate');
        $status = $this->get('status');
        $role = $this->get('role');
       
        $condition = " and order_id!=''";
        $branch = $this->get('branch');
        if($branch != "") {
           if($role!="DEALER")
            $condition .= " and o.branch_id='$branch'";
        }
   
        
       if(($status!= "Received" && $status!= "Delivered" && $status!= "Rejected" && $status!="rejected") && ($role=="DEALER" || $role=="SE"))
            $condition .= " and o.parent_order='0'";  //  and o.parent_order='0'
       
        if($role=="DEALER" && $user!="")
           $condition .= " and o.orderedby='$user'";  //  and o.parent_order='0'
        else if($role=="SE" && $user!="")  {
           
           $seller_info  = "";
           //echo "select GROUP_CONCAT(`seller`) as seller_info from branch_dealers where sales_manager='$user' and branch='$branch'";
           $deal_query = $this->model_all->getTableDataFromQuery("select GROUP_CONCAT(`seller`) as seller_info from branch_dealers where sales_manager='$user' and branch='$branch'");
           if($deal_query->num_rows()>0){
                $deal_rs = $deal_query->row();
                $seller_info  = $deal_rs->seller_info;
                if($seller_info!=""){
                  $seller_info = trim($seller_info,",");
                  $condition .= " and (o.orderedby IN ($seller_info)  or o.created_by='$user')";
                
                }else{
                
                  $condition .= " and o.created_by='$user'";
                }
           
           
              
           }else{
               $condition .= " and o.created_by='$user'";
           
           }
           
           
           
        }
           
           
           
           

        if ($status == "Cancelled") {
            $condition .= " and o.status='Cancelled'";
        } else if ($status == "Ordered") {
            $condition .= " and o.status='Ordered'";
        } else if ($status == "Delivered") {
            $condition .= " and o.status='Delivered' and o.seller_accept='1' ";  // and delivery_recieved='1'
        } else if ($status == "Received") {
            $condition .= " and o.status='Delivered' and o.dboy_accept='1'";
        } else if ($status == "Rejected" || $status == "rejected") {
            $condition .= " and o.status='Delivered' and o.delivery_reject='1'";
        } else if ($status == "Pending") {
            $condition .= " and o.status not in ('Cancelled','Delivered','Ordered')";
        } else if ($status == "track") {
            $condition .= " and o.status not in ('Cancelled')";
        } else if ($status == "Denied") {
            $condition .= " and ((o.fa_status='2') or (o.fa_status='1' and  o.admin_status='2') or (o.fa_status='2' and o.admin_status='2'))";
        } else if ($status == "Dispatch_Reject") {
            $condition .= " and o.status='Rejected' and o.dboy_accept='2'";
        } 

        
       

        if ($fromdate != "" && $todate == "") {
            $fromdate = date("Y-m-d", strtotime($fromdate));
            $condition .= " and date(o.`orderedon`)='$fromdate'";
        } else if ($fromdate != "" && $todate != "") {
            $fromdate = date("Y-m-d", strtotime($fromdate));
            $todate = date("Y-m-d", strtotime($todate));
            $condition .= " and o.`orderedon` between '$fromdate 00:00:00' and '$todate 23:59:59'";
        }

      
   

        $result_set = $this->model_all->getTableDataFromQuery("select o.id, o.order_id, o.orderedon, o.status, o.orderedby, o.created_by,o.order_value,o.payment_type,o.credit_date,s.id as dealer_id,s.company_name as dealer_name,o.parent_order  from seller_orders o,sellers s where o.orderedby=s.id  $condition order by o.orderedon desc");
       
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


    function forward_post() {
       $order = $this->post('order');
       $user = $this->post('user');
       $affected_rows = $this->model_all->update(array("forward_by"=>$user),array("id"=>$order),"seller_orders");
       if($affected_rows){
            $result["status"] = 1;
            $result["message"] = "The order has forwarded successfully";
            
       }else{
             $result["status"] = 0;
             $result["message"] = "Some thing went wrong. Please do try later.";
 
       }
       $this->response($result, 200);

    }

    function details_get() {
        $order = $this->get('order');
        $status = $this->get('status');
        $role = $this->get('role');
        $condition ="";
        $req_table="";
        $req_condition="";
        $req_column ="";
        
        if($status=="delivered"){  
            $req_condition="and sd.order_item_id=o.id";
            $req_table=",seller_pack_details sd";
            $req_column =",sd.delivered_qty";
            $condition =" and o.action_status='1'";
            
        }
        if($status=="rejected" && $role!="FM" && $role!="ADMIN"){
           $req_condition="and sd.order_item_id=o.id";
            $req_table=",seller_pack_details sd";
            $req_column =",sd.delivered_qty";
            $condition =" and o.action_status='1'";
           $condition =" and ((o.action_status='2') or (o.action_status='1' and o.picked_qty<sd.delivered_qty))";
        }

        if($role!="DEALER"){

           

        }

        
        
        $result_set = $this->model_all->getTableDataFromQuery("SELECT o.* $req_column, b.name as seller,i.id as itemid,i.itemname,i.brand,bp.margin_price as sellingprice,bp.pay FROM `seller_order_items` o, items i ,branches b,branch_prices bp,item_prices ip 
 $req_table where bp.id=o.branch_price_id and bp.branch_id=b.id  and bp.itemprice_id = ip.id and ip.item_id=i.id and o.orderid='$order' $req_condition  $condition order by o.action_status asc");
        if ($result_set->num_rows() > 0) {
            
         
            $store_query = $this->model_all->getTableDataFromQuery("select CONCAT(s.first_name,s.last_name)  as name,a.pincode,a.address,a.door_no,a.street_name,a.landmark,a.city,d.district,st.state,o.remarks,o.status,o.est_date,o.est_time,o.delivery_charges,o.service_charge,o.fa_status,o.admin_status,o.payment_type,o.reference_no,o.credit_date,o.paid,s.mobile,s.contact1,o.orderedby,o.created_by,o.order_id,o.forward_by,o.parent_order,o.is_transfered from sellers s,seller_orders o,addresses a,countries c,states st,districts d where s.id=o.orderedby and o.id='$order' and a.user_id=s.id and (a.user_role='seller' or a.user_role='DEALER') and a.is_default='1' and a.country=c.id and a.state=st.id and d.id=a.district  and st.country=c.id and d.state=st.id");


            $deliver_charges = 0.00;
            $total_sur_charge = 0.00;

            if ($store_rs = $store_query->row()) {
             
                /* For Denied Reason  */
            $result["remarks"] = (!empty($store_rs->remarks)?$store_rs->remarks:"NA");      
            $result["denied_reason"] = "";
            $result["denied_person"] = "";
            if ($status == "Denied") {
                if ($store_rs->fa_status == 2) {
                    //seller_orders_apprval_track
                    $req_qry = $this->model_all->getTableDataFromQuery("select r.rej_point,e.first_name,e.last_name from seller_orders_apprval_track s,rejection_points r,employees e where s.rej_for=r.id and s.status='2' and s.user_role='FM' and s.user_id=e.id order by s.id desc limit 0,1");
                    if ($req_qry->num_rows() > 0) {
                        $req_rs = $req_qry->row();
                        $result["denied_reason"] = $req_rs->rej_point;
                        $result["denied_person"] = ucwords($req_rs->first_name . " " . $req_rs->last_name);
                    }
                } else if ($store_rs->admin_status == 2) {
                    $req_qry = $this->model_all->getTableDataFromQuery("select r.rej_point,e.first_name,e.last_name from seller_orders_apprval_track s,rejection_points r,employees e where s.rej_for=r.id and s.status='2' and s.user_role='ADMIN' and s.user_id=e.id order by s.id desc limit 0,1");
                    if ($req_qry->num_rows() > 0) {
                        $req_rs = $req_qry->row();
                        $result["denied_reason"] = $req_rs->rej_point;
                        $result["denied_person"] = ucwords($req_rs->first_name . " " . $req_rs->last_name);
                    }
                }
            }



                /* For Denied Reason end */



                $result["store_name"] = $store_rs->name;
                $result["parent_order"] = $store_rs->parent_order; 
                $result["is_transfered"] = $store_rs->is_transfered;
                $result["order_status"] = $store_rs->status;
                $result["delivery_date"] = date("d-M-Y", strtotime($store_rs->est_date));
                $result["delivery_time"] = $store_rs->est_time;   
                $result["created_by"] = $store_rs->created_by;
                $deliver_charges = $store_rs->delivery_charges;
                $result["delivery_charges"] = $store_rs->delivery_charges;
                $total_sur_charge = $store_rs->service_charge;
                $seller_address ="";
                if ($store_rs->address=="") {
                  if ($store_rs->door_no != "" && $store_rs->door_no!="NA")
                    $seller_address .= $store_rs->door_no . ",";
                  if ($store_rs->street_name != "" && $store_rs->street_name!="NA") {
                    $seller_address .= $store_rs->street_name. ",";
                  }
                  if ($store_rs->landmark != "" && $store_rs->landmark!="NA") {
                    $seller_address .= $store_rs->landmark. ",";
                  }
               }else{
                  $seller_address .= $store_rs->address;
               }
                if ($store_rs->district != "" && $store_rs->district!="NA") {
                    $seller_address .= $store_rs->district. ",";
                }
                if ($store_rs->state != "" && $store_rs->state!="NA") {
                    $seller_address .= $store_rs->state. ",";
                }
                if ($store_rs->pincode!= "" && $store_rs->pincode!="NA") {
                    $seller_address .= " Pin: ".$store_rs->pincode. ".";
                }

                $result["store_address"] = $seller_address;
                $result['order_id'] = $store_rs->order_id;
                $result['fa_status'] = $store_rs->fa_status;
                $result['forward_by'] = $store_rs->forward_by;
                $result['admin_status'] = $store_rs->admin_status;
                
                $result['fa_status'] = $store_rs->fa_status;
                $result['payment_type'] = $store_rs->payment_type;
                $result['reference_no'] = $store_rs->reference_no;
                if($store_rs->credit_date!=""){
                   $result['credit_date'] = date("d-M-y",strtotime($store_rs->credit_date));
                }else{
                   $result['credit_date'] = "";
                }
                
                $result['paid'] = $store_rs->paid;
               
                $result['mobile'] ="NA";
                if($store_rs->mobile!="" && $store_rs->mobile!=0){
                   $result['mobile'] = $store_rs->mobile;
                }else if($store_rs->contact1!="" && $store_rs->contact1!=0) {
                   $result['mobile'] = $store_rs->contact1;
                }
                if($store_rs->orderedby!=$store_rs->created_by){

                    $parent_string="";
                     if($store_rs->parent_order!=0){
                        $parent_qry = $this->model_all->getTableDataFromQuery("select o.created_by,o.orderedby from seller_orders o where o.id='$store_rs->parent_order'");
                        if($parent_qry->num_rows()>0){
                           $parent_rs = $parent_qry->row();
                           if($parent_rs->orderedby!=$parent_rs->created_by){
                              $parent_order_qry = $this->model_all->getTableDataFromQuery("select e.first_name as emp_name,e.uniq_id as code,e.mobile as contact,b.name as branch_name from employees e,branches b where find_in_set(b.id,e.branch) and e.id='$parent_rs->created_by'");
                           }else{
                              $parent_order_qry = $this->model_all->getTableDataFromQuery("select e.first_name as emp_name,e.dealer_code as code,e.contact1 as contact,b.name as branch_name from sellers e,branches b where find_in_set(b.id,e.branch) and e.id='$parent_rs->orderedby'");

                           }

                           if($parent_order_qry->num_rows()>0){

                              $parent_order_rs=$parent_order_qry->row();

                              $parent_string = "\r\n( Placed By : ".$parent_order_rs->emp_name."-".$parent_order_rs->code;
                              if($parent_order_rs->contact!="" && $parent_order_rs->contact!="NA"){
                                 $parent_string = $parent_string."\r\n Contact :".$parent_order_rs->contact;
                              }
                              $parent_string = $parent_string.")";
                           }
                        }

                     }




                     $emp_qry = $this->model_all->getTableDataFromQuery("select e.first_name as emp_name,e.mobile,e.ofc_contact,b.name as branch_name from employees e,branches b where find_in_set(b.id,e.branch) and e.id='$store_rs->created_by'");
                     if($emp_qry->num_rows()>0){
                          $emp_rs=$emp_qry->row();
                          $result["takenby_name"] = $emp_rs->emp_name.$parent_string;
                          $result["takenby_branch"] = $emp_rs->branch_name;
                          if($emp_rs->ofc_contact!=""){
                            $result["takenby_contact"] = $emp_rs->ofc_contact;
                          }else{
                            $result["takenby_contact"] = $emp_rs->mobile;
                          }
                          
                     }else{
                          $result["takenby_name"] = "-";
                          $result["takenby_branch"] = "-";
                          $result["takenby_contact"] = "-"; 
                     }
                     
                }else{
                    $result["takenby_name"] = "Self";
                    $result["takenby_branch"] = "-";
                    $result["takenby_contact"] = "-"; 
                }
                
                
                
            }




            



            $result["status"] = 1;
            $result["message"] = "Records Found";
            $result["total_rows"] = $result_set->num_rows();
            $total_units = 0;
            $total_savings = 0;
            $total_pay = 0;
            $total_items = 0;
            foreach ($result_set->result_array() as $row) {

                
               if($role!="DEALER")
                $row["qty"] = $row["qty"]-$row["transfer_qty"];
                
                
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



        
         if(($status=="rejected" || $status=="delivered") && $role!="FM" && $role!="ADMIN"){
             if($row["action_status"]='1'){
           
               if($row["picked_qty"]<$row["delivered_qty"]){
                $row["qty"] =   $row["delivered_qty"]-$row["picked_qty"]; 
              
               }else{
                   $row["qty"] =   $row["delivered_qty"]; 

               }
             }
              if($row["action_status"]='2'){
                 $row["qty"] =   $row["picked_qty"];

              }
         }       
        
             /* For Transfer Details Purpose */
                if($row['tax_type']=="gst"){
                   $row['total_cost'] = ($row['amount']+round(($row['amount']*$row['cgst']/100)+($row['amount']*$row['sgst']/100),2))*$row['qty'];
                }else if( $row['tax_type']=="igst"){
                   $row['total_cost'] = ($row['amount']+round(($row['amount']*$row['igst']/100),2))*$row['qty'];

                } 
             /* For Transfer Details Purpose */
         
                
                $row['discount'] = ($row['mrp'] - $row['paid']);
                $row['total_price'] =  $row['total_cost']; // ($row['qty'] * $row['paid']);
                $row['margin'] = round((($row['mrp'] - $row['paid']) / $row['paid']) * 100, 2);
                if($status!="rejected" && $row['action_status']!='2') {
                   $total_units += $row['qty'];
                   $total_savings += ($row['mrp'] - $row['paid']) * $row['qty'];
                   $total_pay += $row['total_price']; //($row['qty'] * $row['paid']);
                   $total_items++;
                }else if($status=="rejected"){
                   $total_units += $row['qty'];
                   $total_savings += ($row['mrp'] - $row['paid']) * $row['qty'];
                   $total_pay +=  $row['total_price'];  // ($row['qty'] * $row['paid']);
                   $total_items++;
                }
                //$total_sur_charge += 0.00;
                $row['tax_string']="No Tax";
                $row['single_qty'] = $row['qty'];
                if($row['tax_type']=="gst"){
                   $row['qty'] = $row['qty']."\n + \n ".$row['sgst']." % SGST + ".$row['cgst']." % CGST";
                }else if($row['tax_type']=="igst"){
                   $row['qty'] = $row['qty']."\n + \n ".$row['igst']." % IGST";
                }

               
                 



                $result["records"][] = $row;
            }
            $result['total_units'] = $total_units;
            $result['total_items'] = $total_items;
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
        $order_id = $order;
        
        $status_qry = $this->model_all->getTableDataFromQuery("select * from seller_orders where id='$order'")->row_array();
        if ($status_qry['status'] == "Cancelled") {
            $result["status"] = 0;
            $result["message"] = "Order already Cancelled.";
        } else if ($status_qry['status'] != "Ordered") {
            $result["status"] = 0;
            $result["message"] = "Order already Processed. So we cannot cancel this.";
        } else {
           $dt = date("Y-m-d H:i:s");
           $user = $status_qry["orderedby"];
           $branch = $status_qry["branch_id"];
           $order_string = $status_qry["order_id"];
           $order_value = $status_qry["order_value"];
           $particular = "Order ".$order_string." has Cancelled which costs ".$order_value;
           
           
           
            $this->model_all->save(array("order_id" => $order, "order_status" => 'Cancelled', "changed_on" => $dt, "comments" => $reason), 'seller_order_track');
            
             $this->model_all->update(array("status" => 'Cancelled'), array("id" => $order), 'seller_orders');
           
                $order_item_qry = $this->model_all->getTableDataFromQuery("select * from seller_order_items where orderid='$order'");
                /*foreach ($order_item_qry->result() as $order_rs) {
                   $seller_item_qry = $this->model_all->getTableDataFromQuery("select * from seller_items where FIND_IN_SET($order_rs->id,order_item)");
                   foreach ($seller_item_qry->result() as $item_rs) {
                     $qty = $item_rs->qty - $order_rs->qty;
                     $amount = $item_rs->amount - $order_rs->sp_amount;
                     $this->model_all->update(array("qty" => $qty, "amount" => $amount), array("id" => $item_rs->id), "seller_items");
                     //echo $this->db->last_query();
                   }
                }*/
                
        $this->model_all->save(array("user_id" => $user, "user_role" => 'DEALER', "prev_balance" => 0.00,"branch"=>$branch, "order_id" =>$status_qry["id"],"particular"=>$particular, "amount" => $order_value, "reference_no" => $status_qry["order_id"], "transaction_no" =>"", "transaction_mode" => "credit", "payment_mode" => "", "transaction_date" => $dt, "action_by" => $user, "action_role" => "DEALER", "action_date" => $dt, 'status' => '1'), "wallet_history");
       
       if($status_qry["payment_type"]=="credit") // only this line 506 is placed for checking condition if payment_type is credit only
        $this->model_all->getTableDataFromQuery("update sellers set wallet=wallet+$order_value where id='$user'");
        
        $notify_data = $this->model_all->getDealerExecutive($user,$branch);

        if($notify_data["dealer"]["fcm_key"]!=""){

            
            $payload = array();
            $data = array();
            $payload['title'] = "Welcome to Nova";
            $payload['body'] =  $particular;
            $payload['icon'] = "";  // Name of the icon in the play store
            $payload['click_action'] = "mainactivity";  // For android click activity
            $data['id'] = $order_id;  // For custom value if any
            $payload['to'] = $notify_data["dealer"]["fcm_key"];   // Receiver FCM id
            $data['role'] = "DEALER";   // For custom value if any
            $this->model_all->save(array("notification"=>$particular, "notify_type"=>"cancelled", "user_role"=>"DEALER","user_id"=>$user,"branch"=>$branch,"is_seen"=>"N","notifiy_on"=>date("Y-m-d H:i:s")),"notifications");

            $this->fcm->send( $payload['to'], $payload, $data);

        }

        if($notify_data["se"]["fcm_key"]!=""){
            $payload = array();
            $data = array();
            $body = "The order ".$order_string." has been cancelled by ".$notify_data["dealer"]["company_name"]."(".$notify_data["dealer"]["dealer_code"]."). Total cost of the order is : ".$order_value." due to ".$reason;
            $payload['title'] = "Welcome to Nova";
            $payload['body'] =   $body; /// Message goes here
            $payload['icon'] = "";  // Name of the icon in the play store
            $payload['click_action'] = "mainactivity";  // For android click activity
            $data['id'] = $order_id;  // For custom value if any
            $payload['to'] = $notify_data["se"]["fcm_key"];   // Receiver FCM id
            $data['role'] = "SE";   // For custom value if any
             $this->model_all->save(array("notification"=>$body,"notify_type"=>"cancelled","user_role"=>"SE","user_id"=>$notify_data["se"]["id"],"branch"=>$branch,"is_seen"=>"N","notifiy_on"=>date("Y-m-d H:i:s")),"notifications");
            $this->fcm->send( $payload['to'], $payload, $data);

        }


            $result["status"] = 1;
            $result["message"] = "Order Cancelled Successfully";
        }

        $this->response($result, 200);
        exit;
    }

    function track_get() {
        $order = $this->get('order');
        $status_qry = $this->model_all->getTableData("seller_orders", array("id" => $order), "status,is_transfered");
        // echo $this->db->last_query();
        if ($status_qry->num_rows() > 0) {
            $status_rs = $status_qry->row_array();
            $result["order_status"] = $status_rs['status'];
            $result["is_transfered"] = $status_rs['is_transfered'];
            $track_data = $this->model_all->getTableData('seller_order_track', array("order_id" => $order), "order_status,changed_on,comments", "changed_on", "asc");
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



     function do_action_post() {
        $action = $this->post('action');
        $user= $this->post('user');
        $role = $this->post('role');
        
        $order = $this->post('primary_key');
        $item = $this->post('item');
        $qty = $this->post('qty');
        $reason = $this->post('reason');
        $description = $this->post('description');
        $dt = date("Y-m-d H:i:s");
        $action_status = 0;
        $message = "Action not performed. Please try later";

        if ($action == 1) {
            $affected_rows = $this->model_all->update(array("picked_qty" => $qty, "action_status" => '1', "action_time" => $dt, "reason" => $reason, "description" => $description,"picked_by"=>$user,"picked_role"=>$role), array("id" => $item), "seller_order_items");
            if ($affected_rows) {
                $message = "Item Received successfully";
                $action_status = 1;
                $this->model_all->update(array("seller_accept"=>'1'),array("id"=>$order),"seller_orders");

                $get_detail_query = $this->model_all->getTableDataFromQuery("select delivered_qty from seller_pack_details where order_item_id='$item'");
                if($get_detail_query->num_rows()>0){
                  
                   $get_detail_row = $get_detail_query->row();
                  if($qty < $get_detail_row->delivered_qty){
                    $this->model_all->update(array("delivery_reject"=>'1'),array("id"=>$order),"seller_orders");
                  }
                }

                

            }
        } else if ($action == 2) {
            $img_name = "";
            if ($_FILES['rej_img']['size'] > 0 && $_FILES['rej_img']['error'] == 0) {
                $name = "store_".$item."_" . time() . "_" . $_FILES['rej_img']['name'];
                $source_url = $_FILES['rej_img']['tmp_name'];
                $destination_url = "rejections/seller_" . $name;
                if (@move_uploaded_file($source_url, $destination_url)) {
                    $img_name = $name;
                } else {
                    $img_name = "";
                }
            }
            $affected_rows = $this->model_all->update(array("picked_qty" => $qty, "action_status" => '2', "action_time" => $dt, "reason" => $reason, "description" => $description, "action_img" => $img_name,"picked_by"=>$user,"picked_role"=>$role), array("id" => $item), "seller_order_items");
            if ($affected_rows) {
                $this->model_all->update(array("delivery_reject"=>'1'),array("id"=>$order),"seller_orders");
             //   echo $this->db->last_query();
                $message = "Item rejected successfully";
                $action_status = 1;
                $this->model_all->update(array("seller_accept"=>'1'),array("id"=>$order),"seller_orders");
            }
        }

        $result_set1 = $this->model_all->getTableDataFromQuery("SELECT id FROM seller_order_items WHERE orderid ='$order'");
        $result['total_records'] = $result_set1->num_rows();
        $result_set2 = $this->model_all->getTableDataFromQuery("SELECT id FROM seller_order_items where orderid ='$order' and action_status!='0'");
        $result['total_processed'] = $result_set2->num_rows();
        if($result['total_records']==$result['total_processed']){
            $this->model_all->update(array("seller_accept"=>'1'),array("id"=>$order),"seller_orders");
        }


        $result["status"] = $action_status;
        $result["message"] = $message;
        $this->response($result, 200);
        exit;
    }

function final_details_get() {
    $order = $this->get('order');
    $status = $this->get('status');
    $condition = "";
    if ($status == "delivered") {
        $condition = " and o.action_status='1'";
    }
    if ($status == "rejected") {
        $condition = " and ((o.action_status='2') or (o.action_status='1' and o.picked_qty<sd.delivered_qty))";
    }
    $sub_status = 0;
    $result_set = $this->model_all->getTableDataFromQuery("SELECT o.*,sd.delivered_qty, b.name as seller,i.id as itemid,i.itemname,i.brand,bp.margin_price as sellingprice,bp.pay FROM `seller_order_items` o, items i ,branches b,branch_prices bp,item_prices ip,seller_pack_details sd where bp.id=o.branch_price_id and bp.branch_id=b.id  and bp.itemprice_id = ip.id and ip.item_id=i.id and o.orderid='$order' and o.action_status!='2' and sd.order_item_id=o.id $condition order by o.action_status asc");
    if ($result_set->num_rows() > 0) {

        $deliver_charges = 0.00;
        $total_sur_charge = 0.00;

        $result["total_rows"] = $result_set->num_rows();
        $total_units = 0;
        $total_savings = 0;
        $total_pay = 0;
        $total_items = 0;
        $sub_status = 1;
        foreach ($result_set->result_array() as $row) {
            $row['images'] = array();
            if($row["picked_qty"]<$row["delivered_qty"]){
                $row["picked_qty"] =   $row["picked_qty"]; 
              
            }
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
            if ($status != "rejected" && $row['action_status'] != '2') {
                $total_units += $row['qty'];
                $total_savings += ($row['mrp'] - $row['amount']) * $row['qty'];
                $total_pay += ($row['qty'] * $row['amount']);
                $total_items++;
            } else if ($status == "rejected") {
                $total_units += $row['qty'];
                $total_savings += ($row['mrp'] - $row['amount']) * $row['qty'];
                $total_pay += ($row['qty'] * $row['amount']);
                $total_items++;
            }
            //$total_sur_charge += 0.00;
            $row['tax_string']="No Tax";
            if($row['tax_type']=="gst"){
                   $row['qty'] = $row['qty']."\n + \n ".$row['sgst']." % SGST + ".$row['cgst']." % CGST";
            }else if($row['tax_type']=="igst"){
                   $row['qty'] = $row['qty']."\n + \n ".$row['igst']." % IGST";
            }



            $result["records"][] = $row;
        }
        $result['total_units'] = $total_units;
        $result['total_items'] = $total_items;
        $result['total_savings'] = $total_savings;
        $result['total_sur_charge'] = $total_sur_charge;
        $result['sub_total'] = $total_pay;
        $result['total_pay'] = ($total_pay + $total_sur_charge + $deliver_charges);
    }


    $result_set = $this->model_all->getTableDataFromQuery("SELECT o.*,sd.delivered_qty, b.name as seller,i.id as itemid,i.itemname,i.brand,bp.margin_price as sellingprice,bp.pay FROM `seller_order_items` o, items i ,branches b,branch_prices bp,item_prices ip,seller_pack_details sd where bp.id=o.branch_price_id and bp.branch_id=b.id  and bp.itemprice_id = ip.id and ip.item_id=i.id and o.orderid='$order' and (o.action_status='2' or (o.action_status='1' and  o.picked_qty<sd.delivered_qty)) and sd.order_item_id=o.id $condition order by o.action_status asc");
    if ($result_set->num_rows() > 0) {

        $deliver_charges = 0.00;
        $total_sur_charge = 0.00;


        $result["total_rows"] = $result_set->num_rows();
        $total_units = 0;
        $total_savings = 0;
        $total_pay = 0;
        $total_items = 0;
        $sub_status = 1;
        foreach ($result_set->result_array() as $row) {

            if($row["picked_qty"]<$row["delivered_qty"]){
                $row["qty"] =   $row["delivered_qty"]-$row["picked_qty"]; 
              
            }
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
            if ($status == "rejected") {
                $total_units += $row['qty'];
                $total_savings += ($row['mrp'] - $row['amount']) * $row['qty'];
                $total_pay += ($row['qty'] * $row['amount']);
                $total_items++;
            }
            //$total_sur_charge += 0.00;
            $result["rejected_records"][] = $row;
        }
        /* $result['rejected_units'] = $total_units;
          $result['rejected_items'] = $total_items;
          $result['rejected_savings'] = $total_savings;
          $result['rej_total_sur_charge'] = $total_sur_charge;
          $result['rej_sub_total'] = $total_pay;
          $result['reg_total_pay'] = ($total_pay + $total_sur_charge + $deliver_charges); */
    }
    $result["status"] = $sub_status;
    if ($result["status"] == 1) {
        $result["message"] = "Records Found";
        $store_query = $this->model_all->getTableDataFromQuery("select CONCAT(s.first_name,s.last_name)  as name,a.pincode,a.address,a.door_no,a.street_name,a.landmark,a.city,d.district,st.state,o.remarks.o.status,o.est_date,o.est_time,o.delivery_charges,o.service_charge,o.fa_status,o.admin_status,o.payment_type,o.reference_no,o.credit_date,o.paid,o.parent_order,o.is_transfered,s.mobile,s.contact1 from sellers s,seller_orders o,addresses a,countries c,states st,districts d where s.id=o.orderedby and o.id='$order' and a.user_id=s.id and (a.user_role='seller' or a.user_role='DEALER') and a.is_default='1' and a.country=c.id and a.state=st.id and d.id=a.district  and st.country=c.id and d.state=st.id");
        if ($store_rs = $store_query->row()) {
            $result["remarks"] = (!empty($store_rs->remarks)?$store_rs->remarks:"NA"); 
            $result["store_name"] = $store_rs->name;
            $result["parent_order"] = $store_rs->parent_order;
            $result["is_transfered"] = $store_rs->is_transfered;
            $result["order_status"] = $store_rs->status;
            $result["delivery_date"] = date("d-M-Y", strtotime($store_rs->est_date));
            $result["delivery_time"] = $store_rs->est_time;
            $deliver_charges = $store_rs->delivery_charges;
            $result["delivery_charges"] = $store_rs->delivery_charges;
            $total_sur_charge = $store_rs->service_charge;
            $seller_address ="";
                if ($store_rs->address=="") {
                  if ($store_rs->door_no != "" && $store_rs->door_no!="NA")
                    $seller_address .= $store_rs->door_no . ",";
                  if ($store_rs->street_name != "" && $store_rs->street_name!="NA") {
                    $seller_address .= $store_rs->street_name. ",";
                  }
                  if ($store_rs->landmark != "" && $store_rs->landmark!="NA") {
                    $seller_address .= $store_rs->landmark. ",";
                  }
               }else{
                  $seller_address .= $store_rs->address;
               }
                if ($store_rs->district != "" && $store_rs->district!="NA") {
                    $seller_address .= $store_rs->district. ",";
                }
                if ($store_rs->state != "" && $store_rs->state!="NA") {
                    $seller_address .= $store_rs->state. ",";
                }
                if ($store_rs->pincode!= "" && $store_rs->pincode!="NA") {
                    $seller_address .= " Pin: ".$store_rs->pincode. ".";
                }

            $result["store_address"] = $seller_address;
            $result['fa_status'] = $store_rs->fa_status;
            $result['admin_status'] = $store_rs->admin_status;
            
            $result['fa_status'] = $store_rs->fa_status;
            $result['payment_type'] = $store_rs->payment_type;
            if ($store_rs->credit_date != "") {
                $result['credit_date'] = date("d-M-y", strtotime($store_rs->credit_date));
            } else {
                $result['credit_date'] = "";
            }

            $result['paid'] = $store_rs->paid;
            $result['reference_no'] = $store_rs->reference_no;
             $result['mobile'] ="NA";
                if($store_rs->mobile!="" && $store_rs->mobile!=0){
                   $result['mobile'] = $store_rs->mobile;
                }else if($store_rs->contact1!="" && $store_rs->contact1!=0){
                   $result['mobile'] = $store_rs->contact1;

                }
        }
    } else {
        $result["status"] = 0;
        $result["message"] = "No records Found";
    }


    $this->response($result, 200);
    exit;
}


   function delivery_details_get() {
        $order = $this->get('order');
        $status = $this->get('status');
        $condition = "";
        if ($status == "delivered") {
            $condition = " and o.action_status='1'";
        }
        if ($status == "rejected") {
            $condition = " and ((o.action_status='2') or (o.action_status='1' and o.picked_qty<sd.delivered_qty))";
        }
        $sub_status = 0;


        //echo "SELECT o.*,sd.delivered_qty, b.name as seller,i.id as itemid,i.itemname,i.brand,bp.margin_price as sellingprice,bp.pay FROM `seller_order_items` o, items i ,branches b,branch_prices bp,item_prices ip,seller_pack_details sd where bp.id=o.branch_price_id and bp.branch_id=b.id  and bp.itemprice_id = ip.id and ip.item_id=i.id and o.orderid='$order' and o.action_status!='2' and sd.order_item_id=o.id and sd.packed_qty!=0 and sd.status='1' $condition order by o.action_status asc";
        $result_set = $this->model_all->getTableDataFromQuery("SELECT o.*,sd.delivered_qty, b.name as seller,i.id as itemid,i.itemname,i.brand,bp.margin_price as sellingprice,bp.pay FROM `seller_order_items` o, items i ,branches b,branch_prices bp,item_prices ip,seller_pack_details sd where bp.id=o.branch_price_id and bp.branch_id=b.id  and bp.itemprice_id = ip.id and ip.item_id=i.id and o.orderid='$order' and o.action_status!='2' and sd.order_item_id=o.id and sd.packed_qty!=0 and sd.status='1' $condition order by o.action_status asc");
        if ($result_set->num_rows() > 0) {

            $deliver_charges = 0.00;
            $total_sur_charge = 0.00;

            $result["total_rows"] = $result_set->num_rows();
            $total_units = 0;
            $total_savings = 0;
            $total_pay = 0;
            $total_items = 0;
            $sub_status = 1;
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

  // mrp -> mrp , pay->amount , profit-> discount , margin -> margin , total_cost->total_price


                

                
                if ($status != "rejected" && $row['action_status'] != '2') {

                    if ($row['action_status'] == '0') {
                        $row['mrp'] =  $row['mrp']*$row['pack_qty'];
                        $row['paid'] =  $row['paid']*$row['pack_qty'];
                       // $row['amount'] =  $row['paid']*$row['pack_qty'];
                        $row['discount'] = ($row['mrp'] - $row['paid']);
                        $row['margin'] = round((($row['mrp'] - $row['paid']) / $row['paid']) * 100, 2);
                        $row['total_price'] = ($row['delivered_qty'] * $row['paid']);
                        $total_units += $row['delivered_qty'];
                        $total_savings += ($row['mrp'] - $row['paid']) * $row['delivered_qty'];
                        $total_pay += ($row['delivered_qty'] * $row['paid']);
                    } else if ($row['action_status'] == '1') {
                        $row['mrp'] =  $row['mrp']*$row['pack_qty'];
                        $row['paid'] =  $row['paid']*$row['pack_qty'];
                       // $row['amount'] =  $row['paid']*$row['pack_qty'];
                        $row['discount'] = ($row['mrp'] - $row['paid']);
                        $row['margin'] = round((($row['mrp'] - $row['paid']) / $row['paid']) * 100, 2);
                        $row['total_price'] = ($row['picked_qty'] * $row['paid']);
                        $total_units += $row['picked_qty'];
                        $total_savings += ($row['mrp'] - $row['paid']) * $row['picked_qty'];
                        $total_pay += ($row['picked_qty'] * $row['paid']);
                    }



                    $total_items++;
                } else if ($status == "rejected") {
                    $total_units += $row['delivered_qty'];
                    $total_savings += ($row['mrp'] - $row['paid']) * $row['delivered_qty'];
                    $total_pay += ($row['delivered_qty'] * $row['paid']);
                    $total_items++;
                }
                //$total_sur_charge += 0.00;
                $result["records"][] = $row;
            }
            $deliver_charges = round($deliver_charges,2);
            $result['total_units'] = $total_units;
            $result['total_items'] = $total_items;
            $result['total_savings'] = $total_savings;
            $result['total_sur_charge'] = $total_sur_charge;
            $result['sub_total'] = $total_pay;
            $deliver_charges = ($total_pay / 100);
            $deliver_charges = round($deliver_charges,2);
            $result["delivery_charges"] = $deliver_charges;
            $result['total_pay'] = ($total_pay + $total_sur_charge + $deliver_charges);
        }

        if ($status != "rejected") {
            $condition = " and o.action_status='2'";
        }

        $result_set = $this->model_all->getTableDataFromQuery("SELECT o.*,sd.delivered_qty, b.name as seller,i.id as itemid,i.itemname,i.brand,bp.margin_price as sellingprice,bp.pay FROM `seller_order_items` o, items i ,branches b,branch_prices bp,item_prices ip,seller_pack_details sd where bp.id=o.branch_price_id and bp.branch_id=b.id  and bp.itemprice_id = ip.id and ip.item_id=i.id and o.orderid='$order' and sd.order_item_id=o.id and sd.packed_qty!=0 and sd.status='1' $condition order by o.action_status asc");
        if ($result_set->num_rows() > 0) {


            $deliver_charges = 0.00;
            $total_sur_charge = 0.00;


            $result["total_rows"] = $result_set->num_rows();
            if ($status == "rejected") {
                $total_units = 0;
                $total_savings = 0;
                $total_pay = 0;
                $total_items = 0;
            }
            $sub_status = 1;
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


                if ($row['action_status'] == '1') {
                    $rej_qty = $row['delivered_qty'] - $row['picked_qty'];
                    $row['qty'] = $rej_qty;  // For displaing purpose in app for running on single variable name
                    $row['total_price'] = ($rej_qty * $row['paid']);
                    if ($status == "rejected") {
                        $total_units += $rej_qty;
                        $total_savings += ($row['mrp'] - $row['paid']) * $rej_qty;
                        $total_pay += ($rej_qty * $row['paid']);
                        $total_items++;
                    }
                } else if ($row['action_status'] == '2') {
                    $row['qty'] = $row['delivered_qty'];
                    $row['total_price'] = ($row['delivered_qty'] * $row['paid']);
                    if ($status == "rejected") {
                        $total_units += $row['delivered_qty'];
                        $total_savings += ($row['mrp'] - $row['paid']) * $row['delivered_qty'];
                        $total_pay += ($row['delivered_qty'] * $row['paid']);
                        $total_items++;
                    }
                }
                $row['discount'] = ($row['mrp'] - $row['paid']);
                $row['margin'] = round((($row['mrp'] - $row['paid']) / $row['amount']) * 100, 2);

                //$total_sur_charge += 0.00;
                $result["rejected_records"][] = $row;
            }
            if ($status == "rejected") {
                $result['total_units'] = $total_units;
                $result['total_items'] = $total_items;
                $result['total_savings'] = $total_savings;
                $result['total_sur_charge'] = $total_sur_charge;
                $result['sub_total'] = $total_pay;
                $deliver_charges = ($total_pay / 100);
                $result["delivery_charges"] = $deliver_charges;
                $result['total_pay'] = ($total_pay + $total_sur_charge + $deliver_charges);
            }
            /* $result['rejected_units'] = $total_units;
              $result['rejected_items'] = $total_items;
              $result['rejected_savings'] = $total_savings;
              $result['rej_total_sur_charge'] = $total_sur_charge;
              $result['rej_sub_total'] = $total_pay;
              $result['reg_total_pay'] = ($total_pay + $total_sur_charge + $deliver_charges); */




        }
        $result["status"] = $sub_status;
        if ($result["status"] == 1) {
            $result["message"] = "Records Found";
            $store_query = $this->model_all->getTableDataFromQuery("select CONCAT(s.first_name,s.last_name)  as name,a.pincode,a.address,a.door_no,a.street_name,a.landmark,a.city,d.district,st.state,o.remarks,o.status,o.est_date,o.est_time,o.delivery_charges,o.service_charge,o.fa_status,o.admin_status,o.payment_type,o.reference_no,o.credit_date,o.paid,s.mobile,s.contact1,o.created_by,o.orderedby,o.parent_order,o.is_transfered from sellers s,seller_orders o,addresses a,countries c,states st,districts d where s.id=o.orderedby and o.id='$order' and a.user_id=s.id and (a.user_role='seller' or a.user_role='DEALER') and a.is_default='1' and a.country=c.id and a.state=st.id and d.id=a.district  and st.country=c.id and d.state=st.id");
            if ($store_rs = $store_query->row()) 
                {
                $result["remarks"] = (!empty($store_rs->remarks)?$store_rs->remarks:"NA");
                $result["store_name"] = $store_rs->name;
                $result["parent_order"] = $store_rs->parent_order;
                $result["is_transfered"] = $store_rs->is_transfered;
                
                $result["order_status"] = $store_rs->status;
                $result["delivery_date"] = date("d-M-Y", strtotime($store_rs->est_date));
                $result["delivery_time"] = $store_rs->est_time;
                $deliver_charges = $store_rs->delivery_charges;
                // $result["delivery_charges"] = ($store_rs->delivery_charges/100);//$store_rs->delivery_charges;
                $total_sur_charge = $store_rs->service_charge;
                $seller_address ="";
                if ($store_rs->address=="") {
                  if ($store_rs->door_no != "" && $store_rs->door_no!="NA")
                    $seller_address .= $store_rs->door_no . ",";
                  if ($store_rs->street_name != "" && $store_rs->street_name!="NA") {
                    $seller_address .= $store_rs->street_name. ",";
                  }
                  if ($store_rs->landmark != "" && $store_rs->landmark!="NA") {
                    $seller_address .= $store_rs->landmark. ",";
                  }
               }else{
                  $seller_address .= $store_rs->address;
               }
                if ($store_rs->district != "" && $store_rs->district!="NA") {
                    $seller_address .= $store_rs->district. ",";
                }
                if ($store_rs->state != "" && $store_rs->state!="NA") {
                    $seller_address .= $store_rs->state. ",";
                }
                if ($store_rs->pincode!= "" && $store_rs->pincode!="NA") {
                    $seller_address .= " Pin: ".$store_rs->pincode. ".";
                }

                $result["store_address"] = $seller_address;
                $result['fa_status'] = $store_rs->fa_status;
                $result['admin_status'] = $store_rs->admin_status;
              
                $result['fa_status'] = $store_rs->fa_status;
                $result['payment_type'] = $store_rs->payment_type;
                if ($store_rs->credit_date != "") {
                    $result['credit_date'] = date("d-M-y", strtotime($store_rs->credit_date));
                } else {
                    $result['credit_date'] = "";
                }

                $result['paid'] = $store_rs->paid;
                $result['reference_no'] = $store_rs->reference_no;
                $result['mobile'] ="NA";
                if($store_rs->mobile!="" && $store_rs->mobile!=0){
                   $result['mobile'] = $store_rs->mobile;
                }else if($store_rs->contact1!="" && $store_rs->contact1!=0) {
                   $result['mobile'] = $store_rs->contact1;

                }
                
                if ($store_rs->orderedby != $store_rs->created_by) {

                    $emp_qry = $this->model_all->getTableDataFromQuery("select e.first_name as emp_name,e.mobile,b.name as branch_name from employees e,branches b where e.branch=b.id and e.id='$store_rs->created_by'");
                    if ($emp_qry->num_rows() > 0) {
                        $emp_rs = $emp_qry->row();
                        $result["takenby_name"] = $emp_rs->emp_name;
                        $result["takenby_branch"] = $emp_rs->branch_name;
                        $result["takenby_contact"] = $emp_rs->mobile;
                    } else {
                        $result["takenby_name"] = "-";
                        $result["takenby_branch"] = "-";
                        $result["takenby_contact"] = "-";
                    }
                } else {
                    $result["takenby_name"] = "Self";
                    $result["takenby_branch"] = "-";
                    $result["takenby_contact"] = "-";
                }
            }
        } else {
            $result["status"] = 0;
            $result["message"] = "No records Found";
        }


        $this->response($result, 200);
        exit;
    }
}

