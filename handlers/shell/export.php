<?php

$this->require_admin ();

if (! DBMan::feature ('shell')) {
	$this->add_notification (__ ('Shell has been disabled.'));
	$this->redirect ('/dbman/index');
}

if (! DBMan::feature ('export')) {
	$this->add_notification (__ ('Export has been disabled.'));
	$this->redirect ('/dbman/index');
}

$f = new Form ('post', $this);
if (! $f->verify_csrf ('/dbman/shell/query')) {
	header ('Location: /admin');
	exit;
}

if (! isset ($_POST['query'])) {
	header ('Location: /admin');
	exit;
}

$page->layout = false;
header ('Cache-control: private');
header ('Content-Type: text/plain');
header ('Content-Disposition: attachment; filename=query-export-' . gmdate ('Y-m-d') . '.csv');

$query = html_entity_decode ($_POST['query'], ENT_QUOTES);

$res = DB::fetch ($query);
echo '"' . str_replace ('"', '""', preg_replace ('/[\r\n]+/', ' ', $query)) . "\"\n";
if ($res === false) {
	echo DB::error () . "\n";
	return;
}

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