<?php

$this->require_admin ();

if (! DBMan::feature ('delete')) {
	$this->add_notification (__ ('Delete has been disabled.'));
	$this->redirect ('/dbman/index');
}

$f = new Form ('post', $this);
if (! $f->verify_csrf ('/dbman')) {
	header ('Location: /admin');
	exit;
}

if (! isset ($_POST['table'])) {
	header ('Location: /admin');
	exit;
}

if (! preg_match ('/^[a-zA-Z0-9_]+$/', $_POST['table'])) {
	header ('Location: /admin');
	exit;
}

$page->layout = 'admin';

$pkey = DBMan::primary_key ($_POST['table']);

if (is_array ($pkey)) {
	if (count ($pkey) == 2) {
		$sql = sprintf (
			'delete from `%s` where (`%s` = ? and `%s` = ?)',
			$_POST['table'],
			$pkey[0],
			$pkey[1]
		);
	} elseif (count ($pkey) == 3) {
		$sql = sprintf (
			'delete from `%s` where (`%s` = ? and `%s` = ? and `%s` = ?)',
			$_POST['table'],
			$pkey[0],
			$pkey[1],
			$pkey[2]
		);
	}
} else {
	$sql = sprintf (
		'delete from `%s` where %s = ?',
		$_POST['table'],
		DBMan::primary_key ($_POST['table'])
	);
}

if (is_array ($_POST['key'])) {
	foreach ($_POST['key'] as $key) {
		$key = is_array ($pkey) ? explode ('|', $key) : $key;

		if (! DB::execute ($sql, $key)) {
			$this->add_notification (__ ('An Error Occurred') . ': ' . DB::error ());
			$this->redirect ('/dbman/browse?table=' . urlencode ($_POST['table']));
		}
	}
	$this->add_notification (count ($_POST['key']) . ' ' . __ ('items deleted.'));
	$this->redirect ('/dbman/browse?table=' . urlencode ($_POST['table']));
} else {
	if (DB::execute ($sql, $_POST['key'])) {
		$this->add_notification (__ ('Item deleted.'));
		$this->redirect ('/dbman/browse?table=' . urlencode ($_POST['table']));
	}
}

$page->title = __ ('An Error Occurred');
printf ("<p>%s</p>\n<p><a href='/dbman/browse?table=%s'>&laquo; %s</a></p>\n", DB::error (), Template::sanitize ($_POST['table']), __ ('Back'));

?>