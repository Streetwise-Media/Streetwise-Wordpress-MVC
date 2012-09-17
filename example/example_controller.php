<?php

class swpMVC_Example_Controller extends swpMVCBaseController
{
    
    public function __construct()
    {
        parent::__construct();
        $this->_templatedir = dirname(__FILE__).'/views/';
    }
    
    public function before()
    {
        
    }
    
    public function after()
    {
        
    }
    
    public function hello($first='', $last='')
    {
        get_header();
        echo "Hello $first $last!";
        Console::log('Heres a console message');
        get_footer();
    }
    
    public function render_post_form($slug=false)
    {
        if (!$slug) return $this->set404();
        $post = Post::first(array('conditions' => array('post_name = ?', $slug)));
        if (!$post) return $this->set404();
        get_header();
        echo $post->render($this->template('post_form'));
        echo Post::renderForm($this->template('post_form'), 'new_post');
        get_footer();
    }
    
    public function wp_style()
    {
        get_header();
        echo '<div id="content">';
        //copied from http://codex.wordpress.org/Function_Reference/get_the_post_thumbnail
        $thumbnails = get_posts('numberposts=50');
        foreach ($thumbnails as $thumbnail) {
          if ( has_post_thumbnail($thumbnail->ID)) {
            echo '<a href="' . get_permalink( $thumbnail->ID ) . '" title="' . esc_attr( $thumbnail->post_title ) . '">';
            echo get_the_post_thumbnail($thumbnail->ID, 'thumbnail');
            echo '</a>';
          }
        }
        //end copied code
        echo '</div>';
        get_footer();
    }
    
    public function swpmvc_style()
    {
        get_header();
        $posts = Post::all(array('limit' => 50, 'include' =>
                            array('thumbmeta' => array('thumbnail')),
                            'order' => 'post_date DESC'));
        $self = $this;
        //$tpl_arr = array('thumb', 'post_thumbs_2');
        //$tpl = $tpl_arr[array_rand($tpl_arr)];
        $tpl = 'post_thumbs_2';
        _::each($posts, function($post) use ($self, $tpl) {
            if (!$post->thumbnail_url()) return;
            echo $post->render($self->template($tpl))
                ->replace('image_src', $post->thumbnail_url())->replace('permalink', get_permalink($post->id));
        });
        get_footer();
    }
    
    public function test_validations()
    {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') return $this->save_test_validations();
        $this->_scripts = array(
            array('save_post', get_bloginfo('url').'/wp-content/plugins/example/assets/js/save_post.js', array('jquery'))  
        );
        get_header();
        echo ExampleModel::renderForm($this->template('post_form'), 'new_post');
        get_footer();
    }
    
    public function save_test_validations()
    {
        $post = new ExampleModel($_POST['post']);
        if (!$post->save()) die(json_encode(array('errors' => $post->formErrors('new_post'))));
        die('success');
    }
}