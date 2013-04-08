<?php

$this->require_admin ();

if (! isset ($_POST['table'])) {
	header ('Location: /dbman/index');
	exit;
}

if (! preg_match ('/^[a-zA-Z0-9_]+$/', $_POST['table'])) {
	header ('Location: /dbman/index');
	exit;
}

$page->layout = 'admin';

$sql = sprintf (
	'delete from `%s` where %s = ?',
	$_POST['table'],
	DBMan::primary_key ($_POST['table'])
);

if (DB::execute ($sql, $_POST['key'])) {
	$this->add_notification (__ ('Item deleted.'));
	$this->redirect ('/dbman/browse?table=' . $_POST['table']);
}

$page->title = __ ('An Error Occurred');
printf ("<p>%s</p>\n<p><a href='/dbman/browse?table=%s'>&laquo; %s</a></p>\n", DB::error (), Template::sanitize ($_POST['table']), __ ('Back'));

?>