jQuery(document).ready(function($) {
    // let default url
    var main_menu = $('#menu-comments');
    main_menu.find('a.wp-has-submenu').attr('href', 'edit-comments.php').end().find('.wp-submenu li:has(a[href="edit-comments.php"])').prependTo(main_menu.find('.wp-submenu ul'));

	// fix admin bar
    $('#wp-admin-bar-comments').find('a.ab-item').attr('href', 'edit-comments.php');
});
