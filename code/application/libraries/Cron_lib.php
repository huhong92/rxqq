<?php
class Cron_lib {
    
    // 计划任务配置
    protected $enable_cron = TRUE;// 是否开启cron schedule
    protected $cron_schedule_table_name = 'cron_schedule';//任务表
    protected $cron_schedule_lifetime= 300;//任务有效时间5分钟
    
    protected $ScheduleIsertLifeTime    = 300;// 任务插入有效过期时间 5分钟
    protected $cron_schedule_generate_every = 15;//生成cron schedule的时间间隔
    
    
    protected $cron_history_cleanup_every= 600;//清除cron schedule的时间间隔 秒
    protected $cron_history_success_lifetime = 3600;//运行成功的cron schedule的保存时间 秒
    protected $cron_history_failure_lifetime = 36000;//运行失败的cron schedule的保存时间 秒
    
    protected $cron_schedule = array();
    protected $CI;
    
    public function __construct($params = array())
    {
        log_message('debug', 'Cron_schedule Class Initialized');
        $this->CI =& get_instance();
        if ($this->enable_cron == FALSE) {
            return;
        }

        if (defined('ENVIRONMENT') AND is_file(APPPATH.'config/'.ENVIRONMENT.'/cron_schedules.php')) {
            include(APPPATH.'config/'.ENVIRONMENT.'/cron_schedules.php');
        } elseif (is_file(APPPATH.'config/cron_schedules.php')) {
            include(APPPATH.'config/cron_schedules.php');
        }

        if ( ! isset($cron_schedule) OR ! is_array($cron_schedule)) {
            return;
        }
        
        $this->cron_schedule =& $cron_schedule;
        
        $this->CI->load->database();
    }
    
    /**
     * 获取等待执行的任务
     */
    public function getPendingSchedules()
    {
        $where  = array('cron_status'=>3,'status'=>1);
        return $this->CI->db->where($where)->get($this->cron_schedule_table_name)->result_array();
    }
    
    /**
     * 获取执行成功任务列表
     * @param type $job_code
     * @return type
     */
    public function getSuccessSchedules($job_code)
    {
        $where  = array('job_code'=>$job_code,'cron_status'=>1,'status'=>1);
        return $this->CI->db->where($where)
                ->limit(1)
                ->order_by('idx','desc')
                ->get($this->cron_schedule_table_name)
                ->row_array();
    }
    
    /**
     * 获取最近插入的任务信息
     */
    public function getLastTimeInsertSchedule($job_code)
    {
        $where  = array('job_code'=>$job_code,'status'=>1);
        return $this->CI->db->where($where)
                ->limit(1)
                ->order_by('idx','desc')
                ->get($this->cron_schedule_table_name)
                ->row_array();
    }
    
    /**
     * 插入计划任务
     * @param type $schedule
     * @return type
     */
    public function insertSchedule($schedule)
    {
        $schedule['status']         = 1;
        $schedule['cron_status']    = 3;
        $schedule['time']           = time();
        $schedule['update_time']    = time();
        return $this->CI->db->insert($this->cron_schedule_table_name, $schedule);
    }
    
    /**
     * 更新计划任务
     * @param type $schedule_id
     * @param type $fields
     * @return type
     */
    public function updateSchedule($schedule_id,$fields)
    {
        return $this->CI->db->where('idx', $schedule_id)
            ->update($this->cron_schedule_table_name, $fields);
    }
    
    /**
     * 删除计划任务
     * @param type $schedule_id
     * @return type
     */
    public function deleteSchedule($schedule_id)
    {
        return $this->CI->db->where('idx', $schedule_id)
            ->delete($this->cron_schedule_table_name);
    }


    /**
     * 执行计划任务
     * @throws Exception
     */
    public function dispatch()
    {
        $this->generate();
        // 获取执行任务列表
        $schedules          = $this->getPendingSchedules();
        $scheduleLifetime   = $this->cron_schedule_lifetime;// 任务有效时间300s
        // 判断是否有需要执行的任务
        if ($schedules) {
            foreach ($schedules as $schedule) {
                $jobConfig  = $this->cron_schedule[$schedule['job_code']]['run'];
                try {
                    if ( ! isset($jobConfig['filepath']) OR ! isset($jobConfig['filename'])) {
                        throw new Exception('No filepath or filename found.');
                    } 
                    $filepath = APPPATH.$jobConfig['filepath'].'/'.$jobConfig['filename'];
                    if ( ! file_exists($filepath)) {
                        throw new Exception('No cron schedule file found.');
                    }
                    $class      = FALSE;
                    $function   = FALSE;
                    $params     = '';
                    if (isset($jobConfig['class']) AND $jobConfig['class'] != '') {
                        $class = $jobConfig['class'];
                    }

                    if (isset($jobConfig['function'])) {
                        $function = $jobConfig['function'];
                    }
                    if (isset($jobConfig['params'])) {
                        $params = $jobConfig['params'];
                    }
                    if ($class === FALSE AND $function === FALSE) {
                        throw new Exception('No cron schedule function found.');
                    }
                    $result = $this->updateSchedule($schedule['idx'], array(
                        'cron_status'   => 5,
                        'exec_time'=>time(),
                        'update_time'=>time()
                    ));
                    if ( ! $result) {
                        continue;
                    }
                    if ($class !== FALSE) {
                        if ( ! class_exists($class)) {
                            require($filepath);
                        }
                        $SCHEDULE = new $class;
                        $SCHEDULE->$function($params);
                    } else {
                        if ( ! function_exists($function)) {
                            require($filepath);
                        }

                        $function($params);
                    }

                    $this->updateSchedule($schedule['idx'], array(
                        'cron_status'   => 1,
                        'finish_time'   => time()
                    ));

                } catch (Exception $e) {
                    $this->updateSchedule($schedule['idx'], array(
                        'cron_status'   => 2,
                        'message'       => $e->__toString()
                    ));
                }  
            }
        }
        $this->cleanup();  
    }
    
    /**
     * 生成任务计划
     * @return \Cronschedule_lib
     */
    public function generate()
    {
        if ($this->cron_schedule) {
            foreach ($this->cron_schedule as $jobCode => $jobConfig) {
                $scheduleLifetime           = $this->ScheduleIsertLifeTime;// 插入任务有效时间短
                $schedule = array();
                $schedule['job_code']       = $jobCode;
                $schedule['cron_status']    = 3;
                // 获取最近一次插入的任务
                $sche_info  = $this->getLastTimeInsertSchedule($jobCode);
                if ($jobConfig['run']['exec_type'] == 1) {// 定点执行
                    if (!$sche_info) {
                        $insert_time   = strtotime(date('Y-m-d '.$jobConfig['run']['execute_time']));// 任务插入时间
                    } else {
                        $insert_time    = $sche_info['insert_time'] + 86400;// 加上24小时
                    }
                } else { // 间隔执行
                    if (!$sche_info) {
                        $insert_time    = time();
                    } else {
                        $insert_time    = $sche_info['insert_time'] + $jobConfig['run']['interval_time'];// 没间隔一段时间，插入一个任务
                    }
                }
                $now    = time();
                if ($insert_time - $scheduleLifetime > $now) {// 还没到时间(不在有效时间内)
                    continue;
                }
                if ($insert_time < $now) {// (过期)中间有很长一段时间未插入，重新从当前时间插入
                    if ($jobConfig['run']['exec_type'] == 1) {// 定点任务
                        continue;
                    } else {
                        $insert_time    = $insert_time;
                    }
                }
                // 否则正常情况，插入时间为$insert_time
                $schedule['insert_time']    = $insert_time;
                $this->insertSchedule($schedule);
            }
        }
        return $this;
    }
    
    /**
     * 删除任务计划
     * @return \Cronschedule_lib
     */
    public function cleanup()
    {
        $history = $this->CI->db->where_in('cron_status', array(1,2,4))->get($this->cron_schedule_table_name)->result_array();
        $historyLifetimes = array(1 => $this->cron_history_success_lifetime,2=> $this->cron_history_failure_lifetime,4 => $this->cron_history_failure_lifetime);
        $now = time();
        foreach ($history as $record) {
            if (($record['time'] > 0 && ($record['time']) < $now-$historyLifetimes[$record['cron_status']])) {
                $this->deleteSchedule($record['idx']);
            }
        }
        return $this;
    }
    
}
