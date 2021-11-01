BEGIN;

create table schedule.demo
(
	demo_id serial
		constraint demo_pk
			primary key,
	presenterstatusid int not null
		constraint demo_l_presenterstatus_presenterstatusid_fk
			references l_presenterstatus,
	demo_time timestamp with time zone not null,
	demo_link text,
	num_people int,
	memberid int
		constraint demo_member_memberid_fk
			references member
);

create table schedule.demo_attendee
(
	demo_id int not null
		constraint demo_attendee_demo_demo_id_fk
			references schedule.demo,
	memberid int not null
		constraint demo_attendee_member_memberid_fk
			references member
);

create table schedule.demo_waiting_list
(
	memberid int
		constraint demo_waiting_list_member_memberid_fk
			references member (memberid),
	presenterstatusid int
		constraint demo_waiting_list_l_presenterstatus_presenterstatusid_fk
			references l_presenterstatus (presenterstatusid),
	date_added timestamp with time zone
);



COMMIT;
