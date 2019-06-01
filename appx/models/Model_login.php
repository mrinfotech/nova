<?php

defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * @name: Login model
 * @author: Imron Rosdiana
 */
class Model_login extends CI_Model {

    function __construct() {
        parent::__construct();
        $this->load->database();
    }

    public function validate_admin($username,$password) {
        $this->db->where('username', $username);
        $this->db->where('password', $password);
        $this->db->from("supaer_admin");
        $query= $this->db->get();
        return $query;
    }

    public function validate_emp($data) {
        $this->db->where('email', $data['username']);
        $this->db->where('password', $data['password']);
        return $this->db->get('employees')->row();
    }

    function __destruct() {
        $this->db->close();
    }

}
