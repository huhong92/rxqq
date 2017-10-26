<?php
class Mail_lib extends Base_lib {
    public function __construct() {
        parent::__construct();
        $this->load_model('mail_model');
    }
    
    /**
     * 获取邮件列表
     */
    public function get_mail_list($params)
    {
        if ($params['type'] == 1) {// 系统邮件
            $data   = $this->system_mail_list($params);
        } else {
            $data   = $this->match_mail_list($params);
        }
        return $data;
    }
    
    /**
     * 系统邮件列表
     */
    public function system_mail_list($params)
    {
        // 查看经理注册时间
        $m_info         = $this->CI->utility->get_manager_info($params);
        $register_time  = $m_info['create_time'];
        $condition      = "A.type = 1 AND A.time >= ".$register_time." AND (A.manager_idx = 0 OR A.manager_idx = ".$params['uuid'].") AND A.status = 1 AND IF(B.is_del,0,1) = 1 LIMIT ".$params['offset'].",".$params['pagesize'];
        $join_condition = "A.idx=B.mailconf_idx AND B.status = 1 AND B.manager_idx = ".$params['uuid'];
        $select         = "A.idx AS id,A.title AS title,A.content AS content,A.link AS link,A.is_accessory AS is_accessory,A.accessory_type AS accessory_type,A.accessory_content AS accessory_content,IF(B.idx,1,0) AS is_read,IF(B.receive,1,0) AS receive,A.time AS send_time";
        $tb_a           = "mail_conf AS A";
        $tb_b           = "mail_info AS B";
        $list   = $this->CI->mail_model->left_join($condition, $join_condition, $select, $tb_a, $tb_b,TRUE,1);
        if (!$list) {
            log_message('error','system_mail_list:empty_data'.$this->ip.',未查询到邮件列表');
            $this->CI->output_json_return('empty_data');
        }
        foreach ($list as &$v) {
            if (!$v['is_accessory']) {
                continue;
            }
            // 奖励处理
            $v['reward']    = json_decode($v['accessory_content'],true);
            unset($v['accessory_content']);
        }
        return $list;
    }
    
    /**
     * 赛事邮件列表
     */
    public function match_mail_list($params)
    {
        $m_info         = $this->CI->utility->get_manager_info($params);
        $register_time  = $m_info['create_time'];
        $condition      = "A.type = 2 AND A.time >= ".$register_time." AND (A.manager_idx = 0 OR A.manager_idx = ".$params['uuid'].") AND A.status = 1 AND IF(B.is_del,0,1) = 1 LIMIT ".$params['offset'].",".$params['pagesize'];
        $join_condition = "A.idx=B.mailconf_idx AND B.status = 1 AND B.manager_idx = ".$params['uuid'];
        $select         = "A.idx AS id,A.title AS title,A.content AS content,A.link AS link,A.is_accessory AS is_accessory,A.accessory_type AS accessory_type,A.accessory_content AS accessory_content,IF(B.idx,1,0) AS is_read,IF(B.receive,1,0) AS receive,A.time AS send_time";
        $tb_a           = "mail_conf AS A";
        $tb_b           = "mail_info AS B";
        $list           = $this->CI->mail_model->left_join($condition, $join_condition, $select, $tb_a, $tb_b,TRUE,1);
        if (!$list) {
            log_message('error','system_mail_list:empty_data'.$this->ip.',未查询到邮件列表');
            $this->CI->output_json_return('empty_data');
        }
        $save_time      = $this->CI->passport->get('video_save_time');
        foreach ($list as &$v) {
            $time = time() - ($v['send_time']+$save_time);
            $v['video_time']    = $time>0?$time:0;
        }
        return $list;
    }
    
    /**
     * 插入邮件信息
     */
    public function insert_mail($mail_data)
    {
        $data   = array(
            'sender_id'         => 1,
            'sender_name'       => 'admin',
            'type'              => 2,
            'manager_idx'       => $mail_data['manager_idx'],
            'title'             => $mail_data['title'],
            'content'           => $mail_data['content'],
            'link'              => $mail_data['link'],
            'is_accessory'      => 1,
            'accessory_type'    =>2,
            'accessory_content' => $mail_data['data'],
            'status'            => 1,
        );
        $res = $this->CI->mail_model->insert_data($data,'mail_conf');
        if (!$res) {
            log_message('error','insert_mail:insert_mail_fail'.$this->ip.',赛事邮件插入失败');
            return false;
        }
        //触发邮件红点提示
        $this->load_library('tips_lib');
        $this->CI->tips_lib->tip_pages($mail_data['manager_idx'],1011);//邮件
        $this->CI->tips_lib->tip_pages($mail_data['manager_idx'],1026);//赛事邮件
        return true;
    }
    
    /**
     * 获取赛事录像信息
     * uuid,redis_key(redis存放key),id(邮件id)
     */
    public function get_video_content($params)
    {
        $where  = array('idx'=>$params['id'],'manager_idx'=>$params['uuid'],'type'=>2,'status'=>1);
        $fields = "link,time,accessory_content";
        $info   = $this->CI->mail_model->get_one($where,'mail_conf',$fields);
        if (!$info) {
            log_message('error','get_video_content:empty_data'.$this->ip.',暂无该邮件信息');
            $this->CI->output_json_return('empty_data');
        }
        $save_time  = $this->CI->passport->get('video_save_time');
        if (time() > $info['time'] + $save_time) {
            log_message('error','get_video_content:video_expire_err'.$this->ip.',视频回放已到期');
            $this->CI->output_json_return('video_expire_err');
        }
        
        // 赛事-存放磁盘位置
        $data       = json_decode($info['accessory_content'],true);
        $descript   = $this->CI->utility->get_result_match($info['link']);
        $data['descript']   = $descript;
        return $data;
    }
    
    /**
     * 读取邮件
     */
    public function do_read_mail($params)
    {
        if ($params['type'] == 1) {// 系统邮件
            $this->do_read_system_mail($params);
        } else {
            $this->do_read_match_mail($params);
        }
        return true;
    }
    
    /**
     * 读取系统邮件
     */
    public function do_read_system_mail($params)
    {
        $where      = array('mailconf_idx'=>$params['id'],'manager_idx'=>$params['uuid'],'type'=>1,'status'=>1);
        $fields     = 'idx,is_read,is_del';
        $mail_info  = $this->CI->mail_model->get_one($where,'mail_info',$fields);
        if ($mail_info) {
            return true;
        }
        $where_2    = array('idx'=>$params['id'],'status'=>1);
        $fields_2   = 'title,content,link,is_accessory,accessory_type,accessory_content,sender_id,sender_name,time';
        $conf_info  = $this->CI->mail_model->get_one($where_2,'mail_conf',$fields_2);
        if (!$conf_info) {
            log_message('error','get_video_content:empty_data'.$this->ip.',暂无该邮件信息');
            $this->CI->output_json_return('empty_data');
        }
        $data   = array(
            'manager_idx'       => $params['uuid'],
            'type'              => 1,
            'mailconf_idx'      => $params['id'],
            'title'             => $conf_info['title'],
            'content'           => $conf_info['content'],
            'link'              => $conf_info['link'],
            'is_accessory'      => $conf_info['is_accessory'],
            'accessory_type'    => 1,
            'accessory_content' => $conf_info['accessory_content'],
            'receive'           => 0,
            'is_read'           => 1,
            'is_del'            => 0,
            'status'            => 1,
        );
        $res = $this->CI->mail_model->insert_data($data,'mail_info');
        if (!$res) {
            log_message('error','do_read_system_mail:mail_do_read_err'.$this->ip.',邮件读取失败');
            $this->CI->output_json_return('mail_do_read_err');
        }
        return true;
    }
    
    /**
     * 读取赛事系统邮件
     */
    public function do_read_match_mail($params)
    {
        $where      = array('mailconf_idx'=>$params['id'],'manager_idx'=>$params['uuid'],'type'=>2,'status'=>1);
        $fields     = 'idx,is_read,is_del';
        $mail_info  = $this->CI->mail_model->get_one($where,'mail_info',$fields);
        if ($mail_info) {
            return true;
        }
        $where_2    = array('idx'=>$params['id'],'status'=>1);
        $fields_2   = 'title,content,link,is_accessory,accessory_type,accessory_content,sender_id,sender_name,time';
        $conf_info  = $this->CI->mail_model->get_one($where_2,'mail_conf',$fields_2);
        if (!$conf_info) {
            log_message('error','get_video_content:empty_data'.$this->ip.',暂无该邮件信息');
            $this->CI->output_json_return('empty_data');
        }
        $data   = array(
            'manager_idx'       => $params['uuid'],
            'type'              => 2,
            'mailconf_idx'      => $params['id'],
            'title'             => $conf_info['title'],
            'content'           => $conf_info['content'],
            'link'              => $conf_info['link'],
            'is_accessory'      => $conf_info['is_accessory'],
            'accessory_type'    => 2,
            'accessory_content' => $conf_info['accessory_content'],
            'receive'           => 0,
            'is_read'           => 1,
            'is_del'            => 0,
            'status'            => 1,
        );
        $res = $this->CI->mail_model->insert_data($data,'mail_info');
        if (!$res) {
            log_message('error','do_read_system_mail:mail_do_read_err'.$this->ip.',邮件读取失败');
            $this->CI->output_json_return('mail_do_read_err');
        }
        return true;
    }
    
    /**
     * 删除邮件
     */
    public function do_del_mail($params)
    {
        $where_in       = explode("|", trim($params['ids'],"|"));
        $where          = array('manager_idx'=>$params['uuid'],'status'=>1);
        $where_fields   = 'mailconf_idx';
        $fields         = array('is_del'=>1);
        $upt_res        = $this->CI->mail_model->update_where_in($where_fields, $where_in, $fields, $where, 'mail_info');
        if (!$upt_res) {
            log_message('error','del_mail:mail_do_delete_err'.$this->ip.',邮件删除失败');
            $this->CI->output_json_return('mail_do_delete_err');
        }
        return true;
    }
    
    /**
     * 一键删除邮件（删除所有已读邮件）
     * @param type $params
     */
    public function do_delall_mail($params)
    {
        if ($params['type'] == 1) {// 系统邮件
            $where  = array('manager_idx'=>$params['uuid'],'type'=>1,'is_del'=>0,'status'=>1);
        } else {// 赛事回放
            $where  = array('manager_idx'=>$params['uuid'],'type'=>2,'is_read'=>1,'is_del'=>0,'status'=>1);
        }
        $fields = array('is_del'=>1);
        $upt_res    = $this->CI->mail_model->update_data($fields,$where,'mail_info');
        if (!$upt_res) {
            log_message('error','del_mail:mail_do_delete_err'.$this->ip.',邮件删除失败');
            $this->CI->output_json_return('mail_do_delete_err');
        }
        return true;
    }
    
    /**
     * 领取邮件奖励接口
     */
    public function do_reward_mail($params)
    {
        $where      = array('mailconf_idx'=>$params['id'],'manager_idx'=>$params['uuid'],'type'=>1,'status'=>1);
        $fields     = 'idx,is_read,is_del,accessory_content,receive';
        $mail_info  = $this->CI->mail_model->get_one($where,'mail_info',$fields);
        if (!$mail_info) {
            log_message('error','do_reward_mail:cannot_receive_donot_read_err'.$this->ip.',邮件未阅读，不能领取奖励');
            $this->CI->output_json_return('cannot_receive_donot_read_err');
        }
        if ($mail_info['receive']) {
            log_message('error','do_reward_mail:mail_receive_reward_err'.$this->ip.',奖励已经领取过，不能领取奖励');
            $this->CI->output_json_return('mail_receive_reward_err');
        }
        
        $this->CI->mail_model->start();
        $fields_1   = array('receive'=>1);
        $upt_res    = $this->CI->mail_model->update_data($fields_1,$where,'mail_info');
        if (!$upt_res) {
            log_message('error','do_reward_mail:mail_receive_reward_fail_err'.$this->ip.',奖励领取失败');
            $this->CI->output_json_return('mail_receive_reward_fail_err');
        }
        $reward = json_decode($mail_info['accessory_content'],true);
        if ($reward['prop_info']) {
            $prop = $reward['prop_info'];
            foreach ($prop as $v) {
                $ist_prop = $this->CI->utility->insert_prop_info(array('uuid'=>$params['uuid'],'prop_no'=>$v['prop_no']),$v['num']);
                if (!$ist_prop) {
                    $this->CI->mail_model->error();
                    log_message('error', 'do_reward_achieve:insert_prop_err，道具奖励时，插入失败');
                    $this->CI->output_json_return('insert_prop_err');
                }
            }
        }
        if ($reward['gem_info']) {
            $gem = $reward['gem_info'];
            foreach ($gem as $v) {
                $ist_gem    = $this->CI->utility->insert_gem_info(array('uuid'=>$params['uuid'],'gem_no'=>$v['gem_no'],'num'=>$v['num']));
                if (!$ist_gem) {
                    $this->CI->mail_model->error();
                    log_message('error', 'do_reward_achieve:insert_gem_err，宝石奖励时，插入失败');
                    $this->CI->output_json_return('insert_gem_err');
                }
            }
        }
        if($reward['player_info']) {
            $player = $reward['player_info'];
            for($i=$player['num'];$i>0;$i--) {
                $ist_player = $this->CI->utility->insert_player_info(array('uuid'=>$params['uuid'],'player_no'=>$player['player_no'],'level'=>$player['level']));
                if (!$ist_player) {
                    $this->CI->mail_model->error();
                    log_message('error', 'do_reward_achieve:insert_player_err，球员卡奖励时，插入失败');
                    $this->CI->output_json_return('insert_player_err');
                }
            }
        }
        if($reward['equipt_info']) {
            $equipt = $reward['equipt_info'];
            for($i=$equipt['num'];$i>0;$i--) {
                $ist_equipt = $this->CI->utility->insert_equipt_info(array('uuid'=>$params['uuid'],'equipt_no'=>$equipt['equipt_no'],'level'=>$equipt['level']));
                if (!$ist_equipt) {
                    $this->CI->mail_model->error();
                    log_message('error', 'do_reward_achieve:insert_equipt_err，装备奖励时，插入失败');
                    $this->CI->output_json_return('insert_equipt_err');
                }
            }
        }
        $m_info =   $this->CI->utility->get_manager_info($params);
        if ($reward['euro']) {
            $fields_2['euro']   = $reward['euro'] + $m_info['euro'];
        }
        if ($reward['tickets']) {
            $fields_2['tickets']    = $reward['tickets'] + $m_info['tickets'];
        }
        if ($reward['soccer_soul']) {
            $fields_2['soccer_soul']    = $reward['soccer_soul'] + $m_info['soccer_soul'];
        }
        if ($reward['powder']) {
            $fields_2['powder'] = $reward['powder']  + $m_info['powder'];
        }
        if ($reward['honor']) {
            $fields_2['honor']  = $reward['honor'] + $m_info['honor'];
        }
        if ($reward['achievement']) {
            $fields_2['achievement']    = $reward['achievement'] + $m_info['achievement'];
        }
        if ($reward['talent']) {
            $fields_2['talent'] = $reward['talent']+ $m_info['talent'];
        }
        if ($reward['exp']) {
            $this->CI->load->library('manager_lib');
            $exp_new            = $m_info['current_exp'] + $reward['exp'];
            $exp_info   = $this->CI->manager_lib->exp_belongto_level($exp_new);
            $fields_2['current_exp']    = $exp_new;
            $fields_2['total_exp']      = $exp_info['extotal_exp'];// 上级总经验值
            $fields_2['upgrade_exp']    = $exp_info['upgrade_exp'];
            $fields_2['level']          = $exp_info['level'];
        }
        $where_2    = array('idx'=>$params['uuid'],'status'=>1);
        $upt_m      = $this->CI->utility->update_m_info($fields_2,$where_2);
        if (!$upt_m) {
            $this->CI->mail_model->error();
            log_message('error', 'do_reward_achieve:m_info_update_err，奖励时，经理信息更新失败');
            $this->CI->output_json_return('m_info_update_err');
        }
        $this->CI->mail_model->success();
        return true;
    }
    
    /**
     * 一键领取邮件奖励
     * @param type $params
     */
    public function do_rewardall_mail($params)
    {
        $m_info         = $this->CI->utility->get_manager_info($params);
        $register_time  = $m_info['create_time'];
        $condition      = "A.time >= ".$register_time." AND A.status = 1 AND IF(B.is_del,0,1) = 1 AND IF(B.receive,0,1) = 1";
        $join_condition = "A.idx=B.mailconf_idx AND B.status = 1 AND B.manager_idx = ".$params['uuid'];
        $select         = "A.idx AS id,A.title AS title,A.content AS content,A.link AS link,A.is_accessory AS is_accessory,A.accessory_type AS accessory_type,A.accessory_content AS accessory_content,IF(B.idx,1,0) AS is_read,IF(B.receive,1,0) AS receive";
        $tb_a           = "mail_conf AS A";
        $tb_b           = "mail_info AS B";
        $list           = $this->CI->mail_model->left_join($condition, $join_condition, $select, $tb_a, $tb_b,TRUE);
        if (!$list) {
            log_message('error', 'do_rewardall_mail:empty_data,'.$this->ip.'暂无奖励可领取');
            $this->CI->output_json_return('empty_data');
        }
        
        $this->CI->mail_model->start();
        $reward_new = array();
        foreach ($list as $k=>$v) {
            $reward = json_decode($v['accessory_content'],true);
            if ($reward['euro']) {
                $fields_2['euro']+= $reward['euro'];
            }
            if ($reward['tickets']) {
                $fields_2['tickets']    += $reward['tickets'];
            }
            if ($reward['soccer_soul']) {
                $fields_2['soccer_soul']    +=$reward['soccer_soul'];
            }
            if ($reward['powder']) {
                $fields_2['powder'] += $reward['powder'];
            }
            if ($reward['honor']) {
                $fields_2['honor']  += $reward['honor'];
            }
            if ($reward['achievement']) {
                $fields_2['achievement']    += $reward['achievement'];
            }
            if ($reward['talent']) {
                $fields_2['talent'] += $reward['talent'];
            }
            if ($reward['exp']) {
                $exp    += $reward['exp'];
            }
            if ($reward['player_info']) {
                $player = $reward['player_info'];
                foreach ($player as $v) {
                    for($i=$v['num'];$i>0;$i--) {
                        $ist_player = $this->CI->utility->insert_player_info(array('uuid'=>$params['uuid'],'player_no'=>$v['player_no'],'level'=>$v['level']));
                        if (!$ist_player) {
                            $this->CI->mail_model->error();
                            log_message('error', 'do_reward_achieve:insert_player_err，球员卡奖励时，插入失败');
                            $this->CI->output_json_return('insert_player_err');
                        }
                    }
                }
            }
            if ($reward['equipt_info']) {
                $equipt = $reward['equipt_info'];
                foreach ($equipt as $v) {
                    for($i=$v['num'];$i>0;$i--) {
                        $ist_equipt = $this->CI->utility->insert_equipt_info(array('uuid'=>$params['uuid'],'equipt_no'=>$v['equipt_no'],'level'=>$v['level']));
                        if (!$ist_equipt) {
                            $this->CI->mail_model->error();
                            log_message('error', 'do_reward_achieve:insert_equipt_err，装备奖励时，插入失败');
                            $this->CI->output_json_return('insert_equipt_err');
                        }
                    }
                }
            }
            if ($reward['prop_info']) {
                foreach ($reward['prop_info'] as $val) {
                    $num    = $val['num'];
                    if (!$reward_new['prop_info'][$val['prop_no']]) {
                        $reward_new['prop_info'][$val['prop_no']]['num']   = $val['num'];
                        $reward_new['prop_info'][$val['prop_no']]['prop_no']   = $val['prop_no'];
                    } else {
                        $reward_new['prop_info'][$val['prop_no']]['num']   = $num+$reward_new['prop_info'][$val['prop_no']]['num'];
                    }
                }
            }
            
            if ($reward['gem_info']) {
                foreach ($reward['gem_info'] as $val) {
                    $num    = $val['num'];
                    if (!$reward_new['gem_info'][$val['gem_no']]) {
                        $reward_new['gem_info'][$val['gem_no']]['num']      = $val['num'];
                        $reward_new['gem_info'][$val['gem_no']]['gem_no']   = $val['gem_no'];
                    } else {
                        $reward_new['gem_info'][$val['gem_no']]['num']   = $num+$reward_new['gem_info'][$val['gem_no']]['num'];
                    }
                }
            }
            
            if ($v['is_read']) {// 已读
                $update_data[]  = array(
                    'mailconf_idx'  => $v['id'],
                    'receive'       => 1,
                    'is_read'       => 1,
                );
            } else {// 未读
                $insert_data[]  = array(
                    'manager_idx'   => $params['uuid'],
                    'type'          => 1,
                    'mailconf_idx'  => $v['id'],
                    'title'         => $v['title'],
                    'content'       => $v['content'],
                    'link'          => $v['link'],
                    'is_accessory'  => $v['is_accessory'],
                    'accessory_type'=> $v['accessory_type'],
                    'accessory_content' => $v['accessory_content'],
                    'is_read'           => 1,
                    'is_del'            => 0,
                    'receive'           => 1,
                    'status'            => 1,
                );
            }   
        }
        // 添加道具。宝石
        if ($reward_new['gem_info']) {
            $gem = $reward_new['gem_info'];
            foreach ($gem as $v) {
                $ist_gem    = $this->CI->utility->insert_gem_info(array('uuid'=>$params['uuid'],'gem_no'=>$v['gem_no'],'num'=>$v['num']));
                if (!$ist_gem) {
                    $this->CI->mail_model->error();
                    log_message('error', 'do_reward_achieve:insert_gem_err，宝石奖励时，插入失败');
                    $this->CI->output_json_return('insert_gem_err');
                }
            }
        }
        if ($reward_new['prop_info']) {
            $prop = $reward_new['prop_info'];
            foreach ($prop as $v) {
                $ist_prop = $this->CI->utility->insert_prop_info(array('uuid'=>$params['uuid'],'prop_no'=>$v['prop_no']),$v['num']);
                if (!$ist_prop) {
                    $this->CI->mail_model->error();
                    log_message('error', 'do_reward_achieve:insert_prop_err，道具奖励时，插入失败');
                    $this->CI->output_json_return('insert_prop_err');
                }
            }
        }
        
        // 更新邮件状态
        if ($insert_data) {
            $ist_res    = $this->CI->mail_model->insert_batch($insert_data,'mail_info');
            if (!$ist_res) {
                $this->CI->mail_model->error();
                log_message('error', 'do_rewardall_mail:mail_reward_all_fail_err,'.$this->ip.'邮件奖励一键领取失败');
                $this->CI->output_json_return('mail_reward_all_fail_err');
            }
        }
        if ($update_data) {
            $upt_res    = $this->CI->mail_model->update_batch($update_data, 'mailconf_idx', 'mail_info');
            if (!$upt_res) {
                $this->CI->mail_model->error();
                log_message('error', 'do_rewardall_mail:mail_reward_all_fail_err,'.$this->ip.'邮件奖励一键领取失败');
                $this->CI->output_json_return('mail_reward_all_fail_err');
            }
        }
        // 更新经理信息
        if ($fields_2['euro']) {
            $fields_2['euro']   += $m_info['euro'];
        }
        if ($fields_2['tickets']) {
            $fields_2['tickets']    += $m_info['tickets'];
        }
        if ($fields_2['soccer_soul']) {
            $fields_2['soccer_soul']    +=$m_info['soccer_soul'];
        }
        if ($fields_2['powder']) {
            $fields_2['powder'] += $m_info['powder'];
        }
        if ($fields_2['honor']) {
            $fields_2['honor']  += $m_info['honor'];
        }
        if ($fields_2['achievement']) {
            $fields_2['achievement']    += $m_info['achievement'];
        }
        if ($fields_2['talent']) {
            $fields_2['talent'] += $m_info['talent'];
        }
        
        if ($exp) {
            $this->CI->load->library('manager_lib');
            $exp_new    = $m_info['current_exp'] + $exp;
            $exp_info   = $this->CI->manager_lib->exp_belongto_level($exp_new);
            $fields_2['current_exp']    = $exp_new;
            $fields_2['total_exp']      = $exp_info['extotal_exp'];
            $fields_2['upgrade_exp']    = $exp_info['upgrade_exp'];
            $fields_2['level']          = $exp_info['level'];
        }    
        $upt_m  = $this->CI->utility->update_m_info($fields_2,array('idx'=>$params['uuid'],'status'=>1));
        if (!$upt_m) {
            $this->CI->mail_model->error();
            log_message('error', 'do_rewardall_mail:m_info_update_err,'.$this->ip.'经理信息更新失败');
            $this->CI->output_json_return('m_info_update_err');
        }
        
        $this->CI->mail_model->success();
        return true;
    }
    
    
    
}
