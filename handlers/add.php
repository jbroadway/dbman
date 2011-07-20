<?php

if (! User::require_admin ()) {
	header ('Location: /admin');
	exit;
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
	// add item
	$obj = new Model ($_POST);
	$obj->table = $_GET['table'];
	$obj->key = DBMan::primary_key ($_GET['table']);

	if ($obj->put ()) {
		$page->title = i18n_get ('Item Added');
		printf ("<p><a href='/dbman/browse?table=%s'>&laquo; %s</a></p>\n", $_GET['table'], i18n_get ('Back'));
		return;
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
	printf (
		'<p>%s:<br /><input type="text" name="%s" value="%s" />%s</p>' . "\n",
		$field->name,
		$field->name,
		$o->{$field->name},
		$rule
	);
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