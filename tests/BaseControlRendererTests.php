<?php
global $bootstrapped;
$wp_load_path = $_SERVER['DOCUMENT_ROOT'] . '/';
if (!defined('ABSPATH')) require_once($wp_load_path.'wp-config-test.php');
if (!class_exists('Enhance')) require_once('EnhanceTestFramework.php');
if (!class_exists('swpMVCCore')) require_once(dirname(__FILE__).'/../swpmvc.php');
if (!class_exists('testUtilities')) require_once('testUtilities.php');


class InvalidPostControlsRenderer extends swpMVCBaseControlRenderer
{
    public function weesnaw()
    {
        return new swpMVCModelControl('select', 'Weesnaw');
    }
}

class PostControlsRenderer extends swpMVCBaseControlRenderer
{
    public function weesnaw()
    {
        return new swpMVCModelControl('input', 'Weesnaw', false, $this->model->post_title);
    }
    
    public function post_title()
    {
        return new swpMVCModelControl('input', 'Title', false);
    }
}

class AnotherPostControlsRenderer extends swpMVCBaseControlRenderer
{
    public function weesnaw()
    {
        $options = array(
            $this->model->post_title => $this->model->id,
            'fizz' => 'buzz'
        );
        return new swpMVCModelControl('select', 'Weesnaw', $options, $options, true);
    }
}

class BaseControlRendererTestUtilities
{
    public function hminify($val)
    {
        return preg_replace('/\s+/', '', htmlspecialchars($val));
    }
}

class BaseControlRendererTests extends \Enhance\TestFixture
{
    public function setUp()
    {
        global $wpdb;
        $this->utility = new testUtilities();
        $this->crutil = new BaseControlRendererTestUtilities();
        $this->c = swpMVCCore::instance();
    }
    
    public function tearDown()
    {
        
    }
    
    public function test_render_ignores_control_renderer_methods_that_do_not_return_valid_control_objects()
    {
        $str = 'open <!-- control_weesnaw --><!-- /control_weesnaw --> close';
        $tpl = new swpMVCStamp($str);
        $post = Post::first();
        new InvalidPostControlsRenderer($post);
        \Enhance\Assert::areIdentical($str, $post->render($tpl)->__toString());
        
    }
    
    public function test_control_renderer_applies_to_model_rendering_when_assigned()
    {
        $str = 'open <!-- control_weesnaw --><!-- /control_weesnaw --> close';
        $tpl = new swpMVCStamp($str);
        $post = Post::first();
        new PostControlsRenderer($post);
        $expected = "open <input class='Post Post_control Post_weesnaw' name='Post[weesnaw]' id='Post_weesnaw_input' type='text' value='".$post->post_title."' /> close";
        \Enhance\Assert::areIdentical($this->crutil->hminify($expected), $this->crutil->hminify($post->render($tpl)));
        $str = 'open <!-- control_label_weesnaw --><!-- /control_label_weesnaw --><!-- control_weesnaw --><!-- /control_weesnaw --> close';
        $tpl = new swpMVCStamp($str);
        new AnotherPostControlsRenderer($post);
        $expected = "open<labelclass='PostPost_weesnaw'for='Post_weesnaw_select'>Weesnaw</label><selectclass='PostPost_controlPost_weesnaw'name='Post[weesnaw]'id='Post_weesnaw_select'multiple='multiple'><optionvalue='".$post->id."'selected='selected'>".$post->post_title."</option><optionvalue='buzz'selected='selected'>fizz</option></select>close";
        \Enhance\Assert::areIdentical($this->crutil->hminify($expected), $this->crutil->hminify($post->render($tpl)->__toString()));
    }
    
    public function test_control_renderer_uses_model_property_when_available()
    {
        $str = 'open <!-- control_label_post_title --><!-- /control_label_post_title --><!-- control_post_title --><!-- /control_post_title --> close';
        $tpl = new swpMVCStamp($str);
        $post = Post::first();
        new PostControlsRenderer($post);
        $expected = "open <label class='Post Post_post_title'
            for='Post_post_title_input'>Title</label><input class='Post Post_control Post_post_title'
            name='Post[post_title]' id='Post_post_title_input' type='text'  value='".$post->post_title."' /> close";
        \Enhance\Assert::areIdentical($this->crutil->hminify($expected),
                                    $this->crutil->hminify($post->render($tpl)));
    }
    
}


if (!isset($bootstrapped))
{
    // Find the tests - '.' is the current folder
    //\Enhance\Core::discoverTests('.');
    // Run the tests
    \Enhance\Core::runTests();  
}