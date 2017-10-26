<?php
class Conf_lib extends Base_lib {
    public function __construct() {
        parent::__construct();
        $this->load_model('conf_model');
    }
    
    /**
     * 技能静态数据列表
     */
    public function skill_conf_list()
    {
        $options['where']   = array('status'=>1);
        $skill_list         = $this->CI->conf_model->list_data($options, 'skill_conf');
        return $skill_list;
    }
    
    /**
     * 球员静态数据列表
     * @param type $params
     * @return type
     */
    public function player_conf_list($params)
    {
        $options['where']   = array('status'=>1);
        $options['fields']  = "player_no,pic,frame,quality,name,nationality,club,ability,birthday,position_type,intro";
        $list = $this->CI->conf_model->list_data($options, 'player_lib');
        return $list;
    }
    
    /**
     * 获取道具静态数据
     */
    public function prop_conf_list()
    {
        $options['where']   = array('status'=>1);
        $options['fields']  = "prop_no,type,name,pic,frame,descript";
        $list = $this->CI->conf_model->list_data($options, 'prop_conf');
        return $list;   
    }
    
    /**
     * 获取宝石静态数据
     */
    public function gem_conf_list()
    {
        $options['where']   = array('status'=>1);
        $options['fields']  = "gem_no,name,quality,frame,pic,descript";
        $list = $this->CI->conf_model->list_data($options, 'gem_conf');
        return $list;   
    }
    
    /**
     * 获取宝石静态数据
     */
    public function equipt_conf_list()
    {
        $options['where']   = array('status'=>1);
        $options['fields']  = "type,equipt_no,name,frame,pic,quality,level,holes,descript";
        // $options['groupby'] = "equipt_no";
        $list = $this->CI->conf_model->list_data($options, 'equipt_conf');
        return $list; 
    }
    
    /**
     * 获取属性配置表
     * @return type
     */
    public function attribure_conf_list()
    {
        $options['where']   = array('status'=>1);
        $options['fields']  = "attr_no,level,name as name_en,name_ch,parent_no";
        $list               = $this->CI->conf_model->list_data($options, 'attribute_conf');
        return $list; 
    }
    
    public function data_version_info()
    {
        $options['where']   = array('status'=>1);
        $options['fields']  = "idx id,type,version,last_version";
        $list               = $this->CI->conf_model->list_data($options, 'data_version');
        return $list; 
    }
    
    /**
     * 获取区服配置信息
     * @return type
     */
    public function server_area_info()
    {
        $options['where']   = array('status'=>1);
        $options['fields']  = "idx id,type,name,admin";
        $list               = $this->CI->conf_model->list_data($options, 'server_area_conf');
        return $list; 
    }
}

