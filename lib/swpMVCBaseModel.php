<?php

class swpMVCBaseModel extends ActiveRecord\Model
{
    private $_meta;
    private $_form_helper;
    private static $_finder;
    
    public static function build_find($args)
    {
        if (!self::$_finder) self::$_finder = new swpMVCFinder();
        return self::$_finder->find($args);
    }
    
    public function &read_attribute($attr)
    {
        $value = parent::read_attribute($attr);
        if (is_string($value)) $value = stripslashes($value);
        if (method_exists($this, 'sanitize_render') and is_callable(array($this, 'sanitize_render'))) return $this->sanitize_render($value, $attr);
        return $value;
    }
    
    public function render(swpMVCStamp $tpl)
    {
        $output = $tpl;
        foreach($this->attributes() as $key => $val)
        {
            if (!$tpl->hasSlot($key)) continue;
            $render_method = 'render_'.$key;
            $value = (method_exists($this, $render_method) and is_callable(array($this, $render_method)))
                ? $this->$render_method() : $this->$key;
            $output = $output->replace($key, $value);
            if ($this->needs_template_cleanup($key, $value)) $output = $output->replace($key.'_block', '');
        }
        if (method_exists($this, 'renderers') and is_callable(array($this, 'renderers')) and is_array($this->renderers()))
            foreach($this->renderers() as $key => $func_array)
            {
                if (!$tpl->hasSlot($key)) continue;
                if (!is_array($func_array) or count($func_array) < 2 or
                        !method_exists($this, $func_array[0]) or !is_callable(array($this, $func_array[0])) or
                            !is_array($func_array[1])) continue;
                $r = call_user_func_array(array($this, $func_array[0]), $func_array[1]);
                $output = $output->replace($key, $r);
                if ($this->needs_template_cleanup($key, $r)) $output = $output->replace($key.'_block', '');
            }
        $class = get_called_class();
        if (!method_exists($class, 'controls') or !is_callable(array($class, 'controls'))) return $output;
        $controls = $class::controls($this, $tpl);
        if (!isset($this->_form_helper) or !is_object($this->_form_helper))
            $this->_form_helper = new swFormHelper($class);
        foreach($controls as $prop => $control)
        {
            if (!$tpl->hasSlot('control_label_'.$prop) and !$tpl->hasSlot('control_'.$prop)) continue;
            if (!$class::validate_control($prop, $control)) continue;
            if (isset($control['label']))
                $output = $output->replace('control_label_'.$prop, $this->_form_helper->label($prop, $control));
            $control_val = (is_string($this->$prop)) ? htmlentities($this->$prop) : $this->$prop;
            $output = $output->replace('control_'.$prop, $this->_form_helper->$control['type']($prop, $control, $control_val));
        }
        return $output;
    }
    
    public static function dump_render_tags()
    {
        $class = get_called_class();
        $m = new $class();
        foreach($m->attributes() as $k => $v)
        {
            echo htmlentities("<!-- $k --><!-- /$k -->")."<br />";
        }
        if (method_exists($class, 'renderers') and is_callable(array($class, 'renderers')))
        foreach($class::renderers() as $k => $v)
        {
            echo htmlentities("<!-- $k --><!-- /$k -->").'<br />';
        }
        if (method_exists($class, 'controls') and is_callable(array($class, 'controls')))
        foreach($class::controls(false, false, true) as $k => $v)
        {
            if ($v['label']) echo htmlentities("<!-- control_label_$k --><!-- /control_label_$k -->")."<br />";
            echo htmlentities("<!-- control_$k --><!-- /control_$k -->").'<br />';
        }
    }
    
    public function needs_template_cleanup($key, $value)
    {
        return $value === false or empty($value) or trim($value) === '';
    }
    
    public static function renderForm(swpMVCStamp $output, $prefix = false)
    {
        $class = get_called_class();
        $p = $prefix or $class;
        if (!method_exists($class, 'controls') or !is_callable(array($class, 'controls'))) return $output;
        $controls = $class::controls(false, $output);
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
    
    public function formErrors($prefix=false)
    {
        $errors = $this->errors->to_array();
        $class = get_called_class();
        if (!is_callable(array($class, 'controls'))) return $errors;
        $controls = $class::controls();
        $r = array();
        $p = $prefix ?: $class;
        foreach($errors as $key => $error)
        {
            $r[] = array('value' => $key, 'errors' => $error,
                         'control' => $p.'_'.$key.'_'.$controls[$key]['type']);
        }
        return $r;
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
            if (isset($meta[$m->meta_key]) and is_array($meta[$m->meta_key])) return $meta[$m->meta_key][] = $value;
            if (!isset($meta[$m->meta_key])) return $meta[$m->meta_key] = $value;
            return $meta[$m->meta_key] = array($meta[$m->meta_key], $value);
        });
        $this->_meta = $meta;
        return $this->_meta;
    }
    
    public function meta($key=false, $raw=false, $single=false)
    {
        if ($key and $raw) return _::filter($this->meta, function($m) use ($key) { return $m->meta_key === $key; });
        if (!$key) return $this->load_meta();
        $meta = $this->load_meta();
        return (is_array($meta[$key]) and $single) ? $meta[$key][0] : $meta[$key];
    }

    public function logError($msg)
    {
        trigger_error($msg, E_USER_WARNING);
        Console::error($msg);
        return true;
    }
}