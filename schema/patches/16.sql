BEGIN;
CREATE TABLE schedule.highlight (
    highlight_id SERIAL PRIMARY KEY,
    show_season_timeslot_id INTEGER REFERENCES schedule.show_season_timeslot(show_season_timeslot_id) ON DELETE CASCADE,
    start_time TIMESTAMPTZ NOT NULL,
    end_time TIMESTAMPTZ NOT NULL,
    notes TEXT DEFAULT ''
);
UPDATE myradio.schema
SET value = 16
WHERE attr='version';
COMMIT;
