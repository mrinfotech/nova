<?php
ob_start();
include "novaconfig.php";
error_reporting(E_ALL ^ E_NOTICE);




?>
<!DOCTYPE html>
<html lang="en">
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="description" content="">
    <meta name="author" content="">

    <title>Orders Report</title>

   
</head>
<body>
    <div id="wrapper">


   <table border="1" cellspacing="0px" cellpadding="5px">

         <tr>
            <th>
              S.No
            </th>
           <th>
              Employee
           </th> 
            <th>
               Code
           </th>
            <th>
               No of Orders
           </th>
         
       
        <?php
        $from = $_REQUEST["from"];
        $to= $_REQUEST["to"];
        
        $seller_qry = mysqli_query($con,"select a.* from addresses a where a.user_role='DEALER' and a.address!='' limit $from,$to");
        while($seller_rs=mysqli_fetch_object($seller_qry)){
           $address = "";
           $address_qry = mysqli_query($con,"select a.pincode,a.door_no, a.street_name, a.city, d.district, st.state from addresses a, states st, districts d where a.state=st.id and d.id=a.district  and d.state=st.id and a.id = '$seller_rs->id' and a.user_role='DEALER' or a.user_role='seller'");
if(mysqli_num_rows($address_qry)>0){
$add_det = mysqli_fetch_object($address_qry);
if($add_det->door_no!="NA" && $add_det->door_no!=""){
  $address= $add_det->door_no.',';
}
if($add_det->street_name!="NA"  && $add_det->door_no!=""){
  $address = $address.''.$add_det->street_name.',';
}
if($add_det->city!="NA"  && $add_det->city!=""){
$address = $address.''.$add_det->city.',';
}
if($add_det->district!="NA"  && $add_det->district!=""){
$address = $address.''.$add_det->district.',';
}
if($add_det->state!="NA"  && $add_det->state!=""){
$address = $address.''.$add_det->state.",";
}
$address = trim($address,",");

if($add_det->pincode!="NA"  && $add_det->pincode!=""){
$address = $address.' Pin -'.$add_det->pincode;
}
$address = $address.".";

//echo "update addressesset addresses='$address' where user_id = '$seller_rs->id' and user_role='DEALER' or user_role='seller'";

mysqli_query($con,"update addresses set address='$address' where id= '$seller_rs->id' and user_role='DEALER' or user_role='seller'");
}



 

        }

        ?>
       </table>
        
    </div>
    


</body>
</html>