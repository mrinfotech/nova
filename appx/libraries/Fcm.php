<?php

defined('BASEPATH') OR exit('No direct script access allowed');


class Fcm {

    const FIREBASE_API_KEY = 'AAAAZJqK7qs:APA91bEropExKBvYielpO5wjqk91kynniMphRlLovZPFucAcSsRB6h9zpcRGf06YNEJKubUgav_N6TDawTJy6f1olnKs4K4EtYfS1WOM-1fZ-Fp7ZfWRZKWCf_EqXdn7sGIP5G8-4L17';

    public function __construct() {
       
    }

    //API - Fetch All Pincodes
    function store_token_post() {
        $id = $this->post('id');
        $role = $this->post('role');
        $token = $this->post('token');
       
        if($token!=""){
           $up_status = $this->model_all->update(array('fcm_id'=>$token),array('role'=>$role,'pkid'=>$id),"app_users");
          
           if ($up_status) {
            $result["status"] = 1;
            $result["message"] = "Stored Successfully";
            $this->response($result, 200);
            exit;
           } else {
            $result["status"] = 0;
            $result["message"] = "Some thing went wrong.";
            $this->response($result, 200);
            exit;
          }

        }else{
            $result["status"] = 0;
            $result["message"] = "Token Missing";
            $this->response($result, 200);
            exit;

        }
        
    }


    function sendPushNotification($fields){
        
        // Set POST variables
        $url = 'https://fcm.googleapis.com/fcm/send';  
       
        $headers = array(
            'Authorization: key=' . self::FIREBASE_API_KEY,
            'Content-Type: application/json'
        );
        // Open connection
        $ch = curl_init();
 
        // Set the url, number of POST vars, POST data
        curl_setopt($ch, CURLOPT_URL, $url);
 
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
 
        // Disabling SSL Certificate support temporarly
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
 
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($fields));
 
        // Execute post
        $result = curl_exec($ch);
        
       
        if ($result === FALSE) {
            die('Curl failed: ' . curl_error($ch));
        }
 
        // Close connection
        curl_close($ch);
 
        return $result;


    }


    public function send($to,$notification,$data) {
        $fields = array(
            'to' => $to,
            'data' => $data,
            'notification' => $notification
        );
       // echo json_encode($fields);
        return $this->sendPushNotification($fields);
    }
 
    // Sending message to a topic by topic name
    public function sendToTopic($to, $message) {
        $fields = array(
            'to' => '/topics/' . $to,
            'data' => $message,
        );
        return $this->sendPushNotification($fields);
    }
 
    // sending push message to multiple users by firebase registration ids
    public function sendMultiple($registration_ids, $message) {
        $fields = array(
            'to' => $registration_ids,
            'data' => $message,
        );
 
        return $this->sendPushNotification($fields);
    }

    public function echotest(){

        return "Helloworld";
    }

   

}