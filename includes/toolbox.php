<?php

/**
 * Return an identifier for a comment
 */
function oa_loudvoice_get_reference_for_comment ($comment)
{
	// User Identifier
	$commentid = (is_object ($comment) ? $comment->comment_ID : $comment);

	// We need the identifier
	if (! empty ($commentid))
	{
		return 'WP-COMMENT-' . intval (trim ($commentid));
	}

	// Error
	return null;
}

/**
 * Return the reference for a user
 */
function oa_loudvoice_get_reference_for_user ($user)
{
	// User Identifier
	$userid = (is_object ($user) ? $user->ID : $user);	

	// We need the identifier
	if (! empty ($userid))
	{
		return 'WP-USER-' . intval (trim ($userid));
	}

	// Error
	return null;
}




/**
 * Return the reference of a post
 */
function oa_loudvoice_get_reference_for_post ($post)
{
	// Post Identifier
	$postid = (is_object ($post) ? $post->ID : $post);

	// We need the identifier
	if (! empty ($postid))
	{	
		return 'WP-POST-' . intval (trim ($postid));
	}
	
	// Error
	return null;
}

/**
 * Return a title for a post
 */
function oa_loudvoice_get_title_for_post ($postid)
{
	return strip_tags (get_the_title ($postid), OA_LOUDVOICE_ALLOWED_HTML_TAGS);
}

/**
 * Return a link for a post
 */
function oa_loudvoice_get_link_for_post ($postid)
{
	return get_permalink ($postid);
}

/**
 * Test if required options are configured to display Loudvoice
 */
function oa_louddvoice_is_setup ()
{
	//return false;
	// Read settings
	$settings = get_option ('oa_loudvoice_settings');
	
	// Check if API credentials have been entered
	if (is_array ($settings) && !empty ($settings ['api_subdomain']) && !empty ($settings ['api_key']) && !empty ($settings ['api_secret']))
	{
		return true;
	}
	
	// Setup incomplete
	return false;
}

/**
 * Test if the current connection is being made over http or https
 */
function oa_loudvoice_is_https_on ()
{
	if (!empty ($_SERVER ['SERVER_PORT']))
	{
		if (trim ($_SERVER ['SERVER_PORT']) == '443')
		{
			return true;
		}
	}
	
	if (!empty ($_SERVER ['HTTP_X_FORWARDED_PROTO']))
	{
		if (strtolower (trim ($_SERVER ['HTTP_X_FORWARDED_PROTO'])) == 'https')
		{
			return true;
		}
	}
	
	if (!empty ($_SERVER ['HTTPS']))
	{
		if (strtolower (trim ($_SERVER ['HTTPS'])) == 'on' or trim ($_SERVER ['HTTPS']) == '1')
		{
			return true;
		}
	}
	
	return false;
}

/**
 * Return the list of disabled functions.
 */
function oa_loudvoice_get_disabled_functions ()
{
	$disabled_functions = trim (ini_get ('disable_functions'));
	if (strlen ($disabled_functions) == 0)
	{
		$disabled_functions = array();
	}
	else
	{
		$disabled_functions = explode (',', $disabled_functions);
		$disabled_functions = array_map ('trim', $disabled_functions);
	}
	return $disabled_functions;
}

// Check if a given v4 UUID is valid
function  oa_loudvoice_is_valid_uuid ($uuid)
{
	return preg_match ('/[a-f0-9]{8}-[a-f0-9]{4}-[a-f0-9]{4}-[a-f0-9]{4}-[a-f0-9]{12}/i', trim ($uuid));
}

/**
 * Send an API request by using the given handler
 */
function oa_loudvoice_do_api_request_endpoint ($endpoint, $api_opts = array())
{
	// Read settings
	$settings = get_option ('oa_loudvoice_settings');
	
	// Options
	$api_opts ['api_key'] = (!empty ($settings ['api_key']) ? $settings ['api_key'] : '');
	$api_opts ['api_secret'] = (!empty ($settings ['api_secret']) ? $settings ['api_secret'] : '');
	
	// API Settings
	$api_connection_handler = ((!empty ($settings ['api_connection_handler']) and $settings ['api_connection_handler'] == 'fsockopen') ? 'fsockopen' : 'curl');
	$api_connection_use_https = ((!isset ($settings ['api_connection_use_https']) or $settings ['api_connection_use_https'] == '1') ? true : false);
	$api_subdomain = trim ($settings ['api_subdomain']);
	
	// Endpoint
	$api_resource_url = ($api_connection_use_https ? 'https' : 'http') . '://' . $api_subdomain . '.api.oneall.loc/'.ltrim (trim($endpoint),'/ ');
	
	// Do request
	return oa_loudvoice_do_api_request ($api_connection_handler, $api_resource_url, $api_opts);
}

/**
 * Send an API request by using the given handler
 */
function oa_loudvoice_do_api_request ($handler, $url, $opts = array (), $timeout = 25)
{
	// Proxy Settings
	if (defined ('WP_PROXY_HOST') && defined ('WP_PROXY_PORT'))
	{
		$opts ['proxy_url'] = (defined ('WP_PROXY_HOST') ? WP_PROXY_HOST : '');
		$opts ['proxy_port'] = (defined ('WP_PROXY_PORT') ? WP_PROXY_PORT : '');
		$opts ['proxy_username'] = (defined ('WP_PROXY_USERNAME') ? WP_PROXY_USERNAME : '');
		$opts ['proxy_password'] = (defined ('WP_PROXY_PASSWORD') ? WP_PROXY_PASSWORD : '');
	}
	
	// FSOCKOPEN
	if ($handler == 'fsockopen')
	{
		return oa_loudvoice_fsockopen_request ($url, $opts, $timeout);
	}
	// CURL
	else
	{
		return oa_loudvoice_curl_request ($url, $opts, $timeout);
	}
}

/**
 * **************************************************************************************************************
 * ************************************************* FSOCKOPEN **************************************************
 * **************************************************************************************************************
 */

/**
 * Check if fsockopen is available.
 */
function oa_loudvoice_check_fsockopen_available ()
{
	// Make sure fsockopen has been loaded
	if (function_exists ('fsockopen') and function_exists ('fwrite'))
	{
		$disabled_functions = oa_loudvoice_get_disabled_functions ();
		
		// Make sure fsockopen has not been disabled
		if (!in_array ('fsockopen', $disabled_functions) and !in_array ('fwrite', $disabled_functions))
		{
			// Loaded and enabled
			return true;
		}
	}
	
	// Not loaded or disabled
	return false;
}

/**
 * Check if fsockopen is enabled and can be used to connect to OneAll.
 */
function oa_loudvoice_check_fsockopen ($secure = true)
{
	if (oa_loudvoice_check_fsockopen_available ())
	{
		$result = oa_loudvoice_do_api_request ('fsockopen', ($secure ? 'https' : 'http') . '://www.oneall.com/ping.html');
		if (is_object ($result) and property_exists ($result, 'http_code') and $result->http_code == 200)
		{
			if (property_exists ($result, 'http_data'))
			{
				if (strtolower ($result->http_data) == 'ok')
				{
					return true;
				}
			}
		}
	}
	return false;
}

/**
 * Send an fsockopen request.
 */
function oa_loudvoice_fsockopen_request ($url, $options = array (), $timeout = 15)
{
	// Store the result
	$result = new stdClass ();
	
	// Make sure that this is a valid URL
	if (($uri = parse_url ($url)) === false)
	{
		$result->http_error = 'invalid_uri';
		return $result;
	}
	
	// Check the scheme
	if ($uri ['scheme'] == 'https')
	{
		$port = (isset ($uri ['port']) ? $uri ['port'] : 443);
		$url = ($uri ['host'] . ($port != 443 ? ':' . $port : ''));
		$url_protocol = 'https://';
		$url_prefix = 'ssl://';
	}
	else
	{
		$port = (isset ($uri ['port']) ? $uri ['port'] : 80);
		$url = ($uri ['host'] . ($port != 80 ? ':' . $port : ''));
		$url_protocol = 'http://';
		$url_prefix = '';
	}
	
	// Construct the path to act on
	$path = (isset ($uri ['path']) ? $uri ['path'] : '/') . (!empty ($uri ['query']) ? ('?' . $uri ['query']) : '');
	
	// HTTP Headers
	$headers = array();
	
	// We are using a proxy
	if (!empty ($options ['proxy_url']) && !empty ($options ['proxy_port']))
	{
		// Open Socket
		$fp = @fsockopen ($options ['proxy_url'], $options ['proxy_port'], $errno, $errstr, $timeout);
		
		// Make sure that the socket has been opened properly
		if (!$fp)
		{
			$result->http_error = trim ($errstr);
			return $result;
		}
		
		// HTTP Headers
		$headers [] = "GET " . $url_protocol . $url . $path . " HTTP/1.0";
		$headers [] = "Host: " . $url . ":" . $port;
		
		// Proxy Authentication
		if (!empty ($options ['proxy_username']) && !empty ($options ['proxy_password']))
		{
			$headers [] = 'Proxy-Authorization: Basic ' . base64_encode ($options ['proxy_username'] . ":" . $options ['proxy_password']);
		}
	}
	// We are not using a proxy
	else
	{
		// Open Socket
		$fp = @fsockopen ($url_prefix . $url, $port, $errno, $errstr, $timeout);
		
		// Make sure that the socket has been opened properly
		if (!$fp)
		{
			$result->http_error = trim ($errstr);
			return $result;
		}
		
		// HTTP Headers
		$headers [] = "GET " . $path . " HTTP/1.0";
		$headers [] = "Host: " . $url;
	}
	
	// Enable basic authentication
	if (isset ($options ['api_key']) and isset ($options ['api_secret']))
	{
		$headers [] = 'Authorization: Basic ' . base64_encode ($options ['api_key'] . ":" . $options ['api_secret']);
	}
	
	// Build and send request
	fwrite ($fp, (implode ("\r\n", $headers) . "\r\n\r\n"));
	
	// Fetch response
	$response = '';
	while ( !feof ($fp) )
	{
		$response .= fread ($fp, 1024);
	}
	
	// Close connection
	fclose ($fp);
	
	// Parse response
	list ($response_header, $response_body) = explode ("\r\n\r\n", $response, 2);
	
	// Parse header
	$response_header = preg_split ("/\r\n|\n|\r/", $response_header);
	list ($header_protocol, $header_code, $header_status_message) = explode (' ', trim (array_shift ($response_header)), 3);
	
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
 * Check if cURL has been loaded and is enabled.
 */
function oa_loudvoice_check_curl_available ()
{
	// Make sure cURL has been loaded
	if (in_array ('curl', get_loaded_extensions ()) and function_exists ('curl_init') and function_exists ('curl_exec'))
	{
		$disabled_functions = oa_loudvoice_get_disabled_functions ();
		
		// Make sure cURL not been disabled
		if (!in_array ('curl_init', $disabled_functions) and !in_array ('curl_exec', $disabled_functions))
		{
			// Loaded and enabled
			return true;
		}
	}
	
	// Not loaded or disabled
	return false;
}

/**
 * Check if CURL is available and can be used to connect to OneAll
 */
function oa_loudvoice_check_curl ($secure = true)
{
	if (oa_loudvoice_check_curl_available ())
	{
		$result = oa_loudvoice_do_api_request ('curl', ($secure ? 'https' : 'http') . '://www.oneall.com/ping.html');
		if (is_object ($result) and property_exists ($result, 'http_code') and $result->http_code == 200)
		{
			if (property_exists ($result, 'http_data'))
			{
				if (strtolower ($result->http_data) == 'ok')
				{
					return true;
				}
			}
		}
	}
	return false;
}

/**
 * Send a CURL request.
 */
function oa_loudvoice_curl_request ($url, $options = array (), $timeout = 15)
{
	// Store the result
	$result = new stdClass ();
	
	// Send request
	$curl = curl_init ();
	curl_setopt ($curl, CURLOPT_URL, $url);
	curl_setopt ($curl, CURLOPT_HEADER, 0);
	curl_setopt ($curl, CURLOPT_TIMEOUT, $timeout);
	curl_setopt ($curl, CURLOPT_VERBOSE, 0);
	curl_setopt ($curl, CURLOPT_RETURNTRANSFER, 1);
	curl_setopt ($curl, CURLOPT_SSL_VERIFYPEER, 0);
	curl_setopt ($curl, CURLOPT_SSL_VERIFYHOST, 0);
	curl_setopt ($curl, CURLOPT_USERAGENT, 'Loudvoice ' . OA_LOUDVOICE_VERSION . 'WP (+http://www.oneall.com/)');
	
	// BASIC AUTH?
	if (isset ($options ['api_key']) and isset ($options ['api_secret']))
	{
		curl_setopt ($curl, CURLOPT_USERPWD, $options ['api_key'] . ":" . $options ['api_secret']);
	}
	
	// Proxy Settings
	if (!empty ($options ['proxy_url']) && !empty ($options ['proxy_port']))
	{
		// Proxy Location
		curl_setopt ($curl, CURLOPT_PROXYTYPE, CURLPROXY_HTTP);
		curl_setopt ($curl, CURLOPT_PROXY, $options ['proxy_url']);
		
		// Proxy Port
		curl_setopt ($curl, CURLOPT_PROXYPORT, $options ['proxy_port']);
		
		// Proxy Authentication
		if (!empty ($options ['proxy_username']) && !empty ($options ['proxy_password']))
		{
			curl_setopt ($curl, CURLOPT_PROXYAUTH, CURLAUTH_ANY);
			curl_setopt ($curl, CURLOPT_PROXYUSERPWD, $options ['proxy_username'] . ':' . $options ['proxy_password']);
		}
	}
	
	// Custom Request
	if (!empty ($options ['method']))
	{
		curl_setopt($curl, CURLOPT_CUSTOMREQUEST, strtoupper ($options ['method']));
	}
	
	// Post Data
	if ( ! empty ($options ['post_data']))
	{
		curl_setopt($curl, CURLOPT_POSTFIELDS, $options ['post_data']);
	}			
	
	
	// Make request
	if (($http_data = curl_exec ($curl)) !== false)
	{
		$result->http_code = curl_getinfo ($curl, CURLINFO_HTTP_CODE);
		$result->http_data = $http_data;
		$result->http_error = null;
	}
	else
	{
		$result->http_code = -1;
		$result->http_data = null;
		$result->http_error = curl_error ($curl);
	}
	

	// Done
	return $result;
}