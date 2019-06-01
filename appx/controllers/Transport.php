<?php

require(APPPATH . '/libraries/REST_Controller.php');

class Transport extends REST_Controller {

    public function __construct() {
        parent::__construct();
        $this->load->model('model_all');
    }

    //API - Fetch All Pincodes
    function list_get() {
        $branch = $this->get("branch");
        $result_set = $this->model_all->getTableDataInArray("transport", array("branch" => $branch,'transport_type'=>'reg'), "id,name");
        if (($result_set['total_rows']) > 0) {
            $result["status"] = 1;
            $result["message"] = "Records Found";
            $result["transports"] = $result_set['records'];
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

    

    

    //API - Save Pin Code
    function manage_post() {

        $id = $this->post('id');
        $user = $this->post('user');
        $name = $this->post('name');
        $contact_no = $this->post('contact_no');
        $email = $this->post('email');
        $address = $this->post('address');
        $transport_type = $this->post('transport_type');
        $branch = $this->post('branch');
        if($transport_type==""){
          $transport_type='reg';
        }
        $table="transport";
        

     
        $dt = date("Y-m-d H:i:s");

        //  print_r($_FILES);

        

      

        if ($id> 0) {
            $result_set = $this->model_all->getTableData($table, array("name"=>$name,"contact_no" => $contact_no, "id!="=> $id));
        } else {
            $result_set = $this->model_all->getTableData($table, array("name"=>$name,"contact_no" => $contact_no));
        }

        if ($result_set->num_rows() == 0) {
            
           
                  $data = array('name'=>$name ,'contact_no'=>$contact_no,'email'=>$email,'address'=>$address,'transport_type'=>$transport_type,'branch'=>$branch,'modified_by'=>$user,'modified_on'=>$dt);
                if ($id > 0) {
                    $action_status = $this->model_all->update($data, array("id" => $id), $table);
                    if ($action_status) {
                        $flag = true;
                    }
                } else {
                    $data["created_by"] = $user;
                    $data["created_on"] = $dt;
                    $id = $this->model_all->save($data, $table);
                    if ($id > 0) {
                        $flag = true;
                    }
                }


              

               
            



           
            if ($flag) {
                $result["status"] = 1;
                $result["message"] = "Details Submission Successful";   
            } else {
                $result["status"] = 0;
                $result["message"] = "Details Submission Unsuccessful";
            }
            $this->response($result, 200);
        } else {
            $result["status"] = 0;
            $result["message"] = " Transport already Exists";
            $this->response($result, 200);
        }
        exit;
    }

    function delete_put() {
        $transport = $this->put('transport');
        $user = $this->put('user');
        $dt = date("Y-m-d H:i:s");
        $action_status = $this->model_all->update(array("status" => '0',"modified_on"=>$dt,"modified_by"=>$user), array("id" => $transport), "transport");
        if ($action_status) {
            $result["status"] = 1;
            $result["message"] = "Transport Deleted Successfully";
        } else {
            $result["status"] = 0;
            $result["message"] = "Transport Deletion Unsuccessful.";
        }
        $this->response($result, 200);
        exit;
    }


    function details_get() {
        $order = $this->get('order');
        $dt = date("Y-m-d H:i:s");
     
        $result_set = $this->model_all->getTableDataFromQuery("select dr.estimation_time,dr.from_route,dr.to_route,dr.paid,dr.amount,dv.contact,dv.vechicle_number,dv.driver_number,dv.driver_name,dv.lr_no,t.name as transport_name,t.contact_no,t.transport_type,e.first_name as emp_name,s.company_name from delivery_route dr,delivery_vehicles dv, transport t,deliver_route_order dro,employees e,seller_orders o,sellers s where dr.id=dv.route_id and dr.id=dro.droute_id and dv.transport=t.id and FIND_IN_SET('$order',dro.orders) and  e.id=t.created_by and o.id='$order' and o.orderedby=s.id");
        if ($result_set->num_rows()>0) {
            $result["status"] = 1;
            $result["message"] = "Records found";
            $result["details"][] =  $result_set->row_array();
        } else {
            $result["status"] = 0;
            $result["message"] = "Transport Details Not Found.";
        }
        $this->response($result, 200);
        exit;
    }
    
    
    
    function updatelrno_post() {
        $id = $this->post('id');
        $lrno = $this->post('lrno');
        $user = $this->post('user');
     
        $affected_rows = $this->model_all->update(array("lr_no"=>$lrno,"lr_updated_by"=>$user),array("id"=>$id),"delivery_vehicles");
        if ($affected_rows) {
            $result["status"] = 1;
            $result["message"] = "LR NO Updated successfully";
            
        } else {
            $result["status"] = 0;
            $result["message"] = "Updation Failed.";
        }
        $this->response($result, 200);
        exit;
    }
    
    
    
    function emptylrno_get() {
       
         $user = $this->get('user');
     
        $result_set = $this->model_all->getTableDataFromQuery("select dv.id,o.id as oid,o.order_id, o.order_value, dr.from_route, dr.to_route, dv.contact, dv.vechicle_number, dv.driver_number,dv.driver_name,dv.lr_no,t.name as transport_name,t.contact_no,t.transport_type,e.first_name as emp_name,s.company_name from delivery_route dr,delivery_vehicles dv, transport t,deliver_route_order dro,employees e,seller_orders o,sellers s where dr.id=dv.route_id and dr.id=dro.droute_id and dv.transport=t.id and FIND_IN_SET(o.id,dro.orders) and  e.id=t.created_by and e.id='$user' and o.orderedby=s.id "); //t.transport_type='private' and and  dv.lr_no=''
        if ($result_set->num_rows()>0) {
            $result["status"] = 1;
            $result["message"] = "Records found";
            $result["lr_details"] = array();
            foreach($result_set->result_array() as $row){
              $result["lr_details"][] =  $row;
            }
            
        } else {
            $result["status"] = 0;
            $result["message"] = "Transport Details Not Found.";
        }
        $this->response($result, 200);
        exit; 
    }

}
