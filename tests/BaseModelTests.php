<?php
global $bootstrapped;
$wp_load_path = $_SERVER['DOCUMENT_ROOT'] . '/';
if (!defined('ABSPATH')) require_once($wp_load_path.'wp-config-test.php');
if (!class_exists('Enhance')) require_once('EnhanceTestFramework.php');
if (!class_exists('swpMVCCore')) require_once(dirname(__FILE__).'/../swpmvc.php');
if (!class_exists('testUtilities')) require_once('testUtilities.php');
/*
class PostTerm extends swpMVCBaseModel
{
    static $table_name = 'wp_term_relationships';
    static $primary_key = 'term_taxonomy_id';
    static $has_one = array(
        array('termtaxonomy', 'class' => 'TermTaxonomy', 'foreign_key' => 'term_taxonomy_id')
    );
    static $belongs_to = array(
        array('post', 'foreign_key' => 'object_id'),
    );

}

class TermTaxonomy extends swpMVCBaseModel
{
    static $table_name = 'wp_term_taxonomy';
    static $belongs_to = array(
        array('postterm', 'class' => 'PostTerm', 'foreign_key' => 'term_taxonomy_id')
    );
    static $has_one = array(
        array('tag', 'class' => 'Tag', 'foreign_key' => 'term_id')
    );
}


class Post extends swpMVCBaseModel
{
    static $table_name = 'wp_posts';
    static $belongs_to = array(
                    array('user', 'foreign_key' => 'post_author')
                );
    static $has_many = array(
        array('postterms', 'class' => 'PostTerm', 'foreign_key' => 'object_id')
    );
    
    public function tags()
    {
        $tags = _::map($this->postterms, function($term) { return $term->termtaxonomy->tag; });
        return $tags;
    }
    
}

class Tag extends swpMVCBaseModel
{
    static $table_name = 'wp_terms';
    static $primary_key = 'term_id';
    static $belongs_to = array(
        array('termtaxonomy', 'class' => 'TermTaxonomy', 'foreign_key' => 'term_id'),
    );
}

class User extends swpMVCBaseModel
{
    static $table_name = 'wp_users';
    static $has_many = array(
        array('posts', 'foreign_key' => 'post_author'),
        array('usermeta', 'class' => 'UserMeta', 'foreign_key' => 'user_id'),
    );
}

class UserMeta extends swpMVCBaseModel
{
    static $table_name = 'wp_usermeta';
    static $belongs_to = array(
                array('user', 'foreign_key' => 'user_id', 'class_name' => 'User'),
            );
}
*/

class TestSetterPost extends Post
{
    public function set_post_title($title)
    {
        $this->assign_attribute('post_title', 'wacka '.$title);
    }
}

class MetalessPost extends Post
{
    public static $has_many = array();
}

class TestCastingSetterPost extends Post
{
    public function set_post_title($title)
    {
        $this->assign_attribute('post_title', 'wacka '.$title[0]);
    }
}

class BaseModelTestUtilities
{

}

class BaseModelTests extends \Enhance\TestFixture
{
    public function setUp()
    {
        global $wpdb;
        $this->utility = new testUtilities();
        $this->cu = new BaseModelTestUtilities();
        $this->c = swpMVCCore::instance();
    }
    
    public function tearDown()
    {
        
    }
    
    public function test_base_model_find()
    {
        return;
        $posts = Post::all(array('include' => array('user' => array('usermeta'), 'postterms' => array( 'termtaxonomy' => array('tag'))
                                            ), 'conditions' => array('post_title = ?', 'A Tribute to Steve Jobs')));
        //$this->utility->dump($posts[0]->user);
        foreach($posts as $post)
        {
            echo "{$post->post_title} by {$post->user->display_name}";
            //$this->utility->dump($post->user->__relationships['usermeta']);
            $usermeta = $post->user->usermeta;
            _::map($usermeta, function($meta) {
                if ($meta->meta_key === 'description' and trim($meta->meta_value) !== '')
                    echo ', '.$meta->meta_value.'<br />';
            });
                $tags = $post->tags();
                //$this->utility->dump($tags);
            _::each($tags, function($term) {echo '<h4>'.$term->name.'</h4>'; });
        }
    }
    
    public function test_activerecord_constructor_uses_dynamic_setters()
    {
        $post = new TestSetterPost(array('post_title' => 'test'));
        \Enhance\Assert::areIdentical('wacka test', $post->post_title);
    }
    
    public function test_casting_does_not_interfere_with_dynamic_setters_during_mass_assignment()
    {
        $post = new TestCastingSetterPost(array('post_title' => array('test')));
        \Enhance\Assert::areIdentical('wacka test', $post->post_title);
    }
    
    public function test_late_addition_of_relationships_works()
    {
        MetalessPost::$has_many[] = array('meta', 'class' => 'PostMeta', 'foreign_key' => 'post_id');
        $post = MetalessPost::first(array('include' => array('meta')));
    }
    
}


if (!isset($bootstrapped))
{
    // Find the tests - '.' is the current folder
    //\Enhance\Core::discoverTests('.');
    // Run the tests
    \Enhance\Core::runTests();  
}