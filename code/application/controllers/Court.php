<?php
/**
 * 球场相关控制器
 * @author huhong <huhong@example.com>
 * @date    2016-05-18
 */
class Court extends MY_Controller {
    public function __construct() {
        parent::__construct();
        $this->load->library('court_lib');
    }
    
    /**
     * 经理球员列表
     */
    public function player_list()
    {
        $params                     = $this->public_params();
        $params['type']             = (int)$this->request_params('type');// 1上阵球员 2闲置球员3所有球员
        $params['offset']           = (int)$this->request_params('offset');
        $params['pagesize']         = $this->request_params('pagesize');
        $params['position_type']    = $this->request_params('position_type');
        $params['struc_type']       = $this->request_params('struc_type');// 0所有球场上1普通阵容2天梯阵容
        
        // 校验参数
        if ($params['type'] == '' || $params['offset'] === '' || $params['offset'] < 0) {
            log_message('error', 'params_err：'.$this->court_lib->ip.',',http_build_query($_REQUEST));
            $this->output_json_return('params_err');
        }
        
        $player_type = $this->passport->get('player_type');
        if (!in_array($params['type'], $player_type)) {
            log_message('error', 'player_type_err：'.$this->court_lib->ip.',球员类型输入错误');
            $this->output_json_return('player_type_err');
        }
        
        // 校验签名
        $this->utility->check_sign($params, $params['sign']);
        if (!$params['pagesize']) {
            $params['pagesize'] = parent::PAGESIZE;
        }
        if (!$params['struc_type']) {
            $params['struc_type']   = 0;
        }
        $data = $this->court_lib->get_player_list($params);
        if (!$data) {
            log_message('info', 'empty_data:'.$this->court_lib->ip.',未查询到球员列表数据');
            $this->output_json_return('empty_data');
        }
        $this->output_json_return('success', $data);
    }
    
    /**
     * 获取球员卡详细信息
     */
    public function player_info()
    {
        $params                 = $this->public_params();
        $params['id']           = $this->request_params('id');
        $params['struc_type']   = $this->request_params('struc_type');
        // 校验参数
        if ($params['id'] == '') {
            log_message('error', 'params_err:'.$this->court_lib->ip.','.http_build_query($_REQUEST));
            $this->output_json_return('params_err');
        }
        // 校验签名
        $this->utility->check_sign($params, $params['sign']);
        
        // 获取球员信息
        $data  = $this->utility->get_player_info($params);
        $this->output_json_return('success', $data);
    }
    
    /**
     * 球员装备信息
     */
    public function player_equipt()
    {
        $params         = $this->public_params();
        $params['id']   = (int)$this->request_params('id');
        if ($params['id'] == '') {
            log_message('error', 'params_err：'.$this->court_lib->ip.',',http_build_query($_REQUEST));
            $this->output_json_return('params_err');
        }
        $this->utility->check_sign($params, $params['sign']);
        
        $player_equipt = $this->court_lib->player_equipt($params);
        if (!$player_equipt) {
            $this->output_json_return('empty_data');
        }
        $this->output_json_return('success', $player_equipt);
    }
    
    /**
     * 获取球员技能信息
     */
    public function player_skill()
    {
        $params         = $this->public_params();
        $params['id']   = (int)$this->request_params('id');
        if ($params['id'] == '') {
            log_message('error', 'params_err：'.$this->court_lib->ip.',',http_build_query($_REQUEST));
            $this->output_json_return('params_err');
        }
        $this->utility->check_sign($params, $params['sign']);
        
        $skill_info = $this->court_lib->get_skill_info($params);
        if (!$skill_info) {
            $this->output_json_return('empty_data');
        }
        $this->output_json_return('success', $skill_info);
    }
    
    
    /**
     * 球员下场操作
     */
    public function enter_court()
    {
        $params                 = $this->public_params();
        $params['id']           = (int)$this->request_params('id');
        $params['struc_type']   = $this->request_params('struc_type'); // 1普通阵容2天梯阵容
        
        // 校验参数
        if ($params['id'] == '') {
            log_message('error', 'params_err:'.$this->court_lib->ip.','.http_build_query($_REQUEST));
            $this->output_json_return('params_err');
        }
        // 校验签名
        $this->utility->check_sign($params, $params['sign']);
        $this->court_lib->enter_player($params);
        $this->output_json_return();
    }
    
    /**
     * 场上球员交换位置
     */
    public function exchange_position()
    {
        $params                 = $this->public_params();
        $params['id']           = (int)$this->request_params('id');
        $params['struc_type']   = $this->request_params('struc_type');// 1普通阵容2天梯阵容
        $params['position_no']  = (int)$this->request_params('position_no');
        
        // 校验参数
        if ($params['id'] == '') {
            log_message('error', 'params_err:'.$this->court_lib->ip.','.http_build_query($_REQUEST));
            $this->output_json_return('params_err');
        }
        if (!in_array($params['position_no'], array(0,1,2,3,4,5,6))) {
            log_message('error', 'params_err:'.$this->court_lib->ip.','.http_build_query($_REQUEST));
            $this->output_json_return('params_err');
        }
        $this->utility->check_sign($params, $params['sign']);
        
        $this->court_lib->do_exchange_position($params);
        $this->output_json_return();
    }
    
    
    /**
     * 阵型列表
     */
    public function structure_list()
    {
        $params             = $this->public_params();
        $params['offset']   = $this->request_params('offset');
        $params['pagesize'] = $this->request_params('pagesize');
        $params['type']     = $this->request_params('type');
        
        // 校验参数
        if ($params['offset'] === '' ||  (int)$params['offset'] < 0) {
            log_message('error', 'params_err:'.$this->court_lib->ip.','.http_build_query($_REQUEST));
            $this->output_json_return('params_err');
        }
        // 校验签名
        $this->utility->check_sign($params, $params['sign']);
        // 获取阵型列表
        if (!$params['pagesize']) {
            $params['pagesize'] = parent::PAGESIZE;
        }
        // 获取阵型列表
        $options['where']   = array('status' => 1);
        $total_count        = $this->court_lib->get_total_count($options['where'], 'structure_conf');
        if (!$total_count) {
            log_message('info', 'empty_data:'.$this->court_lib->ip.',未查询到阵型列表数据');
            $this->output_json_return('empty_data');
        }
        $data['pagecount']  = ceil($total_count/$params['pagesize']);
        $data['list']       = $this->court_lib->structure_list($params);
        $this->output_json_return('success', $data);
    }
    
    /**
     * 更新经理使用阵型
     */
    public function update_structure()
    {
        $params         = $this->public_params();
        $params['id']   = (int)$this->request_params('id');
        $params['type'] = $this->request_params('type');// （默认1）1普通阵容2天梯阵容
        
        if ($params['id'] == '') {
            log_message('error', 'params_err:'.$this->court_lib->ip.','.http_build_query($_REQUEST));
            $this->output_json_return('params_err');
        }
        $this->utility->check_sign($params, $params['sign']);
        
        $data   = $this->court_lib->update_structure_info($params);
        $this->output_json_return('success');
    }
    
    /**
     * 球员卡进阶信息接口
     */
    public function  player_upgrade_info()
    {
        $params         = $this->public_params();
        $params['id']   = $this->request_params('id');
        // 校验参数
        if ($params['id'] == '') {
            log_message('error', 'params_err:'.$this->court_lib->ip.','.http_build_query($_REQUEST));
            $this->output_json_return('params_err');
        }
        $this->utility->check_sign($params, $params['sign']);
        // 获取球员升阶配置信息
        $upgrade_info = $this->court_lib->get_player_upgrade_info($params);
        $this->output_json_return('success',$upgrade_info);
    }
    
    /**
     * 球员升阶操作
     */
    public function player_upgrade()
    {
        $params                 = $this->public_params();
        $params['id']           = (int)$this->request_params('id');
        // 校验参数
        if ($params['id'] == '') {
            $this->output_json_return('params_err');
        }
        $this->utility->check_sign($params, $params['sign']);
        $this->court_lib->do_player_upgrade($params);
        $this->output_json_return('success');
    }
    
    /**
     * 查看训练场详情接口
     */
    public function  trainground_info()
    {
        $params = $this->public_params();
        $this->utility->check_sign($params, $params['sign']);
        
        // 获取经理训练位数
        $data   = $this->court_lib->get_trainground_info($params);
        if (!$data) {
            $this->output_json_return('empty_data');
        }
        $this->output_json_return('success',$data);
    }
    
    /**
     * 球员卡训练操作
     */
    public function train()
    {
        $params                 = $this->public_params();
        $params['id']           = (int)$this->request_params('id');
        $params['tg_no']        = (int)$this->request_params('tg_no');
        $params['attribute']    = (int)$this->request_params('attribute'); // 1-5 5个一级属性
        $params['type']         = (int)$this->request_params('type');
        // 校验参数
        if ($params['id'] == '' || $params['attribute'] == '' || $params['type'] == '' || $params['tg_no'] == '') {
            log_message('error', 'params_error:'.$this->court_lib->ip.','.  http_build_query($_REQUEST));
            $this->output_json_return('params_err');
        }
        // 校验签名
        $this->utility->check_sign($params, $params['sign']);
        $params['att_no']   = $params['attribute'];
        // 判断该球员是否正在训练
        $train_info = $this->court_lib->is_train($params);
        if ($train_info) {
            $this->output_json_return('player_training');
        }
        // 判断该球场位是否可训练
        $is_do  = $this->court_lib->can_do_train($params);
        if (!$is_do) {
            log_message('error', 'position_cannt_train_error:'.$this->court_lib->ip.',该训练位暂不可训练球员');
            $this->output_json_return('position_cannt_train');
        }
        
        $this->court_lib->do_train($params);
        
        $this->output_json_return('success');
    }
    
    /**
     * 训练位解锁操作
     */
    public function train_unlock()
    {
        $params             = $this->public_params();
        $params['tg_no']    = (int)$this->request_params('tg_no');
        // 校验参数
        if ($params['tg_no'] == '') {
            log_message('error', 'params_error:'.$this->court_lib->ip.','.  http_build_query($_REQUEST));
            $this->output_json_return('params_err');
        }
        // 校验签名
        $this->utility->check_sign($params, $params['sign']);
        $this->court_lib->train_unlock($params);
        $this->output_json_return('success');
    }
    
    /**
     * 球票取消训练时间接口
     */
    public function clear_time()
    {
        $params             = $this->public_params();
        $params['id']       = (int)$this->request_params('id');
        // 校验参数
        if ($params['id'] == '') {
            $this->output_json_return('params_err');
        }
        // 校验签名
        $this->utility->check_sign($params, $params['sign']);
        // 执行球票取消训练时间操作
        $this->court_lib->clear_train_time($params);
        
        $this->output_json_return('success');
    }
    
    /**
     * 查看球员卡一级训练点数信息
     */
    public function trainpoint_info()
    {
        $params         = $this->public_params();
        $params['id']   = (int)$this->request_params('id');
        // 校验参数
        if ($params['id'] == '') {
            log_message('error', 'params_error:'.$this->court_lib->ip.','.  http_build_query($_REQUEST));
            $this->output_json_return('params_err');
        }
        // 校验签名
        $this->utility->check_sign($params, $params['sign']);
        $data   = $this->court_lib->get_trainpoint_info($params);
        $this->output_json_return('success', $data);
    }
    
    /**
     * 查看球员卡二级属性的的训练点数信息
     */
    public function attrpoint_info ()
    {
        $params                 = $this->public_params();
        $params['id']           = (int)$this->request_params('id');
        $params['attribute']    = (int)$this->request_params('attribute');
        // 校验参数
        if ($params['id'] == '' || $params['attribute'] == '') {
            log_message('error', 'params_error:'.$this->court_lib->ip.','.  http_build_query($_REQUEST));
            $this->output_json_return('params_err');
        }
        // 校验签名
        $this->utility->check_sign($params, $params['sign']);
        
        $data   = $this->court_lib->get_attrpoint_info($params);
        $this->output_json_return('success', $data);
    }
    
    /**
     * 分配球员训练点
     */
    public function trainpoint_allo()
    {
        $params                 = $this->public_params();
        $params['id']           = $this->request_params('id');
        $params['attribute']    = $this->request_params('attribute');// 分配的二级属性
        $params['value']        = $this->request_params('value');
        $params['attribute_2']  = $this->request_params('attribute_2');
        $params['value_2']      = $this->request_params('value_2');
        $params['attribute_3']  = $this->request_params('attribute_3');
        $params['value_3']      = $this->request_params('value_3');
        if ($params['id'] == '' || $params['attribute'] == '' || $params['value'] == '') {
            log_message('error', 'params_error:'.$this->court_lib->ip.','.  http_build_query($_REQUEST));
            $this->output_json_return('params_err');
        }
        $this->utility->check_sign($params, $params['sign']);
        $this->court_lib->allo_trainpoint($params);
        $this->output_json_return('success');
    }
    
    /**
     * 洗炼球员训练点（球员所有训练点）
     */
    public function clear_trainpoint()
    {
        $params         = $this->public_params();
        $params['id']   = $this->request_params('id');
        if ($params['id'] == '') {
            log_message('error', 'params_error:'.$this->court_lib->ip.','.  http_build_query($_REQUEST));
            $this->output_json_return('params_err');
        }
        $this->utility->check_sign($params, $params['sign']);
        $this->court_lib->clear_trainpoint($params);
        $this->output_json_return('success');
    }
    
    /**
     * 重置球员训练点（训练完成，并且存在train_curr表中的数据才可重置）
     */
    public function reset_trainpoint()
    {
        $params = $this->public_params();
        $params['id']   = $this->request_params('id');
        if ($params['id'] == '') {
            log_message('error', 'params_error:'.$this->court_lib->ip.','.  http_build_query($_REQUEST));
            $this->output_json_return('params_err');
        }
        $this->utility->check_sign($params, $params['sign']);
        $this->court_lib->reset_trainpoint($params);
        $this->output_json_return();
    }
    
    /**
     * 释放训练位
     */
    public function release_trainground()
    {
        $params = $this->public_params();
        $params['id']   = $this->request_params('id');
        if ($params['id'] == '') {
            log_message('error', 'params_error:'.$this->court_lib->ip.','.  http_build_query($_REQUEST));
            $this->output_json_return('params_err');
        }
        $this->utility->check_sign($params, $params['sign']);
        $this->court_lib->release_tg($params);
        $this->output_json_return();
    }
    
    /**
     * 清空球员疲劳值
     */
    public function clear_fatigue()
    {
        $params         = $this->public_params();
        $params['id']   = $this->request_params('id');
        if ($params['id'] == '') {
            log_message('error', 'params_error:'.$this->court_lib->ip.','.  http_build_query($_REQUEST));
            $this->output_json_return('params_err');
        }
        $this->utility->check_sign($params, $params['sign']);
        $this->court_lib->do_clear_fatigue($params);
        $this->output_json_return();
    }
    
    /**
     * 获取装备列表
     */
    public function equipt_list()
    {
        $params             = $this->public_params();
        $params['type']     = (int)$this->request_params('type');//1球衣2球裤3球鞋4all
        $params['offset']   = (int)$this->request_params('offset');
        $params['pagesize'] = $this->request_params('pagesize');
        // 校验参数
        if ($params['offset'] === '' || $params['offset'] < 0) {
            log_message('error', 'params_err：'.$this->court_lib->ip.',',http_build_query($_REQUEST));
            $this->output_json_return('params_err');
        }
        // 校验签名
        $this->utility->check_sign($params, $params['sign']);
        if (!$params['pagesize']) {
            $params['pagesize'] = parent::PAGESIZE;
        }
        
        // 获取装备
        $total_count = $this->court_lib->equipt_total_count($params);
        if (!$total_count) {
            log_message('info', 'empty_data:'.$this->court_lib->ip.',未查询到装备列表数据');
            $this->output_json_return('empty_data');
        }
        $data['pagecount']  = ceil($total_count/$params['pagesize']);
        $data['list']       = $this->court_lib->equipt_list($params);
        $this->output_json_return('success', $data);
    }
    
    /**
     * 装备详细信息
     */
    public function equipt_info()
    {
        $params         = $this->public_params();
        $params['id']   = $this->request_params('id');
        if ($params['id'] == '') {
            log_message('error', 'params_err：'.$this->court_lib->ip.',',http_build_query($_REQUEST));
            $this->output_json_return('params_err');
        }
        $this->utility->check_sign($params, $params['sign']);
        
        $data = $this->court_lib->equipt_info($params);
        $this->output_json_return('success', $data);
    }
    
    /*
     * 装备 球员 一键升阶所需人民币接口
     */
    public function rmb_upgrade_info()
    {
        $params         = $this->public_params();
        $params['type'] = (int)$this->request_params('type');
        $params['id']   = (int)$this->request_params('id');
        if ($params['id'] == '' || $params['type'] == '') {
            log_message('error', 'params_err：'.$this->court_lib->ip.',',http_build_query($_REQUEST));
            $this->output_json_return('params_err');
        }
        $this->utility->check_sign($params, $params['sign']);
        
        $data   = $this->court_lib->get_rmb_upgrade_info($params);
        $this->output_json_return('success', $data);
    }


    /**
     * 装备升级|强化所需信息接口
     */
    public function equipt_upgrade_info()
    {
        $params         = $this->public_params();
        $params['id']   = (int)$this->request_params('id');
        if ($params['id'] == '') {
            log_message('error', 'params_err：'.$this->court_lib->ip.',',http_build_query($_REQUEST));
            $this->output_json_return('params_err');
        }
        $this->utility->check_sign($params, $params['sign']);
        
        $data   = $this->court_lib->eupgrade_info($params);
        $this->output_json_return('success', $data);
    }
    
    /**
     * 装备升级|强化
     */
    public function upgrade_equipt()
    {
        $params                 = $this->public_params();
        $params['id']           = (int)$this->request_params('id');
        $params['type']         = (int)$this->request_params('type');
        $params['euro']         = (int)$this->request_params('euro');
        $params['junior_card']  = $this->request_params('junior_card');
        $params['middle_card']  = $this->request_params('middle_card');
        $params['senio_card']   = $this->request_params('senio_card');
        if ($params['id'] == '' || $params['type'] == '' || $params['euro'] == '') {
            log_message('error', 'params_err：'.$this->court_lib->ip.',',http_build_query($_REQUEST));
            $this->output_json_return('params_err');
        }
        $this->utility->check_sign($params, $params['sign']);
        
        $this->court_lib->upgrade_equipt($params);
        $this->output_json_return('success');
    }
    
    /**
     * 卸下球员卡装备
     */
    public function unload_equipt()
    {
        $params         = $this->public_params();
        $params['id']   = (int)$this->request_params('id');// 装备id
        if ($params['id'] == '') {
            log_message('error', 'params_err：'.$this->court_lib->ip.',',http_build_query($_REQUEST));
            $this->output_json_return('params_err');
        }
        $this->utility->check_sign($params, $params['sign']);
        
        $this->court_lib->unload_equipt($params);
        $this->output_json_return('success');
    }
    
    /**
     * 装备装备
     */
    public function load_equipt()
    {
        $params                 = $this->public_params();
        $params['id']           = (int)$this->request_params('id');
        $params['equipt_id']    = (int)$this->request_params('equipt_id');
        if ($params['id'] == '') {
            log_message('error', 'params_err：'.$this->court_lib->ip.',',http_build_query($_REQUEST));
            $this->output_json_return('params_err');
        }
        $this->utility->check_sign($params, $params['sign']);
        
        $this->court_lib->load_equipt($params);
        $this->output_json_return('success');
    }
    
    /**
     * 获取分解信息接口
     */
    public function decompose_info()
    {
        $params             = $this->public_params();
        $params['id']       = (int)$this->request_params('id');// 分解物id
        $params['type']     = (int)$this->request_params('type');// 分解物类型 1球员卡2装备
        $params['id_2']     = $this->request_params('id_2');// 分解物id
        $params['type_2']   = $this->request_params('type_2');// 分解物类型 1球员卡2装备
        $params['id_3']     = $this->request_params('id_3');
        $params['type_3']   = $this->request_params('type_3');
        $params['id_4']     = $this->request_params('id_4');
        $params['type_4']   = $this->request_params('type_4');
        $params['id_5']     = $this->request_params('id_5');
        $params['type_5']   = $this->request_params('type_5');
        
        if ($params['type'] == '' || $params['id'] == '') {
            log_message('error', 'params_err：'.$this->court_lib->ip.',',http_build_query($_REQUEST));
            $this->output_json_return('params_err');
        }
        $this->utility->check_sign($params, $params['sign']);
        
        $data   = $this->court_lib->get_decompose_info($params);
        $this->output_json_return('success',$data);
    }
    
    /**
     * 分解接口
     */
    public function decompose()
    {
        $params             = $this->public_params();
        $params['id']       = (int)$this->request_params('id');// 分解物id
        $params['type']     = (int)$this->request_params('type');// 分解物类型 1球员卡2装备
        $params['id_2']     = $this->request_params('id_2');// 分解物id
        $params['type_2']   = $this->request_params('type_2');// 分解物类型 1球员卡2装备
        $params['id_3']     = $this->request_params('id_3');
        $params['type_3']   = $this->request_params('type_3');
        $params['id_4']     = $this->request_params('id_4');
        $params['type_4']   = $this->request_params('type_4');
        $params['id_5']     = $this->request_params('id_5');
        $params['type_5']   = $this->request_params('type_5');
        $params['id_6']     = $this->request_params('id_6');
        $params['type_6']   = $this->request_params('type_6');
        
        if ($params['type'] == '' || $params['id'] == '') {
            log_message('error', 'params_err：'.$this->court_lib->ip.',',http_build_query($_REQUEST));
            $this->output_json_return('params_err');
        }
        $this->utility->check_sign($params, $params['sign']);
        
        $data   = $this->court_lib->decompose($params);
        $this->output_json_return();
    }
    
    /**
     * 一键分解接口
     */
    public function decompose_all()
    {
        $params             = $this->public_params();
        $params['player']   = $this->request_params('player');
        $params['equipt']   = $this->request_params('equipt');
        $params['gem']      = $this->request_params('gem');
        // 分解物都为空-直接返回
        if ($params['player'] == '' && $params['equipt'] == '' && $params['gem'] == '') {
            $this->output_json_return();
        }
        $this->utility->check_sign($params, $params['sign']);
        $data   = $this->court_lib->do_decompose_all($params);
        $this->output_json_return();
    }
    
    
    /**
     * 球员图鉴(金卡、橙卡)
     */
    public function player_lib()
    {
        $params             = $this->public_params();
        $params['offset']   = (int)$this->request_params('offset');
        $params['pagesize'] = $this->request_params('pagesize');
        if ($params['offset'] === '' || $params['offset'] < 0) {
            log_message('error', 'params_err：'.$this->court_lib->ip.',',http_build_query($_REQUEST));
            $this->output_json_return('params_err');
        }
        $this->utility->check_sign($params, $params['sign']);
        if (!$params['pagesize']) {
            $params['pagesize'] = parent::PAGESIZE;
        }
        
        $total_count    = $this->court_lib->get_total_count(array('quality >'=>4,'status'=>1), 'player_lib');
        if (!$total_count) {
            log_message('info', 'empty_data:'.$this->court_lib->ip.',未查询到球员图鉴列表数据');
            $this->output_json_return('empty_data');
        }
        $data['pagecount']  = ceil($total_count/$params['pagesize']);
        $data['list']       = $this->court_lib->get_player_lib($params);
        $this->output_json_return('success',$data);
    }
    
    /**
     * 意志列表接口
     */
    public function volition_list()
    {
        $params             = $this->public_params();
        $params['type']     = (int)$this->request_params('type');
        $params['offset']   = (int)$this->request_params('offset');
        $params['pagesize'] = (int)$this->request_params('pagesize');
        if ($params['type'] == '' || $params['offset'] === '') {
            log_message('error', 'params_err：'.$this->court_lib->ip.',',http_build_query($_REQUEST));
            $this->output_json_return('params_err');
        }
        if (!in_array($params['type'], array(1,2,3))) {
            log_message('error', 'volition_type_err：'.$this->court_lib->ip.',',http_build_query($_REQUEST));
            $this->output_json_return('volition_type_err');
        }
        $this->utility->check_sign($params, $params['sign']);
        if (!$params['pagesize']) {
            $params['pagesize'] = parent::PAGESIZE;
        }
        $total_count    = $this->court_lib->get_total_count(array('status'=>1), 'volition_conf');
        if (!$total_count) {
            log_message('info', 'empty_data:'.$this->court_lib->ip.',未查询到意志列表数据');
            $this->output_json_return('empty_data');
        }
        $data['pagecount']  = ceil($total_count/$params['pagesize']);
        
        $data['list']       = $this->court_lib->volition_list($params);
        $this->output_json_return('success', $data);
    }
    
    /**
     * 意志组合列表
     */
    public function group_list()
    {
        $params             = $this->public_params();
        $params['offset']   = (int)$this->request_params('offset');
        $params['pagesize'] = $this->request_params('pagesize');
        if ($params['offset'] === '') {
            log_message('error', 'params_err：'.$this->court_lib->ip.',',http_build_query($_REQUEST));
            $this->output_json_return('params_err');
        }
        $this->utility->check_sign($params, $params['sign']);
        if (!$params['pagesize']) {
            $params['pagesize'] = parent::PAGESIZE;
        }
        // 获取组合总条数
        $total_count    = $this->court_lib->get_total_count(array('status'=>1), 'group_conf');
        if (!$total_count) {
            log_message('info', 'empty_data:'.$this->court_lib->ip.',未查询到组合列表数据');
            $this->output_json_return('empty_data');
        }
        $data['pagecount']  = ceil($total_count/$params['pagesize']);
        $data['list']       = $this->court_lib->get_group_list($params);
        $this->output_json_return('success', $data);
    }
    
    /**
     * 意志详情接口
     */
    public function volition_info()
    {
        $params             = $this->public_params();
        $params['id']       = (int)$this->request_params('id');
        if ($params['id'] === '') {
            log_message('error', 'params_err：'.$this->court_lib->ip.',',http_build_query($_REQUEST));
            $this->output_json_return('params_err');
        }
        $this->utility->check_sign($params, $params['sign']);
        
        $data   = $this->court_lib->get_volition_info($params);
        $this->output_json_return('success', $data);
    }
    
    /**
     * 组合详情
     */
    public function group_info()
    {
        $params             = $this->public_params();
        $params['id']   = (int)$this->request_params('id');
        if ($params['id'] === '') {
            log_message('error', 'params_err：'.$this->court_lib->ip.',',http_build_query($_REQUEST));
            $this->output_json_return('params_err');
        }
        $this->utility->check_sign($params, $params['sign']);
        
        $data   = $this->court_lib->get_group_info($params);
        $this->output_json_return('success', $data);
    }
    
    /**
     * 插入卡牌（意志）
     */
    public function insert_player()
    {
        $params                 = $this->public_params();
        $params['id']           = (int)$this->request_params('id');
        $params['player_id']    = (int)$this->request_params('player_id');
        if ($params['id'] == '' || $params['player_id'] == '') {
            log_message('error', 'params_err：'.$this->court_lib->ip.',',http_build_query($_REQUEST));
            $this->output_json_return('params_err');
        }
        $this->utility->check_sign($params, $params['sign']);
        
        $res = $this->court_lib->do_insert_player($params);
        $this->output_json_return('success');
    }
    
    /**
     * 激活组合
     */
    public function active_group()
    {
        $params         = $this->public_params();
        $params['id']   = (int)$this->request_params('id');
        if ($params['id'] == '') {
            log_message('error', 'params_err：'.$this->court_lib->ip.',',http_build_query($_REQUEST));
            $this->output_json_return('params_err');
        }
        $this->utility->check_sign($params, $params['sign']);
        
        $res = $this->court_lib->group_active($params);
        $this->output_json_return('success');
    }
    
    /**
     * 道具列表
     */
    public function prop_list()
    {
        $params             = $this->public_params();
        $params['offset']   = (int)$this->request_params('offset');
        $params['pagesize'] = $this->request_params('pagesize');
        if ($params['offset'] === '') {
            log_message('error', 'params_err：'.$this->court_lib->ip.',',http_build_query($_REQUEST));
            $this->output_json_return('params_err');
        }
        $this->utility->check_sign($params, $params['sign']);
        if (!$params['pagesize']) {
            $params['pagesize'] = parent::PAGESIZE;
        }
        $where  = array('manager_idx'=>$params['uuid'],'status'=>1);
        $total_count    = $this->court_lib->get_total_count($where,'prop');
        if (!$total_count) {
            log_message('info', 'empty_data:'.$this->court_lib->ip.',未查询到道具列表数据');
            $this->output_json_return('empty_data');
        }
        $data['pagecount']  = ceil($total_count/$params['pagesize']);
        $data['list']   = $this->court_lib->get_prop_list($params);
        $this->output_json_return('success',$data);
    }
    
    /**
     * 道具信息
     */
    public function prop_info()
    {
        $params         = $this->public_params();
        $params['id']   = (int)$this->request_params('id');
        if ($params['id'] === '') {
            log_message('error', 'params_err：'.$this->court_lib->ip.',',http_build_query($_REQUEST));
            $this->output_json_return('params_err');
        }
        $this->utility->check_sign($params, $params['sign']);
        
        $data   = $this->court_lib->prop_info($params['id']);
        if (!$data) {
            log_message('info', 'empty_data:'.$this->court_lib->ip.',未查询到道具数据');
            $this->output_json_return('empty_data');
        }
        $this->output_json_return('success',$data);
    }
    
    /*
     * 魔法包获得道具详情
     */
    public function magic_pack_info()
    {
        $params         = $this->public_params();
        $params['id']   = (int)$this->request_params('id');
        if ($params['id'] === '') {
            log_message('error', 'params_err：'.$this->court_lib->ip.',',http_build_query($_REQUEST));
            $this->output_json_return('params_err');
        }
        $this->utility->check_sign($params, $params['sign']);
        
        $data['list']   = $this->court_lib->magic_pack_info($params['id']);
        if (!$data) {
            log_message('info', 'empty_data:'.$this->court_lib->ip.',未查询到道具数据');
            $this->output_json_return('empty_data');
        }
        $this->output_json_return('success',$data);
    }


    /**
     * 道具使用接口
     * 只针对 道具类型为 5体力药水6耐力药水9欧元道具10抽卡道具
     */
    public function prop_use()
    {
        $params         = $this->public_params();
        $params['id']   = (int)$this->request_params('id');
        $params['type'] = (int)$this->request_params('type');// 5体力药水6耐力药水8欧元道具
        if ($params['id'] === '' || $params['type'] == '') {
            log_message('error', 'params_err：'.$this->court_lib->ip.',',http_build_query($_REQUEST));
            $this->output_json_return('params_err');
        }
        if (!in_array($params['type'], array(5,6,8))) {
            log_message('error', 'params_err：'.$this->court_lib->ip.',',http_build_query($_REQUEST));
            $this->output_json_return('params_err');
        }
        $this->utility->check_sign($params, $params['sign']);
        
        $this->court_lib->prop_use($params);
        $this->output_json_return();
    }
    
    /**
     * 魔法包使用接口
     * 只针对 魔法包
     */
    public function magic_pack_use()
    {
        $params         = $this->public_params();
        $params['id']   = (int)$this->request_params('id');
        if ($params['id'] === '') {
            log_message('error', 'params_err：'.$this->court_lib->ip.',',http_build_query($_REQUEST));
            $this->output_json_return('params_err');
        }
        $this->utility->check_sign($params, $params['sign']);
        
        $data['list'] = $this->court_lib->magic_pack_use($params);
        $this->output_json_return('success',$data);
    }
    
    /**
     * VIP礼包使用接口
     * 只针对 VIP礼包
     */
    public function vip_pack_use()
    {
        $params         = $this->public_params();
        $params['id']   = (int)$this->request_params('id');
        if ($params['id'] === '') {
            log_message('error', 'params_err：'.$this->court_lib->ip.',',http_build_query($_REQUEST));
            $this->output_json_return('params_err');
        }
        $this->utility->check_sign($params, $params['sign']);
        $this->court_lib->vip_pack_use($params);
        $this->output_json_return('success',$data);
    }
    
    /**
     * 宝石列表
     */
    public function gem_list()
    {
        $params             = $this->public_params();
        $params['offset']   = (int)$this->request_params('offset');
        $params['pagesize'] = $this->request_params('pagesize');
        if ($params['offset'] === '') {
            log_message('error', 'params_err：'.$this->court_lib->ip.',',http_build_query($_REQUEST));
            $this->output_json_return('params_err');
        }
        $this->utility->check_sign($params, $params['sign']);
        if (!$params['pagesize']) {
            $params['pagesize'] = parent::PAGESIZE;
        }
        
        $where  = array('manager_idx'=>$params['uuid'],'is_use'=>0,'status'=>1);
        $total_count    = $this->court_lib->get_total_count($where,'gem');
        if (!$total_count) {
            log_message('info', 'empty_data:'.$this->court_lib->ip.',未查询到宝石列表数据');
            $this->output_json_return('empty_data');
        }
        $data['pagecount']  = ceil($total_count/$params['pagesize']);
        $data['list']       = $this->court_lib->get_gem_list(" manager_idx = ".$params['uuid'].' AND A.is_use = 0 AND A.status = 1 AND B.status = 1',"A.idx AS id,B.gem_no AS gem_no,B.name AS name,B.quality as quality,B.pic AS pic,B.frame AS frame,A.gem_num as num");
        $this->output_json_return('success',$data);
    }
    
    /**
     * 宝石信息
     */
    public function gem_info()
    {
        $params         = $this->public_params();
        $params['id']   = (int)$this->request_params('id');
        if ($params['id'] === '') {
            log_message('error', 'params_err：'.$this->court_lib->ip.',',http_build_query($_REQUEST));
            $this->output_json_return('params_err');
        }
        $this->utility->check_sign($params, $params['sign']);
        $data   = $this->court_lib->get_gem_info("A.idx = ".$params['id']." AND A.gem_no = B.gem_no AND A.status = 1 and B.status = 1","A.idx AS id,B.gem_no AS gem_no,B.quality as quality,B.pic AS pic,B.frame AS frame,B.descript AS descript");
        if (!$data) {
            log_message('info', 'empty_data:'.$this->court_lib->ip.',未查询到道具数据');
            $this->output_json_return('empty_data');
        }
        $this->output_json_return('success',$data);
    }
    
    /**
     * 嵌入宝石接口
     */
    public function insert_gem()
    {
        $params                 = $this->public_params();
        $params['id']           = (int)$this->request_params('id');
        $params['equipt_id']    = (int)$this->request_params('equipt_id');
        if ($params['id'] == '' || $params['equipt_id'] == '') {
            log_message('error', 'params_err：'.$this->court_lib->ip.',',http_build_query($_REQUEST));
            $this->output_json_return('params_err');
        }
        $this->utility->check_sign($params, $params['sign']);
        $this->court_lib->do_insert_gem($params);
        $this->output_json_return();
    }
    
    /**
     * 卸载宝石接口
     */
    public function delete_gem()
    {
        $params                 = $this->public_params();
        $params['id']           = (int)$this->request_params('id');
        $params['equipt_id']    = (int)$this->request_params('equipt_id');
        if ($params['id'] == '' || $params['equipt_id'] == '') {
            log_message('error', 'params_err：'.$this->court_lib->ip.',',http_build_query($_REQUEST));
            $this->output_json_return('params_err');
        }
        $this->utility->check_sign($params, $params['sign']);
        $this->court_lib->do_delete_gem($params);
        $this->output_json_return();
    }
}