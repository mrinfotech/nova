<?php
ob_start();
include "novaconfig.php";
error_reporting(E_ALL ^ E_NOTICE);
require_once 'novaexcel2.php';
//function compress_image()

if (isset($_POST['sumbit'])) {
	
	$data = new Spreadsheet_Excel_Reader($_FILES['file']['tmp_name']);
		$msg= $data->latest_dealer_dump($con,true,true); 

}//if(isset($_POST['sumbit']))
?>
<!DOCTYPE html>
<html lang="en">
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="description" content="">
    <meta name="author" content="">

    <title>Student Registration</title>

    <!-- Bootstrap Core CSS -->
    <link href="css/bootstrap.min.css" rel="stylesheet">

    <!-- Custom CSS -->
    <link href="css/sb-admin.css" rel="stylesheet">

    <!-- Morris Charts CSS -->
    <link href="css/plugins/morris.css" rel="stylesheet">

    <!-- Custom Fonts -->
    <link href="font-awesome/css/font-awesome.min.css" rel="stylesheet" type="text/css">

    <!-- HTML5 Shim and Respond.js IE8 support of HTML5 elements and media queries -->
    <!-- WARNING: Respond.js doesn't work if you view the page via file:// -->
    <!--[if lt IE 9]>
        <script src="https://oss.maxcdn.com/libs/html5shiv/3.7.0/html5shiv.js"></script>
        <script src="https://oss.maxcdn.com/libs/respond.js/1.4.2/respond.min.js"></script>
    <![endif]-->
    
</head>
<body>
    <div id="wrapper">
       

        <center>
            <div class="col-lg-10 col-lg-offset-1">
            <h3 class="">Student Details Bulk Upload</h3>
                <form name="studentForm" method="post" action="" enctype="multipart/form-data">
                  <div class="col-lg-8 col-lg-offset-2">
                  
                  <div class="col-lg-12">
                  <input type="file" name="file"  id="file" accept=".xls" class="form-control"/>
                                    <span align="justify">(Please upload .xls only)</span>
                                    </div>
                                    <div class="col-lg-12">
                                     <input name ="sumbit" type="submit" value="Submit" align="right" class="btn btn-primary">
                                    </div>
                  </div> 
                       

                </form>

            </div>
        </center>
    </div>
    
    <script src="js/jquery.js"></script>

    <!-- Bootstrap Core JavaScript -->
    <script src="js/bootstrap.min.js"></script>

</body>
</html>