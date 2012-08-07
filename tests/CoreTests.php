<?php
global $bootstrapped;
$wp_load_path = $_SERVER['DOCUMENT_ROOT'] . '/';
if (!defined('ABSPATH')) require_once($wp_load_path.'wp-config-test.php');
if (!class_exists('Enhance')) require_once('EnhanceTestFramework.php');
if (!class_exists('swpMVCCore')) require_once(dirname(__FILE__).'/../swpmvc.php');
if (!class_exists('testUtilities')) require_once('testUtilities.php');

class CoreTestUtilities
{
    public function add_routes($routes)
    {
        return array_merge($routes,
                           array(
                                array('controller' => 'test', 'method' => 'index', 'route' => '/one/:p/:p'),
                                array('controller' => 'fadge', 'method' => 'lar'),
                                array('controller' => 'fadge', 'route' => 'lar')
                           ));
    }
}

class CoreTests extends \Enhance\TestFixture
{
    public function setUp()
    {
        global $wpdb;
        $this->utility = new testUtilities();
        $this->cu = new CoreTestUtilities();
        $this->c = swpMVCCore::instance();
    }
    
    public function tearDown()
    {

    }
    
    public function test_add_filter_swp_mvc_routes_adds_properly_formatted_routes_and_ignores_malformed_routes()
    {
        add_filter('swp_mvc_routes', array($this->cu, 'add_routes'));
        $this->c->router->register_routes();
        $routes = $this->c->router->get_registered_routes();
        $expects = array ( '^one/([^/]+)/([^/]+)/?$' => '/index.php?swpmvc_controller=test&swpmvc_method=index&swpmvc_params[]=$matches[1]&swpmvc_params[]=$matches[2]', );
        \Enhance\Assert::areIdentical($expects, $routes);
    }
    
    public function test_plugin_loads_library_classes()
    {
        \Enhance\Assert::isTrue(class_exists('ActiveRecord\Model'));
        \Enhance\Assert::isTrue(class_exists('swpMVCBaseController'));
        \Enhance\Assert::isTrue(class_exists('swpMVCBaseValidator'));
        \Enhance\Assert::isTrue(class_exists('swpMVCQueryWriter'));
    }
    
}


if (!isset($bootstrapped))
{
    // Find the tests - '.' is the current folder
    //\Enhance\Core::discoverTests('.');
    // Run the tests
    \Enhance\Core::runTests();  
}