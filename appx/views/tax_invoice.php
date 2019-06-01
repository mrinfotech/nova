<?php
$ware_name = "";
$ware_logo = "";
$ware_gstin = "";
$ware_address = "";
$cgst = "";
$sgst = "";
$service_charge = "";


if(isset($items["wallet"])) {
  $wallet = $items["wallet"];

}else{
  $wallet = 0.00;

}



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
        $order_date = $invoice->order_date;
        if ($order_date != "") {
            $order_date = date("d-m-Y");
        }
        $invoiceid = $invoice->invoice_id;

        $seller_address = $invoice->address;
    }
}

if (isset($items["seller"])) {

    $seller_address = $items["seller"]["address"];
    $seller_gst = $items["seller"]["gstin"];
    $seller_state_code = $items["seller"]["state_code"];
    $seller_state = $items["seller"]["state"];
    $seller_name = $items["seller"]["name"];
} else {
    $seller_address = "";
    $seller_gst = "";
    $seller_state_code = "";
    $seller_state = "";
    $seller_name = "";
}


if (isset($items["billto"])) {

    $billto_address = $items["billto"]["address"];
    $billto_gst = $items["billto"]["gstin"];
    $billto_state_code = $items["billto"]["state_code"];
    $billto_state = $items["billto"]["state"];
    $billto_name = $items["billto"]["name"];
} else {
    $billto_address = "";
    $billto_gst = "";
    $billto_state_code = "";
    $billto_state = "";
    $billto_name = "";
}

if (isset($items["order"])) {

    $order_id = $items["order"]["id"];
    $order_date = $items["order"]["order_date"];
    $seller_state_code = $items["seller"]["state_code"];
    $seller_state = $items["seller"]["state"];
    $seller_name = $items["seller"]["name"];
} else {
    $order_id = "";
    $order_date = "";
    $seller_state_code = "";
    $seller_state = "";
    $seller_name = "";
}

if (isset($items["transport"])) {
    $transport_type = $items["transport"]["transport_type"];
    $lr_no = $items["transport"]["lr_no"];
    $contact = $items["transport"]["contact"];
    $supply_date = $items["transport"]["supply_date"];
    $supply_place = $items["transport"]["supply_place"];
} else {
    $transport_type = "";
    $lr_no = "";
    $contact = "";
    $supply_date = "";
    $supply_place = "";
}

if (isset($items["branch_details"])) {
    $company_name = $items["branch_details"]["company"];
    $company_url = $items["branch_details"]["company_url"];
    $formal_name = $items["branch_details"]["formal_name"];
    $branch_name = $items["branch_details"]["branch_name"];
    $branch_address = $items["branch_details"]["address"];
    $branch_contact = $items["branch_details"]["contact"];
    $gst = $items["branch_details"]["gst"];
    $logo = $items["branch_details"]["logo"]; 
    $company_signature= $items["branch_details"]["company_signature"]; 
    $email = $items["branch_details"]["email"];
    $cin_no = $items["branch_details"]["cin_no"];
    $pan = $items["branch_details"]["pan"];
    $branch_acc_no = $items["branch_details"]["acc_no"];
    $branch_acc_holder_name = $items["branch_details"]["acc_holder_name"];
    $branch_bank_name= $items["branch_details"]["bank_name"];
    $branch_bank_branch= $items["branch_details"]["bank_branch"];
    $branch_bank_ifsc = $items["branch_details"]["ifsc"];



  
} else {
    $company_name = "";
    $formal_name = "";
    $company_url="";
    $branch_name = "";
    $branch_address = "";
    $branch_contact = "";
    $gst = "";
    $logo = "";
    $company_signature="";
    $email = "";
    $cin_no = "";
    $pan = "";
    $branch_acc_no="";
    $branch_acc_holder_name="";
    $branch_bank_name="";
    $branch_bank_branch="";
    $branch_bank_ifsc ="";
    
}
function getIndianCurrency($number)
{
    $decimal = round($number - ($no = floor($number)), 2) * 100;
    $hundred = null;
    $digits_length = strlen($no);
    $i = 0;
    $str = array();
    $words = array(0 => '', 1 => 'one', 2 => 'two',
        3 => 'three', 4 => 'four', 5 => 'five', 6 => 'six',
        7 => 'seven', 8 => 'eight', 9 => 'nine',
        10 => 'ten', 11 => 'eleven', 12 => 'twelve',
        13 => 'thirteen', 14 => 'fourteen', 15 => 'fifteen',
        16 => 'sixteen', 17 => 'seventeen', 18 => 'eighteen',
        19 => 'nineteen', 20 => 'twenty', 30 => 'thirty',
        40 => 'forty', 50 => 'fifty', 60 => 'sixty',
        70 => 'seventy', 80 => 'eighty', 90 => 'ninety');
    $digits = array('', 'hundred','thousand','lakh', 'crore');
    while( $i < $digits_length ) {
        $divider = ($i == 2) ? 10 : 100;
        $number = floor($no % $divider);
        $no = floor($no / $divider);
        $i += $divider == 10 ? 1 : 2;
        if ($number) {
            $plural = (($counter = count($str)) && $number > 9) ? 's' : null;
            $hundred = ($counter == 1 && $str[0]) ? ' and ' : null;
            $str [] = ($number < 21) ? $words[$number].' '. $digits[$counter]. $plural.' '.$hundred:$words[floor($number / 10) * 10].' '.$words[$number % 10]. ' '.$digits[$counter].$plural.' '.$hundred;
        } else $str[] = null;
    }
    $Rupees = implode('', array_reverse($str));
    $paise = ($decimal) ? "." . ($words[$decimal / 10] . " " . $words[$decimal % 10]) . ' Paise' : '';
    return ($Rupees ? $Rupees . 'Rupees ' : '') . $paise ;
}
function convert_number_to_words($number) {

    $hyphen = '-';
    $conjunction = ' and ';
    $separator = ', ';
    $negative = 'negative ';
    $decimal = ' point ';
    $dictionary = array(
        0 => 'zero',
        1 => 'one',
        2 => 'two',
        3 => 'three',
        4 => 'four',
        5 => 'five',
        6 => 'six',
        7 => 'seven',
        8 => 'eight',
        9 => 'nine',
        10 => 'ten',
        11 => 'eleven',
        12 => 'twelve',
        13 => 'thirteen',
        14 => 'fourteen',
        15 => 'fifteen',
        16 => 'sixteen',
        17 => 'seventeen',
        18 => 'eighteen',
        19 => 'nineteen',
        20 => 'twenty',
        30 => 'thirty',
        40 => 'fourty',
        50 => 'fifty',
        60 => 'sixty',
        70 => 'seventy',
        80 => 'eighty',
        90 => 'ninety',
        100 => 'hundred',
        1000 => 'thousand',
        1000000 => 'million',
        1000000000 => 'billion',
        1000000000000 => 'trillion',
        1000000000000000 => 'quadrillion',
        1000000000000000000 => 'quintillion'
    );

    if (!is_numeric($number)) {
        return false;
    }

    if (($number >= 0 && (int) $number < 0) || (int) $number < 0 - PHP_INT_MAX) {
        // overflow
        trigger_error(
                'convert_number_to_words only accepts numbers between -' . PHP_INT_MAX . ' and ' . PHP_INT_MAX, E_USER_WARNING
        );
        return false;
    }

    if ($number < 0) {
        return $negative . convert_number_to_words(abs($number));
    }

    $string = $fraction = null;

    if (strpos($number, '.') !== false) {
        list($number, $fraction) = explode('.', $number);
    }

    switch (true) {
        case $number < 21:
            $string = $dictionary[$number];
            break;
        case $number < 100:
            $tens = ((int) ($number / 10)) * 10;
            $units = $number % 10;
            $string = $dictionary[$tens];
            if ($units) {
                $string .= $hyphen . $dictionary[$units];
            }
            break;
        case $number < 1000:
            $hundreds = $number / 100;
            $remainder = $number % 100;
            $string = $dictionary[$hundreds] . ' ' . $dictionary[100];
            if ($remainder) {
                $string .= $conjunction . convert_number_to_words($remainder);
            }
            break;
        default:
            $baseUnit = pow(1000, floor(log($number, 1000)));
            $numBaseUnits = (int) ($number / $baseUnit);
            $remainder = $number % $baseUnit;
            $string = convert_number_to_words($numBaseUnits) . ' ' . $dictionary[$baseUnit];
            if ($remainder) {
                $string .= $remainder < 100 ? $conjunction : $separator;
                $string .= convert_number_to_words($remainder);
            }
            break;
    }

    if (null !== $fraction && is_numeric($fraction)) {
        $string .= $decimal;
        $words = array();
        foreach (str_split((string) $fraction) as $number) {
            $words[] = $dictionary[$number];
        }
        $string .= implode(' ', $words);
    }

    return $string;
}
?>
<style>
    body{
        font-size: 11px !important;
    }



    td{
        padding: 2px !important;
    }

    .bgcolor {
        background-color: #bcd6ee;
    }

    .font10{
        font-size: 8px;
    }

    .head p{
        margin-bottom: 5px;
    }

    .items table th,.items table td{
        font-size:10px;
    }
</style>
<table border="1" cellspacing="0px" cellpadding="4px" style="width:100%">

    <tr style="font-size:12px">
        <th style="width:50%;border-right:none;border-right:none;" align="center"><br><img src="<?php echo base_url() . 'logo/'.$logo ?>" style="width:150px;height:70px;" />
            <h6>Corporate Address : Plot N0.57, 1st Floor, Hanuman Nagar, Chinnatokatta,</h6>
            <h6> New Bowenpally, Secunderabad - 500011, Telangana</h6>
            <h6>Tel: +91-40-27957081 / 82</h6>
            <h6>Website: <?=$company_url?></h6>
        </th>
        <th style="width:50%;border-left:none;font-size: 10px;border-left:none;padding-top: 0px;" align="center" class="head" >
           <span style="margin:0px; color: #2a1770; font-size: 16px"><b><?= $company_name ?></b></span><br>
<?php if($formal_name!="") { echo "(Formally known as ".$formal_name.")"; } ?>
            <h4>Branch Address : <?= $branch_address ?></h4>
            <h4>e-Mail:<?= $email ?></h4>
<h4>CIN No: <?= $cin_no ?>; PAN#: <?= $pan ?></h4>
            <h4>GST  :# <?= $gst ?></h4>
        </th>
    </tr>
<!--    <tr>
        <td colspan="2"> <hr> </td>
    </tr>-->
    <tr>
        <td colspan="2" class="bgcolor" align="center" style="font-size:12px"> <b> TAX INVOICE</b> </td>
    </tr>
    <tr>
        <td colspan="2" class="" style="padding-left:0px">
            <table width="100%" border="1" style="font-size:9px;width: 100%" cellpadding="2px">
                <tr><th>Invoice No: </th><th><?= $order_id ?></th><th>Transport Mode:</th><th><?= $transport_type ?></th></tr>
                <tr><th>Invoice date</th><th><?= $order_date ?></th><th>L.R / Vehicle number:</th><th><?= $lr_no ?></th></tr>
                <tr><th>State Name / Code</th><th><?= $items["branch_statename"] ?> / <?= $items["branch_state"] ?></th><th>Date of Supply:</th><th><?= $supply_date ?></th></tr>
                <tr><th>Reverse Charge (Y/N):</th><th>N</th><th>Place of Supply</th><th><!--<?= $supply_place ?>--> <?= $branch_name ?></th></tr>
                <tr><th>Dispatch Contact :</th><th><?php
                        if (isset($items["depot_contact"])) {
                            echo $items["depot_contact"];
                        }
                        ?></th> <th>Delivery Contact:</th><th><?= $contact ?></th></tr>
                <tr><th colspan="4"></th></tr>
                <tr>
<td colspan="4">
<table border="1px" width="100%" cellspacing="0px" cellpadding="3px">
<tr>
<th colspan="2"  class="bgcolor" align="center" style="width:50%"><b>Bill to Party</b></th>
<th colspan="2" class="bgcolor"  align="center" style="width:50%"><b>Ship to Party</b></th></tr>

<tr>
<th style="width:17%">Name:</th><th class="font10" style="width:33%"><?= $billto_name ?></th>
<th style="width:17%">Name:</th><th class="font10" style="width:33%"><?= $seller_name ?></th></tr>
                <tr>
<th>Address:</th><th><?= $billto_address ?></th>
<th>Address:</th><th><?= $seller_address ?></th></tr>
                <tr>
<th>GSTIN:</th><th><?= $billto_gst ?></th>
<th>GSTIN:</th><th><?= $seller_gst ?></th></tr>
                <tr>
<th>State Name / Code</th>
<th><?= $billto_state . "(" . $billto_state_code . ")" ?></th>
<th>State Name / Code</th>
<th><?= $seller_state . "(" . $seller_state_code . ")" ?></th></tr>
</table>

</td>
</tr>
                <tr><th colspan="4"></th></tr>
            </table>
        </td>
    </tr>
    <tr>
        <td colspan="2" class="">
            <table border="1" cellspacing="0px" cellpadding="3px"  style="font-size:7px;width:100%">
                <tr>
                    <th rowspan="2" class="bgcolor" style="width:8%">S.No.</th>  
                    <th rowspan="2" class="bgcolor" style="width:15%">Product Description (Scientific Name)</th>
                    <th rowspan="2" class="bgcolor" style="width:5%">HSN code</th> 
                    <th rowspan="2" class="bgcolor" style="width:5%">Ordered Cases</th> 
                    <th rowspan="2" class="bgcolor">Case / Box Qty</th>
                    <th rowspan="2" class="bgcolor" style="width:5%">Qty</th>  
                    <th rowspan="2" class="bgcolor">Basic Price</th>
                    <th rowspan="2" class="bgcolor">Amount</th>  
                    <th rowspan="2" class="bgcolor" style="min-width:5%">Discount In %</th>
                    <th rowspan="2" class="bgcolor">Taxable Value</th>  
                    <th colspan="2" class="bgcolor">IGST</th>

                    <th rowspan="2" class="bgcolor">Total</th>
                </tr>
                <tr>
                    <th class="bgcolor">Rate(%)</th>
                    <th class="bgcolor">Amount</th>
                </tr>

                <?php
                if (isset($items["records"])) {
                    $i = 0;
                    $total_pack_qty = 0;
                    $total_qty = 0;
                    $total_mrp_amount = 0;
                     $total_ordered_qty = 0;
                    $total_single_amount = 0;
                    $total_net_amount = 0;
                    $total_discount_amount = 0;
                    $total_amount = 0;
                    $total_cgst_amount = 0;
                    $total_sgst_amount = 0;
                    $total_igst_amount = 0;
                    $total_cart_amount = 0;

                    //$tax_array = array("5.00" => array("taxable" => "0.00"), "0.00" => array("taxable" => "0.00"), "12.00" => array("taxable" => "0.00"), "18.00" => array("taxable" => "0.00"));
                    $tax_array = array();
                    while ($i < count($items["records"])) {
                        $single_item = $items["records"][$i];      //used
                        $single_piece_mrp = $single_item["single_piece_mrp"]; //used
                        $qty = $single_item["qty"];
                        $total_ordered_qty += $qty;                        
                        $row_qty = ($single_item["pack_qty"]*$single_item["picked_qty"]); //used
                        $total_pieces_mrp = $single_piece_mrp * $row_qty; //used


                        $single_piece_discount = round((($single_piece_mrp - $single_item["single_piece_pay"])/$single_piece_mrp)*100,2); //used
                        $total_discount_amount += ($single_piece_discount*$row_qty);  //used
                       
                       
                        $net_amount = $single_item["picked_qty"] * $single_item["amount"]; //used

                        $mrp = $single_item["mrp"];   //used
                        $mrp_amount = $single_item["picked_qty"] * $single_item["mrp"];
                        $discount_amount = $mrp_amount - $net_amount;

                        $cgst = $single_item["cgst"];  //used
                        $sgst = $single_item["sgst"];  //used
                        $igst = $single_item["igst"];  //used
                        if ($igst == "" || $igst == "NA") {
                            $igst = 0.00;
                        }
                        if ($cgst == "" || $cgst == "NA") {
                            $cgst = 0.00;
                        }
                        if ($sgst == "" || $sgst == "NA") {
                            $sgst = 0.00;
                        }
                        $igst_amount = ($net_amount * $igst) / 100;     //used
                        $total_amount = round($net_amount + $igst_amount, 2);  //used
                        

                        $total_pack_qty += $single_item["pack_qty"];  //used

                        $total_qty += $row_qty;       //used
                        $total_single_amount += $mrp;     //used
                        $total_mrp_amount += $total_pieces_mrp;  //used
                        $total_net_amount += $net_amount;  //used
                        
                        $total_igst_amount += $igst_amount;  //used
                        $total_cart_amount += $total_amount;   //used
                        if (!isset($tax_array[$igst])) {
                            $tax_array[$igst] = array();
                            $tax_array[$igst] = array();
                            $tax_array[$igst]["taxable"] = $net_amount;
                        } else {
                            $tax_array[$igst]["taxable"] = $tax_array[$igst]["taxable"] + $net_amount;
                        }
                        /* if (isset($tax_array[$igst])) {
                          $tax_array[$igst]["taxable"] = $tax_array[$igst]["taxable"] + $net_amount;
                          } */
                        ?>
                        <tr>
                            <td><?= $i + 1 ?></td>
                            <td>
                                       <?= $single_item["itemname"] ?> <br>
                                 <b>Batch No</b>  <?= $single_item["batch_no"] ?>  <br>
                                 <b>MFG Dt</b>  <?= $single_item["mfg_date"] ?>  <br>
                                 <b>EXP Dt</b>  <?= $single_item["exp_date"] ?> 

                            </td>
                            <td align="center"><?= $single_item["hsn_code"] ?></td>
                            <td align="center"><?= $qty ?></td>
                            <td align="center"><?= $single_item["pack_qty"] ?></td> 
                            <td align="center"><?= $row_qty ?></td>
                            <td align="right"><?= number_format( $single_piece_mrp,2,'.','') ?></td>
                            <td align="right"><?= number_format($total_pieces_mrp,2,'.','') ?></td>
                            <td align="right"><?= number_format($single_piece_discount,2,'.','') ?> %</td>
                            <td align="right"><?= number_format($net_amount,2,'.','') ?></td>

                            <td align="center"><?= $igst ?> %</td>
                            <td align="right"><?= number_format($igst_amount,2,'.','') ?></td>
                            <td align="right"><?= number_format($total_amount,2,'.','') ?></td>
                        </tr>
                        <?php
                        $i++;
                    }
                }

                $round_value = round($total_cart_amount);
                ?>
                <tr>
                   <td colspan="3" class="bgcolor">Total </td>
                    <td align="center"><?= $total_ordered_qty ?></td>

                    <td align="center"><?= $total_pack_qty ?></td>
                    <td align="center"><?= $total_qty ?></td>
                    <td></td>
                    <td align="right"><?= number_format($total_mrp_amount,2,'.','') ?></td>
                    <td align="right"><?php /* echo number_format($total_discount_amount,2,'.','') */ ?></td>
                    <td align="right"><?= number_format($total_net_amount,2,'.','') ?></td>
                    <td align="right"></td>
                    <td align="right"><?= number_format($total_igst_amount,2,'.','') ?></td>
                    <td align="right"><?= number_format($total_cart_amount,2,'.','') ?></td>
                </tr>
                <tr>
                    <td colspan="9" class="bgcolor">Total Invoice amount in words  </td>


                    <td colspan="3" rowspan="3" style="text-align:right">
Total Amount before Tax</td><td rowspan="3" style="text-align:right"> <?= number_format($total_net_amount,2,'.','') ?>
</td>
                </tr>
                <tr>
                    <td colspan="9">RUPEES <?= strtoupper(getIndianCurrency(round($round_value))) ?> ONLY </td>
                </tr>
                <tr>
                    <td colspan="4">DUE AS ON DATE:  <?php echo number_format($wallet,2,'.','') ; //$round_value
?></td>
                    <td colspan="5">AMOUNT:  <?= number_format($round_value,2,'.','') ?></td>

                </tr>
                <tr>
                    <td colspan="4" class="bgcolor">BANK DETAILS </td>
                    <td colspan="5" class="bgcolor">TAX SUMMARY </td>
                    <td colspan="3">Add: IGST</td>
                    <td  align="right"><?= number_format($total_igst_amount,2,'.','') ?></td>

                </tr>
                <tr>
                    <td>A/C</td>
                    <td colspan="3"><?=$branch_acc_no?></td>
                    <td colspan="5" rowspan="5">
                        <table style="width:100%" cellpadding="2px" cellspacing="0px" border="1">
                            <tr><td style="min-width:30%">Taxable Amount</td><td align="center">IGST %</td><td>IGST Amount</td></tr>
                            <?php
                            $tax_summary = 0.00;
                            $tax_disc = 0.00;
                            foreach ($tax_array as $key => $value) {
                                     $tax_disc = ($tax_array[$key]["taxable"] * $key) / 100;
                                     $tax_summary = $tax_summary + $tax_disc;
                                ?>
                                <tr>
                                    <td align="right"><?= number_format($tax_array[$key]["taxable"],2,'.','')?></td>
                                    <td align="center"><?= $key ?> %</td>
                                    <td align="right"><?= number_format($tax_disc,2,'.','') ?></td>

                                </tr>
                                <?php
                            }
                            ?>
<tr><td colspan="2" align="center">Total </td><td align="right"><?= number_format($tax_summary,2,'.','') ?></td></tr>
                        </table>
                    </td>
                   
                    <td colspan="3">TOTAL</td>
                    <td align="right"><?= number_format($total_cart_amount,2,'.','') ?></td>

                </tr>
                <tr>
                    <td>Name</td>
                    <td colspan="3"><?=$branch_acc_holder_name?></td>
                    
                    <td colspan="3">ROUND OFF (+/-)</td>
                    <td align="right"><?= number_format(round($round_value - $total_cart_amount, 2),2,'.','') ?>

                    </td>

                </tr>
                <tr>
                    <td>Bank</td>
                    <td colspan="3"><?=$branch_bank_name?></td>
                   
                    <td rowspan="3" colspan="3">Total Invoice</td>
                    <td rowspan="3" align="right"><?= number_format($round_value,2,'.','') ?></td>

                </tr>
                <tr>
                    <td>Branch</td>
                    <td colspan="3"><?=$branch_bank_branch?></td>
                   


                </tr>
                <tr>
                    <td>IFSC</td>
                    <td colspan="3"><?=$branch_bank_ifsc?></td>
                    


                </tr>
                <tr>

                    <td colspan="9" class="bgcolor"><h5>TERMS AND CONDITIONS</h5></td>
                    <td colspan="4" rowspan="6">
                    <div style="text-align:center">For <?= $company_name ?></div> 
                        <div><!--<img src="<?php echo base_url() . 'logo/'.$company_signature?>" style="width:100px;height:50px;" />--> </div>
                        <div></div>
                        <div style="text-align:center">Authorised Signatory</div>                    
                    </td>


                </tr>
                <tr>
                     <td>1</td>
                    <td colspan="8">All Goods  returned for replacement must be in Saleable Condition  with Original Packing </td>
                    
                </tr>
                <tr>
                    <td>2</td>
                    <td colspan="8">We are not responsible for any transit damage, loss or Leakage</td>

                </tr>
                <tr>
                    <td>3</td>
                    <td colspan="8">24% PA Interest will be  Charged for the Delayed payment.</td>

                </tr>
                <tr><td colspan="9" class="bgcolor"><h5>DECLARATION</h5></td></tr>
                <tr>
                    <td colspan="9">
                      We declare that this invoice shows the actual price of the goods described and that all particulars are true and correct and that my /our Registration Under GST Act-2017 is valid on the date  of this bill.
                    </td>
                    
                    
                </tr>
                <tr><td colspan="13" align="center">SUBJECT TO HYDERABAD JURISDICTION</td></tr>

            </table>
        </td>
    </tr>
</table>


