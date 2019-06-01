<?php
$ware_name = "";
$ware_logo = "";
$ware_gstin = "";
$ware_address = "";
$cgst = "";
$sgst = "";
$service_charge = "";


if (isset($settings_qry)) {
    foreach ($settings_qry->result() as $settings) {
        $ware_name = $settings->name;
        $ware_logo = $settings->logo;
        $ware_gstin = $settings->gstin;
        $ware_address = $settings->address;
        $cgst = $settings->cgst;
        $sgst = $settings->sgst;
        $service_charge = $settings->service_charge;
    }
}

$seller_address = "";
$order_date = "";
$invoice = "";
if (isset($invoice_qry)) {
    foreach ($invoice_qry->result() as $invoice) {
        $sname = $invoice->sname;
         $order_date  = $invoice->order_date ;
        if ($order_date != "") {
            $order_date = date("d-m-Y");
        }
        $invoiceid = $invoice->invoice_id;

        $seller_address = $invoice->address;
    }
}
?>

<table border="1" cellspacing="0px" cellpadding="5px">
    <tr><th colspan="3" align="center">RETAIL INVOICE/TRANSPORTER COPY</th></tr>
    <tr><th style="width:30%;border-right:none"><img src="<?=base_url()?>logo/<?= $ware_logo ?>" style="width:50px;height:50px"/></th><th style="width:40%;border-left:none" align="center"><h3><?= $settings->name ?></h3></th><th style="width:30%;border-left:none" align="right">GST NO : <?= $ware_gstin ?> <br>Date : <?= $order_date ?></th></tr>
    <tr>
        <td colspan="3">
            <table border="1" cellspacing="0px" cellpadding="5px">
                <tr>
                    <th>BILLING TO</th>
                    <th>SHIPPING TO</th>
                </tr>
                <tr>
                    <td><?= $ware_address ?></td>
                    <td><?= $seller_address ?></td>
                </tr>

            </table>
            <h4>Invoce : #<?= $invoiceid ?></h4>
            <table border="1" cellspacing="0px" style="width:100%" cellpadding="2px" width="100%">
                <tr>

                    <th style="width:32%">SKU-Product Name</th>

                    <th style="width:6%">MRP</th>
                    <th style="width:6%">Units</th>
                    <th style="width:8%">Unit Price</th>
                    <th style="width:8%">Taxable</th>
                    <th style="width:12%" colspan="2">CGST</th>
                    <th style="width:12%" colspan="2">SGST</th>
                    <th style="width:8%">Charges</th>
                    <th style="width:8%">Amount</th>

                </tr>
                    <?php
                    $final_total = 0.00;
                    $total_cgst = 0.00;
                    $total_sgst = 0.00;
                    $total_taxable = 0.00;
                    $total_scharge = 0.00;
                    if (isset($items_qry)) {

                        foreach ($items_qry->result() as $items) {

                            // $items_qry = $this->model_all->getTableDataFromQuery("SELECT si.qty,si.amount, CONCAT(s.first_name,s.last_name)  as seller,i.itemname,i.brand,u.unit_name FROM `seller_items` si, items i ,sellers s,unit_sizes u where i.id=si.item_id and s.id = si.seller_id  and u.unit_id=i.unit_size and si.sellet_invoice_pk='$id'");
                            $mrp = $items->mrp;
                            $qty = $items->qty;
                            $unit_price = $items->sellingprice;
                            if($items->status==1 and $items->qty!=$items->picked_qty){
                              $taxable = $items->picked_qty*$items->sellingprice;
                              $qty=$items->picked_qty;
                            }else{
                              $taxable = $items->amount;
                            }
                            

                            $cgst_amount =round( $cgst * ($taxable) / 100,2);
                            $sgst_amount = round($sgst * ($taxable) / 100,2);
                            $total_cgst += $cgst_amount;
                            $total_sgst += $sgst_amount;
                            $total_taxable += $taxable;
                            $total_scharge += $service_charge;
                            $row_total = $taxable + $cgst_amount + $sgst_amount;
                            $final_total += $row_total;
                            ?>

                        <tr>


                            <td ><?= $items->itemname ?></td>
                            <td><?= $mrp ?></td>
                            <td><?= $qty ?></td>
                            <td><?= $unit_price ?></td>
                            <td><?= $taxable ?></td>
                            <td><?= $cgst ?></td>
                            <td><?= $cgst_amount ?></td>
                            <td><?= $sgst ?></td>
                            <td><?= $sgst_amount ?></td>
                            <td><?= $service_charge ?></td>
                            <td><?= $row_total ?></td>
                        </tr>
                        <?php
                    }
                }
                ?>



                <tr>
                    <th>Total Amount : </th>
                    <th colspan="4" align="right"><?=$total_taxable?></th>
                    <th colspan="2" align="right"><?=round($total_cgst,2)?></th>
                    <th colspan="2" align="right"><?=round($total_sgst,2)?></th>
                    <th></th>
                    <th><?=$final_total?></th>

                </tr>
                <tr>
                    <th colspan="9" align="left">Charges (Convenience Charges + Shipping Charges) :</th>
                    <th><?=$total_scharge?></th>
                    <th></th>
                </tr>
                <?php
                  $final_total += $total_scharge;
                ?>
                <tr>
                    <th colspan="10" align="left">Gross Paid/Payable (Amount Includes All Applicable Taxes) : </th>
                    <th><?=$final_total?></th>
                </tr>
                <tr>
                    <th colspan="13" align="left">Seller Address : <?= $seller_address ?></th>
                </tr>
            </table>
            <h4>GST Information</h4>
            <table border="1" cellspacing="0px" style="width:100%">
                <tr>
                    <th align="center">Invoice Date</th>
                    <th align="center">Invoice No</th>
                    <th align="center">Taxable</th>
                    <th align="center">GST</th>
                    <th align="center">Charges</th>
                    <th align="center">Total Amount</th>
                </tr>
                <tr>
                    <td align="center"><?= $order_date ?></td>
                    <td align="center"><?= $invoiceid ?></td>
                    <td align="center"><?=$total_taxable?></td>
                    <td align="center"><?=$total_sgst+$total_sgst?></td>
                    <td align="center"><?=$total_scharge?></td>
                    <td align="center"><?=$final_total?></td>
                </tr>
               
                <tr>
                    <th colspan="5" align="left">Gross Paid/Payable (Amount Includes All Applicable Taxes) :</th>
                    <th><?= round($final_total)?></th>


                </tr>




            </table>


        </td>
    </tr>


</table>
<h5>Declaration</h5>

<ol>   
    <li>Goods once sold will not be taken back.</li>
    <li>Once we deliver the goods please check the stock after that we are not responsible for any shortage and leakage.</li>
    <li>In Case, If the receiver does not take the delivery of all the items listed in the Invoice then this cannot be treated as Final Invoice.</li>
    <li>For Final Paid Invoice Login to Mystore App and get all purchase & delivery details of each line Item.</li>
    <li>We don`t Accept Coins at the time of delivery. We request you to cooperate with us.</li>
    <li>Please submit GST registration number to take input tax credit. To submit give missed call to  or login to Mystore app update your KYC.</li>

</ol>

<h6>This invoice is system generated format,no need of signature and seal</h6>
