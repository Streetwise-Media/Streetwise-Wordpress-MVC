<?php
global $bootstrapped;
$wp_load_path = $_SERVER['DOCUMENT_ROOT'] . '/';
if (!defined('ABSPATH')) require_once($wp_load_path.'wp-config-test.php');
if (!class_exists('Enhance')) require_once('EnhanceTestFramework.php');
if (!class_exists('swpMVCCore')) require_once(dirname(__FILE__).'/../swpmvc.php');

class ControllerTestUtilities
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

class BaseControlllerTests extends \Enhance\TestFixture
{
    public function setUp()
    {
        global $wpdb;
        $this->utility = new testUtilities();
        $this->c = swpMVCCore::instance();
        $this->cu = new ControllerTestUtilities();
        $this->bc = new swpMVCBaseController();
    }
    
    public function tearDown()
    {

    }
    
    public function base_controller_link_method_returns_correct_route_when_passed_controller_method_and_params()
    {
        $rules = get_option('rewrite_rules');
        unset($rules['^one/([^/]+)/([^/]+)/?$']);
        update_option('rewrite_rules', $rules);
        update_option('swp_mvc_routes', array());
        $this->cu->set_routes(
            array(
                    array('controller' => 'test', 'method' => 'index', 'route' => '/one/:p/:p'),
                    array('controller' => 'test', 'method' => 'index', 'route' => '/one/'),
                    array('controller' => 'woop', 'method' => 'woop', 'route' => '/two/:p')
                 )
        );
        add_filter('swp_mvc_routes', array($this->cu, 'add_routes'));
        $this->c->router->register_routes();
        $link = swpMVCBaseController::link('test', 'index', array('two', 'three'));
        $expect = get_bloginfo('url').'/one/two/three';
        \Enhance\Assert::areIdentical($expect, $link);
        $link = swpMVCBaseController::link('test', 'index');
        $expect = get_bloginfo('url').'/one/';
        \Enhance\Assert::areIdentical($expect, $link);
        $link = swpMVCBaseController::link('woop', 'woop', array('one'));
        $expect = get_bloginfo('url').'/two/one';
        \Enhance\Assert::areIdentical($expect, $link);
    }
    
}


if (!isset($bootstrapped))
{
    // Find the tests - '.' is the current folder
    //\Enhance\Core::discoverTests('.');
    // Run the tests
    \Enhance\Core::runTests();  
}