<?php
/**
 * 基础Controller
 * @author huhong
 * @date 2016-05-10
 */
class MY_Controller extends CI_Controller{
    const PAGESIZE = 20;
    
    function __construct() {
        parent::__construct();
        $this->load->driver('cache');
        $this->ip = $this->input->ip_address();
        header("Access-Control-Allow-Origin: *");
    }

    /**
     * jason格式输出
     * @param array $data
     */
    public function output_json_return($code ='success', $data = array()) {
        header('Content-type: application/json;charset=utf-8');
        if(empty($data)){
           $data = new stdClass();
        }
        $this->lang->load('ecode');
        echo json_encode(array('c' => $code, 'm' => lang($code), 'data' => $data));exit;
    }
    
    /**
     * 获取公共参数
     * @return array 公共参数值
     */
    public function public_params()
    {
        $params['app_id']   = $this->request_params('app_id');
        $params['uuid']     = (int)$this->request_params('uuid');
        $params['token']    = $this->request_params('token');
        $params['sign']     = $this->request_params('sign');
        if ($params['app_id'] == '' || $params['uuid'] == '' || $params['token'] == '' || $params['sign'] == '') {
            $this->output_json_return('params_err');
        }
        // 校验TOKEN是否有效
//        if (!$this->is_login($params['uuid'], $params['token'])) {
//            $this->output_json_return('token_err');
//        }
        return $params;
    }
    
    /**
     * 校验登录TOKEN是否有效 判断是否登录
     * @param int $uuid
     * @return  bool 
     */
    public function is_login($uuid, $token)
    {
        $token_info = $this->get_token($uuid);
        if (!$token_info['token']) {
            return false;
        }
        if ($token_info['token'] != $token) {
            return false;
        }
        if (time() > $token_info['token_expire'] + $token_info['login_ts']) {
            return false;
        }
        return true;
    }

    /**
     * POST|GET接受数据
     * @param string $key 参数key
     * @return string 参数值
     */
    public function request_params($key)
    {
        if ($key == '') {
            return false;
        }
        $p = $this->input->get_post($key, true);
        if (is_array($p)) {
            return $p;
        }
        return trim($p);
    }
    
    /**
     * 设置经理登录token
     * @param type $uuid
     * @param type $app_id
     */
    public function set_token($uuid, $app_id)
    {
        $token_expire   = $this->passport->get('token_expire');
        $token_key      = $this->passport->get('token_key');
        $token_pre      = $this->passport->get('token_pre');
        $token          = $this->gen_login_token($uuid, $app_id,$token_key);
        $value = array(
            'token'         => $token,
            'token_expire'  => $token_expire,
            'login_ts'      => time(),
        );
        $res = $this->save_redis($token_pre.$uuid, $value, $token_expire);
        if ($res) {
            return $value;
        }
        return false;
    }
    
    /*
     * 获取经理登录token
     */
    public function get_token($uuid)
    {
        $token_pre  = $this->passport->get('token_pre');
        $token_info = $this->get_redis_info($token_pre.$uuid);
        return $token_info;
    }
    
    /**
     * 生成唯一登录token
     * @param type $uuid
     * @param type $app_id
     * @return type
     */
    public function gen_login_token($uuid, $app_id, $token_key)
    {
        $arg = $uuid.'_'.$app_id.'_'.$token_key.time();
        return md5($arg);
    }

    /**
     * 存储redis
     * @param string $key redis存储键
     * @param string|array $value redis存储的值
     * @param int $ttl  redis存储有效期
     * @return  bool  TRUE on success, FALSE on failure
     */
    public function save_redis($key, $value, $ttl= 60)
    {
        $result = $this->cache->redis->save($key, $value, $ttl);
        return $result;
    }

    /**
     * 删除redis
     * @param string $key
     * @return bool 
     */
    public function del_redis($key)
    {
        $prex   = $this->passport->get('redis_prex');
        $result = $this->cache->redis->delete($prex.$key);
        return $result;
    }

    /**
     * 获取redis内容
     * @param string $key 获取redis的key
     * @return mix string|array 存储值
     */
    public function get_redis_info($key)
    {
        $result = $this->cache->redis->get($key);
        return $result;
    }
    
    
}

