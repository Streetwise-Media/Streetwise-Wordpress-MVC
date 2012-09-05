<?php

class swFormHelper
{
    public $_prefix;
    
    public function __construct($prefix='')
    {
        $this->_prefix = $prefix;
    }
    
    public function label($key, $control)
    {
        return "<label class='{$this->_prefix} {$this->_prefix}_$key'
            for='{$this->_prefix}_{$key}_{$control['type']}'>{$control['label']}</label>";
    }
    
    public function input($key, $control, $value=false)
    {
        $type = (isset($control['input_type'])) ? $control['input_type'] : 'text';
        $checked = ($type === 'checkbox' and $value != false) ? 'checked="checked"' : '';
        $nid = $this->_prefix.'_'.$key.'_'.$control['type'];
        return "<input class='{$this->_prefix} {$this->_prefix}_control {$this->_prefix}_$key'
            name='{$this->_prefix}[$key]' id='$nid' type='$type' $checked value='$value' />";
    }
    
    public function select($key, $control, $value=false)
    {
        $nid = $this->_prefix.'_'.$key.'_'.$control['type'];
        $multiple = (isset($control['multiple']) and $control['multiple']) ? "multiple='multiple'" : '';
        $select = "<select class='{$this->_prefix} {$this->_prefix}_control {$this->_prefix}_$key'
            name='{$this->_prefix}[$key]' id='$nid' $multiple>";
        foreach($control['options'] as $text => $val)
        {
            $selected = ((is_array($value) and in_array($val, $value)) or $val === $value) ? "selected='selected'" : '';
            $select .= "<option value='$val' $selected>$text</option>";
        }
        $select .= "</option>";
        return $select;
    }
    
    public function textarea($key, $control, $value=false)
    {
        $nid = $this->_prefix.'_'.$key.'_'.$control['type'];
        return "<textarea class='{$this->_prefix} {$this->_prefix}_control {$this->_prefix}_$key' id='$nid'
            name='{$this->_prefix}[$key]'>$value</textarea>";
    }
}