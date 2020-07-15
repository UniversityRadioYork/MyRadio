BEGIN;

ALTER TABLE public.l_presenterstatus
    ALTER COLUMN can_award
        TYPE INTEGER[]
        USING ARRAY[can_award],
    ALTER COLUMN depends
        TYPE INTEGER[]
        USING ARRAY[depends];

COMMIT;