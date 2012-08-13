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
    
    public function test_reduce_to_build_output()
    {
        return;
        $words = array('this ', 'is ', 'the ', 'string ');
        $out = _::reduce($words, function($memo, $str) { return $memo.$str; }, '');
        $this->utility->dump($out);
    }
    
}


if (!isset($bootstrapped))
{
    // Find the tests - '.' is the current folder
    //\Enhance\Core::discoverTests('.');
    // Run the tests
    \Enhance\Core::runTests();  
}