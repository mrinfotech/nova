<?php
if (!defined('BASEPATH'))
    exit('No direct script access allowed');

// Application specific global variables


function compress_image($source_url, $destination_url, $quality) {

    $info = getimagesize($source_url);

    if ($info['mime'] == 'image/jpeg')
        $image = imagecreatefromjpeg($source_url);

    elseif ($info['mime'] == 'image/gif')
        $image = imagecreatefromgif($source_url);

    elseif ($info['mime'] == 'image/png')
        $image = imagecreatefrompng($source_url);

    imagejpeg($image, $destination_url, $quality);
    return $destination_url;
}

function file_icon($ftype) {
    if ($ftype == 'pdf') {
        $class = "fa fa-fw fa-file-pdf-o";
    } else if ($ftype == 'doc' || $ftype == 'docx') {
        $class = "fa fa-fw fa-file-word-o";
    } else if ($ftype == 'xls' || $ftype == 'xlsx') {
        $class = "fa fa-fw fa-file-excel-o";
    } else if ($ftype == 'bmp' || $ftype == 'jpg' || $ftype == 'jpeg' || $ftype == 'png' || $ftype == 'gif' || $ftype == 'ico') {
        $class = "fa fa-fw fa-file-image-o";
    } else if ($ftype == 'mp3' || $ftype == 'mp4') {
        $class = "fa fa-fw fa-file-audio-o";
    } else if ($ftype == 'zip' || $ftype == 'rar') {
        $class = "fa fa-fw fa-file-archive-o";
    } else if ($ftype == 'txt') {
        $class = "fa fa-fw fa-file-text-o";
    } else {
        $class = "fa fa-fw fa-file-o";
    }
    return $class;
}

function getValue($vall, $table, $wcol, $col) {
    $ci = & get_instance();
    $ci->load->database();
    $query = $ci->db->query("select " . $vall . " from " . $table . " where " . $wcol . "='" . $col . "'")->row();

    return $banner = $query->$vall;
}

function getValueMulti($vall, $table, $data) {
    $ci = & get_instance();
    $ci->load->database();
    $ci->db->select($vall);
    $ci->db->from($table);
    $ci->db->where($data);
    $query = $ci->db->get();
    foreach ($query->result() as $row) {
        $banner = $row->$vall;
        if ($banner != "")
            return $banner;
    }
}

function delImage($Path) {

    if (file_exists($Path)) {

        unlink($Path);
    }
}

function deleteDir($dirPath) {
    if (!is_dir($dirPath)) {
        //throw new InvalidArgumentException("$dirPath must be a directory");
    }
    if (substr($dirPath, strlen($dirPath) - 1, 1) != '/') {
        $dirPath .= '/';
    }
    $files = glob($dirPath . '*', GLOB_MARK);
    foreach ($files as $file) {
        if (is_dir($file)) {
            self::deleteDir($file);
        } else {
            unlink($file);
        }
    }
    rmdir($dirPath);
}

function encoded($string, $key = "per", $url_safe = TRUE) {
    $CI = & get_instance();
    if ($key == null || $key == "") {
        $key = $CI->session->userdata('user_id');
    }
    //echo $key;
    //exit;

    $CI->load->library('encrypt');
    $ret = $CI->encrypt->encode($string, $key);

    if ($url_safe) {
        $ret = strtr($ret, array('+' => '.', '=' => '-', '/' => '~'));
    }

    return $ret;
}

function decoded($string, $key = "per") {
    $CI = & get_instance();
    if ($key == null || $key == "") {
        $key = $CI->session->userdata('user_id');
    }
    $string = strtr($string, array('.' => '+', '-' => '=', '~' => '/'));
    $CI->load->library('encrypt');
    return $ret = $CI->encrypt->decode($string, $key);
}

function email($to, $sub, $from, $message, $cc = "", $bcc = "") {
    $ci = & get_instance();
    $ci->load->database();
    $ci->load->library('email');
    $ci->email->from($from, 'Prabandha');
    $ci->email->to($to);
    $ci->email->cc($cc);
    $ci->email->bcc($bcc);
    $ci->email->subject($sub);
    $ci->email->message($message);
//    $ci->load->library('email');
//    $ci->email->from('info@prabandha.com', 'Prabandha');
//    $ci->email->to($to);
//    $ci->email->cc('dinesh.seerapu@mitrayainfo.com');
//    $ci->email->bcc('vinodbaggam@mitrayainfo.com');
//    $ci->email->subject('Email Test');
//    $ci->email->message('Testing the email class.');
    return $ci->email->send();
}

function query($query) {
    $ci = & get_instance();
    $ci->load->database();
    $query = $ci->db->query($query);
    return $query;
}

function sub_menus($id) {
    $ci = & get_instance();
    $ci->load->database();
    //$ci->load->model("model_all");
    ?>
<ul>
    <?php
    //$query="select * from menus where ";
    //$query = $ci->db->query($query);
        $menu_details = $ci->model_all->getTableData('menus', array("parent_id" => $id));
        foreach ($menu_details->result() as $data_records) {
            //for ($j = 0; $j < count($data_records[0]); $j++) {

            if ($data_records->status == 1) {
                $status_str = "cancel";
                $statusString = "DeActivate";
            } else {
                $status_str = "done";
                $statusString = "Activate";
            }
            ?>
            <li>
                <a href="javascript:void(0);" class=""> <!--  menu-toggle waves-effect waves-block toggled-->
                    <i class="material-icons">trending_down</i>
                    <span><?= $data_records->menu_name ?></span>
                </a>
                <a href="<?= base_url('home/menus/' . $data_records->id) ?>"><i class="material-icons" >edit</i></a> 
                <?php if ($data_records->status == 1) { ?>
                    <a id="a<?= $data_records->id ?>" href="javascript:showCommonConfirmMessage('<?= $statusString ?>', 'menus', 'id',<?= $data_records->id ?>, 'status', '0')"><i class="material-icons js-sweetalert"><?= $status_str ?></i></a>
                <?php } else { ?>
                    <a id="a<?= $data_records->id ?>" href="javascript:showCommonConfirmMessage('<?= $statusString ?>', 'menus', 'id',<?= $data_records->id ?>, 'status', '1')"><i class="material-icons js-sweetalert"><?= $status_str ?></i></a>
                    <?php } ?>
                <?php echo sub_menus($data_records->id); ?>
            </li>

            <?php
        }
        ?>
    </ul>
    <?php
}
