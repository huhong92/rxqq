<?php

class Match_model extends MY_Model {
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
     * @param array|string $where 查询条件
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
     * 获取join 查询数据
     * @param string $condition where查询条件
     * @param string $join_condition join条件
     * @param string $select 查询字段
     * @param string $tb_a a表
     * @param string $tb_b b表
     * @param bool $batch false单条数据 ture多条数据
     * @return array 返回数据列表
     */
    public function get_composite_row_array($condition, $join_condition, $select, $tb_a, $tb_b, $batch = FALSE) {
        return parent::get_composite_row_array($condition, $join_condition, $select, $tb_a, $tb_b, $batch);
    }
    
    /**
     * sql语句查询
     */
    public function fetch($sql, $type = 'result')
    {
        return parent::fetch($sql, $type);
    }
}
