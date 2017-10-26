<?php
class Vip extends MY_Controller {
    public function __construct() {
        parent::__construct();
        $this->load->library('vip_lib');
    }
    
    public function vip_info()
    {
        
    }
}
