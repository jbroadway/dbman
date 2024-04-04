<?php

$this->require_admin ();

$page->layout = false;

header ('Content-Type: application/json');

if (! DBMan::feature ('shell')) {
	echo json_encode ([
		'success' => false,
		'error' => __ ('Shell has been disabled.')
	]);
	return;
}

$f = new Form ('post', $this);
if (! $f->verify_csrf ()) {
	echo json_encode ([
		'success' => false,
		'error' => __ ('Request validation failed.')
	]);
	return;
}

if (! isset ($_POST['query']) || empty ($_POST['query'])) {
	echo json_encode (array (
		'success' => false,
		'error' => __ ('No query specified.')
	));
	return;
}

if (! isset ($_POST['title']) || empty ($_POST['title'])) {
	echo json_encode (array (
		'success' => false,
		'error' => __ ('No query name specified.')
	));
	return;
}

$res = new dbman\SavedQuery ([
	'title' => $_POST['title'],
	'query' => $_POST['query'],
	'created' => gmdate ('Y-m-d H:i:s'),
	'created_by' => User::current ()->id
]);

if (! $res->put ()) {
	echo json_encode (array (
		'success' => false,
		'error' => $res->error
	));
}

echo json_encode (array (
	'success' => true,
	'data' => $res->orig ()
));
