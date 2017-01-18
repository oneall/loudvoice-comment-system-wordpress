<?php

/**
 * Displays a debug message if we are running on cli
 */
function oa_loudvoice_debug ($verbose, $message = '')
{
	if ($verbose)
	{
		$message = (!empty ($message) ? ((is_array ($message) or is_object ($message)) ? "\n" . print_r ($message, true) : $message) : '');
		echo $message . "\n";
	}
}

// ////////////////////////////////////////////////////////////////////////////////////////////////
// IMPORT
// ////////////////////////////////////////////////////////////////////////////////////////////////

/**
 * Imports all comments for the given discussion token from Loudvoice to WordPress
 */
function oa_loudvoice_do_import_comments_for_discussion_token ($verbose, $discussion_token, $page = 1, $entries_per_page = 50)
{
	// Result
	$result = array(
		'created' => array(),
		'updated' => array() 
	);
	
	// Is Loudvoice running?
	if (oa_louddvoice_is_setup ())
	{		
		// Post found for this token
		if (($postid = oa_loudvoice_get_postid_for_token ($discussion_token)) !== false)
		{
			// Debug
			if ($page == 1)
			{
				oa_loudvoice_debug ($verbose, ' WordPress PostID Found: ' . $postid);
			}
			oa_loudvoice_debug ($verbose, ' Reading Comments, Page: ' . $page);
			
			// Make Request
			$api_result = oa_loudvoice_do_api_request_endpoint ('/loudvoice/discussions/' . $discussion_token . '/comments.json?page=' . $page . '&entries_per_page=' . $entries_per_page);

			// Check result
			if (is_object ($api_result) and property_exists ($api_result, 'http_code') and $api_result->http_code == 200)
			{
				// Decode result
				$json = @json_decode ($api_result->http_data);
				
				// Make sure it's valid
				if (is_object ($json) and isset ($json->response->result->data->comments))
				{
					// Comments
					$lv_comments = $json->response->result->data->comments;
			
					// Import
					if (! empty ($lv_comments->entries))
					{
						foreach ($lv_comments->entries as $lv_comment)
						{
							// Debug
							oa_loudvoice_debug ($verbose, '  Importing comment_token: ' . $lv_comment->comment_token);

							// Comment found in database
							if (($commentid = oa_loudvoice_get_commentid_for_token ($lv_comment->comment_token)) !== false)
							{								
								// Full Comment Data
								if (($wp_data = get_comment ($commentid, 'ARRAY_A')) !== null)
								{
									// Update Fields
									$wp_data ['comment_approved'] = oa_loudvoice_get_wordpress_approved_status ($lv_comment->moderation_status, $lv_comment->spam_status, $lv_comment->is_trashed);

									$wp_data ['comment_parent'] = ( ! empty ($lv_comment->parent_comment_token) ? oa_loudvoice_get_commentid_for_token ($lv_comment->parent_comment_token) : 0);
									
									// Filter
									$wp_data = wp_filter_comment ($wp_data);
									
									// Insert WordPress Comment
									wp_update_comment ($wp_data);
									
									// Updated
									$result ['updated'] [$commentid] = $lv_comment->comment_token;
									
									// Debug
									oa_loudvoice_debug ($verbose, '   UPDATED WordPress Comment #' . $commentid);
								}
							}
							// Comment not found in database
							else
							{
								// Debug
								oa_loudvoice_debug ($verbose, '   No WordPress Comment Found ' . $lv_comment->comment_token);
								oa_loudvoice_debug ($verbose, '   Adding Comment To Post #' . $postid);
								
								// Prepare WordPress Comment
								$wp_data = array(
									'comment_post_ID'      => $postid,
									'comment_author'       => ( ! empty ($lv_comment->author->name) ? $lv_comment->author->name : ''), 
									'comment_author_email' => ( ! empty ($lv_comment->author->email) ? $lv_comment->author->email : ''),
									'comment_author_url'   => (! empty ($lv_comment->author->website_url) ? $lv_comment->author->website_url : ''),
									'comment_content'      => $lv_comment->text,
									'comment_parent'       => ( ! empty ($lv_comment->parent_comment_token) ? oa_loudvoice_get_commentid_for_token ($lv_comment->parent_comment_token) : 0),
									'user_id'              => 0,
									'comment_author_IP'    => $lv_comment->ip_address,
									'comment_agent'        => 'Loudvoice/1.1 WordPress',
									'comment_date_gmt'     => date ('Y-m-d G:i:s', strtotime ($lv_comment->date_creation)),
									'comment_approved'     => oa_loudvoice_get_wordpress_approved_status ($lv_comment->moderation_status, $lv_comment->spam_status, $lv_comment->is_trashed) 
								);
								
								// Filter
								$wp_data = wp_filter_comment ($wp_data);
								
								// Insert WordPress Comment
								$commentid = wp_insert_comment ($wp_data);
								
								// Updated
								$result ['created'] [$commentid] = $lv_comment->comment_token;
								
								// Update Meta
								add_post_meta ($postid, '_oa_loudvoice_synchronized_comments_'.oa_loudvoice_uniqid(), $lv_comment->comment_token, false);
								update_post_meta ($postid, '_oa_loudvoice_synchronized_discussion_'.oa_loudvoice_uniqid(), $discussion_token);
								
								// Save Comment Meta
								update_comment_meta ($commentid, '_oa_loudvoice_synchronized_discussion_'.oa_loudvoice_uniqid(), $discussion_token);
								update_comment_meta ($commentid, '_oa_loudvoice_synchronized_comments_'.oa_loudvoice_uniqid(), $lv_comment->comment_token);

								// Debug
								oa_loudvoice_debug ($verbose, '  CREATED WordPress Comment #' . $commentid);
							}

							// Trash comment and add meta if comments wasn't refused in LoudVoice
							// So when comments are trashed in WP, you can restore them and keep approved status
							if ($lv_comment->is_trashed == 1 && $lv_comment->moderation_status != 'refused')
							{
								add_comment_meta( $commentid, '_wp_trash_meta_status', 1 );
								add_comment_meta( $commentid, '_wp_trash_meta_time', time() );
							}
								
						}
					}
					
					// Do we have several pages?
					if (!empty ($lv_comments->pagination->current_page) && !empty ($lv_comments->pagination->total_pages))
					{
						// Do we need to parse another page?
						if ($lv_comments->pagination->current_page < $lv_comments->pagination->total_pages)
						{
							$sub_result = oa_loudvoice_do_import_comments_for_discussion_token ($verbose, $discussion_token, ($lv_comments->pagination->current_page + 1), $entries_per_page);
							
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
			oa_loudvoice_debug ($verbose, ' No WordPress PostID found for discussion_token ' . $discussion_token);
		}
	}
	// Done
	return $result;
}

/**
 * Imports all comments from Loudvoice to WordPress
 */
function oa_loudvoice_do_import ($verbose, $page = 1, $entries_per_page = 50)
{
	// Result
	$result = array();
	
	// Is Loudvoice running?
	if (oa_louddvoice_is_setup ())
	{
		// Debug
		oa_loudvoice_debug ($verbose);
		oa_loudvoice_debug ($verbose, 'Importing Discussions, Page: ' . $page . ' / Entries Per Page: ' . $entries_per_page);
		
		// Make Request
		$api_result = oa_loudvoice_do_api_request_endpoint ('/loudvoice/discussions.json?page=' . $page . '&entries_per_page=' . $entries_per_page. '&realm=WP-'.oa_loudvoice_uniqid());

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
					oa_loudvoice_debug ($verbose, 'Importing Discussion ' . $lv_data->discussion_token);
					
					// Import Comments
					$result [$lv_data->discussion_token] = oa_loudvoice_do_import_comments_for_discussion_token ($verbose, $lv_data->discussion_token);
					
					// Debug
					oa_loudvoice_debug ($verbose);
				}
				
				// Do we have several pages?
				if (!empty ($lv_discussions->pagination->current_page) && !empty ($lv_discussions->pagination->total_pages))
				{
					// Do we need to parse another page?
					if ($lv_discussions->pagination->current_page < $lv_discussions->pagination->total_pages)
					{
						$result = $result + oa_loudvoice_do_import ($verbose, ($lv_discussions->pagination->current_page + 1), $entries_per_page);
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
function oa_loudvoice_import ($verbose = false)
{
	ob_start ();
	echo "\n[ === IMPORT LOG BELOW === ]\n";
	$result = oa_loudvoice_do_import (true);
	$verbose = ob_get_contents ();
	ob_end_clean ();
	
	// Display Result
	if (is_array ($result))
	{
		foreach ($result as $discussion_token => $discussion_data)
		{
			oa_loudvoice_debug ($verbose, "  Discussion " . $discussion_token);
			
			foreach ($discussion_data as $key => $data)
			{
				oa_loudvoice_debug ($verbose, "   " . ucwords ($key) . " " . count ($data) . " Comments");
			}
			oa_loudvoice_debug ($verbose);
		}
	}
	
	// Cleanup Meta
	oa_loudvoice_cleanup_post_comment_meta ();

	// Done
	return $result;
}

/**
 * Import all comments from WordPress to Loudvoice, Ajax Call
 */
function oa_loudvoice_import_ajax ()
{
	ob_start ();
	echo "\n[ === IMPORT LOG BELOW === ]\n\n";
	$result = oa_loudvoice_import (true);
	$verbose = ob_get_contents ();
	ob_end_clean ();
	
	// Statistics
	$num_discussions = 0;
	$num_comments = 0;
	
	// Compute Result
	if (is_array ($result))
	{
		foreach ($result as $postid => $post_data)
		{
		
			if(!empty($post_data['created']) || !empty($post_data['updated']) ){
				$num_discussions ++;
			}
			
			foreach ($post_data as $key => $data)
			{
				$num_comments += count ($data);
			}
		}
	}
	
	// Done
	die ('success|import_done|' . $num_discussions . ' post(s) and ' . $num_comments . ' comment(s) have been processed|' . $verbose);
}
add_action ('wp_ajax_oa_loudvoice_import', 'oa_loudvoice_import_ajax');


// ////////////////////////////////////////////////////////////////////////////////////////////////
// EXPORT
// ////////////////////////////////////////////////////////////////////////////////////////////////

/**
 * Exports all comments from WordPress to Loudvoice
 */
function oa_loudvoice_do_export ($verbose = false)
{
	// Global Vars
	global $wpdb;
	
	// Result
	$result = array();
	
	// Is Loudvoice running?
	if (oa_louddvoice_is_setup ())
	{
		// Read Published Posts
		$sql = "SELECT * FROM " . $wpdb->posts . " WHERE post_type = 'post' AND post_status = 'publish' ORDER BY ID ASC";
		$posts = $wpdb->get_results ($sql);
		
		// Loop through results
		if (is_array ($posts))
		{
			foreach ($posts as $post)
			{
				// Export Post
				$result [$post->ID] = oa_loudvoice_do_export_comments_for_postid ($verbose, $post->ID);
				
				// Debug
				oa_loudvoice_debug ($verbose);
			}
		}
	}
	
	// Done
	return $result;
}

/**
 * Exports all comments from WordPress to Loudvoice
 */
function oa_loudvoice_export ($verbose = false)
{
	// Export
	$result = oa_loudvoice_do_export ($verbose);
	
	// Cleanup Meta
	oa_loudvoice_cleanup_post_comment_meta ();
	
	// Done
	return $result;
}

/**
 * Exports all comments from WordPress to Loudvoice, Ajax Call
 */
function oa_loudvoice_export_ajax ()
{
	ob_start ();
	echo "\n[ === EXPORT LOG BELOW === ]\n\n";
	$result = oa_loudvoice_export (true);
	$verbose = ob_get_contents ();
	ob_end_clean ();
	
	// Statistics
	$num_discussions = 0;
	$num_comments = 0;
	
	// Compute Result
	if (is_array ($result))
	{
		foreach ($result as $postid => $post_data)
		{
			$num_discussions ++;
			
			foreach ($post_data as $key => $data)
			{
				$num_comments += count ($data);
			}
		}
	}
	
	// Done
	die ('success|export_done|' . $num_discussions . ' post(s) and ' . $num_comments . ' comment(s) have been processed|' . $verbose);
}
add_action ('wp_ajax_oa_loudvoice_export', 'oa_loudvoice_export_ajax');

/**
 * Exports all comments of the given post from WordPress to Loudvoice
 */
function oa_loudvoice_do_export_comments_for_postid ($verbose, $postid)
{
	global $wpdb;
	
	// Result
	$result = array(
		'created' => array(),
		'updated' => array() 
	);

	$wp_all_discussions = array('realm' => 'WP-'.oa_loudvoice_uniqid(), 'discussions' => array());
	
	// Is Loudvoice running?
	if (oa_louddvoice_is_setup ())
	{
		// Make sure we have a valid post
		if (($post = get_post ($postid)) !== null)
		{
			// Reference
			$discussion_reference = oa_loudvoice_get_reference_for_post ($post);
			
			// Debug
			oa_loudvoice_debug ($verbose, ' Exporting Post WP#' . $postid . ' / ' . $discussion_reference);
			
			// API Data
			$lv_data = array(
				'method' => 'PUT',
				'post_data' => json_encode (array(
					'request' => array(
						'discussion' => array(
							'realm'                             => 'WP-'.oa_loudvoice_uniqid(),
							'title'                             => oa_loudvoice_get_title_for_post ($post),
							'url'                               => oa_loudvoice_get_link_for_post ($post),
							'discussion_reference'              => $discussion_reference,
							'allow_create_discussion_reference' => true 
						) 
					) 
				)) 
			);
			
			// Push post to Loudvoice
			$api_result = oa_loudvoice_do_api_request_endpoint ('/loudvoice/discussions.json', $lv_data);
			
			// Check result (201: created, 200: already exists)
			if (is_object ($api_result) && property_exists ($api_result, 'http_code') && ($api_result->http_code == 200 || $api_result->http_code == 201))
			{
				// Debug
				oa_loudvoice_debug ($verbose, '  Post Exported (+' . ($api_result->http_code == 201 ? 'Created' : 'Updated') . ')');
				
				// Decode result
				$json = @json_decode ($api_result->http_data);
				
				// Make sure it's valid
				if (is_object ($json) and isset ($json->response->result->data->discussion))
				{
					// Discussion
					$lv_discussion = $json->response->result->data->discussion;
					
					// Token
					$discussion_token = $lv_discussion->discussion_token;

					//save discussion
					$wp_all_discussions['discussions'][$postid] = array(
						'reference' => $discussion_reference
					); 
					
					// Update Meta
					update_post_meta ($postid, '_oa_loudvoice_synchronized_discussion_'.oa_loudvoice_uniqid(), $discussion_token);
					
					// Now we synchronize the comments
					$sql = "SELECT * FROM " . $wpdb->comments . " WHERE comment_post_ID='" . $postid . "' AND comment_type != 'trackback' AND comment_type != 'pingback' ORDER BY comment_parent ASC";
					$wp_comments = $wpdb->get_results ($sql);
					
					// Comments found?
					if (is_array ($wp_comments))
					{
						// Cleanup synchronized comments
						delete_post_meta ($postid, '_oa_loudvoice_synchronized_comments_'.oa_loudvoice_uniqid());
						
						// Loop through comments
						foreach ($wp_comments as $wp_comment)
						{
							// Comment
							$commentid = $wp_comment->comment_ID;
							
							// Read the token
							$comment_token = oa_loudvoice_get_token_for_commentid ($commentid);

							//save discussion
							$wp_all_discussions['discussions'][$postid]['comments'][$commentid] = oa_loudvoice_get_comment_reference_for_comment ($wp_comment); 

							//save comments
							$wp_discussions[] = array('id' => $postid, 'reference' => $discussion_reference, 'token' => $discussion_token); 

				
							// Read the parent token
							if ( ! empty ($wp_comment->comment_parent))
							{
								$parent_comment_token = oa_loudvoice_get_token_for_commentid ($wp_comment->comment_parent);
								
								// Debug
								oa_loudvoice_debug ($verbose, '    Parent Comment Token: '.$parent_comment_token);
								
							}
							// No parent
							else
							{
								$parent_comment_token = '';
							}
							
							// Debug
							oa_loudvoice_debug ($verbose, '   Exporting Comment WP#' . $commentid);
							
							// Make sure the token is valid
							if (!empty ($comment_token))
							{
								// Read Comment
								$api_result = oa_loudvoice_do_api_request_endpoint ('/loudvoice/comments/' . $comment_token . '.json');
								if (is_object ($api_result) && property_exists ($api_result, 'http_code'))
								{
									// Comment Does not exist
									if ($api_result->http_code == 404)
									{
										// Debug
										oa_loudvoice_debug ($verbose, '   Orphan Comment Token Found (+Meta Removed)');
										
										// Remove Comment Meta
										delete_comment_meta ($commentid, '_oa_loudvoice_synchronized_discussion_'.oa_loudvoice_uniqid());
										delete_comment_meta ($commentid, '_oa_loudvoice_synchronized_comments_'.oa_loudvoice_uniqid());
										
										// Reset Token
										$comment_token = null;
									}
								}
							}
							
							// API Data
							$lv_data = array(
								'method' => 'PUT',
								'post_data' => json_encode (array(
									'request' => array(
										'discussion' => array(
											'discussion_token' => $discussion_token 
										),
										'comment' => array(
											'realm'                           => 'WP-'.oa_loudvoice_uniqid(),
											'parent_comment_token'            => $parent_comment_token,
											'comment_token'                   => $comment_token,
											'comment_reference'               => oa_loudvoice_get_comment_reference_for_comment ($wp_comment),
											'allow_create_comment_reference'  => true,
											'allow_create_duplicate_comments' => true,
											'moderation_status'               => oa_loudvoice_get_moderation_status_for_comment ($wp_comment),
											'spam_status'                     => oa_loudvoice_get_spam_status_for_comment ($wp_comment),
											'is_trashed'                      => oa_loudvoice_get_is_trashed_status_for_comment ($wp_comment),
											'text'                            => $wp_comment->comment_content,
											'author' => array(
												'author_reference' => oa_loudvoice_get_author_reference_for_comment ($wp_comment),
												'name'             => $wp_comment->comment_author,
												'email'            => $wp_comment->comment_author_email,
												'website_url'      => $wp_comment->comment_author_url,
												'picture_url'      => oa_loudvoice_get_avatar_url ($wp_comment->user_id, $wp_comment->comment_author_email),
												'ip_address'       => $wp_comment->comment_author_IP 
											) 
										) 
									) 
								)) 
							);
							
							// Push post to Loudvoice
							$api_result = oa_loudvoice_do_api_request_endpoint ('/loudvoice/comments.json', $lv_data);

							// Check result (201: created, 200: already exists)
							if (is_object ($api_result) && property_exists ($api_result, 'http_code'))
							{
								if ($api_result->http_code == 200 || $api_result->http_code == 201)
								{
									// Decode result
									$json = @json_decode ($api_result->http_data);
									
									// Comment
									$comment_token = $json->response->result->data->comment->comment_token;
									
									// Debug
									oa_loudvoice_debug ($verbose, '    Comment Exported (+' . ($api_result->http_code == 201 ? 'Created' : 'Updated') . ')');
									oa_loudvoice_debug ($verbose, '    Comment Token: ' . $comment_token);
									
									// Save Post Meta
									add_post_meta ($postid, '_oa_loudvoice_synchronized_comments_'.oa_loudvoice_uniqid(), $comment_token, false);
									update_post_meta ($postid, '_oa_loudvoice_synchronized_discussion_'.oa_loudvoice_uniqid(), $discussion_token);
									
									// Save Comment Meta
									update_comment_meta ($commentid, '_oa_loudvoice_synchronized_discussion_'.oa_loudvoice_uniqid(), $discussion_token);
									update_comment_meta ($commentid, '_oa_loudvoice_synchronized_comments_'.oa_loudvoice_uniqid(), $comment_token);
									
									// Updated
									$result [($api_result->http_code == 201 ? 'created' : 'updated')] [$commentid] = $comment_token;
								}
								else
								{
									
									// Debug
									oa_loudvoice_debug ($verbose, '    Comment Export Error, Code ' . print_r($api_result,true));
								}
							}
							else
							{
								// Debug
								oa_loudvoice_debug ($verbose, '    API Communication Error');
							}
						}
					}
				}
			}
		}
	}


	// ********************
	// Remove/Trash old discussions/comments in LoudVoice
	// ********************

	// API Data
	$lv_data = array(
		'method' => 'DELETE',
		'post_data' => json_encode (array(
			'request' => $wp_all_discussions
		)) 
	);
	
	// Push post to Loudvoice
	$api_result = oa_loudvoice_do_api_request_endpoint ('/loudvoice/clean.json', $lv_data);

	// Check result
	if (is_object ($api_result) and property_exists ($api_result, 'http_code'))
	{				
		if ($api_result->http_code == 200)
		{
			// Decode result
			$json = @json_decode ($api_result->http_data);

			// Make sure it's valid
			if (is_object ($json) and isset ($json->response->result->data->nb_removed_discussion))
			{
				if ($json->response->result->data->nb_removed_discussion > 0 || $json->response->result->data->nb_removed_comments > 0){
					oa_loudvoice_debug ($verbose, '   Cleanup : discussions : ' . $json->response->result->data->nb_removed_discussion.' - comments : ' . $json->response->result->data->nb_removed_comments);
				}
			}

		}
	}


	// Done
	return $result;
}

// ////////////////////////////////////////////////////////////////////////////////////////////////
// SYNC HOOKS
// ////////////////////////////////////////////////////////////////////////////////////////////////

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
		// Arguments
		$postid =  (!empty ($_REQUEST ['postid']) ? intval (trim ($_REQUEST ['postid'])) : null);
		$comment_token = ((!empty ($_REQUEST ['comment_token']) && oa_loudvoice_is_valid_uuid ($_REQUEST ['comment_token'])) ? $_REQUEST ['comment_token'] : null);

		// We need both arguments
		if (!empty ($postid) && !empty ($comment_token))
		{
			// Pull Comment
			$api_result = oa_loudvoice_do_api_request_endpoint ('/loudvoice/comments/' . $comment_token . '.json');

			// Check result
			if (is_object ($api_result) and property_exists ($api_result, 'http_code'))
			{			
				if ($api_result->http_code == 200)
				{
					// Decode result
					$json = @json_decode ($api_result->http_data);

					// Make sure it's valid
					if (is_object ($json) and isset ($json->response->result->data->comment))
					{
						// Loudvoice Objects
						$lv_discussion = $json->response->result->data->discussion;
						$lv_comment = $json->response->result->data->comment;

						// Validate the references before doing anything else
						if ($lv_discussion->discussion_reference == oa_loudvoice_get_reference_for_post ($postid))
						{
							// Make sure the comment has not yet been synchronized							
							if (($commentid = oa_loudvoice_get_commentid_for_token ($comment_token)) == false)
							{
								// Prepare WordPress Comment
								$data = array(
									'comment_post_ID' => $postid,
									'comment_author' => $lv_comment->author->name,
									'comment_author_email' => $lv_comment->author->email,
									'comment_author_url' => '',
									'comment_content' => $lv_comment->text,
									'comment_parent' => ( ! empty ($lv_comment->parent_comment_token) ? oa_loudvoice_get_commentid_for_token ($lv_comment->parent_comment_token) : 0),
									'user_id' => 0,
									'comment_author_IP' => $lv_comment->ip_address,
									'comment_agent' => 'Loudvoice/1.1 WordPress',
									'comment_date_gmt' => date ('Y-m-d G:i:s', strtotime ($lv_comment->date_creation)),
									'comment_approved' => oa_loudvoice_get_wordpress_approved_status ($lv_comment->moderation_status, $lv_comment->spam_status, $lv_comment->is_trashed) 
								);
								
								// Filter
								$data = wp_filter_comment ($data);
								
								// Insert WordPress Comment
								$commentid = wp_insert_comment ($data);
								
								// Save Post Meta
								add_post_meta ($postid, '_oa_loudvoice_synchronized_comments_'.oa_loudvoice_uniqid(), $lv_comment->comment_token, false);
								update_post_meta ($postid, '_oa_loudvoice_synchronized_discussion_'.oa_loudvoice_uniqid(), $lv_discussion->discussion_token);
								
								// Save Comment Meta
								update_comment_meta ($commentid, '_oa_loudvoice_synchronized_discussion_'.oa_loudvoice_uniqid(), $lv_discussion->discussion_token);
								update_comment_meta ($commentid, '_oa_loudvoice_synchronized_comments_'.oa_loudvoice_uniqid(), $lv_comment->comment_token);
								
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
				}
				else
				{
					$status_message = 'error_invalid_result_code';
				}
			}
			else 
			{
				$status_message = 'error_communication_issue';
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
