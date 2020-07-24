BEGIN;

-- noinspection SqlWithoutWhere
DELETE FROM public.sso_session;

ALTER TABLE public.sso_session
    ALTER COLUMN data
        TYPE JSONB;

COMMIT;
