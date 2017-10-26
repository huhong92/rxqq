<?php
class Cron extends MY_Controller {
    
    /**
     * 计划任务执行
     */
    public function index()
    {
        $this->load->library('cron_lib');
        $this->cron_lib->dispatch();
    }
    
     /**
     * 经理耐力恢复操作（每晚22点恢复）
     */
    public function m_endurance($params = array())
    {
        $this->load->library('vitality_lib');
        $this->vitality_lib->update_m_endurance();
        echo "success";
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
        $this->load->library('vitality_lib');
        $this->vitality_lib->update_m_phystrenth();
        echo "success";
    }
    
    /**
     * 球员疲劳值恢复（每1小时降低5点）
     */
    public function p_fatigue()
    {
        $this->load->library('vitality_lib');
        $this->vitality_lib->update_p_fatigue();
        echo "success";
    }
    
    /**
     * 月卡每日奖励发送
     */
    public function month_card()
    {
        $this->load->library('vitality_lib');
        $this->vitality_lib->month_card_reward();
        echo "success";
    }
    
}

