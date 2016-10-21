jQuery(document).ready(function($) {

    //get all tokens
    var tokens = [];
    jQuery('.oa-loudvoice-discussion-token').each(function(){
        tokens.push(jQuery(this).data('discussion_token'));
    });

    //unique tokens
    tokens = jQuery.unique( tokens );

    //load oneall libray 
    var s = document.createElement('script'); s.async = true;
    s.type = 'text/javascript';
    s.src = count_library+tokens.join(';');
    (document.getElementsByTagName('HEAD')[0] || document.getElementsByTagName('BODY')[0]).appendChild(s);
}());