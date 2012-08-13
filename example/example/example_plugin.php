<?php

/*
Plugin Name: Streetwise Media WordPress MVC Example Plugin
Plugin URI: http://streetwise-media.com
Description: Demonstrates basic use of MVC Framework for Streetwise WordPress development
Author: Brian Zeligson
Version: 0.1
Author URI: http://brianzeligson.com
*/

class swpMVC_Example
{
    
    private static $_instance;
    
    private function __construct()
    {
        $this->require_dependencies();
        $this->add_actions();
    }
    
    public static function instance()
    {
        if (!isset(self::$_instance))
            self::$_instance = new swpMVC_Example();
        return self::$_instance;
    }
    
    private function add_actions()
    {
        add_filter('swp_mvc_routes', array($this, 'add_routes'));
    }
    
    private function require_dependencies()
    {
        require_once(dirname(__FILE__).'/example_controller.php');
    }
    
    private function base_route()
    {
        return array(
            'controller' => 'swpMVC_Example_Controller',
            'method' => 'hello',
        );
    }
    
    public function add_routes($routes)
    {
        $example_routes = array(
            '/swpmvc/hello/there/:p',
            '/swpmvc/hello/there/:p/:p',
            '/swpmvc/hello/:p',
            '/swpmvc/hello',
            '/swpmvc/hello/:p/:p',
            '/swpmvc/hello/test/route',
        );
        $r = array();
        foreach($example_routes as $route)
        {
            $a = $this->base_route();
            $a['route'] = $route;
            $r[] = $a;
        }
        $r[] = array('controller' => 'swpMVC_Example_Controller', 'method' => 'wp_style',
                        'route' => '/recent_thumbs/wp_style');
        $r[] = array('controller' => 'swpMVC_Example_Controller', 'method' => 'swpmvc_style',
                        'route' => '/recent_thumbs/swpmvc_style');
        $s =  array_merge($routes, $r);
        return $s;
    }
}

add_action('swp_mvc_init', array('swpMVC_Example', 'instance'));