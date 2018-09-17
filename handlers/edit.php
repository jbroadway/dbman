<?php

if (! User::require_admin ()) {
	header ('Location: /admin');
	exit;
}

if (! isset ($_GET['table'])) {
	header ('Location: /dbman/index');
	exit;
}

if (! preg_match ('/^[a-zA-Z0-9_]+$/', $_GET['table'])) {
	header ('Location: /admin');
	exit;
}

$page->layout = 'admin';
$page->title = i18n_get ('Editing Item') . ': ' . $_GET['table'] . '/' . str_replace ('|', '+', $_GET['key']);

// get the field details of the table so we can dynamically generate the form
$fields = DBMan::table_info ($_GET['table']);
$pkey = DBMan::primary_key ($_GET['table']);

$f = new Form ('post');

// generate rules for required fields
foreach ($fields as $field) {
	$f->rules[$field->name] = DBMan::get_rules ($field);
}

if ($f->submit ()) {
	unset ($_POST['_token_']);
	
	// update item
	$pkey = DBMan::primary_key ($_GET['table']);
	$sql = 'update `' . $_GET['table'] . '` set ';
	$params = array ();
	$sep = '';
	
	foreach ($_POST as $k => $v) {
		if ($k == $pkey) {
			continue;
		}
		$sql .= $sep . '`' . $k . '` = ?';
		$params[] = $v;
		$sep = ', ';
	}
	
	if (is_array ($pkey)) {
		if (count ($pkey) == 2) {
			$sql .= ' where (`' . $pkey[0] . '` = ? and `' . $pkey[1] . '` = ?)';
			$keys = explode ('|', $_GET['key']);
			$params[] = $keys[0];
			$params[] = $keys[1];
		} elseif (count ($pkey) == 3) {
			$sql .= ' where (`' . $pkey[0] . '` = ? and `' . $pkey[1] . '` = ? and `' . $pkey[2] . '` = ?)';
			$keys = explode ('|', $_GET['key']);
			$params[] = $keys[0];
			$params[] = $keys[1];
			$params[] = $keys[2];
		}
	} else {
		$sql .= ' where `' . $pkey . '` = ?';
		$params[] = $_GET['key'];
	}

	if (! db_execute ($sql, $params)) {
		$page->title = i18n_get ('An Error Occurred');
		printf ("<p>%s</p>\n<p><a href='/dbman/browse?table=%s'>&laquo; %s</a></p>\n", db_error (), $_GET['table'], i18n_get ('Back'));
		return;
	}
	
	$this->add_notification (i18n_get ('Item updated.'));
	$this->redirect ('/dbman/browse?table=' . $_GET['table']);
}

// get the initial object from the database
if (is_array ($pkey)) {
	if (count ($pkey) == 2) {
		$o = db_single (
			sprintf (
				'select * from `%s` where (`%s` = ? and `%s` = ?)',
				$_GET['table'],
				$pkey[0],
				$pkey[1]
			),
			explode ('|', $_GET['key'])
		);
	} elseif (count ($pkey) == 3) {
		$o = db_single (
			sprintf (
				'select * from `%s` where (`%s` = ? and `%s` = ? and `%s` = ?)',
				$_GET['table'],
				$pkey[0],
				$pkey[1],
				$pkey[2]
			),
			explode ('|', $_GET['key'])
		);
	}
} else {
	$o = db_single (
		sprintf (
			'select * from `%s` where %s = ?',
			$_GET['table'],
			DBMan::primary_key ($_GET['table'])
		),
		$_GET['key']
	);
}

// generate the form
$o = $f->merge_values ($o);
$o->failed = $f->failed;
echo "<form method='post'>\n";

$timepicker_loaded = false;

// generate the form fields
foreach ($fields as $field) {
	// disable auto-incrementing fields
	if (DBMan::is_auto_incrementing ($field)) {
		printf (
			'<input type="hidden" name="%s" value="%s" />' . "\n",
			$field->name,
			$o->{$field->name}
		);
		continue;
	}

	if (isset ($f->rules[$field->name]['type']) && $f->rules[$field->name]['type'] == 'numeric') {
		$rule = ' <span class="notice" id="' . $field->name . '-notice">' . i18n_getf ('You must enter a number for %s', $field->name) . '</span>';
	} elseif (isset ($f->rules[$field->name]['length'])) {
		$rule = ' <span class="notice" id="' . $field->name . '-notice">' . i18n_getf ('You must enter a value for %s no longer than %s', $field->name, $field->length) . '</span>';
	} elseif (isset ($f->rules[$field->name]['not empty'])) {
		$rule = ' <span class="notice" id="' . $field->name . '-notice">' . i18n_getf ('You must enter a value for %s', $field->name) . '</span>';
	} else {
		$rule = '';
	}

	switch ($field->type) {
		case 'text':
		case 'mediumtext':
			printf (
				'<p>%s:<br /><textarea name="%s" cols="60" rows="8">%s</textarea>%s</p>' . "\n",
				$field->name,
				$field->name,
				Template::quotes ($o->{$field->name}),
				$rule
			);
			break;
		case 'date':
			if (! $timepicker_loaded) {
				$page->add_script ('/js/jquery-ui/jquery-ui.css');
				$page->add_script ('/js/jquery-ui/jquery-ui.min.js');
				$page->add_script (
					'<style>
					/* css for timepicker */
					.ui-timepicker-div .ui-widget-header{ margin-bottom: 8px; }
					.ui-timepicker-div dl{ text-align: left; }
					.ui-timepicker-div dl dt{ height: 25px; }
					.ui-timepicker-div dl dd{ margin: -25px 0 10px 65px; }
					.ui-timepicker-div td { font-size: 90%; }
					</style>'
				);
				$page->add_script ('/apps/blog/js/jquery.timepicker.js');
				$timepicker_loaded = true;
			}
			printf (
				'<p>%s:<br /><input type="text" name="%s" id="%s" value="%s" />%s</p>' . "\n",
				$field->name,
				$field->name,
				$field->name,
				Template::quotes ($o->{$field->name}),
				$rule
			);
			printf (
				"<script>$(function () { $('#%s').datepicker ({ dateFormat: 'yy-mm-dd' }); });</script>\n",
				$field->name
			);
			break;
		case 'time':
			if (! $timepicker_loaded) {
				$page->add_script ('/js/jquery-ui/jquery-ui.css');
				$page->add_script ('/js/jquery-ui/jquery-ui.min.js');
				$page->add_script (
					'<style>
					/* css for timepicker */
					.ui-timepicker-div .ui-widget-header{ margin-bottom: 8px; }
					.ui-timepicker-div dl{ text-align: left; }
					.ui-timepicker-div dl dt{ height: 25px; }
					.ui-timepicker-div dl dd{ margin: -25px 0 10px 65px; }
					.ui-timepicker-div td { font-size: 90%; }
					</style>'
				);
				$page->add_script ('/apps/blog/js/jquery.timepicker.js');
				$timepicker_loaded = true;
			}
			printf (
				'<p>%s:<br /><input type="text" name="%s" id="%s" value="%s" />%s</p>' . "\n",
				$field->name,
				$field->name,
				$field->name,
				Template::quotes ($o->{$field->name}),
				$rule
			);
			printf (
				"<script>$(function () { $('#%s').timepicker ({ timeFormat: 'hh:mm:ss', hourGrid: 4, minuteGrid: 10 }); });</script>\n",
				$field->name
			);
			break;
		case 'datetime':
			if (! $timepicker_loaded) {
				$page->add_script ('/js/jquery-ui/jquery-ui.css');
				$page->add_script ('/js/jquery-ui/jquery-ui.min.js');
				$page->add_script (
					'<style>
					/* css for timepicker */
					.ui-timepicker-div .ui-widget-header{ margin-bottom: 8px; }
					.ui-timepicker-div dl{ text-align: left; }
					.ui-timepicker-div dl dt{ height: 25px; }
					.ui-timepicker-div dl dd{ margin: -25px 0 10px 65px; }
					.ui-timepicker-div td { font-size: 90%; }
					</style>'
				);
				$page->add_script ('/apps/blog/js/jquery.timepicker.js');
				$timepicker_loaded = true;
			}
			printf (
				'<p>%s:<br /><input type="text" name="%s" id="%s" value="%s" />%s</p>' . "\n",
				$field->name,
				$field->name,
				$field->name,
				Template::quotes ($o->{$field->name}),
				$rule
			);
			printf (
				"<script>$(function () { $('#%s').datetimepicker ({ timeFormat: 'hh:mm:ss', dateFormat: 'yy-mm-dd', hourGrid: 4, minuteGrid: 10 }); });</script>\n",
				$field->name
			);
			break;
		case 'enum':
			printf (
				'<p>%s:<br /><select name="%s" id="%s">' . "\n",
				$field->name,
				$field->name,
				$field->name
			);
			if ($field->notnull === 'Yes') {
				echo "<option value=\"\">- select -</option>\n";
			}
			foreach ($field->values as $value) {
				printf (
					'<option value="%s"%s>%s</option>' . "\n",
					$value,
					($o->{$field->name} === $value) ? ' selected' : '',
					$value
				);
			}
			printf (
				'</select>%s</p>' . "\n",
				$rule
			);
			break;
		case 'select':
			printf (
				'<p>%s:<br /><select name="%s" id="%s">' . "\n",
				$field->name,
				$field->name,
				$field->name
			);
			if ($field->notnull === 'Yes') {
				echo "<option value=\"\">- select -</option>\n";
			}
			foreach ($field->values as $value => $display) {
				printf (
					'<option value="%s"%s>%s (%s)</option>' . "\n",
					$value,
					($o->{$field->name} === $value || $field->default === $value) ? ' selected' : '',
					$display,
					$value
				);
			}
			printf (
				'</select>%s</p>' . "\n",
				$rule
			);
			break;
		default:
			printf (
				'<p>%s:<br /><input type="text" name="%s" value="%s" />%s</p>' . "\n",
				$field->name,
				$field->name,
				Template::quotes ($o->{$field->name}),
				$rule
			);
			break;
	}
}
echo "<p><input type='submit' value='" . i18n_get ('Save Item') . "' /></p></form>\n";

// display any notices for failed fields
if (count ($o->failed) > 0) {
	echo "<script>$(function () {\n";
	foreach ($o->failed as $field) {
		printf ("\t$('#%s-notice').show ();\n", $field);
	}
	echo "});\n</script>\n";
}

?>