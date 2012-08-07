<?php
// Include the test framework
$wp_load_path = $_SERVER['DOCUMENT_ROOT'] . '/';
if (!defined('ABSPATH')) require_once($wp_load_path.'wp-config-test.php');
if (!class_exists('Enhance')) require_once('EnhanceTestFramework.php');
if (!class_exists('swpMVCCore')) require_once(dirname(__FILE__).'/../swpmvc.php');
if (!class_exists('testUtilities')) require_once('testUtilities.php');

global $bootstrapped;
$bootstrapped = 'true';

// Find the tests - '.' is the current folder
\Enhance\Core::discoverTests('.');
// Run the tests
\Enhance\Core::runTests();