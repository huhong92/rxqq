<?php
class Task_lib extends Base_lib {
    //新手教程动作
    public $coures_action = array(
        'level_up' => array(//经理升级相关
            '13' => 6,
            '14' => 8,
            '15' => 10,
            '18' => 15,
            ),
    );
    
    // 成就条件配置
    public $achieve_condition   = array(
        'MUPGRADE_CATNO'    => 1,// 成长轨迹
        'FIGHTING_CATNO'    => 2,// 战力达人
        'TALENT_CATNO'      => 3,// 天赋大师
        'ENTER_CATNO'       => 4,// 王者之师
        'PUPGRADE_CATNO'    => 5,//升阶达人
        'TRAINGROUND_CATNO' => 6,// 训练大师
        'ATTRIBUTE_CATNO'   => 7,// 培养大师
        'ACTIVE_CATNO'      => 8,// 收藏家
        'STRUCTURE_CATNO'   => 9,// 阵型大师
        'EQUIPTSUIT_CATNO'  => 10,//装备达人
        'DECOMPOSE_CATNO'   => 11,//分解大师
        'COPY_CATNO'        => 12,// 副本达人
        'LADDER_CATNO'      => 13,//天梯王者
        'RANKING_CATNO'     => 14,// 东方不败
        'PASSLAYER_CATNO'   => 15,//闯关者
    );
    
    //任务动作配置    动作 => array(任务ID);
    public $task_action = array(
            'create_team'   => array(1001 , 1005),  //创建队伍有关
            'match_for_copy'   => array(1002 , 1003 , 2001 , 2002 , 2007),//副本赛相关
            'match_for_league'  => array(1013 , 2010),//五大联赛相关
            'match_for_ladder'     => array(1010 , 2009),//天梯赛相关
            'decompose'    => array(1004 , 2003),//分解装备相关
            'draw'   => array(2004 , 2005),//抽卡相关
            //'insert_player'   => array(1005),//增加球员
            'train'   => array(1012 , 2012),//球员训练相关
            'player_upgrade'   => array(1007 , 2008),//球员升阶相关
            'reset_for_league'   => array(2011),//重置五大联赛
            'upgrade_equipt'   => array(2006),//装备升级
            'update_structure'   => array(1011 , 1008),//更换阵容
            'goods_buy'   => array(1009),//购买物品相关
            'load_equipt'   => array(1006),//装载装备有关
            'clear_fatigue'   => array(2013),//清空疲劳值相关
        );
    
    //任务属性配置
    public $task_condition= array(
            1001  => array('condition' => 1 , 'type' => 1),
            1002  => array('condition' => 1 , 'type' => 1 , 'table' => 'ckpoint_his'),
            1003  => array('where' => 'ckpoint_type = 2' , 'condition' => 1 , 'type' => 1 , 'table' => 'ckpoint_his'),
            1004  => array('condition' => 1 , 'type' => 1 , 'table' => 'decompose_his'),
            1005  => array('condition' => 1 , 'type' => 1),
            1006  => array('where' => 'level > 5' , 'condition' => 1 , 'type' => 1 , 'table' => 'equipt'),
            1007  => array('where' => 'level >= 1' , 'condition' => 1 , 'type' => 1 , 'table' => 'player_info'),
            1008  => array('condition' => 1 , 'type' => 1 , 'table' => 'structure_his'),
            1009  => array('where' => 'buy_status = 1' , 'condition' => 1 , 'type' => 1 , 'table' => 'goodsbuy_his'),
            1010  => array('where' => 'result = 1' , 'condition' => 1 , 'type' => 1 , 'table' => 'ladder_his'),
            1011  => array('where' => 'is_use = 2' , 'condition' => 1 , 'type' => 1 , 'table' => 'structure'),
            1012  => array('condition' => 1 , 'type' => 1 , 'table' => 'train_his'),
            1013  => array('where' => 'result = 1' , 'condition' => 1 , 'type' => 1 , 'table' => 'fiveleague_his'),
            2001  => array('where' => 'ckpoint_type = 1' , 'condition' => 5 , 'type' => 2 , 'table' => 'ckpoint_his'),
            2002  => array('where' => 'ckpoint_type = 2' , 'condition' => 5 , 'type' => 2 , 'table' => 'ckpoint_his'),
            2003  => array('condition' => 5 , 'type' => 2 , 'table' => 'decompose_his'),
            2004  => array('where' => 'type = 1' , 'condition' => 10 , 'type' => 2 , 'table' => 'draw_his'),
            2005  => array('where' => 'type = 3' , 'condition' => 2 , 'type' => 2 , 'table' => 'draw_his'),
            2006  => array('condition' => 10 , 'type' => 2 , 'table' => 'eupgrade_his'),
            2007  => array('where' => 'ckpoint_type = 1' , 'condition' => 3 , 'type' => 2 , 'table' => 'ckpoint_his'),
            2008  => array('condition' => 3 , 'type' => 2 , 'table' => 'pupgrade_his'),
            2009  => array('where' => 'result = 1' , 'condition' => 5 , 'type' => 2 , 'table' => 'ladder_his'),
            2010  => array('where' => 'result = 1' , 'condition' => 10 , 'type' => 2 , 'table' => 'fiveleague_his'),
            2011  => array('condition' => 1 , 'type' => 2 , 'table' => 'fiveleague_reset_his'),
            2012  => array('where' => 'type = 2' , 'condition' => 3 , 'type' => 2 , 'table' => 'train_his'),
            2013  => array('condition' => 3 , 'type' => 2 , 'table' => 'fatigue_his'),
        );
    
    //使用公共刷新方法的任务
    public $common_refresh = array(1002 , 1003 , 1004 , 1006  , 1007 , 1008 , 1009 , 1010 , 1011 , 1012 , 1013 , 2001 , 2002 , 2006 , 2008 , 2009 , 2010 , 2011 , 2012 , 2013);
    
    public function __construct() {
        parent::__construct();
        $this->load_model('task_model');
    }
    
    /*
     * 获取经理完成的新手引导
     */
    public function check_coures_complete($uuid , $n_c_id)
    {
        $where = "manager_idx = $uuid AND n_c_id = $n_c_id AND status = 1";
        $coures_info = $this->CI->task_model->get_one($where , 'novice_coures');
        return $coures_info;
    }
    
    /*
     * 新手引导公共完成方法
     */
    public function n_c_public_complete($uuid , $n_c_id)
    {
        $complete = $this->check_coures_complete($uuid , $n_c_id);
        if($complete){
            return TRUE;
        }
        $res = $this->update_novice_coures($uuid, $n_c_id, 0);
        return $res;
    }

    /*
     * 新手引导 6 
     */
    public function second_match($uuid, $n_c_id)
    {
        $complete = $this->check_coures_complete($uuid, $n_c_id);
        
        if($complete){
            return TRUE;
        }
        //查看经理完成比赛的次数
        $where = " manager_idx = $uuid AND status = 1";
        $complete_num = $this->CI->task_model->total_count('idx' , $where , 'ckpoint_his');
        if($complete_num < 2){
            return TRUE;
        }
        $res = $this->update_novice_coures($uuid, 6, 0);
        return $res;
    }

    /*
     * 新手引导11
     */
    public function n_equipt_up($uuid, $n_c_id)
    {
        $complete = $this->check_coures_complete($uuid, $n_c_id);
        if($complete){
            return TRUE;
        }
        //查看经理初级装备升阶卡的数量
        $where = array('manager_idx'=> $uuid, 'prop_no'=>401, 'status'=>1);
        $complete_num = $this->CI->court_model->get_one($where,'prop','num');
        if($complete_num['num'] < 3){
            return TRUE;
        }
        $res = $this->update_novice_coures($uuid, 11 , 0);
        return $res;
    }

    /*
     * 和等级相关的新手引导 13 14 15 18
     */
    public function level_up($uuid , $level)
    {
        if($level > 16){
            return TRUE;
        }
        $info = $this->coures_action;
        $novice_coures_info = $info['level_up'];
        foreach ($novice_coures_info as $k => $v)
        {
            if($level == $v){
                //检查是否完成
                if(!$this->check_coures_complete($uuid , $k)){
                    //更新经理新手引导进度
                    $this->update_novice_coures($uuid , $k , 0);
                }
            }
        }
    }
    
    /*
     * 新手引导12 解锁第二个阵型
     */
    public function unlock_structure_2($uuid)
    {
        //判断是否已完成
        $complete = $this->check_coures_complete($uuid , 12);
        if($complete){
            return TRUE;
        }
        //检查经理是否解锁阵型--该条件改成 经理level达到3触发【2017-02-28 10:25:00/@author:huhong】
//        $where = array('manager_idx'=> $uuid, 'structure_no'=>2, 'status'=>1);
//        $exists = $this->CI->court_model->get_one($where,'structure','idx as id,is_use,structure_no');
//        if (!$exists) {
//            return TRUE;
//        }
        $res = $this->update_novice_coures($uuid, 12, 0);
        return $res;
    }

    /*
     * 更新经理新手引导进度
     */
    public function update_novice_coures($uuid , $n_c_id , $tip_status)
    {
        $data   = array(
            'manager_idx' => $uuid,
            'n_c_id'      => $n_c_id,
            'n_c_tip'     => $tip_status,
            'status'      => 1,
        );
        $ist_res    = $this->CI->task_model->insert_data($data,'novice_coures');
        if(!$ist_res){
            log_message('error', 'update_novice_coures:'.$this->ip.',修改新手任务状态失败');
            $this->CI->output_json_return('update_novice_coures');
        }
        return $ist_res;
    }
    
    /*
     * 查询任务列表
     */
    public function get_task_list($params)
    {
        $join_condition = "t1.task_no = t2.task_no  AND t2.manager_idx = {$params['uuid']} AND t2.status = 1";
        $condition      = "t1.type = {$params['task_type']} AND t1.status = 1 LIMIT {$params['offset']} , {$params['pagesize']}";
        //查询每日任务的附加条件
        if($params['task_type'] == 2) 
        {
            //获取今天零点时间戳
            $today = strtotime(date('Y-m-d'));
            $join_condition .= " AND t2.time >= $today ";
            //查询玩家每日任务条件达成情况
            $type2_join_condition = "t1.task_no = t2.task_no  AND t2.manager_idx = {$params['uuid']} AND t2.status = 1 AND t2.time >= $today";
            $type2_condition      = "t1.type = {$params['task_type']} AND t1.status = 1 LIMIT {$params['offset']} , {$params['pagesize']}";
            $type2_select         = "t1.task_no as task_no , t2.condition_num as complete";
            $type2_tb_a           = "task_conf AS t1";
            $type2_tb_b           = "task_day_condition AS t2";
            $type2_task = $this->CI->task_model->left_join($type2_condition , $type2_join_condition , $type2_select , $type2_tb_a , $type2_tb_b , TRUE);  
            $type2_task = $this->CI->utility->reset_array_key($type2_task , 'task_no');
            
        }
        //获取玩家完成的任务
        $select         = "t1.task_no as task_no , t1.name , t1.condition , t1.descript , t1.type , t1.exp , t1.euro , t1.tickets , t1.soccer_soul , t1.powder , t1.prop_info , t1.gem_info , t1.equipt_info , t1.url , t2.receive , t2.manager_idx";
        $tb_a           = "task_conf AS t1";
        $tb_b           = "task_complete AS t2";
        $task_list = $this->CI->task_model->left_join($condition , $join_condition , $select , $tb_a , $tb_b , TRUE);  
        //设置任务的状态
        $first_list = array(); 
        $next_list = array();
        $last_list = array();
        if(!$task_list){
            log_message('error', 'get_tasklist_err:'.$this->ip.',获取任务列表失败');
            $this->CI->output_json_return('get_tasklist_err');
        }
        foreach($task_list as $k => &$v)
        {
            //插入每日任务的条件达成情况
            $v['condition_complete'] = 0;
            if($params['task_type'] == 2){          
                if($type2_task[$v['task_no']]['complete']){
                    $v['condition_complete'] = $type2_task[$v['task_no']]['complete'];
                }
            }
            //获取任务奖励数组
            $v['reward'] = $this->CI->utility->get_reward($v);
            unset($v['exp']);
            unset($v['euro']);
            unset($v['tickets']);
            unset($v['soccer_soul']);
            unset($v['powder']);
            unset($v['prop_info']);
            unset($v['gem_info']);
            unset($v['equipt_info']);
            //完成任务
            if($v['manager_idx']){
                $v['complete'] = 1;
                //重新排列任务数据列表：完成但未领取->未完成->完成并已领取（每日任务删掉）
                if($v['receive']){
                    if($v['type'] == 1){
                        $last_list[$k] = $v;
                    }
                }
                else{
                    $v['receive'] = 0;
                    $first_list[$k] = $v;
                }
            }
            else{
                $v['complete'] = 0;
                $v['receive'] = 0;
                $next_list[$k] = $v;
            }
        }
        $task_list = array_merge($first_list, $next_list , $last_list); 
        return $task_list;
    }
    
    /*
     * 查询执行任务的完成情况
     */
    public function get_task_status($uuid , $task_no)
    {
        $task_info = $this->CI->task_model->fetch("SELECT `idx` , `type` , `receive` , `time` FROM task_complete WHERE manager_idx = $uuid AND `status` = 1 AND task_no = $task_no ORDER BY time DESC LIMIT 1" , 'row');
        $return_arr['complete'] = 0;
        $return_arr['receive']  = 0;
        if($task_info){
            if($task_info['type'] == 2){
                $today = strtotime(date('Y-m-d'));
                if($today > $task_info['time']){
                    return $return_arr;
                }
            }
            $return_arr['complete'] = 1;
            $return_arr['receive'] = $task_info['receive'];
            $return_arr['idx'] = $task_info['idx'];
            return $return_arr;
        }
        else{
            return $return_arr;
        }
    }
    
    /*
     * 获取任务查询条件
     */
    public function get_where($uuid , $task_no)
    {
        $task_condition = $this->task_condition;
        $task_condition = $task_condition[$task_no];
        //查询任务目标达成次数
        $where = "manager_idx = $uuid AND status = 1";
        if($task_condition['type'] == 2)
        {
            $today = strtotime(date('Y-m-d'));
            $where .= ' AND time >= '.$today;
        }
        if($task_condition['where'])
        {
            $where .= ' AND '.$task_condition['where'];
        }
        $return_arr['where'] = $where;
        $return_arr['condition'] = $task_condition;
        return $return_arr;
    }

    /*
     * 查新玩家每日任务条件达成次数
     */
    public function type2task_condition_num($uuid , $task_no , $condition_complete_num)
    {
        $this->CI->task_model->start();
        //查询当日每日任务记录
        $today = strtotime(date('Y-m-d'));
        $where = "time >= $today AND manager_idx = $uuid AND task_no = $task_no AND status = 1";
        $have = $this->CI->task_model->get_one($where , 'task_day_condition');
        
        //完成次数
        $data['condition_num'] = $condition_complete_num;
        if($have){
            //更新每日任务条件达成次数
            $res = $this->CI->task_model->update_data($data , $where , 'task_day_condition');
        }
        else if(!$have){
            //新增记录每日任务完成条件
            $data['manager_idx'] = $uuid;
            $data['task_no'] = $task_no;
            $data['status'] = 1;
            $res = $this->CI->task_model->insert_data($data , 'task_day_condition');
        }
        if(!$res){
            $this->CI->task_model->error();
            log_message('error', 'task:type2task_condition_num'.$this->ip.','.http_build_query($_REQUEST));
            $this->CI->output_json_return('type2task_condition_num');
        }
        $this->CI->task_model->success();
    }

    /*
     * 公共刷新任务
     */
    public function refresh_common($uuid , $task_no)
    {
        //获取sql查询条件
        $condition = $this->get_where($uuid , $task_no);
        $where = $condition['where'];
        $condition = $condition['condition'];
        $condition_complete_num = $this->CI->task_model->total_count('idx' , $where , $condition['table']);
        //每日任务条件达成次数
        if($condition['type'] == 2 && ($condition_complete_num >= 1) && ($condition_complete_num <= $condition['condition'])){
            $this->type2task_condition_num($uuid, $task_no, $condition_complete_num);
        }
        if($condition_complete_num >= $condition['condition']){
            //更新任务状态为完成
            $this->complete_task($uuid , $task_no , $condition['type']);
        }
        return;
    }
    
    public function refresh_decompose($uuid , $task_no)
    {
        if($task_no != 2003){
            $this->refresh_common($uuid, $task_no);
            return;
        }
        $condition = $this->get_where($uuid , $task_no);
        $where = $condition['where'];
        $condition = $condition['condition'];
        
        $today = strtotime(date('Y-m-d'));
        $where = 'AND time >= '.$today;
        $select = "decompose1_info as a1 , decompose2_info as a2 , decompose3_info as a3 , decompose4_info as a4 , decompose5_info as a5 , decompose6_info as a6";
        $sql = "SELECT ".$select." FROM decompose_his WHERE manager_idx = ".$uuid." AND status = 1 $where LIMIT 0 , 1000";
        $info   = $this->CI->task_model->fetch($sql,'all');
        $count_info = 0;
        foreach ($info as $k => $v)
        {
            foreach($v as $value)
            {
                if($value != ""){
                    $count_info = $count_info + 1;
                }
            }
        }
        //每日任务条件达成次数
        if($count_info >= 1){
            $this->type2task_condition_num($uuid, $task_no, $count_info);
        }
        if($count_info >= $condition['condition']){
            //更新任务状态为完成
           $this->complete_task($uuid , $task_no , $condition['type']);
        }
        return;
    }


    /*
     * 刷新抽卡任务
     */
    public function refresh_draw($uuid , $task_no)
    {
        //获取sql查询条件
        $condition = $this->get_where($uuid , $task_no);
        $where = $condition['where'];
        $condition = $condition['condition'];
        $condition_complete_num = $this->CI->task_model->get_one($where , $condition['table'] , 'SUM(draw_times) as num');
        //每日任务条件达成次数
        if($condition['type'] == 2 && ($condition_complete_num['num'] >= 1) && ($condition_complete_num['num'] <= $condition['condition'])){
            $this->type2task_condition_num($uuid, $task_no, $condition_complete_num['num'] );
        }
        if($condition_complete_num['num'] >= $condition['condition']){
            //更新任务状态为完成
           $this->complete_task($uuid , $task_no , $condition['type']);
        }
        return;
    }

    /*
     * 新增球员刷新任务
     * 新手任务触发后直接判定完成
     */
    public function refresh_create_team($uuid , $task_no)
    {
        $task_condition = $this->task_condition;
        $task_condition = $task_condition[$task_no];
        //更新任务状态为完成
        $this->complete_task($uuid , $task_no , $task_condition['type']);
    }
    
    /*
     * 完成副本赛刷新任务
     */
    public function refresh_match_for_copy($uuid , $task_no)
    {
        //获取where条件
        $condition = $this->get_where($uuid , $task_no);
        $where = $condition['where'];
        $condition = $condition['condition'];
        $condition_complete_num = $this->CI->task_model->fetch("SELECT COUNT(f_num) as num FROM (SELECT COUNT(*) as f_num FROM {$condition['table']} WHERE $where GROUP BY formation) as t1" , 'row');
        //每日任务条件达成次数
        if($condition['type'] == 2 && ($condition_complete_num['num'] >= 1) && ($condition_complete_num['num'] <= $condition['condition']))
        {
            $this->type2task_condition_num($uuid, $task_no, $condition_complete_num['num'] );
        }
        if($condition_complete_num && ($condition_complete_num['num'] >= $condition['condition']))
        {
           //更新任务状态为完成
           $this->complete_task($uuid , $task_no , $condition['type']);
        }
    }

    /*
     * 完成任务
     */
    public function complete_task($uuid , $task_no , $task_type)
    {
        $this->CI->task_model->start();
        $data['manager_idx'] = $uuid;
        $data['task_no'] = $task_no;
        $data['type'] = $task_type;
        $data['receive'] = 0;
        $data['status'] = 1;
        $res = $this->CI->task_model->insert_data($data , 'task_complete');
        if(!$res)
        {
            log_message('error', 'complete_task_err:'.$this->ip.',完成任务更新数据库失败');
            $this->CI->task_model->error();
            $this->CI->output_json_return('complete_task_err');
        }
        $this->CI->task_model->success();
        //任务页面红点提示
        $this->load_library('tips_lib');
        $this->CI->tips_lib->tip_pages($uuid,1013);
        if($task_type == 1)
            $this->CI->tips_lib->tip_pages($uuid,1021);
        else
            $this->CI->tips_lib->tip_pages($uuid,1020);
        
        return;
    }
    
    /*
     * 领取任务奖励
     */
    public function get_task_reward($params)
    {
        //查询任务是否完成
        $task_status = $this->get_task_status($params['uuid'], $params['task_no']);
        $this->CI->task_model->start();
        if($task_status['complete'] == 1 && $task_status['receive'] == 0)
        {
            $m_info = $this->CI->utility->get_manager_info($params);
            //获取任务对应的奖励
            $where1['task_no'] = $params['task_no'];
            $where1['status'] = 1;
            $fields1 = 'exp , euro , tickets , soccer_soul , powder , prop_info , gem_info , equipt_info';
            $task_reward = $this->CI->task_model->get_one($where1 , 'task_conf' , $fields1);
            //发放奖励
            foreach ($task_reward as $k => $v){
                if($v){
                    //装备奖励
                    if($k == 'equipt_info'){
                        $equipt = $this->CI->utility->get_item_reward($v , 'id' , 'equipt');
                        foreach ($equipt as $key => $value){
                            $insert['uuid'] = $params['uuid'];
                            $insert['equipt_no'] = $value['id'];
                            $res = $this->CI->utility->insert_equipt_info($insert);
                            if(!$res){
                                log_message('error', 'send_equipt_err:'.$this->ip.',发送任务装备奖励失败');
                                $this->CI->task_model->error();
                                $this->CI->output_json_return('task_send_equipt_err');
                            }
                        }
                    }
                    //道具奖励
                    if($k == 'prop_info'){
                        $prop = $this->CI->utility->get_item_reward($v , 'id' , 'prop');
                        foreach ($prop as $key => $value){
                            $insert['uuid']    = $params['uuid'];
                            $insert['prop_no'] = $value['id'];
                            $res = $this->CI->utility->insert_prop_info($insert , $value['num']);
                            if(!$res){
                                log_message('error', 'send_prop_err:'.$this->ip.',发送任务道具奖励失败');
                                $this->CI->task_model->error();
                                $this->CI->output_json_return('task_send_prop_err');
                            }
                        }
                    }
                    //宝石奖励
                    if($k == 'gem_info'){
                        $gem = $this->CI->utility->get_item_reward($v , 'id' , 'prop');
                        foreach ($gem as $key => $value){
                            $insert['uuid']   = $params['uuid'];
                            $insert['gem_no'] = $value['id'];
                            $insert['num']    = $value['num'];
                            $res = $this->CI->utility->insert_gem_info($insert);
                            if(!$res){
                                log_message('error', 'send_gem_err:'.$this->ip.',发送任务宝石奖励失败');
                                $this->CI->task_model->error();
                                $this->CI->output_json_return('task_send_gem_err');
                            }
                        }
                    }
                    //经验奖励
                    if($k == 'exp'){
                        $exp_new    = $m_info['current_exp'] + $v;
                        $exp_info   = $this->CI->manager_lib->exp_belongto_level($exp_new);
                        $fields['current_exp'] = $exp_new;
                        $fields['total_exp'] = $exp_info['extotal_exp'];
                        $fields['upgrade_exp'] = $exp_info['experience'];
                        $fields['level'] = $exp_info['level'];
                    }
                    //其他奖励
                    if($k == 'euro')
                        $fields['euro'] = $m_info['euro'] + $v;
                    if($k == 'tickets')
                        $fields['tickets'] = $m_info['tickets'] + $v;
                    if($k == 'soccer_soul')
                        $fields['soccer_soul'] = $m_info['soccer_soul'] + $v;
                    if($k == 'powder')
                        $fields['powder'] = $m_info['powder'] + $v;
                    unset($insert);
                }
            }
            if($fields)
            {
                $where    = array('idx'=>$params['uuid'],'status'=>1);
                $res  = $this->CI->utility->update_m_info($fields,$where);
                if (!$res) 
                {
                    $this->CI->task_model->error();
                    log_message('error', 'get_task_reward_err:get_reward_err'.$this->ip.','.http_build_query($_REQUEST));
                    $this->CI->output_json_return('task_send_reward_err');
                }
            }
            //修改任务状态为已领取
            $update_data['receive'] = 1;
            $where['idx'] = $task_status['idx'];
            $where['status'] = 1;
            $res = $this->CI->task_model->update_data($update_data , $where , 'task_complete');
            if (!$res) 
            {
                $this->CI->task_model->error();
                log_message('error', 'get_task_reward_err:get_reward_err'.$this->ip.','.http_build_query($_REQUEST));
                $this->CI->output_json_return('task_receive_err');
            }
        }
        else if ($task_status['complete'] == 0)
        {
            $this->CI->task_model->error();
            log_message('error', 'get_task_reward_err:complete_err'.$this->ip.','.http_build_query($_REQUEST));
            $this->CI->output_json_return('task_complete_err');
        }
        else
        {
            $this->CI->task_model->error();
            log_message('error', 'get_task_reward_err:receive_err'.$this->ip.','.http_build_query($_REQUEST));
            $this->CI->output_json_return('task_have_receive');
        }
        $this->CI->task_model->success();
        return $res;
    }
    
    /**
     * 获取成就模块列表
     * @param type $params
     */
    public function get_amodule_list($params)
    {
        $options['where']   = array('status'=>1);
        $options['groupby'] = "module_no";
        $options['fields']  = "module_no as id,module as name";
        $module_list        = $this->CI->task_model->list_data($options,'achievement_conf');
        if (!$module_list) {
            log_message('error', 'get_amodule_list:empty_data'.$this->ip.','.http_build_query($_REQUEST));
            $this->CI->output_json_return('empty_data');
        }
        return $module_list;
    }
    
    /**
     * 获取成就列表
     */
    public function get_achievement_list($params)
    {
        // 获取成就列表总条数
        $total_count    = $this->get_achieve_total_count($params['id']);
        if (!$total_count) {
            log_message('error', 'get_achievement_list:empty_data'.$this->ip.','.http_build_query($_REQUEST));
            $this->CI->output_json_return('empty_data');
        }
        $data['pagecount']  = ceil($total_count/$params['pagesize']);
        $condition      = "A.status = 1 AND A.module_no = ".$params['id'];
        $join_condition = "A.achieve_no = B.achieve_no AND B.manager_idx = ".$params['uuid']." AND B.status = 1";
        $select         = "A.idx as id,A.module_no AS module_no,A.achieve_catno cat_no,A.name AS name,A.achieve_no AS achieve_no,A.descript AS descript,A.achievepoint AS achievepoint,A.euro AS euro,A.tickets AS tickets,A.soccer_soul AS soccer_soul,A.powder AS powder,A.prop_info AS prop_info,A.gem_info AS gem_info,A.player_info AS player_info,A.equipt_info AS equipt_info,IF(B.IDX,1,0) AS complete,IF(B.receive,1,0) AS receive";
        $tb_a           = "achievement_conf AS A";
        $tb_b           = "achievement_complete AS B";
        $achieve_list   = $this->CI->task_model->left_join($condition, $join_condition, $select, $tb_a, $tb_b,TRUE);
        // 每种任务只列举一条，列举顺序 已完成未领取状态-》未完成未领取-》已完成已领取
        foreach ($achieve_list as $k=>$v) {// 1级2级3级
            $v['pic']       = $this->generate_achievement_pic($v['module_no'],$v['achievepoint']);
            $v['reward']    = $this->CI->utility->get_reward($v);
            unset($v['achievepoint']);
            unset($v['euro']);
            unset($v['tickets']);
            unset($v['soccer_soul']);
            unset($v['powder']);
            unset($v['prop_info']);
            unset($v['gem_info']);
            unset($v['equipt_info']);
            unset($v['player_info']);
            if ($v['complete'] == 1 && $v['receive'] == 0) {// 已完成未领取状态
                if(!$new_list[$v['cat_no']][1])  {
                    $new_achieve[$v['cat_no']]  = $v;
                    $new_list[$v['cat_no']][1]  = 1;
                } else {
                    continue;
                }
            } else if($v['complete'] == 0) {// 未完成未领取
                if(!$new_list[$v['cat_no']][1] && !$new_list[$v['cat_no']][2])  {
                    $new_achieve[$v['cat_no']]  = $v;
                    $new_list[$v['cat_no']][2]  = 1;
                } else {
                    continue;
                }
            } elseif($v['complete'] == 1 && $v['receive'] == 1) {// 已完成已领取
                if(!$new_list[$v['cat_no']][1] && !$new_list[$v['cat_no']][2] && !$new_list[$v['cat_no']][3])  {
                    $new_achieve[$v['cat_no']]  = $v;
                    $new_list[$v['cat_no']][3]  = 1;
                } else {
                    continue;
                }
            }
        }
        // 成就分页展示
        $count_all = count(array_slice($new_achieve, $params['offset']));
        if ($count_all >= $params['pagesize']) {
            $data['list'] = array_slice($new_achieve, $params['offset'], $params['pagesize']);
        } else {
            $data['list'] = array_slice($new_achieve, $params['offset'], $count_all);
        }
        // 获取当前成就点数、总成就点数
        $m_info = $this->CI->utility->get_manager_info($params);
        $data['achievepoint_curr']  = $m_info['achievement'];
        $data['achievepoint_total'] = 604;
        //取出经理完成的所有成就
        $sql = "SELECT achieve_catno FROM achievement_complete WHERE manager_idx = {$params['uuid']} AND `status` = 1 AND receive = 1 GROUP BY achieve_catno";
        $complete_catno = $this->CI->task_model->fetch($sql , 'all');
        $complete_list  = array();
        if($complete_catno){
            foreach($complete_catno as $k => $v){
                $complete_list[] = $v['achieve_catno'];
            }
        }
        //增加下拉按钮字段
        foreach ($data['list'] as $k => &$v)
        {
            $v['button'] = 0;
            if(in_array($v['cat_no'] , $complete_list)){
                $v['button'] = 1;
            }
        }
        return $data;
    }
    
    /**
     * 获取成就列表总条数
     * @param int $module_no  模块编号
     */
    public function get_achieve_total_count($module_no)
    {
        $sql    = "SELECT COUNT(idx) as sum FROM achievement_conf WHERE module_no = ".$module_no." AND status = 1 GROUP BY achieve_catno";
        $total  = $this->CI->task_model->fetch($sql);
        if (!$total) {
            return 0;
        }
        return count($total);
    }
    
    /**
     * 获取某类型的成就列表
     */
    public function get_achdetail_list($params)
    {
        $select         = "A.idx as id,A.module_no AS module_no,A.achieve_catno cat_no,A.name AS name,A.achieve_no AS achieve_no,A.descript AS descript,A.achievepoint AS achievepoint,A.euro AS euro,A.tickets AS tickets,A.soccer_soul AS soccer_soul,A.powder AS powder,A.prop_info AS prop_info,A.gem_info AS gem_info,A.player_info AS player_info,A.equipt_info AS equipt_info,IF(B.IDX,1,0) AS complete,IF(B.receive,1,0) AS receive";
        $sql            = "SELECT ".$select." FROM achievement_conf AS A JOIN achievement_complete AS B ON A.achieve_catno = ".$params['cat_no']." AND A.achieve_no = B.achieve_no WHERE B.manager_idx = ".$params['uuid']." AND B.status = 1 AND A.status = 1";
        $achdetail_list = $this->CI->task_model->fetch($sql,'result');
        if (!$achdetail_list) {
            log_message('error', 'get_achdetail_list:empty_data'.$this->ip.','.http_build_query($_REQUEST));
            $this->CI->output_json_return('empty_data');
        }
        foreach ($achdetail_list as $k=>&$v) {
            if ($v['receive']) {
                $v['pic']       = $this->generate_achievement_pic($v['module_no'],$v['achievepoint']);
                $v['reward']    = $this->CI->utility->get_reward($v);
                unset($v['achievepoint']);
                unset($v['euro']);
                unset($v['tickets']);
                unset($v['soccer_soul']);
                unset($v['powder']);
                unset($v['prop_info']);
                unset($v['gem_info']);
                unset($v['equipt_info']);
                unset($v['player_info']);
                $new_list[]   = $v;
            }
        }
        if (!$new_list) {
            log_message('error', 'get_achdetail_list:empty_data'.$this->ip.','.http_build_query($_REQUEST));
            $this->CI->output_json_return('empty_data');
        }
        $data['list']   = $new_list;
        return $data;
    }
    
    /**
     * 判断成就是否触发
     * @param int $uuid             经理idx
     * @param int $achievement_no   成就类型编号
     * @param type $condition       当前完成状态（已完成）
     */
    public function trigger_achievement($uuid,$cat_no,$condition)
    {
        $select = "A.idx as id,A.module_no AS module_no,A.achieve_catno AS achieve_catno,A.name AS name,A.achieve_no AS achieve_no,A.condition AS condition1, A.descript AS descript,A.achievepoint AS achievepoint,A.euro AS euro,A.tickets AS tickets,A.soccer_soul AS soccer_soul,A.powder AS powder,A.prop_info AS prop_info,A.gem_info AS gem_info,A.player_info AS player_info,A.equipt_info AS equipt_info";
        $sql    = "SELECT ".$select." FROM achievement_conf A WHERE achieve_catno = ".$cat_no." AND status = 1 AND achieve_no not in (SELECT achieve_no FROM achievement_complete WHERE manager_idx = ".$uuid." AND status = 1) ORDER BY condition1";
        //针对天梯赛和五大联赛特殊处理（condition字段数据格式为varchar，但存储内容为int，不能正常排序）
        if($cat_no == 13 || $cat_no == 15)
        {
            $sql  = "SELECT ".$select." FROM achievement_conf A WHERE achieve_catno = ".$cat_no." AND status = 1 AND achieve_no not in (SELECT achieve_no FROM achievement_complete WHERE manager_idx = ".$uuid." AND status = 1) ORDER BY achieve_no";
        }
        $info   = $this->CI->task_model->fetch($sql,'row');
        if (!$info) {// 任务不存在
            return false;
        }
        // 判断是否达到触发条件
        //针对天梯赛特殊处理
        if($cat_no == 13){
            if ($info['condition1'] < $condition) {
                return true;
            }
        }
        else{
            if ($info['condition1'] >= $condition) {
                return true;
            }
        }
        $info['uuid']   = $uuid;
        $ist_res        = $this->insert_achievement($info);
        if (!$ist_res) {
            return false;
        }
        //成就页面红点提示
        $this->load_library('tips_lib');
        $this->CI->tips_lib->tip_pages($uuid,1012);
        if($info['module_no'] == 1)
            $this->CI->tips_lib->tip_pages($uuid,1022);
        if($info['module_no'] == 2)
            $this->CI->tips_lib->tip_pages($uuid,1023);
        if($info['module_no'] == 3)
            $this->CI->tips_lib->tip_pages($uuid,1024);
        
        return true;
    }
        
    /**
     * 插入成就完成记录
     * @param int $achievement_id  成就idx
     */
    public function insert_achievement($params)
    {
        $data   = array(
            'manager_idx'   => $params['uuid'],
            'achieve_no'    => $params['achieve_no'],
            'module_no'     => $params['module_no'],
            'achieve_catno' => $params['achieve_catno'],
            'achievepoint'  => $params['achievepoint'],
            'euro'          => $params['euro'],
            'tickets'       => $params['tickets'],
            'soccer_soul'   => $params['soccer_soul'],
            'powder'        => $params['powder'],
            'prop_info'     => $params['prop_info'],
            'gem_info'      => $params['gem_info'],
            'player_info'   => $params['player_info'],
            'equipt_info'   => $params['equipt_info'],
            'receive'       => 0,
            'status'        => 1,
        );
        $ist_res    = $this->CI->task_model->insert_data($data,'achievement_complete');
        return $ist_res;
    }
    
    /**
     * 领取成就奖励
     * @param int $uuid       经理idx
     * @param int $achieve_no 成就编号
     */
    public function do_reward_achieve($params)
    {
        $uuid       = $params['uuid'];
        $achieve_no = $params['achieve_no'];
        $where      = array('achieve_no'=>$achieve_no,'manager_idx'=>$params['uuid'],'status'=>1);
        $fields     = "idx AS id,achieve_no,receive,achievepoint,euro,tickets,soccer_soul,powder,prop_info,gem_info,player_info,equipt_info,";
        $comp_info  = $this->CI->task_model->get_one($where,'achievement_complete',$fields);
        if (!$comp_info) {
            log_message('error', 'do_reward_achieve:achieve_without_complete_err'.$this->ip.',成就未完成不能领取奖励');
            $this->CI->output_json_return('achieve_without_complete_err');
        }
        if ($comp_info['receive'] == 1) {
            log_message('error', 'do_reward_achieve:achieve_down_receive_err'.$this->ip.',成就奖励已经领取，不能重复领取');
            $this->CI->output_json_return('achieve_down_receive_err');
        }
        // 领取奖励操作
        $this->CI->task_model->start();
        $m_info     = $this->CI->utility->get_manager_info(array('uuid'=>$uuid));
        $where_1    = array('idx'=>$uuid,'status'=>1);
        $fields_1   = array(
            'achievement'   =>$comp_info['achievepoint'] + $m_info['achievement'],
            'euro'          =>$comp_info['euro'] + $m_info['euro'],
            'tickets'       =>$comp_info['tickets'] + $m_info['tickets'],
            'soccer_soul'   =>$comp_info['soccer_soul'] + $m_info['soccer_soul'],
            'powder'        =>$comp_info['powder'] + $m_info['powder'],
        );
        $upt_m  = $this->CI->utility->update_m_info($fields_1,$where_1);
        if (!$upt_m) {
            $this->CI->task_model->error();
            log_message('error', 'do_reward_achieve:m_info_update_err，领取奖励，经理信息更新失败');
            $this->CI->output_json_return('m_info_update_err');
        }
        if ($comp_info['prop_info']) {
            $prop = explode("|", trim($comp_info['prop_info'],"|"));
            foreach ($prop as $v) {
                $arr    = explode(":", $v);
                $ist_prop = $this->CI->utility->insert_prop_info(array('uuid'=>$uuid,'prop_no'=>$arr[0]),$arr[1]);
                if (!$ist_prop) {
                    $this->CI->task_model->error();
                    log_message('error', 'do_reward_achieve:insert_prop_err，道具奖励时，插入失败');
                    $this->CI->output_json_return('insert_prop_err');
                }
            }
        }
        if ($comp_info['gem_info']) {
            $gem = explode("|", trim($comp_info['gem_info'],"|"));
            foreach ($gem as $v) {
                $arr        = explode(":", $v);
                $ist_gem    = $this->CI->utility->insert_gem_info(array('uuid'=>$uuid,'gem_no'=>$arr[0],'num'=>$arr[1]));
                if (!$ist_gem) {
                    $this->CI->task_model->error();
                    log_message('error', 'do_reward_achieve:insert_gem_err，宝石奖励时，插入失败');
                    $this->CI->output_json_return('insert_gem_err');
                }
            }
        }
        if($comp_info['player_info']) {
            $player = explode("|", trim($comp_info['player_info'],"|"));
            foreach ($player as $v) {
                $arr    = explode(":", $v);
                for($i=$arr[2];$i>0;$i--) {
                    $ist_player = $this->CI->utility->insert_player_info(array('uuid'=>$uuid,'player_no'=>$arr[0],'level'=>$arr[1]));
                    if (!$ist_player) {
                        $this->CI->task_model->error();
                        log_message('error', 'do_reward_achieve:insert_player_err，球员卡奖励时，插入失败');
                        $this->CI->output_json_return('insert_player_err');
                    }
                }
            }
        }
        if($comp_info['equipt_info']) {
            $equipt = explode("|", trim($comp_info['equipt_info'],"|"));
            foreach ($equipt as $v) {
                $arr    = explode(":", $v);
                for($i=$arr[2];$i>0;$i--) {
                    $ist_equipt = $this->CI->utility->insert_equipt_info(array('uuid'=>$uuid,'equipt_no'=>$arr[0],'level'=>$arr[1]));
                    if (!$ist_equipt) {
                        $this->CI->task_model->error();
                        log_message('error', 'do_reward_achieve:insert_equipt_err，装备奖励时，插入失败');
                        $this->CI->output_json_return('insert_equipt_err');
                    }
                }
            }
        }
        // 更新成就完成表状态
        $fields_2   = array('receive'=>1);
        $where_2    = array('idx'=>$comp_info['id'],'status'=>1);
        $upt_res    = $this->CI->task_model->update_data($fields_2,$where_2,'achievement_complete');
        if (!$upt_res) {
            $this->CI->task_model->error();
            log_message('error', 'do_reward_achieve:achieve_reward_receive_err,成就奖励领取失败');
            $this->CI->output_json_return('achieve_reward_receive_err');
        }
        $this->CI->task_model->success();
        return true;
    }
    
    /**
     * 成就-经理模块-成长轨迹（经理升级时触发）
     */
    public function achieve_mupgrade($uuid,$m_level)
    {
        $cat_no = $this->achieve_condition['MUPGRADE_CATNO'];
        $result = $this->trigger_achievement($uuid,$cat_no,$m_level);
        return $result;
    }
    
    /**
     * 成就-经理模块-战力达人（战斗力达到一定值触发）
     */
    public function achieve_fighting($uuid)
    {
        // 统计经理战斗力
        $this->load_library('manager_lib');
        $fighting   = $this->CI->manager_lib->m_fighting($uuid)['finghting'];
        $cat_no     = $this->achieve_condition['FIGHTING_CATNO'];
        $result     = $this->trigger_achievement($uuid,$cat_no,$fighting);
        return $result;
    }
    
    /**
     * 成就-经理模块-天赋大师（经理三层天赋激活数量 达到条件触发）
     * @param type $uuid
     */
    public function achieve_talent($uuid)
    {
        // 获取经理三层天赋激活数量
        $sql    = "SELECT COUNT(idx) AS sum FROM talent WHERE manager_idx = ".$uuid." AND status = 1 AND talent_idx IN (SELECT idx FROM talent_conf WHERE status = 1  AND unlock_level = 25)";
        $list   = $this->CI->task_model->fetch($sql,'row');
        if (!$list['sum']) {
            return true;
        }
        $cat_no = $this->achieve_condition['TALENT_CATNO'];
        $result = $this->trigger_achievement($uuid,$cat_no,$list['sum']);
        return $result;
    }
    
    /**
     * 成就-球队模块-王者之师（上阵7个不同品质的球员，并取得一场常规赛的胜利）
     * @param type $uuid
     * @param type $formation 
     */
    public function manager_formation($uuid , $formation)
    {
        if($formation){
            foreach ($formation['player'] as $k => $v)
            {
                $formation_1w[] = $v['quality'];
            }
            $return = array_count_values($formation_1w);
            if(count($return) == 1){
                $condition = array_keys($return , 7);
                $info = $this->trigger_achievement_all($uuid, $this->achieve_condition['ENTER_CATNO'],$condition[0]);
            }
            return $info;
        }
    }
    
    /**
     * 成就-球队模块-升阶达人（拥有7个+3的紫色品质以上的球员）
     * @param type $uuid
     */
    public function player_upgrade($uuid)
    {
        //经理所有紫色以上的卡
        $select = " t1.`level` as lvl , t2.quality as quality";
        $sql    = "SELECT ".$select." FROM player_info as t1 LEFT JOIN player_lib as t2 ON t1.player_no = t2.player_no WHERE t1.manager_idx = ".$uuid." AND t2.quality >= 3 AND t1.`status` = 1";
        $info   = $this->CI->task_model->fetch($sql,'all');
        foreach ($info as $k => $v)
        {
            $info_1w[] = $v['lvl'];
        }
        $player_lvl = array_count_values($info_1w);
        $return = TRUE;
        //等级超过二级的卡，并且数量超过7
        foreach ($player_lvl as $k => $v)
        {
            if($k > 2 && ($k % 2 != 0) && $v >= 7){
                $return = $this->trigger_achievement_all($uuid, $this->achieve_condition['PUPGRADE_CATNO'],$k);
            }
        }
        return $return;
    }
    
    /**
     * 成就-球队模块-训练大师（开启N个训练位）
     * @param type $uuid
     */
    public function train_unlock($uuid)
    {
        $select = "COUNT(1) as num";
        $sql = "SELECT ".$select." FROM tgunlock_his WHERE manager_idx = ".$uuid." AND `status` = 1";
        $info   = $this->CI->task_model->fetch($sql,'row');
        $return = TRUE;
        if($info['num'] >= 3){
            $return = $this->trigger_achievement_all($uuid, $this->achieve_condition['TRAINGROUND_CATNO'],$info['num']);
        }
        return $return;
    }
    
    /**
     * 成就-球队模块-培养大师（拥有1个任意3个属性达到200的球员）
     * @param type $uuid
     */
    public function player_update($uuid)
    {
        $select = "speed , shoot , free_kick , acceleration , header , control , physical_ability , power , aggressive , interfere , steals , ball_control , pass_ball , mind , reaction , positional_sense , hand_ball";
        $sql = "SELECT ".$select." FROM player_info WHERE manager_idx = ".$uuid." AND `status` = 1";
        $info   = $this->CI->task_model->fetch($sql,'all');
        $attribute_200[3] = 0;
        $attribute_200[0] = 0;
        foreach ($info as $k => $v)
        {
            $attribute_num = 0;
            foreach($v as $key => $value)
            {
                //记录大于200的属性数量
                if($value >= 200){
                    $attribute_num = $attribute_num+1;
                }
            }
            if($attribute_num >= 3){
                $attribute_200[3] = $attribute_200[3] + 1;
                if($attribute_num == 17){
                    $attribute_200[0] = $attribute_200[0] + 1;
                }
            }
        }
        $return = $this->trigger_achievement_all($uuid, $this->achieve_condition['ATTRIBUTE_CATNO'],$attribute_200);
        return $return;
    }

    /**
     * 成就-球队模块-收藏家（激活所有意志）
     * @param type $uuid
     */
    public function unlock_volition($uuid)
    {
        //查询玩家已经激活的意志
        $select = "COUNT(1) as num";
        $sql = "SELECT ".$select." FROM volition_his WHERE manager_idx = ".$uuid." AND `status` = 1 AND is_active = 1";
        $info   = $this->CI->task_model->fetch($sql,'row');
        $return = TRUE;
        //统计玩家解锁意志的个数，全部意志为45个
        if($info['num'] == 45){
            $return = $this->trigger_achievement_all($uuid, $this->achieve_condition['ACTIVE_CATNO'],1);
        }
        return $return;
    }
    
    /**
     * 成就-球队模块-收藏家（激活所有橙 , 金卡图鉴）
     * @param type $uuid
     */
    public function unlock_player($uuid)
    {
        //查询玩家所有橙卡
         $select = "t1.player_no as id";
        $sql    = "SELECT ".$select." FROM player_info as t1 LEFT JOIN player_lib as t2 ON t1.player_no = t2.player_no WHERE t1.manager_idx = ".$uuid." AND t2.quality = 5 AND t1.`status` = 1 GROUP BY t1.player_no";
        $info   = $this->CI->task_model->fetch($sql,'all');
        $return = TRUE;
        //统计玩家橙卡的个数，总数为80个
        if(count($info) == 80){
            $return = $this->trigger_achievement_all($uuid, $this->achieve_condition['ACTIVE_CATNO'],3);
        }
        //查询玩家所有金卡
         $select = "t1.player_no as id";
        $sql    = "SELECT ".$select." FROM player_info as t1 LEFT JOIN player_lib as t2 ON t1.player_no = t2.player_no WHERE t1.manager_idx = ".$uuid." AND t2.quality = 6 AND t1.`status` = 1 GROUP BY t1.player_no";
        $info   = $this->CI->task_model->fetch($sql,'all');
        $return = TRUE;
        //统计玩家金卡的个数，总数为40个
        if(count($info) == 40){
            $return = $this->trigger_achievement_all($uuid, $this->achieve_condition['ACTIVE_CATNO'],4);
        }
        return $return;
    }
    
    /**
     * 成就-球队模块-收藏家（激活所有组合）
     * @param type $uuid
     */
    public function unlock_group($uuid)
    {
        //查询玩家已经激活的意志
        $select = "COUNT(1) as num";
        $sql = "SELECT ".$select." FROM group1 WHERE manager_idx = ".$uuid." AND `status` = 1 AND is_active = 1";
        $info   = $this->CI->task_model->fetch($sql,'row');
        $return = TRUE;
        //统计玩家解锁意志的个数，全部意志为45个
        if($info['num'] == 29){
            $return = $this->trigger_achievement_all($uuid, $this->achieve_condition['ACTIVE_CATNO'],2);
        }
        return $return;
    }

    /**
     * 成就-球队模块-阵型大师（解锁所有球队阵型）
     * @param type $uuid
     */
    public function add_structure($uuid)
    {
        //查询玩家已经激活的阵型
        $select = "COUNT(1) as num";
        $sql = "SELECT ".$select." FROM structure WHERE manager_idx = ".$uuid." AND `status` = 1";
        $info   = $this->CI->task_model->fetch($sql,'row');
        $return = TRUE;
        //统计玩家解锁阵型数，全部意志为7个
        if($info['num'] == 7){
            $return = $this->trigger_achievement_all($uuid, $this->achieve_condition['STRUCTURE_CATNO'],1);
        }
        return $return;
    }
    
    /**
     * 成就-球队模块-装备达人（拥有7套X品质的套装）
     * @param type $uuid
     */
    public function equipt_collect($uuid)
    {
        $return = TRUE;
        //查询各个套装需要的装备ID
        $sql = "SELECT suit_no as id , `name` , jacket_no as top , trousers_no as mid , shoes_no as down FROM equiptsuit_conf";
        $equiptsuit_info   = $this->CI->task_model->fetch($sql,'all');
        //查询玩家各品质的所有装备
        for($i = 1;$i<=4;$i++)
        {
            $select = "t1.manager_idx , t1.equipt_no , t1.`level` , t2.type , t2.quality";
            $sql = "SELECT ".$select." FROM equipt as t1 LEFT JOIN equipt_conf as t2 ON t1.equipt_no = t2.equipt_no AND t1.`level` = t2.`level` WHERE t1.manager_idx = ".$uuid." AND t2.quality = ".$i;
            $info   = $this->CI->task_model->fetch($sql,'all');
            if(count($info)< 21){
                continue;
            }
            $equipt_quality_num = 0;
            foreach($equiptsuit_info as $k => &$v)
            {
                //每个套装的装备数量
                $top_num = 0;
                $mid_num = 0;
                $down_num = 0;
                foreach ($info as $key => $value)
                {
                    //各个位置装备的数量
                    if($v['top'] == $value['equipt_no']){
                        $top_num = $top_num + 1;
                    }
                    if($v['mid'] == $value['equipt_no']){
                        $mid_num = $mid_num + 1;
                    }
                    if($v['down'] == $value['equipt_no']){
                        $down_num = $down_num + 1;
                    }
                }
                //取最小数 为此套装的数量
                $equipt_quality_num = $equipt_quality_num + min($top_num , $mid_num , $down_num);
            }
            if($equipt_quality_num >= 7){
                $return = $this->trigger_achievement_all($uuid, $this->achieve_condition['EQUIPTSUIT_CATNO'],$i);
            }
        }
        return $return;
    }

    /**
     * 成就-球队模块-分解大师（累计分解1000件球员卡/装备/宝石）
     * @param type $uuid
     */
    public function decompose($uuid)
    {
        $select = "decompose1_info as a1 , decompose2_info as a2 , decompose3_info as a3 , decompose4_info as a4 , decompose5_info as a5";
        $sql = "SELECT ".$select." FROM decompose_his WHERE manager_idx = ".$uuid." AND status = 1 LIMIT 0 , 1000";
        $info   = $this->CI->task_model->fetch($sql,'all');
        $count_info = 0;
        foreach ($info as $k => $v)
        {
            foreach($v as $value)
            {
                if($value != ""){
                    $count_info = $count_info + 1;
                }
            }
        }
        $return = $this->trigger_achievement_all($uuid, $this->achieve_condition['DECOMPOSE_CATNO'],$count_info);
        return $return;
    }
    
    /**
     * 成就-球队模块-副本达人
     * @param type $uuid
     */
    public function ckpoint_complete($uuid)
    {
        $select = "manager_idx , copy_no , ckpoint_no , type";
        $sql    = "SELECT ".$select." FROM ckpoint_complete_his WHERE manager_idx = ".$uuid." AND type = 2 AND status = 1 ORDER BY copy_no DESC LIMIT 0 , 1";
        $info   = $this->CI->task_model->fetch($sql,'row');
        $condition[2] = $info['copy_no'] - 100;
        $sql    = "SELECT ".$select." FROM ckpoint_complete_his WHERE manager_idx = ".$uuid." AND type = 1 AND status = 1 ORDER BY copy_no DESC LIMIT 0 , 1";
        $info   = $this->CI->task_model->fetch($sql,'row');
        $condition[1] = $info['copy_no'] - 100;
        $return = $this->trigger_achievement_all($uuid, $this->achieve_condition['COPY_CATNO'],$condition);
        return $return;
    }

    /**
     * 成就-球队模块-天梯王者（天梯排名首次达到X以上）
     * @param type $uuid
     */
    public function ladder_king($uuid , $ranking)
    {
        $cat_no = $this->achieve_condition['LADDER_CATNO'];
        $result = $this->trigger_achievement($uuid,$cat_no,$ranking);
        return $result;
    }

    /**
     * 成就-球队模块-闯关者（天梯排名首次达到X以上）
     * @param type $uuid
     */
    public function fiveleague($uuid , $ranking)
    {
        $cat_no = $this->achieve_condition['PASSLAYER_CATNO'];
        $result = $this->trigger_achievement($uuid,$cat_no,$ranking);
        return $result;
    }

    /*
     * 查询同类型所有未完成成就
     * @param type $uuid
     * @param type $achieve_catno
     * @param type $condition
     */
    public function trigger_achievement_all($uuid,$cat_no,$condition)
    {
        $select = "A.idx as id,A.module_no AS module_no,A.achieve_catno AS achieve_catno,A.name AS name,A.achieve_no AS achieve_no,A.condition AS condition1, A.descript AS descript,A.achievepoint AS achievepoint,A.euro AS euro,A.tickets AS tickets,A.soccer_soul AS soccer_soul,A.powder AS powder,A.prop_info AS prop_info,A.gem_info AS gem_info,A.player_info AS player_info,A.equipt_info AS equipt_info";
        $sql    = "SELECT ".$select." FROM achievement_conf A WHERE achieve_catno = ".$cat_no." AND status = 1 AND achieve_no not in (SELECT achieve_no FROM achievement_complete WHERE manager_idx = ".$uuid." AND status = 1) ORDER BY condition1";
        $info   = $this->CI->task_model->fetch($sql,'all');
        $tips   = 0;
        if($cat_no == 7 || $cat_no == 12){
            foreach($info as $k => &$v)
            {
                $v['condition1'] = explode(':', $v['condition1']);
                if($v['condition1'][0] <= $condition[$v['condition1'][1]]){
                    $v['uuid'] = $uuid;
                    $ist_res   = $this->insert_achievement($v);
                    if (!$ist_res) {
                        return false;
                    }
                    $tips = 1;
                }
            }
        }
        else {
            foreach($info as $k => &$v)
            {
                if($v['condition1'] == $condition){
                    $v['uuid'] = $uuid;
                    $ist_res   = $this->insert_achievement($v);
                    if (!$ist_res) {
                        return false;
                    }
                    $tips = 1;
                }
            }
        }
        
        if($tips){
            //成就页面红点提示
            $this->load_library('tips_lib');
            $this->CI->tips_lib->tip_pages($uuid,1012);
            if($info['module_no'] == 1)
                $this->CI->tips_lib->tip_pages($uuid,1022);
            if($info['module_no'] == 2)
                $this->CI->tips_lib->tip_pages($uuid,1023);
            if($info['module_no'] == 3)
                $this->CI->tips_lib->tip_pages($uuid,1024);
        }
        return true;
    }

    /**
     * 生成成就图标
     * @param int $moudule_no      模块编号(1-3)
     * @param int $achievepoint    成就点数(1-50)
     */
    public function generate_achievement_pic($moudule_no,$achievepoint)
    {
        $pic    = "";
        if ($achievepoint <= 10) {
            $pic    = $moudule_no."-10";
        } elseif($achievepoint <= 30){
            $pic    = $moudule_no."-30";
        } elseif($achievepoint <= 50) {
            $pic    = $moudule_no."-50";
        }
        return $pic;
    }
    
    
}
