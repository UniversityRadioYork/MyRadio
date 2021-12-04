-- Is a show set to play automatically? (true/false)
--  Basis for the "autoplayout" functionality
ALTER TABLE schedule.show_season_timeslot
	ADD playout bool DEFAULT FALSE;
