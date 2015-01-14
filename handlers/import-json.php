<?php

/**
 * This command imports a JSON encoded file into the
 * database.
 *
 * Please Note: Deletes existing table data from any
 * tables found and not empty.
 */

if (! $this->cli) {
	die ('Must be run from the command line.');
}

$page->layout = false;

if (! isset ($_SERVER['argv'][2])) {
	Cli::out ('Usage: ./elefant import-db <file>', 'info');
	die;
}

$file = $_SERVER['argv'][2];
if (! file_exists ($file)) {
	Cli::out ('** Error: File not found: ' . $file, 'error');
	die;
}

set_time_limit (0);

$import = json_decode (file_get_contents ($file));
$count = 0;

DB::beginTransaction ();

foreach ($import as $table => $rows) {
	if (count ($rows) > 0) {
		DB::execute ('delete from ' . $table);
		foreach ($rows as $n => $row) {
			$row = (array) $row;
			$keys = Model::backticks (array_keys ($row));
			$sql = 'insert into ' . $table . ' (' . join (', ', $keys) . ') values (';
			$vals = array_values ($row);

			$sep = '';
			foreach ($vals as $val) {
				$sql .= $sep . '?';
				$sep = ', ';
			}
			$sql .= ')';
		
			if (! DB::execute ($sql, $vals)) {
				Cli::out ('** Error: ' . DB::error (), 'error');
				DB::rollback ();
				return;
			}

			$count++;
		}
	}
}

DB::commit ();

Cli::out ($count . ' commands executed.', 'success');
