<?php

require(APPPATH . '/libraries/REST_Controller.php');

class Master extends REST_Controller {

    public function __construct() {
        parent::__construct();
        $this->load->model('model_all');
    }

    //API - Fetch All Pincodes
    function rejectionpoints_get() {
        $user = $this->get('user');
        $rej_for = $this->get('rej_for');
        if ($rej_for == "")
            $condition = "rej_for='all'";
        else
            $condition = "rej_for='$rej_for'";
        $result_set = $this->model_all->getTableData("rejection_points", $condition, "id,rej_point");
        if ($result_set->num_rows() > 0) {
            $result["status"] = 1;
            $result["message"] = "Records Found";

            foreach ($result_set->result_array() as $row) {

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



    function discount_points_get() {
        
        $condition = array();
        $result_set = $this->model_all->getTableData("discount_points", $condition, "id,dis_point");
        if ($result_set->num_rows() > 0) {
            $result["status"] = 1;
            $result["message"] = "Records Found";

            foreach ($result_set->result_array() as $row) {

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

    function count_get() {
        $user = $this->get('user');
        $items_count_qry = $this->model_all->getTableData("cart_items", array("user_id" => $user));

        $result["items_count"] = $items_count_qry->num_rows();
        $this->response($result, 200);
        exit;
    }

    //API - Save Pin Code
    function add_post() {


        $status = 0;
        $message = "Product not added to Cart successfully.";
        $user = $this->post('user');
        $item = $this->post('item');
        $sellerid = $this->post('sellerid');
        $qty = $this->post('qty');
        $data = array("user_id" => $user, "item_id" => $item, "sellerid" => $sellerid);
        $cart_qry = $this->model_all->getTableData("cart_items", $data);
        if ($cart_qry->num_rows() > 0) {
            $cart_rs = $cart_qry->row_array();
            $data["product_count"] = $qty;
            $aff_rows = $this->model_all->update($data, array("id" => $cart_rs["id"]), "cart_items");
            if ($aff_rows) {
                $status = 1;
                $message = "Cart updated Successfully";
            }
        } else {
            $data["product_count"] = $qty;
            $id = $this->model_all->save($data, "cart_items");
            if ($id > 0) {
                $status = 1;
                $message = "Product added to cart successfully.";
            }
        }

        $items_count_qry = $this->model_all->getTableData("cart_items", array("user_id" => $user));

        $result["status"] = $status;
        $result["message"] = $message;
        $result["items_count"] = $items_count_qry->num_rows();

        $this->response($result, 200);

        exit;
    }

    //API - Save Pin Code
    function delete_put() {


        $status = 0;
        $message = "Product not added to Cart successfully.";
        $user = $this->put('user');
        $item = $this->put('item');
        $sellerid = $this->put('sellerid');
        $data = array("user_id" => $user, "item_id" => $item, "sellerid" => $sellerid);
        $cart_qry = $this->model_all->getTableData("cart_items", $data);
        if ($cart_qry->num_rows() > 0) {
            $aff_rows = $this->model_all->deleteRow("cart_items", $data);
            if ($aff_rows > 0) {
                $status = 1;
                $message = "Item deleted from cart updated Successfully";
            }
        } else {
            $status = 0;
            $message = "No such item in the cart";
        }

        $items_count_qry = $this->model_all->getTableData("cart_items", array("user_id" => $user));

        $result["status"] = $status;
        $result["message"] = $message;
        $result["items_count"] = $items_count_qry->num_rows();

        $this->response($result, 200);

        exit;
    }

    function checkout_post() {
        $user = $this->post('user');
        $adress = $this->post('address');
        $landmark = $this->post('landmark');
        $latitude = $this->post('latitude');
        $langitude = $this->post('langitude');
        $flat_no = $this->post('flat_no');
        $dt = date("Y-m-d H:i:s");
        $seller_time = date("H:i:s");

        $data = array("orderedby" => $user, "flat_no" => $flat_no, "address" => $adress, "landmark" => $landmark, "latitude" => $latitude, "longitude" => $langitude, "orderedon" => $dt, "deliveredby" => 0, "deliveredon" => '', "delivery_accept" => 0, "delivery_reject" => 0);
        $order_id = $this->model_all->save($data, "orders");
        if ($order_id > 0) {
            $total_value_pay = 0.00;
            $this->model_all->save(array("order_id" => $order_id, "order_status" => 'Ordered', "changed_on" => $dt), 'order_track');
            $order_string = "BT0000" . $order_id;
            $result["status"] = "1";
            $result["message"] = "CheckOut Successfully";
            $result["order_id"] = $order_string;
            $aff_rows = $this->model_all->update(array("order_id" => $order_string), array("id" => $order_id), "orders");
            $result_set = $this->model_all->getTableDataFromQuery("SELECT c.*, CONCAT(s.first_name,s.last_name)  as seller,i.itemname,i.brand,p.pay,p.mrp,p.sellingprice,p.id as price_id FROM `cart_items` c, items i ,sellers s,pricing p where i.id=c.item_id and s.id = c.sellerid and p.sellerid=s.id  and p.itemid = c.item_id and c.user_id='$user'");
            if ($result_set->num_rows() > 0) {
                foreach ($result_set->result() as $item_set) {
                    $scharge = 0.00;
                    $item_data = array("itemid" => $item_set->item_id, "sellerid" => $item_set->sellerid, "orderid" => $order_id, "qty" => $item_set->product_count, "mrp" => $item_set->mrp, "service_charge" => $scharge, "amount" => $item_set->pay);
                    $item_value = ($item_set->product_count * $item_set->pay) + $scharge;
                    $total_value_pay += ($item_set->product_count * $item_set->pay) + $scharge;
                    $order_item_id = $this->model_all->save($item_data, "order_items");
                    $item_ccnt = $item_set->product_count;
                    $this->model_all->getTableDataFromQuery("update quantity set qty=qty-$item_ccnt where itemid='$item_set->item_id' and sellerid='$item_set->sellerid'");
                    /* Actual */
                    $seller_date = date("Y-m-d");
                    $limit_time = "18:00:00";
                    if ($seller_time >= $limit_time) {
                        $seller_date = $date = strtotime("+1 day", strtotime($seller_date));
                    }

                    $seller_details = $this->model_all->getTableData("seller_items", array("order_date" => $seller_date, "item_id" => $item_set->item_id));
                    if ($seller_details->num_rows() > 0) {
                        $seller_rs = $seller_details->row();
                        $this->model_all->getTableDataFromQuery("update seller_items set `qty`=`qty`+$item_set->product_count,`amount`=`amount`+$item_value,order_item=concat(order_item,',$order_item_id')  where id='$seller_rs->id'");
                    } else {
                        $invoice_id = date("dmY", strtotime($seller_date)) . $item_set->sellerid;
                        $item_data = array("seller_id" => $item_set->sellerid, "item_id" => $item_set->item_id, "qty" => $item_set->product_count, "amount" => $item_value, "service_charge" => $scharge, "order_item" => $order_item_id, "invoice_id" => $invoice_id, "picked_qty" => 0, "status" => '0', "reason" => '', "order_date" => $seller_date);
                        $this->model_all->save($item_data, "seller_items");
                    }

                    /* Actual */
                }
                $this->model_all->update(array("order_value" => $total_value_pay), array("id" => $order_id), "orders");
                $this->model_all->deleteRow("cart_items", array("user_id" => $user));
            }
        } else {
            $result["status"] = "0";
            $result["message"] = "Something went wrong.Please try later";
            $result["order_id"] = "";
        }
        $this->response($result, 200);

        exit;
    }

    function validate_mobile_get() {

        $mobile = $this->get('mobile');
        if ($mobile != "") {
            /* $result_set = $this->model_all->getTableData("sellers", array("mobile" => $mobile));
              if ($result_set->num_rows() == 0) { */
            $alphabet = '1234567890';
            $pass = array(); //remember to declare $pass as an array
            $alphaLength = strlen($alphabet) - 1; //put the length -1 in cache
            for ($i = 0; $i < 5; $i++) {
                $n = rand(0, $alphaLength);
                $pass[] = $alphabet[$n];
            }
          //  $otp = implode($pass); //turn the array into a string 
            $otp = 123456;
            $message = "Welcome to Nova. Dear User,Your OTP is " . $otp . ". Do not share this to anyone.";
            $this->model_all->sendSMS_get($mobile, $message);
            $result["status"] = 1;
            $result["message"] = "OTP has sent to given mobile No.";
            $result["otp"] = $otp;
            /* } else {
              $result["status"] = 0;
              $result["message"] = "User already Exists";
              } */
        } else {
            $result["status"] = 0;
            $result["message"] = "Field(s) are Missing";
            $this->response($result, 200);
        }
        $this->response($result, 200);


        exit;
    }




    function dp_post() {


        $user = $this->post('user');
        $role = $this->post('role');
        if ($role == "DEALER") {
            $user_role = "seller";
        } else {
            $user_role = "trade";
        }
                  /*  $result["status"] = 0;
                    $result["message"] = "Unable to Processing";*/
                    if ($_FILES["dp"]["size"] > 0) {
                        $type = explode('.', $_FILES["dp"]["name"]);
                        $type = $type[count($type) - 1];
                        $fileName = $user . "_".$user_role."_dp." . $type;
                        $url = "./dps/" . $fileName;
                        if(in_array($type, array("jpg", "jpeg", "gif", "png"))) {
                            if (is_uploaded_file($_FILES["dp"]["tmp_name"])) {
                                move_uploaded_file($_FILES['dp']['tmp_name'], $url);
                                $action_status = $this->model_all->update(array("dp" => $fileName,"modifiedon"=>date("Y-m-d H:i:s")), array("pkid" => $user,"role"=>$user_role), "app_users");

                               
                                if($action_status){
                                   $result["status"] = 1;
                                   $result["dp"] = base_url()."dps/".$fileName."?".time();
                                   $result["message"] = "Profile Pic Changed Successfullly";
                                }else{
                                   $result["status"] = 0;
                                 
                                   $result["message"] = "Unable to upload";

                                }
                               
                            }else{
                               $result["status"] = 0;
                               $result["message"] = "Unable to Upload";
                            }
                        }else{
                           $result["status"] = 0;
                           $result["message"] = "Invalid File";
                        }
                    }else {
                     $result["status"] = 0;
                     $result["message"] = "image is Missing";
                    }
       
         
      
        

        $this->response($result, 200);
    }
    
    
    
    function emp_roles_get(){
                        $user= $this->get('user');
                        $branch_values = array();
                        $role_values = array();
                        $company_values = array();
                        $result["roles"] = array();
                        $emp_roles_query = $this->model_all->getTableDataFromQuery("select er.id,c.company_id,c.company,b.id as branch_id,b.name as  branch_name,b.contact_no as branch_contact,ar.short_form,ar.id as role_id,ar.role_name as role_name from emp_roles er,branches b, offices o,app_roles ar,companies c where er.employee_id='$user' and er.branch_id=b.id and b.office_id=o.id and ar.id=er.role_id and b.company=c.company_id and ar.short_form!='UN'");
                        foreach($emp_roles_query->result_array() as $roles_row){
                              if(count($branch_values)>0){
                               if(!in_array($roles_row["branch_id"],$branch_values)){
                                   $branch_values[] = $roles_row["branch_id"];
                               } 
                              }else{
                                   $branch_values[]= $roles_row["branch_id"];
                              }


                              if(count($company_values)>0){
                               if(!in_array($roles_row["company_id"],$company_values)){
                                   $company_values[] = $roles_row["company_id"];
                               } 
                              }else{
                                   $company_values[]= $roles_row["company_id"];
                              }

                              if(count($role_values)>0){
                               if(!in_array($roles_row["role_id"],$role_values)){
                                   $role_values[] = $roles_row["role_id"];
                               } 
                              }else{
                                   $role_values[]= $roles_row["role_id"];
                              }
                               $result["roles"][] = $roles_row;
                           
                        }
                        $result["branch_count"] = count($branch_values);
                        $result["role_count"] =  count($role_values);
                        $result["company_count"] =  count($company_values);
                        if(count($result["roles"])>0){
                            $result["status"] = 1;
                            $result["message"] = "Records Found";
                        }else{
                           $result["status"] = 0;
                           $result["message"] = "Invalid File";
                        
                        }
                        $this->response($result, 200);
                        exit;
    
    
    }
    
    
    
    public function dealer_companies_get() {
        $user= $this->get('user');
        $req_sql = $this->model_all->getTableDataFromQuery("select s.* from sellers s where s.id='$user'");
        if ($req_sql->num_rows() > 0) {
            $result["status"] = 1;
            $result["message"] = "Details Found";
            foreach ($req_sql->result() as $srs) {
                $dealer_for = $srs->dealer_for;
                $result["companies"] = array();
                if ($dealer_for != "") {
                    $company_query = $this->model_all->getTableDataFromQuery("select company_id, company from companies where company_id in ($dealer_for)");
                    foreach ($company_query->result_array() as $company_row) {
                        $result["companies"][] = $company_row;
                    }
                } else {
                    $result["status"] = 0;
                    $result["message"] = "No Details Found";
                }
            }
        } else {
            $result["status"] = 0;
            $result["message"] = "Invalid User";
        }
        $this->response($result, 200);
        exit;
    }

    function list_get() {
        $user = $this->get('user');
        $fromdate = $this->get('fromdate');
        $todate = $this->get('todate');
        $status = $this->get('status');
        $role = $this->get('role');
       
        $condition = " and order_id!=''";
        
           
        if($role=="DEALER"){
        
            $table ="sellers";
            //$condition = " and o.is_transfered='0' ";
        
        }else{
            $table ="employees";
            
        
        }

        $branch = $this->get('branch');

       if($branch==""){
        $user_branch_qry = $this->model_all->getTableDataFromQuery("select branch from $table where id='$user'");
        if($user_branch_qry->num_rows()>0){
            $user_branch_rs = $user_branch_qry->row();
            $branch = $user_branch_rs->branch;
        }
       }
       

        
        if($role=="DEALER" && $user!="")
           $condition .= " and o.orderedby='$user'"; 
        else if($role=="SE" && $user!="") {
          
           $seller_list = $this->model_all->tableFieldData("select GROUP_CONCAT(seller) as seller from branch_dealers where branch='$branch' and sales_manager='$user'","seller");
           if(!empty($seller_list)){
              $condition .= " and o.orderedby in ($seller_list)";
           }


           

        }

       
        if($branch != "" && $role!="SA" && $role!="DEALER"  && $role!="SE") {
            $condition .= " and o.branch_id IN ($branch)";
        }

        if ($fromdate != "" && $todate == "") {
            $fromdate = date("Y-m-d", strtotime($fromdate));
            $condition .= " and date(o.`orderedon`)='$fromdate'";
        } else if ($fromdate != "" && $todate != "") {
            $fromdate = date("Y-m-d", strtotime($fromdate));
            $todate = date("Y-m-d", strtotime($todate));
            $condition .= " and o.`orderedon` between '$fromdate 00:00:00' and '$todate 23:59:59'";
        }

      
        

        $result_set = $this->model_all->getTableDataFromQuery("SELECT o.id, o.order_id,o.order_value, s.id as store_id,s.company_name as name, s.mobile,o.is_transfered from seller_orders o, sellers s where o.orderedby = s.id and o.admin_status='1' and o.status NOT IN('Ordered','Accepted','Cancelled','Rejected','Packed') $condition order by o.orderedon desc");
       
        if ($result_set->num_rows() > 0) {
            $result["status"] = 1;
            $result["message"] = "Records Found";
            $result["total_records"] = $result_set->num_rows();       
            


            foreach ($result_set->result_array() as $row) {
                
                
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
    
    
    function employee_code_get() {
        $user = $this->get('user');
        $uniq_id = $this->model_all->tableFieldData("select  uniq_id from employees where id = '$user'","uniq_id");
        $result["status"] = 1;
        $result["message"] = "Records Found";
        $result["uni_code"] = $uniq_id;
        $this->response($result, 200);
            exit;
    }
}
