<?php

class swpMVCBaseController
{
    
    public $page_title = "";
    public $version = "";
    public $_templatedir;
    public $_scripts = array();
    public $_script_localizations = array();
    public $_styles = array();
    protected static $_cache = array();

    public function __construct()
    {
        add_filter( 'wp_title', array($this, 'set_page_title') );
        add_action('wp_enqueue_scripts', array($this, 'enqueue_scripts'));
        add_action('wp_enqueue_scripts', array($this, 'localize_scripts'));
        add_action('wp_enqueue_scripts', array($this, 'enqueue_styles'));
    }
    
    public function set_page_title($title)
    {
    	if(trim($this->page_title) !== '') return $this->page_title;
    	return $title;
    }
    
    public static function cache($key=false)
    {
	if (!$key) return self::$_cache;
	return array_key_exists($key, self::$_cache)
	    ? self::$_cache[$key] : false;
    }
    
    public function template($name)
    {
        if (!$this->_templatedir and $this->logError('No controller template directory defined for template '.$name))
                return new swpMVCStamp('No controller template directory defined');
        return new swpMVCStamp(swpMVCStamp::load($this->_templatedir.$name.'.tpl'));
    }
    
    public function enqueue_scripts()
    {
        if (!is_array($this->_scripts)) $this->_scripts = array();
        foreach($this->_scripts as $script)
        {
	    if (!$script[3] and trim($this->version) !== '') $script[3] = $this->version;
            call_user_func_array('wp_enqueue_script', $script);
        }
    }
    
    public function localize_scripts()
    {
        foreach($this->_script_localizations as $l)
        {
            call_user_func_array('wp_localize_script', $l);
        }
    }
    
    public function enqueue_styles()
    {
        foreach($this->_styles as $style)
        {
	    if (!$style[3] and trim($this->version) !== '') $style[3] = $this->version;
            call_user_func_array('wp_enqueue_style', $style);
        }
    }
    
    public function set404()
    {
        global $wp_query;
        header("HTTP/1.0 404 Not Found - Archive Empty");
        $wp_query->set_404();
        require TEMPLATEPATH.'/404.php';
        exit;
    }
    
    public function noindex()
    {
        add_action('wp_head', array($this, 'noindex_tag'));
    }
    
    public function noindex_tag()
    {
	?><meta name="robots" content="noindex,nofollow" /><?php
    }
    
    public function nocache()
    {
	header("Cache-Control: " .
           "private, no-cache, no-cache=Set-Cookie, proxy-revalidate");
    }
    
    public function isPost()
    {
	return $_SERVER['REQUEST_METHOD'] === 'POST';
    }
    
    public static function link($controller, $method, $params=array())
    {
        $routes = swpMVCCore::instance()->router->routes;
        $matched_route = _::find($routes, function($route) use ($controller, $method, $params) {
            return $route['controller'] === $controller and $route['method'] == $method and
                count($params) === substr_count($route['route'], ':p');
        });
        $error_msg = 'Could not find route matching '.json_encode(
                            array('controller' => $controller, 'method' => $method, 'params' => $params));
        if (!is_array($matched_route) and !isset($matched_route['route'])) return self::logError($error_msg);
        $r = $matched_route['route'];
        foreach($params as $p) $r = preg_replace('/:p/', $p, $r, 1);
        return get_bloginfo('url').$r;
    }
    
    public function logError($msg)
    {
        trigger_error($msg, E_USER_WARNING);
        Console::error($msg);
        return true;
    }
}