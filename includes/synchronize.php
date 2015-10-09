<?php

/**
 * Displays a debug message if we are running on cli
 */
function oa_loudvoice_debug ($cli, $title = '', $body = '')
{
	if ($cli)
	{
		$message_body = (!empty ($body) ? ((is_array ($body) or is_object ($body)) ? "\n" . print_r ($body, true) : (": " . $body)) : '');
		$message_title = (!empty ($title) ? $title : "");
		echo $message_title . $message_body . "\n";
	}
}

// ////////////////////////////////////////////////////////////////////////////////////////////////
// IMPORT
// ////////////////////////////////////////////////////////////////////////////////////////////////

/**
 * Imports all comments for the given discussion token from Loudvoice to WordPress
 */
function oa_loudvoice_do_import_comments_for_discussion_token ($cli, $discussion_token, $page = 1, $entries_per_page = 50)
{
	// Result
	$result = array(
		'created' => array(),
		'updated' => array(),
		'post_not_found' => array() 
	);
	
	// Is Loudvoice running?
	if (oa_louddvoice_is_setup ())
	{

		// Post found for this comment
		if (($postid = oa_loudvoice_get_postid_for_token ($discussion_token)) !== false)
		{
			// Debug
			if ($page == 1)
			{
				oa_loudvoice_debug ($cli, ' WordPress PostID Found', $postid);
			}
			oa_loudvoice_debug ($cli, ' Reading Comments, Page', $page);
			
			// Make Request
			$api_result = oa_loudvoice_do_api_request_endpoint ('/discussions/' . $discussion_token . '/comments.json?page=' . $page . '&entries_per_page=' . $entries_per_page);
			
			// Check result
			if (is_object ($api_result) and property_exists ($api_result, 'http_code') and $api_result->http_code == 200)
			{
				// Decode result
				$json = @json_decode ($api_result->http_data);
				
				// Make sure it's valid
				if (is_object ($json) and isset ($json->response->result->data->comments))
				{
					// Discusisons
					$lv_comments = $json->response->result->data->comments;
					
					// Import
					foreach ($lv_comments->entries as $lv_data)
					{
						// Debug
						oa_loudvoice_debug ($cli, '  Importing comment_token', $lv_data->comment_token);
						
						// Comment found in database
						if (($commentid = oa_loudvoice_get_commentid_for_token ($lv_data->comment_token)) !== false)
						{			
							
							// Full Comment Data
							if (($wp_data = get_comment ($commentid, 'ARRAY_A')) !== null)
							{
								// Update Fields
								$wp_data ['comment_approved'] = oa_loudvoice_get_wordpress_approved_status ($lv_data->moderation_status, $lv_data->spam_status);
								
								// Filter
								$wp_data = wp_filter_comment ($wp_data);
								
								// Insert WordPress Comment
								wp_update_comment ($wp_data);
								
								// Updated
								$result ['updated'] [$commentid] = $lv_data->comment_token;
								
								// Debug
								oa_loudvoice_debug ($cli, '   UPDATED WordPress Comment', $commentid);
							}
						}
						// Comment not found in database
						else
						{
							// Debug
							oa_loudvoice_debug ($cli, '   No WordPress Comment Found', $lv_data->comment_token);
							oa_loudvoice_debug ($cli, '   Adding Comment To Post', $postid);
							
							// Prepare WordPress Comment
							$wp_data = array(
								'comment_post_ID' => $postid,
								'comment_author' => $lv_data->author->name,
								'comment_author_email' => $lv_data->author->email,
								'comment_author_url' => '',
								'comment_content' => $lv_data->text,
								'comment_parent' => 0,
								'user_id' => 0,
								'comment_author_IP' => $lv_data->ip_address,
								'comment_agent' => 'Loudvoice/1.0 WordPress',
								'comment_date_gmt' => date ("Y-m-d G:i:s", strtotime ($lv_data->date_creation)),
								'comment_approved' => oa_loudvoice_get_wordpress_approved_status ($lv_data->moderation_status, $lv_data->spam_status) 
							);
							
							// Filter
							$wp_data = wp_filter_comment ($wp_data);
							
							// Insert WordPress Comment
							$commentid = wp_insert_comment ($wp_data);
							
							// Updated
							$result ['created'] [$commentid] = $lv_data->comment_token;
							
							// Update Meta
							add_post_meta ($postid, '_oa_loudvoice_synchronized_comments', $lv_data->comment_token, false);
							update_post_meta ($postid, '_oa_loudvoice_synchronized', $discussion_token);
							
							// Save Comment Meta
							update_comment_meta ($commentid, '_oa_loudvoice_synchronized_discussion', $discussion_token);
							update_comment_meta ($commentid, '_oa_loudvoice_synchronized', $lv_data->comment_token);
							
							// Debug
							oa_loudvoice_debug ($cli, '  CREATED WordPress Comment', $commentid);
						}
					}
					
					// Do we have several pages?
					if (!empty ($lv_comments->pagination->current_page) && !empty ($lv_comments->pagination->total_pages))
					{
						// Do we need to parse another page?
						if ($lv_comments->pagination->current_page < $lv_comments->pagination->total_pages)
						{
							$sub_result = oa_loudvoice_do_import_comments_for_discussion_token ($cli, $discussion_token, ($lv_comments->pagination->current_page + 1), $entries_per_page);
							
							foreach ($result as $key => $value)
							{
								$result [$key] += $sub_result [$key];
							}
						}
					}
				}
			}
		}
		// No post found for this comment
		else
		{
			// Debug
			oa_loudvoice_debug ($cli, ' No WordPress PostID found for discussion_token', $discussion_token);
		}
	}
	// Done
	return $result;
}

/**
 * Imports all comments from Loudvoice to WordPress
 */
function oa_loudvoice_do_import ($cli, $page = 1, $entries_per_page = 50)
{
	// Result
	$result = array();
	
	// Is Loudvoice running?
	if (oa_louddvoice_is_setup ())
	{
		// Debug
		oa_loudvoice_debug ($cli);
		oa_loudvoice_debug ($cli, 'Importing Discussions', 'Page: ' . $page . ' / Entries Per Page: ' . $entries_per_page);
		
		// Make Request
		$api_result = oa_loudvoice_do_api_request_endpoint ('/discussions.json?page=' . $page . '&entries_per_page=' . $entries_per_page);
		
		// Check result
		if (is_object ($api_result) and property_exists ($api_result, 'http_code') and $api_result->http_code == 200)
		{
			// Decode result
			$json = @json_decode ($api_result->http_data);
			
			// Make sure it's valid
			if (is_object ($json) and isset ($json->response->result->data->discussions))
			{
				// Discusisons
				$lv_discussions = $json->response->result->data->discussions;
				
				// Import
				foreach ($lv_discussions->entries as $lv_data)
				{
					// Debug
					oa_loudvoice_debug ($cli, 'Importing Discussion', $lv_data->discussion_token);
					
					// Import Comments
					$result [$lv_data->discussion_token] = oa_loudvoice_do_import_comments_for_discussion_token ($cli, $lv_data->discussion_token);
					
					// Debug
					oa_loudvoice_debug ($cli);
				}
				
				// Do we have several pages?
				if (!empty ($lv_discussions->pagination->current_page) && !empty ($lv_discussions->pagination->total_pages))
				{
					// Do we need to parse another page?
					if ($lv_discussions->pagination->current_page < $lv_discussions->pagination->total_pages)
					{
						$result = $result + oa_loudvoice_do_import ($cli, ($lv_discussions->pagination->current_page + 1), $entries_per_page);
					}
				}
			}
		}
	}
	
	// Done
	return $result;
}

/**
 * Imports all comments from Loudvoice to WordPress
 */
function oa_loudvoice_import ($cli = false)
{
	// Import
	$result = oa_loudvoice_do_import ($cli);
	
	// Cleanup Meta
	oa_loudvoice_cleanup_post_comment_meta ();
	
	print_r ($result);
	die ();
}
add_action ('wp_ajax_oa_loudvoice_import', 'oa_loudvoice_import');

// ////////////////////////////////////////////////////////////////////////////////////////////////
// EXPORT
// ////////////////////////////////////////////////////////////////////////////////////////////////

/**
 * Exports all comments from WordPress to Loudvoice
 */
function oa_loudvoice_do_export ($cli = false)
{
	// Global Vars
	global $wpdb;
	
	// Status Message
	$num_comments_tot = 0;
	$num_comments_exported = 0;
	$num_comments_errors = 0;
	
	// Is Loudvoice running?
	if (oa_louddvoice_is_setup ())
	{
		// Read Published Posts
		$sql = "SELECT * FROM " . $wpdb->posts . " WHERE post_type != 'revision' AND post_status = 'publish' AND comment_count > 0 ORDER BY ID ASC";
		$posts = $wpdb->get_results ($sql);
		
		// Loop through results
		if (is_array ($posts))
		{
			foreach ($posts as $post)
			{
				// Export Post
				list ($num_comments_post_tot, $num_comments_post_exported, $num_comments_post_errors) = oa_loudvoice_export_post_to_endpoint ($post, $cli);
				
				// Statistics
				$num_comments_tot += $num_comments_post_tot;
				$num_comments_exported += $num_comments_post_exported;
				$num_comments_errors += $num_comments_post_errors;
			}
		}
		
		// Done
		if ($num_comments_errors == 0)
		{
			if ($num_comments_tot == 0)
			{
				$status_message = 'success|no_comments|There are not comments that could be exported.';
			}
			else
			{
				$status_message = 'success|comments_full_exported|All comments have successfully been synchronized.';
			}
		}
		else
		{
			$status_message = 'success|comments_partially_exported|Out ' . $num_comments_tot . ' comments, ' . $num_comments_exported . ' have successfully been synchronized.';
		}
	}
	// Error
	else
	{
		$status_message = 'error|setup_required|Loudvoice needs to be setup before you can export your comments';
	}
	
	// Done
	return $status_message;
}

/**
 * Exports all comments from WordPress to Loudvoice
 */
function oa_loudvoice_export ($cli = false)
{
	// Import
	$result = oa_loudvoice_do_export ($cli, 1, 20);
	
	// Cleanup Meta
	oa_loudvoice_cleanup_post_comment_meta ();
	
	print_r ($result);
	die ();
}
add_action ('wp_ajax_oa_loudvoice_export', 'oa_loudvoice_export');

/**
 * Exports all comments of the given post from WordPress to Loudvoice
 */
function oa_loudvoice_export_post_to_endpoint ($post, $cli = false)
{
	global $wpdb;
	
	// Total Comments
	$num_comments_tot = 0;
	
	// Newly Exported
	$num_comments_exported = 0;
	
	// Errors
	$num_comments_errors = 0;
	
	// Make sure we have a valid post
	if (is_object ($post) && !empty ($post->ID))
	{
		// Post Identifier
		$postid = $post->ID;
		
		// Debug
		oa_loudvoice_debug ($cli, 'Exporting Post', $postid);
		
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
		
		// Push post to Loudvoice
		$result = oa_loudvoice_do_api_request_endpoint ('/discussions.json', $data);
		
		// Check result (201: created, 200: already exists)
		if (is_object ($result) && property_exists ($result, 'http_code') && ($result->http_code == 200 || $result->http_code == 201))
		{
			// Debug
			oa_loudvoice_debug ($cli, 'Post Exported, Action', ($result->http_code == 201 ? 'Created' : 'Updated'));
			
			// Decode result
			$json = @json_decode ($result->http_data);
			
			// Token
			$discussion_token = $json->response->result->data->discussion->discussion_token;
			
			// Should not be empty
			if (!empty ($discussion_token))
			{
				// Update Meta
				update_post_meta ($post->ID, '_oa_loudvoice_synchronized', $discussion_token);
				
				// Now we synchronize the comments
				$sql = "SELECT * FROM " . $wpdb->comments . " WHERE comment_post_ID='" . $post->ID . "' AND comment_type != 'trackback' AND comment_type != 'pingback' ORDER BY comment_ID ASC";
				$comments = $wpdb->get_results ($sql);
				
				// Cleanup synchronized comments
				delete_post_meta ($postid, '_oa_loudvoice_synchronized_comments');
				
				// Comments found?
				if (is_array ($comments))
				{
					foreach ($comments as $comment)
					{
						// Total Comments
						$num_comments_tot ++;
						
						// Comment
						$commentid = $comment->comment_ID;
						
						// Debug
						oa_loudvoice_debug ($cli, 'Exporting Comment', $commentid);
						
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
										'comment_reference' => oa_loudvoice_get_comment_reference_for_comment ($comment),
										'allow_create_comment_reference' => true,
										'allow_create_duplicate_comments' => true,
										'moderation_status' => oa_loudvoice_get_status_for_comment ($comment, 'moderation'),
										'spam_status' => oa_loudvoice_get_status_for_comment ($comment, 'spam'),
										'text' => $comment->comment_content,
										'author' => array(
											'author_reference' => oa_loudvoice_get_author_reference_for_comment ($comment),
											'name' => $comment->comment_author,
											'email' => $comment->comment_author_email,
											'website_url' => $comment->comment_author_url,
											'picture_url' => oa_loudvoice_get_avatar_url_for_userid ($comment->user_id),
											'ip_address' => $comment->comment_author_IP 
										) 
									) 
								) 
							)) 
						);
						print_r ($data);
						// Push post to Loudvoice
						$result = oa_loudvoice_do_api_request_endpoint ('/discussions/comments.json', $data);
						
						// Check result (201: created, 200: already exists)
						if (is_object ($result) && property_exists ($result, 'http_code') && ($result->http_code == 200 || $result->http_code == 201))
						{
							// Decode result
							$json = @json_decode ($result->http_data);
							
							// Created
							if ($result->http_code == 201)
							{
								oa_loudvoice_debug ($cli, 'Comment Exported', $json->response->result->data->comment->comment_token);
								$num_comments_exported ++;
							}
							else
							{
								oa_loudvoice_debug ($cli, 'Comment Already Existed', $json->response->result->data->comment->comment_token);
							}
							
							// Comment
							$comment_token = $json->response->result->data->comment->comment_token;
							
							// Save Post Meta
							add_post_meta ($postid, '_oa_loudvoice_synchronized_comments', $comment_token, false);
							update_post_meta ($postid, '_oa_loudvoice_synchronized', $discussion_token);
							
							// Save Comment Meta
							update_comment_meta ($commentid, '_oa_loudvoice_synchronized_discussion', $discussion_token);
							update_comment_meta ($commentid, '_oa_loudvoice_synchronized', $comment_token);
						}
						// Error
						else
						{
							// Debug
							oa_loudvoice_debug ($cli, 'Comment Export Error, Code', $result->http_code);
							$num_comments_errors ++;
						}
					}
				}
			}
		}
		// Error
		else
		{
			// Debug
			oa_loudvoice_debug ($cli, 'Post Export Error, Code', $result->http_code);
		}
	}
	
	// Done
	return array(
		$num_comments_tot,
		$num_comments_exported,
		$num_comments_errors 
	);
}

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
			$synchronized_comments = get_post_custom_values ('_oa_loudvoice_synchronized_comments', $postid);
			
			// This comment has not yet been synchronized
			if (!is_array ($synchronized_comments) || !in_array ($lv_comment->comment_token, $synchronized_comments))
			{
				// Prepare WordPress Comment
				$data = array(
					'comment_post_ID' => $postid,
					'comment_author' => $lv_comment->author->name,
					'comment_author_email' => $lv_comment->author->email,
					'comment_author_url' => '',
					'comment_content' => $lv_comment->text,
					'comment_parent' => 0,
					'user_id' => 0,
					'comment_author_IP' => $lv_comment->ip_address,
					'comment_agent' => 'Loudvoice/1.0 WordPress',
					'comment_date_gmt' => date ("Y-m-d G:i:s", strtotime ($lv_comment->date_creation)),
					'comment_approved' => oa_loudvoice_get_wordpress_approved_status ($lv_comment->moderation_status, $lv_comment->spam_status) 
				);
				
				// Filter
				$data = wp_filter_comment ($data);
				
				// Insert WordPress Comment
				$commentid = wp_insert_comment ($data);
				
				// Save Post Meta
				add_post_meta ($postid, '_oa_loudvoice_synchronized_comments', $lv_comment->comment_token, false);
				update_post_meta ($postid, '_oa_loudvoice_synchronized', $lv_discussion->discussion_token);
				
				// Save Comment Meta
				update_comment_meta ($commentid, '_oa_loudvoice_synchronized_discussion', $lv_discussion->discussion_token);
				update_comment_meta ($commentid, '_oa_loudvoice_synchronized', $lv_comment->comment_token);
				
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
	die ();
}
add_action ('wp_ajax_nopriv_import_comment', 'oa_loudvoice_import_comment_ajax');
add_action ('wp_ajax_import_comment', 'oa_loudvoice_import_comment_ajax');
