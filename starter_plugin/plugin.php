<?php

/*
Plugin Name: Streetwise Media WordPress MVC Starter Plugin
Plugin URI: http://streetwise-media.com
Description: Scaffolding for plugin using swpMVC
Author: Brian Zeligson
Version: 0.1
Author URI: http://brianzeligson.com
*/

class swpMVC_Starter
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
    
    private function require_dependencies()
    {
        //require models and controllers here
    }
    
    private function add_actions()
    {
        add_filter('swp_mvc_routes', array($this, 'add_routes'));
    }
    
    public function add_routes($routes)
    {
        //add routes here
        return $routes;
    }
}

add_action('swp_mvc_init', array('swpMVC_Starter', 'instance'));