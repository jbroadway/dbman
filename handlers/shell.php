<?php

$page->layout = 'admin';

$this->require_admin ();

if (! DBMan::feature ('shell')) {
	$this->add_notification (__ ('Shell has been disabled.'));
	$this->redirect ('/dbman/index');
}

$f = new Form ('post', $this);

$csrf_token = $f->generate_csrf_token (false, '/dbman/shell/query');
$save_token = $f->generate_csrf_token (false, '/dbman/shell/save');

$page->title = __ ('SQL Shell');

$page->add_script ('/apps/dbman/js/dbman.js?v=5');

$page->add_script (I18n::export (
	'Error',
	'Query executed.',
	'Please wait...',
	'results',
	'Export',
	'Save Query',
	'- Saved Queries -',
	'Query name',
	'Are you sure you want to delete these items?'
));

echo $tpl->render (
	'dbman/shell', 
	array (
		'query' => $_POST['query'],
		'csrf_token' => $csrf_token,
		'save_token' => $save_token,
		'saved_queries' => dbman\SavedQuery::query ()
			->order ('title', 'asc')
			->fetch_orig ()
	)
);

?>