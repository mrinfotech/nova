<?php

require(APPPATH . '/libraries/REST_Controller.php');

class Wallet extends REST_Controller {

    public function __construct() {
        parent::__construct();
        $this->load->model('model_all');
    }

    function history_get() {
        $user = $this->get('user');
        $fromdate = $this->get('fromdate');
        $todate = $this->get('todate');
        $status = $this->get('status');
        $role = $this->get('role');

        $condition = "";
        if ($role == "DEALER" && $user != "")
            $condition .= " and w.user_role='DEALER' and user_id='$user' and o.orderedby='$user'";

        if ($status == "credit") {
            $condition .= " and w.transaction_mode='credit'";
        } else if ($status == "debit") {
            $condition .= " and w.transaction_mode='debit'";
        }



        $branch = $this->get('branch');
        if ($branch != "") {
            $condition .= " and o.branch_id='$branch'";
        }

        if ($fromdate != "" && $todate == "") {
            $fromdate = date("Y-m-d", strtotime($fromdate));
            $condition .= " and date(w.`transaction_date`)='$fromdate'";
        } else if ($fromdate != "" && $todate != "") {
            $fromdate = date("Y-m-d", strtotime($fromdate));
            $todate = date("Y-m-d", strtotime($todate));
            $condition .= " and w.`transaction_date` between '$fromdate 00:00:00' and '$todate 23:59:59'";
        }



        $result_set = $this->model_all->getTableDataFromQuery("select w.id,w.reference_no,w.transaction_no,w.transaction_date, o.order_id, o.orderedon from wallet_history w,seller_orders o where o.id=w.order_id $condition order by w.transaction_date  asc");

        if ($result_set->num_rows() > 0) {
            $result["status"] = 1;
            $result["message"] = "Records Found";
            $result["total_records"] = $result_set->num_rows();



            foreach ($result_set->result_array() as $row) {

                if ($row['transaction_date'] != "") {
                    $row['transaction_date'] = date("d-m-Y", strtotime($row['transaction_date']));
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
        $id = $this->get('transaction_id');
        $user = $this->get('user');
//echo "select w.*, e.id, e.first_name, s.sales_manager from wallet_history w,seller_orders o, employees e, sellers s where o.orderedby='$user' and o.id=w.order_id and w.id='$id' and s.id='$user' and s.sales_manager=e.id order by w.transaction_date  desc";      
        $result_set = $this->model_all->getTableDataFromQuery("select w.*,o.orderedon,o.credit_date from wallet_history w,seller_orders o where o.orderedby='$user' and o.id=w.order_id and w.id='$id'  order by w.transaction_date  desc");
        // $followers = SELECT e.first_name, s.sales_manager from employees e, sellers s where s.id='330' and s.sales_manager=e.id
        if ($result_set->num_rows() > 0) {
            $result["status"] = 1;
            $result["message"] = "Records Found";
            $result["details"] = array();
            foreach ($result_set->result_array() as $row) {

                // $result_set = $this->model_all->getTableDataFromQuery("select e.uniq_id, e.first_name, s.sales_manager from wallet_history w,seller_orders o, employees e, sellers s where o.orderedby='$user' and o.id=w.order_id and w.id='$id' and s.id='$user' and s.sales_manager=e.id order by w.transaction_date  desc");

                $row['follower_name'] = "";
                $row['follower_id'] = "";
                $row['follower_pkey'] = "";

                $row['days_left'] = "";
                $row['days_delay'] = "";
                $dt = date("Y-m-d");
               

                if ($row['credit_date'] != "") {
                    $row['credit_date'] = date("Y-m-d", strtotime($row['credit_date']));
                   $now = strtotime($dt);
                   $credit_date = strtotime($row['credit_date']);
                 // echo $dt." ".$row['credit_date'];

                    if ($row['credit_date'] >= $dt) {
                        // or your date as well
                        $datediff = $credit_date-$now;
                        $days = round($datediff / (60 * 60 * 24));
                        $row['days_left'] = $days . " Days";
                        $row['days_delay'] = "0 Days";
                    } else {
                        $datediff = $now-$credit_date;
                        $days = round($datediff / (60 * 60 * 24));
                        $row['days_left'] = "0 Days";
                        $row['days_delay'] = ($days . " Days");
                    }
                }

                $result["details"][] = $row;
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

}
