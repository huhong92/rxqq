<?php
class Pay_lib extends Base_lib {
    public function __construct() {
        parent::__construct();
        $this->load_model('pay_model');
        $this->load_model('court_model');
    }
    
    /*
     * 记录中九回调链接到文件
     */
    public function log_file($params)
    {
        // 插入测试表
        $table  = 'sheet11';
        $data   = array(
            'idx'       => 1,
            'module_no' => 100,
            'module'    => 100,
            'achieve_catno' => 100,
            'achieve_cat'    => 100,
            'cat_name' => 100,
            'achieve_no'    => 100,
            'condition' => 100,
            'achievepoint'    => 100,
            'descript' => 100,
            'euro'    => 100,
            'tickets' => 100,
            'soccer_soul'    => 100,
            'powder' => 100,
            'prop_info'    => 100,
            'gem_info' => 100,
            'player_info'    => 100,
            'equipt_info' => 100,
            'status'    => 100,
            'time' => time(),
            'update_time'    => 100,
        );
        $id = $this->CI->pay_model->insert_data($data ,$table);
        //记录回调参数到文件
        $src = $this->CI->passport->get('match_result_file');
        $file_name = $src.'pay_callback_test.txt'.time();
        $info = $params;
        file_put_contents($file_name, $info."____1111");
        $re = file_put_contents($file_name, $info."____1111__".$id);var_dump($re);var_dump(111);
        return $re;
    }
    

    /*
     * 微信第三方登录
     */
    public function get_wx_openid($code)
    {
        //获取用户openid
        $fields = array(
            'appid'      => $this->CI->passport->get('appid'),  //应用唯一标识
            'secret'     => $this->CI->passport->get('secret'), //应用密钥AppSecret，在微信开放平台提交应用审核通过后获得
            'code'       => $code,                              //code参数
            'grant_type' => 'authorization_code',               //应用授权作用域，拥有多个作用域用逗号（,）分隔，网页应用目前仅填写snsapi_login即可
        );
        $user_info = $this->CI->utility->curl_get('https://api.weixin.qq.com/sns/oauth2/access_token' , $fields);
        if(!$user_info){
            log_message('error', 'get_wx_oprnid:'.$this->ip.',获取微信授权用户openid失败');
            $this->CI->output_json_return('get_wx_oprnid');
        }
        $user_info = json_decode($user_info , TRUE);
        return $user_info;
        
    }
    
    /*
     * 获取jsapi签名signature
     */
    public function get_jsapi_signature($url)
    {
        //获取微信号调用凭据access_token
        $src = $this->CI->passport->get('match_result_file');
        $file_name = $src.'access_token.txt';
        $access_token = '';
        //获取缓存的access_token
        if(file_exists($file_name)){
            $token_info = file_get_contents($file_name);
            $token_info = json_decode($token_info , TRUE);
            if($token_info['expires_in'] && ($token_info['expires_in'] + $token_info['time'] >= time())){
                $access_token = $token_info['access_token'];
            }
        }
        //重新获取access_token
        if($access_token == ''){
           $access_token = $this->get_jsapi_access_token();
           $access_token = json_decode($access_token , TRUE);
           $access_token = $access_token['access_token'];
        }
        
        $jsapi_ticket = '';
        //获取缓存的jsapi_ticket
        $file_name = $src.'jsapi_ticket.txt';
        if(file_exists($file_name)){
            $ticket = file_get_contents($file_name);
            $ticket = json_decode($ticket , TRUE);
            if($ticket['expires_in'] && ($ticket['expires_in'] + $ticket['time'] >= time())){
                $jsapi_ticket = $ticket['ticket'];
            }
        }
        //重新获取jsapi_ticket
        if($jsapi_ticket == ''){
           $jsapi_ticket = $this->get_jsapi_ticket($access_token);
           $jsapi_ticket = json_decode($jsapi_ticket , TRUE);
           $jsapi_ticket = $jsapi_ticket['ticket'];
        }
        
        //生成随机字符串
        $chars = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789';
        $rand_str = ''; 
        for ( $i = 0; $i < 10; $i++ ){
            $rand_str.= $chars[mt_rand(0, strlen($chars) - 1)];
        }
        //生成JS-SDK签名
        $sign_array = array(
            'noncestr'     =>  $rand_str,      //随机字符串
            'jsapi_ticket' =>  $jsapi_ticket,  //jsapi_ticket
            'timestamp'    =>  time(),         //时间戳
            'url'          =>  $url,           //url
        );
        //排序
        ksort($sign_array);
        //生成字符串
        foreach ($sign_array as $key => $val){
            $sign_str .= $key.'='.$val.'&';
        }
        $sign_str = substr($sign_str,0,strlen($sign_str)-1);
        //sha1加密
        $sign_array['sign'] = sha1($sign_str);
        return $sign_array;
    }
    
    /*
     * 获取access_token
     */
    public function get_jsapi_access_token()
    {
        $fields = array(
            'grant_type' => 'client_credential',                //获取access_token填写client_credential
            'appid'      => $this->CI->passport->get('appid'),  //应用唯一标识
            'secret'     => $this->CI->passport->get('secret'), //应用密钥AppSecret，在微信开放平台提交应用审核通过后获得
        );
        $token_info = $this->CI->utility->curl_get('https://api.weixin.qq.com/cgi-bin/token' , $fields);
        if(!$token_info){
            log_message('error', 'get_access_token:'.$this->ip.',获取access_token失败');
            $this->CI->output_json_return('get_access_token');
        }
        $token_info = json_decode($token_info , TRUE);
        $token_info['time'] = time();
        $token_info = json_encode($token_info);
        $src = $this->CI->passport->get('match_result_file');
        $file_name = $src.'access_token.txt';
        if(file_exists($file_name)){
            unlink($file_name);
        }
        file_put_contents($file_name, $token_info);
        return $token_info;
    }
    
    /*
     * 获取jsapi_ticket
     */
    public function get_jsapi_ticket($access_token)
    {
        $fields = array(
            'access_token' => $access_token, 
            'type'         => 'jsapi',  
        );
        $ticket = $this->CI->utility->curl_get('https://api.weixin.qq.com/cgi-bin/ticket/getticket' , $fields);
        if(!$ticket){
            log_message('error', 'get_jsapi_ticket:'.$this->ip.',获取jsapi_ticket失败');
            $this->CI->output_json_return('get_jsapi_ticket');
        }
        $ticket = json_decode($ticket , TRUE);
        $ticket['time'] = time();
        $ticket = json_encode($ticket);
        $src = $this->CI->passport->get('match_result_file');
        $file_name = $src.'jsapi_ticket.txt';
        if(file_exists($file_name)){
            unlink($file_name);
        }
        file_put_contents($file_name, $ticket);
        return $ticket;
    }

    /*
     * 生成订单，并返回客户端参数
     */
    public function get_pay_params($params)
    {
        //生成订单
        $insert_order['manager_idx'] = $params['uuid'];
        $insert_order['order_no']    = $this->generate_order_id($params['uuid']);
        $insert_order['type']        = $params['type'];
        $insert_order['o_status']    = 3;
        $insert_order['platform']    = $params['platform'];
        $insert_order['status']      = 1;
        //球票直充
        if($insert_order['type'] == 1){
            $insert_order['goods_id']   = 100001;
            $insert_order['goods_name'] = '直充'.$params['num'].'球票';
            $rmb_rate = $this->CI->passport->get('rmb_rate');
            $insert_order['price']    = ($params['num'] / $rmb_rate) * 100 ;//换算为所需人民币（分）
            $insert_order['descript'] = '经理'.$params['uuid'].$insert_order['goods_name'];
        }else if($insert_order['type'] == 2){//充值包
            //充值包详情
            $where = "idx = {$params['id']} AND status = 1";
            $recharge_pack_info = $this->CI->pay_model->get_one($where , 'recharge_conf');
            if(!$recharge_pack_info){
                log_message('error', 'get_recharge_pack_info:'.$this->ip.',获取充值包信息失败');
                $this->CI->output_json_return('get_recharge_pack_info');
            }
            $insert_order['goods_id']   = $params['id'];
            $insert_order['goods_name'] = '购买'.$params['id'].'号充值包';
            $insert_order['price']      = $recharge_pack_info['price'] ;//人民币（分）
            $insert_order['descript']   = '经理'.$params['uuid'].$insert_order['goods_name'];
        }else if($insert_order['type'] == 3){//球员卡一键升阶
            //获取球员卡详情
            $options['where']   = "A.idx =".$params['id'] ." AND A.status = 1 AND B.status = 1";
            $options['select']  = "A.idx AS id , A.player_no AS player_no , A.level AS level , B.quality AS quality";
            $p_info = $this->CI->court_model->get_player_info($options);
            if(!$p_info){
                log_message('error', 'player_not_exist:'.$this->ip.',获取球员卡信息失败');
                $this->CI->output_json_return('player_not_exist');
            }
            if ($p_info['level'] == 9) {
                $this->CI->output_json_return('highest_level_err');
            }
            $uplevel = $p_info['level'] + 1;
            //升阶所需人民币详情
            $where = "type = 1 AND level = $uplevel AND quality = {$p_info['quality']} AND status = 1";
            $upgrade_conf_info = $this->CI->pay_model->get_one($where , 'price_conf');
            if(!$upgrade_conf_info){
                log_message('error', 'get_upgrade_conf_info:'.$this->ip.',获取升阶信息失败');
                $this->CI->output_json_return('get_upgrade_conf_info');
            }
            $insert_order['goods_id']   = $params['id'];
            $insert_order['goods_name'] = '球员'.$p_info['player_no'].'升至'.$uplevel.'阶';
            $insert_order['price']      = $upgrade_conf_info['price'] ;//换算为所需人民币（分）
            $insert_order['descript']   = '经理'.$params['uuid'].$insert_order['goods_name'];
        }else if($insert_order['type'] == 4){//装备一键升阶
            //获取装备详情
            $select = "A.idx as id,B.equipt_no AS equipt_no, B.level AS level , B.quality AS quality";
            $sql    = "select ".$select." from equipt AS A JOIN equipt_conf AS B ON A.equipt_no=B.equipt_no AND A.level = B.level  WHERE A.idx = ".$params['id']." AND A.status = 1 AND B.status = 1";
            $equipt_info = $this->CI->court_model->fetch($sql, 'row');
            if(!$equipt_info){
                log_message('info', 'equipt_empty_data:'.$this->ip.',未查询到装备数据');
                $this->CI->output_json_return('equipt_empty_data');
            }
            if ($equipt_info['level'] == 20) {// 刚装备已经是最高level
                log_message('error', 'upgrade_equipt：'.$this->ip.',当前装备已经是最高level,不能再升级');
                $this->CI->output_json_return('equipt_highest_level');
            }
            $uplevel = $equipt_info['level'] + 1;
            //升阶所需人民币详情
            $where = "type = 2 AND level = $uplevel AND status = 1";
            $upgrade_conf_info = $this->CI->pay_model->get_one($where , 'price_conf');
            if(!$upgrade_conf_info){
                log_message('error', 'get_upgrade_conf_info:'.$this->ip.',获取升阶信息失败');
                $this->CI->output_json_return('get_upgrade_conf_info');
            }
            $insert_order['goods_id']   = $params['id'];
            $insert_order['goods_name'] = '装备'.$equipt_info['equipt_no'].'升至'.$uplevel.'阶';
            $insert_order['price']      = $upgrade_conf_info['price'] ;//换算为所需人民币（分）
            $insert_order['descript']   = '经理'.$params['uuid'].$insert_order['goods_name'];
        }else if($insert_order['type'] == 5){//购买商城礼包
            //获取礼包详情
            $where = "idx = ".$params['id']." AND status = 1";
            $package_info = $this->CI->pay_model->get_one($where , 'package_conf');
            $insert_order['goods_id']   = $params['id'];
            $insert_order['goods_name'] = '购买'.$params['id'].'号礼包';
            $insert_order['price']      = $package_info['price'] ;
            $insert_order['descript']   = '经理'.$params['uuid'].$insert_order['goods_name'];
        }
        //插入订单
        $this->CI->pay_model->start();
        $order_id = $this->CI->pay_model->insert_data($insert_order , 'order');
        if(!$order_id){
            $this->CI->pay_model->error();
            log_message('error', 'insert_order_err:'.$this->ip.',插入订单信息失败');
            $this->CI->output_json_return('insert_order_err');
        }
        
        if($params['platform'] == 1){ //中九
            $data = $this->z9sdk($order_id, $insert_order);
        }
        else if ($params['platform'] == 2){//微信
            $data = $this->wechat($order_id , $insert_order , $params['openid']);
            if(!$data){
                $this->CI->pay_model->error();
                log_message('error', 'wechat_pay_err:'.$this->ip.',请求生成微信订单失败');
                $this->CI->output_json_return('wechat_pay_err');
            }
        }
        else{//支付宝
            $data = $this->alipay($order_id,$insert_order);
        }
        $this->CI->pay_model->success();
        return $data;
    }
    
    /*
     * 中九支付
     */
    public function z9sdk($order_id , $insert_order)
    {
        //生成返回数组
        $return['subject']        = $insert_order['goods_name'];
        $return['order_id']       = $order_id;
        $return['total_fee']      = $insert_order['price'];
        $return['gamename']       = '热血球球';
        $return['productid']      = $insert_order['goods_id'];
        $return['productname']    = $insert_order['goods_name'];
        $return['body']           = $insert_order['descript'];
        $return['partnerorderid'] = $insert_order['order_no'];
        $return['notifyurl']      = $this->CI->passport->get('pay_callback').'pay/z9sdk_callback';
        return $return;
    }

    /*
     * 微信
     */
    public function wechat($order_id , $insert_order , $openid)
    {
        $secret_key                 = $this->CI->passport->get('wx_key'); // 平台秘钥(不是appsecret)
        $params['appid']            = $this->CI->passport->get('appid');
        $params['mch_id']           = $this->CI->passport->get('mchid');
        $params['nonce_str']        = md5(time().mt_rand()); // 生成随机数 md5(time().mt_rand())

        $params['body']             = $insert_order['goods_name']; // 商品名称 代替 商品描述
        $params['out_trade_no']     = $insert_order['order_no'];// 商品订单号
        $params['total_fee']        = $insert_order['price']; // 总金额
        $params['spbill_create_ip'] = $_SERVER["REMOTE_ADDR"];// 客户端ip
        $params['notify_url']       = $this->CI->passport->get('pay_callback').'pay/wechat_callback'; // 接收微信支付异步通知回调地址
        $params['trade_type']       = 'JSAPI'; // 交易类型 JSAPI，NATIVE，APP
        $params['fee_type']         = 'CNY'; // 可选
        $params['openid']           = $openid; // 用户标识
        $url                = 'https://api.mch.weixin.qq.com/pay/unifiedorder'; // 微信请求URL
        $sign_params        = $this->CI->utility->get_sign_params($params); // 校验参数（需要sign校验的参数）
        $key                = $sign_params."&key=".$secret_key; // 秘钥
        $params['sign']     = strtoupper(md5($key));
        // 将数组转为xml格式
        $xml        = $this->array_to_xml($params);
        $content    = $this->CI->utility->wx_post($url, $xml); // 微信统一下单，返回结果
        
        // 将xml转为数组
        $res        = $this->xml_to_array($content);
        if ($res['return_code'] != 'SUCCESS') { // 请求成功
            return FALSE;
        }
        // 生成sign
        $sign_arr['appId']       = $res['appid'];
        $sign_arr['nonceStr']    = $res['nonce_str'];
        $sign_arr['package']     = "prepay_id=".$res['prepay_id'];
        $sign_arr['signType']    = 'MD5';
        $sign_arr['timeStamp']   = time();
        $sign_str          = $this->CI->utility->get_sign_params($sign_arr); // 校验参数（需要sign校验的参数）
        $ret['sign']       = strtoupper(md5($sign_str."&key=".$secret_key));
        $ret['noncestr']   = $res['nonce_str'];
        $ret['timestamp']  = $sign_arr['timeStamp'];
        $ret['package']    = $sign_arr['package'];
        $ret['prepay_id']  = $res['prepay_id'];
        return $ret;
    }

    /*
     * 支付宝
     */
    public function alipay($order_id , $order_info)
    {
        
        //实例化支付宝SDK类
        include("AopSdk.php");
        $aop = new AopClient ();
        //设置公共参数
        $aop->gatewayUrl = 'https://openapi.alipay.com/gateway.do';
        $aop->appId = '111222333';
        $aop->rsaPrivateKey = 'aaabbbccc';
        $aop->alipayrsaPublicKey='cccbbbaaa';
        $aop->apiVersion = '1.0';
        $aop->postCharset='UTF-8';
        $aop->format='json';
        $aop->notifyUrl = $this->CI->passport->get('pay_callback').'pay/alipay_callback';
        $aop->signType='RSA2';
        $request = new AlipayTradeWapPayRequest();
        //设置请求参数
        $pay_params_arr = array(
            'body'         => $order_info['descript'],     //对一笔交易的具体描述信息。如果是多种商品，请将商品描述字符串累加传给body。
            'subject'      => $order_info['goods_name'],   //商品的标题/交易标题/订单标题/订单关键字等。
            'out_trade_no' => $order_id,                   //商户网站唯一订单号
            'total_amount' => $order_info['price'] / 100,  //订单总金额，单位为元，精确到小数点后两位，取值范围[0.01,100000000]
            'product_code' => 'asdsadasdsa',               //销售产品码，商家和支付宝签约的产品码
        );
        //转化为字符串 格式:{"key":"value","key":"value"}
        $pay_params_str = '{';
        foreach ($pay_params_arr as $k => $v){
            $pay_params_str .= '"'.$k.'":"'.$v.'",';
        }
        $pay_params_arr = substr($pay_params_str, 0, -1);
        $pay_params_arr .= '}';
       // var_dump($pay_params_arr);
        $request->setBizContent($pay_params_str);
        
        $result = $aop->pageExecute($request); 
        echo $result;
        exit;
    }

    //支付宝验证签名
    public function alipay_check_rsaCheckV1($params)
    {
        //实例化支付宝SDK类
        include("AopSdk.php");
        $aop = new AopClient ();
        $aop->alipayrsaPublicKey='cccbbbaaa'; //初始化设置支付宝公钥
        $status = $aop->rsaCheckV1($params , '');
        return $status;
    }


    /**
     * 生成订单编号
     */
    public function generate_order_id($uuid)
    {
        return "RXQQ".uniqid().rand(10, 99);
    }
    
    //执行回调操作[回调原方法-备份20170428]
    public function do_callback_($params)
    {
        $success = $params['tradeStatus'];
        //通过验证 执行发奖
        if($success == 'TRADE_SUCCESS'){
            //查询订单详情
            $where = "order_no = '{$params['partnerOrderId']}' AND o_status = 3 AND status = 1";
            $order_info = $this->CI->pay_model->get_one($where , 'order');
            if(!$order_info){
                log_message('error', '查询订单详情失败'.http_build_query($_REQUEST));
                return 'FALSE';
            }
            $this->CI->pay_model->start();
            //发放直充球票
            if($order_info['type'] == 1){
                $rmb_rate = $this->CI->passport->get('rmb_rate');
                $tickets = ($order_info['price'] / 100) * $rmb_rate;
                $res = $this->add_tickets($order_info['manager_idx'], $tickets);
                if(!$res){
                    $this->CI->pay_model->error();
                    log_message('error', '增加经理球票失败'.http_build_query($_REQUEST));
                    //修改订单状态为失败
                    $this->change_order_status($order_info['idx'], 2);
                    return 'FALSE';
                }
                $log_arr['manager_idx'] = $order_info['manager_idx'];
                $log_arr['rmb']         = $order_info['price'];
                $log_arr['good_id']     = 100001;
                $log_arr['level']       = 0;
                $log_arr['platform']    = $order_info['platform'];
                $log_arr['tickets']     = $tickets;
                $log_arr['type']        = $order_info['type'];
            }else if($order_info['type'] == 2){//发放充值包球票
                //查询充值包详情
                $where = "idx = {$order_info['goods_id']} AND status = 1";
                $recharge_pack_info = $this->CI->pay_model->get_one($where , 'recharge_conf');
                if(!$recharge_pack_info){
                    log_message('error', '查询充值包详情失败'.http_build_query($_REQUEST));
                    $this->CI->pay_model->error();
                    //修改订单状态为失败
                    $this->change_order_status($order_info['idx'], 2);
                    return 'FALSE';
                }
                //月卡
                if($recharge_pack_info['type'] == 1){
                    $tickets = 60;
                    //一个月内不能重复购买月卡 
                    $sql = "SELECT time FROM recharge_his WHERE manager_idx = {$order_info['manager_idx']} AND `status` = 1 AND type = 2 AND good_id = 1 ORDER BY time DESC LIMIT 1";
                    $res = $this->CI->pay_model->fetch($sql , 'row');
                    if($res){
                        //如果天数不够月卡周期（30天）
                        if((strtotime(date('Y-m-d' , time())) - strtotime(date('Y-m-d' , $res['time']))) < (86400 * 30)){
                            $this->CI->pay_model->error();
                            log_message('error', '不能重复购买月卡'.http_build_query($_REQUEST));
                            //修改订单状态为失败
                            $this->change_order_status($order_info['idx'], 2);
                            return 'FALSE';
                        }
                    }
                    //发放球票
                    $res = $this->add_tickets($order_info['manager_idx'], $tickets);
                    if(!$res){
                        $this->CI->pay_model->error();
                        log_message('error', '增加经理球票失败'.http_build_query($_REQUEST));
                        //修改订单状态为失败
                        $this->change_order_status($order_info['idx'], 2);
                        return 'FALSE';
                    }
                    //发放邮件通知
                    $ins_data['sender_id']         = 1;
                    $ins_data['sender_name']       = 'admin';
                    $ins_data['manager_idx']       = $order_info['manager_idx'];
                    $ins_data['title']             = '月卡奖励';
                    $ins_data['content']           = '月卡奖励球票已发放';
                    $ins_data['link']              = '';
                    $ins_data['is_accessory']      = 0;
                    $ins_data['accessory_type']    = 0;
                    $ins_data['accessory_content'] = '';
                    $ins_data['status']            = 1;
                    $res = $this->CI->pay_model->insert_data($ins_data , 'mail_conf');
                    if(!$res){
                        $this->CI->pay_model->error();
                        log_message('error', '月卡奖励提示邮件发送失败'.http_build_query($_REQUEST));
                        //修改订单状态为失败
                        $this->change_order_status($order_info['idx'], 2);
                        return 'FALSE';
                    }
                }else{//充值包
                    $tickets = $recharge_pack_info['tickets'];
                    //是否首次购买此充值包
                    $where = "type = 2 AND good_id = {$order_info['goods_id']} AND manager_idx = {$order_info['manager_idx']} AND status = 1";
                    $res = $this->CI->pay_model->get_one($where , 'recharge_his');
                    if(!$res){
                        $tickets = $recharge_pack_info['tickets'] + $recharge_pack_info['present'];
                        $log_arr['present_tickets'] = $recharge_pack_info['present'];
                    }
                    //发放球票
                    $res = $this->add_tickets($order_info['manager_idx'], $tickets);
                    if(!$res){
                        $this->CI->pay_model->error();
                        log_message('error', '增加经理球票失败'.http_build_query($_REQUEST));
                        //修改订单状态为失败
                        $this->change_order_status($order_info['idx'], 2);
                        return 'FALSE';
                    }
                }
                //充值记录
                $log_arr['manager_idx'] = $order_info['manager_idx'];
                $log_arr['rmb']         = $order_info['price'];
                $log_arr['good_id']     = $order_info['goods_id'];
                $log_arr['tickets']     = $tickets;
                $log_arr['level']       = 0;
                $log_arr['platform']    = $order_info['platform'];
                $log_arr['type']        = $order_info['type'];
            }else if($order_info['type'] == 3){//球员卡升阶
                //获取球员卡详情
                $this->load_library('court_lib');
                $p_id['id'] = $order_info['goods_id'];
                $p_info = $this->CI->court_lib->get_player_info($p_id);
                if(!$p_info){
                    log_message('error', '未找到球员卡'.http_build_query($_REQUEST));
                    //修改订单状态为失败
                    $this->change_order_status($order_info['idx'], 2);
                    return 'FALSE';
                }
                if ($p_info['level'] == 9) {
                    log_message('error', '球员已经达到最高阶'.http_build_query($_REQUEST));
                    //修改订单状态为失败
                    $this->change_order_status($order_info['idx'], 2);
                    return 'FALSE';
                }
                // 升级球员卡 -- 升级属性值、球员卡level、技能level
                $player_info = $this->CI->utility->recombine_attr($p_info);
                $fields      = $this->CI->utility->attribute_change($player_info['attribute']);
                $fields['level']    = $p_info['level']+1;
                if ($p_info['generalskill_no'] && $p_info['generalskill_level'] < 5) {
                    $fields['generalskill_level']   = $p_info['generalskill_level']+1;
                }
                $where  = array('idx'=>$order_info['goods_id'], 'manager_idx'=>$order_info['manager_idx'], 'status'=>1);
                $res = $this->CI->court_lib->update_player_info($fields, $where);
                if (!$res) {
                    $this->CI->pay_model->error();
                    log_message('error', '球员进阶失败'.http_build_query($_REQUEST));
                    //修改订单状态为失败
                    $this->change_order_status($order_info['idx'], 2);
                    return 'FALSE';
                }
                // 球员进阶历史记录
                $data = array(
                    'manager_idx'       => $order_info['manager_idx'],
                    'player_idx'        => $order_info['goods_id'],
                    'quality'           => $p_info['quality'],
                    'level'             => $p_info['level'],
                    'curr_level'        => $p_info['level']+1,
                    'prop'              => $order_info['price'].'分 RMB',
                    'status'            => 1,
                );
                $res = $this->CI->court_lib->player_upgrade_history($data);
                if (!$res) {
                    $this->CI->pay_model->error();
                    log_message('error', '球员进阶历史记录失败'.http_build_query($_REQUEST));
                    //修改订单状态为失败
                    $this->change_order_status($order_info['idx'], 2);
                    return 'FALSE';
                }
                //充值记录
                $log_arr['manager_idx'] = $order_info['manager_idx'];
                $log_arr['rmb']         = $order_info['price'];
                $log_arr['good_id']     = $order_info['goods_id'];
                $log_arr['level']       = $fields['level'];
                $log_arr['platform']    = $order_info['platform'];
                $log_arr['tickets']     = 0;
                $log_arr['type']        = $order_info['type'];
                //触发任务 升级球员
                $this->CI->utility->get_task_status($order_info['manager_idx'], 'player_upgrade');
                $this->load_library('task_lib');
                // 触发成就 - 进阶达人
                $this->CI->task_lib->player_upgrade($order_info['manager_idx']);
                // 触发成就 - 培养大师
                $this->CI->task_lib->player_update($order_info['manager_idx']);
                $this->CI->court_model->success();
            }else if($order_info['type'] == 4){//装备升阶
                $this->load_library('court_lib');
                // 获取装备信息
                $e_id['id'] = $order_info['goods_id'];
                $equipt_info = $this->CI->court_lib->equipt_info($e_id);
                if(!$equipt_info){
                    //修改订单状态为失败
                    $this->change_order_status($order_info['idx'], 2);
                    return 'FALSE';
                }
                if ($equipt_info['level'] == 20) {// 装备已经是最高level
                    //修改订单状态为失败
                    $this->change_order_status($order_info['idx'], 2);
                    return 'FALSE';
                }
                $data   = array('level' => $equipt_info['level'] +1);
                $where  = array('idx' => $order_info['goods_id'], 'status'=>1);
                $upt_res = $this->CI->court_model->update_data($data, $where, 'equipt');
                if (!$upt_res) {
                    $this->CI->pay_model->error();
                    log_message('error', '装备升级|强化失败'.http_build_query($_REQUEST));
                    //修改订单状态为失败
                    $this->change_order_status($order_info['idx'], 2);
                    return 'FALSE';
                }
                // 装备升级|强化历史记录
                $ist_data   = array(
                    'manager_idx'   => $order_info['manager_idx'],
                    'manager_name'  => $equipt_info['manager_name'],
                    'equipt_no'     => $equipt_info['equipt_no'],
                    'name'          => $equipt_info['name'],
                    'type'          => 1,
                    'equipt_type'   => $equipt_info['type'],
                    'level'         => $equipt_info['level']+1,
                    'euro'          => 0,
                    'junior_card'   => 0,
                    'middle_card'   => 0,
                    'senio_card'    => 0,
                    'status'        => 1
                );
                $ist_res = $this->CI->court_model->insert_data($ist_data, 'eupgrade_his');
                if (!$ist_res) {
                    log_message('error', '装备升级|强化历史记录失败'.http_build_query($_REQUEST));
                    $this->CI->pay_model->error();
                    //修改订单状态为失败
                    $this->change_order_status($order_info['idx'], 2);
                    return 'FALSE';
                }
                //充值记录
                $log_arr['manager_idx'] = $order_info['manager_idx'];
                $log_arr['rmb']         = $order_info['price'];
                $log_arr['good_id']     = $order_info['goods_id'];
                $log_arr['level']       = $data['level'];
                $log_arr['platform']    = $order_info['platform'];
                $log_arr['tickets']     = 0;
                $log_arr['type']        = $order_info['type'];
                
                // 触发成就 - 战斗力
                $this->load_library('task_lib');
                $this->CI->task_lib->achieve_fighting($order_info['manager_idx']);
                //触发任务 升级装备
                $this->CI->utility->get_task_status($order_info['manager_idx'] , 'upgrade_equipt');
            }else if($order_info['type'] == 5){//购买礼包
            $this->CI->pay_model->success();
                //获取礼包详情
                $where = "idx = ".$order_info['goods_id']." AND status = 1";
                $package_info = $this->CI->pay_model->get_one($where , 'package_conf');
                if(!$package_info){
                    //修改订单状态为失败
                    $this->change_order_status($order_info['idx'], 2);
                    return 'FALSE';
                }
                //发放礼包物品
                $prop_info['prop_info'] = $package_info['prop'];
                $prop_info = $this->CI->utility->get_reward($prop_info);
                
                foreach ($prop_info['prop_info'] as $key => $value){
                    $insert['uuid']    = $order_info['manager_idx'];
                    $insert['prop_no'] = $value['prop_no'];
                    $res = $this->CI->utility->insert_prop_info($insert , $value['num']);
                    if(!$res){
                        log_message('error', '增加经理球票失败'.http_build_query($_REQUEST));
                        $this->CI->pay_model->error();
                        //修改订单状态为失败
                        $this->change_order_status($order_info['idx'], 2);
                        return 'FALSE';
                    }
                }
                //充值记录
                $log_arr['manager_idx'] = $order_info['manager_idx'];
                $log_arr['rmb']         = $order_info['price'];
                $log_arr['good_id']     = $order_info['goods_id'];
                $log_arr['level']       = 0;
                $log_arr['platform']    = $order_info['platform'];
                $log_arr['tickets']     = 0;
                $log_arr['type']        = $order_info['type'];
                $log_arr['prop']        = $package_info['prop'];
            }
            //发放首冲礼包
            $res = $this->first_pay($order_info['manager_idx']);
            if($res != 'ok'){
                log_message('error', '发放首冲礼包失败'.http_build_query($_REQUEST));
                $this->CI->pay_model->error();
                //修改订单状态为失败
                $this->change_order_status($order_info['idx'], 2);
                return 'FALSE';
            }
            //修改订单状态为成功
            $res = $this->change_order_status($order_info['idx'], 1 , $log_arr);
            if($res != 'ok'){
                log_message('error', '修改订单状态失败'.http_build_query($_REQUEST));
                $this->CI->pay_model->error();
                //修改订单状态为失败
                $this->change_order_status($order_info['idx'], 2);
                return 'FALSE';
            }
            $this->CI->pay_model->success();
            return $res;
        }
        else{
            //修改订单状态为失败
            log_message('error', '订单失败'.http_build_query($_REQUEST));
            $this->change_order_status($params['partnerOrderId'], 2);
            return 'FALSE';
        }
    }
    
    
    /**
     * 支付回调操作
     * @param type $params
     * @return string
     */
    public function do_callback($params)
    {
        //查询订单详情
        $where = "order_no = '{$params['partnerOrderId']}' AND o_status = 3 AND status = 1";
        $order_info = $this->CI->pay_model->get_one($where , 'order');
        if(!$order_info){
            log_message('error', '查询订单详情失败'.http_build_query($params));
            return 'FALSE';
        }
        
        $success = $params['tradeStatus'];
        // 用户支付失败---处理逻辑
        if($success != 'TRADE_SUCCESS'){
            log_message('error', 'do_callback:订单失败'.http_build_query($params));
            // 修改订单状态
            $u_order    = $this->update_order_status($params['partnerOrderId'], 2);
            if (!$u_order) {
                log_message('error', 'do_callback:修改订单状态失败'.http_build_query($params));
                return 'FALSE';
            }
            // 插入订单充值历史记录
            $log_arr['manager_idx']     = $order_info['manager_idx'];
            $log_arr['type']            = $order_info['type'];
            $log_arr['rmb']             = $order_info['price'];
            $log_arr['good_id']         = $order_info['goods_id'];
            $log_arr['level']           = (int)$level;
            $log_arr['tickets']         = (int)$tickets;
            $log_arr['present_tickets'] = (int)$present_tickets;
            $log_arr['platform']        = $order_info['platform'];
            $log_arr['status']          = 0;
            $i_ohis = $this->insert_recharge_his($log_arr);
            if (!$i_ohis) {
                log_message('error', 'do_callback:修改订单状态失败'.http_build_query($params));
                return 'FALSE';
            }
            return 'ok';
        }
        
        // 用户支付成功---处理逻辑
        $this->CI->pay_model->start();
        // 拼接数据
        if ($order_info['type'] == 1) {// 球票直充
            $rmb_rate               = $this->CI->passport->get('rmb_rate');
            $tickets                = ($order_info['price'] / 100) * $rmb_rate;
            $order_info['goods_id'] = '100001';
        } elseif($order_info['type'] == 2){// 球票充值包
            //查询充值包详情
            $where              = "idx = {$order_info['goods_id']} AND status = 1";
            $recharge_pack_info = $this->CI->pay_model->get_one($where , 'recharge_conf');
            if(!$recharge_pack_info){
                log_message('error', 'do_callback：查询充值包详情失败'.http_build_query($params));
                $this->CI->pay_model->error();
                return 'FALSE';
            }
            $tickets    = $recharge_pack_info['tickets'];
            if($recharge_pack_info['type'] == 2){// 球票充值包
                //是否首次购买此充值包
                $where = "type = 2 AND good_id = {$order_info['goods_id']} AND manager_idx = {$order_info['manager_idx']} AND status = 1";
                $res = $this->CI->pay_model->get_one($where , 'recharge_his');
                if(!$res){
                    $present_tickets    = $recharge_pack_info['present'];
                }
            }
        }elseif($order_info['type'] == 3){ //球员卡升阶
            //获取球员卡详情
            $this->load_library('court_lib');
            $p_id['id'] = $order_info['goods_id'];
            $p_info     = $this->CI->court_lib->get_player_info($p_id);
            if(!$p_info){
                $this->CI->pay_model->error();
                log_message('error', 'do_callback:未找到球员卡'.http_build_query($params));
                return 'FALSE';
            }
            if ($p_info['level'] < 9) {
                // 升级球员卡 -- 升级属性值、球员卡level、技能level
                $player_info    = $this->CI->utility->recombine_attr($p_info);
                $fields         = $this->CI->utility->attribute_change($player_info['attribute']);
                $level          = $p_info['level'] +1;
                $fields['level']    = $level;
                if ($p_info['generalskill_no'] && $p_info['generalskill_level'] < 5) {
                    $fields['generalskill_level']   = $p_info['generalskill_level']+1;
                }
                $where  = array('idx'=>$order_info['goods_id'], 'manager_idx'=>$order_info['manager_idx'], 'status'=>1);
                $res = $this->CI->court_lib->update_player_info($fields, $where);
                if (!$res) {
                    $this->CI->pay_model->error();
                    log_message('error', 'do_callback:球员进阶失败'.http_build_query($params));
                    return 'FALSE';
                }
                // 球员进阶历史记录
                $data = array(
                    'manager_idx'       => $order_info['manager_idx'],
                    'player_idx'        => $order_info['goods_id'],
                    'quality'           => $p_info['quality'],
                    'level'             => $p_info['level'],
                    'curr_level'        => $p_info['level']+1,
                    'prop'              => $order_info['price'].'分 RMB',
                    'status'            => 1,
                );
                $res = $this->CI->court_lib->player_upgrade_history($data);
                if (!$res) {
                    $this->CI->pay_model->error();
                    log_message('error', '球员进阶历史记录失败'.http_build_query($params));
                    return 'FALSE';
                }
                //触发任务 升级球员
                $this->CI->utility->get_task_status($order_info['manager_idx'], 'player_upgrade');
                $this->load_library('task_lib');
                // 触发成就 - 进阶达人
                $this->CI->task_lib->player_upgrade($order_info['manager_idx']);
                // 触发成就 - 培养大师
                $this->CI->task_lib->player_update($order_info['manager_idx']);
            } 
        }else if($order_info['type'] == 4){//装备升阶
            $this->load_library('court_lib');
            // 获取装备信息
            $e_id['id']     = $order_info['goods_id'];
            $equipt_info    = $this->CI->court_lib->equipt_info($e_id);
            if(!$equipt_info){
                $this->CI->pay_model->error();
                log_message('error', 'do_callback:未找到装备'.http_build_query($params));
                return 'FALSE';
            }
            if ($equipt_info['level'] < 20) {// 装备已经是最高level
                $level  = $equipt_info['level'] +1;
                $data   = array('level' => $level);
                $where  = array('idx' => $order_info['goods_id'], 'status'=>1);
                $upt_res = $this->CI->court_model->update_data($data, $where, 'equipt');
                if (!$upt_res) {
                    $this->CI->pay_model->error();
                    log_message('error', 'do_callback：装备升级|强化失败'.http_build_query($params));
                    return 'FALSE';
                }
                // 装备升级|强化历史记录
                $ist_data   = array(
                    'manager_idx'   => $order_info['manager_idx'],
                    'manager_name'  => $equipt_info['manager_name'],
                    'equipt_no'     => $equipt_info['equipt_no'],
                    'name'          => $equipt_info['name'],
                    'type'          => 1,
                    'equipt_type'   => $equipt_info['type'],
                    'level'         => $equipt_info['level']+1,
                    'euro'          => 0,
                    'junior_card'   => 0,
                    'middle_card'   => 0,
                    'senio_card'    => 0,
                    'status'        => 1
                );
                $ist_res = $this->CI->court_model->insert_data($ist_data, 'eupgrade_his');
                if (!$ist_res) {
                    log_message('error', 'do_callback:装备升级|强化历史记录失败'.http_build_query($params));
                    $this->CI->pay_model->error();
                    return 'FALSE';
                }
                // 触发成就 - 战斗力
                $this->load_library('task_lib');
                $this->CI->task_lib->achieve_fighting($order_info['manager_idx']);
                //触发任务 升级装备
                $this->CI->utility->get_task_status($order_info['manager_idx'] , 'upgrade_equipt');
            }
        }else if($order_info['type'] == 5){//商城礼包
            //获取礼包详情
            $where          = "idx = ".$order_info['goods_id']." AND status = 1";
            $package_info   = $this->CI->pay_model->get_one($where , 'package_conf');
            if(!$package_info){
                $this->CI->pay_model->error();
                log_message('error', 'do_callback:商城礼包信息获取失败'.http_build_query($params));
                return 'FALSE';
            }
            //发放礼包物品
            $prop_info['prop_info'] = $package_info['prop'];
            $prop_info = $this->CI->utility->get_reward($prop_info);
            foreach ($prop_info['prop_info'] as $key => $value){
                $insert['uuid']    = $order_info['manager_idx'];
                $insert['prop_no'] = $value['prop_no'];
                $res = $this->CI->utility->insert_prop_info($insert , $value['num']);
                if(!$res){
                    log_message('error', 'do_callback:增加经理球票失败'.http_build_query($params));
                    $this->CI->pay_model->error();
                    return 'FALSE';
                }
            }
        }
        
        // 发送邮件通知提醒--月卡充值，发送邮件通知
        if ($order_info['type'] == 2 && $recharge_pack_info['type'] == 1) {
            //发放邮件通知
            $ins_data['sender_id']         = 1;
            $ins_data['sender_name']       = 'admin';
            $ins_data['manager_idx']       = $order_info['manager_idx'];
            $ins_data['title']             = '月卡奖励';
            $ins_data['content']           = '月卡奖励球票已发放';
            $ins_data['link']              = '';
            $ins_data['is_accessory']      = 0;
            $ins_data['accessory_type']    = 0;
            $ins_data['accessory_content'] = '';
            $ins_data['status']            = 1;
            $res = $this->CI->pay_model->insert_data($ins_data , 'mail_conf');
            if(!$res){
                $this->CI->pay_model->error();
                log_message('error', 'do_callback:月卡奖励提示邮件发送失败'.http_build_query($params));
                return 'FALSE';
            }
        }
        
        // 发放奖励 球票
        if ($tickets+$present_tickets) {
            // 更新球票|升级vip等级
            // 获取用户充值球票之后，vip等级
            $sql            = "SELECT SUM(tickets) AS tickets FROM recharge_his WHERE manager_idx=".$order_info['manager_idx']." AND status = 1 GROUP BY manager_idx";
            $info           = $this->CI->pay_model->fetch($sql, 'row');
            $price_         = ($info['tickets']+$tickets)*10;// 球票转成->人民币分
            $sql2           = "SELECT level FROM vip_conf WHERE rmb > ".$price_." AND status = 1 ORDER BY rmb";
            $vip_info       = $this->CI->pay_model->fetch($sql2, 'row');
            $m_id['uuid']   = $order_info['manager_idx'];
            $m_info         = $this->CI->utility->get_manager_info($m_id);
            $fields         = array('tickets'=>$m_info['tickets'] + $tickets +$present_tickets,'vip'=>$vip_info['level']);
            $where          = array('idx'=>$order_info['manager_idx'],'status'=>1);
            $res            = $this->CI->pay_model->update_data($fields , $where , 'manager_info');
            if (!$res) {
                log_message('error', 'add_tickets:支付充值,增加经理球票失败'.$tickets);
                $this->CI->pay_model->error();
                return 'FALSE';
            }
        }
        
        //发放首冲礼包
        $res    = $this->first_pay($order_info['manager_idx']);
        if($res != 'ok'){
            log_message('error', 'do_callback:发放首冲礼包失败'.http_build_query($params));
            $this->CI->pay_model->error();
            return 'FALSE';
        }
        
        //修改订单状态为
        $u_order    = $this->update_order_status($order_info['idx'], 1);
        if (!$u_order) {
            $this->CI->pay_model->error();
            log_message('error', 'do_callback:修改订单状态失败'.http_build_query($params));
            return 'FALSE';
        }
        
        // 插入订单充值历史记录
        $log_arr['manager_idx']     = $order_info['manager_idx'];
        $log_arr['type']            = $order_info['type'];
        $log_arr['rmb']             = $order_info['price'];
        $log_arr['good_id']         = $order_info['goods_id'];
        $log_arr['level']           = (int)$level;
        $log_arr['tickets']         = (int)$tickets;
        $log_arr['present_tickets'] = (int)$present_tickets;
        $log_arr['platform']        = $order_info['platform'];
        $log_arr['status']          = 1;
        $i_ohis = $this->insert_recharge_his($log_arr);
        if (!$i_ohis) {
            $this->CI->pay_model->error();
            log_message('error', 'do_callback:修改订单状态失败'.http_build_query($params));
            return 'FALSE';
        }
        $this->CI->pay_model->success();
        return 'ok';
    }
    
    /**
     * 插入订单历史记录表
     */
    public function insert_recharge_his($params)
    {
        $res = $this->CI->pay_model->insert_data($params , 'recharge_his');
        return $res;
    }
    
    /**
     * 更新订单状态
     * @param int $order_id 订单表idx
     * @param int $status   订单状态 1成功2失败3待支付4关闭
     */
    public function update_order_status($order_id , $status)
    {
        $where['idx']       = $order_id;
        $where['status']    = 1;
        $data['o_status']   = $status;
        $res = $this->CI->pay_model->update_data($data , $where , 'order');
        return $res;
    }
    
    
    //修改订单状态
    public function change_order_status($order_id , $status , $log_arr = array())
    {
        //订单成功插入记录充值日志
        if($status == 1){
            $log_arr['status'] = 1;
            //插入订单
            $res = $this->CI->pay_model->insert_data($log_arr , 'recharge_his');
            if(!$res){
                $this->CI->pay_model->error();
                return $res;
            }
        }
        //修改订单状态
        $where['idx']    = $order_id;
        $where['status'] = 1;
        $data['o_status'] = $status;
        $res = $this->CI->pay_model->update_data($data , $where , 'order');
        if(!$res){
            $this->CI->pay_model->error();
            return $res;
        }
        return 'ok';
    }

    //增加经理球票
    public function add_tickets($uuid , $tickets)
    {
        //获取经理信息
        $m_id['uuid'] = $uuid;
        $m_info       = $this->CI->utility->get_manager_info($m_id);
        $fields = array('tickets'=>$m_info['tickets'] + $tickets);
        $where  = array('idx'=>$uuid,'status'=>1);
        $res = $this->CI->pay_model->update_data($fields , $where , 'manager_info');
        if (!$res) {
            return false;
        }
        return true;
    }
    
    //获取订单信息
    public function get_order_info($order_id)
    {
        //查询订单详情
        $where = "order_no = '$order_id' AND o_status = 3 AND status = 1";
        $order_info = $this->CI->pay_model->get_one($where , 'order');
        if(!$order_info){
            return FALSE;
        }
        return $order_info;
    }
    
    //发放首冲礼包
    public function first_pay($uuid)
    {
        //查询是否已经领过首冲礼包
        $where = "manager_idx = '$uuid' AND status = 1";
        $present_his = $this->CI->pay_model->get_one($where , 'present_his');
        if($present_his){
            return TRUE;
        }
        //获取奖励内容
        $where = "status = 1";
        $present_info = $this->CI->pay_model->get_one($where , 'present_conf');
        if(!$present_info){
            return FALSE;
        }
        $present_reward_list = $this->CI->utility->get_reward($present_info);
        foreach ($present_reward_list as $k => $v){
            if($v){
                //球员奖励
                if($k == 'player_info'){
                    foreach ($v as $key => $value){
                        $insert['uuid']      = $uuid;
                        $insert['player_no'] = $value['player_no'];
                        $insert['level']     = $value['level'];
                        for($i = 1;$i <= $value['num']; $i++){
                            $res = $this->CI->utility->insert_player_info($insert);
                            if(!$res){
                                log_message('error', '首充球员奖励发送失败');
                                return FALSE;
                            }
                        }
                    }
                }
                if($k == 'equipt_info'){
                    foreach ($v as $key => $value){
                        $insert['uuid']      = $uuid;
                        $insert['equipt_no'] = $value['equipt_no'];
                        $insert['level']     = $value['level'];
                        for($i = 1;$i <= $value['num']; $i++){
                            $res = $this->CI->utility->insert_equipt_info($insert);
                            if(!$res){
                                log_message('error', '首充装备奖励发送失败');
                                return FALSE;
                            }
                        }
                    }
                }
            }
        }
        //记录发放首充奖励
        $ins_data['rmb']         = 1;
        $ins_data['player_info'] = $present_info['player_info'];
        $ins_data['equipt_info'] = $present_info['equipt_info'];
        $ins_data['status']      = 1;
        $ins_data['manager_idx'] = $uuid;
        $res = $this->CI->pay_model->insert_data($ins_data , 'present_his');
        if(!$res){
            return $res;
        }
        return 'ok';
    }
    
    /**
     * 获取充值包列表【包含月卡、固定充值包】
     * @param type $params
     */
    public function get_packet_list($params)
    {
        // 获取充值配置表
        $options['where']   = array('status'=> 1);
        $options['fields']  = "idx id,type,pic,price,tickets,present";
        $list               = $this->CI->pay_model->list_data($options,'recharge_conf');
        if (!$list) {
            log_message('error', 'team_logo_empty:'.$this->ip.',获取充值包信息失败');
            $this->CI->output_json_return('get_recharge_pack_info');
        }
        // 判断用户：是否充值过、是否充值月卡[充值月卡剩余天数]
        $days               = 0;// 月卡使用剩余天数
        $status             = 0;// 用户是否充值过0未充值过1已充值
        $options['where']   = array('manager_idx'=>$params['uuid'],'good_id'=>1);// 月卡充值包id
        $options['fields']  = "idx id,time";
        $options['limit']   = array('page'=>0,'size'=>1);
        $options['order']   = "time DESC";
        $card_list          = $this->CI->pay_model->list_data($options,'recharge_his');
        if ($card_list[0]) {
            // 判断剩余天数
            $last_date  = date('Ymd',strtotime("+29 day", strtotime(date('Ymd',$card_list[0]['time']))));
            if ($last_date > date('Ymd',time())) {
                $days   = (strtotime($last_date) - strtotime(date('Ymd',time())))/86400;
            }
            $status = 1;
        } else {
            // 判断用户是否充值过
            $where  = array('manager_idx'=>$params['uuid']);
            $info   = $this->CI->pay_model->get_one($where,'recharge_his');
            if ($info) {
                $status = 1;
            }
        }
        // 重组数据
        foreach ($list as $k=>&$v) {
            if ($v['type'] == 1) {//充值类型：1月卡2充值包
                $v['total_ticket']  = $v['tickets']*30;
                // 判断用户是否充值月卡
                $v['days']  = $days;
            } else {
                $v['status']    = $status;
            }
        }
        
        // 返回数据
        return $list;
    }
    
    /**
     * 获取首充礼包【首次充值即可得】
     * @param type $params
     */
    public function get_present_info($params)
    {
        // 获取首充礼包
        $where  = array('status'=> 1);
        $fields = "idx id,rmb,player_info,equipt_info";
        $info   = $this->CI->pay_model->get_one($where,'present_conf',$fields);
        if (!$info) {
            log_message('error', 'get_present_info:'.$this->ip.',首充礼包获取失败');
            $this->CI->output_json_return('present_pack_fail');
        }
        
        // 重组数据
        $data['id'] = $info['id'];
        $equipt     = explode("|", $info['equipt_info']);
        foreach ($equipt as $k=>$v) {
            $arr = explode(":", $v);
            $e['equipt_no'] = $arr[0];
            $e['level']     = $arr[1];
            $e['num']       = $arr[2];
            $e_[]           = $e;
        }
        $player     = explode("|", $info['player_info']);
        foreach ($player as $k=>$v) {
            $arr = explode(":", $v);
            $p['player_no'] = $arr[0];
            $p['level']     = $arr[1];
            $p['num']       = $arr[2];
            $p_[]           = $p;
        }
        $data['equipt_info']    = $e_;
        $data['player_info']    = $p_;
        return $data;
    }
    /**
     * 发放月卡奖励
     * @param type $params
     */
    public function give_month_card_reward($params)
    {
        // 获取月卡配置信息
        $where  = array('type'=>1,'status'=> 1);
        $fields = "idx id,tickets,present";
        $info   = $this->CI->pay_model->get_one($where,'recharge_conf',$fields);
        if (!$info) {
            log_message('error', 'get_present_info:'.$this->ip.',充值包信息获取失败');
            $this->CI->output_json_return('get_recharge_pack_info');
        }
        
        // 发放奖励-更新经理球票数
        $this->CI->pay_model->start();
        $m_info = $this->CI->utility->get_manager_info($params['uuid']);
        $fields2= array('tickets'=>$m_info['tickets'] + $info['tickets']);
        $where2 = array('idx'=>$params['uuid'],'status'=>1);
        $res = $this->CI->utility->update_m_info($fields2,$where2);
        if (!$res) {
            $this->CI->pay_model->error();
            log_message('error', 'give_month_card_reward:'.$this->ip.',发送月卡奖励,球票更新失败');
            $this->CI->output_json_return('m_info_update_err');
        }
        // 邮件通知
        $data   = array(
            'sender_id'         => 1,
            'sender_name'       => 'admin',
            'manager_idx'       => $params['uuid'],
            'title'             => '月卡奖励发送提醒',
            'content'           => '月卡奖励每日定时发送',
            'link'              => '',
            'is_accessory'      => 0,
            'accessory_type'    => 0,
            'accessory_content' => '',
            'status'            => 1,
        );
        $i_res  = $this->CI->pay_model->insert_data($data,'mail_info');
        if (!$i_res) {
            $this->CI->pay_model->error();
            log_message('error', 'give_month_card_reward:'.$this->ip.',月卡奖励提醒邮件插入失败');
            $this->CI->output_json_return('mail_insert_fail');
        }
        $this->CI->pay_model->success();
        return true;
    }
    
    /**
     * 将数组转为XML格式
     */
    public function array_to_xml($arr)
    {
        //对数组进行字母排序
        ksort($arr);
        reset($arr);
        $xml = '';
        $xml .= '<xml>';
        foreach ($arr as $k=>$v) {
            // $xml .= '<'.$k.'><![CDATA['.$v.']]></'.$k.'>';
            $xml .= '<'.$k.'>'.$v.'</'.$k.'>';
        }
        $xml .= '</xml>';
        return $xml;
    }
    
    /**
     * 将XML转为数组
     */
    public function xml_to_array($xml)
    {
        // $xml = "<xml><aa><![CDATA[aaa ]]></aa><c><ddd>sdsdsd</ddd><eee>dsfsdf</eee></c><b>eeee</b></xml>";
        $xml_obj = simplexml_load_string($xml, 'SimpleXMLElement', LIBXML_NOCDATA);
        $xml_arr = json_decode(json_encode($xml_obj),TRUE);
        return $xml_arr;
    }
    
    /**
     * 生成支付宝sign
     * @param type $params
     */
    public function alipay_sign_by_rsa($params)
    {
        $config = $this->CI->passport->get('alipay_config');
        $sign['_input_charset'] = 'utf-8';
        $sign['out_trade_no']   = $params['out_trade_no'];
        $sign['partner']        = $config['partner'];
        $sign['payment_type']   = 1;
        $sign['notify_url']     = $config['notify_url'];
        $sign['service']        = 'mobile.securitypay.pay';
        $sign['subject']        = $params['subject'];
        $sign['total_fee']      = $params['total_fee'];
        $sign['seller_id']      = $config['seller_email'];
        $sign['body']           = $params['subject'];
        $arg  = "";
	while (list ($key, $val) = each ($sign)) {
		$arg.=$key."=".'"'.$val.'"'."&";
	}
	//去掉最后一个&字符
	$arg = substr($arg,0,count($arg)-2);
	
	//如果存在转义字符，那么去掉转义
	if(get_magic_quotes_gpc()){$arg = stripslashes($arg);}
        $sign_  = urlencode($this->rsaSign($arg,$config['private_key']));
        $sign_type  = '&sign_type="RSA"';
        $sign_new   = $arg.'&sign='.'"'.$sign_.'"'.$sign_type;
        return $sign_new;
    }
    
    function rsaSign($data, $private_key) {
        //以下为了初始化私钥，保证在您填写私钥时不管是带格式还是不带格式都可以通过验证。
        $private_key=str_replace("-----BEGIN RSA PRIVATE KEY-----","",$private_key);
	$private_key=str_replace("-----END RSA PRIVATE KEY-----","",$private_key);
	$private_key=str_replace("\n","",$private_key);

	$private_key="-----BEGIN RSA PRIVATE KEY-----".PHP_EOL .wordwrap($private_key, 64, "\n", true). PHP_EOL."-----END RSA PRIVATE KEY-----";
        $res=openssl_get_privatekey($private_key);
        if($res) {
            openssl_sign($data, $sign,$res);
        } else {
            echo "您的私钥格式不正确!"."<br/>"."The format of your private_key is incorrect!";
            exit();
        }
        openssl_free_key($res);
            //base64编码
        $sign = base64_encode($sign);
        return $sign;
    }
}

