<?php
class Statics_lib extends Base_lib {
    public function __construct() {
        parent::__construct();
        $this->load_model('statics_model');
    }
    
    /**
     * 查询总appid列表
     */
    public function get_appid_list()
    {
        $sql    = "SELECT app_id appid FROM register WHERE  status = 1 GROUP BY app_id";
        $list   = $this->CI->statics_model->fetch($sql);
        if (!$list) {
            return false;
        }
        $new_list   = array();
        foreach ($list as $k=>$v) {
            $new_list[$v['appid']]['appid']         = $v['appid'];
            $new_list[$v['appid']]['register_num']  = 0;
            $new_list[$v['appid']]['fee_total']     = 0;
            $new_list[$v['appid']]['fee_num']       = 0;
            $new_list[$v['appid']]['fee_person']    = 0;
            $new_list[$v['appid']]['mlevel5_num']   = 0;
            $new_list[$v['appid']]['mlevel9_num']   = 0;
            $new_list[$v['appid']]['mlevel10_num']  = 0;
        }
        return $new_list;
    }
    
    /**
     * 统计注册人数
     */
    public function register_num($params)
    {
        $start  = strtotime($params['start_time']);
        $end    = strtotime($params['end_time']);
        // 根据app_id计算
        $sql    = "SELECT app_id appid,COUNT(idx) register_num FROM register WHERE time >= ".$start." AND time <= ".$end." AND status = 1 GROUP BY app_id";
        $list   = $this->CI->statics_model->fetch($sql);
        if (!$list) {
            return false;
        }
        return $list;
    }
    
    /**
     * 统计付费人民币（元）保留2位小数
     */
    public function fee_total($params)
    {
        $start  = strtotime($params['start_time']);
        $end    = strtotime($params['end_time']);
        
        // 根据app_id计算
        $sql    = "SELECT A.app_id appid,SUM(B.rmb) rmb FROM register A,recharge_his B WHERE A.manager_idx = B.manager_idx AND B.time >= ".$start." AND B.time <= ".$end."  GROUP BY A.app_id";
        $list   = $this->CI->statics_model->fetch($sql);
        if (!$list) {
            return false;
        }
        return $list;
    }
    
    /**
     * 统计付费次数
     */
    public function fee_num($params)
    {
        $start  = strtotime($params['start_time']);
        $end    = strtotime($params['end_time']);
        
        // 根据app_id计算
        $sql    = "SELECT A.app_id appid,COUNT(B.rmb) fee_num FROM register A,recharge_his B WHERE A.manager_idx = B.manager_idx AND B.time >= ".$start." AND B.time <= ".$end." GROUP BY A.app_id";
        $list   = $this->CI->statics_model->fetch($sql);
        if (!$list) {
            return false;
        }
        return $list;
    }
    
    /**
     * 获取付费人数
     * @param type $params
     * @return boolean
     */
    public function fee_person($params)
    {
        $start  = strtotime($params['start_time']);
        $end    = strtotime($params['end_time']);
        
        // 根据app_id计算
        $sql    = "SELECT appid,COUNT(uuid) fee_person FROM (SELECT A.app_id appid,B.manager_idx uuid  FROM register A,recharge_his B WHERE A.manager_idx = B.manager_idx AND B.time >= ".$start." AND B.time <= ".$end." GROUP BY B.manager_idx) m GROUP BY appid";
        $list   = $this->CI->statics_model->fetch($sql);
        if (!$list) {
            return false;
        }
        return $list;
    }
    
    /**
     * 统计经理等级1-5 【包含1和5】
     */
    public function mlevel5_num($params)
    {
        $start  = strtotime($params['start_time']);
        $end    = strtotime($params['end_time']);
        
        // 根据app_id计算
        $sql    = "SELECT A.app_id appid,COUNT(B.idx) mlevel5_num FROM register A,manager_info B WHERE A.manager_idx = B.idx AND (B.level BETWEEN 1 AND 5) AND B.time >= ".$start." AND B.time <= ".$end."  GROUP BY A.app_id";
        $list   = $this->CI->statics_model->fetch($sql);
        if (!$list) {
            return false;
        }
        return $list;
    }
    
    /**
     * 统计经理等级6-9【包含6和9】
     */
    public function mlevel9_num($params)
    {
        $start  = strtotime($params['start_time']);
        $end    = strtotime($params['end_time']);
        
        // 根据app_id计算
        $sql    = "SELECT A.app_id appid,COUNT(B.idx) mlevel9_num FROM register A,manager_info B WHERE A.manager_idx = B.idx AND (B.level BETWEEN 6 AND 9) AND B.time >= ".$start." AND B.time <= ".$end."  GROUP BY A.app_id";
        $list   = $this->CI->statics_model->fetch($sql);
        if (!$list) {
            return false;
        }
        return $list;
    }
    
    /**
     * 统计经理等级10+【包含10】
     */
    public function mlevel10_num($params)
    {
        $start  = strtotime($params['start_time']);
        $end    = strtotime($params['end_time']);
        
        // 根据app_id计算
        $sql    = "SELECT A.app_id appid,COUNT(B.idx) mlevel10_num FROM register A,manager_info B WHERE A.manager_idx = B.idx AND B.level >= 10  AND B.time >= ".$start." AND B.time <= ".$end." GROUP BY A.app_id";
        $list   = $this->CI->statics_model->fetch($sql);
        if (!$list) {
            return false;
        }
        return $list;
    }
    
}

