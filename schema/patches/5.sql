BEGIN;

ALTER TABLE public.member
    ADD COLUMN eol_state SMALLINT DEFAULT 0;

COMMENT ON COLUMN public.member.eol_state
    IS 'The end-of-life status of this profile. 0 is none, 10 deactivated, 20 archived. 1, 2, 3 are pending deactivated, archived, deleted';

ALTER TABLE public.member
    ADD COLUMN eol_requested_at TIMESTAMPTZ NULL DEFAULT NULL;

COMMENT ON COLUMN public.member.eol_requested_at
    IS 'The time that this member''s profile EOL was requested.';

COMMIT;
