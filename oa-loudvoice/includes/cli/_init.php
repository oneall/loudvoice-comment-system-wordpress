<?php

// Can only be ran from the command line
if (php_sapi_name () !== 'cli')
{
	die ("Meant to be run from command line");
}

// Get the WordPress Root Directory
function get_wordpress_root_dir ()
{
	// Current folder
	$dir = dirname (__FILE__);
	
	// Descden to root
	do
	{
		if (file_exists ($dir . '/wp-config.php'))
		{
			return $dir;
		}
	}
	while ( $dir = realpath ("$dir/..") );
	
	// Not found
	return null;
}

// Required constants
// define ('BASE_PATH', get_wordpress_root_dir () . "/");

define ('BASE_PATH', '/work/tests/wordpress003_loc/');
define ('WP_USE_THEMES', false);

// Required files
global $wp, $wp_query, $wp_the_query, $wp_rewrite, $wp_did_header;
require (BASE_PATH . 'wp-load.php');

// Louvdvoice
require_once (dirname (__FILE__) . '/../settings.php');
require_once (dirname (__FILE__) . '/../toolbox.php');

// Commande Line
$verbose = true;