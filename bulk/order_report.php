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
    <form method="POST">
    <table>
        <tr>
            <td>From Date : <input type="date" name="fromdate" id="fromdate" /> </td><td> To Date : <input type="date" name="todate" id="todate" /></td>
        </tr>
        <tr><td colspan="2"> <input type="submit" name="search" value="Search"/></td></tr>
    </table>
    </form>
   <table border="1" cellspacing="0px" cellpadding="5px" style="margin-top:50px">

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

          $j = 1;
          $condition="";
          if(isset($_REQUEST['search'])){
              $fromdate = $_REQUEST["fromdate"];
              $todate = $_REQUEST["todate"];
              if($fromdate!="" && $todate!=""){
                  $fromdate= date("Y-m-d",strtotime($fromdate));
                  $todate= date("Y-m-d",strtotime($todate));
                  $condition=" and s.orderedon>='$fromdate 00:00:00' and s.orderedon<='$todate 23:59:59'";
              }else if($fromdate=="" && $todate!=""){
                 
                  $todate= date("Y-m-d",strtotime($todate));
                  $condition=" and s.orderedon>='$todate 00:00:00' and s.orderedon<='$todate 23:59:59'";
              }else if($fromdate!="" && $todate==""){
                   $fromdate= date("Y-m-d",strtotime($fromdate));
                   $condition=" and s.orderedon>='$fromdate 00:00:00' and s.orderedon<='$fromdate 23:59:59'";
                  
              }
              
              
          }
        //  echo "select e.first_name,e.uniq_id,count(*) as cnt from `seller_orders` s,`employees` e WHERE  s.created_by=e.id and s.orderedby!=s.created_by $condition GROUP BY s.created_by HAVING COUNT(*)>0";
              $order_query = mysqli_query($con,"select e.first_name,e.uniq_id,count(*) as cnt from `seller_orders` s,`employees` e WHERE  s.created_by=e.id and s.orderedby!=s.created_by $condition GROUP BY s.created_by HAVING COUNT(*)>0");
         
          

          while($order_rs = mysqli_fetch_object($order_query)){
              ?>

                 <tr>
                     <td><?=$j?></td>
                     <td><?=$order_rs->first_name?></td>
                     <td><?=$order_rs->uniq_id?></td>
                     <td align="center"><?=$order_rs->cnt?></td>
                    


                 </tr>


             <?php

            $j++;
          }


        ?>
       </table>
        
    </div>
    


</body>
</html>