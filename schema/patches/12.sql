BEGIN;

ALTER TABLE schedule.demo
    ADD COLUMN signup_cutoff_hours INT DEFAULT 0;

ALTER TABLE schedule.demo
    ADD COLUMN max_participants INT DEFAULT 2;

COMMIT;