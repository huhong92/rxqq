<?php
class Vitality_lib extends Base_lib {
    public function __construct() {
        parent::__construct();
        $this->load_model('vitality_model');
    }
    
    /**
     * 更新经理所有体力值
     */
    public function update_m_endurance()
    {
        $data   = array('endurance'=>50);
        $where  = array('status'=>1);
        $res = $this->CI->vitality_model->update_data($data, $where, 'manager_info');
        return true;
    }
    
    /**
     * 恢复经理体力值
     * @return boolean
     */
    public function update_m_phystrenth()
    {
        $m_active   = $this->CI->passport->get('m_active');// 经理活力值
        $options['where']   = array('status'=>1);
        $options['fields']  = "idx as id,level,physical_strenth";
        $m_list             = $this->CI->vitality_model->list_data($options,'manager_info');
        foreach ($m_list as $v) {
            $phy_curr   = $v['physical_strenth'];
            $phy_total  = $m_active['phy_init'] + ($v['level']-1)*$m_active['phy_step'];
            if ($phy_curr < $phy_total) {
                $phy_curr++;
                $data[]   = array(
                    'idx'               => $v['id'],
                    'physical_strenth'  => $phy_curr,
                );
            }
        }
        $this->CI->vitality_model->update_batch($data,'idx','manager_info');
        return true;
    }
    
    /**
     * 恢复球员的疲劳值
     * @return boolean
     */
    public function update_p_fatigue()
    {
        $p_active   = $this->CI->passport->get('p_active');// 球员疲劳值
        $options['where']   = array('status'=>1);
        $options['fields']  = "idx as id,fatigue";
        $p_list             = $this->CI->vitality_model->list_data($options,'player_info');
        foreach ($p_list as $v) {
            $fatigue_curr   = $v['fatigue'];
            if ($fatigue_curr > 0) {
                $fatigue_curr =($fatigue_curr-$p_active['f_step'] > 0)?$fatigue_curr-$p_active['f_step']:0;
                $data[]   = array(
                    'idx'       => $v['id'],
                    'fatigue'   => $fatigue_curr,
                );
            }
        }
        $this->CI->vitality_model->update_batch($data,'idx','player_info');
        return true;
    }
    
    
    /**
     * 集体发放月卡奖励【每晚12点前 发放月卡 】
     * @param type $params
     */
    public function month_card_reward()
    {
        // 获取已购买月卡并且有效的经理id
        $time   = strtotime(date('Ymd',time() - 29*86400));
        $options['where']   = array('good_id'=>1,'time>='=>$time,'time<'=>  strtotime(date('Ymd',time())));// 月卡充值包id
        $options['fields']  = "idx id,time,manager_idx";
        $card_list          = $this->CI->vitality_model->list_data($options,'recharge_his');
        if (!$card_list) {
            return true;
        }
        
        // 获取月卡配置信息
        $where  = array('type'=>1,'status'=> 1);
        $fields = "idx id,tickets,present";
        $info   = $this->CI->vitality_model->get_one($where,'recharge_conf',$fields);
        if (!$info) {
            log_message('error', 'get_present_info:'.$this->ip.',充值包信息获取失败');
            $this->CI->output_json_return('get_recharge_pack_info');
        }

        // 获取经理目前球票数
        $manger_ids = array_column($card_list, "manager_idx");
        $ids        = implode(",", $manger_ids);
        $sql        = "SELECT idx id,tickets FROM manager_info WHERE idx IN (".$ids.") AND status = 1";
        $m_list     = $this->CI->vitality_model->fetch($sql);
        if (!$m_list) {
            log_message("error", 'get_present_info:'.$this->ip.',经理信息获取失败;月卡球票发放失败-经理ids'.$manger_ids);
            return true;
        }
        
        // 发放奖励-更新经理球票数
        $this->CI->vitality_model->start();
        foreach ($m_list as $k=>$v) {
            $m_update[]   = array(
                'idx'       => $v['id'],
                'tickets'   => $v['tickets'] + $info['tickets'],
            );
            $m_insert[] = array(
                'sender_id'         => 1,
                'sender_name'       => 'admin',
                'manager_idx'       => $v['id'],
                'title'             => '月卡奖励发送提醒',
                'content'           => '月卡奖励每日定时发送',
                'link'              => '',
                'is_accessory'      => 0,
                'accessory_type'    => 0,
                'accessory_content' => '',
                'status'            => 1,
            );
        }
        
        // 更新经理球票
        $u_manager  = $this->CI->vitality_model->update_batch($m_update,'idx','manager_info');
        if (!$u_manager) {
            $this->CI->vitality_model->error();
            log_message('error', 'give_month_card_reward:'.$this->ip.',发送月卡奖励,球票更新失败');
            $this->CI->output_json_return('m_info_update_err');
        }
        
        // 插入邮件通知
        $i_res  = $this->CI->vitality_model->insert_batch($m_insert,'mail_conf');
        if (!$i_res) {
            $this->CI->vitality_model->error();
            log_message('error', 'give_month_card_reward:'.$this->ip.',月卡奖励提醒邮件插入失败');
            $this->CI->output_json_return('mail_insert_fail');
        }
        $this->CI->vitality_model->success();
        return true;
    }
    
}