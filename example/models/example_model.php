<?php

class ExampleModel extends swpMVCBaseModel
{
    public static function tablename()
    {
        return 'wp_posts';
    }
    
    public static function controls()
    {
        return array(
                     'post_title' => array('type' => 'input', 'label' => 'Post Title'),
                    );
    }
    
    public function validate()
    {
        if ($this->post_title !== 'freekydeeky')  $this->errors->add('post_title', 'must equal freekydeeky');
        if ($this->post_title !== 'reallyfreekydeeky') $this->errors->add('post_title', 'is not freeky deeky enough');
    }
}