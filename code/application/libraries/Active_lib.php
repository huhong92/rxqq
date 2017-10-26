<?php
class Active_lib extends Base_lib {
    public function __construct() {
        parent::__construct();
        $this->load_model('active_model');
    }
    
    /**
     * 获取公告列表
     */
    public function get_notice_list($params)
    {
        // 获取用户区服和app_id(包id)
        $where  = array('manager_idx'=>$params['uuid'],'status'=>1);
        $fields = "manager_idx uuid,app_id,service_area";
        $table  = "register";
        $m_info = $this->CI->active_model->get_one($where,$table,$fields);
        
        // 根据区服获取公告
        $table_2            = "notice_conf";
        // $options['where']   = array('service_area'=>$m_info['service_area']);
        $options['where']   = array('status'=>1);
        $options['fields']  = "idx id,service_area,info";
        $options['order']   = "idx desc";
        $list   = $this->CI->active_model->list_data($options,$table_2);
        return $list;
    }
    
}

