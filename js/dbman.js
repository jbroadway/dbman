var dbman = (function ($) {
	var self = {};
	
	/**
	 * Escape a value for output.
	 */
	self.esc = function (html) {
		return String(html)
			.replace (/&/g, '&amp;')
			.replace (/</g, '&lt;')
			.replace (/>/g, '&gt;')
			.replace (/"/g, '&quot;')
			.replace (/'/g, '&#039;');
	};
	
	/**
	 * Turns a link into a POST submission with the `data-*` properties
	 * as parameters. Usage:
	 *
	 *     <a href="/post/here"
	 *        data-table="{{table}}"
	 *        data-id="{{id}}"
	 *        onclick="return dbman.post (this)"
	 *     >{"Post me"}</a>
	 */
	self.post = function (el) {
		if (window.event) {
			window.event.preventDefault ();
		}
	
		var $el = $(el),
			params = $el.data (),
			url = $el.attr ('href'),
			$form = $('<form>')
				.attr ('method', 'post')
				.attr ('action', url);
	
		$.each (params, function (name, value) {
			$('<input type="hidden">')
				.attr ('name', name)
				.attr ('value', value)
				.appendTo ($form);
		});
	
		$form.appendTo ('body');
		$form.submit ();
		return false;
	};
	
	/**
	 * Makes an AJAX call for the results of an SQL query.
	 */
	self.query = function (query) {
		var params = {query: query};
		console.log (params);
		if (! query) {
			return;
		}
		
		$('#results').html ($.i18n ('Please wait...'));

		$.post ('/dbman/shell/query', params, function (res) {
			if (! res.success) {
				$.add_notification (res.error);
				return;
			}
			
			var results = $('#results').html ('');
			
			for (var q in res.data) {
				var sql = res.data[q].sql,
					headers = res.data[q].headers,
					rows = res.data[q].results;

				results.append ('<h5><pre>' + dbman.esc (sql) + '</pre></h5>');

				if (res.data[q].error) {
					results.append ('<p>' + $.i18n ('Error') + ': ' + res.data[q].error);
					continue;
				}

				if (res.data[q].exec) {
					results.append ('<p>' + $.i18n ('Query executed.') + '</p>');
					continue;
				}

				results.append (
					'<p>' +
						res.data[q].results.length + ' ' + $.i18n ('results') + ' (' +
						'<a href="/dbman/shell/export" data-query="' + dbman.esc (res.data[q].sql) + '" ' +
							'onclick="return dbman.post (this)">' + $.i18n ('Export') +
						'</a>):' +
					'</p>'
				);

				var table = '<p><table width="100%"><tr>';

				for (var h in res.data[q].headers) {
					table += '<th>' + dbman.esc (res.data[q].headers[h]) + '</th>';
				}
				table += '</tr>';

				for (var i = 0; i < res.data[q].results.length; i++) {
					table += '<tr>';
					for (var k in res.data[q].results[i]) {
						table += '<td>' + dbman.esc (res.data[q].results[i][k]) + '</td>';
					}
					table += '</tr>';
				}

				table += '</table></p>';

				results.append (table);
			}
		});

		return false;
	};

	/**
	 * Delete the selected items.
	 */
	self.delete = function () {
		if (! confirm ($.i18n ('Are you sure you want to delete these items?'))) {
			return false;
		}

		$('#delete-form')[0].submit ();
		return false;
	};
	
	return self;
})(jQuery);