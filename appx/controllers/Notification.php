<?php

require(APPPATH . '/libraries/REST_Controller.php');

class Notification extends REST_Controller {

    public function __construct() {
        parent::__construct();
        $this->load->model('model_all');
        $this->load->library('fcm');
    }

    //API - Fetch All Pincodes
    function test_get() {
	echo $this->fcm->echotest();
    }


    function store_token_post(){

      $user = $this->post("user");
      $role = $this->post("role");
      $token = $this->post("token");
      if($role=="DEALER" || $role=="seller"){
         $role ="seller";
      }else {
         $role = "trade";

      }

        $affected_rows = $this->model_all->update(array("fcm_id"=>$token),array("pkid"=>$user,"role"=>$role),"app_users");
        if($affected_rows){
            $result["status"] = 1;
            $result["message"] = "Device Token Registered Successfully.";

        }else {
            $result["status"] = 0;
            $result["message"] = "No records Found";
           
        } 

         $this->response($result, 200);
         exit;

    }
    
    
    function sendNotify_get(){
     if($fcm_key!=""){
       $payload = array();
       $data = array();
       $payload['title'] = "";
       $payload['body'] = "Order body"; /// Message goes here
       $payload['icon'] = "";  // Name of the icon in the play store
       $payload['click_action'] = "mainactivity";  // For android click activity
       $data['id'] = 10;  // For custom value if any
       $payload['to'] = "";   // Receiver FCM id
       $data['role'] = "SE";   // For custom value if any
       $this->fcm->send( $payload['to'], $payload, $data);
     }
       
    }
    
    
    
    
    function getDealerExecutive_get(){
    
             $result = $this->model_all->getDealerExecutive(2664,3);
             print_r($result);
    
    }
    
    
    
     function list_get(){
        $branch = $this->get("branch");
        $user_role = $this->get("role");
        $id = $this->get("id");
      //  echo "select * from notifications where branch='$branch' and user_role='$user_role' and user_id='$id' order by notifiy_on";
        $result_set = $this->model_all->getTableDataFromQuery("select * from notifications where branch='$branch' and user_role='$user_role' and user_id='$id' order by notifiy_on desc");
       
        if ($result_set->num_rows() > 0) {
            $result["status"] = 1;
            $result["message"] = "Records Found";
            
            foreach($result_set->result_array() as $row){
            
              if($row["notifiy_on"]!=""){
                  $row["notifiy_date"] = date("Y-m-d",strtotime($row["notifiy_on"]));
                 $row["notifiy_on"] = date("d M,Y h:i A",strtotime($row["notifiy_on"]));
                
              }
              $result["notifications"][] = $row;
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
    
    
    

    



    
   

}
