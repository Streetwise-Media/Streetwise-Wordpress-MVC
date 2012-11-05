<?php

class swpMVCView
{
    private $_storage;
    
    public function methods()
    {
        return _::reject(get_class_methods($this), function($m){
            return $m === 'methods';
        });
    }

    public function render(swpMVCStamp $output)
    {
        foreach($this->methods() as $method)
        {
            if (!is_callable(array($this, $method)) or
                (!$output->hasSlot($method) and !$output->hasSlot($method.'_block'))) continue;
            $output = $output->replace($method, $val = $this->$method($output));
            if (!$val) $output = $output->replace($method.'_block', '');
        }
        return $output;
    }
    
    public function __set($var, $value)
    {
        $this->_storage[$var] = $value;
    }
    
    public function __get($var)
    {
        return (array_key_exists($var, $this->_storage))
            ? $this->_storage[$var] : false;
    }
}