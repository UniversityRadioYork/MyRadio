BEGIN;
ALTER TABLE member
    ADD nname character varying(255);
UPDATE myradio.schema
SET value = 19
WHERE attr='version';
COMMIT;