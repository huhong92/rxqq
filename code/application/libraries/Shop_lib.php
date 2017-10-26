<?php
class Shop_lib extends Base_lib {
    public function __construct() {
        parent::__construct();
        $this->load_model('shop_model');
    }
    
    /**
     * 获取数据总条数
     * @param type $where
     * @param type $table
     */
    public function get_total_count($where,$table)
    {
        $total_count    = $this->CI->shop_model->total_count('idx', $where, $table);
        return $total_count;
    }
    
    /**
     * 获取商店列表
     * @param type $options
     */
    public function get_store_list($options)
    {
        $list = $this->CI->shop_model->list_data($options,'shop_conf');
        if ($list) {
            foreach ($list as $k=>&$v) {
                $v['active_time']   = 0;
                if ($v['limit_time']) {
                    if (time() < $v['open_time'] || $v['close_time'] < time()) {
                        // unset($list[$k]);
                    }
                    $v['active_time']   = $v['close_time'] - time()>0?$v['close_time'] - time():0;
                }
            }
        }
        sort($list);
        return $list;
    }
    
    /**
     * 获取商品列表
     * @param type $options
     */
    public function get_goods_list($options,$uuid,$shop_type)
    {
        $goods_list = $this->CI->shop_model->list_data($options,'goods_conf');
        $m_info     = $this->CI->utility->get_manager_info(array('uuid'=>$uuid));
        
        // VIP信息表
        $where      = array('level'=>$m_info['vip'],'status'=>1);
        $fields     = "banknote_print,physical_strength,endurance";
        $vip_info   = $this->get_vip_info($where,$fields);
        // vip礼包配置表
        $table_2    = "vippackage_conf";
        $fields_2   = 'player player_info,equipt equipt_info,prop prop_info,gem gem_info';
        foreach ($goods_list as $k=>&$v) {
            if ($shop_type == 7) {// VIP礼包商店
                $where_2        = array('idx'=>$v['goods_idx'],'status'=>1);
                $packet = $this->CI->shop_model->get_one($where_2,$table_2,$fields_2);
                if ($packet['player_info']) {
                    $player  = explode("|", trim($packet['player_info'],"|"));
                    foreach ($player as $val) {
                        $player_ = explode(":", $val);
                        $arr = explode("_", $player_[1]);
                        $v['packet']['player_info'][]   = array('player_no'=>$player_[0],'num'=>$arr[0],'level'=>$arr[1]);
                    }
                    
                }
                if ($packet['equipt_info']) {
                    $equipt = explode("|", trim($packet['equipt_info'],"|"));
                    foreach ($equipt as $val) {
                        $equipt_ = explode(":", $val);
                        $arr = explode("_", $equipt_[1]);
                        $v['packet']['equipt_info'][]   = array('equipt_no'=>$equipt_[0],'num'=>$arr[0],'level'=>$arr[1]);
                    }
                }
                if ($packet['prop_info']) {
                    $prop = explode("|", trim($packet['prop_info'],"|"));
                    foreach ($prop as $val) {
                        $prop_ = explode(":", $val);
                        $v['packet']['prop_info'][]   = array('prop_no'=>$prop_[0],'num'=>$prop_[1]);
                    }
                }
                if ($packet['gem_info']) {
                    $gem = explode("|", trim($packet['gem_info'],"|"));
                    foreach ($gem as $val) {
                        $gem_ = explode(":", $val);
                        $v['packet']['gem_info'][]   = array('gem_no'=>$gem_[0],'num'=>$gem_[1]);
                    }
                }
                // $v['packet']    = array('player_info'=>$player_info,'gem_info'=>$gem_info,'equipt_info'=>$equipt_info,'prop_info'=>$prop_info);
            }
            if ($v['goods_idx'] == 803) {// 查看欧元印钞机vip限购次数,大瓶体力药水，大瓶耐力药水
                $v['limit_buy'] = $vip_info['banknote_print'];// 限购次数
            }elseif ($v['goods_idx'] == 502) {
                $v['limit_buy'] = $vip_info['physical_strength'];
            } elseif($v['goods_idx'] == 602) {
                $v['limit_buy'] = $vip_info['endurance'];
            }
            // 查看商品已购买次数
            $buy_times          = $this->get_total_count(array('goods_no'=>$v['goods_no'],"manager_idx"=>$uuid,'status'=>1),'goodsbuy_his');
            $v['buy_times']     = $buy_times;
        }
        return $goods_list;
    }
    
    /**
     * 获取商品详情
     * @param type $params
     */
    public function get_goods_info($params)
    {
        $where      = array('idx'=>$params['id'],'is_online'=>1,'status'=>1);
        $fields     = "idx as id,type,goods_no,name,pic,frame,currency_type,goods_type,goods_idx,sale_price,price,limit_buy,descript";
        $goods_info = $this->CI->shop_model->get_one($where, 'goods_conf', $fields);
        if (!$goods_info) {
            log_message('error', 'empty_data:'.$this->ip.',未查询到商品信息');
            $this->CI->output_json_return('empty_data');
        }
        return $goods_info;
    }
    
    /**
     * 购买商品操作
     * @param type $params
     */
    public function goods_buy($params)
    {
        // 判断商品是否失效
        $goods_info = $this->get_goods_info($params);
        // 判断是否有限购
        $m_info = $this->CI->utility->get_manager_info($params);
        if ($goods_info['limit_buy']) {// 有限购
            $where  = array('level'=>$m_info['vip'],'status'=>1);
            $fields = "physical_strength,banknote_print,endurance";
            $vip_info   = $this->get_vip_info($where,$fields);
                
            if ($goods_info['goods_idx'] == 803) {// 查看欧元印钞机vip限购次数
                $goods_info['limit_buy'] = $vip_info['banknote_print'];
            }
            if ($goods_info['goods_idx'] == 502) {// 大瓶体力药水 vip限购次数
                $goods_info['limit_buy'] = $vip_info['physical_strength'];
            }
            if ($goods_info['goods_idx'] == 602) {// 大瓶耐力药水  vip限购次数
                $goods_info['limit_buy'] = $vip_info['endurance'];
            }
            // 查看经理购买次数
            $today_time     = strtotime(date('Ymd'));
            $tommor_time    = $today_time+86400;
            $buy_times  = $this->get_total_count(array('manager_idx'=>$params['uuid'],'goods_no'=>$goods_info['goods_no'],'time<'=>$tommor_time,'time>='=>$today_time,'status'=>1),'goodsbuy_his');
            if ($buy_times+$params['number'] > $goods_info['limit_buy']) {
                log_message('error', 'goods_limit_buy_err:'.$this->ip.',商品超过限购次数');
                $this->CI->output_json_return('goods_limit_buy_err');
            }
        }
        
        // 判断经理货币是否足够
        $currency   = $goods_info['sale_price']*$params['number'];
        if ((int)$goods_info['currency_type'] === 1) {// 货币类型：1球票2欧元3球魂4荣誉5粉末
            if ($m_info['tickets'] < $currency) {
                log_message('error', 'currency_not_enought:'.$this->ip.',球票不足');
                $this->CI->output_json_return('currency_not_enought');
            }
            $fields  = array('tickets'=>$m_info['tickets'] - $currency);
         } elseif ((int)$goods_info['currency_type'] === 2) {
             if ($m_info['euro'] < $currency) {
                log_message('error', 'currency_not_enought:'.$this->ip.',欧元不足');
                $this->CI->output_json_return('currency_not_enought');
             }
             $fields['euro']  = $m_info['euro'] - $currency;
         }elseif ((int)$goods_info['currency_type'] === 3) {
             if ($m_info['soccer_soul'] < $currency) {
                log_message('error', 'currency_not_enought:'.$this->ip.',球魂不足');
                $this->CI->output_json_return('currency_not_enought');
             }
             $fields['soccer_soul']  = $m_info['soccer_soul'] - $currency;
         }elseif ((int)$goods_info['currency_type'] === 4) {
             if ($m_info['honor'] < $currency) {
                log_message('error', 'currency_not_enought:'.$this->ip.',荣誉不足');
                $this->CI->output_json_return('currency_not_enought');
             }
            $fields['honor']  = $m_info['honor'] - $currency;
         }elseif ((int)$goods_info['currency_type'] === 5) {
             if ($m_info['powder'] < $currency) {
                log_message('error', 'currency_not_enought:'.$this->ip.',粉末不足');
                $this->CI->output_json_return('currency_not_enought');
             }
             $fields['powder']  = $m_info['powder'] - $currency;
        }
        
        // 执行购买操作，插入经理对应表
        $this->CI->shop_model->start();
        $goods_idx  = $goods_info['goods_idx'];
        if (in_array((int)$goods_info['goods_type'], array(3,4,5,6,7,8,9))) {// 购买商品类型：1球员卡 2装备 3-9道具10宝石11vip礼包
            $where      = array('manager_idx'=>$params['uuid'],'prop_no'=>$goods_idx,'status'=>1);
            $prop_info  = $this->get_prop_info($where, "idx as id,prop_no,num");
            if ($prop_info) {//更新道具数量
                $upt_data   = array('num'=>$prop_info['num']+$params['number']);
                $upt_res = $this->update_prop_info($upt_data, $where);
                if (!$upt_res) {
                    $this->CI->shop_model->error();
                    log_message('error', 'prop_goods_buy_err:'.$this->ip.',购买商品时，道具更新失败');
                    $this->CI->output_json_return('prop_goods_buy_err');
                }
            } else {// 插入道具信息
                $where_1    = array('prop_no'=>$goods_idx,'status'=>1);
                $ist_res    = $this->CI->utility->insert_prop_info(array('uuid'=>$params['uuid'],'prop_no'=>$goods_idx),$params['number']);
                if (!$ist_res) {
                    $this->CI->shop_model->error();
                    log_message('error', 'prop_goods_buy_err:'.$this->ip.',购买商品时，道具插入失败');
                    $this->CI->output_json_return('prop_goods_buy_err');
                }
            }
        } elseif ((int)$goods_info['goods_type'] === 2) {//购买商品类型：1球员卡 2装备 3-9道具10宝石11vip礼包 装配配置表idx(特例)
            $where      = array('idx'=>$goods_idx,'status'=>1);
            $econf_info = $this->get_equipt_conf_info($where);
            $para       = array('equipt_no'=>$econf_info['equipt_no'],'uuid'=>$params['uuid'],'level'=>$econf_info['level']);
            for($i = 0;$i<$params['number'];$i++) {
                $ist_res    = $this->CI->utility->insert_equipt_info($para);
                if (!$ist_res) {
                    $this->CI->shop_model->error();
                    log_message('error', 'equipt_goods_buy_err:'.$this->ip.',购买商品时，装备插入失败');
                    $this->CI->output_json_return('equipt_goods_buy_err');
                }
            }
        } elseif ((int)$goods_info['goods_type'] === 1) {// 购买商品类型：1球员卡 2装备 3-9道具10宝石11vip礼包
            $pconf_info = $this->CI->utility->get_player_lib_info($goods_idx);
            $insert['uuid']      = $params['uuid'];
            $insert['player_no'] = $pconf_info['player_no'];
            $insert['level']     = 0;
            for($i = 0;$i<$params['number'];$i++) {
                $res = $this->CI->utility->insert_player_info($insert);
                if (!$ist_res) {
                    $this->CI->shop_model->error();
                    log_message('error', 'prop_goods_buy_err:'.$this->ip.',购买商品时，球员卡插入失败');
                    $this->CI->output_json_return('prop_goods_buy_err');
                }
            }
        } elseif ((int)$goods_info['goods_type'] === 10) {// 购买商品类型：1球员卡 2装备 3-9道具10宝石11vip礼包
            $where      = array('manager_idx'=>$params['uuid'],'gem_no'=>$goods_idx,'is_use'=>0,'status'=>1);
            $gem_info   = $this->get_gem_info($where, "idx as id,gem_no,gem_num,is_use");
            if ($gem_info) {//更新宝石数量
                $upt_data   = array('gem_num'=>$gem_info['gem_num']+$params['number']);
                $upt_res = $this->update_gem_info($upt_data, $where);
                if (!$upt_res) {
                    $this->CI->shop_model->error();
                    log_message('error', 'prop_goods_buy_err:'.$this->ip.',购买商品时，宝石更新失败');
                    $this->CI->output_json_return('prop_goods_buy_err');
                }
            } else {// 插入宝石信息
                $ist_res = $this->CI->utility->insert_gem_info(array('uuid'=>$params['uuid'],'num'=>$params['number'],'gem_no'=>$goods_idx));
                if (!$ist_res) {
                    $this->CI->shop_model->error();
                    log_message('error', 'prop_goods_buy_err:'.$this->ip.',购买商品时，宝石插入失败');
                    $this->CI->output_json_return('prop_goods_buy_err');
                }
            }
        } elseif ((int)$goods_info['goods_type'] ===11) {// 购买商品类型：1球员卡 2装备 3-9道具10宝石11vip礼包
            $vconf_info  = $this->CI->shop_model->get_one(array('idx'=>$goods_idx,'status'=>1), 'vippackage_conf', "level,price");
            if ($vconf_info['level'] > $m_info['vip']) {
                $this->CI->shop_model->error();
                log_message('error', 'vip_level_lack:'.$this->ip.',购买VIP礼包商品时，经理vip等级不足');
                $this->CI->output_json_return('vip_level_lack');
            }
            // 判断经理礼包是否购买过
            $buy_his  = $this->CI->shop_model->get_one(array('manager_idx'=>$params['uuid'],'level'=>$vconf_info['level'],'status'=>1), 'vippackage_his', "idx");
            if ($buy_his) {
                $this->CI->shop_model->error();
                log_message('error', 'buy_only_one_err:'.$this->ip.',VIP礼包只能购买一次');
                $this->CI->output_json_return('buy_only_one_err');
            }
            $data   = array(
                'manager_idx'   => $params['uuid'],
                'level'         => $vconf_info['level'],
                'price'         => $vconf_info['price'],
                'status'        => 1,
            );
            $ist_res = $this->CI->shop_model->insert_data($data,'vippackage_his');
            if (!$ist_res) {
                $this->CI->shop_model->error();
                log_message('error', 'vip_goods_buy_err:'.$this->ip.',购买商品时，vip礼包插入失败');
                $this->CI->output_json_return('vip_goods_buy_err');
            }
        }
        
        // 更新经理货币值
        $where  = array("idx"=> $params['uuid'],"status"=>1);
        $this->load_library('manager_lib');
        $res    = $this->CI->manager_lib->update_manager_info($fields, $where);
        if (!$res) {
            $this->CI->shop_model->error();
            log_message('error', 'm_info_update_err:'.$this->ip.',购买商品时,经理信息更新失败');
            $this->CI->output_json_return('m_info_update_err');
        }
        
        // 购买历史表
        $data   = array(
            'manager_idx'   => $params['uuid'],
            'manager_name'  => $m_info['name'],
            'type'          => $goods_info['type'],
            'goods_type'    => $goods_info['goods_type'],
            'goods_no'      => $goods_info['goods_no'],
            'goods_name'    => $goods_info['name'],
            'currency_type' => $goods_info['currency_type'],
            'price'         => $goods_info['sale_price'],
            'buy_status'    => 1,
            'status'        => 1,
        );
        $res    = $this->CI->shop_model->insert_data($data,'goodsbuy_his');
        if (!$res) {
            $this->CI->shop_model->error();
            log_message('error', 'prop_goods_buy_err:'.$this->ip.',购买商品时，商品购买历史记录插入失败');
            $this->CI->output_json_return('prop_goods_buy_err');
        }
        //触发任务 购买商品
        $this->CI->utility->get_task_status($params['uuid'] , 'goods_buy');
        $this->CI->shop_model->success();
        return true;
    }
    
    /**
     * 获取道具conf信息
     * @param type $where
     * @param type $fields
     * @return type
     */
    public function get_prop_conf_info($where,$fields="*")
    {
        $pconf_info  = $this->CI->shop_model->get_one($where, 'prop_conf', $fields);
        return $pconf_info;
    }
    
    /**
     * 获取道具信息
     */
    public function get_prop_info($where,$fields)
    {
        $prop_info  = $this->CI->shop_model->get_one($where, 'prop', $fields);
        return $prop_info;
    }
    
    /**
     * 更新道具信息
     * @param type $data
     * @param type $where
     */
    public function update_prop_info($data,$where)
    {
        $res = $this->CI->shop_model->update_data($data,$where,'prop');
        return $res;
    }
    
    /**
     * 插入道具信息
     * @param type $data
     * @return type
     */
    public function insert_prop_info($data)
    {
        $res = $this->CI->shop_model->insert_data($data,'prop');
        return $res;
    }
    
    /**
     * 插入装备信息
     */
    public function insert_equipt_info($data)
    {
        $res = $this->CI->shop_model->insert_data($data,'equipt');
        return $res;
    }
    
    /**
     * 获取装备配置信息
     */
    public function get_equipt_conf_info($where,$fields="*")
    {
        $econf_info  = $this->CI->shop_model->get_one($where, 'equipt_conf', $fields);
        return $econf_info;
    }
    
    /**
     * 获取宝石信息
     */
    public function get_gem_info($where,$fields="*")
    {
        $gem_info  = $this->CI->shop_model->get_one($where, 'gem', $fields);
        return $gem_info;
    }
    
    /**
     * 获取宝石conf信息
     */
    public function get_gem_conf_info($where,$fields="*")
    {
        $gconf_info  = $this->CI->shop_model->get_one($where, 'gem_conf', $fields);
        return $gconf_info;
    }
    /**
     * 更新宝石信息
     * @param type $data
     * @param type $where
     * @return type
     */
    public function update_gem_info($data,$where)
    {
        $res = $this->CI->shop_model->update_data($data,$where,'gem');
        return $res;
    }
    
    /**
     * 插入宝石信息
     */
    public function insert_gem_info($data)
    {
        $res = $this->CI->shop_model->insert_data($data,'gem');
        return $res;
    }
    
    /**
     * 获取vip配置信息
     */
    public function get_vip_info($where,$fields)
    {
        $vip_info  = $this->CI->shop_model->get_one($where, 'vip_conf', $fields);
        return $vip_info;
    }
    
    
    /**
     * 获取商城礼包列表
     */
    public function get_package_list($options)
    {
        $list = $this->CI->shop_model->list_data($options,'package_conf');
        foreach ($list as $k=>&$v) {
            $prop_info = explode("|", $v['prop']);
            foreach ($prop_info as $key=>$val) {
                $arr = explode(":", $val);
                $prop['prop_no']    = $arr[0];
                $prop['num']        = $arr[1];
                $v['prop_info'][]   = $prop;
            }
            unset($v['prop']);
        }
        return $list;
    }
    
    
}
