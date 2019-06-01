<div class="content-wrapper">
    <!-- Content Header (Page header) -->
    <section class="content-header">
        <h1>
            Items
            <small>Upload Data here</small>
        </h1>
        <ol class="breadcrumb">
            <li><a href="#"><i class="fa fa-dashboard"></i> Home</a></li>
            <li><a href="#">Items</a></li>
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

            <form role="form" method="POST" enctype="multipart/form-data" action="<?= base_url() ?>manage/items_bulk_upload">
                <div class="box-body">

                    <div class="form-group col-lg-3">
                        <label for="company">Company</label>
                        <select name="company" id="company" class="form-control" onChange="getFullOptions([{target: 'branch', pk: 'id', namee: 'name', table: 'branches', condition: {company: this.value}}], 'branch_cls')" required>
                            <option value=""> Select Company</option>

                            <?php
                            if (isset($companies)) {
                                foreach ($companies->result() as $company_rs) {
                                    ?>
                                    <option value="<?= $company_rs->company_id ?>"> <?= $company_rs->company ?></option>
                                    <?php
                                }
                            }
                            ?>
                        </select>
                    </div>

                   

                    <div class="form-group col-lg-6">
                        <label for="exampleInputFile">File input</label>
                        <input type="file" id="exampleInputFile" name="file" accept=".xls" required>

                        <p class="help-block">Please Upload .xls format only for compatability.</p>
                    </div>
                    <div class="form-group col-lg-3">
                        <button type="submit" class="btn btn-primary" name="sumbit">Submit</button>
                    </div>

                    <div class="form-group col-lg-12 row">
                        <a href="<?php echo base_url() . "templates/items.xls" ?>"><button type="button" class="btn btn-info">Download Sample Format</button></a>

                    </div>

                </div>
                <!-- /.box-body -->




            </form>
        </div>
    </section>
</div>
