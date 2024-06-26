<?php

if (! User::require_admin ()) {
	header ('Location: /admin');
	exit;
}

if (! DBMan::feature ('add')) {
	$this->add_notification (__ ('Add has been disabled.'));
	$this->redirect ('/dbman/index');
}

if (! isset ($_GET['table'])) {
	header ('Location: /dbman/index');
	exit;
}

$page->layout = 'admin';
$page->title = i18n_get ('Add') . ' ' . $_GET['table'];

// get the field details of the table so we can dynamically generate the form
$fields = DBMan::table_info ($_GET['table']);

$f = new Form ('post');

// generate rules for required fields
foreach ($fields as $field) {
	$f->rules[$field->name] = DBMan::get_rules ($field);
}

if ($f->submit ()) {
	unset ($_POST['_token_']);
	
	// add item
	$obj = new Model ($_POST);
	$obj->table = $_GET['table'];

	if ($obj->put ()) {
		try {
			$data = [
				'table' => $_GET['table'],
				'pkey' => DBMan::primary_key ($_GET['table']),
				'values' => $_POST,
				'obj' => $obj
			];
			$this->hook ('dbman/add', $data);
		} catch (Exception $e) {
			error_log ('dbman/add hook error: ' . $e->getMessage ());
		}

		$this->add_notification (i18n_get ('Item added.'));
		$this->redirect ('/dbman/browse?table=' . $_GET['table']);
	}
	$page->title = i18n_get ('An Error Occurred');
	printf ("<p>%s</p>\n<p><a href='/dbman/browse?table=%s'>&laquo; %s</a></p>\n", $obj->error, $_GET['table'], i18n_get ('Back'));
	return;
}

// generate the form
$o = new StdClass;

// set default values
foreach ($fields as $field) {
	if (! empty ($field->default)) {
		$o->{$field->name} = $field->default;
	}
}

$o = $f->merge_values ($o);
$o->failed = $f->failed;
echo "<form method='post'>\n";

$timepicker_loaded = false;

// generate the form fields
foreach ($fields as $field) {
	// disable auto-incrementing fields
	if (DBMan::is_auto_incrementing ($field)) {
		printf (
			'<p>%s:<br /><input type="text" name="%s" value="" disabled /> %s</p>' . "\n",
			$field->name,
			$field->name,
			i18n_get ('Auto-incrementing field')
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
				'<p>%s:<br /><textarea name="%s" id="%s" cols="60" rows="8">%s</textarea>%s</p>' . "\n",
				$field->name,
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
					($o->{$field->name} == $value || $field->default === $value) ? ' selected' : '',
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
				'<p>%s:<br /><input type="text" name="%s" id="%s" value="%s" />%s</p>' . "\n",
				$field->name,
				$field->name,
				$field->name,
				Template::quotes ($o->{$field->name}),
				$rule
			);
			break;
	}
}
echo "<p><input type='submit' value='" . i18n_get ('Add Item') . "' /></p></form>\n";

// display any notices for failed fields
if (count ($o->failed) > 0) {
	echo "<script>$(function () {\n";
	foreach ($o->failed as $field) {
		printf ("\t$('#%s-notice').show ();\n", $field);
	}
	echo "});\n</script>\n";
}

?>