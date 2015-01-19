;(function($, window, document) {
	$('dl.postprofile dd.profile-topics').each(function(){
		$(this).insertBefore($(this).siblings('dd.profile-posts'));
		$(this).removeClass('usertopiccount-hidden');
	});
})(jQuery, window, document);
