<?php

if (! User::require_admin ()) {
	header ('Location: /admin');
	exit;
}

if (! isset ($_GET['query'])) {
	header ('Location: /dbman/index');
	exit;
}

$page->layout = false;
header ('Cache-control: private');
header ('Content-Type: text/plain');
header ('Content-Disposition: attachment; filename=query-export-' . gmdate ('Y-m-d') . '.csv');

$res = db_fetch_array ($_GET['query']);
echo preg_replace ('/[\r\n]+/', ' ', $_GET['query']) . "\n";
if (count ($res) > 0) {
	echo join (',', array_keys ((array) $res[0])) . "\n";
}

foreach ($res as $row) {
	$sep = '';
	foreach ((array) $row as $k => $v) {
		$v = str_replace ('"', '""', $v);
		if (strpos ($v, ',') !== false) {
			$v = '"' . $v . '"';
		}
		$v = str_replace (array ("\n", "\r"), array ('\\n', '\\r'), $v);
		echo $sep . $v;
		$sep = ',';
	}
	echo "\n";
}

?>