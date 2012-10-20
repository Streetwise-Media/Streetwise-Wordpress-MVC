<?php
global $bootstrapped;
$wp_load_path = $_SERVER['DOCUMENT_ROOT'] . '/';
if (!defined('ABSPATH')) require_once($wp_load_path.'wp-config-test.php');
if (!class_exists('Enhance')) require_once('EnhanceTestFramework.php');
if (!class_exists('swpMVCCore')) require_once(dirname(__FILE__).'/../swpmvc.php');
if (!class_exists('testUtilities')) require_once('testUtilities.php');

class PostWeebleRole extends swpMVCBaseRole
{
    public function get_weeble()
    {
        return $this->model->post_title.' weeble';
    }
    
    public function set_post_title_prefix($value)
    {
        $this->model->post_title = $value.' '.$this->model->post_title;
    }
    
    public function h1_id()
    {
        return '<h1>'.$this->model->id.'</h1>';
    }
    
}

class BaseRoleTests extends \Enhance\TestFixture
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
    
    public function test_role_getters_work_correctly()
    {
        $post = Post::first();
        new PostWeebleRole($post);
        \Enhance\Assert::areIdentical($post->post_title.' weeble', $post->weeble);
    }
    
    public function test_role_setters_work_correctly()
    {
        $post = Post::first();
        new PostWeebleRole($post);
        $title = $post->post_title;
        $post->post_title_prefix = 'woo';
        \Enhance\Assert::areIdentical('woo '.$title, $post->post_title);
    }
    
    public function test_role_methods_work_correctly()
    {
        $post = Post::first();
        new PostWeebleRole($post);
        \Enhance\Assert::areIdentical('<h1>'.$post->id.'</h1>', $post->h1_id());
    }
    
    public function test_batch_inject()
    {
        $posts = Post::all(array('limit' => 2));
        PostWeebleRole::batch_inject($posts);
        foreach($posts as $post)
            \Enhance\Assert::areIdentical('<h1>'.$post->id.'</h1>', $post->h1_id());
    }
}


if (!isset($bootstrapped))
{
    // Find the tests - '.' is the current folder
    //\Enhance\Core::discoverTests('.');
    // Run the tests
    \Enhance\Core::runTests();  
}