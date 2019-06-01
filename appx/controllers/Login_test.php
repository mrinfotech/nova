<?php

require(APPPATH . '/libraries/REST_Controller.php');

class Login_test extends REST_Controller {

    public function __construct() {
        parent::__construct();

        $this->load->model('model_all');
    }

    //API - client sends isbn and on valid isbn book information is sent back
    function index_post() {

        $username = $this->post('username');
        $password = $this->post('password');
        $result=array();
        if (!$username || !$password) {
            $result["status"] = 0;
            $result["message"] = "Username / Password Cannot be Empty";
            $this->response($result, 200);
            exit;
        }
        $password = md5($password);
        $result_set = $this->model_all->getTableData("app_users", array("username" => $username, "password" => $password));
       
        if ($result_set->num_rows() > 0) {
            $rs = $result_set->row();
            // echo $rs->role;

            if ($rs->role == "trade") {
                // echo "select e.*,a.role_name from employees e,app_roles a where a.id=e.role_id and e.id='$rs->pkid'";
                $emp_qry = $this->model_all->getTableDataFromQuery("select e.*,a.role_name,a.short_form,b.name as branch_name,b.contact_no as mobile_numbs,b.company from employees e,app_roles a,branches b where a.id=e.role_id and e.id='$rs->pkid' and b.id=e.branch");

                if ($emp_qry->num_rows() > 0) {

                    $result["status"] = 1;
                    $result["message"] = "Valid User";
                    $result["primaryid"] = $rs->pkid;
                  
                   
                    $result["dp"] = base_url() . "dps/noimage.png";
                    if ($rs->dp != "") {
                        $file_headers = @get_headers(base_url() . 'dps/' . $img_rs->img_name);
                        if (stripos($file_headers[0], "404 Not Found") > 0 || (stripos($file_headers[0], "302 Found") > 0 && stripos($file_headers[7], "404 Not Found") > 0)) {
                            
                        } else {
                            $result["dp"] = base_url() . 'dps/' . $rs->dp;
                        }
                    }
                    $result["dp"] = $result["dp"] . "?" . time();

                    foreach ($emp_qry->result() as $emp_rs) {

                        $result["uni_code"] = $emp_rs->uniq_id; 
                        $result["branch"] = $emp_rs->branch;
                         $result["branch_name"] = $emp_rs->branch_name;
                         $result["branch_contact"] = $emp_rs->mobile_numbs;
                        $result["company"] = $emp_rs->company;
                        $result["role"] = $emp_rs->role_name;
                        $result["short_form"] = $emp_rs->short_form;
                        $result["userName"] = ucwords($emp_rs->first_name . " " . $emp_rs->last_name);
                        $result["mobile"] = $emp_rs->mobile;
                        $result["address"] = $emp_rs->address; 

                        $result["address_id"] = "";
                        $result["address"] = "";
                        $result["latitude"] = "";
                        $result["longitude"] = "";
                        $result["locale"] = "";
                        $result["pincode"] = "";

                        $result["companies"] = array();
                      
                        $branch_names = array();
                        if($emp_rs->company!=""){
                            
                                $company_query = $this->model_all->getTableDataFromQuery("select c.company_id, c.company,b.id,o.name as branch_name from companies c,branches b, offices o where c.company_id in ($emp_rs->company) and  b.company=company_id and b.office_id=o.id and b.id in ($emp_rs->branch)");
                                foreach($company_query->result_array() as $company_row){
                                   if(!in_array($company_row["branch_name"],$branch_names)){
                                      $branch_names[] = $company_row["branch_name"];
                                     
                                   }
                                   $result["companies"][] = $company_row;
                                }
                            
                        }

                        $branch_values = array();
                        $role_values = array();
                        $company_values = array();
                        $emp_roles_query = $this->model_all->getTableDataFromQuery("select er.id,c.company_id,c.company,b.id as branch_id,b.name as  branch_name,b.contact_no as branch_contact,ar.short_form,ar.id as role_id,ar.role_name as role_name from emp_roles er,branches b, offices o,app_roles ar,companies c where er.employee_id='$emp_rs->id' and er.branch_id=b.id and b.office_id=o.id and ar.id=er.role_id and b.company=c.company_id");
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











                    }

                    




                } else {
                    $result["status"] = 0;
                    $result["message"] = "Invalid Credentials";
                }
            } else if ($rs->role == "store") {


                $store_sql = $this->model_all->getTableDataFromQuery("select s.*,a.latitude,a.longitude,a.id as addr_id ,a.address,a.locale from stores s, addresses a where a.user_id=s.id and a.user_role='store' and a.status='1' and a.is_default='1' and s.id='$rs->pkid' and s.aprv_status='1' and s.status='1'");
                if ($store_sql->num_rows() > 0) {
                    $result["status"] = 1;
                    $result["message"] = "Valid User";
                    $result["role"] = "Store";
                    $result["primaryid"] = $rs->pkid;
                    $result["dp"] = base_url() . "dps/noimage.png";
                    if ($rs->dp != "") {
                        $file_headers = @get_headers(base_url() . 'dps/' . $img_rs->img_name);
                        if (stripos($file_headers[0], "404 Not Found") > 0 || (stripos($file_headers[0], "302 Found") > 0 && stripos($file_headers[7], "404 Not Found") > 0)) {
                            
                        } else {
                            $result["dp"] = base_url() . 'dps/' . $rs->dp;
                        }
                    }
                    $result["dp"] = $result["dp"] . "?" . time();
                    foreach ($store_sql->result() as $srs) {
                        $result["userName"] = $srs->name;
                        $result["mobile"] = $srs->mobile;
                        $result["address_id"] = $srs->addr_id;
                        $result["address"] = $srs->address;
                        $result["latitude"] = $srs->latitude;
                        $result["longitude"] = $srs->longitude;
                        $result["locale"] = $srs->locale;
                        $result["pincode"] = $srs->pincode;
                    }

                        $result["branch_count"] = 0;
                        $result["role_count"] =  0;
                        $result["roles"] = array();
                } else {
                    $result["status"] = 0;
                    $result["message"] = "Invalid Credentials";
                }
            } else if ($rs->role == "seller") {

                //  echo "select s.*,b.name as branch_name,b.contact_no as mobile_numbs,b.company from sellers s,branches b where s.id='$rs->pkid' and s.status='1' and b.id IN(s.branch)";
                $req_sql = $this->model_all->getTableDataFromQuery("select s.*,b.name as branch_name,b.contact_no as mobile_numbs,b.company from sellers s,branches b where s.id='$rs->pkid' and s.status='1' and b.id IN(s.branch)");
                // echo $this->db->last_query();
                if ($req_sql->num_rows() > 0) {
                    $result["status"] = 1;
                    $result["message"] = "Valid User";
                    $result["role"] = "Seller";
                    $result["short_form"] = "DEALER";
                    $result["primaryid"] = $rs->pkid;
                    $result["dp"] = base_url() . "dps/noimage.png";
                    if ($rs->dp != "") {
                        $file_headers = @get_headers(base_url() . 'dps/' . $img_rs->img_name);
                        if (stripos($file_headers[0], "404 Not Found") > 0 || (stripos($file_headers[0], "302 Found") > 0 && stripos($file_headers[7], "404 Not Found") > 0)) {
                            
                        } else {
                            $result["dp"] = base_url() . 'dps/' . $rs->dp;
                        }
                    }
                    $result["dp"] = $result["dp"] . "?" . time();
                    foreach ($req_sql->result() as $srs) {
                        $result["uni_code"] = $srs->dealer_code; 
                        $result["userName"] = ucwords($srs->company_name); //ucwords($srs->company_name. " " . $srs->last_name);
                        $result["mobile"] = $srs->mobile;
                        $result["branch"] = $srs->branch;
                        $result["branch_contact"] = "";
                        $result["company"] = $srs->dealer_for;
                        $result["branch_name"] = "";
                        $result["address"] = $srs->address;
                        $result["address_id"] = "";
                        $dealer_for = $srs->dealer_for;
                        $result["companies"]= array();
                        $branch_names = array();
                        if($dealer_for!=""){
                            if($dealer_for!=""){
                               // echo "select distinct c.company_id, c.company,b.id,o.name as branch_name from companies c,branch_dealers d, branches b, offices o where c.company_id in ($dealer_for) and d.seller='$srs->id' and d.branch=b.id and b.company=company_id and b.office_id=o.id";
                                $company_query = $this->model_all->getTableDataFromQuery("select distinct c.company_id, c.company,b.id,o.name as branch_name,b.contact_no from companies c,branch_dealers d, branches b, offices o where c.company_id in ($dealer_for) and d.seller='$srs->id' and d.branch=b.id and b.company=company_id and b.office_id=o.id");
                                foreach($company_query->result_array() as $company_row){
                                   if(!in_array($company_row["branch_name"],$branch_names)){
                                      $branch_names[] = $company_row["branch_name"];
                                     
                                   }
                                   $result["companies"][] = $company_row;
                                }
                            }
                        }


                        print_r($result["companies"]);
                        if(count($branch_names)>0){
                         $result["branch_name"] = implode(",",$branch_names);
                        }
                        $result["latitude"] = "";
                        $result["longitude"] = "";
                        $result["locale"] = "";
                        $result["branch_count"] = 0;
                        $result["role_count"] =  0;
                        $result["roles"] = array();
                        //$result["pincode"] = $srs->pincode;
                    }
                } else {
                    $result["status"] = 0;
                    $result["message"] = "Invalid Credentials";
                }
            }

            $this->response($result, 200);
            exit;
        } else {
            $result["status"] = 0;
            $result["message"] = "Invalid Credentials";
            $this->response($result, 200);
            exit;
        }
    }

    function changepassword_put() {
        $oldpassword = $this->put('oldpassword');
        $newpassword = $this->put('newpassword');
        $pid = $this->put('primaryid');
        $role = $this->put('role');
        if ($pid != "" && $role != "" && $oldpassword != "" && $newpassword != "") {
            $old_password = md5($oldpassword);
            $new_password = md5($newpassword);
            if ($role == "admin") {
                $log_qry = $this->model_all->getTableData("app_users", array("password" => $old_password, "id" => $pid));
            } else {
                $log_qry = $this->model_all->getTableData("app_users", array("password" => $old_password, "pkid" => $pid));
            }
            if ($log_qry->num_rows() > 0) {
                if ($old_password == $new_password) {
                    $result["status"] = 2;
                    $result["message"] = "Old password and New Password are Equal.";
                } else {
                    if ($role == "admin") {
                        $upd_status = $this->model_all->update(array("password" => $new_password), array("id" => $pid), "app_users");
                    } else {
                        $upd_status = $this->model_all->update(array("password" => $new_password), array("pkid" => $pid), "app_users");
                    }
                    if ($upd_status) {
                        $result["status"] = 1;
                        $result["message"] = " Password Changed Successfully.";
                    }
                }
            } else {
                $result["status"] = 0;
                $result["message"] = "Old password is mismatched";
            }
            $this->response($result, 200);
            exit;
        } else {
            $result["status"] = 0;
            $result["message"] = "some fields are missing";
            $this->response($result, 200);
            exit;
        }
    }

     function forgotpassword_put() {
        $mobile = $this->put('username');
        $uni_code ="";
        $flag = false;
        $mobile_number="";
        $title="";
        

         
        
   
        if ($mobile != "") {

            $query = "select e.id,a.username,e.mobile,e.ofc_contact from app_users a,employees e where a.username=e.uniq_id and a.role='trade'  and a.pkid=e.id ";
            if(is_numeric($mobile)) {
               $query = $query." and (e.ofc_contact='$mobile' or e.mobile='$mobile')";
            }else{
               $query = $query." and a.username='$mobile'";
            }
            $result_set = $this->model_all->getTableDataFromQuery($query);
            if($result_set->num_rows()>0){
                 $req_rs = $result_set->row();
                 if($req_rs->ofc_contact!="" && $req_rs->ofc_contact!="NA"){
                     $mobile_number = $req_rs->ofc_contact;                 
                 }else if($req_rs->mobile!="" && $req_rs->mobile!="NA"){
                     $mobile_number = $req_rs->mobile;
                 }
                 $uni_code = $req_rs->username;
                 $flag =true;
                 $msg_qry = $this->model_all->getTableDataFromQuery("select b.company from emp_roles e,branches b  where e.employee_id='$req_rs->id' and e.branch_id=b.id");
                 if($msg_qry->num_rows()>0){
                     $msg_row=$msg_qry->row();
                     if($msg_row->company==1){
                        $title="NOVAAS";
                     }else{
                        $title="NOVAAT";
                     }
             
                 }


                 
 


            }else{
                $query = "select s.id,a.username,s.mobile,s.contact1 from app_users a,sellers s where a.username=s.dealer_code and a.role='seller'  and a.pkid=s.id ";
               if(is_numeric($mobile)) {
                $query = $query." and (s.mobile='$mobile' or s.contact1='$mobile')";
               }else{
                $query = $query." and a.username='$mobile'";
               }
                $result_set = $this->model_all->getTableDataFromQuery($query);
                
   
               
                if($result_set->num_rows()>0){
                     $req_rs = $result_set->row();
                     $uni_code = $req_rs->username;
                     if($req_rs->contact1!="" && $req_rs->contact1!="NA"){
                       $mobile_number = $req_rs->contact1;
                     }else if($req_rs->mobile!="" && $req_rs->mobile!="NA"){
                      $mobile_number = $req_rs->mobile;
                     }
                     $flag =true;
                     
                     $msg_qry = $this->model_all->getTableDataFromQuery("select b.company from branch_dealers e,branches b  where e.seller='$req_rs->id' and e.branch=b.id");
                     if($msg_qry->num_rows()>0){
                       $msg_row=$msg_qry->row();
                       if($msg_row->company==1){
                         $title="NOVAAS";
                       }else{
                         $title="NOVAAT";
                       }
                     }
                }
             

            }

            if($flag){
                  
                   
                    $password = $this->model_all->randomPassword(); //turn the array into a string
                    $enc_password = md5($password);
                    $this->model_all->update(array("password"=>$enc_password),array("username" =>$uni_code), "app_users");
                    if($mobile_number!="NA" && $mobile_number!="0" && $mobile_number!=""){
                      $message = "Dear User,Your temporary password is:  " . $password . " and User Name is: " . $uni_code . ". Please do login with above details for further process.";
                      $this->model_all->sendSMS_get($mobile_number, $message);
                    }
                    $result["status"] = 1;
                    $result["message"] = "Temporary password sent to given registered mobile No.";

            }else{
                  $result["status"] = 0;
                  $result["message"] = "User Does not Exists";
            }


           
            
        } else {
            $result["status"] = 0;
            $result["message"] = "Field(s) are Missing";
            
        }
        $this->response($result, 200);


        exit;
    }




}
