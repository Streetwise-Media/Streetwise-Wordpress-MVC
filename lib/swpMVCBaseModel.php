<?php

class swpMVCBaseModel extends ActiveRecord\Model
{
    public function &read_attribute($attr)
    {
        $value = parent::read_attribute($attr);
        if (is_string($value)) $value = stripslashes($value);
        return $value;
    }
    
    
    public function render(Stamp $tpl)
    {
        $output = $tpl;
        foreach($this->attributes() as $key => $val)
        {
            $render_method = 'render_'.$key;
            $value = (method_exists($this, $render_method) and is_callable(array($this, $render_method)))
                ? $this->$render_method() : $val;
            $output = $output->replace($key, trim($value));
        }
        if (method_exists($this, 'renderers') and is_callable(array($this, 'renderers')) and is_array($this->renderers()))
            foreach($this->renderers() as $key => $func_array)
            {
                if (!is_array($func_array) or count($func_array) < 2 or
                        !method_exists($this, $func_array[0]) or !is_callable(array($this, $func_array[0])) or
                            !is_array($func_array[1])) continue;
                $r = call_user_func_array(array($this, $func_array[0]), $func_array[1]);
                $output = $output->replace($key, $r);
            }
        $class = get_called_class();
        if (!method_exists($class, 'controls') or !is_callable(array($class, 'controls'))) return $output;
        $controls = $class::controls();
        foreach($controls as $prop => $control)
        {
            if (!$this->validate_control($prop, $control)) continue;
            $output = $output->replace('control_label_'.$prop, swFormHelper::$control['type']($control, $this->$prop));
            $output = $output->replace('control_'.$prop, swFormHelper::$control['type']($control, $this->$prop));
        }
        return $output;
    }
    
    private function validate_control($prop, $control)
    {
        $class = get_called_class();
        $keys = array('type');
        foreach($keys as $k)
            if (!isset($control[$k]))
            {
                $this->logError("Model $class missing  $k property in control definition for $prop.");
                return false;
            }
        return true;
    }
    
    public function logError($msg)
    {
        trigger_error($msg, E_USER_WARNING);
        Console::error($msg);
        return true;
    }
}