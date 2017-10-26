<?php
/**
 * 活动控制器
 * @author huhong <huhong@example.com>
 * @date    2016-05-18
 */
class Active extends MY_Controller {
    public function __construct() {
        parent::__construct();
        $this->load->library('active_lib');
    }
    
    /**
     * 公告列表
     */
    public function notice_list()
    {
        $params = $this->public_params();
        $data   = $this->active_lib->get_notice_list($params);
        if (!$data) {
            log_message('info', 'empty_data:'.$this->active_lib->ip.',未查询到公告数据');
            $this->output_json_return('empty_data');
        }
        $this->output_json_return('success', $data);
    }
    
}
