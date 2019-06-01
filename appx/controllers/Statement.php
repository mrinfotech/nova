<?php

require(APPPATH . '/libraries/REST_Controller.php');

class Statement extends REST_Controller {

    public function __construct() {
        parent::__construct();
        $this->load->model('model_all');
    }

    function statement($user,$from_role="DEALER",$branch="",$fromdate="",$todate="") {
        $condition = "";


        $result["fromdate"]=$fromdate;
        $result["todate"]=$todate;
        $result["from_role"]=$from_role;
        if ($fromdate != "" && $todate == "") {
            $fromdate = date("Y-m-d", strtotime($fromdate));
            $condition .= " and date(w.`transaction_date`)='$fromdate'";
        } else if ($fromdate != "" && $todate != "") {
            $fromdate = date("Y-m-d", strtotime($fromdate));
            $todate   = date("Y-m-d", strtotime($todate));
            $condition .= " and w.`transaction_date` between '$fromdate 00:00:00' and '$todate 23:59:59'";
        }
       
        if($branch!=""){
            $condition .= " and w.`branch`='$branch'";
        }
        
        $initial_pending = 0.00;
        $initial_date    = "";
        $seller_query    = $this->model_all->getTableDataFromQuery("select * from closing_balance where dealer_id='$user' order by id");  //select * from closing_balance where dealer_id='$user'
        if ($seller_query->num_rows() > 0) {
            $seller_rs       = $seller_query->row();
            $initial_pending = $seller_rs->closing_balance;
            $initial_date    = $seller_rs->closing_date;
            
        }

        
        
        $debit_amount = $this->model_all->getTableDataFromQuery("select sum(amount)  as debit_amount  from wallet_history w where w.user_id='$user' and w.`transaction_date` < '$fromdate 00:00:00' and transaction_mode='debit'")->row()->debit_amount; // Amount
        
        $credit_amount = $this->model_all->getTableDataFromQuery("select sum(amount)  as credit_amount from wallet_history w where w.user_id='$user' and  w.`transaction_date` < '$fromdate 00:00:00' and transaction_mode='credit'")->row()->credit_amount; // Amount
        
        $total_pending = $initial_pending + $debit_amount;
        
        if ($total_pending >= $credit_amount) {
            $total_pending = $total_pending - $credit_amount;
        } else {
            $total_pending = 0.00;
        }
        
        
        $result["pending_amount"] = $total_pending;
        
        $debit_summary  = 0.00;
        $credit_summary = 0.00;
        
        
        if($total_pending<0){
           $object["transaction_type"] = "Dr.";
           $object["debit_amount"]     = $total_pending*-1;
           $object["credit_amount"]    = "";
           $debit_summary              = $total_pending;

        }else{
           $object["transaction_type"] = "Cr.";
           $object["debit_amount"]     =  "";
           $object["credit_amount"]    =  $total_pending;
           $credit_summary             = $total_pending;
        }

        $object["action_date"]      = date("d-m-Y", strtotime($fromdate));
        $object["particular"]       = "Opening Balance";
        //$debit_summary              = $total_pending;
        $result["tabular"][]        = $object;
        
        
        
       
        
        $statement_query = $this->model_all->getTableDataFromQuery("select * from wallet_history w where w.amount!=0 and w.user_id='$user' $condition order by transaction_date asc,id asc");
        
        
        if ($statement_query->num_rows() > 0) {
            $result["status"]           = 1;
            $result["message"]          = "Statement Details";
            $result["statementdetails"] = array();
            foreach ($statement_query->result_array() as $row) {
                
                if ($row['reference_no'] != "" && $row['payment_mode']=="cheque") {
                    $row['cheque_no'] = $row['reference_no'];
                } else {
                    $row['cheque_no'] = 'NA';
                }
                
                if ($row['cheque_deposit_date'] != "0000-00-00") {
                    $row['cheque_date'] = date("d-m-Y", strtotime($row['cheque_deposit_date']));
                } else if ($row['transaction_mode'] == 'credit') {
                    $row['cheque_date'] = date("d-m-Y", strtotime($row['action_date']));
                } else if ($row['transaction_mode'] == 'debit') {
                    $row['cheque_date'] = date("d-m-Y", strtotime($row['transaction_date']));
                } else {
                    $row['cheque_date'] = 'NA';
                }
                
                if ($row['transaction_mode'] == 'debit') {
                    $row['debit'] = $row['amount'];
                } else {
                    $row['debit'] = 'NA';
                }
                
                if ($row['transaction_mode'] == 'credit') {
                    $row['credit'] = $row['amount'];
                } else {
                    $row['credit'] = 'NA';
                }
                
                
                
                
                
                
                $object                = array();
                $object["action_date"] = date("d-m-Y", strtotime($row['transaction_date']));
                if ($row['transaction_mode'] == 'credit') {
                    $object["transaction_type"] = "Cr.";
                    $object["credit_amount"]    = $row['amount'];
                    $object["debit_amount"]     = "";
                    $credit_summary += $row['amount'];
                } else {
                    $object["transaction_type"] = "Dr.";
                    $object["debit_amount"]     = $row['amount'];
                    $object["credit_amount"]    = "";
                    $debit_summary += $row['amount'];
                }
                
                $row['closing_balance'] = $result['pending_amount'] + $debit_summary - $credit_summary;
                $object["particular"]   = $row['particular'];
                
                $result["tabular"][] = $object;
                
            }


                $object                = array();
                $object["particular"] = "<b>Total</b>";
                $object["action_date"] = "";
                $object["transaction_type"] = "";
                $object["credit_amount"]    = $credit_summary;
                $object["debit_amount"]     = $debit_summary;
                $result["tabular"][] = $object;
       
            $result["debit_summary "] = $debit_summary;
            $result["credit_summary"] = $credit_summary;

              $store_query = $this->model_all->getTableDataFromQuery("select se.company_name  as company_name,se.gstin,a.pincode,a.address,a.door_no,a.street_name,a.landmark,a.city,d.district,s.state,s.gst_code as state_id,se.mobile from sellers se,addresses a,countries c,states s,districts d where se.id='$user' and a.user_id=se.id and (a.user_role='seller' or a.user_role='DEALER') and a.is_default='1' and a.country=c.id and a.state=s.id and d.id=a.district  and s.country=c.id and d.state=s.id");
              $result["seller"]=array();
              if($store_query->num_rows()>0) {
             if ($store_rs = $store_query->row()) {
      
                
                $seller_address = "";
                $result["seller"]["name"] = $store_rs->company_name;
                if ($store_rs->address=="") {
                  if ($store_rs->door_no != "" && $store_rs->door_no!="NA")
                    $seller_address .= $store_rs->door_no . ",";
                  if ($store_rs->street_name != "" && $store_rs->street_name!="NA") {
                    $seller_address .= $store_rs->street_name. ",";
                  }
                  if ($store_rs->landmark != "" && $store_rs->landmark!="NA") {
                    $seller_address .= $store_rs->landmark. ",";
                  }
               }else{
                  $seller_address .= $store_rs->address;
               }
                if ($store_rs->district != "" && $store_rs->district!="NA") {
                    $seller_address .= $store_rs->district. ",";
                }
                if ($store_rs->pincode!= "" && $store_rs->pincode!="NA") {
                    $seller_address .= $store_rs->pincode. ".";
                }

                $seller_address = rtrim($seller_address, ",");

                $result["seller"]["address"] = $seller_address;
                $result["seller"]["gstin"] = $store_rs->gstin;
                $result["seller"]["state"] = $store_rs->state;
                $result["seller"]["state_code"] = $store_rs->state_id;
                
              }
             }


             $branch_qry = $this->model_all->getTableDataFromQuery("select o.state,s.state as state_name,b.contact_no,c.email,c.pan,c.cin_no,b.gst_no,c.company,c.url,c.logo,c.signature,c.formal_name,o.name,o.addressline1,b.id,s.gst_code as state,b.acc_no,b.acc_holder_name,b.bank_name,b.bank_branch,b.ifsc from offices o,branches b,companies c,states s where o.id=b.office_id and b.id='$branch' and b.company=c.company_id and o.state=s.id");
           if ($branch_qry->num_rows()>0) {
            if ($branch_rs = $branch_qry->row()) {
                $result["branch_details"]["company"] = $branch_rs->company;
                $result["branch_details"]["company_url"] = $branch_rs->url;
                $result["branch_details"]["company_signature"] = $branch_rs->signature;
                $result["branch_details"]["logo"] = $branch_rs->logo;
                $result["branch_details"]["formal_name"] = $branch_rs->formal_name;
                $result["branch_details"]["branch_name"] = $branch_rs->name;
                $result["branch_details"]["address"] = $branch_rs->addressline1;
                $result["branch_details"]["contact"] = $branch_rs->contact_no;
                $result["branch_details"]["gst"] = $branch_rs->gst_no;
                $result["branch_details"]["email"] = $branch_rs->email;
                $result["branch_details"]["cin_no"] = $branch_rs->cin_no;
                $result["branch_details"]["pan"] = $branch_rs->pan;
                $result["branch_details"]["logo"] = $branch_rs->logo;
                $result["branch_state"] = $branch_rs->state;
                $result["branch_statename"] = $branch_rs->state_name;
                $result["branch_details"]["acc_no"] = $branch_rs->acc_no;
                $result["branch_details"]["acc_holder_name"] = $branch_rs->acc_holder_name;
                $result["branch_details"]["bank_name"] = $branch_rs->bank_name;
                $result["branch_details"]["bank_branch"] = $branch_rs->bank_branch;
                $result["branch_details"]["ifsc"] = $branch_rs->ifsc;
            }
           }







            
        } else {
            $result["status"]  = 0;
            $result["message"] = "No Statement Details Found";
            
        }
        
       return $result;
        
        
    }
    
    
    function balance_update_post()
    {
        
        $user              = $this->post('user');
        $user_role         = 'DEALER';
        // $order_id = $this->post('order_id');
        $amount            = $this->post('amount');
        $discount          = $this->post('discount');
        $discount_point_id = $this->post('discount_point_id');
        $discount_point    = $this->post('discount_point');
        $transaction_mode  = 'credit';
        $payment_mode      = $this->post('payment_mode');
        $cheque_no         = $this->post('cheque_no');
        $deposit_date      = $this->post('deposit_date');
        $cheque_status     = $this->post('cheque_status');
        $account_name      = $this->post('account_name');
        $account_number    = $this->post('account_number');
        $bank_name         = $this->post('bank_name');
        
        $action_by      = $this->post('action_by');
        $action_role    = "trade"; //$this->post('action_role');
        $transaction_no = $this->post('transaction_no');
        // $transaction_date = $this->post('transaction_date');
        $flag           = false;
        
     //   print_r($this->post());
        
        
        if ($payment_mode == "cheque" || $payment_mode=="CHEQUE") {
            $reference_number = $cheque_no;
        } else {
            $reference_number = "";
        }
        
        if ($deposit_date != "") {
            $deposit_date = date("Y-m-d", strtotime($deposit_date));
        } else {
            $deposit_date = "";
        }
        
        $transaction_date = $deposit_date;
        
        if ($transaction_date != "") {
            $transaction_date = date("Y-m-d", strtotime($transaction_date));
        }
        
        if ($discount != 0.00) {
            $discount_amount = round(($amount * $discount) / 100, 2);
        }
        
        
        //$action_date= $dt;
        
        $dt   = date("Y-m-d H:i:s");
        $flag = false;
        if ($cheque_status == 'bounce') {
            $status = '0';
        } else if ($cheque_status == 'pass') {
            $status = '1';
        } else {
            $status = '1';
        }
        
        $table = "wallet_history";
        
        
        
        
        
        if ($deposit_date != "") {
            $deposit_date = date("Y-m-d", strtotime($deposit_date));
        }
        
        
        
        
        
        
        
        
        if ($amount != 0) {
            
            
            $data   = array(
                "user_id" => $user,
                "user_role" => 'DEALER',
                "prev_balance" => 0.00,
                "amount" => $amount,
                "reference_no" => $reference_number,
                "transaction_no" => $transaction_no,
                "transaction_mode" => 'credit',
                "payment_mode" => $payment_mode,
                "transaction_date" => $transaction_date,
                "cheque_deposit_date" => $deposit_date,
                "cheque_status" => $cheque_status,
                "account_name" => $account_name,
                "account_number" => $account_number,
                "bank_name" => $bank_name,
                "action_by" => $action_by,
                "action_role" => $action_role,
                "action_date" => $dt,
                "status" => $status
            );
            $row_id = $this->model_all->save($data, $table);
            if ($row_id > 0) {
                $flag        = true;
                $temp_amount = $amount;
                $particular  = $discount_point;
                $order_str   = "";
                $order_query = $this->model_all->getTableDataFromQuery("select id,order_value,paid from seller_orders where orderedby='$user' and paid_status!='2' order by id");
                if ($order_query->num_rows() > 0) {
                    foreach ($order_query->result() as $order_row) {
                        if ($temp_amount > 0) {
                            $balance_amount = $order_row->order_value - $order_row->paid;
                            
                            if ($temp_amount >= $balance_amount) {
                                $this->model_all->getTableDataFromQuery("update seller_orders set paid=paid+$balance_amount , paid_status='2' where id='$order_row->id'");
                                $temp_amount = $temp_amount - $balance_amount;
                                $order_str   = $order_str . $order_row->id . ",";
                            } else if ($temp_amount > 0) {
                                $this->model_all->getTableDataFromQuery("update seller_orders set paid=paid+$temp_amount , paid_status='1' where id='$order_row->id'");
                                $temp_amount = 0.00;
                                $order_str   = $order_str . $order_row->id . ",";
                            } else {
                                $temp_amount = 0.00;
                            }
                            if ($temp_amount == 0) {
                                break;
                            }
                        } else {
                            break;
                            
                        }
                    }
                    
                    if ($order_str != "") {
                        $order_str  = trim($order_str, ",");
                        $particular = $particular . " " . $order_str . " through " . $payment_mode;
                        if ($payment_mode == "cheque") {
                            $particular = $particular . " with cheque No" . $reference_number;
                        }
                        if ($transaction_no != "")
                            $particular .= " Transaction No:" . $transaction_no . ".";
                        if ($bank_name != "")
                            $particular .= " Bank Name:" . $bank_name . ".";
                        
                        $this->model_all->update(array(
                            "order_id" => $order_str,
                            "particular" => $particular
                        ), array(
                            "id" => $row_id
                        ), $table);
                    }
                    
                    
                }
                
                
            }
        }
        
        if ($discount_amount != 0) {
            
            $payment_mode     = "discount";
            $transaction_date = $dt;
            $deposit_date     = "";
            $cheque_status    = "";
            $account_name     = "";
            $account_number   = "";
            $bank_name        = "";
            $status           = "1";
            $data             = array(
                "user_id" => $user,
                "user_role" => 'DEALER',
                "prev_balance" => 0.00,
                "amount" => $discount_amount,
                "reference_no" => $reference_number,
                "transaction_no" => $transaction_no,
                "transaction_mode" => 'credit',
                "payment_mode" => $payment_mode,
                "transaction_date" => $transaction_date,
                "cheque_deposit_date" => $deposit_date,
                "cheque_status" => $cheque_status,
                "account_name" => $account_name,
                "account_number" => $account_number,
                "bank_name" => $bank_name,
                "action_by" => $action_by,
                "action_role" => $action_role,
                "action_date" => $dt,
                "status" => $status
            );
            
            $row_id = $this->model_all->save($data, $table);
            if ($row_id > 0) {
                $flag        = true;
                $temp_amount = $discount_amount;
                $particular  = "Payment towards ";
                $order_str   = "";
                $order_query = $this->model_all->getTableDataFromQuery("select id,order_value,paid from seller_orders where orderedby='$user' and paid_status!='2' order by id");
                if ($order_query->num_rows() > 0) {
                    foreach ($order_query->result() as $order_row) {
                        if ($temp_amount > 0) {
                            $balance_amount = $order_row->order_value - $order_row->paid;
                            
                            if ($temp_amount >= $balance_amount) {
                                $this->model_all->getTableDataFromQuery("update seller_orders set paid=paid+$balance_amount , paid_status='2' where id='$order_row->id'");
                                $temp_amount = $temp_amount - $balance_amount;
                                $order_str   = $order_str . $order_row->id . ",";
                            } else if ($temp_amount > 0) {
                                $this->model_all->getTableDataFromQuery("update seller_orders set paid=paid+$temp_amount , paid_status='1' where id='$order_row->id'");
                                $temp_amount = 0.00;
                                $order_str   = $order_str . $order_row->id . ",";
                            } else {
                                $temp_amount = 0.00;
                            }
                            if ($temp_amount == 0) {
                                break;
                            }
                        } else {
                            break;
                            
                        }
                    }
                    
                    if ($order_str != "") {
                        $order_str  = trim($order_str, ",");
                        $particular = $particular . " " . $order_str . " through " . $payment_mode;
                        if ($payment_mode == "cheque") {
                            $particular = $particular . " with cheque No" . $reference_no;
                        }
                        if ($transaction_no != "")
                            $particular .= " Transaction No:" . $transaction_no . ".";
                        if ($bank_name != "")
                            $particular .= " Bank Name:" . $bank_name . ".";
                        
                        $this->model_all->update(array(
                            "order_id" => $order_str,
                            "particular" => $particular
                        ), array(
                            "id" => $row_id
                        ), $table);
                    }
                    
                }
                
            }
            
        }
        
        if ($flag) {
            $result["status"]  = 1;
            $result["message"] = "Payment Credited Successfully";
        } else {
            $result["status"]  = 0;
            $result["message"] = "Something went wrong. Try again";
            
        }
        
    }

  
    function packed_details_get($order) {

        $condition = "";
        $sub_status = 0;


        $result_set = $this->model_all->getTableDataFromQuery("SELECT o.*,sd.packed_qty,sd.delivered_qty, b.name as seller,i.id as itemid,i.itemname,i.brand,bp.margin_price as sellingprice,bp.pay,ip.hsn_code,ip.pack_qty,ip.cgst,ip.sgst,ip.igst,ip.mrp as single_piece_mrp FROM `seller_order_items` o, items i ,branches b,branch_prices bp,item_prices ip,seller_pack_details sd where bp.id=o.branch_price_id and bp.branch_id=b.id  and bp.itemprice_id = ip.id and ip.item_id=i.id and o.orderid='$order' and sd.order_item_id=o.id and sd.packed_qty!=0  $condition order by o.action_status asc");
        if ($result_set->num_rows() > 0) {

            $deliver_charges = 0.00;
            $total_sur_charge = 0.00;

            $result["total_rows"] = $result_set->num_rows();
$result["depot_contact"]="";
$result["dealer_state"] ="";
$result["branch_state"]="";
$depot_contact="";
            $total_units = 0;
            $total_savings = 0;
            $total_pay = 0;
            $total_items = 0;
            $sub_status = 1;
            foreach ($result_set->result_array() as $row) {
                $row['single_piece_pay'] = $row['sellingprice'];   
                $row['mrp']= $row['mrp']*$row['pack_qty'];
               // $row['amount']= $row['amount']*$row['pack_qty'];
                $row['picked_qty'] =  $row["packed_qty"];
               
                $row['discount'] = ($row['mrp'] - $row['amount']);

                $row['margin'] = round((($row['mrp'] - $row['amount']) / $row['amount']) * 100, 2);
                $row['total_price'] = ($row['packed_qty'] * $row['amount']);
                $total_units += $row['packed_qty'];
                $total_savings += ($row['mrp'] - $row['amount']) * $row['packed_qty'];
                $total_pay += ($row['packed_qty'] * $row['amount']);
                    


                    $total_items++;
                
                //$total_sur_charge += 0.00;
                $result["records"][] = $row;
            }
            $result['total_units'] = $total_units;
            $result['total_items'] = $total_items;
            $result['total_savings'] = $total_savings;
            $result['total_sur_charge'] = $total_sur_charge;
            $result['sub_total'] = $total_pay;
            $deliver_charges = ($total_pay / 100);
            $result["delivery_charges"] = $deliver_charges;
            $result['total_pay'] = ($total_pay + $total_sur_charge + $deliver_charges);
        }


        $result["status"] = $sub_status;
        if ($result["status"] == 1) {
            $result["message"] = "Records Found";
            $result["seller"] = array();
            $result["order"] = array();
            $result["transport"] = array();
            $result["branch_details"] = array();
            $branch = 0;
$result["depot_contact"]="";
$result["dealer_state"] ="";
$result["branch_state"]="";


            $transport_set = $this->model_all->getTableDataFromQuery("select dr.estimation_time,dr.from_route,dr.to_route,dr.paid,dr.amount,dv.contact,dv.vechicle_number,dv.driver_number,dv.driver_name,dv.lr_no,t.name as transport_name,t.contact_no,t.transport_type,e.first_name as emp_name,s.company_name from delivery_route dr,delivery_vehicles dv, transport t,deliver_route_order dro,employees e,seller_orders o,sellers s where dr.id=dv.route_id and dr.id=dro.droute_id and dv.transport=t.id and FIND_IN_SET('$order',dro.orders) and  e.id=t.created_by and o.id='$order' and o.orderedby=s.id");
            if ($transport_set->num_rows() > 0) {
                $transport_row = $transport_set->row();
                $result["transport"]["transport_type"] = $transport_row->transport_type;
                if ($transport_row->lr_no != "") {
                    $result["transport"]["lr_no"] = $transport_row->lr_no;
                } else if ($transport_row->vechicle_number != "") {
                    $result["transport"]["lr_no"] = $transport_row->vechicle_number;
                }

                $result["transport"]["contact"] = $transport_row->contact;
                $result["transport"]["supply_date"] = $transport_row->estimation_time;
                $result["transport"]["supply_place"] = "";
            }

            $store_query = $this->model_all->getTableDataFromQuery("select se.company_name  as company_name,se.gstin,a.pincode,a.address,a.door_no,a.street_name,a.landmark,a.city,d.district,s.state,s.gst_code as state_id,o.status,o.est_date,o.est_time,o.delivery_charges,o.service_charge,o.fa_status,o.admin_status,o.payment_type,o.reference_no,o.credit_date,o.paid,o.order_id,se.mobile,o.created_by,o.orderedby,o.orderedon,o.branch_id from sellers se,seller_orders o,addresses a,countries c,states s,districts d where se.id=o.orderedby and o.id='$order' and a.user_id=se.id and (a.user_role='seller' or a.user_role='DEALER') and a.is_default='1' and a.country=c.id and a.state=s.id and d.id=a.district  and s.country=c.id and d.state=s.id");
            if($store_query->num_rows()>0) {
             if ($store_rs = $store_query->row()) {
                $branch = $store_rs->branch_id;
                $seller_address = "";
                $result["seller"]["name"] = $store_rs->company_name;
                if ($store_rs->address=="") {
                  if ($store_rs->door_no != "" && $store_rs->door_no!="NA")
                    $seller_address .= $store_rs->door_no . ",";
                  if ($store_rs->street_name != "" && $store_rs->street_name!="NA") {
                    $seller_address .= $store_rs->street_name. ",";
                  }
                  if ($store_rs->landmark != "" && $store_rs->landmark!="NA") {
                    $seller_address .= $store_rs->landmark. ",";
                  }
               }else{
                  $seller_address .= $store_rs->address;
               }
                if ($store_rs->district != "" && $store_rs->district!="NA") {
                    $seller_address .= $store_rs->district. ",";
                }
                if ($store_rs->pincode!= "" && $store_rs->pincode!="NA") {
                    $seller_address .= $store_rs->pincode. ".";
                }

                $seller_address = rtrim($seller_address, ",");

                $result["seller"]["address"] = $seller_address;
                $result["seller"]["gstin"] = $store_rs->gstin;
                $result["seller"]["state"] = $store_rs->state;
                $result["seller"]["state_code"] = $store_rs->state_id;
                $result["dealer_state"] = $store_rs->state_id;

                $result["order"]["id"] = $store_rs->order_id;
                $result["order"]["order_date"] = date("d-M-Y", strtotime($store_rs->orderedon));
                $result["order"][] = $store_rs->status;

                $result["order_status"] = $store_rs->status;
                $result["delivery_date"] = date("d-M-Y", strtotime($store_rs->est_date));
                $result["delivery_time"] = $store_rs->est_time;
                $deliver_charges = $store_rs->delivery_charges;
                // $result["delivery_charges"] = ($store_rs->delivery_charges/100);//$store_rs->delivery_charges;
                $total_sur_charge = $store_rs->service_charge;

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
                $result['mobile'] = $store_rs->mobile;
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
           }
            $branch_qry = $this->model_all->getTableDataFromQuery("select o.state,s.state as state_name,b.contact_no,c.email,c.pan,c.cin_no,b.gst_no,c.company,c.url,c.logo,c.signature,c.formal_name,o.name,o.addressline1,b.id,s.gst_code as state,b.acc_no,b.acc_holder_name,b.bank_name,b.bank_branch,b.ifsc from offices o,branches b,companies c,states s where o.id=b.office_id and b.id='$branch' and b.company=c.company_id and o.state=s.id");
           if ($branch_qry->num_rows()>0) {
            if ($branch_rs = $branch_qry->row()) {
                $result["branch_details"]["company"] = $branch_rs->company;
                $result["branch_details"]["company_url"] = $branch_rs->url;
                $result["branch_details"]["company_signature"] = $branch_rs->signature;
                $result["branch_details"]["logo"] = $branch_rs->logo;
                $result["branch_details"]["formal_name"] = $branch_rs->formal_name;
                $result["branch_details"]["branch_name"] = $branch_rs->name;
                $result["branch_details"]["address"] = $branch_rs->addressline1;
                $result["branch_details"]["contact"] = $branch_rs->contact_no;
                $result["branch_details"]["gst"] = $branch_rs->gst_no;
                $result["branch_details"]["email"] = $branch_rs->email;
                $result["branch_details"]["cin_no"] = $branch_rs->cin_no;
                $result["branch_details"]["pan"] = $branch_rs->pan;
                $result["branch_details"]["logo"] = $branch_rs->logo;
                $result["branch_state"] = $branch_rs->state;
                $result["branch_statename"] = $branch_rs->state_name;
                $result["branch_details"]["acc_no"] = $branch_rs->acc_no;
                $result["branch_details"]["acc_holder_name"] = $branch_rs->acc_holder_name;
                $result["branch_details"]["bank_name"] = $branch_rs->bank_name;
                $result["branch_details"]["bank_branch"] = $branch_rs->bank_branch;
                $result["branch_details"]["ifsc"] = $branch_rs->ifsc;
            }
           }

            $deopt_manger_qry = $this->model_all->getTableDataFromQuery("select mobile,ofc_contact from employees e,app_roles a where e.branch='$branch' and a.short_form='FM' and e.role_id=a.id ");
           if ($deopt_manger_qry->num_rows()>0) {
            if ($deopt_manger_rs = $deopt_manger_qry->row()) {
                $depot_contact = $deopt_manger_rs->ofc_contact;
                if ($depot_contact == "") {
                    $depot_contact = $deopt_manger_rs->mobile;
                }
             }
            } 
            $result["depot_contact"] = $depot_contact;
        }


        return $result;
    }

    function index_get() {

        $user     = $this->get('user');
        $fromdate = $this->get('fromdate');
        $todate   = $this->get('todate');
        $from_role   = $this->get('from_role');
        $branch   = $this->get('branch');
        $data['list'] = $this->statement($user,$from_role,$branch,$fromdate,$todate);
        
        
        $viewdata = $this->load->view('statement', $data,true);
        $this->load->helper('pdf_helper');
        $this->load->view('pdf', array("viewdata" => $viewdata), true);
    }

}
