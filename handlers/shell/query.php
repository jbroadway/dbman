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

$queries = explode (';', $_POST['query']);
$res = array ();

foreach ($queries as $query) {
	$query = trim ($query);
	if ($query === '') {
		continue;
	}

	$exec = preg_match ('/^(alter|create|insert|update|delete|drop) /i', $query, $matches);
	if ($exec) {
		// Verify associated feature is enabled
		$feature = false;

		switch ($matches[1]) {
			case 'insert':
				if (! DBMan::feature ('add')) {
					$feature = __ ('Add has been disabled.');
				}
				break;

			case 'update':
				if (! DBMan::feature ('edit')) {
					$feature = __ ('Edit has been disabled.');
				}
				break;

			case 'delete':
				if (! DBMan::feature ('delete')) {
					$feature = __ ('Delete has been disabled.');
				}
				break;

			case 'drop':
				if (! DBMan::feature ('drop')) {
					$feature = __ ('Drop has been disabled.');
				}
				break;

			case 'alter':
			case 'create':
				if (! DBMan::feature ('schema')) {
					$feature = __ ('Schema changes have been disabled.');
				}
				break;
		}
		
		if ($feature !== false) {
			echo json_encode ([
				'success' => false,
				'error' => $feature
			]);
			return;
		}
		
		// Execute command
		$cur = array (
			'sql' => Template::sanitize ($query),
			'headers' => array (),
			'results' => DB::execute ($query),
			'error' => false,
			'exec' => $exec
		);
	} else {
		// Fetch results
		$cur = array (
			'sql' => Template::sanitize ($query),
			'headers' => array (),
			'results' => DB::fetch ($query),
			'error' => false,
			'exec' => $exec
		);
	}
	
	if ($cur['results'] === false) {
		$cur['error'] = DB::error ();
	} elseif (count ((array) $cur['results']) > 0) {
		$cur['headers'] = array_keys ((array) $cur['results'][0]);
	}

	$res[] = $cur;
}

echo json_encode (array (
	'success' => true,
	'data' => $res
));

?>
