<!DOCTYPE html>
<html>
<head>
  <meta charset="utf-8">
  <meta http-equiv="X-UA-Compatible" content="IE=edge">
  <title>Admin</title>
  <!-- Tell the browser to be responsive to screen width -->
  <meta content="width=device-width, initial-scale=1, maximum-scale=1, user-scalable=no" name="viewport">
  <!-- Bootstrap 3.3.7 -->
  <link rel="stylesheet" href="<?php echo base_url();?>templates/bower_components/bootstrap/dist/css/bootstrap.min.css">
  <!-- Font Awesome -->
  <link rel="stylesheet" href="<?php echo base_url();?>templates/bower_components/font-awesome/css/font-awesome.min.css">
  <!-- Ionicons -->
  <link rel="stylesheet" href="<?php echo base_url();?>templates/bower_components/Ionicons/css/ionicons.min.css">
  <!-- Theme style -->
  <link rel="stylesheet" href="<?php echo base_url();?>templates/dist/css/AdminLTE.min.css">
  <!-- AdminLTE Skins. Choose a skin from the css/skins
       folder instead of downloading all of them to reduce the load. -->
  <link rel="stylesheet" href="<?php echo base_url();?>templates/dist/css/skins/_all-skins.min.css">

  <!-- HTML5 Shim and Respond.js IE8 support of HTML5 elements and media queries -->
  <!-- WARNING: Respond.js doesn't work if you view the page via file:// -->
  <!--[if lt IE 9]>
  <script src="https://oss.maxcdn.com/html5shiv/3.7.3/html5shiv.min.js"></script>
  <script src="https://oss.maxcdn.com/respond/1.4.2/respond.min.js"></script>
  <![endif]-->

  <!-- Google Font -->
  <link rel="stylesheet" href="https://fonts.googleapis.com/css?family=Source+Sans+Pro:300,400,600,700,300italic,400italic,600italic">
  <script src="<?php echo base_url();?>templates/bower_components/jquery/dist/jquery.min.js"></script>
<!-- jQuery UI 1.11.4 -->
<script src="<?php echo base_url();?>templates/bower_components/jquery-ui/jquery-ui.min.js"></script>
</head>
<body class="hold-transition skin-blue sidebar-mini">
<!-- Site wrapper -->
<div class="wrapper">

  <header class="main-header">
    <!-- Logo -->
    <a href="<?php echo base_url();?>templates/index2.html" class="logo">
      <!-- mini logo for sidebar mini 50x50 pixels -->
      <span class="logo-mini"><b>NOVA</b></span>
      <!-- logo for regular state and mobile devices -->
      <span class="logo-lg"><b>NOVA</b> Agri Tech Ltd</span>
    </a>
    <!-- Header Navbar: style can be found in header.less -->
    <nav class="navbar navbar-static-top">
      <!-- Sidebar toggle button-->
      <a href="#" class="sidebar-toggle" data-toggle="push-menu" role="button">
        <span class="sr-only">Toggle navigation</span>
        <span class="icon-bar"></span>
        <span class="icon-bar"></span>
        <span class="icon-bar"></span>
      </a>

      <div class="navbar-custom-menu">
        <ul class="nav navbar-nav">
        
          <li class="dropdown user user-menu">
            <a href="#" class="dropdown-toggle" data-toggle="dropdown">
              <i class="fa fa-user-circle fa-2x"></i>
              
            </a>
            <ul class="dropdown-menu">
<!--                <li><a href="<?=base_url()?>manage/changepassword" class="btn btn-link" style="text-align: left;"> <i class="fa fa-gear "></i> Change Password</a></li>-->
                <li><a style="text-align: left;" href="<?=base_url()?>manage/logout" class="btn btn-link">  <i class="fa fa-sign-out "></i> Sign Out</a></li>
              <!-- User image -->
              
              <!-- Menu Body -->
             
              <!-- Menu Footer-->
              
            </ul>
          </li>
          <!-- Control Sidebar Toggle Button -->
<!--          <li>
            <a href="#" data-toggle="control-sidebar"><i class="fa fa-gears"></i></a>
          </li>-->
        </ul>
      </div>
    </nav>
  </header>

  <!-- =============================================== -->

  <!-- Left side column. contains the sidebar -->
  <aside class="main-sidebar">
    <!-- sidebar: style can be found in sidebar.less -->
    <section class="sidebar">
      <!-- Sidebar user panel -->
      <div class="user-panel">
        <div class="pull-left image">
          <img src="<?php echo base_url();?>templates/dist/img/user2-160x160.jpg" class="img-circle" alt="User Image">
        </div>
        <div class="pull-left info">
          <p>Admin</p>
<!--          <a href="#"><i class="fa fa-circle text-success"></i> Online</a>-->
        </div>
      </div>
      <!-- search form -->
      <!--<form action="#" method="get" class="sidebar-form">
        <div class="input-group">
          <input type="text" name="q" class="form-control" placeholder="Search...">
          <span class="input-group-btn">
                <button type="submit" name="search" id="search-btn" class="btn btn-flat"><i class="fa fa-search"></i>
                </button>
              </span>
        </div>
      </form>-->
      <!-- /.search form -->
      <!-- sidebar menu: : style can be found in sidebar.less -->
      <ul class="sidebar-menu" data-widget="tree">
        <li class="header">MAIN NAVIGATION</li>
     
        <li class="treeview">
          <a href="#">
            <i class="fa fa-home"></i>
            <span>Dealers</span>
<!--            <span class="pull-right-container">
              <span class="label label-primary pull-right">4</span>
            </span>-->
          </a>
          <ul class="treeview-menu">
            <li><a href="<?php echo base_url();?>/manage/dealer_upload"><i class="fa fa-circle-o"></i>Upload</a></li>
<!--            <li><a href="<?php echo base_url();?>/manage/dealers"><i class="fa fa-circle-o"></i> List</a></li>-->
           
          </ul>
        </li>
        <li class="treeview">
          <a href="#">
            <i class="fa fa-user-circle"></i>
            <span>Employees</span>
<!--            <span class="pull-right-container">
              <span class="label label-primary pull-right">4</span>
            </span>-->
          </a>
          <ul class="treeview-menu">
            <li><a href="<?php echo base_url();?>/manage/emp_upload"><i class="fa fa-circle-o"></i>Upload</a></li>
<!--            <li><a href="<?php echo base_url();?>/manage/employees"><i class="fa fa-circle-o"></i> List</a></li>-->
           
          </ul>
        </li>
         <li class="treeview">
          <a href="#">
            <i class="fa fa-list"></i>
            <span>Items</span>
<!--            <span class="pull-right-container">
              <span class="label label-primary pull-right">4</span>
            </span>-->
          </a>
          <ul class="treeview-menu">
            <li><a href="<?php echo base_url();?>/manage/items_upload"><i class="fa fa-circle-o"></i>Upload</a></li>
<!--            <li><a href="<?php echo base_url();?>/manage/employees"><i class="fa fa-circle-o"></i> List</a></li>-->
           
          </ul>
        </li>
        
       
      </ul>
    </section>
    <!-- /.sidebar -->
  </aside>

  <!-- =============================================== -->

  <!-- Content Wrapper. Contains page content -->

