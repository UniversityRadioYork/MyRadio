BEGIN;

ALTER TABLE public.officer
    ADD COLUMN num_places INT DEFAULT 1;

COMMIT;
