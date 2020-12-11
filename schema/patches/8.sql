create table broadcast
(
	broadcast_id serial not null
		constraint broadcast_pk
			primary key,
	member_id int not null
		constraint broadcast_member_memberid_fk
			references member,
	path text not null,
	time timestamp not null
);

comment on table broadcast is 'MyRadio Broadcasts';

