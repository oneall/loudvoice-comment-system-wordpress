<?php

/**
 * Export Settings
 */
define('OA_LOUDVOICE_EXPORT_COMMENTS_STEP', 10);
define('OA_LOUDVOICE_EXPORT_COMMENTS_THROTTLE', 1000);
define('OA_LOUDVOICE_EXPORT_POSTS_STEP', 10);
define('OA_LOUDVOICE_EXPORT_POSTS_THROTTLE', 50000);

/**
 * Import Settings
 */
define('OA_LOUDVOICE_IMPORT_COMMENTS_STEP', 100);
define('OA_LOUDVOICE_IMPORT_COMMENTS_THROTTLE', 1000);
define('OA_LOUDVOICE_IMPORT_POSTS_STEP', 10);
define('OA_LOUDVOICE_IMPORT_POSTS_THROTTLE', 50000);

/**
 * Meta Keys
 */
define('OA_LOUDVOICE_TOKEN_KEY', '_oa_loudvoice_token');
define('OA_LOUDVOICE_TIME_SYNC_KEY', '_oa_loudvoice_time_sync');
define('OA_LOUDVOICE_FORCE_SYNC_KEY', '_oa_loudvoice_force_sync');
define('OA_LOUDVOICE_REMOVE_COMMENTS_KEY', '_oa_loudvoice_force_sync');
define('OA_LOUDVOICE_REMOVE_POSTS_KEY', '_oa_loudvoice_remove_post_tokens');
define('OA_LOUDVOICE_AUTHOR_SESSION_TOKEN_KEY', '_oa_loudvoice_author_session_token');
define('OA_LOUDVOICE_AUTHOR_SESSION_EXPIRE_KEY', '_oa_loudvoice_author_session_expiration');

/**
 * Other Settings
 */
define('OA_LOUDVOICE_ALLOWED_HTML_TAGS', '<b><u><i><h1><h2><h3><code><blockquote><br><hr>');
define('OA_LOUDVOICE_VERSION', '2.1.1');
define('OA_LOUDVOICE_AGENT', 'LoudVoice/' . OA_LOUDVOICE_VERSION . ' WordPress/' . get_bloginfo('version') . ' (+http://www.oneall.com/)');
define('OA_LOUDVOICE_API_BASE', '.api.oneall.com');

/**
 * Available Providers
 */
$oa_loudvoice_providers = array();
$oa_loudvoice_providers['amazon'] = 'Amazon';
$oa_loudvoice_providers['battlenet'] = 'Battle.net';
$oa_loudvoice_providers['blogger'] = 'Blogger';
$oa_loudvoice_providers['discord'] = 'Discord';
$oa_loudvoice_providers['disqus'] = 'Disqus';
$oa_loudvoice_providers['draugiem'] = 'Draugiem';
$oa_loudvoice_providers['dribbble'] = 'Dribbble';
$oa_loudvoice_providers['facebook'] = 'Facebook';
$oa_loudvoice_providers['foursquare'] = 'Foursquare';
$oa_loudvoice_providers['github'] = 'Github.com';
$oa_loudvoice_providers['google'] = 'Google';
$oa_loudvoice_providers['instagram'] = 'Instagram';
$oa_loudvoice_providers['line'] = 'Line';
$oa_loudvoice_providers['linkedin'] = 'LinkedIn';
$oa_loudvoice_providers['livejournal'] = 'LiveJournal';
$oa_loudvoice_providers['mailru'] = 'Mail.ru';
$oa_loudvoice_providers['meetup'] = 'Meetup';
$oa_loudvoice_providers['odnoklassniki'] = 'Odnoklassniki';
$oa_loudvoice_providers['openid'] = 'OpenID';
$oa_loudvoice_providers['paypal'] = 'PayPal';
$oa_loudvoice_providers['pinterest'] = 'Pinterest';
$oa_loudvoice_providers['pixelpin'] = 'PixelPin';
$oa_loudvoice_providers['reddit'] = 'Reddit';
$oa_loudvoice_providers['skyrock'] = 'Skyrock.com';
$oa_loudvoice_providers['soundcloud'] = 'SoundCloud';
$oa_loudvoice_providers['stackexchange'] = 'StackExchange';
$oa_loudvoice_providers['steam'] = 'Steam';
$oa_loudvoice_providers['tumblr'] = 'Tumblr';
$oa_loudvoice_providers['twitch'] = 'Twitch.tv';
$oa_loudvoice_providers['twitter'] = 'Twitter';
$oa_loudvoice_providers['vimeo'] = 'Vimeo';
$oa_loudvoice_providers['vkontakte'] = 'VKontakte';
$oa_loudvoice_providers['weibo'] = 'Weibo';
$oa_loudvoice_providers['windowslive'] = 'Windows Live';
$oa_loudvoice_providers['wordpress'] = 'WordPress.com';
$oa_loudvoice_providers['xing'] = 'Xing';
$oa_loudvoice_providers['yahoo'] = 'Yahoo';
$oa_loudvoice_providers['youtube'] = 'YouTube';
