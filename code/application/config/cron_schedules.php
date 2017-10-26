<?php
// 计划任务---恢复经理耐力
$cron_schedule['m_endurance'] = array(
    'run' => array(
        'filepath'      => 'cron', // 文件所在的目录 相对于APPPATH
        'filename'      => 'Vitality.php', // 文件名
        'class'         => 'Vitality', // 类名 如果只是简单函数 可为空
        'function'      => 'm_endurance', // 要执行的函数
        'params'        => array(), // 需要传递的参数
        'exec_type'     => 1,// 1按照执行时间2按照间隔时间
        'execute_time'  => "22:00:00",// 定点执行时间
    )
);
// 经理体力
$cron_schedule['m_phystrenth'] = array(
    'run' => array(
        'filepath'      => 'cron', // 文件所在的目录 相对于APPPATH
        'filename'      => 'Vitality.php', // 文件名
        'class'         => 'Vitality', // 类名 如果只是简单函数 可为空
        'function'      => 'm_phystrenth', // 要执行的函数
        'params'        => array(), // 需要传递的参数
        'exec_type'     => 2,// 1按照执行时间2按照间隔时间
        'interval_time' => 600,// 执行间隔时间（s）
    )
);
// 球员疲劳值
$cron_schedule['p_fatigue'] = array(
    'run' => array(
        'filepath'      => 'cron', // 文件所在的目录 相对于APPPATH
        'filename'      => 'Vitality.php', // 文件名
        'class'         => 'Vitality', // 类名 如果只是简单函数 可为空
        'function'      => 'p_fatigue', // 要执行的函数
        'params'        => array(), // 需要传递的参数
        'exec_type'     => 2,// 1按照执行时间2按照间隔时间
        'interval_time' => 6000,// 执行间隔时间（s）
    )
);

// 月卡球票方法
$cron_schedule['month_card'] = array(
    'run' => array(
        'filepath'      => 'cron', // 文件所在的目录 相对于APPPATH
        'filename'      => 'Vitality.php', // 文件名
        'class'         => 'Vitality', // 类名 如果只是简单函数 可为空
        'function'      => 'month_card', // 要执行的函数
        'params'        => array(), // 需要传递的参数
        'exec_type'     => 1,// 1按照执行时间2按照间隔时间
        // 'execute_time'  => "23:50:00",// 定点执行时间
        'execute_time'  => "17:45:00",// 定点执行时间
    )
);
