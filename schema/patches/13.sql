create table uryplayer.podcast_category
(
	podcast_catagry_id serial not null,
	category_name text not null
);

create unique index podcast_category_category_name_uindex
	on uryplayer.podcast_category (category_name);

create unique index podcast_category_podcast_catagry_id_uindex
	on uryplayer.podcast_category (podcast_catagry_id);

alter table uryplayer.podcast_category
	add constraint podcast_category_pk
		primary key (podcast_catagry_id);

insert into uryplayer.podcast_category (category_name) values ('URY Podcast'), ('Music Team Interview');

alter table uryplayer.podcast
	add category_id int;

alter table uryplayer.podcast
	add constraint podcast_category__fk
		foreign key (category_id) references uryplayer.podcast_category;

update uryplayer.podcast set category_id=1;
