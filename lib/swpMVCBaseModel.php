<?php

class swpMVCBaseModel extends ActiveRecord\Model
{
    private $_meta;
    private $_form_helper;
    
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
            $output = $output->replace($key, $value);
            if ($this->needs_template_cleanup($key, $value)) $output = $output->replace($key.'_block', '');
        }
        if (method_exists($this, 'renderers') and is_callable(array($this, 'renderers')) and is_array($this->renderers()))
            foreach($this->renderers() as $key => $func_array)
            {
                if (!is_array($func_array) or count($func_array) < 2 or
                        !method_exists($this, $func_array[0]) or !is_callable(array($this, $func_array[0])) or
                            !is_array($func_array[1])) continue;
                $r = call_user_func_array(array($this, $func_array[0]), $func_array[1]);
                $output = $output->replace($key, $r);
                if ($this->needs_template_cleanup($key, $r)) $output = $output->replace($key.'_block', '');
            }
        $class = get_called_class();
        if (!method_exists($class, 'controls') or !is_callable(array($class, 'controls'))) return $output;
        $controls = $class::controls();
        if (!isset($this->_form_helper) or !is_object($this->_form_helper))
            $this->_form_helper = new swFormHelper($class);
        foreach($controls as $prop => $control)
        {
            if (!$class::validate_control($prop, $control)) continue;
            if (isset($control['label']))
                $output = $output->replace('control_label_'.$prop, $this->_form_helper->label($prop, $control, htmlentities($this->$prop)));
            $output = $output->replace('control_'.$prop, $this->_form_helper->$control['type']($prop, $control, $this->$prop));
        }
        return $output;
    }
    
    public function needs_template_cleanup($key, $value)
    {
        return $value === false or empty($value) or trim($value) === '';
    }
    
    public static function renderForm(Stamp $output, $prefix = false)
    {
        $class = get_called_class();
        $p = $prefix or $class;
        if (!method_exists($class, 'controls') or !is_callable(array($class, 'controls'))) return $output;
        $controls = $class::controls();
        $form_helper = new swFormHelper($p);
        foreach($controls as $prop => $control)
        {
            if (!$class::validate_control($prop, $control)) continue;
            if (isset($control['label']))
                $output = $output->replace('control_label_'.$prop, $form_helper->label($prop, $control));
            $output = $output->replace('control_'.$prop, $form_helper->$control['type']($prop, $control));
        }
        return $output;
    }
    
    public function form_helper()
    {
        if (!isset($this->_form_helper) or !is_object($this->_form_helper))
            $this->_form_helper = new swFormHelper();
        return $this->_form_helper;
    }
    
    private static function validate_control($prop, $control)
    {
        $class = get_called_class();
        $keys = array('type');
        foreach($keys as $k)
        {
            if (!isset($control[$k]))
            {
                $class::logError("Model $class missing  $k property in control definition for $prop.");
                return false;
            }
            if ($control['type'] === 'select' and (!isset($control['options']) or !is_array($control['options'])))
            {
                $class::logError("Model $class declared select control for property $k without defining control options array.");
                return false;
            }
        }
        return true;
    }
    
    
    public function hydrate_meta($k, $v)
    {
        return false;
    }
    
    public function dehydrate_meta($k, $v)
    {
        return false;
    }
    
    public function set_meta_value($value)
    {
        $val = (false !== $dehydrated_value = $this->dehydrate_meta($this->meta_key, $value)) ? $dehydrated_value : $value;
        $this->assign_attribute('meta_value', $val);
        $this->flag_dirty('meta_value');
    }
    
    public function load_meta()
    {  
        if (is_array($this->_meta)) return $this->_meta;
        $class = get_called_class();
        if (!property_exists($class, 'has_many')
                or (!_::find($class::$has_many, function($a) { return $a[0] === 'meta'; })))
                    return $this->_meta = array();
        $meta = array();
        $self = $this;
        _::each($this->meta, function($m) use (&$meta, $self) {
            $value = (false !== $hydrated_value = $self->hydrate_meta($m->meta_key, $m->meta_value)) ? $hydrated_value : $m->meta_value;
            if (isset($meta[$m->meta_key]) and is_array($meta[$m->meta_key])) return $meta[$m->meta_key] = $value;
            if (!isset($meta[$m->meta_key])) return $meta[$m->meta_key] = $value;
            return $meta[$m->meta_key] = array($meta[$m->meta_key], $value);
        });
        $this->_meta = $meta;
        return $this->_meta;
    }
    
    public function meta($key=false, $raw=false)
    {
        if ($key and $raw) return _::filter($this->meta, function($m) use ($key) { return $m->meta_key === $key; });
        if (!$key) return $this->load_meta();
        $meta = $this->load_meta();
        return $meta[$key];
    }

    public function logError($msg)
    {
        trigger_error($msg, E_USER_WARNING);
        Console::error($msg);
        return true;
    }
}