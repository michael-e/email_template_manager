(function($) {
	$('textarea').blur(function() {
		var source = $(this).val(),
		utilities = $('#utilities li');

		// Remove current selection
		utilities.removeClass('selected');

		// Get utitities names
		utilities.find('a').each(function() {
			var utility = $(this);
			expression = new RegExp('href=["\']?(?:\\.{2}/utilities/)?' + utility.text());

			// Check for utility occurrences
			if(expression.test(source)) {
				utility.parent().addClass('selected');
			}
		});
	}).blur();
})(jQuery.noConflict());
