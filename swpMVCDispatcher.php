<?php
/*
Template Name: swpMVC Dispatcher
*/
class swpMVCDispatcher
{
    public static function can_call($o, $m)
    {
        return method_exists($o, $m) and is_callable(array($o, $m));
    }
    
    public static function dispatch()
    {
        global $wp_query;
        $c = $wp_query->query_vars['swpmvc_controller'];
        $m = $wp_query->query_vars['swpmvc_method'];
        $p = $wp_query->query_vars['swpmvc_params'];
        if (!is_array($p)) $p = array();
        if (!class_exists($c)) return self::set404();
        $co = new $c();
        if (!self::can_call($co, $m)) return self::set404();
        if (self::can_call($co, 'before')) $co->before();
        call_user_func_array(array($co, $m), $p);
        if (self::can_call($co, 'after')) $co->after();
    }
    
    public static function set404()
    {
        $c = new swpMVCBaseController();
        $c->set404();
    }
}

swpMVCDispatcher::dispatch();

