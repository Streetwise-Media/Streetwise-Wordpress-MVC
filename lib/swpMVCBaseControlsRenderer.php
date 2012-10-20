<?php

class swpMVCBaseControlsRenderer extends swpMVCBaseModelExtender
{
    public static $type = 'controls_renderer';
}

class swpMVCModelControl
{
    public $label = false;
    public $type = false;
    public $input_type = 'text';
    public $options = false;
    public $value = false;
    public $multiple = false;
    
    public function __construct($type, $label=false, $input_type_or_options=false, $value = false, $multiple=false)
    {
        $this->type = $type;
        $this->label = $label;
        $input_type_or_options_property = ($this->type === 'select') ?
            'options' : 'input_type';
        if ($input_type_or_options)
            $this->$input_type_or_options_property = $input_type_or_options;
        $this->value = $value;
        $this->multiple = $multiple;
    }
    
    public function is_valid()
    {
        $keys = array('type');
        foreach($keys as $k)
            if (!$this->$k or
                ($this->type === 'select' and
                 (!$this->options or !is_array($this->options))))
                    return false;
        return true;
    }
    
    private function test_get_class_methods(){}
    
    public function to_array()
    {
        return get_object_vars($this);
    }
}