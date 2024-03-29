<?php

$page->layout = 'admin';

$this->require_admin ();

$f = new Form ('get', $this);
$csrf_token = $f->generate_csrf_token (false, '/dbman');

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
	$total = DB::shift ('select count(*) from `' . $_GET['table'] . '` where ' . $query_clause, $query_params);
	$res = DB::fetch ('select * from `' . $_GET['table'] . '` where ' . $query_clause . ' limit ' . $limit . ' offset ' . $_GET['offset'], $query_params);
} else {
	$total = DB::shift ('select count(*) from `' . $_GET['table'] . '`');
	$res = DB::fetch ('select * from `' . $_GET['table'] . '` limit ' . $limit . ' offset ' . $_GET['offset']);
}
$more = ($count > $_GET['offset'] + $limit);
$prev = $_GET['offset'] - $limit;
$next = $_GET['offset'] + $limit;

if (is_array ($res) && count ($res) > 0) {
	$headers = array_keys ((array) $res[0]);
} else {
	$headers = array ();
}

$url = '/dbman/browse?table=' . urlencode ($_GET['table']) . '&q=' . urlencode ($q) . '&num=%d';

$total = is_numeric ($total) ? $total : 0;
$count = is_array ($res) ? count ($res) : 0;

echo $tpl->render ('dbman/browse_header', [
	'table' => $_GET['table'],
	'csrf_token' => $csrf_token,
	'total' => $total,
	'count' => $count,
	'limit' => $limit,
	'multiple_pages' => ($total > $limit) ? true : false,
	'q' => $_GET['q'],
	'url' => $url
]);



if (is_array ($res)) {
	echo "<form method='post' action='/dbman/delete' id='delete-form'>\n";
	echo "<input type='hidden' name='table' value='" . Template::sanitize ($_GET['table']) . "' />\n";
	echo "<input type='hidden' name='_token_' value='" . Template::sanitize ($csrf_token) . "' />\n";
	echo "<table width='100%' style='clear: both'><tr>\n";
	foreach ($headers as $header) {
		printf ("<th>%s</th>\n", $header);
	}

	if (DBMan::feature ('delete')) {
		echo "<th style='text-align: right'><a href='#' onclick='return dbman.delete ()' title='" . __ ('Delete items') . "' style='text-decoration: none'><i class='fa fa-times'></i></a>&nbsp;</th></tr>\n";
	} else {
		echo "<th>&nbsp;</th></tr>\n";
	}

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
		echo $tpl->render ('dbman/rowoptions', [
			'table' => $_GET['table'],
			'pkey' => DBMan::pkey_value ($row, $pkey),
			'tok' => $csrf_token
		]);
		echo "</tr>\n";
	}

	echo "</table>\n";
	echo "</form>\n";
}

if ($total > $limit) {
	echo $this->run ('navigation/pager', array (
		'style' => 'numbers',
		'url' => $url,
		'total' => $total,
		'count' => $count,
		'limit' => $limit
	));
}

?>