<?php

$page->layout = 'admin';

if (! User::require_admin ()) {
	header ('Location: /admin');
	exit;
}

$page->title = i18n_get ('SQL Shell');

if (isset ($_GET['query']) && ! empty ($_GET['query'])) {
	$queries = explode (';', $_GET['query']);
} else {
	$queries = array ();
}
$res = array ();

foreach ($queries as $query) {
	$query = trim ($query);
	if ($query === '') {
		continue;
	}
	$exec = preg_match ('/^(alter|create|insert|update|delete|drop) /i', $query);
	if ($exec) {
		$res[$query] = array (
			'headers' => array (),
			'results' => db_execute ($query),
			'error' => false,
			'exec' => $exec
		);
	} else {
		$res[$query] = array (
			'headers' => array (),
			'results' => db_fetch_array ($query),
			'error' => false,
			'exec' => $exec
		);
	}
	if ($res[$query]['results'] === false) {
		$res[$query]['error'] = db_error ();
	} elseif (count ($res[$query]['results']) > 0) {
		$res[$query]['headers'] = array_keys ((array) $res[$query]['results'][0]);
	}
}

echo $tpl->render ('dbman/shell', array ('query' => $_GET['query']));

foreach ($res as $query => $info) {
	echo '<h5><pre>' . Template::sanitize ($query) . "</pre></h5>\n";

	if ($info['error']) {
		printf ("<p>%s: %s</p>\n", i18n_get ('Error'), $info['error']);
		continue;
	}

	if ($info['exec']) {
		printf ("<p>%s</p>\n", i18n_get ('Query executed.'));
		continue;
	}

	printf ("<p>%d %s:</p>\n", count ($info['results']), i18n_get ('results'));

	echo "<p><table width='100%'><tr>\n";
	foreach ($info['headers'] as $header) {
		printf ("<th>%s</th>\n", $header);
	}
	echo "</tr>\n";
	foreach ($info['results'] as $row) {
		echo "<tr>\n";
		foreach ((array) $row as $k => $v) {
			printf ("<td>%s</td>\n", Template::sanitize ($v));
		}
		echo "</tr>\n";
	}
	echo "</table></p>\n";
}

?>