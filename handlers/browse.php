<?php

$page->layout = 'admin';

$this->require_admin ();

$f = new Form ('get', $this);
$csrf_token = $f->generate_csrf_token ();

if (! isset ($_GET['table'])) {
	header ('Location: /dbman/index');
	exit;
}

$table_info = DBMan::table_info ($_GET['table']);

$limit = 20;
$num = (isset ($_GET['num'])) ? $_GET['num'] : 1;
$_GET['offset'] = ($num - 1) * $limit;
$q = isset ($_GET['q']) ? $_GET['q'] : ''; // search query
$q_fields = DBMan::fuzzy_search_fields ($table_info);
$q_exact = DBMan::exact_search_fields ($table_info);

// Build the search query from Model
$where = Model::query ()->where_search ($q, $q_fields, $q_exact);
$query_clause = join ('', $where->query_filters);
$query_params = $where->query_params;

$page->title = __ ('Table') . ': ' . Template::sanitize ($_GET['table']);

$this->run ('admin/util/fontawesome');
$page->add_script ('/apps/dbman/js/dbman.js');
$page->add_script (I18n::export (
	'Are you sure you want to delete these items?'
));

$pkey = DBMan::primary_key ($_GET['table']);

if (count ($query_params) > 0) {
	$count = DB::shift ('select count(*) from `' . $_GET['table'] . '` where ' . $query_clause, $query_params);
	$res = DB::fetch ('select * from `' . $_GET['table'] . '` where ' . $query_clause . ' limit ' . $limit . ' offset ' . $_GET['offset'], $query_params);
} else {
	$count = DB::shift ('select count(*) from `' . $_GET['table'] . '`');
	$res = DB::fetch ('select * from `' . $_GET['table'] . '` limit ' . $limit . ' offset ' . $_GET['offset']);
}
$more = ($count > $_GET['offset'] + $limit);
$prev = $_GET['offset'] - $limit;
$next = $_GET['offset'] + $limit;

if (count ($res) > 0) {
	$headers = array_keys ((array) $res[0]);
} else {
	$headers = array ();
}

$url = '/dbman/browse?table=' . urlencode ($_GET['table']) . '&q=' . urlencode ($q) . '&num=%d';

echo $tpl->render ('dbman/browse_header', [
	'table' => $_GET['table'],
	'csrf_token' => $csrf_token,
	'total' => $count,
	'count' => count ($res),
	'limit' => $limit,
	'multiple_pages' => ($count > $limit),
	'q' => $_GET['q'],
	'url' => $url
]);

echo "<form method='post' action='/dbman/delete' id='delete-form'>\n";
echo "<input type='hidden' name='table' value='" . Template::sanitize ($_GET['table']) . "' />\n";
echo "<input type='hidden' name='_token_' value='" . Template::sanitize ($csrf_token) . "' />\n";
echo "<table width='100%' style='clear: both'><tr>\n";
foreach ($headers as $header) {
	printf ("<th>%s</th>\n", $header);
}
echo "<th style='text-align: right'><a href='#' onclick='return dbman.delete ()' title='" . __ ('Delete items') . "' style='text-decoration: none'><i class='fa fa-times'></i></a>&nbsp;</th></tr>\n";
foreach ($res as $row) {
	echo "<tr>\n";
	foreach ((array) $row as $k => $v) {
		if (strlen ($v) > 48) {
			printf (
				"<td title=\"%s\">%s</td>\n",
				Template::sanitize ($v),
				DBMan::linkify (Template::sanitize (substr ($v, 0, 45) . '...'), Template::sanitize ($v))
			);
		} else {
			printf ("<td>%s</td>\n", DBMan::linkify (Template::sanitize ($v)));
		}
	}
	printf (
		"<td style='text-align: right'><a href='/dbman/edit?table=%s&key=%s&_token_=%s'>%s</a> | <input type='checkbox' name='key[]' value='%s' /></td>\n",
		Template::sanitize ($_GET['table']),
		DBMan::pkey_value ($row, $pkey),
		$csrf_token,
		__ ('Edit'),
		DBMan::pkey_value ($row, $pkey)
	);
	echo "</tr>\n";
}
echo "</table>\n";
echo "</form>\n";

if ($count > $limit) {
	echo $this->run ('navigation/pager', array (
		'style' => 'numbers',
		'url' => $url,
		'total' => $count,
		'count' => count ($res),
		'limit' => $limit
	));
}

?>