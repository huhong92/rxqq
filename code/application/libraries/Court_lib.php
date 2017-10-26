<?php
class Court_lib extends Base_lib {
    public $pagesize    = 20;
    public function __construct() {
        parent::__construct();
        $this->load_model('court_model');
    }
    
    /**
     * 获取数据总条数
     * @param type $where
     * @param type $table
     */
    public function get_total_count($where, $table)
    {
        $total_count = $this->CI->court_model->total_count('idx', $where, $table);
        return $total_count;
    }
    
    /**
     * 获取装备总条数
     * @param type $type
     * @return type
     */
    public function equipt_total_count($params){
        $sql    = "SELECT COUNT(A.idx) AS total_count FROM equipt AS A,equipt_conf AS B WHERE A.manager_idx = ".$params['uuid']." AND  A.equipt_no = B.equipt_no AND A.level = B.level AND A.status = 1 AND B.status = 1 ";
        if (in_array($params['type'], array(1,2,3)) ) {
            $sql .= "AND B.type = ".$params['type'];
        }
        $total_count = $this->CI->court_model->fetch($sql, 'row');
        return $total_count['total_count'];
    }
    /**
     * 获取球员总条数
     * @param type $where
     * @return type
     */
    public function get_player_count($where)
    {
        $sql = "SELECT count(A.idx) as num FROM player_info as A,player_lib AS B WHERE A.plib_idx = B.idx AND  ".$where;
        $res = $this->CI->court_model->fetch($sql, 'row');
        return $res['num'];
    }
    
    /**
     * 获取球员列表
     * @param type $options
     *      --where  array()
     *      --limit  array()
     */
    public function get_player_list($params,$fields = '')
    {
        // 获取球员列表
        $where  = "A.manager_idx = ".$params['uuid']." AND A.status = 1 AND B.status = 1 ";
        if ($params['struc_type'] == 2) {// 天梯阵容
            if ($params['type'] == 1) {// 1上阵球员 2闲置球员3所有球员
                $where  .= " AND (A.is_use = 2 or A.is_use = 3) ";
            } else if($params['type'] == 2){
                $where  .= " AND (A.is_use = 0 or A.is_use = 1) ";
            }
        } else if($params['struc_type'] == 1){
            if ($params['type'] == 1) {// 1上阵球员 2闲置球员3所有球员
                $where  .= " AND (A.is_use = 1 or A.is_use = 3) ";
            } else if($params['type'] == 2){
                $where  .= " AND (A.is_use = 0 or A.is_use = 2) ";
            }
        } else {
            if ($params['type'] == 1) {// 1上阵球员 2闲置球员3所有球员
                $where  .= " AND (A.is_use != 0) ";
            } else if($params['type'] == 2){
                $where  .= " AND (A.is_use = 0) ";
            }
        }
        if ($params['position_type'] !== '' && $params['position_type'] !== null) {// 0GK 3CB 2CM 1ST, 可选
            $where  .= " AND B.position_type = ".$params['position_type'];
        }
        $total_count = $this->get_player_count($where);
        if (!$total_count) {
            return false;
        }
        $data['pagecount']  = ceil($total_count/$params['pagesize']);
        if ($params['offset'] == false) {
            $params['offset'] =0;
        }
        if ($params['pagesize'] == false) {
            $params['pagesize'] =$this->pagesize;
        }
        $limit              = array('offset' => $params['offset'],'pagesize'=>$params['pagesize']);
        $data['list']       = $this->CI->court_model->get_player_list($where,$limit,$fields);
        foreach ($data['list'] as $k=>&$v) {
            if ($params['struc_type'] == 2) {// 天梯阵容
                $v['position_no']    = $v['position_no2'];
            }
            if ($v['fatigue'] <= 36) {
                $v['fatigue_type']  = 1;
            } elseif($v['fatigue'] <= 76) {
                $v['fatigue_type']  = 2;
            } else {
                $v['fatigue_type']  = 3;
            }
        }
        return $data;
    }
    
    /**
     * 获取球员信息
     * @param type $params
     *      -- id int 球员id
     *  player_info A, player_lib B
     * $type = 1,自增idx type=2 player_no球员编号查找
     */
    public function get_player_info($params)
    {
        $options['where']   = "A.idx =".$params['id'] ." AND A.status = 1 AND B.status = 1";
        $options['select']  = "A.idx AS id,
            A.plib_idx AS plib_id,
            A.level AS level,
            A.player_no AS player_no,
            A.generalskill_no AS generalskill_no,
            A.generalskill_level AS generalskill_level,
            A.exclusiveskill_no AS exclusiveskill_no,
            A.is_use AS is_use,
            A.position_no AS position_no,
            A.position_no2 AS position_no2,
            A.fatigue AS fatigue,
            @fatigue_total := 100 AS fatigue_total,
            A.speed AS speed,
            A.shoot AS shoot,
            A.free_kick AS free_kick,
            A.acceleration AS acceleration,
            A.header AS header,
            A.control AS control,
            A.physical_ability AS physical_ability,
            A.power AS power,
            A.aggressive AS aggressive,
            A.interfere AS interfere,
            A.steals AS steals,
            A.ball_control AS ball_control,
            A.pass_ball AS pass_ball,
            A.mind AS mind,
            A.reaction AS reaction,
            A.positional_sense AS positional_sense,
            A.hand_ball AS hand_ball,
            B.pic AS pic,
            B.name AS name,
            B.quality AS quality,
            B.ability AS ability,
            B.nationality AS nationality,
            B.club AS club,
            B.birthday AS birthday,
            B.intro AS intro,
            B.position_type AS position_type";
        $player_info    = $this->CI->court_model->get_player_info($options);
        return $player_info;
    }
    
    /**
     * 获取球员lib表 信息
     * @param type $params
     */
    public function get_player_lib_info($params,$type=1)
    {
        if ($type === 1) {
            $where  = array('player_no' => $params['player_no'], 'status' => 1);
        } else {
            $where  = array('idx' => $params['plib_id'], 'status' => 1);
        }
        $fields     = "idx as id,player_no,generalskill_no,exclusiveskill_no,
            speed,shoot,free_kick,acceleration,header,control,physical_ability,power,aggressive,interfere, steals,ball_control,pass_ball,mind,reaction,positional_sense,
            hand_ball,pic,name,quality,nationality,club,birthday,intro,position_type";
        $lib_info   = $this->CI->court_model->get_one($where, 'player_lib',$fields);
        return $lib_info;
    }
    
    /**
     * 获取球员卡 基础信息（不含属性）
     *      包含 --- player_no,name,level,is_use,pic,position_no,quality,nationality,club,birthday,intro
     */
    public function get_player_base_info($params)
    {
        $options['where']   = "A.idx =".$params['id']." AND A.status = 1 AND B.status = 1";
        $options['select']  = "A.idx AS id,
            A.level AS level,
            A.plib_idx AS plib_id,
            A.player_no,
            A.is_use AS is_use,
            A.position_no AS position_no,
            A.position_no2 AS position_no2,
            A.cposition_type AS cposition_type,
            A.fatigue AS fatigue,
            B.ability AS ability,
            B.pic AS pic,
            B.name AS name,
            B.quality AS quality,
            B.nationality AS nationality,
            B.club AS club,
            B.birthday AS birthday,
            B.intro AS intro,
            B.position_type AS position_type";
            
        $player_info = $this->CI->court_model->get_player_info($options);
        if (!$player_info) {
            log_message('info', 'empty_data:'.$this->ip.',get_player_info empty data');
            $this->CI->output_json_return('player_id_err');
        }
        return $player_info;
    }
    
    /**
     * 更新球员信息
     * @param type $fields
     * @param type $where
     */
    public function update_player_info($fields, $where)
    {
        $res = $this->CI->court_model->update_data($fields, $where, 'player_info');
        return $res;
    }
    
    /**
     * 球员下场操作
     */
    public function enter_player($params)
    {
        // 获取该球员信息
        $where  = array('idx'=>$params['id'],'manager_idx'=>$params['uuid'],'status'=>1);
        $fields = "player_no,is_use";
        $p_info = $this->CI->court_model->get_one($where,'player_info',$fields);
        if (!$p_info) {
            log_message('error', 'enter_court:player_id_err'.$this->court_lib->ip.',暂无该球员');
            $this->CI->output_json_return('player_id_err');
        }
        // 判断操作的球场类型
        if ($params['struc_type'] == 2) {// 天梯阵容
            if ($p_info['is_use'] == 1 || $p_info['is_use'] == 0) {// 2 3 
                log_message('error', 'enter_court:player_not_court_err'.$this->court_lib->ip.',该球员不在球场上');
                $this->CI->output_json_return('player_not_court_err');
            }
            $fields1  = array('is_use'=>$p_info['is_use'] - 2,'position_no2'=>7);
        } else {// 普通阵容
            if ($p_info['is_use'] == 2 || $p_info['is_use'] == 0) {// 1 3
                log_message('error', 'enter_court:player_not_court_err'.$this->court_lib->ip.',该球员不在球场上');
                $this->CI->output_json_return('player_not_court_err');
            }
            $fields1  = array('is_use'=>$p_info['is_use'] - 1,'position_no'=>7);
        }
        $this->CI->court_model->start();
        $res = $this->CI->court_model->update_data($fields1,$where,'player_info');
        if (!$res) {
            $this->CI->court_model->error();
            log_message('error', 'enter_court:enter_court_err'.$this->court_lib->ip.',球员下场失败');
            $this->CI->output_json_return('enter_court_err');
        }
        $this->CI->court_model->success();
        return true;
    }
    
    /**
     * 获取球员装备信息
     * @param type $params
     */
    public function player_equipt($params)
    {
        $options['where']   = array('player_idx'=>$params['id'], 'manager_idx'=>$params['uuid'], 'status'=>1);
        $equipt_list =   $this->CI->court_model->list_data($options, 'equipt');
        
        if (empty($equipt_list)) {
            log_message('info', 'empty_data:'.$this->ip.',player_equipt empty data');
            $this->CI->output_json_return('empty_data');
        }
        foreach ($equipt_list as $k=>$v) {
            $equipt_info    = $this->equipt_info(array('id'=>$v['idx']));
            $info['equipt'][$k]['pic']          = $equipt_info['pic'];
            $info['equipt'][$k]['type']         = $equipt_info['type'];
            $info['equipt'][$k]['name']         = $equipt_info['name'];
            $info['equipt'][$k]['equipt_no']    = $equipt_info['equipt_no'];
            $info['equipt'][$k]['level']        = $equipt_info['level'];
            $info['equipt'][$k]['descript']     = $equipt_info['descript'];
            if ((int)$equipt_info['type'] === 1) {// 球衣
                $where['jacket_no']     = $equipt_info['equipt_no'];
                $level['jacket_no']     =$equipt_info['levle'];
            } elseif ((int)$equipt_info['type'] === 2) {// 球裤
                $where['trousers_no']   = $equipt_info['equipt_no'];
                $level['trousers_no']   =$equipt_info['levle'];
            } elseif ((int)$equipt_info['type'] === 3) {// 球鞋
                $where['shoes_no']      = $equipt_info['equipt_no'];
                $level['shoes_no']      = $equipt_info['levle'];
            }
        }
        $suit   = array();
        if((int)count($info['equipt']) === 3) {
            // 查看是否存在套装效果
            $suit_info  = $this->CI->court_model->get_one($where, 'equiptsuit_conf');
            if (!empty($suit_info)) {
                if ($level['jacket_no'] >= 16 && $level['trousers_no'] >=16 && $level['shoes_no'] >= 16) {// 紫色套装
                    $suit['effects']    = $suit_info['effects_3'];// 属性加成百分比值
                } elseif ($level['jacket_no'] >= 11 && $level['trousers_no'] >=11 && $level['shoes_no'] >= 11) {// 蓝色套装
                    $suit['effects']    = $suit_info['effects_2'];
                }else{// 套装效果
                    $suit['effects']    = $suit_info['effects'];
                }
                $suit['name']           = $suit_info['name'];
                $suit['attribute']      = $suit_info['attribute'];
                $suit['descript']       = str_replace("x",$suit['effects'] , $suit_info['descript']);
            }
        }
        $info['suit']   = $suit;
        return $info;
    }
    /**
     * 获取球员技能信息
     * @param type $params
     */
    public function get_skill_info($params)
    {
        $player_info    = $this->get_player_info($params);
        $info           = array();
        $piclib_url     = $this->CI->passport->get('piclib_url');
        $equipt_pic     = $this->CI->passport->get('equipt_pic');
        
        $fields     = "idx as id,skill_no,name,level,pic,descripte";
        if ($player_info['generalskill_no']) {
            $where_1 = array('skill_no' => $player_info['generalskill_no'], 'level'=> $player_info['generalskill_level'], 'status'=>1);
            $generalskill           = $this->CI->court_model->get_one($where_1, 'skill_conf',$fields);
            $generalskill['status'] = 0;
            if ($player_info['level'] >= 3) {
                $generalskill['status'] = 1;// 技能已开启
            }
            $info['generalskill']   = $generalskill; 
        }
        if ($player_info['exclusiveskill_no']) {
            $where_2 = array('skill_no' => $player_info['exclusiveskill_no'], 'level'=> 1, 'status'=>1);
            $exclusiveskill         = $this->CI->court_model->get_one($where_2, 'skill_conf',$fields);
            $exclusiveskill['status'] = 0;
            if ($player_info['level'] >= 7) {
                $exclusiveskill['status'] = 1;// 技能已开启
            }
            $info['exclusiveskill']   = $exclusiveskill;
        }
        
        return $info;
    }
    
    
    /**
     * 获取某经理 球场上阵人数
     * @param type $params
     */
    public function player_enter_num($params)
    {
        $where          = array('manager_idx' => $params['uuid'], 'is_use'  => 1, 'status' => 1);
        $total_count    = $this->CI->court_model->total_count('idx', $where, 'player_info');
        return $total_count;
    }
    
    /**
     * 已弃用--2017-04-27 11:27:00
     * 场上球员换位操作
     * @param type $params
     * @return boolean
     */
    public function exchange_position($params)
    {
        // 当前球员信息
        $where  = array('idx'=>$params['id'],'manager_idx'=>$params['uuid'],'status'=>1);
        $fields = "player_no,is_use,position_no,position_no2";
        $p_info = $this->CI->court_model->get_one($where,'player_info',$fields);
        // 查看当前位置是否有球员
        if ($params['struc_type'] == 2) {
            $where_1    = "manager_idx =".$params['uuid']." AND position_no2 = ".$params['position_no']." AND (is_use =2 OR is_use = 3)  AND status = 1";
        } else {
            $where_1    = "manager_idx =".$params['uuid']." AND position_no = ".$params['position_no']." AND (is_use =1 OR is_use = 3)  AND status = 1";
        }
        $sql            = "SELECT idx as id,is_use,position_no,position_no2 FROM player_info WHERE ".$where_1;
        $other_info     = $this->CI->court_model->fetch($sql,'row');
        // 更新位置
        $this->CI->court_model->start();
        if ($params['struc_type'] == 2) {// 1普通阵容2天梯阵容
            if ($other_info) {// 该位置有球员
                if ($p_info['is_use'] == 2 || $p_info['is_use'] == 3) {// 换位
                    $where_3    = array('idx'=>$other_info['id'],'status'=>1);
                    $fields_3   = array('position_no2'=>$p_info['position_no2']);
                    $res = $this->update_player_info($fields_3, $where_3,'player_info');
                    if (!$res) {
                        $this->CI->court_model->error();
                        log_message('error', 'exchange_position_err:'.$this->ip.',球员换位失败');
                        $this->CI->output_json_return('exchange_position_err');
                    }
                } else {// 将原位置球员踢下场
                    // 上场操作，判断球场是都存在统一编号的球员TODO
                    if ($p_info) {
                        
                    }
                    $res = $this->check_enter_player_sameas($params['uuid'], $p_info['player_no'], 2);
                    if(!$res) {
                        $this->CI->court_model->error();
                        log_message('error', 'exchange_position:player_sameas_err'.$this->ip.',球场上已有相同编号的球员');
                        $this->CI->output_json_return('player_sameas_err');
                    }
                    $where_3    = array('idx'=>$other_info['id'],'status'=>1);
                    $fields_3   = array('is_use'=>$other_info['is_use'] - 2,'position_no2'=>7);
                    $res = $this->update_player_info($fields_3, $where_3,'player_info');
                    if (!$res) {
                        $this->CI->court_model->error();
                        log_message('error', 'exchange_position_err:'.$this->ip.',球员换位失败');
                        $this->CI->output_json_return('exchange_position_err');
                    }
                    $fields4['is_use'] = $p_info['is_use'] + 2;
                }
            } else {// 上阵位置无球员
                if ($p_info['is_use'] != 2 && $p_info['is_use'] != 3) {// 上阵/换位
                    // 上场操作，判断球场是都存在统一编号的球员TODO
                    $res = $this->check_enter_player_sameas($params['uuid'], $p_info['player_no'], 2);
                    if(!$res) {
                        $this->CI->court_model->error();
                        log_message('error', 'exchange_position:player_sameas_err'.$this->ip.',球场上已有相同编号的球员');
                        $this->CI->output_json_return('player_sameas_err');
                    }
                    $fields4['is_use'] = $p_info['is_use'] + 2;
                }  
            }
            $fields4['position_no2']    = $params['position_no'];
        } else {// 普通阵容
            if ($other_info) {// 该位置有球员
                if ($p_info['is_use'] == 1 || $p_info['is_use'] == 3) {// 换位
                    $where_3    = array('idx'=>$other_info['id'],'status'=>1);
                    $fields_3   = array('position_no'=>$p_info['position_no']);
                    $res = $this->update_player_info($fields_3, $where_3,'player_info');
                    if (!$res) {
                        $this->CI->court_model->error();
                        log_message('error', 'exchange_position_err:'.$this->ip.',球员换位失败');
                        $this->CI->output_json_return('exchange_position_err');
                    }
                } else {// 将原位置球员踢下场
                    $where_3    = array('idx'   =>$other_info['id'],'status'=>1);
                    $fields_3   = array('is_use'=>$other_info['is_use'] - 1,'position_no'=>7);
                    $res = $this->update_player_info($fields_3, $where_3,'player_info');
                    if (!$res) {
                        $this->CI->court_model->error();
                        log_message('error', 'exchange_position_err:'.$this->ip.',球员换位失败');
                        $this->CI->output_json_return('exchange_position_err');
                    }
                    // 上场操作，判断球场是都存在统一编号的球员TODO
                    $res = $this->check_enter_player_sameas($params['uuid'], $p_info['player_no'], 1);
                    if(!$res) {
                        $this->CI->court_model->error();
                        log_message('error', 'exchange_position:player_sameas_err'.$this->ip.',球场上已有相同编号的球员');
                        $this->CI->output_json_return('player_sameas_err');
                    }
                    $fields4['is_use'] = $p_info['is_use'] + 1;
                }
            } else {// 上阵位置无球员
                if ($p_info['is_use'] != 1 && $p_info['is_use'] != 3) {// 上阵
                    // 上场操作，判断球场是都存在统一编号的球员TODO
                    $res = $this->check_enter_player_sameas($params['uuid'], $p_info['player_no'], 1);
                    if(!$res) {
                        $this->CI->court_model->error();
                        log_message('error', 'exchange_position:player_sameas_err'.$this->ip.',球场上已有相同编号的球员');
                        $this->CI->output_json_return('player_sameas_err');
                    }
                    $fields4['is_use'] = $p_info['is_use'] + 1;
                }
            }
            $fields4['position_no']    = $params['position_no'];
        }
        
        // 直接上阵
        $res = $this->update_player_info($fields4, $where,'player_info');
        if (!$res) {
            $this->CI->court_model->error();
            log_message('error', 'exchange_position_err:'.$this->ip.',球员换位失败');
            $this->CI->output_json_return('exchange_position_err');
        }
        // 触发成就 - 战斗力
        $this->load_library('task_lib');
        $this->CI->task_lib->achieve_fighting($params['uuid']);
        
        $this->CI->court_model->success();
        return true;
    }
    
    /**
     * 场上球员换位操作
     * @param type $params
     * @return boolean
     */
    public function do_exchange_position($params)
    {
        // 天梯赛换球员位置
        if ($params['struc_type'] == 2) {
            $this->do_exchange_position_ladder($params);
        } else {
            $this->do_exchange_position_common($params);
        }
        return true;
    }
    
    /**
     * 天梯赛场-球员换位操作
     * @param type $params
     */
    public function do_exchange_position_ladder($params)
    {
        $is_use = 1;// 预定义：该球员原本就在球场上--执行换位操作
        // 查看当前需变更球员信息$params['id']
        $where  = array('idx'=>$params['id'],'manager_idx'=>$params['uuid'],'status'=>1);
        $fields = "player_no,is_use,position_no2";
        $p_info = $this->CI->court_model->get_one($where,'player_info',$fields);
        // 查看当前位置是否有球员
        $where_1    = "manager_idx =".$params['uuid']." AND position_no2 = ".$params['position_no']." AND (is_use =2 OR is_use = 3)  AND status = 1";
        $sql        = "SELECT idx as id,is_use,position_no2,player_no FROM player_info WHERE ".$where_1;
        $other_info = $this->CI->court_model->fetch($sql,'row');
        
        // 1.判断变更球员卡，是否在球场上
        if ($p_info['is_use'] == 3 || $p_info['is_user'] == 2) {// 在球场上上，直接换位操作
            $is_use = 1;// 该球员原本就在球场上--目前执行换位操作
            $data_[] = array(
                'idx'           => $params['id'],
                'position_no2'  => $params['position_no'],
                'is_use'        => $p_info['is_use'],
            );
        } else { // 不在球场上，直接上阵
            $is_use = 0;// 该球员原本不在球场上---目前执行上阵操作
            // 上阵前，检测球场上是否有相同编号的球员卡
            if ($p_info['player_no'] != $other_info['player_no']) {
                $res = $this->check_enter_player_sameas($params['uuid'], $p_info['player_no'], 2);
                if(!$res) {
                    log_message('error', 'exchange_position:player_sameas_err'.$this->ip.',球场上已有相同编号的球员');
                    $this->CI->output_json_return('player_sameas_err');
                }
            }
            
            $data_[]    = array(
                'idx'           => $params['id'],
                'position_no2'  => $params['position_no'],
                'is_use'        => $p_info['is_use']+2,
            );
        }
        
        // 2.判断$params['postion_no']是否有球员
        if ($other_info) {// 有球员，更换该球员位置
            if ($is_use == 0) {// 该球员直接上场，需要将该位置的原来球员踢上场
                $data_[]    = array(
                    'idx'           => $other_info['id'],
                    'position_no2'  => 7,
                    'is_use'        => $other_info['is_use'] - 2,
                );
            } else {
                $data_[]    = array(
                    'idx'           => $other_info['id'],
                    'position_no2'  => $p_info['position_no2'],
                    'is_use'        => $other_info['is_use'],
                );
            }
        }
        // 执行换位操作
        $this->CI->court_model->start();
        $upt    = $this->CI->court_model->update_batch($data_, "idx", "player_info");
        if (!$upt) {
            $this->CI->court_model->error();
            log_message('error', 'exchange_position_err:'.$this->ip.',球员换位失败');
            $this->CI->output_json_return('exchange_position_err');
        }
        $this->CI->court_model->success();
        return true;
    }
    
    /**
     * 普通赛场-球员换位操作
     * @param type $params
     */
    public function do_exchange_position_common($params)
    {
        $is_use = 1;// 1预定义该球员是,在操场（换位操作）
        // 查看当前需变更球员信息$params['id']
        $where  = array('idx'=>$params['id'],'manager_idx'=>$params['uuid'],'status'=>1);
        $fields = "player_no,is_use,position_no";
        $p_info = $this->CI->court_model->get_one($where,'player_info',$fields);
        // 查看当前位置是否有球员
        $where_1    = "manager_idx =".$params['uuid']." AND position_no = ".$params['position_no']." AND (is_use =1 OR is_use = 3)  AND status = 1";
        $sql        = "SELECT idx as id,is_use,position_no,player_no FROM player_info WHERE ".$where_1;
        $other_info = $this->CI->court_model->fetch($sql,'row');
        // 1.判断变更球员卡，是否在球场上
        if ($p_info['is_use'] == 1 || $p_info['is_use'] == 3) {// 在球场上上，直接换位操作
            $is_use = 1;
            $data_[] = array(
                'idx'           => $params['id'],
                'position_no'   => $params['position_no'],
                'is_use'        => $p_info['is_use'],
            );
        } else { // 不在球场上，直接上阵
            $is_use = 0;// 直接从场下 -> 到 操上（上阵操作）
            // 上阵前，检测球场上是否有相同编号的球员卡
            if ($p_info['player_no'] != $other_info['player_no']) {
                $res = $this->check_enter_player_sameas($params['uuid'], $p_info['player_no'], 1);
                if(!$res) {
                    log_message('error', 'exchange_position:player_sameas_err'.$this->ip.',球场上已有相同编号的球员');
                    $this->CI->output_json_return('player_sameas_err');
                }
            }
            
            $data_[]    = array(
                'idx'           => $params['id'],
                'position_no'   => $params['position_no'],
                'is_use'        => $p_info['is_use']+1,
            );
        }
        // 2.判断$params['postion_no']是否有球员
        if ($other_info) {// 有球员，更换该球员位置
            if ($is_use == 0) {
                $data_[]    = array(
                    'idx'           => $other_info['id'],
                    'position_no'   => 7,
                    'is_use'        => $other_info['is_use'] - 1,
                );
            } else {
                $data_[]    = array(
                    'idx'           => $other_info['id'],
                    'position_no'   => $p_info['position_no'],
                    'is_use'        => $other_info['is_use'],
                );
            }
        }
        // 执行换位操作
        $this->CI->court_model->start();
        $upt    = $this->CI->court_model->update_batch($data_, "idx", "player_info");
        if (!$upt) {
            $this->CI->court_model->error();
            log_message('error', 'exchange_position_err:'.$this->ip.',球员换位失败');
            $this->CI->output_json_return('exchange_position_err');
        }
        $this->CI->court_model->success();
        return true;
    }
    
    /**
     * 上场球员时，校验场上是都存在相同编号
     * @param int $uuid         经理idx
     * @param type $player_no   球员编号
     * @param type $type        阵容类型 1普通阵容 2天梯阵容
     */
    public function check_enter_player_sameas($uuid,$player_no,$type)
    {
        if ($type == 2) {// 天梯阵容
            $sql    = "SELECT player_no FROM player_info WHERE manager_idx = ".$uuid." AND (is_use = 2 OR is_use = 3) AND player_no = ".$player_no." AND status = 1";
        } else {// 普通阵容
            $sql    = "SELECT player_no FROM player_info WHERE manager_idx = ".$uuid." AND (is_use = 1 OR is_use = 3) AND player_no = ".$player_no." AND status = 1";
        }
        $list   = $this->CI->court_model->fetch($sql,'result');
        if (!$list) {
            return true;
        }
        return false;
    }
    
    /**
     * 获取阵型列表 -- structure_conf
     * @param type $options
     *      -- where array()
     *      -- limit array()
     */
    public function get_structure_list($options)
    {
        $structure_list = $this->CI->court_model->list_data($options, 'structure_conf');
        return $structure_list;
    }
    
    /**
     * 获取阵型列表
     * @param type $params
     */
    public function structure_list($params)
    {
        $condition      = "A.status = 1 ORDER BY A.idx ASC LIMIT ".$params['offset'].",".$params['pagesize'];
        $join_condition = "A.structure_no = B.structure_no AND B.manager_idx = ".$params['uuid']." AND B.status = 1";
        $select         = "A.idx AS id, A.structure_no structure_no, A.type type,A.type_name type_name,A.descript descript,A.lock_level AS lock_level,if(B.idx,1,0) AS `unlock`,B.is_use AS is_use,A.attradd_object attradd_object,A.attradd_percent attradd_percent";
        $tb_a           = "structure_conf AS A";
        $tb_b           = "structure AS B";
        $list           = $this->CI->court_model->get_composite_row_array($condition, $join_condition, $select, $tb_a, $tb_b,true);
        if (!$list) {
            return false;
        }
        
        foreach ($list as $k=>$val) {
            $v['is_use'] = 0;
            if ($params['type'] == 2) {// 天梯阵容列表
                if ($val['is_use'] == 2 || $val['is_use'] == 3) {
                    $v['is_use'] = 1;
                }
            } else {
                if ($val['is_use'] == 1 || $val['is_use'] == 3) {
                    $v['is_use'] = 1;
                }
            }
            $list_[]    = array_merge($val,$v);
        }
        return $list_;
    }
    
    /**
     * 获取经理已解锁|正在使用的 阵型列表
     * @param type $options
     *      -- where array("manager_idx" => )
     * @return array
     */
    public function structure_unlock($options)
    {
        $structure_list = $this->CI->court_model->list_data($options, 'structure');
        return $structure_list;
    }
    
    /**
     * 更新经理阵型（当前使用阵型）
     * @param type $params
     */
    public function update_structure_info($params)
    {
        // 判断该阵型是否已解锁$params['id']
        $where          = array('idx'=>$params['id'], 'status' => 1);
        $structure_info = $this->CI->court_model->get_one($where, 'structure_conf');
        if (!$structure_info) {
            log_message('error', 'structure_empty_data:'.$this->ip.',该阵型不存在');
            $this->CI->output_json_return('structure_empty_data');
        }
        $where = array('manager_idx'=> $params['uuid'], 'structure_no'=>$structure_info['structure_no'], 'status'=>1);
        $exists = $this->CI->court_model->get_one($where,'structure','idx as id,is_use,structure_no');
        if (!$exists) {
            log_message('error', 'structure_lock_err:'.$this->ip.',该阵型暂未解锁');
            $this->CI->output_json_return('structure_lock_err');
        }
        // 修改相同阵型
        if ($params['type'] == 2  && $exists['is_use'] >= 2 || $params['type'] !=2 && ($exists['is_use'] == 1 || $exists['is_use'] == 3)) {
            // 触发成就 - 战斗力
            $this->load_library('task_lib');
            $this->CI->task_lib->achieve_fighting($params['uuid']);
            //触发任务 更换阵型
            $this->CI->utility->get_task_status($params['uuid'] , 'update_structure');
            return $structure_info;
        }
        $this->CI->court_model->start();
        // 更新阵型--删除原使用阵型
        $table  = 'structure';
        if ($params['type'] == 2) {
            $sql    = "UPDATE ".$table." SET is_use = is_use - 2 WHERE manager_idx = ".$params['uuid']." AND is_use >= 2 AND status = 1";
            // 使用更新后的阵型
            $sql2   = "UPDATE ".$table." SET is_use = is_use + 2 WHERE idx = ".$exists['id']." AND status = 1";
        } else {
            $sql    = "UPDATE ".$table." SET is_use = is_use - 1 WHERE manager_idx = ".$params['uuid']." AND (is_use=1 OR is_use = 3) AND status = 1"; 
            // 使用更新后的阵型
            $sql2   = "UPDATE ".$table." SET is_use = is_use + 1 WHERE idx = ".$exists['id']." AND status = 1";
        }
        $res    = $this->CI->court_model->fetch($sql,'update');
        if (!$res) {
            log_message('error', 'update_structure_err:'.$this->ip.',经理使用阵型更换失败');
            $this->CI->court_model->error();
            $this->CI->output_json_return('structure_update_err');
        }
        // 使用更新后的阵型
        $res2    = $this->CI->court_model->fetch($sql2,'update');
        if (!$res2) {
            log_message('error', 'update_structure_err:'.$this->ip.',经理使用阵型更换失败');
            $this->CI->court_model->error();
            $this->CI->output_json_return('structure_update_err');
        }
        
        //记录刷新阵型
        $data   = array(
            'manager_idx'   => $params['uuid'],
            'structure_no'  => $params['id'],
            'is_use'        => $params['type'],
            'status'        => 1,
        );
        $res    = $this->CI->court_model->insert_data($data,'structure_his');
        if (!$res) {
                log_message('error', 'update_structure_err:'.$this->ip.',记录经理更新阵容失败');
                $this->CI->court_model->error();
                $this->CI->output_json_return('structure_his_err');
            }
        // 触发成就 - 战斗力
        $this->load_library('task_lib');
        $this->CI->task_lib->achieve_fighting($params['uuid']);
        // 触发成就 - 解锁全部阵型
        $this->CI->task_lib->add_structure($params['uuid']);
        // 触发新手引导 12 - 解锁第二个阵型【此处触发添加已改成--经理到达3level触发 2017-04-27 17:55:00】
        // $this->CI->task_lib->unlock_structure_2($params['uuid']);
        //触发任务 更换阵型
        $this->CI->utility->get_task_status($params['uuid'] , 'update_structure');
        $this->CI->court_model->success();
        return $structure_info;
    }
    
    /**
     * 获取阵型信息
     * @param type $params
     */
    public function get_structure_info($params)
    {
        $where          = array('idx' => $params['id'], 'status' => 1);
        $structure_info = $this->CI->court_model->get_one($where, 'structure_conf');
        if (!$structure_info) {
            log_message('info', 'empty_data:'.$this->ip.',暂无阵型数据');
            $this->CI->output_json_return('structure_empty_data');
        }
        $structure_info['unlock'] = 0;
        $structure_info['is_use'] = 0;
        
        // 判断该阵型是否已解锁|正在使用
        $where_1    = array('structure_idx' => $params['id'], 'manager_idx'=>$params['uuid'], 'status' => 1);
        $info       = $this->CI->court_model->get_one($where_1, 'structure');
        if ($info) {
            $structure_info['unlock'] = 1;
            if ($info['is_use']) {
                $structure_info['is_use'] = 1;
            }
        }
        
        return $structure_info;
    }
    
    /**
     * 获取球员卡 升阶信息
     * @param type $level 球员卡当前 阶级
     * @return type
     */
    public function get_player_upgrade_info($params)
    {
        $p_info = $this->get_player_info($params);
        if (!$p_info) {
            log_message('info', 'get_player_upgrade_info:player_not_exist'.$this->ip.',该球员卡不存在');
            $this->CI->output_json_return('player_not_exist');
        }
        $player_info    = $this->CI->utility->recombine_attr($p_info);
        foreach ($player_info['attribute'] as $k=>$v) {
            $attribute[$k]  = round($v*1.05,1);
        }
        
        $data['id']         = $params['id'];
        $data['player_no']  = $player_info['player_no'];
        $data['level']      = $player_info['level'];
        $data['next_level'] = $player_info['level']+1;
        // 统计球员的综合属性值
        $attribute = $this->get_player_attribute_sum($attribute, $params['id'], 1, 1);
        $data['attr_info']  = $attribute;
        
        // 获取升阶信息
        $where          = array('quality'=>$p_info['quality'],'level' => $p_info['level']+1,'status' => 1);
        $fields         = "idx as id,quality,level,prop";
        $upgrade_info   = $this->CI->court_model->get_one($where, 'pupgrade_conf',$fields);
        if (!$upgrade_info) {
            log_message('info', 'empty_data:'.$this->ip.',get_player_upgrade_info empty data');
            $this->CI->output_json_return('player_updrade_empty_data');
        }
        $pconf_info      = explode("|", trim($upgrade_info['prop'],"|"));
        foreach ($pconf_info as $k=>$v) {
            $arr = explode(":", $v);
            $prop_info[$k]['prop_no']   = $arr[0];
            $prop_info[$k]['total_num'] = $arr[1];
            // 查看当前拥有多少道具
            $where  = array('prop_no'=>$arr[0],'manager_idx'=>$params['uuid'],'status'=>1);
            $prop = $this->CI->court_model->get_one($where,'prop','num');
            $prop_info[$k]['num']       = $prop['num']?$prop['num']:0;
        }
        $data['prop_info']  = $prop_info;
        
        // 获取技能
        if ($player_info['generalskill_no']) {
            $skill_info['skill_no']     = $player_info['generalskill_no'];
            $skill_info['next_status']  = 0;
            if ($player_info['level'] >= 3) {
                $skill_info['next_status']  = 1;
            } else if($player_info['level'] >= 2){
                $skill_info['next_status']  = 1;
            }
            if ($player_info['generalskill_level'] >= 5) {
                // 获取技能描述
                $where_1    = array('skill_no'=>$skill_info['skill_no'],'level'=>$skill_info['level'],'status'=>1);
                $fields_1   = "descripte";
                $skill_curr = $this->CI->court_model->get_one($where_1,'skill_conf',$fields_1);
                $skill_info['next_descript']    = $skill_curr['descripte'];
                $skill_info['next_level'] = 5;
            } else {
                $where_2    = array('skill_no'=>$skill_info['skill_no'],'level'=>$skill_info['level']+1,'status'=>1);
                $fields_2   = "descripte";
                $skill_next = $this->CI->court_model->get_one($where_2,'skill_conf',$fields_2);
                $skill_info['next_descript']    = $skill_next['descripte'];
                $skill_info['next_level']       = $player_info['generalskill_level']+1;
            }
            $data['generalskill']   = $skill_info;
        }
        
        if ($player_info['exclusiveskill_no']) {
            $skill_info2['skill_no']        = $player_info['exclusiveskill_no'];
            $skill_info2['next_level']      = 1;
            $skill_info2['next_status']     = 0;
            $where_3        = array('skill_no'=>$skill_info2['skill_no'],'level'=>1,'status'=>1);
            $fields_3       = "descripte";
            $skill2_curr    = $this->CI->court_model->get_one($where_3,'skill_conf',$fields_3);
            if ($player_info['level'] >= 7) {
                $skill_info2['next_status'] = 1;
            } else if($player_info['level'] >= 6){
                $skill_info2['next_status'] = 1;
            }
            $skill_info2['next_descript']   = $skill2_curr['descripte'];
            $data['exclusiveskill']   = $skill_info2;
        }
        return $data;
    }
    
    /**
     * 球员进阶操作
     * @param type $params
     */
    public function do_player_upgrade($params)
    {
        // 获取球员基础信息
        $p_info = $this->get_player_info($params);
        if ($p_info['level'] == 9) {
            $this->CI->output_json_return('highest_level_err');
        }
        $where          = array('quality'=>$p_info['quality'],'level' => $p_info['level']+1,'status' => 1);
        $fields         = "idx as id,quality,level,prop";
        $upgrade_info   = $this->CI->court_model->get_one($where, 'pupgrade_conf',$fields);
        // 判断升阶所需的道具，是否足够
        $pconf_info      = explode("|", trim($upgrade_info['prop'],"|"));
        $this->CI->court_model->start();
        foreach ($pconf_info as $k=>$v) {
            $arr = explode(":", $v);
            // 查看当前拥有多少道具
            $where  = array('prop_no'=>$arr[0],'manager_idx'=>$params['uuid'],'status'=>1);
            $prop = $this->CI->court_model->get_one($where,'prop','num');
            if(!$prop){
                $prop['num'] = 0;
            }
            if ($prop['num'] < $arr[1]) {
                $this->CI->court_model->error();
                $this->CI->output_json_return('upgrade_prop_not_enought_err');
            }
            // 扣除道具
            if ($prop['num']-$arr[1] <1) {
                $fields = array('status'=>0,'num'=>0);
            } else {
                $fields = array('num'=>$prop['num'] - $arr[1]);
            }
            $where  = array('manager_idx'=>$params['uuid'],'prop_no'=>$arr[0],'status'=>1);
            $res = $this->CI->court_model->update_data($fields,$where,'prop');
            if (!$res) {
                $this->CI->court_model->error();
                $this->CI->output_json_return('player_upgrade_err');
            }
        }
        // 升级球员卡 -- 升级属性值、球员卡level、技能level
        $player_info        = $this->CI->utility->recombine_attr($p_info);
        $fields  = $this->CI->utility->attribute_change($player_info['attribute']);
        $fields['level']    = $p_info['level']+1;
        if ($p_info['generalskill_no'] && $p_info['generalskill_level'] < 5) {
            $fields['generalskill_level']   = $p_info['generalskill_level']+1;
        }
        $where  = array('idx'=>$params['id'], 'manager_idx'=>$params['uuid'], 'status'=>1);
        $res = $this->update_player_info($fields, $where);
        if (!$res) {
            $this->CI->court_model->error();
            $this->CI->output_json_return('player_upgrade_err');
        }
        // 球员进阶历史记录
        $data = array(
            'manager_idx'       => $params['uuid'],
            'player_idx'        => $params['id'],
            'quality'           => $p_info['quality'],
            'level'             => $p_info['level'],
            'curr_level'        => $p_info['level']+1,
            'prop'              => $upgrade_info['prop'],
            'status'            => 1,
        );
        $res = $this->player_upgrade_history($data);
        if (!$res) {
            log_message('error', 'insert_player_upgrade_history_error:'.$this->ip.'球员进阶成功历史记录失败');
            $this->CI->court_model->error();
            $this->CI->output_json_return('player_upgrade_history_err');
        }
        //触发任务 升级球员
        $this->CI->utility->get_task_status($params['uuid'], 'player_upgrade');
        $this->load_library('task_lib');
        // 触发成就 - 进阶达人
        $this->CI->task_lib->player_upgrade($params['uuid']);
        // 触发成就 - 培养大师
        $this->CI->task_lib->player_update($params['uuid']);
        $this->CI->court_model->success();
        return true;
    }
    
    /**
     * 球员进阶历史记录
     * @param type $data
     * @return type
     */
    public function player_upgrade_history($data)
    {
        $res = $this->CI->court_model->insert_data($data, 'pupgrade_his');
        return $res;
    }
    
    /**
     * 判断经理是否存在空训练位
     */
    public function vacant_train_posi($uuid)
    {
        $open_count = $this->CI->court_model->total_count('idx', array('manager_idx'=>$uuid,'status'=>1), 'trainground_open_his');
        $curr_count = $this->CI->court_model->total_count('idx',array('manager_idx'=>$uuid,'complete'=>0,'status'=>1),'train_his');
        if ($open_count <= $curr_count) {// 无空训练位
            return false;
        }
        return true;
    }
    
    /**
     * 判断该训练位是否可训练球员
     * @param array $params
     */
    public function can_do_train($params)
    {
        // 判断该训练位是否解锁
        $open       = $this->CI->court_model->get_one(array('manager_idx'=>$params['uuid'],'tg_no'=>$params['tg_no'],'status'=>1),'tgunlock_his');
        if (!$open) {
            return false;
        }
        // 判断该训练位是否正在使用中
        $curr_use   = $this->CI->court_model->get_one(array('manager_idx'=>$params['uuid'],'tg_no'=>$params['tg_no'],'status'=>1),'train_curr');
        if ($curr_use) {
            return false;
        }
        return true;
    }
    
    /**
     * 获取训练场详细信息
     */
    public function get_trainground_info($params)
    {
        $options_1          = array('where'=>array('status'=>1));
        $trainground_list   = $this->CI->court_model->list_data($options_1,'trainground_conf');
        
        $options_2          = array('where'=>array('manager_idx'=>$params['uuid'],'status'=>1));
        $open_trainground   = $this->CI->court_model->list_data($options_2,'tgunlock_his',"tg_no");
        foreach ($trainground_list as $key=>$val) {
            $new_train[$key]['id']          = $val['idx'];
            $new_train[$key]['tg_no']       = $val['tg_no'];
            $new_train[$key]['level']       = $val['level'];
            $new_train[$key]['vip_level']   = $val['vip_level'];
            $new_train[$key]['tickets']     = $val['tickets'];
            $new_train[$key]['unlock']      = 0;
            $new_train[$key]['is_train']    = 0;
            $new_train[$key]['train_info']  = array();
            foreach ($open_trainground as $k=>$v) {
                if ((int)$val['tg_no'] === (int)$v['tg_no']) {
                    $new_train[$key]['unlock']        = 1;
                    $where   = array('manager_idx' => $params['uuid'],'tg_no'=>$v['tg_no'], 'status' => 1);
                    $fields  = "player_idx as id,start_time,end_time,type,attribute,complete";
                    $current_info = $this->CI->court_model->get_one($where, 'train_curr',$fields);
                    if ($current_info) {
                        $new_train[$key]['is_train']    = 1;
                        $params['id']                   = $current_info['id'];
                        $player_info                    = $this->get_player_info($params);
                        if (time() >= $current_info['end_time'] || $current_info['complete']) {
                            $current_info['complete']   = 1;
                        } else {
                            $current_info['complete']   = 0;
                        }
                        $current_info['fatigue']        = $player_info['fatigue'];
                        $current_info['fatigue_total']  = 100;
                        $current_info['surplus_time']   = ($current_info['end_time'] - $current_info['start_time'])-(time()-$current_info['start_time']);
                        $new_train[$key]['train_info']  = $current_info;
                    }  else {
                        $new_train['is_train']          = 0;
                        $new_train[$key]['train_info']    = array();
                    }
                }
            }
        }
        return $new_train;
    }
    
    /**
     * 判断球员是否正在训练
     * @param type $params
     */
    public function is_train($params)
    {
        $where              = array('manager_idx' => $params['uuid'], 'player_idx' =>$params['id'],'status' => 1);
        $train_current_info = $this->CI->court_model->get_one($where, 'train_curr');
        return $train_current_info;
    }
    
    /**
     * 执行训练操作
     * @param type $params
     * @return boolean
     */
    public function do_train($params)
    {
        // 校验球员疲劳值
        $player_info    = $this->get_player_info($params);
        if ($params['type'] == 1) {
            $params['time'] = 3;
            $fatigue = $params['time'] * 10; // 消耗疲劳值
            $getpoint   = 1;
        } else {
            $params['time'] = 8;
            $fatigue = $params['time'] * 10;
            $getpoint   = 3;
        }
        if (($this->CI->passport->get('fatigue_max') - $player_info['fatigue']) <  $fatigue) {
            log_message('error', 'fatigue_not_enough:'.$this->ip.',球员疲劳点不够');
            $this->CI->output_json_return('fatigue_not_enough');
        }
        
        $this->CI->court_model->start();
        // 执行训练操作
        $fields = array(
            'manager_idx'   => $params['uuid'],
            'tg_no'         => $params['tg_no'],
            'player_idx'    => $params['id'],
            'start_time'    => time(),
            'end_time'      => time() + ($params['time'])*3600,
            'type'          => $params['type'],
            'attribute'     => $params['att_no'],
            'getpoint'      => $getpoint,
            'point'         => $getpoint,
            'complete'      => 0,
            'tickets'       => 0,
            'status'        => 1,
        );
        $res = $this->CI->court_model->insert_data($fields, 'train_curr');
        if (!$res) {
            log_message('error', 'insert_train_current_error'.$this->ip.',球员当前训练操作插入失败');
            $this->CI->court_model->error();
            $this->CI->output_json_return('do_train_err');
        }
                
        // 扣除球员的疲劳值
        $fields_2   = array('fatigue' => $fatigue+$player_info['fatigue']);
        $where      = array('idx'   => $params['id'], 'status'  => 1);
        $upt_res    = $this->update_player_info($fields_2, $where);
        if (!$upt_res) {
            log_message('error', 'update_train_curr_error'.$this->ip.',球员训练疲劳值跟新失败');
            $this->CI->court_model->error();
            $this->CI->output_json_return('do_train_err');
        }
        //记录训练历史表
        $fields2 = array(
            'manager_idx'   => $params['uuid'],
            'tg_no'         => $params['tg_no'],
            'player_idx'    => $params['id'],
            'start_time'    => time(),
            'end_time'      => time() + ($params['time'])*3600,
            'type'          => $params['type'],
            'attribute'     => $params['att_no'],
            'getpoint'      => $getpoint,
            'train_end'      => 0,
            'tickets'       => 0,
            'status'        => 1,
        );
        $res = $this->CI->court_model->insert_data($fields2, 'train_his');
        if (!$res) {
            log_message('error', 'do_train：train_his_insert_fail_err'.$this->ip.',插入训练历史记录失败');
            $this->CI->court_model->error();
            $this->CI->output_json_return('train_his_insert_fail_err');
        }
        //触发任务 训练球员
        $this->CI->utility->get_task_status($params['uuid'] , 'train');
        $this->CI->court_model->success();
        return true;
    }
    
    /**
     * 训练位解锁
     * @param type $params
     * @return type
     */
    public function train_unlock($params)
    {
        // 校验该训练位是否 解锁
        $where_1    = array('manager_idx'=>$params['uuid'],'tg_no'=>$params['tg_no'],'status'=>1);
        $fields_1   = "idx";
        $info       = $this->CI->court_model->get_one($where_1, 'tgunlock_his',$fields_1);
        if ($info) {
            log_message('error', 'trainground_exists_err'.$this->ip.',训练场已解锁');
            $this->CI->output_json_return('trainground_exists_err');
        }
        
        $where      = array('tg_no'=>$params['tg_no'],'status'=>1);
        $fields     = "level,tickets,vip_level";
        $tg_info    = $this->CI->court_model->get_one($where, 'trainground_conf',$fields);
        $m_info     = $this->CI->utility->get_manager_info($params);
        if ($m_info['level'] < $tg_info['level']) {
            log_message('error', 'm_level_not_enought'.$this->ip.',经理等级不足');
            $this->CI->output_json_return('m_level_not_enought');
        }
        if ($m_info['tickets'] < $tg_info['tickets']) {
            log_message('error', 'not_enough_tickets_err'.$this->ip.',经理球票不足');
            $this->CI->output_json_return('not_enough_tickets_err');
        }
        if ($m_info['vip'] < $tg_info['vip_level']) {
            log_message('error', 'vip_level_not_enought'.$this->ip.',经理vip等级不足');
            $this->CI->output_json_return('vip_level_not_enought');
        }
        $this->CI->court_model->start();
        if ($tg_info['tickets']) {
            $fields = array('tickets'=>$m_info['tickets'] - $tg_info['tickets']);
            $where  = array('idx'=>$params['uuid'],'status'=>1);
            $res = $this->CI->utility->update_m_info($fields,$where);
            if (!$res) {
                $this->CI->court_model->error();
                log_message('error', 'm_info_update_err'.$this->ip.',经理信息更新失败');
                $this->CI->output_json_return('m_info_update_err');
            }
        }
        $data   = array(
            'manager_idx'   => $params['uuid'],
            'tg_no'         => $params['tg_no'],
            'level'         => $m_info['level'],
            'vip_level'     => $m_info['vip'],
            'tickets'       => $tg_info['tickets'],
            'status'        => 1,
        );
        $ist_res = $this->CI->court_model->insert_data($data, 'tgunlock_his');
        if (!$ist_res) {
            $this->CI->court_model->error();
            log_message('error', 'train_unlock_err'.$this->ip.',训练场解锁失败');
            $this->CI->output_json_return('train_unlock_err');
        }
        $this->load_library('task_lib');
        // 触发成就 - 训练大师
        $this->CI->task_lib->train_unlock($params['uuid']);
        $this->CI->court_model->success();
        return true;
    }
    
    /**
     * 取消训练时间
     */
    public function clear_train_time($params)
    {
        // 获取 该球员训练状态（是否正在训练）
        $train_info = $this->train_info($params);
        if (!$train_info) {
            log_message('error', 'get_train_info_error:'.$this->ip.',获取球员训练信息失败');
            $this->CI->output_json_return('player_without_train');
        }
        if ($train_info['type'] == 1) {
            $train_point    = 1;    // 获得的训练点数
            $time           = 3;    // 训练正常花费时间 
            $tickets        = 3;    // 3球票
        }else if ($train_info['type'] == 2) {
            $train_point    = 3;
            $time           = 8;
            $tickets        = 8;
        }
        
        // 校验经理球票是否够
        $m_info     = $this->CI->utility->get_manager_info($params);
        if ($m_info['tickets'] < $tickets) {
            log_message('error', 'not_enough_tickets_err:'.$this->ip.',经理球票不足');
            $this->CI->output_json_return('not_enough_tickets_err');
        }
        // 开启事务
        $this->CI->court_model->start();
        // 更新训练完成记录
        $where  = array('manager_idx'=>$params['uuid'],'player_idx'=>$params['id'],'status'=>1);
        $fields = array('complete'  => 1,'tickets'=>$tickets);
        $res = $this->update_train_info($fields,$where);
        if (!$res) {
            $this->CI->court_model->error();
            $this->CI->output_json_return('tickets_clear_err');
        }
        
        // 扣除经理球票数
        $fields     = array('tickets'=>$m_info['tickets'] - $tickets);
        $where      = array('idx'=>$params['uuid'],'status'=>1);
        $upt_res    = $this->CI->utility->update_m_info($fields,$where);
        if (!$upt_res) {
            log_message('error', 'update_manager_info_err:'.$this->ip.',经理球票数量更新失败');
            $this->CI->court_model->error();
            $this->CI->output_json_return('tickets_clear_err');
        }
        $this->CI->court_model->success();
        return true;
    }
    
    /**
     * 获取球员训练信息
     */
    public function train_info($params)
    {
        $where      = array('manager_idx' => $params['uuid'], 'player_idx' => $params['id'],'complete'=>0,'status' => 1);
        $train_info = $this->CI->court_model->get_one($where, 'train_curr');
        return $train_info;
    }
    
    /**
     * 删除球员训练信息（正在训练，用球票秒掉时间时）
     * @param type $params
     * @return type
     */
    public function update_train_info($fields,$where)
    {
        $res = $this->CI->court_model->update_data($fields,$where, 'train_curr');
        return $res;
    }
    
    /**
     * 获取球员训练点数
     * @param type $params
     */
    public function get_trainpoint_info($params)
    {
        // 获取已分配的训练点-属性值
        $sql    = "SELECT attribute,sum(towlevel_value) as value FROM trainpoint_allo_his WHERE manager_idx=".$params['uuid']." AND player_idx=".$params['id']." AND data_status = 0 AND status = 1 GROUP BY attribute";
        $result = $this->CI->court_model->fetch($sql);
        
        if($result) {
            foreach ($result as $k=>$v) {
                $arr_1[$v['attribute']] = $v['value'];
            }
        } else {
            $arr_1 = array();
        }
        $options['where']   = array('level'=>1,'status'=>1); 
        $attr_info  = $this->CI->court_model->list_data($options,'attribute_conf','attr_no');
        foreach ($attr_info as $k=>$v) {
            $arr_2[$v['attr_no']] = 0;
        }
        $array  = $arr_1+$arr_2;
        foreach ($array as $k=>$v) {
            $trainpoint[$k]['attribute']    = $k;
            $trainpoint[$k]['value']        = $v;
            $trainpoint[$k]['value_total']  = 120;
        }
        sort($trainpoint);
        // 获取未分配的训练点数
        $where      = array('manager_idx'=>$params['uuid'],'player_idx'=>$params['id'],'status'=>1);
        $fields     = "point";
        $curr_info  = $this->CI->court_model->get_one($where,'train_curr',$fields);
        // 获取经理当前未分配的训练点数
        $m_info     = $this->CI->utility->get_manager_info($params);
        $data['id'] = $params['id'];
        $where  = array('idx'=>$params['id'],'status'=>1);
        $data['player_no']  = $this->CI->court_model->get_one($where,'player_info',"player_no")['player_no'];
        $data['surplus_point'] = (int)$m_info['trainpoint']+(int)$curr_info['point'];
        $data['trainpoint'] = $trainpoint;
        return $data;
    }
    
    /**
     * 获取球员一级属性下的训练点数
     * @param type $params
     */
    public function get_attrpoint_info($params)
    {
        // 获取已分配的训练点-属性值
        $sql    = "SELECT towlevel_attr,sum(towlevel_value) as value FROM trainpoint_allo_his WHERE manager_idx=".$params['uuid']." AND player_idx=".$params['id']." AND attribute=".$params['attribute']." AND data_status = 0 AND status = 1 GROUP BY towlevel_attr";
        $result = $this->CI->court_model->fetch($sql);
        if($result) {
            foreach ($result as $k=>$v) {
                $arr_1[$v['towlevel_attr']] = $v['value'];
            }
        } else {
            $arr_1 = array();
        }
        $options['where']   = array('parent_no'=>$params['attribute'],'status'=>1); 
        $attr_info  = $this->CI->court_model->list_data($options,'attribute_conf','attr_no');
        foreach ($attr_info as $k=>$v) {
            $arr_2[$v['attr_no']] = 0;
        }
        $array  = $arr_1+$arr_2;
        foreach ($array as $k=>$v) {
            if ($k == 104 || $k== 105) {
                continue;
            }
            $trainpoint[$k]['attribute']    = $k;
            $trainpoint[$k]['value']        = $v;
            $trainpoint[$k]['value_total']  = 40;
        }
        sort($trainpoint);
        // 获取未分配的训练点数
        $where      = array('manager_idx'=>$params['uuid'],'player_idx'=>$params['id'],'attribute'=>$params['attribute'],'status'=>1);
        $fields     = "point";
        $curr_info  = $this->CI->court_model->get_one($where,'train_curr',$fields);
        // 获取经理当前未分配的训练点数
        $m_info     = $this->CI->utility->get_manager_info($params);
        $data['id'] = $params['id'];
        $where      = array('idx'=>$params['id'],'status'=>1);
        $data['player_no']      = $this->CI->court_model->get_one($where,'player_info',"player_no")['player_no'];
        $data['surplus_point']  = (int)$m_info['trainpoint']+(int)$curr_info['point'];
        $data['attribute']      = $params['attribute'];
        $data['trainpoint']     = $trainpoint;
        return $data;
    }
    
    /**
     * 执行训练点分配操作
     * @param type $params
     */
    public function allo_trainpoint($params)
    {
        // 判断该二级属性是否属于同一个一级属性
        $options['where']    = array('status'=>1);
        $options['fields']   = "attr_no,parent_no,level";
        $attr_list  = $this->CI->court_model->list_data($options,'attribute_conf');
        foreach ($attr_list as $k=>$v) {
            if ($v['attr_no'] == $params['attribute']) {
                if (!$v['parent_no']) {
                    log_message('error', 'not_add_level_one_attribute_err:'.$this->ip.',不允许给一级属性分配训练点');
                    $this->CI->output_json_return('not_add_level_one_attribute_err');
                }
                $p_no[1]    = $v['parent_no'];
                $att_no[$params['attribute']]   = $params['value'];
            }
            if ($v['attr_no'] == $params['attribute_2']) {
                $p_no[2] = $v['parent_no'];
                $att_no[$params['attribute_2']]   = $params['value_2'];
            }
            if ($v['attr_no'] == $params['attribute_3']) {
                $p_no[3] = $v['parent_no'];
                $att_no[$params['attribute_3']]   = $params['value_3'];
            }
        }
        if ($p_no[2] && $p_no[2] != $p_no[1]) {
            log_message('error', 'same_one_attribute_err:'.$this->ip.',只能分配给同一个一级属性下的二级属性');
            $this->CI->output_json_return('same_one_attribute_err');
        }
        if ($p_no[3] && $p_no[3] != $p_no[1]) {
            log_message('error', 'same_one_attribute_err:'.$this->ip.',只能分配给同一个一级属性下的二级属性');
            $this->CI->output_json_return('same_one_attribute_err');
        }
        
        // 判断当前训练是否完成
        $where      = array('player_idx'=>$params['id'],'manager_idx'=>$params['uuid'],'attribute'=>$p_no[1],'status'=>1);
        $fields     = "attribute,getpoint,point,end_time,start_time,complete";
        $curr_info  = $this->CI->court_model->get_one($where, 'train_curr', $fields);
        if (time() < $curr_info['end_time'] && !$curr_info['complete']) {
            log_message('error', 'allo_trainpoint:player_training,'.$this->ip.',该球员训练未完成，咱不能分配训练点');
            $this->CI->output_json_return('player_training');
        }
                
        // 判断该球员空闲的训练点数是否足够
        $m_info     = $this->CI->utility->get_manager_info($params);
        $curr_point = $m_info['trainpoint'] + $curr_info['point'];
        $allo_point = $params['value'] + $params['value_2'] + $params['value_3'];
        if ($allo_point > $curr_point) {
            log_message('error', 'not_enought_trainpoint_err:'.$this->ip.',没有足够的训练点数');
            $this->CI->output_json_return('not_enought_trainpoint_err');
        }
        
        // 判断训练属性值上限40
        $sql    = "SELECT towlevel_attr,SUM(towlevel_value) AS value FROM trainpoint_allo_his WHERE manager_idx=".$params['uuid']." AND player_idx=".$params['id']." AND attribute=".$p_no[1]." AND status = 1 GROUP BY towlevel_attr";
        $allo_list  = $this->CI->court_model->fetch($sql);
        foreach ($allo_list as $k=>$v) {
            foreach ($att_no as $key=>$val) {
                if ($key == $v['towlevel_attr']) {
                    if ($v['value'] + $val > 40) {
                        log_message('error', 'point_upper_limit_err:'.$this->ip.',超过训练点上限');
                        $this->CI->output_json_return('point_upper_limit_err');
                    }
                }
            }
        }
        
        $this->CI->court_model->start();
        // 记录训练点分配历史
        $data[] = array(
            'manager_idx'       => $params['uuid'],
            'player_idx'        => $params['id'],
            'trainpoint'        => $params['value'],
            'attribute'         => $p_no[1],
            'towlevel_attr'     => $params['attribute'],
            'towlevel_value'    => $params['value'],
            'allow_reset'       => 1,
            'data_status'       => 0,
            'status'            => 1,
            'time'              => time(),
            'update_time'       => time(),
            );
        if ($params['attribute_2']) {
            $data[] = array(
                'manager_idx'       => $params['uuid'],
                'player_idx'        => $params['id'],
                'trainpoint'        => $params['value_2'],
                'attribute'         => $p_no[1],
                'towlevel_attr'     => $params['attribute_2'],
                'towlevel_value'    => $params['value_2'],
                'allow_reset'       => 1,
                'data_status'       => 0,
                'status'            => 1,
                'time'              => time(),
                'update_time'       => time(),
            );
        }
        if($params['attribute_3']) {
            $data[] = array(
                'manager_idx'       => $params['uuid'],
                'player_idx'        => $params['id'],
                'trainpoint'        => $params['value_3'],
                'attribute'         => $p_no[1],
                'towlevel_attr'     => $params['attribute_3'],
                'towlevel_value'    => $params['value_3'],
                'allow_reset'       => 1,
                'data_status'       => 0,
                'status'            => 1,
                'time'              => time(),
                'update_time'       => time(),
            );
        }
        $ist_res    = $this->CI->court_model->insert_batch($data, 'trainpoint_allo_his');
        if (!$ist_res) {
            log_message('error', 'allo_trainpoint_err:'.$this->ip.',训练点分配历史记录插入失败');
            $this->CI->court_model->error();
            $this->CI->output_json_return('allo_trainpoint_err');
        }
        
        // 扣除训练点数
        if ($curr_info['point'] >= $allo_point) {
            // 只扣除该球员的训练点数
            $fields_2   = array('point' => $curr_info['point'] - $allo_point);
            $upt_res = $this->CI->court_model->update_data($fields_2, $where,'train_curr');
            if (!$upt_res) {
                log_message('error', 'trainpoint_deduct_err:'.$this->ip.',训练点数扣除失败');
                $this->CI->court_model->error();
                $this->CI->output_json_return('trainpoint_deduct_err');
            }
        } elseif(!$curr_info['point']) {
            // 只扣除 经理的总训练点数
            $fields_3   = array('trainpoint' => $m_info['trainpoint'] - $allo_point);
            $where_3    = array('idx'=>$params['uuid'],'status'=>1);
            $upt_res_3    = $this->CI->utility->update_m_info($fields_3, $where_3);
            if (!$upt_res_3) {
                log_message('error', 'trainpoint_deduct_err:'.$this->ip.',训练点数扣除失败');
                $this->CI->court_model->error();
                $this->CI->output_json_return('trainpoint_deduct_err');
            }
        } else {
            // 先扣除球员训练点数 再扣除总训练点数
            $fields_2   = array('point' => 0);
            $upt_res = $this->CI->court_model->update_data($fields_2, $where,'train_curr');
            if (!$upt_res) {
                log_message('error', 'trainpoint_deduct_err:'.$this->ip.',训练点数扣除失败');
                $this->CI->court_model->error();
                $this->CI->output_json_return('trainpoint_deduct_err');
            }
            
            $fields_3   = array('trainpoint' => $m_info['trainpoint'] - ($allo_point-$curr_info['point']));
            $where_3    = array('idx'=>$params['uuid'],'status'=>1);
            $upt_res    = $this->CI->utility->update_m_info($fields_3, $where_3);
            if (!$upt_res) {
                log_message('error', 'trainpoint_deduct_err:'.$this->ip.',训练点数扣除失败');
                $this->CI->court_model->error();
                $this->CI->output_json_return('trainpoint_deduct_err');
            }
        }
        
        // 触发成就 - 战斗力
        $this->load_library('task_lib');
        $this->CI->task_lib->achieve_fighting($params['uuid']);
        
        $this->CI->court_model->success();
        return true;
    }
    
    /**
     * 洗炼球员训练点（恢复该球员的所有训练点分配）
     * @param type $params
     */
    public function clear_trainpoint($params)
    {                
        // 校验经理是否有洗炼药水 (药水编号701)
        $where_3    = array('manager_idx'=> $params['uuid'], 'prop_no' => 701, 'status' => 1);
        $prop_info   = $this->get_prop_info($where_3,'num');
        if (!$prop_info) {
            log_message('error', 'clear_trainpoint_error:'.$this->court_lib->ip.',没有足够的洗炼石');
            $this->CI->output_json_return('polish_stone_not_enought');
        }
        $options['where']   = array('player_idx' => $params['id'], 'manager_idx' => $params['uuid'], 'status' => 1,'data_status'=>0);
        $options['fields']  = "trainpoint,attribute,towlevel_attr,towlevel_value";
        $info_his           = $this->CI->court_model->list_data($options, 'trainpoint_allo_his');
        if (!$info_his) {
            log_message('error', 'empty_data_trainpoint_allo_his'.$this->ip.',该球员暂无分配训练点记录');
            $this->CI->output_json_return('trainpoint_his_empty_data');
        }
        // 统计总训练点数
        $tp_value   = 0;
        foreach ($info_his as $k=>$v) {
            $tp_value   += $v['towlevel_value'];
        }
        $this->CI->court_model->start();
        // 消耗经理一瓶洗炼药水(编号701)
        $where_3    = array('manager_idx'=> $params['uuid'], 'prop_no' => 701, 'status' => 1);
        if ($prop_info['num'] -1 > 0) {
            $data_3     = array('num' =>$prop_info['num'] -1 );
        } else {
            $data_3     = array('status' =>0);
        }
        $res_3      = $this->CI->court_model->update_data($data_3, $where_3, 'prop');
        if (!$res_3) {
            log_message('error', 'update_prop_error:'.$this->ip.',经理背包表更新失败');
            $this->CI->court_model->error();
            $this->CI->output_json_return('polish_stone_error');
        }
        
        // 删除球员训练点分配历史记录
        $data       = array('data_status' => 1);
        $upt_res    = $this->CI->court_model->update_data($data, $options['where'], 'trainpoint_allo_his');
        if (!$upt_res) {
            log_message('error', 'update_trainpoint_allo_his_error:'.$this->ip.',球员训练点分配历史记录更新失败');
            $this->CI->court_model->error();
            $this->CI->output_json_return('update_trainpoint_err');
        }
        // 更新经理剩余训练点
        $m_info = $this->CI->utility->get_manager_info($params);
        $fields = array('trainpoint'=>$m_info['trainpoint'] + $tp_value);
        $where  = array('idx' =>$params['uuid'],'status'=>1);
        $upt_res = $this->CI->utility->update_m_info($fields,$where);
        if (!$upt_res) {
            log_message('error', 'm_info_update_err:'.$this->ip.',经理剩余训练点数更新失败');
            $this->CI->court_model->error();
            $this->CI->output_json_return('m_info_update_err');
        }
        $this->CI->court_model->success();
        return true;
    }
     
    /**
     * 重置训练点
     * @param type $params
     */
    public function reset_trainpoint($params)
    {
        // 判断是否允许重置训练点
        $where      = array('player_idx'=>$params['id'],'complete'=>1,'status'=>1);
        $fields     = "getpoint,point";
        $tp_info    = $this->CI->court_model->get_one($where,'train_curr',$fields);
        if (!$tp_info) {
            log_message('error', 'not_allow_reset_err:'.$this->ip.',该球员不允许重置训练点数');
            $this->CI->output_json_return('not_allow_reset_err');
        }
        // 重置训练点
        $this->CI->court_model->start();
        $total_point= $tp_info['getpoint'];
        $where_2    = array('manager_idx'=>$params['uuid'],'player_idx'=>$params['id'],'status'=>1);
        $fields_2   = array('point'=>$total_point);
        $res        = $this->CI->court_model->update_data($fields_2,$where_2,'train_curr');
        if (!$res) {
            log_message('error', 'reset_trainpoint_err:'.$this->ip.',重置训练点失败');
            $this->CI->court_model->error();
            $this->CI->output_json_return('reset_trainpoint_err');
        }
        
        // 更新训练点分配表
        $where      = array('player_idx'=>$params['id'],'allow_reset'=>1,'data_status'=>0,'status'=>1);
        $fields     = array('allow_reset'=>0,'data_status'=>2);
        $upt_res = $this->CI->court_model->update_data($fields,$where,'trainpoint_allo_his');
        if (!$upt_res) {
            log_message('error', 'reset_trainpoint_err:'.$this->ip.',重置训练点失败');
            $this->CI->court_model->error();
            $this->CI->output_json_return('reset_trainpoint_err');
        }
        
        // 扣除经理球票
        $tickets    = ($tp_info['getpoint'] - $tp_info['point'])*2;
        $m_info     = $this->CI->utility->get_manager_info($params);
        $fields_3   = array('tickets'=>$m_info['tickets'] - $tickets);
        $where_3    = array('idx'=>$params['uuid'],'status'=>1);
        $res        = $this->CI->utility->update_m_info($fields_3, $where_3);
        if (!$res) {
            log_message('error', 'reset_trainpoint_err:'.$this->ip.',重置训练点失败');
            $this->CI->court_model->error();
            $this->CI->output_json_return('reset_trainpoint_err');
        }
        $this->CI->court_model->success();
        return true;
    }
    
    /**
     * 释放训练位
     * @param type $params
     * @return boolean
     */
    public function release_tg($params)
    {
        // 校验该训练是否完成 =》 训练点是否分配完成
        $where_1    = array('player_idx'=>$params['id'],'status'=>1);
        $tg_info    = $this->CI->court_model->get_one($where_1,'train_curr');
        if (!$tg_info) {
            log_message('error', 'release_tg:not_need_release_err'.$this->ip.',该球员不在训练状态');
            $this->CI->output_json_return('not_need_release_err');
        }
        if (time() < $tg_info['end_time'] && $tg_info['complete'] == 0) {
            log_message('error', 'train_not_complete_err:'.$this->ip.',训练未完成,不允许释放训练位');
            $this->CI->output_json_return('train_not_complete_err');
        }
        if ($tg_info['point']) {
            log_message('error', 'trainpoint_overplus_err:'.$this->ip.',训练点未分配完成,不允许释放训练位');
            $this->CI->output_json_return('trainpoint_overplus_err');
        }
        $this->CI->court_model->start();
        // 释放球员训练位
        $where      = array('player_idx'=>$params['id'],'status'=>1);
        $upt_res    = $this->CI->court_model->delete_data($where,'train_curr');
        if (!$upt_res) {
            log_message('error', 'release_tg_err:'.$this->ip.',训练位释放失败');
            $this->CI->court_model->error();
            $this->CI->output_json_return('release_tg_err');
        }
        // 更新训练点分配表，禁止该球员重置操作
        $fields_2   = array('allow_reset'=>0);
        $where_2    = array('player_idx'=>$params['id'],'allow_reset'=>1,'manager_idx'=>$params['uuid'],'data_status'=>0,'status'=>1);
        $upt_res2   = $this->CI->court_model->update_data($fields_2, $where_2,'trainpoint_allo_his');
        if (!$upt_res2) {
            log_message('error', 'release_tg_err:'.$this->ip.',训练位释放失败');
            $this->CI->court_model->error();
            $this->CI->output_json_return('release_tg_err');
        }
        $this->CI->court_model->success();
        return true;
    }
    
    /**
     * 清空球员疲劳值
     */
    public function do_clear_fatigue($params)
    {
        $where  = array('idx' => $params['id'],'manager_idx'=>$params['uuid'],'status'=>1);
        $fields = "fatigue";
        $p_info = $this->CI->court_model->get_one($where,'player_info',$fields);
        if (!$p_info) {
            log_message('error', 'do_clear_fatigue:player_id_err'.$this->ip.',暂无该球员');
            $this->CI->output_json_return('player_id_err');
        }
        if (!$p_info['fatigue']) {
            log_message('error', 'do_clear_fatigue:without_fatigue_err'.$this->ip.',暂无疲劳值，不需要清空');
            $this->CI->output_json_return('without_fatigue_err');
        }
        $m_info     = $this->CI->utility->get_manager_info($params);
        $tickets    = $this->CI->passport->get('clear_fatigue_tickets');
        if ($tickets > $m_info['tickets']) {
            log_message('error', 'do_clear_fatigue:without_fatigue_err'.$this->ip.',球票不足');
            $this->CI->output_json_return('not_enough_tickets_err');
        }
        
        $this->CI->court_model->start();
        $fields_2   = array('fatigue'=>0);
        $upt_res    = $this->CI->court_model->update_data($fields_2,$where,'player_info');
        if (!$upt_res) {
            $this->CI->court_model->error();
            log_message('error', 'do_clear_fatigue:fatigue_clear_fail_err'.$this->ip.',疲劳值清空失败');
            $this->CI->output_json_return('fatigue_clear_fail_err');
        }
        $fields_3   = array('tickets'=>$m_info['tickets'] - $tickets);
        $where_3    = array('idx'=>$params['uuid'],'status'=>1);
        $upt_m      = $this->CI->utility->update_m_info($fields_3,$where_3);
        if (!$upt_m) {
            $this->CI->court_model->error();
            log_message('error', 'do_clear_fatigue:m_info_update_err'.$this->ip.',经理球票更新失败');
            $this->CI->output_json_return('m_info_update_err');
        }
        //记录清除疲劳值
        $data   = array(
            'manager_idx' => $params['uuid'],
            'player_id'   => $params['id'],
            'tickets'     => $tickets,
            'status'      => 1,
        );
        $res    = $this->CI->court_model->insert_data($data,'fatigue_his');
        //触发任务 清除疲劳值
         $this->CI->utility->get_task_status($params['uuid'], 'clear_fatigue');
        $this->CI->court_model->success();
        return true;
    }
    
    /**
     * 装备列表
     * @param type $params
     * @return type
     */
    public function equipt_list($params)
    {
        $select = " A.idx as id,A.player_idx AS player_idx, A.player_name AS player_name, B.equipt_no AS equipt_no, B.level AS level ,B.type AS type, B.name AS name, B.pic AS pic, B.quality AS quality,B.attradd_info AS attradd_info1 ,B.descript AS descript";
        if ($params['type'] === 4) {// 查看所有装备
            $where_1  = "A.manager_idx = ".$params['uuid']." AND A.status = 1 AND B.status = 1";
        } else {
            $where_1  = "A.manager_idx = ".$params['uuid']." AND B.TYPE = ".$params['type']." AND  A.status = 1 AND B.status = 1";
        }
        $sql    = "select ".$select." from equipt AS A JOIN equipt_conf AS B ON A.equipt_no=B.equipt_no AND A.level = B.level  WHERE ".$where_1." LIMIT ".$params['offset'].",".$params['pagesize'];
        $equipt_list = $this->CI->court_model->fetch($sql);
        if (!$equipt_list) {
            log_message('info', 'empty_data:'.$this->ip.',未查询到装备列表数据');
            $this->CI->output_json_return('empty_data');
        }
        
        // 根据装备列表，查询该装备的不同等级套装
        $where['status']    = 1;
        foreach ($equipt_list as $k=>&$v) {
            $attradd_arr = explode("|",trim($v['attradd_info1'], "|"));
            foreach ($attradd_arr as $k1=>$v1) {
                $arr = explode(":", $v1);
                $attradd_info['attribute']  =  $arr[0]; 
                $attradd_info['add_val']    =  $arr[1];
                $v['attradd_info'][] = $attradd_info;
            }
            // 获取套装加成信息
            if ($v['type'] == 1) {// 球衣装备
                $where['jacket_no'] =  $v['equipt_no'];
            }elseif($v['type'] == 2) {// 球裤装备
                $where['trousers_no'] =  $v['equipt_no'];
            } else {// 球鞋装备
                $where['shoes_no'] =  $v['equipt_no'];
            }
            // 获取装备的套装特效
            $suit_info  = $this->CI->court_model->get_one($where, 'equiptsuit_conf');
            if (!$suit_info) {
                $v['suitadd_info']        = array();
            } else {
                $v['suitadd_info']['attribute']     = $suit_info['attribute'];
                $v['suitadd_info']['effect']        = $suit_info['effects'];
                $v['suitadd_info']['effect_2']      = $suit_info['effects_2'];
                $v['suitadd_info']['effect_3']      = $suit_info['effects_3'];
            }
        }
        return $equipt_list;
    }
    
    /**
     * 获取装备详细信息
     * @param type $params
     */
    public function equipt_info($params)
    {
        $select = "A.idx as id,A.manager_idx as uuid, A.manager_name AS manager_name, A.player_idx AS player_idx, A.player_name AS player_name,B.equipt_no AS equipt_no, B.level AS level ,B.type AS type, B.name AS name, B.pic AS pic, B.quality AS quality,B.holes AS holes,B.attradd_info AS attradd_info1 ,B.descript AS descript";
        $sql    = "select ".$select." from equipt AS A JOIN equipt_conf AS B ON A.equipt_no=B.equipt_no AND A.level = B.level  WHERE A.idx = ".$params['id']." AND A.status = 1 AND B.status = 1";
        $equipt_info = $this->CI->court_model->fetch($sql, 'row');
        if (!$equipt_info) {
            log_message('info', 'equipt_empty_data:'.$this->ip.',未查询到装备数据');
            $this->CI->output_json_return('equipt_empty_data');
        }
        // 装备属性加成信息
        $attradd_arr = explode("|",trim($equipt_info['attradd_info1'], "|"));
        foreach ($attradd_arr as $k1=>$v1) {
            $arr = explode(":", $v1);
            $attradd_info['attribute']  =  $arr[0]; 
            $attradd_info['add_val']    =  $arr[1];
            $equipt_info['attradd_info'][] = $attradd_info;
        }
        
        // 查看装备是否镶嵌宝石
        if (!$equipt_info['holes']) {// 该装备不能镶嵌宝石
            $equipt_info['gem_info']    = array();
        } else {
            $where      = "A.gem_no = B.gem_no AND A.equipt_idx = ".$equipt_info['id']." AND A.status=1 AND B.status=1";
            $fields     = "A.idx AS id , B.gem_no AS gem_no,B.pic AS pic,B.attribute AS attribute, B.attr_value AS attr_value";
            $gem_list   = $this->get_gem_list($where,$fields);
            if (!$gem_list) {
                $equipt_info['gem_info']    = array();
            } else {
                $equipt_info['gem_info']    = $gem_list;
            }
        }
        return $equipt_info;
    }
    
    /*
     *  一键升阶所需人民币
     */
    public function get_rmb_upgrade_info($params)
    {
        //球员卡升阶
        if($params['type'] == 1){
            //获取球员卡详情
            $options['where']   = "A.idx =".$params['id'] ." AND A.status = 1 AND B.status = 1";
            $options['select']  = "A.idx AS id , A.player_no AS player_no , A.level AS level , B.quality AS quality";
            $p_info = $this->CI->court_model->get_player_info($options);
            if(!$p_info){
                log_message('error', 'player_not_exist:'.$this->ip.',获取球员卡信息失败');
                $this->CI->output_json_return('player_not_exist');
            }
            if ($p_info['level'] == 9) {
                log_message('error', 'player_full_level:'.$this->ip.',球员卡已满级');
                $this->CI->output_json_return('highest_level_err');
            }
            $uplevel = $p_info['level'] + 1;
            //升阶所需人民币详情
            $where = "type = 1 AND level = $uplevel AND quality = {$p_info['quality']} AND status = 1";
            $upgrade_conf_info = $this->CI->court_model->get_one($where , 'price_conf');
            if(!$upgrade_conf_info){
                log_message('error', 'get_upgrade_conf_info:'.$this->ip.',获取升阶信息失败');
                $this->CI->output_json_return('get_upgrade_conf_info');
            }
        }else{//装备升阶
            $select = "A.idx as id,B.equipt_no AS equipt_no, B.level AS level , B.quality AS quality";
            $sql    = "select ".$select." from equipt AS A JOIN equipt_conf AS B ON A.equipt_no=B.equipt_no AND A.level = B.level  WHERE A.idx = ".$params['id']." AND A.status = 1 AND B.status = 1";
            $equipt_info = $this->CI->court_model->fetch($sql, 'row');
            if(!$equipt_info){
                log_message('info', 'equipt_empty_data:'.$this->ip.',未查询到装备数据');
                $this->CI->output_json_return('equipt_empty_data');
            }
            if ($equipt_info['level'] == 20) {// 刚装备已经是最高level
                log_message('error', 'upgrade_equipt：'.$this->ip.',当前装备已经是最高level,不能再升级');
                $this->CI->output_json_return('equipt_highest_level');
            }
            $uplevel = $equipt_info['level'] + 1;
            //升阶所需人民币详情
            $where = "type = 2 AND level = $uplevel AND status = 1";
            $upgrade_conf_info = $this->CI->court_model->get_one($where , 'price_conf');
            if(!$upgrade_conf_info){
                log_message('error', 'get_upgrade_conf_info:'.$this->ip.',获取升阶信息失败');
                $this->CI->output_json_return('get_upgrade_conf_info');
            }
        }
        return $upgrade_conf_info['price'];
    }

        /**
     * 
     * @param type $params
     */
    public function eupgrade_info($params)
    {
        // 获取装备升阶所需资料信息
        $equipt_info    = $this->equipt_info($params);
        $where          = array('equipt_level'=>$equipt_info['level'] + 1, 'equipt_type' => $equipt_info['type'],'status' =>1);
        $upgrade_info   = $this->CI->court_model->get_one($where, 'eupgrade_conf',"equipt_type as type,equipt_level as level_next,euro,junior_card,middle_card,senio_card");
        if (!$upgrade_info) {
            log_message('error', 'empty_data：'.$this->court_lib->ip.',暂无装备升级信息');
            $this->CI->output_json_return('empty_data');
        }
        if (in_array($equipt_info['level'], array(5,10,15))) {
            $data['upgrade_type']   = 2;// 升阶（需要升阶卡）
            $data['attr_info']      = array();
        } else {
            $data['upgrade_type']   = 1;// 强化
            $data['prop_info']  = array();
        }
        // 获取道具信息
        if ($data['upgrade_type'] == 1) {
            // 强化：需要属性值
            $where_1    = array('equipt_no'=>$equipt_info['equipt_no'],'level'=>$equipt_info['level']+1,'status'=>1);
            $fields_1   = "attradd_info";
            $attr_info  = $this->CI->court_model->get_one($where_1,'equipt_conf',$fields_1);
            $attr_arr   = explode("|", trim($attr_info['attradd_info'],"|"));
            foreach ($attr_arr as $k=>$v) {
                $arr = explode(":", $v);
                $data['attr_info'][$k]['attribute'] = $arr[0];
                $data['attr_info'][$k]['value_next'] = $arr[1];
                foreach ($equipt_info['attradd_info'] as $key=>$val) {
                    if ($val['attribute'] == $arr[0]) {
                        $data['attr_info'][$k]['value'] = $val['add_val'];
                    }
                }
            }
        } else {
            // 进阶：需要道具进阶卡信息
            $fields = "num";
            if ($upgrade_info['junior_card']) {// 401
                $where  = array('manager_idx'=>$params['uuid'],'prop_no'=>401,'status'=>1);
                $p_info = $this->get_prop_info($where,$fields);
                if (!$p_info['num']) {
                    $num    = 0;
                } elseif($p_info['num'] > $upgrade_info['junior_card']) {
                    $num    =  $upgrade_info['junior_card'];
                } else {
                    $num    = $p_info['num'];
                }
                $data['prop_info'][]  = array('attribute'=>401,'num'=>$num,'num_need'=>$upgrade_info['junior_card']);
            } else {
                $data['prop_info'][]  = array('attribute'=>401,'num'=>0,'num_need'=>0);
            }
            if ($upgrade_info['middle_card']) {// 402
                $where  = array('manager_idx'=>$params['uuid'],'prop_no'=>402,'status'=>1);
                $p_info = $this->get_prop_info($where,$fields);
                if (!$p_info['num']) {
                    $num    = 0;
                } elseif($p_info['num'] > $upgrade_info['middle_card']) {
                    $num    =  $upgrade_info['middle_card'];
                } else {
                    $num    = $p_info['num'];
                }
                $data['prop_info'][]  = array('attribute'=>402,'num'=>$num,'num_need'=>$upgrade_info['middle_card']);
            } else {
                $data['prop_info'][]  = array('attribute'=>402,'num'=>0,'num_need'=>0);
            }
            if ($upgrade_info['senio_card']) {// 403
                $where  = array('manager_idx'=>$params['uuid'],'prop_no'=>403,'status'=>1);
                $p_info = $this->get_prop_info($where,$fields);
                if (!$p_info['num']) {
                    $num    = 0;
                } elseif($p_info['num'] > $upgrade_info['senio_card']) {
                    $num    =  $upgrade_info['senio_card'];
                } else {
                    $num    = $p_info['num'];
                }
                $data['prop_info'][]  = array('attribute'=>403,'num'=>$num,'num_need'=>$upgrade_info['senio_card']);
            } else {
                $data['prop_info'][]  = array('attribute'=>403,'num'=>0,'num_need'=>0);
            }
        }
        $data['id']             = $equipt_info['id'];
        $data['equipt_no']      = $equipt_info['equipt_no'];
        $data['level']          = $equipt_info['level'];
        $data['level_next']     = $equipt_info['level']+1;
        $data['level_total']    = 20;
        $data['euro']           = $upgrade_info['euro'];
        return $data;
    }
    
    /**
     * 装备升级|强化操作
     * @param type $params
     */
    public function upgrade_equipt($params)
    {
        // 获取当前装备的等级，判断是升级(需要升阶卡)level 5->6 10->11 15->16表示升阶操作 or 强化 
        $equipt_info = $this->equipt_info($params);
        if ($equipt_info['level'] == 20) {// 刚装备已经是最高level
            log_message('error', 'upgrade_equipt：'.$this->ip.',当前装备已经是最高level,不能再升级');
            $this->CI->output_json_return('equipt_highest_level');
        }
        // 校验type值是否正确
        if (in_array($equipt_info['level'], array(5,10,15)) && $params['type'] == 1) {
            log_message('error', 'upgrade_equipt：'.$this->ip.',当前type值不正确，应该是2升阶|强化类型');
            $this->CI->output_json_return('equipt_type_err');
        } else if (!in_array($equipt_info['level'], array(5,10,15)) && $params['type'] == 2) {
            log_message('error', 'upgrade_equipt：'.$this->ip.',当前type值不正确，应该是1升级类型');
            $this->CI->output_json_return('equipt_type_err');
        }
        
        // 校验欧元 升阶卡数量是否正确
        $where          = array('equipt_level'=>$equipt_info['level']+1, 'equipt_type' => $equipt_info['type'],'status' =>1);
        $upgrade_info   = $this->CI->court_model->get_one($where, 'eupgrade_conf',"equipt_type as type,equipt_level as level_next,euro,junior_card,middle_card,senio_card");
        if (!$upgrade_info) {
            log_message('error', 'upgrade_equipt：'.$this->ip.',暂无装备升级|强化所需材料数据信息');
            $this->CI->output_json_return('equipt_upgrade_info_empty');
        }
        if ($params['euro'] != $upgrade_info['euro']) {
            log_message('error', 'upgrade_equipt：'.$this->court_lib->ip.',升阶装备欧元数不匹配');
            $this->CI->output_json_return('euro_num_err');
        }
        if ($params['type'] == 2) {
            if ($params['junior_card'] != $upgrade_info['junior_card']) {
                log_message('error', 'upgrade_equipt：'.$this->court_lib->ip.',升阶装备欧元数不匹配');
                $this->CI->output_json_return('junior_card');
            }
            if ($params['middle_card'] != $upgrade_info['middle_card']) {
                log_message('error', 'upgrade_equipt：'.$this->court_lib->ip.',升阶装备欧元数不匹配');
                $this->CI->output_json_return('middle_card');
            }
            if ($params['senio_card'] != $upgrade_info['senio_card']) {
                log_message('error', 'upgrade_equipt：'.$this->court_lib->ip.',升阶装备欧元数不匹配');
                $this->CI->output_json_return('senio_card');
            }
        }
        
        $this->CI->court_model->start();
        $data   = array('level' => $equipt_info['level'] +1);
        $where  = array('idx' => $params['id'], 'status'=>1);
        $upt_res = $this->CI->court_model->update_data($data, $where, 'equipt');
        if (!$upt_res) {
            log_message('error', 'upgrade_equipt_err：'.$this->court_lib->ip.',equipt升级失败');
            $this->CI->court_model->error();
            $this->CI->output_json_return('upgrade_equipt_err');
        }
        
        // 装备升级|强化历史记录
        $ist_data   = array(
            'manager_idx'   => $params['uuid'],
            'manager_name'  => $equipt_info['manager_name'],
            'equipt_no'     => $equipt_info['equipt_no'],
            'name'          => $equipt_info['name'],
            'type'          => $params['type'],
            'equipt_type'   => $equipt_info['type'],
            'level'         => $equipt_info['level']+1,
            'euro'          => (int)$params['euro'],
            'junior_card'   => (int)$params['junior_card'],
            'middle_card'   => (int)$params['middle_card'],
            'senio_card'    => (int)$params['senio_card'],
            'status'        => 1,
        );
        $ist_res = $this->CI->court_model->insert_data($ist_data, 'eupgrade_his');
        if (!$ist_res) {
            log_message('error', 'upgrade_equipt_his_err：'.$this->ip.',装备升级历史记录失败');
            $this->CI->court_model->error();
            $this->CI->output_json_return('upgrade_equipt_err');
        }
        
        // 触发成就 - 战斗力
        $this->load_library('task_lib');
        $this->CI->task_lib->achieve_fighting($params['uuid']);
        //触发任务 升级装备
        $this->CI->utility->get_task_status($params['uuid'] , 'upgrade_equipt');
        $this->CI->court_model->success();
        return true;
    }
    
    /**
     * 卸下球员装备
     * @param type $params
     */
    public function unload_equipt($params)
    {
        $where      = array('idx' => $params['id'], 'manager_idx'=> $params['uuid'],'status' => 1);
        $info   = $this->CI->court_model->get_one($where, 'equipt');
        
        // 判断该装备是否已使用
        if ((int)$info['player_idx'] === 0) {
            log_message('error', 'load_equipt_err：'.$this->ip.',该装备未被球员使用，暂不能卸载');
            $this->CI->output_json_return('equipt_not_used_err');
        }
        
        // 卸载球员的装备
        $data   = array('player_idx' => 0, 'player_name' => '');
        $this->CI->court_model->start();
        $upt_data       = $this->CI->court_model->update_data($data, $where, 'equipt');
        if (!$upt_data) {
            log_message('error', 'equipt_update_err：'.$this->ip.',卸载装备失败');
            $this->CI->court_model->error();
            $this->CI->output_json_return('unload_equipt_err');
        }
        
        // 卸载装备，减少球员属性值
        $para           = array('id'=>$info['player_idx']);
        $player_info    = $this->get_player_base_info($para);
        $equipt_info    = $this->equipt_info($params);
        $data_2     = array();
        foreach ($equipt_info as $k=>$v) {
            if (in_array($k, $this->CI->passport->get('attribute_arr'))) {
                $data_2[$k] = $player_info[$k] - $v;
            }
        }
        $where_2    = array('idx' => $params['id'], 'status'=>1);
        $upt_3      = $this->CI->court_model->update_data($data_2,$where_2, 'player_info');
        if (!$upt_3) {
            log_message('error', 'equipt_reduce_attribute_err：'.$this->ip.',卸载装备后，减少球员属性值失败');
            $this->CI->court_model->error();
            $this->CI->output_json_return('unload_equipt_err');
        }
        
        // 记录球员卸载装备历史记录
        $ist_data   = array(
            'manager_idx'   => $params['uuid'],
            'manager_name'  => $equipt_info['manager_name'],
            'type'          => 2,
            'player_idx'    => $params['id'],
            'player_name'   => $info['player_name'],
            'equipt_idx'    => $params['id'],
            'equipt_no'     => $equipt_info['equipt_no'],
            'equipt_name'   => $equipt_info['name'],
            'level'         => $equipt_info['level'],
            'status'        => 1,
        );
        $ist_res    = $this->CI->court_model->insert_data($ist_data, 'equipt_his');
        if (!$ist_res) {
            log_message('error', 'equipt_his_err：'.$this->ip.',球员卸载装备历史记录失败');
            $this->CI->court_model->error();
            $this->CI->output_json_return('unload_equipt_err');
        }
        
        $this->CI->court_model->success();
        return true;
    }
    
    /**
     * 装备装备
     * @param type $params
     */
    public function load_equipt($params)
    {
        // 判断该装备是否可用、装备类型（1球衣2球裤3球鞋）
        $sql         = "SELECT B.type type,B.equipt_no equipt_no,B.level level,A.manager_idx uuid,A.player_idx player_idx FROM equipt AS A,equipt_conf AS B WHERE A.idx = ".$params['equipt_id']." AND A.manager_idx = ".$params['uuid']." AND A.player_idx = 0 AND A.equipt_no = B.equipt_no AND A.level = B.level";
        $equipt_info = $this->CI->court_model->exec_sql($sql,false);
        if (!$equipt_info) {
            log_message('error', 'load_equipt_err：'.$this->ip.',不允许装载该装备');
            $this->CI->output_json_return('not_allow_use_equipt_err');
        }
        $this->CI->court_model->start();
        // 判断球员是否已存在该装备类型（1球衣2球裤3球鞋）
        $upt    = 0;
        $sql_2  = "SELECT DISTINCT(A.equipt_no) equipt_no, A.idx id,A.level level,B.type type FROM equipt AS A JOIN equipt_conf AS B ON A.equipt_no = B.equipt_no AND B.level = B.level WHERE A.player_idx =".$params['id']." AND A.status=1 AND B.status = 1";
        $equipt_= $this->CI->court_model->exec_sql($sql_2,true);
        if ($equipt_) {
            foreach ($equipt_ as $v) {
                if ($v['type'] == $equipt_info['type']) {// 更新装备
                    $upt    = 1;
                    $upt_data   = array('player_idx' => 0,'player_name'=>''); 
                    $upt_where  = array('idx'=>$v['id'],'status'=>1);
                    $upt_res    = $this->CI->court_model->update_data($upt_data, $upt_where, 'equipt');
                    if (!$upt_res) {
                        log_message('error', 'load_equipt_err：'.$this->ip.',球员装载装备失败');
                        $this->CI->court_model->error();
                        $this->CI->output_json_return('load_equipt_err');
                    }
                }
            }
        }
        
        // 获取球员信息,装载新装备
        $player_info    = $this->get_player_base_info($params);
        $where          = array('idx'=>$params['equipt_id'],'status'=>1);
        $data           = array('player_idx' => $params['id'], 'player_name' => $player_info['name']);
        $upt_res        = $this->CI->court_model->update_data($data, $where, 'equipt');
        if (!$upt_res) {
            log_message('error', 'load_equipt_err：'.$this->ip.',球员装载装备失败');
            $this->CI->court_model->error();
            $this->CI->output_json_return('load_equipt_err');
        }
        
        $para           = array('id'=>$params['equipt_id']);
        $equipt_info    = $this->equipt_info($para);
        
        // 添加装备，提升球员属性---属性加成（后期计算）
//        $data_2     = array();
//        foreach ($equipt_info as $k=>$v) {
//            if (in_array($k, $this->CI->passport->get('attribute_arr'))) {
//                $data_2[$k] = $v + $player_info[$k];
//            }
//        }
//        $where_2    = array('idx' => $params['id'], 'status'=>1);
//        $upt_3      = $this->CI->court_model->update_data($data_2,$where_2, 'player_info');
//        if (!$upt_3) {
//            log_message('error', 'equipt_add_attribute_err：'.$this->ip.',加载装备后，增加球员属性失败');
//            $this->CI->court_model->error();
//            $this->CI->output_json_return('load_equipt_err');
//        }
        
        // 记录球员装载装备历史记录
        $ist_data   = array(
            'manager_idx'   => $params['uuid'],
            'manager_name'  => $equipt_info['manager_name'],
            'type'          => 1,
            'player_idx'    => $params['id'],
            'player_name'   => $player_info['name'],
            'equipt_idx'    => $params['equipt_id'],
            'equipt_no'     => $equipt_info['equipt_no'],
            'equipt_name'   => $equipt_info['name'],
            'level'         => $equipt_info['level'],
            'status'        => 1,
        );
        $ist_res    = $this->CI->court_model->insert_data($ist_data, 'equipt_his');
        if (!$ist_res) {
            log_message('error', 'equipt_his_err：'.$this->ip.',球员装载装备历史记录失败');
            $this->CI->court_model->error();
            $this->CI->output_json_return('load_equipt_err');
        }
        
        // 触发成就 - 战斗力
        $this->load_library('task_lib');
        $this->CI->task_lib->achieve_fighting($params['uuid']);
        //触发任务 装载装备
        $this->CI->utility->get_task_status($params['uuid'], 'load_equipt');
        $this->CI->court_model->success();
        return true;
    }
    
    /**
     * 一键分解 球员卡|装备|宝石
     * @param type $params
     */
    public function do_decompose_all($params)
    {
        // 获取分解配置表
        $options['where']   = array('status'=>1);
        $options['fields']  = "type,quality,level,soccer_soul,euro,powder,prop";
        $conf_list          =   $this->CI->court_model->list_data($options, 'decompose_conf');
        if (!$conf_list) {
            log_message('error', 'do_decompose_all：'.$this->ip.',分解配置文件为空');
            $this->CI->output_json_return('decompose_config_empty_err');
        }
        foreach ($conf_list as $k=>$v) {
            $conf[$v['type']."_".$v['quality']."_".$v['level']]    = $v;
        }
        $this->CI->court_model->start();
        // 获取需要分解的 球员卡|装备|宝石
        if ($params['player']) {
            // 获取经理的球员卡
            $table  = "player_info A,player_lib B";
            $select = "A.idx id,A.level level,B.quality quality,A.player_no player_no";
            $where  = " WHERE A.manager_idx = ".$params['uuid']." AND A.plib_idx = B.idx AND B.quality IN (".$params['player'].") AND A.is_use = 0 AND A.status = 1 AND B.status = 1";
            $p_sql  = "SELECT ".$select." FROM ".$table.$where;
            $p_list = $this->CI->court_model->fetch($p_sql,"result");
            // 获取分解产物
            if ($p_list) {
                foreach ($p_list as $k=>$v) {
                    // 获取分解物
                    $product['soccer_soul'] += $conf["1_".$v['quality']."_".$v['level']]['soccer_soul'];
                    $ids    .= $v['id'].",";
                    $player_info    .= "球员id:".$v['id']."球员编号:".$v['player_no']."阶:+".$v['level'].";";
                }
                $p_ids  = trim($ids,",");
                // 删除球员卡
                $p_sql2 = "UPDATE player_info SET status = 0 WHERE idx IN (".$p_ids.")";
                $p_updt = $this->CI->court_model->fetch($p_sql2,"update");
                if (!$p_updt) {
                    log_message('error', '：decompose_err'.$this->ip.',分解成功后，删除分解物球员卡失败');
                    $this->CI->court_model->error();
                    $this->CI->output_json_return('decompose_err');
                }
            }
        }
        
        if ($params['equipt']) {
            // 获取经理的装备
            $table  = "equipt A,equipt_conf B";
            $select = "A.idx id,A.equipt_no equipt_no,A.level level,B.type type";
            $where  = " WHERE A.manager_idx = ".$params['uuid']." AND A.player_idx =0 AND A.equipt_no = B.equipt_no AND A.status = 1 AND B.status = 1";
            $e_sql  = "SELECT ".$select." FROM ".$table.$where." group by id";
            $e_list = $this->CI->court_model->fetch($e_sql,"result");
            // 获取分解产物
            if ($e_list) {
                foreach ($e_list as $k=>$v) {
                    // 获取分解物
                    $p = $conf[($v['type']+1)."_0_".$v['level']];
                    if ($p['prop']) {
                        $product['prop'][]  = $p['prop'];
                    }
                    if ($p['euro']) {
                        $product['euro']  += $p['euro'];
                    }
                    $e_ids    .= $v['id'].",";
                    $equipt_info    .= "装备id:".$v['id']."装备编号:".$v['equipt_no']."等级:".$v['level'].";";
                }
                $e_ids_  = trim($e_ids,",");
                // 删除装备
                $e_sql2 = "UPDATE equipt SET status = 0 WHERE idx IN (".$e_ids_.")";
                $p_updt = $this->CI->court_model->fetch($e_sql2,"update");
                if (!$p_updt) {
                    log_message('error', '：decompose_err'.$this->ip.',分解成功后，删除装备失败');
                    $this->CI->court_model->error();
                    $this->CI->output_json_return('decompose_err');
                }
            }
        }
        
        if ($params['gem']) {
            // 获取经理的宝石
            $table  = "gem A,gem_conf B";
            $select = "A.idx id,A.gem_no gem_no,B.quality quality,A.gem_num num";
            $where  = " WHERE A.manager_idx = ".$params['uuid']." AND A.is_use =0 AND A.gem_no = B.gem_no AND A.status = 1 AND B.status = 1";
            $g_sql  = "SELECT ".$select." FROM ".$table.$where." GROUP BY id";
            $g_list = $this->CI->court_model->fetch($g_sql,"result");
            // 获取分解产物
            if ($g_list) {
                foreach ($g_list as $k=>$v) {
                    // 获取分解物
                    $p = $conf["5_".($v['quality']-1)."_0"];
                    $product['powder']  += ($p['powder']*$v['num']);
                    $g_ids    .= $v['id'].",";
                    $gem_info    .= "宝石编号:".$v['gem_no']."数量:".$v['num'].";";
                }
                $g_ids_  = trim($g_ids,",");
                // 删除宝石
                $g_sql2 = "UPDATE gem SET status = 0 WHERE idx IN (".$g_ids_.")";
                $p_updt = $this->CI->court_model->fetch($g_sql2,"update");
                if (!$p_updt) {
                    log_message('error', '：decompose_err'.$this->ip.',分解成功后，删除宝石失败');
                    $this->CI->court_model->error();
                    $this->CI->output_json_return('decompose_err');
                }
            }
        }
        
        // 分配分解物--道具发放
        if ($product['prop']) {
            // 重组prop
            foreach ($product['prop'] as $key=>$val) {
                $arr = explode("|", $val);
                foreach ($arr as $k=>$v) {
                    $a   = explode(":", $v);
                    $prop_[$a[0]]   = (int)$prop_[$a[0]] +1;
                }
            }
            $data       = array(
                'manager_idx'   => $params['uuid'],
                'status'        => 1,
            );
            $product_prop_info  = '';
            foreach ($prop_ as $k=>$v) {
                // 查看该道具，经理是否已经有（更新数量）
                $where_6    = array('manager_idx'=>$params['uuid'], 'prop_no' => $k, 'status' =>1);
                $res_6      = $this->CI->court_model->get_one($where_6, 'prop','idx,num');
                if (!$res_6) {
                    $data['prop_no']    = $k;
                    $data['num']        = $v;
                    $ist_res    = $this->CI->court_model->insert_data($data,'prop');
                    if (!$ist_res) {
                        log_message('error', '：decompose_err'.$this->ip.',分解获得的道具插入失败');
                        $this->CI->court_model->error();
                        $this->CI->output_json_return('decompose_err');
                    }
                } else {
                    // 更新道具数量
                    $upt_res    = $this->CI->court_model->update_data(array('num'=>$res_6['num']+$v),array('idx'=>$res_6['idx']),'prop');
                    if (!$upt_res) {
                        log_message('error', '：decompose_err'.$this->ip.',分解获得的道具数量更新失败');
                        $this->CI->court_model->error();
                        $this->CI->output_json_return('decompose_err');
                    }
                }
                $prop_info  .=  '道具编号：'.$k.",数量：".$v;
            }
        }
        
        // 分配分解物--（欧元|球魂|粉末）
        $p          = array('uuid'=>$params['uuid']);
        $m_info     = $this->CI->utility->get_manager_info($p);
        $data_5     = array('euro' => $m_info['euro'] + $product['euro'], 'soccer_soul'=>$m_info['soccer_soul']+$product['soccer_soul'],'powder'=>$m_info['powder']+$product['powder']);
        $where_5    = array('idx' =>$params['uuid'], 'status' =>1);
        $upt_res    = $this->CI->utility->update_m_info($data_5,$where_5);
        if (!$upt_res) {
            log_message('error', '：decompose_err'.$this->ip.',分解获得的球票|球魂更新失败');
            $this->CI->court_model->error();
            $this->output_json_return('decompose_err');
        }
        
        // 分解历史记录
        $data_8= array(
            'manager_idx'   => $params['uuid'],
            'player'        => $player_info?$player_info:"",
            'equipt'        => $equipt_info?$equipt_info:"",
            'gem'           => $gem_info?$gem_info:0,
            'soccer_soul'   => (int)$product['soccer_soul'],
            'euro'          => (int)$product['euro'],
            'powder'        => (int)$product['powder'],
            'prop'          => $prop_info?$prop_info:"",
            'status'        => 1,
        );
        $ist_res= $this->CI->court_model->insert_data($data_8, 'decompose_all_his');
        if (!$ist_res) {
            log_message('error', '：decompose_err'.$this->ip.',分解历史记录插入失败');
            $this->CI->court_model->error();
            $this->CI->output_json_return('decompose_err');
        }
        $this->CI->utility->get_task_status($params['uuid'] , 'decompose');
        $this->CI->court_model->success();
        return true;
    }
    
    /**
     * 分解球员卡|装备
     * @param type $params
     */
    public function decompose($params)
    {
        $array[0]   = array('id' => $params['id'],'type'=>$params['type']);
        if ($params['id_2']) {
            $array[1]   = array('id' => $params['id_2'],'type'=>$params['type_2']);
        }
        if ($params['id_3']) {
            $array[2]   = array('id' => $params['id_3'],'type'=>$params['type_3']);
        }
        if ($params['id_4']) {
            $array[3]   = array('id' => $params['id_4'],'type'=>$params['type_4']);
        }
        if ($params['id_5']) {
            $array[4]   = array('id' => $params['id_5'],'type'=>$params['type_5']);
        }
        if ($params['id_6']) {
            $array[5]   = array('id' => $params['id_6'],'type'=>$params['type_6']);
        }
        
        $decompose_box_info = array(0=>'',1=>'',2=>'',3=>'',4=>'',5=>'');// 分解槽信息
        $product    = array('euro'=>0,'soccer_soul'=>0,'powder'=>0,'prop'=>array());
        $this->CI->court_model->start();
        foreach ($array as $key=>$val) {
            if ($val['type'] === 1) {// 球员卡
                $para['id']     = $val['id'];
                $para['uuid']   = $params['uuid'];
                $player_info    = $this->get_player_base_info($para);   
                if ($player_info['is_use'] == 1) {// 场上球员不能分解
                    log_message('error', 'get_decompose_info：not_allow_decompose_err'.$this->ip.',场上球员不能分解');
                    $this->CI->court_model->error();
                    $this->CI->output_json_return('not_allow_decompose_err');
                }
                // 获取分解后产物
                $where_1    = array('type' => 1, 'quality'=>$player_info['quality'], 'level'=>$player_info['level'], 'status'=>1);
                $res_data1  = $this->CI->court_model->get_one($where_1, 'decompose_conf');
                if (!$res_data1) {
                    log_message('error', '：decompose_product_empty'.$this->ip.',分解后，暂未获得获得产物');
                    $this->CI->court_model->error();
                    $this->CI->output_json_return('decompose_product_empty');
                }
                $product['soccer_soul']   += $res_data1['soccer_soul'];
                
                // 删除经理 该球员卡
                $data_2     = array('status' => 0);
                $where_2    = array('idx' => $val['id'], 'status'=>1);
                $res_2      = $this->CI->court_model->update_data($data_2, $where_2, 'player_info');
                if (!$res_2) {
                    log_message('error', '：decompose_err'.$this->ip.',分解成功后，删除分解物球员卡失败');
                    $this->CI->court_model->error();
                    $this->CI->output_json_return('decompose_err');
                }
                // 拼接分解槽info
                $decompose_box_info[$key] =  '球员卡id:'.$val['id'].',球员编号：'.$player_info['player_no'].'，品质:'.$player_info['quality'].',阶：'.$player_info['level'];
            } elseif($val['type'] === 2) {// 装备
                $para['id']     = $val['id'];
                $para['uuid']   = $params['uuid'];
                $equipt_info    = $this->equipt_info($para);
                if ($equipt_info['player_idx']) {
                    log_message('error', 'get_decompose_info：equipt_not_allow_decompose_err'.$this->ip.',已装载的装备不能分解');
                    $this->CI->output_json_return('equipt_not_allow_decompose_err');
                }
                if ((int)$equipt_info['type'] ===1) {
                    $type   = 2;
                } elseif ((int)$equipt_info['type'] ===2) {
                    $type    = 3;
                } elseif ((int)$equipt_info['type'] ===3) {
                    $type    = 4;
                }
                // 获取分解后产物
                $where_3            = array('type' => $type, 'quality'=>0, 'level'=>$equipt_info['level'], 'status'=>1);
                $res_data3          = $this->CI->court_model->get_one($where_3, 'decompose_conf');
                if (!$res_data3) {
                    log_message('error', '：decompose_product_empty'.$this->ip.',分解后，暂未获得获得产物');
                    $this->CI->court_model->error();
                    $this->CI->output_json_return('decompose_product_empty');
                }
                $equipt_product[]   = $res_data3;
                
                // 删除经理 该装备
                $data_4     = array('status' => 0);
                $where_4    = array('idx' => $val['id'], 'status'=>1);
                $res_4      = $this->CI->court_model->update_data($data_4, $where_4, 'equipt');
                if (!$res_4) {
                    log_message('error', '：decompose_err'.$this->ip.',分解成功后，删除分解物装备失败');
                    $this->CI->court_model->error();
                    $this->CI->output_json_return('decompose_err');
                }
                // 拼接分解槽信息
                $decompose_box_info[$key] =  '装备id:'.$val['id'].',装备编号：'.$equipt_info['equipt_no'].',等级：'.$equipt_info['level'];
            }elseif($val['type'] === 3) {// 宝石
                $gem_info   = $this->CI->court_model->get_one(array('idx'=>$val['id'],'manager_idx'=>$params['uuid'],'status'=>1),'gem','gem_no,is_use');
                if ($gem_info['is_use']) {
                    log_message('error', '：gem_not_allow_decompose_err'.$this->ip.',镶嵌的宝石不能分解');
                    $this->CI->court_model->error();
                    $this->CI->output_json_return('gem_not_allow_decompose_err');
                }
                $gconf_info = $this->CI->court_model->get_one(array('gem_no'=>$gem_info['gem_no'],'status'=>1),'gem_conf','quality,gem_no');
                // 获取分解后产物
                $where_3            = array('type' => 5, 'quality'=>$gconf_info['quality'], 'status'=>1);
                $res_data3          = $this->CI->court_model->get_one($where_3, 'decompose_conf');
                if (!$res_data3) {
                    log_message('error', '：decompose_product_empty'.$this->ip.',分解后，暂未获得获得产物');
                    $this->CI->court_model->error();
                    $this->CI->output_json_return('decompose_product_empty');
                }
                $product['powder']   += $res_data3['powder'];
                // 删除经理 宝石
                $data_4     = array('status' => 0);
                $where_4    = array('idx' => $val['id'], 'status'=>1);
                $res_4      = $this->CI->court_model->update_data($data_4, $where_4, 'gem');
                if (!$res_4) {
                    log_message('error', '：decompose_err'.$this->ip.',分解成功后，删除分解物宝石失败');
                    $this->CI->court_model->error();
                    $this->CI->output_json_return('decompose_err');
                }
                // 拼接分解槽信息
                $decompose_box_info[$key] =  '宝石id:'.$val['id'].',宝石编号：'.$gconf_info['gem_no'].',品质：'.$gconf_info['quality'];
            }
        }
        // 拼接所有获得的物品
        if ($equipt_product) {
            foreach ($equipt_product as $k=>$v) {
                $product['euro']    += $v['euro'];
                $prop_info  = explode("|", trim($v['prop'],"|"));
                foreach ($prop_info as $key=>$val) {
                    $arr = explode(":", $val);
                    $product['prop'][]  = array('prop_no'=>$arr[0],'num'=>$arr[1]);
                }
            }
        }
        
        // 将道具信息插入|更新到道具表
        $data       = array(
            'manager_idx'   => $params['uuid'],
            'status'        => 1,
        );
        $product_prop_info  = '';
        foreach ($product['prop'] as $k=>$v) {
            // 查看该道具，经理是否已经有（更新数量）
            $where_6    = array('manager_idx'=>$params['uuid'], 'prop_no' => $v['prop_no'], 'status' =>1);
            $res_6      = $this->CI->court_model->get_one($where_6, 'prop','idx,num');
            if (!$res_6) {
                $data['prop_no']    = $v['prop_no'];
                $data['num']        = $v['num'];
                $ist_res    = $this->CI->court_model->insert_data($data,'prop');
                if (!$ist_res) {
                    log_message('error', '：decompose_err'.$this->ip.',分解获得的道具插入失败');
                    $this->CI->court_model->error();
                    $this->CI->output_json_return('decompose_err');
                }
            } else {
                // 更新道具数量
                $upt_res    = $this->CI->court_model->update_data(array('num'=>$res_6['num']+$v['num']),array('idx'=>$res_6['idx']),'prop');
                if (!$upt_res) {
                    log_message('error', '：decompose_err'.$this->ip.',分解获得的道具数量更新失败');
                    $this->CI->court_model->error();
                    $this->CI->output_json_return('decompose_err');
                }
            }
            $product_prop_info  .=  '道具编号：'.$v['prop_no'].",数量：".$v['num'];
        }
        // 分解历史记录
        $data_8= array(
            'manager_idx'       => $params['uuid'],
            'decompose1_info'   => $decompose_box_info[0],
            'decompose2_info'   => $decompose_box_info[1],
            'decompose3_info'   => $decompose_box_info[2],
            'decompose4_info'   => $decompose_box_info[3],
            'decompose5_info'   => $decompose_box_info[4],
            'decompose6_info'   => $decompose_box_info[5],
            'soccer_soul'       => $product['soccer_soul'],
            'euro'              => $product['euro'],
            'powder'            => $product['powder'],
            'prop'              => $product_prop_info,
            'status'            => 1,
        );
        $ist_res= $this->CI->court_model->insert_data($data_8, 'decompose_his');
        if (!$ist_res) {
            log_message('error', '：decompose_err'.$this->ip.',分解历史记录插入失败');
            $this->CI->court_model->error();
            $this->CI->output_json_return('decompose_err');
        }
        
        // 将获得物加入表（欧元|球魂|道具|粉末）
        $p          = array('uuid'=>$params['uuid']);
        $m_info     = $this->CI->utility->get_manager_info($p);
        $data_5     = array('euro' => $m_info['euro'] + $product['euro'], 'soccer_soul'=>$m_info['soccer_soul']+$product['soccer_soul'],'powder'=>$m_info['powder']+$product['powder']);
        $where_5    = array('idx' =>$params['uuid'], 'status' =>1);
        $upt_res    = $this->CI->utility->update_m_info($data_5,$where_5);
        if (!$upt_res) {
            log_message('error', '：decompose_err'.$this->ip.',分解获得的球票|球魂更新失败');
            $this->CI->court_model->error();
            $this->output_json_return('decompose_err');
        }
        $this->CI->utility->get_task_status($params['uuid'] , 'decompose');
        $this->CI->court_model->success();
        return true;
    }
    
    /**
     * 获取分解物品，获得的物品信息
     */
    public function get_decompose_info($params)
    {
        $array[0]   = array('id' => $params['id'],'type'=>$params['type']);
        if ($params['id_2']) {
            $array[1]   = array('id' => $params['id_2'],'type'=>$params['type_2']);
        }
        if ($params['id_3']) {
            $array[2]   = array('id' => $params['id_3'],'type'=>$params['type_3']);
        }
        if ($params['id_4']) {
            $array[3]   = array('id' => $params['id_4'],'type'=>$params['type_4']);
        }
        if ($params['id_5']) {
            $array[4]   = array('id' => $params['id_5'],'type'=>$params['type_5']);
        }
        if ($params['id_6']) {
            $array[5]   = array('id' => $params['id_6'],'type'=>$params['type_6']);
        }
        
        // 分解产物
        $product    = array('euro'=>0,'soccer_soul'=>0,'powder'=>0,'prop'=>array());
        foreach ($array as $key=>$val) {
            if ($val['type'] === 1) {// 球员卡
                $para['id']     = $val['id'];
                $player_info    = $this->get_player_base_info($para);
                if ($player_info['is_use'] == 1) {// 场上球员不能分解
                    log_message('error', 'get_decompose_info：not_allow_decompose_err'.$this->ip.',场上球员不能分解');
                    $this->CI->output_json_return('player_not_allow_decompose_err');
                }
                // 获取分解后产物
                $where_1    = array('type' => 1, 'quality'=>$player_info['quality'], 'level'=>$player_info['level'], 'status'=>1);
                $res_data1  = $this->CI->court_model->get_one($where_1, 'decompose_conf');
                if (!$res_data1) {
                    log_message('error', '：decompose_product_empty'.$this->ip.',分解后，暂未获得获得产物');
                    $this->CI->output_json_return('decompose_product_empty');
                }
                $product['soccer_soul']   += $res_data1['soccer_soul'];
            } elseif($val['type'] === 2) {// 装备
                $para['id']     = $val['id'];
                $para['uuid']   = $params['uuid'];
                $equipt_info    = $this->equipt_info($para);
                if ($equipt_info['player_idx']) {
                    log_message('error', 'get_decompose_info：equipt_not_allow_decompose_err'.$this->ip.',已装载的装备不能分解');
                    $this->CI->output_json_return('equipt_not_allow_decompose_err');
                }
                if ((int)$equipt_info['type'] ===1) {
                    $type   = 2;
                } elseif ((int)$equipt_info['type'] ===2) {
                    $type    = 3;
                } elseif ((int)$equipt_info['type'] ===3) {
                    $type    = 4;
                }
                // 获取分解后产物
                $where_3            = array('type' => $type, 'quality'=>0, 'level'=>$equipt_info['level'], 'status'=>1);
                $res_data3          = $this->CI->court_model->get_one($where_3, 'decompose_conf');
                if (!$res_data3) {
                    log_message('error', '：decompose_product_empty'.$this->ip.',分解后，暂未获得获得产物');
                    $this->CI->output_json_return('decompose_product_empty');
                }
                $equipt_product[]   = $res_data3;
            } elseif($val['type'] === 3) {// 宝石
                $gem_info   = $this->CI->court_model->get_one(array('idx'=>$val['id'],'manager_idx'=>$params['uuid'],'status'=>1),'gem','gem_no,is_use');
                if ($gem_info['is_use']) {
                    log_message('error', '：gem_not_allow_decompose_err'.$this->ip.',镶嵌的宝石不能分解');
                    $this->CI->output_json_return('gem_not_allow_decompose_err');
                }
                $gconf_info = $this->CI->court_model->get_one(array('gem_no'=>$gem_info['gem_no'],'status'=>1),'gem_conf','quality');
                // 获取分解后产物
                $where_3            = array('type' => 5, 'quality'=>$gconf_info['quality'], 'status'=>1);
                $res_data3          = $this->CI->court_model->get_one($where_3, 'decompose_conf');
                if (!$res_data3) {
                    log_message('error', '：decompose_product_empty'.$this->ip.',分解后，暂未获得获得产物');
                    $this->CI->output_json_return('decompose_product_empty');
                }
                $product['powder']   += $res_data3['powder'];
            }
        }
        if ($equipt_product) {
            foreach ($equipt_product as $k=>$v) {
                $product['euro']    += $v['euro'];
                $prop_info  = explode("|", trim($v['prop'],"|"));
                foreach ($prop_info as $key=>$val) {
                    $arr = explode(":", $val);
                    $product['prop'][]  = array('prop_no'=>$arr[0],'num'=>$arr[1]);
                }
            }
        }
        // 触发成就 - 分解
        $this->load_library('task_lib');
        $this->CI->task_lib->decompose($params['uuid']);
        return $product;
    }
    
    /**
     * 获取球员图鉴（金卡、橙卡）
     */
    public function get_player_lib($params)
    {
        $condition      = "A.quality > 4 AND A.status = 1 GROUP BY A.player_no ORDER BY A.idx asc LIMIT ".$params['offset'].",".$params['pagesize'];
        $join_condition = "A.player_no = B.player_no AND B.status = 1 AND B.manager_idx = ".$params['uuid'];
        $select         = "A.idx as id,A.player_no AS player_no,A.quality AS quality,A.pic AS pic,A.frame AS frame,A.name AS name,A.ability AS ability,A.nationality AS nationality,A.position_type AS position_type,IF(B.idx,1,0) AS is_exists";
        $tb_a           = "player_lib AS A";
        $tb_b           = "player_info AS B";
        $lib_list       = $this->CI->court_model->get_composite_row_array($condition, $join_condition, $select, $tb_a, $tb_b,true);
        return $lib_list;
    }
    
    /**
     * 意志列表
     * @param type $params
     */
    public function volition_list($params)
    {
        // 意志列表
        $select = "B.idx AS id, B.pic AS pic, B.name AS name, B.decript AS decript, B.player_num AS num_total,IF(A.num_curr,A.num_curr,0) AS num_current,IF(A.is_active,A.is_active,0) AS is_active";
        $sql    = "SELECT ".$select." FROM volition AS A RIGHT JOIN volition_conf AS B ON A.volition_idx= B.idx AND A.manager_idx = ".$params['uuid']." AND A.status = 1 WHERE B.level = ".$params['type']." AND  B.status = 1 LIMIT ".$params['offset'].",".$params['pagesize'];
        $v_list = $this->CI->court_model->fetch($sql);
        return $v_list;
    }
    
    /**
     * 获取组合列表
     */
    public function get_group_list($params)
    {
        // 意志列表
        $select = "B.idx AS id, B.pic AS pic, B.name AS name, B.decript AS decript,IF(A.idx,1,0) AS is_active,B.player_num AS num_total,B.player_detail AS player_detail";
        $sql    = "SELECT ".$select." FROM group1 AS A RIGHT JOIN group_conf AS B ON A.group_idx= B.idx AND A.manager_idx = ".$params['uuid']." AND A.status = 1 WHERE B.status = 1 LIMIT ".$params['offset'].",".$params['pagesize'];
        $g_list = $this->CI->court_model->fetch($sql);
        // 判断经理是否有该球员信息
        foreach ($g_list as $k=>&$v) {
            $num_current    = 0;
            $player_info = explode("|", $v['player_detail']);
            foreach ($player_info as $key=>$val) {
                $arr = explode(":", $val);
                $player_no  = $arr[0];
                $arr2 = explode("_", $arr[1]);
                $level  = $arr2[1];
                // 查找经理该球员是否存在
                $options['where']   = array('manager_idx'=>$params['uuid'],'player_no'=>$player_no,'status'=>1);
                $options['fields']  = "idx as id,player_no,level";
                $p_list             = $this->CI->court_model->list_data($options,'player_info');
                if ($p_list) {
                    $level_key      = array_column($p_list, "id",'level');
                    if (array_key_exists($level, $level_key)) {
                        $num_current++;
                    } else{
                        $arr    = array_keys($level_key);
                        $arr[]  = $level;
                        sort($arr);
                        $position   = array_keys($arr,$level,true)[0];
                        $num    = count($arr)-1;
                        if($position< $num) {
                            $num_current++;
                        }
                        
                    }
                }
            }
            if ($num_current > $v['num_total']) {
                $num_current    = $v['num_total'];
            }
            $v['num_current']   = $num_current;
        }
        return $g_list;
    }
    
    /**
     * 获取意志详情
     * @param type $params
     */
    public function get_volition_info($params)
    {
        // 意志表
        $where  = array('manager_idx'=>$params['uuid'],'volition_idx'=>$params['id'],'status'=>1);
        $fields = "player_info,is_active";
        $v_info = $this->CI->court_model->get_one($where,'volition',$fields);
        
        // 意志配置表
        $where_2    = array('idx'=>$params['id'],'status'=>1);
        $fields_2   = "idx as id,name,player_detail";
        $v_info_2   = $this->CI->court_model->get_one($where_2,'volition_conf',$fields_2); 
        
        $data['id']         = $params['id'];
        $data['is_active']  = $v_info['is_active']?$v_info['is_active']:0;
        $data['name']       = $v_info_2['name'];
        
        // 获取意志需要的卡牌信息
        if (!$v_info_2) {
            log_message('error', 'get_volition_info：empty_data'.$this->ip.',暂无意志信息');
            $this->CI->output_json_return('empty_data');
        }
        if (!$v_info) {
            $need_player = explode("|", trim($v_info_2['player_detail'],"|"));
            foreach ($need_player as $k=>$v) {
                $player_arr     = explode(":",$v);
                $player_no      = $player_arr[0];
                $need_level   = explode("_",$player_arr[1])[1];
                $need_quality = explode("_",$player_arr[1])[0];
                // 获取该球员信息
                $options['where']   = array('manager_idx'=>$params['uuid'],'player_no'=>$player_no,'is_use'=>0,'status'=>1);
                $options['fields']  = "idx as id,player_no,level";
                $p_list             = $this->CI->court_model->list_data($options,'player_info');
                if ($p_list) {
                    $level_key      = array_column($p_list, "id",'level');
                    if (array_key_exists($need_level, $level_key)) {
                        $player_id      = $level_key[$need_level];
                        $chioce_level   = $need_level;
                    } else{
                        $arr    = array_keys($level_key);
                        $arr[]  = $need_level;
                        sort($arr);
                        $position   = array_keys($arr,$need_level,true)[0];
                        $num    = count($arr)-1;
                        if ($position >= $num) {// 排在最后，选中球员
                            $chioce_level = $arr[$position-1];
                        } else if($position< $num) {
                            $chioce_level = $arr[$position+1];
                        }
                        $player_id  = $level_key[$chioce_level];
                    }
                    $player_info_1 = array('id'=>$player_id,'level'=>$chioce_level,'quality'=>$need_quality,'player_no'=>$player_no,'need_level'=>$need_level,'is_exists'=>1,'is_insert'=>0);
                } else {
                    $player_info_1 = array('id'=>0,'level'=>0,'quality'=>$need_quality,'player_no'=>$player_no,'need_level'=>$need_level,'is_exists'=>0,'is_insert'=>0);
                }
                
                // 获取球员存在位置
                $exists_where                       = $this->get_player_exists_where($player_no,$params['uuid']);
                $player_info_1['exists_location']   = $exists_where;
                $data['player_info'][]              = $player_info_1;
            }
            return $data;
        }
        
        // 意志表存在数据
        $exists_player  = explode("|", trim($v_info['player_info'],'|'));// 意志表 卡牌信息
        if ($v_info['is_active'] == 1) {
            foreach ($exists_player as $k=>$v) {
                $arr        = explode(":", $v);
                $arr2       = explode("_", $arr[1]);
                $player_id  = $arr[0];
                $player_no  = $arr2[0];
                $level      = $arr2[1];
                $player2    = explode("|", trim($v_info_2['player_detail'],"|"));
                $need_level = 0;
                foreach ($player2 as $key=>$val) {
                    $arr3   = explode(":", $val);
                    if ($arr3[0] == $player_no) {
                        $need_level     = explode("_", $arr3[1])[1];
                        $need_quality   = explode("_", $arr3[1])[0];
                    }
                }
                // 获取球员存在位置
                $exists_location    = $this->get_player_exists_where($player_no,$params['uuid']);
                $data['player_info'][]  = array('id'=>$player_id,'level'=>$level,'quality'=>$need_quality,'player_no'=>$player_no,'need_level'=>$need_level,'is_insert'=>1,'is_exists'=>1,'exists_location'=>$exists_location);
            }
            return $data;
        }
            
        // 意志尚未激活
        $detail     = explode("|", trim($v_info_2['player_detail'],"|"));// 配置表卡牌信息
        foreach ($exists_player as $k=>$v) {
            $arr        = explode(":", $v);
            $player_id  = $arr[0];
            $arr2       = explode("_", $arr[1]);
            $player_no  = $arr2[0];
            $level      = $arr2[1];
            $p_no[]     = $player_no;
            $_player[$player_no] = array('id'=>$player_id,'level'=>$level);
        }
        // 球员配置表
        foreach ($detail as $key=>$val) {
            $arr3               = explode(":", $val);
            $player_no2         = $arr3[0];
            $arr4               = explode("_", $arr3[1]);
            $player_quality     = $arr4[0];
            $need_level         = $arr4[1];
            if(in_array($player_no2, $p_no)) {// 该卡牌插入
                $player_info_1  = array('id'=>$_player[$player_no2]['id'],'level'=>$_player[$player_no2]['level'],'quality'=>$player_quality,'player_no'=>$player_no2,'need_level'=>$need_level,'is_insert'=>1,'is_exists'=>1);
            } else {
                // 获取该球员信息
                $options['where']   = array('manager_idx'=>$params['uuid'],'player_no'=>$player_no2,'is_use'=>0,'status'=>1);
                $options['fields']  = "idx as id,player_no,level";
                $p_list             = $this->CI->court_model->list_data($options,'player_info');
                if ($p_list) {
                    $level_key      = array_column($p_list, "id",'level');
                    if (array_key_exists($need_level, $level_key)) {
                        $player_id      = $level_key[$need_level];
                        $chioce_level   = $need_level;
                    } else{
                        $arr    = array_keys($level_key);
                        $arr[]  = $need_level;
                        sort($arr);
                        $position   = array_keys($arr,$need_level,true)[0];
                        $num    = count($arr)-1;
                        if ($position >= $num) {// 排在最后，选中球员
                            $chioce_level = $arr[$position-1];
                        } else if($position< $num) {
                            $chioce_level = $arr[$position+1];
                        }
                        $player_id  = $level_key[$chioce_level];
                    }
                    $player_info_1 = array('id'=>$player_id,'level'=>$chioce_level,'quality'=>$player_quality,'player_no'=>$player_no2,'need_level'=>$need_level,'is_exists'=>1,'is_insert'=>0);
                } else {
                    $player_info_1 = array('id'=>0,'level'=>0,'player_no'=>$player_no2,'quality'=>$player_quality,'need_level'=>$need_level,'is_exists'=>0,'is_insert'=>0);
                }
            }
            // 查看球员存在哪些地方
            $exists_where                       = $this->get_player_exists_where($player_no,$params['uuid']);
            $player_info_1['exists_location']   = $exists_where;
            $data['player_info'][]              = $player_info_1;
        }
        return $data;
        
    }
    
    /**
     * 获取组合详情
     * @param type $params
     */
    public function get_group_info($params)
    {
        // 组合配置表
        $where_2    = array('idx'=>$params['id'],'status'=>1);
        $fields_2   = "idx as id,name,player_detail,tickets as tickets_need,euro as euro_need";
        $group_info = $this->CI->court_model->get_one($where_2,'group_conf',$fields_2);
        $player_info    = explode("|", trim($group_info['player_detail'],"|"));
        foreach ($player_info as $k=>$v) {
            $arr        = explode(":", $v);
            $player_no  = $arr[0];
            $arr2       = explode("_", $arr[1]);
            $level      = $arr2[1];
            $player_info_1 = array('id'=>0,'level'=>0,'player_no'=>$player_no,'need_level'=>$level,'is_exists'=>0);
            // 获取该球员信息
            $options['where']   = array('manager_idx'=>$params['uuid'],'player_no'=>$player_no,'status'=>1);
            $options['fields']  = "idx as id,player_no,level";
            $p_list             = $this->CI->court_model->list_data($options,'player_info');
            if ($p_list) {
                $level_key      = array_column($p_list, "id",'level');
                if (array_key_exists($level, $level_key)) {
                    $player_id      = $level_key[$level];
                    $chioce_level   = $level;
                } else{
                    $arr    = array_keys($level_key);
                    $arr[]  = $level;
                    sort($arr);
                    $position   = array_keys($arr,$level,true)[0];
                    $num    = count($arr)-1;
                    if ($position< $num) {// 排在最后，选中球员
                        $chioce_level = $arr[$position+1];
                        $player_id  = $level_key[$chioce_level];
                    }
                }
                if ($player_id) {
                    $player_info_1 = array('id'=>$player_id,'level'=>$chioce_level,'player_no'=>$player_no,'need_level'=>$level,'is_exists'=>1);
                }
            }
            $group_info['player_info'][]    = $player_info_1;
        }
        // 组合表
        $where  = array('manager_idx'=>$params['uuid'],'group_idx'=>$params['id'],'status'=>1);
        $fields = "idx as id";
        $g_info = $this->CI->court_model->get_one($where,'group1',$fields);
        $group_info['is_active']  = 0;
        if ($g_info) {
            $group_info['is_active']  = 1;
        }
        // 获取经理欧元、球魂数
        $m_info = $this->CI->utility->get_manager_info($params);
        $group_info['tickets']  = $m_info['tickets'];
        $group_info['euro']     = $m_info['euro'];
        return $group_info;
    }
    
    /**
     * 插入卡牌操作（意志）
     * @param type $params
     */
    public function do_insert_player($params)
    {    
        // 校验该意志是否已激活
        $where  = array('manager_idx'=>$params['uuid'],'volition_idx'=>$params['id'],'status'=>1);
        $v_info = $this->CI->court_model->get_one($where, 'volition','idx,is_active,num_curr,player_info');
        if ($v_info['is_active']) {
            log_message('error', 'volition_active_err'.$this->ip.',插入卡牌：该意志已激活');
            $this->CI->output_json_return('volition_active_err');
        }
        // 校验经理是否存在该卡牌
        $where  = array('idx'=>$params['player_id'],'is_use'=>0,'status'=>1);
        $player_info2    = $this->CI->court_model->get_one($where,'player_info','idx as id,player_no,level');
        if (!$player_info2) {
            log_message('error', '：player_not_exist'.$this->ip.',插入卡牌：不存在该卡牌');
            $this->CI->output_json_return('player_not_exist');
        }
        
        // 校验当前卡牌是否已插入过
        if ($v_info) {
            $num_current    = $v_info['num_curr'];//当前卡牌数
            $player_info1    = explode('|', trim($v_info['player_info'],'|'));
            foreach ($player_info1 as $k=>$v) {
                $arr = explode(':', $v);
                $arr2 = explode('_', $arr[1]);
                $player_id  = $arr[0];
                $player_no  = $arr2[0];
                $level      = $arr2[1];
                $player2[]  = $player_no;
            }
            if (in_array($player_info2['player_no'], $player2)) {
                log_message('error', 'inserted_player_err'.$this->ip.',插入卡牌：该卡牌已插过');
                $this->CI->output_json_return('inserted_player_err');
            }
            // 拼接当前卡牌信息
            $p_info = trim($v_info['player_info'],'|')."|".$player_info2['id'].":".$player_info2['player_no']."_".$player_info2['level']."|";
        } else {// 第一次插入
            $p_info = $player_info2['id'].":".$player_info2['player_no']."_".$player_info2['level']."|";
        }
        
        // 校验该卡牌是否是意志需要的
        $where      = array('idx'=>$params['id'],'status'=>1);
        $vconf_info = $this->CI->court_model->get_one($where, 'volition_conf','idx,name,level,player_num,player_detail');
        $player_info    = explode('|', trim($vconf_info['player_detail'],'|'));
        foreach ($player_info as $k=>$v) {
            $arr        = explode(':', $v);
            $arr2       = explode('_', $arr[1]);
            $player[]   = $arr[0];// $player[]   = player_no
            $quility[$arr[0]]  =   $arr2[1]; // $quality[player_no]  = level  
        }
        if (!in_array($player_info2['player_no'], $player)) {
            log_message('error', 'neednot_player_err'.$this->ip.',插入卡牌：该意志不需要该球员卡');
            $this->CI->output_json_return('neednot_player_err');
        } else {
            // 判断当前卡牌的等级是否足够
            if ($player_info2['level'] < $quility[$player_info2['player_no']]) {
                log_message('error', 'do_insert_player：insert_player_level_not_enought_err'.$this->ip.',卡牌等级不足，不允许插入');
                $this->CI->output_json_return('insert_player_level_not_enought_err');
            }
        }
        
        // 删除经理该卡牌
        $this->CI->court_model->start();
        $fields_2   = array('status'=>0);
        $where_2    = array('idx'=>$player_info2['id'],'status'=>1);
        $res = $this->update_player_info($fields_2, $where_2);
        if (!$res) {
            $this->CI->court_model->error();
            log_message('error', '：insert_player_err'.$this->ip.',插入卡牌：删除卡牌失败');
            $this->CI->output_json_return('insert_player_err');
        }
        // 更改意志表信息
        if (($num_current+1) == $vconf_info['player_num']) {
            $is_active  = 1;
            $fields['is_active']    = 1;
        } else {
            $is_active  = 0;
        }
        if ($v_info) {
            $fields['player_info']      = $p_info;
            $fields['num_curr']         = $num_current+1;
            $where  = array('volition_idx' => $params['id'],'status'=>1);
            $upt_res = $this->CI->court_model->update_data($fields,$where,'volition');
            if (!$upt_res) {
                $this->CI->court_model->error();
                log_message('error', 'do_insert_player：update_volition_err'.$this->ip.',插入卡牌：经理意志信息修改失败');
                $this->CI->output_json_return('update_volition_err');
            }
        } else {
            $data   = array(
                'manager_idx'   => $params['uuid'],
                'volition_idx'  => $params['id'],
                'name'          => $vconf_info['name'],
                'num_curr'      => $num_current+1,
                'player_info'   => $p_info,
                'is_active'     => $is_active,
                'status'        => 1,
            );
            $ist_res    = $this->CI->court_model->insert_data($data,'volition');
            if (!$ist_res) {
                $this->CI->court_model->error();
                log_message('error', '：do_insert_player：insert_volition_err'.$this->ip.',意志信息插入失败');
                $this->CI->output_json_return('insert_volition_err');
            }
        }
        
        // 记录卡牌插入历史记录
        $data   = array(
            'manager_idx'   => $params['uuid'],
            'volition_idx'  => $params['id'],
            'name'          => $vconf_info['name'],
            'level'         => $vconf_info['level'],
            'player_info'   => $params['player_no'].":".$quility."_".$params['level'],
            'is_active'     => $is_active,
            'status'        => 1,
        );
        $ist_res = $this->CI->court_model->insert_data($data,'volition_his');
        if (!$ist_res) {
            $this->CI->court_model->error();
            log_message('error', '：insert_volition_his_err'.$this->ip.',插入卡牌：意志插卡历史记录插入失败');
            $this->CI->output_json_return('insert_volition_his_err');
        }
        if ($is_active) {
            // 触发成就 - 战斗力
            $this->load_library('task_lib');
            $this->CI->task_lib->achieve_fighting($params['uuid']);
            // 触发成就 - 收藏家
            $this->CI->task_lib->unlock_volition($params['uuid']);
        }
        $this->CI->court_model->success();
        return true;
    }
    
    /**
     * 激活组合操作
     * @param type $params
     */
    public function group_active($params)
    {
        // 判断组合是否已经激活
        $where  = array('manager_idx'=>$params['uuid'],'group_idx'=>$params['id'],'status'=>1);
        $fields = "idx as id";
        $g_info = $this->CI->court_model->get_one($where,'group1',$fields);
        if ($g_info) {
            log_message('error', '：group_active_exists'.$this->ip.',激活组合：球魂不足');
            $this->CI->output_json_return('group_active_exists');
        }
        // 组合配置表
        $where      = array('idx'=>$params['id'],'status'=>1);
        $fields     = "idx as id,name,player_detail,tickets,euro";
        $gconf_info = $this->CI->court_model->get_one($where,'group_conf',$fields);
        $player_info    = explode("|", trim($gconf_info['player_detail'],"|"));
        // 校验经理-欧元球魂是否足够
        $m_info = $this->CI->utility->get_manager_info($params);
        if ($m_info['tickets'] < $gconf_info['tickets']) {
            log_message('error', '：not_enough_tickets_err'.$this->ip.',激活组合：球票不足');
            $this->CI->output_json_return('not_enough_tickets_err');
        }
        if ($m_info['euro'] < $gconf_info['euro']) {
            log_message('error', '：not_enough_euro_err'.$this->ip.',激活组合：欧元不足');
            $this->CI->output_json_return('not_enough_euro_err');
        }
        
        // 判断经理是否存在卡牌
        foreach ($player_info as $k=>$v) {
            $arr        = explode(":", $v);
            $player_no  = $arr[0];
            $arr2       = explode("_", $arr[1]);
            $level      = $arr2[1];
            // 查找经理是否有该球员
            // 获取该球员信息
            $options['where']   = array('manager_idx'=>$params['uuid'],'player_no'=>$player_no,'status'=>1);
            $options['fields']  = "idx as id,player_no,level";
            $p_list             = $this->CI->court_model->list_data($options,'player_info');
            $player_id          = 0;
            if ($p_list) {
                $level_key      = array_column($p_list, "id",'level');
                if (array_key_exists($level, $level_key)) {
                    $player_id      = $level_key[$level];
                    $chioce_level   = $level;
                } else{
                    $arr    = array_keys($level_key);
                    $arr[]  = $level;
                    sort($arr);
                    $position   = array_keys($arr,$level,true)[0];
                    $num    = count($arr)-1;
                    if ($position< $num) {// 排在最后，选中球员
                        $chioce_level = $arr[$position+1];
                        $player_id  = $level_key[$chioce_level];
                    }
                }
            }
            if (!$player_id) {
                // 无该球员
                $this->CI->court_model->error();
                log_message('error', '：lack_player_err'.$this->ip.',激活组合：缺少卡牌');
                $this->CI->output_json_return('lack_player_err');
            }
        }
        
        $this->CI->court_model->start();
        // 插入组合表
        $data   = array(
            'manager_idx'   => $params['uuid'],
            'group_idx'     => $params['id'],
            'name'          => (string)$gconf_info['name'],
            'player_info'   => $gconf_info['player_detail'],
            'euro'          => (int)$gconf_info['euro'],
            'soccersoul'    => (int)$gconf_info['soccersoul'],
            'is_active'     => 1,
            'status'        => 1,
        );
        $ist_res = $this->CI->court_model->insert_data($data,'group1');
        if (!$ist_res) {
            $this->CI->court_model->error();
            log_message('error', '：active_group_err'.$this->ip.',激活组合：组合激活失败');
            $this->CI->output_json_return('active_group_err');
        }
        
        // 更新经理信息-欧元 球魂
        $where2             = array('idx'=>$params['uuid'],'status'=>1);
        $fields2['tickets'] = $m_info['tickets'] - (int)$gconf_info['tickets'];
        $fields2['euro']    = $m_info['euro']  - (int)$gconf_info['euro'];
        $res  = $this->CI->court_model->update_data($fields2, $where2, 'manager_info');var_dump($res);
        if (!$res) {
            $this->CI->court_model->error();
            log_message('error', '：active_group_err'.$this->ip.',激活组合：经理球票、欧元更新失败');
            $this->CI->output_json_return('active_group_err');
        }
        $this->CI->court_model->success();
        
        // 触发成就 - 战斗力
        $this->load_library('task_lib');
        $this->CI->task_lib->achieve_fighting($params['uuid']);
        // 触发成就 - 收藏家
        $this->CI->task_lib->unlock_group($params['uuid']);
        return true;
    }
    
    /**
     * 获取宝石列表
     * @param type $where
     * @return type
     */
    public function get_gem_list($where,$fields = '')
    {
        if (!$fields) {
            $fields = "A.idx AS id,B.gem_no AS gem_no,B.name AS name,B.quality AS quality,B.pic AS pic,B.attribute AS attribute, B.attr_value AS attr_value,B.descript AS descript";
        }
        $sql        = "SELECT ".$fields." FROM gem AS A,gem_conf AS B WHERE A.gem_no = B.gem_no AND ".$where;
        $gem_list   = $this->CI->court_model->fetch($sql);
        return $gem_list;
    }
    
    /**
     * 获取宝石信息
     * @param type $where
     * @param string $fields
     * @return type
     */
    public function get_gem_info($where,$fields = '')
    {
        if (!$fields) {
            $fields = "A.idx AS id,B.gem_no AS gem_no,B.name AS name,A.gem_num AS gem_num,B.quality AS quality,B.pic AS pic,B.attribute AS attribute, B.attr_value AS attr_value,B.descript AS descript";
        }
        $sql        = "SELECT ".$fields." FROM gem AS A,gem_conf AS B WHERE ".$where;
        $gem_info   = $this->CI->court_model->fetch($sql,'row');
        return $gem_info;
    }
    
    /**
     * 获取宝石数量
     */
    public function get_gem_num($uuid,$gem_no)
    {
        $where  = array('gem_no'=>$gem_no,'manager_idx'=>$uuid,'is_use'=>0,'status'=>1);
        $fields = "idx AS id,gem_no,gem_num";
        $gem    = $this->CI->court_model->get_one($where,'gem',$fields);
        return $gem;
    }
    
    /**
     * 获取道具列表
     * @param type $params
     */
    public function get_prop_list($params)
    {
        $select         = "A.idx as id,A.prop_no AS prop_no,B.name AS name,A.num num,B.type AS type";
        $condition      = "A.manager_idx=".$params['uuid']." and A.status = 1 AND B.status = 1";
        $join_condition = "A.prop_no = B.prop_no";
        $tb_a           = "prop AS A";
        $tb_b           = "prop_conf AS B";
        $prop_list      = $this->CI->court_model->get_composite_row_array($condition, $join_condition, $select, $tb_a, $tb_b,true);
        //查看经理未使用的VIP礼包
        $options['where']   = array('manager_idx'=>$params['uuid'], 'use' => 0 , 'status'=>1);
        $options['fields']  = "level , idx";
        $vip_info = $this->CI->court_model->list_data($options,'vippackage_his');      
        if($vip_info){
            foreach ($vip_info as $k => $v){
                $vip_list[$k]['id']      = $v['idx'];
                $vip_list[$k]['prop_no'] = $v['level'];
                $vip_list[$k]['name']    = 'VIP'.$v['level'].'礼包';
                $vip_list[$k]['num']     = 1;
                $vip_list[$k]['type']    = 11;
            }
            $prop_list = array_merge($prop_list , $vip_list);
        }
        return $prop_list;
    }
    
     /**
     * 查看经理道具信息
     */
    public function get_prop_info($where,$fields="")
    {
        $bag_info   = $this->CI->court_model->get_one($where, 'prop',$fields);
        return $bag_info;
    }
    
    /**
     * 获取道具信息
     * @param type $idx
     */
    public function prop_info($idx)
    {
        $sql        = "SELECT A.idx AS id,A.prop_no AS prop_no,B.pic AS pic,B.frame AS frame,B.descript AS descript FROM prop as A,prop_conf AS B WHERE A.idx = ".$idx." AND A.prop_no = B.prop_no AND A.status = 1 AND B.status = 1";
        $prop_info  = $this->CI->court_model->fetch($sql,'row');
        return $prop_info;
    }
    
    /*
     * 获取魔法包内含道具信息
     */
    public function magic_pack_info($id)
    {
        // 根据道具id,判断经理是否有魔法包
        $magic_pack_info = $this->CI->court_model->get_one(array('idx'=>$id,'status'=>1),'prop',"prop_no");
        if (!$magic_pack_info) {
            log_message('error', 'without_prop_err:'.$this->court_lib->ip.',魔法包编号错误');
            $this->CI->output_json_return('without_prop_err');
        }
        //获取道具ID
        $sql = "SELECT t2.idx as id , t2.prop_no as prop_no , t2.`name` as `name` , t2.pic as pic FROM magic_pack_conf as t1 LEFT JOIN prop_conf as t2 ON t1.prop_no = t2.prop_no WHERE package_id = {$magic_pack_info['prop_no']}";
        $prop_info  = $this->CI->court_model->fetch($sql,'all');
        if(!$prop_info){
            log_message('info', 'empty_data:'.$this->court_lib->ip.',未查询到道具数据');
            $this->CI->output_json_return('empty_data');
        }
        foreach ($prop_info as $k => &$v)
        {
            $v['num'] = 1;
        }
        return $prop_info;
    }

    /**
     * 使用道具
     * @param type $params
     * @return boolean
     */
    public function prop_use($params)
    {
        // 5体力药水6耐力药水8欧元道具
        if ($params['type'] == 5) {
            $res = $this->physical_recover_by_posion($params);
        } elseif ($params['type'] == 6) {
            $res = $this->endurance_recover($params);
        } elseif ($params['type'] == 8) {
            $res = $this->europrop_use($params);
        }
        
        if (!$res) {// 道具使用失败
            log_message('error', '：'.$this->ip.',道具使用失败');
            $this->CI->output_json_return('');
        }
        return true;
    }
    
    /**
     * 通过体力药水恢复经理体力
     * @param type $params
     */
    public function physical_recover_by_posion($params)
    {
        // 根据道具id，查看是那种类型的体力药水
        $prop_info = $this->CI->court_model->get_one(array('idx'=>$params['id'],'status'=>1),'prop',"prop_no,num");
        if (!$prop_info) {
            log_message('error', 'without_prop_err:'.$this->court_lib->ip.',欧元道具编号错误');
            $this->CI->output_json_return('without_prop_err');
        }
        if ($prop_info['prop_no'] == 501) {
            $params['add_phy_num']  = 20;// 体力恢复50
        } else if ($prop_info['prop_no'] == 502) {
            $params['add_phy_num']  = 60;// 体力恢复80
        } else {
            log_message('error', 'only_use_potion_strength:'.$this->court_lib->ip.',经理没有该道具');
            $this->CI->output_json_return('only_use_potion_strength');
        }
        $this->CI->court_model->start();
        $manager_info   = $this->CI->utility->get_manager_info($params);
        $physical_strenth   = 56 + ($manager_info['level']-1)*2; // 经理当前体力上限数
        if ($manager_info['physical_strenth'] < $physical_strenth) {// 经理未达到体力上限
            // 消耗道具--体力药水prop_no
            if ($prop_info['num'] -1 > 0) {
                $data_3 = array('num' => $prop_info['num'] - 1);
            } else {
                $data_3 = array('status' => 0);
            }
            $where_3    = array('manager_idx' => $params['uuid'],'prop_no' => $prop_info['prop_no']);
            $upt_res_3  = $this->CI->court_model->update_data($data_3, $where_3, 'prop');
            if (!$upt_res_3) {
                log_message('error', 'update_prop_error:'.$this->ip.',经理背包信息更新失败');
                $this->CI->court_model->error();
                $this->CI->output_json_return('physical_recover_err');
            }
            // 更新（提高）经理当前体力值
            $value  = $params['add_phy_num'] + $manager_info['physical_strenth'];
            $data   = array('physical_strenth' => $value);
            $where  = array('idx' => $params['uuid'], 'status' => 1);
            $this->CI->load->library('manager_lib');
            $upt_res = $this->CI->manager_lib->update_manager_info($data, $where);
            if (!$upt_res) {
                log_message('error', 'update_manager_info_error:'.$this->ip.',经理当前体力值更新失败');
                $this->CI->court_model->error();
                $this->CI->output_json_return('physical_recover_err');
            }
            
            // 插入道具使用历史记录
            $data_4 = array(
                "manager_idx"   => $params['uuid'],
                'manager_name'  => $manager_info['name'],
                'prop_no'       => $prop_info['prop_no'],
                'prop_name'     => '',
                'info'          => "使用道具".$prop_info['prop_no'],
                'status'        => 1,
            );
            $ist_p  = $this->insert_prop_his($data_4);
            if (!$ist_p) {
                $this->CI->court_model->error();
                $this->CI->output_json_return('insert_prop_his_err');
                return false;
            }
        }
        $this->CI->court_model->success();
        return true;
    }
    
    /**
     * 使用道具历史记录
     */
    public function insert_prop_his($data)
    {
        $data_ = array(
            "manager_idx"   => $data['manager_idx'],
            'manager_name'  => $data['manager_name'],
            'prop_no'       => $data['prop_no'],
            'prop_name'     => 0,
            'info'          => (int)$data['info'],
            'status'        => 1,
        );
        $ist_res    = $this->CI->court_model->insert_data($data_,'prop_his');
        if (!$ist_res) {
            log_message('error', 'insert_prop_his:'.$this->ip.',道具使用历史记录插入失败');
            return false;
        }
        return true;
    }
    
    /**
     * 经理耐力恢复
     * @param type $params
     */
    public function endurance_recover($params)
    {
        // 根据道具id,判断经理是否存在耐力药水
        $prop_info = $this->CI->court_model->get_one(array('idx'=>$params['id'],'status'=>1),'prop',"prop_no,num");
        if (!$prop_info) {
            log_message('error', 'without_prop_err:'.$this->court_lib->ip.',欧元道具编号错误');
            $this->CI->output_json_return('without_prop_err');
        }
        if ($prop_info['prop_no'] == 601) {
            $params['add_endurance_num']  = 10;// 耐力恢复10
        } else if ($prop_info['prop_no'] == 602) {
            $params['add_endurance_num']  = 25;// 耐力恢复25
        } else {
            log_message('error', 'only_use_potion_endurance:'.$this->court_lib->ip.','.  http_build_query($_REQUEST));
            $this->CI->output_json_return('only_use_potion_endurance');
        }
        
        $this->CI->court_model->start();
        $manager_info   = $this->CI->utility->get_manager_info($params);
        if ($manager_info['endurance'] < 50) {// 经理未达到耐力上限
            // 消耗道具--体力药水prop_no
            if ($prop_info['num'] - 1 > 0) {
                $data_3 = array('num' => $prop_info['num'] - 1);
            } else {
                $data_3 = array('status' => 0);
            }
            $where_3    = array('manager_idx' => $params['uuid'], 'prop_no' => $prop_info['prop_no']);
            $upt_res_3  = $this->CI->court_model->update_data($data_3, $where_3, 'prop');
            if (!$upt_res_3) {
                log_message('error', 'update_prop_error:'.$this->ip.',经理背包信息更新失败');
                $this->CI->court_model->error();
                $this->CI->output_json_return('endurance_recover_err');
            }
            
            // 更新（提高）经理当前耐力值
            $value  = $params['add_endurance_num'] + $manager_info['endurance'];
            $data   = array('endurance' => $value);
            $where  = array('idx' => $params['uuid'], 'status' => 1);
            $this->CI->load->library('manager_lib');
            $upt_res = $this->CI->manager_lib->update_manager_info($data, $where);
            if (!$upt_res) {
                log_message('error', 'update_manager_info_error:'.$this->ip.',经理当前体力值更新失败');
                $this->CI->court_model->error();
                $this->CI->output_json_return('endurance_recover_err');
            }
            
            // 插入道具使用历史记录
            $data_4 = array(
                "manager_idx"   => $params['uuid'],
                'manager_name'  => $manager_info['name'],
                'prop_no'       => $prop_info['prop_no'],
                'prop_name'     => '',
                'info'          => "使用道具".$prop_info['prop_no'],
                'status'        => 1,
            );
            $ist_p  = $this->insert_prop_his($data_4);
            if (!$ist_p) {
                $this->CI->court_model->error();
                $this->CI->output_json_return('insert_prop_his_err');
                return false;
            }
            $this->CI->court_model->success();
        }
        return true;
    }
    
    /*
     * 使用魔法包
     */
    public function magic_pack_use($params)
    {
        // 根据道具id,判断经理是否有魔法包
        $magic_pack_info = $this->CI->court_model->get_one(array('idx'=>$params['id'],'status'=>1),'prop',"prop_no,num");
        if (!$magic_pack_info) {
            log_message('error', 'without_prop_err:'.$this->court_lib->ip.',魔法报编号错误');
            $this->CI->output_json_return('without_prop_err');
        }
        //取出魔法包内的物品信息
        $options['where']   = array('package_id'=>$magic_pack_info['prop_no'],'status'=>1);
        $options['fields']  = "package_id,type,prop_no,probability";
        $prop_info = $this->CI->court_model->list_data($options,'magic_pack_conf');
        if (!$prop_info) {
            log_message('error', 'without_prop_err:'.$this->court_lib->ip.',魔法报编号错误');
            $this->CI->output_json_return('without_prop_err');
        }
        //随机得到经理获得的物品
        $rand_max = 100;
        foreach ($prop_info as $k => $v)
        {
            $rands = mt_rand(1,$rand_max);
            if($rands <= $v['probability']){
                $prop_no = $v['prop_no'];
                break;
            }
            else{
                $rand_max = $rand_max - $v['probability'];
            }
        }
        //将道具发放给经理
        $this->CI->court_model->start();
        $data['uuid']    = $params['uuid'];
        $data['prop_no'] = $prop_no;
        $res = $this->CI->utility->insert_prop_info($data , 1);
        if(!$res){
            $this->CI->court_model->error();
            $this->CI->output_json_return('insert_prop_err');
        }
        //减少魔法包数量
        if ($magic_pack_info['num'] - 1 > 0) {
            $update = array('num' => $magic_pack_info['num'] - 1);
        } else {
            $update = array('status' => 0);
        }
        $where    = array('manager_idx' => $params['uuid'], 'prop_no' => $magic_pack_info['prop_no']);
        $upt_res  = $this->CI->court_model->update_data($update, $where, 'prop');
        if (!$upt_res) {
            log_message('error', 'update_prop_error:'.$this->ip.',经理背包信息更新失败');
            $this->CI->court_model->error();
            $this->CI->output_json_return('euro_prop_use_err');
        }
        $this->CI->court_model->success();
        return $prop_no;
    }
    
    /*
     * 使用vip礼包
     */
    public function vip_pack_use($params)
    {
        // 根据道具id,判断经理是否有vip礼包
        $vip_pack_info = $this->CI->court_model->get_one(array('idx'=>$params['id'],'manager_idx'=>$params['uuid'],'status'=>1,'use' => 0),'vippackage_his',"level,use");
        if (!$vip_pack_info) {
            log_message('error', 'without_prop_err:'.$this->court_lib->ip.',vip礼包编号错误');
            $this->CI->output_json_return('without_prop_err');
        }
        //取出vip礼包内的物品信息
        $reward_info = $this->CI->court_model->get_one(array('level'=>$vip_pack_info['level'],'status'=>1),'vippackage_conf',"player,equipt,prop,gem");
        if (!$reward_info) {
            log_message('error', 'prop_conf_err:'.$this->court_lib->ip.',vip礼包配置错误');
            $this->CI->output_json_return('prop_conf_err');
        }
        //发放奖励
        $this->CI->court_model->start();
        foreach ($reward_info as $k => $v){
            if($v){
                //球员奖励
                if($k == 'player'){
                    $player = $this->CI->utility->get_item_reward($v , 'id' , 'equipt');
                    foreach ($player as $key => $value){
                        $insert['uuid']      = $params['uuid'];
                        $insert['player_no'] = $value['id'];
                        $insert['level']     = $value['level'];
                        for($i = 1;$i <= $value['num']; $i++){
                            $res = $this->CI->utility->insert_player_info($insert);
                            if(!$res){
                                log_message('error', 'vip_pack_send_player_err:'.$this->ip.',发送 VIP礼包-球员失败');
                                $this->CI->court_model->error();
                                $this->CI->output_json_return('vip_pack_send_player_err');
                            }
                        }
                        
                    }
                }
                //装备奖励
                if($k == 'equipt'){
                    $equipt = $this->CI->utility->get_item_reward($v , 'id' , 'equipt');
                    foreach ($equipt as $key => $value){
                        $insert['uuid']      = $params['uuid'];
                        $insert['equipt_no'] = $value['id'];
                        $insert['level']     = $value['level'];
                        for($i = 1;$i <= $value['num']; $i++){
                            $res = $this->CI->utility->insert_equipt_info($insert);
                            if(!$res){
                                log_message('error', 'vip_pack_send_equipt_err:'.$this->ip.',发送 VIP礼包-装备失败');
                                $this->CI->court_model->error();
                                $this->CI->output_json_return('vip_pack_send_equipt_err');
                            }
                        }
                    }
                }
                //道具奖励
                if($k == 'prop'){
                    $prop = $this->CI->utility->get_item_reward($v , 'id' , 'prop');
                    foreach ($prop as $key => $value){
                        $insert['uuid']    = $params['uuid'];
                        $insert['prop_no'] = $value['id'];
                        $insert['num']     = $value['num'];
                        $res = $this->CI->utility->insert_prop_info($insert , $value['num']);
                        if(!$res){
                            log_message('error', 'vip_pack_send_prop_err:'.$this->ip.',发送 VIP礼包-道具失败');
                            $this->CI->court_model->error();
                            $this->CI->output_json_return('vip_pack_send_prop_err');
                        }
                    }
                }
                //宝石奖励
                if($k == 'gem'){
                    $gem = $this->CI->utility->get_item_reward($v , 'id' , 'prop');
                    foreach ($gem as $key => $value){
                        $insert['uuid']   = $params['uuid'];
                        $insert['gem_no'] = $value['id'];
                        $insert['num']    = $value['num'];
                        $res = $this->CI->utility->insert_gem_info($insert);
                        if(!$res){
                            log_message('error', 'vip_pack_send_gem_err:'.$this->ip.',发送 VIP礼包-宝石失败');
                            $this->CI->court_model->error();
                            $this->CI->output_json_return('vip_pack_send_gem_err');
                        }
                    }
                }
            unset($insert);
            }
        }
        //消耗VIP礼包
        $where    = array('idx'=>$params['id']);
        $update   = array('use' => 1);
        $upt_res  = $this->CI->court_model->update_data($update, $where, 'vippackage_his');
        if (!$upt_res) {
            log_message('error', 'vip_pack_use_err:'.$this->ip.',消耗vip礼包失败');
            $this->CI->court_model->error();
            $this->CI->output_json_return('vip_pack_use_err');
        }
        $this->CI->court_model->success();
        return true;
    }


    /**
     * 欧元道具的使用
     * @param type $params
     */
    public function europrop_use($params)
    {
        // 根据道具id,判断经理是否存在耐力药水
        $prop_info = $this->CI->court_model->get_one(array('idx'=>$params['id'],'status'=>1),'prop',"prop_no,num");
        if (!$prop_info) {
            log_message('error', 'without_prop_err:'.$this->court_lib->ip.',欧元道具编号错误');
            $this->CI->output_json_return('without_prop_err');
        }
        // 道具编号 801 802 803
        if ($prop_info['prop_no'] == 801) {
            $params['add_euro']  = 10000;// 获取1w欧元
        } else if ($prop_info['prop_no'] == 802) {
            $params['add_euro']  = 80000;// 获取8w欧元
        }  else if ($prop_info['prop_no'] == 803) {
            $params['add_euro']  = 150000;// 获取15w欧元
        }else {
            log_message('error', 'only_use_euro_prop:'.$this->court_lib->ip.',欧元道具编号错误');
            $this->CI->output_json_return('only_use_euro_prop');
        }
        $this->CI->court_model->start();
        $manager_info   = $this->CI->utility->get_manager_info($params);
        // 消耗道具--体力药水prop_no
        if ($prop_info['num'] - 1 > 0) {
            $data_3     = array('num' => $prop_info['num'] - 1);
        } else {
            $data_3     = array('status' => 0);
        }
        $where_3    = array('manager_idx' => $params['uuid'], 'prop_no' => $prop_info['prop_no']);
        $upt_res_3  = $this->CI->court_model->update_data($data_3, $where_3, 'prop');
        if (!$upt_res_3) {
            log_message('error', 'update_prop_error:'.$this->ip.',经理背包信息更新失败');
            $this->CI->court_model->error();
            $this->CI->output_json_return('euro_prop_use_err');
        }
        // 更新（提高）经理当前欧元值
        $value  = $params['add_euro'] + $manager_info['euro'];
        $this->CI->court_model->start();
        $data    = array('euro' => $value);
        $where   = array('idx' => $params['uuid'], 'status' => 1);
        $this->CI->load->library('manager_lib');
        $upt_res = $this->CI->manager_lib->update_manager_info($data, $where);
        if (!$upt_res) {
            log_message('error', 'update_manager_info_error:'.$this->ip.',经理当前欧元值更新失败');
            $this->CI->court_model->error();
            $this->CI->output_json_return('euro_prop_use_err');
        }
        
        // 插入道具使用历史记录
        $data_4 = array(
            "manager_idx"   => $params['uuid'],
            'manager_name'  => $manager_info['name'],
            'prop_no'       => $prop_info['prop_no'],
            'prop_name'     => '',
            'info'          => "使用道具".$prop_info['prop_no'],
            'status'        => 1,
        );
        $ist_p  = $this->insert_prop_his($data_4);
        if (!$ist_p) {
            $this->CI->court_model->error();
            $this->CI->output_json_return('insert_prop_his_err');
            return false;
        }

        $this->CI->court_model->success();
        return true;
    }
    
    /**
     * 获取球员属性之和（基础属性(升阶后的属性) + 装备 + 宝石 + 训练）
     * @param array $attribute_new 球员原基础属性
     * @param bigint $player_idx 球员idx
     * @param int $type 获取数据类型 1只获取一级属性2只获取2级属性3都获取
     * @param int $is_show 数据是否用于前端显示1是2否（前端显示去掉隐形属性）
     * @return type
     */
    public function get_player_attribute_sum($attribute_new,$player_idx,$type = 2,$is_show = 1)
    {
        // 获取属性编号
        $options['where']   = array('status'=>1);
        $options['fields']  = "attr_no,level,parent_no,name,name_ch";
        $attrirbute_conf    = $this->CI->court_model->list_data($options,'attribute_conf');
        foreach ($attrirbute_conf as $k=>$v) {
            $attr_conf[$v['attr_no']]   = $v;
            $attrname_conf[$v['name']]  = $v;
        }
        
        // 获取球员装备（宝石）--属性加成
        $options_1['where']     = array('player_idx'=>$player_idx,'status'=>1);
        $options_1['fields']    = "idx,equipt_no,level";
        $equipt_list        = $this->CI->court_model->list_data($options_1,'equipt');
        if ($equipt_list) {
            foreach ($equipt_list as $k=>$v) {
                $econf_info = $this->CI->court_model->get_one(array('equipt_no'=>$v['equipt_no'],'level'=>$v['level'],'status'=>1),'equipt_conf','attradd_info');
                $attradd_arr    = explode("|", trim($econf_info['attradd_info'], "|"));
                foreach ($attradd_arr as $key=>$val) {
                    $arr = explode(":", $val);
                    $attr_name  = $attr_conf[$arr[0]];
                    $value_new  = $this->CI->utility->attribute_change(array($attr_name['name']=>$attribute_new[$attr_name['name']]),3,1,$arr[1]);
                    $attribute_new[$attr_name['name']]  = $value_new[$attr_name['name']];
                }
                
                // 获取装备宝石--属性加成
                $gem_info = $this->CI->court_model->get_one(array('equipt_idx'=>$v['idx'],'is_use'=>1,'status'=>1),'gem',"gem_no");
                if ($gem_info) {
                    $gconf_info = $this->CI->court_model->get_one(array('gem_no'=>$gem_info['gem_no'],'status'=>1),'gem_conf','attribute,attr_value');
                    $attr_name  = $attr_conf[$gconf_info['attribute']];
                    $value_new  = $this->CI->utility->attribute_change(array($attr_name['name']=>$attribute_new[$attr_name['name']]),3,1,$gconf_info['attr_value']);
                    $attribute_new[$attr_name['name']]  = $value_new[$attr_name['name']];
                }
            } 
        }
        
        // 训练属性加成
        $options['where']   = array('player_idx'=>$player_idx,'data_status'=>0,'status'=>1);
        $options['fields']  = "attribute,towlevel_attr,towlevel_value";
        $allo_list          = $this->CI->court_model->list_data($options,'trainpoint_allo_his');
        
        if ($allo_list) {
            foreach ($allo_list as $k=>$v) {
                $train_new[$v['towlevel_attr']] += $v['towlevel_value'];
            }
            foreach ($train_new as $key=>$val) {
                $attr_name  = $attr_conf[$key]['name'];
                $value_new  = $this->CI->utility->attribute_change(array($attr_name=>$attribute_new[$attr_name]),3,1,$val);
                $attribute_new[$attr_name]  = $value_new[$attr_name];
            }
        }
        if ($is_show == 1) {
            unset($attribute_new['header']);
            unset($attribute_new['acceleration']);
        }
        
        // 重组属性格式
        foreach ($attribute_new as $k=>$v) {
            if ($type == 1) {// 只取一级属性
                $attribute[$attrname_conf[$k]['parent_no']] +=$v;
            } elseif($type == 2) {// 只取二级属性
                if ($is_show == 1) {
                    $attribute[$attrname_conf[$k]['attr_no']] =$v;
                } else {
                    $attribute[$attrname_conf[$k]['name']] =$v;
                }
            } else {// 都取
                    $attribute[$attrname_conf[$k]['parent_no']]['attirbute']    = $attrname_conf[$k]['parent_no'];
                    $attribute[$attrname_conf[$k]['parent_no']]['value'] +=$v;
                    $attribute[$attrname_conf[$k]['parent_no']]['level2attr'][$attrname_conf[$k]['attr_no']] = array('attribute'=>$attrname_conf[$k]['attr_no'],'value'=>$v);
            }
        }
        return $attribute;
    }
    
    /**
     * 查看球员卡在哪里关卡（关卡是否开启），那些魔法社存在
     */
    public function get_player_exists_where($player_no,$uuid)
    {
        $data   = array();
        // 球员卡存在某魔法社中
        $where  = array('object_no'=>$player_no,'status'=>1);
        $euro   = $this->get_total_count($where, 'drawproble_euro');
        if (!$euro) {
            $prop = $this->get_total_count($where, 'drawproble_prop');
            if (!$prop) {
                $tickets = $this->get_total_count($where, 'drawproble_tickets');
                if ($tickets) {
                    $data[]   = array('type'=>1,'draw_type'=>3);// type=1:魔法社
                }
            } else {
                $data[]   = array('type'=>1,'draw_type'=>2);
            }
        } else {
            $data[]   = array('type'=>1,'draw_type'=>1);
        }
        
        // 球员卡存在某副本关卡中
        $sql        = "SELECT copy_no,ckpoint_no,type,name,pic FROM ckpoint_conf WHERE player_conf_idx IN(SELECT idx FROM player_probable_conf WHERE player_1 LIKE'".$player_no.":%' OR player_2 LIKE'".$player_no.":%'  OR player_3 LIKE'".$player_no.":%' OR player_4 LIKE'".$player_no.":%' OR player_5 LIKE'".$player_no.":%' AND status = 1) AND status = 1";
        $data_list  = $this->CI->court_model->fetch($sql,'result');
        if ($data_list) {
            foreach ($data_list as $k=>$v) {
                // 判断该关卡是否开启TODO
                $sql        = "SELECT copy_no,ckpoint_no FROM ckpoint_complete_his WHERE manager_idx = ".$uuid." AND status = 1 ORDER BY copy_no DESC,ckpoint_no DESC";
                $ck_info    = $this->CI->court_model->fetch($sql,'row');
                $unlock     = 0;
                if ($ck_info['copy_no'] > $v['copy_no']) {
                    $unlock     = 1;
                } elseif($ck_info['copy_no'] == $v['copy_no'] && $ck_info['ckpoint_no'] >= $v['ckpoint_no']) {
                    $unlock     = 1;
                }
                $data[]     = array('type'=>2,'copy_no'=>$v['copy_no'],'ckpoint_no'=>$v['ckpoint_no'],'unlock'=>$unlock,'name'=>$v['name'],'pic'=>$v['pic'],'match_type'=>$v['type']);// type=2:副本
            }
        }
        return $data;
    }
    
    /**
     * 嵌入宝石
     * @param type $params
     */
    public function do_insert_gem($params)
    {
        // 查看是否存在该宝石
        $where      = array('idx'=>$params['id'],'manager_idx'=>$params['uuid'],'is_use'=>0,'status'=>1);
        $fields     = "gem_no,gem_num";
        $gem_info   = $this->CI->court_model->get_one($where,'gem',$fields);
        if (!$gem_info) {
            log_message('error', 'do_insert_gem:without_gem_info_err'.$this->ip.',经理暂无该宝石');
            $this->CI->output_json_return('without_gem_info_err');
        }
        // 查看装备是否有空余的宝石孔
        $sql            = "SELECT A.idx AS id,A.equipt_no AS equipt_no,A.level AS level,B.holes AS holes FROM equipt AS A,equipt_conf AS B WHERE A.idx = ".$params['equipt_id']." AND manager_idx = ".$params['uuid']." AND A.equipt_no = B.equipt_no AND A.level = B.level AND A.status = 1 AND B.status = 1";
        $equipt_info    = $this->CI->court_model->fetch($sql,'row');
        if (!$equipt_info) {
            log_message('error', 'do_insert_gem:equipt_empty_data'.$this->ip.',经理暂无该装备');
            $this->CI->output_json_return('equipt_empty_data');
        }
        if (!$equipt_info['holes']) {
            log_message('error', 'do_insert_gem:without_gem_holes_err'.$this->ip.',该装备暂无宝石孔');
            $this->CI->output_json_return('without_gem_holes_err');
        }
        $where_2    = array('manager_idx'=>$params['uuid'],'equipt_idx'=>$params['equipt_id'],'status'=>1);
        $used_num   = $this->get_total_count($where_2, "gem");
        if ($used_num >= $equipt_info['holes']) {
            log_message('error', 'do_insert_gem:without_gem_holes_err'.$this->ip.',该装备宝石孔使用完了');
            $this->CI->output_json_return('without_gem_holes_err');
        }
        // 插入宝石操作
        $this->CI->court_model->start();
        if ($gem_info['gem_num'] > 1) {
            $fields_2   = array('gem_num'=>$gem_info['gem_num'] - 1);
        } else {
            $fields_2   = array('status'=>0);
        }
        $upt_res    = $this->CI->court_model->update_data($fields_2,$where,'gem');
        if (!$upt_res) {
            $this->CI->court_model->error();
            log_message('error', 'do_insert_gem:gem_holes_insert_err'.$this->ip.',镶嵌宝石时，宝石信息修改失败');
            $this->CI->output_json_return('gem_holes_insert_err');
        }
        $data   = array(
            'manager_idx'   => $params['uuid'],
            'gem_no'        => $gem_info['gem_no'],
            'equipt_idx'    => $params['equipt_id'],
            'gem_num'       => 1,
            'is_use'        => 1,
            'status'        => 1,
        );
        $ist_res    = $this->CI->court_model->insert_data($data,'gem');
        if (!$ist_res) {
            $this->CI->court_model->error();
            log_message('error', 'do_insert_gem:gem_holes_insert_err'.$this->ip.',镶嵌宝石时，宝石信息插入失败');
            $this->CI->output_json_return('gem_holes_insert_err');
        }
        
        $this->CI->court_model->success();
        return true;
    }
    
    public function do_delete_gem($params)
    {
        // 查看是否存在嵌有该宝石的装备
        $where      = array('idx'=>$params['id'],'equipt_idx'=>$params['equipt_id'],'is_use'=>1,'status'=>1);
        $fields     = "gem_no,gem_num";
        $gem_info   = $this->CI->court_model->get_one($where,'gem',$fields);
        if (!$gem_info) {
            log_message('error', 'do_insert_gem:without_gem_of_equip'.$this->ip.',没有镶嵌该宝石的装备');
            $this->CI->output_json_return('without_gem_of_equip');
        }
        //取下宝石
        $this->CI->court_model->start();
        $fields_2   = array('status'=>0);
        $upt_res    = $this->CI->court_model->update_data($fields_2,$where,'gem');
        if (!$upt_res) {
            $this->CI->court_model->error();
            log_message('error', 'do_insert_gem:gem_delete_err'.$this->ip.',取下宝石时，宝石信息修改失败');
            $this->CI->output_json_return('gem_delete_err');
        }
        $data   = array(
            'manager_idx'   => $params['uuid'],
            'gem_no'        => $gem_info['gem_no'],
            'equipt_idx'    => 0,
            'gem_num'       => 1,
            'is_use'        => 0,
            'status'        => 1,
        );
        $ist_res    = $this->CI->court_model->insert_data($data,'gem');
        if (!$ist_res) {
            $this->CI->court_model->error();
            log_message('error', 'do_insert_gem:gem_delete_err'.$this->ip.',取下宝石时，宝石信息插入失败');
            $this->CI->output_json_return('gem_delete_err');
        }
        
        $this->CI->court_model->success();
    }
    
}
