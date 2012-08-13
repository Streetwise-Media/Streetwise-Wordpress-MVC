<?php

class swpMVCBaseController
{
    
    public $page_title = "";

    public function __construct()
    {
        add_filter( 'wp_title', array($this, 'set_page_title') );
    }
    
    public function set_page_title($title)
    {
    	if(trim($this->page_title) !== '') return $this->page_title;
    	return $title;
    }
    
    public function template($name)
    {
        if (!$this->_templatedir and $this->logError('No controller template directory defined for template '.$name))
                return new Stamp('No controller template directory defined');
        return new Stamp(Stamp::load($this->_templatedir.$name.'.tpl'));
    }
    
    public function enqueue_scripts()
    {
        if (!is_array($this->_scripts)) $this->_scripts = array();
        foreach($this->_scripts as $script)
        {
            call_user_func_array('wp_enqueue_script', $script);
        }
    }
    
    public function localize_scripts()
    {
        foreach($this->_script_localizations as $l)
        {
            call_user_func_array('wp_localize_script', $l);
        }
    }
    
    public function enqueue_styles()
    {
        foreach($this->_styles as $style)
        {
            call_user_func_array('wp_enqueue_style', $style);
        }
    }
    
    public function set404()
    {
        global $wp_query;
        header("HTTP/1.0 404 Not Found - Archive Empty");
        $wp_query->set_404();
        require TEMPLATEPATH.'/404.php';
        exit;
    }
    
    public function force_login($modal_title=false)
    {
        global $blog_id;
        $template_url = get_bloginfo('template_url');
        $your_co_url = get_bloginfo('template_url')."/css/".$blog_id."/images/your-co.png";
        $modal_title = ($modal_title) ? $modal_title : 'Please Login';
        get_header();
        echo $this->template('job-creation')->copy('careers_header')->replace('template_url', $template_url)
            ->replace('your_co_url', $your_co_url);
        echo '
            <script id="binno_process_channel_invite_script" type="text/javascript">
            head.ready(function() {
                jQuery("document").ready(function($){
                    $("#binno_login_now").val("Log In");
                    $("#binno_login_now").removeAttr("disabled");
                    $("#binno_signup_now").val("Signup");
                    $("#binno_signup_now").removeAttr("disabled");
                    $(".binno_modal_header_caption").text("'.$modal_title.'");
                    $("#x_button").remove();
                    $("#binno_modal").css("height", "170px").css("width", "400px");
                    $("#binno_modal_inner").css("height", "140px").css("width", "400px" );
                    $(".binno_modal_footer_links").html(\'<span id="binno_manual_login_reg" class="binno_gigya_link">Manual login</span> | <span id="binno_manual_registration_link" class="binno_gigya_link">Signup</span>\');
                    });
                });
            </script>';
        get_footer();

    }
    
    public function link($controller, $method, $params=array())
    {
        $routes = swpMVCCore::instance()->router->routes;
        $matched_route = _::find($routes, function($route) use ($controller, $method, $params) {
            //echo $route['controller'].' '.$route['method'].' '.substr_count($route['route'], ':p').' '.count($params);
            return $route['controller'] === $controller and $route['method'] == $method and
                count($params) === substr_count($route['route'], ':p');
        });
        $error_msg = 'Could not find route matching '.json_encode(
                            array('controller' => $controller, 'method' => $method, 'params' => $params));
        if (!is_array($matched_route) and !isset($matched_route['route'])) return $this->logError($error_msg);
        $r = $matched_route['route'];
        foreach($params as $p) $r = preg_replace('/:p/', $p, $r, 1);
        return get_bloginfo('url').$r;
    }
    
    public function logError($msg)
    {
        trigger_error($msg, E_USER_WARNING);
        Console::error($msg);
        return true;
    }
}