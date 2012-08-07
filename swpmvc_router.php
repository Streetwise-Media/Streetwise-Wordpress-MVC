<?php

class swpMVCRouter
{
    public $routes;
    private static $_router;
    
    private function __construct()
    {
        $this->routes = array();
        $this->add_actions();
    }
    
    public static function instance()
    {
        if (!isset(self::$_router)) self::$_router = new swpMVCRouter();
        return self::$_router;
    }
    
    public function add_actions()
    {
        add_action('swp_mvc_ready', array($this, 'register_routes'));
    }
    
    public function register_routes()
    {
        if (!is_array($this->routes)) $this->routes = array();
        $this->routes = apply_filters('swp_mvc_routes', $this->routes);
    }
    
    public function get_registered_routes()
    {
        return $this->format_routes_for_wp($this->routes);
    }
    
    public function format_routes_for_wp($routes)
    {
        $r = array();
        foreach($routes as $route)
        {
            if (!isset($route['controller']) or !isset($route['method']) or !isset($route['route'])) continue;
            extract($route);
            $k = str_replace(':p', '([^/]+)', $route);
            $k = preg_replace('/^\//', '^', $k);
            $k = preg_replace('/\/?$/', '/?$', $k);
            $qs = $this->route_query_string($route);
            $v = '/index.php?swpmvc_controller='.$controller
                .'&swpmvc_method='.$method.$qs;
            $r[$k] = $v;
        }
        return $r;
    }
    
    public function route_query_string($route)
    {
        $r = array('');
        $c = substr_count($route, ':p');
        for($i=1; $i <= $c; $i++) $r[] = 'swpmvc_params[]=$matches['.$i.']';
        return join('&', $r);
    }
    
    public function needs_rewrite_flush()
    {
        $rules = get_option('rewrite_rules');
        $stored_mvc_rules = get_option('swp_mvc_routes');
        if (!is_array($stored_mvc_rules)) $stored_mvc_rules = array();
        $registered_mvc_rules = $this->get_registered_routes();
        $r = false;
        foreach($registered_mvc_rules as $rule => $route)
            if (!isset($rules[$rule]) or $rules[$rule] !== $route) $r = true;
        foreach($stored_mvc_rules as $rule => $route)
            if (!isset($registered_mvc_rules[$rule])) $r = true;
        return $r;
    }
}