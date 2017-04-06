<?php
/*
Plugin Name: LoudVoice Comment System
Plugin URI: http://www.oneall.com/
Description: LoudVoice replaces the basic WordPress comments by a <strong>powerful comment system</strong> that includes logging in with 30+ social networks, spam filters and automatic backups.
Version: 2.0
Author: OneAll <support@oneall.com>
Author URI: http://www.oneall.com/
License: GPL2
*/
define ('OA_LOUDVOICE_PLUGIN_URL', plugins_url () . '/' . basename (dirname (__FILE__)));
define ('OA_LOUDVOICE_BASE_PATH', dirname (plugin_basename (__FILE__)));


/**
 * Check technical requirements before activating the plugin (Wordpress 3.0 or newer required)
 */
function oa_loudvoice_activate ()
{
	// Generate UniqID
	oa_loudvoice_uniqid ();

	if (!function_exists ('register_post_status'))
	{
		deactivate_plugins (basename (dirname (__FILE__)) . '/' . basename (__FILE__));
		echo sprintf (__ ('This plugin requires WordPress %s or newer. Please update your WordPress installation to activate this plugin.', 'oa_loudvoice'), '3.0');
		exit ();
	}
}
register_activation_hook (__FILE__, 'oa_loudvoice_activate');

/**
 * Add Setup Link
 */
function oa_loudvoice_add_setup_link ($links, $file)
{
	static $oa_loudvoice_plugin = null;

	if (is_null ($oa_loudvoice_plugin))
	{
		$oa_loudvoice_plugin = plugin_basename (__FILE__);
	}

	if ($file == $oa_loudvoice_plugin)
	{
		$settings_link = '<a href="admin.php?page=oa_loudvoice_settings">' . __ ('Setup', 'oa_loudvoice') . '</a>';
		array_unshift ($links, $settings_link);
	}
	return $links;
}
add_filter ('plugin_action_links', 'oa_loudvoice_add_setup_link', 10, 2);

/**
 * Include required files
 */
require_once (dirname (__FILE__) . '/includes/settings.php');
require_once (dirname (__FILE__) . '/includes/toolbox.php');
require_once (dirname (__FILE__) . '/includes/oversee.php');
require_once (dirname (__FILE__) . '/includes/synchronize.php');
require_once (dirname (__FILE__) . '/includes/frontend.php');
require_once (dirname (__FILE__) . '/includes/backend.php');

/**
 * Add language file
 */
if (function_exists ('load_plugin_textdomain'))
{
	load_plugin_textdomain ('oa_loudvoice', false, OA_LOUDVOICE_BASE_PATH . '/languages/');
}
