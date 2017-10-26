<?php
/**
 * 比赛控制器
 * @author huhong <huhong@example.com>
 * @date    2016-05-18
 */
class Match extends MY_Controller {
    public function __construct() {
        parent::__construct();
        $this->load->library('match_lib');
    }
    
    /**
     * 赛事类型列表接口
     */
    public function match_list()
    {
        $params = $this->public_params();
        $data   = $this->match_lib->match_list($params);
        $this->output_json_return('success', $data);
    }
    
    /**
     * 获取副本列表（共12个副本）
     */
    public function copy_list()
    {
        $params = $this->public_params();
        $data   = $this->match_lib->copy_list($params);
        $this->output_json_return('success', $data);
    }
    
    /**
     * 关卡列表（共9关）
     */
    public function ckpoint_list()
    {
        $params             = $this->public_params();
        $params['copy_no']  = (int)$this->request_params('copy_no');
        if ($params['copy_no'] == '') {
            log_message('error', 'params_err：'.$this->match_lib->ip.',',http_build_query($_REQUEST));
            $this->output_json_return('params_err');
        }
        $this->utility->check_sign($params, $params['sign']);
        $data   = $this->match_lib->ckpoint_list($params);
        $this->output_json_return('success', $data);
    }
    
    /*
     * 领取副本满星奖励
     */
    public function copy_fullstar()
    {
        $params             = $this->public_params();
        $params['copy_no']  = (int)$this->request_params('copy_no');
        if ($params['copy_no'] == '') {
            log_message('error', 'params_err：'.$this->match_lib->ip.',',http_build_query($_REQUEST));
            $this->output_json_return('params_err');
        }
        $this->utility->check_sign($params, $params['sign']);
        $this->match_lib->copy_fullstar($params);
        $this->output_json_return('success');
    }

    /**
     * 关卡详情
     */
    public function ckpoint_info()
    {
        $params                 = $this->public_params();
        $params['copy_no']      = (int)$this->request_params('copy_no');
        $params['ckpoint_no']   = (int)$this->request_params('ckpoint_no');
        if ($params['copy_no'] == '' || $params['ckpoint_no'] == '') {
            log_message('error', 'params_err：'.$this->match_lib->ip.',',http_build_query($_REQUEST));
            $this->output_json_return('params_err');
        }
        $this->utility->check_sign($params, $params['sign']);
        $data   = $this->match_lib->ckpoint_info($params);
        $this->output_json_return('success', $data);
    }
    
    /**
     * 副本赛接口，并返回比赛结果
     */
    public function match_for_copy()
    {
        $params                 = $this->public_params();
        $params['copy_no']      = (int)$this->request_params('copy_no');
        $params['ckpoint_no']   = (int)$this->request_params('ckpoint_no');
        $params['sweep']        = (int)$this->request_params('sweep');// 1扫荡2挑战
        $params['type']         = (int)$this->request_params('type');// 赛事类型 1常规普通赛 2常规精英赛 
        // 参数校验
        if ($params['copy_no'] == '' || $params['ckpoint_no'] == '' || $params['type'] == '' || $params['sweep'] == '') {
            log_message('error', 'params_err：'.$this->match_lib->ip.',',http_build_query($_REQUEST));
            $this->output_json_return('params_err');
        }
        
        // 副本校验、关卡校验TODO
        
        // 精英赛校验
        if ($params['type'] === 2) {
            if (!in_array($params['ckpoint_no'], array(3,6,9))) {
                log_message('error', 'params_err：'.$this->match_lib->ip.',',http_build_query($_REQUEST));
                $this->output_json_return('params_err');
            }
        }
        // $this->utility->check_sign($params,$params['sign']);
        $data   = $this->match_lib->match_for_copy($params);
        $this->output_json_return('success', $data);
    }
    
    /**
     * 天梯赛
     */
    public function match_for_ladder()
    {
        $params         = $this->public_params();
        $params['id']   = (int)$this->request_params('id');
        if ($params['id'] == '') {
            log_message('error', 'params_err：'.$this->match_lib->ip.',',http_build_query($_REQUEST));
            $this->output_json_return('params_err');
        }
        // $this->utility->check_sign($params,$params['sign']);
        
        $data   = $this->match_lib->match_for_ladder($params);
        $this->output_json_return('success', $data);
    }
    
    /**
     * 五大联赛（爬塔赛）
     */
    public function match_for_league()
    {
        $params                 = $this->public_params();
        $params['ckpoint_no']   = (int)$this->request_params('ckpoint_no');
        if ($params['ckpoint_no'] == '' || $params['ckpoint_no'] > 100) {
            log_message('error', 'params_err：'.$this->match_lib->ip.',',http_build_query($_REQUEST));
            $this->output_json_return('params_err');
        }
        // $this->utility->check_sign($params,$params['sign']);
        
        $data   = $this->match_lib->match_for_league($params);
        $this->output_json_return('success', $data);
    }
    
    /**
     * 天梯赛前10名查看
     */
    public function top10_for_ladder()
    {
        $params = $this->public_params();
        $this->utility->check_sign($params,$params['sign']);
        
        $data   = $this->match_lib->top10_for_ladder($params);
        $this->output_json_return('success',$data);
        return $data;
    }
    
    /**
     * 天梯赛-可挑战排行榜
     */
    public function chall_for_ladder()
    {
        $params = $this->public_params();
        $this->utility->check_sign($params,$params['sign']);
        $data   = $this->match_lib->challenger_for_ladder($params);
        $this->output_json_return('success',$data);
    }
    
    /**
     * 获取经理当前关卡信息
     */
    public function ckpoint_for_league()
    {
        $params = $this->public_params();
        $this->utility->check_sign($params,$params['sign']);
        $data   = $this->match_lib->ckpoint_for_league($params);
        $this->output_json_return('success',$data);
    }
    
    /**
     * 五大联赛奖励预览-可预览大、小守关boss的特殊奖励（10 20 30 ... 90 100）
     */
    public function reward_preview()
    {
        $params = $this->public_params();
        $params['offset']   = (int)$this->request_params('offset');
        $params['pagesize'] = $this->request_params('pagesize');
        if ($params['offset'] === '' || $params['offset'] < 0) {
            log_message('error', 'params_err：'.$this->match_lib->ip.',',http_build_query($_REQUEST));
            $this->output_json_return('params_err');
        }
        $this->utility->check_sign($params,$params['sign']);
        if (!$params['pagesize']) {
            $params['pagesize'] = self::PAGESIZE;
        }
        $data   = $this->match_lib->get_reward_preview($params);
        $this->output_json_return('success',$data);
    }
    
    /**
     * 五大联赛-冲关排行接口（前10名）
     */
    public function ranking_for_league()
    {
        $params = $this->public_params();
        $this->utility->check_sign($params,$params['sign']);
        $data   = $this->match_lib->ranking_for_league($params);
        $this->output_json_return('success',$data);
    }
    
    /**
     * 五大联赛重置关卡
     */
    public function reset_for_league()
    {
        $params = $this->public_params();
        $this->utility->check_sign($params,$params['sign']);
        $this->match_lib->reset_for_league($params);
        $this->output_json_return();
    }
    
    /**
     * 副本一键扫荡（副本扫荡）
     */
    public function sweepall_for_copy()
    {
        $params                 = $this->public_params();
        $params['type']         = (int)$this->request_params('type');// 1常规赛 2精英赛
        $params['copy_no']      = $this->request_params('copy_no');
        $params['ckpoint_no']   = $this->request_params('ckpoint_no');
        if ($params['type'] == '' || $params['copy_no'] == '' || $params['ckpoint_no'] == '') {
            log_message('error', 'params_err：'.$this->match_lib->ip.',',http_build_query($_REQUEST));
            $this->output_json_return('params_err');
        }
        $this->utility->check_sign($params,$params['sign']);
        $this->match_lib->do_sweepall_for_copy($params);
        $this->output_json_return();
    }
    
    /**
     * 五大联赛-扫荡
     */
    public function sweep_for_league()
    {
        $params                 = $this->public_params();
        $params['ckpoint_no']   = $this->request_params('ckpoint_no');
        $params['is_tickets']   = $this->request_params('is_tickets');
        if ($params['is_tickets'] === '') {
            log_message('error', 'params_err：'.$this->match_lib->ip.',',http_build_query($_REQUEST));
            $this->output_json_return('params_err');
        }
        $this->utility->check_sign($params,$params['sign']);
        $this->match_lib->do_sweep_league($params);
        $this->output_json_return();
    }
    
    /**
     * 天梯赛初始名次（新手引导时调用）
     */
    public function init_ranking_ladder()
    {
        $params = $this->public_params();
        $this->utility->check_sign($params,$params['sign']);
        $this->match_lib->do_init_ranking_ladder($params);
        $this->output_json_return();
    }
    
    
}

