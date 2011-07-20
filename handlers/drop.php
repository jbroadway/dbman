<?php

$page->layout = 'admin';

if (! User::require_admin ()) {
	header ('Location: /admin');
	exit;
}

if (! db_execute ('drop table `' . $_GET['table'] . '`')) {
	$page->title = i18n_get ('Error');
	printf ("<p><a href='/dbman/index'>&laquo; %s</a></p>\n", i18n_get ('Back'));
	echo '<p>' . db_error () . '</p>';
	return;
}

$page->title = i18n_get ('Table Dropped') . ': ' . $_GET['table'];
printf ("<p><a href='/dbman/index'>&laquo; %s</a></p>\n", i18n_get ('Back'));

?>