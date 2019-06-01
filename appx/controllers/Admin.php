<?php

require(APPPATH . '/libraries/REST_Controller.php');

class Admin extends REST_Controller {

    private $dt;

    public function __construct() {

        parent::__construct();
        $this->load->model('model_all');
        $this->load->library('fcm');
       
    }

     function accept_post() {
        $user = $this->post('user');
        $role = $this->post('role');
        $order = $this->post('order');
        $status = $this->post('status');
        $reason = $this->post('reason');
        $rej_for= $this->post('rej_for');
        $dt =date("Y-m-d H:i:s");
        $status_msg="";
        $result_set = $this->model_all->getTableData("seller_orders",array("id"=>$order));
        //  echo $this->db->last_query();
        if ($result_set->num_rows() > 0) { 
            $row = $result_set->row();
             
            if($row->admin_status==0 || $row->admin_status==2){
               if($status==1)
                     $status_msg = "Accepted";
               else  if($status==2)
                     $status_msg = "Rejected";
               $this->model_all->save(array("order_id" => $order, "status" => $status, "user_id" =>$user , "comments" =>$reason,"rej_for"=>$rej_for,"user_role"=>$role,"modifiedon"=>$dt), 'seller_orders_apprval_track');
               $action_status =$this->model_all->update(array("status"=>$status_msg,"admin_status" => $status,"admin_status_by"=>$user), array("id" => $order), 'seller_orders');
               $this->model_all->save(array("order_id"=>$order,"order_status" =>$status_msg, "changed_on"=>$dt,"comments"=>''),'seller_order_track');


               if($action_status){
                   $result["status"] = 1;
                   if($status==1) {
                     $status_msg = "Approved";
                     $tack_msg= "Accepted";
                   }else  if($status==2) {
                     $status_msg = "Rejected";
                     $tack_msg = "Rejected";
                   }
                      $this->model_all->track_parent_order($order,$tack_msg);
                     // Notification  
                     
                     
                      
        $order_string =  $row->order_id; 
        $order_id =  $row->id;            
        $notify_data = $this->model_all->getDealerExecutive($row->orderedby,$row->branch_id);
        
        if($notify_data["dealer"]["fcm_key"]!=""){
            $payload = array();
            $data = array();
            $payload['title'] = "Welcome to Nova";
            $body = "Your order ".$order_string." has ".$status_msg." by Finance Admin";
            $payload['body'] = $body;  /// Message goes here
            $payload['icon'] = "";  // Name of the icon in the play store
            $payload['click_action'] = "mainactivity";  // For android click activity
            $data['id'] = $order_id;  // For custom value if any
            $payload['to'] = $notify_data["dealer"]["fcm_key"];   // Receiver FCM id
            $data['role'] = "DEALER";   // For custom value if any
            $this->model_all->save(array("notification"=>$body,"notify_type"=>"approved","user_role"=>"DEALER","user_id"=>$row->orderedby,"branch"=>$row->branch_id,"is_seen"=>"N","notifiy_on"=>date("Y-m-d H:i:s"),"related_id"=>$order_id),"notifications");
            $this->fcm->send($payload['to'], $payload, $data);

        }

        if($notify_data["se"]["fcm_key"]!=""){
            $payload = array();
            $data = array();
            $payload['title'] = "Welcome to Nova";
            $body = "The order ".$order_string." has ".$status_msg." by Finance Admin which was placed by ".$notify_data["dealer"]["company_name"]."(".$notify_data["dealer"]["dealer_code"].")"; /// Message goes here;
            $payload['body'] = $body; 
            $payload['icon'] = "";  // Name of the icon in the play store
            $payload['click_action'] = "mainactivity";  // For android click activity
            $data['id'] = $order_id;  // For custom value if any
            $payload['to'] = $notify_data["se"]["fcm_key"];   // Receiver FCM id
            $data['role'] = "SE";   // For custom value if any
            $this->model_all->save(array("notification"=>$body,"notify_type"=>"approved","user_role"=>"SE","user_id"=>$notify_data["se"]["id"],"branch"=>$row->branch_id,"is_seen"=>"N","notifiy_on"=>date("Y-m-d H:i:s"),"related_id"=>$order_id),"notifications");
            $this->fcm->send( $payload['to'], $payload, $data);

        }
                     
                     
                     
                     
                     
                     
                     
                     // Notification
                     
                     
                     
                     

                   $result["message"] = "Order ".$status_msg." Successfully";
               }
            }else{
               $result["status"] = 0;
               $result["message"] = "Order already approved";
            }
           
        } else {
            $result["status"] = 0;
            $result["message"] = "No records Found";
            
        }

        $this->response($result, 200);
        exit;

     }  

    function orders_get() {
        $user = $this->get('user');
        $fromdate = $this->get('fromdate');
        $todate = $this->get('todate');
        $status = $this->get('status');
        $branch = $this->get('branch');
        $condition="";
        if ($status == "Cancelled") {
            $condition .= " and o.status='Cancelled'";
        } else if ($status == "Ordered") {
            $condition .= " and o.status='Ordered' and o.fa_status='1' and o.is_transfered!='2'";
        } else if ($status == "Approved") {
            $condition .= " and o.status NOT IN('Cancelled','Rejected')  and  o.admin_status='1'  ";  // and delivery_recieved='1'  o.fa_status='1' and
        } else if ($status == "Delivered") {
            $condition .= " and o.status='Delivered' and o.delivery_accept='1' ";  // and delivery_recieved='1'
        } else if ($status == "Received") {
            $condition .= " and o.status='Delivered' and o.delivery_accept='1' ";
        } else if ($status == "Rejected") {
            $condition .= " and (o.status='Rejected' or ((o.fa_status='2') or (o.fa_status='1' and o.admin_status_by='$user' and o.admin_status='2') or (o.fa_status='2' and  o.admin_status='2')))";
        } else if ($status == "Pending") {
            $condition .= " and o.status not in('Cancelled','Delivered','Ordered')";
        } else if ($status == "track") {
            $condition .= " and o.status not in('Cancelled')";
        }
        
        if($branch!=""){
        
           $condition .= " and b.id ='$branch'";
        }

        if ($fromdate != "" && $todate == "") {
            $fromdate = date("Y-m-d", strtotime($fromdate));
            $condition .= " and date(o.`orderedon`)='$fromdate'";
        } else if ($fromdate != "" && $todate != "") {
            $fromdate = date("Y-m-d", strtotime($fromdate));
            $todate = date("Y-m-d", strtotime($todate));
            $condition .= " and o.`orderedon` between '$fromdate 00:00:00' and '$todate 23:59:59'";
        }
        $result_set = $this->model_all->getTableDataFromQuery("select s.company_name as dealer_name,s.dealer_code,s.id as dealer_id,s.mobile as dealer_contact, o.id,o.order_id,o.orderedon,o.status,o.orderedby,o.created_by,o.order_value,o.payment_type,o.credit_date,o.parent_order from seller_orders o,sellers s,branches b where o.orderedby=s.id and o.branch_id=b.id $condition order by o.orderedon desc");
        //  echo $this->db->last_query();
        if ($result_set->num_rows() > 0) {
            $result["status"] = 1;
            $result["message"] = "Records Found";
            $result["total_records"] = $result_set->num_rows();
            foreach ($result_set->result_array() as $row) {
                $row['orderedon'] = date("d-m-Y", strtotime($row['orderedon']));
                if($row["orderedby"]!=$row["created_by"]){

                     $parent_string="";
                     if($row["parent_order"]!=0){
                        $parent_qry = $this->model_all->getTableDataFromQuery("select o.created_by,o.orderedby from seller_orders o where o.id='$row[parent_order]'");
                        if($parent_qry->num_rows()>0){
                           $parent_rs = $parent_qry->row();
                           if($parent_rs->orderedby!=$parent_rs->created_by){
                              $parent_order_qry = $this->model_all->getTableDataFromQuery("select e.first_name as emp_name,e.uniq_id as code,b.name as branch_name from employees e,branches b where find_in_set(b.id,e.branch) and e.id='$parent_rs->created_by'");
                           }else{
                              $parent_order_qry = $this->model_all->getTableDataFromQuery("select e.first_name as emp_name,e.dealer_code as code,b.name as branch_name from sellers e,branches b where find_in_set(b.id,e.branch) and e.id='$parent_rs->orderedby'");

                           }

                           if($parent_order_qry->num_rows()>0){

                              $parent_order_rs=$parent_order_qry->row();

                              $parent_string = "( Placed By : ".$parent_order_rs->emp_name."-".$parent_order_rs->code." )";
                           }
                        }

                     }

                     
                     $emp_qry = $this->model_all->getTableDataFromQuery("select e.first_name,e.last_name,e.mobile,b.name as branch_name from employees e,branches b where find_in_set(b.id,e.branch) and e.id='$row[created_by]'");
                     if($emp_qry->num_rows()>0){
                          $emp_rs=$emp_qry->row();
                          $row["takenby_name"] = $emp_rs->first_name." ".$emp_rs->last_name.$parent_string;
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
 
                if($row["status"]=="Rejected"){
                     $apprv_qry = $this->model_all->getTableDataFromQuery("select r.rej_point from seller_orders_apprval_track so,rejection_points r where so.order_id=$row[id] and so.rej_for=r.id and so.order_id='$row[id]' order by so.id desc limit 0,1");
                     if($apprv_qry->num_rows()>0){
                          $apprv_rs=$apprv_qry->row();
                          $row["reason"] = $apprv_rs->rej_point;
                     }
                     
                }
                if($row['credit_date']!=""){
                  $row['credit_date'] = date("d-m-Y", strtotime($row['credit_date']));
                }
              
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

}