<?php

require(APPPATH . '/libraries/REST_Controller.php');

class Test extends REST_Controller {

    public function __construct() {
        parent::__construct();
        $this->load->model('model_all');
    }

    //API - Fetch All Pincodes
    function roles_get() {

        $result_set = $this->model_all->getTableDataInArray("app_roles", array("id!=" => "1"), "id,role_name,short_form");
        if (($result_set['total_rows']) > 0) {
            $result["status"] = 1;
            $result["message"] = "Records Found";
            $result["roles"] = $result_set['records'];
            $this->response($result, 200);
            exit;
        } else {
            $result["status"] = 0;
            $result["message"] = "Detais Not Found";
            $this->response($result, 200);
            exit;
        }
    }

    function companyroles_get() {

        $result_set = $this->model_all->getTableDataInArray("app_roles", array("id!=" => "1", "is_trade!=" => '0'), "id,role_name,short_form");
        if (($result_set['total_rows']) > 0) {
            $result["status"] = 1;
            $result["message"] = "Records Found";
            $result["roles"] = $result_set['records'];
            $this->response($result, 200);
            exit;
        } else {
            $result["status"] = 0;
            $result["message"] = "Detais Not Found";
            $this->response($result, 200);
            exit;
        }
    }

    function list2_get() {
        $sform = $this->get("role");
        $branch = $this->get("branch");
        $query = "select e.*,a.role_name,u.dp,b.name as branch_name,(select address from  addresses where user_id=e.id and is_default='1' and user_role='trade') as perm_address from employees e,app_roles a,app_users u,branches b  where a.id=e.role_id and u.role='trade' and u.pkid=e.id and u.status='1' and b.id=e.branch";
        if ($sform != "") {
            $query .= " and a.short_form='$sform'";
        }

        if ($branch != "") {
            $query .= " and e.branch IN ('$branch')";
        }
       
        $result_set = $this->model_all->getTableDataFromQuery($query);
        if ($result_set->num_rows() > 0) {
            $result["status"] = 1;
            $result["message"] = "Records Found";
            foreach ($result_set->result() as $row) {
                $object = array();
                $object["id"] = $row->id;
                $object["name"] = ucwords($row->first_name . " " . $row->last_name);
                $object["first_name"] = ucwords($row->first_name);
                $object["last_name"] = ucwords($row->last_name);
                $object["emp_id"] = $row->uniq_id;
                $object["branch"] = $row->branch;
                $object["branch_name"] = $row->branch_name;
                $object["mobile"] = $row->mobile;
                $object["email"] = $row->email;
                $object["dob"] = $row->dob;
                $object["address"] = $row->perm_address;
                $object["role_name"] = $row->role_name;
                $object["dp"] = base_url() . "dps/noimage.png";
                if ($row->dp != "") {
                    $file_headers = @get_headers(base_url() . 'dps/' . $row->dp);
                    if (stripos($file_headers[0], "404 Not Found") > 0 || (stripos($file_headers[0], "302 Found") > 0 && stripos($file_headers[7], "404 Not Found") > 0)) {
                        
                    } else {
                        $object["dp"] = base_url() . 'dps/' . $row->dp;
                    }
                }
                $object["dp"] = $object["dp"] . "?" . time();
                $result["employees"][] = $object;
            }

            $this->response($result, 200);
            exit;
        } else {
            $result["status"] = 0;
            $result["message"] = "Detais Not Found";
            $this->response($result, 200);
            exit;
        }
    }



    function list_get() {
        $sform = $this->get("role");
        $branch = $this->get("branch");
        $query = "select e.*,a.role_name,u.dp,b.name as branch_name from employees e,app_roles a,app_users u,branches b  where a.id=e.role_id and u.role='trade' and u.pkid=e.id and u.status='1' and FIND_IN_SET(b.id,e.branch)";
        if ($sform != "") {
            $query .= " and a.short_form='$sform'";
        }

        if ($branch != "") {
            $query .= " and b.id='$branch'";
        }
       
//echo $query;
        $result_set = $this->model_all->getTableDataFromQuery($query);
        if ($result_set->num_rows() > 0) {
            $result["status"] = 1;
            $result["message"] = "Records Found";
            foreach ($result_set->result() as $row) {

                $object = array();
                $object["id"] = $row->id;
                $object["name"] = ucwords($row->first_name . " " . $row->last_name);
                $object["first_name"] = ucwords($row->first_name);
                $object["last_name"] = ucwords($row->last_name);
                $object["emp_id"] = $row->uniq_id;
                $object["branch"] = $row->branch;
                $object["branch_name"] = $row->branch_name;
                $object["mobile"] = $row->mobile;
                $object["email"] = $row->email;
                $object["dob"] = $row->dob;
                
                $object["role_name"] = $row->role_name;
                $object["dp"] = base_url() . "dps/noimage.png";
                if ($row->dp != "") {
                    $file_headers = @get_headers(base_url() . 'dps/' . $row->dp);
                    if (stripos($file_headers[0], "404 Not Found") > 0 || (stripos($file_headers[0], "302 Found") > 0 && stripos($file_headers[7], "404 Not Found") > 0)) {
                        
                    } else {
                        $object["dp"] = base_url() . 'dps/' . $row->dp;
                    }
                }

                $address_qry = $this->model_all->getTableDataFromQuery("select address from  addresses where user_id='$row->id' and is_default='1' and user_role='trade' limit 0,1");
                if($address_rs = $address_qry->row()){
                   $object["address"] = $address_rs->address;
                }else{
                   $object["address"] = "";

                }



                $object["dp"] = $object["dp"] . "?" . time();
                $result["employees"][] = $object;
            }

            $this->response($result, 200);
            exit;
        } else {
            $result["status"] = 0;
            $result["message"] = "Detais Not Found";
            $this->response($result, 200);
            exit;
        }
    }

    function dealers_get() {

        $branch = $this->get("branch");
        $role = $this->get("role");
        $user = $this->get("user");
        
        $condition ="";
        $branch_table ="";
        $branch_fields="";
       
        if ($role == "SE") {
             $condition = $condition." and FIND_IN_SET($user,s.sales_manager)";
        }


        if ($branch!= "") {
            $branch_table =",branches b";
            $branch_fields = ",b.name as branch_name";
            $condition .= " and  FIND_IN_SET($branch,s.branch) and b.id='$branch' and b.id IN (s.branch)";  // 
        }

         $query = "select s.id,s.first_name,s.last_name,s.mobile,s.contact1,s.dealer_code,s.branch,s.status,s.email,s.wallet,s.company_name,a.address as seller_adress $branch_fields from sellers s,addresses a $branch_table where  a.is_default='1' and s.status='1' and  a.user_id=s.id and a.user_role='DEALER' and s.company_name!='' $condition ";

        echo $query;
        $result_set = $this->model_all->getTableDataFromQuery($query." order by s.company_name ");
        if ($result_set->num_rows() > 0) {
            $result["status"] = 1;
            $result["message"] = "Records Found";
            foreach ($result_set->result() as $row) {

$mobile="NA";
if($row->mobile!=0){
$mobile = $row->mobile;
}
else if($row->contact1!="NA"){
$mobile = $row->contact1;
}




                $object = array();
                $object["id"] = $row->id;
                $object["name"] = ucwords($row->first_name . " " . $row->last_name);
                $object["emp_id"] = $row->dealer_code;
                $object["address"] = $row->seller_adress;
                $object["mobile"] = $mobile;
                $object["branch"] = $row->branch;
                $object["branch_name"] = $row->branch_name;
                $object["status"] = $row->status;
                $object["email"] = $row->email;
                $object["wallet"] = $row->wallet;
                $object["company_name"] = $row->company_name;

                $object["role_name"] = "DEALER";
                $object["dp"] = base_url() . "dps/noimage.png";
               
                $result["employees"][] = $object;
            }

            $this->response($result, 200);
            exit;
        } else {
            $result["status"] = 0;
            $result["message"] = "Details Not Found";
            $this->response($result, 200);
            exit;
        }
    }

    // Dealer 
 
function mangedealer_post() {


        // Step 1 Personal Info
        $user = $this->post('user');
        $employee = $this->post('employee');
        $first_name = $this->post('first_name');
        $last_name = $this->post('last_name');
        $address = $this->post('address');

        $door_no = $this->post('door_no');
        $street_name = $this->post('street_name');
        $landmark = $this->post('landmark');
        $district = $this->post('district');

        $country = $this->post('country');
        $state = $this->post('state');
        $town = $this->post('town');
        $pincode = $this->post('pincode');

        $pan = $this->post('pan');
        $gstin = $this->post('gst');
        $company_name = $this->post('company_name');
        $branch = $this->post('branch');


        $working_for = $this->post('worked_for');
        $key_person = $this->post('key_person');

        $latitude = $this->post('latitude');
        $longitude = $this->post('langitude');

        // Step  2 Account Info 
        $contact1 = $this->post('contact1');
        $contact2 = $this->post('contact2');
        $delaer_whatsapp = $this->post('delaer_whatsapp');

      


        // Step 4 Personal Info
        $role = $this->post('role');
        $mobile = $this->post('mobile');
        $email = $this->post('email');

        // Step  3 Account Info

        $reg_type = $this->post('reg_type');
        $bank_accno = $this->post('bank_accno');
        $bank_name = $this->post('bank_name');
        $bank_branch = $this->post('bank_branch');
        $ifsc = $this->post('ifsc');

        // Step  4 Contact Info
        $contact_personal = $this->post('contact_personal');
        $contact_whatsup = $this->post('contact_whatsup');
        $contact_fb = $this->post('contact_fb');
        $contact_email = $this->post('contact_email');
        $designation = $this->post('designation');
        $location = $this->post('location');
     $sales_manager = $this->post('sales_manager');
        $am_contact = $this->post('am_contact');
        $branch = $this->post('branch');
        $dealer_code = $this->post('dealer_code');
        $owner_desg = $this->post('owner_desg');
        $credit_limit = $this->post("credit_limit");
        if ($credit_limit == "") {
            $credit_limit = 0.00;
        }
        $credit_date = $this->post("credit_date");
        if ($credit_date == "") {
            $credit_date = "";
        }else{
            $credit_date = date("Y-m-d",strtotime($credit_date));
        }

        $wallet = $this->post("wallet");
        if ($wallet == "") {
            $wallet = 0.00;
        }




        if($sales_manager==""){

             $sales_manager ="";
        }else{
            // $sales_manager = $this->model_all->tableFieldData("select  uniq_id from employees where id = '$sales_manager'","uniq_id");
             $sales_manager = trim($sales_manager,",");
             $sales_manager = str_replace(",","','",$sales_manager);
             $sales_manager = "'".$sales_manager."'";
            // echo "select  GROUP_CONCAT(id) as id from employees where uniq_id in ($sales_manager)";
             $sales_manager = $this->model_all->tableFieldData("select  GROUP_CONCAT(id) as id from employees where uniq_id in ($sales_manager)","id");
         
             if(empty($sales_manager)){
               $sales_manager ="";
             }
             
        }
        $license_str = $this->post("license");

            $fertilizer = $this->post("fertilizer");
                    $fertilizer_upto = $this->post("fertilizer_upto");
                    $pesticide = $this->post("pesticide");
                    $pesticide_upto = $this->post("pesticide_upto");
                    $seed = $this->post("seed");
                    $seed_upto = $this->post("seed_upto");
                    $other_upto = $this->post("other_upto");
                    if($seed_upto!=""){
                        $seed_upto = date("Y-m-d H:i:s",strtotime($seed_upto));
                    }
                    if($pesticide_upto!=""){
                        $pesticide_upto = date("Y-m-d H:i:s",strtotime($pesticide_upto));
                    }
                    if($fertilizer_upto!=""){
                        $fertilizer_upto = date("Y-m-d H:i:s",strtotime($fertilizer_upto));
                    }

                    if($other_upto!=""){
                        $other_upto  = date("Y-m-d H:i:s",strtotime($other_upto));
                    }
                    
                    $other = $this->post("other");

        $dt = date("Y-m-d H:i:s");
        $table = "sellers";
        $error_name = "Dealer";
        $flag1 = false;
        $flag2 = false;


        if ($employee > 0) {
            $result_set = $this->model_all->getTableData($table, array("mobile" => $mobile, "id!=" => $employee));
        } else {
            $result_set = $this->model_all->getTableData($table, array("mobile" => $mobile));
        }

        if ($result_set->num_rows() == 0) {
            $role_id = 0;
            $role_qry = $this->model_all->getTableData("app_roles", array("short_form" => $role), "id");
            if ($role_qry->num_rows() > 0) {
                $role_rs = $role_qry->row();
                $role_id = $role_rs->id;
            }

            $data = array("first_name" => $first_name, "last_name" => $last_name, "dealer_code" => $dealer_code, "contact1" => $contact1, "contact2" => $contact2, "whatsapp" => $delaer_whatsapp, "email" => $email, "mobile" => $mobile, "owner_desg" => $owner_desg, "latitude" => "", "longitude" => "", "address" => "", "company_name" => $company_name, "gstin" => $gstin, "pan" => $pan, "division" => "", "bank_name" => $bank_name, "bank_accno" => $bank_accno, "bank_branch" => $bank_branch, "ifsc" => $ifsc, "reg_type" => $reg_type, "addressproof1" => "", "addressproof2" => "", "modifiedby" => $user, "modifiedon" => $dt, "status" => '1', "pickerid" => "0", "dealer_for" => $working_for, "credit_limit" => $credit_limit);
            //$data = array("first_name" => $first_name, "last_name" => $last_name, "dealer_code" => $dealer_code, "email" => $email, "mobile" => $mobile, "latitude" => $latitude, "longitude" => $longitude, "address" => $address, "company_name" => $company_name, "gstin" => $gstin, "pan" => $pan, "licence" => $licence, "mandal" => '', "town" => '', "division" => '', "addressproof1" => '', "addressproof2" => '', "createdby" => $user, "createdon" => $dt, "modifiedby" => $user, "modifiedon" => $dt, "status" => '1', "branch" => $branch, "bank_name" => $bank_name, "bank_accno" => $bank_accno, "ifsc" => $ifsc, "pickerid" => 0, "reg_type" => $reg_type, "am_contact" => $am_contact);

            if ($employee > 0) {

                
                $exist_dealer = "";
                $exist_sm = "";
                $exist_brnch = "";
                $emp_qry = $this->model_all->getTableData("sellers", array("id" => $employee), "dealer_for,sales_manager,branch");
                if ($emp_qry->num_rows() > 0) {
                    $emp_rs = $emp_qry->row();
                    $exist_dealer = $emp_rs->dealer_for;
                    $exist_sm = $emp_rs->sales_manager;
                    $exist_brnch = $emp_rs->branch;
                }


                if ($exist_dealer != "") {
                    $dealer_arr = explode(",", $exist_dealer);
                    if (!(in_array($working_for, $dealer_arr))) {
                        $exist_dealer = $exist_dealer . "," . $working_for;
                    }
                } else {
                    $exist_dealer = $working_for;
                }

                $data["dealer_for"] = $exist_dealer;


               // array_unique(array_merge($array1,$array2), SORT_REGULAR);
                if ($exist_sm != "") {
                    $sm_arr = explode(",", $exist_sm );
                    $new_sm_arr =  explode(",", $sales_manager);
                    $new_sm_arr = array_unique(array_merge($sm_arr,$new_sm_arr), SORT_REGULAR);
                    if(count($new_sm_arr)>0){
                           $exist_sm =  implode(",",$new_sm_arr);
                    }
                    
                } else {
                    $exist_sm = $sales_manager;
                }

                $data["sales_manager"] = $exist_sm;





                /*
                 For dynamic licenses 
                $licenses_list = explode("~", $license_str);
                $license_id_str = "";
                for ($i = 0; $i < count($licenses_list); $i++) {
                    $single_item = $licenses_list[$i];
                    $single_item_list = explode("#", $single_item);
                    if (count($single_item_list) == 3) {

                        $license_id = $single_item_list[0];
                        $license_value = $single_item_list[1];
                        $license_upto = $single_item_list[2];
                        $license_id_str = $license_id_str . $license_id . ",";
                        if ($license_upto != "") {
                            $license_upto = date("Y-m-d H:i:s", strtotime($license_upto));
                        }
                        $this->model_all->update(array("license_value" => $license_value, "valid_upto" => $license_upto), array("license_id" => $license_id, "seller_id" => $employee), "seller_licenses");
                    }
                }
                if ($license_id_str != "")
                    $this->model_all->getTableDataFromQuery("delete from seller_licenses where seller_id='$employee' and license_id not in($license_id_str)");
                */
                
                /* For Static licenses */
                $license_qry = $this->model_all->getTableDataFromQuery("select * from delaer_licenses where  seller_id='$employee'");
                if($license_qry->num_rows()>0){
                   $license_rs =  $license_qry->row();
                   $row_id = $license_rs->id;
                   $this->model_all->update(array("seller_id"=>$employee,"fertilizer"=>$fertilizer,"fertilizer_upto"=>$fertilizer_upto,"pesticide"=>$pesticide,"pesticide_upto"=>$pesticide_upto,"seed"=>$seed,"seed_upto"=>$seed_upto,"other"=>$other,"other_upto"=>$other_upto),array("id"=>$row_id),"delaer_licenses");
                }else{
                   $this->model_all->save(array("seller_id"=>$employee,"fertilizer"=>$fertilizer,"fertilizer_upto"=>$fertilizer_upto,"pesticide"=>$pesticide,"pesticide_upto"=>$pesticide_upto,"seed"=>$seed,"seed_upto"=>$seed_upto,"other"=>$other,"other_upto"=>$other_upto),"delaer_licenses");
                }


                $role_nm = "seller";


                $action_status = $this->model_all->update($data, array("id" => $employee), $table);
            
                $new_arr = explode(",",$exist_sm);
                for($m=0;$m<count($new_arr);$m++){
                  $count   = $this->model_all->tableFieldData("select count(*) from branch_dealers where branch='$branch' and seller='$employee' and sales_manager='$new_arr[$m]'","count(*)");   
                  if($count==0){
                   $this->model_all->save(array("branch" => $branch, "seller" => $employee, "sales_manager" => $new_arr[$m]), "branch_dealers");
                  }  
                  
                }
                
               


                if ($action_status) {
                    $flag2 = true;
                    $message = "Dealer Details updated successfully";
                }
                $code_set = $this->model_all->getTableData($table, array("dealer_code" => $dealer_code, "id!=" => $employee));
                if ($code_set->num_rows() > 0) {
                    $message = "Dealerrcode already assigned to another dealer";
                } else {
                    $this->model_all->update(array("dealer_code" => $dealer_code), array("id" => $employee), $table);
                    if ($user != $employee) {
                        $this->model_all->update(array("username" => $dealer_code, "modifiedon" => $dt, "modifiedby" => $user), array("pkid" => $employee, "role" => 'seller'), "app_users");
                    }
                }

                $contacts_qry = $this->model_all->getTableData("contacts", array("contact_personal" => $contact_personal, "role" => 'DEALER', "role_id" => $employee));
                if ($contacts_qry->num_rows() > 0) {
                    $contacts_rs = $contacts_qry->row();
                    $this->model_all->update(array("name" => $key_person, "contact_watsup" => $contact_whatsup, "contact_fb" => $contact_fb, "email" => $contact_email, "designation" => $designation), array("id" => $contacts_rs->id), "contacts");
                } else {
                    $this->model_all->save(array("name" => $key_person, "contact_personal" => $contact_personal, "contact_watsup" => $contact_whatsup, "contact_fb" => $contact_fb, "email" => $contact_email, "designation" => $designation, "role" => 'DEALER', "role_id" => $employee), "contacts");
                }

                $address_qry = $this->model_all->getTableData("addresses", array("is_default" => 1, "user_id" => $employee, "user_role" => 'DEALER', "status" => '1'));
                if ($address_qry->num_rows() > 0) {
                    $address_rs = $address_qry->row();
                    $this->model_all->update(array("latitude" => $latitude, "longitude" => $longitude, "door_no" => $door_no, "street_name" => $street_name, "landmark" => $landmark, "district" => $district, "address" => $address, "city" => $town, "state" => $state, "country" => $country, "locale" => $location, "pincode" => $pincode), array("id" => $address_rs->id), "addresses");
                } else {
                    $this->model_all->save(array("latitude" => $latitude, "longitude" => $longitude, "door_no" => $door_no, "street_name" => $street_name, "landmark" => $landmark, "district" => $district, "address" => $address, "city" => $town, "state" => $state, "country" => $country, "locale" => $location, "pincode" => $pincode, "is_default" => 1, "user_id" => $employee, "user_role" => 'DEALER', "status" => '1'), "addresses");
                }
            } else {
                $role_nm = "seller";
                $data["createdby"] = $user;
                $data["createdon"] = $dt;
                $data["branch"] = $branch;
            /*    $data["credit_date"] = $credit_date; 
                $data["wallet"] = $wallet;   */
                

                /*  $data["sales_manager"] = $user;
                  $data["dealer_for"] = $working_for; */
                $employee = $this->model_all->save($data, $table);
                if ($employee > 0) {
                    $flag1 = true;
                    $message = "Dealer Registered Successfully";
                    if ($dealer_code == "")
                        $dealer_code = 'ND' . $this->model_all->prefix_zeros($employee);    // $this->model_all->randomPassword()

                   /*
                     For Dynamic licenses
                     $licenses_list = explode("~", $license_str);
                    for ($i = 0; $i < count($licenses_list); $i++) {
                        $single_item = $licenses_list[$i];
                        $single_item_list = explode("#", $single_item);
                        if (count($single_item_list) == 3) {
                            $license_id = $single_item_list[0];
                            $license_value = $single_item_list[1];
                            $license_upto = $single_item_list[2];
                            if ($license_upto != "") {
                                $license_upto = date("Y-m-d H:i:s", strtotime($license_upto));
                            }
                            $this->model_all->save(array("license_id" => $license_id, "license_value" => $license_value, "seller_id" => $employee, "valid_upto" => $license_upto), "seller_licenses");
                        }
                    }*/
                    /* For Static licenses */
                    $this->model_all->save(array("seller_id"=>$employee,"fertilizer"=>$fertilizer,"fertilizer_upto"=>$fertilizer_upto,"pesticide"=>$pesticide,"pesticide_upto"=>$pesticide_upto,"seed"=>$seed,"seed_upto"=>$seed_upto,"other"=>$other),"delaer_licenses");


                    $new_arr = explode(",",$sales_manager);
                    for($m=0;$m<count($new_arr);$m++){
                        
                        $this->model_all->save(array("branch" => $branch, "seller" => $employee, "sales_manager" => $new_arr[$m]), "branch_dealers");
                    }
                    

                    
                    $this->model_all->update(array("dealer_code" => $dealer_code,"sales_manager" => $sales_manager), array("id" => $employee), $table);
                    $this->model_all->save(array("name" => $key_person, "contact_personal" => $contact_personal, "contact_watsup" => $contact_whatsup, "contact_fb" => $contact_fb, "email" => $contact_email, "designation" => $designation, "role" => 'DEALER', "role_id" => $employee), "contacts");
                    $this->model_all->save(array("dp" => 'noimage.png', "username" => $dealer_code, "password" => md5('123456'), "role" => $role_nm, "pkid" => $employee, "status" => '1', "createdon" => $dt, "modifiedon" => $dt, "createdby" => $user, "modifiedby" => $user), "app_users");
                    $this->model_all->save(array("latitude" => $latitude, "longitude" => $longitude, "door_no" => $door_no, "street_name" => $street_name, "landmark" => $landmark, "district" => $district, "address" => $address, "city" => $town, "state" => $state, "country" => $country, "locale" => $location, "pincode" => $pincode, "is_default" => 1, "user_id" => $employee, "user_role" => 'DEALER', "status" => '1'), "addresses");
                }
            }






            $i = 0;

            if ($employee > 0 && isset($_FILES) && isset($_FILES['addressproof1']) && $_FILES['addressproof1']['size'] > 0 && $_FILES['addressproof1']['error'] == 0) {
                $prrofname = $employee . "_" . time() . "_" . $_FILES['addressproof1']['name'];
                if (move_uploaded_file($_FILES['addressproof1']['tmp_name'], "address_proofs/" . $prrofname)) {
                    $proof1_status = $this->model_all->update(array("addressproof1" => $prrofname), array("id" => $employee), $table);
                    if ($proof1_status) {
                        $flag2 = true;
                    }
                } else {
                    $flag2 = false;
                }
            }

            if ($employee > 0 && isset($_FILES) && isset($_FILES['addressproof2']) && $_FILES['addressproof2']['size'] > 0 && $_FILES['addressproof2']['error'] == 0) {

                $prrofname = $employee . "_" . time() . "_" . $_FILES['addressproof2']['name'];
                if (move_uploaded_file($_FILES['addressproof2']['tmp_name'], "address_proofs/" . $prrofname)) {
                    $proof2_status = $this->model_all->update(array("addressproof2" => $prrofname), array("id" => $employee), $table);

                    if ($proof1_status) {
                        $flag2 = true;
                    }
                } else {
                    $flag2 = false;
                }
            }






            if ($flag1) {
                $result["status"] = 1;
                $result["message"] = "Details Submitted Successfully";
            } else if ($flag2) {
                $result["status"] = 1;
                $result["message"] = "Details Updated Successfully";
            } else {
                $result["status"] = 0;
                $result["message"] = "Details Submissionm unsucessful";
            }
            $this->response($result, 200);
        } else {
            $result["status"] = 0;
            $result["message"] = $error_name . " with this mobile number already exists.".$mobile;
            $this->response($result, 200);
        }
        exit;
    }

    //API - Save Pin Code
      function add_post() {

        $user = $this->post('user');
        $emp_code = $this->post('emp_code');
        $employee = $this->post('employee');
        $first_name = $this->post('first_name');
        $last_name = $this->post('last_name');
        $role = $this->post('role');
        $mobile = $this->post('mobile');
        $email = $this->post('email');
        $latitude = $this->post('latitude');
        $longitude = $this->post('langitude');
        $address = $this->post('address');
        $branch = $this->post('branch');
        $dept = $this->post('dept');
        $gender = $this->post('gender');
        $location = $this->post('location');
        $blood_group = $this->post('blood_group');
        $father = $this->post('father');
        $mother = $this->post('mother');
        $mstatus = $this->post('mstatus');
        $experience = $this->post('experience');
        $report_to = $this->post('report_to');
        $doj = $this->post('doj');
        $pan = $this->post('pan');
        $pf = $this->post('pf');
        $esi = $this->post('esi');
        $bank_accno = $this->post('bank_accno');
        $bank_name = $this->post('bank_name');

        $company = $this->post('company');
        $country = $this->post('country');
        $state = $this->post('state');
        $town = $this->post('town');
        $pincode = $this->post('pincode');

        $ifsc = $this->post('ifsc');
        $ofc_email = $this->post('ofc_email');
        $ofc_contact = $this->post('ofc_contact');
        $designation = $this->post('designation');


        $dob = $this->post('dob');
        $dt = date("Y-m-d H:i:s");
        $flag1 = FALSE;
        $flag2 = FALSE;

        if ($dob != "")
            $dob = date("Y-m-d", strtotime($dob));
        if ($doj != "")
            $doj = date("Y-m-d", strtotime($doj));

        $table = "employees";
        $error_name = "Employee";
        $role_nm = 'trade';
        $dept_arr = array("FM" => 7, "SE" => 1, "PACKER" => 5, "ADMIN" => 12, "SA" => 12);
        if (isset($dept_arr[$role]))
            $dept_id = $dept_arr[$role];
        else
            $dept_id = 0;


        if ($employee > 0) {
            $result_set = $this->model_all->getTableData($table, array("mobile" => $mobile, "id!=" => $employee));
        } else {
            $result_set = $this->model_all->getTableData($table, array("mobile" => $mobile));
        }
      
        if ($result_set->num_rows() == 0) {
            $role_id = 0;
            $role_qry = $this->model_all->getTableData("app_roles", array("short_form" => $role), "id");
            if ($role_qry->num_rows() > 0) {
                $role_rs = $role_qry->row();
                $role_id = $role_rs->id;
            }


            $data = array("first_name" => $first_name, "last_name" => $last_name, "branch" => $branch, "dept" => $dept_id, "mobile" => $mobile, "email" => $email, "dob" => $dob, "gender" => $gender, "location" => $location, "blood_group" => $blood_group, "father" => $father, "mother" => $mother, "marital" => $mstatus, "expeience" => $experience, "doj" => $doj, "pan" => $pan, "pf" => $pf, "esi" => $esi, "bank_name" => $bank_name, "bank_account" => $bank_accno, "ifsc"=>$ifsc,"address" => $address, "addressproof1" => '', "addressproof2" => '', "role_id" => $role_id, "modifiedon" => $dt, "modifiedby" => $dt, "worked_for" => $company,"ofc_email"=>$ofc_email,"ofc_contact"=>$ofc_contact,"designation"=>$designation);
            if ($employee > 0) {
                if ($user != $employee) {
                    $data["report_to"] = $report_to;
                }
                $action_status = $this->model_all->update($data, array("id" => $employee), "employees");
                if ($action_status) {
                    
                    $flag2 = true;
                }
                

                $code_set = $this->model_all->getTableData($table, array("uniq_id" => $emp_code, "id!=" => $employee));
                if ($code_set->num_rows() > 0) {
                    $message = "Employee Code already assigned to another employee";
                } else {
                    $this->model_all->update(array("uniq_id" => $emp_code), array("id" => $employee), $table);
                    if ($user != $employee) {
                        $this->model_all->update(array("username" => $emp_code, "modifiedon" => $dt, "modifiedby" => $user), array("pkid" => $employee, "role" => 'trade'), "app_users");
                    }
                }


                $address_qry = $this->model_all->getTableData("addresses", array("is_default" => 1, "user_id" => $employee, "user_role" => 'trade', "status" => '1'));
                if ($address_qry->num_rows() > 0) {
                    $address_rs = $address_qry->row();
                    $this->model_all->update(array("latitude" => $latitude, "longitude" => $longitude, "address" => $address, "city" => $town, "state" => $state, "country" => $country, "locale" => $location, "pincode" => $pincode), array("id" => $address_rs->id), "addresses");
                } else {
                    $this->model_all->save(array("latitude" => $latitude, "longitude" => $longitude, "address" => $address, "city" => $town, "state" => $state, "country" => $country, "locale" => $location, "pincode" => $pincode, "is_default" => 1, "user_id" => $employee, "user_role" => 'trade', "status" => '1'), "addresses");
                }
            } else {
                $data["createdby"] = $user;
                $data["createdon"] = $dt;
                $data["report_to"] = $report_to;
                $employee = $this->model_all->save($data, "employees");
                $prefix = "";
                $prefix_qry = $this->model_all->getTableData("companies", array("company_id" => $company), "prefix");
                if ($prefix_qry->num_rows > 0) {
                    $prefix_rs = $prefix_qry->row();
                    $prefix = $prefix_rs->prefix;
                }
                if ($employee > 0) {
                    $flag1 = true;
                    $this->model_all->save(array("latitude" => $latitude, "longitude" => $longitude, "address" => $address, "city" => $town, "state" => $state, "country" => $country, "locale" => $location, "pincode" => $pincode, "is_default" => 1, "user_id" => $employee, "user_role" => 'trade', "status" => '1'), "addresses");
                    if ($emp_code == "") {
                        $uniq_code = $this->model_all->prefix_zeros($employee);
                        $uniq_code = $prefix . $uniq_code;
                        $this->model_all->update(array("uniq_id" => $uniq_code), array("id" => $employee), $table);
                    } else {
                        $code_set = $this->model_all->getTableData($table, array("uniq_id" => $emp_code, "id!=" => $employee));
                        if ($code_set->num_rows() > 0) {
                            $message = "Employee Code already assigned to another employee";
                        } else {
                            $uniq_code = $emp_code;
                            $this->model_all->update(array("uniq_id" => $uniq_code), array("id" => $employee), $table);
                        }
                        
                    }
                    $randm_password = $this->model_all->randomPassword();
                    $this->model_all->save(array("dp" => 'noimage.png', "username" => $uniq_code, "password" => md5($randm_password), "role" => $role_nm, "pkid" => $employee, "status" => '1', "createdon" => $dt, "modifiedon" => $dt, "createdby" => $user, "modifiedby" => $user), "app_users");
                    if($mobile!="")
                     $this->model_all->sendSMS_get($mobile, "You have successfully registered with NOVA. Your Access Details: ". $uniq_code." / ".$randm_password);
                    
                }
            }


            $role_nm = "trade";






            if ($employee > 0 && isset($_FILES) && isset($_FILES['addressproof1']) && $_FILES['addressproof1']['size'] > 0 && $_FILES['addressproof1']['error'] == 0) {

                $prrofname = time() . "_" . $_FILES['addressproof1']['name'];
                if (move_uploaded_file($_FILES['addressproof1']['tmp_name'], "address_proofs/" . $prrofname)) {
                    $proof1_status = $this->model_all->update(array("addressproof1" => $prrofname), array("id" => $employee), "employees");
                    if ($proof1_status) {
                        $flag2 = true;
                    }
                } else {
                    $flag2 = false;
                }
            }

            if ($employee > 0 && isset($_FILES) && isset($_FILES['addressproof2']) && $_FILES['addressproof2']['size'] > 0 && $_FILES['addressproof2']['error'] == 0) {

                $prrofname = time() . "_" . $_FILES['addressproof2']['name'];
                if (move_uploaded_file($_FILES['addressproof2']['tmp_name'], "address_proofs/" . $prrofname)) {
                    $proof2_status = $this->model_all->update(array("addressproof2" => $prrofname), array("id" => $employee), "employees");
                    if ($proof1_status) {
                        $flag2 = true;
                    }
                } else {
                    $flag2 = false;
                }
            }

            if ($flag1) {
                $result["message"] = "Employee Registered Successfully";
                $result["status"] = 1;
            } else if ($flag2) {
                $result["status"] = 1;
                $result["message"] = "Details Updated Successfully";
            } else {
                $result["status"] = 0;
                $result["message"] = "Details Submissionm unsucessful";
            }
            $this->response($result, 200);
        } else {
            $result["status"] = 0;
            $result["message"] = $error_name . " with this mobile number already Exists";
            $this->response($result, 200);
        }
        exit;
    }

    function edit_employee_post() {

        $user = $this->post('user');
        $employee = $this->post('employee');
        $branch = $this->post('branch');
        $company = $this->post('branch');
        $step = $this->post('step');
        $flag1 = true;
        if ($step == 1) {
            $first_name = $this->post('first_name');
            $last_name = $this->post('last_name');
            $gender = $this->post('gender');
            $dob = $this->post('dob');
            $father = $this->post('father');
            $mother = $this->post('mother');
            $mstatus = $this->post('mstatus');
            $address = $this->post('address');
            $country = $this->post('country');
            $state = $this->post('state');
            $town = $this->post('town');
            $pincode = $this->post('pincode');
            $location = $this->post('location');
            $blood_group = $this->post('blood_group');
            $latitude = $this->post('latitude');
            $longitude = $this->post('langitude');
            
            if ($dob != "")
                $dob = date("Y-m-d", strtotime($dob));
            
            $data = array("first_name" => $first_name, "last_name" => $last_name, "branch" => $branch, "dob" => $dob, "father" => $father, "mother" => $mother, "marital" => $mstatus, "address" => $address, "role_id" => $role, "modifiedon" => $dt, "modifiedby" => $user, "worked_for" => $company);
            $action_status = $this->model_all->update($data, array("id" => $employee), $table);
            $address_qry = $this->model_all->getTableDataFromQuery("select * from addresses where role_id='$employee' and role='trade'");
            if ($address_qry->num_rows() > 0) {
                $this->model_all->update(array("latitude" => $latitude, "longitude" => $longitude, "address" => $address, "city" => $town, "state" => $state, "country" => $country, "locale" => $location,"pincode"=>$pincode, "is_default" => 1, "user_id" => 1, "user_role" => 'trade', "status" => '1'), array("is_default" => '1', "role_id" => $employee, "role" => 'trade'));
            }
            if ($action_status) {
                $flag1 = true;
            }
        } else if ($step == 2) {
            $pan = $this->post('pan');
            $pf = $this->post('pf');
            $esi = $this->post('esi');
            $bank_accno = $this->post('bank_accno');
            $bank_name = $this->post('bank_name');
            $ifsc = $this->post('ifsc');
            $data = array("pan" => $pan, "pf" => $pf, "esi" => $esi, "bank_name" => $bank_name, "bank_account" => $bank_accno, "ifsc" => $ifsc, "modifiedon" => $dt, "modifiedby" => $dt);
            $action_status = $this->model_all->update($data, array("id" => $employee), $table);
            if ($action_status) {
                $flag1 = true;
            }
        } else if ($step == 3) {
            $employee_id = $this->post('employee_id');
            $doj = $this->post('doj');
            $dept = $this->post('dept');
            $role = $this->post('role');
            $ofc_email = $this->post('ofc_email');
            $ofc_contact = $this->post('ofc_contact');
            $experience = $this->post('experience');
            $report_to = $this->post('report_to');
            if ($doj != "")
                $doj = date("Y-m-d", strtotime($doj));
            $data = array("uniq_id" => $employee_id, "doj" => $doj, "dept" => $dept, "expeience" => $experience, "report_to" => $report_to, "role_id" => $role, "ofc_mail" => $ofc_email, "ofc_contact" => $ofc_contact, "modifiedon" => $dt, "modifiedby" => $user);
            $action_status = $this->model_all->update($data, array("id" => $employee), $table);
            if ($action_status) {
                $flag1 = true;
            }
        } else if ($step == 4) {
            $mobile = $this->post('mobile');
            $email = $this->post('email');
            $data = array("email" => $email, "mobile" => $mobile, "modifiedon" => $dt, "modifiedby" => $user);
            $action_status = $this->model_all->update($data, array("id" => $employee), $table);
            if ($action_status) {
                $flag1 = true;
            }
        }



        $table = "employees";
        $error_name = "Employee";
        $dt = date("Y-m-d H:i:s");



        if ($employee > 0 && isset($_FILES) && isset($_FILES['addressproof1']) && $_FILES['addressproof1']['size'] > 0 && $_FILES['addressproof1']['error'] == 0) {

            $prrofname = time() . "_" . $_FILES['addressproof1']['name'];
            if (move_uploaded_file($_FILES['addressproof1']['tmp_name'], "address_proofs/" . $prrofname)) {
                $proof1_status = $this->model_all->update(array("addressproof1" => $prrofname), array("id" => $employee), "employees");
                if ($proof1_status) {
                    $flag2 = true;
                }
            } else {
                $flag2 = false;
            }
        }

        if ($employee > 0 && isset($_FILES) && isset($_FILES['addressproof2']) && $_FILES['addressproof2']['size'] > 0 && $_FILES['addressproof2']['error'] == 0) {

            $prrofname = time() . "_" . $_FILES['addressproof2']['name'];
            if (move_uploaded_file($_FILES['addressproof2']['tmp_name'], "address_proofs/" . $prrofname)) {
                $proof2_status = $this->model_all->update(array("addressproof2" => $prrofname), array("id" => $employee), "employees");
                if ($proof1_status) {
                    $flag2 = true;
                }
            } else {
                $flag2 = false;
            }
        }

        if ($flag1) {
            $result["message"] = "Employee Detaild updated Successfully";
            $result["status"] = 1;
        } else if ($flag2) {
            $result["status"] = 1;
            $result["message"] = "Details Updated Successfully";
        } else {
            $result["status"] = 0;
            $result["message"] = "Details Submissionm unsucessful";
        }
        $this->response($result, 200);

        exit;
    }

    function delete_put() {
        $user = $this->put('employee');
        $user_role = $this->put('role');
        $result_set = $this->model_all->update(array("status" => '0'), array("role" => $user_role, "pkid" => $user), "app_users");
        if ($result_set) {
            $result["status"] = 1;
            $result["message"] = "User Deleted Successfully";
        } else {
            $result["status"] = 0;
            $result["message"] = "User Deletion Unsuccessful.";
        }
        $this->response($result, 200);
        exit;
    }
    
    
     function employeedetails_get() {
        $user = $this->get('user');
        $user_role = $this->get('role');

        $result_set = $this->model_all->getTableDataFromQuery("select e.*,r.role_name from  employees e, app_roles r where e.id='$user' and r.id=e.role_id");
        if($result_set->num_rows()>0){
              $row= $result_set->row_array();
              $result["status"] = 1;
              $result["message"] = "User Details Found.";
               $address_query = $this->model_all->getTableDataFromQuery("select a.address,c.id as country_id,c.name as country,s.id as state_id,s.state,a.city,a.pincode from  addresses a, countries c,states s where  a.user_id='$row[id]' and a.user_role='trade' and a.country=c.id and a.state=s.id and s.country=c.id");
              if($address_query->num_rows()>0){
                  $address_row = $address_query->row();
                  $row["address"]=  $address_row->address;
                  $row["country_id"]=  $address_row->country_id;
                  $row["state_id"]=  $address_row->state_id;
                  $row["country"]=  $address_row->country;
                  $row["state"]=  $address_row->state;
                  $row["city"]=  $address_row->city;
                  $row["pincode"]=  $address_row->pincode;
              
              }else{
                  $row["address"]=  "";
                  $row["country_id"]=  "";
                  $row["state_id"]=  "";
                  $row["country"]=  "";
                  $row["state"]=  "";
                  $row["city"]=  "";
                  $row["pincode"]=  "";
              
              }

              $row["dept"] = $this->model_all->tableFieldData("select name from departments where id='$row[dept]'","name");
              $row["report_to"] = trim($this->model_all->tableFieldData("select CONCAT_WS(' ',first_name,last_name) as name from employees where id='$row[report_to]'","name"));
              if(empty($row["mobile"])){
               $row["mobile"] = "NA";
              }        
              
              $result["details"][] = $row;
              
        }else{
             $result["status"] = 0;
             $result["message"] = "No Details Found.";
        }
      
        $this->response($result, 200);
        exit;
    }

    function licenses_get() {

        $result_set = $this->model_all->getTableDataInArray("licences");
        if (($result_set['total_rows']) > 0) {
            $result["status"] = 1;
            $result["message"] = "Records Found";
            $result["licenses"] = $result_set['records'];
            $this->response($result, 200);
            exit;
        } else {
            $result["status"] = 0;
            $result["message"] = "Records Not Found";
            $this->response($result, 200);
            exit;
        }
    }

    function dealerdetails_get() {
        $user = $this->get('user');
        $user_role = $this->get('role');
         $branch= $this->get('branch');
        /*$result_set = $this->model_all->getTableDataFromQuery("select e.*,(select first_name  from employees where id=e.sales_manager) as area_sm,a.dp from  sellers e,app_users a  where e.id='$user' and e.id=a.pkid and (a.role='DEALER' or a.role='SELLER')");*/
        $result_set = $this->model_all->getTableDataFromQuery("select e.*,(select ie.first_name  from employees ie,branch_dealers id where id.branch='$branch' and ie.id=id.seller and ie.id=e.id) as area_sm,a.dp from  sellers e,app_users a  where e.id='$user' and e.id=a.pkid and (a.role='DEALER' or a.role='SELLER')");
        if ($result_set->num_rows()) {
            $row = $result_set->row_array();

            $row["marketing_hq"]= $row["hq"];  
            $row["bank_account"] = $row["bank_accno"];
            if ($row["dp"] != "") {
                $file_headers = @get_headers(base_url() . 'dps/' . $row["dp"]);
                if (stripos($file_headers[0], "404 Not Found") > 0 || (stripos($file_headers[0], "302 Found") > 0 && stripos($file_headers[7], "404 Not Found") > 0)) {
                    $row["dp"] = base_url() . 'dps/noimage.png';
                } else {
                    $row["dp"] = base_url() . 'dps/' . $row["dp"];
                }
            }

            $license_qry = $this->model_all->getTableDataFromQuery("select * from delaer_licenses where  seller_id='$user'");
            if($license_qry->num_rows()>0){
               $license_rs = $license_qry->row();
               $row["fertilizer"]= $license_rs->fertilizer;
               $row["fertilizer_upto"]= $license_rs->fertilizer_upto;
               $row["pesticide"]= $license_rs->pesticide;
               $row["pesticide_upto"]= $license_rs->pesticide_upto;
               $row["seed"]= $license_rs->seed;
               $row["seed_upto"]= $license_rs->seed_upto;
               $row["other"]= $license_rs->other;

             }else{

               $row["fertilizer"]= "";
               $row["fertilizer_upto"]="";
               $row["pesticide"]="";
               $row["pesticide_upto"]="";
               $row["seed"]="";
               $row["seed_upto"]="";
               $row["other"]="";

             }

            $sales_qry = $this->model_all->getTableDataFromQuery("select * from employees where  id='$row[sales_manager]'");
//echo "select * from employees where  id='$row[sales_manager]'";
            if($sales_qry->num_rows()>0){
               $sales_rs = $sales_qry->row();
               $row["marketing_name"]= $sales_rs->first_name;
               $row["marketing_contact"]= $sales_rs->mobile;
               $row["marketing_email"]= $sales_rs->email;
                            
             }else{
               $row["marketing_name"]= "";
               $row["marketing_contact"]= "";
               $row["marketing_email"]= "";
              // $row["marketing_hq"]= "";
             }



            $row["dp"] = $row["dp"] . "?" . time();
            $result["status"] = 1;
            $result["message"] = "User Details Found.";
            $address_query = $this->model_all->getTableDataFromQuery("select a.door_no,a.street_name,a.landmark,a.district as district_id,d.district,a.address,a.id as address_id,c.id as country_id,c.name as country,s.id as state_id,s.state,a.city,a.pincode from  addresses a, countries c,states s,districts d where  a.user_id='$row[id]' and a.user_role='DEALER' and a.country=c.id and a.state=s.id and s.country=c.id and d.state=s.id");
            if ($address_query->num_rows() > 0) {
                $address_row = $address_query->row();
                $row["door_no"] = $address_row->door_no;
                $row["street_name"] = $address_row->street_name;
                $row["landmark"] = $address_row->landmark;
                $row["district"] = $address_row->district;
                $row["district_id"] = $address_row->district_id;

                $row["address"] = $address_row->address;
                $row["country_id"] = $address_row->country_id;
                $row["state_id"] = $address_row->state_id;
                $row["country"] = $address_row->country;
                $row["state"] = $address_row->state;
                $row["city"] = $address_row->city;
                $row["pincode"] = $address_row->pincode;
            } else {
                $row["address"] = "";
                $row["country"] = "";
                $row["country_id"] = "";
                $row["state_id"] = "";
                $row["state"] = "";
                $row["city"] = "";
                $row["pincode"] = "";
                $row["door_no"] = "";
                $row["street_name"] = "";
                $row["landmark"] = "";
                $row["district"] = "";
                $row["district_id"] = "";
            }

            $row["licences"]=array();
            $license_query = $this->model_all->getTableDataFromQuery("select  l.name,s.* from licences l,seller_licenses s where s.license_id=l.id and s.seller_id='$user'");
            if ($license_query->num_rows() > 0) {
                foreach ($license_query->result_array() as $license_row) {
                   $row["licences"][] = $license_row;
                }
            }

            $contact_query = $this->model_all->getTableDataFromQuery("select name as key_person,email as ofc_email, contact_personal as ofc_contact,contact_watsup  as ofc_whatsapp from contacts where role='DEALER' and role_id='$user'");
            if ($contact_query->num_rows() > 0) {

                $contact_row = $contact_query->row_array();
                /* $row["key_person"] =  $contact_row->name;
                  $row["ofc_email"] =   $contact_row->email;
                  $row["ofc_contact"] = $contact_row->contact_personal;
                  $row["ofc_whatsapp"] =  $contact_row->contact_watsup ;
                  $row["area_sm"] = "User Details Found."; */
            } else {
                $contact_row["key_person"] = "";
                $contact_row["ofc_email"] = "";
                $contact_row["ofc_contact"] = "";
                $contact_row["ofc_whatsapp"] = "";
            }
            $result["details"][] = $row;
            $result["contact"][] = $contact_row;
        } else {
            $result["status"] = 0;
            $result["message"] = "No Details Found.";
        }

        $this->response($result, 200);
        exit;
    }


    function page_dealers_get() {

        $branch = $this->get("branch");
        $role = $this->get("role");
        $user = $this->get("user");
        $page = $this->get("page");
        $token = $this->get("token");
        if($page==""){
            $page = 1;
        }

       

        $limit = 1000;
        $start = ($page-1)*$limit;

        $query = "select s.id,s.dealer_code,s.first_name,s.last_name,s.company_name,s.status from sellers s,branches b  where b.id=s.branch ";
        if ($role == "SE") {
            // $query = $query." and sales_manager='$user'";
        }


        if ($branch != "") {
            $query .= " and s.branch IN ('$branch')";
        }

         if($token!=""){
          

            $query = $query ." and ((s.first_name like '%$token%') or (s.last_name like '%$token%') or (s.dealer_code like '%$token%') or (s.company_name like '%$token%'))";
        }
    
        $query = $query." limit $start,$limit";
        $result_set = $this->model_all->getTableDataFromQuery($query);
        if ($result_set->num_rows() > 0) {
            $result["status"] = 1;
            $result["message"] = "Records Found";
            foreach ($result_set->result() as $row) {
                $object = array();
                $object["id"] = $row->id;
                $object["name"] = ucwords($row->first_name . " " . $row->last_name);
                $object["emp_id"] = $row->dealer_code;
                $object["status"] = $row->status;
                $object["company_name"] = $row->company_name;
                $result["employees"][] = $object;
            }

            $this->response($result, 200);
            exit;
        } else {
            $result["status"] = 0;
            $result["message"] = "Details Not Found";
            $this->response($result, 200);
            exit;
        }
    }
    
    function test_get($id){
    
       $add_det_query =  $this->db->query("CALL seller_address($id)");
       return $add_det_query;
    }


}
