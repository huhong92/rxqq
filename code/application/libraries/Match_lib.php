<?php
class Match_lib extends Base_lib {
    public function __construct() {
        parent::__construct();
        $this->load_model('match_model');
    }
    
    /**
     * 获取赛事类型列表
     * @param type $params
     */
    public function match_list($params)
    {
        $options['where']   = array('status'=>1);
        $options['fields']  = "idx AS id,type,name,pic,unlock_level";
        $match_type         = $this->CI->match_model->list_data($options, 'match_type');
        if (!$match_type) {
            log_message('error', 'get_match_type_err:'.$this->ip.',获取赛事类型列表失败');
            $this->CI->output_json_return('match_type_empty');
        }
        $m_level = $this->CI->utility->get_manager_info($params,'level');
        $piclib_url = $this->CI->passport->get('piclib_url');
        $match_pic  = $this->CI->passport->get('match_type');
        foreach ($match_type as $k=>&$v) {
            $v['pic']   = $piclib_url.$match_pic.$v['pic'];
            if ($v['unlock_level'] > $m_level) {
                $v['unlock']    = 0;
            } else {
                $v['unlock']    = 1;
            }
        }
        return $match_type;
    }
    
    /**
     * 副本列表
     * @param type $params
     * @return type
     */
    public function copy_list($params)
    {
        $options['where']   = array('status'=>1);
        $options['fields']  = "idx AS id,copy_no,name,pic";
        $options['order']   = "copy_no ASC";
        $copy_list          = $this->CI->match_model->list_data($options, 'copy_conf');
        if (!$copy_list) {
            log_message('error', 'get_copy_list_err:'.$this->ip.',副本列表数据为空');
            $this->CI->output_json_return('copy_list_empty');
        }
        
        $complete_his   = $this->get_copy_complete_his($params['uuid']);
        $piclib_url     = $this->CI->passport->get('piclib_url');
        $copy_pic       = $this->CI->passport->get('copy');
        if (!$complete_his) {
            foreach ($copy_list as $k=>&$v) {
                $v['pic']   = $piclib_url.$copy_pic.$v['pic'];
                if ($k == 0) {
                    $v['unlock']    = 1;
                } else {
                    $v['unlock']    = 0;
                }
            }
            return $copy_list;
        }
        
        foreach ($copy_list as $k=>&$v) {
            $v['pic']   = $piclib_url.$copy_pic.$v['pic'];
            if ($v['copy_no'] <= $complete_his[0]['copy_no']+1) {
                $v['unlock']    = 1;
            }else {
                $v['unlock']    = 0;
            }
        }
        return $copy_list;
    }
    
    /**
     * 获取关卡列表(不含精英赛)
     */
    public function ckpoint_list($params)
    {
        $options['where']   = array('copy_no'=>$params['copy_no'],'type'=>1,'status'=>1);
        $options['fields']  = "idx AS id,copy_no,ckpoint_no,name,pic";
        $options['order']   = "ckpoint_no ASC";
        $ckpoint_list       = $this->CI->match_model->list_data($options, 'ckpoint_conf');
        if (!$ckpoint_list) {
            log_message('error', 'get_ckpoint_list_err:'.$this->ip.',copy_no='.$params['copy_no'].'关卡列表数据为空');
            $this->CI->output_json_return('ckpoint_list_empty');
        }
        // 关卡完成记录
        $star_curr  = 0;
        $complete_his   = $this->get_ckpoint_complete_his($params['uuid'],$params['copy_no']);
        if (!$complete_his) {
            foreach ($ckpoint_list as $k=>&$v) {// 只有第一关解锁
                if ($k == 0) {
                    $v['unlock']    = 1;
                } else {
                    $v['unlock']    = 0;
                }
                $v['star']          = 0;
            }
        } else {
            foreach ($ckpoint_list as $k=>&$v) {
                $v['unlock']    = 0;
                $v['star']  = 0;
                foreach ($complete_his as $key=>$val) {
                    $i++;
                    if ($v['ckpoint_no'] == $val['ckpoint_no']) {
                        $v['unlock']    = 1;
                        $v['star']      = $val['star'];
                        $star_curr      +=$v['star'];
                    } elseif(count($complete_his)+1 == $v['ckpoint_no']) {
                        $v['unlock']    = 1;
                    }
                }
            }
        }
        $data['list']       = $ckpoint_list;
        $data['star_curr']  = $star_curr;
        $data['star_total'] = 27;
        $data['light_status'] = 0;
        //校验满星奖励是否点亮
        if($star_curr == $data['star_total']){
            $data['light_status'] = 1;
            $where = "copy_no = ".$params['copy_no']." AND manager_idx = ".$params['uuid']." AND status = 1";
            $copy_reward_his = $this->CI->match_model->get_one($where , 'fullstar_his');
            if($copy_reward_his){
                $data['light_status'] = 0;
            }
        }
        //获取满星奖励
        $where = "copy_no = ".$params['copy_no']." AND status = 1";
        $copy_reward = $this->CI->match_model->get_one($where , 'fullstar_conf');
        if(!$copy_reward){
            log_message('error', 'copy_reward_empty:'.$this->ip.',copy_no='.$params['copy_no'].'副本满星奖励数据为空');
            $this->CI->output_json_return('copy_reward_empty');
        }
        $copy_reward_list = $this->CI->utility->get_reward($copy_reward);
        $data['reward'] = $copy_reward_list;
        return $data;
    }
    
    /*
     * 领取副本满星奖励
     */
    public function copy_fullstar($params)
    {
        //获取奖励
        $where = "copy_no = '{$params['copy_no']}' AND status = 1";
        $copy_reward = $this->CI->match_model->get_one($where , 'fullstar_conf');
        if(!$copy_reward){
            log_message('error', 'copy_reward_empty:'.$this->ip.',copy_no='.$params['copy_no'].'副本满星奖励数据为空');
            $this->CI->output_json_return('copy_reward_empty');
        }
        $copy_reward_list = $this->CI->utility->get_reward($copy_reward);
        //查看经理是否已领取
        $where = "copy_no = '{$params['copy_no']}' AND manager_idx = {$params['uuid']} AND status = 1";
        $copy_reward_his = $this->CI->match_model->get_one($where , 'fullstar_his');
        if($copy_reward_his){
            log_message('error', 'copy_reward_his_err:'.$this->ip.',copy_no='.$params['copy_no'].'已领取副本满星奖励');
            $this->CI->output_json_return('copy_reward_his_err');
        }
        
        //查看经理是否满星
        $complete_his   = $this->get_ckpoint_complete_his($params['uuid'],$params['copy_no']);
        $star = 0;
        if($complete_his){
            foreach($complete_his as $k => $v){
                $star = $star + $v['star'];
            }
        }
        $this->CI->match_model->start();
        //满星发奖励
        if($star == 27){
            foreach ($copy_reward_list as $k => $v){
                if($v){
                    //球员奖励
                    if($k == 'player_info'){
                        foreach ($v as $key => $value){
                            $insert['uuid']      = $params['uuid'];
                            $insert['player_no'] = $value['player_no'];
                            $insert['level']     = $value['level'];
                            for($i = 1;$i <= $value['num']; $i++){
                                $res = $this->CI->utility->insert_player_info($insert);
                                if(!$res){
                                    log_message('error', 'copy_reward_get_err:'.$this->ip.',领取满星奖励失败');
                                    $this->CI->match_model->error();
                                    $return['status'] = 'copy_reward_get_err';
                                    return $return;
                                }
                            }
                        }
                    }
                    if($k == 'tickets'){
                        $m_info = $this->CI->utility->get_manager_info($params);
                        $fields = array('tickets'=>$m_info['tickets'] + $v);
                        $where  = array('idx'=>$params['uuid'],'status'=>1);
                        $res = $this->CI->utility->update_m_info($fields,$where);
                        if(!$res){
                            log_message('error', 'copy_reward_get_err:'.$this->ip.',领取满星奖励失败');
                            $this->CI->match_model->error();
                            $return['status'] = 'copy_reward_get_err';
                            return $return;
                        }
                    }
                }
            }
            //记录领取
            $data   = array(
                'manager_idx'   => $params['uuid'],
                'copy_no'       => $params['copy_no'],
                'status'        => 1,
            );
            $res    = $this->CI->match_model->insert_data($data,'fullstar_his');
            if (!$res) {
                $this->CI->match_model->error();
                log_message('error', 'ins_copy_reward_his_err'.$this->ip.',插入满星奖励领取历史失败');
                $this->CI->output_json_return('ins_copy_reward_his_err');
            }
        }
        $this->CI->match_model->success();
        return ;
    }

        /**
     * 关卡详细信息
     * @param type $params
     * @return array
     */
    public function ckpoint_info($params)
    {
        // 获取当前关卡信息（普通赛，精英赛）
        $options['where']   = array('copy_no'=>$params['copy_no'],'ckpoint_no'=>$params['ckpoint_no'],'status'=>1);
        $options['fields']  = "idx AS id,copy_no,ckpoint_no,name,pic,type,structure_no,player_nos,discount,equipt_probable,player_conf_idx";
        $ckpoint_list       = $this->CI->match_model->list_data($options, 'ckpoint_conf');
        if (!$ckpoint_list) {
            log_message('error', 'get_ckpoint_info_err:'.$this->ip.',copy_no='.$params['copy_no'].'关卡详细信息数据为空');
            $this->CI->output_json_return('ckpoint_info_empty');
        }
        // 获取关卡完成记录（战胜记录）
        $complete_his   = $this->get_ckpoint_complete_his($params['uuid'],$params['copy_no'], $params['ckpoint_no']);
        $m_info         = $this->CI->utility->get_manager_info($params);
        $expend_ps      = $this->CI->passport->get('expend_ps');// 消耗的经理体力
        $sweep_total    = $this->CI->passport->get('copy_sweep');// 可扫荡总次数
        $node_type      = 1;
        
        // 判断关卡是否解锁
        $unlock = 1;
        $old_complete_his   = $this->get_ckpoint_complete_his($params['uuid'],$params['copy_no'], ($params['ckpoint_no']-1));
        if (!count($old_complete_his) && $params['ckpoint_no'] != 1) {
            log_message('error', 'ckpoint_no_yet_not_unlock:'.$this->ip.',copy_no='.$params['copy_no'].'ckpoint_no='.$params['ckpoint_no'].'关卡未解锁');
            $this->CI->output_json_return('ckpointno_yet_not_unlock');
        }
        // 设置关卡默认信息
        $nomal['sweep'] = 0;
        $nomal['sweep_total']   = $sweep_total['common'];
        $nomal['sweep_num']     = 0;
        $nomal['consume_ps']    = $expend_ps['common'];
        $nomal['star']          = 0;
        $elite                  = null;
        if (count($ckpoint_list) == 1) {// 只有常规赛，无精英赛
            if (in_array($params['ckpoint_no'], array(3,6,9))) {
                $node_type  = 2;
            }
            if ($complete_his) {// 已完成该场比赛
                $nomal['star']      = $complete_his[0]['star'];
                $nomal['sweep_num'] = $complete_his[0]['sweep_num'];
                // 判断是否可以扫荡（每天免费5次）
                if ($complete_his[0]['star'] >= 3 && $complete_his[0]['sweep_num'] < $sweep_total['common']) {// 可以扫荡操作
                    $nomal['sweep'] = 1;
                }
            }
        } elseif(count($ckpoint_list) == 2){// 既有常规赛，又有精英赛
            $elite_exists   = 1;
            // 精英赛默认数据
            $elite['sweep']         = 0;
            $elite['unlock']        = 0;
            $elite['sweep_total']   = $sweep_total['elite'];
            $elite['sweep_num']     = 0;
            $elite['consume_ps']    = $expend_ps['elite'];
            $elite['star']          = 0;
            if (in_array($params['ckpoint_no'], array(3,6,9))) {
                $node_type  = 2;
            }
            if ($complete_his) {
                foreach ($complete_his as $k=>$v) {
                    if ($v['type'] == 1) {// 常规赛
                        $nomal['sweep_num']     = $v['sweep_num'];
                        $nomal['star']          = $v['star'];
                        $elite['unlock']        = 0;
                        if ($v['star'] >= 3) {
                            $elite['unlock']   = 1;
                            if ($v['sweep_num'] < $nomal['sweep_total'] && $v['sweep_num'] < $sweep_total['common']) {
                                $nomal['sweep'] = 1;
                            }
                        }
                    } else {// 精英赛
                        $elite['sweep_num']     = $v['sweep_num'];
                        $elite['star']          = $v['star'];
                        // 查看精英赛是否允许扫荡（每日免费扫荡次数+vip可购买次数）
                        // 获取经理当前vip，可用球票购买扫荡次数
                        $vip_info   = $this->CI->match_model->get_one(array('level'=>$m_info['vip'],'status'=>1),'vip_conf','elite_sweep');
                        // if ($v['star'] >= 3 &&  $v['sweep_num'] < ($elite['sweep_total'] + (int)$vip_info['elite_sweep'])) {
                        if ($v['star'] >= 3 &&  $v['sweep_num'] < ((int)$vip_info['elite_sweep'])) {
                            $elite['sweep'] = 1;
                        }
                    }
                }
            }
        }
        
        // 获取普通赛奖励
        $nomal['may_get']   = array();
        $where          = array('copy_no'=>$params['copy_no'],'node_type'=>$node_type,'status'=>1);
        $euro_reward    = $this->get_euro_reward($where, "win,draw,lose");
        $nomal['euro']    = (int)$euro_reward['win'];
        $nomal['exp']     = (int)$euro_reward['win'];
        if ($ckpoint_list[0]['player_conf_idx']) {
            $where_2    = array('idx'=>$ckpoint_list[0]['player_conf_idx'],'status'=>1);
            $fields_2   = "player_1,player_2,player_3,player_4,player_5";
            $p_info  = $this->CI->match_model->get_one($where_2,'player_probable_conf',$fields_2);
            foreach ($p_info as $v) {
                $get_player     = explode(":", $v);
                $show_mould    = 0;
                if ($get_player[0]<30000) {
                    $show_mould= 1;// （6:金 5:橙 4:红 3:紫 2:蓝 1:绿）  蓝绿卡展示模板卡
                }
                $nomal['may_get'][]    = array('type'=>1,'no'=>$get_player[0],'level'=>0,'show_mould'=>$show_mould);
            }
        }
        if ($ckpoint_list[0]['equipt_probable']) {
            $equipt_no  = explode("|", trim($ckpoint_list[0]['equipt_probable'],"|"));
            foreach ($equipt_no as $v) {
                $arr = explode(":", $v);
                $nomal['may_get'][]    = array('type'=>2,'no'=>$arr[0],'level'=>1);
            }
        }
        
        // 获取精英赛奖励
        if ($elite_exists == 1) {
            // 获取奖励
            $where              = array('copy_no'=>$params['copy_no'],'node_type'=>3,'status'=>1);
            $euro_reward        = $this->get_euro_reward($where, "win,draw,lose");
            $elite['euro']      = (int)$euro_reward['win'];
            $elite['exp']       = (int)$euro_reward['win'];
            $elite['may_get']   = array();
            if ($ckpoint_list[1]['player_conf_idx']) {
                $where_3    = array('idx'=>$ckpoint_list[1]['player_conf_idx'],'status'=>1);
                $fields_3   = "player_1,player_2,player_3,player_4,player_5";
                $p_info  = $this->CI->match_model->get_one($where_3,'player_probable_conf',$fields_3);
                foreach ($p_info as $v) {
                    $get_player = explode(":", $v);
                    $show_mould = 0;
                    if ($get_player[0]<30000) {
                        $show_mould= 1;// （6:金 5:橙 4:红 3:紫 2:蓝 1:绿）  蓝绿卡展示模板卡
                    }
                    $elite['may_get'][]    = array('type'=>1,'no'=>$get_player[0],'level'=>0,'show_mould'=>$show_mould);
                }
            }
            if ($ckpoint_list[1]['equipt_probable']) {
                $equipt_no  = explode("|", trim($ckpoint_list[1]['equipt_probable'],"|"));
                foreach ($equipt_no as $v) {
                    $arr = explode(":", $v);
                    $elite['may_get'][]    = array('type'=>2,'no'=>$arr[0],'level'=>1);
                }
            }
        }
        $data   = array(
            'copy_no'           => $params['copy_no'],
            'ckpoint_no'        => $params['ckpoint_no'],
            'name'              => $ckpoint_list[0]['name'],
            'unlock'            => $unlock,
            'physical_strenth'  => $m_info['physical_strenth'],
            'nomal'             => $nomal,
            'elite'             => $elite
        );
        return $data;
    }
    
    /**
     * 获取挑战副本完成历史记录
     */
    public function get_copy_complete_his($uuid)
    {
        $options['where']   = array('manager_idx'=>$uuid,'status'=>1);
        $options['fields']  = "idx AS id ,copy_no";
        $options['order']   = "copy_no desc";
        $complete_list      = $this->CI->match_model->list_data($options, 'copy_complete_his');
        return $complete_list;
    }
    
    /**
     * 获取挑战关卡完成历史记录(只有战胜，才能完成该关卡挑战)
     * @param int $uuid
     * @param int $copy_no
     * @return array
     */
    public function get_ckpoint_complete_his($uuid, $copy_no,$ckpoint_no=0)
    {
        $options['where']   = array('manager_idx'=>$uuid,'copy_no'=>$copy_no,'status'=>1);
        if ($ckpoint_no) {
            $options['where']['ckpoint_no'] = $ckpoint_no;
        } else {
            $options['where']['type']       = 1;
        }
        $options['fields']  = "idx AS id ,copy_no,ckpoint_no,type,sweep_num,star";
        $complete_list      = $this->CI->match_model->list_data($options, 'ckpoint_complete_his');
        return $complete_list;
    }
    

    /**
     * 获取经理战队信息(7个上阵球员信息)
     * @param type $uuid
     * @param int $type type = 1:普通阵容type=2天梯阵容
     * @param int $skill_filter 是否需要技能过滤 1需要（赛场上）0不需要
     * @return type
     */
    public function get_m_enter_player($uuid,$type = 1,$skill_filter = 0)
    {
        // 获取经理7个球员信息
        $this->load_library('court_lib');
        $fields         = "A.idx AS id,A.plib_idx AS plib_id,A.level AS level,A.player_no AS player_no,
            A.generalskill_no AS generalskill_no, A.generalskill_level AS generalskill_level, A.exclusiveskill_no AS exclusiveskill_no,
            A.is_use AS is_use,A.position_no AS position_no,A.position_no2 AS position_no2,A.fatigue AS fatigue,
            @fatigue_total := 100 AS fatigue_total,A.speed AS speed, A.shoot AS shoot, A.free_kick AS free_kick,
            A.acceleration AS acceleration,A.header AS header, A.control AS control,A.physical_ability AS physical_ability,
            A.power AS power, A.aggressive AS aggressive,A.interfere AS interfere, A.steals AS steals,A.ball_control AS ball_control,
            A.pass_ball AS pass_ball,A.mind AS mind,A.reaction AS reaction,A.positional_sense AS positional_sense,A.hand_ball AS hand_ball,
            B.pic AS pic, B.name AS name,B.quality AS quality,B.ability AS ability,B.nationality AS nationality, B.club AS club,
            B.birthday AS birthday, B.intro AS intro,B.position_type AS position_type";
        $params         = array('uuid'=>$uuid,'type'=>1,'pagesize'=>7,'offset'=>0,'struc_type'=>$type);// type : 1上阵球员 2闲置球员3所有球员
        $player_list    = $this->CI->court_lib->get_player_list($params,$fields);
        if (count($player_list['list']) != 7) {// 上阵必须为7人
            log_message('error', 'get_m_enter_player:enter_court_7_num_err'.$this->ip.',完成副本关卡挑战插入表失败');
            $this->CI->output_json_return('enter_court_7_num_err');
        }
        foreach ($player_list['list'] as $k=>$v) {
            $player_ = $this->attr_statis_by_playid($v, $uuid, $type);
            if ($skill_filter) {// 过滤技能
                if ($player_['level'] >= 3) {
                    if ($player_['generalskill_no']) {
                        $player_['generalskill_level']   = $player_['level']-2>5?5:$player_['level']-2;
                    }
                    if ($player_['level'] < 7 || !$player_['exclusiveskill_no']) {
                        $player_['exclusiveskill_no']    =0;
                    }
                } else {
                    $player_['generalskill_no']      = 0;
                    $player_['generalskill_level']   =0;
                    $player_['exclusiveskill_no']    = 0;
                }
            }
            $player[]   = $player_;
        }
        
        // 获取经理阵型
        if ($type == 2) {// 天梯阵容
            $condition      = "A.manager_idx = ".$uuid." AND (A.is_use = 2 or A.is_use = 3) AND A.status=1 AND B.status=1";
        } else {// 普通阵容
            $condition      = "A.manager_idx = ".$uuid." AND (A.is_use = 1 or A.is_use = 3) AND A.status=1 AND B.status=1";
        }
        $join_condition = "A.structure_no = B.structure_no";
        $select         = "B.type AS type,B.structure_no AS structure_no";
        $tb_a           = "structure AS A";
        $tb_b           = "structure_conf AS B";
        $structure_info = $this->CI->match_model->get_composite_row_array($condition, $join_condition, $select, $tb_a, $tb_b);
        
        // 获取经理的组合列表
        $condition_1        = "A.manager_idx = ".$uuid." AND A.status=1 AND B.status=1";
        $join_condition_1   = "A.group_idx = B.idx";
        $select_1           = "A.idx AS id,B.condition AS con,B.target_1 AS target_1,B.add_attr_1 AS add_attr_1,B.value_type_1 AS value_type_1,B.add_value_1 AS add_value_1,B.target_2 AS target_2,B.add_attr_2 AS add_attr_2,B.value_type_2 AS value_type_2,B.add_value_2 AS add_value_2,B.cd_target AS cd_target,B.cd AS cd,B.foul_target AS foul_target,B.foul_probable AS foul_probable";
        $tb_a_1             = "group1 AS A";
        $tb_b_1             = "group_conf AS B";
        $group_list         = $this->CI->match_model->get_composite_row_array($condition_1, $join_condition_1, $select_1, $tb_a_1, $tb_b_1,true);
        foreach ($group_list as $k=>$v) {
            if (!$v['condition']) {
                $new_group[]    = $v;
            }
            $condition_arr  = explode('|', trim($v['condition'],'|'));
            foreach ($condition_arr as $key=>$val) {
                if ($val == $structure_info['structure_no']) {
                    $new_group[]    = $v;
                }
            }
        }
        $home = array('player'=>$player,'structure'=>$structure_info['type']);
        if ($new_group) {
            $home['volition_group']   = $new_group;
        }
        return $home;
    }
    
    /**
     * 通过比赛获取比赛结果
     * @params  string $match_key  保存在redis中的key
     */
    public function result_for_match($match_key,$result)
    {
        // 将2对数据传递到redis服务器
        $key            = $this->CI->passport->get('request_match');
        $this->CI->config->load('redis', TRUE, TRUE);
        $redis_server   = $this->CI->config->item('redis')['redis'];
        $res            =  $this->CI->save_redis($key.$match_key, gzencode(json_encode($result)), 300);
        if (!$res) {
            log_message('error', 'result_for_match:redis_server_err'.$this->ip.',redis存储经理和副本比赛信息失败');
            $this->CI->output_json_return('redis_server_err');
        }
        
        // 获取战斗服务器信息, 战斗结果
        $server_info    = $this->random_finght_server();
        $new_key        = $key.$match_key." ".$redis_server['host'].":".$redis_server['port'];
        // $new_key        = $key.$match_key;
        $match_result   = $this->CI->utility->socket_connect($server_info['ip'], $server_info['port'],$new_key);
        if(!$match_result) {
            log_message('error', 'result_for_match:finghting_connect_err'.$this->ip.',战斗服务器链接失败');
            $this->CI->output_json_return('finghting_connect_err');
        }
        $result_arr     = json_decode($match_result, true);
        return $result_arr;
    }
    
    /**
     * 随机获取战斗服务器【IP|Port】
     */
    public function random_finght_server()
    {
        $server_list    = $this->CI->passport->finght_server();
        $key            = array_rand($server_list,1);
        return $server_list[$key];
    }
    
    /**
     * 副本赛(消耗体力)
     */
    public function match_for_copy($params)
    {
        // 判断经理体力是否足够
        $m_info     = $this->CI->utility->get_manager_info($params);
        $expend_ps  = $this->CI->passport->get('expend_ps');
        if ($params['type'] == 1) {
            $expend_ps  = $expend_ps['common'];
        } else {
            $expend_ps  = $expend_ps['elite'];
        }
        if ($m_info['physical_strenth'] < $expend_ps) {
            log_message('error', 'match_for_copy:m_phystrenght_not_enought'.$this->ip.',经理体力不足');
            $this->CI->output_json_return('m_phystrenght_not_enought');
        }
        
        // 当前关卡完成记录
        $where          = array('manager_idx'=>$params['uuid'],'copy_no'=>$params['copy_no'],'ckpoint_no'=>$params['ckpoint_no'],'type'=>$params['type'],'status'=>1);
        $fields         = "idx as id,star,sweep_num";
        $ckpoint_comp   = $this->CI->match_model->get_one($where,'ckpoint_complete_his',$fields);
        if ((!$ckpoint_comp || $ckpoint_comp['star'] < 3) && $params['sweep'] == 1) {
            log_message('error', 'match_for_copy:ckpoint_sweep_not_allow_err'.$this->ip.',该关卡不允许扫荡');
            $this->CI->output_json_return('ckpoint_sweep_not_allow_err');
        }
        
        // 判断扫荡次数
        if ($params['sweep'] == 1) {// 扫荡
            $copy_sweep = $this->CI->passport->get('copy_sweep');
            if ($params['type'] == 1) {// 常规赛
                $sweep_num  = $copy_sweep['common'];
            } else {
                // 获取vip购买精英赛扫荡次数
                $where_5    = array('level'=>$m_info['level'],'status'=>1);
                $vip_conf   = $this->CI->match_model->get_one($where_5,'vip_conf',"elite_sweep");
                $sweep_num  = $copy_sweep['elite']+(int)$vip_conf['elite_sweep'];
            }
            if ($ckpoint_comp['sweep_num'] >= $sweep_num) {
                log_message('error', 'match_for_copy:ckpoint_sweep_not_allow_err'.$this->ip.',该关卡不允许扫荡');
                $this->CI->output_json_return('ckpoint_sweep_not_allow_err');
            }
            // 获取比赛结果
            $where_2    = array('manager_idx'=>$params['uuid'],'copy_no'=>$params['copy_no'], 'ckpoint_no'=>$params['ckpoint_no'], 'type'=>2);
            $fields_2   = array('exp');
            $ckpoint_his= $this->CI->match_model->get_one($where_2, 'ckpoint_his',$fields_2);
            $exp        = $ckpoint_his['exp'];
        } else {// 比赛
            // 1获取经理战队信息 （7个球员信息，经理使用阵型）
            $result['home'] = $this->get_m_enter_player($params['uuid'],1,1);// 1:过滤技能
            //为成就判定保留上阵球员参数
            $achieve_params = $result['home'];
            // 2.获取副本战队信息
            $where_2    = array('copy_no'=>$params['copy_no'], 'ckpoint_no'=>$params['ckpoint_no'], 'type'=>$params['type'], 'status'=>1);
            $fields_2   = array('copy_no','ckpoint_no','name','type','pic','structure_no','player_nos','discount','equipt_probable','player_conf_idx');
            $ckpoint    = $this->CI->match_model->get_one($where_2, 'ckpoint_conf',$fields_2);
            $structure_info2 = $this->CI->match_model->get_one(array('structure_no'=>$ckpoint['structure_no'],'status'=>1), 'structure_conf');
            $p_arr      = explode("|", trim($ckpoint['player_nos'],"|"));
            foreach ($p_arr as $k=>$v) {
                $player         = explode(':', $v);
                $player_info    = $this->attr_statis_by_playerno($player[0],$player[1],$ckpoint['discount'],$ckpoint['structure_no']);
                $player_info['level']        = $player[1];
                if ($player[1] >= 3) {
                    if ($player_info['generalskill_no']) {
                        $player_info['generalskill_level']  = $player[1]-2;
                    }
                    if ($player[1] < 7 || !$player_info['exclusiveskill_no']) {
                        $player_info['exclusiveskill_no']   = 0;
                    }
                } else {
                    $player_info['generalskill_no']     = 0;
                    $player_info['generalskill_level']  = 0;
                    $player_info['exclusiveskill_no']   = 0;
                }
                $player_info['position_no']  = $k;
                $player_2[] = $player_info;
            }
            $result['away']  = array('player'=>$player_2,'structure'=>$structure_info2['type']);
            
            // 到战斗服务进行比赛，获取结果
            $match_key  = $params['uuid']."_".$params['copy_no']."_".$params['ckpoint_no']."_".$params['type'].time();
            $result_arr = $this->result_for_match($match_key,$result);
            // 7.如果result=win,记录比赛胜利记录表
            $exp_info   = $this->CI->passport->get('match_exp');
            if ($result_arr['HomeScore'] < $result_arr['AwayScore']) {
                $match_mark = "lose";// 战败
                $result  = 3;
                $exp        = $exp_info['lose'];
            } elseif($result_arr['HomeScore'] == $result_arr['AwayScore']) {
                $match_mark = "draw";//平局
                $result  = 2;
                $exp        = $exp_info['draw'];
            } else {
                $match_mark = "win";// 战胜
                $result     = 1;
                $exp        = $exp_info['win'];
                if ($result_arr['HomeScore'] >= $result_arr['AwayScore']+3) {
                    $star   = 3;// 得3星
                } elseif($result_arr['HomeScore'] >= $result_arr['AwayScore']+2) {
                    $star   = 2;// 得2星
                } else{
                    $star   = 1;// 得1星
                }
            }
        }
        
        $this->CI->match_model->start();
        // 记录关卡完成挑战 记录
        if ($params['sweep'] == 2) {//1扫荡2挑战
            if (!$ckpoint_comp && $match_mark == "win") {
                $data_1 = array(
                    'manager_idx'   => $params['uuid'],
                    'copy_no'       => $params['copy_no'],
                    'ckpoint_no'    => $params['ckpoint_no'],
                    'type'          => $params['type'],
                    'star'          => $star,
                    'sweep_num'     => 0,// 关卡扫荡次数
                    'status'        => 1,
                );
                $int_res_1    = $this->CI->match_model->insert_data($data_1, 'ckpoint_complete_his');
                if (!$int_res_1) {
                    $this->CI->manager_model->error();
                    log_message('error', 'insert_ckpoint_complete_his_err:'.$this->ip.',完成副本关卡挑战插入表失败');
                    $this->CI->output_json_return('ckpoint_match_err');
                }
            } else if ($ckpoint_comp && $ckpoint_comp['star'] < 3 && $star>=3) {
                $upt_data_1 = array( 'star' => $star);// 修改比赛评星
                $upt_res_1    = $this->CI->match_model->update_data($upt_data_1, $where,'ckpoint_complete_his');
                if (!$upt_res_1) {
                    $this->CI->manager_model->error();
                    log_message('error', 'update_ckpoint_complete_his_err:'.$this->ip.',完成副本关卡挑战，更新扫荡次数失败');
                    $this->CI->output_json_return('ckpoint_sweep_err');
                }
            }
            
            // 记录副本完成挑战 记录
            if ($params['ckpoint_no'] == 9 && $match_mark == "win") {// 副本挑战完成
                $where_3    = array('manager_idx'=>$params['uuid'],'copy_no'=>$params['copy_no'],'status'=>1);
                $res_3      = $this->CI->match_model->get_one($where_3, 'copy_complete_his');
                if (!$res_3) {
                    $data_1 = array(
                        'manager_idx'   => $params['uuid'],
                        'copy_no'       => $params['copy_no'],
                        'status'        => 1,
                    );
                    $int_res_1    = $this->CI->match_model->insert_data($data_1, 'copy_complete_his');
                    if (!$int_res_1) {
                        $this->CI->manager_model->error();
                        log_message('error', 'insert_copy_complete_his_err:'.$this->ip.',完成副本关卡挑战插入表失败');
                        $this->CI->output_json_return('ckpoint_match_err');
                    }
                }
            }  
        } else {// 扫荡
            // 更新扫荡次数
            $where_4    = array('idx' => $ckpoint_comp['id'],'status'=>1);
            $fields_4   = array('sweep_num' => $ckpoint_comp['sweep_num']+1);// 修改扫荡次数
            $upt_res4    = $this->CI->match_model->update_data($fields_4, $where_4,'ckpoint_complete_his');
            if (!$upt_res4) {
                $this->CI->manager_model->error();
                log_message('error', 'update_ckpoint_complete_his_err:'.$this->ip.',完成副本关卡挑战，更新扫荡次数失败');
                $this->CI->output_json_return('ckpoint_sweep_err');
            }
        }
        
        // 8.配置比赛奖励---经验、欧元、装备、球员卡
        if ($params['type'] === 2) {// 精英赛
            $params['node_type']    = 3;
        } elseif (in_array($params['ckpoint_no'], array(3,6,9))) {// 节点赛
            $params['node_type']    = 2;
        } else {
            $params['node_type']    = 1;
        }
       
        // 奖励-欧元
        $where_4    = array('copy_no'=>$params['copy_no'],'node_type'=>$params['node_type'],'status'=>1);
        $fields_4   = "win,draw,lose";
        $euro_info  = $this->get_euro_reward($where_4,$fields_4);
        $euro       = $euro_info[$match_mark];
        
        // 奖励-球员
        $player_data = '';
        if ($ckpoint['player_conf_idx']) {
            $player_no  = $this->get_player_reward($ckpoint['player_conf_idx']);
            $para       = array('uuid'=>$params['uuid'],'player_no'=>$player_no,'manager_name'=>$m_info['name']);
            $res        = $this->CI->utility->insert_player_info($para);
            if (!$res) {
                $this->CI->match_model->error();
                log_message('error', 'insert_player_err:'.$this->ip.',球员奖励插入失败');
                $this->CI->output_json_return('insert_player_err');
            }
            $reward['player']   = array('player_no'=>$player_no,'level'=>0);
            $player_data    .= "player_no:".$player_no.",level:0|";
        }
        
        // 奖励-装备
        $equip_data = '';
        if ($ckpoint['equipt_probable']) {
            $equip_no  = $this->get_equipt_reward($ckpoint['equipt_probable']);
            if ((int)$equip_no) {
                $reward['equipt']   = array('euqipt_no'=>$equip_no,'level'=>1);
                $para_2 = array('uuid'=>$params['uuid'],'equipt_no'=>$equip_no,'manager_name'=>$m_info['name']);
                $res = $this->CI->utility->insert_equipt_info($para_2);
                if (!$res) {
                    $this->CI->match_model->error();
                    log_message('error', 'insert_equipt_err:'.$this->ip.',装备奖励插入失败');
                    $this->CI->output_json_return('insert_equipt_err');
                }
                $equip_data .= "equipt_no:".$equip_no.",level:0|";
            }
        }
        
        // 奖励-经验（扣除体力）
        $exp_new    = $m_info['current_exp'] + $exp;
        $this->CI->load->library('manager_lib');
        $exp_info   = $this->CI->manager_lib->exp_belongto_level($exp_new);
        if (!$exp_info) {
            $exp_info['extotal_exp']    = $m_info['total_exp'];
            $exp_info['experience']     = $m_info['upgrade_exp'];
            $exp_info['level']          = $m_info['level'];
        }
        $fields_3   = array('current_exp'=> $exp_new,'euro'=>$m_info['euro']+$euro,'total_exp'=>$exp_info['extotal_exp'],'upgrade_exp'=>$exp_info['experience'],'physical_strenth'=>$m_info['physical_strenth'] - $expend_ps,'level'=>$exp_info['level']);
        $where_3    = array('idx'=>$params['uuid'],'status'=>1);
        $res        = $this->CI->utility->update_m_info($fields_3,$where_3);
        if (!$res) {
            $this->CI->match_model->error();
            log_message('error', 'update_exp_err:'.$this->ip.',经理经验值奖励更新失败');
            $this->CI->output_json_return('update_exp_err');
        }
        
        // 记录比赛历史记录表
        if ($params['sweep'] == 2) {// 挑战
            $match_result_key   = $this->CI->passport->get('match_result');// 比赛结果存放key
            $data_2 = array(
                'manager_idx'   => $params['uuid'],
                'type'          => $params['sweep'],
                'formation'     => $achieve_params['structure']?$achieve_params['structure']:"",
                'copy_no'       => $params['copy_no'],
                'ckpoint_type'  => $params['type'],
                'ckpoint_no'    => $params['ckpoint_no'],
                'exp'           => $exp,
                'euro'          => $euro,
                'equipt_info'   => $equip_data,
                'player_info'   => $player_data,
                'filename'      => $match_result_key.$match_key,
                'status'        => 1,
            );
            $res_4 = $this->CI->match_model->insert_data($data_2,'ckpoint_his');
            if (!$res_4) {
                $this->CI->match_model->error();
                log_message('error', 'match_for_copy:ckpoint_his_insert_err'.$this->ip.',副本赛历史记录插入失败');
                $this->CI->output_json_return('ckpoint_his_insert_err');
            }
        } else {// 扫荡历史记录
            $data_2 = array(
                'manager_idx'   => $params['uuid'],
                'copy_no'       => $params['copy_no'],
                'ckpoint_type'  => $params['type'],
                'ckpoint_no'    => $params['ckpoint_no'],
                'status'        => 1,
                'formation'     => '',
            );
            $res_4 = $this->CI->match_model->insert_data($data_2,'ckpoint_sweep_his');
            if (!$res_4) {
                $this->CI->match_model->error();
                log_message('error', 'match_for_copy:ckpoint_his_insert_err'.$this->ip.',副本赛扫荡历史记录插入失败');
                $this->CI->output_json_return('ckpoint_his_insert_err');
            }
        }
        $this->CI->match_model->success();
        
        // 9.返回数据
        if ($params['sweep'] == 1) {// 扫荡
            $this->CI->output_json_return();
        }
        $reward['euro'] = $euro;
        $reward['exp']  = $exp;
        $descript   = $this->CI->utility->get_result_match($match_result_key.$match_key,1);
        $data   = array(
            'result'        => $result,
            'score'         => $result_arr['HomeScore'].":".$result_arr['AwayScore'],
            'match_type'    => 1,// 1副本赛2天梯赛3五大联赛
            'chall_id'      => $params['uuid'],
            'chall_name'    => $m_info['name'],// 挑战者name
            'chall_pic'     => $m_info['team_logo'],// 挑战者图标
            'bechall_type'  => 2,// 被挑战者类型 1经理2副本3机器人
            'bechall_id'    => $ckpoint['copy_no']."_".$ckpoint['ckpoint_no'],// 被挑战者id
            'bechall_name'  => $ckpoint['name'],// 被挑战者name
            'bechall_pic'   => $ckpoint['pic'],// 被挑战者图标
            'reward'        => $reward,
            'descript'      => $descript,
        );
        
        //触发任务 副本赛
        $this->CI->utility->get_task_status($params['uuid'] , 'match_for_copy');
        $this->load_library('task_lib');
        //胜利触发
        if($result == 1){
            // 触发成就 - 经理阵型
            $this->CI->task_lib->manager_formation($params['uuid'] , $achieve_params);
            // 触发成就 - 副本达人
            $this->CI->task_lib->ckpoint_complete($params['uuid']);
            //胜利并且3星 触发新手教程
            if($star == 3 && $params['ckpoint_no'] == 3){
                $this->CI->task_lib->n_c_public_complete($params['uuid'] , 9);
            }
        }
        //失败触发新手引导 17 第一次失败【平局也算输】
        if($result == 3 || $result == 2){
            $this->CI->task_lib->n_c_public_complete($params['uuid'] , 17);
        }
        //触发新手引导 6 完成两场比赛分解
        $this->CI->task_lib->second_match($params['uuid'] , 6);
        return $data;
    }
    
    /**
     * 获取副本赛-欧元奖励
     * @param type $where
     * @param type $fields
     * @return type
     */
    public function get_euro_reward($where,$fields="*")
    {
        $euro_info  = $this->CI->match_model->get_one($where,'reward_eurocopy_conf',$fields);
        return $euro_info;
    }
    
    /**
     * 获取副本赛-球员奖励
     * @return int player_no 球员编号
     */
    public function get_player_reward($idx)
    {
        // 获取掉落球员概率
        $where  = array('idx'=>$idx,'status'=>1);
        $fields = "player_1,player_2,player_3,player_4,player_5";
        $pro_info   = $this->CI->match_model->get_one($where,'player_probable_conf',$fields);
        $pro_array  = array();
        foreach ($pro_info as $k=>$v) {
            $arr = explode(":", $v);
            if (array_key_exists($arr[0],$pro_array)) {
                $pro_array[$arr[0]] = $pro_array[$arr[0]]+$arr[1];
            } else {
                $pro_array[$arr[0]] = $arr[1];
            }
            $total_value    += $arr[1];
        }
        $rand   = mt_rand(1, $total_value);// 随机数
        foreach ($pro_array as $k=>$v) {
            if ($rand <= $v) {
                $player_no  = $k;
                return $player_no;
            } else {
                $rand =$rand - $v;
            }
        }
    }
    
    /**
     * 获取副本赛-装备奖励
     * @param string $equipt_probable  201:20|装备编号:掉落概率
     */
    public function get_equipt_reward($equipt_probable)
    {
        $equipt_info    = explode("|", trim($equipt_probable,"|"));
        $pro_array      = array();
        foreach ($equipt_info as $k=>$v) {
            $arr = explode(':',$v);
            if (array_key_exists($arr[0],$pro_array)) {
                $pro_array[$arr[0]] = $pro_array[$arr[0]]+$arr[1];
            } else {
                $pro_array[$arr[0]] = $arr[1];
            }
            $total_value    += $arr[1];
        }
        $pro_array['no_equipt']  = 100-$total_value;
        $rand   = mt_rand(1,100);
        foreach ($pro_array as $k=>$v) {
            if ($rand<=$v) {
                $equipt_no  = $k;
                return $equipt_no;
            } else {
                $rand = $rand-$v;
            }
        }
    }
    
    /**
     * 获取经理球员属性值统计(阵型+装备+宝石+训练+意志+天赋（TODO）)
     * @param bigint $player_id 球员id
     * @param int $type 阵容类型 1普通阵容2天梯阵容
     * 所有的属性加成百分比，都在基础数据上(包含升阶后的)
     */
    public function attr_statis_by_playid($player_info,$uuid, $type= 1)
    {
        // 球员基本属性
        $this->CI->load->library('court_lib');
        $player         = $this->CI->utility->recombine_attr($player_info,$type);
        // 获取经理使用阵型--属性加成（数值类型：百分比）
        if ($type == 2) {// 天梯阵容
            $player['position_no']  = $player['position_no2'];
            $condition              = "A.manager_idx =".$uuid." AND (A.is_use = 2 or A.is_use = 3) AND A.status=1 AND B.status = 1";
        } else {// 普通阵容
            $condition              = "A.manager_idx =".$uuid." AND (A.is_use = 1 or A.is_use = 3) AND A.status=1 AND B.status = 1";
        }
        $join_condition = "A.structure_no=B.structure_no";
        $select         = "A.structure_no AS structure_no,B.type AS type,B.attradd_object AS attradd_object,B.attradd_percent AS attradd_percent";
        $structure_info = $this->CI->match_model->get_composite_row_array($condition, $join_condition, $select, 'structure AS A', 'structure_conf AS B');
        
          // add_obj:(1前锋2中场3后卫4全体)
        if ($structure_info['attradd_object'] === 4) {
            $attribute_new  = $this->CI->utility->attribute_change($player['attribute'],3,2,$structure_info['attradd_percent'],0,$player['attribute']);
        }elseif ((int)$player_info['position_type'] === (int)$structure_info['attradd_object']) {
            $attribute_new  = $this->CI->utility->attribute_change($player['attribute'],3,2,$structure_info['attradd_percent'],0,$player['attribute']);
        } else {
            $attribute_new  = $player['attribute'];
        }
        
        // 获取经理天赋属性加成(数值类型：百分比)TODO
        
        
        // 获取球员属性加成：装备-宝石-训练（数值类型）
        $attribute_new      = $this->CI->court_lib->get_player_attribute_sum($attribute_new,$player['id'],2,2);
        // 获取意志--属性加成(百分比)
        $volition_info = $this->CI->match_model->get_one(array('manager_idx'=>$uuid,'is_active'=>1,'status'=>1),'volition');
        if ($volition_info) {
            $vconf_info = $this->CI->match_model->get_one(array('idx'=>$volition_info['volition_idx'],'status'=>1),'volition_conf','add_attr,add_value');
            $attr_name  = $this->CI->utility->get_attribute_by_no($vconf_info['add_attr']);
            $value_new  = $this->CI->utility->attribute_change(array($attr_name['name']=>$attribute_new[$attr_name['name']]),3,2,$vconf_info['add_value'],0,$player['attribute']);
            $attribute_new[$attr_name['name']]  = $value_new[$attr_name['name']];
        }
        
        // 返回加成后的属性及球员信息
        $player['attribute']   = $attribute_new;
        return $player;
    }
    
    /**
     * 获取副本球员属性值统计
     */
    public function attr_statis_by_playerno($player_no,$player_level,$discount,$structure_no)
    {
        // 副本球员--基本属性值
        $this->CI->load->library('court_lib');
        $player_info    = $this->CI->court_lib->get_player_lib_info(array('player_no'=>$player_no));
        $player         = $this->CI->utility->recombine_attr($player_info);
        $attribute_new  = $this->CI->utility->attribute_change($player['attribute'],2,2,$discount,$player_level);
        // 副本阵型---属性加成
        $structure_info = $this->CI->match_model->get_one(array('structure_no'=>$structure_no,'status'=>1), 'structure_conf');
        if ($structure_info['attradd_object'] === 4) {
            $attribute_new  = $this->CI->utility->attribute_change($attribute_new,3,2,$structure_info['attradd_percent'],0,$attribute_new);
        }elseif ((int)$player_info['position_type'] === (int)$structure_info['attradd_object']) {
            $attribute_new  = $this->CI->utility->attribute_change($attribute_new,3,2,$structure_info['attradd_percent'],0,$attribute_new);
        }
        
        $player['attribute']   = $attribute_new;
        return $player;
    }
    
    /**
     * 天梯赛
     * @param type $params
     */
    public function match_for_ladder($params)
    {
        // 判断经理耐力是否足够
        $ladder = $this->CI->passport->get('ladder');
        $m_info = $this->CI->utility->get_manager_info($params);
        if ($m_info['endurance'] < $ladder['expend_endurance']) {
            log_message('error', 'match_for_ladder:endurance_not_enought_err'.$this->ip.',经理耐力不足');
            $this->CI->output_json_return('endurance_not_enought_err');
        }
        // 判断是否天梯赛是否解锁
        $where      = array('type'=>2,'status'=>1);
        $fields     = "unlock_level";
        $match_type = $this->CI->match_model->get_one($where,'match_type',$fields);
        if ($m_info['level'] < $match_type['unlock_level']) {
            log_message('error', 'match_for_ladder:match_not_unlock_err'.$this->ip.',经理等级不足，五大联赛未解锁');
            $this->CI->output_json_return('match_not_unlock_err');
        }
        // 判断经理是否可以挑战该账号 是否闲置状态
        $where1              = array('idx'=>$params['id'],'status'=>1);
        $fields1            = "idx as id,manager_idx,type,ranking,match_status";
        $bechall_ranking    = $this->CI->match_model->get_one($where1,'ladder_ranking',$fields1);
        if ($bechall_ranking['match_status'] == 1) {
            log_message('error', 'match_for_ladder:bechallenger_isdoing_err'.$this->ip.',被挑战者正在比赛,不允许被挑战');
            $this->CI->output_json_return('bechallenger_isdoing_err');
        }
        
        // 查看经理排行
        $where2     = array('manager_idx'=>$params['uuid'],'type'=>1,'status'=>1);
        $fields2    = "idx as id,manager_idx,type,ranking,match_status";
        $m_ranking  = $this->CI->match_model->get_one($where2,'ladder_ranking',$fields2);
        if ($m_ranking['match_status'] == 1) {
            log_message('error', 'match_for_ladder:is_bechallenger_err'.$this->ip.',你当前被其他玩家挑战,暂不允许挑战其他玩家');
            $this->CI->output_json_return('is_bechallenger_err');
        }
        $ranking_curr   = (int)$m_ranking['ranking'];// 默认当前名次
        $ranking_       = (int)$m_ranking['ranking'];// 比赛之后排名
        $result['home']    = $this->get_m_enter_player($params['uuid'],2,1);// 天梯赛：使用天梯阵容
        
        // 获取被挑战者战队信息
        if ($bechall_ranking['type'] == 1) {// 经理
            $result['away'] = $this->get_m_enter_player($bechall_ranking['manager_idx'],2,1);
            $para           = array('uuid'=>$bechall_ranking['manager_idx']);
            $om_info        = $this->CI->utility->get_manager_info($para);
            $name           = $om_info['name'];
            $pic            = $om_info['team_logo'];
            $mail_data      = array('insert'=>1,'p_name'=>$m_info['name'],'c_name'=>$name,'manager_idx'=>$bechall_ranking['manager_idx']);
        } else {
            // 获取机器人信息
            $where3         = array('idx'=>$bechall_ranking['manager_idx'],'status'=>1);
            $fields3        = "idx as id,rebot_no,name,pic,structure_no,player_nos";
            $rebot_conf     = $this->CI->match_model->get_one($where3,'rebot_conf',$fields3);
            $name           = $rebot_conf['name'];
            $pic            = $rebot_conf['pic'];
            // 获取机器人使用的阵型信息
            $where4         = array('structure_no'=>$rebot_conf['structure_no'],'status'=>1);
            $fields4        = "type,attradd_object,attradd_percent";
            $structure_info = $this->CI->match_model->get_one($where4, 'structure_conf',$fields4);
            $p_arr      = explode("|", trim($rebot_conf['player_nos'],"|"));
            foreach ($p_arr as $k=>$v) {
                $player         = explode(':', $v);
                $player_info    = $this->attr_statis_by_playerno($player[0],$player[1],100,$rebot_conf['structure_no']);
                $player_info['level']        = $player[1];
                if ($player[1] >= 3) {
                    if ($player_info['generalskill_no']) {
                        $player_info['generalskill_level']  = $player[1]-2;
                    }
                    if ($player[1] < 7 || !$player_info['exclusiveskill_no']) {
                        $player_info['exclusiveskill_no']   = 0;
                    }
                } else {
                    $player_info['generalskill_no']     = 0;
                    $player_info['generalskill_level']  = 0;
                    $player_info['exclusiveskill_no']   = 0;
                }
                $player_info['position_no']  = $k;
                $player_2[] = $player_info;
            }
            $result['away']  = array('player'=>$player_2,'structure'=>$structure_info['type']);
        }
        
        $this->CI->match_model->start();
        // 比赛开始之前，更新比赛状态
        $data2_ = array(
            array('idx'=> $bechall_ranking['id'] , 'match_status' => 1),
            array('idx'=> $m_ranking['id'],'match_status' => 1)
        );
        $upt_res = $this->CI->match_model->update_batch( $data2_, 'idx','ladder_ranking');
        if (!$upt_res) {
            $this->CI->match_model->error();
            log_message('error', 'match_for_league:match_status_update_err'.$this->ip.',比赛状态更新失败');
            $this->CI->output_json_return('match_status_update_err');
        }
        
        // 进行比赛，获取比赛结果
        $match_key  = $params['uuid']."_ladder_".$params['id']."_".time();
        $result_arr = $this->result_for_match($match_key,$result);
        
        // 根据比赛，获取比赛奖励
        $reward['honor']    = 0;
        $reward['euro']     = 0;
        $reward['tickets']  = 0;
        if ($result_arr['HomeScore'] > $result_arr['AwayScore']) {
            $result             = 1;
            $reward['honor']    = $ladder['honor_reward'];
            $reward['euro']     = $ladder['euro_reward'];    
            if ($ranking_curr > $bechall_ranking['ranking']) {// 挑战前7名|获取经理首次挑战
                $ranking_           =    $bechall_ranking['ranking'];
                // 更新当前排名
                $data = array(
                    array(
                       'idx'        => $m_ranking['id'],
                       'ranking'    => $bechall_ranking['ranking'],
                       'type'       => 1
                    ),
                    array(
                       'idx'        => $bechall_ranking['id'] ,
                       'ranking'    => $ranking_curr,
                       'type'       => $bechall_ranking['type']
                    )
                 );
                $upt_res = $this->CI->match_model->update_batch( $data, 'idx','ladder_ranking'); 
                if (!$upt_res) {
                    $this->CI->match_model->error();
                    log_message('error', 'match_for_league:ranking_update_err'.$this->ip.',经理排名更新失败');
                    $this->CI->output_json_return('ranking_update_err');
                }
                
                // 判断是否跨新区（s1-s7）
                $sql    = "SELECT stage,`order`,low_order,high_order,tickets FROM laddercrossr_conf WHERE ".$bechall_ranking['ranking']." <= low_order AND ".$bechall_ranking['ranking']." >= high_order AND  status = 1";
                $cross  = $this->CI->match_model->fetch($sql,'row');
                $stage  = (int)$cross['stage'];
                if ($stage) {
                    // 判断是否首次跨新stage
                    $sql    = "SELECT stage FROM laddercrossstage_his WHERE stage <= $stage AND  status = 1";
                    $cross_first  = $this->CI->match_model->fetch($sql,'row');
                    if(!$cross_first) {// 是首次跨新stage
                        $reward['tickets']  = $cross['tickets'];// 首次跨新区奖励球票
                        // 插入首次跨新区表
                        $data4  = array(
                            'manager_idx'   => $params['uuid'],
                            'stage'         => 5,
                            'order'         => $cross['order'],
                            'low_order'     => $cross['low_order'],
                            'high_order'    => $cross['high_order'],
                            'ranking'       => $bechall_ranking['ranking'],
                            'tickets'       => $reward['tickets'],
                            'status'        => 1,
                        );
                        $res = $this->CI->match_model->insert_data($data4,'laddercrossstage_his');
                        if (!$res) {
                            $this->CI->match_model->error();
                            log_message('error', 'match_for_league:cross_stage_his_err'.$this->ip.',跨新区段历史记录插入失败');
                            $this->CI->output_json_return('cross_stage_his_err');
                        }
                    }
                }
            }
        } elseif($result_arr['HomeScore'] == $result_arr['AwayScore']) {
            $result = 2;
        } else {
            $result = 3;
        }
        
        // 更新经理奖励-球票、欧元、荣誉
        $fields6    = array('euro'=>$m_info['euro']+$reward['euro'],'honor'=>$m_info['honor']+$reward['honor'],'tickets'=>$m_info['tickets']+$reward['tickets'],'endurance'=>$m_info['endurance'] - $ladder['expend_endurance']);
        $where6     = array('idx'=>$params['uuid'],'status'=>1);
        $res        = $this->CI->utility->update_m_info($fields6,$where6);
        if (!$res) {
            $this->CI->match_model->error();
            log_message('error', 'match_for_league:update_exp_err'.$this->ip.',经理荣誉欧元奖励更新失败');
            $this->CI->output_json_return('update_exp_err');
        }
        
        // 记录比赛历史记录
        $match_result_key   = $this->CI->passport->get('match_result');// 比赛结果存放key
        $data3      = array(
            'manager_idx'       => $params['uuid'],
            'bechallenger_type' => $bechall_ranking['type'],
            'bechallenger_id'   => $bechall_ranking['id'],
            'result'            => $result,
            'ranking'           => $ranking_curr,
            'ranking_curr'      => $ranking_,
            'honor'             => $reward['honor'],
            'euro'              => $reward['euro'],
            'tickets'           => $reward['tickets'],
            'filename'          => $match_result_key.$match_key,
            'status'            => 1,
        );
        $ist_res    = $this->CI->match_model->insert_data($data3,'ladder_his');
        if (!$ist_res) {
            $this->CI->match_model->error();
            log_message('error', 'match_for_ladder:match_his_record_err'.$this->ip.',天梯塞历史记录插入失败');
            $this->CI->output_json_return('match_his_record_err');
        }
        
        // 比赛完成之后，更新比赛状态
        $data5 = array(
            array('idx'=> $m_ranking['id'],'match_status'    => 0),
            array('idx'=> $bechall_ranking['id'] , 'match_status' => 0)
         );
        $upt_res = $this->CI->match_model->update_batch($data5, 'idx','ladder_ranking'); 
        if (!$upt_res) {
            $this->CI->match_model->error();
            log_message('error', 'match_for_league:match_status_update_err'.$this->ip.',比赛状态更新失败');
            $this->CI->output_json_return('match_status_update_err');
        }
        $this->CI->match_model->success();
        
        // 返回比赛结果信息
        // 比赛录像
        $result_key = $this->CI->passport->get('match_result');
        $descript   = $this->CI->utility->get_result_match($result_key.$match_key,2);
        $data       = array(
            'result'        => $result,
            'score'         => $result_arr['HomeScore'].":".$result_arr['AwayScore'],
            'match_type'    => 2,// 1副本赛2天梯赛3五大联赛
            'chall_id'      => $params['uuid'],
            'chall_name'    => $m_info['name'],// 挑战者name
            'chall_pic'     => $m_info['team_logo'],// 挑战者图标
            'chall_type'    => 1,// 挑战者类型 1经理2副本3机器人
            'bechall_type'  => $bechall_ranking['type']== 2?3:1,// 被挑战者类型 1经理2副本3机器人
            'bechall_id'    => $bechall_ranking['manager_idx'],// 被挑战者id
            'bechall_name'  => $name,// 被挑战者name
            'bechall_pic'   => $pic,// 被挑战者图标
            'ranking'       => $ranking_curr,
            'ranking_curr'  => $ranking_,
            'reward'        => $reward,
            'descript'      => $descript,
        );
        
        //触发任务 天梯赛
        $this->CI->utility->get_task_status($params['uuid'] , 'match_for_ladder');
        //触发成就 - 天梯王者
        $this->load_library('task_lib');
        $this->CI->task_lib->ladder_king($params['uuid'] , $ranking_);
        //胜利触发新手引导 16 第一场天梯赛
        if($result == 1){
            $this->CI->task_lib->n_c_public_complete($params['uuid'] , 16);
        }
        //失败触发新手引导 17 第一次失败【平局也算输】
        if($result == 3 || $result == 2){
            $this->CI->task_lib->n_c_public_complete($params['uuid'] , 17);
        }
        
        // 邮件通知，比赛
        if ($mail_data['insert'] == 1) {
            $data['descript']   = '';
            $this->load_library('mail_lib');
            $this->CI->mail_lib->insert_mail(array('link'=>$match_result_key.$match_key,'title'=>'天梯赛录像','content'=>$mail_data['p_name'].$result_arr['HomeScore'].":".$result_arr['AwayScore'].$mail_data['c_name'],'manager_idx'=>$mail_data['manager_idx'],'data'=>  json_encode($data)));
        }
        $data['descript']   = $descript;
        return $data;
    }
    
    /**
     * 五大联赛
     * @param type $params
     * @return type
     */
    public function match_for_league($params)
    {
        // 判断是否五大联赛是否解锁
        $m_info = $this->CI->utility->get_manager_info($params);
        if (!$params['is_sweep']) {// 非扫荡操作
            $where      = array('type'=>3,'status'=>1);
            $fields     = "unlock_level";
            $match_type = $this->CI->match_model->get_one($where,'match_type',$fields);
            if ($m_info['level'] < $match_type['unlock_level']) {
                log_message('error', 'match_for_league:match_not_unlock_err'.$this->ip.',经理等级不足，五大联赛未解锁');
                $this->CI->output_json_return('match_not_unlock_err');
            }
            
            // 判断经理是否可挑战该关卡
            $where2         = array('manager_idx'=>$params['uuid'],'status'=>1);
            $fields2        = "ckpoint_no";
            $curr_ckpoint   = $this->CI->match_model->get_one($where2,'fiveleague_curr',$fields2);
            if (!$curr_ckpoint && $params['ckpoint_no'] !== 1) {
                log_message('error', 'match_for_league:ckpoint_not_challenger_err'.$this->ip.',当前关卡不能挑战');
                $this->CI->output_json_return('ckpoint_not_challenger_err');
            } elseif($curr_ckpoint && $curr_ckpoint['ckpoint_no'] != $params['ckpoint_no']) {
                log_message('error', 'match_for_league:ckpoint_not_challenger_err'.$this->ip.',当前关卡不能挑战');
                $this->CI->output_json_return('ckpoint_not_challenger_err');
            } elseif($curr_ckpoint['ckpoint_no'] == 101) {
                log_message('error', 'match_for_league:ckpoint_not_challenger_err'.$this->ip.',当前关卡不能挑战');
                $this->CI->output_json_return('ckpoint_not_challenger_err');
            }
            
            // 获取经理球员信息
            $result['home']    = $this->get_m_enter_player($params['uuid'],1,1);
            // 获取五大联赛关卡信息
            $where3             = array('ckpoint_no'=>$params['ckpoint_no'],'status'=>1);
            $fields3            = "ckpoint_no,name,pic,structure_no,ckpoint_no,player_nos,discount,gem_probable,euro,exp";
            $fiveleague_conf    = $this->CI->match_model->get_one($where3,'fiveleague_conf',$fields3);
            // 五大联赛阵型信息
            $where4             = array('structure_no'=>$fiveleague_conf['structure_no'],'status'=>1);
            $fields4            = "type,attradd_object,attradd_percent";
            $structure_info     = $this->CI->match_model->get_one($where4, 'structure_conf',$fields4);
            $p_arr      = explode("|", trim($fiveleague_conf['player_nos'],"|"));
            foreach ($p_arr as $k=>$v) {
                $player         = explode(':', $v);
                $player_info    = $this->attr_statis_by_playerno($player[0],$player[1],$fiveleague_conf['discount'],$fiveleague_conf['structure_no']);
                $player_info['level']        = $player[1];
                if ($player[1] >= 3) {
                    if ($player_info['generalskill_no']) {
                        $player_info['generalskill_level']  = $player[1]-2;
                    }
                    if ($player[1] < 7 || !$player_info['exclusiveskill_no']) {
                        $player_info['exclusiveskill_no']   = 0;
                    }
                } else {
                    $player_info['generalskill_no']     = 0;
                    $player_info['generalskill_level']  = 0;
                    $player_info['exclusiveskill_no']   = 0;
                }
                $player_info['position_no']  = $k;
                $player_2[] = $player_info;
            }
            $result['away']  = array('player'=>$player_2,'structure'=>$structure_info['type']);
            // 进行比赛，获取比赛结果
            $match_key  = $params['uuid']."_fiveleague_".$params['ckpoint_no']."_".time();
            $result_arr = $this->result_for_match($match_key,$result);
        } else {// 扫荡获取比较结果
            // 获取五大联赛关卡信息
            $where3             = array('ckpoint_no'=>$params['ckpoint_no'],'status'=>1);
            $fields3            = "ckpoint_no,name,pic,structure_no,ckpoint_no,player_nos,discount,gem_probable,euro,exp";
            $fiveleague_conf    = $this->CI->match_model->get_one($where3,'fiveleague_conf',$fields3);
            // 获取比赛历史记录
            $where4             = array('manager_idx'=>$params['uuid'],'ckpoint_no'=>$params['ckpoint_no'],'status'=>1);
            $fields4            = "result";
            $fiveleague_his     = $this->CI->match_model->get_one($where4,'fiveleague_his',$fields4);
            $result             = (int)$fiveleague_his['result'];
        }
        
        
        $this->CI->match_model->start();
        // 根据比赛结果，获得奖励
        $reward['exp']    = 0;
        $reward['euro']   = 0;
        $reward['gem']    = array();
        $insert_gem_info= "";
        if ($result_arr['HomeScore'] >$result_arr['AwayScore'] || $result = 1) {
            $result = 1;
            $reward['exp']  = $fiveleague_conf['exp'];
            $reward['euro'] = $fiveleague_conf['euro'];
            // 掉落宝石
            $gem_string     = $fiveleague_conf['gem_probable'];
            $gem_arr        = explode("|", trim($gem_string,'|'));
            foreach ($gem_arr as $k=>$v) {
                $arr    = explode(":", $v);
                if ($arr[1] >= 100) {// 必掉宝石
                    $reward['gem'][]    = $arr[0];
                    $gem_no             = $arr[0];
                } else {
                    $res = $this->CI->utility->probable_sigle_count($arr[1]);
                    if ($res) {// 掉落宝石
                        $reward['gem'][]    = $arr[0];
                        $gem_no             = $arr[0];
                    }
                }
                // 经理添加宝石信息
                if ($gem_no) {
                    $ist_res = $this->CI->utility->insert_gem_info(array('uuid'=>$params['uuid'],'gem_no'=>$gem_no,'num'=>1));
                    if (!$ist_res) {
                        $this->CI->match_model->error();
                        log_message('error', 'match_for_league:insert_gem_err'.$this->ip.',宝石奖励插入失败');
                        $this->CI->output_json_return('insert_gem_err');
                    }
                }
            }
            // 更新经理经验、欧元值
            $exp_new    = $m_info['current_exp'] + $reward['exp'];
            $this->CI->load->library('manager_lib');
            $exp_info   = $this->CI->manager_lib->exp_belongto_level($exp_new);
            if (!$exp_info) {
                $exp_info['extotal_exp']    = $m_info['total_exp'];
                $exp_info['experience']     = $m_info['upgrade_exp'];
                $exp_info['level']          = $m_info['level'];
            }
            $fields7    = array('current_exp'=> $exp_new,'euro'=>$m_info['euro']+$reward['euro'],'total_exp'=>$exp_info['extotal_exp'],'upgrade_exp'=>$exp_info['experience'],'level'=>$exp_info['level']);
            $where7     = array('idx'=>$params['uuid'],'status'=>1);
            $res        = $this->CI->utility->update_m_info($fields7,$where7);
            if (!$res) {
                $this->CI->match_model->error();
                log_message('error', 'match_for_league:update_exp_err'.$this->ip.',经理经验值奖励更新失败');
                $this->CI->output_json_return('update_exp_err');
            }
            
            // 胜利-更新经理当前关卡表
            if ($params['ckpoint_no'] <= 100) {
                $where9     = array('manager_idx'=>$params['uuid'],'status'=>1);
                $fields9    = "idx";
                $five_curr   = $this->CI->match_model->get_one($where9,'fiveleague_curr',$fields9);
                if ($five_curr) {
                    // 更新当前关卡
                    $fields10   = array('ckpoint_no'=>$params['ckpoint_no']+1);
                    $where10    = array('manager_idx'=>$params['uuid'],'status'=>1);
                    $upt_res    = $this->CI->match_model->update_data($fields10,$where10,'fiveleague_curr');
                    if (!$upt_res) {
                        $this->CI->match_model->error();
                        log_message('error', 'match_for_league:fiveleague_curr_update_err'.$this->ip.',五大联赛当前关卡更新失败');
                        $this->CI->output_json_return('fiveleague_curr_update_err');
                    }
                } else {
                    // 插入当前关卡
                    $data3      = array(
                        'manager_idx'   => $params['uuid'],
                        'ckpoint_no'    => $params['ckpoint_no']+1,
                        'status'        => 1
                    );
                    $ist_res    = $this->CI->match_model->insert_data($data3, 'fiveleague_curr');
                    if (!$ist_res) {
                        $this->CI->match_model->error();
                        log_message('error', 'match_for_league:fiveleague_curr_insert_err'.$this->ip.',五大联赛当前关卡插入失败');
                        $this->CI->output_json_return('fiveleague_curr_insert_err');
                    }
                }
            }
        } elseif($result_arr['HomeScore'] == $result_arr['AwayScore'] || $result == 2) {
            $result = 2;
        } else {
            $result = 3;
        }
        
        // 更新经理最高关卡表
        if (!$params['is_sweep']) {// 打比赛
            $where8     = array('manager_idx'=>$params['uuid'],'status'=>1);
            $fields8    = "idx";
            $five_max   = $this->CI->match_model->get_one($where8,'fiveleague_max',$fields8);
            if ($five_max) {
                // 更新最高关卡
                $fields9    = array('ckpoint_no'=>$params['ckpoint_no']);
                $where9     = array('manager_idx'=>$params['uuid'],'status'=>1);
                $upt_res    = $this->CI->match_model->update_data($fields9,$where9,'fiveleague_max');
                if (!$upt_res) {
                    $this->CI->match_model->error();
                    log_message('error', 'match_for_league:fiveleague_max_update_err'.$this->ip.',五大联赛最高关卡更新失败');
                    $this->CI->output_json_return('fiveleague_max_update_err');
                }
            } else {
                // 插入最高关卡
                $data3      = array(
                    'manager_idx'   => $params['uuid'],
                    'ckpoint_no'    => $params['ckpoint_no'],
                    'status'        => 1
                );
                $ist_res    = $this->CI->match_model->insert_data($data3, 'fiveleague_max');
                if (!$ist_res) {
                    $this->CI->match_model->error();
                    log_message('error', 'match_for_league:fiveleague_max_insert_err'.$this->ip.',五大联赛最高关卡插入失败');
                    $this->CI->output_json_return('fiveleague_max_insert_err');
                }
            }
        }
        
        // 记录比赛历史记录
        if (!$params['is_sweep']) {// 非扫荡操作
            $match_result_key   = $this->CI->passport->get('match_result');// 比赛结果存放key
            $data2  = array(
                'manager_idx'   => $params['uuid'],
                'ckpoint_no'    => $params['ckpoint_no'],
                'result'        => $result,
                'euro'          => $reward['euro'],
                'exp'           => $reward['exp'],
                'gem_info'      => $insert_gem_info,
                'filename'      => $match_result_key.$match_key,
                'status'        => 1,
            );
            $res    = $this->CI->match_model->insert_data($data2,'fiveleague_his');
            if (!$res) {
                $this->CI->match_model->error();
                log_message('error', 'match_for_league:match_his_record_err'.$this->ip.',五大联赛比赛历史记录插入失败');
                $this->CI->output_json_return('match_his_record_err');
            }
        }
        
        $this->CI->match_model->success();
        // 返回比赛结果信息
        if ($params['is_sweep']) {
            return true;
        }
        $result_key = $this->CI->passport->get('match_result');
        $descript   = $this->CI->utility->get_result_match($result_key.$match_key,3);
        $data   = array(
            'result'        => $result,
            'score'         => $result_arr['HomeScore'].":".$result_arr['AwayScore'],
            'match_type'    => 3,// 1副本赛2天梯赛3五大联赛
            'chall_id'      => $params['uuid'],
            'chall_name'    => $m_info['name'],// 挑战者name
            'chall_pic'     => $m_info['team_logo'],// 挑战者图标
            'chall_type'    => 1,// 挑战者类型 1经理2副本3机器人
            'bechall_type'  => 2,// 被挑战者类型 1经理2副本3机器人
            'bechall_id'    => $fiveleague_conf['ckpoint_no'],// 被挑战者id
            'bechall_name'  => $fiveleague_conf['name'],// 被挑战者name
            'bechall_pic'   => $fiveleague_conf['pic'],// 被挑战者图标
            'reward'        => $reward,
            'descript'      => $descript,
        );
        //触发任务 五大联赛
        $this->CI->utility->get_task_status($params['uuid'] , 'match_for_league');
        //触发成就 - 闯关者
        $this->load_library('task_lib');
        //失败触发新手引导 17 第一次失败【平局也算输】
        if($result == 3 || $result == 2){
            $this->CI->task_lib->n_c_public_complete($params['uuid'] , 17);
        }
        $this->CI->task_lib->fiveleague($params['uuid'] , $params['ckpoint_no']);
        return $data;
    }
    
    /**
     * 获取当前经理可挑战的9名玩家（前7 + 后2）
     * @param type $params
     */
    public function challenger_for_ladder($params)
    {
        // 获取经理当前排名
        $where      = array('manager_idx'=>$params['uuid'],'status'=>1,'type'=>1);
        $fields     = "idx as id,manager_idx,type,ranking,match_status";
        $m_ranking  = $this->CI->match_model->get_one($where,'ladder_ranking',$fields);
        if (!$m_ranking) {
            $m_ranking['ranking']   = $this->do_init_ranking_ladder($params);
            $m_ranking['type']      = 1;
        }
        
        // 判断经理当前排名是否大于7(前面正好7人)
        if ($m_ranking['ranking'] > 7) {
            // 根据排名规则获取前7名玩家
                // 判断当前排名属性那个区段
                $ranking_rule   = $this->ranking_belongto_stage($m_ranking['ranking']);
                // 1.计算Z值
                $z  = ($m_ranking['ranking']  - $ranking_rule['value_y'])/$ranking_rule['value_r'];
                // 2.根据Z值，判断使用规则
                $order_string   = "";
                if ($z >= 7) {
                    for($j=1;$j<=7;$j++) {
                        $order_arr[8-$j]  = rand($m_ranking['ranking']-$j* $ranking_rule['value_r'], ($m_ranking['ranking']- ($j-1)*$ranking_rule ['value_r'] -1));
                        $order_string .= $order_arr[8-$j].",";
                    }
                } else {
                    $t  = 7 - floor($z);// 计算T的值
                    // 获取S(a-1)区段的规则
                    $Sa_1_rule   = $this->ranking_belongto_stage($ranking_rule['stage'] - 1,$type = 2);
                    if ($t >= 7) {// 前t个用另一个S（a-1）
                        for($i=7;$i>=1;$i--) {
                            $order_arr[$i]  = rand($ranking_rule['value_y']-$i* $Sa_1_rule['value_r'], ($ranking_rule['value_y']- ($i-1)*$Sa_1_rule ['value_r'] -1));
                            $order_string .= $order_arr[$i].",";
                        }
                    } else {
                        for($i=$t;$i>=1;$i--) {
                            $order_arr[$i]  = rand($ranking_rule['value_y']-$i* $Sa_1_rule['value_r'], ($ranking_rule['value_y']- ($i-1)*$Sa_1_rule ['value_r'] -1));
                            $order_string .= $order_arr[$i].",";
                        }
                        for($j=1;$j<=7-$t;$j++) {
                            $order_arr[8-$j]  = rand($m_ranking['ranking']-$j* $ranking_rule['value_r'], ($m_ranking['ranking']- ($j-1)*$ranking_rule ['value_r'] -1));
                            $order_string .= $order_arr[8-$j].",";
                        }
                    }
                }
                
                // 获取当前排名的经理或者机器人
                $sql            = "SELECT idx AS id,manager_idx,type,ranking,match_status FROM ladder_ranking WHERE status = 1 AND ranking IN(".trim($order_string,",").") ORDER BY ranking";
                $forward_list   = $this->CI->match_model->fetch($sql,'result');
        } else { 
            //直接获取前面的7个排名信息
            if ($m_ranking['ranking'] <= 1) {
                $forward_list   = array();
            } else {
                $options['where']   = array('ranking<'=>$m_ranking['ranking'],'status'=>1);
                $options['fields']  = "idx as id,manager_idx,type,ranking,match_status";
                $options['limit']   = array('size'=>7,'page'=>0);
                $forward_list       = $this->CI->match_model->list_data($options,'ladder_ranking');// 获取可挑战排行榜（前部分）
            }
        }
        // 赛事奖励
        $ladder = $this->CI->passport->get('ladder');
        // 获取后2位选手
        if ($m_ranking['ranking'] == 5230) {// 玩家后面无玩家，最后一名
            $back_list      = array();
        } elseif($m_ranking['ranking'] >= 5220) {// 玩家后面最多只有10个人,取最后2名
            $options['where']   = array('status'=>1,'ranking>'=>$m_ranking['ranking']);
            $options['fields']  = "idx as id,manager_idx,type,ranking,match_status";
            $options['limit']   = array('size'=>2,'page'=>0);
            $options['order']   = "ranking DESC";
            $back_list          = $this->CI->match_model->list_data($options,'ladder_ranking');// 获取可挑战排行榜（前部分）
        } elseif($m_ranking['ranking'] >= 5210) {// 玩家后面最多只有20人,取倒数第10名，和最后1名
            $where1         = array('ranking'=>$m_ranking['ranking']+10,'status'=>1);
            $fields1        = "idx as id,manager_idx,type,ranking,match_status";
            $back_list[]    = $this->CI->match_model->get_one($where1,'ladder_ranking',$fields1);
            $sql            = "SELECT idx AS id,manager_idx,type,ranking,match_status FROM ladder_ranking WHERE status = 1 ORDER BY ranking DESC";
            $back_list[]    = $this->CI->match_model->fetch($sql,'row');
        } else {
            $sql2       = "SELECT idx AS id,manager_idx,type,ranking,match_status FROM ladder_ranking WHERE status = 1 AND ranking IN(".($m_ranking['ranking'] + 10).",".($m_ranking['ranking']+20).")";
            $back_list  = $this->CI->match_model->fetch($sql2,'result');
        }
        
        $this->CI->load->library('manager_lib');
        $data   = array();
        // 挑战榜10名
        $m_ranking['player_type']  = 1;// 1自己2对手
        $list   = array_merge($forward_list,array(0=>$m_ranking),$back_list);
        // 获取战斗力、奖励、以及球员信息
        foreach ($list as $k=>$v) {
            $data_list['id']        = $v['id'];
            $data_list['ranking']   = $v['ranking'];
            $data_list['euro']      = $ladder['euro_reward'];
            $data_list['honor']     = $ladder['honor_reward'];
            if ($v['type'] == 1) {// 经理
                // 获取战斗力
                $p_['uuid'] = $v['manager_idx'];
                $m_info = $this->CI->utility->get_manager_info($p_);
                if ($p_['uuid'] == $params['uuid']) {
                    $my_info    = $m_info;
                }
                $finghting = $this->CI->manager_lib->m_fighting($p_['uuid'],2);
                $data_list['finghting'] = $finghting['finghting'];
                $data_list['player']    = $finghting['player_list'];
                $data_list['level']     = $m_info['level'];
                $data_list['name']      = $m_info['name'];
            } else {// 机器人
                // 获取经理上阵球员
                $rebot_id   = $v['manager_idx'];
                $where2     = array('rebot_no'=>$rebot_id,'status'=>1);
                $fields2    = "idx as id,rebot_no,level,name,pic,structure_no,player_nos";
                $rebot_info = $this->CI->match_model->get_one($where2,'rebot_conf',$fields2);
                if (!$rebot_info) {
                    log_message('error', 'top10_for_ladder:rebot_not_found'.$this->ip.',暂无该机器人配置信息');
                    $this->CI->output_json_return('rebot_not_found');
                }
                $player_arr = explode("|", trim($rebot_info['player_nos'],"|"));
                $sum        = 0;
                $player_rebot   = array();
                foreach ($player_arr as $key=>$val) {
                    $arr                    = explode(":", $val);
                    $player_info            = $this->attr_statis_by_playerno($arr[0],$arr[1],100,$rebot_info['structure_no']);
                    $player_rebot[]         = array('player_no'=>$player_info['player_no'],'level'=>$arr[1],'position_no'=>$key);
                    $data_list['player']    = $player_rebot;
                    $sum += array_sum($player_info['attribute']);
                }
                // 获取战斗力
                $data_list['finghting'] = $sum;
                $data_list['level']     = $rebot_info['level'];
                $data_list['name']      = $rebot_info['name'];
            }
            if ($v['player_type'] == 1) {
                $data_list['type']  = 1;
            } else {
                $data_list['type']  = 2;
            }
            $data['list'][] = $data_list;
        }
        
        // 获取发奖励剩余时间
        $surple         = strtotime(date('Y-m-d 22:00:00')) - time()<0?0:strtotime(date('Y-m-d 22:00:00')) - time();
        // 获取我的排名
        $my_ranking     = (int)$this->my_ranking_for_ladder($params['uuid']);
        $data['info']   = array('my_ranking'=>$my_ranking,'surplus_time'=> $surple,'endurance_total'=>$my_info['endurance_total'],'endurance'=>$my_info['endurance']);
        return $data;
    }
    
    
    /**
     * 获取当前经理可挑战的9名玩家（前7 + 后2）
     * @param type $params
     */
    public function top10_for_ladder($params)
    {
        // 获取前10名
        $options['where']   = array('ranking<'=>11,'status'=>1);
        $options['fields']  = "idx as id,manager_idx,type,ranking,match_status";
        $options['order']   = "ranking ASC";
        $ranking_list       = $this->CI->match_model->list_data($options,'ladder_ranking');
        // 获取战斗力、奖励、以及球员信息
        foreach ($ranking_list as $k=>$v) {
            $data_list['id']        = $v['id'];
            $data_list['ranking']   = $v['ranking'];
            if ($v['type'] == 1) {// 经理
                $m_data['uuid']         =  $v['manager_idx'];
                $r_m_info               = $this->CI->utility->get_manager_info($m_data);
                // 获取战斗力
                $finghting              = $this->CI->manager_lib->m_fighting($m_data['uuid'],2);// 2天梯阵容
                $data_list['player']    = $finghting['player_list'];
                $data_list['finghting'] = $finghting['finghting'];
                $data_list['level']     = $r_m_info['level'];
                $data_list['name']      = $r_m_info['name'];
            } else {// 机器人
                // 获取经理上阵球员
                $rebot_id   = $v['manager_idx'];
                $where2     = array('idx'=>$rebot_id,'status'=>1);
                $fields2    = "idx as id,rebot_no,level,name,pic,structure_no,player_nos";
                $rebot_info = $this->CI->match_model->get_one($where2,'rebot_conf',$fields2);
                if (!$rebot_info) {
                    log_message('error', 'top10_for_ladder:rebot_not_found'.$this->ip.',暂无该机器人配置信息');
                    $this->CI->output_json_return('rebot_not_found');
                }
                $player_arr = explode("|", trim($rebot_info['player_nos'],"|"));
                $sum        = 0;
                foreach ($player_arr as $key=>$val) {
                    $arr                    = explode(":", $val);
                    $player_info            = $this->attr_statis_by_playerno($arr[0],$arr[1],100,$rebot_info['structure_no']);
                    $data_list['player'][]  = array('player_no'=>$player_info['player_no'],'level'=>$arr[1],'position_no'=>$key);
                    $sum += array_sum($player_info['attribute']);
                }
                // 获取战斗力
                $data_list['finghting'] = $sum;
                $data_list['level']     = $rebot_info['level'];
                $data_list['name']      = $rebot_info['name'];
            }
            if ($v['player_type'] == 1) {
                $data_list['type']  = 1;
            } else {
                $data_list['type']  = 2;
            }
            $data['list'][] = $data_list;
            unset($data_list);
        }
        // 获取我当前排名
        $data['my_ranking'] = (int)$this->my_ranking_for_ladder($params['uuid']); 
        return $data;
    }
    
    /**
     * 获取天梯赛我的排名
     */
    public function my_ranking_for_ladder($uuid)
    {
        $where  = array('manager_idx'=>$uuid,'status'=>1,'type'=>1);
        $fields = "ranking";
        $info   = $this->CI->match_model->get_one($where,'ladder_ranking',$fields);
        if (!$info) {
            $ranking    = $this->do_init_ranking_ladder(array('uuid'=>$uuid));
            return $ranking;
        }
        return $info['ranking'];
    }
    
    /**
     * 判断当前排名属于那个区段
     * @param int $condition  当前排名名次|idx
     * @param int $type     查找类型（1：排名名次查找2idx自增索引查找）
     * @return type
     */
    public function ranking_belongto_stage($condition,$type = 1)
    {
        if ($type == 1) {
            $where  = array('high_ranking<=' => $condition,'low_ranking>='=>$condition,'status'=>1);
        } else {
            $where  = array('idx' => $condition);
        }
        $fields = "idx as id,stage,high_ranking,low_ranking,value_y,value_r";
        $result =  $this->CI->match_model->get_one($where,'ladderranking_rule',$fields);
        return $result;
    }
    
    /**
     * 获取五大联赛-当前关卡信息
     */
    public function ckpoint_for_league($params)
    {
        // 获取经理当前可挑战关卡
        $where          = array('manager_idx'=>$params['uuid'],'status'=>1);
        $fields         = "ckpoint_no";
        $curr_ckpoint   = $this->CI->match_model->get_one($where,'fiveleague_curr',$fields);
        if (!$curr_ckpoint) {
            $where_2    = array('ckpoint_no'=>1,'status'=>1);
        } else {
            $where_2    = array('ckpoint_no'=>$curr_ckpoint['ckpoint_no'],'status'=>1);
        }
        $fields_2       = "idx as id,ckpoint_no,pic,league,euro,exp,gem_probable";
        $info           = $this->CI->match_model->get_one($where_2,'fiveleague_conf',$fields_2);
        if (!$info) {
            log_message('error', 'ckpoint_for_league:without_ckpoint_info_err'.$this->ip.',暂无该关卡信息');
            $this->CI->output_json_return('without_ckpoint_info_err');
        }
        $info['reward'] = array('exp'=>$info['exp'],'euro'=>$info['euro']);
        if ($info['gem_probable']) {
            $gem_info       = explode("|", trim($info['gem_probable'],"|"));
            foreach ($gem_info as $k=>$v) {
                $info['may_gem'][]  = explode(":", $v)[0];
            }
        } else {
            $info['may_gem']    = array();
        }
        unset($info['euro']);unset($info['exp']);unset($info['gem_probable']);
        
        // 判断经理需要发费球票数 立即完成扫荡
        $sql        = "SELECT idx id, start_ckno,end_ckno,sweep_time,time FROM fiveleague_sweep_his WHERE manager_idx = ".$params['uuid']." AND status=1 ORDER BY idx DESC";
        $sweep_info = $this->CI->match_model->fetch($sql,'row');
        $info['tickets_sweep']   = 0;
        if ($sweep_info) {
            if (time() < $sweep_info['time'] + $sweep_info['sweep_time']) {//扫荡未完成
                $sweep_league   = $this->CI->passport->get('sweep_league');
                $info['tickets_sweep'] = $sweep_league['tickets'] * ($sweep_info['end_ckno'] - $sweep_info['start_ckno']);
            }
        }
        // 判断经理当前可重置次数
        $time           = strtotime(date('Y-m-d',time()));// 当前日期
        $where_1        = array('manager_idx'=>$params['uuid'],'time>='=>$time,'status'=>1);
        $total_count    = $this->CI->match_model->total_count('idx',$where_1,'fiveleague_reset_his');// 当前已重置次数
        $league_conf    = $this->CI->passport->get('league');
        if ($total_count >= $league_conf['free_reset']) {
            $info['free_reset'] = 0;
        } else {
            $info['free_reset'] = $league_conf['free_reset']-$total_count;
        }
        $m_info     = $this->CI->utility->get_manager_info($params);
        // 判断经理vip等级，获取可通过球票购买次数
        $where_3    = array('level'=>$m_info['vip'],'status'=>1);
        $fields_3   = "fiveleague_reset";
        $vip_info   = $this->CI->match_model->get_one($where_3,'vip_conf',$fields_3);
        if ($vip_info['fiveleague_reset']) {
            $info['tickets_reset']  = $info['free_reset']?$vip_info['fiveleague_reset']:$vip_info['fiveleague_reset']+$league_conf['free_reset']-$total_count;
        } else {
            $info['tickets_reset']  = 0;
        }
        $info['tickets']        = (int)$league_conf['reset_tickets'];// 重置消耗球票
        // $info['tickets_sweep']  = $this->CI->passport->get('sweep_league')['tickets'];//扫荡每关几球票
        // 获取经理扫荡剩余时间倒计时
        $sql        = "SELECT sweep_time,time FROM fiveleague_sweep_his WHERE manager_idx = ".$params['uuid']." AND status=1 ORDER BY idx DESC";
        $sweep_info = $this->CI->match_model->fetch($sql,'row');
        if (time() >= $sweep_info['time'] + $sweep_info['sweep_time']) {
            $info['sweepsurplus_time']  = 0;
        } else {
            $info['sweepsurplus_time']  = $sweep_info['time'] + $sweep_info['sweep_time'] - time();
        }
        // 判断当前是否可扫荡
        if ($info['sweepsurplus_time']) {
            $info['sweep']  = 0;
        } else {
            $where_4        = array('manager_idx'=>$params['uuid'],'status'=>1);
            $fields_4       = "ckpoint_no";
            $max_ckpoing    = $this->CI->match_model->get_one($where_4,'fiveleague_max',$fields_4);
            if (!$max_ckpoing || ($curr_ckpoint['ckpoint_no']-1) >= $max_ckpoing['ckpoint_no']) {
                $info['sweep']  = 0;
            } else {
                $info['sweep']  = 1;
            }
        }
        return $info;
    }
    
    /**
     * 获取五大联赛 奖励预览信息
     */
    public function get_reward_preview($params)
    {
        $where          = "mod(ckpoint_no,10)=0 AND status = 1";
        $sql            = "SELECT count(idx) AS total FROM fiveleague_conf  WHERE ".$where;
        $total_count    = $this->CI->match_model->fetch($sql);
        if (!$total_count['total']) {
            log_message('error', 'get_reward_preview:without_ckpoint_info_err'.$this->ip.',暂无该关卡信息');
            $this->CI->output_json_return('without_ckpoint_info_err');
        }
        $data['pagecount']  = ceil($total_count['total']/$params['pagesize']);
        $options['where']   = $where;
        $options['fields']  = "idx AS id,ckpoint_no,name,pic,gem_probable,euro,exp";
        $options['order']   = "ckpoint_no ASC";
        $options['limit']   = array('page'=>$params['offset'],'size'=>$params['pagesize']);
        $list   = $this->CI->match_model->list_data($options,'fiveleague_conf');
        foreach ($list as $k=>&$v) {
            $gem_info       = explode("|", trim($v['gem_probable'],"|"));
            foreach ($gem_info as $key=>$val) {
                $v['may_gem'][]  = explode(":", $val)[0];
            }
            unset($v['gem_probable']);
        }
        
        $data['list']   = $list;
        return $data;
    }
    
    /**
     * 获取五大联赛-冲关排行榜（前10名）
     */
    public function ranking_for_league($params)
    {
        $sql    = "select A.ckpoint_no AS ckpoint_no,A.pic AS pic,C.idx AS uuid,C.team_logo AS teamlogo,C.vip AS vip,C.level AS level,C.name AS name  FROM fiveleague_conf AS A JOIN fiveleague_max AS B ON A.ckpoint_no=B.ckpoint_no  LEFT JOIN  manager_info AS C ON C.idx=B.manager_idx WHERE A.status = 1 AND B.status = 1 AND C.status = 1 ORDER BY B.ckpoint_no DESC LIMIT 0,10";
        $list   = $this->CI->match_model->fetch($sql,'result');
        if (!$list) {
            log_message('error', 'ranking_for_league:without_league_ranking_err'.$this->ip.',暂无冲关排行信息');
            $this->CI->output_json_return('without_league_ranking_err');
        }
        foreach ($list as $k=>&$v) {
            $v['ranking']   = $k+1;
            if ($v['uuid'] == $params['uuid']) {
                $data['my_info']    = array('ranking'=>$k+1,'ckpoint_no'=>$v['ckpoint_no']);
            }
        }
        
        if (!$data['my_info']) {
            // 获取我当前的排名信息
            $where  = array('manager_idx'=>$params['uuid'],'status'=>1);
            $fields = "ckpoint_no";
            $ckpoint_info   = $this->CI->match_model->get_one($where,'fiveleague_max',$fields);
            if (!$ckpoint_info) {
                $data['my_info']    = array('ranking'=>0,'ckpoint_no'=>0);
            } else {
                // 获取排名次
                $where1 = array('ckpoint_no>'=>$ckpoint_info['ckpoint_no'],'status'=>1);
                $total_count    = $this->CI->match_model->total_count('idx',$where1,'fiveleague_max');
                $data['my_info']    = array('ranking'=>$total_count+1,'ckpoint_no'=>$ckpoint_info['ckpoint_no']);
            }
        }
        $data['list']   = $list;
        return $data;
    }
    
    /**
     * 五大联赛重置关卡(将当前关卡恢复到第0关，玩家可以重第一关开始挑战)
     */
    public function reset_for_league($params)
    {
        // 判断经理当前是否允许重置
        $time           = strtotime(date('Y-m-d',time()));// 当前日期
        $tickets        = 0;
        $where_1        = array('manager_idx'=>$params['uuid'],'time>='=>$time,'status'=>1);
        $total_count    = $this->CI->match_model->total_count('idx',$where_1,'fiveleague_reset_his');
        $league_conf    = $this->CI->passport->get('league');
        if ($total_count >= $league_conf['free_reset']) {
            $m_info     = $this->CI->utility->get_manager_info($params);
            // 判断经理vip等级，获取可通过球票购买次数
            $where_2    = array('level'=>$m_info['vip'],'status'=>1);
            $fields_2   = "fiveleague_reset";
            $vip_info   = $this->CI->match_model->get_one($where_2,'vip_conf',$fields_2);
            if (!$vip_info['fiveleague_reset'] && $total_count >= $league_conf['free_reset'] + $vip_info['fiveleague_reset']) {
                log_message('error', 'reset_for_league:league_reset_enought_err'.$this->ip.',超过每日重置次数');
                $this->CI->output_json_return('league_reset_enought_err');
            }
            // 判断经理球票是否足够
            if ($m_info['tickets'] < $league_conf['reset_tickets']) {
                log_message('error', 'reset_for_league:not_enough_tickets_err'.$this->ip.',球票不足');
                $this->CI->output_json_return('not_enough_tickets_err');
            }
            $tickets    = $league_conf['reset_tickets'];
        }
        $this->CI->match_model->start();
        $fields     = array('status'=>0);
        $where      = array('manager_idx'=>$params['uuid'],'status'=>1);
        $upt_res    = $this->CI->match_model->update_data($fields,$where,'fiveleague_curr');
        if (!$upt_res)  {
            $this->CI->match_model->error();
            log_message('error', 'reset_for_league:league_reset_fail_err'.$this->ip.',五大联赛重置失败');
            $this->CI->output_json_return('league_reset_fail_err');
        }
        
        if ($tickets) {
            $fields_2   = array('tickets'=>$m_info['tickets'] - $tickets);
            $where_2    = array('idx'=>$params['uuid'],'status'=>1);
            $m_upt      = $this->CI->utility->update_m_info($fields_2,$where_2);
            if (!$m_upt) {
                $this->CI->match_model->error();
                log_message('error', 'reset_for_league:m_info_update_err'.$this->ip.',经理球票更新失败');
                $this->CI->output_json_return('m_info_update_err');
            }
        }
        // 添加重置记录
        $data   = array(
            'manager_idx'   => $params['uuid'],
            'tickets'       => $tickets,
            'status'        => 1
        );
        $ist    = $this->CI->match_model->insert_data($data,'fiveleague_reset_his');
        if (!$ist) {
            $this->CI->match_model->error();
            log_message('error', 'reset_for_league:league_reset_fail_err'.$this->ip.',五大联赛重置失败');
            $this->CI->output_json_return('league_reset_fail_err');
        }
        //触发任务 重置五大联赛
        $this->CI->utility->get_task_status($params['uuid'] , 'reset_for_league');
        $this->CI->match_model->success();
        return true;
    }
    
    /**
     * 五大联赛-扫荡
     */
    public function do_sweep_league($params)
    {
        // 获取经理扫荡剩余时间倒计时
        $sql        = "SELECT idx id, start_ckno,end_ckno,sweep_time,time FROM fiveleague_sweep_his WHERE manager_idx = ".$params['uuid']." AND status=1 ORDER BY idx DESC";
        $sweep_info = $this->CI->match_model->fetch($sql,'row');
        if ($params['is_tickets'] == 1) { // 立即完成
            if (time() >= $sweep_info['time'] + $sweep_info['sweep_time']) {
                log_message('error', 'do_sweep_league:sweep_first_err'.$this->ip.',请先扫荡');
                $this->CI->output_json_return('sweep_first_err');
            }
        } else {// 扫荡
            if (time() < $sweep_info['time'] + $sweep_info['sweep_time']) {
                log_message('error', 'do_sweep_league:last_sweep_not_conmplete_err'.$this->ip.',上一轮扫荡未完成');
                $this->CI->output_json_return('last_sweep_not_conmplete_err');
            }
        }
        
        $sweep_league   = $this->CI->passport->get('sweep_league');
        // 使用球票--立即完成扫荡操作
        if ($params['is_tickets']   == 1) {
            $this->CI->match_model->start();
            $m_info         = $this->CI->utility->get_manager_info($params);
            $expend_tickets = $sweep_league['tickets'] * ($sweep_info['end_ckno'] - $sweep_info['start_ckno']);
            // 判断经理球票是否足够
            if ($expend_tickets > $m_info['tickets']) {
                log_message('error', 'do_sweep_league:not_enough_tickets_err'.$this->ip.',经理球票不足');
                $this->CI->output_json_return('not_enough_tickets_err');
            }
            // 扣除球票
            $fields['tickets']  = $m_info['tickets'] - $expend_tickets;
            $where['idx']       = $m_info['uuid'];
            $u_res  = $this->CI->match_model->update_data($fields,$where,'manager_info');
            if (!$u_res) {
                $this->CI->match_model->error();
                log_message('error', 'do_sweep_league:m_info_update_err'.$this->ip.',经理球票更新失败');
                $this->CI->output_json_return('m_info_update_err');
                
            }
            $sweep_time = 0;
            // 更改扫荡时间
            $fields['sweep_time']   = 0;
            $fields['tickets']      = $expend_tickets;
            $where['idx']           = $sweep_info['id'];
            $u_res  = $this->CI->match_model->update_data($fields,$where,'fiveleague_sweep_his');
            if (!$u_res) {
                $this->CI->match_model->error();
                log_message('error', 'do_sweep_league:fiveleague_update_time_err'.$this->ip.',五大联赛立即完成,时间更新失败');
                $this->CI->output_json_return('fiveleague_update_time_err');
            }
            $this->CI->match_model->success();
            return true;
        }
        
        // 扫荡操作
        // 最高关卡
        $where_4        = array('manager_idx'=>$params['uuid'],'status'=>1);
        $fields_4       = "ckpoint_no";
        $curr_ckpoing   = $this->CI->match_model->get_one($where_4,'fiveleague_curr',$fields_4);
        if ($params['ckpoint_no']) {
            $max_ckpoing['ckpoint_no']  = $params['ckpoint_no'];
        }
        
        // 获取经理扫荡开始关卡
        $where_2        = array('manager_idx'=>$params['uuid'],'status'=>1);
        $fields_2       = "ckpoint_no";
        $curr_ckpoint   = $this->CI->match_model->get_one($where_2,'fiveleague_curr',$fields_2);
        if (!$curr_ckpoint) {
            $sweep_start    = 1;
        } else {
            $sweep_start    = $curr_ckpoint['ckpoint_no'] - 1;
        }
        
        // 判断当前是否可扫荡
        $where          = array('manager_idx'=>$params['uuid'],'status'=>1);
        $fields         = "ckpoint_no";
        $max_ckpoing    = $this->CI->match_model->get_one($where,'fiveleague_max',$fields);
        if (!$max_ckpoing) {
            log_message('error', 'do_sweep_league:not_allow_sweep_err'.$this->ip.',经理暂无最高成绩记录,不允许扫荡');
            $this->CI->output_json_return('not_allow_sweep_err');
        }
        if (($curr_ckpoing['ckpoint_no'] - 1) >= $max_ckpoing['ckpoint_no']) {
            log_message('error', 'do_sweep_league:not_allow_sweep_ckpoint_err'.$this->ip.',不允许扫荡到当前关卡');
            $this->CI->output_json_return('not_allow_sweep_ckpoint_err');
        }
        $sweep_ckpoint  = (int)$max_ckpoing['ckpoint_no'];
        
        // 统计扫荡关卡花费时间
        $expend_tickets = 0;
        $sweep_time     = $sweep_league['time']*($sweep_ckpoint - $sweep_start +1);
        // 执行扫荡操作
        $this->CI->match_model->start();
        for($i=$sweep_start;$i<=$sweep_ckpoint;$i++) {
            $para   = array('ckpoint_no' => $i, 'uuid' => $params['uuid'],'is_sweep'=>1);
            $res = $this->match_for_league($para);
            if (!$res) {// 比赛错误
                $this->CI->match_model->error();
                log_message('error', 'do_sweep_league:sweep_league_fail_err'.$this->ip.',五大联赛扫荡失败');
                $this->CI->output_json_return('sweep_league_fail_err');
            }
        }
        // 扫荡历史记录表
        $data   = array(
            'manager_idx'   => $params['uuid'],
            'start_ckno'    => $sweep_start,
            'end_ckno'      => $sweep_ckpoint,
            'tickets'       => $expend_tickets,
            'sweep_time'    => $sweep_time,
            'status'        => 1
        );
        $res    = $this->CI->match_model->insert_data($data,'fiveleague_sweep_his');
        if (!$res) {
            $this->CI->match_model->error();
            log_message('error', 'do_sweep_league:sweep_league_insert_his_err'.$this->ip.',五大联赛扫荡历史记录插入失败');
            $this->CI->output_json_return('sweep_league_insert_his_err');
        }
        $this->CI->match_model->success();
        return true;
    }
    
    /**
     * 副本赛一键扫荡
     */
    public function do_sweepall_for_copy($params)
    {
        // 获取当前关卡已扫荡次数
        $where          = array('manager_idx'=>$params['uuid'],'copy_no'=>$params['copy_no'],'ckpoint_no'=>$params['ckpoint_no'],'type'=>$params['type'],'status'=>1);
        $fields         = "sweep_num,star";
        $ckpoint_comp   = $this->CI->match_model->get_one($where,'ckpoint_complete_his',$fields);
        $sweep_num      = (int)$ckpoint_comp['sweep_num'];
        
        // 获取可免费当次数
        $copy_sweep     = $this->CI->passport->get('copy_sweep');
        $copy_expend    = $this->CI->passport->get('expend_ps');
        if ($params['type'] == 1) {// 常规
            $surplus_num    = $copy_sweep['common']-$sweep_num;
            $expend         = $surplus_num*$copy_expend['common'];
        } else {
            $surplus_num    = $copy_sweep['elite']-$sweep_num;
            $expend         = $surplus_num*$copy_expend['elite'];
        }
        
        if ($surplus_num <= 0) {
            log_message('error', 'do_sweepall_for_copy:free_sweep_null_err'.$this->ip.',暂无免费扫荡次数，不能进行一键扫荡');
            $this->CI->output_json_return('free_sweep_null_err');
        }
        // 校验经理体力是都足够
        $m_info = $this->CI->utility->get_manager_info($params);
        if ($m_info['physical_strenth'] < $expend) {
            log_message('error', 'do_sweepall_for_copy:m_phystrenght_not_enought'.$this->ip.',一键扫荡是，体力不足');
            $this->CI->output_json_return('m_phystrenght_not_enought');
        }
        
        // 进行一键扫荡
        $params['sweep']    = 1;
        for($i=$surplus_num;$i>0;$i--) {
            $res = $this->match_for_copy($params);
            if (!$res) {
                log_message('error', 'do_sweepall_for_copy:onekey_sweep_err'.$this->ip.',一键扫荡失败');
                $this->CI->output_json_return('onekey_sweep_err');
            }
        }
        return true;
    }
    
    /**
     * 天梯赛初始排名
     */
    public function do_init_ranking_ladder($params)
    {
        $where_1    = array('manager_idx'=>$params['uuid'],'type'=>1,'status'=>1);
        $fields_1   = "idx";
        $m_existst  = $this->CI->match_model->get_one($where_1,'ladder_ranking',$fields_1);
        if ($m_existst) {
            log_message('info', 'do_init_ranking_ladder:exists_ladder_ranking_table'.$this->ip.',经理已经存在排名表，不允许初始化');
            return true;
        }
        $rand   = mt_rand(2220, 5230);
        $where  = array('ranking'=>$rand,'status'=>1);
        $fields = "idx";
        $exstst = $this->CI->match_model->get_one($where,'ladder_ranking',$fields);
        while ($exstst && $exstst['type'] == 1) {
            $rand   = mt_rand(2220, 5230);
            $where  = array('ranking'=>$rand,'status'=>1);
            $fields = "idx";
            $exstst = $this->CI->match_model->get_one($where,'ladder_ranking',$fields);
        }
        
        $this->CI->match_model->start();
        if ($exstst) {
            $fields     = array('status'=>0);
            $where      = array('idx'=>$exstst['idx'],'status'=>1);
            $upt_res    = $this->CI->match_model->update_data($fields,$where,'ladder_ranking');
            if (!$upt_res) {
                $this->CI->match_model->error();
                log_message('error', 'do_init_ranking_ladder:init_ranking_fail'.$this->ip.',天梯初始排名失败');
                $this->CI->output_json_return('init_ranking_fail');
            }
        }

        //首次打天梯赛,将常规赛阵容同步到天梯赛
        //天梯赛初始阵容球员下场（is_use = 2）
        $down_fields     = array('is_use'=>0 , 'position_no' => 7 , 'position_no2' => 7);
        $down_where      = array('manager_idx'=>$params['uuid'],'status'=>1 , 'is_use'=>2);
        $exchange_res    = $this->CI->match_model->update_data($down_fields,$down_where,'player_info');
        if(!$exchange_res){
            $this->CI->match_model->error();
            log_message('error', 'match_for_ladder:exchange_player_down_err'.$this->ip.',首次天梯赛替换阵容,球员下场失败');
            $this->CI->output_json_return('exchange_player_down_err');
        }
        //常规赛阵容球员（is_use = 1,3）同时上天梯赛阵容
        $up_fields       = array('is_use'=>3);
        $up_where        = array('manager_idx'=>$params['uuid'],'status'=>1 , 'is_use'=>1);
        $exchange_res    = $this->CI->match_model->update_data($up_fields,$up_where,'player_info');
        if(!$exchange_res){
            $this->CI->match_model->error();
            log_message('error', 'match_for_ladder:exchange_player_up_err'.$this->ip.',首次天梯赛替换阵容,球员上场失败');
            $this->CI->output_json_return('exchange_player_up_err');
        }
        //同步天梯赛球员站位
        $sql            = "UPDATE player_info SET position_no2 = position_no WHERE manager_idx = {$params['uuid']} AND is_use = 3 AND status = 1";
        $exchange_res   = $this->CI->match_model->fetch($sql,'update');
        if(!$exchange_res){
            $this->CI->match_model->error();
            log_message('error', 'match_for_ladder:exchange_position_err'.$this->ip.',初始化同步球员站位失败');
            $this->CI->output_json_return('exchange_position_err');
        }
        
        //同步阵型 非天梯赛使用阵容改为所有赛事使用阵型
        $copy_fields     = array('is_use'=>3);
        $copy_where      = array('manager_idx'=>$params['uuid'],'status'=>1 , 'is_use'=>1);
        $exchange_res    = $this->CI->match_model->update_data($copy_fields,$copy_where,'structure');
        if(!$exchange_res){
            $this->CI->match_model->error();
            log_message('error', 'match_for_ladder:exchange_structure_err'.$this->ip.',初始化同步天梯赛阵型失败');
            $this->CI->output_json_return('exchange_structure_err');
        }
        //同步阵型 取消天梯赛正在使用阵型
        $ladder_fields   = array('is_use'=>0);
        $ladder_where    = array('manager_idx'=>$params['uuid'],'status'=>1 , 'is_use'=>2);
        $exchange_res    = $this->CI->match_model->update_data($ladder_fields,$ladder_where,'structure');
        if(!$exchange_res){
            $this->CI->match_model->error();
            log_message('error', 'match_for_ladder:exchange_structure_err'.$this->ip.',初始化同步天梯赛阵型失败');
            $this->CI->output_json_return('exchange_structure_err');
        }
        $data   = array(
            'manager_idx'   => $params['uuid'],
            'type'          => 1,
            'ranking'       => $rand,
            'match_status'  => 0,
            'status'        => 1,
        );
        $ist_res    = $this->CI->match_model->insert_data($data,'ladder_ranking');
        if (!$ist_res) {
            $this->CI->match_model->error();
            log_message('error', 'do_init_ranking_ladder:init_ranking_fail'.$this->ip.',天梯初始排名失败');
            $this->CI->output_json_return('init_ranking_fail');
        }
        $this->CI->match_model->success();
        return $rand;
    }
    
}
