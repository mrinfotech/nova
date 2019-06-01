<?php

defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * @name: Model All
 * @author: 
 */
class Model_all extends CI_Model {

    function __construct() {
        parent::__construct();
        $this->load->database();
    }

    /*
     * Insert the row in a table 
     * $data represents array of inserted column name and values
     * $table represents table name
     * $col  for checking whether the data is existing or not
     */

    public function tabInsert($data, $col, $table, $req_id) {

        $this->db->select('*');
        $this->db->from($table);
        $this->db->where($col);
        $query = $this->db->get();
        if ($query->num_rows() == 0) {
            $this->db->insert($table, $data);
            $action_status = $this->db->insert_id();
        } else {
            if (!$req_id) {
                $action_status = 0;
            } else {
                $row = $query->row();
                if ($this->db->field_exists('status', $table)) {

                    $this->db->where('id', $row->id);
                    $this->db->update($table, array("status" => "1"));
                }
                $action_status = $row->id;
            }
        }


        return $action_status;
    }

    /*
     * Update table content by checking whether the data is existing or not
     * $data represents array of modifiled column name and values
     * $tab represents table name
     * $id  represents comparision  column value
     * $wcol represents comparision column name
     * $col  for checking whether the data is existing or not
     *  */

    public function tabUpdate($id, $data, $wcol, $col, $table) {

        $this->db->select('*');
        $this->db->from($table);
        $this->db->where($col);
        $query = $this->db->get();
        if ($query->num_rows() == 0) {
            $this->db->where($wcol, $id);
            $this->db->update($table, $data);
            if ($query->affected_rows() == 0) {
                $status = true;
            }
        } else {
            $status = false;
        }
        return $msg;
    }

    /*
     * Insert a row in a column
     * $data represents array of inerted column name and values
     * $table represents table name
     * 
     *  */

    public function save($data, $table) {
        $r = $this->db->insert($table, $data);
        $msg = $this->db->insert_id();
        return $msg;
    }

    /*
     * To update records in a table
     * $data represents array of modified column name and values
     * $table represents table name
     * $wcol represents array of comparision column name and values
     *  */

    public function update($data, $wcol, $tab) {
        $this->db->where($wcol);
        $this->db->update($tab, $data);
        // echo $this->db->last_query(); exit;
        if ($this->db->affected_rows() > 0) {
            $msg = true;
        } else {
            $msg = false;
        }
        return $msg;
    }

    /*
     * To update records in a table
     * $columns represents required comma seperated string of required columns
     * $table represents table name
     * $condtions represents array of comparision column name and values
     *  */

       public function getTableData($table, $condtions = array(), $columns = '*', $order_column = "", $order_type = "") {

        $this->db->select($columns);
        $this->db->from($table);
        if (count($condtions) > 0)
            $this->db->where($condtions);

        if ($order_column != "") {
            $this->db->order_by($order_column, $order_type);
        }

        $query = $this->db->get();
        
        return $query;
    }
    
      public function getTableDataFromQuery($query) {

        $result = $this->db->query($query);
        return $result;
     }
    
    
    public function getTableData2($table, $condtions = array(), $columns = '*', $order_column = "", $order_type = "") {

        $this->db->select($columns);
        $this->db->from($table);
        if (count($condtions) > 0)
            $this->db->where($condtions);

        if ($order_column != "") {
            $this->db->order_by($order_column, $order_type);
        }

        $query = $this->db->get();
//        echo $this->db->last_query();
        return $query;
    }

    public function getJoinedTableData($table1, $table2, $table1_column, $table12_column, $table_columns = "", $conditions = array(), $join_type = "left") {

        /* $this->db->select($columns);
          $this->db->from($table);
          if (count($condtions) > 0)
          $this->db->where($condtions);
          $query = $this->db->get();
          return $query;

          if (count($table1_columns) > 0) {

          }

          if (count($table2_columns) > 0) {

          }
         */

        if ($table_columns == "") {
            $table_columns = $table1 . ".*," . $table2 . ".*";
        }
        $this->db->select($table_columns);
        $this->db->from($table1);
        $this->db->join($table2, $table1 . "." . $table1_column . ' = ' . $table2 . "." . $table12_column);
        if (count($conditions) > 0) {
            $this->db->where($conditions);
        }
        $query = $this->db->get();
      //  echo $this->db->last_query();

        return $query;
    }

    /*
     * To deleteRow records in a table
     * $columns represents required comma seperated string of required columns
     * $table represents table name
     * $condtions represents array of comparision column name and values
     * $image_column represents image columns to unlink them
     * $directoryPath represents the image path
     *  */

    public function deleteRow($table, $condtions = array(), $image_column = '', $directoryPath = '') {
        if ($image_column != '') {
            $this->db->select($image_column);
            if (count($condtions) > 0)
                $this->db->where($condtions);
            $this->db->from($table);
            $query = $this->db->get();
            $result = $query->row();
            delImage($directoryPath . $result->image);
        }

        if (count($condtions) > 0)
            $this->db->where($condtions);
        $this->db->delete($table);

        return $this->db->affected_rows();
    }

    /* To get the whole table data in the form of array */
    /*
     * To deleteRow records in a table
     * $columns represents required comma seperated string of required columns
     * $table represents table name
     * $condtions represents array of comparision column name and values
     */

    public function getTableDataInArray($table, $condtions = array(), $columns = '*') {

        $result = array();
        $this->db->select($columns);
        $this->db->from($table);
        if (count($condtions) > 0)
            $this->db->where($condtions);
        $query = $this->db->get();
        //echo $this->db->last_query();
        if ($columns == "*" || $columns == "") {
            $fields = $this->db->list_fields($table);
        } else {

            $fields = explode(",", $columns);
        }

        $result['total_rows'] = 0;
        if ($query->num_rows() > 0) {
            $result['total_rows'] = $query->num_rows();
            foreach ($query->result() as $row) {
               $result["records"][] = $row;
            }
        }

        return $result;
    }

    function updateStatus($data, $where_col, $table) {
       
        $this->db->set('status', "CASE WHEN status = 1 THEN '0' ELSE '1' END", FALSE);
        $this->db->set($data);
        $this->db->where($where_col);
        $this->db->update($table);
        // echo $this->db->last_query();
        //   exit;
        if ($this->db->affected_rows() > 0) {
            $msg = true;
        } else {
            $msg = false;
        }
        return $msg;
    }

    function uploadCsv($data, $table) {
        $r = $this->db->insert($table, $data);
    }

    function table_empty_fields($table) {
        $fields = $this->db->list_fields($table);
        $result = array();
        for ($i = 0; $i < count($fields); $i++) {
            $result[$fields[$i]] = '';
        }
        return $result;
    }
    
    function rows_from_result_set($result_set){
        $total_records = array();
        $total_records['total_rows'] = $result_set->num_rows();
        $total_records['records'] = array();
        foreach ($result_set->result_array() as $row) {
            $total_records['records'][] = $row;
        }
        return $total_records;
    }
    
    
    
    function randomPassword() {
      $alphabet = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ1234567890';
      $pass = array(); //remember to declare $pass as an array
      $alphaLength = strlen($alphabet) - 1; //put the length -1 in cache
      for ($i = 0; $i < 8; $i++) {
        $n = rand(0, $alphaLength);
        $pass[] = $alphabet[$n];
      }
      return implode($pass); //turn the array into a string
    }



   function prefix_zeros($number) {
    $numbLength = strlen($number);
    switch ($numbLength) {
        case 1: $number = "0000" . $number;
            break;
        case 2: $number = "000" . $number;
            break;
        case 3: $number = "00" . $number;
            break;
        case 4: $number = "0" . $number;
            break;
        default : break;
    }
    return $number;
  }

    
    /*  
       SELECT i.*,p.mrp,p.pay,CONCAT(s.first_name,s.last_name) as suppier_name,s.id as sellerid,q.qty FROM `items` i ,pricing p ,quantity q ,sellers s where i.id=p.itemid and i.id=q.itemid and s.id=q.sellerid and s.id=q.sellerid
       
    */



     public function sendSMS($mobile, $msg) {

       $url = 'http://www.siegsms.com/postsms.aspx';

       $fields = array('userid' => urlencode('mitraya'),
         'pass' => urlencode('welcome@123'),
         'phone' => urlencode($mobile),
         'msg' => urlencode($msg),'title' => urlencode('MITRAY'));
        
       $fields_string="";

       foreach ($fields as $key => $value) {
          $fields_string .= $key . '=' . $value . '&';
       }
        rtrim($fields_string, ' &');

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_POST, count($fields));
        curl_setopt($ch, CURLOPT_POSTFIELDS, $fields_string);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);

        $curlData = curl_exec($ch);
        curl_close($ch);

    }



     public function sendSMS_get($mobile, $msg,$title="") {
       if($title==""){
          $title ="NOVAAT";
       }

       $curl = curl_init();
      // Set some options - we are passing in a useragent too here
       $message  = urlencode($msg);
       curl_setopt_array($curl, array(
       CURLOPT_RETURNTRANSFER => 1,
       CURLOPT_URL => 'https://www.smsstriker.com/API/sms.php?username=9573945222&password=Nova@789&from='.$title.'&to='.$mobile.'&msg='.$message.'&type=1',
       CURLOPT_USERAGENT => 'Codular Sample cURL Request'
       ));
        // Send the request & save response to $resp
        $resp = curl_exec($curl);
        // Close request to clear up some resources
        curl_close($curl);

    }

    public function getProcedureData($query) {
        echo $query;
        $result = $this->db->query($query);
        return $result;
     }
     
     public function getDealerExecutive($dealer,$branch){
     
          
           $result = array("dealer"=>array("company_name"=>"","first_name"=>"","last_name"=>"","fcm_key"=>"","dealer_code"=>""),"se"=>array("first_name"=>"","last_name"=>"","fcm_key"=>"","emp_code"=>""),"FM"=>array("first_name"=>"","last_name"=>"","fcm_key"=>"","emp_code"=>""),"admin"=>array("first_name"=>"","last_name"=>"","fcm_key"=>"","emp_code"=>""));
           $dealer_query = $this->db->query("select s.id,s.first_name,s.last_name,s.company_name,s.dealer_code,a.fcm_id from sellers s , app_users a where s.id='$dealer' and a.role='seller' and s.id=a.pkid");
           if( $dealer_query->num_rows()>0){
                $row = $dealer_query->row();
                $result["dealer"]["id"] = $row->id;
                $result["dealer"]["company_name"] = $row->company_name ;
                $result["dealer"]["first_name"] = $row->first_name ;
                $result["dealer"]["last_name"] = $row->last_name ;
                $result["dealer"]["dealer_code"] = $row->dealer_code ;
                $result["dealer"]["fcm_key"] = $row->fcm_id;
                
                $se_query = $this->db->query("select * from  branch_dealers where seller='$row->id' and branch='$branch'");
                if($se_query ->num_rows()>0){
                       $se_row = $se_query->row();
                       $emp_query = $this->db->query("select e.id,e.first_name,e.last_name,e.uniq_id,a.fcm_id from employees e,app_users a where e.id='$se_row->sales_manager' and a.role='trade' and a.pkid='$se_row->sales_manager' and e.id=a.pkid");
                       foreach($emp_query->result() as $emp_row){
                          $result["se"]["id"] = $emp_row->id;
                          $result["se"]["first_name"] = $emp_row->first_name ;
                          $result["se"]["last_name"] = $emp_row->last_name ;
                          $result["se"]["emp_code"] = $emp_row->uniq_id;
                          $result["se"]["fcm_key"] = $emp_row->fcm_id;
                       }
                   
                   
                }

               $emp_query = $this->db->query("select e.id,e.first_name,e.last_name,e.uniq_id,a.fcm_id from employees e,app_users a,emp_roles r where r.role_id='3' and r.branch_id='$branch' and e.id=r.employee_id and a.role='trade' and a.pkid=e.id");
               foreach($emp_query->result() as $emp_row){
                          $result["FM"]["id"] = $emp_row->id;
                          $result["FM"]["first_name"] = $emp_row->first_name ;
                          $result["FM"]["last_name"] = $emp_row->last_name ;
                          $result["FM"]["emp_code"] = $emp_row->uniq_id;
                          $result["FM"]["fcm_key"] = $emp_row->fcm_id;
               }
               
               $emp_query = $this->db->query("select e.id,e.first_name,e.last_name,e.uniq_id,a.fcm_id from employees e,app_users a,emp_roles r where r.role_id='2' and r.branch_id='$branch' and e.id=r.employee_id and a.role='trade' and a.pkid=e.id");
               foreach($emp_query->result() as $emp_row){
                          $result["admin"]["id"] = $emp_row->id;
                          $result["admin"]["first_name"] = $emp_row->first_name ;
                          $result["admin"]["last_name"] = $emp_row->last_name ;
                          $result["admin"]["emp_code"] = $emp_row->uniq_id;
                          $result["admin"]["fcm_key"] = $emp_row->fcm_id;
               }
                   
                   
                
           }
           
           
           
           return $result;
     
     
     
     }
     
     
     
     public function getBranchDM($branch){
          
          $emp_query = $this->db->query("select e.id,e.first_name,e.last_name,e.uniq_id,a.fcm_id from employees e,app_users a,emp_roles r where r.role_id='3' and r.branch_id='$branch' and e.id=r.employee_id and a.role='trade' and a.pkid=e.id");
               foreach($emp_query->result() as $emp_row){
                          $result["FM"]["id"] = $emp_row->id;
                          $result["FM"]["first_name"] = $emp_row->first_name ;
                          $result["FM"]["last_name"] = $emp_row->last_name ;
                          $result["FM"]["emp_code"] = $emp_row->uniq_id;
                          $result["FM"]["fcm_key"] = $emp_row->fcm_id;
               }
     
     }


     public function track_parent_order($order,$status){
          
              $track_qry = $this->db->query("select super_parent from order_transfer_track where order_id='$order'");
              $dt = date("Y-m-d H:i:s");
              if($track_qry->num_rows()==1){
                     $track_rs = $track_qry->row();
                     $order_qry = $this->db->query("select id,parent_order,is_transfered from seller_orders where id='$track_rs->super_parent'");
                     if($order_qry->num_rows()>0){
                       $order_rs = $order_qry->row();
                       if($order_rs->is_transfered==2){
                          
                           $this->db->query("INSERT INTO `seller_order_track` (`track_id`, `order_id`, `order_status`, `changed_on`, `comments`) VALUES (NULL, '$order_rs->id', '$status', '$dt', '$status')");
                       }
                     }

              }

      }
	  
	  
	  
	     
    public function tableFieldData($qry,$field){
        if($qry!=""){
            $result  = $this->db->query($qry);
            if($result->num_rows()>0){
                $rs = $result->row_array();
                if(isset($rs[$field])){
                   return $rs[$field];
                }else{
                   return ""; 
                }
            }else{
                return "";
            }
            
        }else{
                return "";
            }
    }
    
    
    
    public function execute_query($query){
        
        return $this->db->query($query);
     
    }

}

?>
