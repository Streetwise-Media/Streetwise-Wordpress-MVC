<?php

class swpMVCBaseModelDecorator
{
    protected $model;
    public static $class = 'swpMVCBaseModel';
    
    public function __construct(swpMVCBaseModel $model)
    {
	$this->model = $model;
	self::$class = get_class($model);
    }
    
    public function __call($name, $args)
    {
	return call_user_func_array(array($this->model, $name), $args);
    }
    
    public function __get($name)
    {
	return $this->model->$name;
    }
    
    public function __set($name, $val)
    {
	$this->model->$name = $val;
    }
    
    public static function __callStatic($name, $args)
    {
	return call_user_func_array(array(self::$class, $name), $args);
    }
    
}