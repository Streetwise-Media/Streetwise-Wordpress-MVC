#swpMVC

swpMVC is a lightweight MVC framework built to bring some of the experience of other
rapid application development frameworks to WordPress. Inspired largely by Rails, Express
and FuelPHP, it aims to make routing, modeling, and rendering easy, giving you more control
over your code structure than WordPress gives out of the box, without adding too much
extra work.


##Tutorial

The quickest way to get familiar with the swpMVC framework is with the TodoApp tutorial found
[here](http://streetwise-media.github.com/swpmvc_todos/)

##Starter Plugin

***

In place of code generation, you can get a jump start on plugin development by examining
(and using) the starter plugin found in the starter_plugin directory.

###Why a singleton?

Singletons are little more than object oriented global variable, but your plugins need to add WordPress filters and
actions. Using a singleton gives you easy access to the plugin class, and also makes sure you don't
end up running the methods you hang on your filters and actions more than once each.

###require_dependencies

This is called in the constructor, and this is where you should include your models and controllers. By
creating the plugin instance on the swp_mvc_init action, we ensure that the swpMVC core is loaded,
and the base classes which your models and controllers must extend will be available.

###add_actions

Also called in the constructor, this is used to hook the add_routes method to the swp_mvc_routes
filter, where you can add your plugin routes using the syntax described in the router section. By
placing this after the require_dependencies call in the constructor, you can be sure your controllers
are loaded when you add the routes to the system.

###add_routes

swpMVC follows a routing structure that more closely resembles Sinatra or Express than WordPress'
rewrite rules. See the next section for syntax.

##Routes

***

### Adding routes

swpMVC routes are stored as an array of arrays, with each array stored representing one route using the following
structure

    <?php
    
        $route = array('controller' => 'ControllerClass',
                                'method' => 'ControllerMethod',
                                'route' => '/url/of/route/:p/:p'
                            );
        
There is no "automagic" routing, everything must be declared. This is done so that your routing structure is exactly as
you want, with no additional steps required to turn off magic routes.

### Routing parameters

Parameters in your route are represented with the token ":p"

They will be passed to your controller method in the same order they appear in the route. Skipping named parameters allows the
framework to use [only one additional querystring variable](http://codex.wordpress.org/Rewrite_API/add_rewrite_tag)

### Auto-flush rewrite rules

The core framework will monitor whether swpMVC routes have been added, modified or removed, and flush the rewrite
rules as needed, so there is no need to do this manually.

### Example

Here's a full example adding routes from your swpMVC plugin based off the example plugin included in the example
directory

    <?php
        
        public function add_routes($routes)
        {
            $r[] = array('controller' => 'swpMVC_Example_Controller',
                        'method' => 'wp_style',
                        'route' => '/recent_thumbs/wp_style');
            $r[] = array('controller' => 'swpMVC_Example_Controller',
                            'method' => 'swpmvc_style',
                            'route' => '/recent_thumbs/swpmvc_style');
            $r[] = array('controller' => 'swpMVC_Example_Controller',
                            'method' => 'render_post_form',
                            'route' => '/post_form/:p');
            $s =  array_merge($routes, $r);
            return $s;
        }
        
###Overriding the router

Sometimes you may want to override a default route provided by WordPress. In this case, you can use the
swpmvc\_request\_override action hook to manually set the necessary query vars that will redirect to your
desired controller method. The following example will call the PostController::single_post method passing in
the slug when a single post is viewed

    //plugin.php
    
    add_action('swpmvc_request_override', 'override_request');
    
    function override_request()
    {
        if (!is_single()) return;
        global $wp_query, $post;
        $wp_query->query_vars['swpmvc_controller'] = 'PostController';
        $wp_query->query_vars['swpmvc_method'] = 'single_post';
        $wp_query->query_vars['swpmvc_params'] = array($post->post_name);
    }
    
    //PostController.php
    
    class PostController.php extends swpMVCBaseController
    {
        public function single_post($slug)
        {
            //retreive post model by slug and do something with it.
        }
    }

##Models

##Views

##Templates

##Controllers

##Roles

##Renderers

##ControlRenderers

##Validators

##jswpMVC