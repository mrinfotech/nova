<?php

require(APPPATH . '/libraries/REST_Controller.php');

class Invoice extends REST_Controller {

    public function __construct() {
        parent::__construct();
        $this->load->model('model_all');
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
        $this->load->view('pdf', array("viewdata" => $viewdata), true);
    }

    function full_details_get($order) {

        $condition = "";
        $sub_status = 0;


        $result_set = $this->model_all->getTableDataFromQuery("SELECT o.*,b.name as seller,i.id as itemid,i.itemname,i.brand,bp.margin_price as sellingprice,bp.pay,ip.hsn_code,ip.pack_qty,ip.cgst,ip.sgst,ip.igst FROM `seller_order_items` o, items i ,branches b,branch_prices bp,item_prices ip where bp.id=o.branch_price_id and bp.branch_id=b.id  and bp.itemprice_id = ip.id and ip.item_id=i.id and o.orderid='$order'   $condition order by o.action_status asc");
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

                $row["picked_qty"] = $row['qty']; // For making generic code
                $row['discount'] = ($row['mrp'] - $row['amount']);
                $row['margin'] = round((($row['mrp'] - $row['amount']) / $row['amount']) * 100, 2);
                $row['total_price'] = ($row['qty'] * $row['amount']);
                $total_units += $row['qty'];
                $total_savings += ($row['mrp'] - $row['amount']) * $row['qty'];
                $total_pay += ($row['qty'] * $row['amount']);




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

            $store_query = $this->model_all->getTableDataFromQuery("select se.company_name  as company_name,se.gstin,a.door_no,a.street_name,a.landmark,a.city,d.district,s.state,s.id as state_id,o.status,o.est_date,o.est_time,o.delivery_charges,o.service_charge,o.fa_status,o.admin_status,o.payment_type,o.reference_no,o.credit_date,o.paid,o.order_id,se.mobile,o.created_by,o.orderedby,o.orderedon,o.branch_id from sellers se,seller_orders o,addresses a,countries c,states s,districts d where se.id=o.orderedby and o.id='$order' and a.user_id=se.id and (a.user_role='seller' or a.user_role='DEALER') and a.is_default='1' and a.country=c.id and a.state=s.id and d.id=a.district  and s.country=c.id and d.state=s.id");
            if ($store_rs = $store_query->row()) {
                $branch = $store_rs->branch_id;
                $seller_address = "";
                $result["seller"]["name"] = $store_rs->company_name;
                if ($store_rs->door_no != "")
                    $seller_address = $store_rs->door_no . ",";
                if ($store_rs->street_name != "") {
                    $seller_address = $store_rs->door_no . ",";
                }
                if ($store_rs->landmark != "") {
                    $seller_address = $store_rs->landmark . ",";
                }
                if ($store_rs->district != "") {
                    $seller_address = $store_rs->district;
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
            $branch_qry = $this->model_all->getTableDataFromQuery("select o.state,s.state as state_name,b.contact_no,c.email,c.pan,c.cin_no,b.gst_no,c.company,c.url,c.logo,o.name,o.addressline1,b.id,o.state from offices o,branches b,companies c,states s where o.id=b.office_id and b.id='$branch' and b.company=c.company_id and o.state=s.id");
            if ($branch_rs = $branch_qry->row()) {
                $result["branch_details"]["company"] = $branch_rs->company;
                $result["branch_details"]["company_url"] = $branch_rs->url;
                $result["branch_details"]["logo"] = $branch_rs->logo;
                

                $result["branch_details"]["branch_name"] = $branch_rs->name;
                $result["branch_details"]["address"] = $branch_rs->addressline1;
                $result["branch_details"]["contact"] = $branch_rs->contact_no;
                $result["branch_details"]["gst"] = $branch_rs->gst_no;
                $result["branch_details"]["email"] = $branch_rs->email;
                $result["branch_details"]["cin_no"] = $branch_rs->cin_no;
                $result["branch_details"]["pan"] = $branch_rs->pan;
               
                $result["branch_state"] = $branch_rs->state;
                $result["branch_statename"] = $branch_rs->state_name;
            }

            $deopt_manger_qry = $this->model_all->getTableDataFromQuery("select mobile,ofc_contact from employees e,app_roles a where e.branch='$branch' and a.short_form='FM' and e.role_id=a.id ");
            if ($deopt_manger_rs = $deopt_manger_qry->row()) {
                $depot_contact = $deopt_manger_rs->ofc_contact;
                if ($depot_contact == "") {
                    $depot_contact = $deopt_manger_rs->mobile;
                }
            }
            $result["depot_contact"] = $depot_contact;
        }


        return $result;
    }

    function delivery_details_get($order, $status) {

        $condition = "";
        if ($status == "delivered") {
            $condition = " and o.action_status='1'";
        }
        if ($status == "rejected") {
            $condition = " and ((o.action_status='2') or (o.action_status='1' and o.picked_qty<sd.delivered_qty))";
        }
        $sub_status = 0;

        $result_set = $this->model_all->getTableDataFromQuery("SELECT o.*,sor.credit_date, sd.delivered_qty, b.name as seller,i.id as itemid,i.itemname,i.brand,bp.margin_price as sellingprice,bp.pay,ip.hsn_code,ip.pack_qty,ip.cgst,ip.sgst,ip.igst FROM `seller_order_items` o, items i ,branches b,branch_prices bp,item_prices ip,seller_pack_details sd, seller_orders sor where bp.id=o.branch_price_id and bp.branch_id=b.id  and bp.itemprice_id = ip.id and ip.item_id=i.id and o.orderid='$order' and o.action_status!='2' and sd.order_item_id=o.id and sd.packed_qty!=0 and sd.status='1' and o.orderid=sor.id $condition order by o.action_status asc");
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
                $row['discount'] = ($row['mrp'] - $row['amount']);
                $row['margin'] = round((($row['mrp'] - $row['amount']) / $row['amount']) * 100, 2);
                if ($status != "rejected" && $row['action_status'] != '2') {
                    if ($row['action_status'] == '0') {
                        $row['total_price'] = ($row['delivered_qty'] * $row['amount']);
                        $total_units += $row['delivered_qty'];
                        $total_savings += ($row['mrp'] - $row['amount']) * $row['delivered_qty'];
                        $total_pay += ($row['delivered_qty'] * $row['amount']);
                    } else if ($row['action_status'] == '1') {
                        $row['total_price'] = ($row['picked_qty'] * $row['amount']);
                        $total_units += $row['picked_qty'];
                        $total_savings += ($row['mrp'] - $row['amount']) * $row['picked_qty'];
        
                $total_pay += ($row['picked_qty'] * $row['amount']);
                    }

                    $total_items++;
                } else if ($status == "rejected") {
                    $total_units += $row['delivered_qty'];
                    $total_savings += ($row['mrp'] - $row['amount']) * $row['delivered_qty'];
                    $total_pay += ($row['delivered_qty'] * $row['amount']);
                    $total_items++;
                }
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

        /*
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
          $row['total_price'] = ($rej_qty * $row['amount']);
          if ($status == "rejected") {
          $total_units += $rej_qty;
          $total_savings += ($row['mrp'] - $row['amount']) * $rej_qty;
          $total_pay += ($rej_qty * $row['amount']);
          $total_items++;
          }
          } else if ($row['action_status'] == '2') {
          $row['qty'] = $row['delivered_qty'];
          $row['total_price'] = ($row['delivered_qty'] * $row['amount']);
          if ($status == "rejected") {
          $total_units += $row['delivered_qty'];
          $total_savings += ($row['mrp'] - $row['amount']) * $row['delivered_qty'];
          $total_pay += ($row['delivered_qty'] * $row['amount']);
          $total_items++;
          }
          }
          $row['discount'] = ($row['mrp'] - $row['amount']);
          $row['margin'] = round((($row['mrp'] - $row['amount']) / $row['amount']) * 100, 2);

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





          } */
        $result["status"] = $sub_status;
        if ($result["status"] == 1) {
            $result["message"] = "Records Found";
            $result["seller"] = array();
            $result["order"] = array();
            $result["transport"] = array();
            $result["branch_details"] = array();
            $branch = 0;



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

            $store_query = $this->model_all->getTableDataFromQuery("select se.company_name  as company_name,se.gstin,a.door_no,a.street_name,a.landmark,a.city,d.district,s.state,s.gst_code as state_id,o.status,o.est_date,o.est_time,o.delivery_charges,o.service_charge,o.fa_status,o.admin_status,o.payment_type,o.reference_no,o.credit_date,o.paid,o.order_id,se.mobile,o.created_by,o.orderedby,o.orderedon,o.branch_id from sellers se,seller_orders o,addresses a,countries c,states s,districts d where se.id=o.orderedby and o.id='$order' and a.user_id=se.id and (a.user_role='seller' or a.user_role='DEALER') and a.is_default='1' and a.country=c.id and a.state=s.id and d.id=a.district  and s.country=c.id and d.state=s.id");
            if ($store_rs = $store_query->row()) {
                $branch = $store_rs->branch_id;
                $seller_address = "";
                $result["seller"]["name"] = $store_rs->company_name;
                if ($store_rs->door_no != "")
                    $seller_address = $store_rs->door_no . ",";
                if ($store_rs->street_name != "") {
                    $seller_address = $store_rs->door_no . ",";
                }
                if ($store_rs->landmark != "") {
                    $seller_address = $store_rs->landmark . ",";
                }
                if ($store_rs->district != "") {
                    $seller_address = $store_rs->district;
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

            $branch_qry = $this->model_all->getTableDataFromQuery("select o.state,s.state as state_name,b.contact_no,c.email,c.pan,c.cin_no,b.gst_no,c.company,o.name,o.addressline1,b.id,o.state,b.acc_no,b.acc_holder_name,b.bank_name,b.bank_branch,b.ifsc from offices o,branches b,companies c,states s where o.id=b.office_id and b.id='$branch' and b.company=c.company_id and o.state=s.id");
            if ($branch_rs = $branch_qry->row()) {
                $result["branch_details"]["company"] = $branch_rs->company;
                $result["branch_details"]["branch_name"] = $branch_rs->name;
                $result["branch_details"]["address"] = $branch_rs->addressline1;
                $result["branch_details"]["contact"] = $branch_rs->contact_no;
                $result["branch_details"]["gst"] = $branch_rs->gst_no;
                $result["branch_details"]["email"] = $branch_rs->email;
                $result["branch_details"]["cin_no"] = $branch_rs->cin_no;
                $result["branch_details"]["pan"] = $branch_rs->pan;
                $result["branch_details"]["logo"] = "";
                $result["branch_state"] = $branch_rs->state;
                $result["branch_statename"] = $branch_rs->state_name;
                $result["branch_details"]["acc_no"] = $branch_rs->acc_no;
                $result["branch_details"]["acc_holder_name"] = $branch_rs->acc_holder_name;
                $result["branch_details"]["bank_name"] = $branch_rs->bank_name;
                $result["branch_details"]["bank_branch"] = $branch_rs->bank_branch;
                $result["branch_details"]["ifsc"] = $branch_rs->ifsc;
              
            }

            $deopt_manger_qry = $this->model_all->getTableDataFromQuery("select mobile,ofc_contact from employees e,app_roles a where e.branch='$branch' and a.short_form='FM' and e.role_id=a.id ");
            if ($deopt_manger_rs = $deopt_manger_qry->row()) {
                $depot_contact = $deopt_manger_rs->ofc_contact;
                if ($depot_contact == "") {
                    $depot_contact = $deopt_manger_rs->mobile;
                }
            }
            $result["depot_contact"] = $depot_contact;
        }


        return $result;
    }

    function tax_invoice_get($order) {
        /* $id = $this->get('id');
          $data = array();
          $invoice_qry = $this->model_all->getTableDataFromQuery("select si.*,concat(s.first_name,' ',s.last_name) as sname,s.address from seller_items si,sellers s where si.id='$id' and si.seller_id=s.id ");
          if ($invoice_qry->num_rows() > 0) {
          $data['invoice_qry'] = $invoice_qry;
          $items_qry = $this->model_all->getTableDataFromQuery("SELECT si.status,si.mrp,si.sellingprice,si.qty,si.amount,si.picked_qty, CONCAT(s.first_name,s.last_name)  as seller,i.itemname,i.brand,u.unit_name FROM `seller_items` si, items i ,sellers s,unit_sizes u where i.id=si.item_id and s.id = si.seller_id  and u.unit_id=i.unit_size and si.sellet_invoice_pk='$id' and si.status='1'");
          $data['items_qry'] = $items_qry;
          }
          $settings_qry = $this->model_all->getTableDataFromQuery("select * from settings");
          $data['settings_qry']=$settings_qry;
          $viewdata = $this->load->view('invoice',$data,true);
          $this->load->helper('pdf_helper');
          $this->load->view('pdf',array("viewdata"=>$viewdata),true); */

        $items = $this->delivery_details_get($order, 'delivered');



        $data["items"] = $items;
        /*   $viewdata = $this->load->view('tax_invoice', $data, true);
          $this->load->helper('pdf_helper');
          $this->load->view('pdf', array("viewdata" => $viewdata), true); */
        //  echo $items["dealer_state"]." ".$items["branch_state"];
        if ($items["dealer_state"] != $items["branch_state"]) {
            $viewdata = $this->load->view('tax_invoice', $data, true);
        } else {
            $viewdata = $this->load->view('gst_tax_invoice', $data, true);
        }
        $this->load->helper('pdf_helper');
        $this->load->view('pdf', array("viewdata" => $viewdata), true);
    }
    
    
    function packed_details_get($order,$short_form,$user) {

        $condition = "";
        $sub_status = 0;
        $parent_order = 0;


        $result_set = $this->model_all->getTableDataFromQuery("SELECT o.*,sd.batch_no,sd.mfg_date,sd.exp_date,sd.packed_qty,sd.delivered_qty, b.name as seller,i.id as itemid,i.itemname,i.brand,bp.margin_price as sellingprice,bp.pay,ip.hsn_code,ip.pack_qty,ip.cgst,ip.sgst,ip.igst,bp.margin_price as single_piece_mrp FROM `seller_order_items` o, items i ,branches b,branch_prices bp,item_prices ip,seller_pack_details sd where bp.id=o.branch_price_id and bp.branch_id=b.id  and bp.itemprice_id = ip.id and ip.item_id=i.id and o.orderid='$order' and sd.order_item_id=o.id and sd.packed_qty!=0  $condition order by o.action_status asc");

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
                
                $row['qty']= $row['packed_qty']; // As they are asking packed_qty instead of original qty
    
            
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
                $transport_name = "";
                if($transport_row->transport_type=="reg"){
                   $transport_name= ucwords($transport_row->transport_name).",";
                }
                 $result["transport"]["transport_type"] = $transport_name.strtoupper($transport_row->transport_type);
                if ($transport_row->lr_no != "") {
                    $result["transport"]["lr_no"] = $transport_row->lr_no;
                } else if ($transport_row->vechicle_number != "") {
                    $result["transport"]["lr_no"] = $transport_row->vechicle_number;
                }

                $result["transport"]["contact"] = $transport_row->contact;
                $result["transport"]["supply_date"] = $transport_row->estimation_time;
                $result["transport"]["supply_place"] = "";
            }

            $store_query = $this->model_all->getTableDataFromQuery("select se.company_name  as company_name,se.gstin,a.pincode,a.address,a.door_no,a.street_name,a.landmark,a.city,d.district,s.state,s.gst_code as state_id,o.status,o.est_date,o.est_time,o.delivery_charges,o.service_charge,o.fa_status,o.admin_status,o.payment_type,o.reference_no,o.credit_date,o.paid,o.order_id,se.mobile,o.created_by,o.orderedby,o.orderedon,o.branch_id,o.parent_order from sellers se,seller_orders o,addresses a,countries c,states s,districts d where se.id=o.orderedby and o.id='$order' and a.user_id=se.id and (a.user_role='seller' or a.user_role='DEALER') and a.is_default='1' and a.country=c.id and a.state=s.id and d.id=a.district  and s.country=c.id and d.state=s.id");
            if($store_query->num_rows()>0) {
             if ($store_rs = $store_query->row()) {
                $parent_order = $store_rs->parent_order;
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

                if($parent_order==0){
                   $result["billto"]["address"] = $seller_address;
                   $result["billto"]["gstin"] = $store_rs->gstin;
                   $result["billto"]["state"] = $store_rs->state;
                   $result["billto"]["state_code"] = $store_rs->state_id;
                   $result["billto"]["name"] = $store_rs->company_name;

                }else{

                   if($short_form=="SE" || $short_form=="DEALER"){
                       $result["billto"]["address"] = $seller_address;
                       $result["billto"]["gstin"] = $store_rs->gstin;
                       $result["billto"]["state"] = $store_rs->state;
                       $result["billto"]["state_code"] = $store_rs->state_id;
                       $result["billto"]["name"] = $store_rs->company_name;
                   }else{

                
                    $old_branch =0;
                    $old_branch_qry = $this->model_all->getTableDataFromQuery("select branch_id from seller_orders where id='$parent_order'");
                    if($old_branch_qry->num_rows()>0){
                      $old_branch_row = $old_branch_qry->row();
                      $old_branch = $old_branch_row->branch_id;
                    }

                    $branch_qry = $this->model_all->getTableDataFromQuery("select o.state,s.state as state_name,s.gst_code,b.contact_no,c.email,c.pan,c.cin_no,b.gst_no,c.company,c.url,c.logo,c.signature,c.formal_name,o.name,o.addressline1,b.id,s.gst_code as state,b.acc_no,b.acc_holder_name,b.bank_name,b.bank_branch,b.ifsc from offices o,branches b,companies c,states s where o.id=b.office_id and b.id='$old_branch' and b.company=c.company_id and o.state=s.id");
                   if ($branch_qry->num_rows()>0) {
                      if($branch_rs = $branch_qry->row()) {
                         $result["billto"]["address"] = $branch_rs->addressline1;
                         $result["billto"]["gstin"] = $branch_rs->gst_no;
                         $result["billto"]["state"] = $branch_rs->state_name;
                         $result["billto"]["state_code"] = $branch_rs->gst_code;
                         $result["billto"]["name"] = $branch_rs->name.",".$branch_rs->company;  
                    
                      }
                   }
                 }

                }





                $result["order"]["id"] = $store_rs->order_id;
                $result["order"]["order_date"] = date("d-M-Y", strtotime($store_rs->orderedon));
                $result["order"][] = $store_rs->status;




                 $wallet = 0.00;
                $wallet_date = date("Y-m-d",strtotime($store_rs->orderedon));
                $wallet_qry = $this->model_all->getTableDataFromQuery("select * from closing_balance where closing_date<'$wallet_date' and dealer_id='$store_rs->orderedby' limit 0,1");
                if($wallet_qry->num_rows()>0){
                   $wallet_rs = $wallet_qry->row();
                   $wallet = $wallet_rs->closing_balance;

                }
//$store_rs->orderedon
                $debit = $this->model_all->tableFieldData("select sum(amount) as debit_bal from wallet_history where user_id='$store_rs->orderedby' and transaction_mode='debit' and date(transaction_date)='$wallet_date' and transaction_date<='$store_rs->orderedon' and status='1'","debit_bal");
        $credit = $this->model_all->tableFieldData("select sum(amount) as credit_bal from wallet_history where user_id='$store_rs->orderedby' and transaction_mode='credit' and date(transaction_date)='$wallet_date' and transaction_date<='$store_rs->orderedon' and status='1'","credit_bal");
if(empty($debit)){
    $debit = 0.00;
} 
if(empty($credit)){
    $credit = 0.00;
} 

$wallet = $wallet-$debit+$credit;



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

    function invoice_get($order,$short_form="",$user="") {

        $items = $this->packed_details_get($order,$short_form,$user);
        $data["items"] = $items;
        // echo json_encode($items);

        if ($items["dealer_state"] != $items["branch_state"]) {
            $viewdata = $this->load->view('tax_invoice', $data, true);
        } else {
            $viewdata = $this->load->view('intra_invoice', $data, true);
        }
       //$viewdata = $this->load->view('tax_invoice', $data, true);
        $this->load->helper('pdf_helper');
        $this->load->view('pdf', array("viewdata" => $viewdata), true);
    }

}
