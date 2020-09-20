BEGIN;

create table schedule.demo_link
(
	show_season_timeslot_id int
		constraint demo_link_show_season_timeslot_show_season_timeslot_id_fk
			references schedule.show_season_timeslot,
	link text
);

COMMIT;