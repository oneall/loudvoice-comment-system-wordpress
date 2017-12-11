jQuery(document).ready(function($) {

	/* Get all discussion tokens */
	var discussion_tokens = [];
	jQuery('.loudvoice-comments-counter').each(function() {
		var discussion_token = jQuery(this).data('discussion_token');
		if (typeof (discussion_token) !== 'undefined' && discussion_token.length > 0) {
			discussion_tokens.push(discussion_token);
		}
	});

	/* Remove duplicates */
	discussion_tokens = jQuery.unique(discussion_tokens);

	/* Load Counter Updater */
	if (discussion_tokens.length > 0) {
		var s = document.createElement('script');
		s.type = 'text/javascript'; s.async = true;
		s.src = count_library + '?discussions_tokens=' + discussion_tokens.join(';');
		(document.getElementsByTagName('HEAD')[0] || document.getElementsByTagName('BODY')[0]).appendChild(s);
	}
	
}());