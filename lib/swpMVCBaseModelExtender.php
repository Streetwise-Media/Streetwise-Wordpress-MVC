<?php

class swpMVCBaseModelExtender
{
    protected $model;
    
    public function __construct(swpMVCBaseModel $model)
    {
        $this->model = $model;
        $class = get_called_class();
        $method = 'inject_'.$class::$type;
        if (!is_callable(array($this->model, $method))) return;
        $this->model->$method($this);
    }
    
    public function model()
    {
        return $this->model;
    }
    
    public static function batch_inject(array $models)
    {
        $r = array();
        $class = get_called_class();
        foreach($models as $model)
        {
            if (is_subclass_of($model, 'swpMVCBaseModel'))
                new $class($model);
        }
    }
    
    public function methods()
    {
        return _::reject(get_class_methods($this), function($m){
            return in_array($m, array('__construct', 'model', 'batch_inject'));
        });
    }
    
}