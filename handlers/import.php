<?php

$page->layout = 'admin';

$this->require_admin ();

if (! DBMan::feature ('import')) {
	$this->add_notification (__ ('Import has been disabled.'));
	$this->redirect ('/dbman/index');
}

$page->title = __ ('Import CSV Data');

$form = new Form ('post', $this);

$form->data = array (
	'tables' => DBMan::list_tables ()
);

ini_set ('auto_detect_line_endings', true);
set_time_limit (0);

echo $form->handle (function ($form) use ($page, $tpl) {
	$count = 0;
	
	$key = DBMan::primary_key ($_POST['table']);
	$headers = false;

	if (($f = fopen ($_FILES['data']['tmp_name'], 'r')) !== false) {
		while (($row = fgetcsv ($f, 0, ',')) !== false) {
			// optionally skip first line as header
			if ($headers === false) {
				$headers = $row;
				continue;
			}

			$data = array_combine ($headers, $row);
			$obj = new Model ($data);
			$obj->table = $_POST['table'];
			$obj->key = $key;
			
			if (! $obj->put ()) {
				fclose ($f);
				unlink ($_FILES['data']['tmp_name']);
				printf ('<p>%s: %s</p>', __ ('Import failed'), $obj->error);
				printf ('<p><a href="/dbman/index">&laquo; %s</a></p>', __ ('Back'));
				return;
			}

			$count++;
		}
		fclose ($f);
		unlink ($_FILES['data']['tmp_name']);
		printf ('<p>%d %s</p>', $count, __ ('imported.'));
		printf ('<p><a href="/dbman/browse?table=%s">%s &raquo;</a></p>', $_POST['table'], __ ('Continue'));
	} else {
		printf ('<p>%s</p>', __ ('Import failed.'));
		printf ('<p><a href="/dbman/index">&laquo; %s</a></p>', __ ('Back'));
	}
});

?>