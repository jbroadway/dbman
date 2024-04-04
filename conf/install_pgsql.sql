create table #prefix#dbman_saved_query (
	id serial not null primary key,
	title character varying(48) not null,
	message text not null,
	created timestamp not null,
	created_by int not null
);

create index title on #prefix#dbman_saved_query (title);
create index created on #prefix#dbman_saved_query (created);
create index created_by on #prefix#dbman_saved_query (created_by);
