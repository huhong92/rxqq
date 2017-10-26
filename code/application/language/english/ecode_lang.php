<?php
// 公共错误码
$lang['success']    = '操作成功';
$lang['fail']       = '操作失败';
$lang['sign_err']       = '签名不通过';
$lang['params_err']     = '参数错误';
$lang['db_err']         = '服务器异常';
$lang['empty_data']     = '未查询到相关数据';
$lang['token_set_err']  = 'TOKEN设置失败';
$lang['token_err']      = 'TOKEN失效,请重新登录';
$lang['redis_server_err']   = '缓存服务器错误';

// Manager 相关错误码
$lang['register_err']           = '第三方注册失败';
$lang['login_err']              = '登录失败';
$lang['m_info_err']             = '经理名称队标存储失败';
$lang['m_info_get_fail']        = '经理基本信息获取失败';
$lang['m_info_update_err']      = '经理信息更新失败';
$lang['team_logo_empty_data']   = '经理队标库为空';
$lang['lineup_list_empty']      = '初始阵容列表为空';
$lang['lineup_err']             = '经理存在球员卡数据，不能初始化阵容';
$lang['lineup_fomat_err']       = '初始化阵容失败';
$lang['update_exp_err']         = '经理经验值更新失败';
$lang['one_update_teamlogo_error']  = '只能更新一次队标';
$lang['update_teamlogo_error']      = '更新队标失败';
$lang['init_lineup_err']            = '初始化阵容历史记录插入失败';
$lang['init_m_info_err']            = '经理信息初始化失败';
$lang['teamlogo_init_exists']       = '队标已经初始化过';
$lang['name_init_exists']           = '经理名称已经初始化过';
$lang['lineup_init_exists']         = '经理阵容已经初始化';
$lang['m_level_not_enought']        = '经理等级不足';
$lang['vip_level_not_enought']      = '经理VIP等级不足';
$lang['m_phystrenght_not_enought']      = '体力不足';

// 经理天赋
$lang['not_enought_talentpoint_err']    = '没有足够的天赋点';
$lang['talent_active_err']              = '经理天赋激活失败';
$lang['talent_insert_err']              = '天赋插入失败';
$lang['talent_insert_his_err']          = '天赋激活历史记录插入失败';
$lang['talent_reset_err']               = '天赋重置失败';
$lang['talent_reset_his_err']           = '天赋重置历史记录插入失败';
$lang['talent_actived_err']             = '该天赋已激活';




// Player 相关错误码
$lang['player_type_err']            = '您输入的球员type值有误';
$lang['court_player_num']           = '球场上阵人数超过7人';
$lang['enter_court_7_num_err']      = '球场上阵人数必须为7人';
$lang['enter_court_err']            = '球员下阵失败';
$lang['leave_court_err']            = '球员离场失败';
$lang['player_id_err']              = '经理暂无该球员卡';
$lang['player_updrade_empty_data']  = '暂无球员升阶信息';
$lang['get_attr_add_err']           = '获取属性加成失败';
$lang['player_not_court_err']       = '该球员不在球场上';
$lang['player_sameas_err']          = '球场上已有相同编号的球员';


$lang['player_update_err']      = '球员信息更新失败';
$lang['player_doubles_err']     = '球员替身数与升阶所需数不匹配';
$lang['star_doubles_err']       = '球星替身数与升阶所需数不匹配';
$lang['superstar_doubles_err']  = '巨星替身数与升阶所需数不匹配';
$lang['player_upgrade_err']     = '球员升阶失败';
$lang['player_upgrade_history_err'] = '球员升阶历史记录失败';
$lang['player_not_exist']           = '该球员卡不存在';
$lang['player_upgrade_history_err'] = '球员进阶记录失败';
$lang['player_upgrade_empty_data']  = '未查询到球员升级数据';
$lang['use_player_exchange_err']    = '只有上场球员才可换位操作';
$lang['exchange_position_err']      = '球员换位失败';
$lang['upgrade_prop_not_enought_err']    = '升阶球员卡所需的道具升阶卡不足';
$lang['highest_level_err']          = '该球员卡以达到最高Level,不允许升级';


// 将要删除

// 球场相关
        // ---训练场
$lang['without_train_err']  = '无空闲训练场';
$lang['tickets_num_err']    = '球票数不正确';
$lang['player_without_train']   = '该球员未训练，或者训练完成';
$lang['tickets_clear_err']  = '球票取消训练时间失败';
$lang['trainpoint_not_found']   = '该球员暂无未分配的训练点';
$lang['position_cannt_train']   = '该训练位暂不可训练球员';
$lang['trainpoint_err']             = '该球员暂无训练点数';
$lang['allo_trainpoint_err']        = '训练点数与球员训练点数不匹配';
$lang['fatigue_not_enough']         = '球员疲劳值不足';
$lang['do_train_err']               = '球员执行训练操作失败';
$lang['player_training']            = '该球员正在训练，请稍后操作';
$lang['player_lib_empty_data']      = '未查询到该球员的卡库信息';
$lang['without_fatigue_err']         = '暂无疲劳值，不需要清空';
$lang['fatigue_clear_fail_err']         = '疲劳值清空失败';
$lang['train_his_insert_fail_err']      = '训练历史记录插入失败';
$lang['not_need_release_err']      = '该球员不在训练状态，不需要释放训练位';

$lang['update_trainpoint_err']      = '球员训练点数更新失败';
$lang['trainpoint_his_empty_data']  = '未查询到球员训练点历史记录数据';
$lang['polish_stone_error']         = '训练点洗炼失败';
$lang['polish_stone_not_enought']   = '没有足够的洗炼石';
$lang['train_unlock_err']           = '训练场解锁失败';
$lang['trainground_exists_err']     = '该训练场已解锁';
$lang['not_allow_reset_err']        = '该球员不允许重置训练点';
$lang['reset_trainpoint_err']       = '重置训练点失败';
$lang['not_enought_trainpoint_err'] = '没有足够的训练点数';
$lang['same_one_attribute_err']     = '只能分配给同一个一级属性下的二级属性';
$lang['not_add_level_one_attribute_err']      = '不允许给一级属性分配训练点';
$lang['allo_trainpoint_err']         = '训练点分配失败';
$lang['trainpoint_deduct_err']      = '训练点数扣除失败';
$lang['release_tg_err']             = '训练位释放失败';
$lang['train_not_complete_err']     = '训练未完成,不允许释放训练位';
$lang['trainpoint_overplus_err']    = '训练点未分配完成,不允许释放训练位';
$lang['point_upper_limit_err']      = '超过训练点上限';
        
        // ---阵型
$lang['structure_update_err']   = '经理阵型更换失败';
$lang['structure_empty_data']   = '暂无阵型数据';
$lang['structure_lock_err']     = '该阵型暂未解锁';
$lang['insert_structure_err']   = '插入阵型失败';
$lang['insert_structure_error'] = '阵型初始化失败';
$lang['structure_unlock_error'] = '阵型解锁失败';
$lang['structure_his_err']      = '记录更新阵型历史失败';

        // ---道具
$lang['only_use_potion_strength']   = '只能使用体力药水恢复经理体力值';
$lang['physical_recover_err']       = '体力恢复失败';
$lang['physical_strength_enought']  = '经理当前体力达到上限,不需要恢复';
$lang['without_potion_strength']    = '经理没有该体力药水';

$lang['only_use_potion_endurance']  = '只能使用耐力药水恢复经理耐力值';
$lang['endurance_enought']          = '经理当前耐力达到上限,不需要恢复';
$lang['without_potion_endurance']   = '经理没有该耐力药水';
$lang['endurance_recover_err']      = '耐力恢复失败';

$lang['only_use_euro_prop']         = '只能使用欧元道具';
$lang['without_euro_prop']          = '经理没有该欧元道具';
$lang['euro_prop_use_err']          = '该欧元道具使用失败';

$lang['prop_conf_err']              = "道具配置文件错误";
$lang['insert_prop_err']            = '道具插入失败';

$lang['without_prop_err']           = '暂无该道具';
$lang['insert_prop_his_err']        = '道具使用记录插入失败';

        // ---装备
$lang['equipt_highest_level']       = '当前装备等级已到达最高级，不能进行升级';
$lang['equipt_type_err']            = '装备升级|强化type参数错误';
$lang['equipt_upgrade_info_empty']  = '未查询到装备升级|强化所需材料信息';
$lang['euro_num_err']               = '升级|强化装备所需欧元数不匹配';
$lang['junior_card']                = '升级|强化装备所需初级升阶卡数不匹配';
$lang['middle_card']                = '升级|强化装备所需中级升阶卡数不匹配';
$lang['senio_card']                 = '升级|强化装备所需高级升阶卡数不匹配';
$lang['upgrade_equipt_err']         = '装备升级|强化失败';
$lang['unload_equipt_err']          = '装备卸下失败';
$lang['equipt_update_err']          = '经理装备更新失败';
$lang['equipt_position_empty']      = '该装备位暂无装备';
$lang['load_equipt_err']            = '球员装载装备失败';
$lang['equipt_is_used_err']         = '该装备已被使用';
$lang['unload_equipt_err']          = '卸载装备失败';
$lang['equipt_not_used_err']        = '该装备未被球员使用，暂不能卸载';
$lang['equipt_empty_data']          = '经理暂无该装备';
$lang['insert_equipt_err']          = '装备插入失败';
$lang['not_allow_use_equipt_err']   = '不允许装载该装备';

// 宝石
$lang['insert_gem_err']         = '宝石插入失败';
$lang['update_gem_info_err']    = '宝石更新失败';
$lang['without_gem_info_err']   = '暂无该宝石';
$lang['without_gem_holes_err']  = '该装备暂无闲置宝石孔';
$lang['gem_holes_insert_err']   = '镶嵌宝石失败';
$lang['without_gem_of_equip']   = '没有镶嵌该宝石的装备';
$lang['gem_delete_err']         = '取下宝石失败';

        // --- 分解
$lang['decompose_err']              = '分解操作失败';
$lang['decompose_product_empty']    = '分解物暂未配置分解产物';
$lang['player_not_allow_decompose_err']   = '场上球员不能分解';
$lang['gem_not_allow_decompose_err']   = '镶嵌的宝石不能分解';
$lang['equipt_not_allow_decompose_err']   = '已装载的装备不能分解';

        // ---意志
$lang['volition_type_err']          = '参数type输入有误';
$lang['insert_player_err']          = '卡牌插入失败';
$lang['neednot_player_err']         = '该意志不需要该卡牌';
$lang['volition_active_err']        = '该意志已激活';
$lang['inserted_player_err']        = '该卡牌已插过';
$lang['insert_volition_his_err']    = '意志插卡历史记录插入失败';
$lang['update_volition_err']        = '经理意志信息修改失败';
$lang['insert_volition_err']        = '经理意志信息插入失败';
$lang['lack_player_err']            = '未达到激活条件';
$lang['active_group_err']           = '组合激活失败';
$lang['not_enough_euro_err']        = '欧元不足';
$lang['not_enough_soccersoul_err']  = '球魂不足';
$lang['group_active_exists']        = '该组合已激活';
$lang['not_enough_tickets_err']     = '球票不足';
$lang['insert_player_level_not_enought_err']    = '卡牌等级不足，不允许插入';


// 赛事
$lang['finghting_connect_err']    = '战斗服务器链接失败';
// 常规赛事
$lang['match_type_empty']           = '暂无赛事类型列表数据';
$lang['copy_list_empty']            = '暂无副本列表数据';
$lang['copy_reward_empty']          = '暂无满星奖励数据';
$lang['copy_reward_his_err']        = '已经领取过奖励';
$lang['ins_copy_reward_his_err']    = '插入满星奖励领取历史失败';
$lang['copy_reward_get_err']        = '领取满星奖励失败';
$lang['ckpoint_list_empty']         = '暂无关卡列表数据';
$lang['ckpoint_info_empty']         = '该关卡详细信息获取失败';
$lang['ckpoint_match_err']          = '副本关卡挑战失败';
$lang['ckpoint_sweep_err']          = '副本关卡扫荡失败';
$lang['ckpoint_sweep_not_allow_err']          = '副本关卡扫荡失败';
$lang['ckpoint_his_insert_err']          = '副本赛历史记录插入失败';
$lang['free_sweep_null_err']          = '暂无免费扫荡次数';
$lang['onekey_sweep_err']          = '一键扫荡失败';
$lang['ckpointno_yet_not_unlock']          = '关卡未解锁';

// 五大联赛
$lang['match_not_unlock_err']       = "经理等级不足，该赛事未解锁";
$lang['ckpoint_not_challenger_err'] = '当前关卡不能挑战';
$lang['match_his_record_err']       = '比赛历史记录插入失败';
$lang['fiveleague_max_insert_err']  = '五大联赛最高关卡插入失败';
$lang['fiveleague_max_update_err']  = '五大联赛最高关卡更新失败';
$lang['fiveleague_curr_insert_err']  = '五大联赛当前关卡插入失败';
$lang['fiveleague_curr_update_err']  = '五大联赛当前关卡更新失败';
$lang['without_ckpoint_info_err']   = '暂无该关卡信息';
$lang['without_league_ranking_err'] = '暂无冲关排行信息';
$lang['league_reset_fail_err']      = '五大联赛重置失败';
$lang['league_reset_enought_err']   = '超过每日重置次数';
$lang['not_allow_sweep_err']            = '不允许扫荡';
$lang['not_allow_sweep_ckpoint_err']    = '不允许扫荡到当前关卡';
$lang['sweep_league_fail_err']          = '五大联赛扫荡失败';
$lang['sweep_league_insert_his_err']    = '五大联赛扫荡历史记录插入失败';
$lang['last_sweep_not_conmplete_err']   = '上一轮扫荡未完成';
$lang['sweep_first_err']                = '请先扫荡';
$lang['fiveleague_update_time_err']     = '五大联赛立即完成时间更新失败';

// 天梯赛
$lang['bechallenger_isdoing_err']   = '被挑战者正在比赛,不允许被挑战';
$lang['ranking_update_err']         = '排名更新失败';
$lang['is_bechallenger_err']        = '你当前被其他玩家挑战,暂不允许挑战其他玩家';
$lang['match_status_update_err']    = '比赛状态更新失败';
$lang['cross_stage_his_err']        = '跨新区段历史记录插入失败';
$lang['endurance_not_enought_err']  = '经理耐力不足';
$lang['rebot_not_found']            = '暂无该机器人信息';
$lang['init_ranking_fail']          = '天梯初始排名失败';
$lang['first_init_ranking_fail']    = '请先初始天梯排名';

// 商城
$lang['goods_buy_err']              = "商品购买失败";
$lang['goods_cannot_buy']           = "该商品不再可购买时间段，禁止购买操作";
$lang['currency_not_enought']       = '货币不足';
$lang['prop_goods_buy_err']         = '购买道具商品失败';
$lang['vip_goods_buy_err']          = '购买vip礼包失败';
$lang['vip_level_lack']             = '经理当前VIP等级不足';
$lang['equipt_goods_buy_err']       = '购买装备商品失败';
$lang['buy_only_one_err']           = 'VIP礼包只能购买一次';
$lang['vip_package_empty_err']      = '暂无VIP礼包';
// VIP
$lang['goods_limit_buy_err']        = "该商品超过限购次数";
$lang['vip_pack_send_equipt_err']   = "发送 VIP礼包-装备失败";
$lang['vip_pack_send_prop_err']     = "发送 VIP礼包-道具失败";
$lang['vip_pack_send_gem_err']      = "发送 VIP礼包-宝石失败";
$lang['vip_pack_send_player_err']   = "发送 VIP礼包-球员失败";
$lang['vip_pack_use_err']           = "消耗VIP礼包失败";


// 活动


// 抽卡
$lang['tenth_draw_not_allow']   = '该抽卡类型不允许10连抽';
$lang['not_enough_prop_err']    = '没有足够的道具';
$lang['expend_prop_err']        = '抽卡道具消耗更新失败';
$lang['draw_err']               = '抽卡失败';


// 邮件|邮箱
$lang['get_video_content_err']  = '获取视频回放数据失败';
$lang['video_expire_err']    = '视频已过期';
$lang['mail_do_read_err']    = '邮件读取操作记录失败';
$lang['mail_do_delete_err']    = '邮件删除失败';
$lang['mail_delete_cannot_receive_err'] = '邮件已删除，不能领取奖励';
$lang['cannot_receive_donot_read_err']  = '请先读取邮件，再领取奖励';
$lang['mail_receive_reward_err']        = '邮件奖励已领取';
$lang['mail_receive_reward_fail_err']   = '奖励领取失败';
$lang['mail_reward_all_fail_err']       = '奖励一键领取失败';
$lang['mail_insert_fail']               = '邮件插入失败';

// 支付


// 新手引导




// 成就
$lang['achieve_without_complete_err']   = "成就未完成，不能领取奖励";
$lang['achieve_down_receive_err']       = "奖励已领取，不能重复领取";
$lang['achieve_reward_receive_err']     = "成就奖励领取失败";

//任务
$lang['complete_task_err']        =  "完成任务操作失败";
$lang['task_send_equipt_err']     =  "发送任务装备奖励失败";
$lang['task_send_prop_err']       =  "发送任务道具奖励失败";
$lang['task_send_gem_err']        =  "发送任务宝石奖励失败";
$lang['task_send_reward_err']     =  "发送任务奖励失败";
$lang['task_receive_err']         =  "更新任务领取领取状态失败";
$lang['task_complete_err']        =  "任务尚未完成";
$lang['task_have_receive']        =  "不能重复领取任务奖励";
$lang['get_tasklist_err']         =  "获取任务列表失败";
$lang['type2task_condition_num']  =  "更新每日任务达成次数失败";

//红点提示 add_page_tip
$lang['select_page_tip']      =  "没有当前提示";
$lang['add_page_tip']         =  "新增提示失败";
$lang['del_page_tip']         =  "删除提示失败";

//新手任务
$lang['update_novice_coures'] =  "更新新手任务状态失败";

//充值相关错误码
$lang['get_recharge_pack_info'] =  "获取充值包信息失败";
$lang['get_upgrade_conf_info']  =  "获取升阶信息失败";
$lang['insert_order_err']       =  "插入订单失败";
$lang['present_pack_fail']      =  "首充礼包获取失败";
$lang['get_wx_oprnid']          =  "获取微信授权用户openid失败";
$lang['get_access_token']       =  "获取access_token失败";
$lang['get_jsapi_ticket']       =  "获取jsapi_ticket失败";