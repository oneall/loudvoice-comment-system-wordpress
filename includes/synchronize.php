<?php

/**
 * Imports a single comment, used by oa_loudvoice_import_comments_from_endpoint
 */
function oa_loudvoice_import_comment_from_endpoint_data ($data)
{
	// Make sure it's valid
	if (is_object ($data) && isset ($data->comment_token) && isset ($data->discussion->discussion_token))
	{
	}
	print_r ($data);
}

/**
 * Imports all the comments from the API endpoint
 */
function oa_loudvoice_import_comments_from_endpoint ($page = 1, $entries_per_page = 100)
{
	// Result
	$status_message = '';
	
	// Is Loudvoice running?
	if (oa_louddvoice_is_setup ())
	{
		// Make Request
		$result = oa_loudvoice_do_api_request_endpoint ('/discussions/comments.json?page=' . $page . '&entries_per_page=' . $entries_per_page);
		
		// Check result
		if (is_object ($result) and property_exists ($result, 'http_code') and $result->http_code == 200 and property_exists ($result, 'http_data'))
		{
			// Decode result
			$json = @json_decode ($result->http_data);
			
			// Make sure it's valid
			if (is_object ($json) and isset ($json->response->result->data->comments))
			{
				// Comments
				$comments = $json->response->result->data->comments;
				
				// Synchronize
				foreach ($comments->entries as $data)
				{
					oa_loudvoice_import_comment_from_endpoint_data ($data);
				}
				
				// Do we have several pages?
				if (!empty ($comments->pagination->current_page) && !empty ($comments->pagination->total_pages))
				{
					// Do we need to parse another page?
					if ($comments->pagination->current_page < $comments->pagination->total_pages)
					{
						$status_message = oa_loudvoice_import_comments_from_endpoint ($comments->pagination->current_page + 1, $entries_per_page);
					}
				}
			}
		}
	}
	
	// Done
	return $status_message;
}

function oa_loudvoice_debug ($cli, $title, $body)
{
	if ($cli)
	{
		//Build Email
		$message_body = ((is_array ($body) OR is_object ($body)) ? "\n".print_r ($body, true) : (": ".$body));
		$message_title = (!empty ($title) ? $title : "oa_loudvoice_debug");
		
		echo $message_title.$message_body."\n";
	
	}
	
}

/**
 * Exports all the comments to the API endpoint
 */
function oa_loudvoice_export_post_to_endpoint ($post, $cli = false)
{
	global $wpdb;
	
	// Make sure we have a valid post
	if (is_object ($post) && !empty ($post->ID))
	{		
		// Post Identifier
		$postid = $post->ID;
		
		// Debug
		oa_loudvoice_debug ($cli, 'Exporting Post', $post->ID);
		
		// API Data
		$data = array(
			'method' => 'PUT',
			'post_data' => json_encode (array(
				'request' => array(
					'discussion' => array(
						'title' => oa_loudvoice_get_title_for_post ($post),
						'url' => oa_loudvoice_get_link_for_post ($post),
						'discussion_reference' => oa_loudvoice_get_reference_for_post ($post),
						'allow_create_discussion_reference' => true
					) 
				) 
			)) 
		);
		
		// Debug
		oa_loudvoice_debug ($cli, 'API Data /discussions.json', $data);
	
		// Push post to Loudvoice
		$result = oa_loudvoice_do_api_request_endpoint ('/discussions.json', $data);
		
		// Check result (201: created, 200: already exists)
		if (is_object ($result) && property_exists ($result, 'http_code') && ($result->http_code == 200 || $result->http_code == 201))
		{
			// Decode result
			$json = @json_decode ($result->http_data);
			
			// Debug
			oa_loudvoice_debug ($cli, 'API Result', $json);
			
			// Token
			$discussion_token = $json->response->result->data->discussion->discussion_token;
			
			// Should not be empty
			if (!empty ($discussion_token))
			{
				// Update Meta
				update_post_meta ($post->ID, 'oa_loudvoice_synchronized', $discussion_token . "|" . time ());	
				
				// Now we synchronize the comments
				$sql = "SELECT * FROM " . $wpdb->comments . " WHERE comment_post_ID='" . $post->ID . "' AND comment_type != 'trackback' AND comment_type != 'pingback' ORDER BY comment_ID ASC";
				$comments = $wpdb->get_results ($sql);
				
				// Cleanup synchronized comments
				delete_post_meta($postid, 'oa_loudvoice_synchronized_comments');
				
				// Comments found?
				if (is_array ($comments))
				{
					foreach ($comments as $comment)
					{
						// Comment
						$commentid = $comment->comment_ID;
						
						// API Data
						$data = array(
							'method' => 'PUT',
							'post_data' => json_encode (array(
								'request' => array(
									'discussion' => array(
										'discussion_token' => $discussion_token 
									),
									'comment' => array(
										// 'parent_comment_token' => $parent_comment_token,
										'comment_reference' => oa_loudvoice_get_reference_for_comment ($comment), 
									//	'allow_create_comment_reference' => true,
										'text' => $comment->comment_content,
										'author' => array(
											'author_reference' => oa_loudvoice_get_reference_for_user ($comment->user_id),
											'name' => $comment->comment_author,
											'email' => $comment->comment_author_email,
											'website_url' => $comment->comment_author_url,
											'picture_url' => get_avatar ($comment->user_id),
											'ip_address' => $comment->comment_author_IP 
										) 
									) 
								) 
							)) 
						);						
			
						// Debug						
						oa_loudvoice_debug ($cli, 'API Data /discussions/comments.json', $data);
						
						// Push post to Loudvoice
						$result = oa_loudvoice_do_api_request_endpoint ('/discussions/comments.json', $data);
						
						print_r($result);
						// Check result
						if (is_object ($result) and property_exists ($result, 'http_code') and $result->http_code == 200 and property_exists ($result, 'http_data'))
						{
							// Decode result
							$json = @json_decode ($result->http_data);
						
							// Make sure it's valid
							if (is_object ($json) and isset ($json->response->result->data->comment))
							{
								// Comment
								$comment_token = $json->response->result->data->comment->comment_token;
								
								// Save Post Meta								
								add_post_meta ($postid, 'oa_loudvoice_synchronized_comments', $comment_token, false);

								// Save Comment Meta
								update_comment_meta ($commentid, 'oa_loudvoice_synchronized_discussion', $discussion_token);
								update_comment_meta ($commentid, 'oa_loudvoice_synchronized', $comment_token . "|" . time ());
							}
						}				
					}
				}			
			}
			
			// Success
			return true;
		}
	}
	
	// Error
	return false;
}

/**
 * Exports all the comments to the API endpoint
 */
function oa_loudvoice_export_to_endpoint ($cli = false)
{
	// Global Vars
	global $wpdb;
	
	// Is Loudvoice running?
	if (oa_louddvoice_is_setup ())
	{
		// Read Published Posts
		$sql = "SELECT * FROM " . $wpdb->posts . " WHERE post_type != 'revision' AND post_status = 'publish' AND comment_count > 0	ORDER BY ID ASC";
		$posts = $wpdb->get_results ($sql);
		
		// Posts found?
		if (is_array ($posts))
		{
			foreach ($posts as $post)
			{
				// Export Post
				oa_loudvoice_export_post_to_endpoint ($post, $cli);
			}
		}
	}
}

/**
 * Synchronizes all the comments
 */
function oa_loudvoice_full_synchronize ($cli = false)
{
	// Status Message
	$status_message = '';
	
	// Is Loudvoice running?
	if (oa_louddvoice_is_setup ())
	{
		// Import comments
		// oa_loudvoice_import_comments_from_endpoint ();
		
		// Export comments
		oa_loudvoice_export_to_endpoint ($cli);
	}
	// Error
	else
	{
		$status_message = 'error_loudvoice_not_setup';
	}
	
	// Done
	echo $status_message;
	wp_die ();
}
add_action ('wp_ajax_full_synchronize', 'oa_loudvoice_full_synchronize');

/**
 * Synchronize LV -> WP, Processing
 */
function oa_loudvoice_import_post_comment_from_encoded_json ($postid, $encoded_json)
{
	// Status Message
	$status_message = '';
	
	// Decode result
	$json = @json_decode ($encoded_json);
	
	// Make sure it's valid
	if (is_object ($json) and isset ($json->response->result->data->comment))
	{
		// Loudvoice Objects
		$lv_discussion = $json->response->result->data->discussion;
		$lv_comment = $json->response->result->data->comment;
		
		// Validate the references before doing anything else
		if ($lv_discussion->discussion_reference == oa_loudvoice_get_reference_for_post ($postid))
		{
			// These comments have already been synchronized
			$synchronized_comments = get_post_custom_values ('oa_loudvoice_synchronized_comments', $postid);
			
			// This comment has not yet been synchronized
			if (!is_array ($synchronized_comments) || !in_array ($lv_comment->comment_token, $synchronized_comments))
			{
				// Prepare WordPress Comment
				$data = array(
					'comment_post_ID' => $postid,
					'comment_author' => $lv_comment->author->name,
					'comment_author_email' => $lv_comment->author->email,
					'comment_content' => $lv_comment->text,
					'comment_parent' => 0,
					'user_id' => 0,
					'comment_author_IP' => $lv_comment->author->ip_address,
					'comment_agent' => 'Loudvoice/1.0 WordPress',
					'comment_date_gmt' => date ("Y-m-d G:i:s", strtotime ($lv_comment->date_creation)),
					'comment_approved' => 1 
				);
				
				// Filter
				$data = wp_filter_comment ($data);
				
				// Insert WordPress Comment
				$commentid = wp_insert_comment ($data);
				
				// Save Post Meta
				add_post_meta ($postid, 'oa_loudvoice_synchronized_comments', $lv_comment->comment_token, false);
				update_post_meta ($postid, 'oa_loudvoice_synchronized', $lv_discussion->discussion_token . "|" . time ());
				
				// Save Comment Meta
				update_comment_meta ($commentid, 'oa_loudvoice_synchronized_discussion', $lv_discussion->discussion_token);
				update_comment_meta ($commentid, 'oa_loudvoice_synchronized', $lv_comment->comment_token . "|" . time ());
				
				// Synchronized
				$status_message = 'success_comment_synchronized';
			}
			// Already Synchronized
			else
			{
				$status_message = 'success_comment_already_synchronized';
			}
		}
		// Error
		else
		{
			$status_message = 'error_invalid_post_reference';
		}
	}
	// Error
	else
	{
		$status_message = 'error_invalid_data_format';
	}
	
	// Done
	return $status_message;
}

/**
 * Synchronize LV -> WP, Ajax Call
 */
function oa_loudvoice_import_comment_ajax ()
{
	// Check AJAX Nonce
	check_ajax_referer ('oa_loudvoice_ajax_nonce');
	
	// Status Message
	$status_message = '';
	
	// Is Loudvoice running?
	if (oa_louddvoice_is_setup ())
	{
		// Post Identifier
		$postid = null;
		if (!empty ($_REQUEST ['postid']))
		{
			$postid = intval (trim ($_REQUEST ['postid']));
		}
		
		// Comment Token
		$comment_token = null;
		if (!empty ($_REQUEST ['comment_token']) && oa_loudvoice_is_valid_uuid ($_REQUEST ['comment_token']))
		{
			$comment_token = trim ($_REQUEST ['comment_token']);
		}
		
		// We need both arguments
		if (!empty ($postid) && !empty ($comment_token))
		{
			// Pull Comment
			$result = oa_loudvoice_do_api_request_endpoint ('/discussions/comments/' . $comment_token . '.json');
			
			// Check result
			if (is_object ($result) and property_exists ($result, 'http_code') and $result->http_code == 200 and property_exists ($result, 'http_data'))
			{
				$status_message = oa_loudvoice_import_post_comment_from_encoded_json ($postid, $result->http_data);
			}
		}
		// Error
		else
		{
			$status_message = 'error_invalid_arguments';
		}
	}
	// Error
	else
	{
		$status_message = 'error_loudvoice_not_setup';
	}
	
	// Done
	echo $status_message;
	wp_die ();
}
add_action ('wp_ajax_nopriv_import_comment', 'oa_loudvoice_import_comment_ajax');
add_action ('wp_ajax_import_comment', 'oa_loudvoice_import_comment_ajax');

/**
 * Synchronize the post/comment when a new post is made
 */
function oa_loudvoice_synchronize_comment ($commentid)
{
	// Is Loudvoice running?
	if (oa_louddvoice_is_setup ())
	{
		// Read settings
		$settings = get_option ('oa_loudvoice_settings');
		
		// Comment
		$comment = get_comment ($commentid);
		$comment_text = $comment->comment_content;
		
		// Token
		$discussion_reference = oa_loudvoice_get_reference_for_post ($comment->comment_post_ID);
		$parent_comment_token = oa_loudvoice_get_identifier_for_comment ($comment->comment_parent);
		$comment_token = oa_loudvoice_get_identifier_for_comment ($comment->comment_ID);
		
		// Author
		$author_reference = oa_loudvoice_get_identifier_for_user ($comment->user_id);
		$author_name = $comment->comment_author;
		$author_email = $comment->comment_author_email;
		$author_ip_address = $comment->comment_author_IP;
		$author_avatar = get_avatar ($comment);
		
		// Request Data
		$data = array(
			'request' => array(
				'discussion' => array(
					'discussion_reference' => $discussion_reference 
				),
				'comment' => array(
					'parent_comment_token' => $parent_comment_token,
					'text' => $comment_text,
					'author' => array(
						'author_reference' => $author_reference,
						'name' => $author_name,
						'email' => $author_email,
						'picture_url' => $author_avatar,
						'ip_address' => $author_ip_address 
					) 
				) 
			) 
		);
		
		// API Settings
		$api_connection_handler = ((!empty ($settings ['api_connection_handler']) and $settings ['api_connection_handler'] == 'fsockopen') ? 'fsockopen' : 'curl');
		$api_connection_use_https = ((!isset ($settings ['api_connection_use_https']) or $settings ['api_connection_use_https'] == '1') ? true : false);
		$api_subdomain = trim ($settings ['api_subdomain']);
		$api_key = (!empty ($settings ['api_key']) ? $settings ['api_key'] : '');
		$api_secret = (!empty ($settings ['api_secret']) ? $settings ['api_secret'] : '');
		
		// Endpoint
		$api_resource_url = ($api_connection_use_https ? 'https' : 'http') . '://' . $api_subdomain . '.api.oneall.loc/discussions/comments.json';
		
		// API Credentials
		$api_opts = array();
		$api_opts ['api_key'] = $api_key;
		$api_opts ['api_secret'] = $api_secret;
		
		// Push Comment
		$result = oa_social_login_do_api_request ($api_connection_handler, $api_resource_url, $api_opts);
		
		// Check result
		if (is_object ($result) and property_exists ($result, 'http_code') and $result->http_code == 200 and property_exists ($result, 'http_data'))
		{
			if (!add_post_meta ($comment->comment_post_ID, '_oa_loudvoice_synchronized', time (), true))
			{
				update_post_meta ($comment->comment_post_ID, '_oa_loudvoice_synchronized', time ());
			}
		}
	}
}
