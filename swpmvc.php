<?php

/*
Plugin Name: Streetwise Media WordPress MVC
Plugin URI: http://streetwise-media.com
Description: MVC Framework for Streetwise WordPress development
Author: Brian Zeligson
Version: 0.1
Author URI: http://brianzeligson.com
*/

class swpMVCCore
{
    private static $_instance;
    private $_routes;
    
    private function __construct()
    {
        define('PHP_ACTIVERECORD_AUTOLOAD_DISABLE', true);
        $this->add_actions();
        $this->load_dependencies();
    }
    
    public static function instance()
    {
        if (!isset(self::$_instance)) self::$_instance = new swpMVCCore();
        return self::$_instance;
    }
    
    public function add_actions()
    {
        add_action('wp_loaded', array($this, 'swp_mvc_init'), 10);
        add_action('wp_loaded', array($this, 'swp_mvc_ready'), 15);
        add_action('wp_loaded', array($this, 'flush_rewrite_if_needed'), 20);
        add_filter('query_vars', array($this, 'add_query_vars'));
        add_filter('rewrite_rules_array', array($this, 'add_rewrite_rules'));
        add_filter('template_include', array($this, 'template_include'));
    }
    
    public function load_dependencies()
    {
        require_once(dirname(__FILE__).'/swpmvc_router.php');
        $this->router = swpMVCRouter::instance();
        $dir = dirname(__FILE__).'/';
        $this->load_activerecord($dir);
        require_once(dirname(__FILE__).'/profiler/swpMVCProfiler.php');
        if (!class_exists('_')) require_once($dir.'lib/underscore.php');
        foreach (glob($dir.'lib/*.php') as $filename)
            if (!class_exists(str_replace('.php', '', basename($filename)))) require_once($filename);
        require_once($dir.'models/wordpress_models.php');
    }
    
    public function load_activerecord($dir)
    {
        require_once($dir.'lib/activerecord/ActiveRecord.php');
        ActiveRecord\Config::initialize(function($cfg)
        {
            $cfg->set_connections(array(
            'development' => 'mysql://'.DB_USER.':'.DB_PASSWORD.'@'.DB_HOST.'/'.DB_NAME.';charset=utf8'));
        });
    }
    
    public function swp_mvc_init()
    {
        do_action('swp_mvc_init');
    }
    
    public function swp_mvc_ready()
    {
        do_action('swp_mvc_ready');
    }
    
    public function flush_rewrite_if_needed()
    {
        if (!$this->router->needs_rewrite_flush()) return;
        flush_rewrite_rules();
        update_option('swp_mvc_routes', $this->router->get_registered_routes());
    }
    
    public function add_query_vars($vars)
    {
        array_push($vars, 'swpmvc_controller');
        array_push($vars, 'swpmvc_method');
        array_push($vars, 'swpmvc_params');
        return $vars;
    }
    
    public function add_rewrite_rules($rules)
    {
        $mvc_rules = $this->router->get_registered_routes();
        return $mvc_rules + $rules;
    }
    
    public function template_include($template)
    {
        global $wp_query;
        if (!$this->is_swpmvc_request()) return $template;
        $c = $wp_query->query_vars['swpmvc_controller'];
        $m = $wp_query->query_vars['swpmvc_method'];
        if (!class_exists($c) or !method_exists($c, $m) or !is_callable($c, $m)) return $template;
        $wp_query->is_home = false;
        return dirname(__FILE__).'/swpMVCDispatcher.php';
    }
    
    public function is_swpmvc_request()
    {
        global $wp_query;
        $c = $wp_query->query_vars['swpmvc_controller'];
        $m = $wp_query->query_vars['swpmvc_method'];
        return (isset($c) and isset($m));
    }
    
    
}

if (!is_admin()) $bootstrap = swpMVCCore::instance();