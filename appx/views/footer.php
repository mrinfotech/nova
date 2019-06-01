<footer class="main-footer">
    <div class="pull-right hidden-xs">
        <b>Version</b> 2.4.0
    </div>
    <strong>Copyright &copy; 2018 <a href="#">Nova Agritech</a>.</strong> All rights
    reserved.
</footer>

<!-- Control Sidebar -->
<aside class="control-sidebar control-sidebar-dark">

</aside>
<!-- /.control-sidebar -->
<!-- Add the sidebar's background. This div must be placed
     immediately after the control sidebar -->
<div class="control-sidebar-bg"></div>
</div>
<!-- ./wrapper -->

<?php
$this->load->view($footer_links);
?>
<script>
    $(document).ready(function () {
        $('.sidebar-menu').tree()
    });


    function getOptions(target, field1, field2, table, whereArray) {
//    var whereJs = JSON.stringify(whereArray);
//    alert(whereJs);
        var original_value = $('#' + target).val();
        $('#' + target).html('');
        if (country != "") {
            var whereJs = JSON.stringify(whereArray);
            $.post("<?php echo base_url('ajax/getOptions') ?>", {idd: field1, namee: field2, where_data: whereJs, table: table}, function (response) {
                for (i = 0; i < response.total_rows; i++) {
                    obj = response.records[i];
                    //alert(obj[field1]);
                    $('#' + target).append($('<option>', {value: obj[field1], text: obj[field2]}));
                }
                $('#' + target).val(original_value);
            }, 'json');
        }

    }


    function getFullOptions(objects) {
        for (i = 0; i < objects.length; i++) {
            var cur_object = objects[i];
            target = cur_object.target;
            pkey = cur_object.pk;
            field = cur_object.namee;
            whereArray = cur_object.condition;
            table = cur_object.table;
            join = cur_object.join;
            clas = cur_object.clas;
            var original_value = $('#' + (target)).val();
            console.log(target, pkey, field, whereArray, table);
            $('#' + target).html('');
            var whereJs = JSON.stringify(whereArray);
            $.post("<?php echo base_url('ajax/getOptions') ?>", {idd: pkey, namee: field, where_data: whereJs, table: table}, function (response) {
                // console.log(response); 
                $('#' + target).append($('<option>', {value: "", text: "select " + target}));
                for (i = 0; i < response.total_rows; i++) {
                    obj = response.records[i];
                    // alert(obj[field]);
                    $('#' + target).append($('<option>', {value: obj[pkey], text: obj[field]}));
                }
                $('#' + target).val(original_value);
               
            }, 'json');
        }

    }

</script>
</body>
</html>
