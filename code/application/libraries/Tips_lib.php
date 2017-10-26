<?php
class Tips_lib extends Base_lib {

    public function __construct() {
        parent::__construct();
        $this->load_model('tips_model');
    }

    /*
     * 刷新全部页面提示
     */
    public function refresh_tips($uuid)
    {
        $tip_data = array();
        //邮件页面
        $mail = $this->CI->tips_model->get_one(array('manager_idx' => $uuid , 'is_read' => 0 , 'status' => 1) , 'mail_info');
        if($mail){
            array_push($tip_data,1011,1026);
        }
        //天赋点
        $m_info = $this->CI->tips_model->get_one(array('idx' => $uuid , 'status' => 1) , 'manager_info');
        if($m_info && ($m_info['talent'] > 0)){
            array_push($tip_data,1004,1010);
        }
        //天梯耐力
        if($m_info['endurance'] == 50 && $m_info['level'] >= 10){
            array_push($tip_data,1008);
        }
        //空闲训练点
        if($m_info['trainpoint'] > 0){
            array_push($tip_data,1009);
        }
        //任务
        $option['where']   = array('manager_idx' => $uuid ,'receive ' => 0 , 'status' => 1);
        $option['fields']  = 'type';
        $option['groupby'] = 'type';
        $task_info = $this->CI->tips_model->list_data( $option, 'task_complete');
        if($task_info){
            array_push($tip_data,1013);
            foreach($task_info as $k => $v){
                if($v['type'] == 1){
                    array_push($tip_data,1021);
                }else{
                    array_push($tip_data,1020);
                }
            }
        }
        //成就
        $option['where']   = array('manager_idx' => $uuid ,'receive ' => 0 , 'status' => 1);
        $option['fields']  = 'module_no';
        $option['groupby'] = 'module_no';
        $achievement_info = $this->CI->tips_model->list_data( $option, 'achievement_complete');
        if($achievement_info){
            array_push($tip_data,1012);
            foreach($achievement_info as $k => $v){
                if($v['module_no'] == 1){
                    array_push($tip_data,1022);
                }elseif($v['module_no'] == 2){
                    array_push($tip_data,1023);
                }else{
                    array_push($tip_data,1024);
                }
            }
        }
        //魔法社
        $this->load_library('draw_lib');
        $params['uuid'] = $uuid;
        $draw_list = $this->CI->draw_lib->get_draw_list($params);
        if($draw_list){
            foreach($draw_list as $k => $v){
                if($v['type'] != 2 && $v['curr_status'] == 1){
                    array_push($tip_data,1007);
                    break;
                }
            }
        }
        //限时商店
        $this->load_library('shop_lib');
        $options   = array('where'=>array('status'=>1 , 'limit_time' => 1),'fields'=>"type as id,name,limit_time,open_time,close_time");
        $shop_list = $this->CI->shop_lib->get_store_list($options);
        if($shop_list[0]['open_time'] <= time() && $shop_list[0]['close_time'] >= time()){
            array_push($tip_data,1017,1006);
        }
        if($tip_data){
            foreach($tip_data as $val){
                $this->tip_pages($uuid, $val);
            }
        }
        return 1;
    }

    //获取页面红点提示
    public function get_page_tips($uuid)
    {
        //获取需要提示的页面ID
        $join_condition = "t1.p_id = t2.page_id AND t2.manager_idx = $uuid ";
        $condition      = "t1.`status` = 1";
        $select         = "t1.p_id as page , t2.page_id as page_id , t2.status as status , t2.update_time as time  ";
        $tb_a           = "page_tip AS t1";
        $tb_b           = "page_status AS t2";
        $tips_page = $this->CI->tips_model->left_join($condition , $join_condition , $select , $tb_a , $tb_b , TRUE);         
        $return = array();
        foreach($tips_page as $k => $v){
            if($v['status'] == 1){
                $return[] = $v['page_id'];
            }
        }
        return array_unique($return);
    }

    /*
     * 删除红点提示
     */
    public function del_page_tip($uuid , $page_id)
    {
        $this->CI->tips_model->start();
        $is_tip = $this->CI->tips_model->get_one(array('page_id' => $page_id , 'manager_idx' => $uuid , 'status' => 1) , 'page_status');
        if($is_tip){
            $where['manager_idx'] = $uuid;
            $where['page_id']     = $page_id;
            $data['status']       = 0;
            $res = $this->CI->tips_model->update_data($data , $where , 'page_status');
            if(!$res){
                $this->CI->tips_model->error();
                log_message('error', 'tip_pages:del_page_tip'.$this->ip.','.http_build_query($_REQUEST));
                $this->CI->output_json_return('del_page_tip');
            }
        }
        else{
            $this->CI->tips_model->error();
            log_message('error', 'tip_pages:select_page_tip'.$this->ip.','.http_build_query($_REQUEST));
            $this->CI->output_json_return('select_page_tip');
        }
        $this->CI->tips_model->success();
    }

    //添加红点提醒的页面
    public function tip_pages($uuid , $page_id)
    {
        $this->CI->tips_model->start();
        $is_tip = $this->CI->tips_model->get_one(array('page_id' => $page_id , 'manager_idx' => $uuid) , 'page_status');
        if(!$is_tip){
            $data['manager_idx'] = $uuid;
            $data['page_id']   = $page_id;
            $data['status']    = 1;
            $res = $this->CI->tips_model->insert_data($data , 'page_status');
            if(!$res){
                $this->CI->tips_model->error();
                log_message('error', 'tip_pages:add_page_tip'.$this->ip.','.http_build_query($_REQUEST));
                $this->CI->output_json_return('add_page_tip');
            }
        }
        else{
            $where['manager_idx'] = $uuid;
            $where['page_id']     = $page_id;
            $data['status']       = 1;
            $res = $this->CI->tips_model->update_data($data , $where , 'page_status');
            if(!$res){
                $this->CI->tips_model->error();
                log_message('error', 'tip_pages:add_page_tip'.$this->ip.','.http_build_query($_REQUEST));
                $this->CI->output_json_return('add_page_tip');
            }
        }
        $this->CI->tips_model->success();
    }
    
    /*
     * 检查是否有需要提示的新手教程
     */
    public function get_novice_coures_tips($uuid)
    {
        $return = "";
        $options        = array('where'=>array('manager_idx'=>$uuid , 'n_c_tip' => 0 , 'status' => 1),'fields'=>"idx as idx , n_c_id AS id");
        $coures_info = $this->CI->tips_model->list_data($options , 'novice_coures');
        if(count($coures_info) < 1){
            return $return;
        }
        //返回需要提示的新手引导编号
        foreach($coures_info as $k => $v)
        {
            $return[] = $v['id'];
            $where[]  = $v['idx'];
        }
        //修改提示状态为1
        $where = implode(',', $where);
        $this->CI->tips_model->start();
        $sql = "UPDATE novice_coures SET n_c_tip = 1 WHERE idx IN ($where)";
        $res   = $this->CI->tips_model->fetch($sql,'update');
        if(!$res){
            $this->CI->tips_model->error();
            log_message('error', 'get_novice_coures_tips:update_novice_coures'.$this->ip.','.http_build_query($_REQUEST));
            $this->CI->output_json_return('update_novice_coures');
        }
        $this->CI->tips_model->success();
        return $return;
    }
}

