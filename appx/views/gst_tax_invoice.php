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
        $order_date = $invoice->order_date;
        if ($order_date != "") {
            $order_date = date("d-m-Y");
        }
        $invoiceid = $invoice->invoice_id;

        $seller_address = $invoice->address;
    }
}

if (isset($items["seller"]) && count($items["seller"])>0) {

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

if (isset($items["order"]) && count($items["order"])>0) {

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

if (isset($items["transport"]) && count($items["transport"])>0) {
   $transport_type = $items["transport"]["transport_type"];
    $lr_no = $items["transport"]["lr_no"] ;
    $contact = $items["transport"]["contact"] ;
    $supply_date = $items["transport"]["supply_date"];
    $supply_place= $items["transport"]["supply_place"] ;
} else {
    $transport_type="";
    $lr_no="";
    $contact="";
    $supply_date="";
    $supply_place="";
    
}

if (isset($items["branch_details"]) && count($items["branch_details"])>0) {
    $company_name = $items["branch_details"]["company"];
    $branch_name = $items["branch_details"]["branch_name"];
    $branch_address = $items["branch_details"]["address"];
    $branch_contact = $items["branch_details"]["contact"];
    $gst = $items["branch_details"]["gst"];
    $logo = $items["branch_details"]["logo"];
    $email = $items["branch_details"]["email"];
    $cin_no = $items["branch_details"]["cin_no"];
    $pan = $items["branch_details"]["pan"];

    //CIN No: U01119TG2007PTC053901; PAN#: AACCN8771A
} else {
    $company_name = "";
    $branch_name = "";
    $branch_address = "";
    $branch_contact="";
    $gst="";
    $logo = "";
    $email = "";
    $cin_no = "";
    $pan = "";
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
        <th style="width:30%;border-right:none;border-right:none;" align="cener"><br><br><img src="<?php echo base_url() . 'logo/icon.jpg' ?>" style="width:75px;hieght:75px;" /></th>
        <th style="width:70%;border-left:none;font-size: 10px;border-left:none;padding-top: 0px;" align="center" class="head" >
            <h3 style="margin:0px"><?= $company_name ?></h3>

            <h4><?= $branch_address ?></h4>
            <h4>CIN No: <?= $cin_no ?>; PAN#: <?= $pan ?></h4>

            <h4>e-Mail:<?= $email ?></h4>
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
                <tr><th>State Name / Code</th><th><?=$items["branch_statename"]?> / <?=$items["branch_state"]?></th><th>Date of Supply:</th><th><?= $supply_date ?></th></tr>
                <tr><th>Reverse Charge (Y/N):</th><th>N</th><th>Place of Supply</th><th><!--<?=$supply_place?>--> <?=$branch_name?></th></tr>
                <tr><th>Dispatch Contact :</th><th><?php if(isset($items["depot_contact"])) { echo $items["depot_contact"];} ?></th> <th>Delivery Contact:</th><th><?= $contact ?></th></tr>
                <tr><th colspan="4"></th></tr>
                <tr><th colspan="2"  class="bgcolor" align="center"><b>Bill to Party</b></th>
                    <th colspan="2" class="bgcolor"  align="center"><b>Ship to Party</b></th></tr>
                <tr><th>Name:</th><th class="font10"><?= $seller_name ?></th><th>Name:</th><th class="font10"><?= $seller_name ?></th></tr>
                <tr><th>Address:</th><th><?= $seller_address ?></th><th>Address:</th><th><?= $seller_address ?></th></tr>
                <tr><th>GSTIN:</th><th><?= $seller_gst ?></th><th>GSTIN:</th><th><?= $seller_gst ?></th></tr>
                <tr><th>State Name / Code</th><th><?= $seller_state . "(" . $seller_state_code . ")" ?></th><th>State Name / Code</th><th><?= $seller_state . "(" . $seller_state_code . ")" ?></th></tr>
                <tr><th colspan="4"></th></tr>
            </table>
        </td>
    </tr>
    <tr>
        <td colspan="2" class="">
            <table border="1" cellspacing="0px" cellpadding="3px"  style="font-size:8px;width:100%">
                <tr>
                    <th rowspan="2" class="bgcolor" style="min-width:5%">S.No.</th>  
                    <th rowspan="2" class="bgcolor" style="width:14%">Product Description (Scientific Name)</th>
                    <th rowspan="2" class="bgcolor">HSN code</th>  
                    <th rowspan="2" class="bgcolor" style="width:5%">Case / Box Qty</th>
                    <th rowspan="2" class="bgcolor" style="width:5%">Qty</th>  
                    <th rowspan="2" class="bgcolor">Rate</th>
                    <th rowspan="2" class="bgcolor">Amount</th>  
                    <th rowspan="2" class="bgcolor" style="width:5%">Discount</th>
                    <th rowspan="2" class="bgcolor">Taxable Value</th>  
                    <th colspan="2" class="bgcolor">SGST</th>
                    <th colspan="2" class="bgcolor">CGST</th>

                    <th rowspan="2" class="bgcolor">Total</th>
                </tr>
                <tr>
                    <th class="bgcolor">Rate</th>
                    <th class="bgcolor">Amount</th>
                    <th class="bgcolor">Rate</th>
                    <th class="bgcolor">Amount</th>
                </tr>

                <?php
                if (isset($items["records"])) {
                    $i = 0;
                    $total_pack_qty = 0;
                    $total_qty = 0;
                    $total_mrp_amount = 0;
                    $total_single_amount = 0;
                    $total_net_amount = 0;
                    $total_discount_amount = 0;
                    $total_amount = 0;
                    $total_cgst_amount = 0;
                    $total_sgst_amount = 0;
                    $total_igst_amount = 0;
                    $total_cart_amount = 0;

                    $tax_array = array("0.00" => array("taxable" => "0.00"), "2.50" => array("taxable" => "0.00"), "6.00" => array("taxable" => "0.00"), "9.00" => array("taxable" => "0.00"));
                    $cgst_tax_array = array("0.00" => array("taxable" => "0.00"), "2.50" => array("taxable" => "0.00"), "6.00" => array("taxable" => "0.00"), "9.00" => array("taxable" => "0.00"));
                    $sgst_tax_array = array("0.00" => array("taxable" => "0.00"), "2.50" => array("taxable" => "0.00"), "6.00" => array("taxable" => "0.00"), "9.00" => array("taxable" => "0.00"));
                    $cgst_array = array();
                    $sgst_array = array();
                    while ($i < count($items["records"])) {
                        $single_item = $items["records"][$i];
                        $mrp = $single_item["mrp"];
                        $mrp_amount = $single_item["picked_qty"] * $single_item["mrp"];
                        $net_amount = $single_item["picked_qty"] * $single_item["amount"];
                        $discount_amount = $mrp_amount - $net_amount;
                        $cgst = $single_item["cgst"];
                        $sgst = $single_item["sgst"];
                        $igst = $single_item["igst"];
                        if ($igst == "" || $igst == "NA") {
                            $igst = 0.00;
                        }
                        if ($cgst == "" || $cgst == "NA") {
                            $cgst = 0.00;
                        }
                        if ($sgst == "" || $sgst == "NA") {
                            $sgst = 0.00;
                        }
                        $cgst_amount = ($net_amount * $cgst) / 100;
                        $sgst_amount = ($net_amount * $sgst) / 100;

                       /* if (!isset($cgst_array[$cgst])) {
                            $cgst_array[] = $cgst;
                        }
                        if (!isset($sgst_array[$sgst])) {
                            $sgst_array[] = $sgst;
                        }*/
                        $total_amount = round($net_amount + $cgst_amount + $sgst_amount, 2);


                        $total_pack_qty += $single_item["pack_qty"];
                        $total_qty += $single_item["picked_qty"];
                        $total_single_amount += $mrp;
                        $total_mrp_amount += $mrp_amount;
                        $total_net_amount += $net_amount;
                        $total_discount_amount += $discount_amount;
                        $total_cgst_amount += $cgst_amount;
                        $total_sgst_amount += $sgst_amount;

                        $total_cart_amount += $total_amount;
                        $tax_array[$cgst]["taxable"] = $tax_array[$cgst]["taxable"] + $net_amount;
                        
                        if (isset($cgst_tax_array[$cgst])) {
                            $cgst_tax_array[$cgst]["taxable"] = $cgst_tax_array[$cgst]["taxable"] + $net_amount;
                        }
                        
                        if (isset($sgst_tax_array[$cgst])) {
                            $sgst_tax_array[$sgst]["taxable"] = $sgst_tax_array[$sgst]["taxable"] + $net_amount;
                        }

                        
                        ?>
                        <tr>
                            <td><?= $i + 1 ?></td>
                            <td><?= $single_item["itemname"] ?></td>
                            <td><?= $single_item["hsn_code"] ?></td>
                            <td><?= $single_item["pack_qty"] ?></td> 
                            <td><?= $single_item["picked_qty"] ?></td>
                            <td><?= $mrp ?></td>
                            <td><?= $mrp_amount ?></td>
                            <td><?= $discount_amount ?></td>
                            <td><?= $net_amount ?></td>
                            <td><?= $sgst ?></td>
                            <td><?= $sgst_amount ?></td>
                            <td><?= $cgst ?></td>
                            <td><?= $cgst_amount ?></td>
                            <td><?= $total_amount ?></td>
                        </tr>
                        <?php
                        $i++;
                    }
                }

                $round_value = round($total_cart_amount);
                ?>
                <tr>
                    <td colspan="3" class="bgcolor">Total </td>

                    <td><?= $total_pack_qty ?></td>
                    <td><?= $total_qty ?></td>
                    <td></td>
                    <td><?= $total_mrp_amount ?></td>
                    <td><?= $total_discount_amount ?></td>
                    <td><?= $total_net_amount ?></td>
                    <td></td>
                    <td><?= $total_sgst_amount ?></td>
                    <td></td>
                    <td><?= $total_cgst_amount ?></td>
                    <td><?= $total_cart_amount ?></td>
                </tr>
                <tr>
                    <td colspan="9" class="bgcolor">Total Invoice amount in words  </td>


                    <td colspan="4">TOTAL VALUE OF GOODS </td>

                    <td><?= $total_mrp_amount ?></td>
                </tr>
                <tr>
                    <td rowspan="2" colspan="9">RUPEES <?= strtoupper(convert_number_to_words(round($round_value))) ?> ONLY </td>


                    <td colspan="4">DISCOUNT</td>

                    <td><?= $total_discount_amount ?></td>
                </tr>
                <tr>



                    <td colspan="4">Total Amount before Tax</td>

                    <td><?= $total_net_amount ?></td>
                </tr>
                <tr>
                    <td colspan="4">DUE AS ON DATE: DD/MM/YYYY </td>
                    <td colspan="5">AMOUNT:  </td>
                    <td colspan="4">Add: SGST</td>
                    <td><?= $total_sgst_amount ?></td>

                </tr>
                <tr>
                    <td colspan="4" class="bgcolor">BANK DETAILS </td>
                    <td colspan="5" class="bgcolor">TAX SUMMARY </td>
                    <td colspan="4">Add: CGST</td>
                    <td><?= $total_cgst_amount ?></td>

                </tr>
                <tr>
                    <td>A/C</td>
                    <td colspan="2"></td>
                    <td colspan="2">Taxable amount</td>
                    <td>SGST %</td>
                    <td>SGST Amount</td>
                    <td>CGST %</td>
                    <td>CGST Amount</td>
                    <td colspan="4">TOTAL</td>
                    <td><?= $total_cart_amount ?></td>

                </tr>
                <tr>
                    <td>Name</td>
                    <td colspan="2"></td>
                    <td colspan="2"><?= $tax_array["2.50"]["taxable"] ?></td>
                    <td>2.5</td>
                    <td>
                        <?php
                        if (isset($sgst_tax_array["2.50"])) {
                            echo round(($sgst_tax_array["2.50"]["taxable"] * 2.5) / 100, 2);
                        } else {
                            echo 0.00;
                        }
                        ?>
                    </td>
                    <td>2.5</td>
                    <td>
                        <?php
                        if (isset($cgst_tax_array["2.50"])) {
                            echo round(($cgst_tax_array["2.50"]["taxable"] * 2.5) / 100, 2);
                        } else {
                            echo 0.00;
                        }
                        ?>
                    </td>
                    <td colspan="4">ROUND OFF (+/-)</td>
                    <td><?= round($round_value - $total_cart_amount,2) ?></td>

                </tr>
                <tr>
                    <td>Bank</td>
                    <td colspan="2"></td>
                    <td colspan="2"><?= $tax_array["6.00"]["taxable"] ?></td>
                    <td>6</td>
                    <td>
                        <?php
                        if (isset($sgst_tax_array["6.00"])) {
                            echo round(($sgst_tax_array["6.00"]["taxable"] * 6.0) / 100, 2);
                        } else {
                            echo 0.00;
                        }
                        ?>
                    </td>
                    <td>6</td>
                    <td>
                        <?php
                        if (isset($cgst_tax_array["6.00"])) {
                            echo round(($cgst_tax_array["6.00"]["taxable"] * 6.0) / 100, 2);
                        } else {
                            echo 0.00;
                        }
                        ?>
                    </td>
                    <td rowspan="3" colspan="4">Total Invoice</td>
                    <td rowspan="3"><?= $round_value ?></td>

                </tr>
                <tr>
                    <td>Branch</td>
                    <td colspan="2"></td>
                    <td colspan="2"><?= $tax_array["9.00"]["taxable"] ?></td>
                    <td>9</td>
                    <td>
                        <?php
                        if (isset($sgst_tax_array["9.00"])) {
                            echo round(($sgst_tax_array["9.00"]["taxable"] * 9.0) / 100, 2);
                        } else {
                            echo 0.00;
                        }
                        ?>
                    </td>
                    <td>9</td>
                    <td>
                        <?php
                        if (isset($cgst_tax_array["9.00"])) {
                            echo round(($cgst_tax_array["9.00"]["taxable"] * 9.0) / 100, 2);
                        } else {
                            echo 0.00;
                        }
                        ?>
                    </td>


                </tr>
                <tr>
                    <td>IFSC</td>
                    <td colspan="2"></td>
                    <td colspan="2"><?= $tax_array["0.00"]["taxable"] ?></td>
                    <td>0</td>
                    <td>
                        0.00
                    </td>
                    <td>0.00</td>
                    <td>
                        0.00
                    </td>


                </tr>
                <tr>

                    <td colspan="6" class="bgcolor"><h5>TERMS AND CONDITIONS</h5></td>
                    <td rowspan="5" colspan="3"></td>
                    <td colspan="6"><h5>For <?= $company_name ?></h5></td>


                </tr>
                <tr>
                    <td>1</td>
                    <td colspan="5"></td>
                    <td colspan="5" rowspan="3"></td>
                </tr>
                <tr>
                    <td>2</td>
                    <td colspan="5"></td>

                </tr>
                <tr>
                    <td>3</td>
                    <td colspan="5"></td>

                </tr>
                <tr>
                    <td>4</td>
                    <td colspan="5"></td>
                    <td colspan="6" align="center"><h5>Authorised Signatory</h5></td>
                </tr>
                <tr><td colspan="14" align="center">SUBJECT TO HYDERABAD JURISDICTION</td></tr>
            </table>
        </td>
    </tr>
</table>


