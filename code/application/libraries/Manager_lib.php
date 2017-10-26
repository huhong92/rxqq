<?php
class Manager_lib extends Base_lib {
    public function __construct() {
        parent::__construct();
        $this->load_model('manager_model');
    }
    
    /**
     * 用户信息注册
     * @param array $params
     */
    public function login_for_thirdparty($params)
    {
        // 判断该用户是否已经注册过
        $where = array(
            'app_id'        => $params['app_id'],
            'user_id'       => $params['user_id'],
            'service_area'  => $params['service_area'],
        );
        $register_info = $this->get_register_info($where);
        if ($register_info['manager_idx']) { // 该用户已经注册过了
            $params['uuid'] = $register_info['manager_idx'];
            $init_name      = 0;
            $init_teamlogo  = 0;
            $init_lineup    = 0;
            // 判断经理 名称、队标、初始阵容 是否初始化了
            $options['where']   = array('manager_idx'=>$params['uuid'],'status'=>1);
            $init_list          = $this->CI->manager_model->list_data($options,'init_his');
            if ($init_list) {
                foreach ($init_list as $k=>$v) {
                    if ((int)$v['type'] === 1 && $v['init_num']) {
                        $init_name  = 1;// 修改过经理名称
                    } else if ((int)$v['type'] === 2 && $v['init_num']) {
                        $init_teamlogo  = 1; //修改队标
                    } elseif((int)$v['type'] === 3 && $v['init_num']) {
                        $init_lineup    = 1;//初始阵容
                    }
                }
            }
            //电视端二维码登录手机端
            if(($register_info['platform'] != $params['platform']) && $params['platform'] == 1){
                //是否已领过奖励
                $reward_his = $this->CI->manager_model->get_one(array('manager_idx' => $params['uuid'] , 'status' => 1), 'login_reward_his', array('manager_idx'));
                if(!$reward_his){
                    $this->CI->manager_model->start();
                    //发放球票奖励
                    $m_info = $this->CI->manager_model->get_one(array('idx' => $params['uuid'] , 'status' => 1), 'manager_info', array('tickets' , 'euro' , 'name'));
                    $update_data = array('tickets'=>$m_info['tickets'] + 50,'euro'=>$m_info['euro'] + 80000);
                    $where  = array('idx'=>$params['uuid'],'status'=>1);
                    $res    = $this->CI->manager_model->update_data($update_data , $where , 'manager_info');
                    if(!$res){
                        log_message('error', 'send_login_reward_error:'.$this->ip.',发送电视用户手机端登录奖励失败');
                        $this->CI->manager_model->error();
                        $this->CI->output_json_return('send_login_reward_error');
                    }
                    //记录球票变更
                    $tk_ins_data = array(
                        'manager_idx'     => $params['uuid'],
                        'manager_name'    => $m_info['name'],
                        'type'            => 2,
                        'tickets_changer' => 50,
                        'tickets_total'   => $update_data['tickets'],
                        'status'          => 1
                    );
                    $tk_his_id = $this->CI->manager_model->insert_data($tk_ins_data, 'tickets_change_his');
                    if(!$tk_his_id){
                        log_message('error', 'send_login_reward_error:'.$this->ip.',发送电视用户手机端登录奖励失败');
                        $this->CI->manager_model->error();
                        $this->CI->output_json_return('send_login_reward_error');
                    }
                    //记录欧元变更
                    $eu_ins_data = array(
                        'manager_idx'     => $params['uuid'],
                        'manager_name'    => $m_info['name'],
                        'type'            => 4,
                        'change'          => 80000,
                        'total'           => $update_data['euro'],
                        'info'            => '电视用户手机端登录奖励',
                        'status'          => 1
                    );
                    $eu_his_id = $this->CI->manager_model->insert_data($eu_ins_data, 'euro_change_his');
                    if(!$eu_his_id){
                        log_message('error', 'send_login_reward_error:'.$this->ip.',发送电视用户手机端登录奖励失败');
                        $this->CI->manager_model->error();
                        $this->CI->output_json_return('send_login_reward_error');
                    }
                    //发送道具奖励
                    $p_res = $this->CI->utility->insert_prop_info(array('uuid' => $params['uuid'] , 'prop_no' => 501) , 1);
                    if(!$p_res){
                        log_message('error', 'send_login_reward_error:'.$this->ip.',发送电视用户手机端登录奖励失败');
                        $this->CI->manager_model->error();
                        $this->CI->output_json_return('send_login_reward_error');
                    }
                    $p_res = $this->CI->utility->insert_prop_info(array('uuid' => $params['uuid'] , 'prop_no' => 601) , 1);
                    if(!$p_res){
                        log_message('error', 'send_login_reward_error:'.$this->ip.',发送电视用户手机端登录奖励失败');
                        $this->CI->manager_model->error();
                        $this->CI->output_json_return('send_login_reward_error');
                    }
                    //记录已领取奖励
                    $l_r_his = array(
                        'manager_idx' => $params['uuid'],
                        'status'      => 1
                    );
                    $l_r_id = $this->CI->manager_model->insert_data($l_r_his, 'login_reward_his');
                    if(!$l_r_id){
                        log_message('error', 'login_reward_his_error:'.$this->ip.',记录发送电视用户手机端登录奖励失败');
                        $this->CI->manager_model->error();
                        $this->CI->output_json_return('login_reward_his_error');
                    }
                    $this->CI->manager_model->success();
                }
            }
            
            // 记录登录日志
            $this->CI->manager_model->start();
            $login_res = $this->login($params);
            if (!$login_res) {
                log_message('error', 'record_login_history_error:'.$this->ip.',登录日志记录失败');
                $this->CI->manager_model->error();
                $this->CI->output_json_return('register_err');
            }
            $this->CI->manager_model->success();
            return array('uuid'=>$params['uuid'], 'init_name'=>$init_name,'init_teamlogo'=>$init_teamlogo,'init_lineup'=>$init_lineup);
        }
        // 开启事务
        $this->CI->manager_model->start();
        // 该用户未注册
        $data = array(
            'name'          => $params['user_name'],
            'team_logo'     => 'T0001',
            'vip'           => 0,
            'level'         => 1,
            'current_exp'   => 0,
            'upgrade_exp'   => 302,// lv1默认所需的经验
            'total_exp'     => 0,// 上一级总经验
            'physical_strenth' => 56,
            'endurance'     => 50,
            'train_num'     => 0,
            'trainpoint'    => 0,
            'tickets'       => 0,
            'euro'          => 0,
            'soccer_soul'   => 0,
            'honor'         => 0,
            'achievement'   => 0,
            'talent'        => 0,
            'service_area'  => $params['service_area'],
            'status'        => 1,
            'time'          => time(),
            'update_time'   => time(),
        );
        $_uuid = $this->CI->manager_model->insert_data($data, 'manager_info');
        if (!$_uuid) {
            log_message('error', 'manager_info_insert_error:'.$this->ip.',经理信息表插入失败');
            $this->CI->manager_model->error();
            $this->CI->output_json_return('register_err');
        }
        
        // 插入注册表
        $reg_data = array(
            'manager_idx'   => $_uuid,
            'app_id'        => $params['app_id'],
            'user_id'       => $params['user_id'],
            'user_name'     => $params['user_name'],
            'service_area'  => $params['service_area'],
            'platform'      => $params['platform'],
            'status'        => 1,
            'time'          => time(),
            'update_time'   => time(),
        );

        $res = $this->CI->manager_model->insert_data($reg_data, 'register');
        if (!$res) {
            log_message('error', 'register_insert_error:'.$this->ip.',注册表信息插入失败');
            $this->CI->manager_model->error();
            $this->CI->output_json_return('register_err');
        }
        // 初始化阵型
        $data_2 = array(
            'manager_idx'       => $_uuid,
            'structure_no'      => 1,
            'add_obj'           => 3,
            'attradd_percent'   => 5,
            'is_use'            => 3,
            'status'            => 1,
        );
        $res = $this->CI->manager_model->insert_data($data_2, 'structure');
        if (!$res) {
            log_message('error', 'insert_structure_error:'.$this->ip.',阵型初始信息插入失败');
            $this->CI->manager_model->error();
            $this->CI->output_json_return('insert_structure_error');
        }
        
        // 记录登录日志
        $params['uuid'] = $_uuid;
        $login_res = $this->login($params);
        if (!$login_res) {
            log_message('error', 'record_login_history_error:'.$this->ip.',登录日志记录失败');
            $this->CI->manager_model->error();
            $this->CI->output_json_return('register_err');
        }
        $this->CI->manager_model->success();
        return array('uuid'=>$_uuid, 'init_name'=>0,'init_teamlogo'=>0,'init_lineup'=>0);
    }
    
    /*
     * 记录登录日志
     */
    public function login($params)
    {
        if (!$params['name']) {
            $params['name'] = 'user_name';
        }
        $data = array(
            'manager_idx'   => $params['uuid'],
            'manager_name'  => $params['name'],
            'ip'            => $this->ip,
            'platform'      => $params['platform'],
            'status'        => 1,
            'time'          => time(),
            'update_time'   => time(),
        );
        $ist_id = $this->CI->manager_model->insert_data($data, 'login');
        if (!$ist_id) {
            log_message('error', 'insert_login_fail:登录表数据插入失败');
            $this->CI->output_json_return('login_err');
        }
        return true;
    }
    
    /*
     * 刷新页面红点状态
     */
    public function refresh_tips($uuid)
    {
        $this->load_library('tips_lib');
        $return = $this->CI->tips_lib->refresh_tips($uuid);
        return $return;
    }
    
    /**
     * 获取经理信息【包括战斗力】
     */
    public function get_manager_detail($uuid)
    {
        $where  = array('idx' => $uuid, 'status' => 1);
        $fields = array(
            'idx as uuid','name','team_logo','vip','level','current_exp','upgrade_exp','total_exp','physical_strenth','endurance',
            'trainpoint','tickets','euro','soccer_soul','honor','powder','achievement','talent','service_area','time as create_time'
        );
        $m_info = $this->CI->manager_model->get_one($where, 'manager_info', $fields);
        $m_info['fighting']   = $this->m_fighting($uuid)['finghting'];
        // 获取经理总耐力，总体力
        $m_info['endurance_total']  = 50;
        $phy    = ($m_info['level'] - 1)*2 +56;
        $m_info['phystrenth_total'] = $phy>100?100:$phy;
        $m_info['current_exp']      = $m_info['current_exp'];
        return $m_info;
    }
    
    /**
     * 获取经理基本信息
     * @param type $where
     * @param string $select 需要获取经理表某个字段
     * @return type
     */
    public function get_manager_info($uuid)
    {
        $where  = array('idx' => $uuid, 'status' => 1);
        $fields = array(
            'idx as uuid','name','team_logo','vip','level','current_exp','upgrade_exp','total_exp','physical_strenth','endurance',
            'trainpoint','tickets','euro','soccer_soul','honor','powder','achievement','talent','service_area','time as create_time'
        );
        $m_info = $this->CI->manager_model->get_one($where, 'manager_info', $fields);
        // 获取经理总耐力，总体力
        $m_info['endurance_total']  = 50;
        $phy    = ($m_info['level'] - 1)*2 +56;
        $m_info['phystrenth_total'] = $phy>100?100:$phy;
        $m_info['current_exp']      = $m_info['current_exp'];
        return $m_info;
    }
    
    /**
     * 经理的战斗力值
     * 球员表17个属性值（球员属性之和 + 装备 + 阵型 + 宝石 + 意志 + 天赋)
     * @param type $uuid
     * @param int $type 2=>天梯阵容战斗力 1普通阵容战斗力
     * @return type
     */
    public function m_fighting($uuid,$type = 1)
    {
        $this->CI->load->library('match_lib');
        // 获取上阵球员列表
        if ($type == 2) {
            $options['where']   = "manager_idx=".$uuid." AND (is_use = 2 or is_use = 3) AND status = 1";
            $options['fields']  = "idx id,level, position_no2 AS position_no,
            generalskill_no,generalskill_level,exclusiveskill_no,
            is_use,fatigue,speed,shoot, free_kick,
            acceleration,header,control,physical_ability,
            power,aggressive,interfere,steals,ball_control,
            pass_ball,mind,reaction,positional_sense,hand_ball";
            $options['order']   = "position_no2 ASC";
        } else {
            $options['where']   = "manager_idx=".$uuid." AND (is_use = 1 or is_use = 3) AND status = 1";
            $options['fields']  = "idx as id,player_no,level,position_no,level,
            generalskill_no,generalskill_level,exclusiveskill_no,
            is_use,fatigue,speed,shoot, free_kick,
            acceleration,header,control,physical_ability,
            power,aggressive,interfere,steals,ball_control,
            pass_ball,mind,reaction,positional_sense,hand_ball";
            $options['order']   = "position_no ASC";
        }
        $options['limit']   = array('page'=>0,'size'=>7);
        $player_list        = $this->CI->manager_model->list_data($options,'player_info');
        if (empty($player_list)) {
            return array('finghting'=>0,'player_list'=>array());
        }
        foreach ($player_list as $k=>$v) {
            $player_info= $this->CI->match_lib->attr_statis_by_playid($v, $uuid)['attribute'];
            $sum += array_sum($player_info);
        }
        
        return array('finghting'=>$sum,'player_list'=>$player_list);
    }
    
    /**
     * 获取注册表数据
     * @param type $where
     * @return type
     */
    public function get_register_info($where)
    {
        $register_info = $this->CI->manager_model->get_one($where, 'register');
        return $register_info;
    }
    
    /**
     * 更新经理基础信息
     * @param type $where
     * @param type $fields
     * @return type
     */
    public function update_manager_info($fields, $where)
    {
        $m_info     = $this->get_manager_info($where['idx']);
        $m_level    = $m_info['level'];
        if (!$m_info) {
            return false;
        }
        // 经理level升级
        if ($fields['level'] && $fields['level'] > $m_info['level']) {
            $res = $this->m_level_do($where['idx'], $fields['level']);// 查看是否解锁训练位
            if (!$res) {
                return false;
            }
            if($fields['level']%5 == false) {// 查看是否增加天赋点数
                $fields['talent']   = $m_info['talent']+1;
                //触发经理页面红点提示
                $this->load_library('tips_lib');
                $this->CI->tips_lib->tip_pages($m_info['uuid'],1004);
                $this->CI->tips_lib->tip_pages($m_info['uuid'],1010);
            }
            // 经理每升一级增加10点体力
            $add_phy    = $this->CI->passport->get('update_level_add_phy');
            if ($fields['physical_strenth']) {
                $fields['physical_strenth'] = $fields['physical_strenth']+($fields['level']-$m_level)*$add_phy;
            } else {
                $fields['physical_strenth'] = $m_info['physical_strenth']+($fields['level']-$m_level)*$add_phy;
            }
            // 触发新手引导 12 - 经理等级首次到达3level
            if ($fields['level'] == 3) {
                $this->CI->task_lib->unlock_structure_2($m_info['uuid']);
            }
        }
        $res = $this->CI->manager_model->update_data($fields, $where, 'manager_info');
        if (!$res) {
            return false;
        }
        // 触发成就
        $this->load_library('task_lib');
        $this->CI->task_lib->achieve_mupgrade($m_info['uuid'],$fields['level']);
        //触发新手引导 - 13 14 15 18
        $this->CI->task_lib->level_up($m_info['uuid'],$fields['level']);
        return $res;
    }
    
    /**
     * 获取经理队标列表
     */
    public function teamlogo_info()
    {
        $options['where']   = array('status' => 1);
        $options['fields']  = "pic as team_logo";
        $teamlogo_list      = $this->CI->manager_model->list_data($options, 'teamlogo_conf');
        $list = array_rand($teamlogo_list, 6);
        foreach ($list as $key=>$val) {
            $tl_info[]  = $teamlogo_list[$val]['team_logo'];
        }
        if (!$tl_info) {
            log_message('error', 'team_logo_empty:'.$this->ip.',经理队标库为空');
            $this->CI->output_json_return('team_logo_empty_data');
        }
        return $tl_info;
    }
    
    /**
     * 初始化队标、经理名信息
     * @param type $params
     */
    public function update_m_info($params)
    {
        // 更新经理队标|名称
        $this->CI->manager_model->start();
        $where                  = array("idx"=> $params['uuid'],"status"=>1);
        $fields['name']         = $params['name'];
        $fields["team_logo"]    = $params["team_logo"];
        $res = $this->update_manager_info($fields, $where);
        if (!$res) {
            $this->CI->manager_model->error();
            $this->CI->output_json_return('init_m_info_err');
        }
        
        // 记录队标初级记录
        $data = array(
            array( 'manager_idx'  => $params['uuid'],'type'=> 1, 'init_num'=> 1, 'status'=> 1, ),
            array( 'manager_idx' => $params['uuid'],'type'=> 2, 'init_num'=> 1, 'status'=> 1)
            );   
        $res = $this->insert_init_his($data);
        if (!$res) {
            $this->CI->manager_model->error();
            $this->CI->output_json_return('init_m_info_err');
        }
        $this->CI->manager_model->success();
        return true;
    }
    
    /**
     * 更新队标接口
     * @param type $params
     */
    public function update_teamlogo($params)
    {
        // 更新经理名称
        $this->CI->manager_model->start();
        $where                  = array("idx"=> $params['uuid'],"status"=>1);
        $fields['team_logo']    = $params['team_logo'];
        $res = $this->update_manager_info($fields, $where);
        if (!$res) {
            $this->CI->manager_model->error();
            $this->CI->output_json_return('update_teamlogo_error');
        }
        // 修改队标，插入历史记录
        $fields   = array('init_num'=>2);
        $where    = array('manager_idx'=>$params['uuid'],'type'=>2,'status'=>1);
        $res = $this->update_init_his($fields,$where,'init_his');
        if (!$res) {
            $this->CI->manager_model->error();
            $this->CI->output_json_return('update_teamlogo_error');
        }
        $this->CI->manager_model->success();
        return true;
    }
    
    /**
     * 检查队标，是否更新过
     * @param type $params
     */
    public function check_init($params, $type = 2)
    {
        $where  = array('manager_idx'=>$params['uuid'],'type'=>$type, 'status'=>1);
        $info = $this->CI->manager_model->get_one($where,'init_his','init_num');
        return $info;
    }
    
    /**
     * 插入初始信息
     */
    public function insert_init_his($data)
    {
        $res = $this->CI->manager_model->insert_batch($data,'init_his');
        return $res;
    }
    
    /**
     * 更新初始化信息
     * @param type $data
     */
    public function update_init_his($fields, $where)
    {
        $res = $this->CI->manager_model->update_data($fields, $where,'init_his');
        return $res;
    }
    
    /**
     * 初始阵型时，获取初始球员卡列表
     * @return type
     */
    public function lineup_list($params)
    {
        $options['where']   = array('type'=>1, 'status'=>1);
        $options['fields']  = "idx AS id,player_no,name,quality,position_type";
        $list               = $this->CI->manager_model->list_data($options, 'init_lineup');
        if (!$list) {
            log_message('error', 'get_lineup_list_err:'.$this->ip.',获取初始球员卡列表失败');
            $this->CI->output_json_return('lineup_list_empty');
        }
        return $list;
    }
    
    /**
     * 初始化阵容
     */
    public function lineup($params)
    {
        // 判断经理是否已经初始化阵容
        $res = $this->check_init($params, 3);
        if ($res['init_num']) {
            log_message('error', 'lineup_init_exists:'.$this->ip.',该经理阵容已经初始化过');
            $this->CI->output_json_return('lineup_init_exists');
        }
        // 玩家手工选择一名球员卡
        $where          = array('idx'=>$params['id'],'type'=>1,'status'=>1);
        $lineup_info    = $this->CI->manager_model->get_one($where, 'init_lineup');
        
        // 获取随机库
        $options['where']   = array('type'=>2,'status'=>1);
        $lineup_list        = $this->CI->manager_model->list_data($options,'init_lineup');
        foreach ($lineup_list as $k=>$v) {
            if ((int)$v['position_type'] === 0) {// 守门
                $keep_goal[]    =   $v;
            }elseif ((int)$v['position_type'] === 3) {// 后卫
                $guard[]        =   $v;
            }elseif ((int)$v['position_type'] === 2) {// 中场
                $midfield[]     =   $v;
            }elseif ((int)$v['position_type'] === 1) {// 前锋
                $forward[]      =   $v;
            }
        }
        // 随机生成6个球员卡(3-2-1) 3后卫2中场1前锋
        if ((int)$lineup_info['position_type'] == 0) {// 守门
            $choice = 0;// 经理选择守门
            
            // 随机出3后卫2中场1前锋
            $guard_1    = mt_rand(0,count($guard)-1);// 选中的后卫球员卡
            $guard_2    = mt_rand(0,count($guard)-1);
            $guard_3    = mt_rand(0,count($guard)-1);
            while($guard_1==$guard_2) {
                $guard_2    = mt_rand(0,count($guard)-1);
            }
            while($guard_1==$guard_3) {
                $guard_3    = mt_rand(0,count($guard)-1);
            }
            $guard[$guard_1]['position_no'] = 1;
            $guard[$guard_2]['position_no'] = 2;
            $guard[$guard_3]['position_no'] = 3;
            
            $rand_lineup[]  = $guard[$guard_1];
            $rand_lineup[]  = $guard[$guard_2];
            $rand_lineup[]  = $guard[$guard_3];
            
            // 选中的2中场球员卡
            $midfield_1 =  mt_rand(0,count($midfield)-1);
            $midfield_2 =  mt_rand(0,count($midfield)-1);
            while($midfield_1==$midfield_2) {
                $midfield_2 = mt_rand(0,count($midfield)-1);
            }
            $midfield[$midfield_1]['position_no'] = 4;
            $midfield[$midfield_2]['position_no'] = 5;
            
            $rand_lineup[]  = $midfield[$midfield_1];
            $rand_lineup[]  = $midfield[$midfield_2];
            
            // 选中1前锋球员卡
            $forward_1 =  mt_rand(0,count($forward)-1);
            $forward[$forward_1]['position_no'] = 6;
            $rand_lineup[]  = $forward[$forward_1];
        }elseif ((int)$lineup_info['position_type'] == 3) {// 后卫
            $choice = 1;// 经理选择后卫
            // 随机出1守门2后卫2中场1前锋
            // 一张守门
            $keep_goal_1    = mt_rand(0,count($keep_goal)-1);// 守门球员卡
            $keep_goal[$keep_goal_1]['position_no'] = 0;
            $rand_lineup[]  = $keep_goal[$keep_goal_1];
            // 2张后卫
            $guard_1    = mt_rand(0,count($guard)-1);// 选中的后卫球员卡
            $guard_2    = mt_rand(0,count($guard)-1);
            $guard[$guard_1]['position_no'] = 2;
            $guard[$guard_2]['position_no'] = 3;
            while($guard_1==$guard_2) {
                $guard_2 = mt_rand(0,count($guard)-1);
            }
            $rand_lineup[]  = $guard[$guard_1];
            $rand_lineup[]  = $guard[$guard_2];
            // 2张中场球员卡
            $midfield_1 =  mt_rand(0,count($midfield)-1);
            $midfield_2 =  mt_rand(0,count($midfield)-1);
            while($midfield_1==$midfield_2) {
                $midfield_2 = mt_rand(0,count($midfield)-1);
            }
            $midfield[$midfield_1]['position_no'] = 4;
            $midfield[$midfield_2]['position_no'] = 5;
            $rand_lineup[]  = $midfield[$midfield_1];
            $rand_lineup[]  = $midfield[$midfield_2];
            // 选中1前锋球员卡
            $forward_1 =  mt_rand(0,count($forward)-1);
            $forward[$forward_1]['position_no'] = 6;
            $rand_lineup[]  = $forward[$forward_1];
        }elseif ((int)$lineup_info['position_type'] == 2) {// 中场
            $choice = 4;// 经理选择中场
            // 随机出1守门3后卫1中场1前锋
            // 一张守门
            $keep_goal_1    = mt_rand(0,count($keep_goal)-1);// 选中的守门球员卡
            $keep_goal[$keep_goal_1]['position_no'] = 0;
            $rand_lineup[]  = $keep_goal[$keep_goal_1];
            // 3张后卫
            $guard_1    = mt_rand(0,count($guard)-1);// 选中的后卫球员卡
            $guard_2    = mt_rand(0,count($guard)-1);
            $guard_3    = mt_rand(0,count($guard)-1);
            while($guard_1==$guard_2) {
                $guard_2    = mt_rand(0,count($guard)-1);
            }
            while($guard_1==$guard_3) {
                $guard_3    = mt_rand(0,count($guard)-1);
            }
            $guard[$guard_1]['position_no'] = 1;
            $guard[$guard_2]['position_no'] = 2;
            $guard[$guard_3]['position_no'] = 3;
            $rand_lineup[]  = $guard[$guard_1];
            $rand_lineup[]  = $guard[$guard_2];
            $rand_lineup[]  = $guard[$guard_3];
            // 1张中场球员卡
            $midfield_1     =  mt_rand(0,count($midfield)-1);
            $midfield[$midfield_1]['position_no'] = 5;
            $rand_lineup[]  = $midfield[$midfield_1];
            // 1张前锋球员卡
            $forward_1 =  mt_rand(0,count($forward)-1);
            $forward[$forward_1]['position_no'] = 6;
            $rand_lineup[]  = $forward[$forward_1];
        }elseif ((int)$lineup_info['position_type'] == 1) {// 前锋
            $choice = 6;// 经理选择前锋
            // 随机出1守门3张后卫2张中场
            // 一张守门
            $keep_goal_1    = mt_rand(0,count($keep_goal)-1);// 选中的守门球员卡
            $keep_goal[$keep_goal_1]['position_no'] = 0;
            $rand_lineup[]  = $keep_goal[$keep_goal_1];
            // 3张后卫
            $guard_1    = mt_rand(0,count($guard)-1);// 选中的后卫球员卡
            $guard_2    = mt_rand(0,count($guard)-1);
            $guard_3    = mt_rand(0,count($guard)-1);
            while($guard_1==$guard_2) {
                $guard_2    = mt_rand(0,count($guard)-1);
            }
            while($guard_1==$guard_3) {
                $guard_3    = mt_rand(0,count($guard)-1);
            }
            $guard[$guard_1]['position_no'] = 1;
            $guard[$guard_2]['position_no'] = 2;
            $guard[$guard_3]['position_no'] = 3;
            $rand_lineup[]  = $guard[$guard_1];
            $rand_lineup[]  = $guard[$guard_2];
            $rand_lineup[]  = $guard[$guard_3];
            // 2张中场球员卡
            $midfield_1 =  mt_rand(0,count($midfield)-1);
            $midfield_2 =  mt_rand(0,count($midfield)-1);
            while($midfield_1==$midfield_2) {
                $midfield_2 = mt_rand(0,count($midfield)-1);
            }
            $midfield[$midfield_1]['position_no'] = 4;
            $midfield[$midfield_2]['position_no'] = 5;
            $rand_lineup[]  = $midfield[$midfield_1];
            $rand_lineup[]  = $midfield[$midfield_2];
        }
        
        // 根据经理选择，而随机出来的6名球员
        $this->CI->manager_model->start();
        foreach ($rand_lineup as $k=>$v) {
            $player_lib = $this->CI->manager_model->get_one(array('player_no'=>$v['player_no'],'status'=>1),'player_lib');
            $data1[]   = array(
                'manager_idx'           => $params['uuid'],
                'manager_name'          => '',
                'plib_idx'              => $player_lib['idx'],
                'player_no'             => $v['player_no'],
                'level'                 => 0,
                'generalskill_no'       => $player_lib['generalskill_no'],
                'generalskill_level'    => $player_lib['generalskill_no']?1:0,
                'exclusiveskill_no'     => $player_lib['exclusiveskill_no'],
                'is_use'                => 3,
                'position_no'           => $v['position_no'],
                'position_no2'          => $v['position_no'],
                'cposition_type'        => $player_lib['position_type'],
                'reduce_value'          => 0,
                'fatigue'               => 0,
                'speed'                 => $player_lib['speed'],
                'shoot'                 => $player_lib['shoot'],
                'free_kick'             => $player_lib['free_kick'],
                'acceleration'          => $player_lib['acceleration'],
                'header'                => $player_lib['header'],
                'control'               => $player_lib['control'],
                'physical_ability'      => $player_lib['physical_ability'],
                'power'                 => $player_lib['power'],
                'aggressive'            => $player_lib['aggressive'],
                'interfere'             => $player_lib['interfere'],
                'steals'                => $player_lib['steals'],
                'ball_control'          => $player_lib['ball_control'],
                'pass_ball'             => $player_lib['pass_ball'],
                'mind'                  => $player_lib['mind'],
                'reaction'              => $player_lib['reaction'],
                'positional_sense'      => $player_lib['positional_sense'],
                'hand_ball'             => $player_lib['hand_ball'],
                'status'                => 1,
            );
        }
        // 经理选择的1名球员
        $player_lib = $this->CI->manager_model->get_one(array('player_no'=>$lineup_info['player_no'],'status'=>1),'player_lib');
        $data1[]   = array(
            'manager_idx'           => $params['uuid'],
            'manager_name'          => '',
            'plib_idx'              => $player_lib['idx'],
            'player_no'             => $player_lib['player_no'],
            'level'                 => 0,
            'generalskill_no'       => $player_lib['generalskill_no'],
            'generalskill_level'    => $player_lib['generalskill_no']?1:0,
            'exclusiveskill_no'     => $player_lib['exclusiveskill_no'],
            'is_use'                => 3,
            'position_no2'          => $choice,
            'position_no'           => $choice,
            'cposition_type'        => $player_lib['position_type'],
            'reduce_value'          => 0,
            'fatigue'               => 0,
            'speed'                 => $player_lib['speed'],
            'shoot'                 => $player_lib['shoot'],
            'free_kick'             => $player_lib['free_kick'],
            'acceleration'          => $player_lib['acceleration'],
            'header'                => $player_lib['header'],
            'control'               => $player_lib['control'],
            'physical_ability'      => $player_lib['physical_ability'],
            'power'                 => $player_lib['power'],
            'aggressive'            => $player_lib['aggressive'],
            'interfere'             => $player_lib['interfere'],
            'steals'                => $player_lib['steals'],
            'ball_control'          => $player_lib['ball_control'],
            'pass_ball'             => $player_lib['pass_ball'],
            'mind'                  => $player_lib['mind'],
            'reaction'              => $player_lib['reaction'],
            'positional_sense'      => $player_lib['positional_sense'],
            'hand_ball'             => $player_lib['hand_ball'],
            'status'                => 1,
        );
        // 将球员列表插入到球员表中
        $res    = $this->CI->manager_model->insert_batch($data1,'player_info');
        if (!$res) {
            log_message('error', 'lineup_fomat_err:'.$this->ip.',lineup_fomat_err:初始化阵容失败');
            $this->CI->manager_model->error();
            $this->CI->output_json_return('lineup_fomat_err');
        }
        
        // 插入阵容初始化历史记录
        $data[]   = array( 'manager_idx'  => $params['uuid'],'type'=> 3, 'init_num'=> 1, 'status'=> 1);
        $res    = $this->insert_init_his($data);
        if (!$res) {
            log_message('error', 'init_lineup_err:'.$this->ip.',初始化阵容历史记录插入失败');
            $this->CI->manager_model->error();
            $this->CI->output_json_return('init_lineup_err');
        }
        //触发任务 初始化阵容
        $this->CI->utility->get_task_status($params['uuid'], 'create_team');
        $this->CI->manager_model->success();
        return true;
    }
    
    /**
     * 判断经理经验值，属于什么level
     */
    public function exp_belongto_level($exp)
    {
        $sql    ="SELECT idx as id,level,experience,total_exp,extotal_exp,physical_strength FROM mupgrade_conf WHERE total_exp >".$exp." AND status=1 ORDER BY level";
        $info   = $this->CI->manager_model->fetch($sql,'row');
        return $info;
    }
    
    /**
     * 获取经理vip信息
     * @param type $params
     * @return type
     */
    public function mvip_info($params)
    {
        $vip    = $params['vip'];
        // 当前VIP特权
        $where  = array('level'=>$vip,'status'=>1);
        $fields = "rmb,prop,euro_draw,physical_strength,endurance,elite_sweep,fiveleague_reset,trainground,banknote_print,descript";
        $curr_vip   = $this->CI->manager_model->get_one($where,'vip_conf', $fields);
        // 获得道具信息
        $data['get_prop']   = array();
        if ($curr_vip['prop']) {
            $prop_info  = explode("|", trim($curr_vip['prop'],"|"));
            foreach ($prop_info as $k=>$v) {
                $arr = explode(":",$v);
                $data['get_prop'][$k]   = array('prop_no'=>$arr[0],'num'=>$arr[1]);
            }
        }
        
        // 获取当前VIP等级特权
        $data['privilege']  = array();
        $vip_privilege  = $this->CI->passport->get('vip_privilege');
        if ($curr_vip['physical_strength']) {
            $result = str_replace("[value]", $curr_vip['physical_strength'], $vip_privilege['buy_phystrength']);
            $data['privilege'][] = $result;
        }
        if ($curr_vip['endurance']) {
            $result = str_replace("[value]", $curr_vip['endurance'], $vip_privilege['buy_endurance']);
            $data['privilege'][] = $result;
        }
        if ($curr_vip['elite_sweep']) {
            $result = str_replace("[value]", $curr_vip['elite_sweep'], $vip_privilege['sweep_num']);
            $data['privilege'][] = $result;
        }
        if ($curr_vip['fiveleague_reset']) {
            $result = str_replace("[value]", $curr_vip['fiveleague_reset'], $vip_privilege['fivereset_num']);
            $data['privilege'][] = $result;
        }
        if ($curr_vip['trainground']) {
            $result = str_replace("[value]", $curr_vip['trainground'], $vip_privilege['buy_traninground']);
            $data['privilege'][] = $result;
        }
        if ($curr_vip['banknote_print']) {
            $result = str_replace("[value]", $curr_vip['banknote_print'], $vip_privilege['eurodraw_num']);
            $data['privilege'][] = $result;
        }
        // 获取经理已经充值的球票数
        $sql                = "SELECT SUM(tickets) AS tickets FROM recharge_his WHERE manager_idx=".$params['uuid']." AND status = 1 GROUP BY manager_idx";
        $recharge_tickets   = $this->CI->manager_model->fetch($sql, 'row');
        $data['tickets']    = $recharge_tickets['tickets']?$recharge_tickets['tickets']:0;
        // 下级VIP
        if ($vip <= 11) {
            $where  = array('level'=>$vip+1,'status'=>1);
            $data['level_next']     = $vip+1;
        } else {
            $where  = array('level'=>12,'status'=>1);
            $data['level_next']     = $vip;
        }
        
        $next_vip   = $this->CI->manager_model->get_one($where,'vip_conf', "rmb");
        $rmb_rate   = $this->CI->passport->get('rmb_rate');
        $data['tickets_total']  = ($next_vip['rmb']/$rmb_rate);// 累计充值人民币（分）:球票 1元=10球票
        $data['level']          = $vip;
        
        $data['package']    = array();
        // 当前VIP礼包信息
        $condition      = "A.level = ".$vip." AND A.status = 1";
        $join_condition = "A.level = B.level AND B.manager_idx = ".$params['uuid']." AND B.status= 1";
        $select         = "A.idx AS id,A.player AS player,A.equipt AS equipt,A.prop AS prop,A.gem AS gem,IF(B.idx,1,0) AS buy";
        $tb_a           = "vippackage_conf AS A";
        $tb_b           = "vippackage_his AS B";
        $package_info   = $this->CI->manager_model->left_join($condition, $join_condition, $select, $tb_a, $tb_b);
        if ($package_info) {
            if ($package_info['player']) {
                $player = explode("|", trim($package_info['player'],"|"));
                foreach ($player as $k=>$v) {
                    $arr    = explode(":", $v);
                    $info[] = array('type'=>1,'no'=>$arr[0],'num'=>$arr[1],'level'=>$arr[2]);
                }
            }
            if ($package_info['equipt']) {
                $player = explode("|", trim($package_info['equipt'],"|"));
                foreach ($player as $k=>$v) {
                    $arr    = explode(":", $v);
                    $info[] = array('type'=>2,'no'=>$arr[0],'num'=>$arr[1],'level'=>$arr[2]);
                }
            }
            if ($package_info['prop']) {
                $player = explode("|", trim($package_info['prop'],"|"));
                foreach ($player as $k=>$v) {
                    $arr    = explode(":", $v);
                    $info[] = array('type'=>3,'no'=>$arr[0],'num'=>$arr[1],'level'=>0);
                }
            }
            if ($package_info['gem']) {
                $player = explode("|", trim($package_info['gem'],"|"));
                foreach ($player as $k=>$v) {
                    $arr    = explode(":", $v);
                    $info[] = array('type'=>4,'no'=>$arr[0],'num'=>$arr[1],'level'=>0);
                }
            }
            // 查询vip礼包在商品表的id
            $where_2    = array('goods_type'=>11,'goods_idx'=>$package_info['id'],'is_online'=>1,'status'=>1);
            $fields_2   = "idx as id,currency_type,sale_price,price";
            $goods_info = $this->CI->manager_model->get_one($where_2,'goods_conf',$fields_2);
            if (!$goods_info) {
                log_message('error', 'vip_package_empty_err:'.$this->ip.',暂无该VIP礼包');
                $this->CI->output_json_return('vip_package_empty_err');
            }
            $data['package']    = array(
                    'id'            =>$goods_info['id'],
                    'currency_type' =>$goods_info['currency_type'],
                    'sale_price'    =>$goods_info['sale_price'],
                    'price'         =>$goods_info['price'],
                    'status'        => $package_info['buy'],
                    'info'          => $info
            );
        }
        return $data;
    }
    
    /**
     * 经理升级后，训练位解锁，天赋值增加
     */
    public function m_level_do($uuid,$level)
    {
        // 经理升级level ---影响训练位
        if ($level == 2 || $level == 10) {
            $tg_no=$level == 2?1:2;
            $train_info = $this->CI->manager_model->get_one(array('manager_idx'=>$uuid,'tg_no'=>$tg_no,'status'=>1),'tgunlock_his',"idx");
            if (!$train_info) {
                $data   = array(
                    'manager_idx'   => $uuid,
                    'tg_no'         => $tg_no,
                    'level'         => $level,
                    'vip_level'     => 0,
                    'tickets'       => 0,
                    'status'        => 1,
                );
                $ist_res = $this->CI->manager_model->insert_data($data, 'tgunlock_his');
                if (!$ist_res) {
                    $this->CI->manager_model->error();
                    log_message('error', 'train_unlock_err'.$this->ip.',训练场解锁失败');
                    return false;
                    // $this->CI->output_json_return('train_unlock_err');
                }
            }
        }
        // 阵型解锁
        $sql            = "SELECT structure_no,attradd_object,attradd_percent,lock_level FROM structure_conf as A WHERE status = 1 AND NOT EXISTS (SELECT structure_no from structure AS B where manager_idx = ".$uuid." AND A.structure_no = B.structure_no AND status = 1)";
        $structure_list = $this->CI->manager_model->fetch($sql);
        if ($structure_list) {
            foreach ($structure_list as $k=>$v) {
                if ($v['lock_level'] <= $level) {
                    $data1[] = array(
                        'manager_idx'       => $uuid,
                        'structure_no'      => $v['structure_no'],
                        'add_obj'           => $v['attradd_object'],
                        'attradd_percent'   => $v['attradd_percent'],
                        'is_use'            => 0,
                        'status'            => 1,
                    );
                }
            }
            if (!empty($data1)) {
                $res = $this->CI->manager_model->insert_batch($data1,'structure');
                if (!$res) {
                    $this->CI->manager_model->error();
                    log_message('error', 'structure_unlock_error'.$this->ip.',阵型解锁失败');
                    return false;
                    // $this->CI->output_json_return('structure_unlock_error');
                }
                //触发经理页面红点提示
                $this->load_library('tips_lib');
                $this->CI->tips_lib->tip_pages($uuid,1014);
            }
        }
        return true;
    }
    
    /**
     * 获取天赋列表
     * @param type $params
     */
    public function get_talent_list($params)
    {
        $condition      = "A.type =".$params['type']." AND A.status = 1";
        $join_condition = "A.idx = B.talent_idx AND B.manager_idx = ".$params['uuid']." AND B.status = 1";
        $select         = "A.idx as id,A.name AS name,A.pic AS pic,A.unlock_level AS level,A.talent_expend AS talentpoint,IF(B.IDX,1,0) AS is_active";
        $tb_a           = "talent_conf as A";
        $tb_b           = "talent AS B";
        $talent_list    = $this->CI->manager_model->left_join($condition, $join_condition, $select, $tb_a, $tb_b,TRUE);
        $m_info         = $this->get_manager_info($params['uuid']);
        foreach ($talent_list as $k=>&$v) {
            if ($v['is_active']) {
                $v['is_unlock'] = 1;
                $data[$v['level']][] = array('level'=>$v['level'],'info'=>$v);
            } elseif($v['level'] >= $m_info['level'] && $m_info['talent'] >= $v['talentpoint']) {
                $v['is_unlock'] = 1;
                $data[$v['level']][] = array('level'=>$v['level'],'info'=>$v);
            } else {
                $v['is_unlock'] = 0;
                $data[$v['level']][] = array('level'=>$v['level'],'info'=>$v);
            }
        }
        sort($data);
        return $data;
    }
    
    /**
     * 激活经理天赋
     * @param type $params
     */
    public function active_talent($params)
    {
        // 校验该天赋是否已激活
        $talent     = $this->CI->manager_model->get_one(array('manager_idx'=>$params['uuid'],'talent_idx'=>$params['id'],'status'=>1),'talent','idx');
        if ($talent) {
            log_message('info', "active_talent:talent_actived_err,".$this->ip.":该天赋已激活");
            $this->CI->output_json_return('talent_actived_err');
        }
        $where      = array('idx'=>$params['id'],'status'=>1);
        $fields     = "unlock_level,talent_expend";
        $tconf_info = $this->CI->manager_model->get_one($where,'talent_conf',$fields);
        if (!$tconf_info) {
            log_message('info', "active_talent:empty_data,".$this->ip.":暂无该天赋");
            $this->CI->output_json_return('empty_data');
        }
        $m_info = $this->get_manager_info($params['uuid']);
        if ($m_info['talent'] < $tconf_info['talent_expend']) {
            log_message('info', "active_talent:not_enought_talentpoint_err,".$this->ip.":经理没有足够的天赋点");
            $this->CI->output_json_return('not_enought_talentpoint_err');
        }
        if ($m_info['level'] < $tconf_info['unlock_level']) {
            log_message('info', "active_talent:m_level_not_enought,".$this->ip.":经理解锁等级不足");
            $this->CI->output_json_return('m_level_not_enought');
        }
        
        $this->CI->manager_model->start();
        // 天赋激活表
        $data   = array(
            'manager_idx'   => $params['uuid'],
            'talent_idx'    => $params['id'],
            'status'        => 1,
        );
        $ist_res    = $this->CI->manager_model->insert_data($data,'talent');
        if (!$ist_res) {
            $this->CI->manager_model->error();
            log_message('info', "active_talent:talent_insert_err,".$this->ip.":天赋插入失败");
            $this->CI->output_json_return('talent_insert_err');
        }
        // 天赋激活历史记录
        $data_2 = array(
            'manager_idx'   => $params['uuid'],
            'talent_idx'    => $params['id'],
            'talentpoint'   => $tconf_info['talent_expend'],
            'status'        => 1,
        );
        $int_res = $this->CI->manager_model->insert_data($data_2,'talent_his');
        if (!$int_res) {
            $this->CI->manager_model->error();
            log_message('info', "active_talent:talent_insert_his_err,".$this->ip.":天赋激活历史记录插入失败");
            $this->CI->output_json_return('talent_insert_his_err');
        }
        // 更新经理天赋点
        $fields     = array('talent'=>$m_info['talent'] - $tconf_info['talent_expend']);
        $where      = array('idx'=>$params['uuid'],'status'=>1);
        $upt_res    = $this->update_manager_info($fields,$where);
        if (!$upt_res) {
            $this->CI->manager_model->error();
            log_message('info', "active_talent:m_info_update_err,".$this->ip.":经理信息更新失败");
            $this->CI->output_json_return('m_info_update_err');
        }
        
        // 触发成就 - 战斗力
        $this->load_library('task_lib');
        $this->CI->task_lib->achieve_fighting($params['uuid']);
        
        $this->CI->manager_model->success();
    }
    
    /**
     * 重置经理天赋
     * @param type $params
     */
    public function reset_talent($params)
    {
        $m_info         = $this->get_manager_info($params['uuid']);
        $need_tickets   = $this->CI->passport->get('reset_talent_tickets');
        if ($m_info['tickets'] < $need_tickets) {
            log_message('info', "reset_talent:not_enough_tickets_err,".$this->ip.":天赋重置，球票不足");
            $this->CI->output_json_return('not_enough_tickets_err');
        }
        $this->CI->manager_model->start();
        // 获取经理总天赋点数，和需要重置的idx
        $condition      = "A.manager_idx =".$params['uuid']." AND A.status = 1 AND B.status = 1";
        $join_condition = "A.talent_idx = B.idx";
        $select         = "B.talent_expend AS num,B.idx as id";
        $tb_a           = "talent as A";
        $tb_b           = "talent_conf AS B";
        $talent         = $this->CI->manager_model->left_join($condition, $join_condition, $select, $tb_a, $tb_b,TRUE);
        if (!$talent) {
            return true;
        }
        // 删除经理天赋表数据
        $num        = 0;
        $reset_idx  = "";
        foreach ($talent as $k=>$v) {
            $num    +=$v['num'] ;
            $reset_idx  .=$v['id']."|";
        }
        $fields     = array('status'=>0);
        $where      = array('manager_idx'=>$params['uuid'],'status'=>1);
        $upt_res    = $this->CI->manager_model->update_data($fields,$where,'talent');
        if (!$upt_res) {
            $this->CI->manager_model->error();
            log_message('info', "reset_talent:talent_reset_err,".$this->ip.":天赋重置失败");
            $this->CI->output_json_return('talent_reset_err');
        }
        // 插入重置历史记录信息
        $data   = array(
            'manager_idx'   => $params['uuid'],
            'talent'        => $reset_idx,
            'tickets'       => $need_tickets,
            'status'        => 1,
        );
        $res = $this->CI->manager_model->insert_data($data,'talentreset_his');
        if (!$res) {
            $this->CI->manager_model->error();
            log_message('info', "reset_talent:talent_reset_his_err,".$this->ip.":天赋重置历史记录插入失败");
            $this->CI->output_json_return('talent_reset_his_err');
        }
        // 更新经理天赋点数
        $fields     = array('talent'=>$m_info['talent'] + $num);
        $where      = array('idx'=>$params['uuid'],'status'=>1);
        $upt_res    = $this->update_manager_info($fields,$where);
        if (!$upt_res) {
            $this->CI->manager_model->error();
            log_message('info', "reset_talent:m_info_update_err,".$this->ip.":经理信息更新失败");
            $this->CI->output_json_return('m_info_update_err');
        }
        $this->CI->manager_model->success();
        return true;
    }
    
    /**
     * 获取经理随机名
     * @param type $params
     */
    public function get_random_name($params)
    {
        // 获取经理已有的名称、机器人占用的名称
        $sql            = "SELECT A.name AS name FROM manager_info AS A WHERE A.status = 1 UNION SELECT B.name AS name from rebot_conf AS B  where B.status = 1";
        $exists_info    = $this->CI->manager_model->fetch($sql);
        // 随机获取姓(1-205)
        $while = true;
        while ($while) {
            $rand_surname   = mt_rand(1, 205);
            $where          = array('idx'=>$rand_surname,'status'=>1);
            $fields         = "surname";
            $surname        = $this->CI->manager_model->get_one($where,'name_lib',$fields);
            // 随机获取名(1-684)
            $rand_name      = mt_rand(1, 684);
            $where          = array('idx'=>$rand_name,'status'=>1);
            $fields         = "name";
            $name           = $this->CI->manager_model->get_one($where,'name_lib',$fields);
            $new_surname    = $surname['surname'];
            $new_name       = $name['name'];
            if (in_array($new_surname.$new_name, $exists_info)) {
                $while  = true;
            } else {
                $while  = false;
                $m_name = $new_surname.$new_name;
            }
        }
        $data['name'] = $m_name;
        return $data;
    }
    
    /**
     * 获取经理体力耐力信息
     * @return type
     */
    public function get_m_active($params)
    {
        // 获取经理信息
        $m_info     = $this->get_manager_info($params['uuid']);
        $m_active   = $this->CI->passport->get('m_active');
        // 获取经理体力最近恢复时间
        $each_time  = 0;
        $all_time   = 0;
        $phy_       = $m_info['phystrenth_total'] - $m_info['physical_strenth'];
        if ($phy_ > 0) {
            $each_time  = 600;
            $all_time   = ($phy_-1)*600+$each_time;
        }
        
        $active   = array(
            'each_time'         => $each_time,
            'all_time'          => $all_time,
            'recover_time'      => $m_active['endurecover_time'],
            'endurance'         => $m_info['endurance'],
            'physical_strenth'  => $m_info['physical_strenth'],
        );
        return $active;
    }
    
    
    
    
}

