<?php

defined('BASEPATH') OR exit('No direct script access allowed');

class MY_Loader extends CI_Loader {

    public function template($template_name,$links_name="footer_links", $vars = array(), $return = FALSE) {
        $vars['footer_links']=$links_name;
        if ($return):
            $content = $this->view('header', $vars, $return);
            $content .= $this->view($template_name, $vars, $return);
            $content .= $this->view('footer', $vars, $return);
            return $content;
        else:
            $this->view('header', $vars);
            $this->view($template_name, $vars);
            $this->view('footer', $vars);
        endif;
    }
    public function maintemplate($template_name, $vars = array(), $return = FALSE) {
        if ($return):
            $content = $this->view('main/header', $vars, $return);
            $content .= $this->view('main/'.$template_name, $vars, $return);
            $content .= $this->view('main/footer', $vars, $return);
            return $content;
        else:
            $this->view('main/header', $vars);
            $this->view('main/'.$template_name, $vars);
            $this->view('main/footer', $vars);
        endif;
    }

}

?>