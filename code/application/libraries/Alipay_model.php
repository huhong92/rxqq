<?php
class Alipay_lib extends Base_lib {
    public function __construct() {
        parent::__construct();
        $this->load_model('alipay_model');
    }
    
    
}
