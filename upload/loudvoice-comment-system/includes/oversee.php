<?php

/**
 * Triggered before a post is deleted.
 * Keeps the token, so that we can delete it during synchronization.
 */
function oa_loudvoice_watch_delete_post($postid)
{
    // Is LoudVoice running?
    if (oa_louddvoice_is_setup())
    {
        // Comment Token
        $post_token = oa_loudvoice_get_token_for_post($postid);
        if (!empty($post_token))
        {
            // Add the post to the list of posts to be removed
            oa_loudvoice_set_to_delete_for_post_token($post_token, true);
        }
    }
}
add_action('before_delete_post', 'oa_loudvoice_watch_delete_post', 10, 1);

/**
 * Triggered when a post is updated.
 * Marks the post, so that the update is forced during synchronization.
 */
function oa_loudvoice_watch_edit_post($postid, $post_after, $post_before)
{
    // Is LoudVoice running?
    if (oa_louddvoice_is_setup())
    {
        if (is_numeric($postid) && is_object($post_after) && is_object($post_before))
        {
            // Synchronization required?
            $force_sync = false;

            // Only watch these changes
            $statuses = array('post_title', 'post_status', 'comment_status');
            foreach ($statuses as $status)
            {
                if ($post_before->{$status} != $post_after->{$status})
                {
                    $force_sync = true;
                }
            }

            if ($force_sync)
            {
                oa_loudvoice_set_force_sync_for_post($postid, true);
            }
        }
    }
}
add_action('post_updated', 'oa_loudvoice_watch_edit_post', 10, 3);

/**
 * Triggered before a comment is deleted
 * Keeps the token, so that we can delete it during synchronization.
 */
function oa_loudvoice_watch_delete_comment($commentid)
{
    // Is LoudVoice running?
    if (oa_louddvoice_is_setup())
    {
        // Comment Token
        $comment_token = oa_loudvoice_get_token_for_comment($commentid);
        if (!empty($comment_token))
        {
            // Add the comment to the list of comments to be removed
            oa_loudvoice_set_to_delete_for_comment_token($comment_token, true);
        }
    }
}
add_action('delete_comment', 'oa_loudvoice_watch_delete_comment', 10, 2);

/**
 * Triggered when the comment text is updated
 * Marks the comment, so that the update is forced during synchronization.
 */
function oa_loudvoice_watch_edit_comment($commentid, $action = null)
{
    // Is LoudVoice running?
    if (oa_louddvoice_is_setup())
    {
        if (empty($action) || $action != 'delete')
        {
            oa_loudvoice_set_force_sync_for_comment($commentid, true);
        }
    }
}
add_action('edit_comment', 'oa_loudvoice_watch_edit_comment', 10, 2);
add_action('wp_set_comment_status', 'oa_loudvoice_watch_edit_comment', 10, 2);

/**
 * Triggered when a user logs out.
 * Removes the author_session.
 */
function oa_loudvoice_remove_author_session()
{
    // Is LoudVoice running?
    if (oa_louddvoice_is_setup())
    {
        // Read settings
        $settings = get_option('oa_loudvoice_settings');

        // Are author sessions enabled and do we have a valid user?
        if (empty($settings['disable_author_sessions']))
        {
            // Update User Meta
            $user = wp_get_current_user();
            if (is_object($user) && !empty($user->ID))
            {
                delete_user_meta($user->ID, OA_LOUDVOICE_AUTHOR_SESSION_TOKEN_KEY);
                delete_user_meta($user->ID, OA_LOUDVOICE_AUTHOR_SESSION_EXPIRE_KEY);
            }
        }
    }
}
add_action('clear_auth_cookie', 'oa_loudvoice_remove_author_session');

/**
 * Triggered when a user logs in.
 * Creates the author_session.
 */
function oa_loudvoice_create_author_session($user_login, $user)
{
    // Is LoudVoice running?
    if (oa_louddvoice_is_setup())
    {
        // Read settings
        $settings = get_option('oa_loudvoice_settings');

        // Are author sessions enabled and do we have a valid user?
        if (empty($settings['disable_author_sessions']) && is_object($user) && !empty($user->ID))
        {
            // Read Session Details
            $author_session_token = get_user_meta($user->ID, OA_LOUDVOICE_AUTHOR_SESSION_TOKEN_KEY, true);
            $author_session_expiration = get_user_meta($user->ID, OA_LOUDVOICE_AUTHOR_SESSION_EXPIRE_KEY, true);

            // Session not found or expired
            if (empty($author_session_token) || empty($author_session_expiration) || $author_session_expiration > time())
            {
                // API Data
                $data = array(
                    'method' => 'PUT',
                    'post_data' => json_encode(array(
                        'request' => array(
                            'author_session' => array(
                                'author' => array(
                                    'author_reference' => oa_loudvoice_get_author_reference_for_user($user),
                                    'allow_create_new' => true,
                                    'name' => $user->user_login,
                                    'email' => (!empty($user->user_email) ? $user->user_email : null),
                                    'website_url' => (!empty($user->user_url) ? $user->user_url : null),
                                    'picture_url' => oa_loudvoice_get_avatar_url($user->ID, $user->user_email),
                                    'ip_address' => oa_loudvoice_get_user_ip()))))));

                // Make Request
                $result = oa_loudvoice_do_api_request_endpoint('/loudvoice/authors/sessions.json', $data);

                // Check result
                if (is_object($result) and property_exists($result, 'http_code') and ($result->http_code == 200 or $result->http_code == 201))
                {
                    // Decode result
                    $json = @json_decode($result->http_data);

                    // Read Session Details
                    $author_session_token = $json->response->result->data->author_session->author_session_token;
                    $author_session_expiration = strtotime($json->response->result->data->author_session->date_expiration);

                    // Save Meta
                    update_user_meta($user->ID, OA_LOUDVOICE_AUTHOR_SESSION_TOKEN_KEY, $author_session_token);
                    update_user_meta($user->ID, OA_LOUDVOICE_AUTHOR_SESSION_EXPIRE_KEY, $author_session_expiration);
                }
            }
        }
    }
}
add_action('wp_login', 'oa_loudvoice_create_author_session', 99, 2);

/**
 * Triggered by LoudVoice when a comment is added.
 * Imports the comment to the WordPress database
 */
function oa_loudvoice_import_comment_ajax()
{
    // Check AJAX Nonce
    check_ajax_referer('oa_loudvoice_ajax_nonce');

    // Status Message
    $status_message = '';

    // Is Loudvoice running?
    if (oa_louddvoice_is_setup())
    {
        // Arguments
        $postid = (!empty($_REQUEST['postid']) ? intval(trim($_REQUEST['postid'])) : null);
        $comment_token = ((!empty($_REQUEST['comment_token']) && oa_loudvoice_is_valid_uuid($_REQUEST['comment_token'])) ? $_REQUEST['comment_token'] : null);

        // We need both arguments
        if (!empty($postid) && !empty($comment_token))
        {
            // Pull comment.
            $api_result = oa_loudvoice_do_api_request_endpoint('/loudvoice/comments/' . $comment_token . '.json');

            // Check result.
            if (is_object($api_result) && property_exists($api_result, 'http_code'))
            {
                if ($api_result->http_code == 200)
                {
                    // Decode result.
                    $json = @json_decode($api_result->http_data);

                    // Make sure it's valid.
                    if (is_object($json) and isset($json->response->result->data->comment))
                    {
                        // LoudVoice Comment.
                        $comment = $json->response->result->data->comment;

                        // LoudVoice Comment Discussion.
                        $discussion = $comment->discussion;

                        // Validate the references before doing anything else.
                        if ($discussion->discussion_reference == oa_loudvoice_get_reference_for_post($postid))
                        {
                            // Make sure the comment has not yet been synchronized.
                            if (($commentid = oa_loudvoice_get_commentid_for_token($comment_token)) == false)
                            {
                                // Prepare WordPress Comment
                                $data = array(
                                    'comment_post_ID' => $postid,
                                    'comment_author' => $comment->author->name,
                                    'comment_author_email' => $comment->author->email,
                                    'comment_author_url' => (!empty($comment->author->website_url) ? $comment->author->website_url : ''),
                                    'comment_content' => $comment->text,
                                    'comment_parent' => (!empty($comment->parent_comment_token) ? oa_loudvoice_get_commentid_for_token($comment->parent_comment_token) : 0),
                                    'user_id' => 0,
                                    'comment_author_IP' => $comment->ip_address,
                                    'comment_agent' => OA_LOUDVOICE_AGENT,
                                    'comment_date_gmt' => date('Y-m-d G:i:s', strtotime($comment->date_creation)),
                                    'comment_approved' => oa_loudvoice_wrap_status_for_lv_comment($comment, 'comment_approved'));

                                // Apply filters.
                                $data = wp_filter_comment($data);

                                // Insert WordPress comment.
                                $commentid = wp_insert_comment($data);

                                // Update comment meta.
                                oa_loudvoice_set_token_for_comment($commentid, $comment_token);
                                oa_loudvoice_set_time_sync_for_comment($commentid, time());

                                // Synchronized!
                                $status_message = 'success_comment_synchronized';
                            }
                            // Already synchronized.
                            else
                            {
                                $status_message = 'success_comment_already_synchronized';
                            }
                        }
                        // Invalid reference.
                        else
                        {
                            $status_message = 'error_invalid_post_reference';
                        }
                    }
                    // Invalid data format.
                    else
                    {
                        $status_message = 'error_invalid_data_format';
                    }
                }
                // Unknown HTTP result code.
                else
                {
                    $status_message = 'error_invalid_result_code';
                }
            }
            // Error during the communication.
            else
            {
                $status_message = 'error_communication_issue';
            }
        }
        // Invalid arguments.
        else
        {
            $status_message = 'error_invalid_arguments';
        }
    }
    // LoudVoice is not ready.
    else
    {
        $status_message = 'error_loudvoice_not_setup';
    }

    // Done
    echo $status_message;
    die();
}
add_action('wp_ajax_nopriv_oa_loudvoice_import_comment', 'oa_loudvoice_import_comment_ajax');
add_action('wp_ajax_oa_loudvoice_import_comment', 'oa_loudvoice_import_comment_ajax');