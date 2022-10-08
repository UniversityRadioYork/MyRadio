BEGIN;

ALTER TABLE public.l_presenterstatus
    ADD COLUMN archived BOOLEAN DEFAULT 'f';

COMMIT;
