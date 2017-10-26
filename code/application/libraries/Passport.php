<?php
/**
 * 获取passport.php 配置文件参数
 * author huhong
 * date 2016-05-04
 */
class Passport {
    private $CI;
    private $_cache = null;

    function __construct() {
        $this->CI = & get_instance();
    }
        
    function save($item_name,$value) {
        $this->CI->config->load('passport', true);
        $this->CI->config->set_item($item_name,$value, 'passport');
    }
    
    function get($item_name) {
        $this->CI->config->load('passport', true);
        $item = $this->CI->config->item($item_name, 'passport');
        return $item;
    }
    
    /**
     * 获取战斗服务器[IP|Port]
     */
    public function finght_server()
    {
        $this->CI->config->load('finght_server', true);
        $item = $this->CI->config->item('fingting_server', 'finght_server');
        return $item;
    }
}