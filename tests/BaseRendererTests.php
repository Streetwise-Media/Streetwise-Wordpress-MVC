<?php
global $bootstrapped;
$wp_load_path = $_SERVER['DOCUMENT_ROOT'] . '/';
if (!defined('ABSPATH')) require_once($wp_load_path.'wp-config-test.php');
if (!class_exists('Enhance')) require_once('EnhanceTestFramework.php');
if (!class_exists('swpMVCCore')) require_once(dirname(__FILE__).'/../swpmvc.php');
if (!class_exists('testUtilities')) require_once('testUtilities.php');


class PostRenderer extends swpMVCBaseRenderer
{
    public function weesnaw()
    {
        return 'weesnaw';
    }
}

class AltPostRenderer extends swpMVCBaseRenderer
{
    public function weesnaw()
    {
        return 'weesnawweesnaw';
    }
}

class LastPostRenderer extends swpMVCBaseRenderer
{
    public function weesnaw()
    {
        return $this->model()->post_title.' weesnaw';
    }
    
    public function post_title()
    {
        return 'weesnaw';
    }
}

class BaseRendererTests extends \Enhance\TestFixture
{
    public function setUp()
    {
        global $wpdb;
        $this->utility = new testUtilities();
        $this->c = swpMVCCore::instance();
    }
    
    public function tearDown()
    {
        
    }
    
    public function test_renderer_applies_to_model_rendering_when_assigned()
    {
        $str = 'open <!-- weesnaw --><!-- /weesnaw --> close';
        $tpl = new swpMVCStamp($str);
        $post = Post::first();
        \Enhance\Assert::areIdentical($str, $post->render($tpl)->__toString());
        $tpl = new swpMVCStamp($str);
        new PostRenderer($post);
        \Enhance\Assert::areIdentical('open weesnaw close', $post->render($tpl)->__toString());
        $tpl = new swpMVCStamp($str);
        new AltPostRenderer($post);
        \Enhance\Assert::areIdentical('open weesnawweesnaw close', $post->render($tpl)->__toString());
        $tpl = new swpMVCStamp($str);
        new LastPostRenderer($post);
        \Enhance\Assert::areIdentical('open '.$post->post_title.' weesnaw close', $post->render($tpl)->__toString());
    }
    
}


if (!isset($bootstrapped))
{
    // Find the tests - '.' is the current folder
    //\Enhance\Core::discoverTests('.');
    // Run the tests
    \Enhance\Core::runTests();  
}