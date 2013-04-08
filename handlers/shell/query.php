<?php

$this->require_admin ();

$page->layout = false;

header ('Content-Type: application/json');

if (! isset ($_POST['query']) || empty ($_POST['query'])) {
	echo json_encode (array (
		'success' => false,
		'error' => __ ('No query specified')
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

	$exec = preg_match ('/^(alter|create|insert|update|delete|drop) /i', $query);
	if ($exec) {
		$cur = array (
			'sql' => Template::sanitize ($query),
			'headers' => array (),
			'results' => DB::execute ($query),
			'error' => false,
			'exec' => $exec
		);
	} else {
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
	} elseif (count ($cur['results']) > 0) {
		$cur['headers'] = array_keys ((array) $cur['results'][0]);
	}

	$res[] = $cur;
}

echo json_encode (array (
	'success' => true,
	'data' => $res
));

?>