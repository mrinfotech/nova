<?php

require(APPPATH . '/libraries/REST_Controller.php');

class Dealercart extends REST_Controller {

    var $alphas = array();

    public function __construct() {
        parent::__construct();
        $this->load->model('model_all');
        $this->alphas = range('A', 'Z');
        $this->load->library('fcm');
    }

    //API - Fetch All Pincodes
    function list_get() {
        $user = $this->get('user');

        /*
          "SELECT c.id,c.dealer as user_id,c.branch_price_id as item_id,c.product_count,c.added_from, b.name  as seller,i.id as item_id,i.itemname,i.brand,bp.pay,ip.mrp,bp.margin_price as sellingprice,bp.qty as bal_qty FROM `seller_cart_items` c, items i ,branches b,branch_prices bp,item_prices ip where bp.id=c.branch_price_id and b.id = bp.branch_id and bp.itemprice_id=ip.id and c.dealer='$user' and ip.item_id=i.id"
         */
        $dealer_state = 0;
        $credit_limit = 0.00;
        $dealer_qry = $this->model_all->getTableDataFromQuery("select s.id as state_id,se.credit_limit from sellers se,addresses a,countries c,states s,districts d where se.id='$user' and  a.user_id=se.id and (a.user_role='seller' or a.user_role='DEALER') and a.is_default='1' and a.country=c.id and a.state=s.id and d.id=a.district  and s.country=c.id and d.state=s.id");
        if ($dealer_qry->num_rows() > 0) {
            foreach ($dealer_qry->result() as $dealer_row) {
                $dealer_state = $dealer_row->state_id;
                $credit_limit = $dealer_row->credit_limit;
            }
        }
        
        $debit = $this->model_all->getTableDataFromQuery("select sum(amount) as debit_bal from wallet_history where user_id='$user' and transaction_mode='debit' and status='1'")->row()->debit_bal;
        $credit = $this->model_all->getTableDataFromQuery("select sum(amount) as credit_bal from wallet_history where user_id='$user' and transaction_mode='credit' and status='1'")->row()->credit_bal;
        if($credit_limit<=0){
            $balance = 0;
        }else{
            $balance = $debit - $credit;
            if($balance<0){
                 $balance = $credit_limit - $balance;
            }else{
                 $balance = $credit_limit + $balance;
            }
            
            if( $balance < 0){
                 $balance = 0;
            }
        }
        
        
        $result_set = $this->model_all->getTableDataFromQuery("SELECT o.state,cm.company_id,cm.company,c.id,c.dealer as user_id,c.branch_price_id as item_id,ip.id as item_price_id,c.product_count,c.added_from, b.name  as seller,i.itemname,i.brand,bp.pay,bp.company_mrp,bp.margin_price as mrp,bp.margin_price as sellingprice,ip.pack_qty,bp.qty as bal_qty, u.unit_name as pack_type,ip.igst,ip.sgst,ip.cgst FROM `seller_cart_items` c, items i ,branches b,offices o,branch_prices bp,item_prices ip,companies cm,unit_sizes u where cm.company_id=b.company  and bp.id=c.branch_price_id and b.id = bp.branch_id and bp.itemprice_id=ip.id and c.dealer='$user' and ip.item_id=i.id and ip.unit_id=u.unit_id  and b.office_id=o.id order by cm.company_id desc");
        if ($result_set->num_rows() > 0) {
            $result["status"] = 1;
            $result["message"] = "Records Found";
            $result["total_rows"] = $result_set->num_rows();
            $total_units = 0;
            $total_savings = 0;
            $total_sur_charge = 0;
            $total_pay = 0;
            $hidden_total_pay = 0;
            foreach ($result_set->result_array() as $row) {
                //print_r($row['item_id'].',');
                $row['caseprice'] = $row['pay'] * $row['pack_qty'];
                $company_mrp = $row['company_mrp'] * $row['pack_qty'];
                $pay = $row['pay'] * $row['pack_qty'];
                $mrp = $row['mrp'] * $row['pack_qty'];
                $item_total_cost = 0.00;
                $row['main_mrp'] = $company_mrp;
                $igst =  $row['igst'];
                $sgst =  $row['sgst'];
                $cgst =  $row['cgst'];
                if ($dealer_state == $row['state']) {
                    $item_total_cost = ($pay + (round((($sgst * $pay) / 100) + ($cgst * $pay) / 100, 2))) * $row['product_count'];
                } else {
                    $item_total_cost = ($pay + round(($igst * $pay) / 100, 2)) * $row['product_count'];
                }

               


                /* $row['company_mrp'] = $row['company_mrp'] * $row['pack_qty']; 
                  $row['mrp'] = $row['mrp'] * $row['pack_qty'];
                  $row['pay'] = $row['pay'] * $row['pack_qty']; */



                $row['images'] = array();
                $image_qry = $this->model_all->getTableDataFromquery("select * from item_images where item='$row[item_price_id]' order by id");
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
                $row['total_price'] = ($row['product_count'] * $pay) . " /-";
                $row['discount'] = $row['mrp'] - $row['pay'];
                $total_units += $row['product_count'];
                $total_savings += (($mrp - $pay) * $row['product_count']);
                $total_pay += ($row['product_count'] * $pay);
                $hidden_total_pay += $item_total_cost;
                
                
                // $total_sur_charge += 0.00;
                $result["records"][] = $row;
            }
            $service_percent = 0.00;
            $result['hidden_total_pay'] = $hidden_total_pay;
          //  $result['balance'] = $balance;
            $result['total_units'] = $total_units;
            $result['service_percent'] = $service_percent . "%";
            $result['total_savings'] = $total_savings . " /-";
            $result['total_sur_charge'] = $service_percent . " /-";
            $result['sub_pay'] = $total_pay . " /-";
            $result['total_pay'] = ((($total_pay * $service_percent) / 100) + $total_pay) . " /-";
            $result['balance'] = $hidden_total_pay+1;


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
        $items_count_qry = $this->model_all->getTableData("seller_cart_items", array("dealer" => $user));

        $result["items_count"] = $items_count_qry->num_rows();
        $this->response($result, 200);
        exit;
    }

    //API - Save Pin Code
    function add_post() {


        $status = 0;
        $message = "Product not added to Cart successfully.";
        $dealer = $this->post('sellerid');
        $user = $this->post('user');
        $item = $this->post('item');
        $role = $this->post('role');
        $dt = date("Y-m-d H:i:s");
        $qty = $this->post('qty');


        $data = array("dealer" => $dealer, "branch_price_id" => $item);
        $cart_qry = $this->model_all->getTableData("seller_cart_items", $data);
        if ($cart_qry->num_rows() > 0) {
            $cart_rs = $cart_qry->row_array();
            $data["product_count"] = $qty;
            $data["modifiedon"] = $dt;
            $data["added_by"] = $user;
            $data["added_role"] = $role;

            $aff_rows = $this->model_all->update($data, array("id" => $cart_rs["id"]), "seller_cart_items");
            if ($aff_rows) {
                $status = 1;
                $message = "Cart updated Successfully";
            }
        } else {
            $data["product_count"] = $qty;
            $data["modifiedon"] = $dt;
            $data["added_by"] = $user;
            $data["added_role"] = $role;
            $id = $this->model_all->save($data, "seller_cart_items");
            if ($id > 0) {
                $status = 1;
                $message = "Product added to cart successfully.";
            }
        }

        $items_count_qry = $this->model_all->getTableData("seller_cart_items", array("dealer" => $dealer));

        $result["status"] = $status;
        $result["message"] = $message;
        $result["items_count"] = $items_count_qry->num_rows();

        $this->response($result, 200);

        exit;
    }

    //API - Test Fot json
    function json_add_post() {


        $status = 0;
        $message = "Product not processed successfully.";
        $dealer = $this->post('sellerid');
        $user = $this->post('user');
        $item = $this->post('item');
        $role = $this->post('role');
        $dt = date("Y-m-d H:i:s");
        $qty = $this->post('qty');
        $json_qty = $this->post('json_qty');
        $json_encode_array = json_decode($json_qty, true);
        $flag = false;
        $processed_items = 0;
        foreach ($json_encode_array as $key => $value) {
            $data = array("dealer" => $dealer, "branch_price_id" => $key);
            $cart_qry = $this->model_all->getTableData("seller_cart_items", $data);
            if ($cart_qry->num_rows() > 0) {
                $cart_rs = $cart_qry->row_array();
                $data["product_count"] = $value;
                $data["modifiedon"] = $dt;
                $data["added_by"] = $user;
                $data["added_role"] = $role;
                $aff_rows = $this->model_all->update($data, array("id" => $cart_rs["id"]), "seller_cart_items");
                if ($aff_rows) {
                    $flag = true;
                    $processed_items++;
                }
            } else {
                $data["product_count"] = $value;
                $data["modifiedon"] = $dt;
                $data["added_by"] = $user;
                $data["added_role"] = $role;
                $id = $this->model_all->save($data, "seller_cart_items");
                if ($id > 0) {
                    $flag = true;
                    $processed_items++;
                }
            }
        }






        $items_count_qry = $this->model_all->getTableData("seller_cart_items", array("dealer" => $dealer));
        if ($flag) {
            $status = 1;
            $message = $processed_items . " Items Processed Successfully";
        }


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
        $data = array("dealer" => $user, "branch_price_id" => $item);
        $cart_qry = $this->model_all->getTableData("seller_cart_items", $data);
        if ($cart_qry->num_rows() > 0) {
            $aff_rows = $this->model_all->deleteRow("seller_cart_items", $data);
            if ($aff_rows > 0) {
                $status = 1;
                $message = "Item deleted from cart Successfully";
            }
        } else {
            $status = 0;
            $message = "No such item in the cart";
        }

        $items_count_qry = $this->model_all->getTableData("seller_cart_items", array("dealer" => $user));

        $result["status"] = $status;
        $result["message"] = $message;
        $result["items_count"] = $items_count_qry->num_rows();

        $this->response($result, 200);

        exit;
    }

    function checkout_post() {
        $user = $this->post('createdby');
        $createdby = $this->post('user');
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
        $payment_type = $this->post('payment_type');
        $credit_date = $this->post('credit_date');
        $reference_no = $this->post('reference_no');
        $transport = $this->post('transport');
        $remarks = $this->post('remarks');
        if($transport==""){
            $transport = 0;
        }

        $branch = $this->post('branch');
        $transaction_mode = "";
        $transaction_no = "";
        $tax_type = "";
        $dealer_state = 0;
        $branch_state = 0;
        $igst = 0.00;
        $sgst = 0.00;
        $cgst = 0.00;

        /// branch_qry   

        $branch_qry = $this->model_all->getTableDataFromQuery("select o.state from offices o,branches b,companies c,states s where o.id=b.office_id and b.id='$branch' and b.company=c.company_id and o.state=s.id");
        if ($branch_qry->num_rows() > 0) {
            foreach ($branch_qry->result() as $branch_row) {
                $branch_state = $branch_row->state;
            }
        }


        $dealer_qry = $this->model_all->getTableDataFromQuery("select s.id as state_id from sellers se,addresses a,countries c,states s,districts d where se.id='$user' and  a.user_id=se.id and (a.user_role='seller' or a.user_role='DEALER') and a.is_default='1' and a.country=c.id and a.state=s.id and d.id=a.district  and s.country=c.id and d.state=s.id");
        if ($dealer_qry->num_rows() > 0) {
            foreach ($dealer_qry->result() as $dealer_row) {
                $dealer_state = $dealer_row->state_id;
            }
        }

        if ($branch_state == $dealer_state) {
            $tax_type = "gst";
        } else {
            $tax_type = "igst";
        }












        $reference_number = "";
        $transaction_mode = "debit";
        if ($payment_type == "credit" && $credit_date != "") {
            $credit_date = date('Y-m-d', strtotime("+" . $credit_date . " day"));
            
        } else if ($payment_type == "cash" && $credit_date != "") {
            $reference_no = $createdby;
            
        } else if ($payment_type == "cheque" && $credit_date != "") {
            $credit_date = date('Y-m-d', strtotime($credit_date));
           
        } else if (($payment_type == "card" || $payment_type == "netbanking" || $payment_type == "RTGS" || $payment_type == "NEFT" || $payment_type == "IMPS") && $credit_date != "") {
           
            $credit_date = date('Y-m-d', strtotime($credit_date));
        }

        if ($address_id == "" || $address_id == 0) {
            $address_qry = $this->model_all->getTableDataFromQuery("select * from addresses where user_id='$user' and (user_role='seller' or user_role='DEALER') and is_default='1' and status='1'");
            if ($address_qry->num_rows() > 0) {
                $address_rs = $address_qry->row();
                $address_id = $address_rs->id;
            }
        }

        $est_time = date('H:i:s', $strtitime);
        $result = array();
        $result["orders"] = array();
        /* $availability_qry = $this->model_all->getTableDataFromQuery("SELECT c.id,c.dealer as user_id,c.branch_price_id as item_id,c.product_count,c.added_from, b.name  as seller,i.itemname,i.brand,bp.pay,ip.company_mrp,ip.mrp,bp.margin_price as sellingprice,bp.qty as rem_qty FROM `seller_cart_items` c, items i ,branches b,branch_prices bp,item_prices ip where bp.id=c.branch_price_id and b.id = bp.branch_id and bp.itemprice_id=ip.id and c.dealer='$user' and ip.item_id=i.id");

          if ($availability_qry->num_rows() > 0) {
          $availability_flag = TRUE;
          $availability_str = "";
          foreach ($availability_qry->result() as $availability_rs) {
          if ($availability_rs->product_count > $availability_rs->rem_qty) {
          $availability_flag = FALSE;
          $availability_str = $availability_str . "" . $availability_rs->itemname . ",";
          }
          $availability_str = rtrim($availability_str, ",");
          }
          if ($availability_flag) { */



        $company_qry = $this->model_all->getTableDataFromQuery("SELECT distinct cm.company_id,b.id,cm.prefix FROM `seller_cart_items` c, items i ,branches b,branch_prices bp,item_prices ip,companies cm,unit_sizes u where cm.company_id=b.company  and bp.id=c.branch_price_id and b.id = bp.branch_id and bp.itemprice_id=ip.id and c.dealer='$user' and ip.item_id=i.id and ip.unit_id=u.unit_id");
        foreach ($company_qry->result() as $company_row) {
            $seller_item_value = 0.00;
            $service_charge = 0.00;
            $paid = 0.00;
            $company = $company_row->company_id;
            $branch = $company_row->id;
            $prefix = $company_row->prefix;
            $forward_by = 0;

            
            if ($user != $createdby) {
               $forward_by = $createdby;
            }
            $data = array("orderedby" => $user, "flat_no" => $flat_no, "address_id" => $address_id, "address" => $adress, "landmark" => $landmark, "latitude" => $latitude, "longitude" => $langitude, "orderedon" => $dt, "deliveredby" => 0, "deliveredon" => '', "dboy_accept" => 0, "seller_accept" => 0, "est_date" => $delivery_date, "est_time" => $est_time, "service_charge" => $service_charge, "delivery_charges" => "0.00", "payment_type" => $payment_type, "credit_date" => $credit_date, "created_by" => $createdby, "branch_id" => $branch, "reference_no" => $reference_no,"forward_by"=>$forward_by,"company_id"=>$company,"transport_id"=>$transport,"remarks"=>$remarks);

            $order_id = $this->model_all->save($data, "seller_orders");
            // echo $this->db->last_query();

            if ($order_id > 0) {
                $total_value_pay = 0.00;

                $this->model_all->save(array("order_id" => $order_id, "order_status" => 'Ordered', "changed_on" => $dt), 'seller_order_track');
                $parent_count_query = $this->model_all->getTableDataFromQuery("select id from seller_orders where parent_order=0");
                $num_rows = $parent_count_query->num_rows();

$next_year = date('y')+1;
$present_year = date('Y');
$past_year = date('Y') - 1;
if(date('m') <= 3)
   $financial_year = $past_year."-".date('y');
else
   $financial_year = $present_year."-".$next_year;

                $order_string =   $financial_year ."/".date('M')."/".$prefix.$this->model_all->prefix_zeros($num_rows+1);   //$prefix . "I" . $this->model_all->prefix_zeros($num_rows+1); 

                if ($payment_type == "credit")
                    $transaction_no = $order_string;

                $result["status"] = "1";
                $result["message"] = "CheckOut Successfully";

                $order_object = array();
                $order_object["order_id"] = $order_string;
                $order_object["order_key"] = $order_id; 
                $result["orders"][] = $order_object;
                $aff_rows = $this->model_all->update(array("order_id" => $order_string), array("id" => $order_id), "seller_orders");
                /*
                  SELECT c.*, CONCAT(s.first_name,s.last_name)  as seller,i.itemname,i.brand,p.pay,p.mrp,p.sellingprice,p.id as price_id FROM `cart_items` c, items i ,sellers s,pricing p where i.id=c.item_id and s.id = c.sellerid and p.sellerid=s.id  and p.itemid = c.item_id and c.user_id='$user'


                 */


                $result_set = $this->model_all->getTableDataFromQuery("SELECT c.id,c.dealer as user_id,c.branch_price_id,c.product_count,c.added_from, b.name  as seller,i.id as item_id,i.itemname,i.brand,bp.pay,bp.company_mrp,bp.margin_price as mrp,bp.margin_price as sellingprice,bp.qty as rem_qty,ip.pack_qty,ip.cgst,ip.sgst,ip.igst FROM `seller_cart_items` c, items i ,branches b,branch_prices bp,item_prices ip where bp.id=c.branch_price_id and b.id = bp.branch_id and b.id='$branch' and bp.itemprice_id=ip.id and c.dealer='$user' and ip.item_id=i.id");
                if ($result_set->num_rows() > 0) {
                    foreach ($result_set->result() as $item_set) {
                        $scharge = 0.00;
                        $pay = $item_set->pay;


                        $item_qty = $item_set->product_count;
                        if ($tax_type == "gst") {
                            $igst = 0.00;
                            $sgst = $item_set->sgst;
                            $cgst = $item_set->cgst;
                        } else {
                            $igst = $item_set->igst;
                            $sgst = 0.00;
                            $cgst = 0.00;
                        }


                        /*  For given single piece qty */
                        $item_set->sellingprice = $item_set->company_mrp * $item_set->pack_qty; //sellingprice
                        $item_set->pay = $item_set->pay * $item_set->pack_qty;
                        /*  For given single piece qty */
                        $item_pay = $item_set->pay;
                        //  $item_total_cost = $item_pay * $item_qty;
                        if ($tax_type == "gst") {
                            $item_total_cost = ($item_pay + (round((($sgst * $item_pay) / 100) + ($cgst * $item_pay) / 100, 2))) * $item_qty;
                        } else if ($tax_type == "igst") {
                            $item_total_cost = ($item_pay + round(($igst * $item_pay) / 100, 2)) * $item_qty;
                        }


                        $item_sp_amt = $item_set->sellingprice * $item_qty;

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


                        $item_data = array("branch_price_id" => $item_set->branch_price_id, "orderid" => $order_id, "qty" => $item_qty, "company_mrp" => $item_set->company_mrp, "mrp" => $item_set->mrp, "paid" => $pay, "pack_qty" => $item_set->pack_qty, "service_charge" => $scharge, "amount" => $item_pay, "tax_type" => $tax_type, "igst" => $igst, "cgst" => $cgst, "sgst" => $sgst, "total_cost" => $item_total_cost, "sp" => $item_set->sellingprice, "sp_amount" => $item_sp_amt, "created_on" => $dt);
                        $item_value = ($item_qty * $item_pay) + $scharge;
                        $seller_item_value = $item_sp_amt + $scharge;

                        $total_value_pay += ($item_total_cost) + $scharge;


                        $order_item_id = $this->model_all->save($item_data, "seller_order_items");
                        if ($order_item_id > 0) {
                            $this->model_all->deleteRow("seller_cart_items", array("id" => $item_set->id));
                            // echo $this->db->last_query();
                        }



                        $item_ccnt = $item_set->product_count;

                        //  $this->model_all->getTableDataFromQuery("update branch_prices set qty=qty-$item_ccnt where id='$item_set->branch_price_id'"); Reducing Quantity
                        /* Actual */
                        $seller_date = date("Y-m-d");
                        $limit_time = "18:00:00";
                        if ($seller_time >= $limit_time) {
                            $seller_date = date("Y-m-d", strtotime("+1 day", strtotime($seller_date)));
                            $date = $seller_date;
                        }

                        /*        $seller_invoices = $this->model_all->getTableDataFromQuery("select * from seller_invoices where order_date='$seller_date' and seller_id='$item_set->sellerid' and   (is_processed='0' and is_picked='0')");

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
                         */
                        /* Actual */
                    }
                    $order_value = $total_value_pay;
                    $delivery_charge = 0.00; //($total_value_pay / 100);
                    //$total_value_pay = $total_value_pay+$delivery_charge+$service_charge;

                    if($payment_type == "credit"){
			$this->model_all->getTableDataFromQuery("update sellers set wallet=wallet-$order_value where id='$user' ");
		    }

                   // $this->model_all->getTableDataFromQuery("update sellers set wallet=wallet-$order_value where id='$user' ");
                    $this->model_all->update(array("order_value" => $order_value,"final_value"=>$order_value, "delivery_charges" => $delivery_charge), array("id" => $order_id), "seller_orders");
                    if ($payment_type != "") {
                        $this->model_all->save(array('order_id' => $order_id, 'profile_id' => $user, 'profile_role' => 'seller', 'amount' => $order_value, 'status' => 1, 'transaction_type' => $payment_type, 'reference_no' => $reference_no), "transaction_track");

                        if ($user == $createdby) {
                            $action_role = "DEALER";
                        } else {
                            $action_role = "trade";
                        }


                        $this->model_all->save(array("user_id" => $user, "user_role" => 'DEALER', "prev_balance" => 0.00,"branch"=>$branch, "order_id" => $order_id,"particular"=>"Sale-".$order_string, "amount" => $order_value, "reference_no" => $order_string, "transaction_no" => $transaction_no, "transaction_mode" => $transaction_mode, "payment_mode" => $payment_type, "transaction_date" => $dt, "action_by" => $createdby, "action_role" => $action_role, "action_date" => $dt, 'status' => '1'), "wallet_history");
                    }
                }
            } else {
                $result["status"] = "0";
                $result["message"] = "Something went wrong.Please try later.";
                $result["order_id"] = "";
            }
            // $this->model_all->deleteRow("seller_cart_items", array("dealer" => $user));
        }
        /* } else {
          $result["status"] = "0";
          $result["message"] = "Items ".$availability_str." are out of stock";
          }
          } else {

          $result["status"] = "0";
          $result["message"] = "Something went wrong.Please try later.................";
          } */
        

        $notify_data = $this->model_all->getDealerExecutive($user,$branch);
       // print_r($notify_data);
        if($notify_data["dealer"]["fcm_key"]!=""){

            
            $payload = array();
            $data = array();
            $payload['title'] = "Welcome to Nova";
            $payload['body'] =  "Your order ".$order_string." placed successfully. Total cost of your order is : ".$order_value; /// Message goes here
            $payload['icon'] = "";  // Name of the icon in the play store
            $payload['click_action'] = "mainactivity";  // For android click activity
            $data['id'] = $order_id;  // For custom value if any
            $payload['to'] = $notify_data["dealer"]["fcm_key"];   // Receiver FCM id
            $data['role'] = "DEALER";   // For custom value if any
            $this->model_all->save(array("notification"=>"Your order ".$order_string." placed successfully. Total cost of your order is : ".$order_value,"notify_type"=>"placed","user_role"=>"DEALER","user_id"=>$user,"branch"=>$branch,"is_seen"=>"N","notifiy_on"=>date("Y-m-d H:i:s"),"related_id"=>$order_id),"notifications");

            $this->fcm->send( $payload['to'], $payload, $data);

        }

        if($notify_data["se"]["fcm_key"]!=""){
            $payload = array();
            $data = array();
            $body = "A new order ".$order_string." has been placed by ".$notify_data["dealer"]["company_name"]."(".$notify_data["dealer"]["dealer_code"]."). Total cost of the order is : ".$order_value;
            $payload['title'] = "Welcome to Nova";
            $payload['body'] =   $body; /// Message goes here
            $payload['icon'] = "";  // Name of the icon in the play store
            $payload['click_action'] = "mainactivity";  // For android click activity
            $data['id'] = $order_id;  // For custom value if any
            $payload['to'] = $notify_data["se"]["fcm_key"];   // Receiver FCM id
            $data['role'] = "SE";   // For custom value if any
             $this->model_all->save(array("notification"=>$body,"notify_type"=>"placed","user_role"=>"SE","user_id"=>$notify_data["se"]["id"],"branch"=>$branch,"is_seen"=>"N","notifiy_on"=>date("Y-m-d H:i:s"),"related_id"=>$order_id),"notifications");
            $this->fcm->send( $payload['to'], $payload, $data);

        }


        if($notify_data["FM"]["fcm_key"]!=""){
            $payload = array();
            $data = array();
            $body = "A new order ".$order_string." has been placed by ".$notify_data["dealer"]["company_name"]."(".$notify_data["dealer"]["dealer_code"]."). Total cost of the order is : ".$order_value;
            $payload['title'] = "Welcome to Nova";
            $payload['body'] =   $body; /// Message goes here
            $payload['icon'] = "";  // Name of the icon in the play store
            $payload['click_action'] = "mainactivity";  // For android click activity
            $data['id'] = $order_id;  // For custom value if any
            $payload['to'] = $notify_data["FM"]["fcm_key"];   // Receiver FCM id
            $data['role'] = "FM";   // For custom value if any
             $this->model_all->save(array("notification"=>$body,"notify_type"=>"placed","user_role"=>"FM","user_id"=>$notify_data["FM"]["id"],"branch"=>$branch,"is_seen"=>"N","notifiy_on"=>date("Y-m-d H:i:s"),"related_id"=>$order_id),"notifications");
            $this->fcm->send( $payload['to'], $payload, $data);

        }
        $this->response($result, 200);
        exit;
    }

}
