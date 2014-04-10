<?php
/*
Plugin Name: Advanced Modernizr
Description: Configurable Modernizr with lazy loading
Version: 0.1
Author: <a href="http://dougcone.com">Doug Cone</a>, <a href="http://drumcreative.com">Drum Creative</a>
*/
define("AM_SLUG", "advanced_modernizr");
define("AM_ROOT", plugin_dir_path(__FILE__));
define("AM_ROOT_URI", plugin_dir_url(__FILE__));
define("AM_SCRIPT_VERSION", "2.7.1");
require(AM_ROOT.'classes/advanced_modernizr.class.php');
$advanced_modernizr = new advanced_modernizr();