BEGIN;

create table schedule.events
(
	event_id text not null
		constraint events_pk
			primary key,
	title text not null,
	description text
);

create table schedule.event_timeslots
(
	event_id text not null
		constraint event_timeslots_events_event_id_fk
			references schedule.events,
	show_season_timeslot_id int not null
		constraint event_timeslots_show_season_timeslot_show_season_timeslot_id_fk
			references schedule.show_season_timeslot
);

COMMIT;