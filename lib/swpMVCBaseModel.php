<?php

class swpMVCBaseModel extends ActiveRecord\Model
{
    private $_meta;
    private $_form_helper;
    private static $_finder;
    private $_renderer;
    private $_controls_renderer;
    private $_role;
    private $_validator;
    
    public static function build_find($args, $bind_operator='AND')
    {
        if (!self::$_finder) self::$_finder = new swpMVCFinder();
        return self::$_finder->find($args, $bind_operator);
    }
    
    public function &read_attribute($attr)
    {
        $value = parent::read_attribute($attr);
        if (is_string($value)) $value = stripslashes($value);
        if (method_exists($this, 'sanitize_render') and is_callable(array($this, 'sanitize_render'))) return $this->sanitize_render($value, $attr);
        return $value;
    }
    
    public function inject_role(swpMVCBaseRole $role)
    {
        $this->_role = $role;
    }
    
    public function inject_renderer(swpMVCBaseRenderer $renderer)
    {
        $this->_renderer = $renderer;
    }
    
    public function inject_controls_renderer(swpMVCBaseControlRenderer $controls_renderer)
    {
        $this->_controls_renderer = $controls_renderer;
    }
    
    public function inject_validator(swpMVCBaseValidator $validator)
    {
        $this->_validator = $validator;
    }
    
    private function use_renderer_to_populate_template(swpMVCStamp $output)
    {
        if (!$this->_renderer) return $output;
        foreach($this->_renderer->methods() as $method)
        {
            if (!is_callable(array($this->_renderer, $method)) or
                (!$output->hasSlot($method) and !$output->hasSlot($method.'_block'))) continue;
            $output = $output->replace($method, $val = $this->_renderer->$method($output));
            if (!$val or $this->needs_template_cleanup($method, $val))
                $output = $output->replace($method.'_block', '');
        }
        return $output;
    }
    
    private function use_model_attributes_to_populate_template(swpMVCStamp $output)
    {
        foreach($this->attributes() as $key => $val)
        {
            if (!$output->hasSlot($key)) continue;
            $render_method = 'render_'.$key;
            $value = (method_exists($this, $render_method) and is_callable(array($this, $render_method)))
                ? $this->$render_method() : $this->$key;
            $output = $output->replace($key, $value);
            if ($this->needs_template_cleanup($key, $value)) $output = $output->replace($key.'_block', '');
        }
        return $output;
    }
    
    public function use_class_renderer_method_to_populate_template(swpMVCStamp $output)
    {
        if (!method_exists($this, 'renderers') or !is_callable(array($this, 'renderers'))
            or !is_array($this->renderers()))
                return $output;
        foreach($this->renderers() as $key => $func_array)
        {
            if (!$output->hasSlot($key)) continue;
            if (!is_array($func_array) or count($func_array) < 2 or
                    !method_exists($this, $func_array[0]) or !is_callable(array($this, $func_array[0])) or
                        !is_array($func_array[1])) continue;
            $r = call_user_func_array(array($this, $func_array[0]), $func_array[1]);
            $output = $output->replace($key, $r);
            if ($this->needs_template_cleanup($key, $r)) $output = $output->replace($key.'_block', '');
        }
        return $output;   
    }
    
    public function render(swpMVCStamp $tpl)
    {
        $output = $tpl;
        $output = $this->use_renderer_to_populate_template($output);
        $output = $this->use_class_renderer_method_to_populate_template($output);
        $output = $this->use_model_attributes_to_populate_template($output);
        return $this->render_controls($output);
    }
    
    private function render_controls($output)
    {
        $class = get_called_class();
        if (!isset($this->_form_helper) or !is_object($this->_form_helper))
            $this->_form_helper = new swFormHelper($class);
        if ($this->_controls_renderer and $methods = $this->_controls_renderer->methods())
            foreach($methods as $method)
                if (($output->hasSlot('control_label_'.$method) or $output->hasSlot('control_'.$method)) and
                    is_object($val = $this->_controls_renderer->$method())
                    and get_class($val) === 'swpMVCModelControl' and $val->is_valid())
                        $output = $this->process_control($method, $val->to_array(), $output);
        if (!method_exists($class, 'controls') or !is_callable(array($class, 'controls'))) return $output;
        $controls = $class::controls($this, $output);
        foreach($controls as $prop => $control)
            $output = $this->process_control($prop, $control, $output);
        return $output;
    }
    
    private function process_control($prop, $control, swpMVCStamp $tpl)
    {
        $class = get_called_class();
        if (!$tpl->hasSlot('control_label_'.$prop) and !$tpl->hasSlot('control_'.$prop)) return $tpl;
        if (!$class::validate_control($prop, $control)) return $tpl;
        if (isset($control['label']))
            $tpl = $tpl->replace('control_label_'.$prop, $this->_form_helper->label($prop, $control));
        $val = (isset($control['value']) and $control['value']) ?
            $control['value'] : $this->$prop;
        $tpl = $tpl->replace('control_'.$prop, $this->_form_helper->$control['type']($prop, $control, $val));
        return $tpl;
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
    
    public static function renderForm(swpMVCStamp $output, $prefix = false, $control_renderer=false)
    {
        $class = get_called_class();
        $p = $prefix or $class;
        $controls = (method_exists($class, 'controls') and is_callable(array($class, 'controls'))) ?
            $class::controls(false, $output) : array();
        if ($control_renderer and is_subclass_of($control_renderer, 'swpMVCBaseControlRenderer'))
            foreach($control_renderer->methods() as $method)
                if (is_callable(array($control_renderer->$method(), 'to_array')))
                    $controls[$method] = $control_renderer->$method()->to_array();
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
    
    public function formErrors($prefix=false, $headless_messages=false)
    {
        $errors = $this->errors->to_array(null, $headless_messages);
        $class = get_called_class();
        $controls = method_exists($class, 'controls') and is_callable(array($class, 'controls')) ?
            $class::controls(false, false, true) : array();
        $r = array();
        $p = $prefix ?: $class;
        foreach($errors as $key => $error)
        {
            $type = ($this->_controls_renderer and is_callable(array($this->_controls_renderer, $key))
                     and is_callable(array($this->_controls_renderer->$key(), 'is_valid')) and
                     $this->_controls_renderer->$key()->is_valid()) ?
                        $this->_controls_renderer->$key()->type : $controls[$key]['type'];
            $r[] = array('value' => $key, 'errors' => $error,
                         'control' => $p.'_'.$key.'_'.$type);
        }
        return $r;
    }
    
    public function form_helper()
    {
        if (!isset($this->_form_helper) or !is_object($this->_form_helper))
            $this->_form_helper = new swFormHelper();
        return $this->_form_helper;
    }
    
    public final static function validate_control($prop, $control)
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
    
    public function validate()
    {
        if ($this->_validator)
            foreach($this->_validator->methods() as $method)
                $this->_validator->$method();
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
        if ($key and $raw and $method = ($single) ? 'find' : 'filter')
            return _::$method($this->meta, function($m) use ($key) { return $m->meta_key === $key; });
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
    
    public function __get($name)
    {
        $getter = 'get_'.$name;
        if ($this->_role and is_callable(array($this->_role, $getter)))
            return $this->_role->$getter();
        return parent::__get($name);
    }
    
    public function __set($name, $value)
    {
        $setter = 'set_'.$name;
        if ($this->_role and is_callable(array($this->_role, $setter)))
            return $this->_role->$setter($value);
        return parent::__set($name, $value);
    }
    
    public function __call($name, $args)
    {
        if ($this->_role and is_callable(array($this->_role, $name)))
            return call_user_func_array(array($this->_role, $name), $args);
        return parent::__call($name, $args);
    }
}