<?php

$page->layout = 'admin';

if (! User::require_admin ()) {
	header ('Location: /admin');
	exit;
}

$page->title = i18n_get ('Table Info') . ': ' . $_GET['table'];

$columns = DBMan::table_info ($_GET['table']);

echo $tpl->render ('dbman/info', array ('table' => $_GET['table'], 'columns' => $columns));

?>