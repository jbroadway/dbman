<?php

// keep unauthorized users out
$this->require_acl ('admin', $this->app);

// set the layout
$page->layout = 'admin';

// get the version and check if the app installed
$version = Appconf::get ($this->app, 'Admin', 'version');
$current = $this->installed ($this->app, $version);

if ($current === true) {
	// app is already installed and up-to-date, stop here
	$page->title = __ ('Already installed');
	printf ('<p><a href="/%s">%s</a>', Appconf::get ($this->app, 'Admin', 'handler'), __ ('Continue'));
	return;

} elseif ($current !== false) {
	// earlier version found, redirect to upgrade handler
	$this->redirect ('/' . Appconf::get ($this->app, 'Admin', 'upgrade'));
}

$page->title = sprintf (
	'%s: %s',
	__ ('Installing App'),
	Appconf::get ($this->app, 'Admin', 'name')
);

// grab the database driver and begin the transaction
$conn = conf ('Database', 'master');
$driver = $conn['driver'];
DB::beginTransaction ();

// parse the database schema into individual queries
$file = 'apps/' . $this->app . '/conf/install_' . $driver . '.sql';
$sql = sql_split (file_get_contents ($file));

// execute each query in turn
foreach ($sql as $query) {
	if (! DB::execute ($query)) {
		// show error and rollback on failures
		printf (
			'<p>%s</p><p class="visible-notice">%s: %s</p>',
			__ ('Install failed.'),
			__ ('Error'),
			DB::error ()
		);
		DB::rollback ();
		return;
	}
}

// commit transaction and mark the app installed
DB::commit ();
$this->mark_installed ($this->app, $version);

printf ('<p><a href="/%s">%s</a>', Appconf::get ($this->app, 'Admin', 'handler'), __ ('Done.'));
