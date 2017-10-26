<?php
/**
 * 商城控制器
 * @author huhong <huhong@example.com>
 * @date    2016-05-18
 */
class Shop extends MY_Controller {
    public function __construct() {
        parent::__construct();
        $this->load->library('shop_lib');
    }
    
    /**
     * 商店列表接口
     */
    public function store_list()
    {
        $params     = $this->public_params();
        // 校验签名
        $this->utility->check_sign($params, $params['sign']);
        // 获取球员列表
        $options        = array('where'=>array('status'=>1),'fields'=>"type as id,name,limit_time,open_time,close_time");
        $data['list']   = $this->shop_lib->get_store_list($options);
        if (!$data) {
            log_message('error', 'empty_data:'.$this->shop_lib->ip.',暂无商店列表数据');
            $this->output_json_return('empty_data');
        }
        $this->output_json_return('success',$data);
    }
    
    /**
     * 商品列表接口
     */
    public function goods_list()
    {
        $params             = $this->public_params();
        $params['id']       = (int)$this->request_params('id');
        $params['offset']   = (int)$this->request_params('offset');
        $params['pagesize'] = $this->request_params('pagesize');
        
        // 校验参数
        if ($params['id'] == '' || $params['offset'] === '' || $params['offset'] < 0) {
            log_message('error', 'params_err：'.$this->court_lib->ip.',',http_build_query($_REQUEST));
            $this->output_json_return('params_err');
        }
        
        // 校验签名
        $this->utility->check_sign($params, $params['sign']);
        if (!$params['pagesize']) {
            $params['pagesize'] = parent::PAGESIZE;
        }
        
        // 获取商品列表
        $options = array(
            'where' => array('type'=>$params['id'],'is_online'=>1,'status'=>1),
            'fields'=> "idx as id,goods_no,name,pic,frame,currency_type,sale_price,price,limit_buy,descript,goods_idx",
            'limit' => array('size'=>$params['pagesize'],'page'=>$params['offset']),
        );
        $total_count    = $this->shop_lib->get_total_count($options['where'],'goods_conf');
        if (!$total_count) {
            log_message('info', 'empty_data:'.$this->court_lib->ip.',未查询到商品列表信息');
            $this->output_json_return('empty_data');
        }
        $data['pagecount']  = ceil($total_count/$params['pagesize']);
        $data['list']       = $this->shop_lib->get_goods_list($options,$params['uuid'],$params['id']);
        $this->output_json_return('success', $data);
    }
    
    /**
     * 商品详情接口
     */
    public function goods_info()
    {
        $params             = $this->public_params();
        $params['id']       = (int)$this->request_params('id');
        
        // 校验参数
        if ($params['id'] == '') {
            log_message('error', 'params_err：'.$this->court_lib->ip.',',http_build_query($_REQUEST));
            $this->output_json_return('params_err');
        }
        
        // 校验签名
        $this->utility->check_sign($params, $params['sign']);
        
        // 获取商品详情
        $data   = $this->shop_lib->get_goods_info($params);
        $this->output_json_return('success', $data);
    }
    
    /**
     * 购买商品接口
     */
    public function goods_buy()
    {
        $params             = $this->public_params();
        $params['id']       = (int)$this->request_params('id');
        $params['number']   = $this->request_params('number');
        
        // 校验参数
        if ($params['id'] === '') {
            log_message('error', 'params_err：'.$this->court_lib->ip.',',http_build_query($_REQUEST));
            $this->output_json_return('params_err');
        }
        // 校验签名
        $this->utility->check_sign($params, $params['sign']);
        if (!$params['number']) {
            $params['number'] = 1;
        }
        $params['number']   = (int)$params['number'];
        // 购买商品
        $res = $this->shop_lib->goods_buy($params);
        if (!$res) {
            $this->output_json_return('goods_buy_err');
        }
        $this->output_json_return('success');
    }
    
    /**
     * 礼包商城
     */
    public function package_list()
    {
        $params             = $this->public_params();
        $params['offset']   = (int)$this->request_params('offset');
        $params['pagesize'] = $this->request_params('pagesize');
        
        // 校验参数
        if ($params['offset'] === '' || $params['offset'] < 0) {
            log_message('error', 'params_err：'.$this->court_lib->ip.',',http_build_query($_REQUEST));
            $this->output_json_return('params_err');
        }
        
        // 校验签名
        $this->utility->check_sign($params, $params['sign']);
        if (!$params['pagesize']) {
            $params['pagesize'] = parent::PAGESIZE;
        }
        
        // 获取商品列表
        $options = array(
            'where' => array('status'=>1),
            'fields'=> "idx as id,name,name,prop,descript,price",
            'limit' => array('size'=>$params['pagesize'],'page'=>$params['offset']),
        );
        $total_count    = $this->shop_lib->get_total_count($options['where'],'package_conf');
        if (!$total_count) {
            log_message('info', 'empty_data:'.$this->court_lib->ip.',未查询到商城礼包列表信息');
            $this->output_json_return('empty_data');
        }
        $data['pagecount']  = ceil($total_count/$params['pagesize']);
        $data['list']       = $this->shop_lib->get_package_list($options);
        $this->output_json_return('success', $data);
    }
    
    
}
