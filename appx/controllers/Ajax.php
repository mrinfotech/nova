<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Ajax extends CI_Controller {
    /*  get destinations  */

    public function __construct() {
        parent::__construct();
        $this->load->library('form_validation');
        $this->load->model("model_all");
        $this->load->database();
    }

    function reply() {
        echo $h = "hai";
    }

    /*  Delete Any table data using Ajax   */

    function delTableDataAjax() {
        $id = $this->input->post('eid');
        $where_col = $this->input->post('wcol');
        $table = $this->input->post('table');
        $condtions = array($where_col => $id);
        $return_status = $this->model_all->deleteRow($table, $condtions);
        if ($return_status > 0) {
            echo json_encode(array("status" => true));
            $this->session->set_flashdata('msg', 'Records are Deleted');
        } else {
            echo json_encode(array("status" => false));
            $this->session->set_flashdata('msg', 'Something Went Wrong');
        }
    }

    /*  change Status Any table data using Ajax   */

    function updateData() {
        $where_data = $this->input->post('where_data');
        $table = $this->input->post('table');
        $data = $this->input->post('data');

        if ($where_data != "") {
            $where_data = json_decode($where_data, true);
            if (!is_array($where_data)) {
                $where_data = array();
            }
        }

        if ($data != "") {
            $data = json_decode($data, true);
            if (!is_array($data)) {
                $data = array();
            }
        }

        $updated_status = $this->model_all->update($data, $where_data, $table);
        if ($updated_status) {
            echo json_encode(array("status" => true));
            $this->session->set_flashdata('msg', 'Data Saved.');
        } else {
            echo json_encode(array("status" => false));
            $this->session->set_flashdata('msg', 'Something went wrong');
        }
    }

  

    /* To get table data along with columns */

    function getTableDataWithColumns($get_table_name = "", $get_condition = "", $get_columns = "*") {

        if ($get_table_name != "") {
            $table_name = $get_table_name;
            $condition = $get_condition;

            $columns = $get_columns;
        } else {
            $table_name = $this->input->post('table');
            if ($table_name != "") {
                $condition = $this->input->post('condition');
                $columns = $this->input->post('columns');
            }
        }
        if (!is_array($condition) && $condition != "") {

            $condition = json_decode($condition, true);
            if (!is_array($condition)) {
                $condition = array();
            }
        }
        if ($columns == "") {
            $columns = "*";
        }

        if ($get_table_name != "" || $table_name != "") {
            $result = $this->model_all->getTableDataInArray($table_name, $condition, $columns);
            return $result;
        }
    }

    function getTableData($get_table_name = "", $get_condition = array(), $get_columns = "*") {

        if ($get_table_name != "") {
            $table_name = $get_table_name;
            $condition = $get_condition;

            $columns = $get_columns;
        } else {
            $table_name = $this->input->post('table');
            if ($table_name != "") {
                $condition = $this->input->post('condition');
                $columns = $this->input->post('columns');
            }
        }
        if ($condition != "") {
            $condition = json_decode($condition, true);
            if (!is_array($condition)) {
                $condition = array();
            }
        }
        if ($columns == "") {
            $columns = "*";
        }
        if ($get_table_name != "" || $table_name != "") {
            $result = $this->model_all->getTableData($table_name, $condition, $columns);
            return $result;
        }
    }

    function recordStatusAjax() {
        $id = $this->input->post('eid');
        $wcol = $this->input->post('wcol');
        $status = $this->input->post('status');
        $table = $this->input->post('table');
        $this->db->query("update " . $table . " set status='" . $status . "' where " . $wcol . "='" . $id . "'");
        $this->session->set_flashdata('msg', 'Status Updated');
    }

    /* To get states data */

   

    function getOptionsselect() {
        $c_id = $this->input->post('c_val');
        $result['record1'] = query("select e.employeeid,d.designation from empinfo e,designations d where e.id='$c_id' and e.desgid=d.id")->row_array();
        echo json_encode($result);
    }

  

   

 

    function delTableDataAjaxGet($id, $where_col, $table, $type = "status", $url = "") {
        $today = date('Y-m-d H:i:s');
        $data = array("status" => "0", "modifiedby" => $this->session->userdata('pra_userid'), "modifiedon" => $today);
        $condition = array($where_col => $id);
        $flag = false;
        if ($type == "status") {
            $action_status = $this->model_all->updateStatus($data, $condition, $table);
            if ($action_status) {
                $flag = true;
            }
        } else {
            if ($url != "" && file_exists($url)) {
                unlink($url);
            }
            $affective_rows = $this->model_all->deleteRow($table, $condition);
            if ($affective_rows > 0) {
                $flag = true;
            }
        }

        if ($flag) {
            return json_encode(array("status" => true));
        } else {
            return json_encode(array("status" => false));
        }
    }

    function changesInstituteStatas() {
        $id = $this->input->post("id");
        echo $this->delTableDataAjaxGet($id, "id", "directors");
    }

  

    function getOptions() {
        $where_data = $this->input->post('where_data');
        $idd = $this->input->post('idd');
        $namee = $this->input->post('namee');
        $table = $this->input->post('table');
        $join = $this->input->post('join');
        $fields = $idd . ',' . $namee;
        $result = $this->getTableDataWithColumns($table, $where_data, $fields);
        //echo $this->db->last_query();
        echo json_encode($result);
    }

    

    //2 Tables Join
    function getDetFrm2Tbls() {

        $target = $this->input->post("target");
        $table1 = $this->input->post("tbl1");
        $table2 = $this->input->post("tbl2");
        $is_distinct = $this->input->post("is_distinct");
        $colmn = $this->input->post("coln");
        $condt = (array) json_decode($this->input->post("cond"));
        $str = "";




        $k = 1;
        foreach ($condt as $key => $value) {
            $str .= $table1 . "." . $key . "=" . $value;
            if (count($condt) != $k) {
                $str .= " and ";
            }
            $k++;
        }



        if ($is_distinct == "yes")
            $this->db->distinct();

        $this->db->select($colmn);
        $this->db->from($table1);
        $this->db->join($table2, $str);
        $table_result = $this->db->get();
        // $table_result
        


        $result = array();
        $result['query'] = $this->db->last_query();

        $result['total_rows'] = $table_result->num_rows();
        $result['target'] = $target;
        $result['records'] = array();
        foreach ($table_result->result_array() as $row) {
            $result['records'][] = $row;
        }
        echo json_encode($result);
    }

    //3 Tables Join

    function getDetFrm3Tbls() {

        $target = $this->input->post("target");
        $table1 = $this->input->post("table1");
        $table2 = $this->input->post("table2");
        $table3 = $this->input->post("table3");
        $is_distinct = $this->input->post("is_distinct");
        $colmn = $this->input->post("coln");
        $condt1 = (array) json_decode($this->input->post("cond1"));
        $condt2 = (array) json_decode($this->input->post("cond2"));

        $str = "";
        $tb1str = "";
        $tb2str = "";
        $k = 1;
        foreach ($condt1 as $key => $value) {
            $tb1str .= $table1 . "." . $key . "=" . $value;
            if (count($condt1) != $k) {
                $tb1str .= " and ";
            }
            $k++;
        }
        $k = 1;
        foreach ($condt2 as $keyy => $valuee) {

            $tb2str .= $table2 . "." . $keyy . "=" . $valuee;
            if (count($condt2) != $k) {
                $tb2str .= " and ";
            }
            $k++;
        }

        if ($is_distinct == "yes")
            $this->db->distinct();

        $this->db->select($colmn);
        $this->db->from($table1);
        $this->db->join($table2, $tb1str);
        $this->db->join($table3, $tb2str);
        $table_result = $this->db->get();
        // echo $this->db->last_query();

        $result = array();
        $result['total_rows'] = $table_result->num_rows();
        $result['records'] = array();
        $result['target'] = $target;
        foreach ($table_result->result_array() as $row) {
            $result['records'][] = $row;
        }
        echo json_encode($result);
    }

    function getDetFrm4Tbls() {

        $table1 = $this->input->post("table1");
        $table2 = $this->input->post("table2");
        $table3 = $this->input->post("table3");
        $table3 = $this->input->post("table4");
        $is_distinct = $this->input->post("is_distinct");
        $colmn = $this->input->post("coln");
        $condt1 = (array) json_decode($this->input->post("cond1"));
        $condt2 = (array) json_decode($this->input->post("cond2"));
        $condt2 = (array) json_decode($this->input->post("cond3"));


        $tb1str = "";
        $tb2str = "";
        $tb3str = "";
        $k = 1;
        foreach ($condt1 as $key => $value) {
            $tb1str .= $table1 . "." . $key . "=" . $value;
            if (count($condt1) != $k) {
                $tb1str .= " and ";
            }
            $k++;
        }
        $k = 1;
        foreach ($condt2 as $key => $value) {

            $tb2str .= $table2 . "." . $key . "=" . $value;
            if (count($condt2) != $k) {
                $tb2str .= " and ";
            }
            $k++;
        }
        $k = 1;
        foreach ($condt3 as $key => $value) {
            $tb3str .= $table3 . "." . $key . "=" . $value;
            if (count($condt1) != $k) {
                $tb1str .= " and ";
            }
            $k++;
        }

        if ($is_distinct == "yes")
            $this->db->distinct();

        $this->db->select($colmn);
        $this->db->from($table1);
        $this->db->join($table2, $tb1str);
        $this->db->join($table3, $tb2str);
        $this->db->join($table4, $tb3str);
        $table_result = $this->db->get();
        // echo $this->db->last_query();

        $result = array();
        $result['total_rows'] = $table_result->num_rows();
        $result['records'] = array();
        foreach ($table_result->result_array() as $row) {
            $result['records'][] = $row;
        }
        echo json_encode($result);
    }

    

    

  

    function changeStatus() {
        $id = $this->input->post("id");
        $pk = $this->input->post("pk");
        $table = $this->input->post("table");
        $type = $this->input->post("type");
        $url = $this->input->post("url");
        echo $this->delTableDataAjaxGet($id, $pk, $table, $type, $url);
        // echo $this->db->last_query();
    }



   

   

}


