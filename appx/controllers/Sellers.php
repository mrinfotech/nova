<?php

require(APPPATH . '/libraries/REST_Controller.php');

class Sellers extends REST_Controller {

    public function __construct() {
        parent::__construct();
        $this->load->model('model_all');
         $this->load->library('fcm');
    }

    function index_get() {
        $primaryid = $this->get("primaryid");
        $result_set = $this->model_all->getTableDataInArray("sellers", array(
            'status' => 1,
            'pickerid' => $primaryid
                ), "id,first_name,last_name,email,mobile,latitude,longitude,address");

        if (($result_set['total_rows']) > 0) {
            $result["status"] = 1;
            $result["message"] = "Success";
            $result["sellers"] = $result_set['records'];
            $this->response($result, 200);
            exit;
        } else {
            $result["status"] = 0;
            $result["message"] = "No Sellers Found";
            $this->response($result, 200);
            exit;
        }
    }

    function seller_orders_get() {
        $primaryid = $this->get("primaryid");
        $dt = date("Y-m-d");
        $result_set = $this->model_all->getTableDataFromQuery("select distinct s.id,s.first_name,s.last_name,s.email,s.mobile,s.latitude,s.longitude,s.address from sellers s, seller_items si,orders o where s.id=si.seller_id and si.order_date='$dt' and s.pickerid='$primaryid' and FIND_IN_SET(o.id,si.order_item)");

        if ($result_set->num_rows() > 0) {
            $result["status"] = 1;
            $result["message"] = "Success";
            $result["sellers"] = array();
            foreach ($result_set->result_array() as $row) {

                $result["sellers"][] = $row;
            }

            $this->response($result, 200);
            exit;
        } else {
            $result["status"] = 0;
            $result["message"] = "No Sellers Found";
            $this->response($result, 200);
            exit;
        }
    }

    //API - Fetch All Pincodes
    function invoices2_get() {
        $seller = $this->get('seller');
        $status = $this->get('status');
        $fromdate = $this->get('fromdate');
        $todate = $this->get('todate');
        $condition = "";
        if ($status == "")
            $staus = 0;
        if ($fromdate != "" && $todate == "") {
            $fromdate = date("Y-m-d", strtotime($fromdate));
            $condition .= " and `order_date`='$fromdate'";
        } else if ($fromdate != "" && $todate != "") {
            $fromdate = date("Y-m-d", strtotime($fromdate));
            $todate = date("Y-m-d", strtotime($todate));
            $condition .= " and `order_date` between '$fromdate' and '$todate'";
        }


        /* $result_set = $this->model_all->getTableDataFromQuery("select distinct invoice_id,order_date,description,reason from  seller_items where status='$status' and seller_id='$seller' $condition order by order_date desc"); */

        $result_set = $this->model_all->getTableDataFromQuery("select seller_invoices.*,(select count(*) from seller_items where sellet_invoice_pk=seller_invoices.id and status='$status') as sc_cnt from  seller_invoices where  seller_id='$seller' and is_picked!='0' and generate='1'  $condition order by order_date desc,id desc");
        // echo $this->db->last_query();
        if ($result_set->num_rows() > 0) {


            $k = 0;
            $result["total_amount"] = 0.00;
            foreach ($result_set->result_array() as $row) {
                if ($row["sc_cnt"] > 0) {
                    $row['order_date'] = date("d-m-Y", strtotime($row['order_date']));
                    $result["records"][] = $row;
                    $total_qry = $this->model_all->getTableDataFromQuery("select sum(amount) as total_amount from  seller_items where status='$status'  and sellet_invoice_pk='" . $row['id'] . "'"); //and seller_id='$seller'
                    $total_rs = $total_qry->row_array();
                    if ($total_rs['total_amount'] != "")
                        $result["total_amount"] = $result["total_amount"] + $total_rs['total_amount'];
                    $k++;
                }
            }

            if ($k > 0) {
                $result["status"] = 1;
                $result["message"] = "Records Found";
                $result["total_records"] = $result_set->num_rows();
            } else {
                $result["status"] = 0;
                $result["message"] = "No records Found";
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

        $seller = $this->get('seller');
        $invoiceid = $this->get('invoiceid');
        $status = $this->get('status');

        /* if ($invoiceid == "")
          $invoiceid = date("mdY") . $seller; */
        $condition = "";
        if ($status == 2) {
            $condition = " and  (si.status='2' or (si.status='1' and si.qty!=si.picked_qty))";
        }

        if ($status == 1) {
            $condition = " and  si.status='$status'";
        }



        $result_set = $this->model_all->getTableDataFromQuery("SELECT si.id as invoice_item_id,si.qty,si.picked_qty,si.amount,si.sellingprice, CONCAT(s.first_name,s.last_name)  as seller,i.itemname,i.brand,u.unit_name,si.is_processed FROM `seller_items` si, items i ,sellers s,unit_sizes u where i.id=si.item_id and s.id = si.seller_id and s.id='$seller' and u.unit_id=i.unit_size and si.sellet_invoice_pk='$invoiceid' and si.qty!=0 $condition");
        if ($result_set->num_rows() > 0) {

            $result["status"] = 1;
            $result["id"] = $invoiceid;
            $result["message"] = "Records Found";
            $result["total_rows"] = $result_set->num_rows();
            $total_amount = 0;
            $total_units = 0;

            $invoice_qry = $this->model_all->getTableData("seller_invoices", array(
                "id" => $invoiceid
            ));
            foreach ($invoice_qry->result() as $invoice_row) {
                $result["is_processed"] = $invoice_row->is_processed;
                $result["is_picked"] = $invoice_row->is_picked;
                $result["generate"] = $invoice_row->generate;
            }


            foreach ($result_set->result_array() as $row) {

                if ($status == 1) {
                    if ($row['qty'] != $row['picked_qty']) {
                        $row['qty'] = $row['picked_qty'];
                        $row['amount'] = $row['picked_qty'] * $row['sellingprice'];
                    }
                } else if ($status == 2) {
                    if ($row['qty'] != $row['picked_qty']) {
                        $row['qty'] = $row['qty'] - $row['picked_qty'];
                        $row['amount'] = $row['qty'] * $row['sellingprice'];
                    }
                }

                $total_amount += $row['amount'];
                $row['amount'] = "Rs " . ($row['amount']) . " /-";
                $total_units += $row['qty'];
                $result["records"][] = $row;
            }

            $result['total_units'] = $total_units;
            $result['total_amount'] = "Rs " . $total_amount . " /-";

            $this->response($result, 200);
            exit;
        } else {
            $result["status"] = 0;
            $result["message"] = "No records Found";
            $this->response($result, 200);
            exit;
        }
    }

    function process_put() {

        $invoice = $this->put('invoice');
        $invoice_item_id = $this->put('invoice_item_id');
        $token = $this->put('token');
        if ($token == "final") {
            $action_status = $this->model_all->update(array(
                "generate" => '1'
                    ), array(
                "id" => $invoice,
                "is_picked" => '1'
                    ), "seller_invoices");
            if ($action_status > 0) {
                $result["status"] = 1;
                $result["message"] = "Processed Successfully";
                $this->response($result, 200);
                exit;
            } else {
                $result["status"] = 0;
                $result["message"] = "Pickers did not pick items yet.";
                $this->response($result, 200);
                exit;
            }
        } else {
            $dt = date("Y-m-d H:i:s");
            $this->model_all->update(array(
                "is_processed" => '1'
                    ), array(
                "id" => $invoice
                    ), "seller_invoices");
            $action_status = $this->model_all->update(array(
                "is_processed" => '1'
                    ), array(
                "id" => $invoice_item_id
                    ), "seller_items");
            $order_query = $this->model_all->getTableDataFromQuery("select distinct o.orderid from seller_items s,order_items o where FIND_IN_SET(o.id,s.order_item) and s.id='$invoice_item_id'");
            foreach ($order_query->result() as $order_rs) {
                $this->model_all->update(array(
                    'status' => 'Accepted'
                        ), array(
                    "id" => $order_rs->orderid,
                    'status' => 'Ordered'
                        ), "orders");
                if ($this->db->affected_rows() > 0) {
                    $this->model_all->save(array(
                        "order_id" => $order_rs->orderid,
                        "order_status" => 'Accepted',
                        "changed_on" => $dt
                            ), 'order_track');
                }
            }
            if ($action_status > 0) {
                $result["status"] = 1;
                $result["message"] = "Processed Successfully";
                $this->response($result, 200);
                exit;
            } else {
                $result["status"] = 0;
                $result["message"] = "You have made no changes to save";
                $this->response($result, 200);
                exit;
            }
        }
    }

    function report_get() {
        $seller = $this->get('seller');
        $dt = date("Y-m-d");
        $result = array();
        $result["status"] = 0;
        $result["message"] = "No records Found";
        $result["processed"] = 0.00;
        $result["rejected"] = 0.00;
        $result["pending"] = 0.00;
        $result_set = $this->model_all->getTableDataFromQuery("select * from seller_items where order_date='$dt' and seller_id='$seller'");
        if ($result_set->num_rows() > 0) {
            $result["status"] = 1;
            $result["message"] = "Records Found";
            $result["order_value"] = 0;
            $result["processed"] = 0;
            $result["rejected"] = 0;
            $result["pending"] = 0;
            $result["processed_qty"] = 0;
            $result["rejected_qty"] = 0;
            $result["pending_qty"] = 0;
            foreach ($result_set->result() as $row) {
                if ($row->status == 0) {
                    $result["pending"] += $row->amount;
                    $result["pending_qty"] += $row->qty;
                } else if ($row->status == 1) {
                    if ($row->picked_qty == $row->qty) {
                        $result["processed"] += $row->amount;
                        $result["processed_qty"] += $row->qty;
                    } else {
                        $remaining = $row->qty - $row->picked_qty;
                        $amount = $row->amount / $row->qty;
                        $result["rejected"] += ($remaining * $amount);
                        $result["rejected_qty"] += $remaining;
                        $result["processed"] += ($row->picked_qty * $amount);
                        $result["processed_qty"] += $row->picked_qty;
                    }
                } else if ($row->status == 2) {
                    $result["rejected"] += $row->amount;
                    $result["rejected_qty"] += $row->qty;
                }
                $result["order_value"] += $row->amount;
            }
        }
        $result["processed"] = "Rs " . $result["processed"] . " /-";
        $result["rejected"] = "Rs " . $result["rejected"] . " /-";
        $result["pending"] = "Rs " . $result["pending"] . " /-";


        $this->response($result, 200);
        exit;
    }

    function genpdf_get() {
        $id = $this->get('id');
        $data = array();
        $invoice_qry = $this->model_all->getTableDataFromQuery("select si.*,concat(s.first_name,' ',s.last_name) as sname,s.address from seller_items si,sellers s where si.id='$id' and si.seller_id=s.id ");
        if ($invoice_qry->num_rows() > 0) {
            $data['invoice_qry'] = $invoice_qry;
            $items_qry = $this->model_all->getTableDataFromQuery("SELECT si.status,si.mrp,si.sellingprice,si.qty,si.amount,si.picked_qty, CONCAT(s.first_name,s.last_name)  as seller,i.itemname,i.brand,u.unit_name FROM `seller_items` si, items i ,sellers s,unit_sizes u where i.id=si.item_id and s.id = si.seller_id  and u.unit_id=i.unit_size and si.sellet_invoice_pk='$id' and si.status='1'");
            $data['items_qry'] = $items_qry;
        }
        $settings_qry = $this->model_all->getTableDataFromQuery("select * from settings");
        $data['settings_qry'] = $settings_qry;
        $viewdata = $this->load->view('invoice', $data, true);
        $this->load->helper('pdf_helper');
        $this->load->view('pdf', array(
            "viewdata" => $viewdata
                ), true);
    }

    //API - Fetch All Pincodes
    function invoices_get() {
        $seller = $this->get('seller');
        $status = $this->get('status');
        $fromdate = $this->get('fromdate');
        $todate = $this->get('todate');
        $today = date("Y-m-d");
        $condition = "";
        if ($status == "")
            $status = 0;
        else
            $status = $status;
        if ($fromdate == "" && $todate == "") {
            $fromdate = date("Y-m-d");
            $condition .= " and s.`order_date`='$fromdate'";
        } else if ($fromdate != "" && $todate == "") {
            $fromdate = date("Y-m-d", strtotime($fromdate));
            $condition .= " and s.`order_date`='$fromdate'";
        } else if ($fromdate != "" && $todate != "") {
            $fromdate = date("Y-m-d", strtotime($fromdate));
            $todate = date("Y-m-d", strtotime($todate));
            $condition .= " and s.`order_date` between '$fromdate' and '$todate'";
        }

        if ($status == 0) {
            $condition .= " and (s.status='0' or s.status='1') and si.generate='0'";
        } else if ($status == 1) {
            $condition .= " and s.status='1' and si.generate='1'";
        } else if ($status == 2) {
            $condition .= " and s.status='2' and (si.generate='1' or si.generate='0')";
        }





        $result_set = $this->model_all->getTableDataFromQuery("select distinct s.sellet_invoice_pk,s.invoice_id,s.order_date from  seller_items s,seller_invoices si where  s.seller_id='$seller' and s.order_date<='$today' and si.id=s.sellet_invoice_pk $condition order by si.order_date desc,si.id desc"); // s.description,s.reason


        if ($result_set->num_rows() > 0) {


            $k = 0;
            $result["total_amount"] = 0.00;
            foreach ($result_set->result_array() as $row) {
                $row['id'] = $row['sellet_invoice_pk'];
                $row['order_date'] = date("d-m-Y", strtotime($row['order_date']));
                $result["records"][] = $row;
                $total_qry = $this->model_all->getTableDataFromQuery("select sum(amount) as total_amount from  seller_items where status='$status'  and sellet_invoice_pk='" . $row['sellet_invoice_pk'] . "'"); //and seller_id='$seller'
                $total_rs = $total_qry->row_array();
                if ($total_rs['total_amount'] != "")
                    $result["total_amount"] = $result["total_amount"] + $total_rs['total_amount'];
                $k++;
            }

            if ($k > 0) {
                $result["status"] = 1;
                $result["message"] = "Records Found";
                $result["total_records"] = $result_set->num_rows();
            } else {
                $result["status"] = 0;
                $result["message"] = "No records Found";
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

    function seller_balance_get() {
        $user = $this->get('user');
        $dt = date("Y-m-d");
        $before_day = date('Y-m-d', strtotime(' -1 day'));
        $result_set = $this->model_all->getTableDataFromQuery("select closing_balance from closing_balance where dealer_id='$user' and closing_date='$before_day'");

        $debit = $this->model_all->getTableDataFromQuery("select sum(amount) as debit_bal from wallet_history where user_id='$user' and transaction_mode='debit' and date(transaction_date)='$dt' and status='1'")->row()->debit_bal;
        $credit = $this->model_all->getTableDataFromQuery("select sum(amount) as credit_bal from wallet_history where user_id='$user' and transaction_mode='credit' and date(transaction_date)='$dt' and status='1'")->row()->credit_bal;
        $result["status"] = 1;
        $result["message"] = "Account Balance Details";
        $closing_balance = 0.00;

        if ($result_set->num_rows() > 0) {


            foreach ($result_set->result_array() as $row) {
                $closing_balance = $row['closing_balance'];
            }
        }

        $pending_balance = ($closing_balance - $debit) + $credit;


        $row = array();
       /* if ($pending_balance < 0) {
            $row['grade_amount'] = $pending_balance * -1;
        } else {
            $row['grade_amount'] = $pending_balance;
        } */

        $row['grade_amount'] = $pending_balance;
        $result["balance"][] = $row;
        $this->response($result, 200);
        exit;
    }

    function debit_orders_get() {

        $user = $this->get('user');
        $result_set = $this->model_all->getTableDataFromQuery("select so.id, so.order_id from seller_orders so, wallet_history wh where wh.user_id='$user' and so.id in (wh.order_id) and wh.status='0'");

        if ($result_set->num_rows() > 0) {
            $result["status"] = 1;
            $result["message"] = "Order Details";
            $result["orders"] = array();
            foreach ($result_set->result_array() as $row) {
                $result["orders"][] = $row;
            }
            $this->response($result, 200);
            exit;
        } else {
            $result["status"] = 0;
            $result["message"] = "No Orders Found";
            $this->response($result, 200);
            exit;
        }
    }

    function balance_update_post_old() {

        //$user = $this->post('user');
        //$user_role= 'DEALER';
        $order_id = $this->post('order_id');
        //$amount = $this->post('amount');
        //$transaction_mode= 'credit';
        $payment_mode = $this->post('payment_mode');
        $cheque_no = $this->post('cheque_no');
        $deposit_date = $this->post('deposit_date');
        $cheque_status = $this->post('cheque_status');
        $account_name = $this->post('account_name');
        $account_number = $this->post('account_number');
        $bank_name = $this->post('bank_name');
        $action_by = $this->post('action_by');
        $action_role = $this->post('action_role');
        //$action_date= $dt;

        $dt = date("Y-m-d H:i:s");
        $flag = false;

        $table = "wallet_history";

        if ($cheque_no != "") {
            $cheque_set = $this->model_all->getTableData($table, array(
                "reference_no" => $cheque_no
            ));
            if ($cheque_set->num_rows() == 0) {
                if ($cheque_status == 'bounce') {
                    $status = '0';
                } else if ($cheque_status == 'pass') {
                    $status = '2';
                } else {
                    $status = '2';
                }
                if ($deposit_date != "") {
                    $deposit_date = date("Y-m-d", strtotime($deposit_date));
                }

                $amount = $this->model_all->getTableDataFromQuery("select order_value, orderedby from seller_orders where id=$order_id")->row();
                $data = array(
                    "transaction_mode" => 'credit',
                    "payment_mode" => $payment_mode,
                    "reference_no" => $cheque_no,
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
                $payment = $this->model_all->update($data, array(
                    "order_id" => $order_id
                        ), $table);
                $this->model_all->update("update sellers set wallet=wallet+$amount->order_value where id=$amount->orderedby");
                if ($payment > 0) {
                    $result["status"] = 1;
                    $result["message"] = "Payment Credited Successfully";
                }
                $this->response($result, 200);
                exit;
            } else {
                $data = array(
                    "cheque_status" => 'pass',
                    "status" => '2'
                );
                $cheque_status = $this->model_all->update($data, array(
                    "reference_no" => $cheque_no
                        ), $table);
                if ($cheque_status) {
                    $result["status"] = 1;
                    $result["message"] = "Cheque Status Updated Successfully";
                }
                $this->response($result, 200);
                exit;
            }
        } else {
            $data = array(
                "transaction_mode" => 'credit',
                "payment_mode" => $payment_mode,
                "account_name" => $account_name,
                "account_number" => $account_number,
                "bank_name" => $bank_name,
                "action_by" => $action_by,
                "action_role" => $action_role,
                "action_date" => $dt,
                "status" => '2'
            );
            $payment = $this->model_all->update($data, array(
                "order_id" => $order_id
                    ), $table);
            if ($payment > 0) {
                $result["status"] = 1;
                $result["message"] = "Payment Credited Successfully";
            }
            $this->response($result, 200);
            exit;
        }
    }

    function cheque_list_get() {

        $user = $this->get('user');
        $fdate = $this->get('fromdate');
        $tdate = $this->get('todate');
        if ($fdate != "" && $tdate != "") {
            $fromdate = date("Y-m-d", strtotime($fdate));
            $todate = date("Y-m-d", strtotime($tdate));
            //echo "select wh.id, wh.transaction_date, wh.reference_no, wh.action_date,wh.cheque_deposit_date, so.order_id, e.uniq_id, e.uniq_id, so.order_id from wallet_history wh, employees e, seller_orders so where wh.order_id=so.id and wh.action_by=e.id and wh.transaction_date between'2018-09-19 00:00:00' and '2018-09-26 23:59:59'";
            $result_set = $this->model_all->getTableDataFromQuery("select wh.id, wh.transaction_date, wh.reference_no as cheque_no, wh.action_date,wh.cheque_deposit_date, so.order_id, e.uniq_id, e.uniq_id, so.order_id from wallet_history wh, employees e, seller_orders so where wh.order_id=so.id and wh.action_by=e.id and wh.transaction_date between'$fromdate 00:00:00' and '$todate 23:59:59' and wh.payment_mode='cheque'");
        } else {
            $result_set = $this->model_all->getTableDataFromQuery("select wh.id, wh.transaction_date, wh.reference_no as cheque_no, wh.action_date,wh.cheque_deposit_date, so.order_id, e.uniq_id from wallet_history wh, seller_orders so, employees e where wh.user_id='$user' and wh.action_by=e.id and wh.order_id=so.id and wh.payment_mode='cheque'");
        }
        if ($result_set->num_rows() > 0) {
            $result["status"] = 1;
            $result["message"] = "Cheque Details";
            $result["details"] = array();
            foreach ($result_set->result_array() as $row) {
                if ($row['cheque_no'] != "") {
                    if ($row['transaction_date'] != "") {
                        $row['transaction_date'] = date("d-m-Y", strtotime($row['transaction_date']));
                    }
                    if ($row['action_date'] != "") {
                        $row['action_date'] = date("d-m-Y", strtotime($row['action_date']));
                    }
                    if ($row['cheque_deposit_date'] != "") {
                        $row['cheque_deposit_date'] = date("d-m-Y", strtotime($row['cheque_deposit_date']));
                    }
                    $result["details"][] = $row;
                }
            }
            $this->response($result, 200);
            exit;
        } else {
            $result["status"] = 0;
            $result["message"] = "No Cheque Details Found";
            $this->response($result, 200);
            exit;
        }
    }

    function cheque_details_get() {
        $user = $this->get('user');
        $pid = $this->get('id');
        $result_set = $this->model_all->getTableDataFromQuery("select wh.reference_no as cheque_no , wh.amount, wh.cheque_status,wh.transaction_date as cheque_deposit_date, wh.bank_name,wh.account_name,wh.account_number, s.dealer_code, s.company_name from wallet_history wh, sellers s where wh.user_id='$user' and s.id='$user' and wh.id='$pid'");
        if ($result_set->num_rows() > 0) {
            $result["status"] = 1;
            $result["message"] = "Cheque Details";
            $result["details"] = array();
            foreach ($result_set->result_array() as $row) {
                if ($row["cheque_deposit_date"] != "") {
                    $row["cheque_deposit_date"] = date("Y-m-d", strtotime($row["cheque_deposit_date"]));
                }
                $result["details"][] = $row;
            }
            $this->response($result, 200);
            exit;
        } else {
            $result["status"] = 0;
            $result["message"] = "No Cheque Details Found";
            $this->response($result, 200);
            exit;
        }
    }

    function statement_details_get() {
        $user = $this->get('user');
        $result_set = $this->model_all->getTableDataFromQuery("SELECT g.grade_amount, s.grade, wh.reference_no as cheque_no, wh.cheque_deposit_date, wh.transaction_mode, wh.transaction_date, wh.action_date, wh.amount from wallet_history wh, grades g, sellers s where wh.user_id='$user' and s.id='$user' and g.id=s.grade");


        $debit = $this->model_all->getTableDataFromQuery("select sum(amount) as debit_bal from wallet_history where user_id='$user' and transaction_mode='debit' and status='0'")->row()->debit_bal;
        $credit = $this->model_all->getTableDataFromQuery("select sum(amount) as credit_bal from wallet_history where user_id='$user' and transaction_mode='credit' and status='2'")->row()->credit_bal;

        $amnt = $debit - $credit;

        if ($amnt < 0) {
            $bamnt = $amnt * -1;
        } else {
            $bamnt = $amnt;
        }

        if ($result_set->num_rows() > 0) {
            $result["status"] = 1;
            $result["message"] = "Statement Details";
            $result["statementdetails"] = array();
            foreach ($result_set->result_array() as $row) {

                if ($row['cheque_no'] == "") {

                    $row['cheque_no'] = 'NA';
                }

                if ($row['cheque_deposit_date'] != "0000-00-00") {
                    $row['cheque_date'] = date("d-m-Y", strtotime($row['cheque_deposit_date']));
                }

                if ($row['transaction_date'] != "0000-00-00") {
                    $row['transaction_date'] = date("d-m-Y", strtotime($row['transaction_date']));
                }
                if ($row['payment_mode'] == "cheque") {
                    $row['cheque_date'] = $row['transaction_date'];
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

                $row['closing_balance'] = $row['grade_amount'] - $bamnt;
                $result["statementdetails"][] = $row;
            }
            $this->response($result, 200);
            exit;
        } else {
            $result["status"] = 0;
            $result["message"] = "No Statement Details Found";
            $this->response($result, 200);
            exit;
        }
    }

    function statement_get() {

        $user = $this->get('user');
        $fromdate = $this->get('fromdate');
        $todate = $this->get('todate');
        $before_day = date('Y-m-d', strtotime(' -1 day'));
        $condition = "";
        if ($fromdate != "" && $todate == "") {
            $fromdate = date("Y-m-d", strtotime($fromdate));
            $condition .= " and date(w.`transaction_date`)='$fromdate'";
        } else if ($fromdate != "" && $todate != "") {
            $fromdate = date("Y-m-d", strtotime($fromdate));
            $todate = date("Y-m-d", strtotime($todate));
            $condition .= " and w.`transaction_date` between '$fromdate 00:00:00' and '$todate 23:59:59'";
        }

        $initial_pending = 0.00;
        $initial_date = "";
        $seller_query = $this->model_all->getTableDataFromQuery("select * from closing_balance where dealer_id='$user' and closing_date='$before_day'");
        if ($seller_query->num_rows() > 0) {
            $seller_rs = $seller_query->row();
            $initial_pending = $seller_rs->closing_balance;
            $initial_date = $seller_rs->closing_date;
        }


        $debit_amount = $this->model_all->getTableDataFromQuery("select sum(amount)  as debit_amount  from wallet_history w where w.user_id='$user' and w.`transaction_date` < '$fromdate 00:00:00' and transaction_mode='debit'")->row()->debit_amount; // Amount

        $credit_amount = $this->model_all->getTableDataFromQuery("select sum(amount)  as credit_amount from wallet_history w where w.user_id='$user' and  w.`transaction_date` < '$fromdate 00:00:00' and transaction_mode='credit'")->row()->credit_amount; // Amount

        $total_pending = $initial_pending - $debit_amount;

      /*  if ($total_pending >= $credit_amount) {
            $total_pending = $total_pending - $credit_amount;
        } else {
            $total_pending = 0.00;
        }*/

        $total_pending = $total_pending + $credit_amount;
        if(empty($total_pending)){
            $total_pending = 0.00;
        }


        $result["pending_amount"] = $total_pending;

        $debit_summary = 0.00;
        $credit_summary = 0.00;


        $object = array();
        if($total_pending<0){
           $object["transaction_type"] = "Dr.";
           $object["debit_amount"]= $total_pending*-1;
           $object["credit_amount"] = "";

        }else{
           $object["transaction_type"] = "Cr.";
           $object["debit_amount"] =  "";
           $object["credit_amount"] =  $total_pending;
        }
        
        $object["action_date"]= date("d-m-Y", strtotime($fromdate));
        
        $object["particular"]= "Opening Balance";
       
        
        $debit_summary = $total_pending;
        $result["tabular"][]= $object;




        $statement_query = $this->model_all->getTableDataFromQuery("select * from wallet_history w where w.amount!=0 and w.user_id='$user' $condition ");


        if ($statement_query->num_rows() > 0) {
            $result["status"] = 1;
            $result["message"] = "Statement Details";
            $result["statementdetails"] = array();
            foreach ($statement_query->result_array() as $row) {

                if ($row['reference_no'] != "" && $row['payment_mode'] == "cheque") {
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


                $result["statementdetails"][] = $row;



                $object = array();
                $object["action_date"] = date("d-m-Y", strtotime($row['transation_date']));
                if ($row['transaction_mode'] == 'credit') {
                    $object["transaction_type"] = "Cr.";
                    $object["credit_amount"] = $row['amount'];
                    $object["debit_amount"] = "";
                    $credit_summary += $row['amount'];
                } else {
                    $object["transaction_type"] = "Dr.";
                    $object["dedit_amount"] = $row['amount'];
                    $object["credit_amount"] = "";
                    $debit_summary += $row['amount'];
                }

                $row['closing_balance'] = $result['pending_amount'] + $debit_summary - $credit_summary;
                $object["particular"] = $row['particular'];

                $result["tabular"][] = $object;
            }
            $result["debit_summary "] = $debit_summary;
            $result["credit_summary"] = $credit_summary;
        } else {
            $result["status"] = 0;
            $result["message"] = "No Statement Details Found";
        }

        $this->response($result, 200);
        exit;
    }

    function balance_update_post() {

        $user = $this->post('user');
        $user_role = 'DEALER';
        // $order_id = $this->post('order_id');
        $amount = $this->post('amount');
        $discount = $this->post('discount');
        $branch = $this->post('branch');
        $discount_point_id = $this->post('discount_point_id');
        $discount_point = $this->post('discount_point');
        $transaction_mode = 'credit';
        $payment_mode = $this->post('payment_mode');
        $cheque_no = $this->post('cheque_no');
        $deposit_date = $this->post('deposit_date');
        $cheque_status = $this->post('cheque_status');
        $account_name = $this->post('account_name');
        $account_number = $this->post('account_number');
        $bank_name = $this->post('bank_name');

        $action_by = $this->post('action_by');
        $action_role = "trade"; //$this->post('action_role');
        $transaction_no = $this->post('transaction_no');
        // $transaction_date = $this->post('transaction_date');
        $flag = false;

        //   print_r($this->post());


        if ($payment_mode == "cheque" || $payment_mode == "CHEQUE") {
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

        $discount_amount = 0.00;
        if ($discount != 0.00) {
            $discount_amount = round(($amount * $discount) / 100, 2);
        }


        //$action_date= $dt;

        $dt = date("Y-m-d H:i:s");
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


            $data = array(
                "user_id" => $user,
                "user_role" => 'DEALER',
                "prev_balance" => 0.00,
                "amount" => $amount,
                "branch" => $branch,
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



            $notify_data = $this->model_all->getDealerExecutive($user, $branch);
            $payload = array();
            $ndata = array();
            if ($notify_data["dealer"]["fcm_key"] != "") {

                $payload['title'] = "Welcome to Nova";
                $payload['body'] = "";  /// Message goes here
                $payload['icon'] = "";  // Name of the icon in the play store
                $payload['click_action'] = "mainactivity";  // For android click activity
               // $ndata['id'] = $order_id;  // For custom value if any
                $payload['to'] = $notify_data["dealer"]["fcm_key"];   // Receiver FCM id
                $ndata['role'] = "DEALER";   // For custom value if any
            }


            if ($cheque_status == "bounce") {
                $data["particular"] = "Cheque No : " . $reference_number . " Deposited.";
                $row_id = $this->model_all->save($data, $table);
                if ($notify_data["dealer"]["fcm_key"] != "") {
                    $body = $data["particular"];
                    $payload['body'] = $body;
                    $ndata['id'] = $row_id;
                    $this->model_all->save(array("notification" => $body, "notify_type" => "credited", "user_role" => "DEALER", "user_id" => $user, "branch" => $branch, "is_seen" => "N", "notifiy_on" => date("Y-m-d H:i:s"),"related_id"=>$user), "notifications");
                    $this->fcm->send($payload['to'], $payload, $ndata);
                }
                $data["particular"] = "Cheque No : " . $reference_number . " bounced.";
                $data["transaction_mode"] = "debit";
                $row_id = $this->model_all->save($data, $table);
                if ($notify_data["dealer"]["fcm_key"] != "") {
                    $body = $data["particular"];
                    $payload['body'] = $body;
                    $ndata['id'] = $row_id;
                    $this->model_all->save(array("notification" => $body, "notify_type" => "credited", "user_role" => "DEALER", "user_id" => $user, "branch" => $branch, "is_seen" => "N", "notifiy_on" => date("Y-m-d H:i:s"),"related_id"=>$user), "notifications");
                    $this->fcm->send($payload['to'], $payload, $ndata);
                }
            } else {
                $row_id = $this->model_all->save($data, $table);
                if ($row_id > 0) {
                    $flag = true;
                    $this->model_all->getTableDataFromQuery("update sellers set `wallet`=`wallet`+$amount  where id='$user'");
                    $temp_amount = $amount;
                    $particular = $discount_point;
                    $order_str = "";
                    $order_query = $this->model_all->getTableDataFromQuery("select id,order_id,order_value,paid from seller_orders where orderedby='$user' and paid_status!='2' order by id");
                    if ($order_query->num_rows() > 0) {
                        foreach ($order_query->result() as $order_row) {
                            if ($temp_amount > 0) {
                                $balance_amount = $order_row->order_value - $order_row->paid;

                                if ($temp_amount >= $balance_amount) {
                                    $this->model_all->getTableDataFromQuery("update seller_orders set paid=paid+$balance_amount , paid_status='2' where id='$order_row->id'");
                                    $temp_amount = $temp_amount - $balance_amount;
                                    $order_str = $order_str . $order_row->order_id . ",";
                                } else if ($temp_amount > 0) {
                                    $this->model_all->getTableDataFromQuery("update seller_orders set paid=paid+$temp_amount , paid_status='1' where id='$order_row->id'");
                                    $temp_amount = 0.00;
                                    $order_str = $order_str . $order_row->order_id . ",";
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
                            $order_str = trim($order_str, ",");
                            $particular = $particular . " " . $order_str . " through " . $payment_mode;
                            if ($payment_mode == "cheque") {
                                $particular = $particular . " with cheque No " . $reference_number;
                            }
                            if ($transaction_no != "")
                                $particular .= " Transaction No:" . $transaction_no . ". ";
                            if ($bank_name != "")
                                $particular .= " Bank Name: " . $bank_name . ".";

                            $this->model_all->update(array(
                                "order_id" => $order_str,
                                "particular" => $particular
                                    ), array(
                                "id" => $row_id
                                    ), $table);

                            if ($notify_data["dealer"]["fcm_key"] != "") {
                                $body = $particular;
                                $payload['body'] = $body;
                                $ndata['id'] = $row_id;
                                $this->model_all->save(array("notification" => $body, "notify_type" => "credited", "user_role" => "DEALER", "user_id" => $user, "branch" => $branch, "is_seen" => "N", "notifiy_on" => date("Y-m-d H:i:s"),"related_id"=>$user), "notifications");
                                $this->fcm->send($payload['to'], $payload, $ndata);
                            }
                        }
                    }
                }
            }

            if ($discount_amount != 0) {

                $payment_mode = "discount";
                $transaction_date = $dt;
                $deposit_date = "";
                $cheque_status = "";
                $account_name = "";
                $account_number = "";
                $bank_name = "";
                $status = "1";
                $data = array(
                    "user_id" => $user,
                    "user_role" => 'DEALER',
                    "prev_balance" => 0.00,
                    "branch" => $branch,
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
                    $flag = true;
                    $this->model_all->getTableDataFromQuery("update sellers set `wallet`=`wallet`+$discount_amount  where id='$user'");
                    $temp_amount = $discount_amount;
                    $particular = "Payment towards ";
                    $order_str = "";
                    $order_query = $this->model_all->getTableDataFromQuery("select id,order_value,paid,order_id from seller_orders where orderedby='$user' and paid_status!='2' order by id");
                    if ($order_query->num_rows() > 0) {
                        foreach ($order_query->result() as $order_row) {
                            if ($temp_amount > 0) {
                                $balance_amount = $order_row->order_value - $order_row->paid;

                                if ($temp_amount >= $balance_amount) {
                                    $this->model_all->getTableDataFromQuery("update seller_orders set paid=paid+$balance_amount , paid_status='2' where id='$order_row->id'");
                                    $temp_amount = $temp_amount - $balance_amount;
                                    $order_str = $order_str . $order_row->order_id . ",";
                                } else if ($temp_amount > 0) {
                                    $this->model_all->getTableDataFromQuery("update seller_orders set paid=paid+$temp_amount , paid_status='1' where id='$order_row->id'");
                                    $temp_amount = 0.00;
                                    $order_str = $order_str . $order_row->order_id . ",";
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
                            $order_str = trim($order_str, ",");
                            $particular = $particular . " " . $order_str . " through " . $payment_mode;
                            if ($payment_mode == "cheque") {
                                $particular = $particular . " with cheque No " . $reference_no;
                            }
                            if ($transaction_no != "")
                                $particular .= " Transaction No: " . $transaction_no . ".";
                            if ($bank_name != "")
                                $particular .= " Bank Name: " . $bank_name . ".";
                        }
                        $this->model_all->update(array(
                            "order_id" => $order_str,
                            "particular" => "On Payment towards receipt of rupees " . $amount . " @" . $discount . "% allowed as " . $discount_point
                                ), array(
                            "id" => $row_id
                                ), $table);
                        
                        if ($notify_data["dealer"]["fcm_key"] != "") {
                                $body = $particular;
                                $payload['body'] = $body;
                                $ndata['id'] = $row_id;
                                $this->model_all->save(array("notification" => $body, "notify_type" => "credited", "user_role" => "DEALER", "user_id" => $user, "branch" => $branch, "is_seen" => "N", "notifiy_on" => date("Y-m-d H:i:s"),"related_id"=>$user), "notifications");
                                $this->fcm->send($payload['to'], $payload, $ndata);
                            }
                    }
                }
            }
        }
        if ($flag) {
            $result["status"] = 1;
            $result["message"] = "Payment Credited Successfully";
        } else {
            $result["status"] = 0;
            $result["message"] = "Something went wrong. Try again";
        }

        $this->response($result, 200);
        exit;
    }

}
