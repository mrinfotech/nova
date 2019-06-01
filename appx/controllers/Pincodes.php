<?php

require(APPPATH . '/libraries/REST_Controller.php');

class Pincodes extends REST_Controller {

    public function __construct() {
        parent::__construct();
        $this->load->model('model_all');
    }

    //API - Fetch All Pincodes
    function list_get() {

        $result_set = $this->model_all->getTableDataInArray("pincodes", array(), "id,pincode");
        if (($result_set['total_rows']) > 0) {
            $result["status"] = 1;
            $result["message"] = "Valid User";
            $result["pincodes"] = $result_set['records'];
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
    function add_post() {

        $pincode = $this->post('pincode');
        $primaryid = $this->post('primaryid');
        $result_set = $this->model_all->getTableData("pincodes", array("pincode" => $pincode));
        if ($result_set->num_rows() == 0) {
            $insert_id = $this->model_all->save(array("pincode" => $pincode, "createdby" => $primaryid, "modifiedby" => $primaryid), "pincodes");
            if ($insert_id > 0) {
                $result["status"] = 1;
                $result["message"] = "Submitted Successfully";
            } else {
                $result["status"] = 0;
                $result["message"] = "Pincode Submission Unsuccessful";
            }
            $this->response($result, 200);
        } else {
            $result["status"] = 0;
            $result["message"] = "Pincode already Exists";
            $this->response($result, 200);
        }
        exit;
    }

    function update_put() {

        $pincode = $this->put('pincode');
        $primaryid = $this->put('primaryid');
        $pincodeid = $this->put('pincodeid');
        $result_set = $this->model_all->getTableData("pincodes", array("pincode" => $pincode,"id!="=>$pincodeid));
        if ($result_set->num_rows() == 0) {
            $insert_status = $this->model_all->update(array("pincode" => $pincode, "modifiedon" => date("Y-m-d H:i:s"), "modifiedby" => $primaryid),array("id"=>$pincodeid), "pincodes");
            if ($insert_status) {
                $result["status"] = 1;
                $result["message"] = "Pincode Updated Successfully";
            } else {
                $result["status"] = 0;
                $result["message"] = "Updation Unsuccessful.";
            }
            $this->response($result, 200);
        } else {
            $result["status"] = 0;
            $result["message"] = "Pincode with this value already Exists";
            $this->response($result, 200);
        }
        exit;
    }

    //API - create a new book item in database.
    function addBook_post() {

        $name = $this->post('name');

        $price = $this->post('price');

        $author = $this->post('author');

        $category = $this->post('category');

        $language = $this->post('language');

        $isbn = $this->post('isbn');

        $pub_date = $this->post('publish_date');

        if (!$name || !$price || !$author || !$price || !$isbn || !$category) {

            $this->response("Enter complete book information to save", 400);
        } else {

            $result = $this->book_model->add(array("name" => $name, "price" => $price, "author" => $author, "category" => $category, "language" => $language, "isbn" => $isbn, "publish_date" => $pub_date));

            if ($result === 0) {

                $this->response("Book information coild not be saved. Try again.", 404);
            } else {

                $this->response("success", 200);
            }
        }
    }

}
