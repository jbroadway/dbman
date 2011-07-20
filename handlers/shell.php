<?php

$page->layout = 'admin';

if (! User::require_admin ()) {
	header ('Location: /admin');
	exit;
}

$page->title = i18n_get ('SQL Shell');

if (isset ($_GET['query']) && ! empty ($_GET['query'])) {
	$res = db_fetch_array ($_GET['query']);
} else {
	$res = array ();
}

if (count ($res) > 0) {
	$headers = array_keys ((array) $res[0]);
} else {
	$headers = array ();
}

echo $tpl->render ('dbman/shell', array ('query' => $_GET['query']));

if (isset ($_GET['query'])) {
	$err = db_error ();
	if ($err) {
		echo '<p>' . i18n_get ('Error') . ': ' . $err . "</p>\n";
	} else {
		echo '<p>' . count ($res) . ' ' . i18n_get ('results') . ":</p>\n";
	
		
		echo "<p><table width='100%'><tr>\n";
		foreach ($headers as $header) {
			printf ("<th>%s</th>\n", $header);
		}
		echo "</tr>\n";
		foreach ($res as $row) {
			echo "<tr>\n";
			foreach ((array) $row as $k => $v) {
				printf ("<td>%s</td>\n", $v);
			}
			echo "</tr>\n";
		}
		echo "</table></p>\n";
	}
}

?>