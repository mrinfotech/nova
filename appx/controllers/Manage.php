<?php

defined('BASEPATH') OR exit('No direct script access allowed');

class Manage extends CI_Controller {

    public function __construct() {
        parent::__construct();
        // $this->load->model('model_all');
        $this->load->model('model_login');
        $this->load->model('model_all');
        $this->load->library('oleread');
       // echo "User" . $this->session->userdata('user');
        if (empty($this->session->userdata('user'))) {
            // $this->login();
        }
    }

    //API - Fetch All Pincodes
    //API - Save Pin Code
    function import_post() {

        $flag1 = true;
        $flag2 = true;

        $fields = $this->csvimport->get_array($_FILES['csv_file']['tmp_name']);
        $result["records"] = $fields;
        $result["status"] = "1";
        $result["message"] = "Records saved successfully";

        $this->response($result, 200);

        exit;
    }

    function view_get() {
        echo "Testing";
        $this->load->view('importfile');
    }

    function index() {
        if (empty($this->session->userdata('user'))) {
            $this->login();
        } else {
            $this->dashboard();
        }
    }

    function login() {
         if(empty($this->session->userdata('user'))) {
            $this->load->view("adminindex");
         }else{
            $this->emp_upload(); 
         }
    }

    function loginprocess() {
        $username = $this->input->post('username');
        $password = $this->input->post('password');
        $password = md5($password);
        if ($username != "" && $password != "") {
            $query = $this->model_login->validate_admin($username, $password);

            if ($query->num_rows() > 0) {
                $rs = $query->row();
                $uid = $this->session->set_userdata('user', $rs->id);
                // $this->dashboard();
                $this->emp_upload();
            } else {
                $data["message"] = "Invalid Credentials";
            }
        } else {
            $data["message"] = "Invalid Credentials";
            $this->load->view('adminindex', $data);
        }
    }

    public function dashboard() {

        $this->load->template('dashboard', "dashboard_footer_links");
    }

    public function changenewpassword() {

        $up = $this->input->post('signInn');

        if (isset($up)) {
            $old = $this->input->post('oldpassword');
            $new = $this->input->post('newpassword');
            $re = $this->input->post('repassword');
            $this->db->where(array('password' => $old, 'id' => $this->session->userdata('user_id')));
            $c = $this->db->get('admin_login')->num_rows();
            if ($c > 0) {
                if ($new == $re) {
                    $data = array(
                        'password' => $new
                    );
                    if ($this->session->userdata('role') == 'admin') {
                        $tab = "admin_login";
                        $wcol = array('id' => $this->session->userdata('user_id'));
                        $this->model_all->update($data, $wcol, $tab);
                    } else {
                        $tab = "employees";
                        $wcol = array('emp_id' => $this->session->userdata('user_id'));
                        $this->model_all->update($data, $wcol, $tab);
                    }
                    $this->session->set_flashdata('msg', 'Your Password Changed Successfully');
                } else {
                    $this->session->set_flashdata('msg', 'New and Re-enter Passwords Should be Same');
                }
            } else {
                $this->session->set_flashdata('msg', 'Please Enter Valid Old Password');
            }
        } else {
            $this->session->set_flashdata('msg', 'Something went Wrong');
        }
        redirect('home/changepassword');
    }

    public function logout() {

        $this->session->unset_userdata('user');
        $this->session->sess_destroy();

        $this->login();
    }

    /* public function dealers_upload(){
      if(empty($this->session->userdata('user'))){
      $this->login();
      }else{
      $data = array();
      $data["companies"] = $this->model_all->getTableDataFromQuery("select * from companies");
      $this->load->template("dealer_upload","footer_links",$data);
      }

      } */

    public function emp_upload() {
        if (empty($this->session->userdata('user'))) {
            $this->login();
        } else {
            //echo "called";
            $this->load->template("emp_upload", "footer_links");
        }
    }

    public function emp_bulk_upload() {

        if ($_FILES["file"]["size"] > 0) {


            $data = new Spreadsheet_Excel_Reader($_FILES['file']['tmp_name']);

            $msg = $data->v1_emp_dump(true, true);
            $this->load->template("emp_upload", "footer_links", array("err_msg" => $msg));
        }
    }

    public function dealer_upload() {

        //echo "called";
        if (empty($this->session->userdata('user'))) {
            $this->login();
        } else {
            $data = array();
            $data["companies"] = $this->model_all->getTableData("companies", array(), "company_id,company");
            $this->load->template("dealer_upload", "footer_links", $data);
        }
    }

    public function dealer_bulk_upload() {

        if (empty($this->session->userdata('user'))) {
            $this->login();
        } else {
            $company = $this->input->post("company");
            $branch = $this->input->post("branch");

            if ($_FILES["file"]["size"] > 0) {
                $data = new Spreadsheet_Excel_Reader($_FILES['file']['tmp_name']);
                $msg = $data->v1_dealer_dump($company, $branch, true, true);
                $data = array();

                $data["companies"] = $this->model_all->getTableData("companies", array(), "company_id,company");
                $data["err_msg"] = $msg;
                $this->load->template("dealer_upload", "footer_links", $data);
            }
        }
    }

    public function items_upload() {
        //echo "called";
        if (empty($this->session->userdata('user'))) {
            $this->login();
        } else {
            $data = array();
            $data["companies"] = $this->model_all->getTableData("companies", array(), "company_id,company");
            $this->load->template("items_upload", "footer_links", $data);
        }
    }

    public function items_bulk_upload() {

        $company = $this->input->post("company");

        if ($_FILES["file"]["size"] > 0) {
            $data = new Spreadsheet_Excel_Reader($_FILES['file']['tmp_name']);
            $msg = $data->v1_items_upload($company, true, true);
            $data = array();
            $data["companies"] = $this->model_all->getTableData("companies", array(), "company_id,company");
            $data["err_msg"] = $msg;
            $this->load->template("items_upload", "footer_links", $data);
        }
    }

}
