<?php

require(APPPATH . '/libraries/REST_Controller.php');

class Login extends REST_Controller {

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
                        $result["branch_count"] = 0;
                        $result["role_count"] = 0;
                        $result["roles"]= array();

                        $result["companies"]= array();
                        $branch_names = array();

                        if($emp_rs->company!=""){
  echo "select c.company_id, c.company,b.id,o.name as branch_name from companies c,branches b, offices o where c.company_id in ($emp_rs->company) and  b.company=company_id and b.office_id=o.id and b.id='$emp_rs->branch'";                          
                                $company_query = $this->model_all->getTableDataFromQuery("select c.company_id, c.company,b.id,o.name as branch_name from companies c,branches b, offices o where c.company_id in ($emp_rs->company) and  b.company=company_id and b.office_id=o.id and b.id='$emp_rs->branch'");
                                foreach($company_query->result_array() as $company_row){
                                   if(!in_array($company_row["branch_name"],$branch_names)){
                                      $branch_names[] = $company_row["branch_name"];
                                     
                                   }
                                   $result["companies"][] = $company_row;
                                }
                            
                        }


                        $branch_values = array();
                        $role_values = array();
echo "select c.company_id,c.company,b.id as branch_id,b.name as  branch_name,ar.short_form,ar.id as role_id,ar.role_name as role_name from emp_roles er,branches b, offices o,app_roles ar,companies c where er.employee_id='$emp_rs->id' and er.branch_id=b.id and b.office_id=o.id and ar.id=er.role_id and b.company=c.company_id";
                        $emp_roles_query = $this->model_all->getTableDataFromQuery("select c.company_id,c.company,b.id as branch_id,b.name as  branch_name,ar.short_form,ar.id as role_id,ar.role_name as role_name from emp_roles er,branches b, offices o,app_roles ar,companies c where er.employee_id='$emp_rs->id' and er.branch_id=b.id and b.office_id=o.id and ar.id=er.role_id and b.company=c.company_id");
                        foreach($emp_roles_query->result_array() as $roles_row){
                               if(!in_array($roles_row["branch_id"],$branch_values)){
                                   $branch_values = $roles_row["branch_id"];
                   
                               } 
                               if(!in_array($roles_row["role_id"],$role_values)){
                                   $role_values = $roles_row["role_id"];
                   
                               } 
                               $result["roles"][] = $roles_row;
                           
                        }
                        $result["branch_count"] = count($branch_values);
                        $result["role_count"] =  count($role_values);











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
                } else {
                    $result["status"] = 0;
                    $result["message"] = "Invalid Credentials";
                }
            } else if ($rs->role == "seller") {


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

                        $result["branch_count"] = 0;
                        $result["role_count"] = 0;
                        $result["roles"]= array();


                        $branch_names = array();
                        if($dealer_for!=""){
                            if($dealer_for!=""){

                                $company_query = $this->model_all->getTableDataFromQuery("select c.company_id, c.company,b.id,o.name as branch_name from companies c,branch_dealers d, branches b, offices o where c.company_id in ($dealer_for) and d.seller='$srs->id' and d.branch=b.id and b.company=company_id and b.office_id=o.id");
                                foreach($company_query->result_array() as $company_row){
                                   if(!in_array($company_row["branch_name"],$branch_names)){
                                      $branch_names[] = $company_row["branch_name"];
                                     
                                   }
                                   $result["companies"][] = $company_row;
                                }
                            }
                        }



                        if(count($branch_names)>0){
                         $result["branch_name"] = implode(",",$branch_names);
                        }
                        $result["latitude"] = "";
                        $result["longitude"] = "";
                        $result["locale"] = "";
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

        if ($mobile != "") {
            $result_set = $this->model_all->getTableData("app_users", array("username" => $mobile));
           
            if ($result_set->num_rows() > 0) {
                $rs = $result_set->row();
                if ($rs->role == "trade") {
                     $req_qry = $this->model_all->getTableDataFromQuery("select e.mobile from employees e,app_roles a where a.id=e.role_id and e.id='$rs->pkid'");
                }else if ($rs->role == "store") { 
                      $req_qry = $this->model_all->getTableDataFromQuery("select s.mobile from stores s, addresses a where a.user_id=s.id and a.user_role='store' and a.status='1' and a.is_default='1' and s.id='$rs->pkid' and s.aprv_status='1' and s.status='1'");
                }else if ($rs->role == "seller") {
                      $req_qry = $this->model_all->getTableData("sellers", array("id" => $rs->pkid, "status" => '1'),"mobile");
                }
                if($req_qry->num_rows() > 0){
                    $req_rs = $req_qry->row();
                    $mobile_number = $req_rs ->mobile;
                    $password = $this->model_all->randomPassword(); //turn the array into a string
                    $enc_password = md5($password);
                    $this->model_all->update(array("password"=>$enc_password),array("username" =>$mobile), "app_users");
                    $message = "Dear User,Your temporary password :  " . $password . ". Please login with your registered mobile number and change your password";
                    $this->model_all->get_sendSMS($mobile_number, $message);
                    $result["status"] = 1;
                    $result["message"] = "Temporary password sent to given registered mobile No.";
                }else{
                     $result["status"] = 0;
                     $result["message"] = "User Does not Exists";
                }

               
                
            } else {
                $result["status"] = 0;
                $result["message"] = "User Does not Exists";
            }
        } else {
            $result["status"] = 0;
            $result["message"] = "Field(s) are Missing";
            $this->response($result, 200);
        }
        $this->response($result, 200);


        exit;
    }




}
