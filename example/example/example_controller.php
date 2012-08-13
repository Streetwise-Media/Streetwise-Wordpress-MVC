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
                            array('post_thumb_meta' => array('thumb_post')),
                            'order' => 'post_date DESC'));
        $self = $this;
        _::each($posts, function($post) use ($self) {
            if (!$post->thumb_url()) return;
            echo $post->render($self->template('thumb'))
                ->replace('image_src', $post->thumb_url())->replace('permalink', get_permalink($post->id));
        });
        get_footer();
    }
}