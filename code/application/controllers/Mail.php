<?php
/**
 * 邮件控制器
 * @author huhong <huhong@example.com>
 * @date    2016-05-18
 */
class Mail extends MY_Controller {
    public function __construct() {
        parent::__construct();
        $this->load->library('mail_lib');
    }
    
    /**
     * 获取邮件列表
     */
    public function mail_list()
    {
        $params             = $this->public_params();
        $params['type']     = $this->request_params('type');// 1系统邮件2赛事
        $params['offset']   = (int)$this->request_params('offset');
        $params['pagesize'] = $this->request_params('pagesize');
        // 校验参数
        if ($params['type'] == '' || $params['offset']<0) {
            log_message('error', 'params_error:'.$this->task_lib->ip.','.http_build_query($_REQUEST));
            $this->output_json_return('params_err');
        }
        // 校验签名
        $this->utility->check_sign($params, $params['sign']);
        if(!$params['pagesize'])
        {
            $params['pagesize'] = self::PAGESIZE;
        }
        
        $data   = $this->mail_lib->get_mail_list($params);
        $this->output_json_return('success',$data);
    }
    
    /**
     * 获取赛事回放内容
     */
    public function video_content()
    {
        $params         = $this->public_params();
        $params['id']   = (int)$this->request_params('id');
        if ($params['id'] == '') {
            log_message('error', 'params_error:'.$this->mail_lib->ip.','.http_build_query($_REQUEST));
            $this->output_json_return('params_err');
        }
        $this->utility->check_sign($params, $params['sign']);
        $data   = $this->mail_lib->get_video_content($params);
        $this->output_json_return('success',$data);
    }
    
    /**
     * 读取邮件
     */
    public function read_mail()
    {
        $params         = $this->public_params();
        $params['id']   = (int)$this->request_params('id');
        $params['type'] = (int)$this->request_params('type');
        if ($params['id'] == '' || $params['type'] == '') {
            log_message('error', 'params_error:'.$this->mail_lib->ip.','.http_build_query($_REQUEST));
            $this->output_json_return('params_err');
        }
        $this->utility->check_sign($params, $params['sign']);
        
        $this->mail_lib->do_read_mail($params);
        $this->output_json_return('success');
    }
    
    /**
     * 删除邮件操作
     */
    public function del_mail()
    {
        $params         = $this->public_params();
        $params['ids']  = $this->request_params('ids');
        $params['type'] = (int)$this->request_params('type');
        if ($params['ids'] == '' || $params['type'] == '') {
            log_message('error', 'params_error:'.$this->mail_lib->ip.','.http_build_query($_REQUEST));
            $this->output_json_return('params_err');
        }
        $this->utility->check_sign($params, $params['sign']);
        $this->mail_lib->do_del_mail($params);
        $this->output_json_return();
    }
    
    /**
     * 一键删除邮件
     */
    public function delall_mail()
    {
        $params         = $this->public_params();
        $params['type'] = (int)$this->request_params('type');
        if ($params['type'] == '') {
            log_message('error', 'params_error:'.$this->mail_lib->ip.','.http_build_query($_REQUEST));
            $this->output_json_return('params_err');
        }
        $this->utility->check_sign($params, $params['sign']);
        $this->mail_lib->do_delall_mail($params);
        $this->output_json_return();
    }
    
    /**
     * 领取邮件奖励
     */
    public function reward_mail()
    {
        $params         = $this->public_params();
        $params['id']   = (int)$this->request_params('id');
        if ($params['id'] == '') {
            log_message('error', 'params_error:'.$this->mail_lib->ip.','.http_build_query($_REQUEST));
            $this->output_json_return('params_err');
        }
        $this->utility->check_sign($params, $params['sign']);
        $this->mail_lib->do_reward_mail($params);
        $this->output_json_return();
    }
    
    /**
     * 一键领取奖励
     */
    public function rewardall_mail()
    {
        $params = $this->public_params();
        $this->utility->check_sign($params, $params['sign']);
        $this->mail_lib->do_rewardall_mail($params);
        $this->output_json_return();
    }
    
    
}
