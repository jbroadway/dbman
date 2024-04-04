create table #prefix#dbman_saved_query (
	id int not null auto_increment primary key,
	title char(48) not null,
	query text not null,
	created datetime not null,
	created_by int unsigned not null,
	index (title),
	index (created),
	index (created_by)
);
