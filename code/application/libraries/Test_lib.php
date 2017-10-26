<?php
class Test_lib extends Base_lib {
    public function __construct() {
        parent::__construct();
        $this->load_model('test_model');
    }
    
    public function get_skill_list($options)
    {
        $skill_list = $this->CI->test_model->list_data($options, 'skill_conf');
        return $skill_list;
    }
    
    
}

