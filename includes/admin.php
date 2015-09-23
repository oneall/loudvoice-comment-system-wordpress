<?php

// /////////////////////////////////////////////////////////////////////////////////////////////////
// ADMIN GUI
// /////////////////////////////////////////////////////////////////////////////////////////////////

/**
 * Add administration area links
 */
function oa_loudvoice_admin_menu ()
{
	// Setup
	$page = add_menu_page ('OneAll Loudvoice Comments Platform ' . __ ('Setup', 'oa_loudvoice'), 'Loudvoice', 'manage_options', 'oa_loudvoice_setup', 'oa_loudvoice_display_setup');
	add_action ('admin_print_styles-' . $page, 'oa_loudvoice_admin_css');
	
	// Settings
	$page = add_submenu_page ('oa_loudvoice_setup', 'OneAll Loudvoice Comments Platform ' . __ ('Settings', 'oa_loudvoice'), __ ('Settings', 'oa_loudvoice'), 'manage_options', 'oa_loudvoice_settings', 'oa_loudvoice_display_settings');
	add_action ('admin_print_styles-' . $page, 'oa_loudvoice_admin_css');
	
	// Fix Setup title
	global $submenu;
	if (is_array ($submenu) and isset ($submenu ['oa_loudvoice_setup']))
	{
		$submenu ['oa_loudvoice_setup'] [0] [0] = __ ('Setup', 'oa_loudvoice');
	}
	
	add_action ('admin_notices', 'oa_loudvoice_admin_message');
	add_action ('admin_enqueue_scripts', 'oa_loudvoice_admin_js');
	add_action ('admin_init', 'oa_loudvoice_register_settings');
}
add_action ('admin_menu', 'oa_loudvoice_admin_menu');

/**
 * Add an activation message to be displayed once
 */
function oa_loudvoice_admin_message ()
{
	if (get_option ('oa_loudvoice_activation_message') !== '1')
	{
		echo '<div class="updated"><p><strong>' . __ ('Thank you for using the Loudvoice comments platform!', 'oa_loudvoice') . '</strong> ' . sprintf (__ ('Please <strong><a href="%s">complete the setup</a></strong> to enable the plugin.', 'oa_loudvoice'), 'admin.php?page=oa_loudvoice_setup') . '</p></div>';
		update_option ('oa_loudvoice_activation_message', '1');
	}
}

/**
 * Add Settings CSS
 */
function oa_loudvoice_admin_css ($hook = '')
{
	if (!wp_style_is ('oa_loudvoice_admin_css', 'registered'))
	{
		wp_register_style ('oa_loudvoice_admin_css', OA_LOUDVOICE_PLUGIN_URL . "/assets/css/admin.css");
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
 * Add Settings JS
 */
function oa_loudvoice_admin_js ($hook)
{
	if (stripos ($hook, 'oa_loudvoice') !== false)
	{
		if (!wp_script_is ('oa_loudvoice_admin_js', 'registered'))
		{
			wp_register_script ('oa_loudvoice_admin_js', OA_LOUDVOICE_PLUGIN_URL . "/assets/js/admin.js");
		}
		
		$oa_loudvoice_ajax_nonce = wp_create_nonce ('oa_loudvoice_ajax_nonce');
		
		wp_enqueue_script ('oa_loudvoice_admin_js');
		wp_enqueue_script ('jquery');
		
		wp_localize_script ('oa_loudvoice_admin_js', 'objectL10n', array(
			'oa_loudvoice_ajax_nonce' => $oa_loudvoice_ajax_nonce,
			'oa_admin_js_1' => __ ('Contacting API - please wait this may take a few minutes ...', 'oa_loudvoice'),
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

// /////////////////////////////////////////////////////////////////////////////////////////////////
// ADMIN SETTINGS
// ////////////////////////////////////////////////////////////////////////////////////////////////

/**
 * Register plugin settings and their sanitization callback
 */
function oa_loudvoice_register_settings ()
{
	register_setting ('oa_loudvoice_settings_group', 'oa_loudvoice_settings', 'oa_loudvoice_settings_validate');
}


/**
 * Display Setup Page
 **/
function oa_loudvoice_display_setup ()
{
	//Import providers
	GLOBAL $oa_loudvoice_providers;
	?>
		<div class="wrap">
			<div id="oa_loudvoice">
				<h2>OneAll Loudvoice <?php echo (defined ('OA_LOUDVOICE_VERSION') ? OA_LOUDVOICE_VERSION : ''); ?></h2>
				<h2 class="nav-tab-wrapper">
          			<a class="nav-tab nav-tab-active" href="admin.php?page=oa_loudvoice_setup"><?php _e ('Setup', 'oa_loudvoice'); ?></a>
          			<a class="nav-tab" href="admin.php?page=oa_loudvoice_settings"><?php _e ('Settings', 'oa_loudvoice'); ?></a>
        		</h2>
				<?php
					if (get_option ('oa_loudvoice_api_settings_verified') !== '1')
					{
						?>
							<p>
								<?php _e ('Allow your visitors to comment, login and register with 20+ Social Networks like for example Twitter, Facebook, LinkedIn, Instagram, VKontakte, Google or Yahoo.', 'oa_loudvoice'); ?>
								<strong><?php _e ('Draw a larger audience and increase your user engagement in a  few simple steps.', 'oa_loudvoice'); ?> </strong>
							</p>
							<div class="oa_loudvoice_box" id="oa_loudvoice_box_status">
								<div class="oa_loudvoice_box_title">
									<?php _e ('Get Started!', 'oa_loudvoice'); ?>
								</div>
								<p>
									<?php printf (__ ('To be able to use this plugin you first of all need to create a free account at %s and setup a Site.', 'oa_loudvoice'), '<a href="https://app.oneall.com/signup/" target="_blank">http://www.oneall.com</a>'); ?>
									<?php _e ('After having created your account and setup your Site, please enter the Site settings in the form below.', 'oa_loudvoice'); ?>
									<?php _e ("Don't worry the setup takes only a couple of minutes!", 'oa_loudvoice'); ?>
								</p>
								<p>
									<a class="button-secondary" href="https://app.oneall.com/signup/" target="_blank"><strong><?php _e ('Click here to setup your free account', 'oa_loudvoice'); ?></strong></a>
								</p>
								<h3>
									<?php printf (__ ('You are in good company! This plugin is used on more than %s websites!', 'oa_loudvoice'), '250,000'); ?>
								</h3>
							</div>
						<?php
					}
					else
					{
						?>
							<p></p>
							<div class="oa_loudvoice_box" id="oa_loudvoice_box_status">
								<div class="oa_loudvoice_box_title">
									<?php _e ('Your API Account is setup correctly', 'oa_loudvoice'); ?>
								</div>
								<p>
									<?php _e ('Login to your account to manage your providers and access your Social Insights.', 'oa_loudvoice'); ?>
									<?php _e ("Determine which social networks are popular amongst your users and tailor your registration experience to increase your users' engagement.", 'oa_loudvoice'); ?>
								</p>
								<p>
									<a class="button-secondary" href="https://app.oneall.com/signin/" target="_blank"><strong><?php _e ('Click here to login to your account', 'oa_loudvoice'); ?></strong> </a>
								</p>
							</div>
						<?php
					}
	
					if (!empty ($_REQUEST ['settings-updated']) AND strtolower ($_REQUEST ['settings-updated']) == 'true')
					{
						?>
							<div class="oa_loudvoice_box" id="oa_loudvoice_box_updated">
								<?php _e ('Your modifications have been saved successfully!'); ?>
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
							<th colspan="2"><?php _e ('API Connection Handler', 'oa_loudvoice'); ?>
							</th>
						</tr>
						<?php
							$api_connection_handler = ((empty ($settings ['api_connection_handler']) OR $settings ['api_connection_handler'] <> 'fsockopen') ? 'curl' : 'fsockopen');
						?>
						<tr class="row_even">
							<td rowspan="2" class="row_multi" style="width:200px">
								<label><?php _e ('API Connection Handler', 'oa_loudvoice'); ?>:</label>
							</td>
							<td>
								<input type="radio" id="oa_loudvoice_api_connection_handler_curl" name="oa_loudvoice_settings[api_connection_handler]" value="curl" <?php echo (($api_connection_handler <> 'fsockopen') ? 'checked="checked"' : ''); ?> />
								<label for="oa_loudvoice_api_connection_handler_curl"><?php _e ('Use PHP CURL to communicate with the API', 'oa_loudvoice'); ?> <strong>(<?php _e ('Default', 'oa_loudvoice') ?>)</strong></label><br />
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
							$api_connection_use_https = ((!isset ($settings ['api_connection_use_https']) OR $settings ['api_connection_use_https'] == '1') ? true : false);
						?>
						<tr class="row_even">
							<td rowspan="2" class="row_multi" style="width:200px">
								<label><?php _e ('API Connection Port', 'oa_loudvoice'); ?>:</label>
							</td>
							<td>
								<input type="radio" id="oa_loudvoice_api_connection_handler_use_https_1" name="oa_loudvoice_settings[api_connection_use_https]" value="1" <?php echo ($api_connection_use_https ? 'checked="checked"' : ''); ?> />
								<label for="oa_loudvoice_api_connection_handler_use_https_1"><?php _e ('Communication via HTTPS on port 443', 'oa_loudvoice'); ?> <strong>(<?php _e ('Default', 'oa_loudvoice') ?>)</strong></label><br />
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
								<?php _e ('API Settings', 'oa_loudvoice'); ?>
							</th>
							<th><a href="https://app.oneall.com/applications/" target="_blank"><?php _e ('Click here to create and view your API Credentials', 'oa_loudvoice'); ?></a>
							</th>
						</tr>
						<tr class="row_even">
							<td style="width:200px">
								<label for="oa_loudvoice_settings_api_subdomain"><?php _e ('API Subdomain', 'oa_loudvoice'); ?>:</label>
							</td>
							<td>
								<input type="text" id="oa_loudvoice_settings_api_subdomain" name="oa_loudvoice_settings[api_subdomain]" size="65" value="<?php echo (isset ($settings ['api_subdomain']) ? htmlspecialchars ($settings ['api_subdomain']) : ''); ?>" />
							</td>
						</tr>
						<tr class="row_odd">
							<td style="width:200px">
								<label for="oa_loudvoice_settings_api_key"><?php _e ('API Public Key', 'oa_loudvoice'); ?>:</label>
							</td>
							<td>
								<input type="text" id="oa_loudvoice_settings_api_key" name="oa_loudvoice_settings[api_key]" size="65" value="<?php echo (isset ($settings ['api_key']) ? htmlspecialchars ($settings ['api_key']) : ''); ?>" />
							</td>
						</tr>
						<tr class="row_even">
							<td style="width:200px">
								<label for="oa_loudvoice_settings_api_secret"><?php _e ('API Private Key', 'oa_loudvoice'); ?>:</label>
							</td>
							<td>
								<input type="text" id="oa_loudvoice_settings_api_secret" name="oa_loudvoice_settings[api_secret]" size="65" value="<?php echo (isset ($settings ['api_secret']) ? htmlspecialchars ($settings ['api_secret']) : ''); ?>" />
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
							<th colspan="2"><?php _e ('JavaScript Settings', 'oa_loudvoice'); ?>
							</th>
						</tr>
						<?php

						  //We dont have a value yet
						  if ( ! isset ($settings['asynchronous_javascript']))
						  {
						    //No subdomain, this is probably a new installation.
						    if ( ! isset ($settings ['api_subdomain']))
						    {
						      //Enable asynchronous JavaScript.
						      $asynchronous_javascript = 1;
						    }
						    //We have a subdomain, this is probably an updated version of the plugin.
						    else
						    {
						      //Disable asynchronous JavaScript.
						      $asynchronous_javascript = 0;
						    }
						  }
						  //We have a value.
						  else
						  {
						    $asynchronous_javascript = ( ! empty ($settings ['asynchronous_javascript']) ? 1 : 0);
						  }

						?>
						<tr class="row_even">
							<td rowspan="2" class="row_multi" style="width:200px">
								<label><?php _e ('JavaScript Usage', 'oa_loudvoice'); ?>:</label>
							</td>
							<td>
								<input type="radio" id="oa_loudvoice_asynchronous_javascript_1" name="oa_loudvoice_settings[asynchronous_javascript]" value="1" <?php echo ( ! empty ($asynchronous_javascript) ? 'checked="checked"' : ''); ?> />
								<label for="oa_loudvoice_asynchronous_javascript_1"><?php _e ('Asynchronous JavaScript', 'oa_loudvoice'); ?> <strong>(<?php _e ('Default', 'oa_loudvoice') ?>)</strong></label><br />
								<span class="description"><?php _e ('Background loading without interfering with the display and behavior of the existing page.', 'oa_loudvoice'); ?></span>
							</td>
						</tr>
						<tr class="row_even">
							<td>
								<input type="radio" id="oa_loudvoice_asynchronous_javascript_0" name="oa_loudvoice_settings[asynchronous_javascript]" value="0" <?php echo (empty ($asynchronous_javascript) ? 'checked="checked"' : ''); ?> />
								<label for="oa_loudvoice_asynchronous_javascript_0"><?php _e ('Synchronous JavaScript', 'oa_loudvoice'); ?> </label><br />
								<span class="description"><?php _e ('Real-time loading when the page is being rendered by the browser.', 'oa_loudvoice'); ?></span>
							</td>
						</tr>
					</table>

					<table class="form-table oa_loudvoice_table">
						<tr class="row_head">
							<th colspan="2">
								<?php _e ('Enable the social networks/identity providers of your choice', 'oa_loudvoice'); ?>
							</th>
						</tr>
						<?php
							$i = 0;
							foreach ($oa_loudvoice_providers AS $key => $provider_data)
							{
								?>
									<tr class="row_provider <?php echo ((($i++) % 2) == 0) ? 'row_even' : 'row_odd' ?>">
										<td class="cell_provider_icon">
											<label for="oneall_loudvoice_provider_<?php echo $key; ?>">
											  <span class="oa_loudvoice_provider oa_loudvoice_provider_<?php echo $key; ?>" title="<?php echo htmlspecialchars ($provider_data ['name']); ?>"><?php echo htmlspecialchars ($provider_data ['name']); ?> </span>
											 </label>
										</td>
										<td class="cell_provider_label">
											<input type="checkbox" id="oneall_loudvoice_provider_<?php echo $key; ?>" name="oa_loudvoice_settings[providers][<?php echo $key; ?>]" value="1" <?php checked ('1', ((isset ($settings ['providers']) && !empty ($settings ['providers'] [$key])) ? $settings ['providers'] [$key] : 0)); ?> />
											<label for="oneall_loudvoice_provider_<?php echo $key; ?>"><?php echo htmlspecialchars ($provider_data ['name']); ?> </label>
											<?php
													if (in_array ($key, array ('vkontakte', 'mailru', 'odnoklassniki')))
													{
														echo ' - ' . sprintf (__ ('To enable cyrillic usernames, you might need <a target="_blank" href="%s">this plugin</a>', 'oa_loudvoice'), 'http://wordpress.org/extend/plugins/wordpress-special-characters-in-usernames/');
													}
											?>
										</td>
									</tr>
								<?php
							}
						?>
					</table>
					<p class="submit">
						<input type="hidden" name="page" value="setup" />
						<input type="submit" class="button-primary" value="<?php _e ('Save Changes', 'oa_loudvoice') ?>" />
					</p>
				</form>
			</div>
		</div>
	<?php
}
