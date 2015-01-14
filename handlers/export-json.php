<?php

/**
 * This command exports a backup of the database into
 * the specified file encoded as a JSON data structure.
 */

if (! $this->cli) {
	die ('Must be run from the command line.');
}

$page->layout = false;

set_time_limit (0);

$tables = DBMan::list_tables ();
$export = array ();

foreach ($tables as $table) {
	$export[$table] = DB::fetch ('select * from ' . $table);
}

echo json_encode ($export);
