<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Conf extends MY_Controller {
    var $static_time = 300;// 静态数据保存时间
    public function __construct() {
        parent::__construct();
        $this->load->library('conf_lib');
    }
    
    /**
     * 球员静态数据
     */
    public function skill_conf()
    {
        $skill  = $this->get_redis_info("SKILL_CONF_");
        if (!$skill) {
            $skill_list = $this->conf_lib->skill_conf_list();
            // 转json、压缩
            $skill  = gzencode(json_encode($skill_list));
            $this->save_redis('SKILL_CONF_', $skill,$this->static_time);
        }
        // 解压缩
        $skill_list     = gzdecode($skill);
        echo $skill_list;exit;
    }
    
    /**
     * 球员静态数据
     */
    public function play_conf()
    {
        $params = $this->public_params();
        // 校验签名
        $this->utility->check_sign($params, $params['sign']);
        $key    = $this->passport->get('plib_list');
        $plib   = $this->get_redis_info($key);
        if (!$plib) {
            $conf_list  = $this->conf_lib->player_conf_list($params);
            // 转json、压缩
            $plib       = gzencode(json_encode($conf_list));
            $this->save_redis($key, $plib,$this->static_time);
        }
        // 解压缩
        $plib_list      = gzdecode($plib);
        $data['list']   = json_decode($plib_list);
        $this->output_json_return('success',$data);
    }
    
    /**
     * 获取道具静态数据
     */
    public function prop_conf()
    {
        $params = $this->public_params();
        // 校验签名
        $this->utility->check_sign($params, $params['sign']);
        $prop  = $this->get_redis_info("PROP_CONF_");
        if (!$prop) {
            $prop_list  = $this->conf_lib->prop_conf_list();
            $prop       = gzencode(json_encode($prop_list));
            $this->save_redis('PROP_CONF_', $prop,$this->static_time);
        }
        // 解压缩
        $prop_list      = gzdecode($prop);
        $data['list']   = json_decode($prop_list);
        $this->output_json_return('success',$data);
    }
    
    /**
     * 获取宝石静态数据
     */
    public function gem_conf()
    {
        $params = $this->public_params();
        // 校验签名
        $this->utility->check_sign($params, $params['sign']);
        $gem    = $this->get_redis_info("GEM_CONF_");
        if (!$gem) {
            $gem_list   = $this->conf_lib->gem_conf_list();
            $gem        = gzencode(json_encode($gem_list));
            $this->save_redis('GEM_CONF_', $gem,$this->static_time);
        }
        // 解压缩
        $gem_list       = gzdecode($gem);
        $data['list']   = json_decode($gem_list);
        $this->output_json_return('success',$data);
    }
    
    /**
     * 获取装备静态数据
     */
    public function equipt_conf()
    {
        $params = $this->public_params();
        // 校验签名
        $this->utility->check_sign($params, $params['sign']);
        $equipt = $this->get_redis_info("EQUIPT_CONF_");
        if (!$equipt) {
            $equipt_list    = $this->conf_lib->equipt_conf_list();
            $equipt         = gzencode(json_encode($equipt_list));
            $this->save_redis('EQUIPT_CONF_', $equipt,$this->static_time);
        }
        // 解压缩
        $equipt_list    = gzdecode($equipt);
        $data['list']   = json_decode($equipt_list);
        $this->output_json_return('success',$data);
    }
    
    /**
     * 获取属性配置表
     */
    public function attribute_conf()
    {
        $params             = $this->public_params();
        // 校验签名
        $this->utility->check_sign($params, $params['sign']);
        $data['list']   = $this->conf_lib->attribure_conf_list();
        $this->output_json_return('success',$data);
    }
    
    /**
     * 获取静态数据的版本
     */
    public function data_version()
    {
        $params = $this->public_params();
        // 校验签名
        $this->utility->check_sign($params, $params['sign']);
        $data['data']   = $this->conf_lib->data_version_info();
        $this->output_json_return('success',$data);
    }
    
    /**
     * 获取区服信息
     */
    public function server_area()
    {
        $params['app_id']   = $this->request_params('app_id');
        $params['sign']     = $this->request_params('sign');
        // 校验签名
        $this->utility->check_sign($params, $params['sign']);
        $data['data']   = $this->conf_lib->server_area_info();
        $this->output_json_return('success',$data);
    }
}
