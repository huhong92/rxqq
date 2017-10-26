<?php

/* 
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */
class Base_lib {
    protected $CI = NULL;
    protected $ts;
    public $ip;
            
    function __construct() {
        $this->CI = &get_instance();
        $this->ip = $this->CI->input->ip_address();
    }
    
    /**
    * @see CI_Loader::library
    * @param	string	$library	Library name
    * @param	array	$params		Optional parameters to pass to the library class constructor
    * @param	string	$object_name	An optional object name to assign to
    * @return	object
    */
   public function load_library($library, $params = NULL, $object_name = NULL){
           $object = $this->CI->load->library($library,$params,$object_name);
           if($object_name){
                   $alias_name  = strtolower($object_name);
           }else{
                   $alias_name  = strtolower($library);
           }
           $this->$alias_name   = $this->CI->$alias_name;
           return $object;
   }
   
   /**
    * @see CI_Loader::model
    * @param	string	$model		Model name
    * @param	string	$name		An optional object name to assign to
    * @param	bool	$db_conn	An optional database connection configuration to initialize
    * @return 	object
    */
    public function load_model($model, $name = '', $db_conn = FALSE){
           $object = $this->CI->load->model($model,$name,$db_conn);
           if($name){
                   $alias_name  = strtolower($name);
           }else{
                   $alias_name  = strtolower($model);
           }
           $this->$alias_name   = $this->ci->$alias_name;
           return $object;
    }
    
}
