<?php
global $bootstrapped;
$wp_load_path = $_SERVER['DOCUMENT_ROOT'] . '/';
if (!defined('ABSPATH')) require_once($wp_load_path.'wp-config-test.php');
if (!class_exists('Enhance')) require_once('EnhanceTestFramework.php');
if (!class_exists('swpMVCCore')) require_once(dirname(__FILE__).'/../swpmvc.php');
if (!class_exists('testUtilities')) require_once('testUtilities.php');

class EagerLoaderTests extends \Enhance\TestFixture
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
    
    public function test_eager_loading_after_initial_query()
    {
        //define('SW_LOG_QUERIES', true);
        $posts = get_posts();
        foreach($posts as $post)
        {
            unset($post->filter);
            $post_models[] = new Post(get_object_vars($post));
        }
        Post::attach_eagerly($post_models, array('user', 'meta'));
    }
    
    public function test_import_posts_from_wp_to_ar()
    {
        $posts = Post::import_from_wp(get_posts());
        //$this->utility->dump($posts);
    }
}


if (!isset($bootstrapped))
{
    // Find the tests - '.' is the current folder
    //\Enhance\Core::discoverTests('.');
    // Run the tests
    \Enhance\Core::runTests();  
}