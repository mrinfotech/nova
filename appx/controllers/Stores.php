<?php

require(APPPATH . '/libraries/REST_Controller.php');

class Stores extends REST_Controller {

    public function __construct() {
        parent::__construct();
        $this->load->model('model_all');
    }

    //API - Fetch All Pincodes
    function list_get() {
	
        $result_set = $this->model_all->getTableDataInArray("stores", array("status"=>'1',"aprv_status"=>1), "id,name,mobile,address,latitude,longitude,status as store_status");
        if (($result_set['total_rows']) > 0) {
            $result["status"] = 1;
            $result["message"] = "Records Found";
            $result["records"] = $result_set['records'];
            $this->response($result, 200);
            exit;
        } else {
            $result["status"] = 0;
            $result["message"] = "No records Found";
            $this->response($result, 200);
            exit;
        }
    }

    //API - Save Pin Code
    function add_post() {

        $flag1 = true;
        $flag2 = true;

        $name = $this->post('name');
        $pincode = $this->post('pincode');
        $address = $this->post('address');
        $mobile = $this->post('mobile');
        $address = $this->post('address');
        $latitude = $this->post('latitude');
        $longitude = $this->post('longitude');
        $locale = $this->post('locale');
        $gst = $this->post('gst');
        $aadhar = $this->post('aadhar');
        $user = $this->post('user'); 
        $dt = date("Y-m-d");

        $insert_id = $this->model_all->save(array("name" => $name, "mobile" => $mobile, "address" => $address, "pincode" => $pincode,"createdby" => $user, "modifiedby" => $user,"latitude"=>$latitude,"longitude"=>$longitude,"gst"=>$gst, "aadhar"=>$aadhar, "aprv_status"=>'0', "reason"=>"","status"=>'0'), "stores");
        if ($insert_id > 0)
             $flag1 = true;
             $addrss_data = array('latitude' => $latitude, 'longitude' => $longitude, 'address' => $address, 'locale' => $locale, 'user_id' => $insert_id, 'user_role' => 'store', 'status' => '1');
             $this->model_all->save($addrss_data, "addresses");

             $this->model_all->save(array("dp" => 'noimage.png', "username" => $mobile,"password" => md5($this->model_all->randomPassword()),"role" => 'store', "pkid" => $insert_id, "status" => '1',"createdon"=>$dt,"modifiedon"=>$dt,"createdby"=>$user,"modifiedby"=>$user), "app_users");
        $result["file_status"]=$_FILES;
        if ($_FILES['storeImg']['size'] > 0 && $_FILES['storeImg']['error'] == 0) {

            $name = time() . "_" . $_FILES['storeImg']['name'];
            if (move_uploaded_file($_FILES['storeImg']['tmp_name'], "stores/" . $name)) {
                $img_status = $this->model_all->update(array("address_proof" => $name), array("id" => $insert_id), "stores");

                if ($img_status) {
                    $flag2 = true;
                }
            } else {
                $flag2 = false;
            }
        }
        if ($flag1 === true && $flag2 === true) {
            $status = 1;
            $message = "Store Saved Successfully";
        } else if ($flag1 === true && $flag2 === false) {
            $status = 1;
            $message = "Store Saved Successfully. Attachment not uploaded.";
        } else if ($flag1 === false && $flag2 === false) {
            $status = 0;
            $message = "Saving Store unsuccessful.";
        }


        $result["status"] = $status;
        $result["message"] = $message;

        $this->response($result, 200);

        exit;
    }

    function update_post() {

        $flag1 = true;
        $flag2 = true;

        $name = $this->post('name');
        $address = $this->post('address');
        $mobile = $this->post('mobile');
        $pincode = $this->post('pincode');
        $address_proof = $this->post('pincode');
        $latitude = $this->post('latitude');
        $longitude = $this->post('longitude');
        $gst = $this->post('gst');
        $aadhar = $this->post('aadhar');
        $user = $this->post('user');
        $storeId = $this->post('storeId');
        $upd_status = $this->model_all->update(array("name" => $name, "mobile" => $mobile, "address" => $address, "pincode" => $pincode, "modifiedby" => $user,"latitude"=>$latitude,"longitude"=>$longitude,"gst"=>$gst,"aadhar"=>$aadhar), array("id" => $storeId), "stores");
        if ($upd_status) {
            $message = "Store Details updated sucessfully";
            $status = 1;
        }
        if ($_FILES['storeImg']['size'] > 0 && $_FILES['storeImg']['error'] == 0) {
            $name = time() . "_" . $_FILES['storeImg']['name'];
            if (move_uploaded_file($_FILES['storeImg']['tmp_name'], base_url() . "stores/" . $name)) {
                $this->model_all->update(array("address_proof" => $name), array("id" => $storeId), "stores");
            }
        }
        $result["status"] = $status;
        $result["message"] = $message;
        $this->response($result, 200);

        exit;
    }

    function changestatus_put() {

        $store_status = $this->put('store_status');
        $primaryid = $this->put('primaryid');
        $reason = $this->put('reason');
        $id = $this->put('id');
        $dt = date("Y-m-d H:i:s");
        $user = $this->put('user');
        $upd_status = $this->model_all->update(array("status" => $store_status, "modifiedby" => $primaryid), array("id" => $id), "sellers");
        if ($upd_status) {
            $this->model_all->save(array("store_id"=>$id,"status"=>$store_status,"reason"=>$reason,"changed_on"=>$dt,"chenged_by"=>$primaryid),"track_store");
            $message = "Store status changed sucessfully";
            $status = 1;
        } else {
            $message = "Store status changing Unsucessfully";
            $status = 0;
        }

        $result["status"] = $status;
        $result["message"] = $message;

        $this->response($result, 200);

        exit;
    }


    
    function new_get() {

        $result_set = $this->model_all->getTableDataInArray("stores ", array("aprv_status"=>'0',"reason"=>""), "id,name,address,status as store_status");
       
        if (($result_set['total_rows']) > 0) {
            $result["status"] = 1;
            $result["message"] = "Records Found";
            $result["records"] = $result_set['records'];
            $this->response($result, 200);
            exit;
        } else {
            $result["status"] = 0;
            $result["message"] = "No records Found";
            $this->response($result, 200);
            exit;
        }
    }

    function approve_put() {

        $store_status = $this->put('approve_status');
        if($store_status==1) $stringg = "Approved ";
        else if($store_status==0) $stringg = "Rejected ";
        $reason = $this->put('reason');
        $store = $this->put('store');
        $upd_status = $this->model_all->update(array("status" =>'1',"aprv_status"=>$store_status, "reason" => $reason), array("id" => $store), "stores");
        if ($upd_status) {
            $message = $stringg." sucessfully";
            $status = 1;
        } else {
            $message = "Something went wrong. Please try later.";
            $status = 0;
        }

        $result["status"] = $status;
        $result["message"] = $message;
        $this->response($result, 200);
   
        exit;
    }


    function do_action_post() {
        $action = $this->post('action');
        $order = $this->post('primary_key');
        $item = $this->post('item');
        $qty = $this->post('qty');
        $reason = $this->post('reason');
        $description = $this->post('description');
        $dt = date("Y-m-d H:i:s");
        $action_status = 0;
        $message = "Action not performed. Please try later";

        if ($action == 1) {
            $affected_rows = $this->model_all->update(array("picked_qty" => $qty, "action_status" => '1', "action_time" => $dt, "reason" => $reason, "description" => $description), array("id" => $item), "order_items");
            if ($affected_rows) {
                $message = "Item Received successfully";
                $action_status = 1;
            }
        } else if ($action == 2) {
            $img_name = "";
            if ($_FILES['rej_img']['size'] > 0 && $_FILES['rej_img']['error'] == 0) {
                $name = "store_".$item."_" . time() . "_" . $_FILES['rej_img']['name'];
                $source_url = $_FILES['rej_img']['tmp_name'];
                $destination_url = "rejections/" . $name;
                if (@move_uploaded_file($source_url, $destination_url)) {
                    $img_name = $name;
                } else {
                    $img_name = "";
                }
            }
            $affected_rows = $this->model_all->update(array("picked_qty" => $qty, "action_status" => '2', "action_time" => $dt, "reason" => $reason, "description" => $description, "action_img" => $img_name), array("id" => $item), "seller_items");
            if ($affected_rows) {
                $this->model_all->update(array("delivery_reject"=>'1'),array("id"=>$order),"orders");
                $message = "Item rejected successfully";
                $action_status = 1;
            }
        }

        $result_set1 = $this->model_all->getTableDataFromQuery("SELECT id FROM order_items WHERE orderid ='$order'");
        $result['total_records'] = $result_set1->num_rows();
        $result_set2 = $this->model_all->getTableDataFromQuery("SELECT id FROM order_items where orderid ='$order' and action_status!='0'");
        $result['total_processed'] = $result_set2->num_rows();
        if($result['total_records']==$result['total_processed']){
            $this->model_all->update(array("delivery_accept"=>'1',"delivery_recieved"=>'1'),array("id"=>$order),"orders");
        }


        $result["status"] = $action_status;
        $result["message"] = $message;
        $this->response($result, 200);
        exit;
    }

}
