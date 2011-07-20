<?php

$page->layout = 'admin';

if (! User::require_admin ()) {
	header ('Location: /admin');
	exit;
}

$page->title = 'DB Manager';

$tables = DBMan::list_tables ();

echo $tpl->render ('dbman/index', array ('tables' => $tables));

?>