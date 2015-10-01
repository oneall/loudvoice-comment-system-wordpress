<?php

/**
 * Disable Wordpress comments
 */
function oa_loudvoice_pre_comment_on_post ($comment_post_ID)
{
	if (oa_louddvoice_is_setup ())
	{
		wp_die (__ ('The built-in commenting system may not be used while Loudvoice is active.'));
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
		wp_localize_script ('oa_loudvoice_frontend_js', 'objectL10n', array(
			'oa_loudvoice_ajax_nonce' => $ajax_nonce 
		));
		
		// Display Loudvoice
		return dirname (__FILE__) . '/templates/comments.php';
	}
	
	// Loudvoice is not setup
	return $value;
}
add_filter ('comments_template', 'oa_loudvoice_get_comments_template');
