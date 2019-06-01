<?php

require(APPPATH . '/libraries/REST_Controller.php');

class Transferorders extends REST_Controller {

    public function __construct() {
        parent::__construct();
        $this->load->model('model_all');
        $this->alphas = range('A', 'Z');
        $this->load->library('fcm');
    }

    //API - Fetch All Pincodes
    function list_get() {
        $user = $this->get('user');
        $fromdate = $this->get('fromdate');
        $todate = $this->get('todate');
        $status = $this->get('status');
        $role = $this->get('role');

        $condition = " and order_id!=''";
        if ($role == "DEALER" && $user != "")
            $condition .= " and o.orderedby='$user'";
        else if ($role == "SE" && $user != "")
            $condition .= " and o.created_by='$user'";

        if ($status == "Cancelled") {
            $condition .= " and o.status='Cancelled'";
        } else if ($status == "Ordered") {
            $condition .= " and o.status='Ordered'";
        } else if ($status == "Delivered") {
            $condition .= " and o.status='Delivered' and o.seller_accept='1' ";  // and delivery_recieved='1'
        } else if ($status == "Received") {
            $condition .= " and o.status='Delivered' and o.dboy_accept='1' ";
        } else if ($status == "Rejected" || $status == "rejected") {
            $condition .= " and o.status='Delivered' and o.delivery_reject='1'";
        } else if ($status == "Pending") {
            $condition .= " and o.status not in ('Cancelled','Delivered','Ordered')";
        } else if ($status == "track") {
            $condition .= " and o.status not in ('Cancelled')";
        } else if ($status == "Denied") {
            $condition .= " and ((o.fa_status='2') or (o.fa_status='1' and  o.admin_status='2') or (o.fa_status='2' and o.admin_status='2'))";
        }

        $branch = $this->get('branch');
        if ($branch != "") {
            $condition .= " and o.branch_id='$branch'";
        }

        if ($fromdate != "" && $todate == "") {
            $fromdate = date("Y-m-d", strtotime($fromdate));
            $condition .= " and date(o.`orderedon`)='$fromdate'";
        } else if ($fromdate != "" && $todate != "") {
            $fromdate = date("Y-m-d", strtotime($fromdate));
            $todate = date("Y-m-d", strtotime($todate));
            $condition .= " and o.`orderedon` between '$fromdate 00:00:00' and '$todate 23:59:59'";
        }




        $result_set = $this->model_all->getTableDataFromQuery("select o.id, o.order_id, o.orderedon, o.status, o.orderedby, o.created_by,o.order_value,o.payment_type,o.credit_date,s.id as dealer_id,s.company_name as dealer_name,o.parent_order  from seller_orders o,sellers s where o.orderedby=s.id and o.is_transfered!='0' $condition order by o.orderedon desc");

        if ($result_set->num_rows() > 0) {
            $result["status"] = 1;
            $result["message"] = "Records Found";
            $result["total_records"] = $result_set->num_rows();



            foreach ($result_set->result_array() as $row) {
                if ($row["orderedby"] != $row["created_by"]) {

                    $emp_qry = $this->model_all->getTableDataFromQuery("select e.first_name as emp_name,e.mobile,b.name as branch_name from employees e,branches b where e.branch=b.id and e.id='$row[created_by]'");
                    if ($emp_qry->num_rows() > 0) {
                        $emp_rs = $emp_qry->row();
                        $row["takenby_name"] = $emp_rs->emp_name;
                        $row["takenby_branch"] = $emp_rs->branch_name;
                        $row["takenby_contact"] = $emp_rs->mobile;
                    } else {
                        $row["takenby_name"] = "-";
                        $row["takenby_branch"] = "-";
                        $row["takenby_contact"] = "-";
                    }
                } else {
                    $row["takenby_name"] = "Self";
                    $row["takenby_branch"] = "-";
                    $row["takenby_contact"] = "-";
                }
                if ($row['credit_date'] != "") {
                    $row['credit_date'] = date("d-m-Y", strtotime($row['credit_date']));
                }
                $row['orderedon'] = date("d-m-Y", strtotime($row['orderedon']));
                $result["records"][] = $row;
            }
            $this->response($result, 200);
            exit;
        } else {
            $result["status"] = 0;
            $result["message"] = "No records Found";
            $this->response($result, 200);
            exit;
        }
    }

    function details_get() {
        $order = $this->get('order');
        $status = $this->get('status');
        $condition = "";
        $req_table = "";
        $req_condition = "";
        $req_column = "";

        if ($status == "delivered") {
            $req_condition = "and sd.order_item_id=o.id";
            $req_table = ",seller_pack_details sd";
            $req_column = ",sd.delivered_qty";
            $condition = " and o.action_status='1'";
        }
        if ($status == "rejected") {
            $req_condition = "and sd.order_item_id=o.id";
            $req_table = ",seller_pack_details sd";
            $req_column = ",sd.delivered_qty";
            $condition = " and o.action_status='1'";
            $condition = " and ((o.action_status='2') or (o.action_status='1' and o.picked_qty<sd.delivered_qty))";
        }

        $transfered_order = $this->model_all->getTableDataFromQuery("select * from order_transfer_track where parent_order='$order' order by id limit 0,1");
        if ($transfered_order->num_rows() > 0) {
            $row = $transfered_order->row();
            $order = $row->order_id;
        }


        $result_set = $this->model_all->getTableDataFromQuery("SELECT o.* $req_column, b.name as seller,i.id as itemid,i.itemname,i.brand,bp.margin_price as sellingprice,bp.pay FROM `seller_order_items` o, items i ,branches b,branch_prices bp,item_prices ip 
 $req_table where bp.id=o.branch_price_id and bp.branch_id=b.id  and bp.itemprice_id = ip.id and ip.item_id=i.id and o.orderid='$order' $req_condition  $condition order by o.action_status asc");
        if ($result_set->num_rows() > 0) {


            $store_query = $this->model_all->getTableDataFromQuery("select CONCAT(s.first_name,s.last_name)  as name,a.pincode,a.address,a.door_no,a.street_name,a.landmark,a.city,d.district,st.state,o.remarks,o.status,o.est_date,o.est_time,o.delivery_charges,o.service_charge,o.fa_status,o.admin_status,o.payment_type,o.reference_no,o.credit_date,o.paid,s.mobile,s.contact1,o.orderedby,o.created_by,o.order_id,o.parent_order from sellers s,seller_orders o,addresses a,countries c,states st,districts d where s.id=o.orderedby and o.id='$order' and a.user_id=s.id and (a.user_role='seller' or a.user_role='DEALER') and a.is_default='1' and a.country=c.id and a.state=st.id and d.id=a.district  and st.country=c.id and d.state=st.id");




            $deliver_charges = 0.00;
            $total_sur_charge = 0.00;

            if ($store_rs = $store_query->row()) {

                $result["remarks"] = (!empty($store_rs->remarks)?$store_rs->remarks:"NA");
                /* For Denied Reason  */
                
                $result["denied_reason"] = "";
                $result["denied_person"] = "";
                if ($status == "Denied") {
                    if ($store_rs->fa_status == 2) {
                        //seller_orders_apprval_track
                        $req_qry = $this->model_all->getTableDataFromQuery("select r.rej_point,e.first_name,e.last_name from seller_orders_apprval_track s,rejection_points r,employees e where s.rej_for=r.id and s.status='2' and s.user_role='FM' and s.user_id=e.id order by s.id desc limit 0,1");
                        if ($req_qry->num_rows() > 0) {
                            $req_rs = $req_qry->row();
                            $result["denied_reason"] = $req_rs->rej_point;
                            $result["denied_person"] = ucwords($req_rs->first_name . " " . $req_rs->last_name);
                        }
                    } else if ($store_rs->admin_status == 2) {
                        $req_qry = $this->model_all->getTableDataFromQuery("select r.rej_point,e.first_name,e.last_name from seller_orders_apprval_track s,rejection_points r,employees e where s.rej_for=r.id and s.status='2' and s.user_role='ADMIN' and s.user_id=e.id order by s.id desc limit 0,1");
                        if ($req_qry->num_rows() > 0) {
                            $req_rs = $req_qry->row();
                            $result["denied_reason"] = $req_rs->rej_point;
                            $result["denied_person"] = ucwords($req_rs->first_name . " " . $req_rs->last_name);
                        }
                    }
                }



                /* For Denied Reason end */



                $result["parent_order"] = $store_rs->parent_order;
                $result["store_name"] = $store_rs->name;
                $result["order_status"] = $store_rs->status;
                $result["delivery_date"] = date("d-M-Y", strtotime($store_rs->est_date));
                $result["delivery_time"] = $store_rs->est_time;
                $deliver_charges = $store_rs->delivery_charges;
                $result["delivery_charges"] = $store_rs->delivery_charges;
                $total_sur_charge = $store_rs->service_charge;
                $seller_address = "";
                if ($store_rs->address == "") {
                    if ($store_rs->door_no != "" && $store_rs->door_no != "NA")
                        $seller_address .= $store_rs->door_no . ",";
                    if ($store_rs->street_name != "" && $store_rs->street_name != "NA") {
                        $seller_address .= $store_rs->street_name . ",";
                    }
                    if ($store_rs->landmark != "" && $store_rs->landmark != "NA") {
                        $seller_address .= $store_rs->landmark . ",";
                    }
                } else {
                    $seller_address .= $store_rs->address;
                }
                if ($store_rs->district != "" && $store_rs->district != "NA") {
                    $seller_address .= $store_rs->district . ",";
                }
                if ($store_rs->state != "" && $store_rs->state != "NA") {
                    $seller_address .= $store_rs->state . ",";
                }
                if ($store_rs->pincode != "" && $store_rs->pincode != "NA") {
                    $seller_address .= " Pin: " . $store_rs->pincode . ".";
                }

                $result["store_address"] = $seller_address;
                $result['order_id'] = $store_rs->order_id;
                $result['fa_status'] = $store_rs->fa_status;
                $result['admin_status'] = $store_rs->admin_status;

                $result['fa_status'] = $store_rs->fa_status;
                $result['payment_type'] = $store_rs->payment_type;
                $result['reference_no'] = $store_rs->reference_no;
                if ($store_rs->credit_date != "") {
                    $result['credit_date'] = date("d-M-y", strtotime($store_rs->credit_date));
                } else {
                    $result['credit_date'] = "";
                }

                $result['paid'] = $store_rs->paid;

                $result['mobile'] = "NA";
                if ($store_rs->mobile != "" && $store_rs->mobile != 0) {
                    $result['mobile'] = $store_rs->mobile;
                } else if ($store_rs->contact1 != "" && $store_rs->contact1 != 0) {
                    $result['mobile'] = $store_rs->contact1;
                }
                if ($store_rs->orderedby != $store_rs->created_by) {

                    $emp_qry = $this->model_all->getTableDataFromQuery("select e.first_name as emp_name,e.mobile,e.ofc_contact,b.name as branch_name from employees e,branches b where e.branch=b.id and e.id='$store_rs->created_by'");
                    if ($emp_qry->num_rows() > 0) {
                        $emp_rs = $emp_qry->row();
                        $result["takenby_name"] = $emp_rs->emp_name;
                        $result["takenby_branch"] = $emp_rs->branch_name;
                        if ($emp_rs->ofc_contact != "") {
                            $result["takenby_contact"] = $emp_rs->ofc_contact;
                        } else {
                            $result["takenby_contact"] = $emp_rs->mobile;
                        }
                    } else {
                        $result["takenby_name"] = "-";
                        $result["takenby_branch"] = "-";
                        $result["takenby_contact"] = "-";
                    }
                } else {
                    $result["takenby_name"] = "Self";
                    $result["takenby_branch"] = "-";
                    $result["takenby_contact"] = "-";
                }
            }








            $result["status"] = 1;
            $result["message"] = "Records Found";
            $result["total_rows"] = $result_set->num_rows();
            $total_units = 0;
            $total_savings = 0;
            $total_pay = 0;
            $total_items = 0;
            foreach ($result_set->result_array() as $row) {




                $row['images'] = array();
                $image_qry = $this->model_all->getTableData("item_images", array("item" => $row['itemid']));
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

                if ($status == "rejected" || $status == "delivered") {
                    if ($row["action_status"] = '1') {

                        if ($row["picked_qty"] < $row["delivered_qty"]) {
                            $row["qty"] = $row["delivered_qty"] - $row["picked_qty"];
                        } else {
                            $row["qty"] = $row["delivered_qty"];
                        }
                    }
                    if ($row["action_status"] = '2') {
                        $row["qty"] = $row["picked_qty"];
                    }
                }



                $row['discount'] = ($row['mrp'] - $row['paid']);
                $row['total_price'] = $row['total_cost']; // ($row['qty'] * $row['paid']);
                $row['margin'] = round((($row['mrp'] - $row['paid']) / $row['paid']) * 100, 2);
                if ($status != "rejected" && $row['action_status'] != '2') {
                    $total_units += $row['qty'];
                    $total_savings += ($row['mrp'] - $row['paid']) * $row['qty'];
                    $total_pay += $row['total_price']; //($row['qty'] * $row['paid']);
                    $total_items++;
                } else if ($status == "rejected") {
                    $total_units += $row['qty'];
                    $total_savings += ($row['mrp'] - $row['paid']) * $row['qty'];
                    $total_pay += $row['total_price'];  // ($row['qty'] * $row['paid']);
                    $total_items++;
                }
                //$total_sur_charge += 0.00;
                $row['tax_string'] = "No Tax";
                if ($row['tax_type'] == "gst") {
                    $row['qty'] = $row['qty'] . "\n + \n " . $row['sgst'] . " % SGST + " . $row['cgst'] . " % CGST";
                } else if ($row['tax_type'] == "igst") {
                    $row['qty'] = $row['qty'] . "\n + \n " . $row['igst'] . " % IGST";
                }



                $result["records"][] = $row;
            }
            $result['total_units'] = $total_units;
            $result['total_items'] = $total_items;
            $result['total_savings'] = $total_savings;
            $result['total_sur_charge'] = $total_sur_charge;
            $result['sub_total'] = $total_pay;
            $result['total_pay'] = ($total_pay + $total_sur_charge + $deliver_charges);
            $this->response($result, 200);
            exit;
        } else {
            $result["status"] = 0;
            $result["message"] = "No records Found";
            $this->response($result, 200);
            exit;
        }
    }

    function transfer_post() {
        $order = $this->post('order');
        $user = $this->post('user');
        $branch = $this->post('branch');
        $reason = $this->post('reason');
        $dt = date("Y-m-d H:i:s");
        $json_qty = $this->post('item_qty');
        $json_encode_array = json_decode($json_qty, true);


        $new_order = 0;
        $super_parent = 0;
        $super_parent_id = 0;
        $dealer = 0;
        $total_value_pay = 0;
        $delivery_date = date('Y-m-d', strtotime("+4 day"));
        $est_time = date('H:i:s');
        $flag = true;
        $temp_order = $order;
        $previous_count = 0;
        $old_branch = 0;
        $old_branch_name = "";
        $new_branch_name = "";
        $total_transfer_count = 0;
        $total_original_count = 0;
        while ($flag) {
            $order_qry = $this->model_all->getTableDataFromQuery("select order_id,id,parent_order from seller_orders  where id='$temp_order'");
            if ($order_qry->num_rows() > 0) {
                $order_rs = $order_qry->row();
                $super_parent = $order_rs->parent_order;
                $super_parent_id = $order_rs->order_id;

                if ($order_rs->parent_order == 0) {
                    $flag = FALSE;
                } else {
                    $previous_count++;
                    $temp_order = $order_rs->parent_order;
                }
            }
        }





        $order_qry = $this->model_all->getTableDataFromQuery("select s.*,b.name as branch_nm from seller_orders s,branches b  where s.id='$order' and s.branch_id=b.id");
        if ($order_qry->num_rows() > 0) {
            foreach ($order_qry->result() as $order_row) {

                if ($super_parent == 0) {
                    $super_parent = $order_row->id;
                    $super_parent_id = $order_row->order_id;
                }



                $old_branch = $order_row->branch_id;
                $old_branch_name = $order_row->branch_nm;
                $payment_type = $order_row->payment_type;
                $reference_no = $order_row->reference_no;
                $dealer = $order_row->orderedby;
                $transport = $order_row->transport_id;
                $remarks = $order_row->remarks;
                // Getting Branch Details
                $branch_qry = $this->model_all->getTableDataFromQuery("select o.state,b.name from offices o,branches b,companies c,states s where o.id=b.office_id and b.id='$branch' and b.company=c.company_id and o.state=s.id");
                if ($branch_qry->num_rows() > 0) {
                    foreach ($branch_qry->result() as $branch_row) {
                        $branch_state = $branch_row->state;
                        $new_branch_name = $branch_row->name;
                    }
                }

                // Getting Dealer Details

                $dealer_qry = $this->model_all->getTableDataFromQuery("select s.id as state_id from sellers se,addresses a,countries c,states s,districts d where se.id='$dealer' and  a.user_id=se.id and (a.user_role='seller' or a.user_role='DEALER') and a.is_default='1' and a.country=c.id and a.state=s.id and d.id=a.district  and s.country=c.id and d.state=s.id");
                if ($dealer_qry->num_rows() > 0) {
                    foreach ($dealer_qry->result() as $dealer_row) {
                        $dealer_state = $dealer_row->state_id;
                    }
                }


                $forward_by = $user;

                $seller_item_value = 0.00;
                $service_charge = 0.00;
                $paid = 0.00;
                $company = $order_row->company_id;
                $rem_qty = 0;
                $rem_item_pay = 0.00;
                $rem_item_total_cost = 0.00;
                $order_string = $order_row->order_id . "-" . $this->alphas[$previous_count];


                $data = array("order_id" => $order_string, "company_id" => $company, "orderedby" => $dealer, "parent_order" => $order, "flat_no" => $order_row->flat_no, "address_id" => $order_row->address_id, "address" => $order_row->address, "landmark" => $order_row->landmark, "latitude" => $order_row->latitude, "longitude" => $order_row->longitude, "orderedon" => $dt, "deliveredby" => 0, "deliveredon" => '', "dboy_accept" => 0, "seller_accept" => 0, "est_date" => $delivery_date, "est_time" => $est_time, "service_charge" => $service_charge, "delivery_charges" => "0.00", "payment_type" => $payment_type, "credit_date" => $order_row->credit_date, "created_by" => $user, "branch_id" => $branch, "reference_no" => $reference_no, "forward_by" => $forward_by,"transport_id"=>$transport,"remarks"=>$remarks);
                $new_order = $this->model_all->save($data, "seller_orders");
                if ($new_order > 0) {

                    if ($branch_state == $dealer_state) {
                        $tax_type = "gst";
                    } else {
                        $tax_type = "igst";
                    }

                    // Storing track information
                    $tarck_data = array("order_id" => $new_order, "parent_order" => $order, "super_parent" => $super_parent, "transfer_branch" => $branch, "transfer_by" => $user, "transfer_reason" => $reason, "transfered_on" => $dt);
                    $this->model_all->save($tarck_data, "order_transfer_track");

                    //$this->model_all->model_all->getTableDataFromQuery("select count(*) from where order_transfer_track where super_parent='$super_parent'");
                    $this->model_all->save(array("order_id" => $order, "order_status" => 'Transferred', "changed_on" => $dt), 'seller_order_track');
                    $this->model_all->save(array("order_id" => $new_order, "order_status" => 'Ordered', "changed_on" => $dt), 'seller_order_track');


                    foreach ($json_encode_array as $key => $value) {

                        $item_query = $this->model_all->getTableDataFromQuery("select * from seller_order_items where id='$key'");
                        if ($item_query->num_rows() > 0) {

                            $item_row = $item_query->row();
                            /*  For given single piece qty */
                            $sellingprice = $item_row->company_mrp * $item_row->pack_qty; //sellingprice
                            $item_qty = $value;
                            /*  For given single piece qty */

                            $item_pay = $item_row->paid * $item_row->pack_qty;
                            $rem_qty = $item_row->qty - $item_qty;
                            $rem_item_pay = $item_row->paid * $item_row->pack_qty;
                            $scharge = 0.00;


                            if ($item_row->tax_type == "gst") {
                                $rem_item_total_cost = $rem_item_total_cost + (($rem_item_pay + (round((($item_row->sgst * $rem_item_pay) / 100) + ($item_row->cgst * $rem_item_pay) / 100, 2))) * $rem_qty);
                            } else if ($item_row->tax_type == "igst") {
                                $rem_item_total_cost = $rem_item_total_cost + (($rem_item_pay + round(($item_row->igst * $rem_item_pay) / 100, 2)) * $rem_qty);
                            }




                            $branch_price_qry = $this->model_all->getTableDataFromQuery("select * from  branch_prices where id='$item_row->branch_price_id'");
                            if ($branch_price_qry->num_rows() > 0) {
                                $branch_price_rs = $branch_price_qry->row();
                                $itemprice_id = $branch_price_rs->itemprice_id;

                                $new_brachprice_qry = $this->model_all->getTableDataFromQuery("select b.*,i.igst,i.sgst,i.cgst from branch_prices b,item_prices i where b.itemprice_id='$itemprice_id' and b.branch_id='$branch' and b.itemprice_id=i.id");
                                if ($new_brachprice_qry->num_rows() > 0) {
                                    $new_brachprice_rs = $new_brachprice_qry->row();
                                    $new_branch_price_id = $new_brachprice_rs->id;
                                }

                                if ($tax_type == "gst") {
                                    $igst = 0.00;
                                    $sgst = $new_brachprice_rs->sgst;
                                    $cgst = $new_brachprice_rs->cgst;
                                } else {
                                    $igst = $new_brachprice_rs->igst;
                                    $sgst = 0.00;
                                    $cgst = 0.00;
                                }

                                if ($tax_type == "gst") {
                                    $item_total_cost = ($item_pay + (round((($sgst * $item_pay) / 100) + ($cgst * $item_pay) / 100, 2))) * $item_qty;
                                } else if ($tax_type == "igst") {
                                    $item_total_cost = ($item_pay + (round(($igst * $item_pay) / 100, 2))) * $item_qty;
                                }





                                $item_sp_amt = $sellingprice * $item_qty;
                                $this->model_all->update(array("transfer_qty" => $value, "transfered_by" => $user), array("id" => $item_row->id), "seller_order_items");
                                $item_data = array("branch_price_id" => $new_branch_price_id, "orderid" => $new_order, "qty" => $value, "company_mrp" => $item_row->company_mrp, "mrp" => $item_row->mrp, "paid" => $item_row->paid, "pack_qty" => $item_row->pack_qty, "service_charge" => $item_row->service_charge, "amount" => $item_pay, "tax_type" => $tax_type, "igst" => $igst, "cgst" => $cgst, "sgst" => $sgst, "total_cost" => $item_total_cost, "sp" => $sellingprice, "sp_amount" => $item_sp_amt, "created_on" => $dt);

                                $order_item_id = $this->model_all->save($item_data, "seller_order_items");

                                if ($order_item_id > 0) {
                                    $total_transfer_count = $total_transfer_count + $value;
                                    $total_value_pay += ($item_total_cost) + $scharge;
                                }
                            }
                        }
                    }


                    if ($order_row->is_transfered == 0) {
                        $result_set1 = $this->model_all->getTableDataFromQuery("SELECT sum(qty) as total_qty FROM seller_order_items WHERE orderid ='$order'");
                        $total_original_count = $result_set1->row()->total_qty;
                        if (empty($total_original_count)) {
                            $total_original_count = 0;
                        }

                        if ($total_original_count == $total_transfer_count) {
                            $this->model_all->update(array("is_transfered" => '2'), array("id" => $order), "seller_orders");
                        } else {
                            $this->model_all->update(array("is_transfered" => '1'), array("id" => $order), "seller_orders");
                        }
                    }



                    $order_value = $total_value_pay;
                    $transaction_no = $order_string;
                    $transaction_mode = "debit";
                    $payment_type = "";
                    $createdby = $user;
                    $action_role = "trade";
                    $delivery_charge = 0.00;
                    //$this->model_all->getTableDataFromQuery("update sellers set wallet=wallet-$order_value where id='$dealer' ");
                    $this->model_all->update(array("order_value" => $order_value, "final_value" => $order_value, "delivery_charges" => $delivery_charge), array("id" => $new_order), "seller_orders");
                    $this->model_all->update(array("final_value" => $rem_item_total_cost), array("id" => $order), "seller_orders");
                    $this->model_all->save(array("order_id" => $order, "stage" => "transfer", "action_by" => $user, "action_amount" => $rem_item_total_cost, "description" => "Due to in sufficient stock the order has transfered"), "seller_order_amount_track");
                    if ($payment_type != "") {
                        $this->model_all->save(array('order_id' => $new_order, 'profile_id' => $dealer, 'profile_role' => 'seller', 'amount' => $order_value, 'status' => 1, 'transaction_type' => $payment_type, 'reference_no' => $reference_no), "transaction_track");
                    }
                    $this->model_all->save(array("user_id" => $branch, "user_role" => 'branch', "prev_balance" => 0.00, "branch" => $branch, "order_id" => $new_order, "particular" => "Sale-" . $order_string, "amount" => $order_value, "reference_no" => $order_string, "transaction_no" => $transaction_no, "transaction_mode" => $transaction_mode, "payment_mode" => $payment_type, "transaction_date" => $dt, "action_by" => $createdby, "action_role" => $action_role, "action_date" => $dt, 'status' => '1'), "wallet_history");



                    $dt = date("Y-m-d H:i:s");
                    $user = $dealer;
                    $order_string = $order_string;
                    $particular = "Due to insufficient stock , the order has transfered to " . $new_branch_name . "  which costs " . $order_value;

                    $notify_data = $this->model_all->getDealerExecutive($dealer, $old_branch);

                    if ($notify_data["dealer"]["fcm_key"] != "") {


                        $payload = array();
                        $data = array();
                        $payload['title'] = "Welcome to Nova";
                        $payload['body'] = $particular;
                        $payload['icon'] = "";  // Name of the icon in the play store
                        $payload['click_action'] = "mainactivity";  // For android click activity
                        $data['id'] = $new_order;  // For custom value if any
                        $payload['to'] = $notify_data["dealer"]["fcm_key"];   // Receiver FCM id
                        $data['role'] = "DEALER";   // For custom value if any
                        $this->model_all->save(array("notification" => $particular, "notify_type" => "transfered", "user_role" => "DEALER", "user_id" => $user, "branch" => $old_branch, "is_seen" => "N", "notifiy_on" => date("Y-m-d H:i:s"), "related_id" => $order), "notifications");

                        $this->fcm->send($payload['to'], $payload, $data);
                    }

                    if ($notify_data["se"]["fcm_key"] != "") {
                        $payload = array();
                        $data = array();
                        $body = "The order " . $order_string . " has been transferred by " . $notify_data["FM"]["first_name"] . "," . $old_branch_name . " Total cost of the transfer order is : " . $order_value . " due to in sufficient stock.";
                        $payload['title'] = "Welcome to Nova";
                        $payload['body'] = $body; /// Message goes here
                        $payload['icon'] = "";  // Name of the icon in the play store
                        $payload['click_action'] = "mainactivity";  // For android click activity
                        $data['id'] = $new_order;  // For custom value if any
                        $payload['to'] = $notify_data["se"]["fcm_key"];   // Receiver FCM id
                        $data['role'] = "SE";   // For custom value if any
                        $this->model_all->save(array("notification" => $body, "notify_type" => "transfered", "user_role" => "SE", "user_id" => $notify_data["se"]["id"], "branch" => $old_branch, "is_seen" => "N", "notifiy_on" => date("Y-m-d H:i:s"), "related_id" => $order), "notifications");
                        $this->fcm->send($payload['to'], $payload, $data);
                    }


                    $seller_data = $this->model_all->getBranchDM($branch);
                    if ($seller_data["FM"]["fcm_key"] != "") {
                        $payload = array();
                        $data = array();
                        $body = "The order " . $order_string . " has been transferred  by " . $notify_data["FM"]["first_name"] . "," . $old_branch_name . ". Total cost of the transfer order is : " . $order_value;
                        $payload['title'] = "Welcome to Nova";
                        $payload['body'] = $body; /// Message goes here
                        $payload['icon'] = "";  // Name of the icon in the play store
                        $payload['click_action'] = "mainactivity";  // For android click activity
                        $data['id'] = $new_order;  // For custom value if any
                        $payload['to'] = $seller_data["FM"]["fcm_key"];   // Receiver FCM id
                        $data['role'] = "FM";   // For custom value if any
                        $this->model_all->save(array("notification" => $body, "notify_type" => "transfer", "user_role" => "FM", "user_id" => $seller_data["FM"]["id"], "branch" => $branch, "is_seen" => "N", "notifiy_on" => date("Y-m-d H:i:s"), "related_id" => $new_order), "notifications");
                        $this->fcm->send($payload['to'], $payload, $data);
                    }
                }
            }

            if ($new_order > 0) {
                $result["status"] = 1;
                $result["message"] = "The order has been transferred.";
            } else {
                $result["status"] = 0;
                $result["message"] = "Something went wrong please try later. ";
            }
        } else {
            $result["status"] = 0;
            $result["message"] = "Invalid Details";
        }





























        $this->response($result, 200);
        exit;
    }

    function track_get() {
        $order = $this->get('order');
        $status_qry = $this->model_all->getTableData("seller_orders", array("id" => $order), "status");
        // echo $this->db->last_query();
        if ($status_qry->num_rows() > 0) {
            $status_rs = $status_qry->row_array();
            $result["order_status"] = $status_rs['status'];
            $track_data = $this->model_all->getTableData('seller_order_track', array("order_id" => $order), "order_status,changed_on,comments", "changed_on", "asc");
            foreach ($track_data->result_array() as $row) {
                $row['changed_on'] = date("d-m-Y h:i A", strtotime($row['changed_on']));
                $row['comments'] = ($row['comments'] != "") ? $row['comments'] : "";
                $result["track"][] = $row;
            }
            $result["status"] = 1;
            $result["message"] = "Records Found";
        } else {
            $result["status"] = 0;
            $result["message"] = "No Such order found";
        }



        $this->response($result, 200);
        exit;
    }

    //API - Fetch All Pincodes
    function daylist_get() {
        $condition = "";
        $fromdate = $this->get('fromdate');
        $todate = $this->get('todate');
        $status = $this->get('status');
        $store = $this->get('store');
        $total_cost = 0.00;

        if ($status != "") {
            if ($status == "Delivered") {
                $condition .= " and o.status='Delivered' and o.delivery_accept='1' ";  // and delivery_recieved='1'
            } else if ($status == "Rejected") {
                $condition .= " and o.status='Delivered' and o.delivery_reject='1'";
            } else {
                $condition .= " and o.status='$status'";
            }
        };


        if ($store != "")
            $condition .= " and o.orderedby='$store'";
        if ($fromdate != "" && $todate == "") {
            $fromdate = date("Y-m-d", strtotime($fromdate));
            if ($status == "Delivered") {
                $condition .= " and o.`deliveredon` between '$fromdate 00:00:00' and '$fromdate 23:59:59'";
            } else {
                $condition .= " and o.`orderedon` between '$fromdate 00:00:00' and '$fromdate 23:59:59'";
            }
        } else if ($fromdate != "" && $todate != "") {
            $fromdate = date("Y-m-d", strtotime($fromdate));
            $todate = date("Y-m-d", strtotime($todate));
            if ($status == "Delivered") {
                $condition .= " and o.`deliveredon` between '$fromdate 00:00:00' and '$todate 23:59:59'";
            } else {
                $condition .= " and o.`orderedon` between '$fromdate 00:00:00' and '$todate 23:59:59'";
            }
        } else if ($fromdate == "" && $todate == "") {

            $fromdate = date("Y-m-d");
            if ($status == "Delivered") {
                $condition .= " and o.`deliveredon` between '$fromdate 00:00:00' and '$fromdate 23:59:59'";
            } else {
                $condition .= " and o.`orderedon` between '$fromdate 00:00:00' and '$fromdate 23:59:59'";
            }
        }
        // echo "select o.id,o.order_id,o.`orderedon`,s.name as store,o.order_value  from orders o,stores s where o.orderedby = s.id $condition";
        $result_set = $this->model_all->getTableDataFromQuery("select o.id,o.order_id,o.`orderedon`,s.name as store,o.order_value  from orders o,stores s where o.orderedby = s.id $condition");
        //echo $this->db->last_query();
        if ($result_set->num_rows() > 0) {
            $result["status"] = 1;
            $result["message"] = "Records Found";
            $result["total_records"] = $result_set->num_rows();
            foreach ($result_set->result_array() as $row) {
                $row['orderedon'] = date("d-m-Y", strtotime($row['orderedon']));
                $total_cost = $total_cost + $row['order_value'];
                $result["records"][] = $row;
            }
            $result["total_cost"] = "Rs " . $total_cost . " /-";

            $this->response($result, 200);
            exit;
        } else {
            $result["status"] = 0;
            $result["message"] = "No records Found";
            $this->response($result, 200);
            exit;
        }
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
            $affected_rows = $this->model_all->update(array("picked_qty" => $qty, "action_status" => '1', "action_time" => $dt, "reason" => $reason, "description" => $description), array("id" => $item), "seller_order_items");
            if ($affected_rows) {
                $message = "Item Received successfully";
                $action_status = 1;
                $this->model_all->update(array("seller_accept" => '1'), array("id" => $order), "seller_orders");
            }
        } else if ($action == 2) {
            $img_name = "";
            if ($_FILES['rej_img']['size'] > 0 && $_FILES['rej_img']['error'] == 0) {
                $name = "store_" . $item . "_" . time() . "_" . $_FILES['rej_img']['name'];
                $source_url = $_FILES['rej_img']['tmp_name'];
                $destination_url = "rejections/seller_" . $name;
                if (@move_uploaded_file($source_url, $destination_url)) {
                    $img_name = $name;
                } else {
                    $img_name = "";
                }
            }
            $affected_rows = $this->model_all->update(array("picked_qty" => $qty, "action_status" => '2', "action_time" => $dt, "reason" => $reason, "description" => $description, "action_img" => $img_name), array("id" => $item), "seller_order_items");
            if ($affected_rows) {
                $this->model_all->update(array("delivery_reject" => '1'), array("id" => $order), "seller_orders");
                //   echo $this->db->last_query();
                $message = "Item rejected successfully";
                $action_status = 1;
                $this->model_all->update(array("seller_accept" => '1'), array("id" => $order), "seller_orders");
            }
        }

        $result_set1 = $this->model_all->getTableDataFromQuery("SELECT id FROM seller_order_items WHERE orderid ='$order'");
        $result['total_records'] = $result_set1->num_rows();
        $result_set2 = $this->model_all->getTableDataFromQuery("SELECT id FROM seller_order_items where orderid ='$order' and action_status!='0'");
        $result['total_processed'] = $result_set2->num_rows();
        if ($result['total_records'] == $result['total_processed']) {
            $this->model_all->update(array("seller_accept" => '1'), array("id" => $order), "seller_orders");
        }


        $result["status"] = $action_status;
        $result["message"] = $message;
        $this->response($result, 200);
        exit;
    }

    function final_details_get() {
        $order = $this->get('order');
        $status = $this->get('status');
        $condition = "";
        if ($status == "delivered") {
            $condition = " and o.action_status='1'";
        }
        if ($status == "rejected") {
            $condition = " and ((o.action_status='2') or (o.action_status='1' and o.picked_qty<sd.delivered_qty))";
        }
        $sub_status = 0;
        $result_set = $this->model_all->getTableDataFromQuery("SELECT o.*,sd.delivered_qty, b.name as seller,i.id as itemid,i.itemname,i.brand,bp.margin_price as sellingprice,bp.pay FROM `seller_order_items` o, items i ,branches b,branch_prices bp,item_prices ip,seller_pack_details sd where bp.id=o.branch_price_id and bp.branch_id=b.id  and bp.itemprice_id = ip.id and ip.item_id=i.id and o.orderid='$order' and o.action_status!='2' and sd.order_item_id=o.id $condition order by o.action_status asc");
        if ($result_set->num_rows() > 0) {

            $deliver_charges = 0.00;
            $total_sur_charge = 0.00;

            $result["total_rows"] = $result_set->num_rows();
            $total_units = 0;
            $total_savings = 0;
            $total_pay = 0;
            $total_items = 0;
            $sub_status = 1;
            foreach ($result_set->result_array() as $row) {
                $row['images'] = array();
                if ($row["picked_qty"] < $row["delivered_qty"]) {
                    $row["picked_qty"] = $row["picked_qty"];
                }
                $image_qry = $this->model_all->getTableData("item_images", array("item" => $row['itemid']));
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


                $row['discount'] = ($row['mrp'] - $row['amount']);
                $row['total_price'] = ($row['qty'] * $row['amount']);
                $row['margin'] = round((($row['mrp'] - $row['amount']) / $row['amount']) * 100, 2);
                if ($status != "rejected" && $row['action_status'] != '2') {
                    $total_units += $row['qty'];
                    $total_savings += ($row['mrp'] - $row['amount']) * $row['qty'];
                    $total_pay += ($row['qty'] * $row['amount']);
                    $total_items++;
                } else if ($status == "rejected") {
                    $total_units += $row['qty'];
                    $total_savings += ($row['mrp'] - $row['amount']) * $row['qty'];
                    $total_pay += ($row['qty'] * $row['amount']);
                    $total_items++;
                }
                //$total_sur_charge += 0.00;
                $row['tax_string'] = "No Tax";
                if ($row['tax_type'] == "gst") {
                    $row['qty'] = $row['qty'] . "\n + \n " . $row['sgst'] . " % SGST + " . $row['cgst'] . " % CGST";
                } else if ($row['tax_type'] == "igst") {
                    $row['qty'] = $row['qty'] . "\n + \n " . $row['igst'] . " % IGST";
                }



                $result["records"][] = $row;
            }
            $result['total_units'] = $total_units;
            $result['total_items'] = $total_items;
            $result['total_savings'] = $total_savings;
            $result['total_sur_charge'] = $total_sur_charge;
            $result['sub_total'] = $total_pay;
            $result['total_pay'] = ($total_pay + $total_sur_charge + $deliver_charges);
        }


        $result_set = $this->model_all->getTableDataFromQuery("SELECT o.*,sd.delivered_qty, b.name as seller,i.id as itemid,i.itemname,i.brand,bp.margin_price as sellingprice,bp.pay FROM `seller_order_items` o, items i ,branches b,branch_prices bp,item_prices ip,seller_pack_details sd where bp.id=o.branch_price_id and bp.branch_id=b.id  and bp.itemprice_id = ip.id and ip.item_id=i.id and o.orderid='$order' and (o.action_status='2' or (o.action_status='1' and  o.picked_qty<sd.delivered_qty)) and sd.order_item_id=o.id $condition order by o.action_status asc");
        if ($result_set->num_rows() > 0) {

            $deliver_charges = 0.00;
            $total_sur_charge = 0.00;


            $result["total_rows"] = $result_set->num_rows();
            $total_units = 0;
            $total_savings = 0;
            $total_pay = 0;
            $total_items = 0;
            $sub_status = 1;
            foreach ($result_set->result_array() as $row) {

                if ($row["picked_qty"] < $row["delivered_qty"]) {
                    $row["qty"] = $row["delivered_qty"] - $row["picked_qty"];
                }
                $row['images'] = array();
                $image_qry = $this->model_all->getTableData("item_images", array("item" => $row['itemid']));
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


                $row['discount'] = ($row['mrp'] - $row['amount']);
                $row['total_price'] = ($row['qty'] * $row['amount']);
                $row['margin'] = round((($row['mrp'] - $row['amount']) / $row['amount']) * 100, 2);
                if ($status == "rejected") {
                    $total_units += $row['qty'];
                    $total_savings += ($row['mrp'] - $row['amount']) * $row['qty'];
                    $total_pay += ($row['qty'] * $row['amount']);
                    $total_items++;
                }
                //$total_sur_charge += 0.00;
                $result["rejected_records"][] = $row;
            }
            /* $result['rejected_units'] = $total_units;
              $result['rejected_items'] = $total_items;
              $result['rejected_savings'] = $total_savings;
              $result['rej_total_sur_charge'] = $total_sur_charge;
              $result['rej_sub_total'] = $total_pay;
              $result['reg_total_pay'] = ($total_pay + $total_sur_charge + $deliver_charges); */
        }
        $result["status"] = $sub_status;
        if ($result["status"] == 1) {
            $result["message"] = "Records Found";
            $store_query = $this->model_all->getTableDataFromQuery("select CONCAT(s.first_name,s.last_name)  as name,a.pincode,a.address,a.door_no,a.street_name,a.landmark,a.city,d.district,st.state,o.status,o.est_date,o.est_time,o.delivery_charges,o.service_charge,o.fa_status,o.admin_status,o.payment_type,o.reference_no,o.credit_date,o.paid,s.mobile,s.contact1 from sellers s,seller_orders o,addresses a,countries c,states st,districts d where s.id=o.orderedby and o.id='$order' and a.user_id=s.id and (a.user_role='seller' or a.user_role='DEALER') and a.is_default='1' and a.country=c.id and a.state=st.id and d.id=a.district  and st.country=c.id and d.state=st.id");
            if ($store_rs = $store_query->row()) {
                $result["store_name"] = $store_rs->name;
                $result["order_status"] = $store_rs->status;
                $result["delivery_date"] = date("d-M-Y", strtotime($store_rs->est_date));
                $result["delivery_time"] = $store_rs->est_time;
                $deliver_charges = $store_rs->delivery_charges;
                $result["delivery_charges"] = $store_rs->delivery_charges;
                $total_sur_charge = $store_rs->service_charge;
                $seller_address = "";
                if ($store_rs->address == "") {
                    if ($store_rs->door_no != "" && $store_rs->door_no != "NA")
                        $seller_address .= $store_rs->door_no . ",";
                    if ($store_rs->street_name != "" && $store_rs->street_name != "NA") {
                        $seller_address .= $store_rs->street_name . ",";
                    }
                    if ($store_rs->landmark != "" && $store_rs->landmark != "NA") {
                        $seller_address .= $store_rs->landmark . ",";
                    }
                } else {
                    $seller_address .= $store_rs->address;
                }
                if ($store_rs->district != "" && $store_rs->district != "NA") {
                    $seller_address .= $store_rs->district . ",";
                }
                if ($store_rs->state != "" && $store_rs->state != "NA") {
                    $seller_address .= $store_rs->state . ",";
                }
                if ($store_rs->pincode != "" && $store_rs->pincode != "NA") {
                    $seller_address .= " Pin: " . $store_rs->pincode . ".";
                }

                $result["store_address"] = $seller_address;
                $result['fa_status'] = $store_rs->fa_status;
                $result['admin_status'] = $store_rs->admin_status;

                $result['fa_status'] = $store_rs->fa_status;
                $result['payment_type'] = $store_rs->payment_type;
                if ($store_rs->credit_date != "") {
                    $result['credit_date'] = date("d-M-y", strtotime($store_rs->credit_date));
                } else {
                    $result['credit_date'] = "";
                }

                $result['paid'] = $store_rs->paid;
                $result['reference_no'] = $store_rs->reference_no;
                $result['mobile'] = "NA";
                if ($store_rs->mobile != "" && $store_rs->mobile != 0) {
                    $result['mobile'] = $store_rs->mobile;
                } else if ($store_rs->contact1 != "" && $store_rs->contact1 != 0) {
                    $result['mobile'] = $store_rs->contact1;
                }
            }
        } else {
            $result["status"] = 0;
            $result["message"] = "No records Found";
        }


        $this->response($result, 200);
        exit;
    }

    function delivery_details_get() {
        $order = $this->get('order');
        $status = $this->get('status');
        $condition = "";
        if ($status == "delivered") {
            $condition = " and o.action_status='1'";
        }
        if ($status == "rejected") {
            $condition = " and ((o.action_status='2') or (o.action_status='1' and o.picked_qty<sd.delivered_qty))";
        }
        $sub_status = 0;


        $result_set = $this->model_all->getTableDataFromQuery("SELECT o.*,sd.delivered_qty, b.name as seller,i.id as itemid,i.itemname,i.brand,bp.margin_price as sellingprice,bp.pay FROM `seller_order_items` o, items i ,branches b,branch_prices bp,item_prices ip,seller_pack_details sd where bp.id=o.branch_price_id and bp.branch_id=b.id  and bp.itemprice_id = ip.id and ip.item_id=i.id and o.orderid='$order' and o.action_status!='2' and sd.order_item_id=o.id and sd.packed_qty!=0 and sd.status='1' $condition order by o.action_status asc");
        if ($result_set->num_rows() > 0) {

            $deliver_charges = 0.00;
            $total_sur_charge = 0.00;

            $result["total_rows"] = $result_set->num_rows();
            $total_units = 0;
            $total_savings = 0;
            $total_pay = 0;
            $total_items = 0;
            $sub_status = 1;
            foreach ($result_set->result_array() as $row) {


                $row['images'] = array();
                $image_qry = $this->model_all->getTableData("item_images", array("item" => $row['itemid']));
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

                // mrp -> mrp , pay->amount , profit-> discount , margin -> margin , total_cost->total_price





                if ($status != "rejected" && $row['action_status'] != '2') {

                    if ($row['action_status'] == '0') {
                        $row['mrp'] = $row['mrp'] * $row['pack_qty'];
                        $row['paid'] = $row['paid'] * $row['pack_qty'];
                        // $row['amount'] =  $row['paid']*$row['pack_qty'];
                        $row['discount'] = ($row['mrp'] - $row['paid']);
                        $row['margin'] = round((($row['mrp'] - $row['paid']) / $row['paid']) * 100, 2);
                        $row['total_price'] = ($row['delivered_qty'] * $row['paid']);
                        $total_units += $row['delivered_qty'];
                        $total_savings += ($row['mrp'] - $row['paid']) * $row['delivered_qty'];
                        $total_pay += ($row['delivered_qty'] * $row['paid']);
                    } else if ($row['action_status'] == '1') {
                        $row['mrp'] = $row['mrp'] * $row['pack_qty'];
                        $row['paid'] = $row['paid'] * $row['pack_qty'];
                        // $row['amount'] =  $row['paid']*$row['pack_qty'];
                        $row['discount'] = ($row['mrp'] - $row['paid']);
                        $row['margin'] = round((($row['mrp'] - $row['paid']) / $row['paid']) * 100, 2);
                        $row['total_price'] = ($row['picked_qty'] * $row['paid']);
                        $total_units += $row['picked_qty'];
                        $total_savings += ($row['mrp'] - $row['paid']) * $row['picked_qty'];
                        $total_pay += ($row['picked_qty'] * $row['paid']);
                    }



                    $total_items++;
                } else if ($status == "rejected") {
                    $total_units += $row['delivered_qty'];
                    $total_savings += ($row['mrp'] - $row['paid']) * $row['delivered_qty'];
                    $total_pay += ($row['delivered_qty'] * $row['paid']);
                    $total_items++;
                }
                //$total_sur_charge += 0.00;
                $result["records"][] = $row;
            }
            $deliver_charges = round($deliver_charges, 2);
            $result['total_units'] = $total_units;
            $result['total_items'] = $total_items;
            $result['total_savings'] = $total_savings;
            $result['total_sur_charge'] = $total_sur_charge;
            $result['sub_total'] = $total_pay;
            $deliver_charges = ($total_pay / 100);
            $deliver_charges = round($deliver_charges, 2);
            $result["delivery_charges"] = $deliver_charges;
            $result['total_pay'] = ($total_pay + $total_sur_charge + $deliver_charges);
        }

        if ($status != "rejected") {
            $condition = " and o.action_status='2'";
        }

        $result_set = $this->model_all->getTableDataFromQuery("SELECT o.*,sd.delivered_qty, b.name as seller,i.id as itemid,i.itemname,i.brand,bp.margin_price as sellingprice,bp.pay FROM `seller_order_items` o, items i ,branches b,branch_prices bp,item_prices ip,seller_pack_details sd where bp.id=o.branch_price_id and bp.branch_id=b.id  and bp.itemprice_id = ip.id and ip.item_id=i.id and o.orderid='$order' and sd.order_item_id=o.id and sd.packed_qty!=0 and sd.status='1' $condition order by o.action_status asc");
        if ($result_set->num_rows() > 0) {


            $deliver_charges = 0.00;
            $total_sur_charge = 0.00;


            $result["total_rows"] = $result_set->num_rows();
            if ($status == "rejected") {
                $total_units = 0;
                $total_savings = 0;
                $total_pay = 0;
                $total_items = 0;
            }
            $sub_status = 1;
            foreach ($result_set->result_array() as $row) {
                $row['images'] = array();
                $image_qry = $this->model_all->getTableData("item_images", array("item" => $row['itemid']));
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


                if ($row['action_status'] == '1') {
                    $rej_qty = $row['delivered_qty'] - $row['picked_qty'];
                    $row['qty'] = $rej_qty;  // For displaing purpose in app for running on single variable name
                    $row['total_price'] = ($rej_qty * $row['paid']);
                    if ($status == "rejected") {
                        $total_units += $rej_qty;
                        $total_savings += ($row['mrp'] - $row['paid']) * $rej_qty;
                        $total_pay += ($rej_qty * $row['paid']);
                        $total_items++;
                    }
                } else if ($row['action_status'] == '2') {
                    $row['qty'] = $row['delivered_qty'];
                    $row['total_price'] = ($row['delivered_qty'] * $row['paid']);
                    if ($status == "rejected") {
                        $total_units += $row['delivered_qty'];
                        $total_savings += ($row['mrp'] - $row['paid']) * $row['delivered_qty'];
                        $total_pay += ($row['delivered_qty'] * $row['paid']);
                        $total_items++;
                    }
                }
                $row['discount'] = ($row['mrp'] - $row['paid']);
                $row['margin'] = round((($row['mrp'] - $row['paid']) / $row['amount']) * 100, 2);

                //$total_sur_charge += 0.00;
                $result["rejected_records"][] = $row;
            }
            if ($status == "rejected") {
                $result['total_units'] = $total_units;
                $result['total_items'] = $total_items;
                $result['total_savings'] = $total_savings;
                $result['total_sur_charge'] = $total_sur_charge;
                $result['sub_total'] = $total_pay;
                $deliver_charges = ($total_pay / 100);
                $result["delivery_charges"] = $deliver_charges;
                $result['total_pay'] = ($total_pay + $total_sur_charge + $deliver_charges);
            }
            /* $result['rejected_units'] = $total_units;
              $result['rejected_items'] = $total_items;
              $result['rejected_savings'] = $total_savings;
              $result['rej_total_sur_charge'] = $total_sur_charge;
              $result['rej_sub_total'] = $total_pay;
              $result['reg_total_pay'] = ($total_pay + $total_sur_charge + $deliver_charges); */
        }
        $result["status"] = $sub_status;
        if ($result["status"] == 1) {
            $result["message"] = "Records Found";
            $store_query = $this->model_all->getTableDataFromQuery("select CONCAT(s.first_name,s.last_name)  as name,a.pincode,a.address,a.door_no,a.street_name,a.landmark,a.city,d.district,st.state,o.status,o.est_date,o.est_time,o.delivery_charges,o.service_charge,o.fa_status,o.admin_status,o.payment_type,o.reference_no,o.credit_date,o.paid,s.mobile,s.contact1,o.created_by,o.orderedby from sellers s,seller_orders o,addresses a,countries c,states st,districts d where s.id=o.orderedby and o.id='$order' and a.user_id=s.id and (a.user_role='seller' or a.user_role='DEALER') and a.is_default='1' and a.country=c.id and a.state=st.id and d.id=a.district  and st.country=c.id and d.state=st.id");
            if ($store_rs = $store_query->row()) {
                $result["store_name"] = $store_rs->name;
                $result["order_status"] = $store_rs->status;
                $result["delivery_date"] = date("d-M-Y", strtotime($store_rs->est_date));
                $result["delivery_time"] = $store_rs->est_time;
                $deliver_charges = $store_rs->delivery_charges;
                // $result["delivery_charges"] = ($store_rs->delivery_charges/100);//$store_rs->delivery_charges;
                $total_sur_charge = $store_rs->service_charge;
                $seller_address = "";
                if ($store_rs->address == "") {
                    if ($store_rs->door_no != "" && $store_rs->door_no != "NA")
                        $seller_address .= $store_rs->door_no . ",";
                    if ($store_rs->street_name != "" && $store_rs->street_name != "NA") {
                        $seller_address .= $store_rs->street_name . ",";
                    }
                    if ($store_rs->landmark != "" && $store_rs->landmark != "NA") {
                        $seller_address .= $store_rs->landmark . ",";
                    }
                } else {
                    $seller_address .= $store_rs->address;
                }
                if ($store_rs->district != "" && $store_rs->district != "NA") {
                    $seller_address .= $store_rs->district . ",";
                }
                if ($store_rs->state != "" && $store_rs->state != "NA") {
                    $seller_address .= $store_rs->state . ",";
                }
                if ($store_rs->pincode != "" && $store_rs->pincode != "NA") {
                    $seller_address .= " Pin: " . $store_rs->pincode . ".";
                }

                $result["store_address"] = $seller_address;
                $result['fa_status'] = $store_rs->fa_status;
                $result['admin_status'] = $store_rs->admin_status;

                $result['fa_status'] = $store_rs->fa_status;
                $result['payment_type'] = $store_rs->payment_type;
                if ($store_rs->credit_date != "") {
                    $result['credit_date'] = date("d-M-y", strtotime($store_rs->credit_date));
                } else {
                    $result['credit_date'] = "";
                }

                $result['paid'] = $store_rs->paid;
                $result['reference_no'] = $store_rs->reference_no;
                $result['mobile'] = "NA";
                if ($store_rs->mobile != "" && $store_rs->mobile != 0) {
                    $result['mobile'] = $store_rs->mobile;
                } else if ($store_rs->contact1 != "" && $store_rs->contact1 != 0) {
                    $result['mobile'] = $store_rs->contact1;
                }

                if ($store_rs->orderedby != $store_rs->created_by) {

                    $emp_qry = $this->model_all->getTableDataFromQuery("select e.first_name as emp_name,e.mobile,b.name as branch_name from employees e,branches b where e.branch=b.id and e.id='$store_rs->created_by'");
                    if ($emp_qry->num_rows() > 0) {
                        $emp_rs = $emp_qry->row();
                        $result["takenby_name"] = $emp_rs->emp_name;
                        $result["takenby_branch"] = $emp_rs->branch_name;
                        $result["takenby_contact"] = $emp_rs->mobile;
                    } else {
                        $result["takenby_name"] = "-";
                        $result["takenby_branch"] = "-";
                        $result["takenby_contact"] = "-";
                    }
                } else {
                    $result["takenby_name"] = "Self";
                    $result["takenby_branch"] = "-";
                    $result["takenby_contact"] = "-";
                }
            }
        } else {
            $result["status"] = 0;
            $result["message"] = "No records Found";
        }


        $this->response($result, 200);
        exit;
    }

}
