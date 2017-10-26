<?php

class Court_model extends MY_Model {
    public function __construct() {
        parent::__construct();
    }
    
    /**
     * 插入表数据
     * @param array $data
     * @param string $table
     * @return int 返回插入id
     */
    public function insert_data($data, $table)
    {
        $data['time']           = time();
        $data['update_time']    = time();
        return parent::insert_data($data, $table);
    }
    
    /**
     * 插入多行数据
     * @param type $pairs
     * @param type $table
     * @return type
     */
    public function insert_batch($pairs, $table) {
        return parent::insert_batch($pairs, $table);
    }
    
    /**
     * 更新表数据
     * @param array $data
     * @param array $where
     * @param string $table
     * @return bool 返回真|假
     */
    public function update_data($data, $where, $table)
    {
        $data['update_time']    = time();
        return parent::update_data($data, $where, $table);
    }
    
    /**
     * 多条数据更新
     * @param array $data
     * @param type $field
     * @param type $table
     * @return type
     */
    public function update_batch(array $data, $field, $table) {
        return parent::update_batch($data, $field, $table);
    }
    
    /**
     * 删除数据
     * @param type $where
     * @param type $table
     * @return type
     */
    public function delete_data($where, $table)
    {
        return parent::delete_data($where, $table);
    }
    
    /**
     * 获取列表数据
     * @param array $options = array('order' => ,'where'=> ,'fields'=> ......)
     * @param string $table
     * @return array 返回列表数据
     */
    public function list_data($options, $table)
    {
        return parent::get_list_term($options, $table);
    }
    
    /**
     * 获取单条数据
     * @param array $where 查询条件
     * @param string  $fields 查询字段
     * @param string $table 表
     * @return array 返回单条数据
     */
    public function get_one($where, $table, $fields = "*")
    {
        return parent::get_one($where, $fields, $table);
    }
    
    /**
     * 获取数据总条数
     * @param string $count_key count($count_key)
     * @param array $where 查询条件
     * @param string $table 表
     * @return int 返回数据总条数
     */
    public function total_count($count_key, $where, $table)
    {
        return parent::count($count_key, $where, $table);
    }
    
    /**
     * 获取球员列表数据 -- 多表查询
     * @return type
     */
    public function get_player_list($where,$limit = array(),$fields = '')
    {
        $select = "A.idx AS id,
            A.level AS level,
            A.is_use AS is_use,
            A.position_no AS position_no,
            A.position_no2 AS position_no2,
            A.cposition_type AS cposition_type,
            A.fatigue AS fatigue,
            @fatigue_total := 100 AS fatigue_total,
            B.ability AS ability,
            B.player_no AS player_no,
            B.pic AS pic,
            B.name AS name,
            B.quality AS quality,
            B.position_type AS position_type";
        if ($fields) {
            $select = $fields;
        }
        $sql = "SELECT ".$select." FROM player_info AS A JOIN player_lib AS B ON A.player_no = B.player_no AND ".$where." ORDER BY B.ability DESC LIMIT ".$limit['offset'].",".$limit['pagesize'];
        $query = $this->db->query($sql);
        if ($query === FALSE) {
            return false;
        }
        $result = array();
        if ($query->num_rows() > 0) {
            $result = $query->result_array();
        }
        return $result;
    }
    
    /**
     * 获取球员信息 -- 多表查询
     * @return type
     */
    public function get_player_info($options)
    {
        $sql = "SELECT ".$options['select']." FROM player_info AS A JOIN player_lib AS B ON A.player_no = B.player_no WHERE ".$options['where'];
        $query = $this->db->query($sql);
        if ($query === FALSE) {
            return false;
        }
        $result = array();
        if ($query->num_rows() > 0) {
            $result = $query->result_array();
        }
        
        return $result[0];
    }
    
    /**
     * sql语句查询
     */
    public function fetch($sql, $type = 'result')
    {
        return parent::fetch($sql, $type);
    }
    
    /**
     * left join查询
     */
    public function get_composite_row_array($condition, $join_condition, $select, $tb_a, $tb_b,$batch = FALSE)
    {
        return parent::get_composite_row_array($condition, $join_condition, $select, $tb_a, $tb_b, $batch);
    }
    
    /**
     * 执行sql语句
     */
    public function exec_sql($sql,$batch =FALSE)
    {
        return parent::exec_sql($sql, $batch);
    }
}
