<?php
/**
 * 统计控制器
 * @author huhong <huhong@example.com>
 * @date    2016-05-18
 */
class Statics extends MY_Controller {
    public function __construct() {
        parent::__construct();
        $this->load->library('statics_lib');
    }
    
    /**
     * 统计数据
     */
    public function statistics_by_appid()
    {
        // 日期参数
        $params['start_time']   = $this->request_params('start_time');
        $params['end_time']     = $this->request_params('end_time');
        if (!$params['start_time']) {
            $params['start_time']   = "2016-01-01";
        }
        if (!$params['end_time']) {
            $params['end_time']   = "2017-06-28";
        }
        
        // 获取总appid列表
        $appid_list     = $this->statics_lib->get_appid_list();
        
        // 注册
        $register       = $this->statics_lib->register_num($params);
        if ($register) {
            foreach ($register as $k=>$v) {
                $appid_list[$v['appid']]['register_num'] = (int)$v['register_num'];// 单位元
            }
        }
        
        // 付费
        $fee_total      = $this->statics_lib->fee_total($params);
        if ($fee_total) {
            foreach ($fee_total as $k=>$v) {
                $appid_list[$v['appid']]['fee_total'] = (float)($v['rmb']/100);// 单位元
            }
        }
        
        // 付费次数
        $fee_num        = $this->statics_lib->fee_num($params);
        if ($fee_num) {
            foreach ($fee_num as $k=>$v) {
                $appid_list[$v['appid']]['fee_num'] = (int)$v['fee_num'];
            }
        }
        
        // 付费人数
        $fee_person     = $this->statics_lib->fee_person($params);
        if ($fee_person) {
            foreach ($fee_person as $k=>$v) {
                $appid_list[$v['appid']]['fee_person'] = (int)$v['fee_person'];
            }
        }
        
        // 经理等级1-5人数
        $mlevel5_num    = $this->statics_lib->mlevel5_num($params); 
        if ($mlevel5_num) {
            foreach ($mlevel5_num as $k=>$v) {
                $appid_list[$v['appid']]['mlevel5_num'] = (int)$v['mlevel5_num'];
            }
        }
        // 经理等级6-9人数
        $mlevel9_num    = $this->statics_lib->mlevel9_num($params);
        if ($mlevel9_num) {
            foreach ($mlevel9_num as $k=>$v) {
                $appid_list[$v['appid']]['mlevel9_num'] = (int)$v['mlevel9_num'];
            }
        }
        // 经理等级10+人数
        $mlevel10_num   = $this->statics_lib->mlevel10_num($params);
        if ($mlevel10_num) {
            foreach ($mlevel10_num as $k=>$v) {
                $appid_list[$v['appid']]['mlevel10_num'] = (int)$v['mlevel10_num'];
            }
        }
        $this->output_json_return('success',array_values($appid_list));
    }
    
    
    
}
