<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * @name: Model All
 * @author: 
 */
class csv_model extends CI_Model
{
    function __construct()
    {
        parent::__construct();
        $this->load->database();
    }
    function uploadData()
    {
        $count=0;
        $fp = fopen($_FILES['userfile']['tmp_name'],'r') or die("can't open file");
        while($csv_line = fgetcsv($fp,1024))
        {
            $count++;
            if($count == 1)
            {
                continue;
            }//keep this if condition if you want to remove the first row
            for($i = 0, $j = count($csv_line); $i < $j; $i++)
            {
                $insert_csv = array();
                //$insert_csv['id'] = $csv_line[0];//remove if you want to have primary key,
                $insert_csv['instid'] = $csv_line[0];
                $insert_csv['date'] = $csv_line[1];
                $insert_csv['holidaydesc'] = $csv_line[2];
                $insert_csv['status'] = $csv_line[3];
                $insert_csv['createdby'] = $csv_line[4];
                $insert_csv['createdon'] = $csv_line[5];
                $insert_csv['modifiedby'] = $csv_line[6];
                $insert_csv['modifiedon'] = $csv_line[7];

            }
            $i++;
            $data = array(
                //'id' => $insert_csv['id'] ,
                'instid' => $insert_csv['instid'],
                'date' => $insert_csv['date'],
                'holidaydesc' => $insert_csv['holidaydesc'],
                'status' => $insert_csv['status'],
                'createdby' => $insert_csv['createdby'],
                'createdon' => $insert_csv['createdon'],
                'modifiedby' => $insert_csv['modifiedby'],
                'modifiedon' => $insert_csv['modifiedon']);
            $data['crane_features'] = $this->db->insert('instholidayslist', $data);
        }
        fclose($fp) or die("can't close file");
        $data['success']="success";
        return $data;
    }
}