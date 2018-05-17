<?php

/**
 * Get date of last export.
 */
function oa_louddvoice_last_export_date()
{
    return get_option('oa_loudvoice_api_last_export');
}

/**
 * Get date of last import.
 */
function oa_louddvoice_last_import_date()
{
    return get_option('oa_loudvoice_api_last_import');
}


/**
 * Returns the avatar for a given userid.
 */
function oa_loudvoice_get_avatar_url($userid, $email)
{
    $avatar_url = null;

    // Read Avatar
    if (!empty($userid) || !empty($email))
    {
        $avatar_from = (empty($userid) ? $email : $userid);
        $avatar_html = get_avatar($avatar_from);

        // Extract src
        if (preg_match("/src\s*=\s*(['\"]{1})(.*?)\\1/i", $avatar_html, $matches))
        {
            $avatar_url = trim($matches[2]);
        }
    }

    // Error
    return $avatar_url;
}

/**
 * Return an author reference for a user/userid.
 */
function oa_loudvoice_get_author_reference_for_user($mixed)
{
    // Either user or userid can be specified
    $userid = (is_object($mixed) ? $mixed->ID : $mixed);
    if (!empty($userid))
    {
        return 'WP-' . oa_loudvoice_uniqid() . '-USER-' . intval(trim($userid));
    }

    // Error
    return null;
}

/**
 * **************************************************************************************************************
 * * *************************************************** POST ***************************************************
 * **************************************************************************************************************
 */

/**
 * Returns the postid for a given token.
 */
function oa_loudvoice_get_postid_for_token($token)
{
    global $wpdb;

    // The token is mandatory.
    $token = trim(strval($token));
    if (strlen($token) > 0)
    {
        // Read post_id for this token.
        $sql = "SELECT pm.post_id FROM " . $wpdb->postmeta . " AS pm INNER JOIN " . $wpdb->posts . " AS p ON (pm.post_id=p.ID) WHERE pm.meta_key=%s AND pm.meta_value=%s";
        $postid = $wpdb->get_var($wpdb->prepare($sql, OA_LOUDVOICE_TOKEN_KEY, $token));

        // Make sure we have a result
        if (!empty($postid) && is_numeric($postid))
        {
            return $postid;
        }
    }

    // Error
    return false;
}

/**
 * Returns the reference of a post/postid.
 */
function oa_loudvoice_get_reference_for_post($mixed)
{
    // Either post or postid can be specified
    $postid = (is_object($mixed) ? $mixed->ID : $mixed);
    if (!empty($postid))
    {
        return 'WP-' . oa_loudvoice_uniqid() . '-POST-' . intval(trim($postid));
    }

    // Error
    return null;
}

/**
 * Returns the post tokens to be deleted.
 */
function oa_loudvoice_get_post_tokens_to_delete()
{
    // Read
    $option = get_option(OA_LOUDVOICE_REMOVE_POSTS_KEY, '');

    // Decode and return
    return (!empty($option) ? explode(';', $option) : array());
}

/**
 * Sets the to delete flag of a post_token.
 */
function oa_loudvoice_set_to_delete_for_post_token($post_token, $to_delete)
{
    // Read
    $option = get_option(OA_LOUDVOICE_REMOVE_POSTS_KEY, '');

    // Decode
    $post_tokens = (!empty($option) ? explode(';', $option) : array());

    // Delete this one
    if ($to_delete == true)
    {
        if (!in_array($post_token, $post_tokens))
        {
            $post_tokens[] = $post_token;
        }
    }
    // Do not delete this one
    else
    {
        if (($key = array_search($post_token, $post_tokens)) !== false)
        {
            unset($post_tokens[$key]);
        }
    }

    // Nothing left
    if (!is_array($post_tokens) || count($post_tokens) == 0)
    {
        delete_option(OA_LOUDVOICE_REMOVE_POSTS_KEY);
    }
    // Update
    else
    {
        update_option(OA_LOUDVOICE_REMOVE_POSTS_KEY, implode(';', $post_tokens));
    }

    // Done
    return $post_tokens;
}

/**
 * Returns the synchronization force flag of a post/postid.
 */
function oa_loudvoice_get_force_sync_for_post($mixed)
{
    // Post Identifier
    $postid = (is_object($mixed) ? $mixed->ID : $mixed);
    if (!empty($postid))
    {
        if (get_post_meta($postid, OA_LOUDVOICE_FORCE_SYNC_KEY, true) == 1)
        {
            return true;
        }
    }

    // Do not force
    return false;
}

/**
 * Sets the synchronization force flag of a post/postid.
 */
function oa_loudvoice_set_force_sync_for_post($mixed, $do_force_sync)
{
    // Post Identifier
    $postid = (is_object($mixed) ? $mixed->ID : $mixed);
    if (!empty($postid))
    {
        // Remove
        if ($do_force_sync !== true)
        {
            return delete_post_meta($postid, OA_LOUDVOICE_FORCE_SYNC_KEY);
        }
        // Update
        else
        {
            return update_post_meta($postid, OA_LOUDVOICE_FORCE_SYNC_KEY, 1);
        }
    }

    // Error
    return false;
}

/**
 * Sets the token of a post/postid.
 */
function oa_loudvoice_set_token_for_post($mixed, $token)
{
    // Post Identifier
    $postid = (is_object($mixed) ? $mixed->ID : $mixed);
    if (!empty($postid))
    {
        if (empty($token))
        {
            return delete_post_meta($postid, OA_LOUDVOICE_TOKEN_KEY);
        }
        else
        {
            return update_post_meta($postid, OA_LOUDVOICE_TOKEN_KEY, $token);
        }
    }

    // Error
    return null;
}

/**
 * Returns the token for a post/postid.
 */
function oa_loudvoice_get_token_for_post($mixed)
{
    // Post Identifier
    $postid = (is_object($mixed) ? $mixed->ID : $mixed);
    if (!empty($postid))
    {
        return get_post_meta($postid, OA_LOUDVOICE_TOKEN_KEY, true);
    }

    // Error
    return null;
}

/**
 * Sets the synhronization time of a post/postid.
 */
function oa_loudvoice_set_time_sync_for_post($mixed, $time)
{
    // Post Identifier
    $postid = (is_object($mixed) ? $mixed->ID : $mixed);
    if (!empty($postid))
    {
        if (empty($time))
        {
            return delete_post_meta($postid, OA_LOUDVOICE_TIME_SYNC_KEY);
        }
        else
        {
            return update_post_meta($postid, OA_LOUDVOICE_TIME_SYNC_KEY, $time);
        }
    }

    // Error
    return null;
}

/**
 * Returns the synchronization time of a post/postid.
 */
function oa_loudvoice_get_time_sync_for_post($mixed)
{
    // Post Identifier
    $postid = (is_object($mixed) ? $mixed->ID : $mixed);
    if (!empty($postid))
    {
        return get_post_meta($postid, OA_LOUDVOICE_TIME_SYNC_KEY, true);
    }

    // Error
    return null;
}

/**
 * Returns the title of a post.
 */
function oa_loudvoice_get_title_for_post($post)
{
    return strip_tags(get_the_title($post), OA_LOUDVOICE_ALLOWED_HTML_TAGS);
}

/**
 * Returns the is_closed status of a post/postid.
 */
function oa_loudvoice_get_is_closed_for_post($mixed)
{
    // Post Identifier
    $postid = (is_object($mixed) ? $mixed->ID : $mixed);
    if (!empty($postid))
    {
        return (comments_open ($postid) ? false : true);
    }

    // Error
    return null;
}

/**
 * Returns the is_trashed status of a post/postid.
 */
function oa_loudvoice_get_is_trashed_for_post($mixed)
{
    // Post Identifier
    $postid = (is_object($mixed) ? $mixed->ID : $mixed);
    if (!empty($postid))
    {
        return ((strtolower (get_post_status ($postid)) == 'trash') ? true : false);
    }

    // Error
    return null;
}

/**
 * Returns the link of a post/postid.
 */
function oa_loudvoice_get_link_for_post($mixed)
{
    // Post Identifier
    $postid = (is_object($mixed) ? $mixed->ID : $mixed);
    if (!empty($postid))
    {
        return get_permalink($postid);
    }

    // Error
    return null;
}


/**
 * **************************************************************************************************************
 * *************************************************** COMMENT **************************************************
 * **************************************************************************************************************
 */

/**
 * Returns the commentid for a given token.
 */
function oa_loudvoice_get_commentid_for_token($token)
{
    global $wpdb;

    // The token is mandatory.
    $token = trim(strval($token));
    if (strlen($token) > 0)
    {
        // Read user for this token.
        $sql = "SELECT cm.comment_id FROM " . $wpdb->commentmeta . " AS cm INNER JOIN " . $wpdb->comments . " AS c ON (cm.comment_id=c.comment_ID) WHERE cm.meta_key=%s AND cm.meta_value=%s";
        $commentid = $wpdb->get_var($wpdb->prepare($sql, OA_LOUDVOICE_TOKEN_KEY, $token));

        // Make sure we have a result
        if (!empty($commentid) && is_numeric($commentid))
        {
            return $commentid;
        }
    }

    // Error
    return false;
}

/**
 * Returns the WordPress status for a given LoudVoice comment.
 */
function oa_loudvoice_wrap_status_for_lv_comment ($comment, $status)
{
    switch ($status)
    {
        case 'comment_approved':
            if ($comment->is_trashed == 1)
            {
                return 'trash';
            }
            elseif ($comment->is_spam == 1)
            {
                return 'spam';
            }
            elseif ($comment->has_been_approved == 1)
            {
                return 1;
            }
            else
            {
                return 0;
            }
    }

    return null;
}

/**
 * Returns the LoudVoice status for a given WordPress comment.
 */
function oa_loudvoice_wrap_status_for_wp_comment ($comment, $status)
{
    switch ($status)
    {
        case 'is_spam':
            return ((strtolower ($comment->comment_approved) == 'spam') ? true : false);

        case 'is_trashed':
            return ((strtolower ($comment->comment_approved) == 'trash') ? true : false);

        case 'has_been_approved':
            return (($comment->comment_approved == '1') ? true : false);

    }

    return null;
}

/**
 * Returns a comment reference for a comment/commentid.
 */
function oa_loudvoice_get_comment_reference_for_comment($mixed)
{
    // Either comment or commentid can be specified
    $commmentid = (is_object($mixed) ? $mixed->comment_ID : $mixed);
    if ( ! empty ($commmentid))
    {
        return 'WP-' . oa_loudvoice_uniqid() . '-COMMENT-' . intval($commmentid);
    }

    // Error
    return null;
}

/**
 * Returns an author reference for a comment.
 */
function oa_loudvoice_get_author_reference_for_comment($comment)
{
    // Check if comment is set
    if (is_object($comment))
    {
        // User Identifier
        if (!empty($comment->user_id))
        {
            return oa_loudvoice_get_author_reference_for_user($comment->user_id);
        }

        // Guest
        return 'WP-' . oa_loudvoice_uniqid() . '-USER-GUEST-COMMENT-' . intval(trim($comment->comment_ID));
    }

    // Error
    return null;
}

/**
 * Sets the token of a comment/commentid.
 */
function oa_loudvoice_set_token_for_comment($mixed, $token)
{
    // Either comment or commentid can be specified
    $commentid = (is_object($mixed) ? $mixed->comment_ID : $mixed);
    if (!empty($commentid))
    {
        if (is_null($token))
        {
            return delete_comment_meta($commentid, OA_LOUDVOICE_TOKEN_KEY);
        }
        else
        {
            return update_comment_meta($commentid, OA_LOUDVOICE_TOKEN_KEY, $token);
        }
    }

    // Error
    return null;
}

/**
 * Returns the token of a comment/commentid.
 */
function oa_loudvoice_get_token_for_comment($mixed)
{
    // Either comment or commentid can be specified
    $commentid = (is_object($mixed) ? $mixed->comment_ID : $mixed);
    if (!empty($commentid))
    {
        return get_comment_meta($commentid, OA_LOUDVOICE_TOKEN_KEY, true);
    }

    // Error
    return null;
}

/**
 * Set the synchronization time of a comment/commentid.
 */
function oa_loudvoice_set_time_sync_for_comment($mixed, $time)
{
    // Either comment or commentid can be specified
    $commentid = (is_object($mixed) ? $mixed->comment_ID : $mixed);
    if (!empty($commentid))
    {
        // Remove
        if (is_null($time))
        {
            return delete_comment_meta($commentid, OA_LOUDVOICE_TIME_SYNC_KEY);
        }
        // Update
        else
        {
            return update_comment_meta($commentid, OA_LOUDVOICE_TIME_SYNC_KEY, $time);
        }
    }

    // Error
    return false;
}

/**
 * Returns the synchronization time of a comment/commentid.
 */
function oa_loudvoice_get_time_sync_for_comment($mixed)
{
    // Either a comment or an identifier can be specified
    $commentid = (is_object($mixed) ? $mixed->comment_ID : $mixed);
    if (!empty($commentid))
    {
        return get_comment_meta($commentid, OA_LOUDVOICE_TIME_SYNC_KEY, true);
    }

    // Error
    return null;
}

/**
 * Returns the synchronization force flag of a comment/commentid.
 */
function oa_loudvoice_get_force_sync_for_comment($mixed)
{
    // Either a comment or an identifier can be specified
    $commentid = (is_object($mixed) ? $mixed->comment_ID : $mixed);
    if (!empty($commentid))
    {
        // Check if it needs to be synchronized
        if (get_comment_meta($commentid, OA_LOUDVOICE_FORCE_SYNC_KEY, true) == 1)
        {
            return true;
        }
    }

    // Do not force
    return false;
}

/**
 * Sets the synchronization force flag of a comment/commentid.
 */
function oa_loudvoice_set_force_sync_for_comment($mixed, $do_force_sync)
{
    // Either a comment or an identifier can be specified
    $commentid = (is_object($mixed) ? $mixed->comment_ID : $mixed);
    if (!empty($commentid))
    {
        // Remove
        if ($do_force_sync !== true)
        {
            return delete_comment_meta($commentid, OA_LOUDVOICE_FORCE_SYNC_KEY);
        }
        // Update
        else
        {
            return update_comment_meta($commentid, OA_LOUDVOICE_FORCE_SYNC_KEY, 1);
        }
    }

    // Error
    return false;
}

/**
 * Returns the comment tokens to be deleted.
 */
function oa_loudvoice_get_comment_tokens_to_delete()
{
    // Read tokens to delete
    $option = get_option(OA_LOUDVOICE_REMOVE_COMMENTS_KEY, '');

    // Decode and return
    return (!empty($option) ? explode(';', $option) : array());
}

/**
 * Sets the to delete flag of a comment_token.
 */
function oa_loudvoice_set_to_delete_for_comment_token($comment_token, $to_delete)
{
    // Read tokens to delete
    $option = get_option(OA_LOUDVOICE_REMOVE_COMMENTS_KEY, '');

    // Decode
    $comment_tokens = (!empty($option) ? explode(';', $option) : array());

    // Delete this one
    if ($to_delete == true)
    {
        if (!in_array($comment_token, $comment_tokens))
        {
            $comment_tokens[] = $comment_token;
        }
    }
    // Do not delete this one
    else
    {
        if (($key = array_search($comment_token, $comment_tokens)) !== false)
        {
            unset($comment_tokens[$key]);
        }
    }

    // Nothing left
    if (!is_array($comment_tokens) || count($comment_tokens) == 0)
    {
        delete_option(OA_LOUDVOICE_REMOVE_COMMENTS_KEY);
    }
    // Update
    else
    {
        update_option(OA_LOUDVOICE_REMOVE_COMMENTS_KEY, implode(';', $comment_tokens));
    }

    // Done
    return $comment_tokens;
}


/**
 * Returns the commentid for a given reference (eg. WP-ZJONY-COMMENT-1).
 */
function oa_loudvoice_get_commentid_for_reference($reference)
{
    if (preg_match('/COMMENT-([0-9]+)$/i', $reference, $matches))
    {
        return $matches[1];
    }

    // Error
    return null;
}

/**
 * Returns the userid for a given reference (eg. WP-ZJONY-USER-1).
 */
function oa_loudvoice_get_userid_for_reference($reference)
{
    if (preg_match('/USER-([0-9]+)$/i', $reference, $matches))
    {
        return $matches[1];
    }

    // Error
    return null;
}

/**
 * Tests if required options are configured to display LoudVoice.
 */
function oa_louddvoice_is_setup()
{
    // Read settings
    $settings = get_option('oa_loudvoice_settings');

    // Check if API credentials have been entered
    if (is_array($settings) && !empty($settings['api_subdomain']) && !empty($settings['api_key']) && !empty($settings['api_secret']))
    {
        return true;
    }

    // Setup incomplete
    return false;
}

/**
 * Returns the realm of this LoudVoice installation.
 */
function oa_loudvoice_get_realm()
{
    return 'WP-' . oa_loudvoice_uniqid();
}

/**
 * Returns the unique identifier of this LoudVoice installation.
 */
function oa_loudvoice_uniqid()
{
    // Read settings
    $settings = get_option('oa_loudvoice_settings');

    // Check if unique identifier exits
    if (is_array($settings) && !empty($settings['oa_loudvoice_uniqid']))
    {
        // Done
        return $settings['oa_loudvoice_uniqid'];
    }
    // Create Identifier
    else
    {
        // Generate one identifier
        $settings['oa_loudvoice_uniqid'] = oa_loudvoice_generate_uniqid();

        // Update entire array
        update_option('oa_loudvoice_settings', $settings);

        // Done
        return $settings['oa_loudvoice_uniqid'];
    }
}

/**
 * Generates a unique id.
 */
function oa_loudvoice_generate_uniqid($length = 5)
{
    $characters = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ';
    $charactersLength = strlen($characters);
    $randomString = '';

    for ($i = 0; $i < $length; $i++)
    {
        $randomString .= $characters[rand(0, $charactersLength - 1)];
    }
    return $randomString;
}

/**
 * Tests if the current connection is being made over http or https.
 */
function oa_loudvoice_is_https_on()
{
    if (!empty($_SERVER['SERVER_PORT']))
    {
        if (trim($_SERVER['SERVER_PORT']) == '443')
        {
            return true;
        }
    }

    if (!empty($_SERVER['HTTP_X_FORWARDED_PROTO']))
    {
        if (strtolower(trim($_SERVER['HTTP_X_FORWARDED_PROTO'])) == 'https')
        {
            return true;
        }
    }

    if (!empty($_SERVER['HTTPS']))
    {
        if (strtolower(trim($_SERVER['HTTPS'])) == 'on' or trim($_SERVER['HTTPS']) == '1')
        {
            return true;
        }
    }

    return false;
}

/**
 * Returns the user's ip address.
 */
function oa_loudvoice_get_user_ip()
{
    if (isset($_SERVER) && is_array($_SERVER))
    {
        if (!empty($_SERVER['REMOTE_ADDR']))
        {
            $REMOTE_ADDR = $_SERVER['REMOTE_ADDR'];
        }

        if (!empty($_SERVER['X_FORWARDED_FOR']))
        {
            $X_FORWARDED_FOR = explode(',', $_SERVER['X_FORWARDED_FOR']);

            if (is_array($X_FORWARDED_FOR) and count($X_FORWARDED_FOR) > 0)
            {
                $REMOTE_ADDR = trim($X_FORWARDED_FOR[0]);
            }
        }
        elseif (!empty($_SERVER['HTTP_X_FORWARDED_FOR']))
        {
            $HTTP_X_FORWARDED_FOR = explode(',', $_SERVER['HTTP_X_FORWARDED_FOR']);

            if (!empty($HTTP_X_FORWARDED_FOR))
            {
                $REMOTE_ADDR = trim($HTTP_X_FORWARDED_FOR[0]);
            }
        }
        if (!empty($REMOTE_ADDR))
        {
            return preg_replace('/[^0-9a-f:\., ]/si', '', $REMOTE_ADDR);
        }
    }

    // Error
    return null;
}

/**
 * Returns the list of disabled PHP functions.
 */
function oa_loudvoice_get_disabled_functions()
{
    $disabled_functions = trim(ini_get('disable_functions'));
    if (strlen($disabled_functions) == 0)
    {
        $disabled_functions = array();
    }
    else
    {
        $disabled_functions = explode(',', $disabled_functions);
        $disabled_functions = array_map('trim', $disabled_functions);
    }
    return $disabled_functions;
}

/**
 * Checks if a given v4 UUID is valid.
 */
function oa_loudvoice_is_valid_uuid($uuid)
{
    return preg_match('/[a-f0-9]{8}-[a-f0-9]{4}-[a-f0-9]{4}-[a-f0-9]{4}-[a-f0-9]{12}/i', trim($uuid));
}

/**
 * **************************************************************************************************************
 * **************************************************** API *****************************************************
 * **************************************************************************************************************
 */

/**
 * Sends an API request to the given endpoint.
 */
function oa_loudvoice_do_api_request_endpoint($endpoint, $api_opts = array())
{
    // Read settings
    $settings = get_option('oa_loudvoice_settings');

    // Options
    $api_opts['api_key'] = (!empty($settings['api_key']) ? $settings['api_key'] : '');
    $api_opts['api_secret'] = (!empty($settings['api_secret']) ? $settings['api_secret'] : '');

    // API Settings
    $api_connection_handler = ((!empty($settings['api_connection_handler']) and $settings['api_connection_handler'] == 'fsockopen') ? 'fsockopen' : 'curl');
    $api_connection_use_https = ((!isset($settings['api_connection_use_https']) or $settings['api_connection_use_https'] == '1') ? true : false);
    $api_subdomain = trim($settings['api_subdomain']);

    // Endpoint
    $api_resource_url = ($api_connection_use_https ? 'https' : 'http') . '://' . $api_subdomain . OA_LOUDVOICE_API_BASE . '/' . ltrim(trim($endpoint), '/ ');

    // Do request
    return oa_loudvoice_do_api_request($api_connection_handler, $api_resource_url, $api_opts);
}

/**
 * Sends an API request using the given handler.
 */
function oa_loudvoice_do_api_request($handler, $url, $opts = array (), $timeout = 25)
{
    // Proxy Settings
    if (defined('WP_PROXY_HOST') && defined('WP_PROXY_PORT'))
    {
        $opts['proxy_url'] = (defined('WP_PROXY_HOST') ? WP_PROXY_HOST : '');
        $opts['proxy_port'] = (defined('WP_PROXY_PORT') ? WP_PROXY_PORT : '');
        $opts['proxy_username'] = (defined('WP_PROXY_USERNAME') ? WP_PROXY_USERNAME : '');
        $opts['proxy_password'] = (defined('WP_PROXY_PASSWORD') ? WP_PROXY_PASSWORD : '');
    }

    // FSOCKOPEN
    if ($handler == 'fsockopen')
    {
        return oa_loudvoice_fsockopen_request($url, $opts, $timeout);
    }
    // CURL
    else
    {
        return oa_loudvoice_curl_request($url, $opts, $timeout);
    }
}

/**
 * **************************************************************************************************************
 * ************************************************* FSOCKOPEN **************************************************
 * **************************************************************************************************************
 */

/**
 * Checks if fsockopen is available.
 */
function oa_loudvoice_check_fsockopen_available()
{
    // Make sure fsockopen has been loaded
    if (function_exists('fsockopen') and function_exists('fwrite'))
    {
        $disabled_functions = oa_loudvoice_get_disabled_functions();

        // Make sure fsockopen has not been disabled
        if (!in_array('fsockopen', $disabled_functions) and !in_array('fwrite', $disabled_functions))
        {
            // Loaded and enabled
            return true;
        }
    }

    // Not loaded or disabled
    return false;
}

/**
 * Checks if fsockopen is enabled and can be used to connect to OneAll.
 */
function oa_loudvoice_check_fsockopen($secure = true)
{
    if (oa_loudvoice_check_fsockopen_available())
    {
        $result = oa_loudvoice_do_api_request('fsockopen', ($secure ? 'https' : 'http') . '://www.oneall.com/ping.html');
        if (is_object($result) and property_exists($result, 'http_code') and $result->http_code == 200)
        {
            if (property_exists($result, 'http_data'))
            {
                if (strtolower($result->http_data) == 'ok')
                {
                    return true;
                }
            }
        }
    }
    return false;
}

/**
 * Sends an fsockopen request.
 */
function oa_loudvoice_fsockopen_request($url, $options = array (), $timeout = 15)
{
    // Store the result.
    $result = new stdClass();

    // Make sure that this is a valid URL.
    if (($uri = parse_url($url)) === false)
    {
        $result->http_error = 'invalid_uri';
        return $result;
    }

    // Check the scheme.
    if ($uri['scheme'] == 'https')
    {
        $port = (isset($uri['port']) ? $uri['port'] : 443);
        $url = ($uri['host'] . ($port != 443 ? ':' . $port : ''));
        $url_protocol = 'https://';
        $url_prefix = 'ssl://';
    }
    else
    {
        $port = (isset($uri['port']) ? $uri['port'] : 80);
        $url = ($uri['host'] . ($port != 80 ? ':' . $port : ''));
        $url_protocol = 'http://';
        $url_prefix = '';
    }

    // Construct the method to use.
    $method = ( ! empty ($options['method']) ? strtoupper (trim ($options['method'])) : 'GET');

    // Construct the path to act on.
    $path = (isset($uri['path']) ? $uri['path'] : '/') . (!empty($uri['query']) ? ('?' . $uri['query']) : '');

    // HTTP headers.
    $headers = array();

    // We are behind a proxy.
    if (!empty($options['proxy_url']) && !empty($options['proxy_port']))
    {
        // Open socket.
        $fp = @fsockopen($options['proxy_url'], $options['proxy_port'], $errno, $errstr, $timeout);

        // Make sure that the socket has been opened properly.
        if (!$fp)
        {
            $result->http_error = trim($errstr);
            return $result;
        }

        // HTTP headers.
        $headers[] = $method . " " . $url_protocol . $url . $path . " HTTP/1.0";
        $headers[] = "Host: " . $url . ":" . $port;

        // Proxy authentication.
        if (!empty($options['proxy_username']) && !empty($options['proxy_password']))
        {
            $headers[] = 'Proxy-Authorization: Basic ' . base64_encode($options['proxy_username'] . ":" . $options['proxy_password']);
        }
    }
    // We are not behind a proxy.
    else
    {
        // Open socket.
        $fp = @fsockopen($url_prefix . $url, $port, $errno, $errstr, $timeout);

        // Make sure that the socket has been opened properly.
        if (!$fp)
        {
            $result->http_error = trim($errstr);
            return $result;
        }

        // HTTP headers.
        $headers[] = $method." " . $path . " HTTP/1.0";
        $headers[] = "Host: " . $url;
    }

    // Enable basic authentication
    if (isset($options['api_key']) and isset($options['api_secret']))
    {
        $headers[] = 'Authorization: Basic ' . base64_encode($options['api_key'] . ":" . $options['api_secret']);
    }

    // Post data.
    if (!empty($options['post_data']))
    {
        $headers[] = "Content-Length: " . strlen ($options['post_data']);
    }

    $headers[] = "Connection: close";

    // Send request.
    fwrite($fp, (implode("\r\n", $headers) . "\r\n\r\n"));

    // Send data.
    if (!empty($options['post_data']))
    {
        fwrite($fp, $options['post_data']);
    }

    // Fetch response
    $response = '';
    while (!feof($fp))
    {
        $response .= fread($fp, 1024);
    }

    // Close connection
    fclose($fp);

    // Parse response
    list ($response_header, $response_body) = explode("\r\n\r\n", $response, 2);

    // Parse header
    $response_header = preg_split("/\r\n|\n|\r/", $response_header);
    list ($header_protocol, $header_code, $header_status_message) = explode(' ', trim(array_shift($response_header)), 3);

    // Build result
    $result->http_code = $header_code;
    $result->http_data = $response_body;

    // Done
    return $result;
}

/**
 * **************************************************************************************************************
 * * *************************************************** CURL ****************************************************
 * **************************************************************************************************************
 */

/**
 * Check if CURL has been loaded and is enabled.
 */
function oa_loudvoice_check_curl_available()
{
    // Make sure cURL has been loaded
    if (in_array('curl', get_loaded_extensions()) and function_exists('curl_init') and function_exists('curl_exec'))
    {
        $disabled_functions = oa_loudvoice_get_disabled_functions();

        // Make sure cURL not been disabled
        if (!in_array('curl_init', $disabled_functions) and !in_array('curl_exec', $disabled_functions))
        {
            // Loaded and enabled
            return true;
        }
    }

    // Not loaded or disabled
    return false;
}

/**
 * Checks if CURL is available and can be used to connect to OneAll.
 */
function oa_loudvoice_check_curl($secure = true)
{
    if (oa_loudvoice_check_curl_available())
    {
        $result = oa_loudvoice_do_api_request('curl', ($secure ? 'https' : 'http') . '://www.oneall.com/ping.html');
        if (is_object($result) and property_exists($result, 'http_code') and $result->http_code == 200)
        {
            if (property_exists($result, 'http_data'))
            {
                if (strtolower($result->http_data) == 'ok')
                {
                    return true;
                }
            }
        }
    }
    return false;
}

/**
 * Sends a CURL request.
 */
function oa_loudvoice_curl_request($url, $options = array (), $timeout = 15)
{
    // Store the result
    $result = new stdClass();

    // Send request
    $curl = curl_init();
    curl_setopt($curl, CURLOPT_URL, $url);
    curl_setopt($curl, CURLOPT_HEADER, 0);
    curl_setopt($curl, CURLOPT_TIMEOUT, $timeout);
    curl_setopt($curl, CURLOPT_VERBOSE, 0);
    curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, 0);
    curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, 0);
    curl_setopt($curl, CURLOPT_USERAGENT, OA_LOUDVOICE_AGENT);

    // BASIC AUTH?
    if (isset($options['api_key']) and isset($options['api_secret']))
    {
        curl_setopt($curl, CURLOPT_USERPWD, $options['api_key'] . ":" . $options['api_secret']);
    }

    // Proxy Settings
    if (!empty($options['proxy_url']) && !empty($options['proxy_port']))
    {
        // Proxy Location
        curl_setopt($curl, CURLOPT_PROXYTYPE, CURLPROXY_HTTP);
        curl_setopt($curl, CURLOPT_PROXY, $options['proxy_url']);

        // Proxy Port
        curl_setopt($curl, CURLOPT_PROXYPORT, $options['proxy_port']);

        // Proxy Authentication
        if (!empty($options['proxy_username']) && !empty($options['proxy_password']))
        {
            curl_setopt($curl, CURLOPT_PROXYAUTH, CURLAUTH_ANY);
            curl_setopt($curl, CURLOPT_PROXYUSERPWD, $options['proxy_username'] . ':' . $options['proxy_password']);
        }
    }

    // Custom Request
    if (!empty($options['method']))
    {
        curl_setopt($curl, CURLOPT_CUSTOMREQUEST, strtoupper($options['method']));
    }

    // Post Data
    if (!empty($options['post_data']))
    {
        curl_setopt($curl, CURLOPT_POSTFIELDS, $options['post_data']);
    }

    // Make request
    if (($http_data = curl_exec($curl)) !== false)
    {
        $result->http_code = curl_getinfo($curl, CURLINFO_HTTP_CODE);
        $result->http_data = $http_data;
        $result->http_error = null;
    }
    else
    {
        $result->http_code = -1;
        $result->http_data = null;
        $result->http_error = curl_error($curl);
    }

    // Done
    return $result;
}

