<?php

// Export (Admin)
add_action('wp_ajax_oa_loudvoice_export_posts', 'oa_loudvoice_export_posts_ajax');
add_action('wp_ajax_oa_loudvoice_export_comments', 'oa_loudvoice_export_comments_ajax');
add_action('wp_ajax_oa_loudvoice_export_cleanup', 'oa_loudvoice_export_cleanup_ajax');

// Import (Admin)
add_action('wp_ajax_oa_loudvoice_import_posts', 'oa_loudvoice_import_posts_ajax');
add_action('wp_ajax_oa_loudvoice_import_comments', 'oa_loudvoice_import_comments_ajax');
add_action('wp_ajax_oa_loudvoice_import_cleanup', 'oa_loudvoice_import_cleanup_ajax');


// /////////////////////////////////////////////////////////////////////////////////////////////////////////////
// EXPORT CLEANUP
// /////////////////////////////////////////////////////////////////////////////////////////////////////////////

/**
 * Deletes a single comment from LoudVoice.
 */
function oa_loudvoice_delete_comment_token(&$logs, $comment_token, $log_prefix = '      ')
{
    // Is Loudvoice running?
    if (oa_louddvoice_is_setup())
    {
        // Debug
        $logs[] = $log_prefix . 'Deleting Comment Token: ' . $comment_token;

        // API Data
        $lv_data = array(
            'method' => 'DELETE'
        );

        // Remove comments from Loudvoice
        $api_result = oa_loudvoice_do_api_request_endpoint('/loudvoice/comments/' . $comment_token . '.json?confirm_deletion=true', $lv_data);

        // Check result
        if (is_object($api_result) && property_exists($api_result, 'http_code'))
        {
            // 200: Deleted / 404: Does not exist
            if ($api_result->http_code == 200 || $api_result->http_code == 404)
            {
                // Success
                $logs[] = $log_prefix . 'Token Removed!';
                return true;
            }
            else
            {
                // Error
                $logs[] = $log_prefix . 'Unknown Result Code ' . $api_result->http_code;
            }
        }
        else
        {
            // Error
            $logs[] = $log_prefix . 'Export Error! Invalid JSON received';
        }
    }
    else
    {
        // Error
        $logs[] = $log_prefix . 'Export Error! LoudVoice is not setup correctly';
    }

    // Error
    return false;
}

/**
 * Deletes a single post from LoudVoice.
 */
function oa_loudvoice_delete_post_token(&$logs, $post_token, $log_prefix = '      ')
{
    // Is Loudvoice running?
    if (oa_louddvoice_is_setup())
    {
        // Debug
        $logs[] = $log_prefix . 'Deleting Post Token: ' . $post_token;

        // API Data
        $lv_data = array(
            'method' => 'DELETE'
        );

        // Remove discussion from Loudvoice
        $api_result = oa_loudvoice_do_api_request_endpoint('/loudvoice/discussions/' . $post_token . '.json?confirm_deletion=true', $lv_data);

        // Check result
        if (is_object($api_result) && property_exists($api_result, 'http_code'))
        {
            // 200: Deleted / 404: Does not exist
            if ($api_result->http_code == 200 || $api_result->http_code == 404)
            {
                // Success
                $logs[] = $log_prefix . 'Token Removed!';
                return true;
            }
            else
            {
                // Error
                $logs[] = $log_prefix . 'Unknown Result Code ' . $api_result->http_code;
            }
        }
        else
        {
            // Error
            $logs[] = $log_prefix . 'Export Error! Invalid JSON received';
        }
    }
    else
    {
        // Error
        $logs[] = $log_prefix . 'Export Error! LoudVoice is not setup correctly';
    }

    // Error
    return false;
}

/**
 * Exports Cleanup.
 */
function oa_loudvoice_export_cleanup_ajax()
{
    // Global Vars
    global $wpdb;

    // Check AJAX Nonce
    check_ajax_referer('oa_loudvoice_ajax_nonce');

    // Is Loudvoice running?
    if (oa_louddvoice_is_setup())
    {
        // Logs
        $logs = array();

        // Comment Tokens to delete
        $comment_tokens = oa_loudvoice_get_comment_tokens_to_delete();
        if (is_array($comment_tokens) && count($comment_tokens) > 0)
        {
            $logs[] = '[ === Cleaning Up Comment Tokens === ]';

            foreach ($comment_tokens as $comment_token)
            {
                $logs[] = '- Comment Token: ' . $comment_token;

                if (oa_loudvoice_delete_comment_token($logs, $comment_token) == true)
                {
                    oa_loudvoice_set_to_delete_for_comment_token($comment_token, false);
                }
            }
        }

        // Post Tokens to delete
        $post_tokens = oa_loudvoice_get_post_tokens_to_delete();
        if (is_array($post_tokens) && count($post_tokens) > 0)
        {
            $logs[] = '[ === Cleaning Up Post Tokens === ]';

            foreach ($post_tokens as $post_token)
            {
                $logs[] = '- Post Token: ' . $post_token;

                if (oa_loudvoice_delete_post_token($logs, $post_token) == true)
                {
                    oa_loudvoice_set_to_delete_for_post_token($post_token, false);
                }
            }
        }

        // Done
        $logs[] = '[ === Cleanup Processed === ]';

        // Finished
        die('success|complete||' . implode("\n", $logs) . "\n");
    }
    else
    {
        die('error|||[ !!! Error: LoudVoice is not setup !!! ]');
    }
}

// /////////////////////////////////////////////////////////////////////////////////////////////////////////////
// EXPORT POSTS
// /////////////////////////////////////////////////////////////////////////////////////////////////////////////

/**
 * Exports a single post
 */
function oa_loudvoice_export_postid(&$logs, $postid, $log_prefix = '      ')
{
    global $wpdb;

    // Is Loudvoice running?
    if (oa_louddvoice_is_setup())
    {
        // Debug
        $logs[] = $log_prefix . 'Exporting Post WP# ' . $postid . ' ...';

        // Make sure we have a valid post
        if (($post = get_post($postid)) !== null)
        {
            $discussion_token = oa_loudvoice_get_token_for_post($post->ID);
            $last_synchronized = oa_loudvoice_get_time_sync_for_post($post->ID);
            $force_sync = oa_loudvoice_get_force_sync_for_post($post->ID);

            // Debug
            $logs[] = $log_prefix . '- Last Synchronized: ' . (empty($last_synchronized) ? 'Never' : date("d.m.Y G:i", $last_synchronized));

            // Check if it was already exported
            if (empty($discussion_token) || $force_sync)
            {
                // Forced
                if ($force_sync)
                {
                    $logs[] = $log_prefix . '- Post updated in WordPress, synchronization forced';
                }

                // API Data
                $lv_data = array(
                    'method' => 'PUT',
                    'post_data' => json_encode(array(
                        'request' => array(
                            'discussion' => array(
                                'realm' => oa_loudvoice_get_realm(),
                                'title' => oa_loudvoice_get_title_for_post($post),
                                'url' => oa_loudvoice_get_link_for_post($post),
                                'is_closed' => oa_loudvoice_get_is_closed_for_post ($post),
                                'is_trashed' => oa_loudvoice_get_is_trashed_for_post ($post),
                                'discussion_reference' => oa_loudvoice_get_reference_for_post($post),
                                'allow_create_discussion_reference' => true
                            )
                        )
                    ))
                );

                // Push post to Loudvoice
                $api_result = oa_loudvoice_do_api_request_endpoint('/loudvoice/discussions.json', $lv_data);

                // Check result (201: created, 200: already exists)
                if (is_object($api_result) && property_exists($api_result, 'http_code'))
                {
                    if ($api_result->http_code == 200 || $api_result->http_code == 201)
                    {
                        // Decode result
                        $json = @json_decode($api_result->http_data);

                        // Make sure it's valid
                        if (is_object($json) and isset($json->response->result->data->discussion))
                        {
                            // Grab Token
                            $discussion_token = $json->response->result->data->discussion->discussion_token;

                            // Success
                            $logs[] = $log_prefix . '- Discussion Token: ' . $discussion_token;
                            $logs[] = $log_prefix . 'Exported And ' . ($api_result->http_code == 201 ? 'Created' : 'Updated');

                            // Update Meta
                            oa_loudvoice_set_token_for_post($post->ID, $discussion_token);
                            oa_loudvoice_set_time_sync_for_post($post->ID, time());
                            oa_loudvoice_set_force_sync_for_post($post->ID, false);

                            // Done
                            return $discussion_token;
                        }
                        else
                        {
                            // Error
                            $logs[] = $log_prefix . '- Discussion Token: ' . $discussion_token;
                            $logs[] = $log_prefix . 'Export Error: Invalid JSON received';
                        }
                    }
                    else
                    {
                        // Error
                        $logs[] = $log_prefix . '- Discussion Token: ' . $discussion_token;
                        $logs[] = $log_prefix . 'Export Error! Unknown Code ' . $api_result->http_code;
                    }
                }
                else
                {
                    // Error
                    $logs[] = $log_prefix . '- Discussion Token: ' . $discussion_token;
                    $logs[] = $log_prefix . 'Export Error! Invalid JSON received';
                }
            }
            else
            {
                // Success
                $logs[] = $log_prefix . '- Discussion Token: ' . $discussion_token;
                $logs[] = $log_prefix . 'Already Synchronized!';

                // Done
                return $discussion_token;
            }
        }
        else
        {
            // Error
            $logs[] = $log_prefix . 'Export Error! Post identifier #' . $postid . ' is invalid';
        }
    }
    else
    {
        // Error
        $logs[] = $log_prefix . 'Export Error! LoudVoice is not setup correctly';
    }
}

/**
 * Exports all comments from WordPress to Loudvoice
 */
function oa_loudvoice_export_posts_ajax()
{
    // Global Vars
    global $wpdb;

    // Logs
    $logs = array();

    // Check AJAX Nonce
    check_ajax_referer('oa_loudvoice_ajax_nonce');

    // Is Loudvoice running?
    if (oa_louddvoice_is_setup())
    {
        // Posts pagination
        $last_postid = ((isset($_REQUEST['last_id']) && is_numeric($_REQUEST['last_id'])) ? $_REQUEST['last_id'] : null);

        // Read posts (We also take trashed posts, so that we can update their status in LoudVoice)
        $sql = "SELECT * FROM " . $wpdb->posts . " WHERE post_status IN ('trash', 'publish') AND post_type IN ('post', 'page')";

        // Pagination
        if (is_numeric($last_postid))
        {
            $sql .= " AND ID > '" . intval($last_postid) . "'";
        }

        // Ordering
        $sql .= " ORDER by ID ASC LIMIT " . OA_LOUDVOICE_EXPORT_POSTS_STEP;

        // Reset last post
        $last_postid = null;

        // Loop through results
        $posts = $wpdb->get_results($sql);
        if (is_array($posts))
        {
            foreach ($posts as $post)
            {
                $logs[] = '[ === Processing Post WP#' . $post->ID . ' === ]';

                // Process
                $discussion_token = oa_loudvoice_export_postid($logs, $post->ID);

                // Last processed post
                $last_postid = $post->ID;

                // Throttle
                if ( defined ('OA_LOUDVOICE_EXPORT_POSTS_THROTTLE') && is_numeric (OA_LOUDVOICE_EXPORT_POSTS_THROTTLE))
                {
                    usleep(OA_LOUDVOICE_EXPORT_POSTS_THROTTLE);
                }
            }
        }

        // More posts left
        if (!empty($last_postid))
        {
            // Partial Success
            die('success|partial|' . $last_postid . '|' . implode("\n", $logs) . "\n");
        }
        // Mo more posts left
        else
        {
            // Complete Succes
            $logs[] = '[ === Posts Exported === ]';
            die('success|complete||' . implode("\n", $logs) . "\n");
        }
    }
    else
    {
        die('error|||[ !!! Error: LoudVoice is not setup !!! ]');
    }
}

// /////////////////////////////////////////////////////////////////////////////////////////////////////////////
// EXPORT COMMENTS
// /////////////////////////////////////////////////////////////////////////////////////////////////////////////

/**
 * Exports a single comment from WordPress to Loudvoice
 */
function oa_loudvoice_export_commentid(&$logs, $commentid, $log_prefix = '      ')
{
    global $wpdb;

    // Is Loudvoice running?
    if (oa_louddvoice_is_setup())
    {
        // Debug
        $logs[] = $log_prefix . 'Exporting Comment WP#' . $commentid;

        // Make sure we have a valid comment
        if (($comment = get_comment($commentid)) !== null)
        {
            $comment_token = oa_loudvoice_get_token_for_comment($comment->comment_ID);
            $last_synchronized = oa_loudvoice_get_time_sync_for_comment($comment->comment_ID);
            $force_sync = oa_loudvoice_get_force_sync_for_comment($comment->comment_ID);

            // Debug
            $logs[] = $log_prefix . '- Last Synchronized: ' . (empty($last_synchronized) ? 'Never' : date("d.m.Y G:i", $last_synchronized));

            // Check if we need to synchronize it
            if (empty($comment_token) || empty ($last_synchronized) || $force_sync)
            {
                // Forced
                if ($force_sync)
                {
                    $logs[] = $log_prefix . '- Comment updated in WordPress, synchronization forced';
                }

                // Get discussion token
                $discussion_token = oa_loudvoice_get_token_for_post($comment->comment_post_ID);

                // Discussion not found
                if (empty($discussion_token))
                {
                    // Export Discussion
                    $discussion_token = oa_loudvoice_export_postid($logs, $comment->comment_post_ID);

                    // Debug
                    $logs[] = $log_prefix . '- Discussion Token Created: ' . $discussion_token;
                }
                else
                {
                    // Debug
                    $logs[] = $log_prefix . '- Discussion Token Found: ' . $discussion_token;
                }

                // Read the parent token
                if (!empty($commment->comment_parent))
                {
                    // Token of the parent comment
                    $parent_comment_token = oa_loudvoice_get_token_for_comment($comment->comment_parent);

                    // Debug
                    $logs[] = $log_prefix . '- Parent Comment: ' . $comment->comment_post_ID;
                    $logs[] = $log_prefix . '- Parent Token: ' . (empty($parent_comment_token) ? 'None' : $parent_comment_token);
                }
                // No parent
                else
                {
                    // Token of the parent comment
                    $parent_comment_token = '';

                    // Debug
                    $logs[] = $log_prefix . '- Parent Comment: None';
                }

                // API Data
                $lv_data = array(
                    'method' => 'PUT',
                    'post_data' => json_encode(array(
                        'request' => array(
                            'discussion' => array(
                                'discussion_token' => $discussion_token
                            ),
                            'comment' => array(
                                'realm' => oa_loudvoice_get_realm(),
                                'parent_comment_token' => $parent_comment_token,
                                'comment_token' => $comment_token,
                                'comment_reference' => oa_loudvoice_get_comment_reference_for_comment($comment),
                                'allow_create_comment_reference' => true,
                                'allow_duplicate_comments' => true,
                                'is_spam' => oa_loudvoice_wrap_status_for_wp_comment ($comment, 'is_spam'),
                                'is_trashed' => oa_loudvoice_wrap_status_for_wp_comment ($comment, 'is_trashed'),
                                'has_been_approved' => oa_loudvoice_wrap_status_for_wp_comment ($comment, 'has_been_approved'),
                                'date_last_update' => $last_synchronized,
                                'text' => $comment->comment_content,
                                'author' => array(
                                    'author_reference' => oa_loudvoice_get_author_reference_for_comment($comment),
                                    'name' => $comment->comment_author,
                                    'email' => $comment->comment_author_email,
                                    'website_url' => $comment->comment_author_url,
                                    'picture_url' => oa_loudvoice_get_avatar_url($comment->user_id, $comment->comment_author_email),
                                    'ip_address' => $comment->comment_author_IP
                                )
                            )
                        )
                    ))
                );

                // Push post to Loudvoice
                $api_result = oa_loudvoice_do_api_request_endpoint('/loudvoice/comments.json', $lv_data);

                // Check result
                if (is_object($api_result) && property_exists($api_result, 'http_code'))
                {
                    // 201: created, 200: already exists
                    if ($api_result->http_code == 200 || $api_result->http_code == 201)
                    {
                        // Decode result
                        $json = @json_decode($api_result->http_data);

                        // Make sure it's valid
                        if (is_object($json) and isset($json->response->result->data->comment))
                        {
                            // Grab Token
                            $comment_token = $json->response->result->data->comment->comment_token;

                            // Debug
                            $logs[] = $log_prefix . '- Comment Token: ' . $comment_token;
                            $logs[] = $log_prefix . 'Exported And ' . ($api_result->http_code == 201 ? 'Created' : 'Updated');

                            // Update Meta
                            oa_loudvoice_set_token_for_comment($comment->comment_ID, $comment_token);
                            oa_loudvoice_set_time_sync_for_comment($comment->comment_ID, time());
                            oa_loudvoice_set_force_sync_for_comment($comment->comment_ID, false);

                            // Done
                            return $comment_token;
                        }
                        else
                        {
                            // Error
                            $logs[] = $log_prefix . '- Comment Token: ' . $comment_token;
                            $logs[] = $log_prefix . 'Export Error: Invalid JSON received';
                        }
                    }
                    // 404: token not found
                    elseif ($api_result->http_code == 404)
                    {
                        // Decode result
                        $json = @json_decode($api_result->http_data);

                        // Recreate Discussion?
                        $recreate_discussion = false;

                        // Make sure it's valid
                        if (is_object($json) && isset($json->response->request->status->info))
                        {
                            // The node [request->discussion->discussion_token] is invalid, no discussion has been found for that token
                            if (stripos($json->response->request->status->info, 'no discussion has been found') !== false)
                            {
                                $recreate_discussion = true;
                            }
                        }

                        // Error
                        $logs[] = $log_prefix . '- Comment Token: ' . $comment_token;

                        // Recreate discussion
                        if ($recreate_discussion)
                        {
                            $logs[] = $log_prefix . 'Invalid Discussion Token! Recreating Token.';

                            // Remove Meta
                            oa_loudvoice_set_token_for_post($comment->comment_post_ID, null);
                            oa_loudvoice_set_time_sync_for_post($comment->comment_post_ID, null);

                            // Retry
                            return oa_loudvoice_export_commentid($logs, $comment->comment_ID, $log_prefix.'  ');
                        }
                        else
                        {
                            $logs[] = $log_prefix . 'Invalid Comment Token! Recreating Token.';

                            // Remove Meta
                            oa_loudvoice_set_token_for_comment($comment->comment_ID, null);
                            oa_loudvoice_set_time_sync_for_comment($comment->comment_ID, null);

                            // Retry
                            return oa_loudvoice_export_commentid($logs, $comment->comment_ID, $log_prefix.'  ');
                        }
                    }
                    else
                    {
                        // Error
                        $logs[] = $log_prefix . '- Comment Token: ' . $comment_token;
                        $logs[] = $log_prefix . 'Export Error! Unknown Code ' . $api_result->http_code;
                    }
                }
                else
                {
                    // Error
                    $logs[] = $log_prefix . '- Comment Token: ' . $comment_token;
                    $logs[] = $log_prefix . 'Export Error! Invalid JSON received';
                }
            }
            else
            {
                // Success
                $logs[] = $log_prefix . '- Comment Token: ' . $comment_token;
                $logs[] = $log_prefix . 'Already Synchronized!';

                // Done
                return $comment_token;
            }
        }
        else
        {
            // Error
            $logs[] = $log_prefix . 'Export Error! Comment identifier #' . $commentid . ' is invalid';
        }
    }
    else
    {
        // Error
        $logs[] = $log_prefix . 'Export Error! LoudVoice is not setup correctly';
    }
}

/**
 * Exports all comments from WordPress to Loudvoice, Ajax Call
 */
function oa_loudvoice_export_comments_ajax()
{
    // Global Vars
    global $wpdb;

    // Logs
    $logs = array();

    // Check AJAX Nonce
    check_ajax_referer('oa_loudvoice_ajax_nonce');

    // Is Loudvoice running?
    if (oa_louddvoice_is_setup())
    {
        // Comments pagination
        $last_commentid = ((isset($_REQUEST['last_id']) && is_numeric($_REQUEST['last_id'])) ? $_REQUEST['last_id'] : null);

        // Update time
        update_option('oa_loudvoice_api_last_export', time());

        // Read comments
        $sql = "SELECT * FROM " . $wpdb->comments . " WHERE comment_type != 'trackback' AND comment_type != 'pingback'";

        // Pagination
        if (is_numeric($last_commentid))
        {
            $sql .= " AND comment_ID > '" . intval($last_commentid) . "'";
        }

        // Ordering
        $sql .= " ORDER by comment_ID ASC LIMIT " . OA_LOUDVOICE_EXPORT_COMMENTS_STEP;

        // Reset last comment
        $last_commentid = null;

        // Loop through results
        $comments = $wpdb->get_results($sql);
        if (is_array($comments))
        {
            foreach ($comments as $comment)
            {
                $logs[] = '[ === Processing Comment WP#' . $comment->comment_ID . ' === ]';

                // Process
                $comment_token = oa_loudvoice_export_commentid($logs, $comment->comment_ID);

                // Last processed comment
                $last_commentid = $comment->comment_ID;

                // Throttle
                if ( defined ('OA_LOUDVOICE_EXPORT_COMMENTS_THROTTLE') && is_numeric (OA_LOUDVOICE_EXPORT_COMMENTS_THROTTLE))
                {
                    usleep(OA_LOUDVOICE_EXPORT_COMMENTS_THROTTLE);
                }
            }
        }

        // More posts left
        if (!empty($last_commentid))
        {
            die('success|partial|' . $last_commentid . '|' . implode("\n", $logs) . "\n");
        }
        // Mo more comments left
        else
        {
            // Add Log
            $logs[] = '[ === Comments Exported, Unique ID '.oa_loudvoice_uniqid().' === ]';

            // Finished
            die('success|complete||' . implode("\n", $logs) . "\n");
        }
    }
    else
    {
        die('error|||[ !!! Error: LoudVoice is not setup !!! ]');
    }
}

// /////////////////////////////////////////////////////////////////////////////////////////////////////////////
// IMPORT POSTS
// /////////////////////////////////////////////////////////////////////////////////////////////////////////////

/**
 * Cleanup after import
 */
function oa_loudvoice_import_cleanup_ajax()
{
    die('success|complete||');
}

/**
 * Import all posts from LoudVoice to WordPress
 */
function oa_loudvoice_import_posts_ajax()
{
    die('success|complete||');
}

/**
 * Import all comments from LoudVoice to WordPress
 */
function oa_loudvoice_import_comments_ajax()
{
    // Global Vars
    global $wpdb;

    // Logs
    $logs = array();

    // Check AJAX Nonce
    check_ajax_referer('oa_loudvoice_ajax_nonce');

    // Is Loudvoice running?
    if (oa_louddvoice_is_setup())
    {
        // Posts pagination
        $page = ((isset($_REQUEST['last_id']) && is_numeric($_REQUEST['last_id'])) ? ($_REQUEST['last_id'] + 1) : 1);

        // Update time
        update_option('oa_loudvoice_api_last_import', time());

        // Next page
        $next_page = null;

        // Read comments
        $api_result = oa_loudvoice_do_api_request_endpoint('/loudvoice/comments.json?page=' . $page . '&entries_per_page=' . OA_LOUDVOICE_IMPORT_COMMENTS_STEP);

        // Check result
        if (is_object($api_result) && property_exists($api_result, 'http_code') && $api_result->http_code == 200)
        {
            // Decode result
            $json = @json_decode($api_result->http_data);

            // Make sure it's valid
            if (is_object($json) && isset($json->response->result->data->comments))
            {
                // Check if more pages need to be processed afterwards
                if ($json->response->result->data->comments->pagination->current_page < $json->response->result->data->comments->pagination->total_pages)
                {
                    $next_page = $page;
                }

                // Comments
                $comments = $json->response->result->data->comments->entries;

                // Import
                if (is_array($comments))
                {
                    foreach ($comments as $comment)
                    {
                        // Debug
                        $logs[] = '[ === Processing Comment Token ' . $comment->comment_token . ' === ]';

                        // Comment found in database
                        if (($commentid = oa_loudvoice_get_commentid_for_token($comment->comment_token)) !== false)
                        {
                            $logs[] = '     - WordPress Comment Found WP#' . $commentid;

                            // Read comment data
                            if (($wp_data = get_comment($commentid, 'ARRAY_A')) !== null)
                            {
                                // Setup Fields
                                $wp_data['comment_content'] = $comment->text;
                                $wp_data['comment_approved'] = oa_loudvoice_wrap_status_for_lv_comment($comment, 'comment_approved');
                                $wp_data['comment_parent'] = (!empty($comment->parent_comment_token) ? oa_loudvoice_get_commentid_for_token($comment->parent_comment_token) : 0);
                                $wp_data = wp_filter_comment($wp_data);

                                // Update Comment
                                wp_update_comment($wp_data);

                                // Update Meta
                                oa_loudvoice_set_time_sync_for_comment($commentid, time());
                                oa_loudvoice_set_force_sync_for_comment($commentid, false);

                                // Log
                                $logs[] = '     - Existing comment updated';
                            }
                            // Invalid Comment Identifier
                            else
                            {
                                // Log
                                $logs[] = '     - Comment WP#' . $commentid." does not exist, removing orphan token";

                                // Remove Meta
                                oa_loudvoice_set_token_for_comment ($commentid, null);
                                oa_loudvoice_set_time_sync_for_comment($commentid, null);
                                oa_loudvoice_set_force_sync_for_comment($commentid, false);

                                // Reset Comment Identifer
                                $commentid = null;
                            }
                        }


                        // Comment not found in database
                        if (empty ($commentid))
                        {
                            // Log
                            $logs[] = '     - No WordPress comment found';
                            $logs[] = '     - Trying to find post for Discussion Token '.$comment->discussion->discussion_token;

                            // Post found in database
                            if (($postid = oa_loudvoice_get_postid_for_token($comment->discussion->discussion_token)) !== false)
                            {
                                // Log
                                $logs[] = '     - Post WP# '.$postid.' found';
                                $logs[] = '     - Trying to find user for Reference '.$comment->author->author_reference;

                                // Comment Writer
                                $comment_user_id = 0;

                                // Try to lookup author
                                if (($userid = oa_loudvoice_get_userid_for_reference ($comment->author->author_reference)) !== false)
                                {
                                    if (($user_data = get_userdata ($userid)) !== false)
                                    {
                                       $comment_user_id = $user_data->ID;
                                    }
                                }

                                if (empty ($comment_user_id))
                                {
                                    $logs[] = '     - No user found, creating guest comment';
                                }
                                else
                                {
                                    $logs[] = '     - User WP#'. $comment_user_id.' found';
                                }

                                // Prepare WordPress Comment
                                $wp_data = array(
                                    'comment_post_ID' => $postid,
                                    'comment_author' => (!empty($comment->author->name) ? $comment->author->name : ''),
                                    'comment_author_email' => (!empty($comment->author->email) ? $comment->author->email : ''),
                                    'comment_author_url' => (!empty($comment->author->website_url) ? $comment->author->website_url : ''),
                                    'comment_content' => $comment->text,
                                    'comment_parent' => (!empty($comment->parent_comment_token) ? oa_loudvoice_get_commentid_for_token($comment->parent_comment_token) : 0),
                                    'user_id' => $comment_user_id,
                                    'comment_author_IP' => $comment->ip_address,
                                    'comment_agent' => OA_LOUDVOICE_AGENT,
                                    'comment_date_gmt' => date('Y-m-d G:i:s', strtotime($comment->date_creation)),
                                    'comment_approved' => oa_loudvoice_wrap_status_for_lv_comment($comment, 'comment_approved')
                                );

                                // Filter
                                $wp_data = wp_filter_comment($wp_data);

                                // Add Comment
                                $commentid = wp_insert_comment($wp_data);

                                // Update Meta
                                oa_loudvoice_set_token_for_comment($commentid, $comment->comment_token);
                                oa_loudvoice_set_time_sync_for_comment($commentid, time());
                                oa_loudvoice_set_force_sync_for_comment($commentid, false);

                                // Log
                                $logs[] = '     - Added comment WP #' . $commentid;
                            }
                            // Post not found
                            else
                            {
                                // Log
                                $logs[] = '     - No post found, skipping import of this comment.';
                            }
                        }
                    }
                }
            }
        }
        else
        {
            if (isset ($api_result->http_code) && in_array ($api_result->http_code, array (404, 401)))
            {
                $logs[] = $log_prefix . 'Export Error! Please check the API setup in the LoudVoice settings';
            }
            else
            {
                // Error
                $logs[] = $log_prefix . 'Export Error! LoudVoice is not setup correctly. Error Code #'.$api_result->http_code ;
            }
        }

        // More comments left
        if (!empty($next_page))
        {
            // Partial Success
            die('success|partial|' . $next_page . '|' . implode("\n", $logs) . "\n");
        }
        // Mo more posts left
        else
        {
            // Complete Succes
            $logs[] = '[ === Comments Imported, Unique ID '.oa_loudvoice_uniqid().' === ]';
            die('success|complete||' . implode("\n", $logs) . "\n");
        }
    }
    else
    {
        die('error|||[ !!! Error: LoudVoice is not setup !!! ]');
    }
}
