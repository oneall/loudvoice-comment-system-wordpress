<?php


/**
 * Displays a single search-engine crawlable comment
 */
function oneall_loudvoice_display_comment( $comment, $args, $depth )
{
	$GLOBALS['comment'] = $comment;

	// The <li> has no closing </li>. It's automatically added by WordPress
	switch ($comment->comment_type)
	{
		case 'pingback'  :
		case 'trackback' :
			?>
		    <li class="post pingback">
		        <p><?php echo __('Pingback:', 'oa_loudvoice'); ?> <?php comment_author_link($comment); ?> (<?php edit_comment_link(__('Edit', 'oa_loudvoice'), '<span class="edit-link">', '</span>'); ?>)</p>
		    <?php
		break;

		default:
			?>
			    <li <?php comment_class(); ?> id="oneall_loudvoice_comment_<?php echo comment_ID(); ?>">
			        <div id="oneall_loudvoice_comment_header_<?php echo comment_ID(); ?>" class="oneall_loudvoice_comment_header">
			            <cite id="oneall_loudvoice_cite_<?php echo comment_ID(); ?>">
										<?php
										if(comment_author_url())
										{
											?>
												<a id="oneall_loudvoice_author_user_<?php echo comment_ID(); ?>" href="<?php echo comment_author_url(); ?>" target="_blank" rel="nofollow"><?php echo comment_author(); ?></a>
											<?php
										}
										else
										{
											?>
				                				<span id="oneall_loudvoice_author_user_<?php echo comment_ID(); ?>"><?php echo comment_author(); ?></span>
				                			<?php
										}
										?>

									</cite>
								</div>
								<div id="oneall_loudvoice_comment_body_<?php echo comment_ID(); ?>" class="oneall_loudvoice_comment_body">
									<div id="oneall_loudvoice_comment_message_<?php echo comment_ID(); ?>" class="oneall_loudvoice_comment_message"><?php echo wp_filter_kses(comment_text()); ?></div>
								 </div>
			 				<?php
		break;
	}
}


///////////////////////////////////////////////////////////////////////////////
// CONTENT
///////////////////////////////////////////////////////////////////////////////

// Read settings
$settings = get_option ('oa_loudvoice_settings');

// Import providers
GLOBAL $oa_loudvoice_providers;

// Author Session
$author_session_token = null;

// Do we have author sessions?
if (empty ($settings ['disable_author_sessions']))
{
	// Author sessions are enabled
	if (is_user_logged_in ())
	{
		// Read the current user
		$user = wp_get_current_user ();

		// User Found
		if (!empty ($user->ID))
		{
			// Read Session Details
			$tmp_author_session_token = get_user_meta ($user->ID, OA_LOUDVOICE_AUTHOR_SESSION_TOKEN_KEY, true);

			// We have a valid session
			if ( ! empty ($tmp_author_session_token))
			{
				$author_session_token = $tmp_author_session_token;
			}
		}
	}
}

// Build providers
$providers = array();
if (isset ($settings ['providers']) AND is_array ($settings ['providers']))
{
	foreach ($settings ['providers'] as $key => $name)
	{
		$providers [] = $key;
	}
}

// OneAll Javascript Library
$js_library = ((oa_loudvoice_is_https_on () ? 'https' : 'http') . '://' . $settings ['api_subdomain'] . OA_LOUDVOICE_API_BASE . '/socialize/library.js');

// Comments will be embedded in this container
$comments_container_id = 'oneall_loudvoice_' . mt_rand (99999, 9999999);

// Page Details
$page_reference = oa_loudvoice_get_reference_for_post ($post);
$page_title = oa_loudvoice_get_title_for_post ($post);
$page_url = oa_loudvoice_get_link_for_post ($post);

// Headers
?>
<div id="comments" class="comments-area">
	<h2 class="comments-title">
		<?php
			printf(__ ('Comments on %1$s', 'oa_loudvoice'), get_the_title());
		?>
	</h2>
	<?php
		// Default comments for visitors without JavaScript
	?>
	<!-- OneAll.com / Loudvoice/<?php echo constant ('OA_LOUDVOICE_VERSION'); ?> WordPress //-->
	<div id="<?php echo $comments_container_id; ?>">
	   	<?php
	    	// Do we have any comments ?
	    	if (have_comments())
	    	{
	    		// Enable Navigation ?
	    		$have_multiple_pages = (get_comment_pages_count() > 1 && get_option('page_comments'));

	    		?>
					<div class="oneall_loudvoice_discussion">
						<?php
							// Add Pagination
							if ($have_multiple_pages)
							{
								?>
			            			<div class="navigation">
			                			<div class="nav-previous">
			                    			<span class="meta-nav">&larr;</span>&nbsp;
			                    			<?php previous_comments_link( __('Older Comments', 'oa_loudvoice')); ?>
			                			</div>
			                			<div class="nav-next">
			                    			<?php next_comments_link(__('Newer Comments', 'oa_loudvoice')); ?>
			                    			&nbsp;<span class="meta-nav">&rarr;</span>
			                			</div>
			            			</div>
			            		<?php
							}
	            		?>
						<ul id="oneall_loudvoice_comments">
							<?php wp_list_comments (array ('callback' => 'oneall_loudvoice_display_comment')); ?>
	            		</ul>
	            		<?php
							// Add Pagination
							if ($have_multiple_pages)
							{
								?>
			            			<div class="navigation">
			                			<div class="nav-previous">
			                    			<span class="meta-nav">&larr;</span>&nbsp;
			                    			<?php previous_comments_link( __('Older Comments', 'oa_loudvoice')); ?>
			                			</div>
			                			<div class="nav-next">
			                    			<?php next_comments_link(__('Newer Comments', 'oa_loudvoice')); ?>
			                    			&nbsp;<span class="meta-nav">&rarr;</span>
			                			</div>
			            			</div>
			            		<?php
							}
						?>
					</div>
				<?php
	    	}
	    ?>
	</div>

	<!-- OneAll.com / Loudvoice/<?php echo constant ('OA_LOUDVOICE_VERSION'); ?> WordPress //-->
	<script data-cfasync="false" type="text/javascript">

		(function() {
			var oa = document.createElement('script'); oa.type = 'text/javascript';
			oa.async = true; oa.src = '<?php echo $js_library; ?>';
			var s = document.getElementsByTagName('script')[0]; s.parentNode.insertBefore(oa, s);
		})();

		var _oneall = _oneall || [];
		_oneall.push(['loudvoice', 'set_providers', ['<?php echo implode ("','", $providers);?>']]);
		_oneall.push(['loudvoice', 'set_page', '<?php echo $page_title;?>', '<?php echo $page_url;?>']);
		_oneall.push(['loudvoice', 'set_reference', '<?php echo $page_reference; ?>']);
		_oneall.push(['loudvoice', 'set_event', 'on_comment_added', function(data) {oa_loudvoice_import_comment ('<?php echo $post->ID; ?>', data);}]);
        <?php if (is_user_logged_in ()): ?>
        _oneall.push(['loudvoice', 'set_author_session_token', '<?php echo strval($author_session_token); ?>']);
        _oneall.push(['loudvoice', 'set_event', 'on_logout_end_success', function(){
            window.location.replace('<?php echo html_entity_decode(wp_logout_url()); ?>');
        }]);
        _oneall.push(['loudvoice', 'do_render_ui', '<?php echo $comments_container_id; ?>']);
        <?php endif; ?>
	</script>
</div>
