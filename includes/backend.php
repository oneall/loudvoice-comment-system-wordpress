<?php

// /////////////////////////////////////////////////////////////////////////////////////////////////
// ADMIN GUI
// /////////////////////////////////////////////////////////////////////////////////////////////////

/**
 * Adds a warning to be displayed when Loudvoice needs to be setup
 */
function oa_loudvoice_admin_message ()
{
	if (!oa_louddvoice_is_setup ())
	{
		echo '<div class="updated"><p><strong>' . __ ('Thank you for using the Loudvoice comments platform!', 'oa_loudvoice') . '</strong> ' . sprintf (__ ('Please <strong><a href="%s">complete the setup</a></strong> to enable the plugin.', 'oa_loudvoice'), 'admin.php?page=oa_loudvoice_settings') . '</p></div>';
	}
}

/**
 * Adds the administration area links
 */
function oa_loudvoice_admin_menu ()
{
	// Setup
	$page = add_menu_page ('OneAll Loudvoice Comments Platform ' . __ ('Setup', 'oa_loudvoice'), 'Loudvoice', 'manage_options', 'oa_loudvoice_settings', 'oa_loudvoice_display_settings');
	add_action ('admin_print_styles-' . $page, 'oa_loudvoice_admin_css');
	
	// Settings
	$page = add_submenu_page ('oa_loudvoice_settings', 'OneAll Loudvoice Comments Platform ' . __ ('Synchronize', 'oa_loudvoice'), __ ('Synchronize', 'oa_loudvoice'), 'manage_options', 'oa_loudvoice_synchronize', 'oa_loudvoice_display_synchronize');
	add_action ('admin_print_styles-' . $page, 'oa_loudvoice_admin_css');
	
	// Fix Setup title
	global $submenu;
	if (is_array ($submenu) and isset ($submenu ['oa_loudvoice_settings']))
	{
		$submenu ['oa_loudvoice_settings'] [0] [0] = __ ('Setup', 'oa_loudvoice');
	}
	
	add_action ('admin_notices', 'oa_loudvoice_admin_message');
	add_action ('admin_enqueue_scripts', 'oa_loudvoice_admin_js');
	add_action ('admin_init', 'oa_loudvoice_admin_register_settings');
}
add_action ('admin_menu', 'oa_loudvoice_admin_menu');

/**
 * Adds the administration area CSS, called by oa_loudvoice_admin_menu
 */
function oa_loudvoice_admin_css ($hook = '')
{
	if (!wp_style_is ('oa_loudvoice_admin_css', 'registered'))
	{
		wp_register_style ('oa_loudvoice_admin_css', OA_LOUDVOICE_PLUGIN_URL . "/assets/css/backend.css");
	}
	
	if (did_action ('wp_print_styles'))
	{
		wp_print_styles ('oa_loudvoice_admin_css');
	}
	else
	{
		wp_enqueue_style ('oa_loudvoice_admin_css');
	}
}

/**
 * Adds the administration area JS, called by oa_loudvoice_admin_menu
 */
function oa_loudvoice_admin_js ($hook)
{
	if (stripos ($hook, 'oa_loudvoice') !== false)
	{
		if (!wp_script_is ('oa_loudvoice_admin_js', 'registered'))
		{
			wp_register_script ('oa_loudvoice_admin_js', OA_LOUDVOICE_PLUGIN_URL . "/assets/js/backend.js");
		}
		
		$oa_loudvoice_ajax_nonce = wp_create_nonce ('oa_loudvoice_ajax_nonce');
		
		wp_enqueue_script ('oa_loudvoice_admin_js');
		wp_enqueue_script ('jquery');
		
		wp_localize_script ('oa_loudvoice_admin_js', 'objectL10n', array(
			'oa_loudvoice_ajax_nonce' => $oa_loudvoice_ajax_nonce,
			'oa_admin_js_1' => __ ('Contacting API - please wait this may take a few minutes ...', 'oa_loudvoice'),
			'oa_admin_js_2' => __ ('Working - please wait this may take a few minutes ...', 'oa_loudvoice'),
			'oa_admin_js_101' => __ ('The settings are correct - do not forget to save your changes!', 'oa_loudvoice'),
			'oa_admin_js_111' => __ ('Please fill out each of the fields above.', 'oa_loudvoice'),
			'oa_admin_js_112' => __ ('The subdomain does not exist. Have you filled it out correctly?', 'oa_loudvoice'),
			'oa_admin_js_113' => __ ('The subdomain has a wrong syntax!', 'oa_loudvoice'),
			'oa_admin_js_114' => __ ('Could not contact API. Are outbound requests on port 443 allowed?', 'oa_loudvoice'),
			'oa_admin_js_115' => __ ('The API subdomain is correct, but one or both keys are invalid', 'oa_loudvoice'),
			'oa_admin_js_116' => __ ('Connection handler does not work, try using the Autodetection', 'oa_loudvoice'),
			'oa_admin_js_201a' => __ ('Detected CURL on Port 443 - do not forget to save your changes!', 'oa_loudvoice'),
			'oa_admin_js_201b' => __ ('Detected CURL on Port 80 - do not forget to save your changes!', 'oa_loudvoice'),
			'oa_admin_js_201c' => __ ('CURL is available but both ports (80, 443) are blocked for outbound requests', 'oa_loudvoice'),
			'oa_admin_js_202a' => __ ('Detected FSOCKOPEN on Port 443 - do not forget to save your changes!', 'oa_loudvoice'),
			'oa_admin_js_202b' => __ ('Detected FSOCKOPEN on Port 80 - do not forget to save your changes!', 'oa_loudvoice'),
			'oa_admin_js_202c' => __ ('FSOCKOPEN is available but both ports (80, 443) are blocked for outbound requests', 'oa_loudvoice'),
			'oa_admin_js_211' => sprintf (__ ('Autodetection Error - our <a href="%s" target="_blank">documentation</a> helps you fix this issue.', 'oa_loudvoice'), 'http://docs.oneall.com/plugins/guide/social-login-wordpress/#help') 
		));
	}
}


/**
 * Autodetects the API Connection Handler
 */
function oa_loudvoice_admin_autodetect_api_connection_handler ()
{
	// Check AJAX Nonce
	check_ajax_referer ('oa_loudvoice_ajax_nonce');
	
	// Check if CURL is available
	if (oa_loudvoice_check_curl_available ())
	{
		// Check CURL HTTPS - Port 443
		if (oa_loudvoice_check_curl (true) === true)
		{
			echo 'success_autodetect_api_curl_https';
			die ();
		}
		// Check CURL HTTP - Port 80
		elseif (oa_loudvoice_check_curl (false) === true)
		{
			echo 'success_autodetect_api_curl_http';
			die ();
		}
		else
		{
			echo 'error_autodetect_api_curl_ports_blocked';
			die ();
		}
	}
	// Check if FSOCKOPEN is available
	elseif (oa_loudvoice_check_fsockopen_available ())
	{
		// Check FSOCKOPEN HTTPS - Port 443
		if (oa_loudvoice_check_fsockopen (true) == true)
		{
			echo 'success_autodetect_api_fsockopen_https';
			die ();
		}
		// Check FSOCKOPEN HTTP - Port 80
		elseif (oa_loudvoice_check_fsockopen (false) == true)
		{
			echo 'success_autodetect_api_fsockopen_http';
			die ();
		}
		else
		{
			echo 'error_autodetect_api_fsockopen_ports_blocked';
			die ();
		}
	}
	
	// No working handler found
	echo 'error_autodetect_api_no_handler';
	die ();
}
add_action ('wp_ajax_autodetect_api_connection_handler', 'oa_loudvoice_admin_autodetect_api_connection_handler');

/**
 * Check API Settings through an Ajax Call
 */
function oa_loudvoice_admin_check_api_settings ()
{
	check_ajax_referer ('oa_loudvoice_ajax_nonce');
	
	// Check if all fields have been filled out
	if (empty ($_POST ['api_subdomain']) or empty ($_POST ['api_key']) or empty ($_POST ['api_secret']))
	{
		echo 'error_not_all_fields_filled_out';
		delete_option ('oa_loudvoice_api_settings_verified');
		die ();
	}
	
	// Check the handler
	$api_connection_handler = ((!empty ($_POST ['api_connection_handler']) and $_POST ['api_connection_handler'] == 'fsockopen') ? 'fsockopen' : 'curl');
	$api_connection_use_https = ((!isset ($_POST ['api_connection_use_https']) or $_POST ['api_connection_use_https'] == '1') ? true : false);
	
	// FSOCKOPEN
	if ($api_connection_handler == 'fsockopen')
	{
		if (!oa_loudvoice_check_fsockopen ($api_connection_use_https))
		{
			echo 'error_selected_handler_faulty';
			delete_option ('oa_loudvoice_api_settings_verified');
			die ();
		}
	}
	// CURL
	else
	{
		if (!oa_loudvoice_check_curl ($api_connection_use_https))
		{
			echo 'error_selected_handler_faulty';
			delete_option ('oa_loudvoice_api_settings_verified');
			die ();
		}
	}
	
	$api_subdomain = trim (strtolower ($_POST ['api_subdomain']));
	$api_key = trim ($_POST ['api_key']);
	$api_secret = trim ($_POST ['api_secret']);
	
	// Full domain entered
	if (preg_match ("/([a-z0-9\-]+)\.api\.oneall\.com/i", $api_subdomain, $matches))
	{
		$api_subdomain = $matches [1];
	}
	
	// Check subdomain format
	if (!preg_match ("/^[a-z0-9\-]+$/i", $api_subdomain))
	{
		echo 'error_subdomain_wrong_syntax';
		delete_option ('oa_loudvoice_api_settings_verified');
		die ();
	}
	
	// Domain
	$api_domain = $api_subdomain . '.api.oneall.com';
	
	// Connection to
	$api_resource_url = ($api_connection_use_https ? 'https' : 'http') . '://' . $api_domain . '/tools/ping.json';
	
	// Get connection details
	$result = oa_loudvoice_do_api_request ($api_connection_handler, $api_resource_url, array(
		'api_key' => $api_key,
		'api_secret' => $api_secret 
	), 15);
	
	// Parse result
	if (is_object ($result) and property_exists ($result, 'http_code') and property_exists ($result, 'http_data'))
	{
		switch ($result->http_code)
		{
			// Success
			case 200 :
				echo 'success';
				update_option ('oa_loudvoice_api_settings_verified', '1');
			break;
			
			// Authentication Error
			case 401 :
				echo 'error_authentication_credentials_wrong';
				delete_option ('oa_loudvoice_api_settings_verified');
			break;
			
			// Wrong Subdomain
			case 404 :
				echo 'error_subdomain_wrong';
				delete_option ('oa_loudvoice_api_settings_verified');
			break;
			
			// Other error
			default :
				echo 'error_communication';
				delete_option ('oa_loudvoice_api_settings_verified');
			break;
		}
	}
	else
	{
		echo 'error_communication';
		delete_option ('oa_loudvoice_api_settings_verified');
	}
	die ();
}
add_action ('wp_ajax_check_api_settings', 'oa_loudvoice_admin_check_api_settings');

/**
 * Register plugin settings and their sanitization callback
 */
function oa_loudvoice_admin_register_settings ()
{
	register_setting ('oa_loudvoice_settings_group', 'oa_loudvoice_settings', 'oa_loudvoice_settings_validate');
}

/**
 * Plugin settings sanitization callback
 */
function oa_loudvoice_settings_validate ($settings)
{
	// Import providers
	GLOBAL $oa_loudvoice_providers;
	
	// Settings page?
	$page = (!empty ($_POST ['page']) ? strtolower (trim($_POST ['page'])) : '');
	
	// Store the sanitzed settings
	$sanitzed_settings = get_option ('oa_loudvoice_settings');
	
	// Check format
	if (!is_array ($sanitzed_settings))
	{
		$sanitzed_settings = array();
	}
	
	// //////////////////////////////////////////////////////////////////////////////////////////////////////////
	// Settings
	// //////////////////////////////////////////////////////////////////////////////////////////////////////////
	if ($page == 'settings')
	{
		// Setup fields
		$fields = array();
		$fields [] = 'api_connection_handler';
		$fields [] = 'api_connection_use_https';
		$fields [] = 'api_subdomain';
		$fields [] = 'api_key';
		$fields [] = 'api_secret';
		$fields [] = 'disable_auto_comment_import';
		$fields [] = 'disable_author_sessions';
		$fields [] = 'disable_seo_comments';
		$fields [] = 'providers';
		
		// Resest providers
		$sanitzed_settings ['providers'] = array();
		
		// Extract fields
		foreach ($fields as $key => $field)
		{
			// Value is given
			if (isset ($settings [$field]))
			{
				// Provider tickboxes
				if ($field == 'providers')
				{
					// Loop through new values
					if (is_array ($settings ['providers']))
					{
						// Loop through valid values
						foreach ($oa_loudvoice_providers as $field => $name)
						{
							if (!empty ($settings ['providers'] [$field]))
							{
								$sanitzed_settings ['providers'] [$field] = 1;
							}
						}
					}
				}
				// Other field
				else
				{
					$sanitzed_settings [$field] = trim ($settings [$field]);
				}
			}
		}
		
		// Sanitize Disable Author Sessions
		$sanitzed_settings ['disable_author_sessions'] = ( ! empty ($sanitzed_settings ['disable_author_sessions']) ? 1 : 0);
		
		// Sanitize Auto Comment Import
		$sanitzed_settings ['disable_auto_comment_import'] = ( ! empty ($sanitzed_settings ['disable_auto_comment_import']) ? 1 : 0);
		
		// Sanitize Disable SEO Comments
		$sanitzed_settings ['disable_seo_comments'] = ( ! empty ($sanitzed_settings ['disable_seo_comments']) ? 1 : 0);		
		
		// Sanitize API Use HTTPS
		$sanitzed_settings ['api_connection_use_https'] = (empty ($sanitzed_settings ['api_connection_use_https']) ? 0 : 1);
		
		// Sanitize API Connection handler
		if (isset ($sanitzed_settings ['api_connection_handler']) and in_array (strtolower ($sanitzed_settings ['api_connection_handler']), array('curl', 'fsockopen')))
		{
			$sanitzed_settings ['api_connection_handler'] = strtolower ($sanitzed_settings ['api_connection_handler']);
		}
		else
		{
			$sanitzed_settings ['api_connection_handler'] = 'curl';
		}
		
		// Sanitize API Subdomain
		if (isset ($sanitzed_settings ['api_subdomain']))
		{
			// Subdomain is always in lowercase
			$api_subdomain = strtolower ($sanitzed_settings ['api_subdomain']);
			
			// Full domain entered
			if (preg_match ("/([a-z0-9\-]+)\.api\.oneall\.com/i", $api_subdomain, $matches))
			{
				$api_subdomain = $matches [1];
			}
			
			$sanitzed_settings ['api_subdomain'] = $api_subdomain;
		}
		
		// Done
		return $sanitzed_settings;
	}
	
	// Error
	return array();
}


/**
 * Display Settings Page
 */
function oa_loudvoice_display_synchronize ()
{
	?>
		<div class="wrap">
			<div id="oa_loudvoice">
				<h2>OneAll Loudvoice <?php echo (defined ('OA_LOUDVOICE_VERSION') ? OA_LOUDVOICE_VERSION : ''); ?></h2>
				<h2 class="nav-tab-wrapper">
					<a class="nav-tab" href="admin.php?page=oa_loudvoice_settings"><?php _e ('Setup', 'oa_loudvoice'); ?></a>
					<a class="nav-tab nav-tab-active" href="admin.php?page=oa_loudvoice_synchronize"><?php _e ('Synchronize', 'oa_loudvoice'); ?></a>
				</h2>										
				<?php 					
					if (!oa_louddvoice_is_setup ())
					{
						?>
							<div class="oa_loudvoice_box" id="oa_loudvoice_box_status">
								<div class="oa_loudvoice_box_title">
									<?php _e ('Setup Required', 'oa_loudvoice'); ?>
								</div>
								<p>
									<?php _e ('The synchronization requires an API connection. Please <a href="admin.php?page=oa_loudvoice_settings">verify your API settings</a> and make sure that they are correct.', 'oa_loudvoice'); ?>
								</p>
							</div>
						<?php 
					}
					else
					{
						?>	
							<div class="oa_loudvoice_box" id="oa_loudvoice_box_export">
								<div class="oa_loudvoice_box_title">
									<?php _e ('Export Comments from WordPress to Loudvoice', 'oa_loudvoice'); ?>
								</div>
								<div class="oa_loudvoice_box_content">									
									<p>
										<?php _e ('You should export your comments immediately after having installed Loudvoice so that your users do not need to start their discussions from scratch. Please be patient as the export might take a couple of minutes.', 'oa_loudvoice'); ?>			
									</p>
									<p>
										<a class="button-secondary oa_loudvoice_sync" id="oa_loudvoice_export" href="#" ><strong><?php _e ('Export Comments', 'oa_loudvoice'); ?></strong></a>
									</p>							
								</div>
								<div class="oa_loudvoice_box_footer">
									<div id="oa_loudvoice_export_result"></div>
									<textarea id="oa_loudvoice_export_verbose"></textarea>
								</div>
							</div>
							
							<div class="oa_loudvoice_box" id="oa_loudvoice_box_import">
								<div class="oa_loudvoice_box_title">
									<?php _e ('Import Comments from Loudvoice to WordPress', 'oa_loudvoice'); ?>
								</div>
								<div class="oa_loudvoice_box_content">								
									<p>
										<?php _e ('New comments made in Loudvoice are automatically stored in your WordPress database. If you plan to remove Loudvoice, then you can also launch the import manually to make sure that your comments are in sync.', 'oa_loudvoice'); ?>			
									</p>			
									<p>
										<a class="button-secondary oa_loudvoice_sync" id="oa_loudvoice_import" href="#" ><strong><?php _e ('Import Comments', 'oa_loudvoice'); ?></strong></a>
									</p>											
								</div>	
								<div class="oa_loudvoice_box_footer"><div id="oa_loudvoice_import_result"></div></div>							
							</div>
						<?php 
					}
				?>
			</div>
		</div>
	<?php 
}

/**
 * Display Settings Page
 */
function oa_loudvoice_display_settings ()
{
	// Import providers
	GLOBAL $oa_loudvoice_providers;
	
	?>
		<div class="wrap">
			<div id="oa_loudvoice">
				<h2>OneAll Loudvoice <?php echo (defined ('OA_LOUDVOICE_VERSION') ? OA_LOUDVOICE_VERSION : ''); ?></h2>
				<h2 class="nav-tab-wrapper">
					<a class="nav-tab nav-tab-active" href="admin.php?page=oa_loudvoice_settings"><?php _e ('Setup', 'oa_loudvoice'); ?></a>
					<a class="nav-tab" href="admin.php?page=oa_loudvoice_synchronize"><?php _e ('Synchronize', 'oa_loudvoice'); ?></a>
				</h2>
				<?php
					if (get_option ('oa_loudvoice_api_settings_verified') !== '1')
					{
						?>
							<p>
								<?php _e ('Our comments platform has been designed with ease of use in mind and allows your audience to have quick, focused and well thought out interactions surrounding your content. Let your users simply post as guests or connect with 25+ social networks like for example Twitter, Facebook, LinkedIn or Instagram.', 'oa_loudvoice'); ?>
								<strong><?php _e ('Loudvoice gives your users the voice that they deserve!', 'oa_loudvoice'); ?> </strong>
							</p>
							<div class="oa_loudvoice_box" id="oa_loudvoice_box_status">
								<div class="oa_loudvoice_box_title">
									<?php _e ('Get started within minutes', 'oa_loudvoice'); ?>
								</div>
								<p>
									<?php printf (__ ('To be able to use this plugin you first of all need to create a free account at %s and setup a Site.', 'oa_loudvoice'), '<a href="https://app.oneall.com/signup/" target="_blank">http://www.oneall.com</a>'); ?>
									<?php _e ('Please enter the API settings below after having created your OneAll Site.', 'oa_loudvoice'); ?>
									<?php _e ("Don't worry it takes only a couple of minutes!", 'oa_loudvoice'); ?>
								</p>
								<p>
									<a class="button-secondary" href="https://app.oneall.com/signup/" target="_blank"><strong><?php _e ('Setup my free account', 'oa_loudvoice'); ?></strong></a>
								</p>
							</div>
						<?php
					}
					else
					{
						?>						
							<div class="oa_loudvoice_box" id="oa_loudvoice_box_status">
								<div class="oa_loudvoice_box_title">
									<?php _e ('Loudvoice is setup correctly', 'oa_loudvoice'); ?>
								</div>
								<div class="oa_loudvoice_box_content">				
									<p>
										<?php _e ('Login to your account to access the comments management system. Loudvoice automatically detects spam so you will not have much to do!', 'oa_loudvoice'); ?>
									</p>
									<p>
										<a class="button-secondary" href="https://app.oneall.com/signin/" target="_blank"><strong><?php _e ('Click here to login to your account', 'oa_loudvoice'); ?></strong></a>
									</p>
								</div>
							</div>
						<?php
					}
	
					if (!empty ($_REQUEST ['settings-updated']) and strtolower ($_REQUEST ['settings-updated']) == 'true')
					{
						?>
							<div class="oa_loudvoice_box" id="oa_loudvoice_box_updated">	
								<div class="oa_loudvoice_box_content">	
									<?php _e ('Your modifications have successfully been saved successfully!'); ?>
								</div>
							</div>
						<?php
					}
				?>
				<form method="post" action="options.php">
					<?php
						settings_fields ('oa_loudvoice_settings_group');
						$settings = get_option ('oa_loudvoice_settings');
					?>

					<table class="form-table oa_loudvoice_table">
						<tr class="row_head">
							<th colspan="2">
								<?php _e ('API Connection', 'oa_loudvoice'); ?>
							</th>
						</tr>
						<?php
							$api_connection_handler = ((empty ($settings ['api_connection_handler']) or $settings ['api_connection_handler'] != 'fsockopen') ? 'curl' : 'fsockopen');
						?>
						<tr class="row_even">
							<td rowspan="2" class="row_multi" style="width: 250px">
								<label><?php _e ('API Connection Handler', 'oa_loudvoice'); ?>:</label>
							</td>
							<td>
								<input type="radio" id="oa_loudvoice_api_connection_handler_curl" name="oa_loudvoice_settings[api_connection_handler]" value="curl" <?php echo (($api_connection_handler <> 'fsockopen') ? 'checked="checked"' : ''); ?> />
								<label for="oa_loudvoice_api_connection_handler_curl"><?php _e ('Use PHP CURL to communicate with the API', 'oa_loudvoice'); ?> <strong>(<?php _e ('Recommended', 'oa_loudvoice') ?>)</strong></label><br />
								<span class="description"><?php _e ('Using CURL is recommended but it might be disabled on some servers.', 'oa_loudvoice'); ?></span>
							</td>
						</tr>
						<tr class="row_even">
							<td>
								<input type="radio" id="oa_loudvoice_api_connection_handler_fsockopen" name="oa_loudvoice_settings[api_connection_handler]" value="fsockopen" <?php echo (($api_connection_handler == 'fsockopen') ? 'checked="checked"' : ''); ?> />
								<label for="oa_loudvoice_api_connection_handler_fsockopen"><?php _e ('Use PHP FSOCKOPEN to communicate with the API', 'oa_loudvoice'); ?> </label><br />
								<span class="description"><?php _e ('Try using FSOCKOPEN if you encounter any problems with CURL.', 'oa_loudvoice'); ?></span>
							</td>
						</tr>
						<?php
							$api_connection_use_https = ((!isset ($settings ['api_connection_use_https']) or $settings ['api_connection_use_https'] == '1') ? true : false);
						?>
						<tr class="row_even">
							<td rowspan="2" class="row_multi" style="width: 250px">
								<label><?php _e ('API Connection Port', 'oa_loudvoice'); ?>:</label>
							</td>
							<td>
								<input type="radio" id="oa_loudvoice_api_connection_handler_use_https_1" name="oa_loudvoice_settings[api_connection_use_https]" value="1" <?php echo ($api_connection_use_https ? 'checked="checked"' : ''); ?> />
								<label for="oa_loudvoice_api_connection_handler_use_https_1"><?php _e ('Communication via HTTPS on port 443', 'oa_loudvoice'); ?> <strong>(<?php _e ('Recommended', 'oa_loudvoice') ?>)</strong></label><br />
								<span class="description"><?php _e ('Using port 443 is secure but you might need OpenSSL', 'oa_loudvoice'); ?></span>
							</td>
						</tr>
						<tr class="row_even">
							<td>
								<input type="radio" id="oa_loudvoice_api_connection_handler_use_https_0" name="oa_loudvoice_settings[api_connection_use_https]" value="0" <?php echo (!$api_connection_use_https ? 'checked="checked"' : ''); ?> />
								<label for="oa_loudvoice_api_connection_handler_use_https_0"><?php _e ('Communication via HTTP on port 80', 'oa_loudvoice'); ?> </label><br />
								<span class="description"><?php _e ("Using port 80 is a bit faster, doesn't need OpenSSL but is less secure", 'oa_loudvoice'); ?></span>
							</td>
						</tr>
						<tr class="row_foot">
							<td>
								<a class="button-primary" id="oa_loudvoice_autodetect_api_connection_handler" href="#"><?php _e ('Autodetect API Connection', 'oa_loudvoice'); ?></a>
							</td>
							<td>
								<div id="oa_loudvoice_api_connection_handler_result"></div>
							</td>
						</tr>
					</table>
					
					<table class="form-table oa_loudvoice_table">
						<tr class="row_head">
							<th>
								<?php _e ('API Credentials', 'oa_loudvoice'); ?>
							</th>
							<th>
								<a href="https://app.oneall.com/applications/" target="_blank"><?php _e ('Click here to create and view your API Credentials', 'oa_loudvoice'); ?></a>
							</th>
						</tr>
						<tr class="row_even">
							<td style="width: 250px">
								<label for="oa_loudvoice_settings_api_subdomain"><?php _e ('API Subdomain', 'oa_loudvoice'); ?>:</label>
							</td>
							<td>
								<input type="text" id="oa_loudvoice_settings_api_subdomain" name="oa_loudvoice_settings[api_subdomain]" size="65" value="<?php echo (isset ($settings ['api_subdomain']) ? htmlspecialchars ($settings ['api_subdomain']) : ''); ?>" />
							</td>
						</tr>
						<tr class="row_odd">
							<td style="width: 250px"><label for="oa_loudvoice_settings_api_key">
								<?php _e ('API Public Key', 'oa_loudvoice'); ?>:</label>
							</td>
							<td>
								<input type="text" id="oa_loudvoice_settings_api_key" name="oa_loudvoice_settings[api_key]" size="65" value="<?php echo (isset ($settings ['api_key']) ? htmlspecialchars ($settings ['api_key']) : ''); ?>" />
							</td>
						</tr>
						<tr class="row_even">
							<td style="width: 200px">
								<label	for="oa_loudvoice_settings_api_secret"><?php _e ('API Private Key', 'oa_loudvoice'); ?>:</label>
							</td>
							<td>
								<input type="text" id="oa_loudvoice_settings_api_secret" name="oa_loudvoice_settings[api_secret]" size="65"	value="<?php echo (isset ($settings ['api_secret']) ? htmlspecialchars ($settings ['api_secret']) : ''); ?>" />
							</td>
						</tr>
						<tr class="row_foot">
							<td>
								<a class="button-primary" id="oa_loudvoice_test_api_settings" href="#"><?php _e ('Verify API Settings', 'oa_loudvoice'); ?> </a>
							</td>
							<td>
								<div id="oa_loudvoice_api_test_result"></div>
							</td>
						</tr>
					</table>

					<table class="form-table oa_loudvoice_table">
						<tr class="row_head">
							<th colspan="2">
								<?php _e ('Settings', 'oa_loudvoice'); ?>
							</th>
						</tr>
						<?php
							$disable_seo_comments = (!empty ($settings ['disable_seo_comments']) ? 1 : 0);
						?>
						<tr class="row_even">
							<td class="row_multi" rowspan="2" style="width: 250px">
								<label>
									<?php _e ('Use SEO friendly comments?', 'oa_loudvoice'); ?>
								</label>
							</td>
							<td>
								<input type="radio"	id="oa_loudvoice_disable_seo_comments_0" name="oa_loudvoice_settings[disable_seo_comments]" value="0" <?php echo ($disable_seo_comments == 0 ? 'checked="checked"' : ''); ?> />
								<label for="oa_loudvoice_disable_seo_comments_0"><?php _e ('Enable search engine friendly comments', 'oa_loudvoice'); ?> <strong>(<?php _e ('Recommended', 'oa_loudvoice') ?>)</strong></label><br />
								<span class="description"><?php _e ('Displays an optimized version of the comments to search engines and browsers without JavaScript.', 'oa_loudvoice'); ?></span>
							</td>
						</tr>
						<tr class="row_even">
							<td class="row_multi">					
								<input type="radio"	id="oa_loudvoice_disable_seo_comments_1" name="oa_loudvoice_settings[disable_seo_comments]" value="1" <?php echo ($disable_seo_comments <> 0 ? 'checked="checked"' : ''); ?> />
								<label for="oa_loudvoice_disable_seo_comments_1"><?php _e ('Disable search engine friendly comments', 'oa_loudvoice'); ?></strong></label><br />
								<span class="description"><?php _e ('Hides the comments from search engines and browsers without JavaScript.', 'oa_loudvoice'); ?></span>
							</td>
						</tr>		
				
						<?php
							$disable_auto_comment_import = (!empty ($settings ['disable_auto_comment_import']) ? 1 : 0);
						?>
						<tr class="row_odd">
							<td class="row_multi" rowspan="2" style="width: 250px">
								<label>
									<?php _e ('Auto-Import New Comments?', 'oa_loudvoice'); ?>
								</label>
							</td>
							<td>
								<input type="radio"	id="oa_loudvoice_disable_auto_comment_import_0" name="oa_loudvoice_settings[disable_auto_comment_import]" value="0" <?php echo ($disable_auto_comment_import == 0 ? 'checked="checked"' : ''); ?> />
								<label for="oa_loudvoice_disable_auto_comment_import_0"><?php _e ('Yes, automatically import new comments', 'oa_loudvoice'); ?> <strong>(<?php _e ('Recommended', 'oa_loudvoice') ?>)</strong></label><br />
								<span class="description"><?php _e ('Automatically stores new comments that are made in Loudvoice also in your WordPress database.', 'oa_loudvoice'); ?></span>
							</td>
						</tr>
						<tr class="row_odd">
							<td class="row_multi"> 
								<input type="radio"	id="oa_loudvoice_disable_auto_comment_import_1" name="oa_loudvoice_settings[disable_auto_comment_import]" value="1" <?php echo ($disable_auto_comment_import <> 0 ? 'checked="checked"' : ''); ?> />
								<label for="oa_loudvoice_disable_auto_comment_import_1"><?php _e ('No, I will import the comment on my own', 'oa_loudvoice'); ?></strong></label><br />
								<span class="description"><?php _e ('You manually run Synchronize\Import to store Loudvoice comments in your database.', 'oa_loudvoice'); ?></span>						
							</td>
						</tr>		
						
						<?php
							$disable_author_sessions = (!empty ($settings ['disable_author_sessions']) ? 1 : 0);
						?>
						<tr class="row_even">
							<td class="row_multi" rowspan="2" style="width: 250px">
								<label>
									<?php _e ('Connect WordPress users to the Loudvoice comments platform?', 'oa_loudvoice'); ?>
								</label>
							</td>
							<td>
								<input type="radio"	id="oa_loudvoice_disable_author_sessions_0" name="oa_loudvoice_settings[disable_author_sessions]" value="0" <?php echo ($disable_author_sessions == 0 ? 'checked="checked"' : ''); ?> />
								<label for="oa_loudvoice_disable_author_sessions_0"><?php _e ('Yes, connect WordPress users accounts to Loudvoice', 'oa_loudvoice'); ?> <strong>(<?php _e ('Recommended', 'oa_loudvoice') ?>)</strong></label><br />
								<span class="description"><?php _e ('Existing users of your blog will use their WordPress accounts to post comments with Loudvoice.', 'oa_loudvoice'); ?></span>
							</td>
						</tr>
						<tr class="row_even">
							<td class="row_multi"> 
								<input type="radio"	id="disable_author_sessions_1" name="oa_loudvoice_settings[disable_author_sessions]" value="1" <?php echo ($disable_author_sessions <> 0 ? 'checked="checked"' : ''); ?> />
								<label for="disable_author_sessions_1"><?php _e ('No, do not connect WordPress users to Loudvoice', 'oa_loudvoice'); ?></strong></label><br />
								<span class="description"><?php _e ('Existing users can only post as guests or with their social network accounts.', 'oa_loudvoice'); ?></span>						
							</td>
						</tr>
					</table>			
			
					<table class="form-table oa_loudvoice_table">
						<tr class="row_head">
							<th colspan="2">
								<?php _e ('Enable the social networks of your choice to allow users to comment using their social media accounts.', 'oa_loudvoice'); ?>
							</th>
						</tr>
						<?php
	
							// Sort
							asort ($oa_loudvoice_providers);
	
							// Display
							$i = 0;
							foreach ($oa_loudvoice_providers as $key => $name)
							{
								?>
									<tr	class="row_provider <?php echo ((($i++) % 2) == 0) ? 'row_even' : 'row_odd' ?>">
										<td class="cell_provider_icon">
											<label for="oneall_loudvoice_provider_<?php echo $key; ?>">
												<span class="oa_loudvoice_provider oa_loudvoice_provider_<?php echo $key; ?>" title="<?php echo htmlspecialchars ($name); ?>"><?php echo htmlspecialchars ($name); ?> </span>
											</label>
										</td>
										<td class="cell_provider_label">
											<input type="checkbox" id="oneall_loudvoice_provider_<?php echo $key; ?>" name="oa_loudvoice_settings[providers][<?php echo $key; ?>]" value="1" <?php checked ('1', ((isset ($settings ['providers']) && !empty ($settings ['providers'] [$key])) ? $settings ['providers'] [$key] : 0)); ?> />
											<label for="oneall_loudvoice_provider_<?php echo $key; ?>"><?php echo htmlspecialchars ($name); ?> </label>
										</td>
									</tr>
								<?php
							}
						?>	
					</table>
					<p class="submit">
						<input type="hidden" name="page" value="settings" />
						<input type="submit" class="button-primary" value="<?php _e ('Save Changes', 'oa_loudvoice') ?>" />
					</p>
				</form>
			</div>
		</div>
	<?php
}
