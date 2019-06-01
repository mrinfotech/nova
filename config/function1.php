<?php
/*function location_id($loc,$cityid) {
    $name = mysql_data("select loc_id from locations where location='$loc' and city_id='$cityid'","location") ;
    return $name;
}*/

function dist_cntry_name($did) {
    $name = mysql_data("select c.country_name from  countries c ,districts d,states s where d.state_id=s.state_id and d.dist_id='$did' and s.cntry_id=c.cntry_id","country_name") ;
    return $name;
}

function dist_state_name($did) {
    $name = mysql_data("select s.state from  states s ,districts d where d.state_id=s.state_id and d.dist_id='$did'","state") ;
    return $name;
}

function minmaxSqy($lay_id){
    $qry=mysql_query("select min(tsqy) as minn ,max(tsqy) as maxx from sqy where lay_id='$lay_id'");
    if($rs=mysql_fetch_object($qry)) {
        if($rs->minn!=$rs->maxx)
          return number_format($rs->minn,0)."&nbsp;Sqy&nbsp;-&nbsp;<i class='fa fa-crop' style='font-size:18px'></i>&nbsp".number_format($rs->maxx,0)."&nbsp;Sqy";
        else
          return number_format($rs->maxx,0)."&nbsp;Sqy";
		  
    }
}
function minmaxSft($app_id){
    $qry=mysql_query("select min(sft) as minn ,max(sft) as maxx from bhk where app_id='$app_id'");
    if($rs=mysql_fetch_object($qry)) {
        if($rs->minn!=$rs->maxx)
          return number_format($rs->minn,0)."&nbspSft&nbsp;-&nbsp;<i class='fa fa-crop' style='font-size:18px'></i>&nbsp".number_format($rs->maxx,0)."&nbsp;Sft";
        else
          return number_format($rs->maxx,0)."&nbsp;Sft";
    }
}
function minmaxCost($app_id){
    $qry=mysql_query("select min(app_cost) as minn ,max(app_cost) as maxx from bhk where app_id='$app_id'");
    if($rs=mysql_fetch_object($qry)) {
        if($rs->minn!=$rs->maxx)
          return numToMoney($rs->minn)."&nbsp;-&nbsp;<i class='fa fa-inr fa-lg' style='font-size:18px'></i>&nbsp".numToMoney($rs->maxx);
        else
          return numToMoney($rs->maxx);
    }
}
function minmaxCost1($lay_id){
    $qry=mysql_query("select min(tcost) as minn ,max(tcost) as maxx from sqy where lay_id='$lay_id'");
    if($rs=mysql_fetch_object($qry)) {
        if($rs->minn!=$rs->maxx)
          return numToMoney($rs->minn)."&nbsp;-&nbsp;<i class='fa fa-inr fa-lg' style='font-size:18px'></i>&nbsp".numToMoney($rs->maxx);
        else
          return numToMoney($rs->maxx);
		  
    }
}

function numToMoney($amt){
    
    if($amt>=10000000){
        return number_format($amt/10000000,2)." Cr.";
    }else if($amt>=100000){
        return number_format($amt/100000,2)." Lacs.";
    }else if($amt>=1000){
        return number_format($amt/1000,2)." K.";
    }else{
        return $amt;
    }
  }
