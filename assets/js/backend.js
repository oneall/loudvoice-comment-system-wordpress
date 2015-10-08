jQuery(document).ready(function($) {

	/* Export */
	$('.oa_loudvoice_sync').click(function(){
		var message_string, message_container, is_success, data, action;
		
		action = ($(this).attr('id') == 'oa_loudvoice_import' ? 'import' : 'export'); 

		/* Only click once */
		if ( ! $(this).hasClass ('disabled'))
		{
			$(this).addClass ('disabled');
		
			message_container = jQuery('#oa_loudvoice_' + action + '_result');	
			message_container.removeClass('success_message error_message').addClass('working_message');
			message_container.html(objectL10n.oa_admin_js_2);
			
			data = {
				_ajax_nonce: objectL10n.oa_loudvoice_ajax_nonce,
				action: ('oa_loudvoice_' + action)
			};
				
			jQuery.post(ajaxurl,data, function(response) {
			
				alert(response_string);
				
				var response_parts = response_string.split('|');
				var response_status = response_parts[0];
				var response_flag = response_parts[1];
				var response_text = response_parts[2];
				
				message_container.removeClass('working_message');
				message_container.html(response_text);
	
				if (response_status == 'error')
				{
					message_container.addClass('error_message');
				}	
				else
				{
					message_container.addClass('success_message');
				}			
						
			});
			
			$(this).removeClass ('disabled');
		}
		return false;
	});
	

	
	/* Autodetect API Connection Handler */
	$('#oa_loudvoice_autodetect_api_connection_handler').click(function(){	
		var message_string, message_container, is_success, data;	
		
		data = {
			_ajax_nonce: objectL10n.oa_loudvoice_ajax_nonce,
			action: 'autodetect_api_connection_handler'
		};
		
		message_container = jQuery('#oa_loudvoice_api_connection_handler_result');	
		message_container.removeClass('success_message error_message').addClass('working_message');
		message_container.html(objectL10n.oa_admin_js_1);
		
		jQuery.post(ajaxurl,data, function(response) {	
			/* CURL/FSOCKOPEN Radio Boxs */
			var radio_curl = jQuery("#oa_loudvoice_api_connection_handler_curl");			
			var radio_fsockopen = jQuery("#oa_loudvoice_api_connection_handler_fsockopen");					
			var radio_use_http_1 = jQuery("#oa_loudvoice_api_connection_handler_use_https_1");
			var radio_use_http_0 = jQuery("#oa_loudvoice_api_connection_handler_use_https_0");
						
			radio_curl.removeAttr("checked");
			radio_fsockopen.removeAttr("checked");
			radio_use_http_1.removeAttr("checked");
			radio_use_http_0.removeAttr("checked");
				
			/* CURL detected, HTTPS */
			if (response == 'success_autodetect_api_curl_https')
			{
				is_success = true;
				radio_curl.attr("checked", "checked");			
				radio_use_http_1.attr("checked", "checked");					
				message_string = objectL10n.oa_admin_js_201a;
			}		
			/* CURL detected, HTTP */
			else if (response == 'success_autodetect_api_curl_http')
			{
				is_success = true;
				radio_curl.attr("checked", "checked");			
				radio_use_http_0.attr("checked", "checked");					
				message_string = objectL10n.oa_admin_js_201b;
			}				
			/* CURL detected, ports closed */
			else if (response == 'error_autodetect_api_curl_ports_blocked')
			{
				is_success = false;
				radio_curl.attr("checked", "checked");			
				message_string = objectL10n.oa_admin_js_201c;
			}												
			/* FSOCKOPEN detected, HTTPS */
			else if (response == 'success_autodetect_api_fsockopen_https')
			{
				is_success = true;
				radio_fsockopen.attr("checked", "checked");
				radio_use_http_1.attr("checked", "checked");	
				message_string = objectL10n.oa_admin_js_202a;
			}
			/* FSOCKOPEN detected, HTTP */
			else if (response == 'success_autodetect_api_fsockopen_http')
			{
				is_success = true;
				radio_fsockopen.attr("checked", "checked");
				radio_use_http_0.attr("checked", "checked");	
				message_string = objectL10n.oa_admin_js_202b;
			}			
			/* FSOCKOPEN detected, ports closed */
			else if (response == 'error_autodetect_api_fsockopen_ports_blocked')
			{
				is_success = false;
				radio_fsockopen.attr("checked", "checked");			
				message_string = objectL10n.oa_admin_js_202c;
			}	
			
			
			/* No handler detected */
			else
			{
				is_success = false;
				radio_curl.attr("checked", "checked");					
				message_string = objectL10n.oa_admin_js_211;
			}					
			
			message_container.removeClass('working_message');
			message_container.html(message_string);
			
			if (is_success){
				message_container.addClass('success_message');
			} else {
				message_container.addClass('error_message');
			}						
		});
		return false;	
	});
	
	/* Test API Settings */
	$('#oa_loudvoice_test_api_settings').click(function(){
		var message_string;		
		var message_container;
		var is_success;	
	
		var radio_fsockopen_val = jQuery("#oa_loudvoice_api_connection_handler_fsockopen:checked").val();	
		var radio_use_http_0 = jQuery("#oa_loudvoice_api_connection_handler_use_https_0:checked").val();
				
		var subdomain = jQuery('#oa_loudvoice_settings_api_subdomain').val();
		var key = jQuery('#oa_loudvoice_settings_api_key').val();
		var secret = jQuery('#oa_loudvoice_settings_api_secret').val();	
		var handler = (radio_fsockopen_val == 'fsockopen' ? 'fsockopen' : 'curl');		
		var use_https = (radio_use_http_0 == '0' ? '0' : '1');	
	
		var data = {
			_ajax_nonce: objectL10n.oa_loudvoice_ajax_nonce,
			action: 'check_api_settings',
			api_connection_handler: handler,
			api_connection_use_https: use_https,
			api_subdomain: subdomain,
			api_key: key,
			api_secret: secret
		};
		
		message_container = jQuery('#oa_loudvoice_api_test_result');	
		message_container.removeClass('success_message error_message').addClass('working_message');
		message_container.html(objectL10n.oa_admin_js_1);
		
		jQuery.post(ajaxurl,data, function(response) {		
			if (response == 'error_selected_handler_faulty'){
				is_success = false;
				message_string = objectL10n.oa_admin_js_116;
			}
			else if (response == 'error_not_all_fields_filled_out'){
				is_success = false;
				message_string = objectL10n.oa_admin_js_111;
			}
			else if (response == 'error_subdomain_wrong'){
				is_success = false;
				message_string = objectL10n.oa_admin_js_112;
			}
			else if (response == 'error_subdomain_wrong_syntax'){
				is_success = false;
				message_string = objectL10n.oa_admin_js_113;	
			}
			else if (response == 'error_communication'){
				is_success = false;
				message_string = objectL10n.oa_admin_js_114;					
			}
			else if (response == 'error_authentication_credentials_wrong'){
				is_success = false;
				message_string = objectL10n.oa_admin_js_115;		
			}
			else {
				is_success = true;
				message_string = objectL10n.oa_admin_js_101;		
			}
		
			message_container.removeClass('working_message');
			message_container.html(message_string);
		
			if (is_success){				
				message_container.addClass('success_message');
			} else {
				message_container.addClass('error_message');
			}			
		});
		return false;
	});
});