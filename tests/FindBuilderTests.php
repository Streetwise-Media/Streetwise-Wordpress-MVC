<?php
global $bootstrapped;
$wp_load_path = $_SERVER['DOCUMENT_ROOT'] . '/';
if (!defined('ABSPATH')) require_once($wp_load_path.'wp-config-test.php');
if (!class_exists('Enhance')) require_once('EnhanceTestFramework.php');
if (!class_exists('swpMVCCore')) require_once(dirname(__FILE__).'/../swpmvc.php');
if (!class_exists('testUtilities')) require_once('testUtilities.php');

class FindBuilderTests extends \Enhance\TestFixture
{
    public function setUp()
    {
        global $wpdb;
        $this->utility = new testUtilities();
        $this->c = swpMVCCore::instance();
        $this->fb = new swpMVCFinder();
    }
    
    public function tearDown()
    {
        
    }
    
    public function test_find_builder_handles_simple_WHERE_EQUALS()
    {
        $args = array('mouse' => 'gray');
        $expected = array('mouse = ?', 'gray');
        \Enhance\Assert::areIdentical($expected, $this->fb->find($args));
        $args = array('mouse' => 'gray', 'house' => 'green');
        $expected = array('mouse = ? AND house = ?', 'gray', 'green');
        \Enhance\Assert::areIdentical($expected, $this->fb->find($args));
    }
    
    public function test_find_builder_handles_mixed_EQUALS_and_IN()
    {
        $args = array('mouse' => 'gray');
        $expected = array('mouse = ?', 'gray');
        \Enhance\Assert::areIdentical($expected, $this->fb->find($args));
        $args = array('house' => 'green', 'mouse' => array('house', 'garage'));
        $expected = array('house = ? AND mouse IN (?)', 'green', array('house', 'garage'));
        \Enhance\Assert::areIdentical($expected, $this->fb->find($args));
    }
    
    public function test_find_builder_handles_LTE_and_GTE()
    {
        $args = array('mouse' => 'gray', '$lte:mouse_height' => 12);
        $expected = array('mouse = ? AND mouse_height <= ?', 'gray', 12);
        \Enhance\Assert::areIdentical($expected, $this->fb->find($args));
        $args = array('house' => 'green', 'mouse' => array('house', 'garage'), '$gte:mouse_height' => 12);
        $expected = array('house = ? AND mouse IN (?) AND mouse_height >= ?', 'green', array('house', 'garage'), 12);
        \Enhance\Assert::areIdentical($expected, $this->fb->find($args));
    }

    public function test_find_builder_handles_NEQ()
    {
        $args = array('mouse' => 'gray', '$lte:mouse_height' => 12);
        $expected = array('mouse = ? AND mouse_height <= ?', 'gray', 12);
        \Enhance\Assert::areIdentical($expected, $this->fb->find($args));
        $args = array('$neq:house' => 'green', 'mouse' => array('house', 'garage'), '$gte:mouse_height' => 12);
        $expected = array('house <> ? AND mouse IN (?) AND mouse_height >= ?', 'green', array('house', 'garage'), 12);
        \Enhance\Assert::areIdentical($expected, $this->fb->find($args));
    }
    
    public function test_find_builder_handles_NI()
    {
        $args = array('mouse' => 'gray', '$lte:mouse_height' => 12);
        $expected = array('mouse = ? AND mouse_height <= ?', 'gray', 12);
        \Enhance\Assert::areIdentical($expected, $this->fb->find($args));
        $args = array('house' => 'green', '$ni:mouse' => array('house', 'garage'), '$gte:mouse_height' => 12);
        $expected = array('house = ? AND mouse NOT IN (?) AND mouse_height >= ?', 'green', array('house', 'garage'), 12);
        \Enhance\Assert::areIdentical($expected, $this->fb->find($args));
    }
    
    public function test_find_builder_handles_regex_or()
    {
        $args = array('mouse' => 'gray', '$lte:mouse_height' => 12);
        $expected = array('mouse = ? AND mouse_height <= ?', 'gray', 12);
        \Enhance\Assert::areIdentical($expected, $this->fb->find($args));
        $args = array('house' => 'green', '$rxor:mouse' => array('house', 'garage'), '$gte:mouse_height' => 12);
        $expected = array('house = ? AND (mouse REGEXP ? OR mouse REGEXP ?) AND mouse_height >= ?', 'green', 'house', 'garage', 12);
        \Enhance\Assert::areIdentical($expected, $this->fb->find($args));
    }
    
    public function test_find_builder_handles_regex_and()
    {
        $args = array('mouse' => 'gray', '$lte:mouse_height' => 12);
        $expected = array('mouse = ? AND mouse_height <= ?', 'gray', 12);
        \Enhance\Assert::areIdentical($expected, $this->fb->find($args));
        $args = array('house' => 'green', '$rxand:mouse' => array('house', 'garage'), '$gte:mouse_height' => 12);
        $expected = array('house = ? AND (mouse REGEXP ? AND mouse REGEXP ?) AND mouse_height >= ?', 'green', 'house', 'garage', 12);
        \Enhance\Assert::areIdentical($expected, $this->fb->find($args));
    }
}


if (!isset($bootstrapped))
{
    // Find the tests - '.' is the current folder
    //\Enhance\Core::discoverTests('.');
    // Run the tests
    \Enhance\Core::runTests();  
}