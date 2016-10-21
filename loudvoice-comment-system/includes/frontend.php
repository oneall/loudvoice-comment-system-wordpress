<?php

/**
 * Remove recent comments widget
 */ 
function oa_loudvoice_remove_recent_comments()
{
    global $wp_widget_factory;
    
    if (is_object ($wp_widget_factory) && isset ($wp_widget_factory->widgets))
    {
    	if (isset ($wp_widget_factory->widgets['WP_Widget_Recent_Comments']))
    	{
    		unset($wp_widget_factory->widgets['WP_Widget_Recent_Comments']);
    	}  	
    }    
}
add_action('widgets_init', 'oa_loudvoice_remove_recent_comments');

/**
 * Add js to footer to count comments in every pages
 */ 
function oa_loudvoice_comments_count() {

	// Read settings
	$settings = get_option ('oa_loudvoice_settings');

	$count_library = ((oa_loudvoice_is_https_on () ? 'https' : 'http') . '://' . $settings ['api_subdomain'] . '.api.oneall.com/loudvoice/comments/counters.js?discussions_tokens=');

	// Loudvoice is available
	if (oa_louddvoice_is_setup ())
	{
		// Register Loudvoice JavaScript
		if (!wp_script_is ('counter_script', 'registered'))
		{
			wp_register_script ('counter_script', OA_LOUDVOICE_PLUGIN_URL . "/assets/js/comments_counter.js");
		}
		
		// Enqueue Script
		wp_enqueue_script ('counter_script', array( 'jQuery') );
		
		// Create Placeholders
		wp_localize_script ('counter_script', 'count_library', $count_library);
		
	}
}
add_action( 'wp_footer', 'oa_loudvoice_comments_count' );

/**
 * Disable Wordpress comments
 */
function oa_loudvoice_pre_comment_on_post ($comment_post_ID)
{
	if (oa_louddvoice_is_setup ())
	{
		wp_die (__ ('To prevent spam the built-in commenting system may not be used while Loudvoice is active.'));
	}
	return $comment_post_ID;
}
add_action ('pre_comment_on_post', 'oa_loudvoice_pre_comment_on_post');

/**
 * Replace the comments form
 */
function oa_loudvoice_get_comments_template ($value)
{
	global $post;
	
	// It must be a single post with commenting enabled
	if (!(is_singular () && (have_comments () or $post->comment_status == 'open')))
	{
		return;
	}
	
	// Loudvoice is available
	if (oa_louddvoice_is_setup ())
	{
		// Register Loudvoice JavaScript
		if (!wp_script_is ('oa_loudvoice_frontend_js', 'registered'))
		{
			wp_register_script ('oa_loudvoice_frontend_js', OA_LOUDVOICE_PLUGIN_URL . "/assets/js/loudvoice.js");
		}
		
		// Register PlaceHolders
		$ajax_nonce = wp_create_nonce ('oa_loudvoice_ajax_nonce');
		
		// Enqueue Script
		wp_enqueue_script ('oa_loudvoice_frontend_js');
		
		// Create Placeholders
		wp_localize_script ('oa_loudvoice_frontend_js', 'oa_loudvoice', array(
			'nonce' => $ajax_nonce,
			'ajaxurl' => admin_url ('admin-ajax.php') 
		));
		
		// Display Loudvoice
		return dirname (__FILE__) . '/templates/comments.php';
	}
	
	// Loudvoice is not setup
	return $value;
}
add_filter ('comments_template', 'oa_loudvoice_get_comments_template');
