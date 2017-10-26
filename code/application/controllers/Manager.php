<?php
/**
 * 经理类
 * @author huhong
 * @date 2016-05-10
 */
class Manager extends MY_Controller {
    public function __construct() {
        parent::__construct();
        $this->load->library('manager_lib');
    }
    
    /**
     * 第三方注册登录接口
     */
    public function login_for_thirdparty()
    {
        $params['app_id']       = $this->request_params('app_id');
        $params['user_id']      = $this->request_params('user_id');
        $params['user_name']    = urldecode($this->request_params('user_name'));
        $params['service_area'] = $this->request_params('service_area');
        //$params['platform']     = $this->request_params('platform');
        $params['sign']         = $this->request_params('sign');
        
        // 校验参数
        if ($params['app_id'] == '' || $params['user_id'] == '' || $params['user_name'] == '' || $params['service_area'] == '' || $params['sign'] == '') {
            log_message('error', 'params_error:'.$this->manager_lib->ip.','.http_build_query($_REQUEST));
            $this->output_json_return('params_err');
        }
        // 校验签名
        // $this->utility->check_sign($params, $params['sign']);
        $params['platform'] = 2;
        // 注册操作
        $register_info  = $this->manager_lib->login_for_thirdparty($params);
        $params['uuid'] = $register_info['uuid'];
        // 返回经理信息
        $data                   = $this->manager_lib->get_manager_detail($params['uuid']);
        $data['current_exp']    = $data['current_exp'] - $data['total_exp'];
        // 设置登录token
        $token_info = $this->set_token($params['uuid'], $params['app_id']);
        if (!$token_info['token']) {
            log_message('error', 'token_set_err:'.$this->manager_lib->ip.',token设置失败');
            $this->output_json_return('token_set_err');
        }
        //登录成功，重置所有页面红点状态
        $this->manager_lib->refresh_tips($params['uuid']);
        
        $data['token']          = $token_info['token'];
        $data['init_name']      = $register_info['init_name'];
        $data['init_teamlogo']  = $register_info['init_teamlogo'];
        $data['init_lineup']    = $register_info['init_lineup'];
        $this->output_json_return('success', $data);
    }


    /**
     * 获取经理基本信息接口
     */
    public function m_info()
    {
        $params                 = $this->public_params();
        $data                   = $this->manager_lib->get_manager_detail($params['uuid']);
        $data['current_exp']    = $data['current_exp'] - $data['total_exp'];
        if (!$data) {
            $this->output_json_return('empty_data');
        }
        $data['token'] = $this->get_token($params['uuid'])['token'];
        $this->output_json_return('success', $data);
    }
    
    /**
     * 完善经理名称和队标
     */
    public function update_m_info()
    {
        $params                 = $this->public_params();
        $params['name']         = $this->request_params('name');
        $params['team_logo']    = $this->request_params('team_logo');
        
        // 校验参数
        if ($params['name'] == '' || $params['team_logo'] == '') {
            log_message('error', 'params_error:'.$this->manager_lib->ip.','.http_build_query($_REQUEST));
            $this->output_json_return('params_err');
        }
        // 校验签名
        $this->utility->check_sign($params, $params['sign']);
        // 校验队标、名称是否初始化过 TODO
        $res = $this->manager_lib->check_init($params);
        if ($res['init_num']) {// 队标已经初始化过
            log_message('error', 'teamlogo_init_exists:'.$this->manager_lib->ip.',队标已经初始化');
            $this->output_json_return('teamlogo_init_exists');
        }
        $res = $this->manager_lib->check_init($params, $type = 1);
        if ($res['init_num']) {// 经理名称已经初始化过
            log_message('error', 'name_init_exists:'.$this->manager_lib->ip.',名称已经初始化');
            $this->output_json_return('name_init_exists');
        }
        
        $this->manager_lib->update_m_info($params);
        $this->output_json_return();
    }
    
    /**
     * 获取队标列表
     */
    public function teamlogo_info()
    {
        $params     = $this->public_params();
        // 校验签名
        $this->utility->check_sign($params, $params['sign']);
        $data['info']   = $this->manager_lib->teamlogo_info();
        $this->output_json_return('success',$data);
    }
    
    /**
     * 更新队标接口
     */
    public function update_teamlogo()
    {
        $params                 = $this->public_params();
        $params['team_logo']    = $this->request_params('team_logo');
        
        // 校验参数
        if ($params['team_logo'] == '') {
            log_message('error', 'params_error:'.$this->manager_lib->ip.','.http_build_query($_REQUEST));
            $this->output_json_return('params_err');
        }
        // 校验签名
        $this->utility->check_sign($params, $params['sign']);
        // 校验经理是否更新过队标
        $res = $this->manager_lib->check_init($params);
        if ($res['init_num'] >1) {
            log_message('error', 'one_update_teamlogo_error:'.$this->manager_lib->ip.',经理只能更新一次队标');
            $this->output_json_return('one_update_teamlogo_error');
        }
        $this->manager_lib->update_teamlogo($params);
        $this->output_json_return();
    }
    
    /**
     * 获取初始阵容列表
     */
    public function lineup_list()
    {
        $params = $this->public_params();
        $data   = $this->manager_lib->lineup_list($params);
        $this->output_json_return('success', $data);
    }
    
    /**
     * 初始化阵型
     */
    public function lineup()
    {
        $params         = $this->public_params();
        $params['id']   = $this->request_params('id');
        // 校验参数
        if ($params['id'] == '') {
            log_message('error', 'params_error:'.$this->manager_lib->ip.','.http_build_query($_REQUEST));
            $this->output_json_return('params_err');
        }
        // 校验签名
        // $this->utility->check_sign($params, $params['sign']);
        $this->manager_lib->lineup($params);
        $this->output_json_return('success');
    }
    
    /**
     * 随机获取经理名称
     */
    public function random_name()
    {
        $params['app_id']   = $this->request_params('app_id');
        $params['token']    = $this->request_params('token');
        $params['uuid']     = $this->request_params('uuid');
        $params['sign']     = $this->request_params('sign');
        if ($params['app_id'] == '' || $params['sign'] == '') {
            log_message('error', 'params_error:'.$this->manager_lib->ip.','.http_build_query($_REQUEST));
            $this->output_json_return('params_err');
        }
        $this->utility->check_sign($params, $params['sign']);
        $data   = $this->manager_lib->get_random_name($params);
        $this->output_json_return('success',$data);
    }
    
    /**
     * 获取经理战斗力
     */
    public function m_fighting()
    {
        $params             = $this->public_params();
        $params['type']     = $this->request_params('type');// 2=>天梯阵容战斗力 1普通阵容战斗力
        if ($params['type'] && !in_array($params['type'], array(1,2))) {
            $this->output_json_return('params_err');
        }
        $fighting_info  = $this->manager_lib->m_fighting($params['uuid'],$params['type']);
        $data['fighting']   = $fighting_info['finghting'];
        $this->output_json_return('success',$data);
    }
    
    /**
     * 获取VIP信息
     */
    public function vip_info()
    {
        $params         = $this->public_params();
        $params['vip']  = $this->request_params('vip');
        if ($params['vip'] === '' || $params['vip'] > 12 || $params['vip'] < 0) {
            log_message('error', 'params_error:'.$this->manager_lib->ip.','.http_build_query($_REQUEST));
            $this->output_json_return('params_err');
        }
        $this->utility->check_sign($params, $params['sign']);
        
        $data   = $this->manager_lib->mvip_info($params);
        $this->output_json_return('success',$data);
    }
    
    /**
     * 获取经理天赋列表
     */
    public function talent_list()
    {
        $params             = $this->public_params();
        $params['type']     = $this->request_params('type');
        // 校验参数
        if ($params['type'] == '') {
            log_message('error', 'params_error:'.$this->manager_lib->ip.','.http_build_query($_REQUEST));
            $this->output_json_return('params_err');
        }
        // 校验签名
        $this->utility->check_sign($params, $params['sign']);
        
        $data['list']   = $this->manager_lib->get_talent_list($params);
        $this->output_json_return('success',$data);
    }
    
    /**
     * 激活经理天赋
     */
    public function talent_active()
    {
        $params         = $this->public_params();
        $params['id']   = $this->request_params('id');
        // 校验参数
        if ($params['id'] == '') {
            log_message('error', 'params_error:'.$this->manager_lib->ip.','.http_build_query($_REQUEST));
            $this->output_json_return('params_err');
        }
        // 校验签名
        $this->utility->check_sign($params, $params['sign']);
        $this->manager_lib->active_talent($params);
        $this->output_json_return('success');
    }
    
    /**
     * 重置天赋
     */
    public function talent_reset()
    {
        $params = $this->public_params();
        // 校验签名
        $this->utility->check_sign($params, $params['sign']);
        $res = $this->manager_lib->reset_talent($params);
        $this->output_json_return();
    }
    
    /**
     * 获取经理活力信息（体力、耐力）
     */
    public function m_active()
    {
        $params                 = $this->public_params();
//        $data                   = $this->manager_lib->get_manager_detail($params['uuid']);
//        $data['current_exp']    = $data['current_exp'] - $data['total_exp'];
//        if (!$data) {
//            $this->output_json_return('empty_data');
//        }
        $data   = $this->manager_lib->get_m_active($params);
        $this->output_json_return('success',$data);
    }
    
    
    
    
}
