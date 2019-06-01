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

if (isset($list["seller"])) {

    $seller_address = $list["seller"]["address"];
    $seller_gst = $list["seller"]["gstin"];
    $seller_state_code = $list["seller"]["state_code"];
    $seller_state = $list["seller"]["state"];
    $seller_name = $list["seller"]["name"];
} else {
    $seller_address = "";
    $seller_gst = "";
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

if (isset($list["branch_details"])) {
    $company_name = $list["branch_details"]["company"];
    $formal_name = $list["branch_details"]["formal_name"];
    $branch_name = $list["branch_details"]["branch_name"];
    $company_url = $list["branch_details"]["company_url"]; 
    $branch_address = $list["branch_details"]["address"];
    $branch_contact = $list["branch_details"]["contact"];
    $gst = $list["branch_details"]["gst"];
    $logo = $list["branch_details"]["logo"];
    $company_signature= $list["branch_details"]["company_signature"];
    $email = $list["branch_details"]["email"];
    $cin_no = $list["branch_details"]["cin_no"];
    $pan = $list["branch_details"]["pan"];
    $branch_acc_no = $list["branch_details"]["acc_no"];
    $branch_acc_holder_name = $list["branch_details"]["acc_holder_name"];
    $branch_bank_name = $list["branch_details"]["bank_name"];
    $branch_bank_branch = $list["branch_details"]["bank_branch"];
    $branch_bank_ifsc = $list["branch_details"]["ifsc"];
} else {
    $company_name = "";
    $company_url="";
    $company_signature="";
    $formal_name="";
    $branch_name = "";
    $branch_address = "";
    $branch_contact = "";
    $gst = "";
    $logo = "";
    $email = "";
    $cin_no = "";
    $pan = "";
    $branch_acc_no = "";
    $branch_acc_holder_name = "";
    $branch_bank_name = "";
    $branch_bank_branch = "";
    $branch_bank_ifsc = "";
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
        100000 => 'lakh',
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
       
        <th style="width:100%;border-left:none;font-size: 10px;border-left:none;padding-top: 0px;" align="center" class="head" >
           <span style="margin:0px; color: #2a1770; font-size: 16px"><b><?= $company_name ?></b></span><br>
<?php if($formal_name!="") { echo "(Formally known as ".$formal_name.")"; } ?>
            <h6>Corporate Address : Plot N0.57, 1st Floor, Hanuman Nagar, Chinnatokatta,</h6>
            <h6> New Bowenpally, Secunderabad - 500011, Telangana</h6>
            <h6>Tel: +91-40-27957081 / 82</h6>
            <h6>Website: <?=$company_url?></h6>
            <h6>GST  :# <?= $gst ?></h6>
     <br>
            <span style="margin:0px; color: #2a1770; font-size: 15px"><b><?= $seller_name ?></b></span>
            <br><?= $seller_address ?>
            <h6>GST : <?php echo $seller_gst; ?></h6>
           
           
          
        </th>
    
    </tr>
 </table>
<h3 align="center">
Statement
<?php
   if($list["fromdate"]!="" && $list["todate"]!="" && ($list["fromdate"]!=$list["todate"])){
     echo "From ".$list["fromdate"]." to ".$list["todate"];
   }else if($list["fromdate"]!=""){
     echo "On ".$list["fromdate"];
   }

?>
 </h3>

<table border="1" cellspacing="0px" cellpadding="4px" style="width:100%">
    <tr>
      <th style="width:10%" align="center">Date</th>
      <th style="width:5%"  align="center"></th>
      <th style="width:45%" align="center">Particular</th>
      <th style="width:20%" align="center">Debit</th>
      <th style="width:20%" align="center">Credit</th>
    </tr>
    <?php
      if(isset($list["tabular"]) && count($list["tabular"])>0){
        
          for($j=0;$j<count($list["tabular"]);$j++){
            $record = $list["tabular"][$j];
             ?>
                <tr>
                   <td><?php  echo $record["action_date"]; ?></td>
                   <td><?php  echo $record["transaction_type"]; ?></td>
                   <td><?php  echo $record["particular"]; ?></td>
                   <td align="right"><?php  echo $record["debit_amount"]; ?></td>
                   <td align="right"><?php  echo $record["credit_amount"]; ?></td>
                </tr>

             <?php
          }

      }


    ?>


</table>


