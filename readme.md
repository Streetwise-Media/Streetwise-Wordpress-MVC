<style>
    code{
        width:900px;
    }
</style>

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

***

Models must extend the swpMVCBaseModel class. This class itself extends ActiveRecord\Model, from the
[PHP ActiveRecord library](http://phpactiverecord.org). For query syntax, CRUD operations, basic model definitions, and
overloading, refer to the ActiveRecord docs. The copy included in swpMVC [includes several modifications to make it more WordPress friendly, (the diff is backwards, sorry)](https://github.com/beezee/php-activerecord/compare/master...original).

###public static function tablename

Instead of declaring the model table with a static variable, we use a static method. This allows us to do the following

    <?php
        
        public static function tablename()
        {
            global $wpdb;
            return $wpdb->prefix.'posts';
        }

This model would now be multisite aware. The advantage to using a method over a variable is that we can now dynamically
define the table property for our model.

###public static function conditions

This defines any conditions that should apply to every finder query that is generated by the model. For example if I wanted
to model draft posts only:
    
    <?php
        public static function conditions()
        {
            return array("post_status = ?", "draft");
        }
        
###public static function joins

This defines any joins that should apply to every finder query that is generated by the model. In general for related eager loading,
I favor include, using the joins method only when my conditions method relies on data in another table. An example of how this
can be used to model categories:

    <?php
    
    class Category extends swpMVCBaseModel
    {
        public static function tablename()
        {
            global $wpdb;
            return $wpdb->prefix.'terms';
        }
    
        public static function conditions()
        {
            global $wpdb;
            $tt = $wpdb->prefix.'term_taxonomy';
            return array("$tt.taxonomy = ?", 'category');
        }
        
        public static function joins()
        {
            global $wpdb;
            $t = self::tablename();
            $tt = $wpdb->prefix.'term_taxonomy';
            return "LEFT JOIN $tt ON $t.term_id = $tt.term_id";
        }
    }
    
Now any finder queries generated by the Category class will include a left join on the term_taxonomy table, and
filter results to include only those where the term_taxonomy.taxonomy field has a value of "category." Filtering
subsets with models is particularly relevant in WordPress, where different "types" of data are frequently lumped
together in single tables.

One catch to using the joins method, is that calls to the [models finder methods](http://www.phpactiverecord.org/projects/main/wiki/Finders)
will need to use table prefixes for any columns that are present in both the main and joined tables. For this I recommend your
Model::tablename() methods.


###Automatic stripslashes

Model properties in string format are automatically run through stripslashes when accessed directly. To override this, call the
properties method on an object, and access the properties from the resulting array.

###$model->render()

swpMVCBaseModel comes with an instance method 'render,' which accepts as an argument a Stamp template object
(see [Templates](/Streetwise-Wordpress-MVC/#templates)
section for details,) and autopopulates the template using the model properties.

###public function sanitize_render()

This method accepts two parameters, a model property value and the property name, and gives you a chance to sanitize it
before it is returned. This will be applied any time you directly access a model property. It can be bypassed in the same
was described under the
[automatic stripslashes section](http://streetwise-media.github.com/Streetwise-Wordpress-MVC/#models/automatic-stripslashes).

Here's an example of a sanitize_render definition that will run all model properties except title
through strip_tags when accessed directly:

    <?php
    
        public function sanitize_render($value, $name)
        {
            if ($name === 'title') return $value;
            return strip_tags($value);
        }
        
        //calling the following on an instance of this model
        //would strip all tags from the property value:
        
        echo $model->property;
        
        //the following would bypass
        
        $properties = $model->properties(); 
        echo $properties['property'];

###public function render_{{property_name}}

These methods act as overrides for your properties when called by the render method. For example, if a Stamp view object
passed to the render method contains a tag called post_name, and your Post model has a method called render_post_name,
the return value of that method will be used to populate the Stamp object in favor of the value of the instance property
post_name.

###Model::renderForm()

This method can be called statically on a model class to render an empty form for the class properties.

The method accepts three parameters. The first parameter is required and must be a valid Stamp template object to be
populated, the second is an optional form prefix (defaults to the class name,), and last is an optional
[ControlRenderer](/Streetwise-Wordpress-MVC/#controlrenderers) which defines the form controls that
correspond to your model properties.

###Form prefixes

Each model rendering a form adds a prefix to its form elements. By default when invoked via $model->render(), the form prefix
will be the class name of the model instance invoking the render method. To override this, set the '\_prefix' property on
the  model instances form helper before calling render, as follows:

    <?php
        
        $model->form_helper()->_prefix = 'my_custom_form_prefix';
        $model->render($this->template('template_name'));
        
###$model->formErrors()

This method can be called on a model instance to return an array of validation errors from an attempt to save an invalid
model, paired with the ID of the control for each value that fails validation as would be generated by $model->render() or
Model::renderForm(). This method accepts form prefix as an argument, and defaults to classname as a form prefix.

The format of the response is below

    [
        {
            value: (model attribute that failed validation),
            errors: [array of error messages added by validation failures],
            control: (id of control element generated by render or formRender method)
        }
    ]
    
See the example plugin controller save_test_validations method paired with the example plugin assets/js/save_post.js file
for a clear example of usage.

###A note about "through" relationships

Personally I've not had much success declaring [through relationships](http://www.phpactiverecord.org/projects/main/wiki/Associations#has_many_through)
with my activerecord models, so I have stuck to nesting includes during my finder calls manually. While the through relationship
does improve efficiency, I've decided it's not worth the trouble, as even without the generated joins you can typically get all
the data you will need for less than 10 queries with simple nested eager loading. Here's an example of getting 10 posts with their
related post tag and category data using simple nested eager loading:

    <?php
        
        $posts = Post::all(array('limit' => 10,
                'include' => array('postterms' =>
                    array('termtaxonomy' =>
                        array('term')
                    )
                )
            )
        );
        
If you do have some success modeling with the through relationships, please reach out via Github issues and I'll be happy to update
the docs and repo as needed.

###Included models


*       Post
*	PostMeta
*	TermRelationship
*	TermTaxonomy
*	Term
*       Category
*       Tag
*	Comment
*	User
*	UserMeta

All models can be found in models/wordpress_models.php. There's not alot of code, and the best way to learn model definition, as
well as see what added methods are available on each model is to view the source.

##Model meta

***

the swpMVCBaseModel class includes some methods for working with meta, a popular WordPress data structure. Any table with
columns foreign_key, meta_key, meta_value will work with these methods, once you've defined a
[$has_many relationship](http://www.phpactiverecord.org/projects/main/wiki/Associations) to a model with the name meta.

###$model->meta()

This method will return an empty array if there is no $has_many [relationship](http://www.phpactiverecord.org/projects/main/wiki/Associations)
named meta defined for the model on which it is called.

If the meta relationship is defined, it must point to a Model of a table with columns foreign_key, meta_key and meta_value.
By calling $model->meta() when these circumstances are met, the return value will be an associative array where keys are
equal to meta_key, and values are equal to the meta_value. In the case of duplicate meta_key rows for one model instance,
the meta_value will be an indexed array of all values found.

It is recommended to [eager load](http://www.phpactiverecord.org/projects/main/wiki/Finders#eager-loading) the meta when
querying for your models if you intend to use this method, to avoid the
[n+1 query problem](http://www.phabricator.com/docs/phabricator/article/Performance_N+1_Query_Problem.html)

The method accepts two parameters, $key and $raw. Passing in $key returns the meta value where meta\_key matches the
provided key. Passing in true for the $raw parameter will return an array of actual meta objects, as opposed to the hydrated
meta\_values.

###public function hydrate_meta

When working with ActiveRecord Models, WordPress will not serialize and unserialize data automatically for you. This method
accepts two parameters, meta_key and meta_value, and gives you the opportunity to modify meta values as necessary when
retreiving them using the $model->meta() method. (This does not apply when the $raw parameter is passed as true.)

Here's an example that will unserialize meta when the key is equal to 'serialized_meta':

    <?php
        
        public function hydrate_meta($meta_key, $meta_value)
        {
            return ($meta_key === 'serialized_meta') ?
                unserialize($meta_value) : $meta_value;
        }
        
###public function dehydrate_meta

This method serves the opposite purpose of hydrate_meta, and will be invoked on any values assigned to the 'meta_value'
property of a meta object. For a class with dehydrate_meta defined as follows:

    <?php
    
        public function dehydrate_meta($meta_key, $meta_value)
        {
            return ($meta_key === 'serialized_meta') ?
                serialize($meta_value) : $meta_value;
        }
        
Calling the below on a model instance of the class would make the following boolean statment true:

    <?php
    
        $model->meta_value = array('one', 'two', 'three');
        $model->meta_value === 'a:3:{i:0;s:3:"one";i:1;s:3:"two";i:2;s:5:"three";}';


##Views

***

Views allow you to group together and define methods that will populate template tags which match those method names.
Properties can be set on views to be accessed from within view methods, and \_\_get() and \_\_set() are utilized in
the base class to prevent memory consumption that results from assigning undeclared properties to an object instance.
Views must extend the swpMVCView class

###$view->render()

This method requires one argument, an instance of [swpMVCStamp](/Streetwise-Wordpress-MVC/#templates) to be populated,
and returns the template with any template tags matching a method name on the view replaced by the return value of that
method. The template object will be passed to each method called on the view object, so can be accepted by any view
method for use in generating a return value.

###Example
    
    //view class

    class ExampleView extends swpMVCView
    {
        public function header()
        {
            return $this->header
        }
        
        public function post_listing(swpMVCStamp $output) //this will be the template object passed to $view->render
        {
            $r = '';
            foreach($this->posts as $post)
                $r .= $post->render(clone $output->copy('post_listing'));
            return $r;
        }
    }
    
    //template file example.tpl
    
    <div>
        <h2><!-- header --><!-- /header --></h2>
        <!-- post_listing -->
            <div class="post-item">
                <h4><!-- post_title --><!-- /post_title --></h4>
            </div>
        <!-- /post_listing -->
    </div>
    
    //controller class
    
    class ExampleController extends swpMVCBaseController
    {
        public function before()
        {
            $this->_templatedir = '/full/path/to/example.tpl/with/trailing/slash/';
        }
    
        public function example_method()
        {
            $posts = Post::all(array('limit' => 10));
            $view = new ExampleView();
            $view->header = 'Example Post Listing'
            $view->posts = $posts;
            echo $view->render($this->template('example'));
        }
    }
    
In the above example, ExampleController::example_method would output Example Post Listing in the h2 at the top of
example.tpl, and then 10 divs of class post-item with the post title of each in their respective h4 tag.

##Templates

***

swpMVC Templates are based off of an older version of [Gabor DeMooij's Stamp library,](https://github.com/gabordemooij/stamp/blob/StampEngine/Stamp.php)
using extremely simple principles. Your templates will contain no logic at all, and in most cases will be completely valid
HTML on their own.

###Stamp tags

Stamp tags take the form of html comments, with an opening and closing comment representing one replaceable block.
For example:

    <a href="<!-- url --><!-- /url -->">Link to somewhere</a>
    
gives you a region labeled url that can be manipulated through a stamp object.

###$stamp->replace()

If you place the above template code in a file called template.tpl, and call the below code from your controller:

    <?php
        
        echo $this->template('template')->replace('url', 'http://www.somesite.com');
        
the result would be:

    <a href="http://www.somesite.com">Link to somewhere</a>
    
###$stamp->copy()

This method allows you to copy defined template regions. Given the below template in file template.tpl:

    <p>Here's some stuff</p>
    <!-- more_stuff -->
        <p>And here's some more stuff</p>
    <!-- /more_stuff -->
    
The below code would yield true at the boolean in the last statement:

    <?php
    
        $more_stuff = $this->template('template')->copy('more_stuff');
        $more_stuff === "<p>And here's some more stuff</p>";


This is useful when populating one template with multiple models. For example, given the below template in file post.tpl:

    <h1><!-- post_title --><!-- /post_title --></h1>
    <!-- author_data -->
        by <!-- display_name --><!-- /display_name -->
    <!-- /author_data -->
    
The below controller code would replace post_title with the title of the post object, and display_name with the display name
of the post author:

    <?php
        
        $post = Post::first(array('include' => 'user');
        echo $post->render($this->template('post'))
            ->replace('author_data',
                $post->user->render(
                    $this->template('post')->copy('author_data')
                )
            );
    
###Populating templates with $model->render()

When using the [$model->render()](/Streetwise-Wordpress-MVC/#models/model-render) method, your model will automatically
replace tags named according to the following conventions:

    <!-- attribute_name --><!-- /attribute_name -->

The above gets replaced with a model property named attribute\_name, or the return value of model instance method
    render\_attribute\_name if such method exists.
    
    <!-- attribute_name_block -->
        <!-- attribute_name --><!-- /attribute_name -->
    <!-- /attribute_name_block -->
    
The above gets replaced with a model property named attribute\_name, or the return value of model instance method
render\_attribute\_name if such method exists. If the value is falsy or returns true when passed through
$model->needs_template_cleanup(), the entire attribute\_name\_block section is stripped from the template when rendered.
    
    <!-- control_attribute_name --><!-- /control_attribute_name -->

The above gets replaced with a rendered [form control](/Streetwise-Wordpress-MVC/#controlrenderers/swpmvcmodelcontrol)
object returned by the 'attribute\_name' method of any [ControlRenderer](/Streetwise-Wordpress-MVC/#controlrenderers)
attached to the model.
    
    <!-- control_label_attribute_name --><!-- /control_label_attribute_name -->

The above gets replaced with the label property of the [form control](/Streetwise-Wordpress-MVC/#controlrenderers/swpmvcmodelcontrol)
object returned by the 'attribute\_name' method of any [ControlRenderer](/Streetwise-Wordpress-MVC/#controlrenderers)
attached to the model.


##Controllers

***

With well defined models and model extensions, controllers can be relatively sparse.
Your controller classes must extend swpMVCBaseController, which equips them with the following functionality.

###$this->page_title

Set this property in your controller method to what you want the title attribute on the generated page to be. This needs to be set
before you call get\_header().

###$this->_templatedir

Set this property to the directory where your views for the current controller are stored, including a trailing slash.
Best practice is to define this in the constructor, in which case you'll want to make sure to call the parent constructor as well:

    <?php
        
        public function __construct()
        {
            $this->_templatedir = dirname(__FILE__).'/../views/';
            parent::__construct();
        }
        
###$this->_scripts

Set this property to an array where each element is an array of arguments to be passed to
[wp_enqueue_script](http://codex.wordpress.org/Function_Reference/wp_enqueue_script). Define the property before calling
get\_header() to have your scripts automatically enqueued on that page.

###$this->_styles

Same as $this->\_scripts, except each element of this array should be an array of arguments for
[wp_enqueue_style](http://codex.wordpress.org/Function_Reference/wp_enqueue_style).

###$tthis->version

Allows you to set a default version for javascripts and css files added by the controller. Passing in a version parameter when
defining a script or style array will override this setting.

###$this->_script_localizations

Same as \_styles and \_scripts, except each element of this array should be an array of arguments for
[wp_localize_script](http://codex.wordpress.org/Function_Reference/wp_localize_script).

###$this->template()

Requires $this->\_templatedir to be defined. Accepts the filename of a template (minus the file extension, which must be .tpl,)
and returns an [swpMVCStamp](/Streetwise-Wordpress-MVC/#templates) object for population and rendering.
Here's an example of using the template method to pass a view to a models render method:

    <?php
        
        $post = Post::first();
        $post->render($this->template('show_post'));
        
In the above example, we assume that the controllers templatedir property points to a directory that contains a file called
show_post.tpl, which contains the correct [Stamp tags](/Streetwise-Wordpress-MVC/#templates/stamp-tags)
to be populated by the Post model.

###$this->set404()

This method generates a WordPress 404 page using the currently selected WordPress theme. It must be called before any output
is generated. Here's an example of using this method within a controller method:

    <?php
    
        public function show_post($slug=false)
        {
            if (!$slug) return $this->set404();
            $post = Post::first(array('conditions' => array('post_name = ?', $slug)));
            if (!$post) return $this->set404();
            get_header();
            $post->render($this->template('show_post'));
            get_footer();
        }
        
In this method, if no slug was passed to the controller method, we return a 404. If a slug was passed, we attempt to find a post
using the provided slug. If we cannot, we return a 404. Only at that point if we have found a post using the provided slug do we
begin to generate output from the controller method.

###$this->isPost()

Returns true if the request method is POST, false otherwise.

###$this->noindex()

Adds a noindex nofollow tag to the header. Must be called before get\_header()

###$this->nocache

Adds Cache-Control headers to prevent page from being cached. Must be called before any output is sent.

###self::link()

This method accepts three arguments, a controller class name, a method name, and an optional array of parameters. It will then
return the corresponding url for that controller method. For example, if I've defined the following
[route](/Streetwise-Wordpress-MVC/#router/adding-routes) in my plugin:
    
    <?php
    
        $route = array('controller' => 'ControllerClass',
                            'method' => 'ControllerMethod',
                            'route' => '/url/of/route/:p/:p'
                        );
                        
Then the below statement would be true:

    <?php
    
        ControllerClass::link(
                    'ControllerClass',
                    'ControllerMethod', 
                    array('arg1', 'arg2')
                ) === get_bloginfo('url').'/url/of/route/arg1/arg2';
                
The link method is preferable to hard coding any fragment of a url into your views or controllers,
since this will automatically update any references if you change the route definitions for your plugin.

###$this->logError()

This method accepts a string as a parameter, and will write that string as an E\_USER\_WARNING level error to your PHP log,
as well as an error notice to the [pQp Console](/Streetwise-Wordpress-MVC/#logging-utility/php-quick-profiler) if you are running
in the development environment.

###public function before()

This method will run before any controller method is executed.

###public function after()

This method will run after any controller method is executed

###protected static $_cache

Set this variable on your controller class to stash queried data for accessing via other parts of your codebase. For example to
access a post queried from your controller method from a sidebar widget without setting a global variable or running a second
query, in your controller use:

    <?php
    
        $post = Post::first();
        self::$_cache['post'] = $post;

And then in your widget code you can use the following:

    <?php
    
        $cache = ControllerClass::cache();
        $post = $cache['post'];
        
Note you must use the static cache() method to retrieve the value. This prevents outside sources from polluting the data
cached by your controller.

##Model Extenders

There are four types of model extenders that allow you attach functionality to models at runtime, each defined in detail
below. All three types share common means of injection.

###Injecting an extender

To inject a model extender into a single model, simply pass the model to the extender constructor as follows:

    new ModelExtender($model);
    
###Batch injecting extenders

To inject a type of model extender into an array of models, pass the array of models to the static method batch\_inject

    $posts = Post::all(array('limit' => 10));
    PostExtender::batch_inject($posts); //all 10 posts now have a PostExtender assigned to them

###Roles

Roles allow you to extend your models at runtime with additional getters, setters, and general purpose methods for
business logic specific to a particular context. Roles must extend swpMVCBaseRole.

####Example

Note in the Role definition the model to which the Role is assigned is referred to as $this->model. Also note that any
properties added by a Role will not be acknowledged by the underlying ActiveRecord model, and therefore not persisted
to the database.

    class UserSignupRole extends swpMVCBaseRole
    {
        private $_authorized;
        
        public function set_authorized($value)
        {
            $this->_authorized = $value;
        }
        
        public function get_authorized()
        {
            return $this->_authorized;
        }
        
        public function send_welcome()
        {
            wp_mail($this->model->user_email, 'Welcome to the site!', 'Hey this is the welcome email.');
        }
    }
    
    class ExampleUserController
    {
        //This way will not work because we have not assigned a role
        public function confirm_account_bad($id)
        {
            $user = User::first($id);
            if (!$user) return $this->set404();
            $user->authorized = true; // throws an invalid property exception
            if ($user->authorized) //throws an invalid property exception
                $user->send_welcome(); //throws an invalid method exception
        }
        
        //Same code as above, works after we've assigned the role
        public function confirm_account($id)
        {
            $user = User::first($id);
            if (!$user) return $this->set404();
            new UserSignupRole($user); //this makes the rest of the method work
            $user->authorized = true;
            if ($user->authorized)
                $user->send_welcome();
        }
    }

###Renderers

***

Renderers are defined similarly to Roles, and function similarly to Views. They are used to define rendering rules for
model properties when a model is rendererd. Renderers must extend swpMVCBaseRenderer

####Example

In the below example, all methods in the renderer will be used to replace corresponding template tags when the template
is passed to $model->render(); Note that again, the model is available as $this->model in the renderer definition, and
also that the renderer methods will take precedence over any corresponding model property or
$model->render\_{{property\_name}} method.

    class ObnoxiousPostRenderer extends swpMVCBaseRenderer
    {
        public function post_title()
        {
            return strtoupper($this->model->post_title);
        }
        
        public function has_manners()
        {
            return false;
        }
        
        public function post_content()
        {
            return wpautop(strtoupper($this->model->post_content));
        }
    }
    
    //template post_view.tpl
    <div class="post">
        <h3><!-- post_title --><!-- /post_title --></h3>
        <!-- post_content --><!-- /post_content -->
        <!-- has_manners_block -->
            <p>This post was written by someone who has an inside voice.</p>
        <!-- /has_manners_block -->
    </div>
    
    class ExamplePostController extends swpMVCBaseController
    {
        public function before()
        {
            $this->_templatedir = '/full/path/to/post_view.tpl/with/trailing/slash/';
        }
        
        public function view_obnoxious_post($id)
        {
            $post = Post::first($id);
            new ObnoxiousPostRenderer($post);
            echo $post->render($this->template('post_view'));
        }
    }
    
The view\_obnoxious\_post method in the above example will give us a Craigslist style display, where EVERYTHING IS
UPPERCASED FOR NO GOOD REASON AT ALL. Also note that because the renderers has\_manners method returns false, the
has\_manners\_block section is automatically stripped from the template when renderered by a post with this
renderer attached.

##ControlRenderers

##Validators

##jswpMVC