<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Test extends MY_Controller {
    
    public function __construct() {
        parent::__construct();
        $this->load->library('test_lib');
        $this->load->library('court_lib');
        $this->load->library('draw_lib');
        $this->load->library('manager_lib');
        $this->load->library('match_lib');
        $this->load->library('task_lib');
        $this->load->library('tips_lib');
    }
    
    function test_11()
    {
        $params['s_name']       = $this->input->get_post('s_name');
        $params['start_time']   = $this->input->get_post('start_time');
        $params['end_time']     = $this->input->get_post('end_time');
        
        if ($params['s_name'] == 'S001') {
            $url    = "http://s001.rxqq.the9.com/";
        } elseif($params['s_name'] == 's002') {
            $url    = "http://s002.rxqq.the9.com/";
        } else {
            $url    = "http://api.rxqq.the9.com/";
        }
    }
    
    public function index()
    {
        // phpinfo();
        $time   = time();
        $date   = date('Ymd H:i:s',$time);
        var_dump($date);exit;
        $data       = array(
            'result'        => 2,
            'score'         => "2:2",
            'match_type'    => 2,
            'chall_id'      => 1,
            'chall_name'    => 'test',
            'chall_pic'     => '10001',
            'chall_type'    => 1,
            'bechall_type'  => 1,
            'bechall_id'    => 2,
            'bechall_name'  =>'test2',
            'bechall_pic'   =>'1002',
            'ranking'       => 1,
            'ranking_curr'  => 1,
            'reward'        => array('exp'=>1),
            'descript'      => '',
        );//        $redard = array(
//            'exp'   => 1,
//            'euro'  =>100,
//            'soccer_soul'   => 10,
//            'powder'        => 1,
//            'honor'         => 10,
//            'achievement'   => 333,
//            'talent'        => 3333,
//            'tickets'       =>1,
//            'player_info'   => array('0'=>array('player_no'=>60001,'num'=>2,'level'=>1),'1'=>array('player_no'=>60002,'num'=>1,'level'=>4)),
//            'equipt_info'   => array(0=>array('equipt_no'=>201,'level'=>1,'num'=>1),1=>array('equipt_no'=>202,'level'=>2,'num'=>2)),
//            'prop_info'     => array(0=>array('prop_no'=>801,'num'=>2),1=>array('prop_no'=>601,'num'=>2)),
//            'gem_info'      => array(0=>array('gem_no'=>1001,'num'=>2),1=>array('gem_no'=>1002,'num'=>2)),
//        );

        $aa = json_encode($data);
        echo $aa;exit;
        $this->court_lib->get_player_exists_where(11180);
       // $rse = $this->manager_lib->exp_belongto_level(1500);
       var_dump($rse);
    }
        
     /*
     * 测试任务触发
     */
    public function test_task()
    {
        $params              = $this->public_params();
        $params['task_type'] = (int)$this->request_params('task_type');
        $params['offset'] = 0;
        $params['pagesize'] = 10;
        //$this->load_library('task_lib');
        $this->task_lib->get_task_list($params);
       // $this->utility->get_task_status($params['uuid'], $params['task_action']);
    }
    
    public function get_redis()
    {
        $result = $this->get_redis_info('Request_Match_67_101_1_11492053957');
        $a = gzdecode($result);
        $b = json_decode($a, true);
        var_dump($b);exit;
        
        
       // $result = $this->get_redis_info('testnxj');
       // VAR_DUMP($result);
        
    }
    
    
    public function probable()
    {
        $options    = array(
            'where' => array('status'   => 1),
        );
        $result = $this->utility->probable($options, 'drawproble_euro');// 抽中的物品
        var_dump($result);
    }
    
    public function save_kill()
    {
        $options = array(
            'status' => 1,
        );
        $skill_list = $this->test_lib->get_skill_list($options);
        // 压缩技能
        $key        = $this->passport->get('skill');
        $this->save_redis($key, json_encode($skill_list), 0);   
        
        $result = $this->get_redis_info($key);
        var_dump($result);
    }
    
    public function get_match_result()
    {
        $server_info    = $this->passport->get('fingting_server');
        $match_result   = $this->utility->socket_connect($server_info['ip'], $server_info['port']);
        var_dump($match_result);exit;
    }
    
    public function add_player()
    {
        $params['uuid']             = (int)$this->request_params('uuid');
        $params['player_no']        = (int)$this->request_params('player_no');
        $params['level']            = (int)$this->request_params('level');
        
        $manager_name   = $this->utility->get_manager_info($params)['name'];
        $where  = array('player_no'=>$params['player_no'],'status'=>1);
        $player_lib  = $this->court_model->get_one($where,'player_lib');
        $player     = $this->utility->recombine_attr($player_lib);
        
        // 计算level之后的新属性值
        $para   = array('player_no'=>$params['player_no'],'status'=>1,'level'=>$params['level']);
        $attribute = $this->utility->attribute_change($player['attribute'],2,2,100,$params['level']);
        
        $data   = array(
                'manager_idx'           => $params['uuid'],
                'manager_name'          => $manager_name,
                'plib_idx'              => $player_lib['idx'],  
                'player_no'             => $player_lib['player_no'],
                'level'                 => $params['level'],
                'generalskill_no'       => $player_lib['generalskill_no'],
                'generalskill_level'    => $player_lib['generalskill_no']?1:0,
                'exclusiveskill_no'     => $player_lib['exclusiveskill_no'],
                'is_use'                => 0,
                'position_no'           => $params['position_no'],
                'cposition_type'        => $params['cposition_type'],
                'reduce_value'          => 0,
                'fatigue'               => 0,
                'speed'                 => $attribute['speed'],
                'shoot'                 => $attribute['shoot'],
                'free_kick'             => $attribute['free_kick'],
                'acceleration'          => $attribute['acceleration'],
                'header'                => $attribute['header'],
                'control'               => $attribute['control'],
                'physical_ability'      => $attribute['physical_ability'],
                'power'                 => $attribute['power'],
                'aggressive'            => $attribute['aggressive'],
                'interfere'             => $attribute['interfere'],
                'steals'                => $attribute['steals'],
                'ball_control'          => $attribute['ball_control'],
                'pass_ball'             => $attribute['pass_ball'],
                'mind'                  => $attribute['mind'],
                'reaction'              => $attribute['reaction'],
                'positional_sense'      => $attribute['positional_sense'],
                'hand_ball'             => $attribute['hand_ball'],
                'status'                => 1,
        );
        $res    = $this->court_model->insert_data($data,'player_info');
        var_dump($res);exit;
    }
    
    
    public function view()
    {
        $this->load->view('test.php') ;
    }
    
    public function draw()
    {
        $res = $this->draw_lib->do_draw(1);
        var_dump($res);exit;
    }
    
    public function player_upgrade()
    {
        $res = $this->tips_lib->get_novice_coures_tips(69);
        exit;
    }
    
    public function m_updata()
    {
        $fields =    array('vip'=>1);
        $where  = array('idx'=>5);
        $res = $this->manager_lib->update_manager_info($fields,$where);
        // $res = $this->manager_lib->m_level_do(6,2);
       var_dump($res);exit;

    }
    
    /**
     * 统计副本战斗力
     */
    public function count_copy_finghting()
    {
        $params['app_id']       = 1;
        $params['uuid']         = 6;
        $params['token']        = "123123";
        $params['sign']         = "12313";
        $params['sweep']        = 2;// 1扫荡2挑战
        
        $options['where']   = array('status'=>1);
        $options['fields']  = "copy_no,type,ckpoint_no";
        $ckpoint_list   = $this->match_model->list_data($options, 'ckpoint_conf');
        foreach ($ckpoint_list as $k=>$v) {
            $params['copy_no']      = $v['copy_no'];
            $params['ckpoint_no']   = $v['ckpoint_no'];
            $params['type']         = $v['type'];// 赛事类型 1常规普通赛 2常规精英赛 
            
            // 统计副本关卡战斗力
            $player = $this->match_lib->match_for_copy($params);
            $sum    = 0;
            foreach ( $player as $k=>$v) {
                foreach ($v['attribute'] as $key=>$val) {
                    $sum    += $val;
                }
            }
            $this->load->model('match_model');
            $data   = array(
                'copy_no'       => $params['copy_no'],
                'ckpoint_no'    => $params['ckpoint_no'],
                'type'          => $params['type'],
                'finghting'     => $sum,
                'status'        => 1,
            );
            $res = $this->match_model->insert_data($data,'finghting_result');
        }
        var_dump(111);
    }
    
    /**
     * 测试天梯赛排名
     */
    public function ladder_ranking()
    {
        $params['id_1'] = $this->request_params('id_1');
        $params['id_2'] = $this->request_params('id_2');
        if ($params['id_1'] == $params['id_2']) {
            return false;
        }
        
        // 获取战队1的能力
        // 获取机器人使用的阵型信息
        $where      = array('idx'=>$params['id_1'],'status'=>1);
        $fields     = "rebot_no,level,structure_no,player_nos,discount";
        $rebot_conf = $this->match_model->get_one($where,'rebot_conf',$fields);
        
        $where4         = array('structure_no'=>$rebot_conf['structure_no'],'status'=>1);
        $fields4        = "type,attradd_object,attradd_percent";
        $structure_info = $this->match_model->get_one($where4, 'structure_conf',$fields4);
        $p_arr      = explode("|", trim($rebot_conf['player_nos'],"|"));
        foreach ($p_arr as $k=>$v) {
            $player         = explode(':', $v);
            $player_info    = $this->match_lib->attr_statis_by_playerno($player[0],$player[1],$rebot_conf['discount'],$rebot_conf['structure_no']);
            $player_info['level']        = $player[1];
            $player_info['generalskill_no']?$player_info['generalskill_level']=1:$player_info['generalskill_level']=0;
            $player_info['position_no']  = $k;
            $player_2[] = $player_info;
        }
        $result['home']  = array('player'=>$player_2,'structure'=>$structure_info['type']);
        
        // 获取战队2的能力
        $where_2        = array('idx'=>$params['id_2'],'status'=>1);
        $fields_2       = "rebot_no,level,structure_no,player_nos,discount";
        $rebot_conf_2   = $this->match_model->get_one($where_2,'rebot_conf',$fields_2);
        $where4         = array('structure_no'=>$rebot_conf_2['structure_no'],'status'=>1);
        $fields4        = "type,attradd_object,attradd_percent";
        $structure_info = $this->match_model->get_one($where4, 'structure_conf',$fields4);
        $p_arr      = explode("|", trim($rebot_conf_2['player_nos'],"|"));
        foreach ($p_arr as $k=>$v) {
            $player         = explode(':', $v);
            $player_info    = $this->match_lib->attr_statis_by_playerno($player[0],$player[1],$rebot_conf_2['discount'],$rebot_conf_2['structure_no']);
            $player_info['level']        = $player[1];
            $player_info['generalskill_no']?$player_info['generalskill_level']=1:$player_info['generalskill_level']=0;
            $player_info['position_no']  = $k;
            $player_3[] = $player_info;
        }
        $result['away']  = array('player'=>$player_3,'structure'=>$structure_info['type']);
        // 进行比赛，获取比赛结果
        $match_key  = $params['id_1']."_ladder_".$params['id_2']."_".time();
        $result_arr = $this->match_lib->result_for_match($match_key,$result);
        if ($result_arr['HomeScore'] > $result_arr['AwayScore']) {
            $result = 1;// 赢
        } elseif($result_arr['HomeScore'] == $result_arr['AwayScore']) {
            $result = 2;// 平
        } else {
            $result = 3;// 败
        }
        
        echo ($result_arr['HomeScore'].":".$result_arr['AwayScore']);exit;
    }
    
    /**
     * 奖励测试
     */
    public function achieve_test()
    {
        $this->task_lib->achieve_fighting(7,21);
    }
    
   public function get_file()
   {
       $aa  = file_get_contents('//172.18.248.3/文件暂放区/新业务事业部/1.png',true);
       var_dump($aa);exit;
   }
   
   /**
    * 月卡发球票测试
    */
    public function month_card()
    {
        $this->load->library('vitality_lib');
        $this->vitality_lib->month_card_reward();
        return true;
    }
   
    /**
     * 成就测试数据添加
     */
    public function achievement()
    {
        // 获取成就列表
        $options['where']   = array('status'=>1);
        $options['fields']  = "module_no,module,achieve_catno,achieve_no,achievepoint,euro,tickets,soccer_soul,powder,prop_info,gem_info,player_info,equipt_info";
        $list   = $this->match_model->list_data($options, 'achievement_conf');
        foreach ($list as $k=>$achievement) {
            $data   = array(
                'manager_idx'   => 200,
                'achieve_no'    => $achievement['achieve_no'],
                'module_no'     => $achievement['module_no'],
                'achieve_catno' => $achievement['achieve_catno'],
                'achievepoint'  => $achievement['achievepoint'],
                'euro'          => $achievement['euro'],
                'tickets'       => $achievement['tickets'],
                'soccer_soul'   => $achievement['soccer_soul'],
                'powder'        => $achievement['powder'],
                'prop_info'     => $achievement['prop_info'],
                'gem_info'      => $achievement['gem_info'],
                'player_info'   => $achievement['player_info'],
                'equipt_info'   => $achievement['equipt_info'],
                'receive'       => 0,
                'status'        => 1,
            );
            $res = $this->match_model->insert_data($data,'achievement_complete');
        }
        var_dump($res);
    }
    
    public function test()
    {
        echo 'test';
        $pattern    = '/.*你好.*/u';;
        $str        = "的鹅鹅你好鹅饿";
        preg_match($pattern, $str,$matches);
        var_dump($matches);
    }
   
}
