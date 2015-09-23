<?php
/*
Plugin Name: Loudvoice Comments Platform
Plugin URI: http://www.oneall.com/
Description: Loudvoice replaces your WordPress comments by a <strong>powerful comments platform</strong> that includes logging in with 30+ social networks, spam filters and automatic backups.
Version: 5.0
Author: OneAll
Author URI: http://www.oneall.com/
License: GPL2
 */

define ('OA_LOUDVOICE_PLUGIN_URL', plugins_url () . '/' . basename (dirname (__FILE__)));
define ('OA_LOUDVOICE_BASE_PATH', dirname (plugin_basename (__FILE__)));
define ('OA_LOUDVOICE_VERSION', '1.0');

/**
 * Check technical requirements before activating the plugin (Wordpress 3.0 or newer required)
 */
function oa_loudvoice_activate ()
{
	if (!function_exists ('register_post_status'))
	{
		deactivate_plugins (basename (dirname (__FILE__)) . '/' . basename (__FILE__));
		echo sprintf (__ ('This plugin requires WordPress %s or newer. Please update your WordPress installation to activate this plugin.', 'oa_loudvoice'), '3.0');
		exit;
	}
	update_option ('oa_loudvoice_activation_message', 0);
}
register_activation_hook (__FILE__, 'oa_loudvoice_activate');


/**
 * Add Setup Link
 **/
function oa_loudvoice_add_setup_link ($links, $file)
{
	static $oa_loudvoice_plugin = null;

	if (is_null ($oa_loudvoice_plugin))
	{
		$oa_loudvoice_plugin = plugin_basename (__FILE__);
	}

	if ($file == $oa_loudvoice_plugin)
	{
		$settings_link = '<a href="admin.php?page=oa_loudvoice_setup">' . __ ('Setup', 'oa_loudvoice') . '</a>';
		array_unshift ($links, $settings_link);
	}
	return $links;
}
add_filter ('plugin_action_links', 'oa_loudvoice_add_setup_link', 10, 2);


/**
 * This file only has to be included for versions before 3.1.
 * Deprecated since version 3.1, the functions are included by default
 */
if (!function_exists ('email_exists'))
{
	require_once(ABSPATH . WPINC . '/registration.php');
}


/**
 * Include required files
 */
require_once(dirname (__FILE__) . '/includes/settings.php');
require_once(dirname (__FILE__) . '/includes/general.php');
//require_once(dirname (__FILE__) . '/includes/toolbox.php');
require_once(dirname (__FILE__) . '/includes/admin.php');
//require_once(dirname (__FILE__) . '/includes/user_interface.php');
//require_once(dirname (__FILE__) . '/includes/widget.php');


/**
 * Initialise
 */
add_action ('init', 'oa_loudvoice_init', 9);
