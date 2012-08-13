<?php

class swpMVC_Example_Controller extends swpMVCBaseController
{
    
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
    
    public function show_post($slug='')
    {
        $posts = Post::all(array('include' => array('comments' => array('user' => array('usermeta'), 'comments'),
                                        'user' => array('usermeta'), 'postterms' => array( 'termtaxonomy' => array('term'))),
                                 'conditions' => array('post_name = ? AND post_status = "publish"', $slug)));
        if (empty($posts)) return $this->set404();
        
        get_header();
        $post = $posts[0];
        echo '<h2>'.$post->post_title.'</h2>';
        echo '<h4>'.$post->user->display_name.'</h4>';
        echo 'Posted in: '.join(', ', _::map($post->categories(), function($cat) { return $cat->name; }));
        echo '<br />';
        echo 'Tagged: '.join(', ', _::map($post->tags(), function($tag) { return $tag->name; }));
        echo wpautop($post->post_content);
        echo '<h2>Comments</h2>';
        _::each($post->comments, function($comment) {
            echo '<div><h5>'.$comment->user->display_name.' @'.$comment->user->meta('twitter').'</h5>'.wpautop($comment->comment_content);
            _::each($comment->comments, function($comment) {
                echo '<div style="margin-left:20px"><h5>'.$comment->user->display_name.' @'.$comment->user->meta('twitter').'</h5>'.
                    wpautop($comment->comment_content).'</div>';
                });
            echo '</div>';
        });
        get_footer();
    }
    
    public function post_author($slug='')
    {
        $posts = Post::all(array('include' =>  array('user' => array('usermeta', 'comments' => array('post'), 'posts')),
                                 'conditions' => array('post_name = ? AND post_status = "publish"', $slug)));
        if (empty($posts)) return $this->set404();
        $post = $posts[0];
        get_header();
        echo '<h2>'.$post->user->display_name.'</h2>';
        echo '<h3>Comments</h3>';
        _::each($post->user->comments, function($comment) {
            echo 'on :<a href="'.get_permalink($comment->post->id).'">'.$comment->post->post_title.'</a><br />';
            echo wpautop($comment->comment_content);
        });
        echo '<h3>Posts</h3>';
        _::each($post->user->posts, function($post) {
            echo '<a href="'.get_permalink($post->id).'">'.$post->post_title.'</a><br />';
        });
        get_footer();
    }
}