<?php

require(APPPATH . '/libraries/REST_Controller.php');

class Dealeritems extends REST_Controller {

    public function __construct() {
        parent::__construct();

        $this->load->model('model_all');
    }

    //API - Fetch All Items
    function index_get() {
        $id = $this->get("id");
        $result_set = $this->model_all->getTableDataInArray("items", array('productid' => $id), "id,itemname");

        if (($result_set['total_rows']) > 0) {
            $result["status"] = 1;
            $result["message"] = "Success";
            $result["items"] = $result_set['records'];
            $this->response($result, 200);
            exit;
        } else {
            $result["status"] = 0;
            $result["message"] = "No Items Found";
            $this->response($result, 200);
            exit;
        }
    }

    function list_get() {
        $id = $this->get("id");
        $item_id = $this->get("item_id");
        $user = $this->get("user");
        $barcode = $this->get("barcode");
        $branch = $this->get("branch");
        $category = $this->get("category");
        $cart_string = "";
        $company_string ="";
        $barcode_string = "";
        $id_string = "";
        $item_id_string="";

        if (empty($user))
            $user = 0;
        if (!empty($id))
            $id_string = " and i.productid='$id'";

        if (!empty($barcode))
            $barcode_string = " and bp.barcode='$barcode'";

        if(!empty($category)){
            $company_string = " and c.company='$category'";
        }

        if(!empty($item_id)){
            $item_id_string = " and i.id='$item_id'";
        }

        

  

     

        $cart_string = ",(select product_count from seller_cart_items where dealer='" . $user . "' and branch_price_id=bp.id) as cart";



        $result_set = $this->model_all->getTableDataFromQuery("SELECT i.brand,i.itemname,i.productid,bp.company_mrp, bp.margin_price as mrp,ip.item_descr,bp.id as id, bp.pay,bp.is_bt,b.name as suppier_name,b.id as sellerid,bp.qty,ip.id as item_price_id,ip.pack_qty,u.unit_name as case_rate $cart_string FROM `items` i ,item_prices ip ,`branch_prices` bp ,branches b,unit_sizes u, categories c where i.id = ip.item_id and ip.id=bp.itemprice_id and bp.branch_id=b.id and i.status=1 $id_string and b.id in ($branch) and ip.unit_id=u.unit_id and bp.pay!=0  and ip.company_mrp!=0 and c.id=i.productid $company_string $barcode_string $item_id_string order by i.id asc,bp.pay asc");



        $result['total_rows'] = $result_set->num_rows();
        $result['items'] = array();
        if ($result_set->num_rows() > 0) {
            $result["status"] = 1;
            $result["message"] = "Success";
            foreach ($result_set->result_array() as $row) {
                $row['images'] = array();
                $image_qry = $this->model_all->getTableDataFromquery("select * from item_images where item='$row[item_price_id]' order by id");
                $row['caseprice'] = $row['pay'] * $row['pack_qty']; 
                foreach ($image_qry->result() as $img_rs) {
                    if ($img_rs->img_name != "") {
                        $file_headers = @get_headers(base_url() . 'item_pics/' . $img_rs->img_name);
                        if (stripos($file_headers[0], "404 Not Found") > 0 || (stripos($file_headers[0], "302 Found") > 0 && stripos($file_headers[7], "404 Not Found") > 0)) {
                            $picture = base_url() . 'item_pics/noimage.png';
                        } else {
                            $picture = base_url() . 'item_pics/' . $img_rs->img_name;
                        }
                    } else {
                        $picture = base_url() . "item_pics/noimage.png";
                    }
                    $row['images'][] = $picture."?".time();
                }
                $row['margin'] = number_format((( $row['mrp'] - $row['pay']) / $row['mrp']) * 100, 2) . " %";
                $row['discount'] = $row['mrp'] - $row['pay'];
                $row['cart'] = ($row['cart'] != "") ? $row['cart'] : 0;

                $row['bal_qty'] = $row['qty'] ;//- $row['cart'];
                $result['items'][] = $row;
            }
            $this->response($result, 200);
            exit;
        } else {
            $result["status"] = 0;
            $result["message"] = "No Items Found";
            $this->response($result, 200);
            exit;
        }
    }

     function btdetails_get() {
        $id = $this->get("id");
        $user = $this->get("user");

        $cart_string = "";
        $barcode_string = "";
        $id_string = "";
        if (empty($user))
            $user = 0;
        if (!empty($id))
            $id_string = " and i.id='$id'";

        if (!empty($barcode))
            $barcode_string = " and p.barcode='$barcode'";

        $cart_string = ",(select product_count from cart_items where user_id='" . $user . "' and item_id=i.id and sellerid=q.sellerid) as cart";

        $result_set = $this->model_all->getTableDataFromQuery("SELECT i.id,i.brand,i.itemname,i.productid, p.id as pricing_id ,p.mrp, p.pay,p.is_bt, CONCAT(s.first_name,s.last_name) as suppier_name,s.id as sellerid,q.qty $cart_string FROM `items` i ,pricing p ,quantity q ,sellers s where i.id=p.itemid and i.id=q.itemid and s.id=q.sellerid and s.id=q.sellerid and i.status=1 $id_string  $barcode_string");

        /* echo "SELECT i.id,i.brand,i.itemname,i.productid, p.mrp, p.pay, CONCAT(s.first_name,s.last_name) as suppier_name,s.id as sellerid,q.qty $cart_string FROM `items` i ,pricing p ,quantity q ,sellers s where i.id=p.itemid and i.id=q.itemid and s.id=q.sellerid and s.id=q.sellerid and i.status=1 $id_string  $barcode_string"; */

        $result['total_rows'] = $result_set->num_rows();
        $result['items'] = array();
        if ($result_set->num_rows() > 0) {
            $result["status"] = 1;
            $result["message"] = "Success";
            foreach ($result_set->result_array() as $row) {
                $row['images'] = array();
                $image_qry = $this->model_all->getTableData("item_images", array("item" => $row['id']));
                foreach ($image_qry->result() as $img_rs) {
                    if ($img_rs->img_name != "") {
                        $file_headers = @get_headers(base_url() . 'item_pics/' . $img_rs->img_name);
                        if (stripos($file_headers[0], "404 Not Found") > 0 || (stripos($file_headers[0], "302 Found") > 0 && stripos($file_headers[7], "404 Not Found") > 0)) {
                            $picture = base_url() . 'item_pics/noimage.png';
                        } else {
                            $picture = base_url() . 'item_pics/' . $img_rs->img_name;
                        }
                    } else {
                        $picture = base_url() . "item_pics/noimage.png";
                    }
                    $row['images'][] = $picture;
                }
                $row['bt_data'] = array();
                $bt_qry = $this->model_all->getTableData("bt_items", array("pricing_id" => $row['pricing_id']));
                $bt_data = array();
                foreach ($bt_qry->result_array() as $bt_row) {
                    unset($bt_row['pricing_id']);
                    $bt_row['save'] = ($row['pay']*$bt_row['bt_qty'])-$bt_row['bt_price'];
                    $bt_row['bt_price'] = "Rs.".$bt_row['bt_price']." /-";
                    $bt_data[] = $bt_row;
                }
                $row['bt_data'] = $bt_data;

                $row['margin'] = number_format((( $row['mrp'] - $row['pay']) / $row['pay']) * 100, 2) . " %";
                $row['discount'] = $row['mrp'] - $row['pay'];
                $row['cart'] = ($row['cart'] != "") ? $row['cart'] : 0;

                $row['bal_qty'] = $row['qty'] - $row['cart'];
                $result['items'][] = $row;
            }
            $this->response($result, 200);
            exit;
        } else {
            $result["status"] = 0;
            $result["message"] = "No Items Found";
            $this->response($result, 200);
            exit;
        }
    }


  function offers_get() {
        $id = $this->get("id");
        $user = $this->get("user");
        $barcode = $this->get("barcode");
        $offer_type = $this->get("offer_type");
        $offer_string = "";
        
        $cart_string = "";
        $barcode_string = "";
        $present_time = date("Y-m-d H:i:s");
        if($offer_type=="hot" || $offer_type=="offer"){
            $offer_string = " and o.offer_type='$offer_type' and ('$present_time' between `valid_from` and `valid_to`)";
        }else if($offer_type=="today"){
            $offer_string = "and ('$present_time' between `valid_from` and `valid_to`)";
        }
        $id_string = "";
        if (empty($user))
            $user = 0;
        if (!empty($id))
            $id_string = " and i.productid='$id'";

        if (!empty($barcode))
            $barcode_string = " and p.barcode='$barcode'";

        $cart_string = ",(select product_count from cart_items where user_id='" . $user . "' and item_id=i.id and sellerid=q.sellerid) as cart";

       
        $result_set = $this->model_all->getTableDataFromQuery("SELECT i.id,i.brand,i.itemname,i.productid, p.mrp, p.pay,p.is_bt,o.offer_value, CONCAT(s.first_name,s.last_name) as suppier_name,s.id as sellerid,q.qty $cart_string FROM `items` i ,pricing p ,quantity q ,sellers s,offers o where o.pricing_id = p.id  $offer_string  and i.id=p.itemid and i.id=q.itemid and s.id=q.sellerid and s.id=q.sellerid and i.status=1 $id_string  $barcode_string order by p.pay,i.id asc");
       

        $result['total_rows'] = $result_set->num_rows();
        $result['items'] = array();
        if ($result_set->num_rows() > 0) {
            $result["status"] = 1;
            $result["message"] = "Success";
            foreach ($result_set->result_array() as $row) {
                $row['images'] = array();
                $image_qry = $this->model_all->getTableData("item_images", array("item" => $row['id']));
                foreach ($image_qry->result() as $img_rs) {
                    if ($img_rs->img_name != "") {
                        $file_headers = @get_headers(base_url() . 'item_pics/' . $img_rs->img_name);
                        if (stripos($file_headers[0], "404 Not Found") > 0 || (stripos($file_headers[0], "302 Found") > 0 && stripos($file_headers[7], "404 Not Found") > 0)) {
                            $picture = base_url() . 'item_pics/noimage.png';
                        } else {
                            $picture = base_url() . 'item_pics/' . $img_rs->img_name;
                        }
                    } else {
                        $picture = base_url() . "item_pics/noimage.png";
                    }
                    $row['images'][] = $picture;
                }
                $row['margin'] = number_format((( $row['mrp'] - $row['pay']) / $row['pay']) * 100, 2) . " %";
                $row['discount'] = $row['mrp'] - $row['pay'];
                $row['cart'] = ($row['cart'] != "") ? $row['cart'] : 0;

                $row['bal_qty'] = $row['qty'] - $row['cart'];
                $result['items'][] = $row;
            }
            $this->response($result, 200);
            exit;
        } else {
            $result["status"] = 0;
            $result["message"] = "No Items Found";
            $this->response($result, 200);
            exit;
        }
        

    }


     function search_get(){
        $term = $this->get("term");
        $company = $this->get("company");
        $condition ="";
        if($company!=""){
           $condition ="and company in ($company)";
        }
        $result_set=$this->model_all->getTableDataFromQuery("SELECT c.id,c.`categoryname`,c.parentid,IFNULL((select categoryname from categories where id=c.parentid),'') as parent_cateogorty FROM `categories` c  where parentid!=0 $condition");
        if ($result_set->num_rows() > 0) {
            $result["status"] = 1;
            $result["message"] = "Records Found";
            $result["records"] = array();
            foreach ($result_set->result_array() as $row) {
                $result["records"][]=$row;
            }
             $this->response($result, 200);
        }else{
            $result["status"] = 0;
            $result["message"] = "No Items Found";
            $this->response($result, 200);
            exit;
        }
    }



   function globalsearch_get() {
       
        $category = $this->get("category");

  

     

       



        $result_set = $this->model_all->getTableDataFromQuery("SELECT i.id as item_id,i.itemname,i.productid,cm.company_id FROM `items` i, categories c,companies cm where  i.status=1 and c.id=i.productid and c.company=cm.company_id and c.company='$category' order by i.id asc");



        $result['total_rows'] = $result_set->num_rows();
        $result['items'] = array();
        if ($result_set->num_rows() > 0) {
            $result["status"] = 1;
            $result["message"] = "Success";
            foreach ($result_set->result_array() as $row) {
                
                $result['items'][] = $row;
            }
            $this->response($result, 200);
            exit;
        } else {
            $result["status"] = 0;
            $result["message"] = "No Items Found";
            $this->response($result, 200);
            exit;
        }
    }

}

