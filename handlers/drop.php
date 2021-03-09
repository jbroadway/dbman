<?php

$page->layout = 'admin';

if (! User::require_admin ()) {
	header ('Location: /admin');
	exit;
}

if (! DBMan::feature ('drop')) {
	$this->add_notification (__ ('Drop has been disabled.'));
	$this->redirect ('/dbman/index');
}

$f = new Form ('get', $this);
if (! $f->verify_csrf ('/dbman')) {
	header ('Location: /admin');
	exit;
}

if (! preg_match ('/^[a-zA-Z0-9_]+$/', $_GET['table'])) {
	header ('Location: /admin');
	exit;
}

if (! db_execute ('drop table `' . $_GET['table'] . '`')) {
	$page->title = i18n_get ('Error');
	printf ("<p><a href='/dbman/index'>&laquo; %s</a></p>\n", i18n_get ('Back'));
	echo '<p>' . db_error () . '</p>';
	return;
}

$this->add_notification (i18n_get ('Table Dropped') . ': ' . $_GET['table']);
$this->redirect ('/dbman/index');

?>