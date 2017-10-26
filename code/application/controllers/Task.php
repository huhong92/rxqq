<?php
/**
 * 任务成就控制器
 * @author huhong <huhong@example.com>
 * @date    2016-05-18
 */
class Task extends MY_Controller {
    public function __construct() {
        parent::__construct();
        $this->load->library('task_lib');
    }
    
    /*获取任务列表
     * params type int 1：主线任务  2：每日任务  必须
     */
    public function task_list() {
        $params = $this->public_params();
        $params['task_type']   = $this->request_params('task_type');
        $params['offset'] = (int)$this->request_params('offset');
        $params['pagesize']    = $this->request_params('pagesize');
        // 校验参数
        if ($params['task_type'] == '' || $params['offset'] === '') {
            log_message('error', 'params_error:'.$this->task_lib->ip.','.http_build_query($_REQUEST));
            $this->output_json_return('params_err');
        }
        // 校验签名
        $this->utility->check_sign($params, $params['sign']);
        if(!$params['pagesize'])
        {
            $params['pagesize'] = 10;
        }
        //获取任务列表
        $task_list = $this->task_lib->get_task_list($params);
        $data['list'] = $task_list;
        $this->output_json_return('success', $data);
    }
    
    /*
     * 领取任务奖励
     */
    public function get_task_reward()
    {
        $params = $this->public_params();
        $params['task_no'] = $this->request_params('task_no');
        // 校验参数
        if ($params['task_no'] == '') {
            log_message('error', 'params_error:'.$this->task_lib->ip.','.http_build_query($_REQUEST));
            $this->output_json_return('params_err');
        }
        // 校验签名
        $this->utility->check_sign($params, $params['sign']);
        //领取任务奖励
        $data = $this->task_lib->get_task_reward($params);
        $this->output_json_return('success' , $data);
    }
    
    /**
     * 成就模块列表
     */
    public function amodule_list()
    {
        $params = $this->public_params();
        $this->utility->check_sign($params,$params['sign']);
        
        $data   = $this->task_lib->get_amodule_list($params);
        $this->output_json_return('success',$data);
    }
    
    /**
     * 获取成就列表
     */
    public function achievement_list()
    {
        $params             = $this->public_params();
        $params['id']       = (int)$this->request_params('id');// 模块id
        $params['offset']   = $this->request_params('offset');
        $params['pagesize'] = $this->request_params('pagesize');
        if ($params['id']   == '' || $params['offset'] < 0 || $params['offset'] === '') {
            log_message('error', 'params_error:'.$this->task_lib->ip.','.http_build_query($_REQUEST));
            $this->output_json_return('params_err');
        }
        $this->utility->check_sign($params,$params['sign']);
        if (!$params['pagesize']) {
            $params['pagesize'] = self::PAGESIZE;
        }
        $data   = $this->task_lib->get_achievement_list($params);
        $this->output_json_return('success',$data);
    }
    
    /**
     * 成就详细列表
     */
    public function achdetail_list()
    {
        $params             = $this->public_params();
        $params['cat_no']   = (int)$this->request_params('cat_no');// 成就类型编号
        if ($params['cat_no'] == '') {
            log_message('error', 'params_error:'.$this->task_lib->ip.','.http_build_query($_REQUEST));
            $this->output_json_return('params_err');
        }
        $this->utility->check_sign($params,$params['sign']);
        $data   = $this->task_lib->get_achdetail_list($params);
        $this->output_json_return('success',$data);
    }
    
    /**
     * 领取成就奖励
     */
    public function reward_achievement()
    {
        $params                 = $this->public_params();
        $params['achieve_no']   = (int)$this->request_params('achieve_no');// 成就编号
        if ($params['achieve_no'] == '') {
            log_message('error', 'params_error:'.$this->task_lib->ip.','.http_build_query($_REQUEST));
            $this->output_json_return('params_err');
        }
        $this->utility->check_sign($params,$params['sign']);
        
        $this->task_lib->do_reward_achieve($params);
        $this->output_json_return('success');
    }
    
}
