;(function($, window, document) {
	$('dl.postprofile dd.profile-topics').each(function(){
		$(this).insertAfter($(this).siblings('dd.profile-posts'));
		$(this).show();
	});
})(jQuery, window, document);
