BEGIN;

ALTER TABLE sis2.member_signin
    ALTER COLUMN location
        DROP NOT NULL;

UPDATE myradio.schema
    SET value = 16
    WHERE attr='version';

COMMIT;
