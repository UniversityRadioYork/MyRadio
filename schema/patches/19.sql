BEGIN;
ALTER TABLE schedule.show_season_timeslot
  ADD COLUMN showplan_last_modified TIMESTAMPTZ DEFAULT NULL;

UPDATE myradio.schema
SET value = 14
WHERE attr='version';

COMMIT;
