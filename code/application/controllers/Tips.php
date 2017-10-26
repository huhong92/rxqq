<?php

/**
 * 页面红点提示控制器
 * @author huhong <huhong@example.com>
 * @date    2016-05-18
 */
class Tips extends MY_Controller {
    public function __construct() {
        parent::__construct();
        $this->load->library('tips_lib');
    }
    
    /**
     * 获取页面红点提示
     */
    public function tip_status()
    {
        $params = $this->public_params();
        $this->utility->check_sign($params, $params['sign']);
        $data['list']   = $this->tips_lib->get_page_tips($params['uuid']);
        $data['novice_coures'] = $this->tips_lib->get_novice_coures_tips($params['uuid']);
        $this->output_json_return('success', $data);
    }
    
    /*
     * 消除页面红点提示
     */
    public function tip_deselect()
    {
        $params = $this->public_params();
        $params['page_id']  = (int)$this->request_params('page_id');
        // 校验参数
        if ($params['page_id'] === '') {
            log_message('error', 'params_error:'.$this->tips_lib->ip.','.http_build_query($_REQUEST));
            $this->output_json_return('params_err');
        }
        $this->utility->check_sign($params, $params['sign']);
        $data  = $this->tips_lib->del_page_tip($params['uuid'] , $params['page_id']);
        $this->output_json_return('success', $data);
    }
}

