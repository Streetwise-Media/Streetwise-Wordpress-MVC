<?php

class TermRelationship extends swpMVCBaseModel
{
    public static function tablename()
    {
        global $wpdb;
        return $wpdb->prefix.'term_relationships';
    }
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
    public static function tablename()
    {
        global $wpdb;
        return $wpdb->prefix.'term_taxonomy';
    }
    static $primary_key = 'term_id';
    static $has_many = array(
        array('postterms', 'class' => 'TermRelationship', 'primary_key' => 'term_taxonomy_id', 'foreign_key' => 'term_taxonomy_id')
    );
    static $has_one = array(
        array('term', 'class' => 'Term', 'foreign_key' => 'term_id')
    );
}


class Post extends swpMVCBaseModel
{
    public static function tablename()
    {
        global $wpdb;
        return $wpdb->prefix.'posts';
    }
    
    static $belongs_to = array(
                    array('user', 'foreign_key' => 'post_author')
                );
    static $has_many = array(
        array('postterms', 'class' => 'TermRelationship', 'foreign_key' => 'object_id'),
        array('comments', 'class' => 'Comment', 'foreign_key' => 'comment_post_ID',
            'conditions' => array('comment_parent = ?', array('0'))),
        array('meta', 'class' => 'PostMeta', 'foreign_key' => 'post_id')
    );
    
    public function tags()
    {
        $tag_terms = _::filter($this->postterms, function($term) { return $term->termtaxonomy->taxonomy === 'post_tag'; });
        return _::map($tag_terms, function($term) { return $term->termtaxonomy->term; });
    }
    
    public function categories()
    {
        $cat_terms = _::filter($this->postterms, function($term) { return $term->termtaxonomy->taxonomy === 'category'; });
        return _::map($cat_terms, function($term) { return $term->termtaxonomy->term; });
    }
    
    public static function controls()
    {
        return array(
            'post_title' => array('type' => 'input', 'label' => 'Title'),
            'post_content' => array('type' => 'textarea', 'label' => 'Content')
        );
    }
    
}

class PostMeta extends swpMVCBaseModel
{
    public static function tablename()
    {
        global $wpdb;
        return $wpdb->prefix.'postmeta';
    }
    
    static $belongs_to = array(
                array('post', 'foreign_key' => 'post_id', 'class_name' => 'Post'),
            );
}

class Comment extends swpMVCBaseModel
{
    public static function tablename()
    {
        global $wpdb;
        return $wpdb->prefix.'comments';
    }
    static $belongs_to = array(
        array('post', 'class' => 'Post', 'foreign_key' => 'comment_post_ID',
              'limit' => 10, 'conditions' => array('post_status = ?', 'publish')),
        array('user', 'foreign_key' => 'user_id'),
        array('comment', 'class' => 'Comment', 'foreign_key' => 'comment_parent', 'limit' => 10)
    );
    static $has_many = array(
        array('comments', 'class' => 'Comment', 'foreign_key' => 'comment_parent')
    );
}

class Term extends swpMVCBaseModel
{
    public static function tablename()
    {
        global $wpdb;
        return $wpdb->prefix.'terms';
    }
    
    static $primary_key = 'term_id';
    static $belongs_to = array(
        array('termtaxonomy', 'class' => 'TermTaxonomy', 'foreign_key' => 'term_id'),
    );
}

class User extends swpMVCBaseModel
{
    private $_usermeta;
    public static function tablename()
    {
        global $wpdb;
        return $wpdb->prefix.'users';
    }
    
    static $has_many = array(
        array('posts', 'foreign_key' => 'post_author', 'limit' => 10, 'conditions' => array('post_status = ?', 'publish')),
        array('comments', 'foreign_key' => 'user_id', 'limit' => 10),
        array('meta', 'class' => 'UserMeta', 'foreign_key' => 'user_id'),
    );
}

class UserMeta extends swpMVCBaseModel
{
    public static function tablename()
    {
        global $wpdb;
        return $wpdb->prefix.'usermeta';
    }
    
    static $belongs_to = array(
                array('user', 'foreign_key' => 'user_id', 'class_name' => 'User'),
            );
}