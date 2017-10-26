<?php
class Vitality{
    private $CI;
    public function __construct() {
        $this->CI = & get_instance();
    }
    
    /**
     * 经理耐力恢复操作（每晚22点恢复）
     */
    public function m_endurance($params = array())
    {
        $this->CI->load->library('vitality_lib');
        $this->CI->vitality_lib->update_m_endurance();
        return true;
    }
    
    /**
     * 天梯排位赛每晚22点发送奖励
     */
    public function award_grant()
    {
        
    }
    
    /**
     * 经理体力恢复（每10分钟恢复1点）
     */
    public function m_phystrenth()
    {
        $this->CI->load->library('vitality_lib');
        $this->CI->vitality_lib->update_m_phystrenth();
        return true;
    }
    
    /**
     * 球员疲劳值恢复（每1小时降低5点）
     */
    public function p_fatigue()
    {
        $this->CI->load->library('vitality_lib');
        $this->CI->vitality_lib->update_p_fatigue();
        return true;
    }
    
    /**
     * 月卡每日奖励发送
     */
    public function month_card()
    {
        $this->CI->load->library('vitality_lib');
        $this->CI->vitality_lib->month_card_reward();
        return true;
    }
    
}

