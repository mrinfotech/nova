<?php

require(APPPATH . '/libraries/REST_Controller.php');

class Cart extends REST_Controller {

    var $alphas = array();

    public function __construct() {
        parent::__construct();
        $this->load->model('model_all');
        $this->alphas = range('A', 'Z');
    }

    //API - Fetch All Pincodes
    function list_get() {
        $user = $this->get('user');
        $result_set = $this->model_all->getTableDataFromQuery("SELECT c.*, CONCAT(s.first_name,s.last_name)  as seller,i.itemname,i.brand,p.pay,p.mrp,p.sellingprice,q.qty as bal_qty FROM `cart_items` c, items i ,sellers s,pricing p,quantity q where i.id=c.item_id and s.id = c.sellerid and p.sellerid=s.id  and p.itemid = c.item_id and c.user_id='$user' and q.itemid=c.item_id and q.sellerid=s.id");
        if ($result_set->num_rows() > 0) {
            $result["status"] = 1;
            $result["message"] = "Records Found";
            $result["total_rows"] = $result_set->num_rows();
            $total_units = 0;
            $total_savings = 0;
            $total_sur_charge = 0;
            $total_pay = 0;
            foreach ($result_set->result_array() as $row) {
                $row['images'] = array();
                $image_qry = $this->model_all->getTableData("item_images", array("item" => $row['item_id']));
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
                $row['total_price'] = ($row['product_count'] * $row['pay']) . " /-";
                $row['discount'] = $row['mrp'] - $row['pay'];
                $total_units += $row['product_count'];
                $total_savings += (($row['mrp'] - $row['pay']) * $row['product_count']);
                $total_pay += ($row['product_count'] * $row['pay']);
                // $total_sur_charge += 0.00;
                $result["records"][] = $row;
            }
            $service_percent = 0.00;
            $result['total_units'] = $total_units;
            $result['service_percent'] = $service_percent . "%";
            $result['total_savings'] = $total_savings . " /-";
            $result['total_sur_charge'] = $service_percent . " /-";
            $result['sub_pay'] = $total_pay . " /-";
            $result['total_pay'] = ((($total_pay * $service_percent) / 100) + $total_pay) . " /-";


            $this->response($result, 200);
            exit;
        } else {
            $result["status"] = 0;
            $result["message"] = "No records Found";
            $this->response($result, 200);
            exit;
        }
    }

    function count_get() {
        $user = $this->get('user');
        $items_count_qry = $this->model_all->getTableData("cart_items", array("user_id" => $user));

        $result["items_count"] = $items_count_qry->num_rows();
        $this->response($result, 200);
        exit;
    }

    //API - Save Pin Code
    function add_post() {


        $status = 0;
        $message = "Product not added to Cart successfully.";
        $user = $this->post('user');
        $item = $this->post('item');
        $sellerid = $this->post('sellerid');
        $qty = $this->post('qty');
        $data = array("user_id" => $user, "item_id" => $item, "sellerid" => $sellerid);
        $cart_qry = $this->model_all->getTableData("cart_items", $data);
        if ($cart_qry->num_rows() > 0) {
            $cart_rs = $cart_qry->row_array();
            $data["product_count"] = $qty;
            $aff_rows = $this->model_all->update($data, array("id" => $cart_rs["id"]), "cart_items");
            if ($aff_rows) {
                $status = 1;
                $message = "Cart updated Successfully";
            }
        } else {
            $data["product_count"] = $qty;
            $id = $this->model_all->save($data, "cart_items");
            if ($id > 0) {
                $status = 1;
                $message = "Product added to cart successfully.";
            }
        }

        $items_count_qry = $this->model_all->getTableData("cart_items", array("user_id" => $user));

        $result["status"] = $status;
        $result["message"] = $message;
        $result["items_count"] = $items_count_qry->num_rows();

        $this->response($result, 200);

        exit;
    }

    //API - Save Pin Code
    function delete_put() {


        $status = 0;
        $message = "Product not added to Cart successfully.";
        $user = $this->put('user');
        $item = $this->put('item');
        $sellerid = $this->put('sellerid');
        $data = array("user_id" => $user, "item_id" => $item, "sellerid" => $sellerid);
        $cart_qry = $this->model_all->getTableData("cart_items", $data);
        if ($cart_qry->num_rows() > 0) {
            $aff_rows = $this->model_all->deleteRow("cart_items", $data);
            if ($aff_rows > 0) {
                $status = 1;
                $message = "Item deleted from cart updated Successfully";
            }
        } else {
            $status = 0;
            $message = "No such item in the cart";
        }

        $items_count_qry = $this->model_all->getTableData("cart_items", array("user_id" => $user));

        $result["status"] = $status;
        $result["message"] = $message;
        $result["items_count"] = $items_count_qry->num_rows();

        $this->response($result, 200);

        exit;
    }

    function checkout_post() {
        $user = $this->post('user');
        $adress = "";
        $landmark = $this->post('landmark');
        $latitude = $this->post('latitude');
        $langitude = $this->post('langitude');
        $flat_no = $this->post('flat_no');
        $dt = date("Y-m-d H:i:s");
        $seller_time = date("H:i:s");
        $address_id = $this->post('address');
        $delivery_date = date('Y-m-d', strtotime("+2 day"));
        $strtitime = strtotime($delivery_date);
        $strtitime = $strtitime + (6 * 60 * 60);
        $est_time = date('H:i:s', $strtitime);
        $seller_item_value = 0.00;
        $availability_qry = $this->model_all->getTableDataFromQuery("SELECT c.*, CONCAT(s.first_name,s.last_name)  as seller,i.itemname,i.brand,p.pay,p.mrp,p.sellingprice,p.id as price_id,q.qty as rem_qty FROM `cart_items` c, items i ,sellers s,pricing p,quantity q where i.id=c.item_id and s.id = c.sellerid and p.sellerid=s.id  and p.itemid = c.item_id and q.itemid = i.id and q.sellerid=s.id  and c.user_id='$user'");
        if ($availability_qry->num_rows() > 0) {
            $availability_flag = TRUE;
            $availability_str= "";
            foreach ($availability_qry->result() as $availability_rs) {
                if ($availability_rs->product_count > $availability_rs->rem_qty) {
                    $availability_flag = FALSE;
                    $availability_str = $availability_str."".$availability_rs->itemname.",";
                }
                $availability_str = rtrim($availability_str,",");
            }
            if ($availability_flag) {
                $service_charge = 0.00;
                $data = array("orderedby" => $user, "flat_no" => $flat_no, "address_id" => $address_id, "address" => $adress, "landmark" => $landmark, "latitude" => $latitude, "longitude" => $langitude, "orderedon" => $dt, "deliveredby" => 0, "deliveredon" => '', "delivery_accept" => 0, "delivery_reject" => 0, "est_date" => $delivery_date, "est_time" => $est_time, "service_charge" => $service_charge, "delivery_charges" => "0.00");
                $order_id = $this->model_all->save($data, "orders");
                if ($order_id > 0) {
                    $total_value_pay = 0.00;

                    $this->model_all->save(array("order_id" => $order_id, "order_status" => 'Ordered', "changed_on" => $dt), 'order_track');
                    $order_string = "BT0000" . $order_id;
                    $result["status"] = "1";
                    $result["message"] = "CheckOut Successfully";
                    $result["order_id"] = $order_string;
                    $result["order_key"] = $order_id;
                    $aff_rows = $this->model_all->update(array("order_id" => $order_string), array("id" => $order_id), "orders");
                    $result_set = $this->model_all->getTableDataFromQuery("SELECT c.*, CONCAT(s.first_name,s.last_name)  as seller,i.itemname,i.brand,p.pay,p.mrp,p.sellingprice,p.id as price_id FROM `cart_items` c, items i ,sellers s,pricing p where i.id=c.item_id and s.id = c.sellerid and p.sellerid=s.id  and p.itemid = c.item_id and c.user_id='$user'");
                    if ($result_set->num_rows() > 0) {
                        foreach ($result_set->result() as $item_set) {
                            $scharge = 0.00;
                            $item_qty = $item_set->product_count;
                            $item_pay = $item_set->pay;
                            $item_sp_amt = $item_set->sellingprice * $item_qty;
                            $item_total_cost = $item_pay * $item_qty;
                            if ($item_set->added_from == "bt") {
                                $bt_items_qry = $this->model_all->getTableDataFromQuery("select * from  bt_items where pricing_id='$item_set->price_id' and  bt_qty<='$item_qty' order by bt_qty desc");
                                if ($bt_items_qry->num_rows() > 0) {
                                    $loop_items_qty = $item_set->product_count;
                                    $item_total_cost = 0;
                                    foreach ($bt_items_qry->result() as $bt_items_row) {
                                        if ($bt_items_row->qty <= $loop_items_qty) {
                                            $item_total_cost = $loop_items_qty->bt_price;
                                            $loop_items_qty = $loop_items_qty - $bt_items_row->qty;
                                        }
                                    }
                                    $item_total_cost = $item_total_cost + ($loop_items_qty) * $item_pay;
                                }
                            } else if ($item_set->added_from != "list") {
                                $offer_items_qry = $this->model_all->getTableDataFromQuery("select * from  offres where pricing_id='$item_set->price_id' and valid_from>='$dt' and valid_to<='$dt' and offer_type='$item_set->added_from'");
                                if ($offer_items_qry->num_rows() > 0) {
                                    foreach ($offer_items_qry->result() as $order_items_row) {
                                        if ($order_items_row->min_order <= $item_set->product_count) {
                                            $item_pay = $order_items_row->offer_value;
                                            $item_total_cost = $item_pay * $item_qty;
                                        }
                                    }
                                }
                            }


                            $item_data = array("itemid" => $item_set->item_id, "sellerid" => $item_set->sellerid, "orderid" => $order_id, "qty" => $item_qty, "mrp" => $item_set->mrp, "service_charge" => $scharge, "amount" => $item_pay, "total_cost" => $item_total_cost, "sp" => $item_set->sellingprice, "sp_amount" => $item_sp_amt, "created_on" => $dt);
                            $item_value = ($item_qty * $item_pay) + $scharge;
                            $seller_item_value = $item_sp_amt + $scharge;
                            $total_value_pay += ($item_qty * $item_pay) + $scharge;
                            $order_item_id = $this->model_all->save($item_data, "order_items");
                            $item_ccnt = $item_set->product_count;

                            $this->model_all->getTableDataFromQuery("update quantity set qty=qty-$item_ccnt where itemid='$item_set->item_id' and sellerid='$item_set->sellerid'");
                            /* Actual */
                            $seller_date = date("Y-m-d");
                            $limit_time = "18:00:00";
                            if ($seller_time >= $limit_time) {
                                $seller_date = date("Y-m-d", strtotime("+1 day", strtotime($seller_date)));
                                $date = $seller_date;
                            }

                            $seller_invoices = $this->model_all->getTableDataFromQuery("select * from seller_invoices where order_date='$seller_date' and seller_id='$item_set->sellerid' and   (is_processed='0' and is_picked='0')");

                            $exist_invoices = $this->model_all->getTableDataFromQuery("select * from seller_invoices where order_date='$seller_date' and seller_id='$item_set->sellerid' and   (is_processed!='0' or is_picked!='0')");
                            if ($seller_invoices->num_rows() == 0) {
                                $invoice_id = date("Ymd", strtotime($seller_date)) . $item_set->sellerid . ($this->alphas[$exist_invoices->num_rows()]);
                                $invoice_data = array('invoice_id' => $invoice_id, 'order_date' => $seller_date, 'seller_id' => $item_set->sellerid, 'is_processed' => '0', 'is_picked' => '0', 'generate' => '0');
                                $sellet_invoice_pk = $this->model_all->save($invoice_data, "seller_invoices");
                            } else {
                                $row = $seller_invoices->row();
                                $invoice_id = $row->invoice_id;
                                $sellet_invoice_pk = $row->id;
                            }
                            $seller_details = $this->model_all->getTableData("seller_items", array("order_date" => $seller_date, "item_id" => $item_set->item_id, 'seller_id' => $item_set->sellerid,"sellet_invoice_pk" => $invoice_id));
                            if ($seller_details->num_rows() > 0) {
                                $seller_rs = $seller_details->row();
                                $this->model_all->getTableDataFromQuery("update seller_items set `qty`=`qty`+$item_qty,`amount`=`amount`+$seller_item_value,order_item=concat(order_item,',$order_item_id')  where id='$seller_rs->id'");
                            } else {
                                $item_data = array("seller_id" => $item_set->sellerid, "item_id" => $item_set->item_id, "mrp" => $item_set->mrp, "sellingprice" => $item_set->sellingprice, "qty" => $item_qty, "amount" => $seller_item_value, "service_charge" => $scharge, "order_item" => $order_item_id, "invoice_id" => $invoice_id, "sellet_invoice_pk" => $sellet_invoice_pk, "picked_qty" => 0, "status" => '0', "reason" => '', "order_date" => $seller_date);
                                $this->model_all->save($item_data, "seller_items");
                            }

                            /* Actual */
                        }
                        $order_value = $total_value_pay;
                        $delivery_charge = ($total_value_pay / 100);
                        //$total_value_pay = $total_value_pay+$delivery_charge+$service_charge;

                        $this->model_all->update(array("order_value" => $order_value, "delivery_charges" => $delivery_charge), array("id" => $order_id), "orders");
                        $this->model_all->deleteRow("cart_items", array("user_id" => $user));
                    }
                } else {
                    $result["status"] = "0";
                    $result["message"] = "Something went wrong.Please try later";
                    $result["order_id"] = "";
                }
            } else {
                $result["status"] = "0";
                $result["message"] = "Items ".$availability_str." are out of stock";
            }
        } else {

            $result["status"] = "0";
            $result["message"] = "Something went wrong.Please try later";
        }
        $this->response($result, 200);

        exit;
    }

}

 
