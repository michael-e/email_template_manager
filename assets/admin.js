(function($) {
	$(document).ready(function() {
		$('textarea').unbind('blur');
		$('textarea').blur(function(event) {
			var source = $(this).val(),
			utilities = $('#utilities li');

			// Remove current selection
			utilities.removeClass('selected');

			// Get utitities names
			utilities.find('a').each(function() {
				var utility = $(this);
				expression = new RegExp('href="workspace/utilities/' + utility.text());

				// Check for utility occurrences
				if(expression.test(source)) {
					utility.parent().addClass('selected');
				}
			});
		}).blur();

		$('#utilities li').unbind('click');
		$('#utilities li').click(function(event) {
			if ($(event.target).is('a')) return;

			var editor = $('textarea.code'),
				lines = editor.val().split('\n'),
				link = $(this).find('a').text(),
				statement = '<xsl:import href="workspace/utilities/' + link + '"/>',
				regexp = '^<xsl:import href="workspace/utilities/' + link + '"',
				newLine = '\n',
				numberOfNewLines = 1;

			if ($(this).hasClass('selected')) {
				for (var i = 0; i < lines.length; i++) {
					console.log($.trim(lines[i]));
					if ($.trim(lines[i]).match(regexp) != null) {
						(lines[i + 1] === '' && $.trim(lines[i - 1]).substring(0, 11) !== '<xsl:import') ? lines.splice(i, 2) : lines.splice(i, 1);
						break;
					}
				}

				editor.val(lines.join(newLine));
				$(this).removeClass('selected');
			}
			else {
				for (var i = 0; i < lines.length; i++) {
					if ($.trim(lines[i]).substring(0, 4) === '<!--' || $.trim(lines[i]).match('^<xsl:(?:import|variable|output|comment|template)')) {

						numberOfNewLines = $.trim(lines[i]).substring(0, 11) === '<xsl:import' ? 1 : 2;
						// we are inside the page template editor
						lines[i] = statement + Array(numberOfNewLines + 1).join(newLine) + lines[i];
						break;
					}
				}

				editor.val(lines.join(newLine));
				$(this).addClass('selected');
			}
			return false;
		});
	});
})(jQuery.noConflict());