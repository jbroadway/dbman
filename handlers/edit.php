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
$page->title = i18n_get ('Editing Item') . ': ' . $_GET['table'] . '/' . $_GET['key'];

// get the field details of the table so we can dynamically generate the form
$fields = DBMan::table_info ($_GET['table']);

$f = new Form ('post');
$f->verify_csrf = false;

// generate rules for required fields
foreach ($fields as $field) {
	$f->rules[$field->name] = DBMan::get_rules ($field);
}

if ($f->submit ()) {
	// update item
	$pkey = DBMan::primary_key ($_GET['table']);
	$sql = 'update `' . $_GET['table'] . '` set ';
	$params = array ();
	$sep = '';
	
	foreach ($_POST as $k => $v) {
		if ($k == $pkey) {
			continue;
		}
		$sql .= $sep . $k . ' = ?';
		$params[] = $v;
		$sep = ', ';
	}
	
	$sql .= ' where ' . $pkey . ' = ?';
	$params[] = $_GET['key'];

	if (! db_execute ($sql, $params)) {
		$page->title = i18n_get ('An Error Occurred');
		printf ("<p>%s</p>\n<p><a href='/dbman/browse?table=%s'>&laquo; %s</a></p>\n", db_error (), $_GET['table'], i18n_get ('Back'));
		return;
	}
	$this->add_notification (i18n_get ('Item updated.'));
	$this->redirect ('/dbman/browse?table=' . $_GET['table']);
}

// get the initial object from the database
$o = db_single (
	sprintf (
		'select * from `%s` where %s = ?',
		$_GET['table'],
		DBMan::primary_key ($_GET['table'])
	),
	$_GET['key']
);

// generate the form
$o = $f->merge_values ($o);
$o->failed = $f->failed;
echo "<form method='post'>\n";

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
			printf (
				'<p>%s:<br /><textarea name="%s" cols="60" rows="8">%s</textarea>%s</p>' . "\n",
				$field->name,
				$field->name,
				Template::quotes ($o->{$field->name}),
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