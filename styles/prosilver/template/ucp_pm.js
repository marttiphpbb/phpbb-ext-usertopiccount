;(function($, window, document) {
	$('dl.postprofile dd.profile-topics').each(function(){
		$(this).insertBefore($(this).siblings('dd.profile-posts'));
	});
})(jQuery, window, document);
