<?php

$page->layout = 'admin';

if (! User::require_admin ()) {
	header ('Location: /admin');
	exit;
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

$page->title = i18n_get ('Table Info') . ': ' . $_GET['table'];

$columns = DBMan::table_info ($_GET['table']);

echo $tpl->render ('dbman/info', array ('table' => $_GET['table'], 'columns' => $columns));

?>