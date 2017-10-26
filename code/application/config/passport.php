<?php

// REDIS 存储
$config['token_key']    = "RXQQ34Hid7Ykd9djchd832";//token加密的key
$config['token_expire'] = 30*24*3600;//用户登录态过期时间（秒）
$config['token_pre']    = 'TK_';
$config['manager_info'] = "M_INFO_";// 经理登录信息 存放到redis --- redis存储前缀
$config['static_table'] = "STATIC_TABLE_";// 静态表 存放到redis --- redis存储前缀
$config['match_result'] = "Match_";// 比赛结果 存放到redis  --- redis存储前缀
$config['match_result_file']    = "./match/result/s001/";// 比赛结果 存放文件位置
$config['request_match']= 'Request_Match_';

$config['rmb_rate']     = 10;// 球票汇率 1元=10球票

$config['skill']        = "SKILL_";// 技能存储前缀
$config['plib_list']    = "PLIB_LIST_";// 球员库列表数据
$config['player_lib']   = "PLIB_";// 球员库信息
$config['player_info']  = "PINFO_";//球员基础信息
$config['m_info']       = "MINFO_";// 经理基础信息

// sign加密的key
$config['sign'] = 'RXQQSIgn7gXLvCu8h668o8buYRd';

// 赛事系统
$config['video_save_time'] = 259200;// 保存3天
$config['match_exp']    = array('win' => 200, 'draw' => 150, 'lose' => 100); // 常规、精英 副本比赛经验奖励
$config['expend_ps']    = array('common'=>4,'elite'=>8);// 经理比赛常规精英赛，消耗体力值
$config['copy_sweep']   = array('common'=>5,'elite'=>2); // 常规赛 每个关卡每天 可以免费扫荡 5次
$config['ladder_cd']    = 5; // 天梯赛 冷却时间 5 分钟
$config['ladder']       = array('euro_reward'=>1000,'honor_reward'=>20,'expend_endurance'=>5);
$config['ladder_rule']  = array('less'=>'','greater'=>'');
$config['league']       = array('free_reset' => 1,'reset_tickets'=>6);// 天梯赛免费重置次数,重置消耗球票数
$config['sweep_league'] = array('time'=>15,'tickets'=>1);// 每关扫荡15S,每关1球票
$config['clear_fatigue_tickets']    = 20;// 清空疲劳值消耗球票数
$config['update_level_add_phy']     = 10;// 经理升级，体力恢复10点

// 值校验
$config['player_type']      = array(1,2,3);// 1:场上球员 2：闲置球员 3所有球员
$config['attribute_arr']    = array('speed','shoot', 'free_kick', 'acceleration', 'header', 'control', 'physical_ability', 'power', 'aggressive', 'interfere', 'steals', 'ball_control', 'pass_ball', 'mind', 'reaction', 'positional_sense', 'hand_ball');
$config['attribute_max']    = 200;// 最大属性值
$config['fatigue_max']      = 100;// 最大疲劳值
$config['fatigue_type']     = array('1'=>36,'2'=>76,'100');// 疲劳值小于36=》绿色 小于76=》橙色 小于100=》红色

$config['fingting_server']  = array('ip'=>'172.18.67.125','port'=>9000);
// 经理体力值
$config['m_active']         = array(
                                'phy_init'          =>56,// 体力初始值
                                'phy_step'          => 2,// 经理每升一级，提升2点
                                'phy_recove'        => 4,// 经理每升一级，恢复4点
                                'phy_max'           =>100,// 体力上限
                                'phyrecover_time'   =>600,// 600S恢复一点体力
                                'endurance_max'     => 50,// 耐力上限，耐力值不变
                                'endurecover_time'  =>'22:00:00');// 耐力恢复时间

$config['p_active']         = array('fatigue_max'   =>100,'f_step'=>5);// 球员疲劳值配置
$config['reset_talent_tickets'] = 10;// 重置天赋所需球票数

// VIP特权文本信息
// vip可购买体力次数
$config['vip_privilege'] = array(
    'buy_phystrength'   => "每天可购买[value]次大瓶体力药水",
    'buy_endurance'     => "每天可购买[value]次大瓶耐力药水",
    'buy_traninground'  => "可购买[value]个训练位",
    'buy_europrint'     => "每天可购买[value]个印钞机",
    'sweep_num'         => "每天可购买[value]次精英常规赛扫荡次数",
    'fivereset_num'     => "每天可购买[value]次五大联赛重置次数",
    'eurodraw_num'      => "每天可以免费额外抽取[value]次欧元抽卡",
);

//充值回调地址
$config['pay_callback'] = "http://api.rxqq.the9.com/"; 
//微信appid
$config['appid']  = "wx354decb879463528"; 
//微信私钥
$config['secret'] = "aa9dd9418300c6c2678c9752b7b6f90a"; 
//微信支付分配的商户号
$config['mchid'] = "1235376302"; 
//微信商户平台设置的密钥
$config['wx_key'] = "fc71deec27ac47cfa0df1582fc4b241b"; 

