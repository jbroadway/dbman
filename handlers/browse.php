<?php

$page->layout = 'admin';

$this->require_admin ();

if (! isset ($_GET['table'])) {
	header ('Location: /dbman/index');
	exit;
}

$limit = 20;
$num = (isset ($_GET['num'])) ? $_GET['num'] : 1;
$_GET['offset'] = ($num - 1) * $limit;

$page->title = __ ('Table') . ': ' . Template::sanitize ($_GET['table']);

$pkey = DBMan::primary_key ($_GET['table']);
$count = DB::shift ('select count(*) from `' . $_GET['table'] . '`');
$res = DB::fetch ('select * from `' . $_GET['table'] . '` limit ' . $limit . ' offset ' . $_GET['offset']);
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
	__ ('Back'),
	Template::sanitize ($_GET['table']),
	__ ('Add Item')
);

echo '<p style="float: left">' . $count . ' ' . __ ('results') . ":</p>\n";

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
		"<td><a href='/dbman/edit?table=%s&key=%s'>%s</a> | <a href='/dbman/delete' data-table='%s' data-key='%s' onclick=\"return $.confirm_and_post (this, 'Are you sure you want to delete this item?')\">%s</a></td>\n",
		Template::sanitize ($_GET['table']),
		$row->{$pkey},
		__ ('Edit'),
		Template::sanitize ($_GET['table']),
		$row->{$pkey},
		__ ('Delete')
	);
	echo "</tr>\n";
}
echo "</table></p>\n";

if ($count > $limit) {
	echo $this->run ('navigation/pager', array (
		'style' => 'numbers',
		'url' => '/dbman/browse?table=' . urlencode ($_GET['table']) . '&num=%d',
		'total' => $count,
		'count' => count ($res),
		'limit' => $limit
	));
}

?>