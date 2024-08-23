BEGIN;
ALTER TABLE member
    ADD nname character varying(255);
COMMIT;