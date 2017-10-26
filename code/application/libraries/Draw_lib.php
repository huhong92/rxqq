<?php
class Draw_lib extends Base_lib {
    public function __construct() {
        parent::__construct();
        $this->load_model('draw_model');
    }
    
    /**
     * 获取数据总条数
     * @param type $where
     * @param type $table
     * @return type
     */
    public function get_total_count($where,$table)
    {
        $options['where']   = $where;
        $list               = $this->CI->draw_model->list_data($options,$table);
        $total_count        = 0;
        if (!$list) {
            return $total_count;
        }
        foreach ($list as $k=>$v) {
            if ($v['draw_times'] == 1) {
                $total_count +=1;
            } else {
                $total_count +=10;
            }
        }
        return $total_count;
    }
    
    /**
     * 获取抽卡类型列表
     * @param type $params
     */
    public function get_draw_list($params)
    {
        $options    = array(
            'where' => array('status'=>1,),
            'fields'=> "idx as id,type,one_expend as expend,is_firstget,is_firstfree,is_limitfree,limit_time,is_continuous,ten_expend",
        );
        $draw_list  = $this->CI->draw_model->list_data($options,'draw_conf');
        if (!$draw_list) {
            log_message('info', 'empty_data:'.$this->ip.',未查询到抽卡类型列表数据');
            $this->CI->output_json_return('empty_data');
        }
        // 获取当前魔法钥匙个数
        $where  = array('manager_idx'=>$params['uuid'],'prop_no'=>901,'status'=>1);
        $fields = "num";
        $p_info = $this->CI->draw_model->get_one($where,'prop',$fields);
        
        // 判断当前状态是否免费抽取
        foreach ($draw_list as $k=>&$v) {
            $v['key_num']       = (int)$p_info['num'];
            $v['surplus_time']  = 0;
            $v['is_first']      = 0;
            $where  = array('manager_idx'=>$params['uuid'],'type'=>$v['type'],'status'=>1);
            $count  = $this->get_total_count($where,'draw_his');
            if (!$count) {
                $v['is_first']  = 1;
            }    
            if ($v['is_limitfree'] == 1) {// 是否间隔一定时间后，免费
                // 查看上次抽卡时间
                $sql    = "SELECT time FROM draw_his WHERE manager_idx=".$params['uuid']." AND type=".$v['type']." AND status = 1 order by time desc";
                $info   = $this->CI->draw_model->fetch($sql,'row');
                if (!$info) {// 从没抽过 获取 达到免抽时间
                    $v['curr_status']   = 0;
                } elseif (time() - $info['time'] >= $v['limit_time']*3600) {
                    $v['curr_status']   = 1;
                } else {
                    $v['surplus_time']  = $v['limit_time']*3600 - (time() - $info['time']);
                }
            }
            
            if ($v['is_firstfree'] == 1 && !$count) {// 是否首次免费
                $v['curr_status']   = 1;
            }
            // 判断是否支持10连抽，距离下10还剩几次
            $v['draw_num']  = 10;
            if ($v['is_continuous']) {
                $sql    = "SELECT count('idx') AS num FROM draw_his WHERE manager_idx=".$params['uuid']." AND type=".$v['type']." AND draw_times = 1 AND status = 1";
                $tcount = $this->CI->draw_model->fetch($sql,'row');
                if ((int)$tcount['num']%10) {
                    $v['draw_num']  = (int)$tcount['num']%10;
                }
            }
        }
        return $draw_list;
    }
    
    /**
     * 抽卡操作
     * @param type $params
     */
    public function draw($params)
    {
        // 获取抽卡数据
        $where  = array('idx'=>$params['id'],'status'=>1);
        $fields = "idx as id,type,one_expend as expend,is_firstget,is_firstfree,is_limitfree,limit_time,is_continuous,ten_expend,normal_id,first_id,tenth_id,prop_no";
        $info   = $this->CI->draw_model->get_one($where,'draw_conf',$fields);
        //判断如果是10连抽，是否可以
        if ($params['type'] === 10 && !$info['is_continuous']) {
            log_message('error', ':tenth_draw_not_allow'.$this->ip.',该抽卡类型不允许10连抽');
            $this->CI->output_json_return('tenth_draw_not_allow');
        }
        
        $this->CI->draw_model->start();
        // 判断是免费，还是付费
        $curr_status    = 0;// 1免费 0付费
        if ($params['type'] === 1) {// 单次抽卡
            $expend         = $info['expend'];
            $sql        = "SELECT time FROM draw_his WHERE manager_idx=".$params['uuid']." AND type=".$info['type']." AND status = 1 order by time desc";
            $draw_info  = $this->CI->draw_model->fetch($sql,'row');
            if ($info['is_limitfree'] == 1) {// 是否间隔一定时间后，免费
                if ($draw_info && time() >= $info['limit_time']*3600 +$draw_info['time']) {
                    $curr_status    = 1;
                    $expend = 0;
                }
            }
            if ($info['is_firstfree'] == 1 && !$draw_info) {// 是否首次免费
                $curr_status    = 1;
                $expend         = 0;
            }
        } else {// 10连抽
            $curr_status   = 0;
            $expend = $info['ten_expend'];
        }
        
        // 判断付费还是免费
        if (!$curr_status) {// 抽卡付费状态 0付费1免费
            if ($info['type'] == 1) {// 1欧元抽卡2道具3球票
                $m_info = $this->CI->utility->get_manager_info($params);
                if ($m_info['euro'] < $expend) {// 欧元不够
                    $this->CI->draw_model->error();
                    log_message('error',':not_enough_euro_err'.$this->ip.',抽卡欧元不足');
                    $this->CI->output_json_return('not_enough_euro_err');
                }
                // 更新经理信息
                $fields_3   = array('euro'=>$m_info['euro']-$expend);
                $where_3    = array('idx'=>$params['uuid'],'status'=>1);
                $res        = $this->CI->utility->update_m_info($fields_3,$where_3);
                if (!$res) {
                    $this->CI->draw_model->error();
                    log_message('error',':m_info_update_err'.$this->ip.',消耗欧元更新失败');
                    $this->CI->output_json_return('m_info_update_err');
                }
            } elseif ($info['type'] == 3) {
                $m_info = $this->CI->utility->get_manager_info($params);
                if ($m_info['tickets'] < $expend) {// 球票不够
                    $this->CI->draw_model->error();
                    log_message('error',':not_enough_tickets_err'.$this->ip.',抽卡球票不足');
                    $this->CI->output_json_return('not_enough_tickets_err');
                }
                // 更新经理信息
                $fields_3   = array('tickets'=>$m_info['tickets']-$expend);
                $where_3    = array('idx'=>$params['uuid'],'status'=>1);
                $res        = $this->CI->utility->update_m_info($fields_3,$where_3);
                if (!$res) {
                    $this->CI->draw_model->error();
                    log_message('error',':m_info_update_err'.$this->ip.',消耗球票更新失败');
                    $this->CI->output_json_return('m_info_update_err');
                }
            } else{
                $where      = array('manager_idx'=>$params['uuid'],'prop_no'=>$info['prop_no'],'status'=>1);
                $fields     = "idx as id,num";
                $prop_info  = $this->CI->draw_model->get_one($where,'prop',$fields);
                if ($prop_info['num'] < $expend) {
                    $this->CI->draw_model->error();
                    log_message('error',':not_enough_prop_err'.$this->ip.',抽卡道具不足');
                    $this->CI->output_json_return('not_enough_prop_err');
                }
                if ($prop_info['num']-$expend > 0) {
                    $fields = array('num'=>$prop_info['num']-$expend);
                } else {
                    $fields = array('status'=>0);
                }
                $where  = array('manager_idx'=>$params['uuid'],'prop_no'=>$info['prop_no'],'status'=>1);
                $res = $this->CI->draw_model->update_data($fields,$where,'prop');
                if (!$res) {
                    $this->CI->draw_model->error();
                    log_message('error',':expend_prop_err'.$this->ip.',抽卡道具消耗更新失败');
                    $this->CI->output_json_return('expend_prop_err');
                }
            }
        }
        
        // 执行抽卡
        if ($params['type'] == 1) {
            // 执行抽卡操作
            if ($params['id'] == 1) {// 欧元抽卡
                $drawlib_id = 1;
            } elseif ($params['id'] == 2) {
                $drawlib_id = 2;
            } else{
                // 获取经理该类型抽卡次数
                $count  = $this->get_total_count(array('manager_idx'=>$params['uuid'],'type'=>$params['id'],'status'=>1), 'draw_his');
                if (!$count && $info['first_id']) {
                    $drawlib_id = 4;
                } elseif($count == 9 && $info['tenth_id']) {
                    $drawlib_id = 5;
                } else {
                    $drawlib_id = 3;
                }
            }
            $result = $this->do_draw($drawlib_id,$params['uuid']);
            if (!$result) {
                $this->CI->draw_model->error();
                log_message('error',':draw_err'.$this->ip.',抽卡失败不足');
                $this->CI->output_json_return('draw_err');
            }
            $draw_result['goods_no']   .= $result['type'].":".$result['object_no']."|";
            $draw_result['type']        = $result['type'];
            $result_new[]               = $result;
        } else {
            $count  = $this->get_total_count(array('manager_idx'=>$params['uuid'],'type'=>$params['id'],'status'=>1), 'draw_his');
            $val_1  = 1;
            $val_2  = 10;
            for($i=0;$i<10;$i++) {
                $num_   = $i+$val_1+$count;
                if (!$count && $info['first_id']) {
                    $drawlib_id = 4;
                } elseif((!($num_%$val_2)) && $info['tenth_id']) {
                    $drawlib_id = 5;
                } else {
                    $drawlib_id = 3;
                }
                $result= $this->do_draw($drawlib_id,$params['uuid']);
                if (!$result) {
                    $this->CI->draw_model->error();
                    log_message('error',':draw_err'.$this->ip.',抽卡失败不足');
                    $this->CI->output_json_return('draw_err');
                }
                $draw_result['goods_no']  .= $result['type'].":".$result['object_no']."|";
                $draw_result['type']        = 4;
                $result_new[]               = $result;
            }
        }
        
        // 记录抽卡历史记录
        $data   = array(
            'manager_idx'   => $params['uuid'],
            'type'          => $params['id'],
            'draw_times'    => $params['type'],
            'is_free'       => $curr_status,
            'goods_type'    => $draw_result['type'],
            'goods_no'      => $draw_result['goods_no'],
            'expend_type'   => $params['id'],
            'expend'        => $expend,
            'status'        => 1
        );
        if($params['type'] == 2)
            $data['draw_times'] = 10;
        $ist_res = $this->CI->draw_model->insert_data($data,'draw_his');
        if (!$ist_res) {
            $this->CI->draw_model->error();
            log_message('error',':draw_err'.$this->ip.',抽卡记录插入失败');
            $this->CI->output_json_return('draw_err');
        }
        //触发任务 更换阵型
        $this->CI->utility->get_task_status($params['uuid'] , 'draw');
        $this->CI->draw_model->success();
        return $result_new;
    }
    

    /**
     * 抽卡操作
     * @param type $drawlib_id   抽卡卡库id
     * @param type $count       已抽卡的次数
     */
    public function do_draw($drawlib_id,$uuid)
    {
        if ($drawlib_id == 1) {// 
            $table  = 'drawproble_euro';// 欧元常规卡库
        } elseif ($drawlib_id == 2) {
            $table  = 'drawproble_prop';// 道具常规卡库
        } elseif ($drawlib_id == 3) {
            $table  = 'drawproble_tickets';// 球票常规卡库
        } elseif ($drawlib_id == 4) {
            $table  = 'drawproble_tkfirst';// 球票首抽卡库
        } elseif ($drawlib_id == 5) {
            $table  = 'drawproble_tktenth';// 球票第10次卡库
        }
        
        $options['where']   = array('status'=>1);
        $options['fields']  = "idx as id,type,object_no,probable";
        $result = $this->CI->utility->probable($options,$table);
        if ($result['type'] == 1) {// 球员卡
            $res = $this->CI->utility->insert_player_info(array('uuid'=>$uuid,'player_no'=>$result['object_no']));
            if (!$res) {
                return false;
            }
            $result['object_id']    = $res;
        } elseif($result['type'] == 2) {// 装备
            $res = $this->CI->utility->insert_equipt_info(array('uuid'=>$uuid,'equipt_no'=>$result['object_no']));
            if (!$res) {
                return false;
            }
            $result['object_id']    = $res;
        } else {// 道具
            $res = $this->CI->utility->insert_prop_info(array('uuid'=>$uuid,'prop_no'=>$result['object_no']));
            if (!$res) {
                return false;
            }
            $result['object_id'] = $res;
        }
        return $result;
    }
    
    /**
     * 抽卡--可获得卡片预览(只预览 6:金 5:橙 4:红 )
     * @param type $params
     */
    public function get_draw_preview($params)
    {
        if ($params['type'] == 1) {
            $table  = 'drawproble_euro';
        } else if($params['type'] == 2) {
            $table  = 'drawproble_prop';
        } else if($params['type'] == 3) {
            $table  = 'drawproble_tickets';
        }
        
        $where          = array('object_no>='=>40000,'type'=>1,'status'=>1);
        $total_count    = $this->CI->draw_model->total_count($where,$table);
        if (!$total_count) {
            log_message('info', 'empty_data:'.$this->court_lib->ip.',未查询到抽卡可获得数据');
            $this->CI->output_json_return('empty_data');
        }
        $data['pagecount']  = ceil($total_count/$params['pagesize']);
        $options['where']   = $where;
        $options['limit']   = array('size'=>$params['pagesize'],'page'=>$params['offset']);
        $options['fields']  = "idx id,type,object_no";
        $data['list']       = $this->CI->draw_model->list_data($options,$table);
        return $data;
    }
}
