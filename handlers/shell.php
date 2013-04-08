<?php

$page->layout = 'admin';

$this->require_admin ();

$page->title = __ ('SQL Shell');

$page->add_script ('/apps/dbman/js/dbman.js');
$page->add_script (I18n::export (
	'Error',
	'Query executed.',
	'Please wait...',
	'results',
	'Export'
));
echo $tpl->render ('dbman/shell', array ('query' => $_POST['query']));

?>