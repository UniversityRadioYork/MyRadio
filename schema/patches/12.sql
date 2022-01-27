BEGIN;

ALTER TABLE schedule.demo
    ADD COLUMN signup_cutoff_hours INT DEFAULT 0;

COMMIT;