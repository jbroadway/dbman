create table #prefix#dbman_saved_query (
	id integer primary key,
	title char(48) not null,
	query text not null,
	created datetime not null,
	created_by integer not null
);

create index #prefix#dbman_saved_query_title on #prefix#dbman_saved_query (title);
create index #prefix#dbman_saved_query_created on #prefix#dbman_saved_query (created);
create index #prefix#dbman_saved_query_creator on #prefix#dbman_saved_query (created_by);
