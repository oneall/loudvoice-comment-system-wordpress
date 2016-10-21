<?php

// Display a single comment
function oneall_loudvoice_display_comment( $comment, $args, $depth )
{	
	
	$GLOBALS['comment'] = $comment;
	
	switch ($comment->comment_type)
	{
		case 'pingback'  :
		case 'trackback' :
			?>
		    <li class="post pingback">
		        <p><?php echo dsq_i('Pingback:'); ?> <?php comment_author_link(); ?>(<?php edit_comment_link(dsq_i('Edit'), ' '); ?>)</p>
		    </li>
		    <?php
		break;

		default:
			?>
    			<li <?php comment_class(); ?> id="oneall_loudvoice_comment_<?php echo comment_ID(); ?>">
        			<div id="oneall_loudvoice_comment_header_<?php echo comment_ID(); ?>" class="oneall_loudvoice_comment_header">
            			<cite id="oneall_loudvoice_cote_<?php echo comment_ID(); ?>">
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
				</li>
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
			$tmp_author_session_token = get_user_meta ($user->ID, '_oa_loudvoice_author_session_token', true);
			
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

// Setup
$js_library = ((oa_loudvoice_is_https_on () ? 'https' : 'http') . '://' . $settings ['api_subdomain'] . '.api.oneall.com/socialize/library.js');
$api_subdomain = (!empty ($oalv_settings ['api_subdomain']) ? $oalv_settings ['api_subdomain'] : '');


// Comments will be embedded in this container
$comments_container_id = 'oneall_loudvoice_' . mt_rand (99999, 9999999);

// Page Details
$page_reference = oa_loudvoice_get_reference_for_post ($post);
$page_title     = oa_loudvoice_get_title_for_post ($post);
$page_url       = oa_loudvoice_get_link_for_post ($post);

$post_token     = oa_loudvoice_get_token_for_postid ($post->ID);

// Headers
?>
	<div id="comments" class="comments-area">
		<h2 class="comments-title">
			<?php
				printf( _nx( '<span class="oa-loudvoice-discussion-token" data-discussion_token="'.$post_token.'">One</span> thought on &ldquo;%2$s&rdquo;', '<span class="oa-loudvoice-discussion-token" data-discussion_token="'.$post_token.'">%1$s</span> thoughts on &ldquo;%2$s&rdquo;', get_comments_number(), 'comments title', 'twentyfifteen' ), number_format_i18n( get_comments_number() ), get_the_title() );
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
						// Do we have than one page?
						if ($have_multiple_pages)
						{
							?>				
		            			<div class="navigation">
		                			<div class="nav-previous">
		                    			<span class="meta-nav">&larr;</span>&nbsp;
		                    			<?php previous_comments_link( __('Older Comments')); ?>
		                			</div>
		                			<div class="nav-next">
		                    			<?php next_comments_link(__('Newer Comments')); ?>
		                    			&nbsp;<span class="meta-nav">&rarr;</span>
		                			</div>
		            			</div>
		            		<?php 
						}
	            		?>            	
	            		
						<ul id="oneall_loudvoice_comments">
		                <?php
		                    // List comments using our callback
		                    wp_list_comments(array('callback' => 'oneall_loudvoice_display_comment'));
		                ?>
	            		</ul>
	            		
	            		<?php 
						// Do we have than one page?
						if ($have_multiple_pages)
						{
							?>				
		            			<div class="navigation">
		                			<div class="nav-previous">
		                    			<span class="meta-nav">&larr;</span>&nbsp;
		                    			<?php previous_comments_link( __('Older Comments')); ?>
		                			</div>
		                			<div class="nav-next">
		                    			<?php next_comments_link(__('Newer Comments')); ?>
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
			_oneall.push(['loudvoice', 'set_author_session_token', '<?php echo strval ($author_session_token); ?>']);
			_oneall.push(['loudvoice', 'set_event', 'on_comment_added', function(data) {oa_loudvoice_import_comment ('<?php echo $post->ID; ?>', data);}]);
			_oneall.push(['loudvoice', 'do_render_ui', '<?php echo $comments_container_id; ?>']);
		</script>
	</div>
</div>