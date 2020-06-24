BEGIN;

ALTER TABLE schedule.show_season_timeslot
    ADD COLUMN cancelled_at TIMESTAMPTZ NULL DEFAULT NULL;

INSERT INTO metadata.metadata_key
    VALUES (
            18,
            'cancel-reason',
            false,
            'Reason for Timeslot cancellation',
            300,
            'Reasons for Timeslot cancellation',
            false
        );

COMMIT;