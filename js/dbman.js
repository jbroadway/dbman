var dbman = (function ($) {
	var self = {};

	self.csrf_token = '';
	self.save_token = '';
	self.saved_queries = [];

	/**
	 * Set the CSRF tokens upon initialization.
	 */
	self.set_tokens = function (query, save) {
		self.csrf_token = query;
		self.save_token = save;
	}

	self.init_saved_queries = function (queries) {
		self.saved_queries = queries;
		self.update_query_list ();
	}

	self.add_saved_query = function (res) {
		self.saved_queries.append (res);
		self.update_query_list ();
	}

	self.update_query_list = function () {
		var $select = $('#queries'),
			escape = document.createElement ('textarea');

		$select.empty ();

		$select.append ($('<option>', {
			value: '',
			text: $.i18n ('- Saved Queries -')
		}));

		// Add options
		for (i = 0; i < self.saved_queries.length; i++) {
			var query = self.saved_queries[i];

			escape.innerHTML = query.query;

			$select.append ($('<option>', {
				value: i,
				text: query.title + ' (' + escape.textContent + ')'
			}));
		}
	}
	
	self.select_saved_query = function () {
		var $query = $('#query'),
			index = $('#queries').find (':selected').val ();
		
		if (index == '') {
			return;
		}

		var res = self.saved_queries[index],
			val = $.codemirror['query'].getValue ();

		if (val == '') {
			$.codemirror['query'].setValue ('-- ' + res.title + "\n" + res.query);
		} else {
			$.codemirror['query'].setValue (val + ";\n\n-- " + res.title + "\n" + res.query);
		}
	};

	/**
	 * Escape a value for output.
	 */
	self.esc = function (html) {
		var res = String(html)
			.replace (/&/g, '&amp;')
			.replace (/</g, '&lt;')
			.replace (/>/g, '&gt;')
			.replace (/"/g, '&quot;')
			.replace (/'/g, '&#039;');
		
		if (res.match (/^https?:\/\//)) {
			return '<a href="' + res + '">' + res + '</a>';
		}
		
		return res;
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

		$('<input type="hidden">')
			.attr ('name', '_token_')
			.attr ('value', $('#csrf-token').val ())
			.appendTo ($form);

		$form.appendTo ('body');
		$form.submit ();
		return false;
	};

	/**
	 * Save a query to the saved queries list. Usage:
	 *
	 *     <a href="/dbman/shell/save"
	 *        data-query="{{query}}"
	 *        onclick="return dbman.save (this)"
	 *     >{"Save query"}</a>
	 */
	self.save = function (el) {
		if (window.event) {
			window.event.preventDefault ();
		}

		var title = prompt ('Query name', '');
		
		if (title == null || title == '') {
			return false; // Cancelled request
		}

		var $el = $(el),
			query = $el.data ('query');

		var params = {query: query, title: title, _token_: self.save_token};
		
		$.post ('/dbman/shell/save', params, function (res) {
			console.log (res);
			if (! res.success) {
				$('#results').html (res.error);
				return;
			}

			self.add_saved_query (res.data);
		});

		return false;
	};
	
	/**
	 * Makes an AJAX call for the results of an SQL query.
	 */
	self.query = function (query) {
		if (! query) {
			return;
		}

		var params = {query: query, _token_: self.csrf_token};
		
		$('#results').html ($.i18n ('Please wait...'));

		$.post ('/dbman/shell/query', params, function (res) {
			console.log (res);
			if (! res.success) {
				$('#results').html (res.error);
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
						'</a>, ' +
						'<a href="/dbman/shell/save" data-query="' + dbman.esc (res.data[q].sql) + '" ' +
							'onclick="return dbman.save (this)">' + $.i18n ('Save Query') +
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