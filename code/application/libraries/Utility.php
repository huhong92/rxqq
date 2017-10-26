<?php
/**
 * 公共帮助类库文件
 * author huhong
 * date 2016-05-04
 */
class Utility {
    private $CI;
    
    public function __construct() {
        $this->CI = & get_instance();
    }
    
    /**
     * 校验参数
     * @param array $params
     * @param string $sign
     * @return string
     */
    public function check_sign($params, $sign)
    {
        $get_sign = $this->get_sign($params);
        if (ENVIRONMENT != 'development') {
            if ($get_sign != $sign) {
                log_message('error', 'sign_err:'.$this->CI->input->ip_address().','.$get_sign.',签名错误');
                $this->CI->output_json_return('sign_err');
            }
        }
        return true;
    }
    
    /**
     * 获取参数校验值
     * @param array $params
     * @return string 校验值
     */
    public function get_sign($params)
    {
        foreach ($params as $key => $val) {
            if ($key == 'sign' || $key === '' || $val === '') {
                continue;
            }
            $para[$key] = $params[$key];
        }
        ksort($para);
        $arg = '';
        foreach ($para as $k=>$v) {
            $arg .= $k.'='.$v.'&';
        }
        $sign_key = $this->CI->passport->get('sign');
        $arg .= 'key='.$sign_key;
         return md5($arg);
    }
    
    /**
     * 获取抽卡概率--获取抽取结果
     * @params array options array ("where" => array(), 'fields' =>'', ...)
     * @params string table 表名
     * @return array 获取抽中数据
     */
    public function probable($options, $table)
    {
        $this->CI->load->model('draw_model');
        $list   = $this->CI->draw_model->list_data($options, $table);
        if (empty($list) || !is_array($list)) {
            $this->CI->output_json_return('empty_data');
        }
        // 先将二维数组变为一维数组, 然后将概率值扩大100倍
        $pro    = array();
        foreach ($list as $k=>$v) {
            $pro[$k] = $v['probable']*100;
        }
        asort($pro);
        $sum = array_sum($pro);   //总概率
        // 获取随机数，选中抽中物品
        $rand = mt_rand(1,$sum);
        foreach ($pro as $k=>$v) {  
            if ($rand <= $v) {
                $result = $k;
                break;
            } else {
                $rand = $rand - $v;
            }
        }
        return $list[$result];
    }
    
    
    /**
     *  单个概率计算
     * @param float $probable_valu 概率值(保留一位小数)35.1%
     */
    public function probable_sigle_count($probable_valu)
    {
        $value  = ($probable_valu*10);
        $rand   = rand(1, 1000);
        if ($rand < $value) {
            return true;
        }
        return false;
    }
    
    /**
     * 球员属性折扣  【【(所有属性加成，都在lib库属性基础上增加)】】
     * @param int $para = array(球员编号|球员id,level)
     * @param array $attr 部分需要加成属性 array("name"=>"value", "", "",)
     * @param int $add_value 数值5（表示的5%或者5）
     * @param int $add_type 加成类型 1：百分比 2:数值
     * @param int $value_type 值类型（1增加2折扣3生成当前球员的+N阶属性）
     * @param int $copy 1:副本-存在阶层 库2不存在
     * @return array 返回加成后的部分属性值
     */
    public function attribute_discount($para,$attr, $add_value, $add_type = 1, $value_type = 1,$copy=1)
    {
        $this->CI->load->library('court_lib');
        if ($copy === 1) {
            $info   = $this->CI->court_lib->get_player_lib_info($para);
        } else {
            $info   = $this->CI->court_lib->get_player_info($para);
        }
        $player_info            = $this->recombine_attr($info);
        $attribute              = array();
        foreach ($player_info['attribute'] as $k=>$v) {
            if ($add_type == 1 && $value_type == 1) {   // 百分比-加属性
                $attribute[$k] = round(($v*$add_value)/100 + $v, 1);
            } elseif($add_type == 2  && $value_type == 1) { // 数值-加属性
                $attribute[$k] = round(($v + $add_value), 1);
            }  elseif($add_type == 1  && $value_type == 2) {// 百分比-折扣属性
                if ($copy) {// 副本折扣，存在+N阶
                    for($i=$para['level'];$i>0;$i--) {
                        $v = ($v*0.05+$v);
                    }
                }
                $attribute[$k] = round(($v*$add_value)/100, 1);
            }  elseif($add_type == 1  && $value_type == 3) {// 百分比-手动生成球员+N阶后的属性
                for($i=$para['level'];$i>0;$i--) {
                    $v = ($v*0.05+$v);
                }
                $attribute[$k] = round($v, 1);
            }
        }
        return $attribute;
    }
    
    
    /**
     * 修改球员卡属性值
     * @param array $source_attr    需要改变的属性集合
     * @param int   $attr_type      变化类型 1:球员升阶2:副本折扣3:训练、装备、宝石、阵型、意志、组合加成4机器人
     * @param int   $value_type     值类型1：数值2：百分比
     * @param float $value          数值
     * @param int   $player_level   球员阶（副本有作用）
     * @param array $base_data      计算属性加成时，基础数据（为空时，用source_attr,否则使用$base_data）
     * @return type
     */
    public function attribute_change($source_attr,$attr_type = 1,$value_type = 1,$value = 0,$player_level = 0,$base_data = array())
    {
        foreach ($source_attr as $k=>$v) {
            if ($attr_type === 1) {// 升阶球员卡--属性值变化
                $attribute[$k] = round($v*1.05, 1);
            } elseif ($attr_type === 2) {// 球员副本-属性折扣变化
                for($i=$player_level;$i>0;$i--) {
                    $v = ($v*0.05+$v);
                }
                $attribute[$k] = round(($v*$value)/100, 1);
            }elseif($attr_type === 4){// 机器人
                for($i=$player_level;$i>0;$i--) {
                    $v = ($v*1.05);
                }
                $attribute[$k] = round($v, 1);
            } else {// 装备、宝石、阵型、意志、组合-属性变化
                if ($value_type == 1) {// 数值
                    $attribute[$k] = round(($v + $value), 1);
                } else {// 百分比，（返回百分比之后的数据）
                    if ($base_data) {
                        foreach ($base_data as $key=>$val) {
                            if ($key == $k) {
                                $attribute[$k] = round(($val*$value)/100 + $v, 1);
                            }
                        }
                    } 
                }
            }
        }
        return $attribute;
    }
    
    /**
     * socket连接服务器-或者赛果
     * @param type $server
     * @param type $port
     * @param string $key
     */
    public function socket_connect($server, $port,$key)
    {
        try {
            $socket = socket_create(AF_INET, SOCK_STREAM, SOL_TCP) ;// 返回套接字
            $res    = @socket_connect ($socket, $server, $port);// 连接是否成功
            if (!$res) {
                log_message("error",  "socket_connect fail");
                return false;
            }
            socket_write ($socket, $key);
    //        while($buffer = socket_read($socket, 1024,PHP_NORMAL_READ )) {  
    //            if(!$buffer) {  
    //                break;
    //            }else{  
    //                $result .= $buffer;
    //            }
    //        }
            $result = socket_read($socket,2048); 
            socket_close ($socket);
        } catch (Exception $exc) {
            log_message("error",  $exc->getTraceAsString());
            return false;
        }
        return $result;
    }
    
    /**
     * 存储redis
     * @param string $key redis存储键
     * @param string|array $value redis存储的值
     * @param int $ttl  redis存储有效期
     * @return  bool  TRUE on success, FALSE on failure
     */
    public function save_redis($key, $value, $ttl= 60)
    {
        $result = $this->CI->cache->redis->save($key, $value, $ttl);
        return $result;
    }

    /**
     * 删除redis
     * @param string $key
     * @return bool 
     */
    public function del_redis($key)
    {
        $result = $this->CI->cache->redis->delete($key);
        return $result;
    }

    /**
     * 获取redis内容
     * @param string $key 获取redis的key
     * @return mix string|array 存储值
     */
    public function get_redis_info($key)
    {
        $result = $this->CI->cache->redis->get($key);
        return $result;
    }
    
    /**
     * 获取解压缩后的redis（弃用）
     * @param string $key  赛事结果保存key[例如：Match_...]
     * @param int    $type 赛事类型1副本赛2天梯赛3五大联赛
     * @return type
     */
    public function get_redis_by_unzip($key)
    {
        // 从redis获取比赛结果
        $result = $this->get_redis_info($key);
        if (!$result) {
            return false;
        }
        $a      = gzdecode($result);
        $b      = json_decode($a, true);
        return $b;
    }
    

    /**
     * 从共享磁盘获取比赛结果
     * @param type $key  赛事结果保存key[例如：Match_...]
     * @param type $type 赛事类型1副本赛2天梯赛3五大联赛
     * @return boolean
     */
    public function get_match_file_info($key) {
        // 从共享文件获取比赛结果
        $path       = $this->CI->passport->get('match_result_file');// ./match/result/s001/20170623/16/name
        $date       = date("Ymd",time());
        $hour       = date('H',time());
        $real_path  = $path.$date."/".$hour;
        $filename   = $real_path.$key;
        
        // 判断文件是否存在
        if (!file_exists($filename)) {
            return false;
        }
        $content    = file_get_contents($filename);
        return $content;
    }
    
    /**
     * 获取比赛结果-并处理redis
     * @param string $key  赛事结果保存key[例如：Match_...]
     * @param int    $type 赛事类型1副本赛2天梯赛3五大联赛
     * @return type
     */
    public function get_result_match($key,$type = 1)
    {   
        // 1.获取Redis保存的比赛结果
        $redis  = $this->get_redis_info($key);
        if ($redis) {
            $result     = $redis;
            $del_redis  = 1;
            // 存入文件
            $file   = $this->get_match_file_info($key);
            if (!$file) {
                $path       = $this->CI->passport->get('match_result_file');// ./match/result/s001/20170623/16/name
                $date       = date("Ymd",time());
                $hour       = date('H',time());
                $real_path  = $path.$date."/".$hour."/";
                $filename   = $real_path.$key;
                if (is_dir($path.$date)) {
                    if (!is_dir($real_path)) {
                        // 创建
                        mkdir($real_path,0755,true);
                    }
                } else {
                    // 创建
                    mkdir($real_path,0755,true);
                }
                $res    = file_put_contents($filename, $redis);
                if (!$res) {
                    log_message("error", "get_result_match:赛事结果写入文件失败".date('Y-m-d H:i:s'));
                    $del_redis  = 0;
                }
            }
            // 删除redis
            if ($del_redis) {
                $this->del_redis($key);
            }
        } else {
            $file   = $this->get_match_file_info($key);
            if (!$file) {
                return false;
            }
            $result = $file;
        }
        
        // 5.返回比赛结果
        $a  = gzdecode($result);
        $b  = json_decode($a, true); 
        return $b;
    }
    
    /**
     * 获取经理信息[不包括战斗力]
     * @param type $params $params['uuid]
     * @param string $select 获取单一字段
     * @return array|string
     */
    public function get_manager_info($params, $select = '')
    {
        $this->CI->load->library('manager_lib');
        $m_info = $this->CI->manager_lib->get_manager_info($params['uuid']);
        if ($select) {
            return $m_info[$select];
        }
        return $m_info;
    }
    
    /**
     * 更新经理信息
     */
    function update_m_info($fields,$where)
    {
        $this->CI->load->library('manager_lib');
        $res = $this->CI->manager_lib->update_manager_info($fields, $where);
        if (!$res) {
            return false;
        }
        return $res;
    }
    
    /**
     * @param int $player_no
     * @param int $uuid
     * @return array 球员卡库数据信息
     */
    public function get_player_lib_info($player_no)
    {
        $this->CI->load->library('court_lib');
        $params     = array('player_no'=>$player_no);
        $lib_info   = $this->CI->court_lib->get_player_lib_info($params);
        return $lib_info;
    }
    
    /**
     * 获取球员信息(by id|by player_no)
     * @param array $params
     *  player_info A, player_lib B
     * $type = 1,自增idx
     */
    public function get_player_info($params)
    {
        $this->CI->load->library('court_lib');
        $this->CI->load->library('conf_lib');
        $p_info = $this->CI->court_lib->get_player_info($params);
//        if ($params['struc_type']   == 2) {
//            $p_info['position_no']  = $p_info['position_no2'];
//        }
        $info                   = $this->recombine_attr($p_info);
        $attribute_new          = $this->CI->court_lib->get_player_attribute_sum($info['attribute'],$params['id'],3);
        $info['attribute']    = $attribute_new;
        return $info;
    }
    
    /**
     * 根据属性编号翻译属性名
     */
    public function get_attribute_by_no($attr_no)
    {
        $where = array(
            'attr_no'   => $attr_no,
            'status'    => 1,
        );
        $attr_info = $this->CI->court_model->get_one($where, 'attribute_conf');
        return $attr_info;
    }
    
    /**
     * 重组球员数据格式
     * @param type $player_info
     */
    public function recombine_attr($player_info)
    {
        $attr   = $this->CI->passport->get('attribute_arr');
        foreach($player_info as $k=>$v) {
            if (in_array($k, $attr)) {
                $player_info['attribute'][$k]   =   $v;
                unset($player_info[$k]);
            }
        }
        return $player_info;
    }
    
    /**
     * 将二级属性值换算成一级属性值
     */
    public function towlevel_replace_($player_info)
    {
        $this->CI->load->library('conf_lib');
        $p_info     = $this->recombine_attr($player_info);
        $attr_list  = $this->CI->conf_lib->attribure_conf_list();
        $attribute  = array();
        foreach ($attr_list as $k=>$v) {
            if ($v['name_en'] == 'acceleration' || $v['name_en'] == 'header') {
                continue;
            }
            if ($v['level'] == 2) {// 二级属性
                $attribute[$v['parent_no']] += $p_info['attribute'][$v['name_en']];
            } 
        }
        $p_info['attribute']    = $attribute;
        return $p_info;
    }
    
    /**
     * @param  array $params uuid,player_no
     * 插入球员信息
     */
    public function insert_player_info($params)
    {
        $this->CI->load->library('court_lib');
        $where          = array('player_no'=>$params['player_no'],'status'=>1);
        $player_lib     = $this->CI->court_model->get_one($where,'player_lib');
        if (!$player_lib) {
            log_message('error', 'insert_player_info_err:'.$this->CI->input->ip_address().',insert_player_info_err,球员编号不存在');
            return false;
        }
        $data   = array(
                'manager_idx'           => $params['uuid'],
                'manager_name'          => '',
                'plib_idx'              => $player_lib['idx'],  
                'player_no'             => $player_lib['player_no'],
                'level'                 => $params['level']?$params['level']:0,
                'generalskill_no'       => $player_lib['generalskill_no'],
                'generalskill_level'    => $player_lib['generalskill_no']?1:0,
                'exclusiveskill_no'     => $player_lib['exclusiveskill_no'],
                'is_use'                => 0,
                'position_no'           => 7,
                'position_no2'          => 7,
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
        $res    = $this->CI->court_model->insert_data($data,'player_info');
        if($res)
        {
            $this->CI->load->library('task_lib');
            // 触发成就 - 进阶达人
            $this->CI->task_lib->player_upgrade($params['uuid']);
            $this->CI->task_lib->unlock_player($params['uuid']);
            $this->CI->load->library('tips_lib');
            //触发球员页面红点
            $player_info = $this->CI->court_model->get_one(array('player_no' => $player_lib['player_no'] ,'status' => 1) , 'player_lib');
            if($player_info['quality'] >= 4)
            {
                $this->CI->tips_lib->tip_pages($params['uuid'],1005);
                $this->CI->tips_lib->tip_pages($params['uuid'],1030);
            }
            
            //经理全部卡牌
            $m_p_info = $this->CI->court_model->fetch("SELECT player_no , `level` FROM player_info WHERE manager_idx = {$params['uuid']} AND `status` = 1");
            //经理球员ID集合
            foreach($m_p_info as $k => $v)
            {
                $m_p_no_arr[] = $v['player_no'];
            }
            
            //触发意志红点
            $volition_info = $this->CI->court_model->fetch("SELECT t1.idx as idx , t1.player_detail as player_detail , t2.volition_idx as v_id FROM volition_conf as t1 LEFT JOIN volition as t2 on t1.idx = t2.volition_idx AND t2.is_active = 1 AND t2.manager_idx = {$params['uuid']} AND t2.status = 1 WHERE t1.status = 1 ");     
            //经理未激活的所有意志
            foreach ($volition_info as $k => $v)
            {
                //筛选玩家未达成的意志
                if(!$v['v_id'])
                {
                    $player_detail_str = explode('|', $v['player_detail']);
                    foreach($player_detail_str as $key => $detail)
                    {
                        $player_detail_arr = explode(':', $detail);
                        //生成意志所需的所有球员和相应属性
                        $need_player_info[$v['idx']][$key]['no'] = $player_detail_arr[0];
                        $quality_level = explode('_', $player_detail_arr[1]);
                        $need_player_info[$v['idx']][$key]['level'] = $quality_level[1];
                        $need_player_info[$v['idx']]['no_list'][] = $player_detail_arr[0];
                    }
                }
            }
            
            $volition_tips = FALSE;
            //遍历所有意志信息
            foreach($need_player_info as $k => &$v)
            {
                //如果意志与新添加球员有关
                if(in_array($player_lib['player_no'], $v['no_list']))
                {
                    unset($v['no_list']);
                    $i = 0;
                    //遍历意志需要的所有球员信息
                    $tips_need_num = count($v);
                    foreach($v as $key => $val)
                    {
                        $is_break = 1;
                        //验证经历是否拥有球员
                        foreach($m_p_info as $key2 => $val2)
                        {
                            if($val2['player_no'] == $val['no'] && $val2['level'] == $val['level'])
                            {
                                //达到要求标记并继续
                                $i = $i + 1;
                                $is_break = 0;
                            }
                        }
                        if($is_break)
                            break;
                        //条件全部达成
                        if($tips_need_num == $i)
                           $volition_tips = TRUE; 
                    }
                }
                //跳出所有遍历
                if($volition_tips)
                {
                    break;
                }
            }
            if($volition_tips)
            {
                $this->CI->tips_lib->tip_pages($params['uuid'],1005);
                $this->CI->tips_lib->tip_pages($params['uuid'],1031);
            }
        }
        return $res;
    }
    
    /**
     * 插入装备信息
     */
    public function insert_equipt_info($params)
    {
        $this->CI->load->library('court_lib');
        $data   = array(
                'manager_idx'   => $params['uuid'],
                'manager_name'  => '',
                'player_idx'    => 0,  
                'player_name'   => '',
                'equipt_no'     => $params['equipt_no'],
                'level'         => $params['level']?$params['level']:1,
                'status'        => 1,
        );
        $res    = $this->CI->court_model->insert_data($data,'equipt');
        if($res)
        {
            // 触发成就 - 套装
            $this->CI->load->library('task_lib');
            $this->CI->task_lib->equipt_collect($params['uuid']);
            //触发新手教程 10 
            $this->CI->task_lib->n_c_public_complete($params['uuid'] , 10);
            //触发装备页面红点提示
            $this->CI->load->library('tips_lib');
            $equipt_info = $this->CI->court_model->get_one(array('equipt_no' => $params['equipt_no'] , 'level' => $data['level'] , 'status' => 1) , 'equipt_conf');
            if($equipt_info['type'] == 1)
                $this->CI->tips_lib->tip_pages($params['uuid'],1027);
            if($equipt_info['type'] == 2)
                $this->CI->tips_lib->tip_pages($params['uuid'],1028);
            if($equipt_info['type'] == 3)
                $this->CI->tips_lib->tip_pages($params['uuid'],1029);
        }
        return $res;
    }
    
    /**
     * 插入道具信息
     * @param type $params
     */
    public function insert_prop_info($params,$num = 1)
    {
        $this->CI->load->library('court_lib');
        $where      = array('manager_idx'=>$params['uuid'],'prop_no'=>$params['prop_no'],'status'=>1);
        $fields     = "idx AS id,prop_no,num,manager_idx as uuid";
        $prop_info  = $this->CI->court_lib->get_prop_info($where,$fields);
        if (!$prop_info) {
            $data       = array(
                'manager_idx'   => $params['uuid'],
                'prop_no'       => $params['prop_no'],
                'num'           => $num,
                'status'        => 1,
            );
            $res    = $this->CI->court_model->insert_data($data,'prop');            
            if (!$res) {
                log_message('error', 'insert_prop_info_err:'.$this->CI->input->ip_address().',insert_prop_info_err,插入道具信息数据失败');
                return false;
            }
            return $res;
        }
        
        $fields_2 = array('num'=>$prop_info['num']+$num);
        $where_2  = array('manager_idx'=>$params['uuid'],'prop_no'=>$params['prop_no'],'status'=>1);
        $res    = $this->CI->court_model->update_data($fields_2,$where_2,'prop');
        if (!$res) {
            log_message('error', 'insert_prop_info_err:'.$this->CI->input->ip_address().',insert_prop_info_err,插入道具信息数据失败');
            return false;
        }
        if($params['prop_no'] == 401){
            //触发新手引导 11 升级装备
            $this->CI->load->library('task_lib');
            $this->CI->task_lib->n_equipt_up($params['uuid'] , 11);
        }
        
        return $prop_info['id'];
    }
    
    /**
     * 插入宝石信息
     */
    public function insert_gem_info($params)
    {
        
        $this->CI->load->library('court_lib');
        $gem_info = $this->CI->court_lib->get_gem_num($params['uuid'],$params['gem_no']);
        if ($gem_info) {// 更新数量
            $fields = array('gem_num'=>$gem_info['gem_num']+$params['num']);
            $where  = array('manager_idx'=>$params['uuid'],'gem_no'=>$params['gem_no'],'is_use'=>0,'status'=>1);
            $res    = $this->CI->court_model->update_data($fields,$where,'gem');
            if (!$res) {
                log_message('error', 'insert_gem_info:'.$this->CI->input->ip_address().',insert_gem_info_err,更新宝石数量失败');
                return false;
            }
            return true;
        }
        // 插入
        $data   = array(
                'manager_idx'   => $params['uuid'],
                'gem_no'        => $params['gem_no'],  
                'equipt_idx'    => 0,
                'gem_num'       => $params['num'],
                'is_use'        => 0,
                'status'        => 1,
        );
        $res    = $this->CI->court_model->insert_data($data,'gem');
        if (!$res) {
            log_message('error', 'insert_gem_info_err:'.$this->CI->input->ip_address().',insert_gem_info_err,插入宝石信息数据失败');
            return false;
        }
        return true;
    }
    /*
     * 重新定义数组键
     */
    public function reset_array_key($old_array , $key)
    {
        if($old_array)
        {
            foreach($old_array as $k => $v)
            {
                $return_array[$v[$key]] = $v;
            }
            return $return_array;
        }
        return FALSE;
    }
    /*
     * 转换奖励列表格式
     */
    public function get_reward($params_array)
    {
        foreach ($params_array as $k => $v)
        {
            switch ($k)
            {
                //经验
                case 'exp':
                    if($v) $return_arr['exp'] = $v;
                    break;
                //欧元
                case 'euro':
                    if($v) $return_arr['euro'] = $v;
                    break;
                //成就点
                case 'achievepoint':
                    if($v) $return_arr['achievepoint'] = $v;
                    break;
                    
                //球票
                case 'tickets':
                    if($v) $return_arr['tickets'] = $v;
                    break;
                    
                //球魂
                case 'soccer_soul':
                    if($v) $return_arr['soccer_soul'] = $v;
                    break;
                    
                //粉末
                case 'powder':
                    if($v) $return_arr['powder'] = $v;
                    break;
                    
                //道具
                case 'prop_info':
                    if($v) $return_arr['prop_info'] = $this->get_item_reward($v , 'prop_no' , 'prop');
                    break;
                    
                //宝石
                case 'gem_info':
                    if($v) $return_arr['gem_info'] = $this->get_item_reward($v , 'gem_no' , 'prop');
                    break;
                    
                //装备
                case 'equipt_info':
                    if($v) $return_arr['equipt_info'] = $this->get_item_reward($v , 'equipt_no' , 'equipt');
                    break;
                //球员卡
                case 'player_info':
                    if($v) $return_arr['player_info'] = $this->get_item_reward($v , 'player_no' , 'equipt');
                    break;    
                default:
                    break;
            }
        }
        return $return_arr;
    }
    
    /*
     * 转换物品奖励
     * type : 按照 宝石，道具 或者 装备，球员 来转变格式    装备，球员：equipt；道具，宝石：prop
     */
    public function get_item_reward($params_str , $key , $type = 'prop')
    {
        $params_arr = explode('|', $params_str);
        foreach ($params_arr as $k => $v)
        {
            $item_arr = explode(':', $v);
            $return_arr[$k][$key]    = $item_arr[0];
            if($type == 'prop'){
                $return_arr[$k]['num']   = $item_arr[1];
            }
            else{
                $return_arr[$k]['level'] = $item_arr[1];
                $return_arr[$k]['num']   = $item_arr[2];
            }
            
        }
        return $return_arr;
    }
    
    /*
     * 动作相关任务 状态查询
     */
    public function get_task_status($uuid , $action)
    {
        $this->CI->load->library('task_lib');
        //获取活动对应的所有任务
        $task_id_list = $this->CI->task_lib->task_action;
        $task_id_list = $task_id_list[$action];
        //获取调用公共刷新方法的任务
        $common_refresh_list = $this->CI->task_lib->common_refresh;
        foreach($task_id_list as $k => $v)
        {
            //调查任务完成状态
            $task_stsuts = $this->CI->task_lib->get_task_status($uuid , $v);
            //没有完成则刷新任务状态
            if(!$task_stsuts['complete']){
                //调用公共刷新方法
                if(in_array($v, $common_refresh_list)){
                    $f_name = 'refresh_common';
                }
                else{
                    $f_name = 'refresh_'.$action;
                }
                $this->CI->task_lib->$f_name($uuid , $v);
            }
        }
        return;
    }
    
    /**
     * 获取拼接签名参数
     */
    public function get_sign_params($params)
    {
        //除去数组中的空值和签名参数
        while (list($key, $val) = each($params)) {
            if ($key == "sign" || $key == "sign_type" ||  ($val === "")){
                continue;
            } else {
                $para[$key] = $params[$key];
            }
        }
        //对数组进行字母排序
        ksort($para);
        reset($para);
        while(list($key, $val) = each($para)) {
            $arg .= $key . "=" . $val . "&";
        }
        //去掉最后一个&字符
	$arg = substr($arg,0,count($arg)-2);
        return $arg;
    }
    
    //访问外部地址（HTTPS POST方式）
    public function wx_post($url, $post_data = '') {
        //log_scribe('trace', 'proxy_php', 'POST Request: ' . $url . ' post_data' . $post_data);
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_TIMEOUT, '15');
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, FALSE);
        // Set request method to POST
        curl_setopt($ch, CURLOPT_POST, 1);
        // Set query data here with CURLOPT_POSTFIELDS
        curl_setopt($ch, CURLOPT_POSTFIELDS, $post_data);
        $content = curl_exec($ch);
         //var_dump( curl_error($ch) );exit;//如果执行curl过程中出现异常，可打开此开关，以便查看异常内容
        curl_close($ch);
        return $content;
    }
    
    //get 方式
    public function curl_get($url, $fields = array()) {
        if (is_array($fields)) {
            $qry_str = http_build_query($fields);
        } else {
            $qry_str = $fields;
        }
        if (trim($qry_str) != '') {
            $url = $url . '?' . $qry_str;
        }
        //log_scribe('trace', 'proxy_php', 'GET Request: ' . $url);
        $ch = curl_init();
        // Set query data here with the URL
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_TIMEOUT, '100');
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        
        $content = trim(curl_exec($ch));
        curl_close($ch);
        return $content;
    }
}

