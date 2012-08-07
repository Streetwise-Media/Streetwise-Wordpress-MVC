<?php
global $bootstrapped;
$wp_load_path = $_SERVER['DOCUMENT_ROOT'] . '/';
if (!defined('ABSPATH')) require_once($wp_load_path.'wp-config-test.php');
if (!class_exists('Enhance')) require_once('EnhanceTestFramework.php');
if (!class_exists('swpMVCCore')) require_once(dirname(__FILE__).'/../swpmvc.php');

class RouterTestUtilities
{
    
    public function default_routes()
    {
        return array(
                    array('controller' => 'test', 'method' => 'index', 'route' => '/one/:p/:p'),
                    array('controller' => 'test', 'method' => 'one', 'route' => '/:p/two/:p'),
                    array('controller' => 'fadge', 'route' => 'lar')
               );
    }
    
    public function reset_routes()
    {
        $this->routes = $this->default_routes();
    }
    
    public function routes()
    {
        return (is_array($this->routes)) ? $this->routes :
            $this->default_routes();
    }
    
    public function set_routes($routes)
    {
        $this->routes = $routes;
    }
    
    public function add_routes($routes)
    {
        return $this->routes;
    }
}

class RouterTests extends \Enhance\TestFixture
{
    public function setUp()
    {
        global $wpdb;
        $this->utility = new testUtilities();
        $this->c = swpMVCCore::instance();
        $this->ru = new RouterTestUtilities();
    }
    
    public function tearDown()
    {

    }
    
    public function test_routes_are_formatted_properly_for_wp()
    {
        $routes = array(
            array('controller' => 'test', 'method' => 'index', 'route' => '/one/:p/:p')
        );
        $formatted_routes = $this->c->router->format_routes_for_wp($routes);
        $expects = array(
            '^one/([^/]+)/([^/]+)/?$' => '/index.php?swpmvc_controller=test&swpmvc_method=index&swpmvc_params[]=$matches[1]&swpmvc_params[]=$matches[2]');
        \Enhance\Assert::areIdentical($expects, $formatted_routes);
    }
    
    public function needs_flush_rewrite_correctly_determines_if_routes_have_changed()
    {
        $rules = get_option('rewrite_rules');
        unset($rules['^one/([^/]+)/([^/]+)/?$']);
        update_option('rewrite_rules', $rules);
        update_option('swp_mvc_routes', array());
        $this->ru->set_routes(
            array(
                    array('controller' => 'test', 'method' => 'index', 'route' => '/one/:p/:p'),
                 )
        );
        add_filter('swp_mvc_routes', array($this->ru, 'add_routes'));
        $this->c->router->register_routes();
        //test added
        \Enhance\Assert::isTrue($this->c->router->needs_rewrite_flush());
        //test modified
        $rules = get_option('rewrite_rules');
        $rules['^one/([^/]+)/([^/]+)/?$'] = '/index.php?swpmvc_controller=test&swpmvc_method=index&swpmvc_params[]=$matches[1]&swpmvc_params[]=$matches[2]&swpmvc_params[]=$matches[3]';
        update_option('rewrite_rules', $rules);
        \Enhance\Assert::isTrue($this->c->router->needs_rewrite_flush());
        //test deleted
        $update_option = array ( '^one/([^/]+)/([^/]+)/?$' =>
                             '/index.php?swpmvc_controller=test&swpmvc_method=index&swpmvc_params[]=$matches[1]&swpmvc_params[]=$matches[2]', );
        $rules['^one/([^/]+)/([^/]+)/?$'] = $update_option['^one/([^/]+)/([^/]+)/?$'];
        update_option('rewrite_rules', $rules);
        update_option('swp_mvc_routes', $update_option);
        $this->ru->reset_routes();
        $this->c->router->register_routes();
        \Enhance\Assert::isTrue($this->c->router->needs_rewrite_flush());
        //test no change
        $this->ru->set_routes(
            array(
                    array('controller' => 'test', 'method' => 'index', 'route' => '/one/:p/:p'),
                 )
        );
        $this->c->router->register_routes();
        $rules = get_option('rewrite_rules');
        update_option('rewrite_rules', array_merge($rules, array ( '^one/([^/]+)/([^/]+)/?$' => '/index.php?swpmvc_controller=test&swpmvc_method=index&swpmvc_params[]=$matches[1]&swpmvc_params[]=$matches[2]', )));
        update_option('swp_mvc_routes', array ( '^one/([^/]+)/([^/]+)/?$' => '/index.php?swpmvc_controller=test&swpmvc_method=index&swpmvc_params[]=$matches[1]&swpmvc_params[]=$matches[2]', ));
        \Enhance\Assert::isFalse($this->c->router->needs_rewrite_flush());
    }
    
}


if (!isset($bootstrapped))
{
    // Find the tests - '.' is the current folder
    //\Enhance\Core::discoverTests('.');
    // Run the tests
    \Enhance\Core::runTests();  
}