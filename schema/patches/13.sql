BEGIN;
CREATE TABLE schedule.autoviz_configuration (
    autoviz_config_id SERIAL PRIMARY KEY,
    show_season_timeslot_id INTEGER UNIQUE REFERENCES schedule.show_season_timeslot(show_season_timeslot_id) ON DELETE CASCADE,
    record BOOLEAN DEFAULT 'f',
    stream_url TEXT NULL DEFAULT NULL,
    stream_key TEXT NULL DEFAULT NULL
);
COMMIT;
