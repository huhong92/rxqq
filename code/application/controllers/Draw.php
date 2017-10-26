<?php

/**
 * 抽卡控制器
 * @author huhong <huhong@example.com>
 * @date    2016-05-18
 */
class Draw extends MY_Controller {
    public function __construct() {
        parent::__construct();
        $this->load->library('draw_lib');
    }
    
    /**
     * 魔法社 类型列表
     */
    public function draw_list()
    {
        $params = $this->public_params();
        $this->utility->check_sign($params, $params['sign']);
        $data   = $this->draw_lib->get_draw_list($params);
        $this->output_json_return('success', $data);
    }
    
    /**
     * 抽卡操作
     */
    public function draw()
    {
        $params         = $this->public_params();
        $params['id']   = (int)$this->request_params('id');
        $params['type'] = (int)$this->request_params('type');
        if ($params['id'] == '' || $params['type'] == '') {
            log_message('error', 'params_err：'.$this->court_lib->ip.',',http_build_query($_REQUEST));
            $this->output_json_return('params_err');
        }
        $this->utility->check_sign($params, $params['sign']);
        $data   = $this->draw_lib->draw($params);
        $this->output_json_return('success',$data);
    }
    
    /**
     * 抽卡--可获得卡片预览
     */
    public function draw_preview()
    {
        $params             = $this->public_params();
        $params['type']     = (int)$this->request_params('type');
        $params['offset']   = (int)$this->request_params('offset');
        $params['pagesize'] = $this->request_params('pagesize');
        
        if ($params['type'] == '' || $params['offset'] < 0) {
            log_message('error', 'params_err：'.$this->court_lib->ip.',',http_build_query($_REQUEST));
            $this->output_json_return('params_err');
        }
        if (!in_array($params['type'], array(1,2,3))) {
            log_message('error', 'params_err：'.$this->court_lib->ip.',',http_build_query($_REQUEST));
            $this->output_json_return('params_err');
        }
        $this->utility->check_sign($params, $params['sign']);
         if (!$params['pagesize']) {
            $params['pagesize'] = parent::PAGESIZE;
        }
        
        $data   = $this->draw_lib->get_draw_preview($params);
        $this->output_json_return('success',$data);
    }
}

