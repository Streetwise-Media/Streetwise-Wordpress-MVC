<?php

//avoid direct calls to this file, because now WP core and framework has been used
if ( !function_exists('add_action') ) {
	header('Status: 403 Forbidden');
	header('HTTP/1.1 403 Forbidden');
	exit();
}

if ( function_exists('add_action') ) {
	//if ( error_reporting(E_ALL) ) 
		//error_reporting(E_ALL ^ E_NOTICE);
}

if ( !class_exists('PhpQuickProfiler') ) require_once( 'classes/PhpQuickProfiler.php' );
if ( !class_exists('MySqlDatabase') ) require_once( 'classes/MySqlDatabase.php' );
if ( !class_exists('Console') ) require_once( 'classes/Console.php' );

require_once( 'display.php' );

class swpMVCProfiler
{
	private $_profiled;
	private $profiler;
	private $db = '';

	// constructor
	function __construct()
	{
		add_action('init',array($this,'init'));
		add_action('wp_enqueue_scripts', array($this, 'scripts'));
		add_action('wp_footer', array($this, 'end'));
	}
	
	function scripts()
	{
		wp_enqueue_style('pQp', get_bloginfo('url').'/wp-content/plugins/swpmvc/profiler/css/pQp.css');
	}

	function init()
	{
			$this->db = new MySqlDatabase('127.0.0.1',DB_USER,DB_PASSWORD);
			$this->db->connect(true);
			$this->db->changeDatabase(DB_NAME);

			$this->profiler = new PhpQuickProfiler(PhpQuickProfiler::getMicroTime());

			Console::logSpeed('Initializing...');
	}

	function end()
	{
			Console::logSpeed('Concluding!');
			$this->profiler->display($this->db);
	}

}

if (defined('SW_WP_ENVIRONMENT') and SW_WP_ENVIRONMENT === 'development')
	$p = new swpMVCProfiler();

?>