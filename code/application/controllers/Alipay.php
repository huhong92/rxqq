<?php
/**
 * 支付控制器
 * @author huhong <huhong@example.com>
 * @date    2016-05-18
 */
class Alipay extends MY_Controller {
    public function __construct() {
        parent::__construct();
        $this->load->library('alipay_lib');
    }
    
    
}

