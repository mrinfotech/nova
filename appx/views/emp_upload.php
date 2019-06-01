<div class="content-wrapper">
    <!-- Content Header (Page header) -->
    <section class="content-header">
        <h1>
            Employee
            <small>Upload Data here</small>
        </h1>
        <ol class="breadcrumb">
            <li><a href="#"><i class="fa fa-dashboard"></i> Home</a></li>
            <li><a href="#">Employees</a></li>
            <li class="active">Bulk Upload</li>
        </ol>
    </section>

    <!-- Main content -->
    <section class="content">


        <div class="box box-primary">
            <div class="box-header with-border">
                <!--                <h3 class="box-title">Quick Example</h3>-->
            </div>
            <!-- /.box-header -->
            <!-- form start -->
            <?php
            if (isset($err_msg)) {
                ?>
                <div class="alert alert-danger">

                    <?php echo $err_msg ?>
                </div>  
                <?php
            }
            ?>

            <form role="form" method="POST" enctype="multipart/form-data" action="<?= base_url() ?>manage/emp_bulk_upload">
                <div class="box-body">

                    <div class="form-group">
                        <label for="exampleInputFile">File input</label>
                        <input type="file" id="exampleInputFile" name="file" accept=".xls">

                        <p class="help-block">Please Upload .xls format only.</p>
                    </div>
                    <a href="<?php  echo base_url()."templates/emp.xls"  ?>"><button type="button" class="btn btn-info">Download Sample Format</button></a>
                </div>
                <!-- /.box-body -->

                <div class="box-footer">
                    <button type="submit" class="btn btn-primary" name="sumbit">Submit</button>
                </div>
            </form>
        </div>
    </section>
</div>
