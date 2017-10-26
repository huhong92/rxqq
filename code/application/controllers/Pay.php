<?php
class Pay extends MY_Controller {
    public function __construct() {
        parent::__construct();
        $this->load->library('Pay_lib');
    }

    /**
     * 生成支付订单
     */
    public function pay()
    {
        $params              = $this->public_params();
        $params['type']      = (int)$this->request_params('type');   //1球票直充 2充值包 3升级球员 4升级装备 5购买组合包
        $params['id']        = $this->request_params('id');          //充值包id,装备球员id,组合包id
        $params['num']       = $this->request_params('num');         //球票充值数量（可选）
        $params['openid']    = $this->request_params('openid');      //微信用户唯一标示openid，微信支付时必填（可选）
        $params['platform']  = $this->request_params('platform');    //充值渠道 1中九 2微信 3支付宝
        // 校验参数
        if ($params['type'] == '') {
            log_message('error', 'params_error:'.$this->pay_lib->ip.','.http_build_query($_REQUEST));
            $this->output_json_return('params_err');
        }
        if($params['type'] == 1 && ($params['num'] == 0 || $params['num'] == '')){
            log_message('error', 'params_error:'.$this->pay_lib->ip.','.http_build_query($_REQUEST));
            $this->output_json_return('params_err');
        }
        if($params['type'] != 1 && ($params['id'] == 0 || $params['id'] == '')){
            log_message('error', 'params_error:'.$this->pay_lib->ip.','.http_build_query($_REQUEST));
            $this->output_json_return('params_err');
        }
        if($params['platform'] == 2 && $params['openid'] == ''){
            log_message('error', 'params_error:'.$this->pay_lib->ip.','.http_build_query($_REQUEST));
            $this->output_json_return('params_err');
        }
        // 校验签名
        $this->utility->check_sign($params, $params['sign']);
        $data = $this->pay_lib->get_pay_params($params);
        $this->output_json_return('success', $data);
    }
    
    public function z9sdk_callback()
    {
        //记录回调参数到文件
        //$this->pay_lib->log_file();
        $params['sign'] = $this->request_params('sign');
        $params['notifyTime']     = $this->request_params('notifyTime');       //请求回调时间
        $params['outTradeNo']     = $this->request_params('outTradeNo');       //中九订单号
        $params['tradeStatus']    = $this->request_params('tradeStatus');      //支付结果，TRADE_SUCCESS表示成功，其他表示交易失败
        $params['gmtPayment']     = $this->request_params('gmtPayment');       //订单支付时间
        $params['totalFee']       = $this->request_params('totalFee');         //支付金额，单位为元
        $params['productId']      = $this->request_params('productId');        //商品ID
        $params['partnerId']      = $this->request_params('partnerId');        //九分配给CP的合作游戏的ID
        $params['partnerOrderId'] = $this->request_params('partnerOrderId');   //合作方（游戏厂商）自有定单号
        
        //校验参数
        if($params['sign'] == '' || $params['notifyTime'] == '' || $params['outTradeNo'] == '' || $params['tradeStatus'] == '' || $params['totalFee'] == '' || $params['productId'] == '' || $params['partnerId'] == '' || $params['partnerOrderId'] == ''){
            echo 'FALSE';
            exit;
        }
        //验证签名
        $sign = $params['sign'];
        unset($params['sign']);
        
        if($params['gmtPayment'] == '' || !$params['gmtPayment']){
            unset($params['gmtPayment']);
        }   
        
        //制作验证字符串 手动拼接方式
        foreach ($params as $key => $val){
            $params_str .= $key.'='.$val.'&';
        }
        $params_str.='key=3361b1eb638ec287702bb5f14b8bc45b';

        if($sign != md5($params_str)){
            log_message('error', 'sign_error:sign校验失败'.http_build_query($_REQUEST));
            echo 'FALSE';
            exit;
        }
        //验证经理信息
        $order_info = $this->pay_lib->get_order_info($params['partnerOrderId']);
        if(!$order_info['manager_idx']){
            log_message('error', 'get_uuid:获取经理ID失败'.http_build_query($_REQUEST));
            echo 'FALSE';
            exit;
        }
        //验证金额
//        if($params['totalFee'] != ($order_info['price']) / 100){
//            log_message('error', '金额不匹配'.http_build_query($_REQUEST));
//            echo 'FALSE aaa';
//            exit;
//        }
        //执行发奖
        $data = $this->pay_lib->do_callback($params);
        echo $data;
        exit;
    }
    
    /*
     * 微信回调接口
     */
    public function wechat_callback()
    {
        $return['return_code'] = 'FAIL';
        $xmldata = file_get_contents("php://input");
        $data = $this->pay_lib->xml_to_array($xmldata);
 
        if($data['return_code'] != 'SUCCESS'){
            log_message('error', '通信失败'.http_build_query($xmldata));
            $return['return_msg'] = '通信失败';
            $this->pay_lib->log_file('通信失败');
            $return = $this->pay_lib->array_to_xml($return);
            echo $return;
            exit;
        }
        //验证签名
        $secret_key = 'fc71deec27ac47cfa0df1582fc4b241b';
        $sign_str = $this->utility->get_sign_params($data);
        $sign = strtoupper(md5($sign_str."&key=".$secret_key));
        if($sign != $data['sign']){
            log_message('error', '签名认证不通过'.http_build_query($xmldata));
            $this->pay_lib->log_file('签名认证不通过::::'.$sign.'--------'.$data['sign']);
            $return['return_msg'] = '签名认证不通过';
            $return = $this->pay_lib->array_to_xml($return);
            echo $return;
            exit;
        }
        
        //验证登录
        $order_info = $this->pay_lib->get_order_info($data['out_trade_no']);
        if(!$order_info['manager_idx']){
            log_message('error', 'get_order_info:获取经理ID失败'.http_build_query($xmldata));
            $this->pay_lib->log_file('获取经理ID失败');
            $return['return_msg'] = '获取经理ID失败';
            $return = $this->pay_lib->array_to_xml($return);
            echo $return;
            exit;
        }
        $token_info = $this->get_token($order_info['manager_idx']);
        if (time() > $token_info['token_expire'] + $token_info['login_ts']) {
            log_message('error', 'token失效'.http_build_query($xmldata));
            $this->pay_lib->log_file('token失效');
            $return['return_msg'] = 'token失效';
            $return = $this->pay_lib->array_to_xml($return);
            echo $return;
            exit;
        }
        //验证金额
        if($data['total_fee'] != $order_info['price']){
            log_message('error', '金额不匹配'.http_build_query($xmldata));
            $this->pay_lib->log_file('金额不匹配');
            $return['return_msg'] = '金额不匹配';
            $return = $this->pay_lib->array_to_xml($return);
            echo $return;
            exit;
        }
        
        //执行发奖
        if($data['result_code'] === 'SUCCESS'){
            $params['tradeStatus'] = 'TRADE_SUCCESS';
        }else{
            $params['tradeStatus'] = 'FALSE';
        }
        $params['partnerOrderId'] = $data['out_trade_no'];
        
        $data = $this->pay_lib->do_callback($params);
        if($data == 'ok'){
            $return['return_code'] = 'SUCCESS';
            $return['return_msg']  = 'OK';
            
        }else{
            log_message('error', '发奖失败'.http_build_query($xmldata));
            $return['return_msg'] = '发奖失败';
        }
        $return = $this->pay_lib->array_to_xml($return);
        echo $return;
        exit;
    }

    /*
     * 支付宝回调接口
     */
    public function alipay_callback()
    {
        $return['return_code'] = 'FAIL';
        
        if($_REQUEST['trade_status'] != 'TRADE_SUCCESS'){
            log_message('error', '通信失败'.http_build_query($_REQUEST));
            echo '通信失败';
            exit;
        }
        //验证签名
        $sign = $this->pay_lib->alipay_check_rsaCheckV1($_REQUEST);
        if(!$sign){
            log_message('error', '签名认证不通过'.http_build_query($_REQUEST));
            echo '签名认证不通过';
            exit;
        }
        
        //验证登录
        $order_info = $this->pay_lib->get_order_info($_REQUEST['out_trade_no']);
        if(!$order_info['manager_idx']){
            log_message('error', 'get_order_info:获取经理ID失败'.http_build_query($_REQUEST));
            echo '获取经理ID失败';
            exit;
        }
        $token_info = $this->get_token($order_info['manager_idx']);
        if (time() > $token_info['token_expire'] + $token_info['login_ts']) {
            echo 'token失效';
            exit;
        }
        //验证金额
        if(($_REQUEST['total_amount'] * 100) != $order_info['price']){
            log_message('error', '金额不匹配'.http_build_query($_REQUEST));
             echo '金额不匹配';
            exit;
        }
        
        //执行发奖
        if($_REQUEST['result_code'] === 'SUCCESS'){
            $params['tradeStatus'] = 'TRADE_SUCCESS';
        }else{
            $params['tradeStatus'] = 'FALSE';
        }
        $params['partnerOrderId'] = $_REQUEST['out_trade_no'];
        
        $data = $this->pay_lib->do_callback($params);
        if($data == 'ok'){
            $return['return_code'] = 'SUCCESS';
            $return['return_msg']  = 'OK';
            
        }else{
            log_message('error', '发奖失败'.http_build_query($_REQUEST));
            $return['return_msg'] = '发奖失败';
        }
        $return = $this->pay_lib->array_to_xml($return);
        echo $return;
        exit;
    }
    
    /**
     * 获取充值包列表
     */
    public function packet_list()
    {
        $params = $this->public_params();
        $this->utility->check_sign($params, $params['sign']);
        $data   = $this->pay_lib->get_packet_list($params);
        $this->output_json_return('success', $data);
    }
    
    /**
     * 获取首充礼包
     */
    public function present_info()
    {
        $params = $this->public_params();
        $this->utility->check_sign($params, $params['sign']);
        $data   = $this->pay_lib->get_present_info($params);
        $this->output_json_return('success', $data);
    }
    
    /**
     * 返回微信openid
     */
    public function get_openid()
    {
        $code  = $this->request_params('code');
        if ($code == '') {
            log_message('error', 'params_err：'.$this->match_lib->ip.',',http_build_query($_REQUEST));
            $this->output_json_return('params_err');
        }
        
        $data = $this->pay_lib->get_wx_openid($code);
        $this->output_json_return('success', $data);
    }
    
    /*
     * 返回jsapi签名signature
     */
    public function jsapi_signature()
    {
        $url  = $this->request_params('url');
        if ($url == '') {
            log_message('error', 'params_err：'.$this->match_lib->ip.',',http_build_query($_REQUEST));
            $this->output_json_return('params_err');
        }
        $data = $this->pay_lib->get_jsapi_signature($url);
        $this->output_json_return('success', $data);
    }
}
