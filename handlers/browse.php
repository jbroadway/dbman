<?php

$page->layout = 'admin';

if (! User::require_admin ()) {
	header ('Location: /admin');
	exit;
}

if (! isset ($_GET['table'])) {
	header ('Location: /dbman/index');
	exit;
}

$limit = 20;
$num = (isset ($_GET['num'])) ? $_GET['num'] : 1;
$_GET['offset'] = ($num - 1) * $limit;

$page->title = i18n_get ('Table') . ': ' . $_GET['table'];

$pkey = DBMan::primary_key ($_GET['table']);
$count = db_shift ('select count(*) from `' . $_GET['table'] . '`');
$res = db_fetch_array ('select * from `' . $_GET['table'] . '` limit ' . $limit . ' offset ' . $_GET['offset']);
$more = ($count > $_GET['offset'] + $limit);
$prev = $_GET['offset'] - $limit;
$next = $_GET['offset'] + $limit;

if (count ($res) > 0) {
	$headers = array_keys ((array) $res[0]);
} else {
	$headers = array ();
}

printf (
	"<p><a href='/dbman/index'>&laquo; %s</a> | <a href='/dbman/add?table=%s'>%s</a></p>\n",
	i18n_get ('Back'),
	$_GET['table'],
	i18n_get ('Add Item')
);

echo '<p style="float: left">' . $count . ' ' . i18n_get ('results') . ":</p>\n";

if ($count > $limit) {
	echo '<div style="float: right">' . $this->run ('navigation/pager', array (
		'style' => 'numbers',
		'url' => '/dbman/browse?table=' . $_GET['table'] . '&num=%d',
		'total' => $count,
		'count' => count ($res),
		'limit' => $limit
	)) . '</div>';
}

echo "<p style='clear: both'><table width='100%'><tr>\n";
foreach ($headers as $header) {
	printf ("<th>%s</th>\n", $header);
}
echo "<th>&nbsp;</th></tr>\n";
foreach ($res as $row) {
	echo "<tr>\n";
	foreach ((array) $row as $k => $v) {
		if (strlen ($v) > 48) {
			printf (
				"<td title=\"%s\">%s...</td>\n",
				Template::sanitize ($v),
				Template::sanitize (substr ($v, 0, 45))
			);
		} else {
			printf ("<td>%s</td>\n", Template::sanitize ($v));
		}
	}
	printf (
		"<td><a href='/dbman/edit?table=%s&key=%s'>%s</a> | <a href='/dbman/delete?table=%s&key=%s' onclick=\"return confirm ('Are you sure you want to delete this item?')\">%s</a></td>\n",
		$_GET['table'],
		$row->{$pkey},
		i18n_get ('Edit'),
		$_GET['table'],
		$row->{$pkey},
		i18n_get ('Delete')
	);
	echo "</tr>\n";
}
echo "</table></p>\n";

if ($count > $limit) {
	echo $this->run ('navigation/pager', array (
		'style' => 'numbers',
		'url' => '/dbman/browse?table=' . $_GET['table'] . '&num=%d',
		'total' => $count,
		'count' => count ($res),
		'limit' => $limit
	));
}

?>