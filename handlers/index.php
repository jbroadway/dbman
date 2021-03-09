<?php

$page->layout = 'admin';

if (! User::require_admin ()) {
	header ('Location: /admin');
	exit;
}

$f = new Form ('get', $this);
$csrf_token = $f->generate_csrf_token (false, '/dbman');

$page->title = 'DB Manager';

$tables = DBMan::list_tables ();

echo $tpl->render ('dbman/index', array ('tables' => $tables, 'csrf_token' => $csrf_token));

?>