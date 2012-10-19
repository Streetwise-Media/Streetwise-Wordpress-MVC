<?php
global $bootstrapped;
// Include thetest framework
$wp_load_path = $_SERVER['DOCUMENT_ROOT'] . '/';
if (!defined('ABSPATH')) require_once($wp_load_path.'wp-config-test.php');
if (!class_exists('Enhance')) require_once('EnhanceTestFramework.php');
if (!class_exists('testUtilities')) require_once(dirname(__FILE__).'/testUtilities.php');

class PostDecorator extends swpMVCBaseModelDecorator
{
    public function renderers()
    {
	$r = $this->model->renderers();
	$r['woo'] = 'frog';
	return $r;
    }
    
    public function lowercase_title()
    {
	return strtolower($this->post_title);
    }
}

class BaseModelDecoratorTests extends \Enhance\TestFixture
{
    
    public function setUp()
    {
	$this->utility = new testUtilities();
	//error_reporting(0);
    }
    
    public function tearDown()
    {
	
    }
    
    public function test_model_decorator_delegates_correctly()
    {
	$meta = PostMeta::first(array('order' => 'meta_id DESC'));
        $post = Post::first($meta->post_id, array('include' => 'meta'));
	$decorated_post = new PostDecorator($post);
	\Enhance\Assert::areIdentical($post->to_array(), $decorated_post->to_array());
	\Enhance\Assert::areIdentical($post->post_title, $decorated_post->post_title);
	$decorated_post->post_title = 'wawaweewa';
	\Enhance\Assert::areIdentical('wawaweewa', $decorated_post->post_title);
	\Enhance\Assert::areIdentical($post->meta(), $decorated_post->meta());
    }

    public function test_model_decorator_intercepts_correctly()
    {
	$meta = PostMeta::first(array('order' => 'meta_id DESC'));
        $post = Post::first($meta->post_id, array('include' => 'meta'));
	$decorated_post = new PostDecorator($post);
	$expected = $post->renderers();
	$expected['woo'] = 'frog';
	\Enhance\Assert::areIdentical($expected, $decorated_post->renderers());
    }
    
    public function test_model_decorator_adds_new_functionality_correctly()
    {
	$meta = PostMeta::first(array('order' => 'meta_id DESC'));
        $post = Post::first($meta->post_id, array('include' => 'meta'));
	$decorated_post = new PostDecorator($post);
	\Enhance\Assert::areIdentical(strtolower($post->post_title), $decorated_post->lowercase_title());
    }
}

if (!isset($bootstrapped))
{
    // Find the tests - '.' is the current folder
    //\Enhance\Core::discoverTests('.');
    // Run the tests
    \Enhance\Core::runTests();  
}
?>