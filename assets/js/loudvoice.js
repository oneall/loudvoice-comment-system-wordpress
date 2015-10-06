function oa_loudvoice_import_comment(postid, api_data) {
	if ((postid - 0) == postid && ('' + postid).trim().length > 0) {
		var post_data = {
			"action" : 'import_comment',
			"postid" : postid,
			"comment_token" : api_data.comment_token,
			"_ajax_nonce" : oa_loudvoice.nonce
		};
		jQuery.post(oa_loudvoice.ajaxurl, post_data, function(response) {
			alert(response);
		});
	}
};

